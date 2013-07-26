<?php

/**
 * Fiscaat Record Capabilites
 *
 * Used to map record capabilities to WordPress's existing capabilities.
 *
 * @package Fiscaat
 * @subpackage Capabilities
 */

/**
 * Return record capabilities
 *
 * @uses apply_filters() Calls 'fiscaat_get_record_caps' with the capabilities
 * @return array Record capabilities
 */
function fiscaat_get_record_caps() {
	return apply_filters( 'fiscaat_get_record_caps', array (
		'edit_posts'          => 'edit_records',
		'edit_others_posts'   => 'edit_others_records',
		'publish_posts'       => 'publish_records',
		'read_private_posts'  => 'read_private_records',
		'delete_posts'        => 'delete_records',
		'delete_others_posts' => 'delete_others_records'
	) );
}

/**
 * Maps record capabilities
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
function fiscaat_map_record_meta_caps( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {

	// What capability is being checked?
	switch ( $cap ) {

		/** Reading ***********************************************************/

		case 'read_record' :

			// User cannot read
			if ( ! user_can( $user_id, 'fiscaat_spectate' ) ) {
				$caps = array( 'do_not_allow' );

			// Fisci can always read
			} elseif ( user_can( $user_id, 'fiscaat' )
				|| fiscaat_user_can_spectate( $args[0], $user_id )
			) {
				$caps = array( 'fiscaat_spectate' );
			}

			break;

		/** Publishing ********************************************************/

		case 'publish_records'  :

			// Restrain when requirements lack
			if ( ! fiscaat_has_open_year() || ! fiscaat_has_open_account() ) {
				$caps = array( 'do_not_allow' );

			// Only Fisci can always publish
			} else {
				$caps = array( 'fiscaat' );
			}

			break;

		/** Editing ***********************************************************/

		case 'edit_record' :

			// Do some post ID based logic
			$_post = get_post( $args[0] );
			if ( !empty( $_post ) ){

				// Record is closed
				if ( fiscaat_get_closed_status_id() == $_post->post_status ){
					$caps = array( 'do_not_allow' );

				// Fisci can always edit
				} elseif ( user_can( $user_id, 'fiscaat' ) ) {
					$caps = array( 'fiscaat' );

				} else {
					$caps = array( 'do_not_allow' );
				}
			}

			break;

		case 'edit_records'        :
		case 'edit_others_records' :

			// Fisci can always edit
			if ( user_can( $user_id, 'fiscaat' ) ) {
				$caps = array( 'fiscaat' );

			} else {
				$caps = array( 'do_not_allow' );
			}

			break;

		/** Deleting **********************************************************/

		case 'delete_record'         :
		case 'delete_records'        :
		case 'delete_others_records' :

			// Records are only deleted on reset or uninstall
			if ( ! is_admin() && ( ! fiscaat_is_reset() || ! fiscaat_is_uninstall() ) ){
				$caps = array( 'do_not_allow' );
			}

			break;

		/** Attachments *******************************************************/

		case 'upload_files' :

			global $wp_query;

			// Fisci can always upload for records
			if ( fiscaat_get_record_post_type() == $wp_query->get( 'post_type' ) ) {
				$caps = array( 'fiscaat' );
			}

			break;

		/** Admin *************************************************************/

		case 'fiscaat_records_admin' :
			$caps = array( 'fiscaat' );
			break;
	}

	return apply_filters( 'fiscaat_map_record_meta_caps', $caps, $cap, $user_id, $args );
}
