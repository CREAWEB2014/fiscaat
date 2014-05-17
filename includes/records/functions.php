<?php

/**
 * Fiscaat Record Functions
 *
 * @package Fiscaat
 * @subpackage Functions
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** Meta **********************************************************************/

/**
 * Return default record meta keys with values
 * 
 * @param int $record_id
 * @return array
 */
function fct_get_record_default_meta(){
	return apply_filters( 'fct_get_record_default_meta', array(
		'period_id'      => fct_get_current_period_id(), // Period
		'account_id'     => 0,                           // Account
		'record_date'    => '',                          // Physical record date
		'record_type'    => '',                          // 'debit' or 'credit'
		'amount'         => 0,                           // Amount
		'offset_account' => '',                          // Bank account received from or sent to

		/**
		 * Suggested single-entry record meta schema
		 *
		 * This would eliminate the need for double record entries while 
		 * keeping the same amount of record meta items (record_type is 
		 * replaced by a second account_id). The only side note thus far 
		 * seems to be the lacking direct possibility for a separate 
		 * description field on both debit and credit sides. An additional 
		 * meta field can solve this.
		 *
		 * Main benefits of this schema are:
		 *  1. Less record entries (half)
		 *  2. Traceability by directly linking a single record to two accounts
		 *  3. Reduced error rate in amount miscalculations
		 *
		 * Main issues of this schema are:
		 *  1. Lost possibility of multiple opposite entries
		 *  2. Records cannot have a single parent account (post_parent)
		 *  3. One description field for two accounts (post_content)
		 *
		 * Solving aforementioned issues:
		 *  1. Just deal with it
		 *  2. Make record period the post parent?
		 *  3. Use post_content and post_excerpt fields as descriptions
		 *
		 * This means though that a record has not one single parent account,
		 * but two. This will reflect on how record parenting is used
		 * throughout Fiscaat, so this may receive a second opinion. One 
		 * option is to make the period the direct post parent, which means
		 * that accounts no longer would have children posts. Since querying
		 * records by account is already mostly done through the account_id
		 * post meta, this makes no big difference.
		 *
		 * With the new sechma accounts can calculate their ending balances
		 * by comparing all records that have an associated debit_account_id
		 * meta and credit_account_id value respectively. So there's nothing
		 * new.
		 */
		// 'period_id'         => fct_get_current_period_id(), // Period
		// 'debit_account_id'  => 0,                           // Debited account
		// 'credit_account_id' => 0,                           // Credited account
		// 'record_date'       => '',                          // Physical record date
		// 'amount'            => 0,                           // Amount
		// 'offset_account'    => '',                          // Bank account received from or sent to
	) );
}

/**
 * Return stored meta value for given record
 *
 * @uses get_post_meta()
 * 
 * @param string $meta_key Meta key
 * @param int $record_id Period id
 * @return mixed $meta_value
 */
function fct_get_record_meta( $record_id, $meta_key ){
	$meta_value = get_post_meta( $record_id, '_fct_'. $meta_key, true );
	return apply_filters( 'fct_get_record_meta', $meta_value, $record_id, $meta_key );
}

/**
 * Update meta value for given record
 * 
 * @param int $record_id
 * @param string $meta_key
 * @param mixed $meta_value
 * @return boolean
 */
function fct_update_record_meta( $record_id, $meta_key, $meta_value ){
	return update_post_meta( $record_id, '_fct_'. $meta_key, $meta_value );
}

/**
 * Delete meta value for given record
 * 
 * @param int $record_id
 * @param string $meta_key
 * @return boolean
 */
function fct_delete_record_meta( $record_id, $meta_key ){
	return delete_post_meta( $record_id, '_fct_'. $meta_key );
}

/** Insert ********************************************************************/

/**
 * A wrapper for wp_insert_post() that also includes the necessary meta values
 * for the record to function properly.
 *
 * @since 0.0.1
 *
 * @uses fct_parse_args()
 * @uses fct_get_record_post_type()
 * @uses wp_insert_post()
 * @uses fct_update_record_meta()
 *
 * @param array $record_data Record post data
 * @param arrap $record_meta Record meta data
 */
