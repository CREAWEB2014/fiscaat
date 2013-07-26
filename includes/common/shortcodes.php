<?php

/**
 * Fiscaat Shortcodes
 *
 * @package Fiscaat
 * @subpackage Shortcodes
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'Fiscaat_Shortcodes' ) ) :
/**
 * Fiscaat Shortcode Class
 *
 * @since Fiscaat (r3031)
 */
class Fiscaat_Shortcodes {

	/** Vars ******************************************************************/

	/**
	 * @var array Shortcode => function
	 */
	public $codes = array();

	/** Functions *************************************************************/

	/**
	 * Add the register_shortcodes action to fiscaat_init
	 *
	 * @since Fiscaat (r3031)
	 *
	 * @uses setup_globals()
	 * @uses add_shortcodes()
	 */
	public function __construct() {
		$this->setup_globals();
		$this->add_shortcodes();
	}

	/**
	 * Shortcode globals
	 *
	 * @since Fiscaat (r3143)
	 * @access private
	 *
	 * @uses apply_filters()
	 */
	private function setup_globals() {

		// Setup the shortcodes
		$this->codes = apply_filters( 'fiscaat_shortcodes', array(

			/** Records *******************************************************/

			'fiscaat-record-index'   => array( $this, 'display_record_index'   ), // Record index
			'fiscaat-record-form'    => array( $this, 'display_record_form'    ), // Record form
			'fiscaat-single-record'  => array( $this, 'display_record'         ), // Specific record - pass an 'id' attribute

			/** Accounts ******************************************************/

			'fiscaat-account-index'  => array( $this, 'display_account_index'  ), // Account index
			'fiscaat-single-account' => array( $this, 'display_single_account' ), // Records of Account

			/** Years *********************************************************/

			'fiscaat-year-index'     => array( $this, 'display_year_index'     ), // Year index
			'fiscaat-single-year'    => array( $this, 'display_single_year'    ), // Records of Year per account

		) );
	}

	/**
	 * Register the Fiscaat shortcodes
	 *
	 * @since Fiscaat (r3031)
	 *
	 * @uses add_shortcode()
	 * @uses do_action()
	 */
	private function add_shortcodes() {
		foreach( (array) $this->codes as $code => $function ) {
			add_shortcode( $code, $function );
		}
	}

	/**
	 * Unset some globals in the $fiscaat object that hold query related info
	 *
	 * @since Fiscaat (r3034)
	 */
	private function unset_globals() {
		$fiscaat = fiscaat();

		// Unset global queries
		$fiscaat->year_query = new stdClass;
		$fiscaat->account_query = new stdClass;
		$fiscaat->record_query = new stdClass;

		// Unset global ID's
		$fiscaat->current_year_id     = 0;
		$fiscaat->current_account_id     = 0;
		$fiscaat->current_record_id     = 0;
		$fiscaat->current_account_tag_id = 0;

		// Reset the post data
		wp_reset_postdata();
	}

	/** Output Buffers ********************************************************/

	/**
	 * Start an output buffer.
	 *
	 * This is used to put the contents of the shortcode into a variable rather
	 * than outputting the HTML at run-time. This allows shortcodes to appear
	 * in the correct location in the_content() instead of when it's created.
	 *
	 * @since Fiscaat (r3079)
	 *
	 * @param string $query_name
	 *
	 * @uses fiscaat_set_query_name()
	 * @uses ob_start()
	 */
	private function start( $query_name = '' ) {

		// Set query name
		fiscaat_set_query_name( $query_name );

		// Remove 'fiscaat_replace_the_content' filter to prevent infinite loops
		remove_filter( 'the_content', 'fiscaat_replace_the_content' );

		// Start output buffer
		ob_start();
	}

	/**
	 * Return the contents of the output buffer and flush its contents.
	 *
	 * @since Fiscaat( r3079)
	 *
	 * @uses Fiscaat_Shortcodes::unset_globals() Cleans up global values
	 * @return string Contents of output buffer.
	 */
	private function end() {

		// Put output into usable variable
		$output = ob_get_contents();

		// Unset globals
		$this->unset_globals();

		// Flush the output buffer
		ob_end_clean();

		// Reset the query name
		fiscaat_reset_query_name();

		// Add 'fiscaat_replace_the_content' filter back (@see $this::start())
		add_filter( 'the_content', 'fiscaat_replace_the_content' );

		return $output;
	}

	/** Year shortcodes ******************************************************/

