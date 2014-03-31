<?php

/**
 * Fiscaat User Options
 *
 * @package Fiscaat
 * @subpackage UserOptions
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Get the default user options and their values
 *
 * @return array Filtered user option names and values
 */
function fct_get_default_user_options() {

	// Default options
	return apply_filters( 'fct_get_default_user_options', array(
		// No user options yet
	) );
}

/**
 * Add default user options
 *
 * This is destructive, so existing Fiscaat user options will be overridden.
 *
 * @uses fct_get_default_user_options() To get default options
 * @uses update_user_option() Adds default options
 * @uses do_action() Calls 'fct_add_user_options'
 */
function fct_add_user_options( $user_id = 0 ) {

	// Validate user id
	$user_id = fct_get_user_id( $user_id );
	if ( empty( $user_id ) )
		return;

	// Add default options
	foreach ( fct_get_default_user_options() as $key => $value )
		update_user_option( $user_id, $key, $value );

	// Allow previously activated plugins to append their own user options.
	do_action( 'fct_add_user_options', $user_id );
}

/**
 * Delete default user options
 *
 * Hooked to fct_uninstall, it is only called once when Fiscaat is uninstalled.
 * This is destructive, so existing Fiscaat user options will be destroyed.
 *
 * @uses fct_get_default_user_options() To get default options
 * @uses delete_user_option() Removes default options
 * @uses do_action() Calls 'fct_delete_options'
 */
function fct_delete_user_options( $user_id = 0 ) {

	// Validate user id
	$user_id = fct_get_user_id( $user_id );
	if ( empty( $user_id ) )
		return;

	// Add default options
	foreach ( fct_get_default_user_options() as $key => $value )
		delete_user_option( $user_id, $key );

	// Allow previously activated plugins to append their own options.
	do_action( 'fct_delete_user_options', $user_id );
}

/**
 * Add filters to each Fiscaat option and allow them to be overloaded from
 * inside the $fiscaat->options array.
 *
 * @uses fct_get_default_user_options() To get default options
 * @uses add_filter() To add filters to 'pre_option_{$key}'
 * @uses do_action() Calls 'fct_add_option_filters'
 */
function fct_setup_user_option_filters() {

	// Add filters to each Fiscaat option
	foreach ( fct_get_default_user_options() as $key => $value )
		add_filter( 'get_user_option_' . $key, 'fct_filter_get_user_option', 10, 3 );

	// Allow previously activated plugins to append their own options.
	do_action( 'fct_setup_user_option_filters' );
}

/**
 * Filter default options and allow them to be overloaded from inside the
 * $fiscaat->user_options array.
 *
 * @param bool $value Optional. Default value false
 * @return mixed false if not overloaded, mixed if set
 */
function fct_filter_get_user_option( $value = false, $option = '', $user = 0 ) {
	$fiscaat = fiscaat();

	// Check the options global for preset value
	if ( isset( $user->ID ) && isset( $fiscaat->user_options[$user->ID] ) && ! empty( $fiscaat->user_options[$user->ID][$option] ) )
		$value = $fiscaat->user_options[$user->ID][$option];

	// Always return a value, even if false
	return $value;
}