function fct_insert_record( $record_data = array(), $record_meta = array() ) {

	// Record
	$record_data = fct_parse_args( $record_data, array(
		'post_parent'    => 0, // Account ID
		'post_status'    => fct_get_public_status_id(),
		'post_type'      => fct_get_record_post_type(),
		'post_author'    => fct_get_current_user_id(),
		'post_password'  => '',
		'post_content'   => '',
		'post_title'     => '',
		'menu_order'     => 0,
		'comment_status' => 'open' // @todo Fix comment system
	), 'insert_record' );

	// Insert record
	$record_id   = wp_insert_post( $record_data );

	// Bail if no record was added
	if ( empty( $record_id ) ) {
		return false;
	}

	// Record meta
	$record_meta = fct_parse_args( $record_meta, fct_get_record_default_meta(), 'insert_record_meta' );

	// Insert record meta
	foreach ( $record_meta as $meta_key => $meta_value ) {
		fct_update_record_meta( $record_id, $meta_key, $meta_value );
	}

	// Update the account
	$account_id = fct_get_record_account_id( $record_id );
	if ( ! empty( $account_id ) ) {
		fct_update_account( array( 'account_id' => $account_id, 'is_edit' => false ) );
	}

	// Return new record ID
	return $record_id;
}

/** Record Updaters ************************************************************/

/**
 * Update the record with its period id it is in
 *
 * @since 0.0.1
 *
 * @param int $record_id Optional. Record id to update
 * @param int $period_id Optional. Period id
 * @uses fct_get_record_id() To get the record id
 * @uses fct_get_period_id() To get the period id
 * @uses get_post_ancestors() To get the record's period
 * @uses get_post_field() To get the post type of the post
 * @uses update_post_meta() To update the record period id meta
 * @uses apply_filters() Calls 'fct_update_record_period_id' with the period id
 *                        and record id
 * @return bool Record's period id
 */
function fct_update_record_period_id( $record_id = 0, $period_id = 0 ) {
	$record_id = fct_get_record_id( $record_id );
	$period_id = fct_get_period_id( $period_id );

	// If no period_id was passed, walk up ancestors and look for period type
	if ( empty( $period_id ) ) {

		// Get ancestors
		$ancestors = (array) get_post_ancestors( $record_id );

		// Loop through ancestors
		if ( !empty( $ancestors ) ) {
			foreach ( $ancestors as $ancestor ) {

				// Get first parent that is a period
				if ( get_post_field( 'post_type', $ancestor ) == fct_get_period_post_type() ) {
					$period_id = $ancestor;

					// Found a period, so exit the loop and continue
					continue;
				}
			}
		}
	}

	// Update the period ID
	fct_update_record_meta( $record_id, 'period_id', $period_id );

	return apply_filters( 'fct_update_record_period_id', (int) $period_id, $record_id );
}

/**
 * Update the record with its account id it is in
 *
 * @since 0.0.1
 *
 * @param int $record_id Optional. Record id to update
 * @param int $account_id Optional. Account id
 * @uses fct_get_record_id() To get the record id
 * @uses fct_get_account_id() To get the account id
 * @uses get_post_ancestors() To get the record's account
 * @uses get_post_field() To get the post type of the post
 * @uses update_post_meta() To update the record account id meta
 * @uses apply_filters() Calls 'fct_update_record_account_id' with the account id
 *                        and record id
 * @return bool Record's account id
 */
function fct_update_record_account_id( $record_id = 0, $account_id = 0 ) {
	$record_id  = fct_get_record_id( $record_id );
	$account_id = fct_get_account_id( $account_id );

	// If no account_id was passed, walk up ancestors and look for account type
	if ( empty( $account_id ) ) {

		// Get ancestors
		$ancestors = (array) get_post_ancestors( $record_id );

		// Loop through ancestors
		if ( ! empty( $ancestors ) ) {
			foreach ( $ancestors as $ancestor ) {

				// Get first parent that is an account
				if ( get_post_field( 'post_type', $ancestor ) == fct_get_account_post_type() ) {
					$account_id = $ancestor;

					// Found an account, so exit the loop and continue
					continue;
				}
			}
		}
	}

	// Update the account ID
	fct_update_record_meta( $record_id, 'account_id', $account_id );

	return apply_filters( 'fct_update_record_account_id', (int) $account_id, $record_id );
}

