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
	 * @var bool Minimum capability to access Fiscaat pages
	 */
	public $minimum_capability = 'fct_spectate';

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
		$this->admin_dir  = trailingslashit( $fiscaat->includes_dir . 'admin'  ); // Admin path
		$this->admin_url  = trailingslashit( $fiscaat->includes_url . 'admin'  ); // Admin url
		$this->images_url = trailingslashit( $this->admin_url       . 'images' ); // Admin images URL
		$this->styles_url = trailingslashit( $this->admin_url       . 'styles' ); // Admin styles URL
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
		require( $this->admin_dir . 'includes/class-fiscaat-admin-records.php' );
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

		/** Ajax **************************************************************/

		add_action( 'wp_ajax_fct_suggest_account', array( $this, 'suggest_account' ) );

		/** Filters ***********************************************************/

		// Modify Fiscaat's admin links
		add_filter( 'plugin_action_links', array( $this, 'modify_plugin_action_links' ), 10, 2 );

		// Map settings capabilities
		add_filter( 'fct_map_meta_caps',   array( $this, 'map_settings_meta_caps'     ), 10, 4 );

		/** Dependencies ******************************************************/

		// Allow plugins to modify these actions
		do_action_ref_array( 'fct_admin_loaded', array( &$this ) );
	}

	/**
	 * Add the admin menus
	 *
	 * @uses add_management_page() To add the Recount page in Tools section
	 * @uses add_options_page() To add the Years settings page in Settings
	 *                           section
	 */
	public function admin_menus() {

		$hooks = array();

		// Fiscaat menu
		if ( current_user_can( 'fct_spectate' ) ) {

			// Parent menu item
			add_menu_page(
				__( 'Fiscaat', 'fiscaat' ),
				__( 'Fiscaat', 'fiscaat' ),
				$this->minimum_capability,
				'fiscaat',
				'',
				'dashicons-vault',
				333333
			);

			// Accounts
			add_submenu_page(
				'fiscaat',
				__( 'General Ledger', 'fiscaat' ),
				__( 'General Ledger', 'fiscaat' ),
				$this->minimum_capability,
				'edit.php?post_type=' . fct_get_account_post_type()
			);

			// Records
			add_submenu_page(
				'fiscaat',
				__( 'Manage Records', 'fiscaat' ),
				__( 'Manage Records', 'fiscaat' ),
				$this->minimum_capability,
				'edit.php?post_type=' . fct_get_record_post_type()
			);

			// Years
			add_submenu_page(
				'fiscaat',
				__( 'Fiscaat Years', 'fiscaat' ),
				__( 'View Years', 'fiscaat' ),
				$this->minimum_capability,
				'edit.php?post_type=' . fct_get_year_post_type()
			);

			// Balance
			add_submenu_page(
				'fiscaat',
				__( 'Balance', 'fiscaat' ),
				__( 'Balance', 'fiscaat' ),
				$this->minimum_capability,
				'fct-balance',
				'fct_admin_balance'
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
					__( 'Settings',  'fiscaat' ),
					__( 'Settings',  'fiscaat' ),
					$this->minimum_capability,
					'fct-settings',
					'fct_admin_settings'
				);
			}
		}

		// Tools pages. These are later removed in admin_head
		if ( current_user_can( 'fct_tools_page' ) ) {
			if ( current_user_can( 'fct_tools_repair_page' ) ) {
				$hooks[] = add_management_page(
					__( 'Repair Fiscaat', 'fiscaat' ),
					__( 'Fiscaat Repair', 'fiscaat' ),
					$this->minimum_capability,
					'fiscaat-repair',
					'fct_admin_repair'
				);
			}

			if ( current_user_can( 'fct_tools_import_page' ) ) {
				$hooks[] = add_management_page(
					__( 'Import Fiscaat', 'fiscaat' ),
					__( 'Fiscaat Import', 'fiscaat' ),
					$this->minimum_capability,
					'fiscaat-converter',
					'fct_converter_settings'
				);
			}

			if ( current_user_can( 'fct_tools_reset_page' ) ) {
				$hooks[] = add_management_page(
					__( 'Reset Fiscaat', 'fiscaat' ),
					__( 'Fiscaat Reset', 'fiscaat' ),
					$this->minimum_capability,
					'fiscaat-reset',
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
				$this->minimum_capability,
				'fiscaat-repair',
				'fct_admin_repair'
			);
		}

	}

	/**
	 * If this is a new installation or no years exists, create some initial Fiscaat content
	 *
	 * @uses fct_has_open_year() To check if an open year exists
	 * @uses fct_create_initial_content() To create initial Fiscaat content
	 */
	public static function new_install() {
		if ( ! fct_is_install() )
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
	 * @uses get_post() To get the post
	 * @uses get_post_type_object() To get the post type object
	 * @uses apply_filters() Calls 'fct_map_meta_caps' with caps, cap, user id and
	 *                        args
	 * @return array Actual capabilities for meta capability
	 */
	public static function map_settings_meta_caps( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {

		// What capability is being checked?
		switch ( $cap ) {

			// Fisci & Admins
			case 'fct_settings_page'          : // Settings Page

				// Fisci
				if ( user_can( $user_id, 'fiscaat' ) ) {
					$caps = array( 'fiscaat' );
				
				// Admins
				} else {
					$caps = array( 'manage_options' );
				}

				break;
				
			// Fisci
			case 'fct_tools_page'             : // Tools Page
			case 'fct_tools_repair_page'      : // Tools - Repair Page
			case 'fct_tools_import_page'      : // Tools - Import Page
			case 'fct_tools_reset_page'       : // Tools - Reset Page
			case 'fct_settings_currency'      : // Settings - General
			case 'fct_settings_functionality' : // Settings - Functionality // Really, Fisci can unset Control?
			case 'fct_settings_per_page'      : // Settings - Per page
			case 'fct_settings_accounts'      : // Settings - Accounts
				$caps = array( 'fiscaat' );
				break;

			// Admins
			case 'fct_settings_root_slugs'    : // Settings - Root slugs
			case 'fct_settings_single_slugs'  : // Settings - Single slugs
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
		if ( ! current_user_can( 'administrator' ) )
			return;

		// Show message that nothing is visible yet. Users need to be promoted to Fiscus, Controller or Spectator
	}

	/**
	 * Add Settings link to plugins area
	 *
	 * @param array $links Links array in which we would prepend our link
	 * @param string $file Current plugin basename
	 * @return array Processed links
	 */
	public static function modify_plugin_action_links( $links, $file ) {

		// Return normal links if not Fiscaat
		if ( plugin_basename( fiscaat()->file ) != $file )
			return $links;

		// Add a few links to the existing links array
		return array_merge( $links, array(
			'settings' => '<a href="' . add_query_arg( array( 'page' => 'fiscaat' ), admin_url( 'options-general.php' ) ) . '">' . esc_html__( 'Settings', 'fiscaat' ) . '</a>',
		) );
	}

	/**
	 * Add the 'Right now in Years' dashboard widget
	 *
	 * @uses wp_add_dashboard_widget() To add the dashboard widget
	 */
	public static function dashboard_widget_right_now() {
		wp_add_dashboard_widget( 'fiscaat-dashboard-right-now', _x( 'Fiscaat', 'Right now in Fiscaat', 'fiscaat' ), 'fct_dashboard_widget_right_now' );
	}

	/**
	 * Enqueue any admin scripts we might need
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'suggest' );
	}

	/**
	 * Add some general styling to the admin area
	 *
	 * @uses fct_get_year_post_type() To get the year post type
	 * @uses fct_get_account_post_type() To get the account post type
	 * @uses fct_get_record_post_type() To get the record post type
	 * @uses sanitize_html_class() To sanitize the classes
	 */
	public function admin_head() {

		// Remove the individual recount and converter menus.
		// They are grouped together by h2 tabs
		remove_submenu_page( 'tools.php', 'fiscaat-repair'    );
		remove_submenu_page( 'tools.php', 'fiscaat-converter' );
		remove_submenu_page( 'tools.php', 'fiscaat-reset'     );

		// The /wp-admin/images/ folder
		$wp_admin_url     = admin_url( 'images/' );

		// Icons for top level admin menus
		$version          = fct_get_version();
		$menu_icon_url    = $this->images_url . 'menu.png?ver='       . $version;
		$icon32_url       = $this->images_url . 'icons32.png?ver='    . $version;
		$menu_icon_url_2x = $this->images_url . 'menu-2x.png?ver='    . $version;
		$icon32_url_2x    = $this->images_url . 'icons32-2x.png?ver=' . $version;

		// The image size changed in WordPress 3.5
		if ( function_exists( 'wp_enqueue_media' ) ) {
			$icon32_size = '756px 45px';
		} else {
			$icon32_size = '708px 45px';
		}

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
					var other = ( i == 1 ) ? 0 : 1;

					this.change( function(){
						dropdowns[other].find( 'option[value='+ this.value +']' ).attr( 'selected', true );
					});
				});
			});
		</script>

		<style type="text/css" media="screen">
		/*<![CDATA[*/

			/* Kludge for too-wide years dropdown */
			#poststuff #fct_account_attributes select#parent_id,
			#poststuff #fct_record_attributes select#fct_year_id {
				max-width: 193px;
			}

			/* Kludge for too-wide account dropdown */
			#poststuff #fct_record_attributes select#parent_id,
			#poststuff #fct_record_attributes select#fct_record_account_ledger_id,
			.column-fct_record_account select.fct_new_record_account_id,
			#posts-filter select#fct_account_id {
				max-width: 193px;
			}


			#fiscaat-dashboard-right-now p.sub,
			#fiscaat-dashboard-right-now .table,
			#fiscaat-dashboard-right-now .versions {
				margin: -12px;
			}

			#fiscaat-dashboard-right-now .inside {
				font-size: 12px;
				padding-top: 20px;
				margin-bottom: 0;
			}

			#fiscaat-dashboard-right-now p.sub {
				padding: 5px 0 15px;
				color: #8f8f8f;
				font-size: 14px;
				position: absolute;
				top: -17px;
				left: 15px;
			}
				body.rtl #fiscaat-dashboard-right-now p.sub {
					right: 15px;
					left: 0;
				}

			#fiscaat-dashboard-right-now .table {
				margin: 0;
				padding: 0;
				position: relative;
			}

			#fiscaat-dashboard-right-now .table_content {
				float: left;
				border-top: #ececec 1px solid;
				width: 45%;
			}
				body.rtl #fiscaat-dashboard-right-now .table_content {
					float: right;
				}

			#fiscaat-dashboard-right-now .table_discussion {
				float: right;
				border-top: #ececec 1px solid;
				width: 45%;
			}
				body.rtl #fiscaat-dashboard-right-now .table_discussion {
					float: left;
				}

			#fiscaat-dashboard-right-now table td {
				padding: 3px 0;
				white-space: nowrap;
			}

			#fiscaat-dashboard-right-now table tr.first td {
				border-top: none;
			}

			#fiscaat-dashboard-right-now td.b {
				padding-right: 6px;
				text-align: right;
				font-family: Georgia, "Times New Roman", "Bitstream Charter", Times, serif;
				font-size: 14px;
				width: 1%;
			}
				body.rtl #fiscaat-dashboard-right-now td.b {
					padding-left: 6px;
					padding-right: 0;
				}

			#fiscaat-dashboard-right-now td.b a {
				font-size: 18px;
			}

			#fiscaat-dashboard-right-now td.b a:hover {
				color: #d54e21;
			}

			#fiscaat-dashboard-right-now .t {
				font-size: 12px;
				padding-right: 12px;
				padding-top: 6px;
				color: #777;
			}
				body.rtl #fiscaat-dashboard-right-now .t {
					padding-left: 12px;
					padding-right: 0;
				}

			#fiscaat-dashboard-right-now .t a {
				white-space: nowrap;
			}

			#fiscaat-dashboard-right-now .spam {
				color: red;
			}

			#fiscaat-dashboard-right-now .waiting {
				color: #e66f00;
			}

			#fiscaat-dashboard-right-now .approved {
				color: green;
			}

			#fiscaat-dashboard-right-now .versions {
				padding: 6px 10px 12px;
				clear: both;
			}

			#fiscaat-dashboard-right-now .versions .b {
				font-weight: bold;
			}

			#fiscaat-dashboard-right-now a.button {
				float: right;
				clear: right;
				position: relative;
				top: -5px;
			}
				body.rtl #fiscaat-dashboard-right-now a.button {
					float: left;
					clear: left;
				}

			/* Icon 32 */
			#icon-edit.icon32-posts-<?php echo $year_class; ?>,
			#icon-edit.icon32-posts-<?php echo $account_class; ?>,
			#icon-edit.icon32-posts-<?php echo $record_class; ?> {
				background: url('<?php echo $icon32_url; ?>');
				background-repeat: no-repeat;
			}

			/* Icon Positions */
			#icon-edit.icon32-posts-<?php echo $year_class; ?> {
				background-position: -4px 0px;
			}

			#icon-edit.icon32-posts-<?php echo $account_class; ?> {
				background-position: -4px -90px;
			}

			#icon-edit.icon32-posts-<?php echo $record_class; ?> {
				background-position: -4px -180px;
			}

			/* Icon 32 2x */
			@media only screen and (-webkit-min-device-pixel-ratio: 1.5) {
				#icon-edit.icon32-posts-<?php echo $year_class; ?>,
				#icon-edit.icon32-posts-<?php echo $account_class; ?>,
				#icon-edit.icon32-posts-<?php echo $record_class; ?> {
					background-image: url('<?php echo $icon32_url_2x; ?>');
					background-size: 45px 255px;
				}
			}

			/* Menu */
			#menu-posts-<?php echo $year_class; ?> .wp-menu-image,
			#menu-posts-<?php echo $account_class; ?> .wp-menu-image,
			#menu-posts-<?php echo $record_class; ?> .wp-menu-image,

			#menu-posts-<?php echo $year_class; ?>:hover .wp-menu-image,
			#menu-posts-<?php echo $account_class; ?>:hover .wp-menu-image,
			#menu-posts-<?php echo $record_class; ?>:hover .wp-menu-image,

			#menu-posts-<?php echo $year_class; ?>.wp-has-current-submenu .wp-menu-image,
			#menu-posts-<?php echo $account_class; ?>.wp-has-current-submenu .wp-menu-image,
			#menu-posts-<?php echo $record_class; ?>.wp-has-current-submenu .wp-menu-image {
				background: url('<?php echo $menu_icon_url; ?>');
				background-repeat: no-repeat;
			}

			/* Menu Positions */
			#menu-posts-<?php echo $year_class; ?> .wp-menu-image {
				background-position: 0px -32px;
			}
			#menu-posts-<?php echo $year_class; ?>:hover .wp-menu-image,
			#menu-posts-<?php echo $year_class; ?>.wp-has-current-submenu .wp-menu-image {
				background-position: 0px 0px;
			}
			#menu-posts-<?php echo $account_class; ?> .wp-menu-image {
				background-position: -70px -32px;
			}
			#menu-posts-<?php echo $account_class; ?>:hover .wp-menu-image,
			#menu-posts-<?php echo $account_class; ?>.wp-has-current-submenu .wp-menu-image {
				background-position: -70px 0px;
			}
			#menu-posts-<?php echo $record_class; ?> .wp-menu-image {
				background-position: -35px -32px;
			}
			#menu-posts-<?php echo $record_class; ?>:hover .wp-menu-image,
			#menu-posts-<?php echo $record_class; ?>.wp-has-current-submenu .wp-menu-image {
				background-position:  -35px 0px;
			}

			/* Menu 2x */
			@media only screen and (-webkit-min-device-pixel-ratio: 1.5) {
				#menu-posts-<?php echo $year_class; ?> .wp-menu-image,
				#menu-posts-<?php echo $account_class; ?> .wp-menu-image,
				#menu-posts-<?php echo $record_class; ?> .wp-menu-image,

				#menu-posts-<?php echo $year_class; ?>:hover .wp-menu-image,
				#menu-posts-<?php echo $account_class; ?>:hover .wp-menu-image,
				#menu-posts-<?php echo $record_class; ?>:hover .wp-menu-image,

				#menu-posts-<?php echo $year_class; ?>.wp-has-current-submenu .wp-menu-image,
				#menu-posts-<?php echo $account_class; ?>.wp-has-current-submenu .wp-menu-image,
				#menu-posts-<?php echo $record_class; ?>.wp-has-current-submenu .wp-menu-image {
					background-image: url('<?php echo $menu_icon_url_2x; ?>');
					background-size: 100px 64px;
				}
			}

		/*]]>*/
		</style>

		<?php
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
