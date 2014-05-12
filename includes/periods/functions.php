<?php

/**
 * Fiscaat Period Functions
 *
 * @package Fiscaat
 * @subpackage Functions
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** Meta **********************************************************************/

/**
 * Return default period meta keys with values
 *
 * @param int $period_id
 * @return array
 */
function fct_get_period_default_meta(){
	return (array) apply_filters( 'fct_get_period_default_meta', array(
		'closed'        => 0, // Period date closed
		'account_count' => 0, // Total period account count
		'record_count'  => 0, // Total record count
		'end_value'     => 0, // Current period end value
	) );
}

/**
 * Return stored meta value for given period
 *
 * @uses get_post_meta()
 * 
 * @param int $period_id Period id
 * @param string $meta_key Meta key
 * @return mixed $meta_value
 */
function fct_get_period_meta( $period_id, $meta_key ){
	$meta_value = get_post_meta( $period_id, '_fct_'. $meta_key, true );
	return apply_filters( 'fct_get_period_meta', $meta_value, $period_id, $meta_key );
}

/**
 * Update meta value for given period
 *
 * @uses update_post_meta()
 * 
 * @param int $period_id
 * @param string $meta_key
 * @param mixed $meta_value
 * @return boolean
 */
function fct_update_period_meta( $period_id, $meta_key, $meta_value ){
	return update_post_meta( $period_id, '_fct_'. $meta_key, $meta_value );
}

/**
 * Delete meta value for given period
 * 
 * @param int $period_id
 * @param string $meta_key
 * @return boolean
 */
function fct_delete_period_meta( $period_id, $meta_key ){
	return delete_post_meta( $period_id, '_fct_'. $meta_key );
}

/** Insert ********************************************************************/

/**
 * A wrapper for wp_insert_post() that also includes the necessary meta values
 * for the period to function properly.
 *
 * @uses fct_parse_args()
 * @uses fct_get_period_post_type()
 * @uses wp_insert_post()
 * @uses update_post_meta()
 *
 * @param array $period_data Period post data
 * @param arrap $period_meta Period meta data
 */
function fct_insert_period( $period_data = array(), $period_meta = array() ) {

	// Period
	$default_period = array(
		'post_parent'    => 0, // period ID
		'post_status'    => fct_get_public_status_id(),
		'post_type'      => fct_get_period_post_type(),
		'post_author'    => fct_get_current_user_id(),
		'post_password'  => '',
		'post_content'   => '',
		'post_title'     => '',
		'menu_order'     => 0,
		'comment_status' => 'closed'
	);
	$period_data = fct_parse_args( $period_data, $default_period, 'insert_period' );

	// Insert period
	$period_id   = wp_insert_post( $period_data );

	// Bail if no period was added
	if ( empty( $period_id ) )
		return false;

	// Period meta
	$period_meta = fct_parse_args( $period_meta, fct_get_period_default_meta(), 'insert_period_meta' );

	// Insert period meta
	foreach ( $period_meta as $meta_key => $meta_value ) {
		fct_update_period_meta( $period_id, $meta_key, $meta_value );
	}

	// Return new period ID
	return $period_id;
}

/** Period Actions *************************************************************/

/**
 * Closes a period
 *
 * @param int $period_id period id
 * @uses get_post() To get the period
 * @uses do_action() Calls 'fct_close_period' with the period id
 * @uses add_post_meta() To add the previous status to a meta
 * @uses fct_update_period_meta() To add the previous close date to a meta
 * @uses wp_insert_post() To update the period with the new status
 * @uses do_action() Calls 'fct_opened_period' with the period id
 * @return mixed False or {@link WP_Error} on failure, period id on success
 */
function fct_close_period( $period_id = 0 ) {

	// Get period
	if ( ! $period = get_post( $period_id ) )
		return $period;

	// Bail if already closed
	if ( fct_get_closed_status_id() == $period->post_status )
		return false;

	// Execute pre close code
	do_action( 'fct_close_period', $period_id );

	// Add pre close status
	add_post_meta( $period_id, '_fct_status', $period->post_status );

	// Set closed status
	$period->post_status = fct_get_closed_status_id();

	// Set closed date
	fct_update_period_closed( $period_id );

	// No revisions
	remove_action( 'pre_post_update', 'wp_save_post_revision' );

	// Update period
	$period_id = wp_insert_post( $period );

	// Execute post close code
	do_action( 'fct_closed_period', $period_id );

	// Return period_id
	return $period_id;
}