/**
 * Adjust the date of a record
 *
 * NOTE: A record should never have it's original date adjusted! 
 * This function therefore should always return false after initial
 * creation.
 *
 * @param int $record_id Optional. Record id
 * @param int $record_date Optional. Record date
 * @return boolean False
 */
function fct_update_record_date( $record_id = 0, $record_date = '' ) {

	// Bail if record already exists: cannot update value
	if ( fct_is_record( $record_id ) ) {
		return false;
	}

	$record_id = fct_get_record_id( $record_id );

	// If no record_date was passed, delete the record meta
	if ( empty( $record_date ) ) {
		fct_delete_record_meta( $record_id, 'record_date' );

	// Update the record date
	} else {

		// Parse mysql date
		$record_date = gmdate( 'Y-m-d H:i:s', $record_date );

		// Update meta
		fct_update_record_meta( $record_id, 'record_date', $record_date );
	}
	
	return apply_filters( 'fct_update_record_date', $record_date, $record_id );
}

/**
 * Adjust the type of a record
 *
 * NOTE: A record should never have it's type adjusted! This 
 * function therefore should always return false after initial
 * creation.
 *
 * @param int $record_id Optional. Record id
 * @param int $record_type Optional. Record type
 * @return boolean False
 */
function fct_update_record_type( $record_id = 0, $record_type = '' ) {

	// Bail if record already exists: cannot update value
	if ( fct_is_record( $record_id ) ) {
		return false;
	}

	$record_id = fct_get_record_id( $record_id );

	// Bail if no valid type
	if ( ! in_array( $record_type, array_keys( fct_get_record_types() ) ) ) {
		return false;
	}

	fct_update_record_meta( $record_id, 'record_type', $record_type );

	return apply_filters( 'fct_update_record_type', $record_type, $record_id );
}

/**
 * Adjust the amount of a record
 *
 * NOTE: A record should never have it's amount adjusted! This 
 * function therefore should always returns after initial
 * creation.
 *
 * @param int $record_id Optional. Record id
 * @param int $amount Optional. Record amount
 * @return boolean False
 */
function fct_update_record_amount( $record_id = 0, $amount = 0 ) {

	// Bail if record already exists: cannot update value
	if ( fct_is_record( $record_id ) ) {
		return false;
	}

	$record_id = fct_get_record_id( $record_id );

	// Bail if no valid amount
	if ( empty( $amount ) || ! is_numeric( $amount ) ) {
		return false;
	}

	// Update the record amount
	fct_update_record_meta( $record_id, 'amount', (float) $amount );

	return apply_filters( 'fct_update_record_amount', (float) $amount, $record_id );
}

/**
 * Adjust the offset account of a record
 *
 * @param int $record_id Optional. Record id
 * @param int $offset_account Optional. Record meta is deleted when empty
 * @uses fct_get_record_id() To get the record's id
 * @uses fct_get_record_account_id() To get the record's account id
 * @uses fct_delete_record_meta() To delete the record's offset account
 * @uses fct_update_record_meta() To update the record's offset account
 * @uses apply_filters() Calls 'fct_update_record_offeset_account' with the
 *                        record id and offset account
 * @return mixed Record's offset account
 */
function fct_update_record_offset_account( $record_id = 0, $offset_account = 0 ) {
	$record_id = fct_get_record_id( $record_id );

	// If no offset_account was passed, delete the record meta
	if ( empty( $offset_account ) ) {
		fct_delete_record_meta( $record_id, 'offset_account' );

	// Update the record offset account
	} else {
		fct_update_record_meta( $record_id, 'offset_account', $offset_account );
	}
	
	return apply_filters( 'fct_update_record_offset_account', $offset_account, $record_id );
}

