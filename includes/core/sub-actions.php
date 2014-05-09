<?php

/**
 * Plugin Dependency
 *
 * The purpose of the following hooks is to mimic the behavior of something
 * called 'plugin dependency' which enables a plugin to have plugins of their
 * own in a safe and reliable way.
 *
 * We do this in Fiscaat by mirroring existing WordPress hooks in many places
 * allowing dependant plugins to hook into the Fiscaat specific ones, thus
 * guaranteeing proper code execution only when Fiscaat is active.
 *
 * The following functions are wrappers for hooks, allowing them to be
 * manually called and/or piggy-backed on top of other hooks if needed.
 *
 * @todo use anonymous functions when PHP minimun requirement allows (5.3)
 */

/** Activation Actions ********************************************************/

/**
 * Runs on Fiscaat activation
 *
 * @uses register_uninstall_hook() To register our own uninstall hook
 * @uses do_action() Calls 'fct_activation' hook
 */
function fct_activation() {
	do_action( 'fct_activation' );
}

/**
 * Runs on Fiscaat deactivation
 *
 * @uses do_action() Calls 'fct_deactivation' hook
 */
function fct_deactivation() {
	do_action( 'fct_deactivation' );
}

/**
 * Runs when uninstalling Fiscaat
 *
 * @uses do_action() Calls 'fct_uninstall' hook
 */
function fct_uninstall() {
	do_action( 'fct_uninstall' );
}

/** Main Actions **************************************************************/

/**
 * Main action responsible for constants, globals, and includes
 *
 * @uses do_action() Calls 'fct_loaded'
 */
function fct_loaded() {
	do_action( 'fct_loaded' );
}

/**
 * Setup constants
 *
 * @uses do_action() Calls 'fct_constants'
 */
function fct_constants() {
	do_action( 'fct_constants' );
}

/**
 * Setup globals BEFORE includes
 *
 * @uses do_action() Calls 'fct_boot_strap_globals'
 */
function fct_boot_strap_globals() {
	do_action( 'fct_boot_strap_globals' );
}

/**
 * Include files
 *
 * @uses do_action() Calls 'fct_includes'
 */
function fct_includes() {
	do_action( 'fct_includes' );
}

/**
 * Setup globals AFTER includes
 *
 * @uses do_action() Calls 'fct_setup_globals'
 */
function fct_setup_globals() {
	do_action( 'fct_setup_globals' );
}

/**
 * Register any objects before anything is initialized
 *
 * @uses do_action() Calls 'fct_register'
 */
function fct_register() {
	do_action( 'fct_register' );
}

/**
 * Initialize any code after everything has been loaded
 *
 * @uses do_action() Calls 'fct_init'
 */
function fct_init() {
	do_action( 'fct_init' );
}

/**
 * Initialize widgets
 *
 * @uses do_action() Calls 'fct_widgets_init'
 */
function fct_widgets_init() {
	do_action( 'fct_widgets_init' );
}

/**
 * Setup the currently logged-in user
 *
 * @uses do_action() Calls 'fct_setup_current_user'
 */
function fct_setup_current_user() {
	do_action( 'fct_setup_current_user' );
}

/** Supplemental Actions ******************************************************/

/**
 * Load translations for current language
 *
 * @uses do_action() Calls 'fct_load_textdomain'
 */
function fct_load_textdomain() {
	do_action( 'fct_load_textdomain' );
}

/**
 * Setup the post types
 *
 * @uses do_action() Calls 'fct_register_post_type'
 */
function fct_register_post_types() {
	do_action( 'fct_register_post_types' );
}

/**
 * Setup the post statuses
 *
 * @uses do_action() Calls 'fct_register_post_statuses'
 */
function fct_register_post_statuses() {
	do_action( 'fct_register_post_statuses' );
}

/**
 * Register the default Fiscaat shortcodes
 *
 * @uses do_action() Calls 'fct_register_shortcodes'
 */
function fct_register_shortcodes() {
	do_action( 'fct_register_shortcodes' );
}

/**
 * Enqueue Fiscaat specific CSS and JS
 *
 * @uses do_action() Calls 'fct_enqueue_scripts'
 */
function fct_enqueue_scripts() {
	do_action( 'fct_enqueue_scripts' );
}

/**
 * Add the Fiscaats-specific rewrite tags
 *
 * @uses do_action() Calls 'fct_add_rewrite_tags'
 */
function fct_add_rewrite_tags() {
	do_action( 'fct_add_rewrite_tags' );
}

/** User Actions **************************************************************/

/**
 * The main action for hooking into when a user account is updated
 *
 * @param int $user_id ID of user being edited
 * @param array $old_user_data The old, unmodified user data
 * @uses do_action() Calls 'fct_profile_update'
 */
function fct_profile_update( $user_id = 0, $old_user_data = array() ) {
	do_action( 'fct_profile_update', $user_id, $old_user_data );
}

/** Final Action **************************************************************/

/**
 * Fiscaat has loaded and initialized everything, and is okay to go
 *
 * @uses do_action() Calls 'fct_ready'
 */
function fct_ready() {
	do_action( 'fct_ready' );
}

/** Theme Permissions *********************************************************/

/**
 * The main action used for redirecting Fiscaat theme actions that are not
 * permitted by the current_user
 *
 * @uses do_action()
 */
function fct_template_redirect() {
	do_action( 'fct_template_redirect' );
}

/** Theme Helpers *************************************************************/

/**
 * The main action used for executing code before the theme has been setup
 *
 * @uses do_action()
 */
function fct_setup_theme() {
	do_action( 'fct_setup_theme' );
}

/**
 * The main action used for executing code after the theme has been setup
 *
 * @uses do_action()
 */
function fct_after_setup_theme() {
	do_action( 'fct_after_setup_theme' );
}

/**
 * Filter the plugin locale and domain.
 *
 * @param string $locale
 * @param string $domain
 */
function fct_plugin_locale( $locale = '', $domain = '' ) {
	return apply_filters( 'fct_plugin_locale', $locale, $domain );
}

/** Filters *******************************************************************/

/**
 * Piggy back filter for WordPress's 'request' filter
 *
 * @param array $query_vars
 * @return array
 */
function fct_request( $query_vars = array() ) {
	return apply_filters( 'fct_request', $query_vars );
}

/**
 * Generate Fiscaats-specific rewrite rules
 *
 * @param WP_Rewrite $wp_rewrite
 * @uses do_action() Calls 'fct_generate_rewrite_rules' with {@link WP_Rewrite}
 */
function fct_generate_rewrite_rules( $wp_rewrite ) {
	do_action_ref_array( 'fct_generate_rewrite_rules', array( &$wp_rewrite ) );
}

/**
 * Maps record/account/period caps to built in WordPress caps
 *
 * @param array $caps Capabilities for meta capability
 * @param string $cap Capability name
 * @param int $user_id User id
 * @param mixed $args Arguments
 */
function fct_map_meta_caps( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {
	return apply_filters( 'fct_map_meta_caps', $caps, $cap, $user_id, $args );
}
