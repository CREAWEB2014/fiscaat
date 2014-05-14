<?php

/**
 * Fiscaat Common Template Tags
 *
 * Common template tags are ones that are used by more than one component, like
 * periods, accounts, records, etc...
 *
 * @package Fiscaat
 * @subpackage TemplateTags
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** URLs **********************************************************************/

/**
 * Ouput the period URL
 * 
 * @uses fct_get_periods_url() To get the periods URL
 * @param string $path Additional path with leading slash
 */
function fct_periods_url( $path = '/' ) {
	echo fct_get_periods_url( $path );
}

	/**
	 * Return the period URL
	 * 
	 * @uses home_url() To get the home URL
	 * @uses fct_get_root_slug() To get the period root location
	 * @param string $path Additional path with leading slash
	 */
	function fct_get_periods_url( $path = '/' ) {
		return home_url( fct_get_root_slug() . $path );
	}

/**
 * Ouput the period URL
 *
 * @uses fct_get_accounts_url() To get the accounts URL
 * @param string $path Additional path with leading slash
 */
function fct_accounts_url( $path = '/' ) {
	echo fct_get_accounts_url( $path );
}

	/**
	 * Return the period URL
	 *
	 * @uses home_url() To get the home URL
	 * @uses fct_get_account_archive_slug() To get the accounts archive location
	 * @param string $path Additional path with leading slash
	 * @return The URL to the accounts archive
	 */
	function fct_get_accounts_url( $path = '/' ) {
		return home_url( fct_get_account_archive_slug() . $path );
	}

/** Add-on Actions ************************************************************/

/**
 * Add our custom head action to wp_head
 *
 * @uses do_action() Calls 'fct_head'
 */
function fct_head() {
	do_action( 'fct_head' );
}

/**
 * Add our custom head action to wp_head
 *
 * @uses do_action() Calls 'fct_footer'
 */
function fct_footer() {
	do_action( 'fct_footer' );
}

/** Loop **********************************************************************/

/**
 * The generic main loop.
 *
 * Calls the associated fct_has_{posts} callback
 *
 * @since 0.0.8
 *
 * @see fct_has_records()
 * @see fct_has_accounts()
 * @see fct_has_periods()
 * 
 * @uses fct_post_callback() To call 'fct_has_{posts}'
 * @param string $post_type Optional. Post type name
 * @return bool|object Loop information
 */
function fct_has_posts( $post_type = '' ) {
	return fct_post_callback( 'fct_has_%ss', $post_type );
}

/**
 * Whether there are more posts available in the loop
 *
 * Calls the associated fct_{posts} callback
 *
 * @since 0.0.8
 *
 * @see fct_records()
 * @see fct_accounts()
 * @see fct_periods()
 * 
 * @uses fct_post_callback() To call 'fct_{posts}'
 * @param string $post_type Optional. Post type name
 * @return object Post information
 */
function fct_posts( $post_type = '' ) {
	return fct_post_callback( 'fct_%ss', $post_type );
}

/**
 * Loads up the current post in the loop
 *
 * Calls the associated fct_the_{post} callback
 *
 * @since 0.0.8
 *
 * @see fct_the_record()
 * @see fct_the_account()
 * @see fct_the_period()
 * 
 * @uses fct_post_callback() To call 'fct_the_{post}'
 * @param string $post_type Optional. Post type name
 * @return object|false Post information or False if no valid post type
 */
function fct_the_post( $post_type = '' ) {
	return fct_post_callback( 'fct_the_%s', $post_type );
}

/** Is Functions **************************************************************/

/**
 * Check if current page is a Fiscaat period
 *
 * @param int $post_id Possible post_id to check
 * @uses fct_get_period_post_type() To get the period post type
 * @return bool True if it's a period page, false if not
 */
function fct_is_period( $post_id = 0 ) {

	// Assume false
	$retval = false;

	// Supplied ID is a period
	if ( ! empty( $post_id ) && ( fct_get_period_post_type() == get_post_type( $post_id ) ))
		$retval = true;

	return (bool) apply_filters( 'fct_is_period', $retval, $post_id );
}

/**
 * Check if we are viewing a period archive.
 *
 * @uses is_post_type_archive() To check if we are looking at the period archive
 * @uses fct_get_period_post_type() To get the period post type ID
 *
 * @return bool
 */
function fct_is_period_archive() {

	// Default to false
	$retval = false;

	// In period archive
	if ( is_post_type_archive( fct_get_period_post_type() ) || fct_is_query_name( 'fct_period_archive' ) )
		$retval = true;

	return (bool) apply_filters( 'fct_is_period_archive', $retval );
}

/**
 * Viewing a single period
 *
 * @uses is_single()
 * @uses fct_get_period_post_type()
 * @uses get_post_type()
 * @uses apply_filters()
 *
 * @return bool
 */
function fct_is_single_period() {

	// Assume false
	$retval = false;

	// Edit is not a single period
	if ( fct_is_period_edit() )
		return false;

	// Single and a match
	if ( is_singular( fct_get_period_post_type() ) || fct_is_query_name( 'fct_single_period' ) )
		$retval = true;

	return (bool) apply_filters( 'fct_is_single_period', $retval );
}

/**
 * Check if current page is a period edit page
 *
 * @uses WP_Query Checks if WP_Query::fct_is_period_edit is true
 * @return bool True if it's the period edit page, false if not
 */
function fct_is_period_edit() {
	global $wp_query, $pagenow;

	// Assume false
	$retval = false;

	// Check query
	if ( ! empty( $wp_query->fct_is_period_edit ) && ( $wp_query->fct_is_period_edit == true ) )
		$retval = true;

	// Editing in admin
	elseif ( is_admin() && ( 'post.php' == $pagenow ) && ( get_post_type() == fct_get_period_post_type() ) && ( ! empty( $_GET['action'] ) && ( 'edit' == $_GET['action'] ) ) )
		$retval = true;

	return (bool) apply_filters( 'fct_is_period_edit', $retval );
}

/**
 * Check if current page is a Fiscaat account
 *
 * @param int $post_id Possible post_id to check
 * @uses fct_get_account_post_type() To get the account post type
 * @uses get_post_type() To get the post type of the post id
 * @return bool True if it's a account page, false if not
 */
function fct_is_account( $post_id = 0 ) {

	// Assume false
	$retval = false;

	// Supplied ID is a account
	if ( ! empty( $post_id ) && ( fct_get_account_post_type() == get_post_type( $post_id ) ) )
		$retval = true;

	return (bool) apply_filters( 'fct_is_account', $retval, $post_id );
}

/**
 * Viewing a single account
 *
 * @uses is_single()
 * @uses fct_get_account_post_type()
 * @uses get_post_type()
 * @uses apply_filters()
 *
 * @return bool
 */
function fct_is_single_account() {

	// Assume false
	$retval = false;

	// Edit is not a single account
	if ( fct_is_account_edit() )
		return false;

	// Single and a match
	if ( is_singular( fct_get_account_post_type() ) || fct_is_query_name( 'fct_single_account' ) )
		$retval = true;

	return (bool) apply_filters( 'fct_is_single_account', $retval );
}

