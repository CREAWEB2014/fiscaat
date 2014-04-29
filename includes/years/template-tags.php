<?php

/**
 * Fiscaat Year Template Tags
 *
 * @package Fiscaat
 * @subpackage TemplateTags
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** Post Type *****************************************************************/

/**
 * Output the unique id of the custom post type for years
 *
 * @uses fct_get_year_post_type() To get the year post type
 */
function fct_year_post_type() {
	echo fct_get_year_post_type();
}
	/**
	 * Return the unique id of the custom post type for years
	 *
	 * @uses apply_filters() Calls 'fct_get_year_post_type' with the year
	 *                        post type id
	 * @return string The unique year post type id
	 */
	function fct_get_year_post_type() {
		return apply_filters( 'fct_get_year_post_type', fiscaat()->year_post_type );
	}

/** Year Loop ****************************************************************/

/**
 * The main year loop.
 *
 * WordPress makes this easy for us.
 *
 * @param mixed $args All the arguments supported by {@link WP_Query}
 * @uses WP_Query To make query and get the years
 * @uses fct_get_year_post_type() To get the year post type id
 * @uses fct_get_year_id() To get the year id
 * @uses get_option() To get the years per page option
 * @uses current_user_can() To check if the current user is capable of editing
 *                           others' years
 * @uses apply_filters() Calls 'fct_has_years' with
 *                        bbPres::year_query::have_posts()
 *                        and bbPres::year_query
 * @return object Multidimensional array of year information
 */
function fct_has_years( $args = '' ) {
	$fiscaat = fiscaat();

	// Setup possible post__not_in array
	$post_stati[] = fct_get_public_status_id();

	// Check if user can read private years
	if ( current_user_can( 'read_private_years' ) )
		$post_stati[] = fct_get_private_status_id();

	// Check if user can read hidden years
	if ( current_user_can( 'read_hidden_years' ) )
		$post_stati[] = fct_get_hidden_status_id();

	// The default year query for most circumstances
	$defaults = array (
		'post_type'      => fct_get_year_post_type(),
		'post_parent'    => fct_is_year_archive() ? 0 : fct_get_year_id() ,
		'post_status'    => implode( ',', $post_stati ),
		'posts_per_page' => get_option( '_fct_years_per_page', 50 ),
		'orderby'        => 'menu_order',
		'order'          => 'ASC'
	);
	$fct_f = fct_parse_args( $args, $defaults, 'has_years' );

	// Run the query
	$fiscaat->year_query = new WP_Query( $fct_f );

	return apply_filters( 'fct_has_years', $fiscaat->year_query->have_posts(), $fiscaat->year_query );
}

/**
 * Whether there are more years available in the loop
 *
 * @uses Fiscaat:year_query::have_posts() To check if there are more years
 *                                          available
 * @return object Year information
 */
function fct_years() {

	// Put into variable to check against next
	$have_posts = fiscaat()->year_query->have_posts();

	// Reset the post data when finished
	if ( empty( $have_posts ) )
		wp_reset_postdata();

	return $have_posts;
}

/**
 * Loads up the current year in the loop
 *
 * @uses Fiscaat:year_query::the_post() To get the current year
 * @return object Year information
 */
function fct_the_year() {
	return fiscaat()->year_query->the_post();
}

/** The Current Year *********************************************************/

/**
 * Output the current year id
 *
 * @uses fct_get_current_year_id() To get the current year id
 */
function fct_current_year_id() {
	echo fct_get_current_year_id();
}
	/**
	 * Return the current year id
	 *
	 * @uses wpdb To load the latest published year once
	 * @uses appy_filters() Calls 'fct_get_current_year_id' with the
	 *                       current year id
	 * @return int The current year id
	 */
	function fct_get_current_year_id() {
		global $wpdb;

		$fiscaat = fiscaat();

		// Load once
		if ( empty( $fiscaat->the_current_year_id ) ) {
			$year_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_status = %s AND post_type = %s LIMIT 1", fct_get_public_status_id(), fct_get_year_post_type() ) );
			$fiscaat->the_current_year_id = ! empty( $year_id ) ? (int) $year_id : 0;
		}

		return apply_filters( 'fct_get_current_year_id', $fiscaat->the_current_year_id );
	}

