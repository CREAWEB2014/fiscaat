<?php

/**
 * Fiscaat Options
 *
 * @package Fiscaat
 * @subpackage Options
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Get the default site options and their values.
 * 
 * @return array Filtered option names and values
 */
function fiscaat_get_default_options() {

	// Default options
	return apply_filters( 'fiscaat_get_default_options', array(

		/** DB Version ********************************************************/

		'_fiscaat_db_version'            => fiscaat()->db_version,

		/** Settings **********************************************************/

		'_fiscaat_currency'              => 'USD',        // Currency

		/** Functionality *****************************************************/

		'_fiscaat_enable_control'        => 1,            // Enable Control functionality
		'_fiscaat_enable_comments'       => 0,            // Enable Fiscaat comments
		'_fiscaat_use_wp_editor'         => 1,            // Use the WordPress editor if available

		/** Accounts **********************************************************/

		'_fiscaat_main_bank_account'     => 0,            // Main Bank Account
		'_fiscaat_main_bank_ledger_id'   => 102,          // Main Bank Account Ledger id
		'_fiscaat_second_bank_account'   => 0,            // Second Bank Account
		'_fiscaat_second_bank_ledger_id' => 0,            // Second Bank Account Ledger id
		'_fiscaat_third_bank_account'    => 0,            // Third Bank Account
		'_fiscaat_third_bank_ledger_id'  => 0,            // Third Bank Account Ledger id
		'_fiscaat_balance_ledger_id'     => 199,          // Balance Account Ledger id
		'_fiscaat_suspense_ledger_id'    => 999,          // Suspense Account Ledger id

		/** Per Page **********************************************************/

		'_fiscaat_accounts_per_page'     => 15,           // Accounts per page
		'_fiscaat_records_per_page'      => 50,           // Records per page

		/** Slugs Section *****************************************************/

		'_fiscaat_root_slug'             => 'fiscaat',    // Root slug
		'_fiscaat_ledger_slug'           => 'ledger',     // Ledger slug

		/** Single Slugs ******************************************************/

		'_fiscaat_include_root'          => 1,            // Include fiscaat-archive before single slugs
		'_fiscaat_record_slug'           => 'record',     // Record slug
		'_fiscaat_account_slug'          => 'account',    // Account slug
		'_fiscaat_year_slug'             => 'year',       // Year slug

		/** Records ***********************************************************/

		'_fiscaat_title_max_length'      => 80,           // Title Max Length

	) );
}

/**
 * Add default options
 *
 * Hooked to fiscaat_activate, it is only called once when Fiscaat is activated.
 * This is non-destructive, so existing settings will not be overridden.
 *
 * @uses fiscaat_get_default_options() To get default options
 * @uses add_option() Adds default options
 * @uses do_action() Calls 'fiscaat_add_options'
 */
function fiscaat_add_options() {

	// Add default options
	foreach ( fiscaat_get_default_options() as $key => $value )
		add_option( $key, $value );

	// Allow previously activated plugins to append their own options.
	do_action( 'fiscaat_add_options' );
}

/**
 * Delete default options
 *
 * Hooked to fiscaat_uninstall, it is only called once when Fiscaat is uninstalled.
 * This is destructive, so existing settings will be destroyed.
 *
 * @uses fiscaat_get_default_options() To get default options
 * @uses delete_option() Removes default options
 * @uses do_action() Calls 'fiscaat_delete_options'
 */
function fiscaat_delete_options() {

	// Add default options
	foreach ( array_keys( fiscaat_get_default_options() ) as $key )
		delete_option( $key );

	// Allow previously activated plugins to append their own options.
	do_action( 'fiscaat_delete_options' );
}

/**
 * Add filters to each Fiscaat option and allow them to be overloaded from
 * inside the $fiscaat->options array.
 *
 * @uses fiscaat_get_default_options() To get default options
 * @uses add_filter() To add filters to 'pre_option_{$key}'
 * @uses do_action() Calls 'fiscaat_add_option_filters'
 */
function fiscaat_setup_option_filters() {

	// Add filters to each Fiscaat option
	foreach ( array_keys( fiscaat_get_default_options() ) as $key )
		add_filter( 'pre_option_' . $key, 'fiscaat_pre_get_option' );

	// Allow previously activated plugins to append their own options.
	do_action( 'fiscaat_setup_option_filters' );
}