/**
 * Check if we are viewing a account archive.
 *
 * @uses is_post_type_archive() To check if we are looking at the account archive
 * @uses fct_get_account_post_type() To get the account post type ID
 *
 * @return bool
 */
function fct_is_account_archive() {

	// Default to false
	$retval = false;

	// In account archive
	if ( is_post_type_archive( fct_get_account_post_type() ) || fct_is_query_name( 'fct_account_archive' ) )
		$retval = true;

	return (bool) apply_filters( 'fct_is_account_archive', $retval );
}

/**
 * Check if current page is a account edit page
 *
 * @uses WP_Query Checks if WP_Query::fct_is_account_edit is true
 * @return bool True if it's the account edit page, false if not
 */
function fct_is_account_edit() {
	global $wp_query, $pagenow;

	// Assume false
	$retval = false;

	// Check query
	if ( ! empty( $wp_query->fct_is_account_edit ) && ( $wp_query->fct_is_account_edit == true ) )
		$retval = true;

	// Editing in admin
	elseif ( is_admin() && ( 'post.php' == $pagenow ) && ( get_post_type() == fct_get_account_post_type() ) && ( ! empty( $_GET['action'] ) && ( 'edit' == $_GET['action'] ) ) )
		$retval = true;

	return (bool) apply_filters( 'fct_is_account_edit', $retval );
}

/**
 * Check if the current post type is one of Fiscaat's
 *
 * @param mixed $the_post Optional. Post object or post ID.
 * @uses get_post_type()
 * @uses fct_get_period_post_type()
 * @uses fct_get_account_post_type()
 * @uses fct_get_record_post_type()
 *
 * @return bool
 */
function fct_is_custom_post_type( $the_post = false ) {

	// Assume false
	$retval = false;

	// Viewing one of the Fiscaat post types
	if ( in_array( get_post_type( $the_post ), array(
		fct_get_period_post_type(),
		fct_get_account_post_type(),
		fct_get_record_post_type()
	) ) )
		$retval = true;

	return (bool) apply_filters( 'fct_is_custom_post_type', $retval, $the_post );
}

/**
 * Check if current page is a Fiscaat record
 *
 * @param int $post_id Possible post_id to check
 * @uses fct_get_record_post_type() To get the record post type
 * @uses get_post_type() To get the post type of the post id
 * @return bool True if it's a record page, false if not
 */
function fct_is_record( $post_id = 0 ) {

	// Assume false
	$retval = false;

	// Supplied ID is a record
	if ( ! empty( $post_id ) && ( fct_get_record_post_type() == get_post_type( $post_id ) ) )
		$retval = true;

	return (bool) apply_filters( 'fct_is_record', $retval, $post_id );
}

/**
 * Check if current page is a record edit page
 *
 * @uses WP_Query Checks if WP_Query::fct_is_record_edit is true
 * @return bool True if it's the record edit page, false if not
 */
function fct_is_record_edit() {
	global $wp_query, $pagenow;

	// Assume false
	$retval = false;

	// Check query
	if ( ! empty( $wp_query->fct_is_record_edit ) && ( true == $wp_query->fct_is_record_edit ) )
		$retval = true;

	// Editing in admin
	elseif ( is_admin() && ( 'post.php' == $pagenow ) && ( get_post_type() == fct_get_record_post_type() ) && ( ! empty( $_GET['action'] ) && ( 'edit' == $_GET['action'] ) ) )
		$retval = true;

	return (bool) apply_filters( 'fct_is_record_edit', $retval );
}

/**
 * Viewing a single record
 *
 * @uses is_single()
 * @uses fct_get_record_post_type()
 * @uses get_post_type()
 * @uses apply_filters()
 *
 * @return bool
 */
function fct_is_single_record() {

	// Assume false
	$retval = false;

	// Edit is not a single record
	if ( fct_is_record_edit() )
		return false;

	// Single and a match
	if ( is_singular( fct_get_record_post_type() ) || ( fct_is_query_name( 'fct_single_record' ) ) )
		$retval = true;

	return (bool) apply_filters( 'fct_is_single_record', $retval );
}

/**
 * Check if current page is a view page
 *
 * @global WP_Query $wp_query To check if WP_Query::fct_is_view is true 
 * @uses fct_is_query_name() To get the query name
 * @return bool Is it a view page?
 */
function fct_is_single_view() {
	global $wp_query;

	// Assume false
	$retval = false;

	// Check query
	if ( ! empty( $wp_query->fct_is_view ) && ( true == $wp_query->fct_is_view ) )
		$retval = true;

	// Check query name
	if ( empty( $retval ) && fct_is_query_name( 'fct_single_view' ) )
		$retval = true;

	return (bool) apply_filters( 'fct_is_single_view', $retval );
}

/**
 * Check if current page is an edit page
 *
 * @uses WP_Query Checks if WP_Query::fct_is_edit is true
 * @return bool True if it's the edit page, false if not
 */
function fct_is_edit() {
	global $wp_query;

	// Assume false
	$retval = false;

	// Check query
	if ( ! empty( $wp_query->fct_is_edit ) && ( $wp_query->fct_is_edit == true ) )
		$retval = true;

	return (bool) apply_filters( 'fct_is_edit', $retval );
}

/**
 * Use the above is_() functions to output a body class for each scenario
 *
 * @since Fiscaat (r2926)
 *
 * @param array $wp_classes
 * @param array $custom_classes
 * @uses fct_is_single_period()
 * @uses fct_is_single_account()
 * @uses fct_is_single_record()
 * @uses fct_is_record_edit()
 * @uses fct_is_single_view()
 * @uses fct_is_period_archive()
 * @uses fct_is_account_archive()
 * @return array Body Classes
 */
function fct_body_class( $wp_classes, $custom_classes = false ) {

	$fct_classes = array();

	/** Archives **************************************************************/

	if ( fct_is_period_archive() )
		$fct_classes[] = fct_get_period_post_type() . '-archive';

	if ( fct_is_account_archive() )
		$fct_classes[] = fct_get_account_post_type() . '-archive';

	/** Components ************************************************************/

	if ( fct_is_single_period() )
		$fct_classes[] = fct_get_period_post_type();

	if ( fct_is_single_account() )
		$fct_classes[] = fct_get_account_post_type();

	if ( fct_is_single_record() )
		$fct_classes[] = fct_get_record_post_type();

	if ( fct_is_account_edit() )
		$fct_classes[] = fct_get_account_post_type() . '-edit';

	if ( fct_is_record_edit() )
		$fct_classes[] = fct_get_record_post_type() . '-edit';

	if ( fct_is_single_view() )
		$fct_classes[] = 'fiscaat-view';

	/** Clean up **************************************************************/

	// Add Fiscaat class if we are within a Fiscaat page
	if ( ! empty( $fct_classes ) )
		$fct_classes[] = 'Fiscaat';

	// Merge WP classes with Fiscaat classes and remove any duplicates
	$classes = array_unique( array_merge( (array) $fct_classes, (array) $wp_classes ) );

	return apply_filters( 'fct_get_the_body_class', $classes, $fct_classes, $wp_classes, $custom_classes );
}

