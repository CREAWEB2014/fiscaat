<?php

/**
 * Fiscaat Control Years Functions
 *
 * @package Fiscaat
 * @subpackage Functions
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** Year Actions ***************************************************************/

/**
 * Add control year default meta
 * 
 * @param array $meta Year meta
 * @return array Meta
 */
function fct_ctrl_get_year_default_meta( $meta ) {
	
	// Declined record count
	$meta['record_count_declined'] = 0; 

	// Unapproved record count
	$meta['record_count_unapproved'] = 0; 

	return $meta;
}

/**
 * Bump the total declined record count of a year
 *
 * @param int $year_id Optional. Year id.
 * @param int $difference Optional. Default 1
 * @uses fct_get_year_id() To get the year id
 * @uses fct_update_year_meta() To update the year's record count meta
 * @uses apply_filters() Calls 'fct_bump_year_record_count_declined' with the
 *                        record count, year id, and difference
 * @return int Year declined record count
 */
function fct_bump_year_record_count_declined( $year_id = 0, $difference = 1 ) {

	// Get some counts
	$year_id      = fct_get_year_id( $year_id );
	$record_count = fct_get_year_record_count_declined( $year_id, false );
	$new_count    = (int) $record_count + (int) $difference;

	// Update this year id
	fct_update_year_meta( $year_id, 'record_count_declined', (int) $new_count );

	return (int) apply_filters( 'fct_bump_year_record_count_declined', (int) $new_count, $year_id, (int) $difference );
}

/**
 * Bump the total unapproved record count of a year
 *
 * @param int $year_id Optional. Year id.
 * @param int $difference Optional. Default 1
 * @uses fct_get_year_id() To get the year id
 * @uses fct_update_year_meta() To update the year's record count meta
 * @uses apply_filters() Calls 'fct_bump_year_record_count_unapproved' with the
 *                        record count, year id, and difference
 * @return int Year unapproved record count
 */
function fct_bump_year_record_count_unapproved( $year_id = 0, $difference = 1 ) {

	// Get some counts
	$year_id      = fct_get_year_id( $year_id );
	$record_count = fct_get_year_record_count_unapproved( $year_id, false );
	$new_count    = (int) $record_count + (int) $difference;

	// Update this year id
	fct_update_year_meta( $year_id, 'record_count_unapproved', (int) $new_count );

	return (int) apply_filters( 'fct_bump_year_record_count_unapproved', (int) $new_count, $year_id, (int) $difference );
}

/**
 * Adjust the total declined record count of a year
 *
 * @param int $year_id Optional. Year id or record id. It is checked whether it
 *                       is a record or a year. If it's a record, its grandparent,
 *                       i.e. the year is automatically retrieved.
 * @param int $record_count Optional. Set the record count manually
 * @uses fct_is_record() To check if the supplied id is a record
 * @uses fct_get_record_id() To get the record id
 * @uses fct_get_record_year_id() To get the record year id
 * @uses fct_get_year_id() To get the year id
 * @uses wpdb::prepare() To prepare our sql query
 * @uses wpdb::get_var() To execute our query and get the count var back
 * @uses fct_update_year_meta() To update the year declined record count meta
 * @uses apply_filters() Calls 'fct_update_year_record_count_declined' with the
 *                        declined record count and year id
 * @return int Account declined record count
 */
function fct_update_year_record_count_declined( $year_id = 0, $record_count = 0 ) {
	global $wpdb;

	// If record_id was passed as $year_id, then get its year
	if ( fct_is_record( $year_id ) ) {
		$record_id = fct_get_record_id( $year_id );
		$year_id   = fct_get_record_year_id( $record_id );

	// $year_id is not a record_id, so validate and proceed
	} else {
		$year_id   = fct_get_year_id( $year_id );
	}

	// Get records of year
	if ( empty( $record_count ) )
		$record_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_parent = %d AND post_status = '%s' AND post_type = '%s';", $year_id, fct_get_declined_status_id(), fct_get_record_post_type() ) );

	// Update the count
	fct_update_year_meta( $year_id, 'record_count_declined', (int) $record_count );

	return (int) apply_filters( 'fct_update_year_record_count_declined', (int) $record_count, $year_id );
}

/**
 * Adjust the total unapproved record count of a year
 *
 * @param int $year_id Optional. Year id or record id. It is checked whether it
 *                       is a record or a year. If it's a record, its grandparent,
 *                       i.e. the year is automatically retrieved.
 * @param int $record_count Optional. Set the record count manually
 * @uses fct_is_record() To check if the supplied id is a record
 * @uses fct_get_record_id() To get the record id
 * @uses fct_get_record_year_id() To get the record year id
 * @uses fct_get_year_id() To get the year id
 * @uses wpdb::prepare() To prepare our sql query
 * @uses wpdb::get_var() To execute our query and get the count var back
 * @uses fct_update_year_meta() To update the year unapproved record count meta
 * @uses apply_filters() Calls 'fct_update_year_record_count_unapproved' with the
 *                        unapproved record count and year id
 * @return int Account unapproved record count
 */
function fct_update_year_record_count_unapproved( $year_id = 0, $record_count = 0 ) {
	global $wpdb;

	// If record_id was passed as $year_id, then get its year
	if ( fct_is_record( $year_id ) ) {
		$record_id = fct_get_record_id( $year_id );
		$year_id   = fct_get_record_year_id( $record_id );

	// $year_id is not a record_id, so validate and proceed
	} else {
		$year_id   = fct_get_year_id( $year_id );
	}

	// Get records of year
	if ( empty( $record_count ) )
		$record_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_parent = %d AND post_status NOT IN ( '" . join( '\',\'', array( fct_get_approved_status_id(), fct_get_closed_status_id() ) ) . "' ) AND post_type = '%s';", $year_id, fct_get_record_post_type() ) );

	// Update the count
	fct_update_year_meta( $year_id, 'record_count_unapproved', (int) $record_count );

	return (int) apply_filters( 'fct_update_year_record_count_unapproved', (int) $record_count, $year_id );
}

/**
 * Return whether to bail before closing a year
 *
 * Bail year closing if it has unapproved records.
 * 
 * @param bool $bail Whether to bail
 * @param object $year Year object
 * @return bool Bail
 */
function fct_ctrl_pre_close_year_bail( $bail, $year ) {

	// Get unapproved records of year
	$unapproved = fct_get_year_meta( $year->ID, 'record_count_unapproved' );
	
	// Bail if year has unapproved records
	return ! empty( $unapproved );
}

