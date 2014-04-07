<?php

/**
 * Fiscaat Account Functions
 *
 * @package Fiscaat
 * @subpackage Functions
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** Meta **********************************************************************/

/**
 * Return default account meta keys with values
 *
 * @return array
 */
function fct_get_account_default_meta(){
	return (array) apply_filters( 'fct_get_account_default_meta', array(
		'year_id'                 => fct_get_current_year_id(), // Year 
		'ledger_id'               => 0,                         // Account ledger id
		'account_type'            => '',                        // 'result' or 'asset'
		'record_count'            => 0,                         // Record count
		'from_value'              => 0,                         // Result from balance.
		'to_value'                => 0,                         // Current value to balance or income statment.
		'spectators'              => array()                    // User ids
	) );
}

/**
 * Return stored meta value for given account
 *
 * @uses get_post_meta()
 * 
 * @param string $meta_key Meta key
 * @param int $account_id Year id
 * @return mixed $meta_value
 */
function fct_get_account_meta( $account_id, $meta_key ){
	$meta_value = get_post_meta( $account_id, '_fct_'. $meta_key, true );
	return apply_filters( 'fct_get_account_meta', $meta_value, $account_id, $meta_key );
}

/**
 * Update meta value for given account
 * 
 * @param int $account_id
 * @param string $meta_key
 * @param mixed $meta_value
 * @return boolean
 */
function fct_update_account_meta( $account_id, $meta_key, $meta_value ){
	return update_post_meta( $account_id, '_fct_'. $meta_key, $meta_value );
}

/**
 * Delete meta value for given account
 * 
 * @param int $account_id
 * @param string $meta_key
 * @return boolean
 */
function fct_delete_account_meta( $account_id, $meta_key ){
	return delete_post_meta( $account_id, '_fct_'. $meta_key );
}

/** Insert ********************************************************************/

/**
 * A wrapper for wp_insert_post() that also includes the necessary meta values
 * for the account to function properly.
 *
 * @since Fiscaat (r3349)
 *
 * @uses fct_parse_args()
 * @uses fct_get_account_post_type()
 * @uses wp_insert_post()
 * @uses update_post_meta()
 *
 * @param array $account_data Year post data
 * @param arrap $account_meta Year meta data
 */
function fct_insert_account( $account_data = array(), $account_meta = array() ) {

	// Account
	$default_account = array(
		'post_parent'    => fct_get_current_year_id(), // Year id
		'post_status'    => fct_get_public_status_id(),
		'post_type'      => fct_get_account_post_type(),
		'post_author'    => fct_get_current_user_id(),
		'post_password'  => '',
		'post_content'   => '',
		'post_title'     => '',
		'comment_status' => 'closed',
		'menu_order'     => 0,
	);

	// Parse args
	$account_data = fct_parse_args( $account_data, $default_account, 'insert_account' );

	// Insert account
	$account_id   = wp_insert_post( $account_data );

	// Bail if no account was added
	if ( empty( $account_id ) )
		return false;

	// Account meta
	$account_meta = fct_parse_args( $account_meta, fct_get_account_default_meta(), 'insert_account_meta' );

	// Insert account meta
	foreach ( $account_meta as $meta_key => $meta_value )
		fct_update_account_meta( $account_id, $meta_key, $meta_value );

	// Update the year
	$year_id = fct_get_account_year_id( $account_id );
	if ( ! empty( $year_id ) ) {
		fct_update_year( array( 'year_id' => $year_id ) );
	}

	// Return new account ID
	return $account_id;
}

/** Count Bumpers *************************************************************/

/**
 * Bump the total record count of an account
 *
 * @param int $account_id Optional. Account id.
 * @param int $difference Optional. Default 1
 * @param bool $update_ancestors Optional. Default true
 * @uses fct_get_account_id() To get the account id
 * @uses fct_update_account_meta() To update the account's record count meta
 * @uses apply_filters() Calls 'fct_bump_account_record_count' with the record
 *                        count, account id, and difference
 * @return int Account record count
 */
