<?php

/**
 * Fiscaat Control Functions
 *
 * @package Fiscaat
 * @subpackage Functions
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Post Status ***************************************************************/

/**
 * Return the approved post status ID
 *
 * @return string
 */
function fct_get_approved_status_id() {
	return fiscaat()->control->approved_status_id;
}

/**
 * Return the declined post status ID
 *
 * @return string
 */
function fct_get_declined_status_id() {
	return fiscaat()->control->declined_status_id;
}

/**
 * Register the post statuses used by Fiscaat Control
 *
 * @uses register_post_status() To register post statuses
 */
function fct_ctrl_register_post_statuses() {

	// Approved
	register_post_status(
		fct_get_approved_status_id(),
		apply_filters( 'fct_register_approved_post_status', array(
			'label'                     => _x( 'Approved', 'post', 'fiscaat' ),
			'label_count'               => _nx_noop( 'Approved <span class="count">(%s)</span>', 'Approved <span class="count">(%s)</span>', 'post', 'fiscaat' ),
			'public'                    => true,
			'show_in_admin_status_list' => true,
			'show_in_admin_all'         => true
		) )
	);

	// Declined
	register_post_status(
		fct_get_declined_status_id(),
		apply_filters( 'fct_register_declined_post_status', array(
			'label'                     => _x( 'Declined', 'post', 'fiscaat' ),
			'label_count'               => _nx_noop( 'Declined <span class="count">(%s)</span>', 'Declined <span class="count">(%s)</span>', 'post', 'fiscaat' ),
			'public'                    => true,
			'show_in_admin_status_list' => true,
			'show_in_admin_all_list'    => true
		) )
	);
}

/**
 * Add control post stati to record status dropdown
 * 
 * @param array $statuses
 * @return array
 */
function fct_ctrl_record_statuses( $statuses ) {

	// Insert statuses after 'publish' and before 'close'. array_splice only does numeric keys
	$position = array_search( fct_get_public_status_id(), array_keys( $statuses ) ) + 1;
	$statuses = array_slice( $statuses, 0, $position, true ) + array( 
		fct_get_declined_status_id() => __( 'Declined', 'fiscaat' ),
		fct_get_approved_status_id() => __( 'Approved', 'fiscaat' ),
	) + array_slice( $statuses, $position, null, true );

	return $statuses;
}

/**
 * Enable record status dropdown for Controllers
 * 
 * @param array $args Status dropdown disable args
 * @return array Args
 */
function fct_ctrl_record_status_disable_dropdown( $args ) {

	// User can control
	if ( current_user_can( 'fct_control' ) ) {

		// Enable dropdown
		$args['disable'] = false;

		// Disable non-control statuses
		$args['disable_options'] = array_diff( fct_get_post_stati( fct_get_record_post_type() ), array( fct_get_approved_status_id(), fct_get_declined_status_id() ) );

	// All other users
	} else {
		
		// Disable control statuses
		$args['disable_options'] = array( fct_get_approved_status_id(), fct_get_declined_status_id() );
	}

	return $args;
}

/** Statistics ****************************************************************/

/**
 * Add record post status counts to default Fiscaat statistics args
 * 
 * The following args will be added:
 *  - count_approved_records: Count approved records of the current period?
 *  - count_unapproved_records: Count unapproved records of the current period?
 *  - count_declined_records: Count declined records of the current period?
 * 
 * @param array $args 
 * @return array Args
 */
function fct_ctrl_get_statistics_default_args( $args ) {

	// Merge record post status counts
	$args = array_merge( $args, array(
		'count_approved_records'   => true,
		'count_unapproved_records' => true,
		'count_declined_records'   => true
	) );

	return $args;
}

/**
 * Add various control statistics to the Fiscaat statistics array
 *
 * @uses fct_get_total_controllers()
 * @uses fct_get_declined_status_id()
 * @uses fct_get_approved_status_id()
 * @uses fct_get_closed_status_id()
 * 
 * @param array $stats Fiscaat statistics
 * @param array $args Arguments for what values to count
 * @return array Statistics
 */
