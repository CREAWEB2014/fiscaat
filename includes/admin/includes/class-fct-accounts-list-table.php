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
	 * Constructs the posts list table
	 * 
	 * @param array $args
	 */
	function __construct( $args = array() ) {
		parent::__construct( array(
			'plural'   => 'accounts',
			'singular' => 'account',
			'screen'   => isset( $args['screen'] ) ? $args['screen'] : null,
		) );
	}

	function _get_bulk_actions() {
		$actions = array();

		if ( current_user_can( 'edit_accounts' ) ) {
			$actions['close'] = __( 'Close', 'fiscaat' );
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
	function _get_columns() {
		return array(
			'cb'                       => '<input type="checkbox" />',
			'fct_account_year'         => __( 'Year',                         'fiscaat' ),
			'fct_account_ledger_id'    => _x( 'No.', 'Account number column', 'fiscaat' ),
			'title'                    => __( 'Account',                      'fiscaat' ),
			'fct_account_type'         => __( 'Type',                         'fiscaat' ),
			'fct_account_record_count' => __( 'Records',                      'fiscaat' ),
			'fct_account_end_value'    => __( 'Value',                        'fiscaat' ),
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
			'fct_account_ledger_id'    => 'account_ledger_id',
			'title'                    => 'post_title',
			'fct_account_type'         => 'account_type',
			'fct_account_record_count' => 'account_record_count',
			'fct_account_end_value'    => 'account_value',
		);
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
	 * @param int $account_id Account ID
	 */
	function _column_content( $column_name, $account_id ) {

		switch ( $column_name ) {
			case 'fct_account_year' :
				$year_id = fct_get_account_year_id( $account_id );
				
				if ( ! empty( $year_id ) ) {
					$year_title = fct_get_year_title( $year_id );
					if ( empty( $year_title ) ) {
						$year_title = __( 'No Year', 'fiscaat' );
					}
					echo $year_title;

				} else {
					_e( '(No Year)', 'fiscaat' );
				}
				break;

			case 'fct_account_ledger_id' :
				fct_account_ledger_id( $account_id );
				break;

			case 'fct_account_type' :

				// Capital
				if ( fct_get_capital_account_type_id() == fct_get_account_type( $account_id ) ) {
					_ex( 'C', 'Capital account type', 'fiscaat' );

				// Revenue
				} else {
					_ex( 'R', 'Revenue account type', 'fiscaat' );
				}
				break;

			case 'fct_account_record_count' :
				fct_account_record_count( $account_id );
				break;

			case 'fct_account_end_value' :
				fct_currency_format( fct_get_account_end_value( $account_id ), true );
				break;
		}
	}
}
