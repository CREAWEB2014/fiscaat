<?php

/**
 * Fiscaat Years List Table class
 *
 * @package Fiscaat
 * @subpackage List_Table
 * @since 0.0.7
 * @access private
 */

class FCT_Years_List_Table extends FCT_Posts_List_Table {

	/**
	 * Constructs the posts list table
	 * 
	 * @param array $args
	 */
	function __construct( $args = array() ) {
		parent::__construct( array(
			'plural'   => 'years',
			'singular' => 'year',
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
	function _get_bulk_actions() {
		$actions = array();

		if ( $this->is_trash && current_user_can( 'edit_posts' ) ) {
			$actions['untrash'] = __( 'Restore' );
		}

		if ( current_user_can( 'delete_years' ) && ( $this->is_trash || ! EMPTY_TRASH_DAYS ) ) {
			$actions['delete'] = __( 'Delete Permanently' );
		} elseif ( current_user_can( 'delete_years' ) ) {
			$actions['trash'] = __( 'Move to Trash' );
		}

		return $actions;
	}

	/**
	 * Return dedicated year columns
	 *
	 * @since 0.0.8
	 * 
	 * @return array Columns
	 */
	function _get_columns() {
		return array(
			'cb'                       => '<input type="checkbox" />',
			'title'                    => __( 'Title',                 'fiscaat' ),
			'fct_year_started'         => _x( 'Opened', 'column name', 'fiscaat' ),
			'fct_year_closed'          => _x( 'Closed', 'column name', 'fiscaat' ),
			'fct_year_account_count'   => __( 'Accounts',              'fiscaat' ),
			'fct_year_record_count'    => __( 'Records',               'fiscaat' ),
			'fct_year_end_value'       => __( 'Value',                 'fiscaat' ),
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
			'fct_year_started'         => array( 'date',               true ),
			'fct_year_closed'          => array( 'year_closed',        true ),
			'fct_year_account_count'   => array( 'year_account_count', true ),
			'fct_year_record_count'    => array( 'year_record_count',  true ),
			'fct_year_end_value'       => array( 'year_end_value',     true ),
		);
	}

	/**
	 * Display dedicated column content
	 *
	 * @since 0.0.8
	 *
	 * @uses fct_year_started()
	 * @uses fct_year_closed()
	 * @uses fct_year_account_count()
	 * @uses fct_year_record_count()
	 * @uses fct_currency_format()
	 * @uses fct_get_year_end_value()
	 * @param string $column_name Column name
	 * @param int $year_id Year ID
	 */
	function _column_content( $column_name, $year_id ) {

		// Check column name
		switch ( $column_name ) {

			// Year start date
			case 'fct_year_started':
				$date = fct_get_year_started( $year_id );
				echo '<abbr title="' . mysql2date( __( 'Y/m/d g:i:s A' ), $date ) . '">' . apply_filters( 'post_date_column_time', mysql2date( 'Y/m/d', $date ), $year_id, $column_name, 'list' ) . '</abbr>';
				break;

			// Year close date
			case 'fct_year_closed':
				$date = fct_get_year_closed( $year_id );
				echo '<abbr title="' . mysql2date( __( 'Y/m/d g:i:s A' ), $date )  . '">' . apply_filters( 'post_date_column_time', mysql2date( 'Y/m/d', $date ), $year_id, $column_name, 'list' ) . '</abbr>';
				break;

			// Year account count
			case 'fct_year_account_count' :
				fct_year_account_count( $year_id );
				break;

			// Year record count
			case 'fct_year_record_count' :
				fct_year_record_count( $year_id );
				break;

			// Year end value
			case 'fct_year_end_value' :
				fct_currency_format( fct_get_year_end_value( $year_id ), true );
				break;
		}
	}
}
