<?php

/**
 * Fiscaat Actions
 *
 * @package Fiscaat
 * @subpackage Core
 *
 * This file contains the actions that are used through-out Fiscaat. They are
 * consolidated here to make searching for them easier, and to help developers
 * understand at a glance the order in which things occur.
 *
 * There are a few common places that additional actions can currently be found
 *
 *  - Fiscaat: In {@link Fiscaat::setup_actions()} in fiscaat.php
 *  - Admin: More in {@link Fiscaat_Admin::setup_actions()} in admin.php
 *
 * @see /core/filters.php
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
 *           v--WordPress Actions        v--Fiscaat Sub-actions
 */
add_action( 'plugins_loaded',           'fct_loaded',                   10    );
add_action( 'init',                     'fct_init',                     0     ); // Early for fct_register
add_action( 'parse_query',              'fct_parse_query',              2     ); // Early for overrides
add_action( 'widgets_init',             'fct_widgets_init',             10    );
add_action( 'generate_rewrite_rules',   'fct_generate_rewrite_rules',   10    );
add_action( 'wp_enqueue_scripts',       'fct_enqueue_scripts',          10    );
add_action( 'wp_head',                  'fct_head',                     10    );
add_action( 'wp_footer',                'fct_footer',                   10    );
add_action( 'set_current_user',         'fct_setup_current_user',       10    );
add_action( 'setup_theme',              'fct_setup_theme',              10    );
add_action( 'after_setup_theme',        'fct_after_setup_theme',        10    );
add_action( 'template_redirect',        'fct_template_redirect',        10    );
add_action( 'profile_update',           'fct_profile_update',           10, 2 ); // user_id and old_user_data

/**
 * fct_loaded - Attached to 'plugins_loaded' above
 *
 * Attach various loader actions to the fct_loaded action.
 * The load order helps to execute code at the correct time.
 *                                                                 v---Load order
 */
add_action( 'fct_loaded', 'fct_constants',                 2  );
add_action( 'fct_loaded', 'fct_boot_strap_globals',        4  );
add_action( 'fct_loaded', 'fct_includes',                  6  );
add_action( 'fct_loaded', 'fct_setup_globals',             8  );
add_action( 'fct_loaded', 'fct_setup_option_filters',      10 );
add_action( 'fct_loaded', 'fct_setup_user_option_filters', 12 );
add_action( 'fct_loaded', 'fct_filter_user_roles_option',  16 );

/**
 * fct_init - Attached to 'init' above
 *
 * Attach various initialization actions to the init action.
 * The load order helps to execute code at the correct time.
 *                                                      v---Load order
 */
add_action( 'fct_init', 'fct_register',         0   );
add_action( 'fct_init', 'fct_load_textdomain',  10  );
add_action( 'fct_init', 'fct_add_rewrite_tags', 20  );
add_action( 'fct_init', 'fct_ready',            999 );

/**
 * There is no action API for roles to use, so hook in immediately after the
 * $wp_roles global is set, which is the 'setup_theme' action.
 *
 * This is kind of lame, but is all we have for now.
 */
add_action( 'fct_setup_theme', 'fct_add_roles', 1 );

/**
 * When switching to a new blog, a users mapped role will get wiped out by
 * WP_User::for_blog() and WP_User::_init_caps().
 *
 * This happens naturally in multisite setups during WP_Admin_Bar::initialize(),
 * which is annoying because it will happen on each page-load.
 *
 * Resetting the role on blog-switch enables us to maintain the user's dynamic
 * role between sites. Note that if a user already has a role on that site, no
 * mapping will occur.
 *
 * We also hook to 'fct_setup_current_user' -- naturally.
 */
// add_action( 'switch_blog',                'fct_set_current_user_default_role' );
// add_action( 'fct_setup_current_user', 'fct_set_current_user_default_role' );

/**
 * fct_register - Attached to 'init' above on 0 priority
 *
 * Attach various initialization actions early to the init action.
 * The load order helps to execute code at the correct time.
 *                                                         v---Load order
 */
add_action( 'fct_register', 'fct_register_post_types',     2  );
add_action( 'fct_register', 'fct_register_post_statuses',  4  );
add_action( 'fct_register', 'fct_register_shortcodes',     10 );

// Try to load the fiscaat-functions.php file from the active themes
// add_action( 'fct_after_setup_theme', 'fct_load_theme_functions', 10 );

// Widgets
// add_action( 'fct_widgets_init', array( 'Fiscaat_Years_Widget',    'register_widget' ), 10 );
// add_action( 'fct_widgets_init', array( 'Fiscaat_Accounts_Widget', 'register_widget' ), 10 );
// add_action( 'fct_widgets_init', array( 'Fiscaat_Records_Widget',  'register_widget' ), 10 );

// Template - Head, foot, errors and messages
// add_action( 'fct_head',             'fct_account_notices'  );
// add_action( 'fct_template_notices', 'fct_template_notices' );

// Before Delete/Trash/Untrash Account
add_action( 'wp_trash_post', 'fct_trash_year'   );
add_action( 'trash_post',    'fct_trash_year'   );
add_action( 'untrash_post',  'fct_untrash_year' );
add_action( 'delete_post',   'fct_delete_year'  );

// After Deleted/Trashed/Untrashed Account
add_action( 'trashed_post',   'fct_trashed_year'   );
add_action( 'untrashed_post', 'fct_untrashed_year' );
add_action( 'deleted_post',   'fct_deleted_year'   );

