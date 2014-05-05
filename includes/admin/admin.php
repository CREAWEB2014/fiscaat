<?php

/**
 * Main Fiscaat Admin Class
 *
 * @package Fiscaat
 * @subpackage Administration
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Fiscaat_Admin' ) ) :

/**
 * Loads Fiscaat plugin admin area
 *
 * @package Fiscaat
 * @subpackage Administration
 */
class Fiscaat_Admin {

	/** Directory *************************************************************/

	/**
	 * @var string Path to the Fiscaat admin directory
	 */
	public $admin_dir = '';

	/** URLs ******************************************************************/

	/**
	 * @var string URL to the Fiscaat admin directory
	 */
	public $admin_url = '';

	/**
	 * @var string URL to the Fiscaat images directory
	 */
	public $images_url = '';

	/**
	 * @var string URL to the Fiscaat admin styles directory
	 */
	public $styles_url = '';

	/** Capability ************************************************************/

	/**
	 * @var string Minimum capability to access Fiscaat pages
	 */
	public $minimum_capability = 'fct_spectate';

	/** Page Type *************************************************************/

	/**
	 * @var string Current admin page object type
	 */
	private $_page_type = null;

	/** Functions *************************************************************/

	/**
	 * The main Fiscaat admin loader
	 *
	 * @uses Fiscaat_Admin::setup_globals() Setup the globals needed
	 * @uses Fiscaat_Admin::includes() Include the required files
	 * @uses Fiscaat_Admin::setup_actions() Setup the hooks and actions
	 */
	public function __construct() {
		$this->setup_globals();
		$this->includes();
		$this->setup_actions();
	}

	/**
	 * Admin globals
	 *
	 * @access private
	 */
	private function setup_globals() {
		$fiscaat = fiscaat();

		/** Paths *************************************************************/

		$this->admin_dir    = trailingslashit( $fiscaat->includes_dir . 'admin'    ); // Admin path
		$this->admin_url    = trailingslashit( $fiscaat->includes_url . 'admin'    ); // Admin url

		$this->includes_dir = trailingslashit( $this->admin_dir       . 'includes' ); // Admin includes path
		$this->includes_url = trailingslashit( $this->admin_url       . 'includes' ); // Admin includes url

		$this->images_url   = trailingslashit( $this->admin_url       . 'images'   ); // Admin images URL
		$this->styles_url   = trailingslashit( $this->admin_url       . 'styles'   ); // Admin styles URL

		/** Pages *************************************************************/

		$this->get_page_type();
	}

	/**
	 * Include required files
	 *
	 * @access private
	 */
	private function includes() {
		require( $this->admin_dir . 'accounts.php'  );
		require( $this->admin_dir . 'converter.php' );
		require( $this->admin_dir . 'functions.php' );
		require( $this->admin_dir . 'import.php'    );
		require( $this->admin_dir . 'metaboxes.php' );
		require( $this->admin_dir . 'records.php'   );
		require( $this->admin_dir . 'settings.php'  );
		require( $this->admin_dir . 'tools.php'     );
		require( $this->admin_dir . 'users.php'     );
		require( $this->admin_dir . 'years.php'     );

		// Record new/edit pages
		// require( $this->admin_dir . 'includes/class-fiscaat-admin-records.php' );
		// require( $this->admin_dir . 'records-edit.php' );
		// require( $this->admin_dir . 'records-new.php'  );
	}

