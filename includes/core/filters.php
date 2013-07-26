<?php

/**
 * Fiscaat Filters
 *
 * @package Fiscaat
 * @subpackage Core
 *
 * This file contains the filters that are used through-out Fiscaat. They are
 * consolidated here to make searching for them easier, and to help developers
 * understand at a glance the order in which things occur.
 *
 * There are a few common places that additional filters can currently be found
 *
 *  - Fiscaat: In {@link Fiscaat::setup_actions()} in fiscaat.php
 *  - Admin: More in {@link Fiscaat_Admin::setup_actions()} in admin.php
 *
 * @see /core/actions.php
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

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
add_filter( 'request',                 'fiscaat_request',            10    );
add_filter( 'wp_title',                'fiscaat_title',              10, 3 );
add_filter( 'body_class',              'fiscaat_body_class',         10, 2 );
add_filter( 'map_meta_cap',            'fiscaat_map_meta_caps',      10, 4 );
add_filter( 'redirect_canonical',      'fiscaat_redirect_canonical', 10    );
add_filter( 'plugin_locale',           'fiscaat_plugin_locale',      10, 2 );

// Remove years roles from list of all roles
add_filter( 'editable_roles', 'fiscaat_filter_blog_editable_roles' );

// Links
add_filter( 'paginate_links',                'fiscaat_add_view_all' );
add_filter( 'fiscaat_get_account_permalink', 'fiscaat_add_view_all' );
add_filter( 'fiscaat_get_record_permalink',  'fiscaat_add_view_all' );
add_filter( 'fiscaat_get_year_permalink',    'fiscaat_add_view_all' );

// wp_filter_kses on new/edit account/record title
add_filter( 'fiscaat_new_record_pre_title',     'wp_filter_kses' );
add_filter( 'fiscaat_new_account_pre_title',    'wp_filter_kses' );
add_filter( 'fiscaat_edit_record_pre_title',    'wp_filter_kses' );
add_filter( 'fiscaat_edit_account_pre_title',   'wp_filter_kses' );

// balanceTags, wp_filter_kses and wp_rel_nofollow on new/edit account/record text
add_filter( 'fiscaat_new_record_pre_content',   'balanceTags'     );
add_filter( 'fiscaat_new_record_pre_content',   'wp_rel_nofollow' );
add_filter( 'fiscaat_new_record_pre_content',   'wp_filter_kses'  );
add_filter( 'fiscaat_new_account_pre_content',  'balanceTags'     );
add_filter( 'fiscaat_new_account_pre_content',  'wp_rel_nofollow' );
add_filter( 'fiscaat_new_account_pre_content',  'wp_filter_kses'  );
add_filter( 'fiscaat_edit_record_pre_content',  'balanceTags'     );
add_filter( 'fiscaat_edit_record_pre_content',  'wp_rel_nofollow' );
add_filter( 'fiscaat_edit_record_pre_content',  'wp_filter_kses'  );
add_filter( 'fiscaat_edit_account_pre_content', 'balanceTags'     );
add_filter( 'fiscaat_edit_account_pre_content', 'wp_rel_nofollow' );
add_filter( 'fiscaat_edit_account_pre_content', 'wp_filter_kses'  );

// Run filters on record content
add_filter( 'fiscaat_get_record_content', 'capital_P_dangit'             );
add_filter( 'fiscaat_get_record_content', 'wptexturize',            3    );
add_filter( 'fiscaat_get_record_content', 'convert_chars',          5    );
add_filter( 'fiscaat_get_record_content', 'make_clickable',         9    );
add_filter( 'fiscaat_get_record_content', 'force_balance_tags',     25   );
add_filter( 'fiscaat_get_record_content', 'convert_smilies',        20   );
add_filter( 'fiscaat_get_record_content', 'wpautop',                30   );

// Run filters on account content
add_filter( 'fiscaat_get_account_content', 'capital_P_dangit'             );
add_filter( 'fiscaat_get_account_content', 'wptexturize',            3    );
add_filter( 'fiscaat_get_account_content', 'convert_chars',          5    );
add_filter( 'fiscaat_get_account_content', 'make_clickable',         9    );
add_filter( 'fiscaat_get_account_content', 'force_balance_tags',     25   );
add_filter( 'fiscaat_get_account_content', 'convert_smilies',        20   );
add_filter( 'fiscaat_get_account_content', 'wpautop',                30   );

// Add number format filter to functions requiring numeric output
add_filter( 'fiscaat_get_year_account_count',   'fiscaat_number_format', 10 );
add_filter( 'fiscaat_get_year_record_count',    'fiscaat_number_format', 10 );
add_filter( 'fiscaat_get_account_record_count', 'fiscaat_number_format', 10 );

// Run wp_kses_data on account/record content in admin section
if ( is_admin() ) {
	add_filter( 'fiscaat_get_record_content',  'wp_kses_data' );
	add_filter( 'fiscaat_get_account_content', 'wp_kses_data' );
}

// Capabilities
add_filter( 'fiscaat_map_meta_caps', 'fiscaat_map_primary_meta_caps', 10, 4 ); // Primary caps
add_filter( 'fiscaat_map_meta_caps', 'fiscaat_map_year_meta_caps',    10, 4 ); // Years
add_filter( 'fiscaat_map_meta_caps', 'fiscaat_map_account_meta_caps', 10, 4 ); // Accounts
add_filter( 'fiscaat_map_meta_caps', 'fiscaat_map_record_meta_caps',  10, 4 ); // Records
