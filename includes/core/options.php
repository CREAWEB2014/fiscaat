-<?php

/**
 * Fiscaat Options
 *
 * @package Fiscaat
 * @subpackage Options
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Get the default site options and their values.
 * 
 * @return array Filtered option names and values
 */
function fct_get_default_options() {

	// Default options
	return apply_filters( 'fct_get_default_options', array(

		/** DB Version ********************************************************/

		'_fct_db_version'            => fiscaat()->db_version,

		/** Settings **********************************************************/

		'_fct_currency'              => 'USD',        // Currency

		/** Functionality *****************************************************/

		'_fct_enable_control'        => 1,            // Enable Control functionality
		'_fct_enable_comments'       => 0,            // Enable Fiscaat comments
		'_fct_use_wp_editor'         => 1,            // Use the WordPress editor if available

		/** Accounts **********************************************************/

		'_fct_main_bank_account'     => 0,            // Main Bank Account
		'_fct_main_bank_ledger_id'   => 102,          // Main Bank Account Ledger id
		'_fct_second_bank_account'   => 0,            // Second Bank Account
		'_fct_second_bank_ledger_id' => 0,            // Second Bank Account Ledger id
		'_fct_third_bank_account'    => 0,            // Third Bank Account
		'_fct_third_bank_ledger_id'  => 0,            // Third Bank Account Ledger id
		'_fct_balance_ledger_id'     => 199,          // Balance Account Ledger id
		'_fct_suspense_ledger_id'    => 999,          // Suspense Account Ledger id

		/** Per Page **********************************************************/

		'_fct_accounts_per_page'     => 15,           // Accounts per page
		'_fct_records_per_page'      => 50,           // Records per page

		/** Slugs Section *****************************************************/

		'_fct_root_slug'             => 'fiscaat',    // Root slug
		'_fct_ledger_slug'           => 'ledger',     // Ledger slug

		/** Single Slugs ******************************************************/

		'_fct_include_root'          => 1,            // Include fiscaat-archive before single slugs
		'_fct_record_slug'           => 'record',     // Record slug
		'_fct_account_slug'          => 'account',    // Account slug
		'_fct_year_slug'             => 'year',       // Year slug

		/** Records ***********************************************************/

		'_fct_title_max_length'      => 80,           // Title Max Length

	) );
}

/**
 * Add default options
 *
 * Hooked to fct_activate, it is only called once when Fiscaat is activated.
 * This is non-destructive, so existing settings will not be overridden.
 *
 * @uses fct_get_default_options() To get default options
 * @uses add_option() Adds default options
 * @uses do_action() Calls 'fct_add_options'
 */
function fct_add_options() {

	// Add default options
	foreach ( fct_get_default_options() as $key => $value )
		add_option( $key, $value );

	// Allow previously activated plugins to append their own options.
	do_action( 'fct_add_options' );
}

/**
 * Delete default options
 *
 * Hooked to fct_uninstall, it is only called once when Fiscaat is uninstalled.
 * This is destructive, so existing settings will be destroyed.
 *
 * @uses fct_get_default_options() To get default options
 * @uses delete_option() Removes default options
 * @uses do_action() Calls 'fct_delete_options'
 */
function fct_delete_options() {

	// Add default options
	foreach ( array_keys( fct_get_default_options() ) as $key )
		delete_option( $key );

	// Allow previously activated plugins to append their own options.
	do_action( 'fct_delete_options' );
}

/**
 * Add filters to each Fiscaat option and allow them to be overloaded from
 * inside the $fiscaat->options array.
 *
 * @uses fct_get_default_options() To get default options
 * @uses add_filter() To add filters to 'pre_option_{$key}'
 * @uses do_action() Calls 'fct_add_option_filters'
 */
function fct_setup_option_filters() {

	// Add filters to each Fiscaat option
	foreach ( array_keys( fct_get_default_options() ) as $key )
		add_filter( 'pre_option_' . $key, 'fct_pre_get_option' );

	// Allow previously activated plugins to append their own options.
	do_action( 'fct_setup_option_filters' );
}