/**
 * Use the above is_() functions to return if in any Fiscaat page
 *
 * @uses fct_is_single_period()
 * @uses fct_is_single_account()
 * @uses fct_is_single_record()
 * @uses fct_is_record_edit()
 * @uses fct_is_record_edit()
 * @uses fct_is_single_view()
 * @return bool In a Fiscaat page
 */
function is_fiscaat() {

	// Defalt to false
	$retval = false;

	/** Archives **************************************************************/

	if ( fct_is_period_archive() )
		$retval = true;

	elseif ( fct_is_account_archive() )
		$retval = true;

	/** Components ************************************************************/

	elseif ( fct_is_single_period() )
		$retval = true;

	elseif ( fct_is_single_account() )
		$retval = true;

	elseif ( fct_is_single_record() )
		$retval = true;

	elseif ( fct_is_account_edit() )
		$retval = true;

	elseif ( fct_is_record_edit() )
		$retval = true;

	elseif ( fct_is_single_view() )
		$retval = true;

	/** Done ******************************************************************/

	return (bool) apply_filters( 'is_fiscaat', $retval );
}

/** Listeners *****************************************************************/

/**
 * Check if it's a period or a account or record of a period and if
 * the user can't view it, then sets a 404
 *
 * @uses current_user_can() To check if the current user can fiscaat
 * @uses is_singular() To check if it's a singular page
 * @uses fct_get_period_post_type() To get the period post type
 * @uses fct_get_account_post_type() To get the account post type
 * @uses fct_get_record_post_type() To get the record post type
 * @uses fct_get_account_period_id() To get the account period id
 * @uses fct_get_record_period_id() To get the record period id
 * @uses fct_user_can_spectate() To check if the period is closed or not
 * @uses fct_set_404() To set a 404 status
 */
function fct_enforce_404() {

	// Bail if not viewing a single item or if user has caps
	if ( ! is_singular() || current_user_can( 'fct_spectate' ) ) // || is_super_admin() ?
		return;

	global $wp_query;

	// Define local variables
	$period_id = 0;

	// Check post type
	switch ( $wp_query->get( 'post_type' ) ) {

		// Period
		case fct_get_period_post_type() :
			$period_id = fct_get_period_id( $wp_query->post->ID );
			break;

		// Topic
		case fct_get_account_post_type() :
			$period_id = fct_get_account_period_id( $wp_query->post->ID );
			break;

		// Reply
		case fct_get_record_post_type() :
			$period_id = fct_get_record_period_id( $wp_query->post->ID );
			break;
	}

	// If page is Fiscaat and user is not capable, set 404
	if ( ! empty( $period_id ) && ! fct_user_can_spectate( $wp_query->post->ID ) )
		fct_set_404();
}

/** Forms *********************************************************************/

/**
 * Output hidden request URI field for user forms.
 *
 * The referer link is the current Request URI from the server super global. The
 * input name is '_wp_http_referer', in case you wanted to check manually.
 *
 * @param string $url Pass a URL to redirect to
 * @uses wp_get_referer() To get the referer
 * @uses esc_attr() To escape the url
 * @uses apply_filters() Calls 'fct_redirect_to_field' with the referer field
 *                        and url
 */
function fct_redirect_to_field( $redirect_to = '' ) {

	// Rejig the $redirect_to
	if ( !isset( $_SERVER['REDIRECT_URL'] ) || ( !$redirect_to == home_url( $_SERVER['REDIRECT_URL'] ) ) )
		$redirect_to = wp_get_referer();

	// Make sure we are directing somewhere
	if ( empty( $redirect_to ) )
		$redirect_to = home_url( isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '' );

	// Remove loggedout query arg if it's there
	$redirect_to    = (string) esc_attr( remove_query_arg( 'loggedout', $redirect_to ) );
	$redirect_field = '<input type="hidden" id="fct_redirect_to" name="redirect_to" value="' . $redirect_to . '" />';

	echo apply_filters( 'fct_redirect_to_field', $redirect_field, $redirect_to );
}

/**
 * Echo sanitized $_REQUEST value.
 *
 * Use the $input_type parameter to properly process the value. This
 * ensures correct sanitization of the value for the receiving input.
 *
 * @param string $request Name of $_REQUEST to look for
 * @param string $input_type Type of input. Default: text. Accepts:
 *                            textarea|password|select|radio|checkbox
 * @uses fct_get_sanitize_val() To sanitize the value.
 */
function fct_sanitize_val( $request = '', $input_type = 'text' ) {
	echo fct_get_sanitize_val( $request, $input_type );
}

	/**
	 * Return sanitized $_REQUEST value.
	 *
	 * Use the $input_type parameter to properly process the value. This
	 * ensures correct sanitization of the value for the receiving input.
	 *
	 * @param string $request Name of $_REQUEST to look for
	 * @param string $input_type Type of input. Default: text. Accepts:
	 *                            textarea|password|select|radio|checkbox
	 * @uses esc_attr() To escape the string
	 * @uses apply_filters() Calls 'fct_get_sanitize_val' with the sanitized
	 *                        value, request and input type
	 * @return string Sanitized value ready for screen display
	 */
	function fct_get_sanitize_val( $request = '', $input_type = 'text' ) {

		// Check that requested
		if ( empty( $_REQUEST[$request] ) )
			return false;

		// Set request varaible
		$pre_ret_val = $_REQUEST[$request];

		// Treat different kinds of fields in different ways
		switch ( $input_type ) {
			case 'text'     :
			case 'textarea' :
				$retval = esc_attr( stripslashes( $pre_ret_val ) );
				break;

			case 'password' :
			case 'select'   :
			case 'radio'    :
			case 'checkbox' :
			default :
				$retval = esc_attr( $pre_ret_val );
				break;
		}

		return apply_filters( 'fct_get_sanitize_val', $retval, $request, $input_type );
	}

/**
 * Output the current tab index of a given form in html
 *
 * Use this function to handle the tab indexing of user facing forms within a
 * template file. Calling this function will automatically increment the global
 * tab index by default.
 *
 * @param int $auto_increment Optional. Default true. Set to false to prevent
 *                             increment
 */
function fct_tab_index_attr( $auto_increment = true ) {
	echo 'tabindex="' . fct_get_tab_index( $auto_increment ) . '"';
}

/**
 * Output the current tab index of a given form
 *
 * Use this function to handle the tab indexing of user facing forms within a
 * template file. Calling this function will automatically increment the global
 * tab index by default.
 *
 * @param int $auto_increment Optional. Default true. Set to false to prevent
 *                             increment
 */
function fct_tab_index( $auto_increment = true ) {
	echo fct_get_tab_index( $auto_increment );
}

	/**
	 * Output the current tab index of a given form
	 *
	 * Use this function to handle the tab indexing of user facing forms
	 * within a template file. Calling this function will automatically
	 * increment the global tab index by default.
	 *
	 * @uses apply_filters Allows return value to be filtered
	 * @param int $auto_increment Optional. Default true. Set to false to
	 *                             prevent the increment
	 * @return int $fiscaat->tab_index The global tab index
	 */
	function fct_get_tab_index( $auto_increment = true ) {
		$fiscaat = fiscaat();

		if ( true === $auto_increment )
			++$fiscaat->tab_index;

		return apply_filters( 'fct_get_tab_index', (int) $fiscaat->tab_index );
	}