/** Year *********************************************************************/

/**
 * Output year id
 *
 * @param $year_id Optional. Used to check emptiness
 * @uses fct_get_year_id() To get the year id
 */
function fct_year_id( $year_id = 0 ) {
	echo fct_get_year_id( $year_id );
}
	/**
	 * Return the year id
	 *
	 * @param $year_id Optional. Used to check emptiness
	 * @uses Fiscaat::year_query::in_the_loop To check if we're in the loop
	 * @uses Fiscaat::year_query::post::ID To get the year id
	 * @uses WP_Query::post::ID To get the year id
	 * @uses fct_is_single_year() To check if it's a year page
	 * @uses fct_is_single_account() To check if it's a account page
	 * @uses fct_get_account_year_id() To get the account year id
	 * @uses get_post_field() To get the post's post type
	 * @uses apply_filters() Calls 'fct_get_year_id' with the year id and
	 *                        supplied year id
	 * @return int The year id
	 */
	function fct_get_year_id( $year_id = 0 ) {
		global $wp_query;

		$fiscaat = fiscaat();

		// Easy empty checking
		if ( ! empty( $year_id ) && is_numeric( $year_id ) )
			$fct_year_id = $year_id;

		// Currently inside a year loop
		elseif ( ! empty( $fiscaat->year_query->in_the_loop ) && isset( $fiscaat->year_query->post->ID ) )
			$fct_year_id = $fiscaat->year_query->post->ID;

		// Currently viewing a year
		elseif ( fct_is_single_year() && ! empty( $fiscaat->current_year_id ) )
			$fct_year_id = $fiscaat->current_year_id;

		// Currently viewing a year
		elseif ( fct_is_single_year() && isset( $wp_query->post->ID ) )
			$fct_year_id = $wp_query->post->ID;

		// Currently viewing a account
		elseif ( fct_is_single_account() )
			$fct_year_id = fct_get_account_year_id();

		// Fallback to current year
		else
			$fct_year_id = fct_get_current_year_id();

		return (int) apply_filters( 'fct_get_year_id', (int) $fct_year_id, $year_id );
	}

/**
 * Gets a year
 *
 * @param int|object $year year id or year object
 * @param string $output Optional. OBJECT, ARRAY_A, or ARRAY_N. Default = OBJECT
 * @param string $filter Optional Sanitation filter. See {@link sanitize_post()}
 * @uses get_post() To get the year
 * @uses apply_filters() Calls 'fct_get_year' with the year, output type and
 *                        sanitation filter
 * @return mixed Null if error or year (in specified form) if success
 */
function fct_get_year( $year, $output = OBJECT, $filter = 'raw' ) {

	// Use year ID
	if ( empty( $year ) || is_numeric( $year ) )
		$year = fct_get_year_id( $year );

	// Attempt to load the year
	$year = get_post( $year, OBJECT, $filter );
	if ( empty( $year ) )
		return $year;

	// Bail if post_type is not a year
	if ( $year->post_type !== fct_get_year_post_type() )
		return null;

	// Tweak the data type to return
	if ( $output == OBJECT ) {
		return $year;

	} elseif ( $output == ARRAY_A ) {
		$_year = get_object_vars( $year );
		return $_year;

	} elseif ( $output == ARRAY_N ) {
		$_year = array_values( get_object_vars( $year ) );
		return $_year;
	}

	return apply_filters( 'fct_get_year', $year, $output, $filter );
}

/**
 * Output the link to the year
 *
 * @param int $year_id Optional. Year id
 * @uses fct_get_year_permalink() To get the permalink
 */
function fct_year_permalink( $year_id = 0 ) {
	echo fct_get_year_permalink( $year_id );
}
	/**
	 * Return the link to the year
	 *
	 * @param int $year_id Optional. Year id
	 * @param $string $redirect_to Optional. Pass a redirect value for use with
	 *                              shortcodes and other fun things.
	 * @uses fct_get_year_id() To get the year id
	 * @uses get_permalink() Get the permalink of the year
	 * @uses apply_filters() Calls 'fct_get_year_permalink' with the year
	 *                        link
	 * @return string Permanent link to year
	 */
	function fct_get_year_permalink( $year_id = 0, $redirect_to = '' ) {
		$year_id = fct_get_year_id( $year_id );

		// Use the redirect address
		if ( ! empty( $redirect_to ) ) {
			$year_permalink = esc_url_raw( $redirect_to );

		// Use the account permalink
		} else {
			$year_permalink = get_permalink( $year_id );
		}

		return apply_filters( 'fct_get_year_permalink', $year_permalink, $year_id );
	}