	/**
	 * Setup the admin hooks, actions and filters
	 *
	 * @access private
	 *
	 * @uses add_action() To add various actions
	 * @uses add_filter() To add various filters
	 */
	private function setup_actions() {

		// Bail to prevent interfering with the deactivation process
		if ( fct_is_deactivation() )
			return;

		/** General Actions ***************************************************/

		add_action( 'fct_admin_menu',              array( $this, 'admin_menus'                ) ); // Add menu item to settings menu
		add_action( 'fct_admin_head',              array( $this, 'admin_head'                 ) ); // Add some general styling to the admin area
		add_action( 'fct_admin_notices',           array( $this, 'activation_notice'          ) ); // Add notice if not using a Fiscaat theme
		add_action( 'fct_register_admin_settings', array( $this, 'register_admin_settings'    ) ); // Add settings
		add_action( 'fct_activation',              array( $this, 'new_install'                ) ); // Create new content on install
		add_action( 'admin_enqueue_scripts',       array( $this, 'enqueue_scripts'            ) ); // Add enqueued JS and CSS
		add_action( 'wp_dashboard_setup',          array( $this, 'dashboard_widget_right_now' ) ); // Years 'Right now' Dashboard widget

		/** Development *******************************************************/

		add_action( 'fct_admin_init', array( $this, 'delete_content' ) );

		/** Redirect **********************************************************/

		add_action( 'load-edit.php',               array( $this, 'redirect_edit_pages' ), 0 );

		/** Ajax **************************************************************/

		add_action( 'wp_ajax_fct_suggest_account', array( $this, 'suggest_account'     )    );

		/** Filters ***********************************************************/

		add_filter( 'plugin_action_links', array( $this, 'modify_plugin_action_links' ), 10, 2 ); // Modify Fiscaat's admin links
		add_filter( 'fct_map_meta_caps',   array( $this, 'map_settings_meta_caps'     ), 10, 4 ); // Map settings capabilities

		/** Dependencies ******************************************************/

		// Allow plugins to modify these actions
		do_action_ref_array( 'fct_admin_loaded', array( &$this ) );
	}

	/**
	 * Dev only: delete or add content
	 */
	public function delete_content() {

		// Bail if no add_content query arg
		if ( isset( $_GET['add_content'] ) && $_GET['add_content'] ) {
			fct_create_initial_content();
		}

		// Bail if no del_content query arg
		if ( isset( $_GET['del_content'] ) && $_GET['del_content'] ) {

			// Delete all accounts
			foreach ( get_posts( array( 'post_type' => fct_get_account_post_type(), 'fields' => 'ids', 'numberposts' => -1 ) ) as $post_id ) {
				wp_delete_post( $post_id, true ); // force delete
			}

			// Delete all years
			foreach ( get_posts( array( 'post_type' => fct_get_year_post_type(), 'fields' => 'ids', 'numberposts' => -1 ) ) as $post_id ) {
				wp_delete_post( $post_id, true ); // force delete
			}
		}
	}

	/**
	 * Return the Fiscaat admin page type
	 *
	 * Based on 'page' query parameter, to identify required post type.
	 *
	 * @since 0.0.7
	 * 
	 * @return string|bool Page type. Either false, 'record', 'account' or 'year'
	 */
	public function get_page_type() {

		// Set page type if unknown
		if ( null === $this->_page_type ) {
			$type = false;

			// Only for Fiscaat admin post pages
			if ( isset( $_REQUEST['page'] ) ) {
				switch ( $_REQUEST['page'] ) {
					case 'fct-records' :
						$type = 'record';
						break;
					case 'fct-accounts' :
						$type = 'account';
						break;
					case 'fct-years' :
						$type = 'year';
						break;
				}
			}

			$this->_page_type = $type;
		}

		return $this->_page_type;
	}

