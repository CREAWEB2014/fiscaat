<?php

/**
 * Fiscaat Capabilites
 *
 * The functions in this file are used primarily as convenient wrappers for
 * capability output in user profiles. This includes mapping capabilities and
 * groups to human readable strings,
 *
 * @package Fiscaat
 * @subpackage Capabilities
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** Mapping *******************************************************************/

/**
 * Returns an array of capabilities based on the role that is being requested.
 *
 *
 * @todo Map all of these and deprecate
 *
 * @param string $role Optional. Defaults to The role to load caps for
 * @uses apply_filters() Allow return value to be filtered
 *
 * @return array Capabilities for $role
 */
function fct_get_caps_for_role( $role = '' ) {

	// Which role are we looking for?
	switch ( $role ) {

		// Fiscus
		case fct_get_fiscus_role() :
			$caps = array(

				// Fisci only
				'fiscaat'                => true,

				// Primary caps
				'fct_spectate'           => true,

				// Record caps
				'publish_records'        => true,
				'edit_records'           => true,
				'edit_others_records'    => true,
				'delete_records'         => true,
				'delete_others_records'  => true,
				'read_private_records'   => true,

				// Account caps
				'manage_accounts'        => true,
				'publish_accounts'       => true,
				'edit_accounts'          => true,
				'edit_others_accounts'   => true,
				'delete_accounts'        => true,
				'delete_others_accounts' => true,
				'read_private_accounts'  => true,

				// Year caps
				'publish_years'          => true,
				'edit_years'             => true,
				'edit_others_years'      => true,
				'delete_years'           => true,
				'delete_others_years'    => true,
				'read_private_years'     => true
			);

			break;

		// Controller
		case fct_get_spectator_role() :
			$caps = array(

				// Primary caps
				'fct_spectate'           => true,

				// Record caps
				'publish_records'        => false,
				'edit_records'           => false,
				'edit_others_records'    => false,
				'delete_records'         => false,
				'delete_others_records'  => false,
				'read_private_records'   => false,

				// Account caps
				'publish_accounts'       => false,
				'edit_accounts'          => false,
				'edit_others_accounts'   => false,
				'delete_accounts'        => false,
				'delete_others_accounts' => false,
				'read_private_accounts'  => false,

				// Year caps
				'publish_years'          => false,
				'edit_years'             => false,
				'edit_others_years'      => false,
				'delete_years'           => false,
				'delete_others_years'    => false,
				'read_private_years'     => false
			);

			break;

		// Default
		default :
			$caps = array(

				// Primary caps
				'fct_spectate'           => false,

				// Record caps
				'publish_records'        => false,
				'edit_records'           => false,
				'edit_others_records'    => false,
				'delete_records'         => false,
				'delete_others_records'  => false,
				'read_private_records'   => false,

				// Account caps
				'publish_accounts'       => false,
				'edit_accounts'          => false,
				'edit_others_accounts'   => false,
				'delete_accounts'        => false,
				'delete_others_accounts' => false,
				'read_private_accounts'  => false,

				// Year caps
				'publish_years'          => false,
				'edit_years'             => false,
				'edit_others_years'      => false,
				'delete_years'           => false,
				'delete_others_years'    => false,
				'read_private_years'     => false
			);

			break;
	}

	return apply_filters( 'fct_get_caps_for_role', $caps, $role );
}

/**
 * Adds capabilities to WordPress user roles.
 */
function fct_add_caps() {

	// Loop through available roles and add caps
	foreach( fct_get_wp_roles()->role_objects as $role ) {
		foreach ( fct_get_caps_for_role( $role->name ) as $cap => $value ) {
			$role->add_cap( $cap, $value );
		}
	}

	do_action( 'fct_add_caps' );
}

/**
 * Removes capabilities from WordPress user roles.
 */
function fct_remove_caps() {

	// Loop through available roles and remove caps
	foreach( fct_get_wp_roles()->role_objects as $role ) {
		foreach ( array_keys( fct_get_caps_for_role( $role->name ) ) as $cap ) {
			$role->remove_cap( $cap );
		}
	}

	do_action( 'fct_remove_caps' );
}

/**
 * Get the $wp_roles global without needing to declare it everywhere
 *
 * @global WP_Roles $wp_roles
 * @return WP_Roles
 */