	/**
	 * Display an index of all visible root level years in an output buffer
	 * and return to ensure that post/page contents are displayed first.
	 *
	 * @since Fiscaat (r3031)
	 *
	 * @param array $attr
	 * @param string $content
	 * @uses fiscaat_has_years()
	 * @uses get_template_part()
	 * @return string
	 */
	public function display_year_index() {

		// Unset globals
		$this->unset_globals();

		// Start output buffer
		$this->start( 'fiscaat_year_archive' );

		fiscaat_get_template_part( 'content', 'archive-year' );

		// Return contents of output buffer
		return $this->end();
	}

	/**
	 * Display the contents of a specific year ID in an output buffer
	 * and return to ensure that post/page contents are displayed first.
	 *
	 * @since Fiscaat (r3031)
	 *
	 * @param array $attr
	 * @param string $content
	 * @uses get_template_part()
	 * @uses fiscaat_single_year_description()
	 * @return string
	 */
	public function display_year( $attr, $content = '' ) {

		// Sanity check required info
		if ( !empty( $content ) || ( empty( $attr['id'] ) || !is_numeric( $attr['id'] ) ) )
			return $content;

		// Set passed attribute to $year_id for clarity
		$year_id = fiscaat()->current_year_id = $attr['id'];

		// Bail if ID passed is not a year
		if ( !fiscaat_is_year( $year_id ) )
			return $content;

		// Start output buffer
		$this->start( 'fiscaat_single_year' );

		// Check year caps
		if ( fiscaat_user_can_view_year( array( 'year_id' => $year_id ) ) ) {
			fiscaat_get_template_part( 'content',  'single-year' );

		// Year is private and user does not have caps
		} elseif ( fiscaat_is_year_private( $year_id, false ) ) {
			fiscaat_get_template_part( 'feedback', 'no-access'    );
		}

		// Return contents of output buffer
		return $this->end();
	}

	/**
	 * Display the year form in an output buffer and return to ensure
	 * post/page contents are displayed first.
	 *
	 * @since Fiscaat (r3566)
	 *
	 * @uses get_template_part()
	 */
	public function display_year_form() {

		// Start output buffer
		$this->start( 'fiscaat_year_form' );

		// Output templates
		fiscaat_get_template_part( 'form', 'year' );

		// Return contents of output buffer
		return $this->end();
	}

	/** Account shortcodes ******************************************************/

	/**
	 * Display an index of all visible root level accounts in an output buffer
	 * and return to ensure that post/page contents are displayed first.
	 *
	 * @since Fiscaat (r3031)
	 *
	 * @param array $attr
	 * @param string $content
	 * @uses fiscaat_get_hidden_year_ids()
	 * @uses get_template_part()
	 * @return string
	 */
	public function display_account_index() {

		// Unset globals
		$this->unset_globals();

		// Filter the query
		if ( ! fiscaat_is_account_archive() ) {
			add_filter( 'fiscaat_before_has_accounts_parse_args', array( $this, 'display_account_index_query' ) );
		}

		// Start output buffer
		$this->start( 'fiscaat_account_archive' );

		// Output template
		fiscaat_get_template_part( 'content', 'archive-account' );

		// Return contents of output buffer
		return $this->end();
	}

	/**
	 * Display the contents of a specific account ID in an output buffer
	 * and return to ensure that post/page contents are displayed first.
	 *
	 * @since Fiscaat (r3031)
	 *
	 * @param array $attr
	 * @param string $content
	 * @uses get_template_part()
	 * @return string
	 */
	public function display_account( $attr, $content = '' ) {

		// Sanity check required info
		if ( !empty( $content ) || ( empty( $attr['id'] ) || !is_numeric( $attr['id'] ) ) )
			return $content;

		// Unset globals
		$this->unset_globals();

		// Set passed attribute to $year_id for clarity
		$account_id = fiscaat()->current_account_id = $attr['id'];
		$year_id = fiscaat_get_account_year_id( $account_id );

		// Bail if ID passed is not a account
		if ( !fiscaat_is_account( $account_id ) )
			return $content;

		// Reset the queries if not in theme compat
		if ( !fiscaat_is_theme_compat_active() ) {

			$fiscaat = fiscaat();

			// Reset necessary year_query attributes for accounts loop to function
			$fiscaat->year_query->query_vars['post_type'] = fiscaat_get_year_post_type();
			$fiscaat->year_query->in_the_loop             = true;
			$fiscaat->year_query->post                    = get_post( $year_id );

			// Reset necessary account_query attributes for accounts loop to function
			$fiscaat->account_query->query_vars['post_type'] = fiscaat_get_account_post_type();
			$fiscaat->account_query->in_the_loop             = true;
			$fiscaat->account_query->post                    = get_post( $account_id );
		}

		// Start output buffer
		$this->start( 'fiscaat_single_account' );

		// Check year caps
		if ( fiscaat_user_can_view_year( array( 'year_id' => $year_id ) ) ) {
			fiscaat_get_template_part( 'content', 'single-account' );

		// Year is private and user does not have caps
		} elseif ( fiscaat_is_year_private( $year_id, false ) ) {
			fiscaat_get_template_part( 'feedback', 'no-access'    );
		}

		// Return contents of output buffer
		return $this->end();
	}

