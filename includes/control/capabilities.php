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

	// Which capability is being checked?	
	switch ( $cap ) {

		/** Reading ***********************************************************/

		// Controllers can read all
		case 'read_period'  :
		case 'read_account' :
		case 'read_record'  :
			if ( user_can( $user_id, 'fct_control' ) )
				$caps = array( 'fct_control' );
			break;

		/** Editing ***********************************************************/

		// Controllers can edit post stati
		case 'edit_record' :
			if ( user_can( $user_id, 'fct_control' ) ) {

				// Do some post ID based logic
				$_post = get_post( $args[0] );
				if ( ! empty( $_post ) ){

					// Record is not closed
					if ( fct_get_closed_status_id() != $_post->post_status )
						$caps = array( 'fct_control' );
				}
			}

			break;

		case 'edit_records'        :
		case 'edit_others_records' :
			if ( user_can( $user_id, 'fct_control' ) )
				$caps = array( 'fct_control' );

			break;

		/** Closing ***********************************************************/

		/**
		 * Accounts are closed when all its records are approved by
		 * a Controller. This means that any unapproved or declined
		 * record inhibits a Fiscus from closing its account. Since
		 * this consequently flows through to closing the account's
		 * period, there are no checks for a period's unapproved
		 * record count.
		 */
		case 'close_account' :
			if ( user_can( $user_id, 'close_accounts' ) ) {

				// Do some post ID based logic
				$_post = get_post( $args[0] );
				if ( ! empty( $_post ) ){

					// Account has unapproved records
					if ( (bool) fct_get_account_record_count_unapproved( $args[0] ) )
						$caps = array( 'do_not_allow' );
			}

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
			'edit_records'           => true, // Conditionally
			'edit_others_records'    => true, // Conditionally
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
	$role = fct_get_controller_role();
	$roles[ $role ] = array(
		'name'         => __( 'Controller', 'fiscaat' ),
		'capabilities' => fct_get_caps_for_role( $role )
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

/**
 * Get the total number of controllers on Fiscaat
 *
 * @uses count_users() To execute our query and get the var back
 * @uses apply_filters() Calls 'fct_get_total_controllers' with number of controllers
 * @return int Total number of controllers
 */
function fct_get_total_controllers() {
	$user_count = count_users();
	$role       = fct_get_controller_role();

	// Check for Controllers
	if ( ! isset( $user_count['avail_roles'][$role] ) )
		return 0;

	return (int) apply_filters( 'fct_get_total_controllers', (int) $user_count['avail_roles'][$role] );
}

