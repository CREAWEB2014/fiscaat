<?php

/**
 * Fiscaat Year Functions
 *
 * @package Fiscaat
 * @subpackage Functions
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** Meta **********************************************************************/

/**
 * Return default year meta keys with values
 *
 * @param int $year_id
 * @return array
 */
function fiscaat_get_year_default_meta(){
	return (array) apply_filters( 'fiscaat_get_year_default_meta', array(
		'closed'                  => 0, // Year date closed
		'account_count'           => 0, // Total year account count
		'record_count'            => 0, // Total record count
		'record_count_unapproved' => 0, // Unapproved record count
		'record_count_declined'   => 0, // Declined record count
		'to_balance'              => 0, // Current result to balance
	) );
}

/**
 * Return stored meta value for given year
 *
 * @uses get_post_meta()
 * 
 * @param int $year_id Year id
 * @param string $meta_key Meta key
 * @return mixed $meta_value
 */
function fiscaat_get_year_meta( $year_id, $meta_key ){
	$meta_value = get_post_meta( $year_id, '_fiscaat_'. $meta_key, true );
	return apply_filters( 'fiscaat_get_year_meta', $meta_value, $year_id, $meta_key );
}

/**
 * Update meta value for given year
 *
 * @uses update_post_meta()
 * 
 * @param int $year_id
 * @param string $meta_key
 * @param mixed $meta_value
 * @return boolean
 */
function fiscaat_update_year_meta( $year_id, $meta_key, $meta_value ){
	return update_post_meta( $year_id, '_fiscaat_'. $meta_key, $meta_value );
}

/**
 * Delete meta value for given year
 * 
 * @param int $year_id
 * @param string $meta_key
 * @return boolean
 */
function fiscaat_delete_year_meta( $year_id, $meta_key ){
	return delete_post_meta( $year_id, '_fiscaat_'. $meta_key );
}

/** Insert ********************************************************************/

/**
 * A wrapper for wp_insert_post() that also includes the necessary meta values
 * for the year to function properly.
 *
 * @uses fiscaat_parse_args()
 * @uses fiscaat_get_year_post_type()
 * @uses wp_insert_post()
 * @uses update_post_meta()
 *
 * @param array $year_data Year post data
 * @param arrap $year_meta Year meta data
 */
function fiscaat_insert_year( $year_data = array(), $year_meta = array() ) {

	// Year
	$default_year = array(
		'post_parent'    => 0, // year ID
		'post_status'    => fiscaat_get_public_status_id(),
		'post_type'      => fiscaat_get_year_post_type(),
		'post_author'    => fiscaat_get_current_user_id(),
		'post_password'  => '',
		'post_content'   => '',
		'post_title'     => '',
		'menu_order'     => 0,
		'comment_status' => 'closed'
	);
	$year_data = fiscaat_parse_args( $year_data, $default_year, 'insert_year' );

	// Insert year
	$year_id   = wp_insert_post( $year_data );

	// Bail if no year was added
	if ( empty( $year_id ) )
		return false;

	// Year meta
	$year_meta = fiscaat_parse_args( $year_meta, fiscaat_get_year_default_meta(), 'insert_year_meta' );

	// Insert year meta
	foreach ( $year_meta as $meta_key => $meta_value )
		fiscaat_update_year_meta( $year_id, $meta_key, $meta_value );

	// Return new year ID
	return $year_id;
}

/** Year Actions *************************************************************/

/**
 * Closes a year
 *
 * @param int $year_id year id
 * @uses get_post() To get the year
 * @uses do_action() Calls 'fiscaat_close_year' with the year id
 * @uses add_post_meta() To add the previous status to a meta
 * @uses fiscaat_update_year_meta() To add the previous close date to a meta
 * @uses wp_insert_post() To update the year with the new status
 * @uses do_action() Calls 'fiscaat_opened_year' with the year id
 * @return mixed False or {@link WP_Error} on failure, year id on success
 */