/**
 * Output the title of the year
 *
 * @param int $year_id Optional. Year id
 * @uses fct_get_year_title() To get the year title
 */
function fct_year_title( $year_id = 0 ) {
	echo fct_get_year_title( $year_id );
}
	/**
	 * Return the title of the year
	 *
	 * @param int $year_id Optional. Year id
	 * @uses fct_get_year_id() To get the year id
	 * @uses get_the_title() To get the year title
	 * @uses apply_filters() Calls 'fct_get_year_title' with the title
	 * @return string Title of year
	 */
	function fct_get_year_title( $year_id = 0 ) {
		$year_id = fct_get_year_id( $year_id );
		$title   = get_the_title( $year_id );

		return apply_filters( 'fct_get_year_title', $title, $year_id );
	}

/**
 * Output the year archive title
 *
 * @param string $title Default text to use as title
 */
function fct_year_archive_title( $title = '' ) {
	echo fct_get_year_archive_title( $title );
}
	/**
	 * Return the year archive title
	 *
	 * @param string $title Default text to use as title
	 *
	 * @uses fct_get_page_by_path() Check if page exists at root path
	 * @uses get_the_title() Use the page title at the root path
	 * @uses get_post_type_object() Load the post type object
	 * @uses fct_get_year_post_type() Get the year post type ID
	 * @uses get_post_type_labels() Get labels for year post type
	 * @uses apply_filters() Allow output to be manipulated
	 *
	 * @return string The year archive title
	 */
	function fct_get_year_archive_title( $title = '' ) {

		// If no title was passed
		if ( empty( $title ) ) {

			// Set root text to page title
			$page = fct_get_page_by_path( fct_get_root_slug() );
			if ( ! empty( $page ) ) {
				$title = get_the_title( $page->ID );

			// Default to year post type name label
			} else {
				$fto    = get_post_type_object( fct_get_year_post_type() );
				$title  = $fto->labels->name;
			}
		}

		return apply_filters( 'fct_get_year_archive_title', $title );
	}

/**
 * Allow year rows to have adminstrative actions
 *
 * @uses do_action()
 * @todo Links and filter
 */
function fct_year_row_actions() {
	do_action( 'fct_year_row_actions' );
}

/**
 * Output the years start date
 * 
 * @uses fct_get_year_started() To get the year's start date
 * @param int $year_id Year id
 * @param bool $gmt Optional. Use GMT
 */
function fct_year_started( $year_id = 0, $gmt = false ) {
	echo fct_get_year_started( $year_id, $gmt );
}
	/**
	 * Return the years start date
	 * 
	 * @uses fct_get_year_id() To get the year id
	 * @uses apply_filters() Calls 'fct_get_year_started' with year 
	 *                        started, year id, use gmt, date and time
	 * @param int $year_id Year id
	 * @param bool $at Whether to use 'date at time' format or other
	 * @param bool $gmt Optional. Use GMT
	 * @return string Year's start date
	 */
	function fct_get_year_started( $year_id = 0, $at = true, $gmt = false ){
		$year_id = fct_get_year_id( $year_id );
		$date    = get_post_time( get_option( 'date_format' ), $gmt, $year_id );
		$time    = get_post_time( get_option( 'time_format' ), $gmt, $year_id );

		// August 4, 2012 at 2:37 pm
		if ( $at ) {
			$result = sprintf( _x( '%1$s at %2$s', 'date at time', 'fiscaat' ), $date, $time );

		// August 4, 2012 <br/> 2:37 pm
		} else {
			$result = sprintf( _x( '%1$s <br /> %2$s', 'date <br/> time', 'fiscaat' ), $date, $time );
		}

		return apply_filters( 'fct_get_year_started', $result, $year_id, $gmt, $date, $time );
	}

