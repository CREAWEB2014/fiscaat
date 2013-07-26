<?php

/**
 * Fiscaat Year Capabilites
 *
 * Used to map year capabilities to WordPress's existing capabilities.
 *
 * @package Fiscaat
 * @subpackage Capabilities
 */

/**
 * Return year capabilities
 *
 * @uses apply_filters() Calls 'fiscaat_get_year_caps' with the capabilities
 * @return array Year capabilities
 */
function fiscaat_get_year_caps() {
	return apply_filters( 'fiscaat_get_year_caps', array (
		'edit_posts'          => 'edit_years',
		'edit_others_posts'   => 'edit_others_years',
		'publish_posts'       => 'publish_years',
		'read_private_posts'  => 'read_private_years',
		'delete_posts'        => 'delete_years',
		'delete_others_posts' => 'delete_others_years'
	) );
}

/**
 * Maps year capabilities
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
function fiscaat_map_year_meta_caps( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {

	// What capability is being checked?
	switch ( $cap ) {

		/** Reading ***********************************************************/

		case 'read_year' :

			// User cannot read
			if ( ! user_can( $user_id, 'fiscaat_spectate' ) ) {
				$caps = array( 'do_not_allow' );

			// Fisci, Controllers and assigned users can read
			} elseif ( user_can( $user_id, 'fiscaat' ) 
				|| fiscaat_user_can_spectate( $args[0], $user_id ) 
			) {
				$caps = array( 'fiscaat_spectate' );
			}

			break;

		/** Publishing ********************************************************/

		case 'publish_years'  :

			// Publish on install 
			if ( fiscaat_is_install() ) {
				$caps = array( 'administrator' );

			// Only Fisci can always edit
			} else {
				$caps = array( 'fiscaat' );
			}

			break;

		/** Editing ***********************************************************/

		case 'edit_years'         :
		case 'edit_others_years'  :

			// Only Fisci can always edit
			$caps = array( 'fiscaat' );

			break;

		case 'edit_year' :

			// User cannot edit
			if ( !user_can( $user_id, 'fiscaat' ) ) {
				$caps = array( 'do_not_allow' );
			
			// Year is closed
			} elseif ( fiscaat_is_year_closed( $args[0] ) ) {
				$caps = array( 'do_not_allow' );

			// Fisci can edit
			} else {
				$caps = array( 'fiscaat' );
			}

			break;

		/** Deleting **********************************************************/

		case 'delete_year'         :
		case 'delete_years'        :
		case 'delete_others_years' :

			// Years are deleted on reset or uninstall
			if ( is_admin() && ( fiscaat_is_reset() || fiscaat_is_uninstall() ) ) {
				$caps = array( 'administrator' );

			// User cannot delete
			} elseif ( ! user_can( $user_id, 'fiscaat' ) ) {
				$caps = array( 'do_not_allow' );

			// Year has no records
			} elseif ( ! fiscaat_year_has_records() ) {
				$caps = array( 'fiscaat' );

			// Else not
			} else {
				$caps = array( 'do_not_allow' );
			}

			break;

		/** Admin *************************************************************/

		// Only Fisci can admin years
		case 'fiscaat_years_admin' :
			$caps = array( 'fiscaat' );
			break;
	}

	return apply_filters( 'fiscaat_map_year_meta_caps', $caps, $cap, $user_id, $args );
}

