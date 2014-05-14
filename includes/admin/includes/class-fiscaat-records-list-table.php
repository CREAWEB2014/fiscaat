<?php

/**
 * Records List Table class
 *
 * @package Fiscaat
 * @subpackage List_Table
 * @access private
 */

class Fiscaat_Records_List_Table extends WP_List_Table {

	function __construct() {
		global $post_type_object, $wpdb;

		$post_type = get_current_screen()->post_type;
		$post_type_object = get_post_type_object( $post_type );

		parent::__construct( array(
			'plural' => 'records',
		) );

	}

	function ajax_user_can() {
		global $post_type_object;

		return current_user_can( $post_type_object->cap->edit_posts );
	}

	function prepare_items() {
		global $post_type_object, $avail_post_stati, $wp_query, $per_page;

		if ( apply_filters( 'fct_records_list_table_custom_query', false ) ) {
			$avail_post_stati = apply_filters( 'fct_records_list_table_items', array() );

			// Add missing fiscaat rows
			if ( $end = end( $wp_query->posts ) && ! isset( $end->fct_row ) )
				$wp_query->posts = apply_filters( 'the_posts', $wp_query->posts, $wp_query );

		} else {
			$avail_post_stati = wp_edit_posts_query();
		}

		$total_items = $wp_query->found_posts;

		$post_type = $post_type_object->name;
		$per_page = $this->get_items_per_page( 'edit_' . $post_type . '_per_page' );
 		$per_page = apply_filters( 'edit_posts_per_page', $per_page, $post_type );

		$total_pages = $wp_query->max_num_pages;

		$this->is_trash = isset( $_REQUEST['post_status'] ) && $_REQUEST['post_status'] == 'trash';

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'total_pages' => $total_pages,
			'per_page' => $per_page
		) );
	}

	function has_items() {
		return have_posts();
	}

	function no_items() {
		global $post_type_object;
		echo $post_type_object->labels->not_found;
	}

	function get_views() {
		return apply_filters( 'fct_records_list_table_views', array() );
	}

	function get_bulk_actions() {
		return apply_filters( 'fct_records_list_table_bulk_actions', array() );
	}

	function extra_tablenav( $which ) {
	?>
		<div class="alignleft actions">
			<?php do_action( 'fct_records_list_table_tablenav', $which ); ?>
		</div>
	<?php
	}

	function current_action() {
		return apply_filters( 'fct_records_list_table_action', parent::current_action() );
	}

	function pagination( $which ) {
		do_action( 'fct_records_list_table_pagination', $which );
	}

	function get_table_classes() {
		$classes = array( 'widefat', 'fixed', 'posts', 'records' );
		return apply_filters( 'fct_records_list_table_class', $classes );
	}

	function get_columns() {
		$post_type = fct_get_record_post_type();

		$posts_columns = array();

		$posts_columns['cb']                               = '<input type="checkbox" />';
		$posts_columns['fct_record_created']           = __( 'Date', 'fiscaat' );
		/* translators: manage records column name */
		$posts_columns['fct_record_account_ledger_id'] = _x( 'No.', 'ledger id column name', 'fiscaat' );
		/* translators: manage records column name */
		$posts_columns['fct_record_account']           = _x( 'Account', 'column name', 'fiscaat' );
		/* translators: manage records column name */
		$posts_columns['fct_record_description']       = _x( 'Description', 'column name', 'fiscaat' );
		$posts_columns['fct_record_offset_account']    = __( 'Offset Account', 'fiscaat' );
		$posts_columns['fct_record_value']             = __( 'Debit/Credit', 'fiscaat' );

		if ( post_type_supports( $post_type, 'author' ) )
			$posts_columns['author'] = __( 'Author', 'fiscaat' );

		$posts_columns = apply_filters( "fct_records_posts_columns", $posts_columns );

		return $posts_columns;
	}

	function get_sortable_columns() {
		return apply_filters( 'fct_records_sortable_columns', array() );
	}

	function display_rows( $posts = array() ) {
		global $wp_query, $post_type_object, $per_page;

		if ( empty( $posts ) )
			$posts = $wp_query->posts;

		add_filter( 'the_title', 'esc_html' );

		$this->_display_rows( $posts );
	}

	function _display_rows( $posts ) {
		global $post;

		foreach ( $posts as $post )
			$this->single_row( $post );
	}

	function single_row( $a_post, $level = 0 ) {
		global $post;
		static $alternate;

		$global_post = $post;
		$post = $a_post;
		setup_postdata( $post );

		$edit_link = get_edit_post_link( $post->ID );
		$title = _draft_or_post_title();
		$post_type_object = get_post_type_object( $post->post_type );
		$can_edit_post = current_user_can( $post_type_object->cap->edit_post, $post->ID );

		$alternate = 'alternate' == $alternate ? '' : 'alternate';
		$classes = $alternate . ' iedit author-' . ( get_current_user_id() == $post->post_author ? 'self' : 'other' );
	?>
		<tr id="post-<?php echo $post->ID; ?>" class="<?php echo implode( ' ', get_post_class( $classes, $post->ID ) ); ?>" valign="top">
	<?php

		list( $columns, $hidden ) = $this->get_column_info();

		foreach ( $columns as $column_name => $column_display_name ) {
			$class = "class=\"$column_name column-$column_name\"";

			$style = '';
			if ( in_array( $column_name, $hidden ) )
				$style = ' style="display:none;"';

			$attributes = "$class$style";

			if ( ! isset( $post->fct_row ) || ! $post->fct_row ) :

				switch ( $column_name ) {

				case 'cb':
				?>

					<th scope="row" class="check-column"><?php if ( $can_edit_post ) { ?><input type="checkbox" name="post[]" value="<?php the_ID(); ?>" /><?php } ?></th>

				<?php
				break;

				case 'fct_record_created':
					$date = $post->post_date;

					$this->input_td( array(
						'name'     => 'created', 
						'value'    => fct_convert_date( $date, 'Y-m-d', true ),
						'disabled' => true,
					), $attributes );
				break;

				case 'fct_record_account_ledger_id':
				?>

					<td <?php echo $attributes; ?>>
						<?php fct_account_ledger_dropdown( array(
							'select_name' => sprintf( 'fct_new_record[ledger_id][%s]', ! empty( $post->ID ) ? $post->ID : '' ),
							'select_id'   => 'fct_new_record_ledger_id', // Unique?
							'class'       => 'fct_new_record_ledger_id',
							'show_none'   => '&mdash;',
							'selected'    => $post->post_parent,
						)); ?>
					</td>

				<?php
				break;

				case 'fct_record_account' :
				?>

					<td <?php echo $attributes; ?>>
						<?php fct_account_dropdown( array(
							'select_name' =>  sprintf( 'fct_new_record[account_id][]', ! empty( $post->ID ) ? $post->ID : '' ),
							'select_id'   => 'fct_new_record_account_id', // Unique?
							'class'       => 'fct_new_record_account_id',
							'show_none'   => __('&mdash; No Account &mdash;', 'fiscaat'),
							'selected'    => $post->post_parent,
						) ); ?>
					</td>

				<?php
				break;

				case 'fct_record_description':
					$this->input_td( array( 
						'type'  => 'textarea', 
						'name'  => 'description', 
						'value' => $post->post_content,
						'style' => 'rows="1" ',
					), $attributes );
				break;

				case 'fct_record_offset_account' :
					$this->input_td( array( 
						'type'  => 'text', 
						'name'  => 'offset_account', 
						'value' => '' //sanitize_text_field( $post->offset_account ),
					), $attributes );
				break;

				case 'fct_record_value' :
				?>

					<td <?php echo $attributes; ?>>
						<input name="fct_new_record[debit][]"  class="fct_record_debit_value small-text"  type="text" value="<?php if ( fct_get_debit_record_type_id()  == $post->fct_value_type ){ fct_currency_format( $post->fct_value ); } ?>" disabled="disabled" tabindex="<?php fct_tab_index(); ?>" />
						<input name="fct_new_record[credit][]" class="fct_record_credit_value small-text" type="text" value="<?php if ( fct_get_credit_record_type_id() == $post->fct_value_type ){ fct_currency_format( $post->fct_value ); } ?>" disabled="disabled" tabindex="<?php fct_tab_index(); ?>" />
					</td>

				<?php
				break;

				default:
				?>

					<td <?php echo $attributes; ?>>
						<?php do_action( "fct_records_list_table_custom_column", $column_name, $post->ID ); ?>
					</td>

				<?php
				break;

				}

			// Fiscaat total row
			elseif ( isset( $post->fct_row_total ) && $post->fct_row_total ) : 

				switch ( $column_name ) {

				case 'fct_record_value':
				?>

					<td <?php echo $attributes; ?>>
						<input id="fct_records_debit_total"  class="fct_record_debit_value fct_record_total small-text"  type="text" value="<?php fct_currency_format( $post->fct_debit_total ); ?>" disabled="disabled" />
						<input id="fct_records_credit_total" class="fct_record_credit_value fct_record_total small-text" type="text" value="<?php fct_currency_format( $post->fct_credit_total ); ?>" disabled="disabled" />
					</td>
	
				<?php
				break;

				default:
				?>

					<td <?php echo $attributes; ?>>
						<?php do_action( "fct_records_list_table_total_column", $column_name, $post->ID ); ?>
					</td>

				<?php
				break;

				}

			endif;
		}
	?>
		</tr>
	<?php
		$post = $global_post;
	}

	/**
	 * Output list table td with input field
	 */
	function input_td( $args = array(), $attributes = '' ) {
		global $post;

		$default = array(
			'type' => 'text', 'value' => '', 'name' => '', 'style' => '',
			'disabled' => false, 'checked' => false, 'selected' => false,
			);
		$args = wp_parse_args( $args, $default );
		extract( $args );

		// Require name attribute
		if ( empty( $name ) )
			return;

		$output = '';
		$class = sprintf( 'class="fct_new_record_%s" ',   esc_attr( $name ) );
		$name  = sprintf( 'name="fct_new_record[%s][%s]" ', esc_attr( $name ), ! empty( $post->ID ) ? $post->ID : '' );

		$checked = '';
		$selected = '';
		$disabled = $disabled ? 'disabled="disabled" ' : '';
		$tabindex = sprintf( 'tabindex="%s" ', fct_get_tab_index() );

		switch ( $type ) {
			case 'textarea':
				$value = esc_textarea( $value );
				$output = "<textarea $name$class$disabled$tabindex$style>$value</textarea>";
			break;

			case 'checkbox':
				$checked = checked( $checked ) .' ';
			case 'radio'   :
				$selected = selected( $selected ) .' ';
			case 'text'    :
			case 'hidden'  :
			default        :
				$type   = sprintf( 'type="%s" ',  esc_attr( $type  ) );
				$value  = sprintf( 'value="%s" ', esc_attr( $value ) );
				$output = "<input $type$name$class$value$disabled$checked$selected$tabindex$style />";
			break;
		}

		$output = apply_filters( 'fct_records_list_table_input_td', $output );

		echo "<td $attributes>$output</td>";
	}

	/**
	 * Outputs the hidden row displayed when inline editing
	 *
	 * @since 3.1.0
	 */
	function inline_edit() {
		return;
	}
}

?>
