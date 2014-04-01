<?php

/**
 * Fiscaat Control Filters
 *
 * @package Fiscaat
 * @subpackage Control
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Capabilities
add_filter( 'fct_map_meta_caps',     'fct_ctrl_map_meta_caps',     20, 4 );
add_filter( 'fct_get_caps_for_role', 'fct_ctrl_get_caps_for_role', 20, 2 );
add_filter( 'fct_get_dynamic_roles', 'fct_ctrl_get_dynamic_roles', 20    );

// Years
add_filter( 'fct_get_year_default_meta', 'fct_ctrl_get_year_default_meta' );
add_filter( 'fct_pre_close_year_bail',   'fct_ctrl_pre_close_year_bail'   );

// Accounts
add_filter( 'fct_get_account_default_meta', 'fct_ctrl_get_account_default_meta' );
add_filter( 'fct_pre_close_account_bail',   'fct_ctrl_pre_close_account_bail'   );
// fct_get_single_account_description

// Records
add_filter( 'fct_pre_close_record_bail', 'fct_ctrl_pre_close_record_bail' );

add_filter( 'fct_record_statuses',                       'fct_ctrl_record_statuses'                       );
add_filter( 'fct_record_status_dropdown_disable',        'fct_ctrl_record_status_dropdown_disable'        );
add_filter( 'fct_record_status_dropdown_option_disable', 'fct_ctrl_record_status_dropdown_option_disable' );

// Statistics
add_filter( 'fct_get_statistics', 'fct_ctrl_get_statistics', 10, 2 );
