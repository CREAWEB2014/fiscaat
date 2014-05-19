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
	return apply_filters( 'fct_get_account_caps', array(
		'create_posts'        => 'create_accounts',
		'edit_posts'          => 'edit_accounts',
		'edit_others_posts'   => 'edit_others_accounts',
		'publish_posts'       => 'publish_accounts',
		'read_private_posts'  => 'read_private_accounts',
		'delete_posts'        => 'delete_accounts',
		'delete_others_posts' => 'delete_others_accounts',
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

		/** Creating **********************************************************/

		/**
		 * Accounts can only be created within an open period.
		 */
		case 'create_accounts'  :

			// No open period
			if ( ! fct_has_open_period() ) {
				$caps = array( 'do_not_allow' );
			}

			break;

		/** Editing ***********************************************************/

		/**
		 * Accounts cannot be edited when closed or within closed periods.
		 */
		case 'edit_account' :

			// Account is closed or period is closed
			if ( fct_is_account_closed( $args[0] ) || fct_is_account_period_closed( $args[0] ) ) {
				$caps = array( 'do_not_allow' );

			// Fisci can edit
			} elseif ( user_can( $user_id, 'fiscaat' ) ) {

				// Default to edit_posts
				$caps = array( get_post_type_object( fct_get_account_post_type() )->cap->edit_posts );
			}

			break;
		
		/** Closing ***********************************************************/

		/**
		 * Accounts are closed in order to ensure their final state before
		 * closing their parent period. Closing an account does not require
		 * anything of its records. Though, the period must be open.
		 */
		case 'close_accounts' :

			// Fisci can close/open accounts
			$caps = array( 'fiscaat' );

			break;
			
		case 'close_account' :

			// Account's period is closed
			if ( fct_is_account_period_closed( $args[0] ) ) {
				$caps = array( 'do_not_allow' );

			// Fisci can close/open accounts
			} elseif ( user_can( $user_id, 'fiscaat' ) ) {
				$caps = array( 'fiscaat' );
			}

			break;

		/** Deleting **********************************************************/

		/**
		 * Accounts cannot be deleted if they contain any records or if 
		 * their period is closed.
		 */
		case 'delete_account' :

			// Account has records or period is closed
			if ( fct_account_has_records( $args[0] ) || fct_is_account_period_closed( $args[0] ) ) {
				$caps = array( 'do_not_allow' );

			// Fisci can delete accounts
			} elseif ( user_can( $user_id, 'fiscaat' ) ) {
				$caps = array( 'fiscaat' );
			}

			break;

		/** Admin *************************************************************/

		case 'fct_accounts_admin' :

			$caps = array( 'fiscaat' );

			break;
	}

	return apply_filters( 'fct_map_account_meta_caps', $caps, $cap, $user_id, $args );
}