/**
 * Output the years close date
 * 
 * @uses fct_get_year_closed() To get the year's close date
 * @param int $year_id Year id
 */
function fct_year_closed( $year_id = 0 ) {
	echo fct_get_year_closed( $year_id );
}
	/**
	 * Return the years close date
	 * 
	 * @uses fct_get_year_id() To get the year id
	 * @uses fct_get_year_meta() To get the year's close date
	 * @uses apply_filters() Calls 'fct_get_year_closed' with
	 *                        the close date and year id
	 * @param int $year_id Year id
	 * @return string Year's close date
	 */
	function fct_get_year_closed( $year_id = 0, $at = true ){
		$year_id = fct_get_year_id( $year_id );
		$date    = fct_get_year_meta( $year_id, 'closed' );

		// Year is closed
		if ( ! empty( $date ) ) {
			$date    = fct_convert_date( $date, get_option( 'date_format' ) );
			$time    = fct_convert_date( $date, get_option( 'time_format' ) );

			// August 4, 2012 at 2:37 pm
			if ( $at ) {
				$result = sprintf( _x( '%1$s at %2$s', 'date at time', 'fiscaat' ), $date, $time );

			// August 4, 2012 <br/> 2:37 pm
			} else {
				$result = sprintf( _x( '%1$s <br /> %2$s', 'date <br/> time', 'fiscaat' ), $date, $time );
			}

		// Not closed
		} else {
			$result = $date = $time = '';
		}

		return apply_filters( 'fct_get_year_closed', $result, $year_id, $date, $time );
	}

/**
 * Output the year's end value
 *
 * @uses fct_get_year_value_end() To get the year's end value
 * @param int $year_id Optional. Year id
 */
function fct_year_value_end( $year_id = 0 ) {
	echo fct_get_year_value_end( $year_id );
}
	/**
	 * Return the year's end value
	 *
	 * @param int $year_id Optional. Year id
	 * @uses fct_get_year_id() To get the year id
	 * @uses fct_get_year_meta() To get the year's end value
	 * @uses apply_filters() Calls 'fct_get_year_value_end' with
	 *                        the end value and year id
	 * @return float Year's end value
	 */
	function fct_get_year_value_end( $year_id = 0 ) {
		$year_id   = fct_get_year_id( $year_id );
		$value_end = (float) fct_get_year_meta( $year_id, 'value_end' );

		return (float) apply_filters( 'fct_get_year_value_end', $value_end, $year_id );
	}

/** Year Counts **************************************************************/

/**
 * Output the accounts link of the year
 *
 * @param int $year_id Optional. Account id
 * @uses fct_get_year_accounts_link() To get the year accounts link
 */
function fct_year_accounts_link( $year_id = 0 ) {
	echo fct_get_year_accounts_link( $year_id );
}

	/**
	 * Return the accounts link of the year
	 *
	 * @param int $year_id Optional. Account id
	 * @uses fct_get_year_id() To get the year id
	 * @uses fct_get_year() To get the year
	 * @uses fct_get_year_account_count() To get the year account count
	 * @uses fct_get_year_permalink() To get the year permalink
	 * @uses remove_query_arg() To remove args from the url
	 * @uses apply_filters() Calls 'fct_get_year_accounts_link' with the
	 *                        accounts link and year id
	 */
	function fct_get_year_accounts_link( $year_id = 0 ) {
		$year_id  = fct_get_year_id( $year_id );
		$accounts = sprintf( _n( '%s account', '%s accounts', fct_get_year_account_count( $year_id, true, false ), 'fiscaat' ), fct_get_year_account_count( $year_id ) );
		$retval   = '';

		// First link never has view=all
		if ( fct_get_view_all( 'edit_others_accounts' ) )
			$retval .= "<a href='" . esc_url( fct_remove_view_all( fct_get_year_permalink( $year_id ) ) ) . "'>$accounts</a>";
		else
			$retval .= $accounts;

		return apply_filters( 'fct_get_year_accounts_link', $retval, $year_id );
	}

