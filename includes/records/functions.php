<?php

/**
 * Fiscaat Record Functions
 *
 * @package Fiscaat
 * @subpackage Functions
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/** Meta **********************************************************************/

/**
 * Return default record meta keys with values
 * 
 * @param int $record_id
 * @return array
 */
function fct_get_record_default_meta(){
	return apply_filters( 'fct_get_record_default_meta', array(
		'year_id'        => fct_get_current_year_id(), // Year
		'account_id'     => 0,                             // Account
		'offset_account' => 0,                             // Account received from or send to
		'value'          => 0,                             // Value
		'value_type'     => '',                            // 'debit', 'credit'
	) );
}

/**
 * Return stored meta value for given record
 *
 * @uses get_post_meta()
 * 
 * @param string $meta_key Meta key
 * @param int $record_id Year id
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
 * @since Fiscaat (r3349)
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
	$default_record = array(
		'post_parent'    => 0, // Account ID
		'post_status'    => fct_get_public_status_id(),
		'post_type'      => fct_get_record_post_type(),
		'post_author'    => fct_get_current_user_id(),
		'post_password'  => '',
		'post_content'   => '',
		'post_title'     => '',
		'menu_order'     => 0,
		'comment_status' => 'open' // @todo Fix comment system
	);
	$record_data = fct_parse_args( $record_data, $default_record, 'insert_record' );

	// Insert record
	$record_id   = wp_insert_post( $record_data );

	// Bail if no record was added
	if ( empty( $record_id ) )
		return false;

	// Record meta
	$record_meta = fct_parse_args( $record_meta, fct_get_record_default_meta(), 'insert_record_meta' );

	// Insert record meta
	foreach ( $record_meta as $meta_key => $meta_value )
		fct_update_record_meta( $record_id, $meta_key, $meta_value );

	// Update the account
	$account_id = fct_get_record_account_id( $record_id );
	if ( !empty( $account_id ) )
		fct_update_account( array( 'account_id' => $account_id, 'is_edit' => false ) );

	// Return new record ID
	return $record_id;
}

/** Record Updaters ************************************************************/

/**
 * Update the record with its year id it is in
 *
 * @since Fiscaat (r2855)
 *
 * @param int $record_id Optional. Record id to update
 * @param int $year_id Optional. Year id
 * @uses fct_get_record_id() To get the record id
 * @uses fct_get_year_id() To get the year id
 * @uses get_post_ancestors() To get the record's year
 * @uses get_post_field() To get the post type of the post
 * @uses update_post_meta() To update the record year id meta
 * @uses apply_filters() Calls 'fct_update_record_year_id' with the year id
 *                        and record id
 * @return bool Record's year id
 */
function fct_update_record_year_id( $record_id = 0, $year_id = 0 ) {

	// Validation
	$record_id = fct_get_record_id( $record_id );
	$year_id   = fct_get_year_id( $year_id );

	// If no year_id was passed, walk up ancestors and look for year type
	if ( empty( $year_id ) ) {

		// Get ancestors
		$ancestors = (array) get_post_ancestors( $record_id );

		// Loop through ancestors
		if ( !empty( $ancestors ) ) {
			foreach ( $ancestors as $ancestor ) {

				// Get first parent that is a year
				if ( get_post_field( 'post_type', $ancestor ) == fct_get_year_post_type() ) {
					$year_id = $ancestor;

					// Found a year, so exit the loop and continue
					continue;
				}
			}
		}
	}

	// Update the year ID
	fct_update_record_meta( $record_id, 'year_id', $year_id );

	return apply_filters( 'fct_update_record_year_id', (int) $year_id, $record_id );
}

/**
 * Update the record with its account id it is in
 *
 * @since Fiscaat (r2855)
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

	// Validation
	$record_id  = fct_get_record_id( $record_id );
	$account_id = fct_get_account_id( $account_id );

	// If no account_id was passed, walk up ancestors and look for account type
	if ( empty( $account_id ) ) {

		// Get ancestors
		$ancestors = (array) get_post_ancestors( $record_id );

		// Loop through ancestors
		if ( !empty( $ancestors ) ) {
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

	// Validation
	$record_id  = fct_get_record_id( $record_id );

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
 * Adjust the value of a record
 *
 * NOTE: A record can never have it's value adjusted! This function
 * therefor always returns false.
 *
 * @param int $record_id Optional. Record id
 * @param int $value Optional. Record value
 * @return boolean False
 */
function fct_update_record_value( $record_id = 0, $value = 0 ) {
	$record_id = fct_get_record_id( $record_id );
	
	fct_update_record_meta( $record_id, 'value', (float) $value );

	return apply_filters( 'fct_update_record_value', (float) $value, $record_id );
}