	/**
	 * Add the admin menus
	 *
	 * @uses add_menu_page() To add the Fiscaat Root page
	 * @uses add_submenu_page() To add the various Fiscaat submenu pages
	 * @uses add_management_page() To add the Recount page in Tools section
	 */
	public function admin_menus() {
		$hooks = array();

		// Fiscaat pages
		if ( current_user_can( 'fct_spectate' ) ) {

			// Fiscaat Core Root
			add_menu_page(
				__( 'Fiscaat', 'fiscaat' ),
				__( 'Fiscaat', 'fiscaat' ),
				$this->minimum_capability,
				'fiscaat',
				'fct_settings_page',
				'dashicons-vault',
				333333
			);

			// Accounts
			$hooks[] = add_submenu_page(
				'fiscaat',
				__( 'Accounts', 'fiscaat' ),
				__( 'Accounts', 'fiscaat' ),
				$this->minimum_capability,
				'fct-accounts',
				'fct_admin_posts_page'
			);

			// Records
			$hooks[] = add_submenu_page(
				'fiscaat',
				__( 'Records', 'fiscaat' ),
				__( 'Records', 'fiscaat' ),
				$this->minimum_capability,
				'fct-records',
				'fct_admin_posts_page'
			);

			// Years
			$hooks[] = add_submenu_page(
				'fiscaat',
				__( 'Years', 'fiscaat' ),
				__( 'Years', 'fiscaat' ),
				$this->minimum_capability,
				'fct-years',
				'fct_admin_posts_page'
			);

			// Reports
			add_submenu_page(
				'fiscaat',
				__( 'Reports', 'fiscaat' ),
				__( 'Reports', 'fiscaat' ),
				$this->minimum_capability,
				'fct-reports',
				'fct_admin_reports'
			);

			// Are settings enabled?
			if ( current_user_can( 'fct_settings_page' ) ) {
				add_submenu_page(
					'fiscaat',
					__( 'Settings',  'fiscaat' ),
					__( 'Settings',  'fiscaat' ),
					'fct_settings_page',
					'fct-settings',
					'fct_admin_settings'
				);
			}

			// Setup page specific hooks
			foreach ( $hooks as $k => $hook ) {
				add_action( "load-$hook",        array( $this, 'setup_edit_posts' ), 0 );
				add_action( "load-post-new.php", array( $this, 'setup_new_posts'  ), 0 );
				unset( $hooks[ $k ] );
			}
		}

		// Tools pages. These are later removed in admin_head
		if ( current_user_can( 'fct_tools_page' ) ) {
			if ( current_user_can( 'fct_tools_repair_page' ) ) {
				$hooks[] = add_management_page(
					__( 'Repair Fiscaat', 'fiscaat' ),
					__( 'Fiscaat Repair', 'fiscaat' ),
					'fct_tools_repair_page',
					'fct-repair',
					'fct_admin_repair'
				);
			}

			if ( current_user_can( 'fct_tools_import_page' ) ) {
				$hooks[] = add_management_page(
					__( 'Import Fiscaat', 'fiscaat' ),
					__( 'Fiscaat Import', 'fiscaat' ),
					'fct_tools_import_page',
					'fct-converter',
					'fct_converter_settings'
				);
			}

			if ( current_user_can( 'fct_tools_reset_page' ) ) {
				$hooks[] = add_management_page(
					__( 'Reset Fiscaat', 'fiscaat' ),
					__( 'Fiscaat Reset', 'fiscaat' ),
					'fct_tools_reset_page',
					'fct-reset',
					'fct_admin_reset'
				);
			}

			// Fudge the highlighted subnav item when on a Fiscaat admin page
			foreach( $hooks as $hook ) {
				add_action( "admin_head-$hook", 'fct_tools_modify_menu_highlight' );
			}

			// Fiscaat Tools Root
			add_management_page(
				__( 'Fiscaat', 'fiscaat' ),
				__( 'Fiscaat', 'fiscaat' ),
				'fct_tools_page',
				'fct-repair',
				'fct_admin_repair'
			);
		}
	}

	/**
	 * Setup edit posts page, globals, screen, list table, and hooks
	 *
	 * @since 0.0.7
	 *
	 * @uses fct_admin_get_page_type()
	 * @uses fct_admin_get_page_post_type()
	 * @uses get_post_type_object()
	 * @uses fct_get_list_table()
	 * @uses add_action() Hooks 'fct_admin_load_{$type}s_page' on the current
	 *                     action at priority 10.
	 * @global $post_type
	 * @global $post_type_object
	 * @global $post_new_file
	 * @uses current_filter()
	 */
	public function setup_edit_posts() {
		global $post_type, $post_type_object, $post_new_file, $wp_list_table;

		// Get the current page type. Bail if empty
		$type = fct_admin_get_page_type();
		if ( empty( $type ) )
			return;

		// Set globals
		$post_type        = fct_admin_get_page_post_type();
		$post_type_object = get_post_type_object( $post_type );
		$post_new_file    = fct_admin_get_post_new_file(); 

		/**
		 * set_current_screen() ran previous to this moment without a
		 * correct $typenow variable, so here we run it again. This sets
		 * the $typenow global, among others.
		 */
		set_current_screen( 'edit-' . $post_type );

		// Setup page type list table
		$class         = sprintf( 'FCT_%s_List_Table', ucfirst( $type . 's' ) );
		$wp_list_table = fct_get_list_table( $class, array( 'screen' => get_current_screen() ) );

		// Setup type specific load hook
		add_action( current_filter(), "fct_admin_load_edit_{$type}s" );

		// Load up list table items for this specific hook
		add_action( current_filter(), array( $wp_list_table, 'prepare_items' ), 60 );

		// Display list views
		add_action( 'fct_admin_before_posts_form', array( $wp_list_table, 'views' ), 20 );
	}

