<?php

/**
 * Fiscaat Classes
 *
 * @package Fiscaat
 * @subpackage Classes
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( !class_exists( 'Fiscaat_Component' ) ) :
/**
 * Fiscaat Component Class
 *
 * The Fiscaat component class is responsible for simplifying the creation
 * of components that share similar behaviors and routines. It is used
 * internally by Fiscaat to create periods, accounts and records, but can be
 * extended to create other really neat things.
 *
 * @package Fiscaat
 * @subpackage Classes
 */
class Fiscaat_Component {

	/**
	 * @var string Unique name (for internal identification)
	 * @internal
	 */
	var $name;

	/**
	 * @var Unique ID (normally for custom post type)
	 */
	var $id;

	/**
	 * @var string Unique slug (used in query string and permalinks)
	 */
	var $slug;

	/**
	 * @var WP_Query The loop for this component
	 */
	var $query;

	/**
	 * @var string The current ID of the queried object
	 */
	var $current_id;


	/** Methods ***************************************************************/

	/**
	 * Fiscaat Component loader
	 *
	 * @param mixed $args Required. Supports these args:
	 *  - name: Unique name (for internal identification)
	 *  - id: Unique ID (normally for custom post type)
	 *  - slug: Unique slug (used in query string and permalinks)
	 *  - query: The loop for this component (WP_Query)
	 *  - current_id: The current ID of the queried object
	 * @uses Fiscaat_Component::setup_globals() Setup the globals needed
	 * @uses Fiscaat_Component::includes() Include the required files
	 * @uses Fiscaat_Component::setup_actions() Setup the hooks and actions
	 */
	public function __construct( $args = '' ) {
		if ( empty( $args ) )
			return;

		$this->setup_globals( $args );
		$this->includes();
		$this->setup_actions();
	}

	/**
	 * Component global variables
	 * 
	 * @access private
	 *
	 * @uses apply_filters() Calls 'fct_{@link Fiscaat_Component::name}_id'
	 * @uses apply_filters() Calls 'fct_{@link Fiscaat_Component::name}_slug'
	 */
	private function setup_globals( $args = '' ) {
		$this->name = $args['name'];
		$this->id   = apply_filters( 'fct_' . $this->name . '_id',   $args['id']   );
		$this->slug = apply_filters( 'fct_' . $this->name . '_slug', $args['slug'] );
	}

	/**
	 * Include required files
	 * 
	 * @access private
	 *
	 * @uses do_action() Calls 'fct_{@link Fiscaat_Component::name}includes'
	 */
	private function includes() {
		do_action( 'fct_' . $this->name . 'includes' );
	}

	/**
	 * Setup the actions
	 * 
	 * @access private
	 *
	 * @uses add_action() To add various actions
	 * @uses do_action() Calls
	 *                    'fct_{@link Fiscaat_Component::name}setup_actions'
	 */
	private function setup_actions() {
		add_action( 'fct_register_post_types',    array( $this, 'register_post_types'    ), 10, 2 ); // Register post types
		add_action( 'fct_register_taxonomies',    array( $this, 'register_taxonomies'    ), 10, 2 ); // Register taxonomies
		add_action( 'fct_add_rewrite_tags',       array( $this, 'add_rewrite_tags'       ), 10, 2 ); // Add the rewrite tags
		add_action( 'fct_generate_rewrite_rules', array( $this, 'generate_rewrite_rules' ), 10, 2 ); // Generate rewrite rules

		// Additional actions can be attached here
		do_action( 'fct_' . $this->name . 'setup_actions' );
	}

	/**
	 * Setup the component post types
	 *
	 * @uses do_action() Calls 'fct_{@link Fiscaat_Component::name}_register_post_types'
	 */
	public function register_post_types() {
		do_action( 'fct_' . $this->name . '_register_post_types' );
	}

	/**
	 * Register component specific taxonomies
	 *
	 * @uses do_action() Calls 'fct_{@link Fiscaat_Component::name}_register_taxonomies'
	 */
	public function register_taxonomies() {
		do_action( 'fct_' . $this->name . '_register_taxonomies' );
	}