/**
 * Handle all the extra meta stuff from posting a new record or editing a record
 *
 * @uses fct_get_record_id() To get the record id
 * @uses fct_get_account_id() To get the account id
 * @uses fct_get_period_id() To get the period id
 * @uses fct_get_record_account_id() To get the record account id
 * @uses fct_get_account_period_id() To get the account period id
 * @uses fct_update_record_period_id() To update the record's period id
 * @uses fct_update_record_account_id() To update the record's account id
 * @uses fct_update_record_date() To update the record's date
 * @uses fct_update_record_type() To update the record's type
 * @uses fct_update_record_offset_account() To update the record's offset account
 * @uses fct_update_record_amount() To update the record's amount
 * @uses fct_update_account() To update the record's account
 * 
 * @param mixed $args Optional. Supports these arguments:
 *  - record_id: Record id
 *  - account_id: Account id
 *  - period_id: Period id
 *  - record_type: Record type
 *  - record_date: Record date
 *  - amount: Record amount
 *  - offset_account: Record offset account
 *  - is_edit: Optional. Is the post being edited? Defaults to false.
 */
function fct_update_record( $args = '' ) {

	// Parse arguments against default values
	$r = fct_parse_args( $args, array(
		'record_id'      => 0,
		'account_id'     => 0,
		'period_id'      => 0,
		'record_type'    => 0,
		'record_date'    => 0,
		'amount'         => 0,
		'offset_account' => 0,
		'is_edit'        => false
	), 'update_record' );
	extract( $r );	

	// Validate the ID's passed from 'fct_new_record' action
	$record_id  = fct_get_record_id ( $record_id  );
	$account_id = fct_get_account_id( $account_id );
	$period_id  = fct_get_period_id ( $period_id  );

	// Bail if there is no record
	if ( empty( $record_id ) )
		return;

	// Check account_id
	if ( empty( $account_id ) )
		$account_id = fct_get_record_account_id( $record_id );

	// Check period_id
	if ( ! empty( $account_id ) && empty( $period_id ) )
		$period_id = fct_get_account_period_id( $account_id );

	// Record meta relating to record position in tree
	fct_update_record_period_id ( $record_id, $period_id  );
	fct_update_record_account_id( $record_id, $account_id );

	// Record date
	fct_update_record_date( $record_id, $record_date );

	// Type and Amount. Should return false, because once created, never editable
	fct_update_record_type  ( $record_id, $record_type );
	fct_update_record_amount( $record_id, $amount      );

	// Update offset account
	fct_update_record_offset_account( $record_id, $offset_account );

	// Update associated account values if this is a new record
	if ( empty( $is_edit ) ) {

		// Update parent account
		fct_update_account( array( 'account_id' => $account_id, 'period_id' => $period_id ) );
	}
}

/** Before Delete/Trash/Untrash ***********************************************/

/**
 * Called before deleting a record
 *
 * @uses fct_get_record_id() To get the record id
 * @uses fct_is_record() To check if the passed id is a record
 * @uses do_action() Calls 'fct_delete_record' with the record id
 */
function fct_delete_record( $record_id = 0 ) {
	$record_id = fct_get_record_id( $record_id );

	if ( empty( $record_id ) || ! fct_is_record( $record_id ) )
		return false;

	do_action( 'fct_delete_record', $record_id );
}

/**
 * Called before trashing a record
 *
 * @uses fct_get_record_id() To get the record id
 * @uses fct_is_record() To check if the passed id is a record
 * @uses do_action() Calls 'fct_trash_record' with the record id
 */
function fct_trash_record( $record_id = 0 ) {
	$record_id = fct_get_record_id( $record_id );

	if ( empty( $record_id ) || ! fct_is_record( $record_id ) )
		return false;

	do_action( 'fct_trash_record', $record_id );
}

/**
 * Called before untrashing (restoring) a record
 *
 * @uses fct_get_record_id() To get the record id
 * @uses fct_is_record() To check if the passed id is a record
 * @uses do_action() Calls 'fct_unstrash_record' with the record id
 */
function fct_untrash_record( $record_id = 0 ) {
	$record_id = fct_get_record_id( $record_id );

	if ( empty( $record_id ) || ! fct_is_record( $record_id ) )
		return false;

	do_action( 'fct_untrash_record', $record_id );
}

/** After Delete/Trash/Untrash ************************************************/

/**
 * Called after deleting a record
 *
 * @uses fct_get_record_id() To get the record id
 * @uses fct_is_record() To check if the passed id is a record
 * @uses do_action() Calls 'fct_deleted_record' with the record id
 */