/**
 * Output a select box allowing to pick which period/account a new account/record
 * belongs in.
 *
 * Can be used for any post type, but is mostly used for accounts and periods.
 *
 * @param mixed $args See {@link fct_get_dropdown()} for arguments
 */
function fct_dropdown( $args = '' ) {
	echo fct_get_dropdown( $args );
}

	/**
	 * Output a select box allowing to pick which period/account a new
	 * account/record belongs in.
	 *
	 * @param mixed $args The function supports these args:
	 *  - post_type: Post type, defaults to fct_get_period_post_type() (fct_period)
	 *  - selected: Selected ID, to not have any value as selected, pass
	 *               anything smaller than 0 (due to the nature of select
	 *               box, the first value would of course be selected -
	 *               though you can have that as none (pass 'show_none' arg))
	 *  - sort_column: Sort by? Defaults to 'menu_order, post_title'
	 *  - post_parent: Child of. Defaults to 0
	 *  - post_status: Which all post_statuses to find in? Can be an array
	 *                  or CSV of publish, category, closed, private, spam,
	 *                  trash (based on post type) - if not set, these are
	 *                  automatically determined based on the post_type
	 *  - posts_per_page: Retrieve all periods/accounts. Defaults to -1 to get
	 *                     all posts
	 *  - walker: Which walker to use? Defaults to {@link Fiscaat_Walker_Dropdown}
	 *  - select_id: ID of the select box. Defaults to 'fct_period_id'
	 *  - tab: Tabindex value. False or integer
	 *  - options_only: Show only <options>? No <select>?
	 *  - show_none: False or something like __( '(No Period)', 'fiscaat' ),
	 *                will have value=""
	 *  - none_found: False or something like
	 *                 __( 'No periods to post to!', 'fiscaat' )
	 *  - disable_closed: Disable closed periods? Defaults to true. Only for periods.
	 * @uses Fiscaat_Walker_Dropdown() As the default walker to generate the
	 *                              dropdown
	 * @uses current_user_can() To check if the current user can read
	 *                           private periods
	 * @uses fct_get_period_post_type() To get the period post type
	 * @uses fct_get_account_post_type() To get the account post type
	 * @uses walk_page_dropdown_tree() To generate the dropdown using the
	 *                                  walker
	 * @uses apply_filters() Calls 'fct_get_dropdown' with the dropdown
	 *                        and args
	 * @return string The dropdown
	 */
	function fct_get_dropdown( $args = '' ) {

		/** Arguments *********************************************************/

		$r = fct_parse_args( $args, array(
			'post_type'          => fct_get_period_post_type(),
			'post_parent'        => null,
			'post_status'        => null,
			'selected'           => 0,
			'exclude'            => array(),
			'numberposts'        => -1,
			'meta_key'           => null,
			'meta_value'         => null,
			'orderby'            => 'menu_order title',
			'order'              => 'ASC',
			'walker'             => '',

			// Output-related
			'select_id'          => 'fct_period_id',
			'select_name'        => false, // Custom
			'class'              => false, // Custom
			'tab'                => fct_get_tab_index(),
			'options_only'       => false,
			'show_none'          => false,
			'none_found'         => false,
			'disable_categories' => false,
			'disable_closed'     => false,
			'disabled'           => ''
		), 'get_dropdown' );

		if ( empty( $r['walker'] ) ) {
			$r['walker']            = new Fiscaat_Walker_Dropdown();
			$r['walker']->tree_type = $r['post_type'];
		}

		// Force 0
		if ( is_numeric( $r['selected'] ) && $r['selected'] < 0 )
			$r['selected'] = 0;

		// Force array
		if ( ! empty( $r['exclude'] ) && ! is_array( $r['exclude'] ) ) {
			$r['exclude'] = explode( ',', $r['exclude'] );
		}

		/** Post Status *******************************************************/

		// Force array
		if ( ! empty( $r['post_status'] ) && ! is_array( $r['post_status'] ) ) {
			$r['post_status'] = explode( ',', $r['post_status'] );
		}

		// Public
		if ( empty( $r['post_status'] ) ) {
			$r['post_status'] = array( fct_get_public_status_id() );
		}

		// Closed
		if ( ! $r['disable_closed'] && ! in_array( fct_get_closed_status_id(), $r['post_status'] ) ) {
			$r['post_status'][] = fct_get_closed_status_id();
		}

		/** Setup variables ***************************************************/

		$retval = '';
		$posts  = get_posts( array(
			'post_type'          => $r['post_type'],
			'post_status'        => $r['post_status'],
			'exclude'            => $r['exclude'],
			'post_parent'        => $r['post_parent'],
			'numberposts'        => $r['numberposts'],
			'meta_key'           => $r['meta_key'],
			'meta_value'         => $r['meta_value'],
			'orderby'            => $r['orderby'],
			'order'              => $r['order'],
			'walker'             => $r['walker'],
			'disable_categories' => $r['disable_categories']
		) );

		/** Drop Down *********************************************************/

		// Build the opening tag for the select element
		if ( empty( $r['options_only'] ) ) {

			// Should this select appear disabled?
			$disabled  = disabled( $r['disabled'], true, false );

			// Setup the tab index attribute
			$tab       = !empty( $r['tab'] ) ? ' tabindex="' . intval( $r['tab'] ) . '"' : '';

			// Open the select tag
			$retval   .= '<select name="' . esc_attr( $r['select_id'] ) . '" id="' . esc_attr( $r['select_id'] ) . '"' . $disabled . $tab . '>' . "\n";
		}

		// Display a leading 'no-value' option, with or without custom text
		if ( ! empty( $r['show_none'] ) || ! empty( $r['none_found'] ) ) {

			// Open the 'no-value' option tag
			$retval .= "\t<option value=\"\" class=\"level-0\">";

			// Use deprecated 'none_found' first for backpat
			if ( ! empty( $r['none_found'] ) && is_string( $r['none_found'] ) ) {
				$retval .= esc_html( $r['none_found'] );

			// Use 'show_none' second
			} elseif ( ! empty( $r['show_none'] ) && is_string( $r['show_none'] ) ) {
				$retval .= esc_html( $r['show_none'] );

			// Otherwise, make some educated guesses
			} else {

				// Switch the response based on post type
				switch ( $post_type ) {

					// Accounts
					case fct_get_account_post_type() :
						$retval .= __( '&mdash; No accounts &mdash;', 'fiscaat' );
						break;

					// Periods
					case fct_get_period_post_type() :
						$retval .= __( '&mdash; No periods &mdash;', 'fiscaat' );
						break;

					// Any other
					default :
						$retval .= __( '&mdash; None &mdash;', 'fiscaat' );
						break;
				}

			}

			// Close the 'no-value' option tag
			$retval .= '</option>';
		}

		// Items found so walk the tree
		if ( ! empty( $posts ) ) {
			$retval .= walk_page_dropdown_tree( $posts, 0, $r );
		}

		// Close the select tag
		if ( empty( $options_only ) ) {
			$retval .= '</select>';
		}

		return apply_filters( 'fct_get_dropdown', $retval, $r );
	}

