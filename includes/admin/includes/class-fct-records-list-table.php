<?php

/**
 * Fiscaat Records List Table class
 *
 * @package Fiscaat
 * @subpackage List_Table
 * @since 0.0.7
 * @access private
 */

class FCT_Records_List_Table extends FCT_Posts_List_Table {

	/**
	 * Holds the parent account id when displaying account records
	 *
	 * @since 0.0.8
	 * @var int|bool
	 * @access protected
	 */
	var $account_display = false;

	/**
	 * Holds the displayed debit and credit record amounts
	 *
	 * @since 0.0.8
	 * @var array
	 * @access protected
	 */
	var $amounts = array();

	/**
	 * Constructs the posts list table
	 * 
	 * @param array $args
	 */
	function __construct( $args = array() ) {
		parent::__construct( array(
			'plural'   => 'records',
			'singular' => 'record',
			'screen'   => isset( $args['screen'] ) ? $args['screen'] : null,
		) );

		// Displaying account records
		if ( isset( $_GET['fct_account_id'] ) && ! empty( $_GET['fct_account_id'] ) )
			$this->account_display = $_GET['fct_account_id'];

		// Setup amounts counter
		$this->amounts = array( 
			fct_get_debit_record_type_id()  => array(), 
			fct_get_credit_record_type_id() => array() 
		);

		// Single row data
		add_action( 'fct_admin_records_start_row', array( $this, '_start_or_end_row' ) );
		add_action( 'fct_admin_records_end_row',   array( $this, '_start_or_end_row' ) );
		add_action( 'fct_admin_records_total_row', array( $this, '_total_row'        ) );

		// Single post-new row data
		add_action( 'fct_admin_new_records_row',   array( $this, '_new_row'          ) );
	}

	/**
	 * Setup posts query and query vars
	 *
	 * @since 0.0.8
	 * 
	 * @todo Create own version
	 */
	function prepare_items() {

		/**
		 * Various actions: view, edit, post
		 */
		
		parent::prepare_items();
	}

	/**
	 * Return post status views
	 *
	 * Use {@link fct_count_posts()} when displaying account's records for it
	 * enables counting posts by parent.
	 * Additionally append the account id query arg to the views's urls.
	 *
	 * @since 0.0.8
	 *
	 * @uses fct_count_posts()
	 * @return array Views
	 */
	function get_views() {
		global $locked_post_status, $avail_post_stati;

		$post_type = $this->screen->post_type;

		if ( ! empty( $locked_post_status ) )
			return array();

		if ( $this->account_display ) {
			$num_posts = fct_count_posts( array( 'type' => $post_type, 'perm' => 'readable', 'parent' => $this->account_display ) );
			$parent    = '&fct_account_id=' . $this->account_display;
		} else {
			$num_posts = wp_count_posts( $post_type, 'readable' );
			$parent    = '';
		}

		$status_links  = array();
		$class         = '';
		$total_posts   = array_sum( (array) $num_posts );

		// Subtract post types that are not included in the admin all list.
		foreach ( get_post_stati( array( 'show_in_admin_all_list' => false ) ) as $state ) {
			$total_posts -= $num_posts->$state;
		}

		$class = empty( $class ) && empty( $_REQUEST['post_status'] ) && empty( $_REQUEST['show_sticky'] ) ? ' class="current"' : '';
		$status_links['all'] = "<a href='edit.php?post_type=$post_type{$parent}'$class>" . sprintf( _nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $total_posts, 'posts' ), number_format_i18n( $total_posts ) ) . '</a>';

		foreach ( get_post_stati( array( 'show_in_admin_status_list' => true ), 'objects' ) as $status ) {
			$class = '';

			$status_name = $status->name;

			if ( ! in_array( $status_name, $avail_post_stati ) )
				continue;

			if ( empty( $num_posts->$status_name ) )
				continue;

			if ( isset( $_REQUEST['post_status'] ) && $status_name == $_REQUEST['post_status'] )
				$class = ' class="current"';

			$status_links[$status_name] = "<a href='edit.php?post_status=$status_name&amp;post_type=$post_type{$parent}'$class>" . sprintf( translate_nooped_plural( $status->label_count, $num_posts->$status_name ), number_format_i18n( $num_posts->$status_name ) ) . '</a>';
		}

