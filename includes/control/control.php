<?php

/**
 * Main Fiscaat Control Class
 *
 * @package Fiscaat
 * @subpackage Control
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Fiscaat_Control' ) ) :

/**
 * Loads Fiscaat plugin control functionality
 *
 * @package Fiscaat
 * @subpackage Control
 */
class Fiscaat_Control {

	/** Directory *************************************************************/

	/**
	 * @var string Path to the Fiscaat control directory
	 */
	public $admin_dir = '';

	/** URLs ******************************************************************/

	/**
	 * @var string URL to the Fiscaat control directory
	 */
	public $admin_url = '';

	/** Functions *************************************************************/

	/**
	 * The main Fiscaat control loader
	 *
	 * @uses Fiscaat_Control::setup_globals() Setup the globals needed
	 * @uses Fiscaat_Control::includes() Include the required files
	 * @uses Fiscaat_Control::setup_actions() Setup the hooks and actions
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
		$fct = fiscaat();

		/** Paths *************************************************************/

		$this->control_dir  = trailingslashit( $fct->includes_dir . 'control'  ); // Control path
		$this->control_url  = trailingslashit( $fct->includes_url . 'control'  ); // Control url

		/** Identifiers *******************************************************/

		// Status identifiers
		$this->approved_status_id  = apply_filters( 'fct_approved_post_status', 'approved' );
		$this->declined_status_id  = apply_filters( 'fct_declined_post_status', 'declined' );
	}

	/**
	 * Include required files
	 *
	 * @access private
	 */
	private function includes() {

		/** Components ********************************************************/

		require( $this->control_dir . 'accounts.php'     );
		require( $this->control_dir . 'capabilities.php' );
		require( $this->control_dir . 'functions.php'    );
		require( $this->control_dir . 'periods.php'      );
		require( $this->control_dir . 'records.php'      );
		require( $this->control_dir . 'template.php'     );

		/** Admin *************************************************************/

		// Quick admin check
		if ( is_admin() ){
			require( $this->control_dir . 'admin.php'    );
		}
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

		// Post type meta
		add_filter( 'fct_get_period_default_meta',  'fct_ctrl_get_period_default_meta'  );
		add_filter( 'fct_get_account_default_meta', 'fct_ctrl_get_account_default_meta' );

		// Post statuses
		add_action( 'fct_register_post_statuses',                'fct_ctrl_register_post_statuses'                );
		add_filter( 'fct_record_statuses',                       'fct_ctrl_record_statuses'                       );
		add_filter( 'fct_record_status_dropdown_disable',        'fct_ctrl_record_status_dropdown_disable'        );
		add_filter( 'fct_record_status_dropdown_option_disable', 'fct_ctrl_record_status_dropdown_option_disable' );

		// Statistics
		add_filter( 'fct_before_get_statistics_parse_args', 'fct_ctrl_get_statistics_default_args'        );
		add_filter( 'fct_get_statistics',                   'fct_ctrl_get_statistics',              10, 2 );

		/** Capabilities ******************************************************/

		add_filter( 'fct_get_dynamic_roles',     'fct_ctrl_get_dynamic_roles', 20    );
		add_filter( 'fct_get_caps_for_role',     'fct_ctrl_get_caps_for_role', 20, 2 );
		add_filter( 'fct_map_period_meta_caps',  'fct_ctrl_map_meta_caps',     10, 4 );
		add_filter( 'fct_map_account_meta_caps', 'fct_ctrl_map_meta_caps',     10, 4 );
		add_filter( 'fct_map_record_meta_caps',  'fct_ctrl_map_meta_caps',     10, 4 );
		add_filter( 'fct_map_admin_meta_caps',   'fct_ctrl_map_meta_caps',     10, 4 );

		/** Admin *************************************************************/

		if ( is_admin() ) {

			// Columns
			add_filter( 'fct_admin_periods_column_headers',    'fct_ctrl_admin_periods_column_headers'    );
			add_filter( 'fct_admin_periods_sortable_columns',  'fct_ctrl_admin_periods_sortable_columns'  );
			add_filter( 'fct_admin_periods_request',           'fct_ctrl_admin_periods_request'           );
			add_filter( 'fct_admin_accounts_column_headers',   'fct_ctrl_admin_accounts_column_headers'   );
			add_filter( 'fct_admin_accounts_sortable_columns', 'fct_ctrl_admin_accounts_sortable_columns' );
			add_filter( 'fct_admin_accounts_request',          'fct_ctrl_admin_accounts_request'          );

			// Column content
			add_action( 'fct_admin_accounts_column_data', 'fct_ctrl_admin_accounts_column_data', 10, 2 );
			add_action( 'fct_admin_periods_column_data',  'fct_ctrl_admin_periods_column_data',  10, 2 );

			// Record toggle
			add_filter( 'fct_toggle_record',              'fct_ctrl_admin_records_toggle_record',        10, 3 );
			add_filter( 'fct_toggle_record_notice_admin', 'fct_ctrl_admin_records_toggle_record_notice', 10, 4 );
		}

		/** Dependencies ******************************************************/

		// Allow plugins to modify these actions
		do_action_ref_array( 'fct_control_loaded', array( &$this ) );
	}

}

endif; // class_exists check

/**
 * Setup Fiscaat Control
 *
 * @since 0.0.9
 *
 * @uses fct_is_control_active()
 * @uses Fiscaat_Control
 */
function fct_control() {

	// Bail when this component is not activated
	if ( ! fct_is_control_active() )
		return;

	fiscaat()->control = new Fiscaat_Control();
}
