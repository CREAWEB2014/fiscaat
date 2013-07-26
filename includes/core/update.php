<?php

/**
 * Fiscaat Updater
 *
 * @package Fiscaat
 * @subpackage Updater
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * If there is no raw DB version, this is the first installation
 *
 * @uses get_option()
 * @uses fiscaat_get_db_version() To get Fiscaat's database version
 * @return bool True if update, False if not
 */
function fiscaat_is_install() {
	return ! fiscaat_get_db_version_raw();
}

/**
 * Compare the Fiscaat version to the DB version to determine if updating
 *
 * @uses get_option()
 * @uses fiscaat_get_db_version() To get Fiscaat's database version
 * @return bool True if update, False if not
 */
function fiscaat_is_update() {
	$raw    = (int) fiscaat_get_db_version_raw();
	$cur    = (int) fiscaat_get_db_version();
	$retval = (bool) ( $raw < $cur );
	return $retval;
}

/**
 * Determine if Fiscaat is being activated
 *
 * Note that this function currently is not used in Fiscaat core and is here
 * for third party plugins to use to check for Fiscaat activation.
 *
 * @return bool True if activating Fiscaat, false if not
 */
function fiscaat_is_activation( $basename = '' ) {
	$fiscaat = fiscaat();

	$action = false;
	if ( ! empty( $_REQUEST['action'] ) && ( '-1' != $_REQUEST['action'] ) )
		$action = $_REQUEST['action'];
	elseif ( ! empty( $_REQUEST['action2'] ) && ( '-1' != $_REQUEST['action2'] ) )
		$action = $_REQUEST['action2'];

	// Bail if not activating
	if ( empty( $action ) || !in_array( $action, array( 'activate', 'activate-selected' ) ) )
		return false;

	// The plugin(s) being activated
	if ( $action == 'activate' ) {
		$plugins = isset( $_GET['plugin'] ) ? array( $_GET['plugin'] ) : array();
	} else {
		$plugins = isset( $_POST['checked'] ) ? (array) $_POST['checked'] : array();
	}

	// Set basename if empty
	if ( empty( $basename ) && !empty( $fiscaat->basename ) )
		$basename = $fiscaat->basename;

	// Bail if no basename
	if ( empty( $basename ) )
		return false;

	// Is Fiscaat being activated?
	return in_array( $basename, $plugins );
}

/**
 * Determine if Fiscaat is being deactivated
 *
 * @return bool True if deactivating Fiscaat, false if not
 */
function fiscaat_is_deactivation( $basename = '' ) {
	$fiscaat = fiscaat();

	$action = false;
	if ( ! empty( $_REQUEST['action'] ) && ( '-1' != $_REQUEST['action'] ) )
		$action = $_REQUEST['action'];
	elseif ( ! empty( $_REQUEST['action2'] ) && ( '-1' != $_REQUEST['action2'] ) )
		$action = $_REQUEST['action2'];

	// Bail if not deactivating
	if ( empty( $action ) || !in_array( $action, array( 'deactivate', 'deactivate-selected' ) ) )
		return false;

	// The plugin(s) being deactivated
	if ( $action == 'deactivate' ) {
		$plugins = isset( $_GET['plugin'] ) ? array( $_GET['plugin'] ) : array();
	} else {
		$plugins = isset( $_POST['checked'] ) ? (array) $_POST['checked'] : array();
	}

	// Set basename if empty
	if ( empty( $basename ) && !empty( $fiscaat->basename ) )
		$basename = $fiscaat->basename;

	// Bail if no basename
	if ( empty( $basename ) )
		return false;

	// Is Fiscaat being deactivated?
	return in_array( $basename, $plugins );
}

/**
 * Update the DB to the latest version
 *
 * @uses update_option()
 * @uses fiscaat_get_db_version() To get Fiscaat's database version
 */
function fiscaat_version_bump() {
	$db_version = fiscaat_get_db_version();
	update_option( '_fiscaat_db_version', $db_version );
}

/**
 * Create the current year, some default accounts without records
 *
 * @param array $args Array of arguments to override default values
 */
