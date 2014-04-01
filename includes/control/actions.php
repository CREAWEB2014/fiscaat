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

// fct_update_account
