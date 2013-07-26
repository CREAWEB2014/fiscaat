<?php

/**
 * Fiscaat Control Component
 *
 * @package Fiscaat
 * @subpackage Control
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Fiscaat_Control' ) ) :

/**
 * Loads Fiscaat control functionality
 *
 * @package Fiscaat
 * @subpackage Control
 */
class Fiscaat_Control {

	/**
	 * @var $cap The main control capability
	 */
	public $cap;

	public function __construct() {
		$this->setup_globals();
		$this->setup_actions();
	}

	public function setup_globals() {
		$this->cap = 'fiscaat_control';
	}

	public function setup_actions() {

		// Controller role
		add_filter( 'fiscaat_get_dynamic_roles', array( $this, 'controller_role' ), 20    );
		add_filter( 'fiscaat_get_caps_for_role', array( $this, 'controller_caps' ), 20, 2 );
		add_filter( 'fiscaat_map_meta_caps',     array( $this, 'map_meta_caps'   ), 20, 4 );

		// Record post status dropdown
		add_filter( 'fiscaat_record_statuses',                       array( $this, 'record_statuses'                       ) );
		add_filter( 'fiscaat_record_status_dropdown_disable',        array( $this, 'record_status_dropdown_disable'        ) );
		add_filter( 'fiscaat_record_status_dropdown_option_disable', array( $this, 'record_status_dropdown_option_disable' ) );
	}

	/**
	 * User Can Control check
	 * 
	 * @param int $user_id The user ID
	 * @return boolean User has control cap
	 */
	public function ucc( $user_id = 0 ) {
		if ( empty( $user_id ) )
			return current_user_can( $this->cap );
		else 
			return user_can( $user_id, $this->cap );
	}

	/**
	 * Create Controller role for Fiscaat dynamic roles
	 * 
	 * @param array $roles
	 * @return array
	 */
	public function controller_role( $roles ) {
		$roles[fiscaat_get_controller_role()] = array(
			'name'         => __( 'Controller', 'fiscaat' ),
			'capabilities' => fiscaat_get_caps_for_role( fiscaat_get_controller_role() )
		);

		return $roles;
	}

	/**
	 * Return capabilities for Controller role
	 * 
	 * @param array $caps
	 * @param string $role
	 * @return array
	 */
	public function controller_caps( $caps, $role ) {
		if ( fiscaat_get_controller_role() == $role ) {
			$caps = array(

				// Controllers only
				$this->cap               => true,

				// Primary caps
				'fiscaat_spectate'       => true,

				// Record caps
				'publish_records'        => false,
				'edit_records'           => true,
				'edit_others_records'    => true,
				'delete_records'         => false,
				'delete_others_records'  => false,
				'read_private_records'   => false,

				// Account caps
				'publish_accounts'       => false,
				'edit_accounts'          => false,
				'edit_others_accounts'   => false,
				'delete_accounts'        => false,
				'delete_others_accounts' => false,
				'read_private_accounts'  => false,

				// Year caps. Controllers only
				'publish_years'          => false,
				'edit_years'             => false,
				'edit_others_years'      => false,
				'delete_years'           => false,
				'delete_others_years'    => false,
				'read_private_years'     => false
			);
		}

		return $caps;
	}

	/**
	 * Map meta capabilities when the user can control
	 * 
	 * @param array $caps
	 * @param string $cap
	 * @param int $user_id
	 * @param array $args
	 * @return Mapped caps
	 */
	public function map_meta_caps( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {
		
		// User can not control
		if ( ! $this->ucc( $user_id ) ) 
			return $caps;

		switch ( $cap ) {

			// Controllers can read all
			case 'read_year'    :
			case 'read_account' :
			case 'read_record'  :
				if ( user_can( $user_id, 'fiscaat_spectate' ) )
					$caps = array( 'fiscaat_spectate' );
				break;

			// Controllers can edit post stati
			case 'edit_record' :

				// Do some post ID based logic
				$_post = get_post( $args[0] );
				if ( !empty( $_post ) ){

					// Record is not closed
					if ( fiscaat_get_closed_status_id() != $_post->post_status )
						$caps = array( $this->cap );
				}

				break;
			case 'edit_records'        :
			case 'edit_others_records' :
				$caps = array( $this->cap );
				break;
		}

		return apply_filters( 'fiscaat_control_map_meta_caps', $caps, $cap, $user_id, $args );
	}

	/**
	 * Add control post stati to record status dropdown
	 * 
	 * @param array $options
	 * @return array
	 */
	public function record_statuses( $options ) {

		// Insert options after 'publish' and before 'close'
		$options = array_splice( $options, 1, 0, array(
			fiscaat_get_approved_status_id()    => __('Approved',    'fiscaat'),
			fiscaat_get_disapproved_status_id() => __('Disapproved', 'fiscaat'),
		) );

		return $options;
	}

	/**
	 * Enable record status dropdown for Controllers
	 * 
	 * @param boolean $disable
	 * @return boolean
	 */
	public function record_status_dropdown_disable( $disable ) {
		if ( $this->ucc() )
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
	public function record_status_dropdown_option_disable( $disable, $option ) {
		switch ( $option ) {

			// Disable 'approved' and 'disapproved' record post stati for non-Controllers
			case fiscaat_get_approved_status_id()    :
			case fiscaat_get_disapproved_status_id() :
				if ( ! $this->ucc() )
					$disable = true;
				break;

			// Disable all other post stati for Controllers
			default :
				if ( $this->ucc() )
					$disable = true;
				break;
		}

		return $disable;
	}
}

endif; // class_exists

/**
 * Setup Fiscaat Control
 *
 * @uses fiscaat_is_control_active()
 * @uses Fiscaat_Control
 */
function fiscaat_control() {
	if ( ! fiscaat_is_control_active() ) return;

	fiscaat()->control = new Fiscaat_Control();
}

/**
 * The controller role for Fiscaat users
 *
 * @uses apply_filters() Allow override of hardcoded controller role
 * @return string
 */
function fiscaat_get_controller_role() {
	return apply_filters( 'fiscaat_get_controller_role', 'fiscaat_controller' );
}