function fiscaat_close_year( $year_id = 0 ) {

	// Get year
	if ( !$year = get_post( $year_id, ARRAY_A ) )
		return $year;

	// Bail if already closed
	if ( fiscaat_get_closed_status_id() == $year['post_status'] )
		return false;

	// Bail if year has unapproved records
	if ( 0 != fiscaat_get_year_meta( $year_id, 'record_count_unapproved' ) )
		return false;

	// Execute pre close code
	do_action( 'fiscaat_close_year', $year_id );

	// Add pre close status
	add_post_meta( $year_id, '_fiscaat_status', $year['post_status'] );

	// Set closed status
	$year['post_status'] = fiscaat_get_closed_status_id();

	// Set closed date
	fiscaat_update_year_closed( $year_id );

	// No revisions
	remove_action( 'pre_post_update', 'wp_save_post_revision' );

	// Update year
	$year_id = wp_insert_post( $year );

	// Execute post close code
	do_action( 'fiscaat_closed_year', $year_id );

	// Return year_id
	return $year_id;
}

/**
 * Opens a year
 *
 * @param int $year_id year id
 * @uses get_post() To get the year
 * @uses do_action() Calls 'fiscaat_open_year' with the year id
 * @uses get_post_meta() To get the previous status
 * @uses delete_post_meta() To delete the previous status meta
 * @uses fiscaat_update_year_meta() To delete the previous close date meta
 * @uses wp_insert_post() To update the year with the new status
 * @uses do_action() Calls 'fiscaat_opened_year' with the year id
 * @return mixed False or {@link WP_Error} on failure, year id on success
 */
function fiscaat_open_year( $year_id = 0 ) {

	// Get year
	if ( !$year = get_post( $year_id, ARRAY_A ) )
		return $year;

	// Bail if already open
	if ( fiscaat_get_closed_status_id() != $year['post_status'])
		return false;

	// Execute pre open code
	do_action( 'fiscaat_open_year', $year_id );

	// Get previous status
	$year_status         = get_post_meta( $year_id, '_fiscaat_status', true );

	// Set previous status
	$year['post_status'] = $year_status;

	// Unset closed date
	fiscaat_update_year_meta( $year_id, 'closed', 0 );

	// Remove old status meta
	delete_post_meta( $year_id, '_fiscaat_status' );

	// No revisions
	remove_action( 'pre_post_update', 'wp_save_post_revision' );

	// Update year
	$year_id = wp_insert_post( $year );

	// Execute post open code
	do_action( 'fiscaat_opened_year', $year_id );

	// Return year_id
	return $year_id;
}

/** Count Bumpers *************************************************************/

/**
 * Bump the total record count of a year
 *
 * @param int $year_id Optional. Year id.
 * @param int $difference Optional. Default 1
 * @uses fiscaat_get_year_id() To get the year id
 * @uses fiscaat_update_year_meta() To update the year's record count meta
 * @uses apply_filters() Calls 'fiscaat_bump_year_record_count' with the record
 *                        count, year id, and difference
 * @return int Year record count
 */
function fiscaat_bump_year_record_count( $year_id = 0, $difference = 1 ) {

	// Get some counts
	$year_id      = fiscaat_get_year_id( $year_id );
	$record_count = fiscaat_get_year_record_count( $year_id, false, false );
	$new_count    = (int) $record_count + (int) $difference;

	// Update this year id
	fiscaat_update_year_meta( $year_id, 'record_count', (int) $new_count );

	return (int) apply_filters( 'fiscaat_bump_year_record_count', (int) $new_count, $year_id, (int) $difference );
}

/**
 * Bump the total declined record count of a year
 *
 * @param int $year_id Optional. Year id.
 * @param int $difference Optional. Default 1
 * @uses fiscaat_get_year_id() To get the year id
 * @uses fiscaat_update_year_meta() To update the year's record count meta
 * @uses apply_filters() Calls 'fiscaat_bump_year_record_count_declined' with the
 *                        record count, year id, and difference
 * @return int Year declined record count
 */
function fiscaat_bump_year_record_count_declined( $year_id = 0, $difference = 1 ) {

	// Get some counts
	$year_id      = fiscaat_get_year_id( $year_id );
	$record_count = fiscaat_get_year_record_count_declined( $year_id, false );
	$new_count    = (int) $record_count + (int) $difference;

	// Update this year id
	fiscaat_update_year_meta( $year_id, 'record_count_declined', (int) $new_count );

	return (int) apply_filters( 'fiscaat_bump_year_record_count_declined', (int) $new_count, $year_id, (int) $difference );
}

