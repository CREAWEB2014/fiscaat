<?php

/**
 * Fiscaat User Functions
 *
 * @package Fiscaat
 * @subpackage Functions
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** User Updaters *************************************************************/

/**
 * Helper function hooked to 'fct_edit_user_profile_update' action to save or
 * update user to the list of global spectators.
 *
 * @param int $user_id
 * @uses fct_get_user_id() 
 * @uses fct_get_global_spectators()
 * @uses update_option()
 */
function fct_profile_update_global_spectator( $user_id = 0 ) {

	// Bail if no user ID was passed
	if ( empty( $user_id ) )
		return;

	// Bail if no data
	if ( ! isset( $_POST['fct-global-spectator-nonce'] ) )
		return;

	// Get user data
	$add_user = isset( $_POST['fct-global-spectator'] );

	// Validate data
	$user_id  = fct_get_user_id( $user_id );
	$old_spec = fct_get_global_spectators();
	$new_spec = false;

	// Add spectator
	if ( $add_user && ! in_array( $user_id, $old_spec ) ){
		$new_spec = array_unique( array_merge( $old_spec, array( $user_id ) ) );

	// Remove spectator
	} elseif ( ! $add_user && in_array( $user_id, $old_spec ) ){
		$new_spec = array_diff( $old_spec, array( $user_id ) );
	}

	if ( false != $new_spec )
		update_option( '_fct_global_spectators', $new_spec );
}

/**
 * Helper function hooked to 'fct_edit_user_profile_update' action to save or
 * update user to the list of blocked commenters.
 *
 * @param int $user_id
 * @uses fct_get_user_id()
 * @uses fct_get_blocked_commenters()
 * @uses update_option()
 */
function fct_profile_update_block_commenter( $user_id = 0 ) {

	// Bail if no user ID was passed
	if ( empty( $user_id ) )
		return;

	// Bail if no data
	if ( ! isset( $_POST['fct-block-commenter-nonce'] ) )
		return;

	// Get user data
	$add_user = isset( $_POST['fct-block-commenter'] );

	// Validate data
	$user_id  = fct_get_user_id( $user_id );
	$blocked  = fct_get_blocked_commenters();
	$to_block = false;

	// Add spectator
	if ( $add_user && ! in_array( $user_id, $blocked ) ){
		$to_block = array_unique( array_merge( $blocked, array( $user_id ) ) );

	// Remove spectator
	} elseif ( ! $add_user && in_array( $user_id, $blocked ) ){
		$to_block = array_diff( $blocked, array( $user_id ) );
	}

	if ( false != $to_block )
		update_option( '_fct_blocked_commenters', $to_block );

}

/** Spectators ****************************************************************/

/**
 * Check if a user is assigned to read given account
 * 
 * @param int $user_id User id
 * @param int $account_id Account id
 * @uses fct_is_record()
 * @uses fct_get_record_id()
 * @uses fct_get_record_account_id()
 * @uses fct_get_account_id()
 * @uses fct_get_user_id() To get the current user if none given
 * @uses fct_get_account_spectators()
 * @return boolean
 */
function fct_user_in_account_spectators( $account_id = 0, $user_id = 0 ) {

	// Validate account
	if ( fct_is_record( $account_id ) ){
		$record_id  = fct_get_record_id( $account_id );
		$account_id = fct_get_record_account_id( $record_id );
	} else {
		$account_id = fct_get_account_id( $account_id );
	}

	// Validate user
	$user_id = fct_get_user_id( $user_id, false, true );

	// Find a match
	$users = fct_get_account_spectators( $account_id );
	$match = in_array( $user_id, $users );

	return (bool) apply_filters( 'fct_user_in_account_spectators', $match, $user_id, $account_id );
}

/**
 * Return the global spectator ids
 *
 * @uses get_option()
 * @uses apply_filters()
 * @return array Spectator ids
 */
function fct_get_global_spectators() {
	$user_ids = get_option( '_fct_global_spectators', array() );
	return (array) apply_filters( 'fct_get_global_spectators', $user_ids );
}

/**
 * Check if user is a global spectator
 * 
 * @param itn $user_id Optional. User id
 * @uses fct_get_user_id()
 * @uses fct_get_global_spectators()
 * @uses apply_filters() Calls 'fct_user_is_global_spectator' with
 *                        match and user id
 * @return bool User is global spectator
 */
