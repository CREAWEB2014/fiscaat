<?php

/**
 * Fiscaat Admin Functions
 *
 * @package Fiscaat
 * @subpackage Administration
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Menu ******************************************************************/

/**
 * Add a separator to the WordPress admin menus
 */
function fct_admin_separator() {
	global $menu;

	// Prevent duplicate separators when no new menu items exist
	if ( ! current_user_can( fiscaat()->admin->minimum_capability ) )
		return;

	$menu[] = array( '', 'read', 'separator-fiscaat', '', 'wp-menu-separator fiscaat' );
}

/**
 * Tell WordPress we have a custom menu order
 *
 * @param bool $menu_order Menu order
 * @return bool Always true
 */
function fct_admin_custom_menu_order( $menu_order = false ) {
	if ( ! current_user_can( fiscaat()->admin->minimum_capability ) )
		return $menu_order;

	return true;
}

/**
 * Move our custom separator above our custom admin page
 *
 * @param array $menu_order Menu Order
 * @uses fct_get_period_post_type() To get the period post type
 * @return array Modified menu order
 */
function fct_admin_menu_order( $menu_order ) {

	// Bail if user cannot see any top level Fiscaat menus
	if ( empty( $menu_order ) || ! current_user_can( fiscaat()->admin->minimum_capability ) )
		return $menu_order;

	// Initialize our custom order array
	$fct_menu_order = array();

	// Menu values
	$wp_core_sep  = 'separator2';
	$custom_menus = array(
		'separator-fiscaat', // Separator
		'fiscaat'            // Fiscaat
	);

	// Loop through menu order and do some rearranging
	foreach ( $menu_order as $item ) {

		// Position Fiscaat menus above appearance
		if ( $wp_core_sep == $item ) {

			// Add our custom menus
			foreach ( $custom_menus as $custom_menu ) {
				if ( array_search( $custom_menu, $menu_order ) ) {
					$fct_menu_order[] = $custom_menu;
				}
			}

			// Add the appearance separator
			$fct_menu_order[] = $wp_core_sep;

		// Skip our menu items
		} elseif ( ! in_array( $item, $custom_menus ) ) {
			$fct_menu_order[] = $item;
		}
	}

	// Return our custom order
	return $fct_menu_order;
}

/**
 * This tells WP to highlight the Tools > Fiscaat menu item,
 * regardless of which actual Fiscaat Tools screen we are on.
 *
 * The conditional prevents the override when the user is viewing settings or
 * any third-party plugins.
 *
 * @global string $plugin_page
 * @global array $submenu_file
 */
function fct_tools_modify_menu_highlight() {
	global $plugin_page, $submenu_file;

	// This tweaks the Tools subnav menu to only show one Fiscaat menu item
	if ( ! in_array( $plugin_page, array( 'fct-settings' ) ) )
		$submenu_file = 'fct-repair';
}

/** Permalink *************************************************************/

/**
 * Filter sample permalinks so that certain languages display properly.
 *
 * @param string $post_link Custom post type permalink
 * @param object $_post Post data object
 * @param bool $leavename Optional, defaults to false. Whether to keep post name or page name.
 * @param bool $sample Optional, defaults to false. Is it a sample permalink.
 *
 * @uses is_admin() To make sure we're on an admin page
 * @uses fct_is_custom_post_type() To get the period post type
 *
 * @return string The custom post type permalink
 */
function fct_filter_sample_permalink( $post_link, $_post, $leavename = false, $sample = false ) {

	// Bail if not on an admin page and not getting a sample permalink
	if ( ! empty( $sample ) && is_admin() && fct_is_custom_post_type() )
		return urldecode( $post_link );

	// Return post link
	return $post_link;
}

/** Uninstall *************************************************************/

/**
 * Return whether Fiscaat is being uninstalled
 *
 * @uses WP_UNINSTALL_PLUGIN
 * @return bool Fiscaat is uninstalling
 */
