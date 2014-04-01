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
function fct_get_year_default_meta(){
	return (array) apply_filters( 'fct_get_year_default_meta', array(
		'closed'                  => 0, // Year date closed
		'account_count'           => 0, // Total year account count
		'record_count'            => 0, // Total record count
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
function fct_get_year_meta( $year_id, $meta_key ){
	$meta_value = get_post_meta( $year_id, '_fct_'. $meta_key, true );
	return apply_filters( 'fct_get_year_meta', $meta_value, $year_id, $meta_key );
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
function fct_update_year_meta( $year_id, $meta_key, $meta_value ){
	return update_post_meta( $year_id, '_fct_'. $meta_key, $meta_value );
}

/**
 * Delete meta value for given year
 * 
 * @param int $year_id
 * @param string $meta_key
 * @return boolean
 */
function fct_delete_year_meta( $year_id, $meta_key ){
	return delete_post_meta( $year_id, '_fct_'. $meta_key );
}

/** Insert ********************************************************************/

/**
 * A wrapper for wp_insert_post() that also includes the necessary meta values
 * for the year to function properly.
 *
 * @uses fct_parse_args()
 * @uses fct_get_year_post_type()
 * @uses wp_insert_post()
 * @uses update_post_meta()
 *
 * @param array $year_data Year post data
 * @param arrap $year_meta Year meta data
 */
function fct_insert_year( $year_data = array(), $year_meta = array() ) {

	// Year
	$default_year = array(
		'post_parent'    => 0, // year ID
		'post_status'    => fct_get_public_status_id(),
		'post_type'      => fct_get_year_post_type(),
		'post_author'    => fct_get_current_user_id(),
		'post_password'  => '',
		'post_content'   => '',
		'post_title'     => '',
		'menu_order'     => 0,
		'comment_status' => 'closed'
	);
	$year_data = fct_parse_args( $year_data, $default_year, 'insert_year' );

	// Insert year
	$year_id   = wp_insert_post( $year_data );

	// Bail if no year was added
	if ( empty( $year_id ) )
		return false;

	// Year meta
	$year_meta = fct_parse_args( $year_meta, fct_get_year_default_meta(), 'insert_year_meta' );

	// Insert year meta
	foreach ( $year_meta as $meta_key => $meta_value )
		fct_update_year_meta( $year_id, $meta_key, $meta_value );

	// Return new year ID
	return $year_id;
}

/** Year Actions *************************************************************/

/**
 * Closes a year
 *
 * @param int $year_id year id
 * @uses get_post() To get the year
 * @uses do_action() Calls 'fct_close_year' with the year id
 * @uses add_post_meta() To add the previous status to a meta
 * @uses fct_update_year_meta() To add the previous close date to a meta
 * @uses wp_insert_post() To update the year with the new status
 * @uses do_action() Calls 'fct_opened_year' with the year id
 * @return mixed False or {@link WP_Error} on failure, year id on success
 */
function fct_close_year( $year_id = 0 ) {

	// Get year
	if ( ! $year = get_post( $year_id, ARRAY_A ) )
		return $year;

	// Bail if already closed
	$bail = fct_get_closed_status_id() == $year['post_status'];
	if ( apply_filters( 'fct_no_close_year', $bail, $year ) )
		return false;

	// Execute pre close code
	do_action( 'fct_close_year', $year_id );

	// Add pre close status
	add_post_meta( $year_id, '_fct_status', $year['post_status'] );

	// Set closed status
	$year['post_status'] = fct_get_closed_status_id();

	// Set closed date
	fct_update_year_closed( $year_id );

	// No revisions
	remove_action( 'pre_post_update', 'wp_save_post_revision' );

	// Update year
	$year_id = wp_insert_post( $year );

	// Execute post close code
	do_action( 'fct_closed_year', $year_id );

	// Return year_id
	return $year_id;
}

/**
 * Opens a year
 *
 * @param int $year_id year id
 * @uses get_post() To get the year
 * @uses do_action() Calls 'fct_open_year' with the year id
 * @uses get_post_meta() To get the previous status
 * @uses delete_post_meta() To delete the previous status meta
 * @uses fct_update_year_meta() To delete the previous close date meta
 * @uses wp_insert_post() To update the year with the new status
 * @uses do_action() Calls 'fct_opened_year' with the year id
 * @return mixed False or {@link WP_Error} on failure, year id on success
 */
function fct_open_year( $year_id = 0 ) {

	// Get year
	if ( !$year = get_post( $year_id, ARRAY_A ) )
		return $year;

	// Bail if already open
	if ( fct_get_closed_status_id() != $year['post_status'])
		return false;

	// Execute pre open code
	do_action( 'fct_open_year', $year_id );

	// Get previous status
	$year_status         = get_post_meta( $year_id, '_fct_status', true );

	// Set previous status
	$year['post_status'] = $year_status;

	// Unset closed date
	fct_update_year_meta( $year_id, 'closed', 0 );

	// Remove old status meta
	delete_post_meta( $year_id, '_fct_status' );

	// No revisions
	remove_action( 'pre_post_update', 'wp_save_post_revision' );

	// Update year
	$year_id = wp_insert_post( $year );

	// Execute post open code
	do_action( 'fct_opened_year', $year_id );

	// Return year_id
	return $year_id;
}

/** Count Bumpers *************************************************************/

/**
 * Bump the total record count of a year
 *
 * @param int $year_id Optional. Year id.
 * @param int $difference Optional. Default 1
 * @uses fct_get_year_id() To get the year id
 * @uses fct_update_year_meta() To update the year's record count meta
 * @uses apply_filters() Calls 'fct_bump_year_record_count' with the record
 *                        count, year id, and difference
 * @return int Year record count
 */
function fct_bump_year_record_count( $year_id = 0, $difference = 1 ) {

	// Get some counts
	$year_id      = fct_get_year_id( $year_id );
	$record_count = fct_get_year_record_count( $year_id, false, false );
	$new_count    = (int) $record_count + (int) $difference;

	// Update this year id
	fct_update_year_meta( $year_id, 'record_count', (int) $new_count );

	return (int) apply_filters( 'fct_bump_year_record_count', (int) $new_count, $year_id, (int) $difference );
}

/**
 * Bump the total account count of a year
 *
 * @param int $year_id Optional. Year id.
 * @param int $difference Optional. Default 1
 * @param bool $update_ancestors Optional. Default true
 * @uses fct_get_year_id() To get the year id
 * @uses fct_update_year_meta() To update the year's account count meta
 * @uses apply_filters() Calls 'fct_bump_year_account_count' with the account
 *                        count, year id, and difference
 * @return int Year account count
 */
function fct_bump_year_account_count( $year_id = 0, $difference = 1 ) {

	// Get some counts
	$year_id       = fct_get_year_id( $year_id );
	$account_count = fct_get_year_account_count( $year_id, false );
	$new_count     = (int) $account_count + (int) $difference;

	// Update this year id
	fct_update_year_meta( $year_id, 'account_count', (int) $new_count );

	return (int) apply_filters( 'fct_bump_year_account_count', (int) $new_count, $year_id, (int) $difference );
}

/** Year Updaters ************************************************************/

/**
 * Handle the saving of core year metadata (Status and Close date)
 *
 * @param int $year_id
 * @uses fct_is_year_closed() To check if year is closed
 * @uses fct_close_year() To close year
 * @uses fct_open_year() To open year
 * @return If year ID is empty
 */
function fct_save_year_extras( $year_id = 0 ) {

	// Validate the year ID
	$year_id = fct_get_year_id( $year_id );

	// Bail if year ID is empty
	if ( empty( $year_id ) || ! fct_is_year( $year_id ) )
		return;

	/** Year Status ******************************************************/

	if ( ! empty( $_POST['fct_year_status'] ) && in_array( $_POST['fct_year_status'], array( 'open', 'closed' ) ) ) {
		if ( 'closed' == $_POST['fct_year_status'] && ! fct_is_year_closed( $year_id, false ) ) {
			fct_close_year( $year_id );
		} elseif ( 'open' == $_POST['fct_year_status'] && fct_is_year_closed( $year_id, false ) ) {
			fct_open_year( $year_id );
		}
	}

	/** Close date *******************************************************/

	if ( ! empty( $_POST['fct_year_closed'] ) && ! fct_is_year_closed( $year_id, false ) ) {
		fct_update_year_closed( $year_id );
	}
}

/**
 * Adjust the close date of a year with current time
 * 
 * @param int $year_id Optional. Year id
 * @param string $date Mysql date string
 * @uses fct_get_year_id() To get the year id
 * @uses fct_get_current_time() To get the current date in mysql format
 * @return string Year close date
 */
function fct_update_year_closed( $year_id = 0, $date = '' ) {
	$year_id = fct_get_year_id( $year_id );
	
	// Require close date
	if ( empty( $date ) )
		return false;

	fct_update_year_meta( $year_id, 'closed', $date );

	return apply_filters( 'fct_update_year_closed', $date, $year_id );
}

/**
 * Adjust the total account count of a year
 *
 * @param int $year_id Optional. Year id or account id. It is checked whether it
 *                       is a account or a year. If it's a account, its parent,
 *                       i.e. the year is automatically retrieved.
 * @uses fct_get_year_id() To get the year id
 * @uses fct_get_account_year_id() To get the account year id
 * @uses fct_update_year_account_count() To update the year account count
 * @uses fct_year_query_account_ids() To get the year account ids
 * @uses fct_update_year_meta() To update the year's account count meta
 * @uses apply_filters() Calls 'fct_update_year_account_count' with the account
 *                        count and year id
 * @return int Year account count
 */
function fct_update_year_account_count( $year_id = 0 ) {

	// If account_id was passed as $year_id, then get its year
	if ( fct_is_account( $year_id ) ) {
		$account_id = fct_get_account_id( $year_id );
		$year_id    = fct_get_account_year_id( $account_id );

	// $year_id is not an account_id, so validate and proceed
	} else {
		$year_id    = fct_get_year_id( $year_id );
	}

	// Get total accounts for this year
	$accounts = (int) count( fct_year_query_account_ids( $year_id ) );

	// Update the count
	fct_update_year_meta( $year_id, 'account_count', (int) $accounts );

	return (int) apply_filters( 'fct_update_year_account_count', (int) $accounts, $year_id );
}

/**
 * Adjust the total record count of a year
 *
 * @param int $year_id Optional. Year id or record id. It is checked whether it
 *                       is a record or a year. If it's a record, its grandparent,
 *                       i.e. the year is automatically retrieved.
 * @uses fct_get_year_id() To get the year id
 * @uses fct_update_year_record_count() To update the year record count
 * @uses fct_year_query_record_ids() To get the year record ids
 * @uses wpdb::prepare() To prepare the sql statement
 * @uses wpdb::get_var() To execute the query and get the var back
 * @uses fct_update_year_meta() To update the year's record count meta
 * @uses apply_filters() Calls 'fct_update_year_account_count' with the record
 *                        count and year id
 * @return int Year record count
 */
function fct_update_year_record_count( $year_id = 0 ) {
	global $wpdb;

	// If record_id was passed as $year_id, then get its year
	if ( fct_is_record( $year_id ) ) {
		$record_id = fct_get_record_id( $year_id );
		$year_id   = fct_get_record_year_id( $record_id );

	// $year_id is not a record_id, so validate and proceed
	} else {
		$year_id   = fct_get_year_id( $year_id );
	}

	// Don't count records if the year is empty
	$record_ids = fct_year_query_record_ids( $year_id );
	if ( ! empty( $record_ids ) )
		$record_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_parent IN ( " . join( ',', $record_ids ) . " ) AND post_type = '%s';", fct_get_record_post_type() ) );
	else
		$record_count = 0;

	// Update the count
	fct_update_year_meta( $year_id, 'record_count', (int) $record_count );

	return (int) apply_filters( 'fct_update_year_record_count', (int) $record_count, $year_id );
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
function fct_update_year_to_balance( $year_id = 0, $to_balance = 0 ) {
	
	// If record_id was passed as $year_id, then get its year
	if ( fct_is_record( $year_id ) ) {
		$record_id = fct_get_record_id( $year_id );
		$year_id   = fct_get_record_year_id( $record_id );

	// $year_id is not a record_id, so validate and proceed
	} else {
		$year_id   = fct_get_year_id( $year_id );
	}

	// Get total result records for this year
	$records = (array) fct_year_query_result_record_ids( $year_id );

	// Has records and value isn't given
	if ( ! empty( $records ) && empty( $to_balance ) ){

		// Loop all records and add the result value
		foreach ( $records as $record_id ){
			$to_balance += fct_get_debit_record_type() == fct_get_record_value_type( $record_id )
				? fct_get_record_value( $record_id ) * -1
				: fct_get_record_value( $record_id );
		}
	}

	// Update the value
	fct_update_year_meta( $year_id, 'to_balance', (float) $to_balance );

	return (float) apply_filters( 'fct_update_year_to_balance', (float) $to_balance, $year_id );
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
 * @uses fct_update_year_to_balance() To update the year to balance value
 * @uses fct_update_year_account_count() To update the year account count
 * @uses fct_update_year_record_count() To update the year record count
 * @uses fct_update_year_record_count_declined() To update the declined record count
 * @uses fct_update_year_record_count_unapproved() To update the unapproved record count
 */
function fct_update_year( $args = '' ) {
	$defaults = array(
		'year_id'    => 0,
		'to_balance' => 0
	);
	$r = fct_parse_args( $args, $defaults, 'update_year' );
	extract( $r );

	// Check year id
	$year_id = fct_get_year_id( $year_id );

	// Update year to balance
	fct_update_year_to_balance( $year_id, $to_balance );

	// Counts
	fct_update_year_account_count          ( $year_id );
	fct_update_year_record_count           ( $year_id );
	// @todo Move to Control
	// fct_update_year_record_count_unapproved( $year_id );
	// fct_update_year_record_count_declined  ( $year_id );
}


/** Queries *******************************************************************/

/**
 * Return whether there exists an open year in Fiscaat
 *
 * @uses fct_get_year_post_type()
 * @uses apply_filters() Calls 'fct_has_open_year' with Fiscaat has open year
 * @return bool Fiscaat has open year
 */
function fct_has_open_year() {
	$counts = wp_count_posts( fct_get_year_post_type() );
	$retval = (bool) $counts->publish;

	return (bool) apply_filters( 'fct_has_open_year', $retval );
}

/**
 * Returns whether the year has any records
 * 
 * @param int $year_id Year id
 * @uses fct_get_public_child_count()
 * @uses fct_get_year_id()
 * @uses fct_get_record_post_type()
 * @uses apply_filters() Calls 'fct_year_has_records' with year
 *                        has records and year id
 * @return bool Year has records
 */
function fct_year_has_records( $year_id = 0 ) {
	$record_count = fct_get_public_child_count( fct_get_year_id( $year_id ), fct_get_record_post_type() );

	return (bool) apply_filters( 'fct_year_has_records', $record_count > 0, $year_id );
}

/**
 * Returns the year's account ids
 *
 * Accounts with all statuses are returned.
 *
 * @param int $year_id Year id
 * @param string $account_type Optional. Account type restriction
 * @uses fct_get_result_account_type()
 * @uses fct_get_asset_account_type()
 * @uses fct_get_account_post_type() To get the account post type
 * @uses fct_get_public_child_ids() To get the account ids
 * @uses apply_filters() Calls 'fct_year_query_account_ids' with the account ids
 *                        and year id and account type
 */
function fct_year_query_account_ids( $year_id, $account_type = '' ) {

	// Handle account types
	if ( in_array( $account_type, array( fct_get_result_account_type(), fct_get_asset_account_type() ) ) ) {

		// Run query
		$query = new WP_Query( array(
			'post_type'  => fct_get_account_post_type(),
			'parent'     => $year_id,
			'meta_key'   => '_fct_account_type',
			'meta_value' => $account_type,
			'fields'     => 'ids',
		) );
		$account_ids = $query->posts;
		
	// Find all accounts
	} else {
   		$account_ids = fct_get_public_child_ids( $year_id, fct_get_account_post_type() );
	}

	return apply_filters( 'fct_year_query_account_ids', $account_ids, $year_id, $account_type );
}

/**
 * Returns the year's result account ids
 * 
 * @param int $year_id Year id
 * @uses fct_get_account_post_type() To get the account post type
 * @uses fct_get_result_account_type() To get the result account type
 */
function fct_year_query_result_account_ids( $year_id ) {
	return fct_year_query_account_ids( $year_id, fct_get_result_account_type() );
}

/**
 * Returns the year's asset account ids
 * 
 * @param int $year_id Year id
 * @uses fct_get_account_post_type() To get the account post type
 * @uses fct_get_asset_account_type() To get the asset account type
 */
function fct_year_query_asset_account_ids( $year_id ) {
	return fct_year_query_account_ids( $year_id, fct_get_asset_account_type() );
}

/**
 * Returns the year's record ids
 *
 * Records with all statuses are returned.
 *
 * @param int $year_id Year id
 * @uses fct_get_record_post_type() To get the year post type
 * @uses fct_get_public_child_ids() To get the year ids
 * @uses apply_filters() Calls 'fct_year_query_record_ids' with the record
 *                        ids and year id
 */
function fct_year_query_record_ids( $year_id ) {
	$record_ids = fct_get_public_child_ids( $year_id, fct_get_record_post_type() );

	return apply_filters( 'fct_year_query_record_ids', $record_ids, $year_id );
}

/**
 * Returns the year's result record ids
 *
 * Records with all statuses are returned.
 *
 * @param int $year_id Year id
 * @uses fct_get_result_child_ids() To get the year result account ids
 * @uses fct_get_record_post_type() To get the year post type
 * @uses apply_filters() Calls 'fct_year_query_result_record_ids' with the record
 *                        ids and year id
 */
function fct_year_query_result_record_ids( $year_id ) {
	$account_ids = fct_year_query_result_account_ids( $year_id );
	$record_ids  = array();

	// Get all account record ids
	foreach ( $account_ids as $account_id )
		$record_ids = array_merge( $record_ids, fct_get_public_child_ids( $account_id, fct_get_record_post_type() ) );

	return apply_filters( 'fct_year_query_result_record_ids', $record_ids, $year_id );
}

/**
 * Returns the year's asset record ids
 *
 * Records with all statuses are returned.
 *
 * @param int $year_id Year id
 * @uses fct_get_asset_child_ids() To get the year asset account ids
 * @uses fct_get_record_post_type() To get the year post type
 * @uses apply_filters() Calls 'fct_year_query_asset_record_ids' with the record
 *                        ids and year id
 */
function fct_year_query_asset_record_ids( $year_id ) {
	$account_ids = fct_year_query_asset_account_ids( $year_id );
	$record_ids  = array();

	// Get all account record ids
	foreach ( $account_ids as $account_id )
		$record_ids = array_merge( $record_ids, fct_get_public_child_ids( $account_id, fct_get_record_post_type() ) );

	return apply_filters( 'fct_year_query_asset_record_ids', $record_ids, $year_id );
}

/** Permissions ***************************************************************/

/**
 * Redirect if unathorized user is attempting to edit a year
 * 
 * @uses fct_is_year_edit()
 * @uses current_user_can()
 * @uses fct_get_year_id()
 * @uses wp_safe_redirect()
 * @uses fct_get_year_permalink()
 */
function fct_check_year_edit() {

	// Bail if not editing a year
	if ( !fct_is_year_edit() )
		return;

	// User cannot edit year, so redirect back to year
	if ( !current_user_can( 'edit_year', fct_get_year_id() ) ) {
		wp_safe_redirect( fct_get_year_permalink() );
		exit();
	}
}

/**
 * Delete all accounts (and their records) for a specific year ID
 *
 * @param int $year_id
 * @uses fct_get_year_id() To validate the year ID
 * @uses fct_is_year() To make sure it's a year
 * @uses fct_get_account_post_type() To get the account post type
 * @uses fct_accounts() To make sure there are accounts to loop through
 * @uses wp_trash_post() To trash the post
 * @return If year is not valid
 */
function fct_delete_year_accounts( $year_id = 0 ) {

	// Validate year ID
	$year_id = fct_get_year_id( $year_id );
	if ( empty( $year_id ) )
		return;

	// Year is being permanently deleted, so its accounts gotta go too
	if ( $accounts = new WP_Query( array(
		'suppress_filters' => true,
		'post_type'        => fct_get_account_post_type(),
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
 * @uses fct_get_year_id() To validate the year ID
 * @uses fct_is_year() To make sure it's a year
 * @uses fct_get_public_status_id() To return public post status
 * @uses fct_get_closed_status_id() To return closed post status
 * @uses fct_get_pending_status_id() To return pending post status
 * @uses fct_get_account_post_type() To get the account post type
 * @uses wp_trash_post() To trash the post
 * @uses update_post_meta() To update the year meta of trashed accounts
 * @return If year is not valid
 */
function fct_trash_year_accounts( $year_id = 0 ) {

	// Validate year ID
	$year_id = fct_get_year_id( $year_id );
	if ( empty( $year_id ) )
		return;

	// Allowed post statuses to pre-trash
	$post_stati = join( ',', array(
		fct_get_public_status_id(),
		fct_get_closed_status_id()
	) );

	// Year is being trashed, so its accounts are trashed too
	if ( $accounts = new WP_Query( array(
		'suppress_filters' => true,
		'post_type'        => fct_get_account_post_type(),
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
		update_post_meta( $year_id, '_fct_pre_trashed_accounts', $pre_trashed_accounts );

		// Reset the $post global
		wp_reset_postdata();
	}
}

/**
 * Untrash all previously trashed accounts inside a year
 *
 * @param int $year_id
 * @uses fct_get_year_id() To validate the year ID
 * @uses fct_is_year() To make sure it's a year
 * @uses get_post_meta() To update the year meta of trashed accounts
 * @uses wp_untrash_post() To trash the post
 * @return If year is not valid
 */
function fct_untrash_year_accounts( $year_id = 0 ) {

	// Validate year ID
	$year_id = fct_get_year_id( $year_id );

	if ( empty( $year_id ) )
		return;

	// Get the accounts that were not previously trashed
	$pre_trashed_accounts = get_post_meta( $year_id, '_fct_pre_trashed_accounts', true );

	// There are accounts to untrash
	if ( ! empty( $pre_trashed_accounts ) ) {

		// Maybe reverse the trashed accounts array
		if ( is_array( $pre_trashed_accounts ) )
			$pre_trashed_accounts = array_reverse( $pre_trashed_accounts );

		// Loop through accounts
		foreach ( (array) $pre_trashed_accounts as $account ) {
			wp_untrash_post( $account );
		}
	}
}

/** Before Delete/Trash/Untrash ***********************************************/

/**
 * Called before deleting a year.
 *
 * This function is supplemental to the actual year deletion which is
 * handled by WordPress core API functions. It is used to clean up after
 * a year that is being deleted.
 *
 * @since Fiscaat (r3668)
 * @uses fct_get_year_id() To get the year id
 * @uses fct_is_year() To check if the passed id is a year
 * @uses do_action() Calls 'fct_delete_year' with the year id
 */
function fct_delete_year( $year_id = 0 ) {
	$year_id = fct_get_year_id( $year_id );

	if ( empty( $year_id ) || ! fct_is_year( $year_id ) )
		return false;

	do_action( 'fct_delete_year', $year_id );
}

/**
 * Called before trashing a year
 *
 * This function is supplemental to the actual year being trashed which is
 * handled by WordPress core API functions. It is used to clean up after
 * a year that is being trashed.
 *
 * @since Fiscaat (r3668)
 * @uses fct_get_year_id() To get the year id
 * @uses fct_is_year() To check if the passed id is a year
 * @uses do_action() Calls 'fct_trash_year' with the year id
 */
function fct_trash_year( $year_id = 0 ) {
	$year_id = fct_get_year_id( $year_id );

	if ( empty( $year_id ) || ! fct_is_year( $year_id ) )
		return false;

	do_action( 'fct_trash_year', $year_id );
}

/**
 * Called before untrashing a year
 *
 * @since Fiscaat (r3668)
 * @uses fct_get_year_id() To get the year id
 * @uses fct_is_year() To check if the passed id is a year
 * @uses do_action() Calls 'fct_untrash_year' with the year id
 */
function fct_untrash_year( $year_id = 0 ) {
	$year_id = fct_get_year_id( $year_id );

	if ( empty( $year_id ) || ! fct_is_year( $year_id ) )
		return false;

	do_action( 'fct_untrash_year', $year_id );
}

/** After Delete/Trash/Untrash ************************************************/

/**
 * Called after deleting a year
 *
 * @since Fiscaat (r3668)
 * @uses fct_get_year_id() To get the year id
 * @uses fct_is_year() To check if the passed id is a year
 * @uses do_action() Calls 'fct_deleted_year' with the year id
 */
function fct_deleted_year( $year_id = 0 ) {
	$year_id = fct_get_year_id( $year_id );

	if ( empty( $year_id ) || ! fct_is_year( $year_id ) )
		return false;

	do_action( 'fct_deleted_year', $year_id );
}

/**
 * Called after trashing a year
 *
 * @since Fiscaat (r3668)
 * @uses fct_get_year_id() To get the year id
 * @uses fct_is_year() To check if the passed id is a year
 * @uses do_action() Calls 'fct_trashed_year' with the year id
 */
function fct_trashed_year( $year_id = 0 ) {
	$year_id = fct_get_year_id( $year_id );

	if ( empty( $year_id ) || ! fct_is_year( $year_id ) )
		return false;

	do_action( 'fct_trashed_year', $year_id );
}

/**
 * Called after untrashing a year
 *
 * @since Fiscaat (r3668)
 * @uses fct_get_year_id() To get the year id
 * @uses fct_is_year() To check if the passed id is a year
 * @uses do_action() Calls 'fct_untrashed_year' with the year id
 */
function fct_untrashed_year( $year_id = 0 ) {
	$year_id = fct_get_year_id( $year_id );

	if ( empty( $year_id ) || ! fct_is_year( $year_id ) )
		return false;

	do_action( 'fct_untrashed_year', $year_id );
}