function fct_bump_account_record_count( $account_id = 0, $difference = 1 ) {

	// Get counts
	$account_id   = fct_get_account_id( $account_id );
	$record_count = fct_get_account_record_count( $account_id, false );
	$new_count    = (int) $record_count + (int) $difference;

	// Update this account id's record count
	fct_update_account_meta( $account_id, 'record_count', (int) $new_count );

	return (int) apply_filters( 'fct_bump_account_record_count', (int) $new_count, $account_id, (int) $difference );
}

/**
 * Bump the total to value of an account
 *
 * Fiscaat handles an account's to value as credit less debit.
 * 
 * @param int $account_id Optional. Account id
 * @param int $add_value Value to add
 * @param string $value_type Value type to add
 * @uses fct_is_record() To find if given id is a record
 * @uses fct_get_account_id() To get the account id
 * @uses fct_get_record_account_id() To get the record's account id
 * @uses fct_get_account_meta() To get the account's to value
 * @uses fct_get_debit_record_type() To get the debit type id
 * @uses fct_get_credit_record_type() To get the credit type id
 * @uses fct_update_account_meta() To update the account's to value
 * @uses apply_filters() Calls 'fct_bump_account_to_value' with the to value,
 *                               account id, added value, and value type
 * @return int Account to value
 */
function fct_bump_account_to_value( $account_id = 0, $add_value = 0, $value_type = '' ) {

	// Bail if no valid params
	if ( empty( $add_value ) || ! in_array( $value_type, array( fct_get_debit_record_type(), fct_get_credit_record_type() ) ) )
		return false;

	// If it's a record, then get the parent (account id)
	if ( fct_is_record( $account_id ) ) {
		$account_id = fct_get_record_account_id( $account_id );
	} else {
		$account_id = fct_get_account_id( $account_id );
	}

	// Get to values
	$to_value     = fct_get_account_meta( $account_id, 'to_value' );
	$new_to_value = (int) $to_value;

	// Value less debit
	if ( $value_type == fct_get_debit_record_type() ) {
		$new_to_value= (int) $add_value;

	// Value plus credit
	} elseif ( $value_type == fct_get_credit_record_type() ) {
		$new_to_value += (int) $add_value;
	}

	// Update this account id's to value
	fct_update_account_meta( $account_id, 'to_value', (int) $new_to_value );

	return (int) apply_filters( 'fct_bump_account_to_value', (int) $new_to_value, $account_id, (int) $value, $value_type );
}

/** Account Updaters ************************************************************/

/**
 * Update the account's year id
 *
 * @param int $account_id Optional. Account id to update
 * @param int $year_id Optional. Year id
 * @uses fct_get_account_id() To get the account id
 * @uses get_post_field() To get the post parent of the account id
 * @uses fct_get_year_id() To get the year id
 * @uses fct_update_account_meta() To update the account year id meta
 * @uses apply_filters() Calls 'fct_update_account_year_id' with the year id
 *                        and account id
 * @return int Year id
 */
function fct_update_account_year_id( $account_id = 0, $year_id = 0 ) {
	$account_id = fct_get_account_id( $account_id );

	if ( empty( $year_id ) ) {
		$year_id = get_post_field( 'post_parent', $account_id );
	}

	fct_update_account_meta( $account_id, 'year_id', (int) $year_id );

	return apply_filters( 'fct_update_account_year_id', (int) $year_id, $account_id );
}

/**
 * Update the account's ledger id
 *
 * @param int $account_id Optional. Account id to update
 * @param int $ledger_id Required. Ledger id
 * @uses fct_get_account_id() To get the account id
 * @uses fct_update_account_meta() To update the account ledger id meta
 * @uses apply_filters() Calls 'fct_update_account_ledger_id' with the ledger id
 *                        and account id
 * @return int Ledger id
 */
function fct_update_account_ledger_id( $account_id = 0, $ledger_id = 0 ) {
	$account_id = fct_get_account_id( $account_id );

	// Bail if no valid param
	if ( empty( $ledger_id ) )
		return false;

	// Bail if ledger id conflict
	fct_check_ledger_id( $account_id, (int) $ledger_id );

	// Update the meta value
	fct_update_account_meta( $account_id, 'ledger_id', (int) $ledger_id );

	return (int) apply_filters( 'fct_update_account_ledger_id', (int) $ledger_id, $account_id );
}