	/**
	 * Setup new posts page hooks
	 *
	 * @since 0.0.8
	 * 
	 * @uses fct_get_year_post_type() To get the year post type
	 * @uses fct_get_account_post_type() To get the account post type
	 * @uses fct_get_record_post_type() To get the record post type
	 * @uses fct_get_post_type_type()
	 */
	public function setup_new_posts() {

		// Bail if not a Fiscaat post type
		if ( ! isset( $_GET['post_type'] ) || ! $type = fct_get_post_type_type( $_GET['post_type'] ) )
			return;

		// Setup type specific load hook
		add_action( current_filter(), "fct_admin_load_new_{$type}s" );
	}

	/**
	 * If this is a new installation or no years exists, create some initial Fiscaat content
	 *
	 * @uses fct_has_open_year() To check if an open year exists
	 * @uses fct_create_initial_content() To create initial Fiscaat content
	 */
	public function new_install() {
		if ( fct_has_open_year() )
			return;

		fct_create_initial_content();
	}

	/**
	 * Register the settings
	 *
	 * @uses add_settings_section() To add our own settings section
	 * @uses add_settings_field() To add various settings fields
	 * @uses register_setting() To register various settings
	 * @todo Put fields into multidimensional array
	 */
	public static function register_admin_settings() {

		// Bail if no sections available
		$sections = fct_admin_get_settings_sections();
		if ( empty( $sections ) )
			return false;

		// Loop through sections
		foreach ( (array) $sections as $section_id => $section ) {

			// Only proceed if current user can see this section
			if ( ! current_user_can( $section_id ) )
				continue;

			// Only add section and fields if section has fields
			$fields = fct_admin_get_settings_fields_for_section( $section_id );
			if ( empty( $fields ) )
				continue;

			// Add the section
			add_settings_section( $section_id, $section['title'], $section['callback'], $section['page'] );

			// Loop through fields for this section
			foreach ( (array) $fields as $field_id => $field ) {

				// Add the field
				add_settings_field( $field_id, $field['title'], $field['callback'], $section['page'], $section_id, $field['args'] );

				// Register the setting
				register_setting( $section['page'], $field_id, $field['sanitize_callback'] );
			}
		}
	}