/**
 * Output total account count of a year
 *
 * @param int $year_id Optional. Year id
 * @param boolean $integer Optional. Whether or not to format the result
 * @uses fct_get_year_account_count() To get the year account count
 */
function fct_year_account_count( $year_id = 0, $integer = false ) {
	echo fct_get_year_account_count( $year_id, $integer );
}
	/**
	 * Return total account count of a year
	 *
	 * @param int $year_id Optional. Year id
	 * @param boolean $integer Optional. Whether or not to format the result
	 * @uses fct_get_year_id() To get the year id
	 * @uses fct_get_year_meta() To get the year account count
	 * @uses apply_filters() Calls 'fct_get_year_account_count' with the
	 *                        account count and year id
	 * @return int Year account count
	 */
	function fct_get_year_account_count( $year_id = 0, $integer = false ) {
		$year_id  = fct_get_year_id( $year_id );
		$accounts = (int) fct_get_year_meta( $year_id, 'account_count', true );
		$filter   = ( true === $integer ) ? 'fct_get_year_account_count_int' : 'fct_get_year_account_count';

		return apply_filters( $filter, $accounts, $year_id );
	}

/**
 * Output total record count of a year
 *
 * @param int $year_id Optional. Year id
 * @param boolean $integer Optional. Whether or not to format the result
 * @uses fct_get_year_record_count() To get the year record count
 */
function fct_year_record_count( $year_id = 0, $integer = false ) {
	echo fct_get_year_record_count( $year_id, $integer );
}
	/**
	 * Return total post count of a year
	 *
	 * @param int $year_id Optional. Year id
	 * @param boolean $integer Optional. Whether or not to format the result
	 * @uses fct_get_year_id() To get the year id
	 * @uses fct_get_year_meta() To get the year record count
	 * @uses apply_filters() Calls 'fct_get_year_record_count' with the
	 *                        record count and year id
	 * @return int Year record count
	 */
	function fct_get_year_record_count( $year_id = 0, $integer = false ) {
		$year_id = fct_get_year_id( $year_id );
		$records = (int) fct_get_year_meta( $year_id, 'record_count' );
		$filter  = ( true === $integer ) ? 'fct_get_year_record_count_int' : 'fct_get_year_record_count';

		return apply_filters( $filter, $records, $year_id );
	}

/**
 * Output the year status
 * 
 * @param int $year_id Optional. Year id
 * @uses fct_get_year_status()
 */
function fct_year_status( $year_id = 0 ) {
	echo fct_get_year_status( $year_id );
}

	/**
	 * Return the year status
	 * 
	 * @param int $year_id Optional. Year id
	 * @uses fct_get_year_id()
	 * @uses get_post_status()
	 * @uses apply_filters() Calls 'fct_get_year_status' with the status
	 *                        and year id
	 * @return string Year status
	 */
	function fct_get_year_status( $year_id = 0 ) {
		$year_id = fct_get_year_id( $year_id );
		return apply_filters( 'fct_get_year_status', get_post_status( $year_id ), $year_id );
	}

/**
 * Is the year open?
 *
 * @param int $year_id Optional. Year id
 *
 * @param int $year_id Optional. Year id
 * @uses fct_is_year_closed() To get whether the year is closed
 * @return bool Whether the year is open or not
 */
function fct_is_year_open( $year_id = 0 ) {
	return ! fct_is_year_closed( $year_id );
}

	/**
	 * Is the year closed?
	 *
	 * @param int $year_id Optional. Year id
	 * @uses fct_get_year_status() To get the year status
	 * @uses apply_filters() Calls 'fct_is_year_closed' with the year
	 *                        is closed and year id
	 * @return bool True if closed, false if not
	 */
	function fct_is_year_closed( $year_id = 0 ) {
		$retval = fct_get_closed_status_id() == fct_get_year_status( fct_get_year_id( $year_id ) );
		return (bool) apply_filters( 'fct_is_year_closed', $retval, $year_id );
	}

/**
 * Output the author of the year
 *
 * @param int $year_id Optional. Year id
 * @uses fct_get_year_author() To get the year author
 */