/**
 * Opens a period
 *
 * @param int $period_id period id
 * @uses get_post() To get the period
 * @uses do_action() Calls 'fct_open_period' with the period id
 * @uses get_post_meta() To get the previous status
 * @uses delete_post_meta() To delete the previous status meta
 * @uses fct_update_period_meta() To delete the previous close date meta
 * @uses wp_insert_post() To update the period with the new status
 * @uses do_action() Calls 'fct_opened_period' with the period id
 * @return mixed False or {@link WP_Error} on failure, period id on success
 */
function fct_open_period( $period_id = 0 ) {

	// Get period
	if ( !$period = get_post( $period_id, ARRAY_A ) )
		return $period;

	// Bail if already open
	if ( fct_get_closed_status_id() != $period['post_status'])
		return false;

	// Execute pre open code
	do_action( 'fct_open_period', $period_id );

	// Get previous status
	$period_status         = get_post_meta( $period_id, '_fct_status', true );

	// Set previous status
	$period['post_status'] = $period_status;

	// Unset closed date
	fct_update_period_meta( $period_id, 'closed', 0 );

	// Remove old status meta
	delete_post_meta( $period_id, '_fct_status' );

	// No revisions
	remove_action( 'pre_post_update', 'wp_save_post_revision' );

	// Update period
	$period_id = wp_insert_post( $period );

	// Execute post open code
	do_action( 'fct_opened_period', $period_id );

	// Return period_id
	return $period_id;
}

/** Count Bumpers *************************************************************/

/**
 * Bump the total record count of a period
 *
 * @param int $period_id Optional. Period id.
 * @param int $difference Optional. Default 1
 * @uses fct_get_period_id() To get the period id
 * @uses fct_update_period_meta() To update the period's record count meta
 * @uses apply_filters() Calls 'fct_bump_period_record_count' with the record
 *                        count, period id, and difference
 * @return int Period record count
 */
function fct_bump_period_record_count( $period_id = 0, $difference = 1 ) {

	// Get some counts
	$period_id    = fct_get_period_id( $period_id );
	$record_count = fct_get_period_record_count( $period_id, false, false );
	$new_count    = (int) $record_count + (int) $difference;

	// Update this period id
	fct_update_period_meta( $period_id, 'record_count', (int) $new_count );

	return (int) apply_filters( 'fct_bump_period_record_count', (int) $new_count, $period_id, (int) $difference );
}

/**
 * Bump the total account count of a period
 *
 * @param int $period_id Optional. Period id.
 * @param int $difference Optional. Default 1
 * @param bool $update_ancestors Optional. Default true
 * @uses fct_get_period_id() To get the period id
 * @uses fct_update_period_meta() To update the period's account count meta
 * @uses apply_filters() Calls 'fct_bump_period_account_count' with the account
 *                        count, period id, and difference
 * @return int Period account count
 */
function fct_bump_period_account_count( $period_id = 0, $difference = 1 ) {

	// Get some counts
	$period_id     = fct_get_period_id( $period_id );
	$account_count = fct_get_period_account_count( $period_id, false );
	$new_count     = (int) $account_count + (int) $difference;

	// Update this period id
	fct_update_period_meta( $period_id, 'account_count', (int) $new_count );

	return (int) apply_filters( 'fct_bump_period_account_count', (int) $new_count, $period_id, (int) $difference );
}

/** Period Updaters ************************************************************/

/**
 * Handle the saving of core period metadata (Status and Close date)
 *
 * @param int $period_id
 * @uses fct_is_period_closed() To check if period is closed
 * @uses fct_close_period() To close period
 * @uses fct_open_period() To open period
 * @return If period ID is empty
 */