/**
 * Filter default options and allow them to be overloaded from inside the
 * $fiscaat->options array.
 *
 * @param bool $value Optional. Default value false
 * @return mixed false if not overloaded, mixed if set
 */
function fiscaat_pre_get_option( $value = '' ) {

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
function fiscaat_is_control_active( $default = 0 ) {
	return (bool) apply_filters( 'fiscaat_is_control_active', (bool) get_option( '_fiscaat_enable_control', $default ) );
}

/**
 * Checks if comments feature is enabled.
 *
 * @param $default bool Optional.Default value true
 * @uses get_option() To get the comments option
 * @return bool Is comments enabled or not
 */
function fiscaat_is_comments_active( $default = 0 ) {
	return (bool) apply_filters( 'fiscaat_is_comments_active', (bool) get_option( '_fiscaat_enable_comments', $default ) );
}

/**
 * Use the WordPress editor if available
 *
 * @param $default bool Optional. Default value true
 * @uses get_option() To get the WP editor option
 * @return bool Use WP editor?
 */
function fiscaat_use_wp_editor( $default = 1 ) {
	return (bool) apply_filters( 'fiscaat_use_wp_editor', (bool) get_option( '_fiscaat_use_wp_editor', $default ) );
}

/**
 * Output the maximum length of a title
 *
 * @param $default bool Optional. Default value 80
 */
function fiscaat_title_max_length( $default = 80 ) {
	echo fiscaat_get_title_max_length( $default );
}
	/**
	 * Return the maximum length of a title
	 *
	 * @param $default bool Optional. Default value 80
	 * @uses get_option() To get the maximum title length
	 * @return int Is anonymous posting allowed?
	 */
	function fiscaat_get_title_max_length( $default = 80 ) {
		return (int) apply_filters( 'fiscaat_get_title_max_length', (int) get_option( '_fiscaat_title_max_length', $default ) );
	}

/** Slugs *********************************************************************/

/**
 * Return the root slug
 *
 * @return string
 */
function fiscaat_get_root_slug( $default = 'fiscaat' ) {
	return apply_filters( 'fiscaat_get_root_slug', get_option( '_fiscaat_root_slug', $default ) );
}

/**
 * Are we including the root slug in front of Fiscaat pages?
 *
 * @return bool
 */
function fiscaat_include_root_slug( $default = 1 ) {
	return (bool) apply_filters( 'fiscaat_include_root_slug', (bool) get_option( '_fiscaat_include_root', $default ) );
}

/**
 * Maybe return the root slug, based on whether or not it's included in the url
 *
 * @return string
 */
function fiscaat_maybe_get_root_slug() {
	$retval = '';

	if ( fiscaat_get_root_slug() && fiscaat_include_root_slug() )
		$retval = trailingslashit( fiscaat_get_root_slug() );

	return apply_filters( 'fiscaat_maybe_get_root_slug', $retval );
}

/**
 * Return the ledger slug
 *
 * @return string
 */
function fiscaat_get_ledger_slug( $default = 'ledger' ) {
	return apply_filters( 'fiscaat_get_ledger_slug', get_option( '_fiscaat_ledger_slug', $default ) );
}

/**
 * Return the year slug
 *
 * @return string
 */
function fiscaat_get_year_slug( $default = 'year' ) {
	return apply_filters( 'fiscaat_get_year_slug', fiscaat_maybe_get_root_slug() . get_option( '_fiscaat_year_slug', $default ) );
}

/**
 * Return the account slug
 *
 * @return string
 */
function fiscaat_get_account_slug( $default = 'account' ) {
	return apply_filters( 'fiscaat_get_account_slug', fiscaat_maybe_get_root_slug() . get_option( '_fiscaat_account_slug', $default ) );
}

/**
 * Return the single record slug
 *
 * @return string
 */
function fiscaat_get_record_slug( $default = 'record' ) {
	return apply_filters( 'fiscaat_get_record_slug', fiscaat_maybe_get_root_slug() . get_option( '_fiscaat_record_slug', $default ) );
}

