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

	function _get_bulk_actions() {
		$actions = array();

		if ( $this->is_trash )
			$actions['untrash'] = __( 'Restore' );

		if ( $this->is_trash || ! EMPTY_TRASH_DAYS )
			$actions['delete'] = __( 'Delete Permanently' );
		else
			$actions['trash'] = __( 'Move to Trash' );

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
			'title'                    => __( 'Title',                            'fiscaat' ),
			'fct_year_started'         => _x( 'Opened', 'Start date column name', 'fiscaat' ),
			'fct_year_closed'          => _x( 'Closed', 'Close date column name', 'fiscaat' ),
			'fct_year_account_count'   => __( 'Accounts',                         'fiscaat' ),
			'fct_year_record_count'    => __( 'Records',                          'fiscaat' ),
			'fct_year_end_value'       => __( 'Value',                            'fiscaat' ),
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
			'fct_year_started'         => array( 'date',        true ),
			'fct_year_closed'          => array( 'year_closed', true ),
			'fct_year_account_count'   => 'year_account_count',
			'fct_year_record_count'    => 'year_record_count',
			'fct_year_end_value'       => 'year_value',
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

		switch ( $column_name ) {
			case 'fct_year_started':
				fct_year_started( $year_id, false );
				break;

			case 'fct_year_closed':
				fct_year_closed( $year_id, false );
				break;

			case 'fct_year_account_count' :
				fct_year_account_count( $year_id );
				break;

			case 'fct_year_record_count' :
				fct_year_record_count( $year_id );
				break;

			case 'fct_year_end_value' :
				fct_currency_format( fct_get_year_end_value( $year_id ), true );
				break;
		}
	}
}