	/**
	 * Display the account form in an output buffer and return to ensure
	 * post/page contents are displayed first.
	 *
	 * @since Fiscaat (r3031)
	 *
	 * @uses get_template_part()
	 */
	public function display_account_form() {

		// Start output buffer
		$this->start( 'fiscaat_account_form' );

		// Output templates
		fiscaat_get_template_part( 'form', 'account' );

		// Return contents of output buffer
		return $this->end();
	}

	/** Records ***************************************************************/

	/**
	 * Display the contents of a specific record ID in an output buffer
	 * and return to ensure that post/page contents are displayed first.
	 *
	 * @since Fiscaat (r3031)
	 *
	 * @param array $attr
	 * @param string $content
	 * @uses get_template_part()
	 * @return string
	 */
	public function display_record( $attr, $content = '' ) {

		// Sanity check required info
		if ( !empty( $content ) || ( empty( $attr['id'] ) || !is_numeric( $attr['id'] ) ) )
			return $content;

		// Unset globals
		$this->unset_globals();

		// Set passed attribute to $record_id for clarity
		$record_id = fiscaat()->current_record_id = $attr['id'];
		$year_id = fiscaat_get_record_year_id( $record_id );

		// Bail if ID passed is not a record
		if ( !fiscaat_is_record( $record_id ) )
			return $content;

		// Reset the queries if not in theme compat
		if ( !fiscaat_is_theme_compat_active() ) {

			$fiscaat = fiscaat();

			// Reset necessary year_query attributes for records loop to function
			$fiscaat->year_query->query_vars['post_type'] = fiscaat_get_year_post_type();
			$fiscaat->year_query->in_the_loop             = true;
			$fiscaat->year_query->post                    = get_post( $year_id );

			// Reset necessary record_query attributes for records loop to function
			$fiscaat->record_query->query_vars['post_type'] = fiscaat_get_record_post_type();
			$fiscaat->record_query->in_the_loop             = true;
			$fiscaat->record_query->post                    = get_post( $record_id );
		}

		// Start output buffer
		$this->start( 'fiscaat_single_record' );

		// Check year caps
		if ( fiscaat_user_can_view_year( array( 'year_id' => $year_id ) ) ) {
			fiscaat_get_template_part( 'content',  'single-record' );

		// Year is private and user does not have caps
		} elseif ( fiscaat_is_year_private( $year_id, false ) ) {
			fiscaat_get_template_part( 'feedback', 'no-access'    );
		}

		// Return contents of output buffer
		return $this->end();
	}

	/**
	 * Display the record form in an output buffer and return to ensure
	 * post/page contents are displayed first.
	 *
	 * @since Fiscaat (r3031)
	 *
	 * @uses get_template_part()
	 */
	public function display_record_form() {

		// Start output buffer
		$this->start( 'fiscaat_record_form' );

		// Output templates
		fiscaat_get_template_part( 'form', 'record' );

		// Return contents of output buffer
		return $this->end();
	}

	/** Account Tags ************************************************************/

	/**
	 * Display a tag cloud of all account tags in an output buffer and return to
	 * ensure that post/page contents are displayed first.
	 *
	 * @since Fiscaat (r3110)
	 *
	 * @return string
	 */
	public function display_account_tags() {

		// Unset globals
		$this->unset_globals();

		// Start output buffer
		$this->start( 'fiscaat_account_tags' );

		// Output the account tags
		wp_tag_cloud( array(
			'smallest' => 9,
			'largest'  => 38,
			'number'   => 80,
			'taxonomy' => fiscaat_get_account_tag_tax_id()
		) );

		// Return contents of output buffer
		return $this->end();
	}

	/**
	 * Display the contents of a specific account tag in an output buffer
	 * and return to ensure that post/page contents are displayed first.
	 *
	 * @since Fiscaat (r3110)
	 *
	 * @param array $attr
	 * @param string $content
	 * @uses get_template_part()
	 * @return string
	 */
	public function display_accounts_of_tag( $attr, $content = '' ) {

		// Sanity check required info
		if ( !empty( $content ) || ( empty( $attr['id'] ) || !is_numeric( $attr['id'] ) ) )
			return $content;

		// Unset globals
		$this->unset_globals();

		// Filter the query
		if ( ! fiscaat_is_account_tag() ) {
			add_filter( 'fiscaat_before_has_accounts_parse_args', array( $this, 'display_accounts_of_tag_query' ) );
		}

		// Start output buffer
		$this->start( 'fiscaat_account_tag' );

		// Set passed attribute to $ag_id for clarity
		fiscaat()->current_account_tag_id = $tag_id = $attr['id'];

		// Output template
		fiscaat_get_template_part( 'content', 'archive-account' );

		// Return contents of output buffer
		return $this->end();
	}

