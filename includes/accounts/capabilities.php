<?php

/**
 * Fiscaat Account Capabilites
 *
 * Used to map account capabilities to WordPress's existing capabilities.
 *
 * @package Fiscaat
 * @subpackage Capabilities
 */

/**
 * Return account capabilities
 *
 * @uses apply_filters() Calls 'fct_get_account_caps' with the capabilities
 * @return array Account capabilities
 */
function fct_get_account_caps() {
	return apply_filters( 'fct_get_account_caps', array (
		'create_posts'        => 'create_accounts',
		'edit_posts'          => 'edit_accounts',
		'edit_others_posts'   => 'edit_others_accounts',
		'publish_posts'       => 'publish_accounts',
		'read_private_posts'  => 'read_private_accounts',
		'delete_posts'        => 'delete_accounts',
		'delete_others_posts' => 'delete_others_accounts'
	) );
}

/**
 * Maps account capabilities
 *
 * @param array $caps Capabilities for meta capability
 * @param string $cap Capability name
 * @param int $user_id User id
 * @param mixed $args Arguments
 * @uses get_post() To get the post
 * @uses get_post_type_object() To get the post type object
 * @uses apply_filters() Filter capability map results
 * @return array Actual capabilities for meta capability
 */
function fct_map_account_meta_caps( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {

	// What capability is being checked?
	switch ( $cap ) {

		/** Reading ***********************************************************/

		case 'read_account' :

			// User cannot read
			if ( ! user_can( $user_id, 'fct_spectate' ) ){
				$caps = array( 'do_not_allow' );

			// Fisci, Controllers and assigned users can read
			} elseif ( user_can( $user_id, 'fiscaat' ) 
				|| fct_user_can_spectate( $args[0], $user_id ) 
			){
				$caps = array( 'fct_spectate' );
			}

			break;

		/** Publishing ********************************************************/

		case 'publish_accounts'  :

			// Restrain when requirements lack
			if ( ! fct_has_open_year() ) {
				$caps = array( 'do_not_allow' );

			// Publish on activation 
			} elseif ( fct_is_install() ){
				$caps = array( 'administrator' );

			// Only Fisci can always publish
			} else {
				$caps = array( 'fiscaat' );
			}

			break;

		/** Editing ***********************************************************/

		case 'edit_accounts'        :
		case 'edit_others_accounts' :

			// Only Fisci can always edit
			$caps = array( 'fiscaat' );

			break;

		case 'edit_account' :

			// User cannot edit
			if ( ! user_can( $user_id, 'fiscaat' ) ){
				$caps = array( 'do_not_allow' );

			// Account is closed
			} elseif ( fct_is_account_closed( $args[0] ) ){
				$caps = array( 'do_not_allow' );

			// Fisci can edit
			} else {
				$caps = array( 'fiscaat' );
			}
		
		/** Deleting **********************************************************/

		case 'delete_account'         :
		case 'delete_accounts'        :
		case 'delete_others_accounts' :

			// Accounts are deleted on reset or uninstall
			if ( is_admin() && ( fct_is_reset() || fct_is_uninstall() ) ){
				$caps = array( 'administrator' );

			// User cannot delete
			} elseif ( ! user_can( $user_id, 'fiscaat' ) ) {
				$caps = array( 'do_not_allow' );

			// Account has no records
			} elseif ( ! fct_account_has_records() ) {
				$caps = array( 'fiscaat' );

			// Not else
			} else {
				$caps = array( 'do_not_allow' );
			}

			break;

		/** Admin *************************************************************/

		case 'fct_accounts_admin' :
			$caps = array( 'fiscaat' );
			break;
	}

	return apply_filters( 'fct_map_account_meta_caps', $caps, $cap, $user_id, $args );
}