	/**
	 * Maps settings capabilities
	 *
	 * @param array $caps Capabilities for meta capability
	 * @param string $cap Capability name
	 * @param int $user_id User id
	 * @param mixed $args Arguments
	 * @uses apply_filters() Calls 'fct_map_meta_caps' with caps, cap, user id and
	 *                        args
	 * @return array Actual capabilities for meta capability
	 */
	public static function map_settings_meta_caps( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {

		// What capability is being checked?
		switch ( $cap ) {

			// Fisci & Admins
			case 'fct_settings_page' : // Settings Page
				if ( user_can( $user_id, 'fiscaat' ) ) {
					$caps = array( 'fiscaat' );
				} else {
					$caps = array( 'manage_options' );
				}
				break;
				
			// Fisci
			case 'fct_tools_page'        : // Tools Page
			case 'fct_tools_repair_page' : // Tools - Repair Page
			case 'fct_tools_import_page' : // Tools - Import Page
			case 'fct_tools_reset_page'  : // Tools - Reset Page
			case 'fct_settings_currency' : // Settings - General
			case 'fct_settings_features' : // Settings - Features // Really, Fisci can unset Control?
			case 'fct_settings_per_page' : // Settings - Per page
			case 'fct_settings_accounts' : // Settings - Accounts
				$caps = array( 'fiscaat' );
				break;

			// Admins
			case 'fct_settings_root_slugs'   : // Settings - Root slugs
			case 'fct_settings_single_slugs' : // Settings - Single slugs
				$caps = array( 'manage_options' );
				break;
		}

		return apply_filters( 'fct_map_settings_meta_caps', $caps, $cap, $user_id, $args );
	}

	/**
	 * Register the importers
	 *
	 * @uses apply_filters() Calls 'fct_importer_path' filter to allow plugins
	 *                        to customize the importer script locations.
	 */
	public function register_importers() {

		// Leave if we're not in the import section
		if ( ! defined( 'WP_LOAD_IMPORTERS' ) )
			return;

		// Load Importer API
		require_once( ABSPATH . 'wp-admin/includes/import.php' );

		// Load our importers
		$importers = apply_filters( 'fct_importers', array( 'fiscaat' ) );

		// Loop through included importers
		foreach ( $importers as $importer ) {

			// Allow custom importer directory
			$import_dir  = apply_filters( 'fct_importer_path', $this->admin_dir . 'importers', $importer );

			// Compile the importer path
			$import_file = trailingslashit( $import_dir ) . $importer . '.php';

			// If the file exists, include it
			if ( file_exists( $import_file ) ) {
				require( $import_file );
			}
		}
	}

	/**
	 * Admin area activation notice
	 *
	 * Shows a message in admin area about the required steps to setup Fiscaat
	 *
	 * @uses current_user_can() To check notice should be displayed.
	 */
	public function activation_notice() {
		
		// Admins only
		if ( ! current_user_can( 'manage_options' ) )
			return;

		// Show message that nothing is visible yet. Users need to be promoted to Fiscus, Controller or Spectator
	}

	/**
	 * Add extra links to plugins area
	 *
	 * @param array $links Links array in which we would prepend our link
	 * @param string $file Current plugin basename
	 * @return array Processed links
	 */
	public static function modify_plugin_action_links( $links, $file ) {

		// Return normal links if not Fiscaat
		if ( fiscaat()->basename != $file )
			return $links;

		// Settings
		if ( current_user_can( 'fct_settings_page' ) ) {
			$links['settings'] = '<a href="' . add_query_arg( array( 'page' => 'fct-settings' ), admin_url( 'admin.php' ) ) . '">' . esc_html__( 'Settings', 'fiscaat' ) . '</a>';
		}

		return $links;
	}

	/**
	 * Add the 'Right now in Years' dashboard widget
	 *
	 * @uses wp_add_dashboard_widget() To add the dashboard widget
	 */
	public function dashboard_widget_right_now() {

		// Bail if user is not capable
		if ( current_user_can( $this->minimum_capability ) )
			return;

		wp_add_dashboard_widget( 'fct-dashboard-right-now', _x( 'Fiscaat', 'Right now in Fiscaat', 'fiscaat' ), 'fct_dashboard_widget_right_now' );
	}

	/**
	 * Enqueue any admin scripts we might need
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'suggest' );
	}

	/**
	 * Setup menu fixes and add some general styling to the admin area
	 *
	 * @uses fct_get_year_post_type() To get the year post type
	 * @uses fct_get_account_post_type() To get the account post type
	 * @uses fct_get_record_post_type() To get the record post type
	 * @uses sanitize_html_class() To sanitize the classes
	 */
	public function admin_head() {
		global $post_type, $parent_file, $submenu_file;

		// Remove the individual edit post type menus.
		// They are redirected to their respective Fiscaat pages
		$post_types = array( fct_get_record_post_type(), fct_get_account_post_type(), fct_get_year_post_type() );
		foreach ( $post_types as $_post_type ) {
			remove_menu_page( 'edit.php?post_type=' . $_post_type );

			// This tells WP to highlight Fiscaat's toplevel menu and matching posts menu 
			// item, associating any page (post-new.php) with the relevant post type.
			if ( isset( $post_type ) && $post_type == $_post_type ) {
				$parent_file  = 'fiscaat'; // @todo Fix not showing toplevel menu
				$submenu_file = 'fct-' . fct_get_post_type_type( $post_type ) . 's';
			}
		}

		// Remove the Fiscaat submenu
		// It is of no further use.
		remove_submenu_page( 'fiscaat', 'fiscaat' );

		// Remove the individual recount and converter menus.
		// They are grouped together by h2 tabs
		remove_submenu_page( 'tools.php', 'fct-repair'    );
		remove_submenu_page( 'tools.php', 'fct-converter' );
		remove_submenu_page( 'tools.php', 'fct-reset'     );

		// Top level menu classes
		$year_class    = sanitize_html_class( fct_get_year_post_type() );
		$account_class = sanitize_html_class( fct_get_account_post_type() );
		$record_class  = sanitize_html_class( fct_get_record_post_type() ); ?>

		<script type="text/javascript">
			jQuery(document).ready(function() {

				var fct_account_id = jQuery( '#fct_account_id' );

				fct_account_id.suggest( ajaxurl + '?action=fct_suggest_account', {
					onSelect: function() {
						var value = this.value;
						fct_account_id.val( value.substr( 0, value.indexOf( ' ' ) ) );
					}
				} );
			});

			/* Communicate between primary account id and ledger id dropdowns */
			jQuery(document).ready(function($) {
				var dropdowns = [ 
					$( 'select#fct_account_id, select#parent_id' ),
					$( 'select#fct_ledger_account_id, select#fct_record_account_ledger_id' )
				];

				$.each( dropdowns, function( i ){
					var other_dd = ( i == 1 ) ? 0 : 1;

					this.change( function(){
						dropdowns[other_dd].find( 'option[value='+ this.value +']' ).attr( 'selected', true );
					});
				});
			});
		</script>

		<style type="text/css" media="screen">
		/*<![CDATA[*/

			input.medium-text {
				width: 100px;
				padding: 1px 6px;
			}

			span.dashicons-before.fct-icon-success:before {
				content: '\f147'; /* dashicons-yes */
				background: #1DA817;
				color: #fff;
				border-radius: 50%;
				margin-top: 1px;
				border: 1px solid #188114;
				-webkit-box-shadow: inset 0 1px 0 #67D552, 0 1px 0 rgba(0,0,0,.15);
				box-shadow: inset 0 1px 0 #67D552, 0 1px 0 rgba(0,0,0,.15);
			}

			span.dashicons-before.fct-icon-error:before {
				content: '\f335'; /* dashicons-no-alt */
				margin-top: 1px;
				background: #e14d43;
				color: #fff;
				border-radius: 50%;
				border: 1px solid #d02a21;
				-webkit-box-shadow: inset 0 1px 0 #ec8a85, 0 1px 0 rgba(0,0,0,.15);
				box-shadow: inset 0 1px 0 #ec8a85, 0 1px 0 rgba(0,0,0,.15);
			}

			/* Kludge for too-wide years dropdown */
			#poststuff #fct_account_attributes select#parent_id,
			#poststuff #fct_record_attributes select#fct_year_id {
				max-width: 193px;
			}

			/* Kludge for too-wide account dropdown */
			#poststuff #fct_record_attributes select#parent_id,
			#poststuff #fct_record_attributes select#fct_record_account_ledger_id,
			#posts-filter select#fct_account_id {
				max-width: 193px;
			}