function fct_ctrl_get_statistics( $stats, $args ) {

	// Setup local var
	$ctrl = array();

	// Counting users
	if ( ! empty( $args['count_users'] ) ) {
		$ctrl['controller_count'] = fct_get_total_controllers();
	}

	// Counting records
	if ( ! empty( $args['count_current_records'] ) ) {

		// Get record counts
		$current_records = fct_count_posts( array( 
			'type'      => fct_get_record_post_type(), 
			'period_id' => fct_get_current_period_id() 
		) );

		// Unapproved
		if ( ! empty( $args['count_unapproved_records'] ) ) {
			$declined = fct_get_declined_status_id();
			$ctrl['current_unapproved_count'] = $current_records->publish + isset( $current_records->{$declined} ) ? $current_records->{$declined} : 0;
		}

		// Declined
		if ( ! empty( $args['count_declined_records'] ) ) {
			$declined = fct_get_declined_status_id();
			$ctrl['current_declined_count']   = isset( $current_records->{$declined} ) ? $current_records->{$declined} : 0;
		}

		// Approved
		if ( ! empty( $args['count_approved_records'] ) ) {
			$approved = fct_get_approved_status_id();
			$ctrl['current_approved_count']   = isset( $current_records->{$approved} ) ? $current_records->{$approved} : 0;
		}
	}

	// Sanitize and merge 
	if ( ! empty( $ctrl ) ) {

		// Sanitize values
		$ctrl = array_map( 'absint',             $ctrl );
		$ctrl = array_map( 'number_format_i18n', $ctrl );

		// Merge with statistics
		$stats = array_merge( $ctrl, $stats );
	}

	return $stats;
}

/** Admin Bar *****************************************************************/

/**
 * Add control admin bar menu items
 *
 * @param array $menu_items
 * @return array
 */
function fct_ctrl_admin_bar_menu( $menu_items ) {

	// Count bubble template
	$bubble = '<span class="update-plugins count-%1$s" title="%2$s"><span class="update-count">%3$s</span></span>';

	// Unapproved Records
	if ( current_user_can( 'fct_control' ) ) {	
		$count = fct_get_period_record_count_unapproved( fct_get_current_period_id() );
		
		// Create node with unapproved records
		if ( ! empty( $count ) ) {
			$menu_items['fiscaat-control'] = array(
				'parent' => 'fiscaat',
				'title'  => sprintf( __( 'Unapproved %s', 'fiscaat' ), sprintf( 
					$bubble, 
					$count, 
					sprintf( __( '%d Unapproved Records', 'fiscaat' ), number_format_i18n( $count ) ), 
					number_format_i18n( $count ) 
				) ),
				'href'   => add_query_arg( array( 'page' => 'fct-records', 'post_status' => 'unapproved' ), admin_url( 'admin.php' ) ),
				'meta'   => array()
			);
		}
	}

	// Declined Records
	if ( current_user_can( 'fiscaat' ) || current_user_can( 'fct_control' ) ) {
		$count = fct_get_period_record_count_declined( fct_get_current_period_id() );
		
		// Create node with declined records
		if ( ! empty( $count ) ) {
			$menu_items['fiscaat-declined'] = array(
				'parent' => 'fiscaat',
				'title'  => sprintf( __( 'Declined %s', 'fiscaat' ), sprintf( 
					$bubble, 
					$count, 
					sprintf( __( '%d Declined Records', 'fiscaat' ), number_format_i18n( $count ) ), 
					number_format_i18n( $count ) 
				) ),
				'href'   => add_query_arg( array( 'page' => 'fct-records', 'post_status' => fct_get_declined_status_id() ), admin_url( 'admin.php' ) ),
				'meta'   => array()
			);
		}
	}

	return $menu_items;
}