/**
 * Redirect user when conflicting ledger id is given
 * 
 * @param int $account_id Account id
 * @param int $ledger_id Ledger id
 * @uses fct_get_account_ledger_id()
 */
function fct_check_ledger_id( $account_id, $ledger_id ) {
	$old_ledger_id = fct_get_account_ledger_id( $account_id );

	// Ledger id already taken
	if ( ! empty( $ledger_id ) && ! in_array( (int) $ledger_id, array_diff( fct_get_year_ledger_ids(), array( $old_ledger_id ) ) ) )
		return;

	// Set the right message
	$message = ! empty( $ledger_id ) ? 12 : 13;

	// Redirect to edit page with message
	wp_safe_redirect( add_query_arg( array( 'post' => $account_id, 'action' => 'edit', 'fct_ledger_id' => (int) $ledger_id, 'message' => $message ), admin_url( 'post.php' ) ) );

	// For good measure
	exit;
}

/**
 * Update the account's account type
 *
 * @param int $account_id Optional. Account id to update
 * @param int $account_type Required. Ledger id
 * @uses fct_get_account_id() To get the account id
 * @uses fct_update_account_meta() To update the account account type meta
 * @uses apply_filters() Calls 'fct_update_account_account_type' with the account type
 *                        and account id
 * @return int Account type
 */
function fct_update_account_account_type( $account_id = 0, $account_type = '' ) {
	$account_id = fct_get_account_id( $account_id );

	// Only update if param given
	if ( empty( $account_type ) )
		return false;

	// Bail if no valid param
	if ( ! in_array( $account_type, array( fct_get_result_account_type(), fct_get_asset_account_type() ) ) )
		return false;

	fct_update_account_meta( $account_id, 'account_type', $account_type );

	return apply_filters( 'fct_update_account_account_type', $account_type, $account_id );
}

/**
 * Adjust the total record count of an account
 *
 * @param int $account_id Optional. Account id to update
 * @param int $record_count Optional. Set the record count manually.
 * @uses fct_is_record() To check if the passed account id is a record
 * @uses fct_get_record_account_id() To get the record account id
 * @uses fct_get_account_id() To get the account id
 * @uses fct_get_record_post_type() To get the record post type
 * @uses fct_get_public_child_count() To get the record count
 * @uses fct_update_account_meta() To update the account record count meta
 * @uses apply_filters() Calls 'fct_update_account_record_count' with the record
 *                        count and account id
 * @return int Account record count
 */
function fct_update_account_record_count( $account_id = 0, $record_count = 0 ) {

	// If it's a record, then get the parent (account id)
	if ( fct_is_record( $account_id ) ) {
		$account_id = fct_get_record_account_id( $account_id );
	} else {
		$account_id = fct_get_account_id( $account_id );
	}

	// Get records of account if not passed
	if ( empty( $record_count ) ) {
		$record_count = fct_get_public_child_count( $account_id, fct_get_record_post_type() );
	}

	fct_update_account_meta( $account_id, 'record_count', (int) $record_count );

	return apply_filters( 'fct_update_account_record_count', (int) $record_count, $account_id );
}

/**
 * Adjust the total to value of an account
 *
 * Fiscaat handles an account's to value as credit less debit.
 * 
 * @param int $account_id Optional. Accoun id to update
 * @param boolean|int $value Optional. Set the record to value manually
 * @uses fct_is_record() To find if given id is a record
 * @uses fct_get_record_account_id() To get the record's account id
 * @uses fct_get_account_id() To get the account id
 * @uses fct_get_public_child_ids() To get the account's records
 * @uses fct_get_account_meta() To get the account's to value
 * @uses fct_get_debit_record_type() To get the debit type id
 * @uses fct_get_credit_record_type() To get the credit type id
 * @uses fct_update_record_meta() To update the record's to value and value type
 * @uses apply_filters() Calls 'fct_update_account_to_value' with the to value
 *                               and account id
 * @return int Account to value
 */