/**
 * Bump the total unapproved record count of a year
 *
 * @param int $year_id Optional. Year id.
 * @param int $difference Optional. Default 1
 * @uses fiscaat_get_year_id() To get the year id
 * @uses fiscaat_update_year_meta() To update the year's record count meta
 * @uses apply_filters() Calls 'fiscaat_bump_year_record_count_unapproved' with the
 *                        record count, year id, and difference
 * @return int Year unapproved record count
 */
function fiscaat_bump_year_record_count_unapproved( $year_id = 0, $difference = 1 ) {

	// Get some counts
	$year_id      = fiscaat_get_year_id( $year_id );
	$record_count = fiscaat_get_year_record_count_unapproved( $year_id, false );
	$new_count    = (int) $record_count + (int) $difference;

	// Update this year id
	fiscaat_update_year_meta( $year_id, 'record_count_unapproved', (int) $new_count );

	return (int) apply_filters( 'fiscaat_bump_year_record_count_unapproved', (int) $new_count, $year_id, (int) $difference );
}

/**
 * Bump the total account count of a year
 *
 * @param int $year_id Optional. Year id.
 * @param int $difference Optional. Default 1
 * @param bool $update_ancestors Optional. Default true
 * @uses fiscaat_get_year_id() To get the year id
 * @uses fiscaat_update_year_meta() To update the year's account count meta
 * @uses apply_filters() Calls 'fiscaat_bump_year_account_count' with the account
 *                        count, year id, and difference
 * @return int Year account count
 */
function fiscaat_bump_year_account_count( $year_id = 0, $difference = 1 ) {

	// Get some counts
	$year_id       = fiscaat_get_year_id( $year_id );
	$account_count = fiscaat_get_year_account_count( $year_id, false );
	$new_count     = (int) $account_count + (int) $difference;

	// Update this year id
	fiscaat_update_year_meta( $year_id, 'account_count', (int) $new_count );

	return (int) apply_filters( 'fiscaat_bump_year_account_count', (int) $new_count, $year_id, (int) $difference );
}

/** Year Updaters ************************************************************/

/**
 * Handle the saving of core year metadata (Status and Close date)
 *
 * @param int $year_id
 * @uses fiscaat_is_year_closed() To check if year is closed
 * @uses fiscaat_close_year() To close year
 * @uses fiscaat_open_year() To open year
 * @return If year ID is empty
 */
function fiscaat_save_year_extras( $year_id = 0 ) {

	// Validate the year ID
	$year_id = fiscaat_get_year_id( $year_id );

	// Bail if year ID is empty
	if ( empty( $year_id ) || ! fiscaat_is_year( $year_id ) )
		return;

	/** Year Status ******************************************************/

	if ( ! empty( $_POST['fiscaat_year_status'] ) && in_array( $_POST['fiscaat_year_status'], array( 'open', 'closed' ) ) ) {
		if ( 'closed' == $_POST['fiscaat_year_status'] && ! fiscaat_is_year_closed( $year_id, false ) ) {
			fiscaat_close_year( $year_id );
		} elseif ( 'open' == $_POST['fiscaat_year_status'] && fiscaat_is_year_closed( $year_id, false ) ) {
			fiscaat_open_year( $year_id );
		}
	}

	/** Close date *******************************************************/

	if ( ! empty( $_POST['fiscaat_year_closed'] ) && ! fiscaat_is_year_closed( $year_id, false ) ) {
		fiscaat_update_year_closed( $year_id );
	}
}

/**
 * Adjust the close date of a year with current time
 * 
 * @param int $year_id Optional. Year id
 * @param string $date Mysql date string
 * @uses fiscaat_get_year_id() To get the year id
 * @uses fiscaat_get_current_time() To get the current date in mysql format
 * @return string Year close date
 */
function fiscaat_update_year_closed( $year_id = 0, $date = '' ) {
	$year_id = fiscaat_get_year_id( $year_id );
	
	// Require close date
	if ( empty( $date ) )
		return false;

	fiscaat_update_year_meta( $year_id, 'closed', $date );

	return apply_filters( 'fiscaat_update_year_closed', $date, $year_id );
}

