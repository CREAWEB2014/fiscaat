<?php

/**
 * Fiscaat Admin Functions
 *
 * @package Fiscaat
 * @subpackage Administration
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** Admin Menus ***************************************************************/

/**
 * Add a separator to the WordPress admin menus
 */
function fiscaat_admin_separator() {

	// Prevent duplicate separators when no new menu items exist
	if ( ! current_user_can( 'fiscaat_spectate' ) )
		return;

	// Prevent duplicate separators when no core menu items exist
	if ( ! current_user_can( 'manage_options' ) )
		return;

	global $menu;

	$menu[] = array( '', 'read', 'separator-fiscaat', '', 'wp-menu-separator fiscaat' );
}

/**
 * Tell WordPress we have a custom menu order
 *
 * @param bool $menu_order Menu order
 * @return bool Always true
 */
function fiscaat_admin_custom_menu_order( $menu_order = false ) {
	if ( ! current_user_can( 'fiscaat_spectate' ) )
		return $menu_order;

	return true;
}

/**
 * Move our custom separator above our custom post types
 *
 * @param array $menu_order Menu Order
 * @uses fiscaat_get_year_post_type() To get the year post type
 * @return array Modified menu order
 */
function fiscaat_admin_menu_order( $menu_order ) {

	// Bail if user cannot see any top level Fiscaat menus
	if ( empty( $menu_order ) || ( ! current_user_can( 'fiscaat_spectate' ) ) )
		return $menu_order;

	// Initialize our custom order array
	$fiscaat_menu_order = array();

	// Menu values
	$second_sep   = 'separator2';
	$custom_menus = array(
		'separator-fiscaat',                                     // Separator
		'edit.php?post_type=' . fiscaat_get_year_post_type(),    // Years
		'edit.php?post_type=' . fiscaat_get_account_post_type(), // Accounts
		'edit.php?post_type=' . fiscaat_get_record_post_type()   // Records
	);

	// Loop through menu order and do some rearranging
	foreach ( $menu_order as $item ) {

		// Position Fiscaat menus above appearance
		if ( $second_sep == $item ) {

			// Add our custom menus
			foreach ( $custom_menus as $custom_menu ) {
				if ( array_search( $custom_menu, $menu_order ) ) {
					$fiscaat_menu_order[] = $custom_menu;
				}
			}

			// Add the appearance separator
			$fiscaat_menu_order[] = $second_sep;

		// Skip our menu items
		} elseif ( ! in_array( $item, $custom_menus ) ) {
			$fiscaat_menu_order[] = $item;
		}
	}

	// Return our custom order
	return $fiscaat_menu_order;
}

/**
 * Filter sample permalinks so that certain languages display properly.
 *
 * @param string $post_link Custom post type permalink
 * @param object $_post Post data object
 * @param bool $leavename Optional, defaults to false. Whether to keep post name or page name.
 * @param bool $sample Optional, defaults to false. Is it a sample permalink.
 *
 * @uses is_admin() To make sure we're on an admin page
 * @uses fiscaat_is_custom_post_type() To get the year post type
 *
 * @return string The custom post type permalink
 */
function fiscaat_filter_sample_permalink( $post_link, $_post, $leavename = false, $sample = false ) {

	// Bail if not on an admin page and not getting a sample permalink
	if ( ! empty( $sample ) && is_admin() && fiscaat_is_custom_post_type() )
		return urldecode( $post_link );

	// Return post link
	return $post_link;
}

/**
 * Return whether Fiscaat is being uninstalled
 *
 * @uses WP_UNINSTALL_PLUGIN
 * @return bool Fiscaat is uninstalling
 */
function fiscaat_is_uninstall() {
	return defined( 'WP_UNINSTALL_PLUGIN' ) && fiscaat()->basename == WP_UNINSTALL_PLUGIN;
}

/**
 * Uninstall all Fiscaat options and capabilities from a specific site.
 *
 * @param type $site_id
 */
function fiscaat_do_uninstall( $site_id = 0 ) {
	if ( empty( $site_id ) )
		$site_id = get_current_blog_id();

	switch_to_blog( $site_id );
	fiscaat_delete_options();
	fiscaat_remove_caps();
	flush_rewrite_rules();
	restore_current_blog();
}

/**
 * This tells WP to highlight the Tools > Years menu item,
 * regardless of which actual Fiscaat Tools screen we are on.
 *
 * The conditional prevents the override when the user is viewing settings or
 * any third-party plugins.
 *
 * @global string $plugin_page
 * @global array $submenu_file
 */
function fiscaat_tools_modify_menu_highlight() {
	global $plugin_page, $submenu_file;

	// This tweaks the Tools subnav menu to only show one Fiscaat menu item
	if ( ! in_array( $plugin_page, array( 'fiscaat-settings' ) ) )
		$submenu_file = 'fiscaat-repair';
}

/**
 * Output the tabs in the admin area
 *
 * @param string $active_tab Name of the tab that is active
 */
function fiscaat_tools_admin_tabs( $active_tab = '' ) {
	echo fiscaat_get_tools_admin_tabs( $active_tab );
}

	/**
	 * Output the tabs in the admin area
	 *
	 * @param string $active_tab Name of the tab that is active
	 */
	function fiscaat_get_tools_admin_tabs( $active_tab = '' ) {

		// Declare local variables
		$tabs_html    = '';
		$idle_class   = 'nav-tab';
		$active_class = 'nav-tab nav-tab-active';

		// Setup core admin tabs
		$tabs = apply_filters( 'fiscaat_tools_admin_tabs', array(
			'0' => array(
				'href' => get_admin_url( '', add_query_arg( array( 'page' => 'fiscaat-repair'    ), 'tools.php' ) ),
				'name' => __( 'Repair Fiscaat', 'fiscaat' )
			),
			'1' => array(
				'href' => get_admin_url( '', add_query_arg( array( 'page' => 'fiscaat-converter' ), 'tools.php' ) ),
				'name' => __( 'Import Data', 'fiscaat' )
			),
			'2' => array(
				'href' => get_admin_url( '', add_query_arg( array( 'page' => 'fiscaat-reset'     ), 'tools.php' ) ),
				'name' => __( 'Reset Fiscaat', 'fiscaat' )
			)
		) );

		// Loop through tabs and build navigation
		foreach( $tabs as $tab_id => $tab_data ) {
			$is_current = (bool) ( $tab_data['name'] == $active_tab );
			$tab_class  = $is_current ? $active_class : $idle_class;
			$tabs_html .= '<a href="' . $tab_data['href'] . '" class="' . $tab_class . '">' . $tab_data['name'] . '</a>';
		}

		// Output the tabs
		return $tabs_html;
	}