function fct_update_account_to_value( $account_id = 0, $to_value = false ) {

	// If it's a record, then get the parent (account id)
	if ( fct_is_record( $account_id ) ) {
		$account_id = fct_get_record_account_id( $account_id );
	} else {
		$account_id = fct_get_account_id( $account_id );
	}

	// Get value if none given
	if ( false === $to_value ) {

		// Get records of account
		$record_ids = fct_get_public_child_ids( $account_id, fct_get_record_post_type() );

		if ( ! empty( $record_ids ) ) {

			// Setup values array
			$values = array( fct_get_debit_record_type() => 0, fct_get_credit_record_type() => 0 );

			// Loop records and add record value to value type
			foreach ( $record_ids as $record_id ) {
				$values[ fct_get_record_value_type( $record_id ) ] += fct_get_record_value( $record_id );
			}

			// Less credit with debit
			$to_value = $values[ fct_get_credit_record_type() ] - $values[ fct_get_debit_record_type() ];

		// No records
		} else {
			$to_value = 0;
		}
	}

	fct_update_account_meta( $account_id, 'to_value', (float) $to_value );

	return (float) apply_filters( 'fct_update_account_to_value', (float) $to_value, $account_id );
}

/**
 * Adjust the user ids as spectators of an account
 *
 * New value must be set as param.
 * 
 * @param int $account_id Optional. Account id to update
 * @param boolean|array $spectators False if not to update, array as new value
 * @uses fct_get_account_id() To get the account id
 * @uses fct_update_account_meat() To update the account spectator meta
 * @uses apply_filters() Calls 'fct_update_account_spectators' with the
 *                        spectators and account id
 * @return array Account spectators
 */
function fct_update_account_spectators( $account_id = 0, $spectators = false ) {
	$account_id = fct_get_account_id( $account_id );

	// Specifically ignore updating to prevent empty arrays be pushed
	if ( false === $spectators )
		return false;

	// Sanitize new spectators
	$spectators = array_map( 'intval', (array) $spectators );

	fct_update_account_meta( $account_id, 'spectators', $spectators );

	return (array) apply_filters( 'fct_update_account_spectators', $spectators, $account_id );
}

/**
 * Handle all the extra meta stuff from posting a new account
 *
 * @param string|array $args Optional. Update arguments
 * @uses fct_get_account_id() To get the account id
 * @uses fct_get_year_id() To get the year id
 * @uses fct_get_account_year_id() To get the account year id
 * @uses fct_update_account_year_id() To update the account's year id
 * @uses fct_update_account_account_id() To update the account's account id
 * @uses fct_update_account_record_count() To update the account record count
 * @uses fct_update_account_record_count_declined() To udpate the account declined record count
 * @uses fct_update_account_record_count_unapproved() To udpate the account unapproved record count
 * @uses fct_update_year() To udpate the account's year
 */
function fct_update_account( $args = '' ) {
	$defaults = array(
		'account_id'   => 0,
		'year_id'      => 0,
		'ledger_id'    => 0,
		'account_type' => '',
		'to_value'     => false,
		'spectators'   => false,
		'is_edit'      => true
	);
	$r = fct_parse_args( $args, $defaults, 'update_account' );
	extract( $r );	

	// Validate the ID's passed from 'fct_new_account' action
	$account_id = fct_get_account_id( $account_id );

	// Bail if there is no account
	if ( empty( $account_id ) )
		return;

	// Check year_id
	if ( empty( $year_id ) )
		$year_id = fct_get_account_year_id( $account_id );

	// Year account meta
	fct_update_account_year_id( $account_id, $year_id );

	// Account type
	fct_update_account_account_type( $account_id, $account_type );

	// Update ledger id
	fct_update_account_ledger_id( $account_id, $ledger_id );

	// Update account spectators
	fct_update_account_spectators( $account_id, $spectators );

	// Update associated account values if this is not a new account
	if ( empty( $is_edit ) ) {

		// Record account meta
		fct_update_account_record_count           ( $account_id, 0         );
		// @todo Move to Control
		// fct_update_account_record_count_declined  ( $account_id, 0         );
		// fct_update_account_record_count_unapproved( $account_id, 0         );
		fct_update_account_to_value               ( $account_id, $to_value );

		// Update account year
		fct_update_year( array( 'year_id' => $year_id ) );
	}
}

