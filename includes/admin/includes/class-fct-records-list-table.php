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
	 * Holds the debit and credit record amounts
	 *
	 * @since 0.0.8
	 * @var array
	 * @access protected
	 */
	var $amounts;

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
		$this->amounts = array( fct_get_debit_record_type_id() => array(), fct_get_credit_record_type_id() => array() );

		// Single row data
		add_action( 'fct_admin_records_start_row',  array( $this, '_start_or_end_row' ) );
		add_action( 'fct_admin_records_end_row',    array( $this, '_start_or_end_row' ) );
		add_action( 'fct_admin_records_total_row',  array( $this, '_total_row'        ) );
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
	 * Use {@link fct_count_posts()} when displaying account's records.
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

	function _get_bulk_actions() {
		return array();
	}

	/**
	 * Return dedicated record columns
	 *
	 * @since 0.0.8
	 * 
	 * @return array Columns
	 */
	function _get_columns() {
		return array(
			'cb'                           => '<input type="checkbox" />',
			'fct_record_created'           => __( 'Date' ),
			'fct_record_account_ledger_id' => _x( 'No.',            'Account number column name',        'fiscaat' ),
			'fct_record_account'           => __( 'Account',                                             'fiscaat' ),
			'fct_record_description'       => __( 'Description',                                         'fiscaat' ),
			'fct_record_offset_account'    => __( 'Offset Account',                                      'fiscaat' ),
			'fct_record_amount'            => _x( 'Amount',         'Amount column name (debit/credit)', 'fiscaat' ),
		);
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
			'fct_record_created'           => array( 'date', true ),
			'fct_record_account_ledger_id' => 'record_account_ledger_id',
			'fct_record_account'           => 'record_account',
			'fct_record_offset_account'    => 'record_offset_account',
			'fct_record_amount'            => 'record_amount',
		);
	}

	/**
	 * Display post rows
	 *
	 * When there are items, show account (start, end, total) rows
	 *
	 * @since 0.0.8
	 * 
	 * @param array $posts Found posts
	 * @param integer $level Depth
	 */
	function display_rows( $posts = array(), $level = 0 ) {
		global $wp_query;

		if ( empty( $posts ) ) {
			$posts = $wp_query->posts;
		}

		add_filter( 'the_title', 'esc_html' );

		// Start account row
		if ( $this->has_items() && $this->account_display )
			$this->_display_single_row( 'start' );

		$this->_display_rows( $posts, $level );

		// End account row
		if ( $this->has_items() && $this->account_display )
			$this->_display_single_row( 'end' );

		// Total sum row
		if ( $this->has_items() )
			$this->_display_single_row( 'total' );
	}

	/**
	 * Display account's records start row
	 * 
	 * @since 0.0.8
	 *
	 * @uses do_action() Calls 'fct_admin_records_{$row_name}_row'
	 * @param string $row_name Unique row name
	 */
	function _display_single_row( $row_name = '' ) {

		// Bail if no row name given
		$row_name = esc_attr( esc_html( $row_name ) );
		if ( empty( $row_name ) )
			return;

		// Revenue accounts have no starting value
		if ( 'start' == $row_name && fct_get_revenue_account_type_id() == fct_get_account_type( $this->account_display ) )
			return;

		$alternate =& $this->alternate;
		$alternate = 'alternate' == $alternate ? '' : 'alternate';
		$classes = $alternate . ' iedit records-row-' . $row_name;

		list( $columns, $hidden ) = $this->get_column_info(); ?>
		<tr id="fct-records-<?php echo $row_name; ?>-row" class="<?php echo $classes; ?>" valign="top">
			
			<?php foreach ( $columns as $column_name => $column_display_name ) :
				$class = " class=\"$column_name column-$column_name\"";
				$style = '';
	
				if ( in_array( $column_name, $hidden ) )
					$style = ' style="display:none;"';

				$attributes = "$class$style"; 

				$el1 = 'cb' == $column_name ? 'th scope="row" class="check-column"' : "td $attributes";
				$el2 = 'cb' == $column_name ? 'th' : 'td';

				echo "<$el1>";
				do_action( "fct_admin_records_{$row_name}_row", $column_name );
				echo "</$el2>";
			endforeach; ?>

		</tr>
		<?php
	}

	function _display_rows( $posts, $level = 0 ) {
		foreach ( $posts as $post )
			$this->single_row( $post, $level );
	}

	/**
	 * Display dedicated column content
	 *
	 * @since 0.0.8
	 *
	 * @uses fct_get_account_year_id()
	 * @uses fct_get_year_title()
	 * @uses fct_account_ledger_id()
	 * @uses fct_get_account_type()
	 * @uses fct_get_revenue_account_type_id()
	 * @uses fct_get_capital_account_type_id()
	 * @uses fct_account_record_count()
	 * @uses fct_currency_format()
	 * @uses fct_get_account_end_value()
	 * @param string $column_name Column name
	 * @param int $record_id Record ID
	 */
	function _column_content( $column_name, $record_id ) {
		$account_id = fct_get_record_account_id( $record_id );

		switch ( $column_name ) {
			case 'fct_record_created':
				echo get_the_date();
				break;

			case 'fct_record_account_ledger_id' :
				if ( ! empty( $account_id ) )
					fct_account_admin_records_link( $account_id, true );
				break;

			case 'fct_record_account' :
				if ( ! empty( $account_id ) ) {
					$account_title = fct_get_account_admin_records_link( $account_id );
					if ( empty( $account_title ) ) {
						$account_title = __( 'No Account', 'fiscaat' );
					}
					echo $account_title;

				} else {
					// _e( 'No Account', 'fiscaat' );
				}
				break;

			case 'fct_record_description' :
				fct_record_excerpt( $record_id );
				break;

			case 'fct_record_offset_account' :
				fct_record_offset_account( $record_id );
				break;

			case 'fct_record_amount' :
				$value = fct_get_record_amount( $record_id ); // Always float
				$rtype = fct_get_record_type(   $record_id );
				$this->amounts[ $rtype ][] = $value; ?>

				<input id="fct_record_<?php echo $record_id; ?>_debit_amount"  class="fct_record_debit_amount small-text"  type="text" value="<?php if ( fct_get_debit_record_type_id()  == $rtype ){ fct_currency_format( $value ); } ?>" disabled="disabled" />
				<input id="fct_record_<?php echo $record_id; ?>_credit_amount" class="fct_record_credit_amount small-text" type="text" value="<?php if ( fct_get_credit_record_type_id() == $rtype ){ fct_currency_format( $value ); } ?>" disabled="disabled" />

				<?php
				break;

			case 'fct_record_author' :
				fct_record_author_display_name( $record_id );
				break;

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
						if ( current_user_can( 'edit_others_records' ) || current_user_can( 'fiscaat' ) ) {
							$year_title .= '<div class="attention">' . __( '(Mismatch)', 'fiscaat' ) . '</div>';
						}
					}
					echo $year_title;

				} else {
					_e( 'No Year', 'fiscaat' );
				}
				break;
		}
	}

	/**
	 * Display contents of either an account's start or end row
	 * 
	 * @since 0.0.8
	 *
	 * @uses fct_get_account_id()
	 * @param string $column Column name
	 */
	function _start_or_end_row( $column ) {

		// Bail if no valid parent account id
		if ( ! $account_id = fct_get_account_id( $this->account_display ) )
			return;

		$start = strpos( current_filter(), 'start' );

		switch ( $column ) {
			case 'fct_record_created' :

				// Display date of start value
				if ( $start ) {
					$account = fct_get_account( $account_id );
					echo fct_convert_date( $account->post_date,    get_option( 'date_format' ), true );

				// Now
				} else {
					echo fct_convert_date( fct_get_current_time(), get_option( 'date_format' ), true );
				}
				break;

			case 'fct_record_description' :
				if ( fct_get_capital_account_type_id() == fct_get_account_type( $account_id ) ) {
					if ( $start ) {
						_e( 'Start Balance', 'fiscaat' );
					} else {
						_e( 'End Balance',   'fiscaat' );
					}

				// Revenue accounts have no start value
				} else {
					if ( ! $start ) 
						_e( 'To Income Statement', 'fiscaat' );
				}
				break;

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
	 * @param string $column Column name
	 * @param array $args
	 */
	function _total_row( $column ) {

		switch ( $column ) {
			case 'fct_record_description' :
				$total_title = _x( 'Total', 'Sum of all records', 'fiscaat' );

				// Alert capable users of debit credit mismatch
				if ( array_sum( $this->amounts[ fct_get_debit_record_type_id() ] ) != array_sum( $this->amounts[ fct_get_credit_record_type_id() ] ) ) {
					$total_title .= '<div class="attention">' . __( '(Mismatch)', 'fiscaat' ) . '</div>';
				}

				echo $total_title;
				break;

			case 'fct_record_amount' : ?>

				<input id="fct_records_debit_total"  class="fct_record_debit_amount fct_record_total small-text"  type="text" value="<?php fct_currency_format( array_sum( $this->amounts[ fct_get_debit_record_type_id()  ] ) ); ?>" disabled="disabled" />
				<input id="fct_records_credit_total" class="fct_record_credit_amount fct_record_total small-text" type="text" value="<?php fct_currency_format( array_sum( $this->amounts[ fct_get_credit_record_type_id() ] ) ); ?>" disabled="disabled" />

				<?php
				break;
		}
	}

}