/**
 * Output the required hidden fields when creating/editing a period
 *
 * @since Fiscaat (r3553)
 *
 * @uses fct_is_period_edit() To check if it's the period edit page
 * @uses wp_nonce_field() To generate hidden nonce fields
 * @uses fct_period_id() To output the period id
 * @uses fct_is_single_period() To check if it's a period page
 * @uses fct_period_id() To output the period id
 */
function fct_period_form_fields() {

	if ( fct_is_period_edit() ) : ?>

		<input type="hidden" name="action"          id="fct_post_action" value="fiscaat-edit-period" />
		<input type="hidden" name="fct_period_id" id="fct_period_id"     value="<?php fct_period_id(); ?>" />

		<?php

		if ( current_user_can( 'unfiltered_html' ) )
			wp_nonce_field( 'fiscaat-unfiltered-html-period_' . fct_get_period_id(), '_fct_unfiltered_html_period', false );

		?>

		<?php wp_nonce_field( 'fiscaat-edit-period_' . fct_get_period_id() );

	else :

		if ( fct_is_single_period() ) : ?>

			<input type="hidden" name="fct_period_parent_id" id="fct_period_parent_id" value="<?php fct_period_parent_id(); ?>" />

		<?php endif; ?>

			<input type="hidden" name="action" id="fct_post_action" value="fiscaat-new-period" />

		<?php

		if ( current_user_can( 'unfiltered_html' ) )
			wp_nonce_field( 'fiscaat-unfiltered-html-period_new', '_fct_unfiltered_html_period', false );

		?>

		<?php wp_nonce_field( 'fiscaat-new-period' );

	endif;
}

/**
 * Output the required hidden fields when creating/editing a account
 *
 * @since Fiscaat (r2753)
 *
 * @uses fct_is_account_edit() To check if it's the account edit page
 * @uses wp_nonce_field() To generate hidden nonce fields
 * @uses fct_account_id() To output the account id
 * @uses fct_is_single_period() To check if it's a period page
 * @uses fct_period_id() To output the period id
 */
function fct_account_form_fields() {

	if ( fct_is_account_edit() ) : ?>

		<input type="hidden" name="action"       id="fct_post_action" value="fiscaat-edit-account" />
		<input type="hidden" name="fct_account_id" id="fct_account_id"    value="<?php fct_account_id(); ?>" />

		<?php

		if ( current_user_can( 'unfiltered_html' ) )
			wp_nonce_field( 'fiscaat-unfiltered-html-account_' . fct_get_account_id(), '_fct_unfiltered_html_account', false );

		?>

		<?php wp_nonce_field( 'fiscaat-edit-account_' . fct_get_account_id() );

	else :

		if ( fct_is_single_period() ) : ?>

			<input type="hidden" name="fct_period_id" id="fct_period_id" value="<?php fct_period_id(); ?>" />

		<?php endif; ?>

			<input type="hidden" name="action" id="fct_post_action" value="fiscaat-new-account" />

		<?php if ( current_user_can( 'unfiltered_html' ) )
			wp_nonce_field( 'fiscaat-unfiltered-html-account_new', '_fct_unfiltered_html_account', false ); ?>

		<?php wp_nonce_field( 'fiscaat-new-account' );

	endif;
}

/**
 * Output the required hidden fields when creating/editing a record
 *
 * @since Fiscaat (r2753)
 *
 * @uses fct_is_record_edit() To check if it's the record edit page
 * @uses wp_nonce_field() To generate hidden nonce fields
 * @uses fct_record_id() To output the record id
 * @uses fct_account_id() To output the account id
 * @uses fct_period_id() To output the period id
 */
function fct_record_form_fields() {

	if ( fct_is_record_edit() ) : ?>

		<input type="hidden" name="fct_record_title" id="fct_record_title" value="<?php printf( __( 'Record To: %s', 'fiscaat' ), fct_get_account_title() ); ?>" />
		<input type="hidden" name="fct_record_id"    id="fct_record_id"    value="<?php fct_record_id(); ?>" />
		<input type="hidden" name="action"           id="fct_post_action"  value="fiscaat-edit-record" />

		<?php if ( current_user_can( 'unfiltered_html' ) )
			wp_nonce_field( 'fiscaat-unfiltered-html-record_' . fct_get_record_id(), '_fct_unfiltered_html_record', false ); ?>

		<?php wp_nonce_field( 'fiscaat-edit-record_' . fct_get_record_id() );

	else : ?>

		<input type="hidden" name="fct_record_title" id="fct_record_title" value="<?php printf( __( 'Record To: %s', 'fiscaat' ), fct_get_account_title() ); ?>" />
		<input type="hidden" name="fct_account_id"   id="fct_account_id"   value="<?php fct_account_id(); ?>" />
		<input type="hidden" name="action"           id="fct_post_action"  value="fiscaat-new-record" />

		<?php if ( current_user_can( 'unfiltered_html' ) )
			wp_nonce_field( 'fiscaat-unfiltered-html-record_' . fct_get_account_id(), '_fct_unfiltered_html_record', false ); ?>

		<?php wp_nonce_field( 'fiscaat-new-record' );

		// Show redirect field if not viewing a specific account
		if ( fct_is_query_name( 'fct_single_account' ) ) :
			fct_redirect_to_field( get_permalink() );

		endif;
	endif;
}

/**
 * Output a textarea or TinyMCE if enabled
 *
 * @param array $args
 * @uses fct_get_the_content() To return the content to output
 */
function fct_the_content( $args = array() ) {
	echo fct_get_the_content( $args );
}

	/**
	 * Return a textarea or TinyMCE if enabled
	 *
	 * @param array $args
	 *
	 * @uses apply_filter() To filter args and output
	 * @uses wp_parse_pargs() To compare args
	 * @uses fct_use_wp_editor() To see if WP editor is in use
	 * @uses fct_is_edit() To see if we are editing something
	 * @uses wp_editor() To output the WordPress editor
	 *
	 * @return string HTML from output buffer 
	 */
	function fct_get_the_content( $args = array() ) {

		// Default arguments
		$defaults = array(
			'context'       => 'account',
			'before'        => '<div class="fiscaat-the-content-wrapper">',
			'after'         => '</div>',
			'wpautop'       => true,
			'media_buttons' => false,
			'textarea_rows' => '12',
			'tabindex'      => fct_get_tab_index(),
			'editor_class'  => 'fiscaat-the-content',
			'tinymce'       => true,
			'teeny'         => true,
			'quicktags'     => true
		);
		$r = fct_parse_args( $args, $defaults, 'get_the_content' );
		extract( $r );

		// Assume we are not editing
		$post_content = '';

		// Start an output buffor
		ob_start();

		// Output something before the editor
		if ( ! empty( $before ) )
			echo $before;

		// Get sanitized content
		if ( fct_is_edit() )
			$post_content = call_user_func( 'fct_get_form_' . $context . '_content' );

		// Use TinyMCE if available
		if ( fct_use_wp_editor() ) :
			wp_editor( htmlspecialchars_decode( $post_content, ENT_QUOTES ), 'fct_' . $context . '_content', array(
				'wpautop'       => $wpautop,
				'media_buttons' => $media_buttons,
				'textarea_rows' => $textarea_rows,
				'tabindex'      => $tabindex,
				'editor_class'  => $editor_class,
				'tinymce'       => $tinymce,
				'teeny'         => $teeny,
				'quicktags'     => $quicktags
			) );

		/**
		 * Fallback to normal textarea.
		 *
		 * Note that we do not use esc_textarea() here to prevent double
		 * escaping the editable output, mucking up existing content.
		 */
		else : ?>

			<textarea id="fct_<?php echo esc_attr( $context ); ?>_content" class="<?php echo esc_attr( $editor_class ); ?>" name="fct_<?php echo esc_attr( $context ); ?>_content" cols="60" rows="<?php echo esc_attr( $textarea_rows ); ?>" tabindex="<?php echo esc_attr( $tabindex ); ?>"><?php echo $post_content; ?></textarea>

		<?php endif;

		// Output something after the editor
		if ( ! empty( $after ) )
			echo $after;

		// Put the output into a usable variable
		$output = ob_get_contents();

		// Flush the output buffer
		ob_end_clean();

		return apply_filters( 'fct_get_the_content', $output, $args, $post_content );
	}