/**
 * Filter default options and allow them to be overloaded from inside the
 * $fiscaat->options array.
 *
 * @param bool $value Optional. Default value false
 * @return mixed false if not overloaded, mixed if set
 */
function fct_pre_get_option( $value = '' ) {

	// Remove the filter prefix
	$option = str_replace( 'pre_option_', '', current_filter() );

	// Check the options global for preset value
	if ( isset( fiscaat()->options[$option] ) )
		$value = fiscaat()->options[$option];

	// Always return a value, even if false
	return $value;
}

/** Active? *******************************************************************/

/**
 * Checks if control feature is enabled.
 *
 * @param $default bool Optional.Default value true
 * @uses get_option() To get the control option
 * @return bool Is control enabled or not
 */
function fct_is_control_active( $default = 0 ) {
	return (bool) apply_filters( 'fct_is_control_active', (bool) get_option( '_fct_enable_control', $default ) );
}

/**
 * Checks if comments feature is enabled.
 *
 * @param $default bool Optional.Default value true
 * @uses get_option() To get the comments option
 * @return bool Is comments enabled or not
 */
function fct_is_comments_active( $default = 0 ) {
	return (bool) apply_filters( 'fct_is_comments_active', (bool) get_option( '_fct_enable_comments', $default ) );
}

/**
 * Use the WordPress editor if available
 *
 * @param $default bool Optional. Default value true
 * @uses get_option() To get the WP editor option
 * @return bool Use WP editor?
 */
function fct_use_wp_editor( $default = 1 ) {
	return (bool) apply_filters( 'fct_use_wp_editor', (bool) get_option( '_fct_use_wp_editor', $default ) );
}

/**
 * Output the maximum length of a title
 *
 * @param $default bool Optional. Default value 80
 */
function fct_title_max_length( $default = 80 ) {
	echo fct_get_title_max_length( $default );
}
	/**
	 * Return the maximum length of a title
	 *
	 * @param $default bool Optional. Default value 80
	 * @uses get_option() To get the maximum title length
	 * @return int Is anonymous posting allowed?
	 */
	function fct_get_title_max_length( $default = 80 ) {
		return (int) apply_filters( 'fct_get_title_max_length', (int) get_option( '_fct_title_max_length', $default ) );
	}

/** Slugs *********************************************************************/

/**
 * Return the root slug
 *
 * @return string
 */
function fct_get_root_slug( $default = 'fiscaat' ) {
	return apply_filters( 'fct_get_root_slug', get_option( '_fct_root_slug', $default ) );
}

/**
 * Are we including the root slug in front of Fiscaat pages?
 *
 * @return bool
 */
function fct_include_root_slug( $default = 1 ) {
	return (bool) apply_filters( 'fct_include_root_slug', (bool) get_option( '_fct_include_root', $default ) );
}

/**
 * Maybe return the root slug, based on whether or not it's included in the url
 *
 * @return string
 */
function fct_maybe_get_root_slug() {
	$retval = '';

	if ( fct_get_root_slug() && fct_include_root_slug() )
		$retval = trailingslashit( fct_get_root_slug() );

	return apply_filters( 'fct_maybe_get_root_slug', $retval );
}

/**
 * Return the ledger slug
 *
 * @return string
 */
function fct_get_ledger_slug( $default = 'ledger' ) {
	return apply_filters( 'fct_get_ledger_slug', get_option( '_fct_ledger_slug', $default ) );
}

/**
 * Return the year slug
 *
 * @return string
 */
function fct_get_year_slug( $default = 'year' ) {
	return apply_filters( 'fct_get_year_slug', fct_maybe_get_root_slug() . get_option( '_fct_year_slug', $default ) );
}

/**
 * Return the account slug
 *
 * @return string
 */
function fct_get_account_slug( $default = 'account' ) {
	return apply_filters( 'fct_get_account_slug', fct_maybe_get_root_slug() . get_option( '_fct_account_slug', $default ) );
}

/**
 * Return the single record slug
 *
 * @return string
 */
function fct_get_record_slug( $default = 'record' ) {
	return apply_filters( 'fct_get_record_slug', fct_maybe_get_root_slug() . get_option( '_fct_record_slug', $default ) );
}
