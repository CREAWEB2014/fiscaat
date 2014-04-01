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

	return apply_filters( 'fct_get_total_controllers', (int) $user_count['avail_roles'][$role] );
}

/**
 * Add the controller count to fiscaat statistics
 *
 * @uses fct_get_total_controllers()
 * @param array $stats
 * @param array $args
 * @return array Statistics
 */
function fct_ctrl_get_statistics( $stats, args ) {

	// Counting users
	if ( $args['count_users'] ) {
		$count = fct_get_total_controllers();
		$stats['controller_count'] = number_format_i18n( absint( $count ) )
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

	// Control Unapproved node
	if ( current_user_can( 'fct_control' ) ) {	
		$menu_items['fiscaat-control'] = array(
			'title'  => sprintf( __('Unapproved Records (%d)', 'fiscaat'), fct_get_year_record_count_unapproved( fct_get_current_year_id() ) ),
			'parent' => 'fiscaat',
			'href'   => add_query_arg( array( 'post_type' => fct_get_record_post_type(), 'approval' => 0 ), admin_url( 'edit.php' ) ),
			'meta'   => array()
		);
	}

	// Control Declined node. Only if there are any
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