function fct_save_period_extras( $period_id = 0 ) {

	// Validate the period ID
	$period_id = fct_get_period_id( $period_id );

	// Bail if period ID is empty
	if ( empty( $period_id ) || ! fct_is_period( $period_id ) )
		return;

	/** Period Status ******************************************************/

	if ( ! empty( $_POST['fct_period_status'] ) && in_array( $_POST['fct_period_status'], array( 'open', 'closed' ) ) ) {
		if ( 'closed' == $_POST['fct_period_status'] && ! fct_is_period_closed( $period_id, false ) ) {
			fct_close_period( $period_id );
		} elseif ( 'open' == $_POST['fct_period_status'] && fct_is_period_closed( $period_id, false ) ) {
			fct_open_period( $period_id );
		}
	}

	/** Close date *******************************************************/

	if ( ! empty( $_POST['fct_period_closed'] ) && ! fct_is_period_closed( $period_id, false ) ) {
		fct_update_period_closed( $period_id );
	}
}

/**
 * Adjust the close date of a period with current time
 * 
 * @param int $period_id Optional. Period id
 * @param string $date Mysql date string
 * @uses fct_get_period_id() To get the period id
 * @uses fct_current_time() To get the current date in mysql format
 * @uses fct_update_period_meta() To update the period's close date
 * @return string Period close date
 */
function fct_update_period_closed( $period_id = 0, $date = '' ) {
	$period_id = fct_get_period_id( $period_id );
	
	// Default close date to now GMT
	if ( empty( $date ) ) {
		$date = fct_current_time( 'mysql', true );
	}

	fct_update_period_meta( $period_id, 'closed', $date );

	return apply_filters( 'fct_update_period_closed', $date, $period_id );
}

/**
 * Adjust the total account count of a period
 *
 * @param int $period_id Optional. Period id or account id. It is checked whether it
 *                       is a account or a period. If it's a account, its parent,
 *                       i.e. the period is automatically retrieved.
 * @uses fct_get_period_id() To get the period id
 * @uses fct_get_account_period_id() To get the account period id
 * @uses fct_update_period_account_count() To update the period account count
 * @uses fct_period_query_account_ids() To get the period account ids
 * @uses fct_update_period_meta() To update the period's account count meta
 * @uses apply_filters() Calls 'fct_update_period_account_count' with the account
 *                        count and period id
 * @return int Period account count
 */
function fct_update_period_account_count( $period_id = 0 ) {

	// If account_id was passed as $period_id, then get its period
	if ( fct_is_account( $period_id ) ) {
		$account_id = fct_get_account_id( $period_id );
		$period_id  = fct_get_account_period_id( $account_id );

	// $period_id is not an account_id, so validate and proceed
	} else {
		$period_id  = fct_get_period_id( $period_id );
	}

	// Get total accounts for this period
	$accounts = (int) count( fct_period_query_account_ids( $period_id ) );

	// Update the count
	fct_update_period_meta( $period_id, 'account_count', (int) $accounts );

	return (int) apply_filters( 'fct_update_period_account_count', (int) $accounts, $period_id );
}

/**
 * Adjust the total record count of a period
 *
 * @param int $period_id Optional. Period id or record id. It is checked whether it
 *                       is a record or a period. If it's a record, its grandparent,
 *                       i.e. the period is automatically retrieved.
 * @uses fct_get_period_id() To get the period id
 * @uses fct_update_period_record_count() To update the period record count
 * @uses fct_period_query_record_ids() To get the period record ids
 * @uses wpdb::prepare() To prepare the sql statement
 * @uses wpdb::get_var() To execute the query and get the var back
 * @uses fct_update_period_meta() To update the period's record count meta
 * @uses apply_filters() Calls 'fct_update_period_account_count' with the record
 *                        count and period id
 * @return int Period record count
 */
