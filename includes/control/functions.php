<?php

/**
 * Fiscaat Control Functions
 *
 * @package Fiscaat
 * @subpackage Functions
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

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
function fct_ctrl_record_statuses( $options ) {

	// Insert options after 'publish' and before 'close'
	$options = array_splice( $options, 1, 0, array(
		fct_get_approved_status_id() => __('Approved', 'fiscaat'),
		fct_get_declined_status_id() => __('Declined', 'fiscaat'),
	) );

	return $options;
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

/**
 * Get the total number of controllers on Fiscaat
 *
 * @uses count_users() To execute our query and get the var back
 * @uses apply_filters() Calls 'fct_get_total_controllers' with number of controllers
 * @return int Total number of controllers
 */
function fct_get_total_controllers() {
	$user_count = count_users();
	$role       = fct_get_controller_role();

	// Check for Controllers
	if ( ! isset( $user_count['avail_roles'][$role] ) )
		return 0;

	return (int) apply_filters( 'fct_get_total_controllers', (int) $user_count['avail_roles'][$role] );
}

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

/**
 * Output the dashboard widget right now control content
 *
 * @uses fct_get_total_controllers()
 */
function fct_ctrl_dashboard_widget_right_now_content() {

	// Get controller count
	$controller_count = fct_get_total_controllers(); ?>

		<tr>
			<?php
				$num  = $controller_count;
				$text = _n( 'Controller', 'Controllers', $controller_count, 'fiscaat' );
			?>

			<td class="first b b-users"><?php echo $num; ?></td>
			<td class="t users"><?php echo $text; ?></td>
		</tr>

	<?php	
}

/**
 * Add control admin bar menu items
 *
 * @param array $menu_items
 * @return array
 */
function fct_ctrl_admin_bar_menu( $menu_items ) {

	// Unapproved records node
	if ( current_user_can( 'fct_control' ) ) {	
		$menu_items['fiscaat-control'] = array(
			'title'  => sprintf( __('Unapproved Records (%d)', 'fiscaat'), fct_get_year_record_count_unapproved( fct_get_current_year_id() ) ),
			'parent' => 'fiscaat',
			'href'   => add_query_arg( array( 'post_type' => fct_get_record_post_type(), 'approval' => 0 ), admin_url( 'edit.php' ) ),
			'meta'   => array()
		);
	}

	// Declined records node, only if there are any
	if ( ( current_user_can( 'fiscaat' ) || current_user_can( 'fct_control' ) ) && 0 != fct_get_year_record_count_declined( fct_get_current_year_id() ) {
		$menu_items['fiscaat-declined'] = array(
			'title'  => sprintf( __('Declined Records (%d)', 'fiscaat'), fct_get_year_record_count_declined( fct_get_current_year_id() ) ),
			'parent' => 'fiscaat',
			'href'   => add_query_arg( array( 'post_type' => fct_get_record_post_type(), 'approval' => 2 ), admin_url( 'edit.php' ) ),
			'meta'   => array()
		);
	}

	return $menu_items;
}

