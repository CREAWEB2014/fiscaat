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
				'create_records'         => true,
				'publish_records'        => true,
				'edit_records'           => true,
				'edit_others_records'    => true,
				'delete_records'         => true,
				'delete_others_records'  => true,

				// Account caps
				'create_accounts'        => true,
				'publish_accounts'       => true,
				'edit_accounts'          => true,
				'edit_others_accounts'   => true,
				'delete_accounts'        => true,
				'delete_others_accounts' => true,

				// Period caps
				'create_periods'         => true,
				'publish_periods'        => true,
				'edit_periods'           => true,
				'edit_others_periods'    => true,
				'delete_periods'         => true,
				'delete_others_periods'  => true,
			);

			break;

		// Spectator
		case fct_get_spectator_role() :
			$caps = array(

				// Primary caps
				'fct_spectate'           => true,
			);

			break;

		// Default
		default :
			$caps = array();

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

/**
 * Get the available roles minus Fiscaat's dynamic roles
 *
 * @since 0.0.6
 *
 * @uses fct_get_wp_roles() To load and get the $wp_roles global
 * @return array
 */
function fct_get_blog_roles() {

	// Get WordPress's roles (returns $wp_roles global)
	$wp_roles  = fct_get_wp_roles();

	// Apply the WordPress 'editable_roles' filter to let plugins ride along.
	//
	// We use this internally via fct_filter_blog_editable_roles() to remove
	// any custom Fiscaat roles that are added to the global.
	$the_roles = isset( $wp_roles->roles ) ? $wp_roles->roles : false;
	$all_roles = apply_filters( 'editable_roles', $the_roles );

	return apply_filters( 'fct_get_blog_roles', $all_roles, $wp_roles );
}

/** Fiscaat Roles ************************************************************/

/**
 * Add the Fiscaat roles to the $wp_roles global.
 *
 * We do this to avoid adding these values to the database.
 */
function fct_add_fiscaat_roles() {
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
 * Gets a translated role name from a role ID
 *
 * @since 0.0.6
 *
 * @param string $role_id
 * @return string Translated role name
 */
function fct_get_dynamic_role_name( $role_id = '' ) {
	$roles = fct_get_dynamic_roles();
	$role  = isset( $roles[$role_id] ) ? $roles[$role_id]['name'] : '';

	return apply_filters( 'fct_get_dynamic_role_name', $role, $role_id, $roles );
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
 * The spectator role for registered user that can view financial data
 *
 * @uses apply_filters() Allow override of hardcoded spectator role
 * @return string
 */
function fct_get_spectator_role() {
	return apply_filters( 'fct_get_spectator_role', 'fct_spectator' );
}
