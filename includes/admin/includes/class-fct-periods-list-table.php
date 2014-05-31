<?php

/**
 * Fiscaat Periods List Table class
 *
 * @package Fiscaat
 * @subpackage List_Table
 * @since 0.0.7
 * @access private
 */

class FCT_Periods_List_Table extends FCT_Posts_List_Table {

	/**
	 * Constructs the posts list table
	 * 
	 * @param array $args
	 */
	public function __construct( $args = array() ) {
		parent::__construct( array(
			'plural'   => 'periods',
			'singular' => 'period',
			'screen'   => isset( $args['screen'] ) ? $args['screen'] : null,
		) );
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

		if ( $this->is_trash && current_user_can( 'delete_periods' ) ) {
			$actions['untrash'] = __( 'Restore' );
		}

		if ( current_user_can( 'delete_periods' ) && ( $this->is_trash || ! EMPTY_TRASH_DAYS ) ) {
			$actions['delete'] = __( 'Delete Permanently' );
		} elseif ( current_user_can( 'delete_periods' ) ) {
			$actions['trash'] = __( 'Move to Trash' );
		}

		return $actions;
	}

	/**
	 * Return dedicated period columns
	 *
	 * @since 0.0.8
	 * 
	 * @return array Columns
	 */
	public function _get_columns() {
		$columns = array(
			'cb'                       => '<input type="checkbox" />',
			'title'                    => __( 'Title' ),
			'author'                   => __( 'Author' ),
			'fct_period_post_date'     => _x( 'Opened', 'column name', 'fiscaat' ),
			'fct_period_close_date'    => _x( 'Closed', 'column name', 'fiscaat' ),
			'fct_period_account_count' => __( 'Accounts',              'fiscaat' ),
			'fct_period_record_count'  => __( 'Records',               'fiscaat' ),
			'fct_period_end_value'     => __( 'Value',                 'fiscaat' ),
		);

		if ( ! current_user_can( 'edit_periods' ) ) {
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
			'fct_period_post_date'     => array( 'date',                 true ),
			'fct_period_close_date'    => array( 'period_closed',        true ),
			'fct_period_account_count' => array( 'period_account_count', true ),
			'fct_period_record_count'  => array( 'period_record_count',  true ),
			'fct_period_end_value'     => array( 'period_end_value',     true ),
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
	 * @uses fct_period_post_date()
	 * @uses fct_period_close_date()
	 * @uses fct_period_account_count()
	 * @uses fct_period_record_count()
	 * @uses fct_currency_format()
	 * @uses fct_get_period_end_value()
	 * @param string $column_name Column name
	 * @param int $period_id Period ID
	 */
	public function _column_content( $column_name, $period_id ) {

		// Check column name
		switch ( $column_name ) {

			// Period start date
			case 'fct_period_post_date':
				$date = fct_get_period_post_date( $period_id );
				echo '<abbr title="' . mysql2date( __( 'Y/m/d g:i:s A' ), $date ) . '">' . apply_filters( 'post_date_column_time', mysql2date( 'Y/m/d', $date ), $period_id, $column_name, 'list' ) . '</abbr>';
				break;

			// Period close date
			case 'fct_period_close_date':
				$date = fct_get_period_close_date( $period_id );
				echo '<abbr title="' . mysql2date( __( 'Y/m/d g:i:s A' ), $date )  . '">' . apply_filters( 'post_date_column_time', mysql2date( 'Y/m/d', $date ), $period_id, $column_name, 'list' ) . '</abbr>';
				break;

			// Period account count
			case 'fct_period_account_count' :
				fct_period_account_count( $period_id );
				break;

			// Period record count
			case 'fct_period_record_count' :
				fct_period_record_count( $period_id );
				break;

			// Period end value
			case 'fct_period_end_value' :
				fct_currency_format( fct_get_period_end_value( $period_id ), true );
				break;
		}
	}
}