function fct_year_author_display_name( $year_id = 0 ) {
	echo fct_get_year_author_display_name( $year_id );
}
	/**
	 * Return the author of the year
	 *
	 * @param int $year_id Optional. Year id
	 * @uses fct_get_year_id() To get the year id
	 * @uses fct_get_year_author_id() To get the year author id
	 * @uses get_the_author_meta() To get the display name of the author
	 * @uses apply_filters() Calls 'fct_get_year_author' with the author
	 *                        and year id
	 * @return string Author of year
	 */
	function fct_get_year_author_display_name( $year_id = 0 ) {
		$year_id = fct_get_year_id( $year_id );
		$author  = get_the_author_meta( 'display_name', fct_get_year_author_id( $year_id ) );

		return apply_filters( 'fct_get_year_author_display_name', $author, $year_id );
	}

/**
 * Output the author ID of the year
 *
 * @param int $year_id Optional. Year id
 * @uses fct_get_year_author_id() To get the year author id
 */
function fct_year_author_id( $year_id = 0 ) {
	echo fct_get_year_author_id( $year_id );
}
	/**
	 * Return the author ID of the year
	 *
	 * @param int $year_id Optional. Year id
	 * @uses fct_get_year_id() To get the year id
	 * @uses get_post_field() To get the year author id
	 * @uses apply_filters() Calls 'fct_get_year_author_id' with the author
	 *                        id and year id
	 * @return string Author of year
	 */
	function fct_get_year_author_id( $year_id = 0 ) {
		$year_id  = fct_get_year_id( $year_id );
		$author_id = get_post_field( 'post_author', $year_id );

		return (int) apply_filters( 'fct_get_year_author_id', (int) $author_id, $year_id );
	}

/**
 * Output the row class of a year
 *
 * @param int $year_id Optional. Year ID.
 * @uses fct_get_year_class() To get the row class of the year
 */
function fct_year_class( $year_id = 0 ) {
	echo fct_get_year_class( $year_id );
}
	/**
	 * Return the row class of a year
	 *
	 * @param int $year_id Optional. Year ID
	 * @uses fct_get_year_id() To validate the year id
	 * @uses fct_is_year_category() To see if year is a category
	 * @uses fct_get_year_status() To get the year status
	 * @uses fct_get_year_visibility() To get the year visibility
	 * @uses fct_get_year_parent_id() To get the year parent id
	 * @uses get_post_class() To get all the classes including ours
	 * @uses apply_filters() Calls 'fct_get_year_class' with the classes
	 * @return string Row class of the year
	 */
	function fct_get_year_class( $year_id = 0 ) {
		$fiscaat   = fiscaat();
		$year_id   = fct_get_year_id( $year_id );
		$count     = isset( $fiscaat->year_query->current_post ) ? $fiscaat->year_query->current_post : 1;
		$classes   = array();

		// Get some classes
		$classes[] = 'loop-item-' . $count;
		$classes[] = ( (int) $count % 2 ) ? 'even' : 'odd';
		$classes[] = 'fiscaat-year-status-' . fct_get_year_status( $year_id );

		// Ditch the empties
		$classes   = array_filter( $classes );
		$classes   = get_post_class( $classes, $year_id );

		// Filter the results
		$classes   = apply_filters( 'fct_get_year_class', $classes, $year_id );
		$retval    = 'class="' . join( ' ', $classes ) . '"';

		return $retval;
	}

/** Forms *********************************************************************/

/**
 * Output the value of year title field
 *
 * @uses fct_get_form_year_title() To get the value of year title field
 */
function fct_form_year_title() {
	echo fct_get_form_year_title();
}
	/**
	 * Return the value of year title field
	 *
	 * @uses fct_is_year_edit() To check if it's year edit page
	 * @uses apply_filters() Calls 'fct_get_form_year_title' with the title
	 * @return string Value of year title field
	 */
	function fct_get_form_year_title() {

		// Get _POST data
		if ( 'POST' == strtoupper( $_SERVER['REQUEST_METHOD'] ) && isset( $_POST['fct_year_title'] ) )
			$year_title = $_POST['fct_year_title'];

		// Get edit data
		elseif ( fct_is_year_edit() )
			$year_title = fct_get_global_post_field( 'post_title', 'raw' );

		// No data
		else
			$year_title = '';

		return apply_filters( 'fct_get_form_year_title', esc_attr( $year_title ) );
	}