function fct_user_is_global_spectator( $user_id = 0 ) {
	$user_id = fct_get_user_id( $user_id, false, true );
	$match   = in_array( $user_id, fct_get_global_spectators() );

	return (bool) apply_filters( 'fct_user_is_global_spectator', $match, $user_id );
}

/**
 * Returns whether a user can spectate the given Fiscaat page
 *
 * Ignores 'fiscaat' or 'control' capabilities
 * 
 * @param int $post_id Post id
 * @param int $user_id Optional. User id. Defaults to current user
 * @uses fct_get_user_id()
 * @uses fct_is_year()
 * @uses fct_user_is_global_spectator()
 * @uses fct_is_account()
 * @uses fct_is_record()
 * @uses fct_user_in_account_spectators()
 * @uses apply_filters() Calls 'fct_user_can_specate' with user
 *                        can spectate, post id, and user id
 * @return bool User can spectate
 */
function fct_user_can_spectate( $post_id = 0, $user_id = 0 ) {

	// Validate user
	$user_id = fct_get_user_id( $user_id, false, true );

	// Assume not
	$can_spectate = false;

	// Check post type
	switch ( $post_id ) {

		// Year
		case fct_is_year( $post_id ) :
			$can_spectate = fct_user_is_global_spectator( $user_id );
			break;

		// Account & Record
		case fct_is_account( $post_id ) :
		case fct_is_record( $post_id )  :
			$can_spectate = fct_user_in_account_spectators( $post_id, $user_id );
			
			// Global fallback
			if ( ! $can_spectate )
				$can_spectate = fct_user_is_global_spectator( $user_id );
			break;
	}

	// Require user is capable
	$can_spectate = $can_spectate && user_can( $user_id, 'fct_spectate' );

	return (bool) apply_filters( 'fct_user_can_spectate', $can_spectate, $post_id, $user_id );
}

/** Comments ******************************************************************/

/**
 * Return the blocked commenter ids
 *
 * @uses get_option()
 * @uses apply_filters()
 * @return array Spectator ids
 */
function fct_get_blocked_commenters() {
	$user_ids = get_option( '_fct_blocked_commenters', array() );
	return (array) apply_filters( 'fct_get_blocked_commenters', $user_ids );
}

/**
 * Check if a user can comment in Fiscaat
 * 
 * @param int $user_id Optional. User id
 * @uses fct_get_user_id()
 * @uses get_option()
 * @uses apply_filters() Calls 'fct_user_can_comment' with user
 *                        can comment and user id
 * @return bool User can comment
 */
function fct_user_can_comment( $user_id = 0 ) {
	$user_id = fct_get_user_id( $user_id, false, true );

	// Get blocked commenters
	$blocked = fct_get_blocked_commenters();
	$match   = ! in_array( $user_id, $blocked );

	return (bool) apply_filters( 'fct_user_can_comment', $match, $user_id );
}

/** User Counts ***************************************************************/

/**
 * Get the total number of fisci on Fiscaat
 *
 * @uses wp_cache_get() Check if query is in cache
 * @uses get_users() To execute our query and get the var back
 * @uses wp_cache_set() Set the query in the cache
 * @uses apply_filters() Calls 'fct_get_total_users' with number of fisci
 * @return int Total number of fisci
 */
function fct_get_total_fisci() {
	$user_count = count_users();
	$role       = fct_get_fiscus_role();

	// Check for Fisci
	if ( ! isset( $user_count['avail_roles'][$role] ) )
		return 0;

	return apply_filters( 'fct_get_total_fisci', (int) $user_count['avail_roles'][$role] );
}

/**
 * Get the total number of spectators on Fiscaat
 *
 * @uses wp_cache_get() Check if query is in cache
 * @uses get_users() To execute our query and get the var back
 * @uses wp_cache_set() Set the query in the cache
 * @uses apply_filters() Calls 'fct_get_total_users' with number of spectators
 * @return int Total number of spectators
 */
