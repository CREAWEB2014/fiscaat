<?php

/**
 * Fiscaat Control Actions
 *
 * @package Fiscaat
 * @subpackage Control
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Post statuses
add_action( 'fct_register_post_statuses', 'fct_ctrl_register_post_statuses' );

// Admin
add_action( 'fct_admin_accounts_column_data', 'fct_ctrl_admin_accounts_column_data', 10, 2 );
add_action( 'fct_admin_years_column_data',    'fct_ctrl_admin_years_column_data',    10, 2 );