/**
 * Adjust the total account count of a year
 *
 * @param int $year_id Optional. Year id or account id. It is checked whether it
 *                       is a account or a year. If it's a account, its parent,
 *                       i.e. the year is automatically retrieved.
 * @uses fiscaat_get_year_id() To get the year id
 * @uses fiscaat_get_account_year_id() To get the account year id
 * @uses fiscaat_update_year_account_count() To update the year account count
 * @uses fiscaat_year_query_account_ids() To get the year account ids
 * @uses fiscaat_update_year_meta() To update the year's account count meta
 * @uses apply_filters() Calls 'fiscaat_update_year_account_count' with the account
 *                        count and year id
 * @return int Year account count
 */
function fiscaat_update_year_account_count( $year_id = 0 ) {

	// If account_id was passed as $year_id, then get its year
	if ( fiscaat_is_account( $year_id ) ) {
		$account_id = fiscaat_get_account_id( $year_id );
		$year_id    = fiscaat_get_account_year_id( $account_id );

	// $year_id is not an account_id, so validate and proceed
	} else {
		$year_id    = fiscaat_get_year_id( $year_id );
	}

	// Get total accounts for this year
	$accounts = (int) count( fiscaat_year_query_account_ids( $year_id ) );

	// Update the count
	fiscaat_update_year_meta( $year_id, 'account_count', (int) $accounts );

	return (int) apply_filters( 'fiscaat_update_year_account_count', (int) $accounts, $year_id );
}

/**
 * Adjust the total declined record count of a year
 *
 * @param int $year_id Optional. Year id or record id. It is checked whether it
 *                       is a record or a year. If it's a record, its grandparent,
 *                       i.e. the year is automatically retrieved.
 * @param int $record_count Optional. Set the record count manually
 * @uses fiscaat_is_record() To check if the supplied id is a record
 * @uses fiscaat_get_record_id() To get the record id
 * @uses fiscaat_get_record_year_id() To get the record year id
 * @uses fiscaat_get_year_id() To get the year id
 * @uses wpdb::prepare() To prepare our sql query
 * @uses wpdb::get_var() To execute our query and get the count var back
 * @uses fiscaat_update_year_meta() To update the year declined record count meta
 * @uses apply_filters() Calls 'fiscaat_update_year_record_count_declined' with the
 *                        declined record count and year id
 * @return int Account declined record count
 */
function fiscaat_update_year_record_count_declined( $year_id = 0, $record_count = 0 ) {
	global $wpdb;

	// If record_id was passed as $year_id, then get its year
	if ( fiscaat_is_record( $year_id ) ) {
		$record_id = fiscaat_get_record_id( $year_id );
		$year_id   = fiscaat_get_record_year_id( $record_id );

	// $year_id is not a record_id, so validate and proceed
	} else {
		$year_id   = fiscaat_get_year_id( $year_id );
	}

	// Get records of year
	if ( empty( $record_count ) )
		$record_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_parent = %d AND post_status = '%s' AND post_type = '%s';", $year_id, fiscaat_get_declined_status_id(), fiscaat_get_record_post_type() ) );

	// Update the count
	fiscaat_update_year_meta( $year_id, 'record_count_declined', (int) $record_count );

	return (int) apply_filters( 'fiscaat_update_year_record_count_declined', (int) $record_count, $year_id );
}

/**
 * Adjust the total unapproved record count of a year
 *
 * @param int $year_id Optional. Year id or record id. It is checked whether it
 *                       is a record or a year. If it's a record, its grandparent,
 *                       i.e. the year is automatically retrieved.
 * @param int $record_count Optional. Set the record count manually
 * @uses fiscaat_is_record() To check if the supplied id is a record
 * @uses fiscaat_get_record_id() To get the record id
 * @uses fiscaat_get_record_year_id() To get the record year id
 * @uses fiscaat_get_year_id() To get the year id
 * @uses wpdb::prepare() To prepare our sql query
 * @uses wpdb::get_var() To execute our query and get the count var back
 * @uses fiscaat_update_year_meta() To update the year unapproved record count meta
 * @uses apply_filters() Calls 'fiscaat_update_year_record_count_unapproved' with the
 *                        unapproved record count and year id
 * @return int Account unapproved record count
 */