/** Queries *********************************************************************/

/**
 * Returns whether there exists an open account in Fiscaat
 *
 * @uses fct_get_account_post_type()
 * @uses apply_filters() Calls 'fct_has_open_account' with Fiscaat has open account
 * @return bool Fiscaat has open account
 */
function fct_has_open_account() {
	$counts = wp_count_posts( fct_get_account_post_type() );
	$retval = (bool) $counts->publish; 

	return (bool) apply_filters( 'fct_has_open_account', $retval );
}

/**
 * Returns whether the account has any records
 * 
 * @param int $account_id Year id
 * @uses fct_get_public_child_count()
 * @uses fct_get_account_id()
 * @uses fct_get_record_post_type()
 * @uses apply_filters() Calls 'fct_account_has_records' with account
 *                        has records and account id
 * @return bool Year has records
 */
function fct_account_has_records( $account_id = 0 ) {
	$record_count = fct_get_public_child_count( fct_get_account_id( $account_id ), fct_get_record_post_type() );

	return (bool) apply_filters( 'fct_account_has_records', $record_count > 0, $account_id );
}

/**
 * Returns an array of all ledger id for the year
 *
 * @param int $year_id Optional. Year id
 * @uses fct_get_year_id()
 * @uses fct_get_account_post_type()
 * @uses fct_get_account_ledger_id()
 * @uses apply_filters() Calls 'fct_get_year_ledger_ids' with
 *                        the ids and year id
 * @return array Year ledger ids
 */
function fct_get_year_ledger_ids( $year_id = 0 ) {
	$year_id = fct_get_year_id( $year_id );
	$ids     = array();

	if ( $accounts = new WP_Query( array(
		'post_type' => fct_get_account_post_type(),
		'parent'    => $year_id,
		'meta_key'  => '_fct_ledger_id',
		'fields'    => 'ids'
	) ) ) {

		// Walk query result
		foreach ( $accounts->posts as $account_id ){

			// Array as account id => ledger id
			$ids[$account_id] = fct_get_account_ledger_id( $account_id );
		}
	}

	// Sort array by ledger ids
	asort( $ids );

	return apply_filters( 'fct_get_year_ledger_ids', $ids, $year_id );
}

/** Account Actions *************************************************************/

/**
 * Closes an account
 *
 * @param int $account_id Account id
 * @uses get_post() To get the account
 * @uses do_action() Calls 'fct_close_account' with the account id
 * @uses add_post_meta() To add the previous status to a meta
 * @uses wp_insert_post() To update the account with the new status
 * @uses do_action() Calls 'fct_opened_account' with the account id
 * @return mixed False or {@link WP_Error} on failure, account id on success
 */
function fct_close_account( $account_id = 0 ) {

	// Get account
	if ( ! $account = get_post( $account_id, ARRAY_A ) )
		return $account;

	// Bail if already closed
	$bail = fct_get_closed_status_id() == $account['post_status'];
	if ( apply_filters( 'fct_no_close_account', $bail, $account ) )
		return false;

	// Execute pre close code
	do_action( 'fct_close_account', $account_id );

	// Set closed status
	$account['post_status'] = fct_get_closed_status_id();

	// No revisions
	remove_action( 'pre_post_update', 'wp_save_post_revision' );

	// Update account
	$account_id = wp_insert_post( $account );

	// Execute post close code
	do_action( 'fct_closed_account', $account_id );

	// Return account_id
	return $account_id;
}

