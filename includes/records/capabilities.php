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
 * @uses apply_filters() Calls 'fct_get_record_caps' with the capabilities
 * @return array Record capabilities
 */
function fct_get_record_caps() {
	return apply_filters( 'fct_get_record_caps', array(
		'create_posts'        => 'create_records',
		'edit_posts'          => 'edit_records',
		'edit_others_posts'   => 'edit_others_records',
		'publish_posts'       => 'publish_records',
		'read_private_posts'  => 'read_private_records',
		'delete_posts'        => 'delete_records',
		'delete_others_posts' => 'delete_others_records',
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
function fct_map_record_meta_caps( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {

	// What capability is being checked?
	switch ( $cap ) {

		/** Reading ***********************************************************/

		case 'read_record' :

			// User cannot read
			if ( ! user_can( $user_id, 'fct_spectate' ) ) {
				$caps = array( 'do_not_allow' );

			// Fisci can always read
			} elseif ( user_can( $user_id, 'fiscaat' )
				|| fct_user_can_spectate( $args[0], $user_id )
				) {
				$caps = array( 'fct_spectate' );
			}

			break;

		/** Creating **********************************************************/

		/**
		 * Records cannot be created when there is both no open account
		 * and no open period to assign the record to.
		 */
		case 'create_records' :

			// Bail when there's no open period or account
			if ( ! fct_has_open_period() && ! fct_has_open_account() ) {
				$caps = array( 'do_not_allow' );
			}

			break;

		/** Editing ***********************************************************/

		/**
		 * Records cannot be edited when either its account or period is
		 * closed. In that state, the records are finite.
		 */
		case 'edit_record' :

			// Record's period or account is closed
			if ( fct_is_record_period_closed( $args[0] ) || fct_is_record_account_closed( $args[0] ) ) {
				$caps = array( 'do_not_allow' );

			// Else Fisci can edit
			} elseif ( user_can( $user_id, 'fiscaat' ) ) {

				// Default to edit_posts
				$caps = array( get_post_type_object( fct_get_record_post_type() )->cap->edit_posts );
			}

			break;

		/** Deleting **********************************************************/

		/**
		 * To prevent any form of financial manipulation, record deletion
		 * in Fiscaat is fully disabled. 
		 */
		case 'delete_records' :
		case 'delete_others_records' :
		case 'delete_record' :

			$caps = array( 'do_not_allow' );

			break;

		/** Admin *************************************************************/

		case 'fct_records_admin' :

			$caps = array( 'fiscaat' );

			break;
	}

	return apply_filters( 'fct_map_record_meta_caps', $caps, $cap, $user_id, $args );
}