/** Query *********************************************************************/

/**
 * Check the passed parameter against the current _fct_query_name
 *
 * @uses fct_get_query_name() Get the query var '_fct_query_name'
 * @return bool True if match, false if not
 */
function fct_is_query_name( $name = '' )  {
	return (bool) ( fct_get_query_name() == $name );
}

/**
 * Get the '_fct_query_name' setting
 *
 * @uses get_query_var() To get the query var '_fct_query_name'
 * @return string To return the query var value
 */
function fct_get_query_name()  {
	return get_query_var( '_fct_query_name' );
}

/**
 * Set the '_fct_query_name' setting to $name
 *
 * @param string $name What to set the query var to
 * @uses set_query_var() To set the query var '_fct_query_name'
 */
function fct_set_query_name( $name = '' )  {
	set_query_var( '_fct_query_name', $name );
}

/**
 * Used to clear the '_fct_query_name' setting
 *
 * @uses fct_set_query_name() To set the query var '_fct_query_name' value to ''
 */
function fct_reset_query_name() {
	fct_set_query_name();
}

/** Breadcrumbs ***************************************************************/

/**
 * Output a breadcrumb
 *
 * @param string $sep Separator. Defaults to '&larr;'
 * @param bool $current_page Include the current item
 * @param bool $root Include the root page if one exists
 * @uses fct_get_breadcrumb() To get the breadcrumb
 */
function fct_breadcrumb( $args = array() ) {
	echo fct_get_breadcrumb( $args );
}

	/**
	 * Return a breadcrumb ( period -> account -> record )
	 *
	 * @since Fiscaat (r2589)
	 *
	 * @param string $sep Separator. Defaults to '&larr;'
	 * @param bool $current_page Include the current item
	 * @param bool $root Include the root page if one exists
	 *
	 * @uses get_post() To get the post
	 * @uses fct_get_period_permalink() To get the period link
	 * @uses fct_get_account_permalink() To get the account link
	 * @uses fct_get_record_permalink() To get the record link
	 * @uses get_permalink() To get the permalink
	 * @uses fct_get_period_post_type() To get the period post type
	 * @uses fct_get_account_post_type() To get the account post type
	 * @uses fct_get_record_post_type() To get the record post type
	 * @uses fct_get_period_title() To get the period title
	 * @uses fct_get_account_title() To get the account title
	 * @uses fct_get_record_title() To get the record title
	 * @uses get_the_title() To get the title
	 * @uses apply_filters() Calls 'fct_get_breadcrumb' with the crumbs
	 * @return string Breadcrumbs
	 */
	function fct_get_breadcrumb( $args = array() ) {

		// Turn off breadcrumbs
		if ( apply_filters( 'fct_no_breadcrumb', is_front_page() ) )
			return;

		// Define variables
		$front_id         = $root_id                                 = 0;
		$ancestors        = $crumbs           = $tag_data            = array();
		$pre_root_text    = $pre_front_text   = $pre_current_text    = '';
		$pre_include_root = $pre_include_home = $pre_include_current = true;

		/** Home Text *********************************************************/

		// No custom home text
		if ( empty( $args['home_text'] ) ) {

			// Set home text to page title
			$front_id = get_option( 'page_on_front' );
			if ( ! empty( $front_id ) ) {
				$pre_front_text = get_the_title( $front_id );

			// Default to 'Home'
			} else {
				$pre_front_text = __( 'Home', 'fiscaat' );
			}
		}

		/** Root Text *********************************************************/

		// No custom root text
		if ( empty( $args['root_text'] ) ) {
			$page = fct_get_page_by_path( fct_get_root_slug() );
			if ( ! empty( $page ) ) {
				$root_id = $page->ID;
			}
			$pre_root_text = fct_get_period_archive_title();
		}

		/** Includes **********************************************************/

		// Root slug is also the front page
		if ( ! empty( $front_id ) && ( $front_id == $root_id ) )
			$pre_include_root = false;

		// Don't show root if viewing period archive
		if ( fct_is_period_archive() )
			$pre_include_root = false;

		// Don't show root if viewing page in place of period archive
		if ( ! empty( $root_id ) && ( ( is_single() || is_page() ) && ( $root_id == get_the_ID() ) ) )
			$pre_include_root = false;

		/** Current Text ******************************************************/

		// Period archive
		if ( fct_is_period_archive() ) {
			$pre_current_text = fct_get_period_archive_title();

		// Account archive
		} elseif ( fct_is_account_archive() ) {
			$pre_current_text = fct_get_account_archive_title();

		// Single Period
		} elseif ( fct_is_single_period() ) {
			$pre_current_text = fct_get_period_title();

		// Single Account
		} elseif ( fct_is_single_account() ) {
			$pre_current_text = fct_get_account_title();

		// Single Account
		} elseif ( fct_is_single_record() ) {
			$pre_current_text = fct_get_record_title();

		// Single
		} else {
			$pre_current_text = get_the_title();
		}

		/** Parse Args ********************************************************/

		// Parse args
		$defaults = array(

			// HTML
			'before'          => '<div class="fiscaat-breadcrumb"><p>',
			'after'           => '</p></div>',
			
			// Separator
			'sep'             => __( '&rsaquo;', 'fiscaat' ),
			'pad_sep'         => 1,
			'sep_before'      => '<span class="fiscaat-breadcrumb-sep">',
			'sep_after'       => '</span>',
			
			// Crumbs
			'crumb_before'    => '',
			'crumb_after'     => '',

			// Home
			'include_home'    => $pre_include_home,
			'home_text'       => $pre_front_text,

			// Period root
			'include_root'    => $pre_include_root,
			'root_text'       => $pre_root_text,

			// Current
			'include_current' => $pre_include_current,
			'current_text'    => $pre_current_text,
			'current_before'  => '<span class="fiscaat-breadcrumb-current">',
			'current_after'   => '</span>',
		);
		$r = fct_parse_args( $args, $defaults, 'get_breadcrumb' );
		extract( $r );

		/** Ancestors *********************************************************/

		// Get post ancestors
		if ( is_page() || is_single() || fct_is_period_edit() || fct_is_account_edit() || fct_is_record_edit() )
			$ancestors = array_reverse( (array) get_post_ancestors( get_the_ID() ) );

		// Do we want to include a link to home?
		if ( ! empty( $include_home ) || empty( $home_text ) )
			$crumbs[] = '<a href="' . trailingslashit( home_url() ) . '" class="fiscaat-breadcrumb-home">' . $home_text . '</a>';

		// Do we want to include a link to the period root?
		if ( ! empty( $include_root ) || empty( $root_text ) ) {

			// Page exists at root slug path, so use its permalink
			$page = fct_get_page_by_path( fct_get_root_slug() );
			if ( ! empty( $page ) ) {
				$root_url = get_permalink( $page->ID );

			// Use the root slug
			} else {
				$root_url = get_post_type_archive_link( fct_get_period_post_type() );
			}

			// Add the breadcrumb
			$crumbs[] = '<a href="' . $root_url . '" class="fiscaat-breadcrumb-root">' . $root_text . '</a>';
		}

		// Ancestors exist
		if ( ! empty( $ancestors ) ) {

			// Loop through parents
			foreach( (array) $ancestors as $parent_id ) {

				// Parents
				$parent = get_post( $parent_id );

				// Switch through post_type to ensure correct filters are applied
				switch ( $parent->post_type ) {

					// Period
					case fct_get_period_post_type() :
						$crumbs[] = '<a href="' . fct_get_period_permalink( $parent->ID ) . '" class="fiscaat-breadcrumb-period">' . fct_get_period_title( $parent->ID ) . '</a>';
						break;

					// Account
					case fct_get_account_post_type() :
						$crumbs[] = '<a href="' . fct_get_account_permalink( $parent->ID ) . '" class="fiscaat-breadcrumb-account">' . fct_get_account_title( $parent->ID ) . '</a>';
						break;

					// Record (Note: not in most themes)
					case fct_get_record_post_type() :
						$crumbs[] = '<a href="' . fct_get_record_permalink( $parent->ID ) . '" class="fiscaat-breadcrumb-record">' . fct_get_record_title( $parent->ID ) . '</a>';
						break;

					// WordPress Post/Page/Other
					default :
						$crumbs[] = '<a href="' . get_permalink( $parent->ID ) . '" class="fiscaat-breadcrumb-item">' . get_the_title( $parent->ID ) . '</a>';
						break;
				}
			}
		}

		/** Current ***********************************************************/

		// Add current page to breadcrumb
		if ( ! empty( $include_current ) || empty( $pre_current_text ) )
			$crumbs[] = $current_before . $current_text . $current_after;

		/** Separator *********************************************************/

		// Wrap the separator in before/after before padding and filter
		if ( ! empty( $sep ) )
			$sep = $sep_before . $sep . $sep_after;

		// Pad the separator
		if ( ! empty( $pad_sep ) )
			$sep = str_pad( $sep, strlen( $sep ) + ( (int) $pad_sep * 2 ), ' ', STR_PAD_BOTH );

		/** Finish Up *********************************************************/

		// Filter the separator and breadcrumb
		$sep    = apply_filters( 'fct_breadcrumb_separator', $sep    );
		$crumbs = apply_filters( 'fct_breadcrumbs',          $crumbs );

		// Build the trail
		$trail = ! empty( $crumbs ) ? ( $before . $crumb_before . implode( $sep . $crumb_after . $crumb_before , $crumbs ) . $crumb_after . $after ) : '';

		return apply_filters( 'fct_get_breadcrumb', $trail, $crumbs, $r );
	}