function fct_get_wp_roles() {
	global $wp_roles;

	// Load roles if not set
	if ( ! isset( $wp_roles ) )
		$wp_roles = new WP_Roles();

	return $wp_roles;
}

/** Year Roles ***************************************************************/

/**
 * Add the Fiscaat roles to the $wp_roles global.
 *
 * We do this to avoid adding these values to the database.
 */
function fct_add_roles() {
	$wp_roles = fct_get_wp_roles();

	foreach( fct_get_dynamic_roles() as $role_id => $details ) {
		$wp_roles->roles[$role_id]        = $details;
		$wp_roles->role_objects[$role_id] = new WP_Role( $details['name'], $details['capabilities'] );
		$wp_roles->role_names[$role_id]   = $details['name'];
	}
}

/**
 * Helper function to add filter to option_wp_user_roles
 *
 * @see _fct_reinit_dynamic_roles()
 *
 * @global WPDB $wpdb Used to get the database prefix
 */
function fct_filter_user_roles_option() {
	global $wpdb;

	$role_key = $wpdb->prefix . 'user_roles';

	add_filter( 'option_' . $role_key, '_fct_reinit_dynamic_roles' );
}

/**
 * This is necessary because in a few places (noted below) WordPress initializes
 * a blog's roles directly from the database option. When this happens, the
 * $wp_roles global gets flushed, causing a user to magically lose any
 * dynamically assigned roles or capabilities when $current_user in refreshed.
 *
 * Because dynamic multiple roles is a new concept in WordPress, we work around
 * it here for now, knowing that improvements will come to WordPress core later.
 *
 * @see switch_to_blog()
 * @see restore_current_blog()
 * @see WP_Roles::_init()
 *
 * @internal Used by Fiscaat to reinitialize dynamic roles on blog switch
 *
 * @param array $roles
 * @return array Combined array of database roles and dynamic Fiscaat roles
 */
function _fct_reinit_dynamic_roles( $roles = array() ) {
	foreach( fct_get_dynamic_roles() as $role_id => $details ) {
		$roles[$role_id] = $details;
	}
		return $roles;
}

/**
 * Fetch a filtered list of Fiscaat roles that the current user is
 * allowed to have.
 *
 * Simple function who's main purpose is to allow filtering of the
 * list of Fiscaat roles so that plugins can remove inappropriate ones depending
 * on the situation or user making edits.
 *
 * Specifically because without filtering, anyone with the edit_users
 * capability can edit others to be administrators, even if they are
 * only editors or authors. This filter allows admins to delegate
 * user management.
 *
 * @return array
 */
function fct_get_dynamic_roles() {
	return (array) apply_filters( 'fct_get_dynamic_roles', array(

		// Fiscus
		fct_get_fiscus_role() => array(
			'name'         => __( 'Fiscus', 'fiscaat' ),
			'capabilities' => fct_get_caps_for_role( fct_get_fiscus_role() )
		),

		// Spectator
		fct_get_spectator_role() => array(
			'name'         => __( 'Spectator', 'fiscaat' ),
			'capabilities' => fct_get_caps_for_role( fct_get_spectator_role() )
		)

	) );
}

/**
 * Removes the Fiscaat roles from the editable roles array
 *
 * This used to use array_diff_assoc() but it randomly broke before 2.2 release.
 * Need to research what happened, and if there's a way to speed this up.
 *
 * @param array $all_roles All registered roles
 * @return array 
 */
function fct_filter_blog_editable_roles( $all_roles = array() ) {

	// Loop through Fiscaat roles
	foreach ( array_keys( fct_get_dynamic_roles() ) as $fct_role ) {

		// Loop through WordPress roles
		foreach ( array_keys( $all_roles ) as $wp_role ) {

			// If keys match, unset
			if ( $wp_role == $fct_role ) {
				unset( $all_roles[$wp_role] );
			}
		}
	}

	return $all_roles;
}

/**
 * The fiscus role for Fiscaat users
 *
 * @uses apply_filters() Allow override of hardcoded fiscus role
 * @return string
 */
function fct_get_fiscus_role() {
	return apply_filters( 'fct_get_fiscus_role', 'fct_fiscus' );
}

/**
 * The spectator role for registered user that can view financial accounts
 *
 * @uses apply_filters() Allow override of hardcoded spectator role
 * @return string
 */
function fct_get_spectator_role() {
	return apply_filters( 'fct_get_spectator_role', 'fct_spectator' );
}