function fiscaat_update_year_record_count_unapproved( $year_id = 0, $record_count = 0 ) {
	global $wpdb;

	// If record_id was passed as $year_id, then get its year
	if ( fiscaat_is_record( $year_id ) ) {
		$record_id = fiscaat_get_record_id( $year_id );
		$year_id   = fiscaat_get_record_year_id( $record_id );

	// $year_id is not a record_id, so validate and proceed
	} else {
		$year_id   = fiscaat_get_year_id( $year_id );
	}

	// Get records of year
	if ( empty( $record_count ) )
		$record_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_parent = %d AND post_status NOT IN ( '" . join( '\',\'', array( fiscaat_get_approved_status_id(), fiscaat_get_closed_status_id() ) ) . "' ) AND post_type = '%s';", $year_id, fiscaat_get_record_post_type() ) );

	// Update the count
	fiscaat_update_year_meta( $year_id, 'record_count_unapproved', (int) $record_count );

	return (int) apply_filters( 'fiscaat_update_year_record_count_unapproved', (int) $record_count, $year_id );
}

/**
 * Adjust the total record count of a year
 *
 * @param int $year_id Optional. Year id or record id. It is checked whether it
 *                       is a record or a year. If it's a record, its grandparent,
 *                       i.e. the year is automatically retrieved.
 * @uses fiscaat_get_year_id() To get the year id
 * @uses fiscaat_update_year_record_count() To update the year record count
 * @uses fiscaat_year_query_record_ids() To get the year record ids
 * @uses wpdb::prepare() To prepare the sql statement
 * @uses wpdb::get_var() To execute the query and get the var back
 * @uses fiscaat_update_year_meta() To update the year's record count meta
 * @uses apply_filters() Calls 'fiscaat_update_year_account_count' with the record
 *                        count and year id
 * @return int Year record count
 */
function fiscaat_update_year_record_count( $year_id = 0 ) {
	global $wpdb;

	// If record_id was passed as $year_id, then get its year
	if ( fiscaat_is_record( $year_id ) ) {
		$record_id = fiscaat_get_record_id( $year_id );
		$year_id   = fiscaat_get_record_year_id( $record_id );

	// $year_id is not a record_id, so validate and proceed
	} else {
		$year_id   = fiscaat_get_year_id( $year_id );
	}

	// Don't count records if the year is empty
	$record_ids = fiscaat_year_query_record_ids( $year_id );
	if ( ! empty( $record_ids ) )
		$record_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_parent IN ( " . join( ',', $record_ids ) . " ) AND post_type = '%s';", fiscaat_get_record_post_type() ) );
	else
		$record_count = 0;

	// Update the count
	fiscaat_update_year_meta( $year_id, 'record_count', (int) $record_count );

	return (int) apply_filters( 'fiscaat_update_year_record_count', (int) $record_count, $year_id );
}

/**
 * Adjust the total to balance value of a year
 * 
 * @param int $year_id Optional. Year id or record id. It is checked whether it
 *                       is a record or a year. If it's a record, its grandparent,
 *                       i.e. the year is automatically retrieved.
 * @param int $to_balance Optional. The to balance value
 * @return int Year total to balance value
 */
function fiscaat_update_year_to_balance( $year_id = 0, $to_balance = 0 ) {
	
	// If record_id was passed as $year_id, then get its year
	if ( fiscaat_is_record( $year_id ) ) {
		$record_id = fiscaat_get_record_id( $year_id );
		$year_id   = fiscaat_get_record_year_id( $record_id );

	// $year_id is not a record_id, so validate and proceed
	} else {
		$year_id   = fiscaat_get_year_id( $year_id );
	}

	// Get total result records for this year
	$records = (array) fiscaat_year_query_result_record_ids( $year_id );

	// Has records and value isn't given
	if ( ! empty( $records ) && empty( $to_balance ) ){

		// Loop all records and add the result value
		foreach ( $records as $record_id ){
			$to_balance += fiscaat_get_debit_record_type() == fiscaat_get_record_value_type( $record_id )
				? fiscaat_get_record_value( $record_id ) * -1
				: fiscaat_get_record_value( $record_id );
		}
	}

	// Update the value
	fiscaat_update_year_meta( $year_id, 'to_balance', (float) $to_balance );

	return (float) apply_filters( 'fiscaat_update_year_to_balance', (float) $to_balance, $year_id );
}

