<?php

/**
 * Fiscaat Updater
 *
 * @package Fiscaat
 * @subpackage Updater
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * If there is no raw DB version, this is the first installation
 *
 * @uses get_option()
 * @uses fct_get_db_version() To get Fiscaat's database version
 * @return bool True if update, False if not
 */
function fct_is_install() {
	return ! fct_get_db_version_raw();
}

/**
 * Compare the Fiscaat version to the DB version to determine if updating
 *
 * @uses get_option()
 * @uses fct_get_db_version() To get Fiscaat's database version
 * @return bool True if update, False if not
 */
function fct_is_update() {
	$raw    = (int) fct_get_db_version_raw();
	$cur    = (int) fct_get_db_version();
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
function fct_is_activation( $basename = '' ) {

	$fct = fiscaat();
	$action  = false;

	if ( ! empty( $_REQUEST['action'] ) && ( '-1' != $_REQUEST['action'] ) ) {
		$action = $_REQUEST['action'];
	} elseif ( ! empty( $_REQUEST['action2'] ) && ( '-1' != $_REQUEST['action2'] ) ) {
		$action = $_REQUEST['action2'];
	}

	// Bail if not activating
	if ( empty( $action ) || ! in_array( $action, array( 'activate', 'activate-selected' ) ) ) {
		return false;
	}

	// The plugin(s) being activated
	if ( $action == 'activate' ) {
		$plugins = isset( $_GET['plugin'] ) ? array( $_GET['plugin'] ) : array();
	} else {
		$plugins = isset( $_POST['checked'] ) ? (array) $_POST['checked'] : array();
	}

	// Set basename if empty
	if ( empty( $basename ) && ! empty( $fct->basename ) ) {
		$basename = $fct->basename;
	}

	// Bail if no basename
	if ( empty( $basename ) ) {
		return false;
	}

	// Is Fiscaat being activated?
	return in_array( $basename, $plugins );
}

/**
 * Determine if Fiscaat is being deactivated
 *
 * @return bool True if deactivating Fiscaat, false if not
 */
function fct_is_deactivation( $basename = '' ) {

	$fct = fiscaat();
	$action = false;

	if ( ! empty( $_REQUEST['action'] ) && ( '-1' != $_REQUEST['action'] ) ) {
		$action = $_REQUEST['action'];
	} elseif ( ! empty( $_REQUEST['action2'] ) && ( '-1' != $_REQUEST['action2'] ) ) {
		$action = $_REQUEST['action2'];
	}

	// Bail if not deactivating
	if ( empty( $action ) || ! in_array( $action, array( 'deactivate', 'deactivate-selected' ) ) ) {
		return false;
	}

	// The plugin(s) being deactivated
	if ( $action == 'deactivate' ) {
		$plugins = isset( $_GET['plugin'] ) ? array( $_GET['plugin'] ) : array();
	} else {
		$plugins = isset( $_POST['checked'] ) ? (array) $_POST['checked'] : array();
	}

	// Set basename if empty
	if ( empty( $basename ) && ! empty( $fct->basename ) ) {
		$basename = $fct->basename;
	}

	// Bail if no basename
	if ( empty( $basename ) ) {
		return false;
	}

	// Is Fiscaat being deactivated?
	return in_array( $basename, $plugins );
}

/**
 * Update the DB to the latest version
 *
 * @uses update_option()
 * @uses fct_get_db_version() To get Fiscaat's database version
 */
function fct_version_bump() {
	$db_version = fct_get_db_version();
	update_option( '_fct_db_version', $db_version );
}

/**
 * Setup the Fiscaat updater
 *
 * @uses fct_is_update()
 * @uses fct_version_updater()
 */
function fct_setup_updater() {

	// Bail if no update needed
	if ( ! fct_is_update() )
		return;

	// Call the automated updater
	fct_version_updater();
}

/**
 * Create the current period, some default accounts without records
 *
 * @param array $args Array of arguments to override default values
 */
function fct_create_initial_content( $args = array() ) {

	// Create intital accounts data
	$intial_accounts = array(

		/** Assets and Liabilities ********************************************/

		// Cash Book
		'cash_book' => array(
			'account_title'   => _x( 'Cash Book', 'Account title',                      'fiscaat' ),
			'account_content' => __( 'The cash book monitors all physical cash flows.', 'fiscaat' ),
			'account_type'    => fct_get_capital_account_type_id(),
			'ledger_id'       => 101,
		),

		// Main Bank Account
		'main_bank_account' => array(
			'account_title'   => _x( 'Main Bank Account', 'Account title',         'fiscaat' ),
			'account_content' => __( 'Shows the flows of your banking transfers.', 'fiscaat' ),
			'account_type'    => fct_get_capital_account_type_id(),
			'ledger_id'       => 102,
		),

		// Accounts Receivable
		'accounts_receivable' => array(
			'account_title'   => _x( 'Accounts Receivable', 'Account title',     'fiscaat' ),
			'account_content' => __( 'Keeping an eye out for all your debtors.', 'fiscaat' ),
			'account_type'    => fct_get_capital_account_type_id(),
			'ledger_id'       => 121,
		),

		// Other Assets
		'other_assets' => array(
			'account_title'   => _x( 'Other Assets', 'Account title',     'fiscaat' ),
			'account_content' => __( 'Carries all non-specified assets.', 'fiscaat' ),
			'account_type'    => fct_get_capital_account_type_id(),
			'ledger_id'       => 171,
		),

		// Notes Payable
		'notes_payable' => array(
			'account_title'   => _x( 'Notes Payable', 'Account title',              'fiscaat' ),
			'account_content' => __( 'Carries the short term written liabilities.', 'fiscaat' ),
			'account_type'    => fct_get_capital_account_type_id(),
			'ledger_id'       => 201,
		),

		// Accounts Payable
		'accounts_payable' => array(
			'account_title'   => _x( 'Accounts Payable', 'Account title',                  'fiscaat' ),
			'account_content' => __( 'Keep your friends close and your creditors closer.', 'fiscaat' ),
			'account_type'    => fct_get_capital_account_type_id(),
			'ledger_id'       => 211,
		),

		/** Revenues and Expenses *********************************************/

		// Operating Revenues
		'operating_revenues' => array(
			'account_title'   => _x( 'Operating Revenues', 'Account title', 'fiscaat' ),
			'account_content' => __( 'Carries all gained revenues.',        'fiscaat' ),
			'account_type'    => fct_get_revenue_account_type_id(),
			'ledger_id'       => 310,
		),

		// Operating Expenses
		'operating_expenses' => array(
			'account_title'   => _x( 'Operating Expenses', 'Account title', 'fiscaat' ),
			'account_content' => __( 'Carries all expenses.',               'fiscaat' ),
			'account_type'    => fct_get_revenue_account_type_id(),
			'ledger_id'       => 510,
		),

		// Non-operating Revenues
		'non_operating_revenues' => array(
			'account_title'   => _x( 'Non-operating Revenues', 'Account title',             'fiscaat' ),
			'account_content' => __( 'Carries all revenues and gains from non-operations.', 'fiscaat' ),
			'account_type'    => fct_get_revenue_account_type_id(),
			'ledger_id'       => 930,
		),

		// Non-operating Expenses
		'non_operating_expenses' => array(
			'account_title'   => _x( 'Non-operating Expenses', 'Account title',              'fiscaat' ),
			'account_content' => __( 'Carries all expenses and losses from non-operations.', 'fiscaat' ),
			'account_type'    => fct_get_revenue_account_type_id(),
			'ledger_id'       => 950,
		),

		/** Utility Accounts **************************************************/

		// Balance Account
		'balance_account' => array(
			'account_title'   => _x( 'Balance Account', 'Account title',              'fiscaat' ),
			'account_content' => __( 'Manages transfers between your bank accounts.', 'fiscaat' ),
			'account_type'    => fct_get_capital_account_type_id(),
			'ledger_id'       => 199,
		),

		// Suspense Account
		'suspense_account' => array(
			'account_title'   => _x( 'Suspense Account', 'Account title',                         'fiscaat' ),
			'account_content' => __( 'Carries temporarily uncertain or doubtful record entries.', 'fiscaat' ),
			'account_type'    => fct_get_capital_account_type_id(),
			'ledger_id'       => 999,
		),

	);

	// Define local variable
	$account_ids = array();

	// Create the initial period
	$period_id = fct_insert_period( array(
		'post_title' => __( 'Initial Period', 'fiscaat' ),
	) );

	// Create the initial accounts
	foreach ( $intial_accounts as $acnt ){
		$account_ids[] = fct_insert_account( 
			array(
				'post_parent'  => $period_id,
				'post_title'   => $acnt['account_title'],
				'post_content' => $acnt['account_content'],
			),
			array(
				'period_id'    => $period_id,
				'account_type' => $acnt['account_type'],
				'ledger_id'    => $acnt['ledger_id'],
			)
		);
	}

	return array(
		'period_id'   => $period_id,
		'account_ids' => $account_ids,
	);
}

/**
 * Fiscaat's version updater looks at what the current database version is, and
 * runs whatever other code is needed.
 *
 * This is most-often used when the data schema changes, but should also be used
 * to correct issues with Fiscaat meta-data silently on software update.
 */
function fct_version_updater() {

	// Get the raw database version
	$raw_db_version = (int) fct_get_db_version_raw();

	/** 0.0 Branch ************************************************************/

	// 1.0.0
	if ( $raw_db_version < 100 ) {
		// Nothing changed		
	}

	/** All done! *************************************************************/

	// Bump the version
	fct_version_bump();

	// Delete rewrite rules to force a flush
	fct_delete_rewrite_rules();
}

/**
 * Hooked to the 'fct_activation' action, this helper function automatically makes
 * the current user a Fiscus in the site if they just activated Fiscaat,
 * regardless of the fct_allow_global_access() setting.
 *
 * @since 0.0.8
 *
 * @internal Used to internally make the current user a fiscus on activation
 *
 * @uses current_user_can() To bail if user cannot activate plugins
 * @uses get_current_user_id() To get the current user ID
 * @uses get_current_blog_id() To get the current blog ID
 * @uses is_user_member_of_blog() To bail if the current user does not have a role
 * @uses fct_get_user_role() To bail if the user already has a Fiscaat role 
 * @uses fct_set_user_role() To make the current user a fiscus
 *
 * @return If user can't activate plugins or has already a Fiscaat role
 */
function fct_make_current_user_fiscus() {

	// Bail if the current user can't activate plugins since previous pageload
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	// Get the current user ID
	$user_id = get_current_user_id();
	$blog_id = get_current_blog_id();

	// Bail if user is not actually a member of this site
	if ( ! is_user_member_of_blog( $user_id, $blog_id ) ) {
		return;
	}

	// Bail if the current user already has a Fiscaat role to prevent
	// unexpected role and capability escalation.
	if ( fct_get_user_role( $user_id ) ) {
		return;
	}

	// Make the current user a fiscus
	fct_set_user_role( $user_id, fct_get_fiscus_role() );

	// Reload the current user so caps apply immediately
	wp_get_current_user();
}