function fct_is_uninstall() {
	return defined( 'WP_UNINSTALL_PLUGIN' ) && fiscaat()->basename == WP_UNINSTALL_PLUGIN;
}

/**
 * Uninstall all Fiscaat options and capabilities from a specific site.
 *
 * @param type $site_id
 */
function fct_do_uninstall( $site_id = 0 ) {
	if ( empty( $site_id ) )
		$site_id = get_current_blog_id();

	switch_to_blog( $site_id );
	fct_delete_options();
	fct_remove_caps();
	flush_rewrite_rules();
	restore_current_blog();
}

/** Tools *****************************************************************/

/**
 * Output the tabs in the admin area
 *
 * @param string $active_tab Name of the tab that is active
 */
function fct_tools_admin_tabs( $active_tab = '' ) {
	echo fct_get_tools_admin_tabs( $active_tab );
}

	/**
	 * Output the tabs in the admin area
	 *
	 * @param string $active_tab Name of the tab that is active
	 */
	function fct_get_tools_admin_tabs( $active_tab = '' ) {

		// Declare local variables
		$tabs_html    = '';
		$idle_class   = 'nav-tab';
		$active_class = 'nav-tab nav-tab-active';

		// Setup core admin tabs
		$tabs = apply_filters( 'fct_tools_admin_tabs', array(
			'0' => array(
				'href' => get_admin_url( '', add_query_arg( array( 'page' => 'fct-repair'    ), 'tools.php' ) ),
				'name' => __( 'Repair Fiscaat', 'fiscaat' )
			),
			'1' => array(
				'href' => get_admin_url( '', add_query_arg( array( 'page' => 'fct-converter' ), 'tools.php' ) ),
				'name' => __( 'Import Data', 'fiscaat' )
			),
			'2' => array(
				'href' => get_admin_url( '', add_query_arg( array( 'page' => 'fct-reset'     ), 'tools.php' ) ),
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

/** Posts Pages ***********************************************************/

/**
 * Return the admin page type
 *
 * @since 0.0.7
 *
 * @return string The admin page type
 */
function fct_admin_get_page_object_type() {
	return fiscaat()->admin->get_page_object_type();
}

/**
 * Return the admin page post type
 *
 * @since 0.0.7
 *
 * @uses fct_admin_get_page_object_type()
 * @uses fct_get_record_post_type()
 * @uses fct_get_account_post_type()
 * @uses fct_get_period_post_type()
 * @return string The admin page post type
 */
function fct_admin_get_page_post_type() {
	$type = fct_admin_get_page_object_type();

	// Return Fiscaat post type
	if ( function_exists( "fct_get_{$type}_post_type" ) ) {
		return call_user_func( "fct_get_{$type}_post_type" );

	// Default to usable 'post'
	} else {
		return 'post';
	}
}

/**
 * Return the post new file for the current post type
 *
 * @since 0.0.8
 *
 * @uses fct_get_record_post_type()
 * @uses fct_admin_get_new_records_mode()
 * @param string $post_type Optional. Post type name
 * @return string Post new file
 */
function fct_admin_get_post_new_file( $post_type = '' ) {

	// Fallback to global post type
	if ( empty( $post_type ) && isset( $GLOBALS['post_type'] ) ) {
		$post_type = $GLOBALS['post_type'];
	}

	// Records are treated differently
	if ( fct_get_record_post_type() == $post_type ) {
		$file = 'admin.php?page=fct-records&mode=' . fct_admin_get_new_records_mode();
	} else {
		$file = "post-new.php?post_type=$post_type";
	}

	return $file;
}

/**
 * Setup and return a posts list table
 *
 * @since 0.0.7
 *
 * @param string $class The type of the list table, which is the class name.
 * @param array $args Optional. Arguments to pass to the class.
 * @return object|bool Object on success, false if the class does not exist.
 */
function fct_get_list_table( $class, $args = array() ) {
	$classes = apply_filters( 'fct_get_list_table_classes', array(
		'FCT_Records_List_Table'  => array( 'fct-posts', 'fct-records'  ),
		'FCT_Accounts_List_Table' => array( 'fct-posts', 'fct-accounts' ),
		'FCT_Periods_List_Table'  => array( 'fct-posts', 'fct-periods'  ),
	) );

	if ( isset( $classes[ $class ] ) ) {
		foreach ( (array) $classes[ $class ] as $required ) {

			// Load WP core list table
			if ( false !== strpos( $required, 'wp-' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/class-' . $required . '-list-table.php' );

			// Load Fiscaat core list table
			} elseif ( false !== strpos( $required, 'fct-' ) ) {
				require_once( fiscaat()->admin->includes_dir . 'class-' . $required . '-list-table.php' );

			// Load custom list table
			} elseif ( ( $file = apply_filters( 'fct_get_list_table_file', $required, $class, $args ) ) && file_exists( $file ) ) {
				require_once( $file );
			}
		}

		if ( isset( $args['screen'] ) )
			$args['screen'] = convert_to_screen( $args['screen'] );
		elseif ( isset( $GLOBALS['hook_suffix'] ) )
			$args['screen'] = get_current_screen();
		else
			$args['screen'] = null;

		return new $class( $args );
	}

	return false;
}

/**
 * Display admin list table for posts page
 *
 * @since 0.0.7
 *
 * @uses fct_admin_get_page_object_type()
 * @uses fct_admin_page_title()
 * @uses do_action() Calls 'fct_admin_pre_posts_form'
 * @uses do_action() Calls 'fct_admin_post_posts_form'
 */
function fct_admin_posts_page() {
	global $wp_list_table, $post_type_object; ?>

	<div class="wrap">
		<h2><?php fct_admin_page_title(); ?></h2>

		<?php do_action( "fct_admin_before_posts_form" ); ?>

		<form id="posts-filter" action="" method="get">

			<input type="hidden" name="page" class="post_page" value="<?php echo ! empty($_REQUEST['page']) ? esc_attr($_REQUEST['page']) : 'fiscaat'; ?>" />
			<input type="hidden" name="post_status" class="post_status_page" value="<?php echo ! empty($_REQUEST['post_status']) ? esc_attr($_REQUEST['post_status']) : ''; ?>" />
			<?php $wp_list_table->search_box( $post_type_object->labels->search_items, 'post' ); ?>

			<?php $wp_list_table->display(); ?>

		</form>

		<?php do_action( "fct_admin_after_posts_form" ); ?>

		<div id="ajax-response"></div>
		<br class="clear" />
	</div>

	<?php
}

/**
 * Output the admin page title
 *
 * @since 0.0.7
 *
 * @uses fct_admin_get_page_title()
 */
function fct_admin_page_title() {
	echo fct_admin_get_page_title();
}
	/**
	 * Return the admin page title
	 *
	 * @since 0.0.7
	 *
	 * @uses fct_admin_get_page_object_type()
	 * @uses apply_filters() Calls 'fct_admin_{$type}s_page_title' with
	 *                        the page title
	 * @return string Admin page title
	 */
	function fct_admin_get_page_title() {
		global $post_type_object;
		$type = fct_admin_get_page_object_type();

		// Filter object page specific
		return apply_filters( "fct_admin_{$type}s_page_title", esc_html( $post_type_object->labels->name ) );
	}

/**
 * Append add-new post button to the posts page title
 *
 * @since 0.0.8
 *
 * @param string $titel Page title
 * @return string Page title
 */
function fct_admin_page_title_add_new( $title ) {
	global $post_type_object, $post_new_file;

	// Only if user can create posts
	if ( current_user_can( $post_type_object->cap->create_posts ) ) {
		$title .= ' <a href="'. esc_url( admin_url( $post_new_file ) ) . '" class="add-new-h2">' . esc_html( $post_type_object->labels->add_new ) . '</a>';
	}

	return $title;
}

/**
 * Append search terms to the posts page title when a search query is performed
 *
 * @since 0.0.9
 *
 * @param string $title Page title
 * @return string Page title
 */
function fct_admin_page_title_search_terms( $title ) {

	// Append search terms if available
	if ( ! empty( $_REQUEST['s'] ) ) {
		$title .= sprintf( ' <span class="subtitle">' . __( 'Search results for &#8220;%s&#8221;' ) . '</span>', get_search_query() );
	}

	return $title;
}

/** Records Mode **********************************************************/

/**
 * Return the current records mode
 *
 * @since 0.0.8
 *
 * @uses get_current_screen()
 * @uses fct_admin_get_new_records_mode()
 * @uses fct_admin_get_edit_records_mode()
 * @uses fct_admin_get_view_records_mode()
 * @return string Records mode. Either new, edit, view or empty
 */
function fct_admin_get_records_mode() {

	// Page has record's post type and is in mode
	if ( isset( get_current_screen()->post_type ) && ( fct_get_record_post_type() == get_current_screen()->post_type )
		&& isset( $_GET['mode'] ) && in_array( $_GET['mode'], array(
			fct_admin_get_new_records_mode(),
			fct_admin_get_edit_records_mode(),
			fct_admin_get_view_records_mode()
	) ) ) {
		$mode = $_GET['mode'];

	// Default empty (view)
	} else {
		$mode = '';
	}

	return $mode;
}

/**
 * Return the post-new records mode
 *
 * @since 0.0.8
 *
 * @uses apply_filters() Calls 'fct_admin_get_new_records_mode' with the new mode
 * @return string Records post-new mode
 */
function fct_admin_get_new_records_mode() {
	return apply_filters( 'fct_admin_get_new_records_mode', 'new' );
}

/**
 * Return the edit records mode
 *
 * @since 0.0.8
 *
 * @uses apply_filters() Calls 'fct_admin_get_edit_records_mode' with the edit mode
 * @return string Records edit mode
 */
function fct_admin_get_edit_records_mode() {
	return apply_filters( 'fct_admin_get_edit_records_mode', 'edit' );
}

/**
 * Return the view records mode
 *
 * @since 0.0.8
 *
 * @uses apply_filters() Calls 'fct_admin_get_view_records_mode' with the view mode
 * @return string Records view mode
 */
function fct_admin_get_view_records_mode() {
	return apply_filters( 'fct_admin_get_view_records_mode', 'view' );
}

/**
 * Return whether the current page is the records post-new mode
 *
 * @since 0.0.8
 *
 * @uses fct_admin_get_new_records_mode()
 * @uses fct_admin_get_records_mode()
 * @uses current_user_can() To check if the current user can create records
 * @return bool Page is records post-new mode
 */
function fct_admin_is_new_records() {
	return fct_admin_get_new_records_mode() == fct_admin_get_records_mode() && current_user_can( 'create_records' );
}

/**
 * Return whether the current page is the records edit mode
 *
 * @since 0.0.8
 *
 * @uses fct_admin_get_edit_records_mode()
 * @uses fct_admin_get_records_mode()
 * @uses current_user_can() To check if the current user can edit records
 * @return bool Page is records edit mode
 */
function fct_admin_is_edit_records() {
	return fct_admin_get_edit_records_mode() == fct_admin_get_records_mode() && current_user_can( 'edit_records' );
}

/**
 * Return whether the current page is the records edit mode
 *
 * @since 0.0.8
 *
 * @uses fct_admin_is_new_records()
 * @uses fct_admin_is_edit_records()
 * @return bool Page is records edit mode
 */
function fct_admin_is_view_records() {
	return ! fct_admin_is_new_records() && ! fct_admin_is_edit_records();
}