/** Errors & Messages *********************************************************/

/**
 * Display possible errors & messages inside a template file
 *
 * @uses WP_Error Fiscaat::errors::get_error_codes() To get the error codes
 * @uses WP_Error Fiscaat::errors::get_error_data() To get the error data
 * @uses WP_Error Fiscaat::errors::get_error_messages() To get the error
 *                                                       messages
 * @uses is_wp_error() To check if it's a {@link WP_Error}
 */
function fct_template_notices() {

	// Bail if no notices or errors
	if ( !fct_has_errors() )
		return;

	// Define local variable(s)
	$errors = $messages = array();

	// Get Fiscaat
	$fiscaat = fiscaat();

	// Loop through notices
	foreach ( $fiscaat->errors->get_error_codes() as $code ) {

		// Get notice severity
		$severity = $fiscaat->errors->get_error_data( $code );

		// Loop through notices and separate errors from messages
		foreach ( $fiscaat->errors->get_error_messages( $code ) as $error ) {
			if ( 'message' == $severity ) {
				$messages[] = $error;
			} else {
				$errors[]   = $error;
			}
		}
	}

	// Display errors first...
	if ( ! empty( $errors ) ) : ?>

		<div class="fiscaat-template-notice error">
			<p>
				<?php echo implode( "</p>\n<p>", $errors ); ?>
			</p>
		</div>

	<?php endif;

	// ...and messages last
	if ( ! empty( $messages ) ) : ?>

		<div class="fiscaat-template-notice">
			<p>
				<?php echo implode( "</p>\n<p>", $messages ); ?>
			</p>
		</div>

	<?php endif;
}

/** Title *********************************************************************/

/**
 * Custom page title for Fiscaat pages
 *
 * @since Fiscaat (r2788)
 *
 * @param string $title Optional. The title (not used).
 * @param string $sep Optional, default is '&raquo;'. How to separate the
 *                     various items within the page title.
 * @param string $seplocation Optional. Direction to display title, 'right'.
 * @uses fct_is_single_user() To check if it's a user profile page
 * @uses fct_is_single_user_edit() To check if it's a user profile edit page
 * @uses fct_is_user_home() To check if the profile page is of the current user
 * @uses get_query_var() To get the user id
 * @uses get_userdata() To get the user data
 * @uses fct_is_single_period() To check if it's a period
 * @uses fct_get_period_title() To get the period title
 * @uses fct_is_single_account() To check if it's a account
 * @uses fct_get_account_title() To get the account title
 * @uses fct_is_single_record() To check if it's a record
 * @uses fct_get_record_title() To get the record title
 * @uses is_tax() To check if it's the tag page
 * @uses get_queried_object() To get the queried object
 * @uses fct_is_single_view() To check if it's a view
 * @uses fct_get_view_title() To get the view title
 * @uses apply_filters() Calls 'fct_raw_title' with the title
 * @uses apply_filters() Calls 'fct_profile_page_wp_title' with the title,
 *                        separator and separator location
 * @return string The tite
 */