/**
 * Updates the counts of a year.
 *
 * This calls a few internal functions that all run manual queries against the
 * database to get their results. As such, this function can be costly to run
 * but is necessary to keep everything accurate.
 *
 * @param mixed $args Supports these arguments:
 *  - year_id: Year id
 *  - to_balance: To balance value
 * @uses fiscaat_update_year_to_balance() To update the year to balance value
 * @uses fiscaat_update_year_account_count() To update the year account count
 * @uses fiscaat_update_year_record_count() To update the year record count
 * @uses fiscaat_update_year_record_count_declined() To update the declined record count
 * @uses fiscaat_update_year_record_count_unapproved() To update the unapproved record count
 */
function fiscaat_update_year( $args = '' ) {
	$defaults = array(
		'year_id'    => 0,
		'to_balance' => 0
	);
	$r = fiscaat_parse_args( $args, $defaults, 'update_year' );
	extract( $r );

	// Check year id
	$year_id = fiscaat_get_year_id( $year_id );

	// Update year to balance
	fiscaat_update_year_to_balance( $year_id, $to_balance );

	// Counts
	fiscaat_update_year_account_count          ( $year_id );
	fiscaat_update_year_record_count           ( $year_id );
	fiscaat_update_year_record_count_unapproved( $year_id );
	fiscaat_update_year_record_count_declined  ( $year_id );
}


/** Queries *******************************************************************/

/**
 * Return whether there exists an open year in Fiscaat
 *
 * @uses fiscaat_get_year_post_type()
 * @uses apply_filters() Calls 'fiscaat_has_open_year' with Fiscaat has open year
 * @return bool Fiscaat has open year
 */
function fiscaat_has_open_year() {
	$counts = wp_count_posts( fiscaat_get_year_post_type() );
	$retval = (bool) $counts->publish;

	return (bool) apply_filters( 'fiscaat_has_open_year', $retval );
}

/**
 * Returns whether the year has any records
 * 
 * @param int $year_id Year id
 * @uses fiscaat_get_public_child_count()
 * @uses fiscaat_get_year_id()
 * @uses fiscaat_get_record_post_type()
 * @uses apply_filters() Calls 'fiscaat_year_has_records' with year
 *                        has records and year id
 * @return bool Year has records
 */
function fiscaat_year_has_records( $year_id = 0 ) {
	$record_count = fiscaat_get_public_child_count( fiscaat_get_year_id( $year_id ), fiscaat_get_record_post_type() );

	return (bool) apply_filters( 'fiscaat_year_has_records', $record_count > 0, $year_id );
}

/**
 * Returns the year's account ids
 *
 * Accounts with all statuses are returned.
 *
 * @param int $year_id Year id
 * @param string $account_type Optional. Account type restriction
 * @uses fiscaat_get_result_account_type()
 * @uses fiscaat_get_asset_account_type()
 * @uses fiscaat_get_account_post_type() To get the account post type
 * @uses fiscaat_get_public_child_ids() To get the account ids
 * @uses apply_filters() Calls 'fiscaat_year_query_account_ids' with the account ids
 *                        and year id and account type
 */
function fiscaat_year_query_account_ids( $year_id, $account_type = '' ) {

	// Handle account types
	if ( in_array( $account_type, array( fiscaat_get_result_account_type(), fiscaat_get_asset_account_type() ) ) ) {

		// Run query
		$query = new WP_Query( array(
			'post_type'  => fiscaat_get_account_post_type(),
			'parent'     => $year_id,
			'meta_key'   => '_fiscaat_account_type',
			'meta_value' => $account_type,
			'fields'     => 'ids',
		) );
		$account_ids = $query->posts;
		
	// Find all accounts
	} else {
   		$account_ids = fiscaat_get_public_child_ids( $year_id, fiscaat_get_account_post_type() );
	}

	return apply_filters( 'fiscaat_year_query_account_ids', $account_ids, $year_id, $account_type );
}