/**
 * Opens an account
 *
 * @param int $account_id Account id
 * @uses get_post() To get the account
 * @uses do_action() Calls 'fct_open_account' with the account id
 * @uses get_post_meta() To get the previous status
 * @uses delete_post_meta() To delete the previous status meta
 * @uses wp_insert_post() To update the account with the new status
 * @uses do_action() Calls 'fct_opened_account' with the account id
 * @return mixed False or {@link WP_Error} on failure, account id on success
 */
function fct_open_account( $account_id = 0 ) {

	// Get account
	if ( !$account = get_post( $account_id, ARRAY_A ) )
		return $account;

	// Bail if already open
	if ( fct_get_closed_status_id() != $account['post_status'])
		return false;

	// Execute pre open code
	do_action( 'fct_open_account', $account_id );

	// Set previous status
	$account['post_status'] = fct_get_public_status_id();

	// No revisions
	remove_action( 'pre_post_update', 'wp_save_post_revision' );

	// Update account
	$account_id = wp_insert_post( $account );

	// Execute post open code
	do_action( 'fct_opened_account', $account_id );

	// Return account_id
	return $account_id;
}

/** Before Delete/Trash/Untrash ***********************************************/

/**
 * Called before deleting an account.
 * 
 * This function is supplemental to the actual account deletion which is
 * handled by WordPress core API functions. It is used to clean up after
 * an account that is being deleted.
 *
 * @uses fct_get_account_id() To get the account id
 * @uses fct_is_account() To check if the passed id is an account
 * @uses do_action() Calls 'fct_delete_account' with the account id
 * @uses fct_has_records() To check if the account has records
 * @uses fct_records() To loop through the records
 * @uses fct_the_record() To set a record as the current record in the loop
 * @uses fct_get_record_id() To get the record id
 * @uses wp_delete_post() To delete the record
 */
function fct_delete_account( $account_id = 0 ) {

	// Validate account ID
	$account_id = fct_get_account_id( $account_id );

	if ( empty( $account_id ) || !fct_is_account( $account_id ) )
		return false;

	do_action( 'fct_delete_account', $account_id );

	// Account is being permanently deleted, so its records gotta go too
	if ( $records = new WP_Query( array(
		'suppress_filters' => true,
		'post_type'        => fct_get_record_post_type(),
		'post_status'      => 'any',
		'post_parent'      => $account_id,
		'posts_per_page'   => -1,
		'nopaging'         => true,
		'fields'           => 'id=>parent'
	) ) ) {
		foreach ( $records->posts as $record ) {
			wp_delete_post( $record->ID, true );
		}

		// Reset the $post global
		wp_reset_postdata();
	}
}

/**
 * Called before trashing an account
 *
 * This function is supplemental to the actual account being trashed which is
 * handled by WordPress core API functions. It is used to clean up after
 * an account that is being trashed.
 * 
 * @uses fct_get_account_id() To get the account id
 * @uses fct_is_account() To check if the passed id is an account
 * @uses do_action() Calls 'fct_trash_account' with the account id
 * @uses wp_trash_post() To trash the record
 * @uses update_post_meta() To save a list of just trashed records for future use
 */
function fct_trash_account( $account_id = 0 ) {

	// Validate account ID
	$account_id = fct_get_account_id( $account_id );

	if ( empty( $account_id ) || !fct_is_account( $account_id ) )
		return false;

	do_action( 'fct_trash_account', $account_id );

	// Account is being trashed, so its records are trashed too
	if ( $records = new WP_Query( array(
		'suppress_filters' => true,
		'post_type'        => fct_get_record_post_type(),
		'post_status'      => fct_get_public_status_id(),
		'post_parent'      => $account_id,
		'posts_per_page'   => -1,
		'nopaging'         => true,
		'fields'           => 'id=>parent'
	) ) ) {

		// Prevent debug notices
		$pre_trashed_records = array();

		// Loop through records, trash them, and add them to array
		foreach ( $records->posts as $record ) {
			wp_trash_post( $record->ID );
			$pre_trashed_records[] = $record->ID;
		}

		// Set a post_meta entry of the records that were trashed by this action.
		// This is so we can possibly untrash them, without untrashing records
		// that were purposefully trashed before.
		update_post_meta( $account_id, '_fct_pre_trashed_records', $pre_trashed_records );

		// Reset the $post global
		wp_reset_postdata();
	}
}