			<?php if ( isset( get_current_screen()->id ) && 'dashboard' == get_current_screen()->id ) : ?>

			#fct-dashboard-right-now p.sub,
			#fct-dashboard-right-now .table,
			#fct-dashboard-right-now .versions {
				margin: -12px;
			}

			#fct-dashboard-right-now .inside {
				font-size: 12px;
				padding-top: 20px;
				margin-bottom: 0;
			}

			#fct-dashboard-right-now p.sub {
				padding: 5px 0 15px;
				color: #8f8f8f;
				font-size: 14px;
				position: absolute;
				top: -17px;
				left: 15px;
			}
				body.rtl #fct-dashboard-right-now p.sub {
					right: 15px;
					left: 0;
				}

			#fct-dashboard-right-now .table {
				margin: 0;
				padding: 0;
				position: relative;
			}

			#fct-dashboard-right-now .table_content {
				float: left;
				border-top: #ececec 1px solid;
				width: 45%;
			}
				body.rtl #fct-dashboard-right-now .table_content {
					float: right;
				}

			#fct-dashboard-right-now .table_discussion {
				float: right;
				border-top: #ececec 1px solid;
				width: 45%;
			}
				body.rtl #fct-dashboard-right-now .table_discussion {
					float: left;
				}

			#fct-dashboard-right-now table td {
				padding: 3px 0;
				white-space: nowrap;
			}

			#fct-dashboard-right-now table tr.first td {
				border-top: none;
			}

			#fct-dashboard-right-now td.b {
				padding-right: 6px;
				text-align: right;
				font-family: Georgia, "Times New Roman", "Bitstream Charter", Times, serif;
				font-size: 14px;
				width: 1%;
			}
				body.rtl #fct-dashboard-right-now td.b {
					padding-left: 6px;
					padding-right: 0;
				}

			#fct-dashboard-right-now td.b a {
				font-size: 18px;
			}

			#fct-dashboard-right-now td.b a:hover {
				color: #d54e21;
			}

			#fct-dashboard-right-now .t {
				font-size: 12px;
				padding-right: 12px;
				padding-top: 6px;
				color: #777;
			}
				body.rtl #fct-dashboard-right-now .t {
					padding-left: 12px;
					padding-right: 0;
				}

			#fct-dashboard-right-now .t a {
				white-space: nowrap;
			}

			#fct-dashboard-right-now .spam {
				color: red;
			}

			#fct-dashboard-right-now .waiting {
				color: #e66f00;
			}

			#fct-dashboard-right-now .versions {
				padding: 6px 10px 12px;
				clear: both;
			}

			#fct-dashboard-right-now .versions .b {
				font-weight: bold;
			}

			#fct-dashboard-right-now a.button {
				float: right;
				clear: right;
				position: relative;
				top: -5px;
			}
				body.rtl #fct-dashboard-right-now a.button {
					float: left;
					clear: left;
				}

			<?php endif; // Dashboard ?>

		/*]]>*/
		</style>

		<?php
	}

	/** Redirect **************************************************************/

	/**
	 * Redirect from edit.php to Fiscaat's own post type page
	 *
	 * @since 0.0.8
	 *
	 * @uses fct_get_year_post_type() To get the year post type
	 * @uses fct_get_account_post_type() To get the account post type
	 * @uses fct_get_record_post_type() To get the record post type
	 * @uses fct_get_post_type_type()
	 * @uses wp_redirect()
	 */
	public function redirect_edit_pages() {

		// Bail if not a Fiscaat post type
		if ( ! isset( $_GET['post_type'] ) || ! in_array( $_GET['post_type'], array(
				fct_get_record_post_type(),
				fct_get_account_post_type(),
				fct_get_year_post_type()
			) ) )
			return;

		$type = fct_get_post_type_type( $_GET['post_type'] );
		wp_redirect( add_query_arg( 'page', "fct-{$type}s", admin_url( 'admin.php' ) ) );
		exit;
	}

	/** Ajax ******************************************************************/

	/**
	 * Ajax action for facilitating the account auto-suggest
	 *
	 * @uses get_posts()
	 * @uses fct_get_account_post_type()
	 * @uses fct_get_account_id()
	 * @uses fct_get_account_title()
	 */
	public function suggest_account() {

		// Try to get some accounts
		$accounts = get_posts( array(
			's'         => like_escape( $_REQUEST['q'] ),
			'post_type' => fct_get_account_post_type()
		) );

		// If we found some accounts, loop through and display them
		if ( ! empty( $accounts ) ) {
			foreach ( (array) $accounts as $post ) {
				echo sprintf( __( '%s - %s', 'fiscaat' ), fct_get_account_id( $post->ID ), fct_get_account_title( $post->ID ) ) . "\n";
			}
		}

		die();
	}
}

endif; // class_exists check

/**
 * Setup Fiscaat Admin
 *
 * @uses Fiscaat_Admin
 * @uses Fiscaat_Converter
 */
function fct_admin() {
	fiscaat()->admin = new Fiscaat_Admin();
	fiscaat()->admin->converter = new Fiscaat_Converter();
}
