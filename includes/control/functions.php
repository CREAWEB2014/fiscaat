<?php

/**
 * Fiscaat Control Functions
 *
 * @package Fiscaat
 * @subpackage Functions
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** Post Status ***************************************************************/

/**
 * Return the approved post status ID
 *
 * @return string
 */
function fct_get_approved_status_id() {
	return fiscaat()->approved_status_id;
}

/**
 * Return the declined post status ID
 *
 * @return string
 */
function fct_get_declined_status_id() {
	return fiscaat()->declined_status_id;
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
			'label_count'               => _nx_noop( 'Approved <span class="count">(%s)</span>', 'Approved <span class="count">(%s)</span>', 'fiscaat' ),
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
			'label_count'               => _nx_noop( 'Declined <span class="count">(%s)</span>', 'Declined <span class="count">(%s)</span>', 'fiscaat' ),
			'public'                    => true,
			'show_in_admin_status_list' => true,
			'show_in_admin_all_list'    => true
		) )
	);
}

/**
 * Add control post stati to record status dropdown
 * 
 * @param array $options
 * @return array
 */
function fct_ctrl_record_statuses( $statuses ) {

	// Insert statuses after 'publish' and before 'close'
	$statuses = array_splice( $statuses, 1, 0, array(
		fct_get_declined_status_id() => __('Declined', 'fiscaat'),
		fct_get_approved_status_id() => __('Approved', 'fiscaat'),
	) );

	return $statuses;
}

/**
 * Enable record status dropdown for Controllers
 * 
 * @param boolean $disable
 * @return boolean
 */
function fct_ctrl_record_status_dropdown_disable( $disable ) {

	// User can control
	if ( fct_is_control_active() && current_user_can( 'fct_control' ) )
		$disable = false;

	return $disable;
}

/**
 * Disable record status dropdown options for (non-)Controllers
 * 
 * @param boolean $disable
 * @param string $option
 * @return boolean
 */
function fct_ctrl_record_status_dropdown_option_disable( $disable, $option ) {

	// Which options is being checked?
	switch ( $option ) {

		// Disable 'approved' and 'declined' record post stati for non-Controllers
		case fct_get_approved_status_id() :
		case fct_get_declined_status_id() :
			if ( ! current_user_can( 'fct_control' ) )
				$disable = true;
			break;

		// Disable all other post stati for Controllers
		default :
			if ( current_user_can( 'fct_control' ) )
				$disable = true;
			break;
	}

	return $disable;
}

/** Statistics ****************************************************************/

/**
 * Add record post status counts to default Fiscaat statistics args
 * 
 * The following args will be added:
 *  - count_approved_records: Count approved records of the current year?
 *  - count_unapproved_records: Count unapproved records of the current year?
 *  - count_declined_records: Count declined records of the current year?
 * 
 * @param array $args 
 * @return array Args
 */
function fct_ctrl_get_statistics_default_args( $args ) {

	// Merge record post status counts
	$args = array_merge( array(
		'count_approved_records'   => true,
		'count_unapproved_records' => true,
		'count_declined_records'   => true
	), $args );

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

		// Approved
		if ( ! empty( $args['count_approved_records'] ) ) {
			$approved = fct_get_approved_status_id();
			$closed   = fct_get_closed_status_id();
			$ctrl['current_approved_count']   = $current_records->{$approved} + $current_records->{$closed};
		}

		// Unapproved
		if ( ! empty( $args['count_unapproved_records'] ) ) {
			$declined = fct_get_declined_status_id();
			$ctrl['current_unapproved_count'] = $current_records->publish + $current_records->{$declined};
		}

		// Declined
		if ( ! empty( $args['count_declined_records'] ) ) {
			$declined = fct_get_declined_status_id();
			$ctrl['current_declined_count']   = $current_records->{$declined};
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
		$count = fct_get_year_record_count_unapproved( fct_get_current_year_id() );
		
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
				'href'   => add_query_arg( array( 'post_type' => fct_get_record_post_type(), 'approval' => 0 ), admin_url( 'edit.php' ) ),
				'meta'   => array()
			);
		}
	}

	// Declined Records
	if ( current_user_can( 'fiscaat' ) || current_user_can( 'fct_control' ) ) {
		$count = fct_get_year_record_count_declined( fct_get_current_year_id() );
		
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
				'href'   => add_query_arg( array( 'post_type' => fct_get_record_post_type(), 'approval' => 2 ), admin_url( 'edit.php' ) ),
				'meta'   => array()
			);
		}
	}

	return $menu_items;
}