function fct_update_period_record_count( $period_id = 0 ) {
	global $wpdb;

	// If record_id was passed as $period_id, then get its period
	if ( fct_is_record( $period_id ) ) {
		$record_id = fct_get_record_id( $period_id );
		$period_id = fct_get_record_period_id( $record_id );

	// $period_id is not a record_id, so validate and proceed
	} else {
		$period_id = fct_get_period_id( $period_id );
	}

	// Don't count records if the period is empty
	$record_ids = fct_period_query_record_ids( $period_id );
	if ( ! empty( $record_ids ) ) {
		$record_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_parent IN ( " . join( ',', $record_ids ) . " ) AND post_type = '%s';", fct_get_record_post_type() ) );
	} else {
		$record_count = 0;
	}

	// Update the count
	fct_update_period_meta( $period_id, 'record_count', (int) $record_count );

	return (int) apply_filters( 'fct_update_period_record_count', (int) $record_count, $period_id );
}

/**
 * Adjust the total end value of a period
 * 
 * @param int $period_id Optional. Period id or record id. It is checked whether it
 *                       is a record or a period. If it's a record, its grandparent,
 *                       i.e. the period is automatically retrieved.
 * @param int $end_value Optional. The end value
 * @return int Period total end value
 */
function fct_update_period_end_value( $period_id = 0, $end_value = 0 ) {
	
	// If record_id was passed as $period_id, then get its period
	if ( fct_is_record( $period_id ) ) {
		$record_id = fct_get_record_id( $period_id );
		$period_id = fct_get_record_period_id( $record_id );

	// $period_id is not a record_id, so validate and proceed
	} else {
		$period_id = fct_get_period_id( $period_id );
	}

	// Get total result records for this period
	$records = (array) fct_period_query_revenue_record_ids( $period_id );

	// Has records and value isn't given
	if ( ! empty( $records ) && empty( $end_value ) ) {

		// Loop all records and add the result value
		foreach ( $records as $record_id ){
			$end_value += fct_get_debit_record_type_id() == fct_get_record_type( $record_id )
				? fct_get_record_value( $record_id ) * -1
				: fct_get_record_value( $record_id );
		}
	}

	// Update the value
	fct_update_period_meta( $period_id, 'end_value', (float) $end_value );

	return (float) apply_filters( 'fct_update_period_end_value', (float) $end_value, $period_id );
}

/**
 * Updates the counts of a period.
 *
 * This calls a few internal functions that all run manual queries against the
 * database to get their results. As such, this function can be costly to run
 * but is necessary to keep everything accurate.
 *
 * @uses fct_get_period_id() To get the period id
 * @uses fct_update_period_account_count() To update the period's account count
 * @uses fct_update_period_record_count() To update the period's record count
 * @uses fct_update_period_end_value() To update the period's to balance value
 * 
 * @param mixed $args Optional. Supports these arguments:
 *  - period_id: Period id
 *  - end_value: Period end value
 */
function fct_update_period( $args = '' ) {

	// Parse arguments against default values
	$r = fct_parse_args( $args, array(
		'period_id'     => 0,
		'end_value'     => 0
	), 'update_period' );
	extract( $r );

	// Check period id
	$period_id = fct_get_period_id( $period_id );

	// Counts
	fct_update_period_account_count( $period_id );
	fct_update_period_record_count ( $period_id );
	// @todo Move to Control
	// fct_update_period_record_count_unapproved( $period_id );
	// fct_update_period_record_count_declined  ( $period_id );

	// Update period to balance
	fct_update_period_end_value( $period_id, $end_value );
}


/** Queries *******************************************************************/

/**
 * Return whether there exists an open period in Fiscaat
 *
 * @uses fct_get_period_post_type()
 * @uses apply_filters() Calls 'fct_has_open_period' with Fiscaat has open period
 * @return bool Fiscaat has open period
 */
function fct_has_open_period() {
	$counts = wp_count_posts( fct_get_period_post_type() );
	$status = fct_get_public_status_id();
	$retval = (bool) $counts->$status;

	return (bool) apply_filters( 'fct_has_open_period', $retval );
}

/**
 * Returns whether the period has any non-closed records
 * 
 * @param int $period_id Period id
 * @uses fct_get_public_child_count()
 * @uses fct_get_period_id()
 * @uses fct_get_record_post_type()
 * @uses apply_filters() Calls 'fct_period_has_records' with period
 *                        has records and period id
 * @return bool Period has records
 */
