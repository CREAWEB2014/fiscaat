<?php

/**
 * Fiscaat User Capabilites
 *
 * Used to map user capabilities to WordPress's existing capabilities.
 *
 * @package Fiscaat
 * @subpackage Capabilities
 */

/**
 * Maps primary capabilities
 *
 * @param array $caps Capabilities for meta capability
 * @param string $cap Capability name
 * @param int $user_id User id
 * @param mixed $args Arguments
 * @uses apply_filters() Filter mapped results
 * @return array Actual capabilities for meta capability
 */
function fiscaat_map_primary_meta_caps( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {

	// What capability is being checked?
	switch ( $cap ) {
		case 'comment_fiscaat' :

			// Do not allow those who are not allowed
			if ( ! fiscaat_user_can_comment( $user_id ) ) {
				$caps = array( 'do_not_allow' );

			// Spectators can comment
			} else {
				$caps = array( 'fiscaat_spectate' );
			}

			break;
	}

	return apply_filters( 'fiscaat_map_primary_meta_caps', $caps, $cap, $user_id, $args );
}

/**
 * Return a user's main role
 *
 * @param int $user_id
 * @uses fiscaat_get_user_id() To get the user id
 * @uses get_userdata() To get the user data
 * @uses apply_filters() Calls 'fiscaat_set_user_role' with the role and user id
 * @return string
 */
function fiscaat_set_user_role( $user_id = 0, $new_role = '' ) {

	// Validate user id
	$user_id = fiscaat_get_user_id( $user_id, false, false );
	$user    = get_userdata( $user_id );

	// User exists
	if ( !empty( $user ) ) {

		// Get users year role
		$role = fiscaat_get_user_role( $user_id );

		// User already has this role so no new role is set
		if ( $new_role == $role ) {
			$new_role = false;

		// Users role is different than the new role
		} else {

			// Remove the old role
			if ( ! empty( $role ) ) {
				$user->remove_role( $role );
			}

			// Add the new role
			if ( !empty( $new_role ) ) {
				$user->add_role( $new_role );
			}
		}

	// User does not exist so return false
	} else {
		$new_role = false;
	}

	return apply_filters( 'fiscaat_set_user_role', $new_role, $user_id, $user );
}

/**
 * Return a user's main role
 *
 * @param int $user_id
 * @uses fiscaat_get_user_id() To get the user id
 * @uses get_userdata() To get the user data
 * @uses apply_filters() Calls 'fiscaat_get_user_role' with the role and user id
 * @return string
 */
function fiscaat_get_user_role( $user_id = 0 ) {

	// Validate user id
	$user_id = fiscaat_get_user_id( $user_id, false, false );
	$user    = get_userdata( $user_id );
	$role    = false;

	// User has roles so lets
	if ( ! empty( $user->roles ) ) {
		$roles = array_intersect( array_values( $user->roles ), array_keys( fiscaat_get_dynamic_roles() ) );

		// If there's a role in the array, use the first one
		if ( !empty( $roles ) ) {
			$role = array_shift( array_values( $roles ) );
		}
	}

	return apply_filters( 'fiscaat_get_user_role', $role, $user_id, $user );
}

/**
 * Helper function hooked to 'fiscaat_edit_user_profile_update' action to save or
 * update user roles and capabilities.
 *
 * @param int $user_id
 * @uses fiscaat_get_user_role() to get role
 * @uses fiscaat_set_user_role() to set role
 */
function fiscaat_profile_update_role( $user_id = 0 ) {

	// Bail if no user ID was passed
	if ( empty( $user_id ) )
		return;

	// Bail if no role
	if ( ! isset( $_POST['fiscaat-role'] ) )
		return;

	// Fiscaat role we want the user to have
	$new_role     = sanitize_text_field( $_POST['fiscaat-role'] );
	$fiscaat_role = fiscaat_get_user_role( $user_id );

	// Set the new Fiscaat role
	if ( $new_role != $fiscaat_role ) {
		fiscaat_set_user_role( $user_id, $new_role );
	}
}

