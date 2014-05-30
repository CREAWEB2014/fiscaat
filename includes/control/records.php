<?php

/**
 * Fiscaat Control Record Functions
 *
 * @package Fiscaat
 * @subpackage Control
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** Record Actions *************************************************************/

/**
 * Marks a record as declined
 *
 * @uses get_post() To get the record
 * @uses do_action() Calls 'fct_decline_record' with the record ID
 * @uses add_post_meta() To add the previous status to a meta
 * @uses wp_insert_post() To insert the updated post
 * @uses do_action() Calls 'fct_declined_record' with the record ID
 * @param int $record_id Record id
 * @return mixed False or {@link WP_Error} on failure, record id on success
 */
function fct_decline_record( $record_id = 0 ) {

	// Get record
	$record = get_post( $record_id, ARRAY_A );
	if ( empty( $record ) )
		return $record;

	// Bail if already declined
	if ( fct_get_declined_status_id() == $record['post_status'] )
		return false;

	// Execute pre declined code
	do_action( 'fct_decline_record', $record_id );

	// Set post status to declined
	$record['post_status'] = fct_get_declined_status_id();

	// No revisions
	remove_action( 'pre_post_update', 'wp_save_post_revision' );

	// Update the record
	$record_id = wp_insert_post( $record );

	// Execute post declined code
	do_action( 'fct_declined_record', $record_id );

	// Return record_id
	return $record_id;
}

/**
 * Approves a record
 *
 * @uses get_post() To get the record
 * @uses do_action() Calls 'fct_approve_record' with the record ID
 * @uses get_post_meta() To get the previous status meta
 * @uses delete_post_meta() To delete the previous status meta
 * @uses wp_insert_post() To insert the updated post
 * @uses do_action() Calls 'fct_approved_record' with the record ID
 * @param int $record_id Record id
 * @return mixed False or {@link WP_Error} on failure, record id on success
 */
function fct_approve_record( $record_id = 0 ) {

	// Get record
	$record = get_post( $record_id, ARRAY_A );
	if ( empty( $record ) )
		return $record;

	// Bail if already approved
	if ( fct_get_approved_status_id() == $record['post_status'] )
		return false;

	// Execute pre approve code
	do_action( 'fct_approve_record', $record_id );

	// Set post status to approved
	$record['post_status'] = fct_get_approved_status_id();

	// No revisions
	remove_action( 'pre_post_update', 'wp_save_post_revision' );

	// Update the record
	$record_id = wp_insert_post( $record );

	// Execute post approve code
	do_action( 'fct_approved_record', $record_id );

	// Return record_id
	return $record_id;
}