function fct_period_has_records( $period_id = 0 ) {
	$record_count = fct_get_public_child_count( fct_get_period_id( $period_id ), fct_get_record_post_type() );

	return (bool) apply_filters( 'fct_period_has_records', $record_count > 0, $period_id );
}

/**
 * Returns the period's account ids
 *
 * Accounts with all statuses are returned.
 *
 * @param int $period_id Period id
 * @param string $account_type Optional. Account type restriction
 * @uses fct_get_account_types() To get all account types
 * @uses fct_get_account_post_type() To get the account post type
 * @uses fct_get_public_child_ids() To get the account ids
 * @uses apply_filters() Calls 'fct_period_query_account_ids' with the account ids
 *                        and period id and account type
 */
function fct_period_query_account_ids( $period_id, $account_type = '' ) {

	// Handle account types
	if ( in_array( $account_type, array_keys( fct_get_account_types() ) ) ) {

		// Run query
		$query = new WP_Query( array(
			'post_type'  => fct_get_account_post_type(),
			'parent'     => $period_id,
			'meta_key'   => '_fct_account_type',
			'meta_value' => $account_type,
			'fields'     => 'ids',
		) );
		$account_ids = $query->posts;
		
	// Find all accounts
	} else {
   		$account_ids = fct_get_public_child_ids( $period_id, fct_get_account_post_type() );
	}

	return apply_filters( 'fct_period_query_account_ids', $account_ids, $period_id, $account_type );
}

/**
 * Returns the period's revenue account ids
 * 
 * @param int $period_id Period id
 * @uses fct_get_account_post_type() To get the account post type
 * @uses fct_get_revenue_account_type_id() To get the revenue account type
 */
function fct_period_query_revenue_account_ids( $period_id ) {
	return fct_period_query_account_ids( $period_id, fct_get_revenue_account_type_id() );
}

/**
 * Returns the period's capital account ids
 * 
 * @param int $period_id Period id
 * @uses fct_get_account_post_type() To get the account post type
 * @uses fct_get_capital_account_type_id() To get the capital account type
 */
function fct_period_query_capital_account_ids( $period_id ) {
	return fct_period_query_account_ids( $period_id, fct_get_capital_account_type_id() );
}

/**
 * Returns the period's record ids
 *
 * Records with all statuses are returned.
 *
 * @param int $period_id Period id
 * @uses fct_get_record_post_type() To get the period post type
 * @uses fct_get_public_child_ids() To get the period ids
 * @uses apply_filters() Calls 'fct_period_query_record_ids' with the record
 *                        ids and period id
 */
function fct_period_query_record_ids( $period_id ) {
	$record_ids = fct_get_public_child_ids( $period_id, fct_get_record_post_type() );

	return apply_filters( 'fct_period_query_record_ids', $record_ids, $period_id );
}

/**
 * Returns the period's result record ids
 *
 * Records with all statuses are returned.
 *
 * @param int $period_id Period id
 * @uses fct_get_revenue_child_ids() To get the period result account ids
 * @uses fct_get_record_post_type() To get the period post type
 * @uses apply_filters() Calls 'fct_period_query_revenue_record_ids' with the record
 *                        ids and period id
 */
function fct_period_query_revenue_record_ids( $period_id ) {
	$account_ids = fct_period_query_revenue_account_ids( $period_id );
	$record_ids  = array();

	// Get all account record ids
	foreach ( $account_ids as $account_id ) {
		$record_ids = array_merge( $record_ids, fct_get_public_child_ids( $account_id, fct_get_record_post_type() ) );
	}

	return apply_filters( 'fct_period_query_revenue_record_ids', $record_ids, $period_id );
}

/**
 * Returns the period's capital record ids
 *
 * Records with all statuses are returned.
 *
 * @param int $period_id Period id
 * @uses fct_get_capital_child_ids() To get the period capital account ids
 * @uses fct_get_record_post_type() To get the period post type
 * @uses apply_filters() Calls 'fct_period_query_capital_record_ids' with the record
 *                        ids and period id
 */
