<?php

/**
 * Fiscaat Control Periods Functions
 *
 * @package Fiscaat
 * @subpackage Control
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** Filters ********************************************************************/

/**
 * Add control period default meta
 * 
 * @param array $meta Period meta
 * @return array Meta
 */
function fct_ctrl_get_period_default_meta( $meta ) {
	return array_merge( $meta, array(
		'record_count_declined'   => 0, // Declined record count
		'record_count_unapproved' => 0, // Unapproved record count
	) );
}

/** Records ********************************************************************/

/**
 * Bump the total declined record count of a period
 *
 * @param int $period_id Optional. Period id.
 * @param int $difference Optional. Default 1
 * @uses fct_get_period_id() To get the period id
 * @uses fct_update_period_meta() To update the period's record count meta
 * @uses apply_filters() Calls 'fct_bump_period_record_count_declined' with the
 *                        record count, period id, and difference
 * @return int Period declined record count
 */
function fct_bump_period_record_count_declined( $period_id = 0, $difference = 1 ) {

	// Get some counts
	$period_id    = fct_get_period_id( $period_id );
	$record_count = fct_get_period_record_count_declined( $period_id, false );
	$new_count    = (int) $record_count + (int) $difference;

	// Update this period id
	fct_update_period_meta( $period_id, 'record_count_declined', (int) $new_count );

	return (int) apply_filters( 'fct_bump_period_record_count_declined', (int) $new_count, $period_id, (int) $difference );
}

/**
 * Bump the total unapproved record count of a period
 *
 * @param int $period_id Optional. Period id.
 * @param int $difference Optional. Default 1
 * @uses fct_get_period_id() To get the period id
 * @uses fct_update_period_meta() To update the period's record count meta
 * @uses apply_filters() Calls 'fct_bump_period_record_count_unapproved' with the
 *                        record count, period id, and difference
 * @return int Period unapproved record count
 */
function fct_bump_period_record_count_unapproved( $period_id = 0, $difference = 1 ) {

	// Get some counts
	$period_id    = fct_get_period_id( $period_id );
	$record_count = fct_get_period_record_count_unapproved( $period_id, false );
	$new_count    = (int) $record_count + (int) $difference;

	// Update this period id
	fct_update_period_meta( $period_id, 'record_count_unapproved', (int) $new_count );

	return (int) apply_filters( 'fct_bump_period_record_count_unapproved', (int) $new_count, $period_id, (int) $difference );
}

/**
 * Adjust the total declined record count of a period
 *
 * @param int $period_id Optional. Period id or record id. It is checked whether it
 *                       is a record or a period. If it's a record, its grandparent,
 *                       i.e. the period is automatically retrieved.
 * @param int $record_count Optional. Set the record count manually
 * @uses fct_is_record() To check if the supplied id is a record
 * @uses fct_get_record_id() To get the record id
 * @uses fct_get_record_period_id() To get the record period id
 * @uses fct_get_period_id() To get the period id
 * @uses wpdb::prepare() To prepare our sql query
 * @uses wpdb::get_var() To execute our query and get the count var back
 * @uses fct_update_period_meta() To update the period declined record count meta
 * @uses apply_filters() Calls 'fct_update_period_record_count_declined' with the
 *                        declined record count and period id
 * @return int Account declined record count
 */
function fct_update_period_record_count_declined( $period_id = 0, $record_count = 0 ) {
	global $wpdb;

	// If record_id was passed as $period_id, then get its period
	if ( fct_is_record( $period_id ) ) {
		$record_id = fct_get_record_id( $period_id );
		$period_id = fct_get_record_period_id( $record_id );

	// $period_id is not a record_id, so validate and proceed
	} else {
		$period_id = fct_get_period_id( $period_id );
	}

	// Get records of period
	if ( empty( $record_count ) ) {
		$record_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_parent = %d AND post_status = '%s' AND post_type = '%s';", $period_id, fct_get_declined_status_id(), fct_get_record_post_type() ) );
	}

	// Update the count
	fct_update_period_meta( $period_id, 'record_count_declined', (int) $record_count );

	return (int) apply_filters( 'fct_update_period_record_count_declined', (int) $record_count, $period_id );
}

/**
 * Adjust the total unapproved record count of a period
 *
 * @param int $period_id Optional. Period id or record id. It is checked whether it
 *                       is a record or a period. If it's a record, its grandparent,
 *                       i.e. the period is automatically retrieved.
 * @param int $record_count Optional. Set the record count manually
 * @uses fct_is_record() To check if the supplied id is a record
 * @uses fct_get_record_id() To get the record id
 * @uses fct_get_record_period_id() To get the record period id
 * @uses fct_get_period_id() To get the period id
 * @uses wpdb::prepare() To prepare our sql query
 * @uses wpdb::get_var() To execute our query and get the count var back
 * @uses fct_update_period_meta() To update the period unapproved record count meta
 * @uses apply_filters() Calls 'fct_update_period_record_count_unapproved' with the
 *                        unapproved record count and period id
 * @return int Account unapproved record count
 */
function fct_update_period_record_count_unapproved( $period_id = 0, $record_count = 0 ) {
	global $wpdb;

	// If record_id was passed as $period_id, then get its period
	if ( fct_is_record( $period_id ) ) {
		$record_id = fct_get_record_id( $period_id );
		$period_id = fct_get_record_period_id( $record_id );

	// $period_id is not a record_id, so validate and proceed
	} else {
		$period_id = fct_get_period_id( $period_id );
	}

	// Get records of period
	if ( empty( $record_count ) ) {
		$record_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_parent = %d AND post_status NOT IN ( '" . join( '\',\'', array( fct_get_approved_status_id(), fct_get_closed_status_id() ) ) . "' ) AND post_type = '%s';", $period_id, fct_get_record_post_type() ) );
	}

	// Update the count
	fct_update_period_meta( $period_id, 'record_count_unapproved', (int) $record_count );

	return (int) apply_filters( 'fct_update_period_record_count_unapproved', (int) $record_count, $period_id );
}