function fiscaat_create_initial_content( $args = array() ) {

	// Create intital accounts data
	$intial_accounts = array(

		/** Assets and Liabilities ********************************************/

		// Cash Book
		'cash_book' => array(
			'account_title'   => __( 'Cash Book',                                   'fiscaat' ),
			'account_content' => __( 'The cash book monitors all real cash flows.', 'fiscaat' ),
			'account_type'    => fiscaat_get_asset_account_type(),
			'ledger_id'       => 101,
		),

		// Main Bank Account
		'main_bank_account' => array(
			'account_title'   => __( 'Main Bank Account',                          'fiscaat' ),
			'account_content' => __( 'Shows the flows of your banking transfers.', 'fiscaat' ),
			'account_type'    => fiscaat_get_asset_account_type(),
			'ledger_id'       => 102,
		),

		// Accounts Receivable
		'accounts_receivable' => array(
			'account_title'   => __( 'Accounts Receivable',                      'fiscaat' ),
			'account_content' => __( 'Keeping an eye out for all your debtors.', 'fiscaat' ),
			'account_type'    => fiscaat_get_asset_account_type(),
			'ledger_id'       => 121,
		),

		// Other Assets
		'other_assets' => array(
			'account_title'   => __( 'Other Assets',                      'fiscaat' ),
			'account_content' => __( 'Carries all non-specified assets.', 'fiscaat' ),
			'account_type'    => fiscaat_get_asset_account_type(),
			'ledger_id'       => 171,
		),

		// Notes Payable
		'notes_payable' => array(
			'account_title'   => __( 'Notes Payable',                               'fiscaat' ),
			'account_content' => __( 'Carries the short term written liabilities.', 'fiscaat' ),
			'account_type'    => fiscaat_get_asset_account_type(),
			'ledger_id'       => 201,
		),

		// Accounts Payable
		'accounts_payable' => array(
			'account_title'   => __( 'Accounts Payable',                                   'fiscaat' ),
			'account_content' => __( 'Keep your friends close and your creditors closer.', 'fiscaat' ),
			'account_type'    => fiscaat_get_asset_account_type(),
			'ledger_id'       => 211,
		),

		/** Results ***********************************************************/

		// Operating Revenues
		'operating_revenues' => array(
			'account_title'   => __( 'Operating Revenues',           'fiscaat' ),
			'account_content' => __( 'Carries all gained revenues.', 'fiscaat' ),
			'account_type'    => fiscaat_get_result_account_type(),
			'ledger_id'       => 310,
		),

		// Operating Expenses
		'operating_expenses' => array(
			'account_title'   => __( 'Operating Expenses',    'fiscaat' ),
			'account_content' => __( 'Carries all expenses.', 'fiscaat' ),
			'account_type'    => fiscaat_get_result_account_type(),
			'ledger_id'       => 510,
		),

		// Non-operating Revenues
		'non_operating_revenues' => array(
			'account_title'   => __( 'Non-operating Revenues',                              'fiscaat' ),
			'account_content' => __( 'Carries all revenues and gains from non-operations.', 'fiscaat' ),
			'account_type'    => fiscaat_get_result_account_type(),
			'ledger_id'       => 930,
		),

		// Non-operating Expenses
		'non_operating_expenses' => array(
			'account_title'   => __( 'Non-operating Expenses',                               'fiscaat' ),
			'account_content' => __( 'Carries all expenses and losses from non-operations.', 'fiscaat' ),
			'account_type'    => fiscaat_get_result_account_type(),
			'ledger_id'       => 950,
		),

		/** Other *************************************************************/

		// Balance Account
		'balance_account' => array(
			'account_title'   => __( 'Balance Account',                               'fiscaat' ),
			'account_content' => __( 'Manages transfers between your bank accounts.', 'fiscaat' ),
			'account_type'    => fiscaat_get_asset_account_type(),
			'ledger_id'       => 199,
		),

		// Suspense Account
		'suspense_account' => array(
			'account_title'   => __( 'Suspense Account',                                                'fiscaat' ),
			'account_content' => __( 'Carries temporarily uncertain or doubtful receipts or expenses.', 'fiscaat' ),
			'account_type'    => fiscaat_get_asset_account_type(),
			'ledger_id'       => 999,
		),

	);

	// Define local variable
	$account_ids = array();

	// Create the initial year
	$year_id = fiscaat_insert_year( array(
		'post_title' => __( 'Initial Year', 'fiscaat' ),
	) );

	// Create the initial accounts
	foreach ( $intial_accounts as $account ){
		extract( $account );

		$account_ids[] = fiscaat_insert_account( 
			array(
				'post_parent'  => $year_id,
				'post_title'   => $account_title,
				'post_content' => $account_content,
			),
			array(
				'year_id'      => $year_id,
				'account_type' => $account_type,
				'ledger_id'    => $ledger_id,
			)
		);
	}

	update_option( '_fiscaat_activated', fiscaat_get_current_time() );

	return array(
		'year_id'     => $year_id,
		'account_ids' => $account_ids,
	);
}

/**
 * Setup the Fiscaat updater
 *
 * @uses fiscaat_version_updater()
 * @uses fiscaat_version_bump()
 */
function fiscaat_setup_updater() {

	// Bail if no update needed
	if ( ! fiscaat_is_update() )
		return;

	// Call the automated updater
	fiscaat_version_updater();
}

/**
 * Fiscaat's version updater looks at what the current database version is, and
 * runs whatever other code is needed.
 *
 * This is most-often used when the data schema changes, but should also be used
 * to correct issues with Fiscaat meta-data silently on software update.
 */
function fiscaat_version_updater() {

	// Get the raw database version
	$raw_db_version = (int) fiscaat_get_db_version_raw();

	/** 0.0 Branch ************************************************************/

	// 0.0.1
	if ( $raw_db_version < 001 ) {
		// Do nothing
	}

	/** All done! *************************************************************/

	// Bump the version
	fiscaat_version_bump();

	// Delete rewrite rules to force a flush
	fiscaat_delete_rewrite_rules();
}
