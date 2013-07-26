<?php

/**
 * Fiscaat Extentions
 *
 * There's a world of really cool plugins out there, and Fiscaat comes with
 * support for some of the most popular ones.
 *
 * @package Fiscaat
 * @subpackage Extend
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Loads Akismet inside the Fiscaat global class
 *
 * @return If Fiscaat is not active
 */
function fiscaat_setup_akismet() {

	// Bail if no akismet
	if ( !defined( 'AKISMET_VERSION' ) ) return;

	// Bail if Akismet is turned off
	if ( !fiscaat_is_akismet_active() ) return;

	// Include the Akismet Component
	require( fiscaat()->includes_dir . 'extend/akismet.php' );

	// Instantiate Akismet for Fiscaat
	fiscaat()->extend->akismet = new Fiscaat_Akismet();
}

/**
 * Requires and creates the BuddyPress extension, and adds component creation
 * action to bp_init hook. @see fiscaat_setup_buddypress_component()
 *
 * @return If BuddyPress is not active
 */
function fiscaat_setup_buddypress() {

	if ( ! function_exists( 'buddypress' ) ) {

		/**
		 * Helper for BuddyPress 1.6 and earlier
		 *
		 * @since Fiscaat (r4395)
		 * @return BuddyPress
		 */
		function buddypress() {
			return isset( $GLOBALS['bp'] ) ? $GLOBALS['bp'] : false;
		}
	}

	// Bail if in maintenance mode
	if ( ! buddypress() || buddypress()->maintenance_mode )
		return;

	// Include the BuddyPress Component
	require( fiscaat()->includes_dir . 'extend/buddypress/loader.php' );

	// Instantiate BuddyPress for Fiscaat
	fiscaat()->extend->buddypress = new Fiscaat_Forums_Component();
}