// Auto trash/untrash/delete a years accounts
add_action( 'fct_delete_year',  'fct_delete_year_accounts',  10 );
add_action( 'fct_trash_year',   'fct_trash_year_accounts',   10 );
add_action( 'fct_untrash_year', 'fct_untrash_year_accounts', 10 );

// New/Edit Year
add_action( 'fct_new_year',  'fct_update_year', 10 );
add_action( 'fct_edit_year', 'fct_update_year', 10 );

// Save year extra metadata
add_action( 'fct_new_year_post_extras',         'fct_save_year_extras', 2 );
add_action( 'fct_edit_year_post_extras',        'fct_save_year_extras', 2 );
add_action( 'fct_year_attributes_metabox_save', 'fct_save_year_extras', 2 );

// New/Edit Record
add_action( 'fct_new_record',  'fct_update_record', 10, 6 );
add_action( 'fct_edit_record', 'fct_update_record', 10, 6 );

// Before Delete/Trash/Untrash Record
add_action( 'wp_trash_post', 'fct_trash_record'   );
add_action( 'trash_post',    'fct_trash_record'   );
add_action( 'untrash_post',  'fct_untrash_record' );
add_action( 'delete_post',   'fct_delete_record'  );

// After Deleted/Trashed/Untrashed Record
add_action( 'trashed_post',   'fct_trashed_record'   );
add_action( 'untrashed_post', 'fct_untrashed_record' );
add_action( 'deleted_post',   'fct_deleted_record'   );

// New/Edit Account
add_action( 'fct_new_account',  'fct_update_account', 10, 2 );
add_action( 'fct_edit_account', 'fct_update_account', 10, 2 );

// Before Delete/Trash/Untrash Account
add_action( 'wp_trash_post', 'fct_trash_account'   );
add_action( 'trash_post',    'fct_trash_account'   );
add_action( 'untrash_post',  'fct_untrash_account' );
add_action( 'delete_post',   'fct_delete_account'  );

// After Deleted/Trashed/Untrashed Account
add_action( 'trashed_post',   'fct_trashed_account'   );
add_action( 'untrashed_post', 'fct_untrashed_account' );
add_action( 'deleted_post',   'fct_deleted_account'   );

// Update account branch
// add_action( 'fct_trashed_account',   'fct_update_account_walker' );
// add_action( 'fct_untrashed_account', 'fct_update_account_walker' );
// add_action( 'fct_deleted_account',   'fct_update_account_walker' );

// Update record branch
// add_action( 'fct_trashed_record',    'fct_update_record_walker' );
// add_action( 'fct_untrashed_record',  'fct_update_record_walker' );
// add_action( 'fct_deleted_record',    'fct_update_record_walker' );
// add_action( 'fct_disallowed_record', 'fct_update_record_walker' );
// add_action( 'fct_allowed_record',    'fct_update_record_walker' );

// User role and meta
add_action( 'fct_profile_update', 'fct_profile_update_role'             );
add_action( 'fct_profile_update', 'fct_profile_update_global_spectator' );
add_action( 'fct_profile_update', 'fct_profile_update_block_commenter'  );

// Hook WordPress admin actions to Fiscaat profiles on save
add_action( 'fct_user_edit_after', 'fct_user_edit_after' );

// Caches
add_action( 'fct_new_year_pre_extras',     'fct_clean_post_cache' );
add_action( 'fct_new_year_post_extras',    'fct_clean_post_cache' );
add_action( 'fct_new_account_pre_extras',  'fct_clean_post_cache' );
add_action( 'fct_new_account_post_extras', 'fct_clean_post_cache' );
add_action( 'fct_new_record_pre_extras',   'fct_clean_post_cache' );
add_action( 'fct_new_record_post_extras',  'fct_clean_post_cache' );

/**
 * Fiscaat needs to redirect the user around in a few different circumstances:
 *
 * 1. Accessing content (years/accounts/records)
 * 2. Form submission within a theme (new and edit)
 * 3. Editing years, accounts, and records
 */
add_action( 'fct_template_redirect', 'fct_enforce_404',            -1 );
// add_action( 'fct_template_redirect', 'fct_new_year_handler',       10 );
// add_action( 'fct_template_redirect', 'fct_new_account_handler',    10 );
// add_action( 'fct_template_redirect', 'fct_new_record_handler',     10 );
// add_action( 'fct_template_redirect', 'fct_edit_year_handler',      1  );
// add_action( 'fct_template_redirect', 'fct_edit_record_handler',    1  );
// add_action( 'fct_template_redirect', 'fct_edit_account_handler',   1  );
// add_action( 'fct_template_redirect', 'fct_toggle_account_handler', 1  );
// add_action( 'fct_template_redirect', 'fct_toggle_record_handler',  1  );
add_action( 'fct_template_redirect', 'fct_check_year_edit',        10 );
add_action( 'fct_template_redirect', 'fct_check_account_edit',     10 );
add_action( 'fct_template_redirect', 'fct_check_record_edit',      10 );

// Control
// add_action( 'fct_init', 'fct_control' );

// Admin bar
add_action( 'admin_bar_menu', 'fct_admin_bar_menu',       90 );
add_action( 'wp_head',        'fct_admin_bar_menu_style', 90 );