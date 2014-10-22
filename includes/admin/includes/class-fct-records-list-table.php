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
	 * Holds the period ID of the queried records
	 *
	 * @since 0.0.9
	 * @var int|bool
	 * @access protected
	 */
	protected $period_id = false;

	/**
	 * Holds the account ID when querying the account's records
	 *
	 * @since 0.0.8
	 * @var int|bool
	 * @access protected
	 */
	protected $account_id = false;

	/**
	 * Holds the displayed debit and credit record amounts
	 *
	 * @since 0.0.8
	 * @var array
	 * @access protected
	 */
	protected $amounts = array();

	/**
	 * Constructs the posts list table
	 *
	 * @param array $args
	 */
	public function __construct( $args = array() ) {
		parent::__construct( array(
			'plural'   => 'records',
			'singular' => 'record',
			'screen'   => isset( $args['screen'] ) ? $args['screen'] : null,
		) );

		// Set the period id
		if ( ! empty( $_REQUEST['fct_period_id'] ) ) {
			$this->period_id = (int) $_REQUEST['fct_period_id'];
		// Default to the current period
		} else {
			$this->period_id = fct_get_current_period_id();
		}

		// Set the account id when querying an account's records
		if ( ! empty( $_REQUEST['fct_account_id'] ) ) {
			$this->account_id = (int) $_REQUEST['fct_account_id'];
		}

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
	public function prepare_items() {

		/**
		 * Various actions: view, edit, post
		 */

		parent::prepare_items();
	}

	/**
	 * Return post status views
	 *
	 * Use {@link fct_count_posts()} when displaying account's records
	 * for it enables counting posts by parent. Additionally append the
	 * account id query arg to the views's urls.
	 *
	 * @since 0.0.8
	 *
	 * @uses fct_count_posts()
	 * @return array Views
	 */
	public function get_views() {
		global $locked_post_status, $avail_post_stati;

		$post_type = $this->screen->post_type;

		if ( ! empty( $locked_post_status ) )
			return array();

		// Account's record count
		if ( $this->account_id ) {
			$num_posts = fct_count_posts( array(
				'type'        => $post_type,
				'perm'        => 'readable',
				'post_parent' => $this->account_id,
			) );
			$parent = '&fct_account_id=' . $this->account_id;

		// Period's record count. Not querying all records
		} elseif ( $this->period_id ) {
			$num_posts = fct_count_posts( array(
				'type'      => $post_type,
				'perm'      => 'readable',
				'period_id' => $this->period_id,
			) );

		// Total records count. Never getting here since period is always set
		} else {
			$num_posts = wp_count_posts( $post_type, 'readable' );
		}

		$status_links  = array();
		$class         = '';
		$parent        = isset( $parent ) ? $parent : '';
		$total_posts   = array_sum( (array) $num_posts );

		// Prepend a link for the period's records when viewing a single account
		if ( $this->account_id ) {
			$status_links['period'] = "<a href=\"admin.php?page=fct-records&amp;fct_period_id={$this->period_id}\"$class>" . sprintf( _x( 'Period <span class="count">(%s)</span>', 'records', 'fiscaat' ), fct_get_period_record_count( $this->period_id ) ) . '</a>';
		}

		// Subtract post stati that are not included in the admin all list.
		foreach ( get_post_stati( array( 'show_in_admin_all_list' => false ) ) as $state ) {
			$total_posts -= $num_posts->$state;
		}

		$class = empty( $class ) && empty( $_REQUEST['post_status'] ) && empty( $_REQUEST['show_sticky'] ) ? ' class="current"' : '';
		$status_links['all'] = "<a href=\"admin.php?page=fct-records{$parent}\"$class>" . sprintf( _nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $total_posts, 'posts' ), number_format_i18n( $total_posts ) ) . '</a>';

		foreach ( get_post_stati( array( 'show_in_admin_status_list' => true ), 'objects' ) as $status ) {
			$class = '';

			$status_name = $status->name;

			if ( ! in_array( $status_name, $avail_post_stati ) )
				continue;

			if ( empty( $num_posts->$status_name ) )
				continue;

			if ( ! empty( $_REQUEST['post_status'] ) && $status_name == $_REQUEST['post_status'] )
				$class = ' class="current"';

			$status_links[$status_name] = "<a href=\"admin.php?page=fct-records&amp;post_status=$status_name{$parent}\"$class>" . sprintf( translate_nooped_plural( $status->label_count, $num_posts->$status_name ), number_format_i18n( $num_posts->$status_name ) ) . '</a>';
		}

		return apply_filters( "fct_admin_get_records_views", $status_links );
	}

	/**
	 * Return dedicated bulk actions
	 *
	 * @since 0.0.8
	 *
	 * @return array Bulk actions
	 */
	public function _get_bulk_actions() {
		return array();
	}

	/**
	 * Return table classes. Mode aware
	 *
	 * @since 0.0.8
	 *
	 * @return array Classes
	 */
	public function get_table_classes() {
		$classes = array( 'widefat', 'fixed', 'posts', 'records' );

		if ( ! fct_admin_is_view_records() ) {
			$classes[] = fct_admin_get_records_mode() . '-records';
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
	public function _get_columns() {
		$columns = array(
			'cb'                           => '<input type="checkbox" />',
			'fct_record_post_date'         => _x( 'Inserted', 'column name', 'fiscaat' ),
			'author'                       => __( 'Author' ),
			'fct_record_period'            => _x( 'Period',   'column name', 'fiscaat' ),
			'fct_record_account_ledger_id' => _x( 'No.',      'column name', 'fiscaat' ),
			'fct_record_account'           => __( 'Account',                 'fiscaat' ),
			'fct_record_description'       => __( 'Description',             'fiscaat' ),
			'fct_record_date'              => __( 'Date' ),
			'fct_record_offset_account'    => __( 'Offset Account',          'fiscaat' ),
			'fct_record_amount'            => _x( 'Amount', 'column name',   'fiscaat' ),
		);

		// Remove rows in new/edit mode
		if ( fct_admin_is_new_records() || fct_admin_is_edit_records() ) {
			unset(
				$columns['fct_record_post_date'],
				$columns['author'],
				$columns['fct_record_period']
			);

			// Display single account column for new mode
			if ( fct_admin_is_new_records() ) {
				unset( $columns['fct_record_account_ledger_id'] );
			}
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
	public function _get_sortable_columns() {

		// Do not sort in new mode
		if ( fct_admin_is_new_records() ) {
			$columns = array();

		} else {
			$columns = array(
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

		return $columns;
	}

	/**
	 * Return columns that are hidden by default
	 *
	 * @since 0.0.8
	 *
	 * @return array Hidden columns
	 */
	public function _get_hidden_columns( $columns ) {

		// Hide columns on view page to keep it clean
		if ( fct_admin_is_view_records() ) {
			$columns[] = 'author';
			$columns[] = 'fct_record_period';
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
	 * @uses do_action() Calls 'fct_admin_posts_insert_form_bottom'
	 * @uses do_action() Calls 'fct_admin_posts_insert_form_top'
	 */
	public function display_tablenav( $which ) {
		if ( 'top' == $which ) {
			wp_nonce_field( 'bulk-records' );
		}

		// Close #posts-insert form and start bottom tablenav
		if ( 'bottom' == $which && ( fct_admin_is_new_records() || fct_admin_is_edit_records() ) ) : ?>

				<?php do_action( 'fct_admin_posts_insert_form_bottom' ); ?>

			</form><!-- #posts-insert -->
			<form id="posts-filter2" action="" method="get">

				<input type="hidden" name="page" class="post_page" value="<?php echo ! empty($_REQUEST['page']) ? esc_attr($_REQUEST['page']) : 'fct-records'; ?>" />
				<input type="hidden" name="post_status" class="post_status_page" value="<?php echo ! empty($_REQUEST['post_status']) ? esc_attr($_REQUEST['post_status']) : ''; ?>" />
				<?php wp_nonce_field( 'bulk-records' ); ?>

		<?php endif; ?>

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
		
		<?php // Close top tablenav and start #posts-insert form
		if ( 'top' == $which && ( fct_admin_is_new_records() || fct_admin_is_edit_records() ) ) : ?>

			</form><!-- #posts-filter -->
			<form id="posts-insert" action="" method="post">

				<?php do_action( 'fct_admin_posts_insert_form_top' ); ?> 

		<?php endif;
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
	public function display_rows_or_placeholder() {

		// Display edit mode, not when displaying account
		if ( fct_admin_is_edit_records() && $this->has_items() && ! $this->account_id ) {
			$this->display_edit_rows();

		// Display post-new mode
		} elseif ( fct_admin_is_new_records() ) {
			$this->display_new_rows();

		// Display rows when present or displaying account
		} elseif ( fct_admin_is_view_records() && ( $this->has_items() || $this->account_id ) ) {
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
	public function display_edit_rows( $posts = array(), $level = 0 ) {
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
	public function display_new_rows() {

		// Start with 25 empty rows
		for ( $i = 0; $i < 25; $i++ ) {
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
	public function single_new_row() {
		$alternate =& $this->alternate;
		$alternate = 'alternate' == $alternate ? '' : 'alternate';
		$classes = $alternate . ' iedit record';

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
	 * @uses fct_account_ledger_dropdown()
	 * @uses fct_account_dropdown()
	 * @param string $column_name Column name
	 */
	public function _new_row( $column_name ) {

		// Check column name
		switch ( $column_name ) {

			// Record account ledger id
			case 'fct_record_account_ledger_id' :
				fct_ledger_dropdown( array(
					'select_name'    => 'records[ledger_account_id][]',
					'class'          => 'fct_record_ledger_id',
					'show_none'      => '&mdash;',
					'disable_closed' => true,
				) );
				break;

			// Record account
			case 'fct_record_account' :

				// Prepend ledger dropdown in new mode
				if ( fct_admin_is_new_records() && ! in_array( 'fct_record_account_ledger_id', array_keys( $this->get_columns() ) ) ) {
					fct_ledger_dropdown( array(
						'select_name'    => 'records[ledger_account_id][]',
						'class'          => 'fct_record_ledger_id',
						'show_none'      => '&mdash;',
						'disable_closed' => true,
					) );
				}

				fct_account_dropdown( array(
					'select_name'    => 'records[account_id][]',
					'class'          => 'fct_record_account_id',
					'show_none'      => __( '&mdash; No Account &mdash;', 'fiscaat' ),
					'disable_closed' => true,
				) );
				break;

			// Record content
			case 'fct_record_description' : ?>

				<textarea name="records[description][]" class="fct_record_description" rows="1" <?php fct_tab_index_attr(); ?>></textarea>

				<?php
				break;

			// Record date
			case 'fct_record_date': 
				$today = mysql2date( _x( 'Y-m-d', 'date input field format', 'fiscaat' ), fct_current_time() ); ?>

				<input name="records[date][]" type="text" class="fct_record_date medium-text" value="" placeholder="<?php echo $today; ?>" <?php fct_tab_index_attr(); ?>/>

				<?php
				break;

			// Record offset account
			case 'fct_record_offset_account' : ?>

				<input name="records[offset_account][]" type="text" class="fct_record_offset_account" value="" <?php fct_tab_index_attr(); ?>/>

				<?php
				break;

			// Record amount
			case 'fct_record_amount' : ?>

				<input name="records[amount][debit][]"  class="debit_amount small-text"  type="number" step="0.01" min="0" value="" <?php fct_tab_index_attr(); ?>/>
				<input name="records[amount][credit][]" class="credit_amount small-text" type="number" step="0.01" min="0" value="" <?php fct_tab_index_attr(); ?>/>

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
	public function display_rows( $posts = array(), $level = 0 ) {
		global $wp_query;

		if ( empty( $posts ) ) {
			$posts = $wp_query->posts;
		}

		add_filter( 'the_title', 'esc_html' );

		// Start account row. Revenue accounts have no starting value
		if ( $this->account_id && fct_get_revenue_account_type_id() != fct_get_account_type( $this->account_id ) ) {
			$this->display_helper_row( 'start' );
		}

		$this->_display_rows( $posts, $level );

		// End account row
		if ( $this->account_id ) {
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
	 * @uses fct_get_record_period_id()
	 * @uses fct_get_account_period_id()
	 * @uses fct_get_period_title()
	 * @param string $column_name Column name
	 * @param int $record_id Record ID
	 */
	public function _column_content( $column_name, $record_id ) {
		$account_id = fct_get_record_account_id( $record_id );

		// Check column name
		switch ( $column_name ) {

			// Record post date
			case 'fct_record_post_date':
				$date = get_post_time( 'U', $period_id );
				echo '<abbr title="' . mysql2date( __( 'Y/m/d g:i:s A' ), $date ) . '">' . apply_filters( 'post_date_column_time', mysql2date( __( 'Y/m/d' ), $date ), $record_id, $column_name, 'list' ) . '</abbr>';
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

			// Record date
			case 'fct_record_date':
				$date = fct_get_record_date( $record_id );
				echo '<abbr title="' . mysql2date( __( 'Y/m/d g:i:s A' ), $date ) . '">' . apply_filters( 'post_date_column_time', mysql2date( __( 'Y/m/d' ), $date ), $record_id, $column_name, 'list' ) . '</abbr>';
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

				<input id="fct_record_<?php echo $record_id; ?>_debit_amount"  class="debit_amount small-text"  type="number" step="0.01" min="0" value="<?php if ( fct_get_debit_record_type_id()  == $rtype ){ fct_currency_format( $value ); } ?>" readonly />
				<input id="fct_record_<?php echo $record_id; ?>_credit_amount" class="credit_amount small-text" type="number" step="0.01" min="0" value="<?php if ( fct_get_credit_record_type_id() == $rtype ){ fct_currency_format( $value ); } ?>" readonly />

				<?php
				break;

			// Record period
			case 'fct_record_period' :
				$record_period_id  = fct_get_record_period_id(  $record_id  );
				$account_period_id = fct_get_account_period_id( $account_id );

				if ( ! empty( $record_period_id ) ) {
					$period_title = fct_get_period_title( $record_period_id );
					if ( empty( $period_title ) ) {
						$period_title = __( 'No Period', 'fiscaat' );
					}

					// Alert capable users of record period mismatch
					if ( $record_period_id != $account_period_id ) {
						if ( current_user_can( 'edit_others_records' ) ) {
							$period_title .= ' <div class="attention">' . __( '(Mismatch)', 'fiscaat' ) . '</div>';
						}
					}
					echo $period_title;

				} else {
					_e( 'No Period', 'fiscaat' );
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
	public function display_helper_row( $which = '' ) {

		// Bail if no row name given
		$which = esc_attr( esc_html( $which ) );
		if ( empty( $which ) )
			return;

		$alternate =& $this->alternate;
		$alternate = 'alternate' == $alternate ? '' : 'alternate';
		$classes   = "{$alternate} iedit {$which}-records";

		list( $columns, $hidden ) = $this->get_column_info(); ?>
		<tr id="fct-<?php echo $which; ?>-records" class="<?php echo $classes; ?>" valign="top">

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
	public function _start_or_end_row( $column ) {

		// Bail if no valid parent account id
		if ( ! $account_id = fct_get_account_id( $this->account_id ) )
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
							_e( 'Beginning Balance', 'fiscaat' );
						} else {
							_e( 'Ending Balance',    'fiscaat' );
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

				<input id="fct_account_debit_<?php echo $_row; ?>"  class="debit_amount small-text"  type="number" step="0.01" min="0" value="<?php if ( $value > 0 ) { fct_currency_format( abs( $value ) ); } ?>" <?php fct_tab_index_attr(); ?> readonly />
				<input id="fct_account_credit_<?php echo $_row; ?>" class="credit_amount small-text" type="number" step="0.01" min="0" value="<?php if ( $value < 0 ) { fct_currency_format( abs( $value ) ); } ?>" <?php fct_tab_index_attr(); ?> readonly />

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
	public function _total_row( $column ) {

		// Check column name
		switch ( $column ) {

			// Row title
			case 'fct_record_description' :
				$total_title = _x( 'Total', 'Sum of all records', 'fiscaat' );

				// Alert capable users of debit credit mismatch
				if ( array_sum( $this->amounts[ fct_get_debit_record_type_id() ] ) != array_sum( $this->amounts[ fct_get_credit_record_type_id() ] ) ) {
					$total_title .= ' <span class="attention">' . __( '(Mismatch)', 'fiscaat' ) . '</span>';
				}

				echo $total_title;
				break;

			// Submit button
			case 'fct_record_offset_account' :

				// THE records submit button
				if ( fct_admin_is_new_records() ) {
					submit_button( __( 'Submit', 'fiscaat' ), 'primary', 'submit-records', false, array( 'tabindex' => fct_get_tab_index() ) );
				}

				break;

			// Total amount
			case 'fct_record_amount' : 
				$format = fct_the_currency_format();
				$placeholder = sprintf( '0%s%s', $format['decimal_point'], str_repeat( '0', $format['decimals'] ) ); ?>

				<input id="fct_records_debit_total"  class="debit_amount fct_record_total small-text"  type="number" step="0.01" min="0" value="<?php fct_currency_format( array_sum( $this->amounts[ fct_get_debit_record_type_id()  ] ) ); ?>" <?php fct_tab_index_attr(); ?> placeholder="<?php echo $placeholder; ?>" readonly />
				<input id="fct_records_credit_total" class="credit_amount fct_record_total small-text" type="number" step="0.01" min="0" value="<?php fct_currency_format( array_sum( $this->amounts[ fct_get_credit_record_type_id() ] ) ); ?>" <?php fct_tab_index_attr(); ?> placeholder="<?php echo $placeholder; ?>" readonly />

				<?php
				break;
		}
	}
}
