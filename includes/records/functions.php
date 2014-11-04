<?php

/**
 * Fiscaat Record Functions
 *
 * @package Fiscaat
 * @subpackage Functions
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Meta **********************************************************************/

/**
 * Return default record meta keys with values
 * 
 * @param int $record_id
 * @return array
 */
function fct_get_record_default_meta(){
	return apply_filters( 'fct_get_record_default_meta', array(
		'period_id'      => fct_get_current_period_id(),       // Period
		'account_id'     => 0,                                 // Account
		'record_date'    => fct_current_time( 'mysql', true ), // Physical record date GMT
		'record_type'    => '',                                // 'debit' or 'credit'
		'amount'         => 0,                                 // Amount
		'offset_account' => '',                                // Bank account received from or sent to

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

	// Bail when inserting in closed account
	if ( fct_is_account_closed( $record_data['post_parent'] ) )
		return false;

	// Insert record
	$record_id = wp_insert_post( $record_data );

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
 * @param bool $gmt Optional. Whether the provided value uses GMT
 * @return boolean False
 */
function fct_update_record_date( $record_id = 0, $record_date = '', $gmt = false ) {

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
		$record_date = call_user_func_array( ! $gmt ? 'gmdate' : 'date', array( 'Y-m-d H:i:s', $record_date ) );

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
		'record_date'    => 0,
		'record_type'    => 0,
		'amount'         => 0,
		'offset_account' => 0,
		'is_edit'        => false
	), 'update_record' );

	// Validate the ID's passed from 'fct_new_record' action
	$record_id  = fct_get_record_id ( $r['record_id']  );
	$account_id = fct_get_account_id( $r['account_id'] );
	$period_id  = fct_get_period_id ( $r['period_id']  );

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
	fct_update_record_date( $record_id, $r['record_date'] );

	// Type and Amount. Should return false, because once created, never editable
	fct_update_record_type  ( $record_id, $r['record_type'] );
	fct_update_record_amount( $record_id, $r['amount']      );

	// Update offset account
	fct_update_record_offset_account( $record_id, $r['offset_account'] );

	// Update parent account
	fct_update_account( array( 'account_id' => $account_id, 'period_id' => $period_id ) );
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
 * @return array Record statuses as array( status => label )
 */
function fct_get_record_statuses() {
	return apply_filters( 'fct_get_record_statuses', array(
		fct_get_public_status_id() => __( 'Published' ),
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
		fct_get_credit_record_type_id() => __( 'Credit', 'fiscaat' ),
	) );
}

/** Insert Records ********************************************************/

/**
 * Handle bulk inserting of new or edited records
 * 
 * @since 0.0.9
 *
 * @uses current_user_can()
 * @uses fct_transform_records_input()
 * @uses fct_sanitize_record_data()
 * 
 * @param array $records The records data to insert
 * @param bool $is_edit Optional. Whether the records are being edited. Defaults to false
 * @return array|WP_Error Inserted record ids or an error object
 */
function fct_bulk_insert_records( $records = array(), $is_edit = false ) {

	// Bail when user is not capable
	if ( ! current_user_can( 'create_records' ) || ! current_user_can( 'edit_records' ) )
		return;

	// Bail when there's nothing to insert or errors already exist (from elsewhere?)
	if ( empty( $records ) || ! is_array( $records ) || fct_has_errors() )
		return;

	// Sanitize record data
	array_walk( $records, 'fct_sanitize_record_data', $is_edit );

	// Register error when the amount sums are not equal
	if ( array_sum( array_map( 'floatval', wp_list_pluck( wp_list_filter( $records, array( 'record_type' => fct_get_debit_record_type_id()  ) ), 'amount' ) ) )
		!== array_sum( array_map( 'floatval', wp_list_pluck( wp_list_filter( $records, array( 'record_type' => fct_get_credit_record_type_id() ) ), 'amount' ) ) ) 
	) {
		fct_add_error( 'unequal', __( 'The credit sum does not equal the debit sum.', 'fiscaat' ) );
	}

	// Bail when errors are registered
	if ( fct_has_errors() )
		return;

	/**
	 * Runs before bulk inserting records
	 *
	 * @since 0.0.9
	 *
	 * @param array $records The record data used for inserting
	 * @param bool $is_edit Whether the data is from edited records
	 */
	do_action( 'fct_bulk_insert_records', $records, $is_edit );

	// Walk all records
	$record_ids = array();
	foreach ( $records as $i => $record_data ) {

		// Create new record
		$record_ids[] = fct_insert_record( 
			// Select available fields for the post object by key
			array_intersect_key(
				$record_data,
				// Default post object fields
				array_flip( array( 'ID', 'post_parent', 'post_status', 'post_author', 'post_content' ) )
			// Select available meta fields for the record by key
			), array_intersect_key(
				$record_data,
				// Default record meta fields
				fct_get_record_default_meta()
			)
		);
	}

	/**
	 * Runs after bulk inserting records
	 *
	 * @since 0.0.9
	 *
	 * @param array $record_ids The ids of the inserted records
	 * @param array $records The record data used for inserting
	 * @param bool $is_edit Whether the data is from edited records
	 */
	do_action( 'fct_bulk_inserted_records', $record_ids, $records, $is_edit );

	return $record_ids;
}

/**
 * Transform the records input (new or edit) to return usable records data
 *
 * @since 0.0.9
 * 
 * @uses fct_get_record_types()
 * @param array|string $input Optional. Array or $_REQUEST key of input data. Defaults to 'records'
 * @param array $data_map Optional. Mapping of record data keys to input field names
 * @return array|bool Transformed records or False when the process failed
 */
function fct_transform_records_input( $input = array(), $data_map = array() ) {

	// Get records to process
	if ( ! is_array( $input ) ) {
		// Get request input key. Defaults to 'records'
		$input_key = is_string( $input ) && ! empty( $input ) ? $input : 'records';
		$input     = ! empty( $_REQUEST[ $input_key ] ) ? (array) $_REQUEST[ $input_key ] : array();
	} 

	// Nothing to process
	if ( empty( $input ) ) {
		return false;
	}

	// Define data mapping variable as: Record data key => Input field name
	$data_map = fct_parse_args( $data_map, array(
		'ID'                  => 'ID',
		'post_parent'         => 'account_id',
		'post_content'        => 'description',
		'period_id'           => 'period_id',
		'account_id'          => 'account_id',
		'record_date'         => 'record_date',
		'record_type'         => 'amount',
		'amount'              => array( 'amount' => array_keys( fct_get_record_types() ) ),
		'offset_account'      => 'offset_account',
	), 'transform_records_input' );

	// Bail if input is already properly structured
	if ( 0 === count( array_intersect( array_values( $data_map ), array_keys( $input ) ) ) ) {
		return $input;
	}

	$records = array();

	// Walk data fields
	foreach ( $data_map as $data_key => $field_name ) {
		$subfields = array();

		// Field has subfields
		if ( is_array( $field_name ) ) {
			$field = $field_name;
			$field_name = key( $field_name );
			$subfields = $field[ $field_name ];
		}

		// Bail when field is not present
		if ( ! isset( $input[ $field_name ] ) )
			continue;

		// Loop all entries of this field
		foreach ( $input[ $field_name ] as $i => $value ) {

			// Handle subfields
			if ( is_array( $value ) && in_array( $i, $subfields ) ) {
				foreach ( $value as $j => $subvalue ) {

					// Only process non-empty inputs
					if ( ! empty( $subvalue ) ) {
						$records[ $j ][ $data_key ] = $subvalue;
					}
				}

			// Handle arrays
			} elseif ( is_array( $value ) ) {
				foreach ( $value as $j => $_value ) {

					// Only process non-empty inputs
					if ( ! empty( $_value ) ) {
						// Set field key, for the value exists for this entry
						$records[ $j ][ $data_key ] = $i;
					}
				}

			// Only process non-empty inputs
			} elseif ( ! empty( $value ) ) {
				$records[ $i ][ $data_key ] = $value;
			}
		}
	}

	return apply_filters( 'fct_transform_records_input', $records, $input, $data_map );
}

/**
 * Sanitize a single record's data. Used before inserting records
 *
 * @since 0.0.9
 *
 * @uses fct_get_required_record_fields()
 * @uses fct_add_error()
 * @uses fct_is_record()
 * @uses fct_is_account()
 * @uses fct_is_period()
 * @uses fct_get_record_types()
 * @uses is_wp_error()
 * @uses apply_filters() Calls 'fct_record_date_input_format'
 * @uses apply_filters() Calls 'fct_sanitize_record_data'
 * 
 * @param array $data Record data
 * @param bool|int $record_id Optional. The sanitized record's ID. Defaults to false
 * @param bool $is_edit Optional. Whether the data is from editing records
 * @return array Record data
 */
function fct_sanitize_record_data( $data, $record_id = false, $is_edit = false ) {

	// Define WP_Error error codes
	$error_code_missing = 'missing';
	$error_code_invalid = 'invalid';
	if ( false !== $record_id ) {
		$error_code_missing .= '-' . (int) $record_id;
		$error_code_invalid .= '-' . (int) $record_id;
	}

	// Loop the required fields
	foreach ( fct_get_required_record_fields( $is_edit ) as $required_field ) {
		// Field is not provided
		if ( ! in_array( $required_field, array_keys( $data ) ) || empty( $data[ $required_field ] ) ) {
			// Register the field as missing
			fct_add_error( $error_code_missing, $required_field );
		}
	}

	// Loop fields to sanitize
	foreach ( $data as $field => $input ) {

		// Define local var
		$valid = true;

		// Check field name
		switch ( $field ) {
			case 'ID' :
				// Record does not exist
				if ( ! fct_is_record( (int) $input ) ) {
					$valid = false;
				} else {
					$input = (int) $input;
				}
				break;
			case 'post_parent' :
			case 'account_id'  :
				// Account does not exist
				if ( ! fct_is_account( (int) $input ) ) {
					$valid = false;
				} else {
					$input = (int) $input;
				}
				break;
			case 'post_content' :
				$input = strip_tags( $input ); // Too strict?
				break;
			case 'period_id' :
				// Period does not exist
				if ( ! fct_is_period( (int) $input ) ) {
					$valid = false;
				} else {
					$input = (int) $input;
				}
				break;
			case 'record_date' :
				// Check date validity as Y-m-d
				$format = apply_filters( 'fct_record_date_input_format', _x( 'd-m-Y', 'date input field format', 'fiscaat' ) );
				$date = DateTime::createFromFormat( $format, $input );
				if ( ! $date || $date->format( $format ) != $input ) {
					$valid = false;
				} else {
					$input = $date->format( 'Y-m-d H:i:s' );
				}
				break;
			case 'record_type' :
				// Record type does not exist
				if ( ! in_array( $input, array_keys( fct_get_record_types() ) ) ) {
					$valid = false;
				}
				break;
			case 'amount' :
				// Format value to float
				$formatted = fct_float_format_from_string( $input );
				if ( ! is_numeric( $formatted ) || empty( $formatted ) ) {
					$valid = false;
				} else {
					$input = $formatted;
				}
				break;
			default :
				/**
				 * Sanitize the record input data
				 *
				 * Return a (empty) WP_Error instance to indicate that the provided
				 * input should be marked as invalid.
				 *
				 * @since 0.0.9
				 * 
				 * @param mixed $input The provided input value
				 * @param string $field The field name
				 * @param bool $is_edit Whether this is edit data
				 * @return mixed|WP_Error $input The sanitized data or an instance of WP_Error
				 *                                when the provided input is invalid
				 */
				$input = apply_filters( 'fct_sanitize_record_data', $input, $field, $is_edit );
				break;
		}

		// Input is marked invalid
		if ( ! $valid || is_wp_error( $input ) ) {
			// Register the field as invalid
			fct_add_error( $error_code_invalid, $field );

		// Use sanitized input data
		} else {
			$data[ $field ] = $input;
		}
	}

	return $data;	
}

/**
 * Return the required record fields
 *
 * @since 0.0.9
 *
 * @uses apply_filters() Calls 'fct_get_required_record_fields'
 * @param bool $is_edit Whether the fields are required for editing records
 * @return array Required record fields
 */
function fct_get_required_record_fields( $is_edit = false ) {

	// Enable custom required fields
	$fields = apply_filters( 'fct_get_required_record_fields', array(), $is_edit );

	// Define default required fields
	$fields = array_merge( $fields, array(
		'post_parent',
		'post_content',
		'account_id',
		'record_type',
		'amount'
	) );

	// Define required edit fields
	if ( $is_edit ) {
		$fields = array_merge( $fields, array(
			'ID',
			'period_id',
			'record_date'
		) );
	}

	return $fields;
}

/**
 * Return labels for the record's fields
 *
 * @since 0.0.9
 *
 * @uses apply_filters() Calls 'fct_get_record_field_labels'
 * @return array Record field labels as field => label
 */
function fct_get_record_field_labels() {

	// Define core post object labels
	$core_labels = array(
		'ID'                    => _x( 'ID',                     'Post object field label for `ID`',                    'fiscaat' ),
		'post_author'           => _x( 'Author',                 'Post object field label for `post_author`',           'fiscaat' ),
		'post_date'             => _x( 'Post date',              'Post object field label for `post_date`',             'fiscaat' ),
		'post_date_gmt'         => _x( 'Post date GMT',          'Post object field label for `post_date_gmt`',         'fiscaat' ),
		'post_content'          => _x( 'Description',            'Post object field label for `post_content`',          'fiscaat' ),
		'post_title'            => _x( 'Title',                  'Post object field label for `post_title`',            'fiscaat' ),
		'post_excerpt'          => _x( 'Short description',      'Post object field label for `post_excerpt`',          'fiscaat' ),
		'post_status'           => _x( 'Status',                 'Post object field label for `post_status`',           'fiscaat' ),
		'comment_status'        => _x( 'Comment status',         'Post object field label for `comment_status`',        'fiscaat' ),
		'ping_status'           => _x( 'Ping status',            'Post object field label for `ping_status`',           'fiscaat' ),
		'post_password'         => _x( 'Password',               'Post object field label for `post_password`',         'fiscaat' ),
		'post_name'             => _x( 'Slug',                   'Post object field label for `post_name`',             'fiscaat' ),
		'to_ping'               => _x( 'Ping count',             'Post object field label for `to_ping`',               'fiscaat' ),
		'pinged'                => _x( 'Pinged count',           'Post object field label for `pinged`',                'fiscaat' ),
		'post_modified'         => _x( 'Date last modified',     'Post object field label for `post_modified`',         'fiscaat' ),
		'post_modified_gmt'     => _x( 'Date last modified GMT', 'Post object field label for `post_modified_gmt`',     'fiscaat' ),
		'post_content_filtered' => _x( 'Description filtered',   'Post object field label for `post_content_filtered`', 'fiscaat' ),
		'post_parent'           => _x( 'Parent',                 'Post object field label for `post_parent`',           'fiscaat' ),
		'guid'                  => _x( 'GUID',                   'Post object field label for `guid`',                  'fiscaat' ),
		'menu_order'            => _x( 'Menu order',             'Post object field label for `menu_order`',            'fiscaat' ),
		'post_type'             => _x( 'Post type',              'Post object field label for `post_type`',             'fiscaat' ),
		'post_mime_type'        => _x( 'Post mime type',         'Post object field label for `post_mime_type`',        'fiscaat' ),
		'comment_count'         => _x( 'Comment count',          'Post object field label for `comment_count`',         'fiscaat' ),
	);

	// Define record meta labels
	$meta_labels = array(
		'period_id'      => _x( 'Period',         'Record meta field label for `period_id`',      'fiscaat' ),
		'account_id'     => _x( 'Account',        'Record meta field label for `account_id`',     'fiscaat' ),
		'record_date'    => _x( 'Date of origin', 'Record meta field label for `record_date`',    'fiscaat' ),
		'record_type'    => _x( 'Record type',    'Record meta field label for `record_type`',    'fiscaat' ),
		'amount'         => _x( 'Amount',         'Record meta field label for `amount`',         'fiscaat' ),
		'offset_account' => _x( 'Offset account', 'Record meta field label for `offset_account`', 'fiscaat' ),
	);

	$labels = array_merge( $core_labels, $meta_labels );

	return apply_filters( 'fct_get_record_field_labels', $labels );
}

/**
 * Modify the notices from bulk inserting records
 *
 * @uses WP_Error Fiscaat::errors::get_error_codes() To get the error codes
 * @uses WP_Error Fiscaat::errors::get_error_messages() To get the error
 *                                                       messages
 * @uses is_wp_error() To check if it's a {@link WP_Error}
 */
function fct_bulk_insert_records_notices() {

	// Bail if no notices or errors
	if ( ! fct_has_errors() )
		return;

	// Define local variable(s)	
	$labels   = fct_get_record_field_labels();
	$messages = array(
		'missing' => __( '<a href="%2$s">This record</a> is missing the following field(s): %1$s',             'fiscaat' ),
		'invalid' => __( '<a href="%2$s">This record</a> has invalid values for the following field(s): %1$s', 'fiscaat' )
	);

	// Get Fiscaat
	$fct = fiscaat();

	// Loop through notices
	foreach ( $fct->errors->get_error_codes() as $code ) {

		// Handle missing or invalid fields. Codes have a pattern of 'missing-{$record_id}'.
		if ( 0 === strpos( $code, 'missing' ) || 0 === strpos( $code, 'invalid' ) ) {

			// Split the message code
			$new_code  = substr( $code, 0, 7 );
			$record_id = substr( $code, 8 );
			
			// Skip when there was nothing found
			if ( false === $record_id )
				continue;

			// Collect the fields
			$fields  = $fct->errors->get_error_messages( $code );
			$_fields = array_intersect_key( $labels, array_flip( $fields ) );

			// Remove duplicates: Account
			if ( 2 === count( array_intersect( array( 'post_parent', 'account_id' ), $fields ) ) ) {
				unset( $_fields['post_parent'] );
			}
			// Remove duplicates: Amount
			if ( 2 === count( array_intersect( array( 'record_type', 'amount' ), $fields ) ) ) {
				unset( $_fields['record_type'] );
			}

			// Re-register the message with the new code
			fct_add_error( $new_code, sprintf( $messages[$new_code], '<code>' . implode( '</code> <code>', $_fields ) . '</code>', '#' ) );

			// Remove the original error code with messages
			$fct->errors->remove( $code );
		}
	}
}