function fct_get_total_spectators() {
	$user_count = count_users();
	$role       = fct_get_spectator_role();

	// Check for Spectators
	if ( ! isset( $user_count['avail_roles'][$role] ) )
		return 0;

	return apply_filters( 'fct_get_total_spectators', (int) $user_count['avail_roles'][$role] );
}

/** Admin Bar Menu ************************************************************/

/**
 * Setup Fiscaat admin bar menu for all spectators on front-end
 * 
 * @uses WP_Admin_Bar::remove_node() To remove default nodes
 * @uses current_user_can() To check if user can see menu
 * @uses fct_get_year_post_type()
 * @uses fct_get_account_post_type()
 * @uses fct_get_record_post_type()
 * @uses apply_filters() Calls 'fct_admin_bar_menu' with the nodes
 * @uses WP_Admin_Bar::add_node() To add new admin bar menu items
 * @param WP_Admin_Bar $wp_admin_bar
 */
function fct_admin_bar_menu( $wp_admin_bar ) {

	// Remove any New Fiscaat post type nodes
	foreach ( array( fct_get_year_post_type(), fct_get_account_post_type(), fct_get_record_post_type() ) as $post_type ) {
		$wp_admin_bar->remove_node( 'new-' . $post_type );
	}

	// Bail if not on the front-end or user is not capable
	if ( is_admin() || ! current_user_can( 'fct_spectate' ) )
		return;

	// Setup nodes as id => other attrs
	$nodes = array( 
		
		// Top level menu
		'fiscaat' => array(
			'title'  => _x('Fiscaat', 'Admin bar menu title', 'fiscaat'),
			'href'   => add_query_arg( array( 'post_type' => fct_get_year_post_type() ), admin_url( 'edit.php' ) ),
			'meta'   => array()
		),

		// General Ledger
		'fct-general-ledger' => array(
			'parent' => 'fiscaat',
			'title'  =>  __('General Ledger', 'fiscaat'),
			'href'   => add_query_arg( array( 'post_type' => fct_get_account_post_type() ), admin_url( 'edit.php' ) ),
			'meta'   => array()
		)
	);

	// New records node
	if ( current_user_can( 'create_records' ) ) {
		$nodes['fct-add-records'] = array(
			'parent' => 'fiscaat',
			'title'  => __('Manage Records', 'fiscaat'),
			'href'   => add_query_arg( array( 'post_type' => fct_get_record_post_type() ), admin_url( 'post-new.php' ) ),
			'meta'   => array()
		);
	}

	// New account node
	if ( current_user_can( 'create_accounts' ) ) {
		$nodes['fct-add-account'] = array(
			'parent' => 'fiscaat',
			'title'  => __('Add Account', 'fiscaat'),
			'href'   => add_query_arg( array( 'post_type' => fct_get_account_post_type() ), admin_url( 'post-new.php' ) ),
			'meta'   => array()
		);
	}

	// Tools page
	if ( current_user_can( 'fct_tools_page' ) ) {
		$nodes['fct-tools'] = array(
			'parent' => 'fiscaat',
			'title'  => __('Tools', 'fiscaat'),
			'href'   => add_query_arg( array( 'page' => 'fct-repair' ), admin_url( 'tools.php' ) ),
			'meta'   => array()
		);
	}

	// Settings page
	if ( current_user_can( 'fct_settings_page' ) ) {
		$nodes['fct-settings'] = array(
			'parent' => 'fiscaat',
			'title'  => __('Settings', 'fiscaat'),
			'href'   => add_query_arg( array( 'page' => 'fct-settings' ), admin_url() ),
			'meta'   => array()
		);
	}

	// Create admin bar menu
	foreach ( apply_filters( 'fct_admin_bar_menu', $nodes ) as $node_id => $args ) {

		// Don't do empty nodes
		if ( ! empty( $args ) && ! empty( $args['title'] ) ) {

			// Add node
			$wp_admin_bar->add_node( array_merge( array( 'id' => $node_id ), (array) $args ) );
		}
	}
}

/**
 * Output styles for Fiscaat's admin bar menu item
 *
 * @since 0.0.6
 */
function fct_admin_bar_menu_style() { ?> 
<style type="text/css" media="screen">
	#wpadminbar #wp-admin-bar-fiscaat > .ab-item:before { content: '\f178'; top: 2px; }
</style>
<?php
}