		return apply_filters( "fct_admin_get_{$this->_args['plural']}_views", $status_links );
	}

	/**
	 * Return dedicated bulk actions
	 *
	 * @since 0.0.8
	 * 
	 * @return array Bulk actions
	 */
	function _get_bulk_actions() {
		return array();
	}

	/**
	 * Return table classes. Mode aware
	 *
	 * @since 0.0.8
	 * 
	 * @return array Classes
	 */
	function get_table_classes() {
		$classes = array( 'widefat', 'fixed', 'posts', $this->_args['plural'] );

		if ( ! fct_admin_is_view_records() ) {
			$classes[] = fct_admin_get_records_mode();
		}

		return $classes;
	}

	/**
	 * Return dedicated record columns
	 *
	 * @since 0.0.8
	 * 
	 * @return array Columns
	 */
	function _get_columns() {
		$columns = array(
			'cb'                           => '<input type="checkbox" />',
			'fct_record_post_date'         => _x( 'Inserted', 'column name', 'fiscaat' ),
			'author'                       => __( 'Author' ),
			'fct_record_year'              => _x( 'Year',   'column name',   'fiscaat' ),
			'fct_record_account_ledger_id' => _x( 'No.',    'column name',   'fiscaat' ),
			'fct_record_account'           => __( 'Account',                 'fiscaat' ),
			'fct_record_date'              => __( 'Date' ),
			'fct_record_description'       => __( 'Description',             'fiscaat' ),
			'fct_record_offset_account'    => __( 'Offset Account',          'fiscaat' ),
			'fct_record_amount'            => _x( 'Amount', 'column name',   'fiscaat' ),
		);

		// Remove rows in edit/new mode
		if ( ! fct_admin_is_view_records() ) {
			unset( 
				$columns['cb'], 
				$columns['fct_record_post_date'],
				$columns['author'],
				$columns['fct_record_year']
			);
		}

		return $columns;
	}

	/**
	 * Return which columns are sortable
	 *
	 * @since 0.0.8
	 *
	 * @return array Sortable columns as array( column => sort key )
	 */
	function _get_sortable_columns() {
		return array(
			'fct_record_post_date'         => array( 'date',        true ),
			'fct_record_date'              => array( 'record_date', true ),

			// @todo Fix sorting by account ledger id. 
			// @see Fiscaat_Records_Admin::filter_post_rows()
			// 'fct_record_account_ledger_id' => 'record_account_ledger_id',

			'fct_record_account'           => 'parent',
			'fct_record_offset_account'    => 'record_offset_account',
			'fct_record_amount'            => 'record_amount',
		);
	}

	/**
	 * Return columns that are hidden by default
	 *
	 * @since 0.0.8
	 * 
	 * @return array Hidden columns
	 */
	function _get_hidden_columns( $columns ) {

		// Hide columns on view page to keep it clean
		if ( fct_admin_is_view_records() ) {
			$columns[] = 'author';
			$columns[] = 'fct_record_year';
		}

		return $columns;
	}

	/** Display Rows ******************************************************/

	/**
	 * Generate the table navigation above or below the table
	 *
	 * When editing or creating records enclose the list table in
	 * a <form> element with method=post to enable proper submitting.
	 *
	 * @since 0.0.8
	 * 
	 * @uses fct_admin_is_view_records()
	 * @uses do_action() Calls 'fct_admin_bottom_posts_insert_form's
	 * @uses do_action() Calls 'fct_admin_top_posts_insert_form'
	 */
	function display_tablenav( $which ) {
		if ( 'top' == $which ) {
			wp_nonce_field( 'bulk-' . $this->_args['plural'] );
		}

		// Close posts-insert form before bottom tablenav
		if ( 'bottom' == $which && ! fct_admin_is_view_records() ) {
?>
	<?php do_action( 'fct_admin_bottom_posts_insert_form' ); ?>

</form><!-- #posts-insert -->
<form id="posts-filter2" action="" method="get">

	<input type="hidden" name="page" class="post_page" value="<?php echo ! empty($_REQUEST['page']) ? esc_attr($_REQUEST['page']) : 'fct-records'; ?>" />
	<input type="hidden" name="post_status" class="post_status_page" value="<?php echo ! empty($_REQUEST['post_status']) ? esc_attr($_REQUEST['post_status']) : ''; ?>" />
	<?php wp_nonce_field( 'bulk-' . $this->_args['plural'] ); ?>
<?php 
		}

		// Display tablenav
?>
	<div class="tablenav <?php echo esc_attr( $which ); ?>">

<?php if ( $this->has_bulk_actions() ) : ?>
		<div class="alignleft actions bulkactions">
			<?php $this->bulk_actions(); ?>
		</div>
<?php endif; 

		$this->extra_tablenav( $which );
		$this->pagination( $which );
?>

		<br class="clear" />
	</div>
<?php

		// Open posts-insert form after top tablenav
		if ( 'top' == $which && ! fct_admin_is_view_records() ) { 
?>
</form><!-- #posts-filter -->
<form id="posts-insert" action="" method="post">

    <?php do_action( 'fct_admin_top_posts_insert_form' ); ?> <?php         }
}

	/**
	 * Generate the <tbody> part of the table
	 *
	 * @since 0.0.8
	 *
	 * @uses fct_admin_is_view_records()
	 * @uses fct_admin_is_edit_records()
	 * @uses fct_admin_is_new_records()
	 * @uses FCT_Records_List_Table::display_rows()
	 * @uses FCT_Records_List_Table::display_edit_rows()
	 * @uses FCT_Records_List_Table::display_new_rows()
	 */
	function display_rows_or_placeholder() {

		// Display edit mode, not when displaying account
		if ( fct_admin_is_edit_records() && $this->has_items() && ! $this->account_display ) {
			$this->display_edit_rows();

		// Display post-new mode
		} elseif ( fct_admin_is_new_records() ) {
			$this->display_new_rows();

		// Display rows when present or displaying account
		} elseif ( fct_admin_is_view_records() && ( $this->has_items() || $this->account_display ) ) {
			$this->display_rows();

		// Placeholder
		} else {
			echo '<tr class="no-items"><td class="colspanchange" colspan="' . $this->get_column_count() . '">';
			$this->no_items();
			echo '</td></tr>';
		}
	}

	/** Edit Rows *********************************************************/

	/**
	 * Display post-new rows
	 *
	 * @since 0.0.8
	 * 
	 * @uses FCT_Records_List_Table::_display_rows()
	 * @uses FCT_Records_List_Table::display_helper_row()
	 * @param array $posts Posts
	 * @param integer $level Depth
	 */
	function display_edit_rows( $posts = array(), $level = 0 ) {
		global $wp_query;

		if ( empty( $posts ) ) {
			$posts = $wp_query->posts;
		}

		add_filter( 'the_title', 'esc_html' );

		$this->_display_rows( $posts, $level );

		// Total sum row
		$this->display_helper_row( 'total' );
	}

	/** New Rows **********************************************************/

	/**
	 * Display post-new rows
	 *
	 * @since 0.0.8
	 *
	 * @uses FCT_Records_List_Table::single_new_row()
	 * @uses FCT_Records_List_Table::display_helper_row()
	 */
	function display_new_rows() {

		// Start with 10 empty rows
		for ( $i = 0; $i < 10; $i++ ) {
			$this->single_new_row();
		}

		// Total sum row
		$this->display_helper_row( 'total' );
	}

	/**
	 * Display single post-new row
	 *
	 * @since 0.0.8
	 *
	 * @uses do_action() Calls 'fct_admin_new_records_row' with the column name
	 */
	function single_new_row() {
		$alternate =& $this->alternate;
		$alternate = 'alternate' == $alternate ? '' : 'alternate';
		$classes = $alternate . ' iedit new-records-row';

		list( $columns, $hidden ) = $this->get_column_info(); ?>
		<tr class="<?php echo $classes; ?>" valign="top">
			
			<?php foreach ( $columns as $column_name => $column_display_name ) :
				$class = " class=\"$column_name column-$column_name\"";
				$style = '';
	
				if ( in_array( $column_name, $hidden ) )
					$style = ' style="display:none;"';

				$attributes = "$class$style"; 

				$el1 = 'cb' == $column_name ? 'th scope="row" class="check-column"' : "td $attributes";
				$el2 = 'cb' == $column_name ? 'th' : 'td';

				echo "<$el1>";
				do_action( "fct_admin_new_records_row", $column_name );
				echo "</$el2>";
			endforeach; ?>

		</tr>
		<?php
	}

	/**
	 * Display dedicated post-new column content
	 *
	 * @since 0.0.8
	 *
	 * @uses fct_ledger_dropdown()
	 * @uses fct_account_dropdown()
	 * @param string $column_name Column name
	 */
	function _new_row( $column_name ) {

		// Check column name
		switch ( $column_name ) {

			// Record date
			case 'fct_record_date': ?>

				<input name="records[date][]" type="text" class="fct_record_date medium-text" value="" />

				<?php
				break;

			// Record account ledger id
			case 'fct_record_account_ledger_id' :
				fct_ledger_dropdown( array(
					'select_name' => 'records[ledger_account_id][]', 
					'class'       => 'fct_record_ledger_id',
					'show_none'   => '&mdash;',
					'tabindex'    => '',
				) );
				break;

			// Record account
			case 'fct_record_account' :
				fct_account_dropdown( array(
					'select_name' => 'records[account_id][]',
					'class'       => 'fct_record_account_id',
					'show_none'   => __( '&mdash; No Account &mdash;', 'fiscaat' ),
					'tabindex'    => '',
				) );
				break;

			// Record content
			case 'fct_record_description' : ?>

				<textarea name="records[description][]" class="fct_record_description" rows="1" ></textarea>

				<?php
				break;

			// Record offset account
			case 'fct_record_offset_account' : ?>

				<input name="records[offset_account][]" type="text" class="fct_record_offset_account" value="" />

				<?php
				break;

			// Record amount
			case 'fct_record_amount' : ?>

				<input name="records[amount][debit][]"  class="fct_record_debit_amount small-text"  type="text" value="" />
				<input name="records[amount][credit][]" class="fct_record_credit_amount small-text" type="text" value="" />

				<?php
				break;
		}
	}

	/** View Rows *********************************************************/

	/**
	 * Display post rows
	 *
	 * When there are items, show account (start, end, total) rows
	 *
	 * @since 0.0.8
	 * 
	 * @uses FCT_Records_List_Table::display_helper_row()
	 * @param array $posts Posts
	 * @param integer $level Depth
	 */
	function display_rows( $posts = array(), $level = 0 ) {
		global $wp_query;

		if ( empty( $posts ) ) {
			$posts = $wp_query->posts;
		}

		add_filter( 'the_title', 'esc_html' );

		// Start account row. Revenue accounts have no starting value
		if ( $this->account_display && fct_get_revenue_account_type_id() != fct_get_account_type( $this->account_display ) ) {
			$this->display_helper_row( 'start' );
		}

		$this->_display_rows( $posts, $level );

		// End account row
		if ( $this->account_display ) {
			$this->display_helper_row( 'end' );
		}

		// Total sum row
		$this->display_helper_row( 'total' );
	}

	/**
	 * Display dedicated column content
	 *
	 * @since 0.0.8
	 *
	 * @uses fct_get_record_account_id()
	 * @uses get_the_date()
	 * @uses fct_account_ledger_id()
	 * @uses fct_get_account_title()
	 * @uses fct_record_excerpt()
	 * @uses fct_record_offset_account()
	 * @uses fct_get_record_amount()
	 * @uses fct_get_record_type()
	 * @uses fct_get_debit_record_type()
	 * @uses fct_get_credit_record_type()
	 * @uses fct_currency_format()
	 * @uses fct_get_record_year_id()
	 * @uses fct_get_account_year_id()
	 * @uses fct_get_year_title()
	 * @param string $column_name Column name
	 * @param int $record_id Record ID
	 */
	function _column_content( $column_name, $record_id ) {
		$account_id = fct_get_record_account_id( $record_id );

		// Check column name
		switch ( $column_name ) {

			// Record post date
			case 'fct_record_post_date':
				$date = get_post_time( 'U', $year_id );
				echo '<abbr title="' . mysql2date( __( 'Y/m/d g:i:s A' ), $date ) . '">' . apply_filters( 'post_date_column_time', mysql2date( 'Y/m/d', $date ), $record_id, $column_name, 'list' ) . '</abbr>';
				break;

			// Record date
			case 'fct_record_date':
				$date = fct_get_record_date( $record_id );
				echo '<abbr title="' . mysql2date( __( 'Y/m/d g:i:s A' ), $date ) . '">' . apply_filters( 'post_date_column_time', mysql2date( 'Y/m/d', $date ), $record_id, $column_name, 'list' ) . '</abbr>';
				break;

			// Record account ledger id
			case 'fct_record_account_ledger_id' :
				if ( ! empty( $account_id ) ) {
					fct_account_ledger_id( $account_id, true );
				}
				break;

			// Record account
			case 'fct_record_account' :
				if ( ! empty( $account_id ) ) {
					$account_title = fct_get_account_title( $account_id );
					if ( empty( $account_title ) ) {
						$account_title = __( 'No Account', 'fiscaat' );
					}
					echo $account_title;

				} else {
					_e( 'No Account', 'fiscaat' );
				}
				break;

			// Record content
			case 'fct_record_description' :
				fct_record_excerpt( $record_id );
				break;

			// Record offset account
			case 'fct_record_offset_account' :
				fct_record_offset_account( $record_id );
				break;

			// Record amount
			case 'fct_record_amount' :
				$value = fct_get_record_amount( $record_id ); // Always float
				$rtype = fct_get_record_type(   $record_id );
				$this->amounts[ $rtype ][] = $value; ?>

				<input id="fct_record_<?php echo $record_id; ?>_debit_amount"  class="fct_record_debit_amount small-text"  type="text" value="<?php if ( fct_get_debit_record_type_id()  == $rtype ){ fct_currency_format( $value ); } ?>" disabled="disabled" />
				<input id="fct_record_<?php echo $record_id; ?>_credit_amount" class="fct_record_credit_amount small-text" type="text" value="<?php if ( fct_get_credit_record_type_id() == $rtype ){ fct_currency_format( $value ); } ?>" disabled="disabled" />

				<?php
				break;

			// Record year
			case 'fct_record_year' :
				$record_year_id  = fct_get_record_year_id(  $record_id  );
				$account_year_id = fct_get_account_year_id( $account_id );

				if ( ! empty( $record_year_id ) ) {
					$year_title = fct_get_year_title( $record_year_id );
					if ( empty( $year_title ) ) {
						$year_title = __( 'No Year', 'fiscaat' );
					}

					// Alert capable users of record year mismatch
					if ( $record_year_id != $account_year_id ) {
						if ( current_user_can( 'edit_others_records' ) ) {
							$year_title .= ' <div class="attention">' . __( '(Mismatch)', 'fiscaat' ) . '</div>';
						}
					}
					echo $year_title;

				} else {
					_e( 'No Year', 'fiscaat' );
				}
				break;
		}
	}

	/** Helper Rows *******************************************************/

	/**
	 * Display records's helper row
	 * 
	 * @since 0.0.8
	 *
	 * @uses do_action() Calls 'fct_admin_records_{$which}_row'
	 * @param string $which The row name
	 */
	function display_helper_row( $which = '' ) {

		// Bail if no row name given
		$which = esc_attr( esc_html( $which ) );
		if ( empty( $which ) )
			return;

		$alternate =& $this->alternate;
		$alternate = 'alternate' == $alternate ? '' : 'alternate';
		$classes   = "{$alternate} iedit records-{$which}-row";

		list( $columns, $hidden ) = $this->get_column_info(); ?>
		<tr id="fct-records-<?php echo $which; ?>-row" class="<?php echo $classes; ?>" valign="top">
			
			<?php foreach ( $columns as $column_name => $column_display_name ) :
				$class = " class=\"$column_name column-$column_name\"";
				$style = '';
	
				if ( in_array( $column_name, $hidden ) )
					$style = ' style="display:none;"';

				$attributes = "$class$style"; 

				$el1 = 'cb' == $column_name ? 'th scope="row" class="check-column"' : "td $attributes";
				$el2 = 'cb' == $column_name ? 'th' : 'td';

				echo "<$el1>";
				do_action( "fct_admin_records_{$which}_row", $column_name );
				echo "</$el2>";
			endforeach; ?>

		</tr>
		<?php
	}

	/**
	 * Display contents of either an account's start or end row
	 * 
	 * @since 0.0.8
	 *
	 * @uses fct_get_account_id()
	 * @uses fct_get_capital_account_type_id()
	 * @uses fct_get_account_type()
	 * @uses fct_get_account_start_value()
	 * @uses fct_get_account_end_value()
	 * @uses fct_get_debit_record_type_id()
	 * @uses fct_get_credit_record_type_id()
	 * @uses fct_currency_format()
	 * @param string $column Column name
	 */
	function _start_or_end_row( $column ) {

		// Bail if no valid parent account id
		if ( ! $account_id = fct_get_account_id( $this->account_display ) )
			return;

		// Is this the start row?
		$start = false !== strpos( current_filter(), 'start' );

		// Check column name
		switch ( $column ) {

			// Row title
			case 'fct_record_description' :
				switch ( fct_get_account_type( $account_id ) ) {
					case fct_get_capital_account_type_id() :
						if ( $start ) {
							_e( 'Start Balance', 'fiscaat' );
						} else {
							_e( 'End Balance',   'fiscaat' );
						}
						break;

					case fct_get_revenue_account_type_id() :
						// Revenue accounts have no starting value
						if ( ! $start ) 
							_e( 'To Income Statement', 'fiscaat' );
						break;
				}
				break;

			// Row account amount
			case 'fct_record_amount' :
				$_row  = $start ? 'start' : 'end';
				$value = call_user_func_array( "fct_get_account_{$_row}_value", array( 'account_id' => $account_id ) ); 
				$this->amounts[ $value > 0 ? fct_get_debit_record_type_id() : fct_get_credit_record_type_id() ][] = abs( $value ); ?>

				<input id="fct_account_<?php echo $_row; ?>_value_debit"  class="fct_record_debit_amount small-text"  type="text" value="<?php if ( $value > 0 ) { fct_currency_format( abs( $value ) ); } ?>" disabled="disabled" />
				<input id="fct_account_<?php echo $_row; ?>_value_credit" class="fct_record_credit_amount small-text" type="text" value="<?php if ( $value < 0 ) { fct_currency_format( abs( $value ) ); } ?>" disabled="disabled" />

				<?php
				break;
		}
	}

	/**
	 * Display contents of the records's total row
	 * 
	 * @since 0.0.8
	 *
	 * @uses fct_get_debit_record_type_id()
	 * @uses fct_get_credit_record_type_id()
	 * @uses fct_currency_format()
	 * @param string $column Column name
	 */
	function _total_row( $column ) {

		// Check column name
		switch ( $column ) {

			// Row title
			case 'fct_record_description' :
				$total_title = _x( 'Total', 'Sum of all records', 'fiscaat' );

				// Alert capable users of debit credit mismatch
				if ( array_sum( $this->amounts[ fct_get_debit_record_type_id() ] ) != array_sum( $this->amounts[ fct_get_credit_record_type_id() ] ) ) {
					$total_title .= '<div class="attention">' . __( '(Mismatch)', 'fiscaat' ) . '</div>';
				}

				echo $total_title;
				break;

			// Total amount
			case 'fct_record_amount' : ?>

				<input id="fct_records_debit_total"  class="fct_record_debit_amount fct_record_total small-text"  type="text" value="<?php fct_currency_format( array_sum( $this->amounts[ fct_get_debit_record_type_id()  ] ) ); ?>" disabled="disabled" />
				<input id="fct_records_credit_total" class="fct_record_credit_amount fct_record_total small-text" type="text" value="<?php fct_currency_format( array_sum( $this->amounts[ fct_get_credit_record_type_id() ] ) ); ?>" disabled="disabled" />

				<?php
				break;
		}
	}
}