/**
 * Returns the year's result account ids
 * 
 * @param int $year_id Year id
 * @uses fiscaat_get_account_post_type() To get the account post type
 * @uses fiscaat_get_result_account_type() To get the result account type
 */
function fiscaat_year_query_result_account_ids( $year_id ) {
	return fiscaat_year_query_account_ids( $year_id, fiscaat_get_result_account_type() );
}

/**
 * Returns the year's asset account ids
 * 
 * @param int $year_id Year id
 * @uses fiscaat_get_account_post_type() To get the account post type
 * @uses fiscaat_get_asset_account_type() To get the asset account type
 */
function fiscaat_year_query_asset_account_ids( $year_id ) {
	return fiscaat_year_query_account_ids( $year_id, fiscaat_get_asset_account_type() );
}

/**
 * Returns the year's record ids
 *
 * Records with all statuses are returned.
 *
 * @param int $year_id Year id
 * @uses fiscaat_get_record_post_type() To get the year post type
 * @uses fiscaat_get_public_child_ids() To get the year ids
 * @uses apply_filters() Calls 'fiscaat_year_query_record_ids' with the record
 *                        ids and year id
 */
function fiscaat_year_query_record_ids( $year_id ) {
	$record_ids = fiscaat_get_public_child_ids( $year_id, fiscaat_get_record_post_type() );

	return apply_filters( 'fiscaat_year_query_record_ids', $record_ids, $year_id );
}

/**
 * Returns the year's result record ids
 *
 * Records with all statuses are returned.
 *
 * @param int $year_id Year id
 * @uses fiscaat_get_result_child_ids() To get the year result account ids
 * @uses fiscaat_get_record_post_type() To get the year post type
 * @uses apply_filters() Calls 'fiscaat_year_query_result_record_ids' with the record
 *                        ids and year id
 */
function fiscaat_year_query_result_record_ids( $year_id ) {
	$account_ids = fiscaat_year_query_result_account_ids( $year_id );
	$record_ids  = array();

	// Get all account record ids
	foreach ( $account_ids as $account_id )
		$record_ids = array_merge( $record_ids, fiscaat_get_public_child_ids( $account_id, fiscaat_get_record_post_type() ) );

	return apply_filters( 'fiscaat_year_query_result_record_ids', $record_ids, $year_id );
}

/**
 * Returns the year's asset record ids
 *
 * Records with all statuses are returned.
 *
 * @param int $year_id Year id
 * @uses fiscaat_get_asset_child_ids() To get the year asset account ids
 * @uses fiscaat_get_record_post_type() To get the year post type
 * @uses apply_filters() Calls 'fiscaat_year_query_asset_record_ids' with the record
 *                        ids and year id
 */
function fiscaat_year_query_asset_record_ids( $year_id ) {
	$account_ids = fiscaat_year_query_asset_account_ids( $year_id );
	$record_ids  = array();

	// Get all account record ids
	foreach ( $account_ids as $account_id )
		$record_ids = array_merge( $record_ids, fiscaat_get_public_child_ids( $account_id, fiscaat_get_record_post_type() ) );

	return apply_filters( 'fiscaat_year_query_asset_record_ids', $record_ids, $year_id );
}

/** Permissions ***************************************************************/

/**
 * Redirect if unathorized user is attempting to edit a year
 * 
 * @uses fiscaat_is_year_edit()
 * @uses current_user_can()
 * @uses fiscaat_get_year_id()
 * @uses wp_safe_redirect()
 * @uses fiscaat_get_year_permalink()
 */
function fiscaat_check_year_edit() {

	// Bail if not editing a year
	if ( !fiscaat_is_year_edit() )
		return;

	// User cannot edit year, so redirect back to year
	if ( !current_user_can( 'edit_year', fiscaat_get_year_id() ) ) {
		wp_safe_redirect( fiscaat_get_year_permalink() );
		exit();
	}
}