/**
 * Adjust the value type of a record
 *
 * NOTE: A record can never have it's value type adjusted! This function
 * therefor always returns false.
 *
 * @param int $record_id Optional. Record id
 * @param int $value_type Optional. Record value type
 * @return boolean False
 */
function fct_update_record_value_type( $record_id = 0, $value_type = '' ) {
	$record_id = fct_get_record_id( $record_id );

	// Bail if no valid param
	if ( ! in_array( $value_type, array( fct_get_debit_record_type(), fct_get_credit_record_type() ) ) )
		return false;

	fct_update_record_meta( $record_id, 'value_type', $value_type );

	return apply_filters( 'fct_update_record_value_type', $value_type, $record_id );
}

/**
 * Handle all the extra meta stuff from posting a new record or editing a record
 *
 * @param int $record_id Optional. Record id
 * @param int $account_id Optional. Topic id
 * @param int $year_id Optional. Forum id
 * @param bool|array $anonymous_data Optional. If it is an array, it is
 *                    extracted and anonymous user info is saved
 * @param int $author_id Author id
 * @param bool $is_edit Optional. Is the post being edited? Defaults to false.
 * @uses fct_get_record_id() To get the record id
 * @uses fct_get_account_id() To get the account id
 * @uses fct_get_year_id() To get the year id
 * @uses fct_get_current_user_id() To get the current user id
 * @uses fct_get_record_account_id() To get the record account id
 * @uses fct_get_account_year_id() To get the account year id
 * @uses update_post_meta() To update the record metas
 * @uses set_transient() To update the flood check transient for the ip
 * @uses fct_update_user_last_posted() To update the users last posted time
 * @uses fct_is_subscriptions_active() To check if the subscriptions feature is
 *                                      activated or not
 * @uses fct_is_user_subscribed() To check if the user is subscribed
 * @uses fct_remove_user_subscription() To remove the user's subscription
 * @uses fct_add_user_subscription() To add the user's subscription
 * @uses fct_update_record_year_id() To update the record year id
 * @uses fct_update_record_account_id() To update the record account id
 */
function fct_update_record( $args = '' ) {
	$defaults = array(
		'record_id'      => 0,
		'account_id'     => 0,
		'year_id'        => 0,
		'offset_account' => 0,
		'value_type'     => 0,
		'value'          => 0,
		'is_edit'        => false
	);
	$r = fct_parse_args( $args, $defaults, 'update_record' );
	extract( $r );	

	// Validate the ID's passed from 'fct_new_record' action
	$record_id  = fct_get_record_id( $record_id );
	$account_id = fct_get_account_id( $account_id );
	$year_id    = fct_get_year_id( $year_id );

	// Bail if there is no record
	if ( empty( $record_id ) )
		return;

	// Check account_id
	if ( empty( $account_id ) )
		$account_id = fct_get_record_account_id( $record_id );

	// Check year_id
	if ( !empty( $account_id ) && empty( $year_id ) )
		$year_id = fct_get_account_year_id( $account_id );

	// Record meta relating to record position in tree
	fct_update_record_year_id   ( $record_id, $year_id    );
	fct_update_record_account_id( $record_id, $account_id );

	// Update offset account
	fct_update_record_offset_account     ( $record_id, $offset_account      );

	// Value & Type. Will return false, because once created, never editable
	fct_update_record_value     ( $record_id, $value      );
	fct_update_record_value_type( $record_id, $value_type );

	// Update associated account values if this is a new record
	if ( empty( $is_edit ) ) {

		// Update parent account
		fct_update_account( array( 'account_id' => $account_id, 'year_id' => $year_id ) );
	}
}


/** Record Actions *************************************************************/

/**
 * Closes a record
 *
 * @param int $record_id Record id
 * @uses get_post() To get the record
 * @uses do_action() Calls 'fct_close_record' with the record ID
 * @uses get_post_meta() To get the previous status meta
 * @uses delete_post_meta() To delete the previous status meta
 * @uses wp_insert_post() To insert the updated post
 * @uses do_action() Calls 'fct_closed_record' with the record ID
 * @return mixed False or {@link WP_Error} on failure, record id on success
 */
function fct_close_record( $record_id = 0 ) {

	// Get record
	$record = get_post( $record_id, ARRAY_A );
	if ( empty( $record ) )
		return $record;

	// Bail if already closed
	if ( fct_get_closed_status_id() == $record['post_status'] )
		return false;

	// Bail if unapproved
	if ( fct_get_approved_status_id() != $record['post_status'] )
		return false;

	// Execute pre close code
	do_action( 'fct_close_record', $record_id );

	// Set post status to closed
	$record['post_status'] = fct_get_closed_status_id();

	// No revisions
	remove_action( 'pre_post_update', 'wp_save_post_revision' );

	// Update the record
	$record_id = wp_insert_post( $record );

	// Execute post close code
	do_action( 'fct_closed_record', $record_id );

	// Return record_id
	return $record_id;
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
