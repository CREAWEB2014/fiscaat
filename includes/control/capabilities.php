<?php

/**
 * Fiscaat Control Capabilities
 *
 * @package Fiscaat
 * @subpackage Capabilities
 *
 * @todo Fisci cannot be Controllers et vice versa
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Map meta capabilities when the user can control
 * 
 * @param array $caps
 * @param string $cap
 * @param int $user_id
 * @param array $args
 * @return Mapped caps
 */
function fct_ctrl_map_meta_caps( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {

	// For Controllers only
	if ( ! user_can( $user_id, 'fct_control' ) )
		return $caps;

	// Which capability is being checked?	
	switch ( $cap ) {

		/** Reading ***********************************************************/

		// Controllers can read all
		case 'read_year'    :
		case 'read_account' :
		case 'read_record'  :
			$caps = array( 'fct_control' );
			break;

		/** Editing ***********************************************************/

		// Controllers can edit post stati
		case 'edit_record' :

			// Do some post ID based logic
			$_post = get_post( $args[0] );
			if ( ! empty( $_post ) ){

				// Record is not closed
				if ( fct_get_closed_status_id() != $_post->post_status )
					$caps = array( 'fct_control' );
			}

			break;

		case 'edit_records'        :
		case 'edit_others_records' :
			$caps = array( 'fct_control' );
			break;
	}

	return apply_filters( 'fct_ctrl_map_meta_caps', $caps, $cap, $user_id, $args );
}

/**
 * Return capabilities for Controller role
 * 
 * @param array $caps
 * @param string $role
 * @return array
 */
function fct_ctrl_get_caps_for_role( $caps, $role ) {

	// Controller
	if ( fct_get_controller_role() == $role ) {
		$caps = array(

			// Controllers only
			'fct_control'            => true,

			// Primary caps
			'fct_spectate'           => true,

			// Record caps
			'publish_records'        => false,
			'edit_records'           => true,
			'edit_others_records'    => true,
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

			// Year caps. Controllers only
			'publish_years'          => false,
			'edit_years'             => false,
			'edit_others_years'      => false,
			'delete_years'           => false,
			'delete_others_years'    => false,
			'read_private_years'     => false
		);
	}

	return $caps;
}

/**
 * Create Controller role for Fiscaat dynamic roles
 * 
 * @param array $roles
 * @return array
 */
function fct_ctrl_get_dynamic_roles( $roles ) {
	$roles[fct_get_controller_role()] = array(
		'name'         => __( 'Controller', 'fiscaat' ),
		'capabilities' => fct_get_caps_for_role( fct_get_controller_role() )
	);

	return $roles;
}

/**
 * The controller role for Fiscaat users
 *
 * @uses apply_filters() Allow override of hardcoded controller role
 * @return string
 */
function fct_get_controller_role() {
	return apply_filters( 'fct_get_controller_role', 'fct_controller' );
}