function fct_title( $title = '', $sep = '&raquo;', $seplocation = '' ) {

	// Store original title to compare
	$_title = $title;

	/** Archives **************************************************************/

	// Period Archive
	if ( fct_is_period_archive() ) {
		$title = fct_get_period_archive_title();

	// Account Archive
	} elseif ( fct_is_account_archive() ) {
		$title = fct_get_account_archive_title();

	/** Singles ***************************************************************/

	// Period page
	} elseif ( fct_is_single_period() ) {
		$title = sprintf( __( 'Period: %s', 'fiscaat' ), fct_get_period_title() );

	// Account page
	} elseif ( fct_is_single_account() ) {
		$title = sprintf( __( 'Account: %s', 'fiscaat' ), fct_get_account_title() );

	// Records
	} elseif ( fct_is_single_record() ) {
		$title = fct_get_record_title();
	}

	// Filter the raw title
	$title = apply_filters( 'fct_raw_title', $title, $sep, $seplocation );

	// Compare new title with original title
	if ( $title == $_title )
		return $title;

	// Temporary separator, for accurate flipping, if necessary
	$t_sep  = '%WP_TITILE_SEP%';
	$prefix = '';

	if ( ! empty( $title ) )
		$prefix = " $sep ";

	// sep on right, so reverse the order
	if ( 'right' == $seplocation ) {
		$title_array = array_reverse( explode( $t_sep, $title ) );
		$title       = implode( " $sep ", $title_array ) . $prefix;

	// sep on left, do not reverse
	} else {
		$title_array = explode( $t_sep, $title );
		$title       = $prefix . implode( " $sep ", $title_array );
	}

	// Filter and return
	return apply_filters( 'fct_title', $title, $sep, $seplocation );
}

/** Currency ******************************************************************/

/**
 * Output the currency symbol
 *
 * @uses fct_get_currency_symbol()
 */
function fct_currency_symbol() {
	echo fct_get_currency_symbol();
}

	/**
	 * Return the currency symbol
	 *
	 * @uses fct_get_currency()
	 * @return string Currency symbol
	 */
	function fct_get_currency_symbol() {
		return fct_get_currency( 'symbol' );
	}

/**
 * Output the stored currency attribute
 * 
 * @param string $arg Optional. Currency attribute
 * @uses fct_get_currency() To get the currency
 */
function fct_currency( $arg = '' ){
	echo fct_get_currency( $arg );
}

	/**
	 * Return the stored currency (attribute)
	 *
	 * Defaults to currency ISO code
	 *
	 * @uses fct_get_currencies() To get the currencies list
	 * 
	 * @param string $arg Optional. Currency attribute may be one of:
	 * - symbol: Returns the currency symbol
	 * - name: Returns the currency name
	 * @return string The currency or details
	 */
	function fct_get_currency( $attr = '' ){
		$fiscaat = fiscaat();

		// Load once. Default to 'USD'
		if ( empty( $fiscaat->currency ) ) {
			$fiscaat->currency = get_option( '_fct_currency', 'USD' );
		}

		$iso = $fiscaat->currency;

		// Attribute requested
		if ( ! empty( $attr ) ) {
			$currency = fct_get_currencies( $iso );

			if ( ! empty( $currency ) && isset( $currency[$attr] ) ) {
				$currency = $currency[$attr];
			} else {
				$currency = $iso;
			}

		// Default to currency ISO code
		} else {
			$currency = $iso;
		}

		return apply_filters( 'fct_get_currency', $currency, $attr );
	}

/**
 * Output a Fiscaat specific method of formatting values by currency
 *
 * @param int|string $value Number to format
 * @param bool|string $curr_pos Optional. Whether to return with positioned currency symbol
 * @return string Formatted string
 */
function fct_currency_format( $number = 0, $curr_pos = false ) {
	echo fct_get_currency_format( $number, $curr_pos );
}

	/**
	 * Return a Fiscaat specific method of formatting values by currency
	 *
	 * @uses fct_get_currency() To get the currency
	 * @uses fct_the_currency_format() To get the currency format
	 * @param int|string $value Number to format
	 * @param bool|string $curr_pos Optional. Whether to return with positioned currency symbol
	 * @return string Formatted string
	 */
	function fct_get_currency_format( $number = 0, $curr_pos = false ) {

		// Parse float for it may be a string
		$number = fct_float_format( $number );

		// Parse currency format		
		$format = fct_the_currency_format();
		$retval = number_format( $number, $format['decimals'], $format['decimal_point'], $format['thousands_sep'] );

		// Prepend currency symbol
		if ( ! empty( $curr_pos ) ) {
			$pos    = ! is_string( $curr_pos ) ? get_option( '_fct_currency_position' ) : $curr_pos;
			$symbol = fct_get_currency_symbol();

			// Add symbol to the value
			switch ( $pos ) {
				case 'left' : 
					$retval = $symbol . $retval;
					break;

				case 'right' : 
					$retval .= $symbol;
					break;
				
				case 'right_space' : 
					$retval .= ' ' . $symbol;
					break;
				
				case 'left_space' : 
				default           :
					$retval = $symbol . ' ' . $retval;
					break;
			}
		}

		return apply_filters( 'fct_get_currency_format', $retval, $number, $curr_pos );
	}

/**
 * Output a select box allowing to pick a currency.
 *
 * @param mixed $args See {@link fct_get_dropdown()} for arguments
 */

function fct_currency_dropdown( $args = '' ) {
	echo fct_get_currency_dropdown( $args );
}

	/**
	 * Return a select box allowing to pick a currency.
	 * 
	 * @param mixed $args See {@link fct_get_dropdown()} for arguments
	 * @return string The dropdown
	 */
	function fct_get_currency_dropdown( $args = '' ) {

		/** Arguments *********************************************************/

		$defaults = array(
			'selected'           => 0,

			// Output-related
			'select_id'          => '_fct_currency',
			'tab'                => fct_get_tab_index(),
			'options_only'       => false,
			'none_found'         => false,
			'disabled'           => ''
		);

		$r = fct_parse_args( $args, $defaults, 'get_currency_dropdown' );
		extract( $r );

		/** Setup variables ***************************************************/

		$name      = esc_attr( $select_id );
		$select_id = $name;
		$tab       = (int) $tab;
		$retval    = '';
		$items     = fct_get_currencies();
		$disabled  = disabled( isset( fiscaat()->options[$disabled] ), true, false );

		/** Drop Down *********************************************************/

		// Items found
		if ( ! empty( $items ) ) {
			if ( empty( $options_only ) ) {
				$tab     = ! empty( $tab ) ? ' tabindex="' . $tab . '"' : '';
				$retval .= '<select name="' . $name . '" id="' . $select_id . '"' . $tab  . $disabled . '>' . "\n";
			}

			// Loop all currency items
			foreach ( $items as $iso => $args ) {
				$retval .= "\t<option value=\"$iso\" class=\"level-0\"". selected( $selected, $iso, false ) . ">" . $args['name'] ." (" . $args['symbol'] . ")</option>\n";
			}

			if ( empty( $options_only ) ) {
				$retval .= '</select>';
			}

		// No items found - Display feedback if no custom message was passed
		} elseif ( empty( $none_found ) ) {
			$retval = __( '&mdash; No Currencies &mdash;', 'fiscaat' );
		}

		return apply_filters( 'fct_get_currency_dropdown', $retval, $args );
	}
