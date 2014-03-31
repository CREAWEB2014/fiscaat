<?php

/**
 * Fiscaat Control Actions
 *
 * @package Fiscaat
 * @subpackage Control
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Controller role
add_filter( 'fct_get_dynamic_roles', 'fct_control_get_dynamic_role',  20    );
add_filter( 'fct_get_caps_for_role', 'fct_control_get_caps_for_role', 20, 2 );
add_filter( 'fct_map_meta_caps',     'fct_control_map_meta_caps',     20, 4 );

// Record post status dropdown
add_filter( 'fct_record_statuses',                       'fct_control_record_statuses'                       );
add_filter( 'fct_record_status_dropdown_disable',        'fct_control_record_status_dropdown_disable'        );
add_filter( 'fct_record_status_dropdown_option_disable', 'fct_control_record_status_dropdown_option_disable' );

// Statistics
add_filter( 'fct_get_statistics', 'fct_control_get_statistics', 10, 2 );