	/**
	 * Display the contents of a specific account tag in an output buffer
	 * and return to ensure that post/page contents are displayed first.
	 *
	 * @since Fiscaat (r3346)
	 *
	 * @param array $attr
	 * @param string $content
	 * @uses get_template_part()
	 * @return string
	 */
	public function display_account_tag_form() {

		// Unset globals
		$this->unset_globals();

		// Start output buffer
		$this->start( 'fiscaat_account_tag_edit' );

		// Output template
		fiscaat_get_template_part( 'content', 'account-tag-edit' );

		// Return contents of output buffer
		return $this->end();
	}

	/** Views *****************************************************************/

	/**
	 * Display the contents of a specific view in an output buffer and return to
	 * ensure that post/page contents are displayed first.
	 *
	 * @since Fiscaat (r3031)
	 *
	 * @param array $attr
	 * @param string $content
	 * @uses get_template_part()
	 * @uses fiscaat_single_year_description()
	 * @return string
	 */
	public function display_view( $attr, $content = '' ) {

		// Sanity check required info
		if ( empty( $attr['id'] ) )
			return $content;

		// Set passed attribute to $view_id for clarity
		$view_id = $attr['id'];

		// Start output buffer
		$this->start( 'fiscaat_single_view' );

		// Unset globals
		$this->unset_globals();

		// Load the view
		fiscaat_view_query( $view_id );

		// Output template
		fiscaat_get_template_part( 'content', 'single-view' );

		// Return contents of output buffer
		return $this->end();
	}

	/** Account ***************************************************************/

	/**
	 * Display a login form
	 *
	 * @since Fiscaat (r3302)
	 *
	 * @return string
	 */
	public function display_login() {

		// Unset globals
		$this->unset_globals();

		// Start output buffer
		$this->start( 'fiscaat_login' );

		// Output templates
		if ( !is_user_logged_in() )
			fiscaat_get_template_part( 'form',     'user-login' );
		else
			fiscaat_get_template_part( 'feedback', 'logged-in'  );

		// Return contents of output buffer
		return $this->end();
	}

	/**
	 * Display a register form
	 *
	 * @since Fiscaat (r3302)
	 *
	 * @return string
	 */
	public function display_register() {

		// Unset globals
		$this->unset_globals();

		// Start output buffer
		$this->start( 'fiscaat_register' );

		// Output templates
		if ( !is_user_logged_in() )
			fiscaat_get_template_part( 'form',     'user-register' );
		else
			fiscaat_get_template_part( 'feedback', 'logged-in'     );

		// Return contents of output buffer
		return $this->end();
	}

	/**
	 * Display a lost password form
	 *
	 * @since Fiscaat (r3302)
	 *
	 * @return string
	 */
	public function display_lost_pass() {

		// Unset globals
		$this->unset_globals();

		// Start output buffer
		$this->start( 'fiscaat_lost_pass' );

		// Output templates
		if ( !is_user_logged_in() )
			fiscaat_get_template_part( 'form',     'user-lost-pass' );
		else
			fiscaat_get_template_part( 'feedback', 'logged-in'      );
	
		// Return contents of output buffer
		return $this->end();
	}

	/** Other *****************************************************************/

	/**
	 * Display a breadcrumb
	 *
	 * @since Fiscaat (r3302)
	 *
	 * @return string
	 */
	public function display_breadcrumb() {

		// Unset globals
		$this->unset_globals();

		// Start output buffer
		$this->start();

		// Output breadcrumb
		fiscaat_breadcrumb();

		// Return contents of output buffer
		return $this->end();
	}

	/** Query Filters *********************************************************/

	/**
	 * Filter the query for the account index
	 *
	 * @since Fiscaat (r3637)
	 *
	 * @param array $args
	 * @return array
	 */
	public function display_account_index_query( $args = array() ) {
		$args['author']        = 0;
		$args['show_stickies'] = true;
		$args['order']         = 'DESC';
		return $args;
	}

	/**
	 * Filter the query for account tags
	 *
	 * @since Fiscaat (r3637)
	 *
	 * @param array $args
	 * @return array
	 */
	public function display_accounts_of_tag_query( $args = array() ) {
		$args['tax_query'] = array( array(
			'taxonomy' => fiscaat_get_account_tag_tax_id(),
			'field'    => 'id',
			'terms'    => fiscaat()->current_account_tag_id
		) );

		return $args;
	}
}
endif;
