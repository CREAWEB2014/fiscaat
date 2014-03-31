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
add_filter( 'fiscaat_get_dynamic_roles', 'fiscaat_control_get_dynamic_role',  20    );
add_filter( 'fiscaat_get_caps_for_role', 'fiscaat_control_get_caps_for_role', 20, 2 );
add_filter( 'fiscaat_map_meta_caps',     'fiscaat_control_map_meta_caps',     20, 4 );

// Record post status dropdown
add_filter( 'fiscaat_record_statuses',                       'fiscaat_control_record_statuses'                       );
add_filter( 'fiscaat_record_status_dropdown_disable',        'fiscaat_control_record_status_dropdown_disable'        );
add_filter( 'fiscaat_record_status_dropdown_option_disable', 'fiscaat_control_record_status_dropdown_option_disable' );

// Statistics
add_filter( 'fiscaat_get_statistics', 'fiscaat_control_get_statistics', 10, 2 );