function fct_deleted_record( $record_id = 0 ) {
	$record_id = fct_get_record_id( $record_id );

	if ( empty( $record_id ) || ! fct_is_record( $record_id ) )
		return false;

	do_action( 'fct_deleted_record', $record_id );
}

/**
 * Called after trashing a record
 *
 * @uses fct_get_record_id() To get the record id
 * @uses fct_is_record() To check if the passed id is a record
 * @uses do_action() Calls 'fct_trashed_record' with the record id
 */
function fct_trashed_record( $record_id = 0 ) {
	$record_id = fct_get_record_id( $record_id );

	if ( empty( $record_id ) || ! fct_is_record( $record_id ) )
		return false;

	do_action( 'fct_trashed_record', $record_id );
}

/**
 * Called after untrashing (restoring) a record
 *
 * @uses fct_get_record_id() To get the record id
 * @uses fct_is_record() To check if the passed id is a record
 * @uses do_action() Calls 'fct_untrashed_record' with the record id
 */
function fct_untrashed_record( $record_id = 0 ) {
	$record_id = fct_get_record_id( $record_id );

	if ( empty( $record_id ) || ! fct_is_record( $record_id ) )
		return false;

	do_action( 'fct_untrashed_record', $record_id );
}

/** Settings ******************************************************************/

/**
 * Return the records per page setting
 *
 * @param int $default Default records per page (15)
 * @uses get_option() To get the setting
 * @uses apply_filters() To allow the return value to be manipulated
 * @return int
 */
function fct_get_records_per_page( $default = 15 ) {

	// Get database option and cast as integer
	$retval = get_option( '_fct_records_per_page', $default );

	// If return val is empty, set it to default
	if ( empty( $retval ) )
		$retval = $default;

	// Filter and return
	return (int) apply_filters( 'fct_get_records_per_page', $retval, $default );
}

/** Permissions ***************************************************************/

/**
 * Redirect if unathorized user is attempting to edit a record
 *
 * @uses fct_is_record_edit()
 * @uses current_user_can()
 * @uses fct_get_record_id()
 * @uses wp_safe_redirect()
 * @uses fct_get_account_permalink()
 */
function fct_check_record_edit() {

	// Bail if not editing an account
	if ( ! fct_is_record_edit() )
		return;

	// User cannot edit account, so redirect back to record
	if ( ! current_user_can( 'edit_record', fct_get_record_id() ) ) {
		wp_safe_redirect( fct_get_record_url() );
		exit();
	}
}

/** Post Status ***************************************************************/

/**
 * Return all available record statuses
 *
 * @since 0.0.5
 * 
 * @uses apply_filters() Calls 'fct_get_record_statuses' with the
 *                        record statuses
 * @uses fct_get_public_status_id()
 * @uses fct_get_closed_status_id()
 * @return array Record statuses as array( status => label )
 */
function fct_get_record_statuses() {
	return apply_filters( 'fct_get_record_statuses', array(
		fct_get_public_status_id() => __( 'Open',   'fiscaat' ),
		fct_get_closed_status_id() => __( 'Closed', 'fiscaat' )
	) );
}

/** Record Types **************************************************************/

/**
 * Return the debit record value type id
 *
 * @since 0.0.5
 * 
 * @return string The debit record value type
 */
function fct_get_debit_record_type_id() {
	return fiscaat()->debit_type_id;
}

/**
 * Return the credit record value type id
 * 
 * @since 0.0.5
 * 
 * @return string The credit record value type
 */
function fct_get_credit_record_type_id() {
	return fiscaat()->credit_type_id;
}

/**
 * Return all record types
 *
 * @since 0.0.5
 * 
 * @uses apply_filters() Calls 'fct_get_record_types' with the
 *                        record types
 * @uses fct_get_debit_record_type_id()
 * @uses fct_get_credit_record_type_id()
 * @return array Record types as array( type => label )
 */
function fct_get_record_types() {
	return apply_filters( 'fct_get_record_types', array(
		fct_get_debit_record_type_id()  => __( 'Debit',  'fiscaat' ),
		fct_get_credit_record_type_id() => __( 'Credit', 'fiscaat' )
	) );
}