	/**
	 * Add any additional rewrite tags
	 *
	 * @uses do_action() Calls 'fct_{@link Fiscaat_Component::name}_add_rewrite_tags'
	 */
	public function add_rewrite_tags() {
		do_action( 'fct_' . $this->name . '_add_rewrite_tags' );
	}

	/**
	 * Generate any additional rewrite rules
	 *
	 * @uses do_action() Calls 'fct_{@link Fiscaat_Component::name}_generate_rewrite_rules'
	 */
	public function generate_rewrite_rules( $wp_rewrite ) {
		do_action_ref_array( 'fct_' . $this->name . '_generate_rewrite_rules', $wp_rewrite );
	}
}
endif; // Fiscaat_Component

if ( class_exists( 'Walker' ) ) :

/**
 * Create HTML dropdown list of Fiscaat periods/accounts.
 *
 * @package Fiscaat
 * @subpackage Classes
 *
 * @uses Walker
 */
class Fiscaat_Walker_Dropdown extends Walker {

	/**
	 * @see Walker::$tree_type
	 *
	 * @var string
	 */
	var $tree_type;

	/**
	 * @see Walker::$db_fields
	 *
	 * @var array
	 */
	var $db_fields = array( 'parent' => 'post_parent', 'id' => 'ID' );

	/** Methods ***************************************************************/

	/**
	 * Set the tree_type
	 */
	public function __construct() {
		$this->tree_type = fct_get_period_post_type();
	}

	/**
	 * @see Walker::start_el()
	 *
	 * @uses current_user_can() To check if the current user can post in
	 *                           closed periods
	 * @uses fct_is_record_period_closed() To check if the record's period
	 *                                      is closed
	 * @uses fct_is_record_account_closed() To check if the record's account
	 *                                       is closed
	 * @uses fct_is_account_period_closed() To check if the account's period 
	 *                                       is closed
	 * @uses fct_is_period_closed() To check if the period is closed
	 * @uses apply_filters() Calls 'fct_walker_dropdown_post_title' with the
	 *                        title, output, post, depth and args
	 *
	 * @param string $output Passed by reference. Used to append additional
	 *                        content.
	 * @param object $_post Post data object.
	 * @param int $depth Depth of post in reference to parent posts. Used
	 *                    for padding.
	 * @param array $args Uses 'selected' argument for selected post to set
	 *                     selected HTML attribute for option element.
	 */
	public function start_el( &$output, $_post, $depth, $args ) {
		$pad     = str_repeat( '&nbsp;', $depth * 3 );
		$output .= '<option class="level-' . $depth . '"';
		$disable = false;

		/**
		 * Determine whether to disable the <option> if:
		 * - <select> isn't disabled
		 * - we're told to do so
		 * - the post type is a period or account
		 * - period or account is closed
		 */
		if ( ( true != $args['disabled'] ) && $args['disable_closed'] ) {

			// Check the post type
			switch ( $_post->post_type ) {

				// Record
				case fct_get_record_post_type() :

					// Check record's period first, then maybe check its account
					if ( ! $disable = fct_is_record_period_closed( $_post->ID ) )
						$disable = fct_is_record_account_closed( $_post->ID );
					break;

				// Account
				case fct_get_account_post_type() :

					// Check account's period first, then maybe check account itself
					if ( ! $disable = fct_is_account_period_closed( $_post->ID ) )
						$disable = fct_is_account_closed( $_post->ID );
					break;

				// Period
				case fct_get_period_post_type() :
					$disable = fct_is_period_closed( $_post->ID );
					break;
			}
		}

		// Fill in option attributes
		if ( $disable ) {
			$output .= ' disabled="disabled" value=""';
		} else {
			$output .= ' value="' . $_post->ID .'"' . selected( $args['selected'], $_post->ID, false );
		}

		$output .= '>';
		$title   = apply_filters( 'fct_walker_dropdown_post_title', $_post->post_title, $output, $_post, $depth, $args );
		$output .= $pad . esc_html( $title );
		$output .= "</option>\n";
	}
}

endif; // class_exists check
