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
function fct_map_primary_meta_caps( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {

	// What capability is being checked?
	switch ( $cap ) {
		case 'fct_comment' :

			// Do not allow those who are not allowed
			if ( ! fct_user_can_comment( $user_id ) ) {
				$caps = array( 'do_not_allow' );

			// Spectators can comment
			} else {
				$caps = array( 'fct_spectate' );
			}

			break;
	}

	return apply_filters( 'fct_map_primary_meta_caps', $caps, $cap, $user_id, $args );
}

/**
 * Return a user's main role
 *
 * @param int $user_id
 * @uses fct_get_user_id() To get the user id
 * @uses get_userdata() To get the user data
 * @uses apply_filters() Calls 'fct_set_user_role' with the role and user id
 * @return string
 */
function fct_set_user_role( $user_id = 0, $new_role = '' ) {

	// Validate user id
	$user_id = fct_get_user_id( $user_id, false, false );
	$user    = get_userdata( $user_id );

	// User exists
	if ( !empty( $user ) ) {

		// Get users year role
		$role = fct_get_user_role( $user_id );

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

	return apply_filters( 'fct_set_user_role', $new_role, $user_id, $user );
}

/**
 * Return a user's forums role
 *
 * @since 0.0.1
 *
 * @param int $user_id
 * @uses fct_get_user_id() To get the user id
 * @uses get_userdata() To get the user data
 * @uses apply_filters() Calls 'fct_get_user_role' with the role and user id
 * @return string
 */
function fct_get_user_role( $user_id = 0 ) {

	// Validate user id
	$user_id = fct_get_user_id( $user_id );
	$user    = get_userdata( $user_id );
	$role    = false;

	// User has roles so look for a Fiscaat one
	if ( ! empty( $user->roles ) ) {

		// Look for a Fiscaat role
		$roles = array_intersect(
			array_values( $user->roles ),
			array_keys( fct_get_dynamic_roles() )
		);

		// If there's a role in the array, use the first one. This isn't very
		// smart, but since roles aren't exactly hierarchical, and Fiscaat
		// does not yet have a UI for multiple user roles, it's fine for now.
		if ( !empty( $roles ) ) {
			$role = array_shift( $roles );
		}
	}

	return apply_filters( 'fct_get_user_role', $role, $user_id, $user );
}

/**
 * Return a user's blog role
 *
 * @since 0.0.6
 *
 * @param int $user_id
 * @uses fct_get_user_id() To get the user id
 * @uses get_userdata() To get the user data
 * @uses apply_filters() Calls 'fct_get_user_blog_role' with the role and user id
 * @return string
 */
function fct_get_user_blog_role( $user_id = 0 ) {

	// Add Fiscaat roles (returns $wp_roles global)
	fct_add_fiscaat_roles();

	// Validate user id
	$user_id = fct_get_user_id( $user_id );
	$user    = get_userdata( $user_id );
	$role    = false;

	// User has roles so lets
	if ( ! empty( $user->roles ) ) {

		// Look for a non Fiscaat role
		$roles     = array_intersect(
			array_values( $user->roles ),
			array_keys( fct_get_blog_roles() )
		);

		// If there's a role in the array, use the first one. This isn't very
		// smart, but since roles aren't exactly hierarchical, and WordPress
		// does not yet have a UI for multiple user roles, it's fine for now.
		if ( !empty( $roles ) ) {
			$role = array_shift( $roles );
		}
	}

	return apply_filters( 'fct_get_user_blog_role', $role, $user_id, $user );
}

/**
 * Helper function hooked to 'fct_edit_user_profile_update' action to save or
 * update user roles and capabilities.
 *
 * @param int $user_id
 * @uses fct_get_user_role() to get role
 * @uses fct_set_user_role() to set role
 */
function fct_profile_update_role( $user_id = 0 ) {

	// Bail if no user ID was passed
	if ( empty( $user_id ) )
		return;

	// Bail if no role
	if ( ! isset( $_POST['fiscaat-role'] ) )
		return;

	// Fiscaat role we want the user to have
	$new_role = sanitize_text_field( $_POST['fiscaat-role'] );
	$fct_role = fct_get_user_role( $user_id );

	// Set the new Fiscaat role
	if ( $new_role != $fct_role ) {
		fct_set_user_role( $user_id, $new_role );
	}
}
