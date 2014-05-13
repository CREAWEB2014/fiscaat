<?php

/**
 * Fiscaat Period Capabilites
 *
 * Used to map period capabilities to WordPress's existing capabilities.
 *
 * @package Fiscaat
 * @subpackage Capabilities
 */

/**
 * Return period capabilities
 *
 * @uses apply_filters() Calls 'fct_get_period_caps' with the capabilities
 * @return array Period capabilities
 */
function fct_get_period_caps() {
	return apply_filters( 'fct_get_period_caps', array (
		'create_posts'        => 'create_periods',
		'edit_posts'          => 'edit_periods',
		'edit_others_posts'   => 'edit_others_periods',
		'publish_posts'       => 'publish_periods',
		'read_private_posts'  => 'read_private_periods',
		'delete_posts'        => 'delete_periods',
		'delete_others_posts' => 'delete_others_periods'
	) );
}

/**
 * Maps period capabilities
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
function fct_map_period_meta_caps( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {

	// What capability is being checked?
	switch ( $cap ) {

		/** Reading ***********************************************************/

		case 'read_period' :

			// User cannot read
			if ( ! user_can( $user_id, 'fct_spectate' ) ) {
				$caps = array( 'do_not_allow' );

			// Fisci, Controllers and assigned users can read
			} elseif ( user_can( $user_id, 'fiscaat' )
				|| fct_user_can_spectate( $args[0], $user_id ) 
				) {
				$caps = array( 'fct_spectate' );
			}

			break;

		/** Publishing ********************************************************/

		case 'publish_periods' :
			$caps = array( 'fiscaat' );
			break;

		/** Editing ***********************************************************/

		case 'edit_periods'        :
		case 'edit_others_periods' :

			// Only Fisci can always edit
			$caps = array( 'fiscaat' );

			break;

		case 'edit_period' :

			// Period is closed
			if ( fct_is_period_closed( $args[0] ) ) {
				$caps = array( 'do_not_allow' );

			// Fisci can edit
			} else {
				$caps = array( 'fiscaat' );
			}

			break;

		/** Closing ***********************************************************/

		/**
		 * Periods are closed in order to ensure their final state in the
		 * accounting system history. Once closed, neither details or its 
		 * accounts can be edited, nor records can be added to it.
		 */
		case 'close_periods' :

			// Fisci can close/open periods
			$caps = array( 'fiscaat' );

			break;

		case 'close_period'  :

			// Period has open account
			if ( fct_has_open_account() ) {
				$caps = array( 'do_not_allow' );

			// Fisci can close
			} else {
				$caps = array( 'fiscaat' );
			}
			
			break;

		/** Deleting **********************************************************/

		case 'delete_period'         :
		case 'delete_periods'        :
		case 'delete_others_periods' :

			// Periods are deleted on reset or uninstall
			if ( is_admin() && ( fct_is_reset() || fct_is_uninstall() ) ) {
				$caps = array( 'administrator' );

			// User cannot delete
			} elseif ( ! user_can( $user_id, 'fiscaat' ) ) {
				$caps = array( 'do_not_allow' );

			// Period has no records
			} elseif ( ! fct_period_has_records() ) {
				$caps = array( 'fiscaat' );

			// Else not
			} else {
				$caps = array( 'do_not_allow' );
			}

			break;

		/** Admin *************************************************************/

		// Only Fisci can admin periods
		case 'fct_periods_admin' :
			$caps = array( 'fiscaat' );
			break;
	}

	return apply_filters( 'fct_map_period_meta_caps', $caps, $cap, $user_id, $args );
}