function fct_period_query_capital_record_ids( $period_id ) {
	$account_ids = fct_period_query_capital_account_ids( $period_id );
	$record_ids  = array();

	// Get all account record ids
	foreach ( $account_ids as $account_id ) {
		$record_ids = array_merge( $record_ids, fct_get_public_child_ids( $account_id, fct_get_record_post_type() ) );
	}

	return apply_filters( 'fct_period_query_capital_record_ids', $record_ids, $period_id );
}

/** Permissions ***************************************************************/

/**
 * Redirect if unathorized user is attempting to edit a period
 * 
 * @uses fct_is_period_edit()
 * @uses current_user_can()
 * @uses fct_get_period_id()
 * @uses wp_safe_redirect()
 * @uses fct_get_period_permalink()
 */
function fct_check_period_edit() {

	// Bail if not editing a period
	if ( ! fct_is_period_edit() )
		return;

	// User cannot edit period, so redirect back to period
	if ( ! current_user_can( 'edit_period', fct_get_period_id() ) ) {
		wp_safe_redirect( fct_get_period_permalink() );
		exit();
	}
}

/**
 * Delete all accounts (and their records) for a specific period ID
 *
 * @param int $period_id
 * @uses fct_get_period_id() To validate the period ID
 * @uses fct_is_period() To make sure it's a period
 * @uses fct_get_account_post_type() To get the account post type
 * @uses fct_accounts() To make sure there are accounts to loop through
 * @uses wp_trash_post() To trash the post
 * @return If period is not valid
 */
