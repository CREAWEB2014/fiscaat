<?php

/**
 * Fiscaat Control Accounts Functions
 *
 * @package Fiscaat
 * @subpackage Functions
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** Account Actions ************************************************************/

/**
 * Add control account default meta
 * 
 * @param array $meta Account meta
 * @return array Meta
 */
function fct_ctrl_get_account_default_meta( $meta ) {
	return array_merge( $meta, array(
		'record_count_declined'   => 0, // Declined record count
		'record_count_unapproved' => 0, // Unapproved record count
	) );
}

/**
 * Bump the total declined record count of an account
 *
 * @param int $account_id Optional. Account id.
 * @param int $difference Optional. Default 1
 * @uses fct_get_account_id() To get the account id
 * @uses fct_update_account_meta() To update the account's record count meta
 * @uses apply_filters() Calls 'fct_bump_account_record_count_declined' with the
 *                        record count, account id, and difference
 * @return int Account declined record count
 */
function fct_bump_account_record_count_declined( $account_id = 0, $difference = 1 ) {

	// Get counts
	$account_id   = fct_get_account_id( $account_id );
	$record_count = fct_get_account_record_count_declined( $account_id, false );
	$new_count    = (int) $record_count + (int) $difference;

	// Update this account id's declined record count
	fct_update_account_meta( $account_id, 'record_count_declined', (int) $new_count );

	return (int) apply_filters( 'fct_bump_account_record_count_declined', (int) $new_count, $account_id, (int) $difference );
}

/**
 * Bump the total unapproved record count of an account
 *
 * @param int $account_id Optional. Account id.
 * @param int $difference Optional. Default 1
 * @uses fct_get_account_id() To get the account id
 * @uses fct_update_account_meta() To update the account's record count meta
 * @uses apply_filters() Calls 'fct_bump_account_record_count_unapproved' with the
 *                        record count, account id, and difference
 * @return int Account unapproved record count
 */
function fct_bump_account_record_count_unapproved( $account_id = 0, $difference = 1 ) {

	// Get counts
	$account_id   = fct_get_account_id( $account_id );
	$record_count = fct_get_account_record_count_unapproved( $account_id, false );
	$new_count    = (int) $record_count + (int) $difference;

	// Update this account id's unapproved record count
	fct_update_account_meta( $account_id, 'record_count_unapproved', (int) $new_count );

	return (int) apply_filters( 'fct_bump_account_record_count_unapproved', (int) $new_count, $account_id, (int) $difference );
}

/**
 * Adjust the total unapproved record count of an account
 *
 * @param int $account_id Optional. Account id to update
 * @param int $record_count Optional. Set the record count manually
 * @uses fct_is_record() To check if the passed account id is a record
 * @uses fct_get_record_account_id() To get the record account id
 * @uses fct_get_account_id() To get the account id
 * @uses fct_get_record_post_type() To get the record post type
 * @uses wpdb::prepare() To prepare our sql query
 * @uses wpdb::get_var() To execute our query and get the var back
 * @uses fct_update_account_meta() To update the account unapproved record count meta
 * @uses apply_filters() Calls 'fct_update_account_record_count_unapproved' with the
 *                        unapproved record count and account id
 * @return int Account unapproved record count
 */
function fct_update_account_record_count_unapproved( $account_id = 0, $record_count = 0 ) {
	global $wpdb;

	// If it's a record, then get the parent (account id)
	if ( fct_is_record( $account_id ) )
		$account_id = fct_get_record_account_id( $account_id );
	else
		$account_id = fct_get_account_id( $account_id );

	// Get records of account
	if ( empty( $record_count ) )
		$record_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_parent = %d AND post_status NOT IN ( '" . join( '\',\'', array( fct_get_approved_status_id(), fct_get_closed_status_id() ) ) . "') AND post_type = '%s';", $account_id, fct_get_record_post_type() ) );

	fct_update_account_meta( $account_id, 'record_count_unapproved', (int) $record_count );

	return apply_filters( 'fct_update_account_record_count_unapproved', (int) $record_count, $account_id );
}

/**
 * Adjust the total declined record count of an account
 *
 * @param int $account_id Optional. Account id to update
 * @param int $record_count Optional. Set the record count manually
 * @uses fct_is_record() To check if the passed account id is a record
 * @uses fct_get_record_account_id() To get the record account id
 * @uses fct_get_account_id() To get the account id
 * @uses fct_get_record_post_type() To get the record post type
 * @uses wpdb::prepare() To prepare our sql query
 * @uses wpdb::get_var() To execute our query and get the var back
 * @uses fct_update_account_meta() To update the account declined record count meta
 * @uses apply_filters() Calls 'fct_update_account_record_count_declined' with the
 *                        declined record count and account id
 * @return int Account declined record count
 */
function fct_update_account_record_count_declined( $account_id = 0, $record_count = 0 ) {
	global $wpdb;

	// If it's a record, then get the parent (account id)
	if ( fct_is_record( $account_id ) )
		$account_id = fct_get_record_account_id( $account_id );
	else
		$account_id = fct_get_account_id( $account_id );

	// Get records of account
	if ( empty( $record_count ) )
		$record_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_parent = %d AND post_status = '%s' AND post_type = '%s';", $account_id, fct_get_declined_status_id(), fct_get_record_post_type() ) );

	fct_update_account_meta( $account_id, 'record_count_declined', (int) $record_count );

	return apply_filters( 'fct_update_account_record_count_declined', (int) $record_count, $account_id );
}
