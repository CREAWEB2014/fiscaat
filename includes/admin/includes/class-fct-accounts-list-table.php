<?php

/**
 * Fiscaat Accounts List Table class
 *
 * @package Fiscaat
 * @subpackage List_Table
 * @since 0.0.7
 * @access private
 */

class FCT_Accounts_List_Table extends FCT_Posts_List_Table {

	/**
	 * Holds the parent period id when displaying period accounts
	 *
	 * @since 0.0.9
	 * @var int
	 * @access protected
	 */
	protected $period_id = false;

	/**
	 * Constructs the posts list table
	 * 
	 * @param array $args
	 */
	public function __construct( $args = array() ) {
		parent::__construct( array(
			'plural'   => 'accounts',
			'singular' => 'account',
			'screen'   => isset( $args['screen'] ) ? $args['screen'] : null,
		) );

		// Displaying period accounts
		if ( isset( $_REQUEST['period_id'] ) && ! empty( $_REQUEST['period_id'] ) ) {
			$this->period_id = $_REQUEST['period_id'];

		// Default to current period
		} elseif ( ! isset( $_REQUEST['period_id'] ) ) {
			$this->period_id = fct_get_current_period_id();
		}
	}

	/**
	 * Return post status views
	 *
	 * Use {@link fct_count_posts()} when displaying period's accounts 
	 * for it enables counting posts by parent. Additionally append the 
	 * period id query arg to the views's urls.
	 *
	 * @since 0.0.9
	 *
	 * @uses fct_count_posts()
	 * @return array Views
	 */
	public function get_views() {
		global $locked_post_status, $avail_post_stati;

		$post_type = $this->screen->post_type;

		if ( ! empty( $locked_post_status ) )
			return array();

		// Period's accounts count
		if ( $this->period_id ) {
			$num_posts = fct_count_posts( array( 
				'type'        => $post_type, 
				'perm'        => 'readable', 
				'post_parent' => $this->period_id,
			) );
			$parent    = '&period_id=' . $this->period_id;

		// All accounts count
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
		$status_links['all'] = "<a href='admin.php?page=fct-accounts{$parent}'$class>" . sprintf( _nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $total_posts, 'posts' ), number_format_i18n( $total_posts ) ) . '</a>';

		foreach ( get_post_stati( array( 'show_in_admin_status_list' => true ), 'objects' ) as $status ) {
			$class = '';

			$status_name = $status->name;

			if ( ! in_array( $status_name, $avail_post_stati ) )
				continue;

			if ( empty( $num_posts->$status_name ) )
				continue;

			if ( isset( $_REQUEST['post_status'] ) && $status_name == $_REQUEST['post_status'] )
				$class = ' class="current"';

			$status_links[$status_name] = "<a href='admin.php?page=fct-accounts&amp;post_status=$status_name{$parent}'$class>" . sprintf( translate_nooped_plural( $status->label_count, $num_posts->$status_name ), number_format_i18n( $num_posts->$status_name ) ) . '</a>';
		}

		return apply_filters( "fct_admin_get_accounts_views", $status_links );
	}

	/**
	 * Return dedicated bulk actions
	 *
	 * @since 0.0.8
	 * 
	 * @return array Bulk actions
	 */
	public function _get_bulk_actions() {
		$actions = array();

		if ( $this->is_trash && current_user_can( 'delete_accounts' ) ) {
			$actions['untrash'] = __( 'Restore' );
		}

		if ( current_user_can( 'delete_accounts' ) && ( $this->is_trash || ! EMPTY_TRASH_DAYS ) ) {
			$actions['delete'] = __( 'Delete Permanently' );
		} elseif ( current_user_can( 'delete_accounts' ) ) {
			$actions['trash'] = __( 'Move to Trash' );
		}

		return $actions;
	}

	/**
	 * Return dedicated account columns
	 *
	 * @since 0.0.8
	 * 
	 * @return array Columns
	 */
	public function _get_columns() {
		$columns = array(
			'cb'                       => '<input type="checkbox" />',
			'fct_account_period'       => __( 'Period',             'fiscaat' ),
			'fct_account_ledger_id'    => _x( 'No.', 'column name', 'fiscaat' ),
			'title'                    => __( 'Account',            'fiscaat' ),
			'fct_account_type'         => __( 'Type',               'fiscaat' ),
			'fct_account_record_count' => __( 'Records',            'fiscaat' ),
			'fct_account_end_value'    => __( 'Value',              'fiscaat' ),
			'author'                   => __( 'Author' ),
		);

		if ( ! current_user_can( 'edit_accounts' ) ) {
			unset( $columns['author'] );
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
		return array(
			'fct_account_period'       => 'parent',
			'fct_account_ledger_id'    => 'account_ledger_id',
			'title'                    => 'title',
			'fct_account_type'         => 'account_type',
			'fct_account_record_count' => array( 'account_record_count', true ),
			'fct_account_end_value'    => array( 'account_end_value',    true ),
		);
	}

	/**
	 * Return columns that are hidden by default
	 *
	 * @since 0.0.8
	 * 
	 * @return array Hidden columns
	 */
	public function _get_hidden_columns( $columns ) {
		$columns[] = 'author';

		return $columns;
	}

	/**
	 * Display dedicated column content
	 *
	 * @since 0.0.8
	 *
	 * @uses fct_get_account_period_id()
	 * @uses fct_get_period_title()
	 * @uses fct_account_ledger_id()
	 * @uses fct_get_account_type()
	 * @uses fct_get_revenue_account_type_id()
	 * @uses fct_get_capital_account_type_id()
	 * @uses fct_account_record_count()
	 * @uses fct_currency_format()
	 * @uses fct_get_account_end_value()
	 * @param string $column_name Column name
	 * @param int $account_id Account ID
	 */
	public function _column_content( $column_name, $account_id ) {

		// Check column name
		switch ( $column_name ) {

			// Account period
			case 'fct_account_period' :
				$period_id = fct_get_account_period_id( $account_id );
				
				if ( ! empty( $period_id ) ) {
					$period_title = fct_get_period_title( $period_id );
					if ( empty( $period_title ) ) {
						$period_title = __( 'No Period', 'fiscaat' );
					}
					echo $period_title;

				} else {
					_e( '(No Period)', 'fiscaat' );
				}
				break;

			// Account ledger id
			case 'fct_account_ledger_id' :
				$ledger_id = fct_get_account_ledger_id( $account_id );

				if ( ! empty( $ledger_id ) ) {
					echo $ledger_id;
				} else {
					echo '&mdash;';
				}
				break;

			// Account type
			case 'fct_account_type' :
				$account_type = fct_get_account_type( $account_id );

				// Capital
				if ( fct_get_capital_account_type_id() == $account_type ) {
					_ex( 'C', 'Capital account type', 'fiscaat' );

				// Revenue
				} elseif ( fct_get_revenue_account_type_id() == $account_type ) {
					_ex( 'R', 'Revenue account type', 'fiscaat' );
				}
				break;

			// Account record count
			case 'fct_account_record_count' :
				fct_account_record_count( $account_id );
				break;

			// Account end value
			case 'fct_account_end_value' :
				fct_currency_format( fct_get_account_end_value( $account_id ), true );
				break;
		}
	}
}