function fct_delete_period_accounts( $period_id = 0 ) {

	// Validate period ID
	$period_id = fct_get_period_id( $period_id );
	if ( empty( $period_id ) )
		return;

	// Period is being permanently deleted, so its accounts gotta go too
	if ( $accounts = new WP_Query( array(
		'suppress_filters' => true,
		'post_type'        => fct_get_account_post_type(),
		'post_parent'      => $period_id,
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
 * Trash all accounts inside a period
 * 
 * @param int $period_id
 * @uses fct_get_period_id() To validate the period ID
 * @uses fct_is_period() To make sure it's a period
 * @uses fct_get_public_status_id() To return public post status
 * @uses fct_get_closed_status_id() To return closed post status
 * @uses fct_get_pending_status_id() To return pending post status
 * @uses fct_get_account_post_type() To get the account post type
 * @uses wp_trash_post() To trash the post
 * @uses update_post_meta() To update the period meta of trashed accounts
 * @return If period is not valid
 */
function fct_trash_period_accounts( $period_id = 0 ) {

	// Validate period ID
	$period_id = fct_get_period_id( $period_id );
	if ( empty( $period_id ) )
		return;

	// Allowed post statuses to pre-trash
	$post_stati = join( ',', array(
		fct_get_public_status_id(),
		fct_get_closed_status_id()
	) );

	// Period is being trashed, so its accounts are trashed too
	if ( $accounts = new WP_Query( array(
		'suppress_filters' => true,
		'post_type'        => fct_get_account_post_type(),
		'post_parent'      => $period_id,
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
		update_post_meta( $period_id, '_fct_pre_trashed_accounts', $pre_trashed_accounts );

		// Reset the $post global
		wp_reset_postdata();
	}
}

/**
 * Untrash all previously trashed accounts inside a period
 *
 * @param int $period_id
 * @uses fct_get_period_id() To validate the period ID
 * @uses fct_is_period() To make sure it's a period
 * @uses get_post_meta() To update the period meta of trashed accounts
 * @uses wp_untrash_post() To trash the post
 * @return If period is not valid
 */
function fct_untrash_period_accounts( $period_id = 0 ) {

	// Validate period ID
	$period_id = fct_get_period_id( $period_id );

	if ( empty( $period_id ) )
		return;

	// Get the accounts that were not previously trashed
	$pre_trashed_accounts = get_post_meta( $period_id, '_fct_pre_trashed_accounts', true );

	// There are accounts to untrash
	if ( ! empty( $pre_trashed_accounts ) ) {

		// Maybe reverse the trashed accounts array
		if ( is_array( $pre_trashed_accounts ) ) {
			$pre_trashed_accounts = array_reverse( $pre_trashed_accounts );
		}

		// Loop through accounts
		foreach ( (array) $pre_trashed_accounts as $account ) {
			wp_untrash_post( $account );
		}
	}
}

/** Before Delete/Trash/Untrash ***********************************************/

/**
 * Called before deleting a period
 *
 * This function is supplemental to the actual period deletion which is
 * handled by WordPress core API functions. It is used to clean up after
 * a period that is being deleted.
 *
 * @since 0.0.1
 * 
 * @uses fct_get_period_id() To get the period id
 * @uses fct_is_period() To check if the passed id is a period
 * @uses do_action() Calls 'fct_delete_period' with the period id
 */
function fct_delete_period( $period_id = 0 ) {
	$period_id = fct_get_period_id( $period_id );

	if ( empty( $period_id ) || ! fct_is_period( $period_id ) )
		return false;

	do_action( 'fct_delete_period', $period_id );
}

/**
 * Called before trashing a period
 *
 * This function is supplemental to the actual period being trashed which is
 * handled by WordPress core API functions. It is used to clean up after
 * a period that is being trashed.
 *
 * @since 0.0.1
 * 
 * @uses fct_get_period_id() To get the period id
 * @uses fct_is_period() To check if the passed id is a period
 * @uses do_action() Calls 'fct_trash_period' with the period id
 */
function fct_trash_period( $period_id = 0 ) {
	$period_id = fct_get_period_id( $period_id );

	if ( empty( $period_id ) || ! fct_is_period( $period_id ) )
		return false;

	do_action( 'fct_trash_period', $period_id );
}

/**
 * Called before untrashing a period
 *
 * @since 0.0.1
 * 
 * @uses fct_get_period_id() To get the period id
 * @uses fct_is_period() To check if the passed id is a period
 * @uses do_action() Calls 'fct_untrash_period' with the period id
 */
function fct_untrash_period( $period_id = 0 ) {
	$period_id = fct_get_period_id( $period_id );

	if ( empty( $period_id ) || ! fct_is_period( $period_id ) )
		return false;

	do_action( 'fct_untrash_period', $period_id );
}

/** After Delete/Trash/Untrash ************************************************/

/**
 * Called after deleting a period
 *
 * @since 0.0.1
 * 
 * @uses fct_get_period_id() To get the period id
 * @uses fct_is_period() To check if the passed id is a period
 * @uses do_action() Calls 'fct_deleted_period' with the period id
 */
function fct_deleted_period( $period_id = 0 ) {
	$period_id = fct_get_period_id( $period_id );

	if ( empty( $period_id ) || ! fct_is_period( $period_id ) )
		return false;

	do_action( 'fct_deleted_period', $period_id );
}

/**
 * Called after trashing a period
 *
 * @since 0.0.1
 * 
 * @uses fct_get_period_id() To get the period id
 * @uses fct_is_period() To check if the passed id is a period
 * @uses do_action() Calls 'fct_trashed_period' with the period id
 */
function fct_trashed_period( $period_id = 0 ) {
	$period_id = fct_get_period_id( $period_id );

	if ( empty( $period_id ) || ! fct_is_period( $period_id ) )
		return false;

	do_action( 'fct_trashed_period', $period_id );
}

/**
 * Called after untrashing a period
 *
 * @since 0.0.1
 * 
 * @uses fct_get_period_id() To get the period id
 * @uses fct_is_period() To check if the passed id is a period
 * @uses do_action() Calls 'fct_untrashed_period' with the period id
 */
function fct_untrashed_period( $period_id = 0 ) {
	$period_id = fct_get_period_id( $period_id );

	if ( empty( $period_id ) || ! fct_is_period( $period_id ) )
		return false;

	do_action( 'fct_untrashed_period', $period_id );
}

/** Post Status ***************************************************************/

/**
 * Return all availabel period post statuses
 *
 * @since 0.0.5
 * 
 * @uses apply_filters() Calls 'fct_get_period_statuses' with the period statuses
 * @return array Period statuses as array( status => label )
 */
function fct_get_period_statuses() {
	return apply_filters( 'fct_get_period_statuses', array(
		fct_get_public_status_id() => _x( 'Open',   'fiscaat' ),
		fct_get_closed_status_id() => _x( 'Closed', 'fiscaat' )
	) );
}

