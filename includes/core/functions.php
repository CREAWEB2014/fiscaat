<?php

/**
 * Fiscaat Core Functions
 *
 * @package Fiscaat
 * @subpackage Functions
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** Versions ******************************************************************/

/**
 * Output the Fiscaat version
 *
 * @uses fct_get_version() To get the Fiscaat version
 */
function fct_version() {
	echo fct_get_version();
}
	/**
	 * Return the Fiscaat version
	 *
	 * @return string The Fiscaat version
	 */
	function fct_get_version() {
		return fiscaat()->version;
	}

/**
 * Output the Fiscaat database version
 *
 * @uses fct_get_version() To get the Fiscaat version
 */
function fct_db_version() {
	echo fct_get_db_version();
}
	/**
	 * Return the Fiscaat database version
	 *
	 * @return string The Fiscaat version
	 */
	function fct_get_db_version() {
		return fiscaat()->db_version;
	}

/**
 * Output the Fiscaat database version directly from the database
 *
 * @uses fct_get_version() To get the current Fiscaat version
 */
function fct_db_version_raw() {
	echo fct_get_db_version_raw();
}
	/**
	 * Return the Fiscaat database version directly from the database
	 *
	 * @return string The current Fiscaat version
	 */
	function fct_get_db_version_raw() {
		return get_option( '_fct_db_version', '' );
	}

/** Post Meta *****************************************************************/

/**
 * Update a posts record meta ID
 *
 * @param int $post_id The post to update
 * @param int $record_id The record
 */
function fct_update_record_id( $post_id, $record_id ) {

	// Allow the record ID to be updated 'just in time' before save
	$record_id = apply_filters( 'fct_update_record_id', $record_id, $post_id );

	// Update the post meta record ID
	update_post_meta( $post_id, '_fct_record_id', (int) $record_id );
}

/**
 * Update a posts account meta ID
 *
 * @param int $post_id The post to update
 * @param int $account_id The account
 */
function fct_update_account_id( $post_id, $account_id ) {

	// Allow the account ID to be updated 'just in time' before save
	$account_id = apply_filters( 'fct_update_account_id', $account_id, $post_id );

	// Update the post meta account ID
	update_post_meta( $post_id, '_fct_account_id', (int) $account_id );
}

/**
 * Update a posts year meta ID
 *
 * @param int $post_id The post to update
 * @param int $year_id The year
 */
function fct_update_year_id( $post_id, $year_id ) {

	// Allow the year ID to be updated 'just in time' before save
	$year_id = apply_filters( 'fct_update_year_id', $year_id, $post_id );

	// Update the post meta year ID
	update_post_meta( $post_id, '_fct_year_id', (int) $year_id );
}

/** Post Type *****************************************************************/

/**
 * Return the Fiscaat's object type from the post type
 *
 * @since 0.0.8
 *
 * @uses fct_get_record_post_type()
 * @uses fct_get_account_post_type()
 * @uses fct_get_year_post_type()
 * @param string $post_type Post type
 * @return string|bool Fiscaat object type or False if not Fiscaat's
 */
function fct_get_post_type_type( $post_type = '' ) {

	// Setup local var
	$type = false;
	
	// Default to global post type
	if ( empty( $post_type ) && isset( $GLOBALS['post_type'] ) ) {
		$post_type = $GLOBALS['post_type'];
	}

	switch ( $post_type ) {
		case fct_get_record_post_type() :
			$type = 'record';
			break;

		case fct_get_account_post_type() :
			$type = 'account';
			break;

		case fct_get_year_post_type() :
			$type = 'year';
			break;
	}

	return $type;
}

/** Errors ********************************************************************/

/**
 * Adds an error message to later be output in the theme
 *
 * @see WP_Error()
 * @uses WP_Error::add();
 *
 * @param string $code Unique code for the error message
 * @param string $message Translated error message
 * @param string $data Any additional data passed with the error message
 */
function fct_add_error( $code = '', $message = '', $data = '' ) {
	fiscaat()->errors->add( $code, $message, $data );
}

/**
 * Check if error messages exist in queue
 *
 * @see WP_Error()
 *
 * @uses is_wp_error()
 * @usese WP_Error::get_error_codes()
 */
function fct_has_errors() {
	$has_errors = fiscaat()->errors->get_error_codes() ? true : false;

	return apply_filters( 'fct_has_errors', $has_errors, fiscaat()->errors );
}

/** Post Statuses *************************************************************/

/**
 * Return the public post status ID
 *
 * @return string
 */
function fct_get_public_status_id() {
	return fiscaat()->public_status_id;
}

/**
 * Return the closed post status ID
 *
 * @return string
 */
function fct_get_closed_status_id() {
	return fiscaat()->closed_status_id;
}

/** Rewrite IDs ***************************************************************/

/**
 * Return the enique ID for all edit rewrite rules (year|account|record)
 *
 * @return string
 */
function fct_get_edit_rewrite_id() {
	return fiscaat()->edit_id;
}

/**
 * Delete a blogs rewrite rules, so that they are automatically rebuilt on
 * the subsequent page load.
 */
function fct_delete_rewrite_rules() {
	delete_option( 'rewrite_rules' );
}