/**
 * Called before untrashing an account
 *
 * @uses fct_get_account_id() To get the account id
 * @uses fct_is_account() To check if the passed id is an account
 * @uses do_action() Calls 'fct_untrash_account' with the account id
 * @uses get_post_meta() To get the list of records which were trashed with the
 *                        account
 * @uses wp_untrash_post() To untrash the record
 */
function fct_untrash_account( $account_id = 0 ) {
	$account_id = fct_get_account_id( $account_id );

	if ( empty( $account_id ) || ! fct_is_account( $account_id ) )
		return false;

	do_action( 'fct_untrash_account', $account_id );

	// Get the records that were not previously trashed
	$pre_trashed_records = get_post_meta( $account_id, '_fct_pre_trashed_records', true );

	// There are records to untrash
	if ( !empty( $pre_trashed_records ) ) {

		// Maybe reverse the trashed records array
		if ( is_array( $pre_trashed_records ) )
			$pre_trashed_records = array_reverse( $pre_trashed_records );

		// Loop through records
		foreach ( (array) $pre_trashed_records as $record ) {
			wp_untrash_post( $record );
		}
	}
}

/** After Delete/Trash/Untrash ************************************************/

/**
 * Called after deleting an account
 *
 * @uses fct_get_account_id() To get the account id
 * @uses fct_is_account() To check if the passed id is an account
 * @uses do_action() Calls 'fct_deleted_account' with the account id
 */
function fct_deleted_account( $account_id = 0 ) {
	$account_id = fct_get_account_id( $account_id );

	if ( empty( $account_id ) || ! fct_is_account( $account_id ) )
		return false;

	do_action( 'fct_deleted_account', $account_id );
}

/**
 * Called after trashing an account
 *
 * @uses fct_get_account_id() To get the account id
 * @uses fct_is_account() To check if the passed id is an account
 * @uses do_action() Calls 'fct_trashed_account' with the account id
 */
function fct_trashed_account( $account_id = 0 ) {
	$account_id = fct_get_account_id( $account_id );

	if ( empty( $account_id ) || ! fct_is_account( $account_id ) )
		return false;

	do_action( 'fct_trashed_account', $account_id );
}

/**
 * Called after untrashing an account
 *
 * @uses fct_get_account_id() To get the account id
 * @uses fct_is_account() To check if the passed id is an account
 * @uses do_action() Calls 'fct_untrashed_account' with the account id
 */
function fct_untrashed_account( $account_id = 0 ) {
	$account_id = fct_get_account_id( $account_id );

	if ( empty( $account_id ) || ! fct_is_account( $account_id ) )
		return false;

	do_action( 'fct_untrashed_account', $account_id );
}

/** Settings ******************************************************************/

/**
 * Return the accounts per page setting
 *
 * @param int $default Default records per page (15)
 * @uses get_option() To get the setting
 * @uses apply_filters() To allow the return value to be manipulated
 * @return int
 */
function fct_get_accounts_per_page( $default = 15 ) {

	// Get database option and cast as integer
	$retval = get_option( '_fct_accounts_per_page', $default );

	// If return val is empty, set it to default
	if ( empty( $retval ) )
		$retval = $default;

	// Filter and return
	return (int) apply_filters( 'fct_get_accounts_per_page', $retval, $default );
}

/** Permissions ***************************************************************/

/**
 * Redirect if unathorized user is attempting to edit an account
 * 
 * @uses fct_is_account_edit()
 * @uses current_user_can()
 * @uses fct_get_account_id()
 * @uses wp_safe_redirect()
 * @uses fct_get_account_permalink()
 */
function fct_check_account_edit() {

	// Bail if not editing an account
	if ( ! fct_is_account_edit() )
		return;

	// User cannot edit account, so redirect back to account
	if ( ! current_user_can( 'edit_account', fct_get_account_id() ) ) {
		wp_safe_redirect( fct_get_account_permalink() );
		exit();
	}
}