/**
 * Delete all accounts (and their records) for a specific year ID
 *
 * @param int $year_id
 * @uses fiscaat_get_year_id() To validate the year ID
 * @uses fiscaat_is_year() To make sure it's a year
 * @uses fiscaat_get_account_post_type() To get the account post type
 * @uses fiscaat_accounts() To make sure there are accounts to loop through
 * @uses wp_trash_post() To trash the post
 * @return If year is not valid
 */
function fiscaat_delete_year_accounts( $year_id = 0 ) {

	// Validate year ID
	$year_id = fiscaat_get_year_id( $year_id );
	if ( empty( $year_id ) )
		return;

	// Year is being permanently deleted, so its accounts gotta go too
	if ( $accounts = new WP_Query( array(
		'suppress_filters' => true,
		'post_type'        => fiscaat_get_account_post_type(),
		'post_parent'      => $year_id,
		'post_status'      => 'any',
		'posts_per_page'   => -1,
		'nopaging'         => true,
		'fields'           => 'id=>parent'
	) ) ) {
		foreach ( $accounts->posts as $account ) {
			wp_delete_post( $account->ID, true );
		}

		// Reset the $post global
		wp_reset_postdata();
	}
}

/**
 * Trash all accounts inside a year
 * 
 * @param int $year_id
 * @uses fiscaat_get_year_id() To validate the year ID
 * @uses fiscaat_is_year() To make sure it's a year
 * @uses fiscaat_get_public_status_id() To return public post status
 * @uses fiscaat_get_closed_status_id() To return closed post status
 * @uses fiscaat_get_pending_status_id() To return pending post status
 * @uses fiscaat_get_account_post_type() To get the account post type
 * @uses wp_trash_post() To trash the post
 * @uses update_post_meta() To update the year meta of trashed accounts
 * @return If year is not valid
 */
function fiscaat_trash_year_accounts( $year_id = 0 ) {

	// Validate year ID
	$year_id = fiscaat_get_year_id( $year_id );
	if ( empty( $year_id ) )
		return;

	// Allowed post statuses to pre-trash
	$post_stati = join( ',', array(
		fiscaat_get_public_status_id(),
		fiscaat_get_declined_status_id(),
		fiscaat_get_approved_status_id(),
		fiscaat_get_closed_status_id()
	) );

	// Year is being trashed, so its accounts are trashed too
	if ( $accounts = new WP_Query( array(
		'suppress_filters' => true,
		'post_type'        => fiscaat_get_account_post_type(),
		'post_parent'      => $year_id,
		'post_status'      => $post_stati,
		'posts_per_page'   => -1,
		'nopaging'         => true,
		'fields'           => 'id=>parent'
	) ) ) {

		// Prevent debug notices
		$pre_trashed_accounts = array();

		// Loop through accounts, trash them, and add them to array
		foreach ( $accounts->posts as $account ) {
			wp_trash_post( $account->ID, true );
			$pre_trashed_accounts[] = $account->ID;
		}

		// Set a post_meta entry of the accounts that were trashed by this action.
		// This is so we can possibly untrash them, without untrashing accounts
		// that were purposefully trashed before.
		update_post_meta( $year_id, '_fiscaat_pre_trashed_accounts', $pre_trashed_accounts );

		// Reset the $post global
		wp_reset_postdata();
	}
}

/**
 * Untrash all previously trashed accounts inside a year
 *
 * @param int $year_id
 * @uses fiscaat_get_year_id() To validate the year ID
 * @uses fiscaat_is_year() To make sure it's a year
 * @uses get_post_meta() To update the year meta of trashed accounts
 * @uses wp_untrash_post() To trash the post
 * @return If year is not valid
 */
function fiscaat_untrash_year_accounts( $year_id = 0 ) {

	// Validate year ID
	$year_id = fiscaat_get_year_id( $year_id );

	if ( empty( $year_id ) )
		return;

	// Get the accounts that were not previously trashed
	$pre_trashed_accounts = get_post_meta( $year_id, '_fiscaat_pre_trashed_accounts', true );

	// There are accounts to untrash
	if ( ! empty( $pre_trashed_accounts ) ) {

		// Maybe reverse the trashed accounts array
		