/**
 * Output the year start date input
 * 
 * @param int $year_id Year id
 * @param bool $gmt Optional. Use GMT
 * @uses fct_get_form_year_started() To get the year start date input
 */
function fct_form_year_started( $year_id = 0, $gmt = false ) {
	echo fct_get_form_year_started( $year_id, $gmt );
}
	/**
	 * Return the year start date input
	 * 
	 * @param int $year_id The year id to use
	 * @param bool $gmt Optional. Use GMT
	 * @uses fct_get_year_id()
	 * @uses fct_get_year_started()
	 * @uses fct_convert_date()
	 * @return string HTML input for setting the year start date
	 */
	function fct_get_form_year_started( $year_id = 0, $gmt = false ) {

		// Get _POST data
		if ( 'POST' == strtoupper( $_SERVER['REQUEST_METHOD'] ) && isset( $_POST['fct_year_post_date'] ) )
			$year_start = $_POST['fct_year_post_date'];

		// Get edit data
		elseif ( fct_is_year_edit() )
			$year_start = fct_get_global_post_field( 'post_date', 'raw' );

		// No data
		else
			$year_start = '';

		return apply_filters( 'fct_get_form_year_started', esc_attr( $year_start ) );
	}

/**
 * Output the year close date input
 * 
 * @param int $year_id Year id
 * @uses fct_get_form_year_closed() To get the year close date input
 */
function fct_form_year_closed( $year_id = 0 ) {
	echo fct_get_form_year_closed( $year_id );
}
	/**
	 * Return the year close date input
	 * 
	 * @param int $year_id The year id to use
	 * @uses fct_get_year_id()
	 * @uses fct_get_year_closed()
	 * @uses fct_convert_date()
	 * @return string HTML input for setting the year close date
	 */
	function fct_get_form_year_closed( $year_id = 0 ) {

		// Get _POST data
		if ( 'POST' == strtoupper( $_SERVER['REQUEST_METHOD'] ) && isset( $_POST['fct_year_closed'] ) )
			$year_close = $_POST['fct_year_closed'];

		// Get edit data
		elseif ( fct_is_year_edit() )
			$year_close = fct_get_year_closed( $year_id );

		// No data
		else
			$year_close = '';

		return apply_filters( 'fct_get_form_year_closed', esc_attr( $year_close ) );
	}

/** Form Dropdows *************************************************************/

/**
 * Output value year status dropdown
 *
 * @param int $year_id The year id to use
 * @param bool $disable Optional. Whether to disable the dropdown
 * @uses fct_get_form_year_status() To get the year status dropdown
 */
function fct_form_year_status_dropdown( $year_id = 0 ) {
	echo fct_get_form_year_status_dropdown( $year_id );
}
	/**
	 * Return the year status dropdown
	 *
	 * @param int $year_id The year id to use
	 * @param bool $disable Optional. Whether to disable the dropdown
	 * @uses fct_get_year_id() To check the year id
	 * @uses fct_get_year_status() To get the year status
	 * @uses fct_get_year_statuses() To get all year statuses
	 * @uses apply_filters() Calls 'fct_get_form_year_status_dropdown' with the
	 *                        status dropdown, year id, and year statuses
	 * @return string HTML select list for selecting year status
	 */
	function fct_get_form_year_status_dropdown( $year_id = 0 ) {
		$year_id     = fct_get_year_id( $year_id );
		$year_status = fct_get_year_status( $year_id );
		$statuses    = fct_get_year_statuses();

		// Disable dropdown
		$disable = fct_is_control_active() && ! current_user_can( 'fiscaat' ) ? true : false;

		$status_output = '<select name="fct_year_status" id="fct_year_status_select" '. disabled( $disable, true, false ) .'>' . "\n";

		foreach( $statuses as $value => $label )
			$status_output .= "\t" . '<option value="' . $value . '"' . selected( $year_status, $value, false ) . '>' . esc_html( $label ) . '</option>' . "\n";

		$status_output .= '</select>';

		return apply_filters( 'fct_get_form_year_status_dropdown', $status_output, $year_id, $statuses );
	}

