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
add_filter( 'fct_no_close_year',         'fct_ctrl_no_close_year'         );

// Accounts
add_filter( 'fct_get_account_default_meta', 'fct_ctrl_get_account_default_meta' );
add_filter( 'fct_no_close_account',         'fct_ctrl_no_close_account'         );
// fct_get_single_account_description

// Records
add_filter( 'fct_no_close_record', 'fct_ctrl_no_close_record' );

add_filter( 'fct_record_statuses',                       'fct_ctrl_record_statuses'                       );
add_filter( 'fct_record_status_dropdown_disable',        'fct_ctrl_record_status_dropdown_disable'        );
add_filter( 'fct_record_status_dropdown_option_disable', 'fct_ctrl_record_status_dropdown_option_disable' );

// Stats
add_filter( 'fct_get_statistics', 'fct_ctrl_get_statistics', 10, 2 );
