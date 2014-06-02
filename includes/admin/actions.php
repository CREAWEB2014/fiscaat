<?php

/**
 * Fiscaat Admin Actions
 *
 * @package Fiscaat
 * @subpackage Admin
 *
 * This file contains the actions that are used through-out Fiscaat Admin. They
 * are consolidated here to make searching for them easier, and to help developers
 * understand at a glance the order in which things occur.
 *
 * There are a few common places that additional actions can currently be found
 *
 *  - Fiscaat: In {@link Fiscaat::setup_actions()} in fiscaat.php
 *  - Admin: More in {@link Fiscaat_Admin::setup_actions()} in admin.php
 *
 * @see includes/core/actions.php
 * @see includes/core/filters.php
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Attach Fiscaat to WordPress
 *
 * Fiscaat uses its own internal actions to help aid in third-party plugin
 * development, and to limit the amount of potential future code changes when
 * updates to WordPress core occur.
 *
 * These actions exist to create the concept of 'plugin dependencies'. They
 * provide a safe way for plugins to execute code *only* when Fiscaat is
 * installed and activated, without needing to do complicated guesswork.
 *
 * For more information on how this works, see the 'Plugin Dependency' section
 * near the bottom of this file.
 *
 *           v--WordPress Actions       v--Fiscaat Sub-actions
 */
add_action( 'admin_menu',              'fct_admin_menu'                    );
add_action( 'admin_init',              'fct_admin_init'                    );
add_action( 'admin_head',              'fct_admin_head'                    );
add_action( 'admin_footer',            'fct_admin_footer'                  );
add_action( 'admin_notices',           'fct_admin_notices'                 );
add_action( 'custom_menu_order',       'fct_admin_custom_menu_order'       );
add_action( 'menu_order',              'fct_admin_menu_order'              );

// Hook on to admin_init
add_action( 'fct_admin_init',          'fct_admin_periods'                 );
add_action( 'fct_admin_init',          'fct_admin_accounts'                );
add_action( 'fct_admin_init',          'fct_admin_records'                 );
add_action( 'fct_admin_init',          'fct_setup_updater',            999 );
add_action( 'fct_admin_init',          'fct_register_importers'            );
add_action( 'fct_admin_init',          'fct_register_admin_settings'       );

// Initialize the admin area
add_action( 'fct_init', 'fct_admin' );

// Reset the menu order
add_action( 'fct_admin_menu', 'fct_admin_separator' );

// Activation
add_action( 'fct_activation', 'fct_delete_rewrite_rules'     );
add_action( 'fct_activation', 'fct_make_current_user_fiscus' );

// Deactivation
add_action( 'fct_deactivation', 'fct_remove_caps'          );
add_action( 'fct_deactivation', 'fct_delete_rewrite_rules' );

// Contextual Helpers
add_action( 'load-settings_page_fiscaat', 'fct_admin_settings_help' );

// Admin page title
add_action( 'fct_admin_records_page_title',  'fct_admin_page_title_search_terms', 20 );
add_action( 'fct_admin_accounts_page_title', 'fct_admin_page_title_search_terms', 20 );
add_action( 'fct_admin_periods_page_title',  'fct_admin_page_title_search_terms', 20 );

// Handle submission of Tools pages
add_action( 'load-tools_page_fiscaat-repair', 'fct_admin_repair_handler' );
add_action( 'load-tools_page_fiscaat-reset',  'fct_admin_reset_handler'  );

// Add sample permalink filter
add_filter( 'post_type_link', 'fct_filter_sample_permalink', 10, 4 );

/** Sub-Actions ***************************************************************/

/**
 * Piggy back admin_init action
 *
 * @uses do_action() Calls 'fct_admin_init'
 */
function fct_admin_init() {
	do_action( 'fct_admin_init' );
}

/**
 * Piggy back admin_menu action
 *
 * @uses do_action() Calls 'fct_admin_menu'
 */
function fct_admin_menu() {
	do_action( 'fct_admin_menu' );
}

/**
 * Piggy back admin_head action
 *
 * @uses do_action() Calls 'fct_admin_head'
 */
function fct_admin_head() {
	do_action( 'fct_admin_head' );
}

/**
 * Piggy back admin_footer action
 *
 * @uses do_action() Calls 'fct_admin_footer'
 */
function fct_admin_footer() {
	do_action( 'fct_admin_footer' );
}

/**
 * Piggy back admin_notices action
 *
 * @uses do_action() Calls 'fct_admin_notices'
 */
function fct_admin_notices() {
	do_action( 'fct_admin_notices' );
}

/**
 * Dedicated action to register Fiscaat importers
 *
 * @uses do_action() Calls 'fct_admin_notices'
 */
function fct_register_importers() {
	do_action( 'fct_register_importers' );
}

/**
 * Dedicated action to register admin settings
 *
 * @uses do_action() Calls 'fct_register_admin_settings'
 */
function fct_register_admin_settings() {
	do_action( 'fct_register_admin_settings' );
}

/** Post Pages ****************************************************************/

/**
 * Dedicated action to load the record's post-new.php page
 *
 * @since 0.0.8
 *
 * @uses do_action() Calls 'fct_admin_load_post_record'
 */
function fct_admin_load_post_record() {
	do_action( 'fct_admin_load_post_record' );
}

/**
 * Dedicated action to load the account's post-new.php page
 *
 * @since 0.0.8
 *
 * @uses do_action() Calls 'fct_admin_load_post_account'
 */
function fct_admin_load_post_account() {
	do_action( 'fct_admin_load_post_account' );
}

/**
 * Dedicated action to load the period's post-new.php page
 *
 * @since 0.0.8
 *
 * @uses do_action() Calls 'fct_admin_load_post_period'
 */
function fct_admin_load_post_period() {
	do_action( 'fct_admin_load_post_period' );
}
