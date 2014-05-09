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

// Periods
add_filter( 'fct_get_period_default_meta', 'fct_ctrl_get_period_default_meta' );
add_filter( 'fct_no_close_period',         'fct_ctrl_no_close_period'         );

// Accounts
add_filter( 'fct_get_account_default_meta', 'fct_ctrl_get_account_default_meta' );
add_filter( 'fct_no_close_account',         'fct_ctrl_no_close_account'         );
// fct_get_single_account_description

// Records
add_filter( 'fct_no_close_record',                       'fct_ctrl_no_close_record'                       );
add_filter( 'fct_record_statuses',                       'fct_ctrl_record_statuses'                       );
add_filter( 'fct_record_status_dropdown_disable',        'fct_ctrl_record_status_dropdown_disable'        );
add_filter( 'fct_record_status_dropdown_option_disable', 'fct_ctrl_record_status_dropdown_option_disable' );

// Statistics
add_filter( 'fct_before_get_statistics_parse_args', 'fct_ctrl_get_statistics_default_args'   );
add_filter( 'fct_get_statistics',                   'fct_ctrl_get_statistics',         10, 2 );

// Admin
add_filter( 'fct_admin_periods_column_headers',    'fct_ctrl_admin_periods_column_headers'      );
add_filter( 'fct_admin_periods_sortable_columns',  'fct_ctrl_admin_periods_sortable_columns'    );
add_filter( 'fct_admin_periods_request',           'fct_ctrl_admin_periods_request'             );
add_filter( 'fct_admin_accounts_column_headers',   'fct_ctrl_admin_accounts_column_headers'   );
add_filter( 'fct_admin_accounts_sortable_columns', 'fct_ctrl_admin_accounts_sortable_columns' );
add_filter( 'fct_admin_accounts_request',          'fct_ctrl_admin_accounts_request'          );
