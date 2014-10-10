<?php

/**
 * Fiscaat Period Template Tags
 *
 * @package Fiscaat
 * @subpackage TemplateTags
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Post Type *****************************************************************/

/**
 * Output the unique id of the custom post type for periods
 *
 * @uses fct_get_period_post_type() To get the period post type
 */
function fct_period_post_type() {
	echo fct_get_period_post_type();
}
	/**
	 * Return the unique id of the custom post type for periods
	 *
	 * @uses apply_filters() Calls 'fct_get_period_post_type' with the period
	 *                        post type id
	 * @return string The unique period post type id
	 */
	function fct_get_period_post_type() {
		return apply_filters( 'fct_get_period_post_type', fiscaat()->period_post_type );
	}

/**
 * Return array of labels used by the period post type
 *
 * @since 0.0.9
 *
 * @uses apply_filters() Calls 'fct_get_period_post_type_labels'
 * @return array Record post type labels
 */
function fct_get_period_post_type_labels() {
	return apply_filters( 'fct_get_period_post_type_labels', array(
		'name'               => _x( 'Periods', 'Post type general name', 'fiscaat' ),
		'singular_name'      => _x( 'Period', 'Post type singular name', 'fiscaat' ),
		'menu_name'          => _x( 'Periods', 'Admin menu',             'fiscaat' ),
		'name_admin_bar'     => _x( 'Period', 'Add new on admin bar',    'fiscaat' ),
		'all_items'          => __( 'All Periods',                       'fiscaat' ),
		'add_new'            => __( 'Add New',                           'fiscaat' ),
		'add_new_item'       => __( 'Add New Period',                    'fiscaat' ),
		'edit'               => __( 'Edit',                              'fiscaat' ),
		'edit_item'          => __( 'Edit Period',                       'fiscaat' ),
		'new_item'           => __( 'New Period',                        'fiscaat' ),
		'view'               => __( 'View Period',                       'fiscaat' ),
		'view_item'          => __( 'View Period',                       'fiscaat' ),
		'search_items'       => __( 'Search Periods',                    'fiscaat' ),
		'not_found'          => __( 'No periods found',                  'fiscaat' ),
		'not_found_in_trash' => __( 'No periods found in Trash',         'fiscaat' ),
	) );
}

/**
 * Return array of period post type rewrite settings
 *
 * @since 0.0.9
 * 
 * @return array Record post type rewrite settings
 */
function fct_get_period_post_type_rewrite() {
	return apply_filters( 'fct_get_period_post_type_rewrite', array(
		'slug'       => fct_get_period_slug(),
		'with_front' => false
	) );
}

/**
 * Return array of features the period post type supports
 *
 * By default support no features, so this returns false. Title and
 * description input fields are custom provided by Fiscaat.
 *
 * @since 0.0.9
 * 
 * @return array|bool Features period post type supports or false
 *                     when supporting no features.
 */
function fct_get_period_post_type_supports() {
	return apply_filters( 'fct_get_period_post_type_supports', false );
}

/** Period Loop ****************************************************************/

/**
 * The main period loop.
 *
 * WordPress makes this easy for us.
 *
 * @param mixed $args All the arguments supported by {@link WP_Query}
 * @uses WP_Query To make query and get the periods
 * @uses fct_get_period_post_type() To get the period post type id
 * @uses fct_get_period_id() To get the period id
 * @uses get_option() To get the periods per page option
 * @uses apply_filters() Calls 'fct_has_periods' with
 *                        Fiscaat::period_query::have_posts()
 *                        and Fiscaat::period_query
 * @return object Multidimensional array of period information
 */
function fct_has_periods( $args = '' ) {
	$fct = fiscaat();

	// The default period query for most circumstances
	$fct_f = fct_parse_args( $args, array(
		'post_type'      => fct_get_period_post_type(),
		'posts_per_page' => get_option( '_fct_periods_per_page', 25 ),
		'orderby'        => 'post_date',
		'order'          => 'DESC'
	), 'has_periods' );

	// Run the query
	$fct->period_query = new WP_Query( $fct_f );

	return apply_filters( 'fct_has_periods', $fct->period_query->have_posts(), $fct->period_query );
}

/**
 * Whether there are more periods available in the loop
 *
 * @uses Fiscaat:period_query::have_posts() To check if there are more periods
 *                                          available
 * @return object Period information
 */
function fct_periods() {

	// Put into variable to check against next
	$have_posts = fiscaat()->period_query->have_posts();

	// Reset the post data when finished
	if ( empty( $have_posts ) )
		wp_reset_postdata();

	return $have_posts;
}

/**
 * Loads up the current period in the loop
 *
 * @uses Fiscaat:period_query::the_post() To get the current period
 * @return object Period information
 */
function fct_the_period() {
	return fiscaat()->period_query->the_post();
}

/** The Current Period *********************************************************/

/**
 * Output the current period id
 *
 * @uses fct_get_current_period_id() To get the current period id
 */
function fct_current_period_id() {
	echo fct_get_current_period_id();
}
	/**
	 * Return the current period id
	 *
	 * @uses wpdb To load the latest published period once
	 * @uses appy_filters() Calls 'fct_get_current_period_id' with the
	 *                       current period id
	 * @return int The current period id
	 */
	function fct_get_current_period_id() {
		global $wpdb;

		$fct = fiscaat();

		// Load once
		if ( empty( $fct->the_current_period_id ) ) {
			$period_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_status = %s AND post_type = %s LIMIT 1", fct_get_public_status_id(), fct_get_period_post_type() ) );
			$fct->the_current_period_id = ! empty( $period_id ) ? (int) $period_id : 0;
		}

		return apply_filters( 'fct_get_current_period_id', $fct->the_current_period_id );
	}

/** Period *********************************************************************/

/**
 * Output period id
 *
 * @param $period_id Optional. Used to check emptiness
 * @uses fct_get_period_id() To get the period id
 */
function fct_period_id( $period_id = 0 ) {
	echo fct_get_period_id( $period_id );
}
	/**
	 * Return the period id
	 *
	 * @param $period_id Optional. Used to check emptiness
	 * @uses Fiscaat::period_query::in_the_loop To check if we're in the loop
	 * @uses Fiscaat::period_query::post::ID To get the period id
	 * @uses WP_Query::post::ID To get the period id
	 * @uses fct_is_single_period() To check if it's a period page
	 * @uses fct_is_single_account() To check if it's a account page
	 * @uses fct_get_account_period_id() To get the account period id
	 * @uses get_post_field() To get the post's post type
	 * @uses apply_filters() Calls 'fct_get_period_id' with the period id and
	 *                        supplied period id
	 * @return int The period id
	 */
	function fct_get_period_id( $period_id = 0 ) {
		global $wp_query;

		$fct = fiscaat();

		// Easy empty checking
		if ( ! empty( $period_id ) && is_numeric( $period_id ) ) {
			$fct_period_id = $period_id;

		// Currently inside a period loop
		} elseif ( ! empty( $fct->period_query->in_the_loop ) && isset( $fct->period_query->post->ID ) ) {
			$fct_period_id = $fct->period_query->post->ID;

		// Currently viewing a period
		} elseif ( fct_is_single_period() && ! empty( $fct->current_period_id ) ) {
			$fct_period_id = $fct->current_period_id;

		// Currently viewing a period
		} elseif ( fct_is_single_period() && isset( $wp_query->post->ID ) ) {
			$fct_period_id = $wp_query->post->ID;

		// Currently viewing a account
		} elseif ( fct_is_single_account() ) {
			$fct_period_id = fct_get_account_period_id();

		// Fallback to current period
		} else {
			$fct_period_id = fct_get_current_period_id();
		}

		return (int) apply_filters( 'fct_get_period_id', (int) $fct_period_id, $period_id );
	}

/**
 * Gets a period
 *
 * @param int|object $period period id or period object
 * @param string $output Optional. OBJECT, ARRAY_A, or ARRAY_N. Default = OBJECT
 * @param string $filter Optional Sanitation filter. See {@link sanitize_post()}
 * @uses get_post() To get the period
 * @uses apply_filters() Calls 'fct_get_period' with the period, output type and
 *                        sanitation filter
 * @return mixed Null if error or period (in specified form) if success
 */
function fct_get_period( $period, $output = OBJECT, $filter = 'raw' ) {

	// Use period ID
	if ( empty( $period ) || is_numeric( $period ) )
		$period = fct_get_period_id( $period );

	// Attempt to load the period
	$period = get_post( $period, OBJECT, $filter );
	if ( empty( $period ) )
		return $period;

	// Bail if post_type is not a period
	if ( $period->post_type !== fct_get_period_post_type() )
		return null;

	// Tweak the data type to return
	if ( $output == OBJECT ) {
		return $period;

	} elseif ( $output == ARRAY_A ) {
		$_period = get_object_vars( $period );
		return $_period;

	} elseif ( $output == ARRAY_N ) {
		$_period = array_values( get_object_vars( $period ) );
		return $_period;
	}

	return apply_filters( 'fct_get_period', $period, $output, $filter );
}

/**
 * Output the link to the period
 *
 * @param int $period_id Optional. Period id
 * @uses fct_get_period_permalink() To get the permalink
 */
function fct_period_permalink( $period_id = 0 ) {
	echo fct_get_period_permalink( $period_id );
}
	/**
	 * Return the link to the period
	 *
	 * @param int $period_id Optional. Period id
	 * @param $string $redirect_to Optional. Pass a redirect value for use with
	 *                              shortcodes and other fun things.
	 * @uses fct_get_period_id() To get the period id
	 * @uses get_permalink() Get the permalink of the period
	 * @uses apply_filters() Calls 'fct_get_period_permalink' with the period
	 *                        link
	 * @return string Permanent link to period
	 */
	function fct_get_period_permalink( $period_id = 0, $redirect_to = '' ) {
		$period_id = fct_get_period_id( $period_id );

		// Use the redirect address
		if ( ! empty( $redirect_to ) ) {
			$period_permalink = esc_url_raw( $redirect_to );

		// Use the account permalink
		} else {
			$period_permalink = get_permalink( $period_id );
		}

		return apply_filters( 'fct_get_period_permalink', $period_permalink, $period_id );
	}

/**
 * Output the title of the period
 *
 * @param int $period_id Optional. Period id
 * @uses fct_get_period_title() To get the period title
 */
function fct_period_title( $period_id = 0 ) {
	echo fct_get_period_title( $period_id );
}
	/**
	 * Return the title of the period
	 *
	 * @param int $period_id Optional. Period id
	 * @uses fct_get_period_id() To get the period id
	 * @uses get_the_title() To get the period title
	 * @uses apply_filters() Calls 'fct_get_period_title' with the title
	 * @return string Title of period
	 */
	function fct_get_period_title( $period_id = 0 ) {
		$period_id = fct_get_period_id( $period_id );
		$title     = get_the_title( $period_id );

		return apply_filters( 'fct_get_period_title', $title, $period_id );
	}

/**
 * Output the period archive title
 *
 * @param string $title Default text to use as title
 */
function fct_period_archive_title( $title = '' ) {
	echo fct_get_period_archive_title( $title );
}
	/**
	 * Return the period archive title
	 *
	 * @param string $title Default text to use as title
	 *
	 * @uses fct_get_page_by_path() Check if page exists at root path
	 * @uses get_the_title() Use the page title at the root path
	 * @uses get_post_type_object() Load the post type object
	 * @uses fct_get_period_post_type() Get the period post type ID
	 * @uses get_post_type_labels() Get labels for period post type
	 * @uses apply_filters() Allow output to be manipulated
	 *
	 * @return string The period archive title
	 */
	function fct_get_period_archive_title( $title = '' ) {

		// If no title was passed
		if ( empty( $title ) ) {

			// Set root text to page title
			$page = fct_get_page_by_path( fct_get_root_slug() );
			if ( ! empty( $page ) ) {
				$title = get_the_title( $page->ID );

			// Default to period post type name label
			} else {
				$pto   = get_post_type_object( fct_get_period_post_type() );
				$title = $pto->labels->name;
			}
		}

		return apply_filters( 'fct_get_period_archive_title', $title );
	}

/**
 * Allow period rows to have adminstrative actions
 *
 * @uses do_action()
 * @todo Links and filter
 */
function fct_period_row_actions() {
	do_action( 'fct_period_row_actions' );
}

/**
 * Return the period's raw start date
 *
 * @since 0.0.8
 *
 * @uses fct_get_period_id() To get the period id
 * @uses fct_get_period_meta() To get the period's start date
 * @uses apply_filters() Calls 'fct_get_period_started' with
 *                        the start date and period id
 *
 * @param int $period_id Period id
 * @param bool $gmt Optional. Use GMT
 * @return string Period's raw start date 
 */
function fct_get_period_post_date( $period_id, $gmt = false ) {
	$period_id = fct_get_period_id( $period_id );
	$date      = get_post_time( 'Y-m-d H:i:s', $gmt, $period_id );

	return apply_filters( 'fct_get_period_post_date', $date, $period_id, $gmt );
}

/**
 * Output the period's start date 
 * 
 * @uses fct_get_period_started() To get the period's start date
 * 
 * @param int $period_id Period id
 * @param bool $at Whether to use 'date at time' format or other
 * @param bool $gmt Optional. Use GMT
 */
function fct_period_started( $period_id = 0, $at = true, $gmt = false ) {
	echo fct_get_period_started( $period_id, $at, $gmt );
}
	/**
	 * Return the period's start date 
	 * 
	 * @uses fct_get_period_id() To get the period id
	 * @uses fct_get_period_post_date() To get the period's raw start date
	 * @uses apply_filters() Calls 'fct_get_period_started' with
	 *                        the close date and period id
	 * 
	 * @param int $period_id Period id
	 * @param bool $at Whether to use 'date at time' format or other
	 * @param bool $gmt Optional. Use GMT
	 * @return string Period's start date
	 */
	function fct_get_period_started( $period_id = 0, $at = true, $gmt = false ){
		$period_id = fct_get_period_id( $period_id );
		$started   = fct_get_period_post_date( $period_id, $gmt );

		$date = fct_convert_date( $started, get_option( 'date_format' ) );
		$time = fct_convert_date( $started, get_option( 'time_format' ) );

		// August 4, 2012 at 2:37 pm
		if ( $at ) {
			$result = sprintf( _x( '%1$s at %2$s', 'date at time', 'fiscaat' ), $date, $time );

		// August 4, 2012 <br/> 2:37 pm
		} else {
			$result = sprintf( _x( '%1$s <br /> %2$s', 'date <br/> time', 'fiscaat' ), $date, $time );
		}

		return apply_filters( 'fct_get_period_started', $result, $period_id, $gmt, $date, $time );
	}

/**
 * Return the period's raw close date
 *
 * @since 0.0.8
 *
 * @uses fct_get_period_id() To get the period id
 * @uses fct_get_period_meta() To get the period's close date
 * @uses apply_filters() Calls 'fct_get_period_close_date' with
 *                        the close date and period id
 *
 * @param int $period_id Period id
 * @param bool $gmt Optional. Use GMT
 * @return string Period's raw close date
 */
function fct_get_period_close_date( $period_id, $gmt = false ) {
	$period_id = fct_get_period_id( $period_id );
	$date      = fct_get_period_meta( $period_id, 'close_date' );

	// Period is closed
	if ( ! empty( $date ) ) {

		// Date is stored as GMT. Convert back
		if ( ! $gmt ) {
			$date = gmdate( 'Y-m-d H:i:s', strtotime( $date ) + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
		}

	// Not closed
	} else {
		$date = '';
	}

	return apply_filters( 'fct_get_period_close_date', $date, $period_id, $gmt );
}

/**
 * Output the period's close date
 * 
 * @uses fct_get_period_closed() To get the period's close date
 * 
 * @param int $period_id Period id
 * @param bool $at Whether to use 'date at time' format or other
 * @param bool $gmt Optional. Use GMT
 */
function fct_period_closed( $period_id = 0, $at = true, $gmt = false ) {
	echo fct_get_period_closed( $period_id, $at, $gmt );
}
	/**
	 * Return the period's close date
	 * 
	 * @uses fct_get_period_id() To get the period id
	 * @uses fct_get_period_close_date() To get the period's raw close date
	 * @uses apply_filters() Calls 'fct_get_period_closed' with
	 *                        the close date and period id
	 * 
	 * @param int $period_id Period id
	 * @param bool $at Whether to use 'date at time' format or other
	 * @param bool $gmt Optional. Use GMT
	 * @return string Period's close date
	 */
	function fct_get_period_closed( $period_id = 0, $at = true, $gmt = false ){
		$period_id = fct_get_period_id( $period_id );
		$closed    = fct_get_period_close_date( $period_id, $gmt );

		// Period is closed
		if ( ! empty( $date ) ) {
			$date    = fct_convert_date( $closed, get_option( 'date_format' ) );
			$time    = fct_convert_date( $closed, get_option( 'time_format' ) );

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

		return apply_filters( 'fct_get_period_closed', $result, $period_id, $date, $time );
	}

/**
 * Output the period's end value
 *
 * @uses fct_get_period_end_value() To get the period's end value
 * @param int $period_id Optional. Period id
 */
function fct_period_end_value( $period_id = 0 ) {
	echo fct_get_period_end_value( $period_id );
}
	/**
	 * Return the period's end value
	 *
	 * @param int $period_id Optional. Period id
	 * @uses fct_get_period_id() To get the period id
	 * @uses fct_get_period_meta() To get the period's end value
	 * @uses apply_filters() Calls 'fct_get_period_end_value' with
	 *                        the end value and period id
	 * @return float Period's end value
	 */
	function fct_get_period_end_value( $period_id = 0 ) {
		$period_id = fct_get_period_id( $period_id );
		$end_value = (float) fct_get_period_meta( $period_id, 'end_value' );

		return (float) apply_filters( 'fct_get_period_end_value', $end_value, $period_id );
	}

/** Period Counts **************************************************************/

/**
 * Output the accounts link of the period
 *
 * @param int $period_id Optional. Account id
 * @uses fct_get_period_accounts_link() To get the period accounts link
 */
function fct_period_accounts_link( $period_id = 0 ) {
	echo fct_get_period_accounts_link( $period_id );
}

	/**
	 * Return the accounts link of the period
	 *
	 * @param int $period_id Optional. Account id
	 * @uses fct_get_period_id() To get the period id
	 * @uses fct_get_period() To get the period
	 * @uses fct_get_period_account_count() To get the period account count
	 * @uses fct_get_period_permalink() To get the period permalink
	 * @uses remove_query_arg() To remove args from the url
	 * @uses apply_filters() Calls 'fct_get_period_accounts_link' with the
	 *                        accounts link and period id
	 */
	function fct_get_period_accounts_link( $period_id = 0 ) {
		$period_id  = fct_get_period_id( $period_id );
		$accounts   = sprintf( _n( '%s account', '%s accounts', fct_get_period_account_count( $period_id, true, false ), 'fiscaat' ), fct_get_period_account_count( $period_id ) );
		$retval     = '';

		// First link never has view=all
		if ( fct_get_view_all( 'edit_others_accounts' ) )
			$retval .= "<a href='" . esc_url( fct_remove_view_all( fct_get_period_permalink( $period_id ) ) ) . "'>$accounts</a>";
		else
			$retval .= $accounts;

		return apply_filters( 'fct_get_period_accounts_link', $retval, $period_id );
	}

/**
 * Output total account count of a period
 *
 * @param int $period_id Optional. Period id
 * @param boolean $integer Optional. Whether or not to format the result
 * @uses fct_get_period_account_count() To get the period account count
 */
function fct_period_account_count( $period_id = 0, $integer = false ) {
	echo fct_get_period_account_count( $period_id, $integer );
}
	/**
	 * Return total account count of a period
	 *
	 * @param int $period_id Optional. Period id
	 * @param boolean $integer Optional. Whether or not to format the result
	 * @uses fct_get_period_id() To get the period id
	 * @uses fct_get_period_meta() To get the period account count
	 * @uses apply_filters() Calls 'fct_get_period_account_count' with the
	 *                        account count and period id
	 * @return int Period account count
	 */
	function fct_get_period_account_count( $period_id = 0, $integer = false ) {
		$period_id  = fct_get_period_id( $period_id );
		$accounts   = (int) fct_get_period_meta( $period_id, 'account_count', true );
		$filter     = ( true === $integer ) ? 'fct_get_period_account_count_int' : 'fct_get_period_account_count';

		return apply_filters( $filter, $accounts, $period_id );
	}

/**
 * Output total record count of a period
 *
 * @param int $period_id Optional. Period id
 * @param boolean $integer Optional. Whether or not to format the result
 * @uses fct_get_period_record_count() To get the period record count
 */
function fct_period_record_count( $period_id = 0, $integer = false ) {
	echo fct_get_period_record_count( $period_id, $integer );
}
	/**
	 * Return total post count of a period
	 *
	 * @param int $period_id Optional. Period id
	 * @param boolean $integer Optional. Whether or not to format the result
	 * @uses fct_get_period_id() To get the period id
	 * @uses fct_get_period_meta() To get the period record count
	 * @uses apply_filters() Calls 'fct_get_period_record_count' with the
	 *                        record count and period id
	 * @return int Period record count
	 */
	function fct_get_period_record_count( $period_id = 0, $integer = false ) {
		$period_id = fct_get_period_id( $period_id );
		$records   = (int) fct_get_period_meta( $period_id, 'record_count' );
		$filter    = ( true === $integer ) ? 'fct_get_period_record_count_int' : 'fct_get_period_record_count';

		return apply_filters( $filter, $records, $period_id );
	}

/**
 * Output the period status
 * 
 * @param int $period_id Optional. Period id
 * @uses fct_get_period_status()
 */
function fct_period_status( $period_id = 0 ) {
	echo fct_get_period_status( $period_id );
}

	/**
	 * Return the period status
	 * 
	 * @param int $period_id Optional. Period id
	 * @uses fct_get_period_id()
	 * @uses get_post_status()
	 * @uses apply_filters() Calls 'fct_get_period_status' with the status
	 *                        and period id
	 * @return string Period status
	 */
	function fct_get_period_status( $period_id = 0 ) {
		$period_id = fct_get_period_id( $period_id );
		return apply_filters( 'fct_get_period_status', get_post_status( $period_id ), $period_id );
	}

/**
 * Is the period open?
 *
 * @uses fct_is_period_closed() To get whether the period is closed
 * 
 * @param int $period_id Optional. Period id
 * @return bool Whether the period is open or not
 */
function fct_is_period_open( $period_id = 0 ) {
	return ! fct_is_period_closed( $period_id );
}

	/**
	 * Is the period closed?
	 *
	 * @uses fct_get_period_status() To get the period status
	 * @uses apply_filters() Calls 'fct_is_period_closed' with the period
	 *                        is closed and period id
	 *
	 * @param int $period_id Optional. Period id
	 * @return bool True if closed, false if not
	 */
	function fct_is_period_closed( $period_id = 0 ) {
		$closed = fct_get_closed_status_id() == fct_get_period_status( fct_get_period_id( $period_id ) );
		return (bool) apply_filters( 'fct_is_period_closed', $closed, $period_id );
	}

/**
 * Output the author of the period
 *
 * @param int $period_id Optional. Period id
 * @uses fct_get_period_author() To get the period author
 */
function fct_period_author_display_name( $period_id = 0 ) {
	echo fct_get_period_author_display_name( $period_id );
}
	/**
	 * Return the author of the period
	 *
	 * @param int $period_id Optional. Period id
	 * @uses fct_get_period_id() To get the period id
	 * @uses fct_get_period_author_id() To get the period author id
	 * @uses get_the_author_meta() To get the display name of the author
	 * @uses apply_filters() Calls 'fct_get_period_author' with the author
	 *                        and period id
	 * @return string Author of period
	 */
	function fct_get_period_author_display_name( $period_id = 0 ) {
		$period_id = fct_get_period_id( $period_id );
		$author    = get_the_author_meta( 'display_name', fct_get_period_author_id( $period_id ) );

		return apply_filters( 'fct_get_period_author_display_name', $author, $period_id );
	}

/**
 * Output the author ID of the period
 *
 * @param int $period_id Optional. Period id
 * @uses fct_get_period_author_id() To get the period author id
 */
function fct_period_author_id( $period_id = 0 ) {
	echo fct_get_period_author_id( $period_id );
}
	/**
	 * Return the author ID of the period
	 *
	 * @param int $period_id Optional. Period id
	 * @uses fct_get_period_id() To get the period id
	 * @uses get_post_field() To get the period author id
	 * @uses apply_filters() Calls 'fct_get_period_author_id' with the author
	 *                        id and period id
	 * @return string Author of period
	 */
	function fct_get_period_author_id( $period_id = 0 ) {
		$period_id = fct_get_period_id( $period_id );
		$author_id = get_post_field( 'post_author', $period_id );

		return (int) apply_filters( 'fct_get_period_author_id', (int) $author_id, $period_id );
	}

/**
 * Output the row class of a period
 *
 * @param int $period_id Optional. Period ID.
 * @uses fct_get_period_class() To get the row class of the period
 */
function fct_period_class( $period_id = 0 ) {
	echo fct_get_period_class( $period_id );
}
	/**
	 * Return the row class of a period
	 *
	 * @param int $period_id Optional. Period ID
	 * @uses fct_get_period_id() To validate the period id
	 * @uses fct_is_period_category() To see if period is a category
	 * @uses fct_get_period_status() To get the period status
	 * @uses fct_get_period_visibility() To get the period visibility
	 * @uses fct_get_period_parent_id() To get the period parent id
	 * @uses get_post_class() To get all the classes including ours
	 * @uses apply_filters() Calls 'fct_get_period_class' with the classes
	 * @return string Row class of the period
	 */
	function fct_get_period_class( $period_id = 0 ) {
		$fct       = fiscaat();
		$period_id = fct_get_period_id( $period_id );
		$count     = isset( $fct->period_query->current_post ) ? $fct->period_query->current_post : 1;
		$classes   = array();

		// Get some classes
		$classes[] = 'loop-item-' . $count;
		$classes[] = ( (int) $count % 2 ) ? 'even' : 'odd';
		$classes[] = 'fiscaat-period-status-' . fct_get_period_status( $period_id );

		// Ditch the empties
		$classes   = array_filter( $classes );
		$classes   = get_post_class( $classes, $period_id );

		// Filter the results
		$classes   = apply_filters( 'fct_get_period_class', $classes, $period_id );
		$retval    = 'class="' . join( ' ', $classes ) . '"';

		return $retval;
	}

/** Dropdowns *****************************************************************/

/**
 * Output a select box allowing to pick which period to show.
 *
 * @param mixed $args See {@link fct_get_dropdown()} for arguments
 */
function fct_period_dropdown( $args = '' ) {
	echo fct_get_period_dropdown( $args );
}

	/**
	 * Return a select box allowing to pick which period to show.
	 * 
	 * @param mixed $args See {@link fct_get_dropdown()} for arguments
	 * @return string The dropdown
	 */
	function fct_get_period_dropdown( $args = '' ) {

		/** Arguments *********************************************************/

		$r = fct_parse_args( $args, array(
			'post_type'          => fct_get_period_post_type(),
			'post_parent'        => null,
			'selected'           => 0,
			'orderby'            => 'post_date',
			'order'              => 'DESC',
			'disable_closed'     => false,

			// Output-related
			'select_id'          => 'fct_period_id',
			'show_none'          => __( 'In all periods', 'fiscaat' ),
		), 'get_period_dropdown' );

		/** Drop Down *********************************************************/

		$retval = fct_get_dropdown( $r );

		return apply_filters( 'fct_get_period_dropdown', $retval, $r );
	}

/** Forms *********************************************************************/

/**
 * Output the value of period title field
 *
 * @uses fct_get_form_period_title() To get the value of period title field
 */
function fct_form_period_title() {
	echo fct_get_form_period_title();
}
	/**
	 * Return the value of period title field
	 *
	 * @uses fct_is_period_edit() To check if it's period edit page
	 * @uses apply_filters() Calls 'fct_get_form_period_title' with the title
	 * @return string Value of period title field
	 */
	function fct_get_form_period_title() {

		// Get _POST data
		if ( 'POST' == strtoupper( $_SERVER['REQUEST_METHOD'] ) && isset( $_POST['fct_period_title'] ) ) {
			$period_title = $_POST['fct_period_title'];

		// Get edit data
		} elseif ( fct_is_period_edit() ) {
			$period_title = fct_get_global_post_field( 'post_title', 'raw' );

		// No data
		} else {
			$period_title = '';
		}

		return apply_filters( 'fct_get_form_period_title', esc_attr( $period_title ) );
	}

/**
 * Output the period start date input
 * 
 * @param int $period_id Period id
 * @param bool $gmt Optional. Use GMT
 * @uses fct_get_form_period_post_date() To get the period start date input
 */
function fct_form_period_post_date( $period_id = 0, $gmt = false ) {
	echo fct_get_form_period_post_date( $period_id, $gmt );
}
	/**
	 * Return the period start date input
	 * 
	 * @param int $period_id The period id to use
	 * @param bool $gmt Optional. Use GMT
	 * @uses fct_get_period_id()
	 * @uses fct_get_period_post_date()
	 * @uses fct_convert_date()
	 * @return string HTML input for setting the period start date
	 */
	function fct_get_form_period_post_date( $period_id = 0, $gmt = false ) {

		// Get _POST data
		if ( 'POST' == strtoupper( $_SERVER['REQUEST_METHOD'] ) && isset( $_POST['fct_period_post_date'] ) ) {
			$period_start = $_POST['fct_period_post_date'];

		// Get edit data
		} elseif ( fct_is_period_edit() ) {
			$period_start = fct_get_global_post_field( 'post_date', 'raw' );

		// No data
		} else {
			$period_start = '';
		}

		return apply_filters( 'fct_get_form_period_post_date', esc_attr( $period_start ) );
	}

/**
 * Output the period close date input
 * 
 * @param int $period_id Period id
 * @uses fct_get_form_period_close_date() To get the period close date input
 */
function fct_form_period_close_date( $period_id = 0 ) {
	echo fct_get_form_period_close_date( $period_id );
}
	/**
	 * Return the period close date input
	 * 
	 * @param int $period_id The period id to use
	 * @uses fct_get_period_id()
	 * @uses fct_get_period_close_date()
	 * @uses fct_convert_date()
	 * @return string HTML input for setting the period close date
	 */
	function fct_get_form_period_close_date( $period_id = 0 ) {

		// Get _POST data
		if ( 'POST' == strtoupper( $_SERVER['REQUEST_METHOD'] ) && isset( $_POST['fct_period_close_date'] ) ) {
			$period_close = $_POST['fct_period_close_date'];

		// Get edit data
		} elseif ( fct_is_period_edit() ) {
			$period_close = fct_get_period_close_date( $period_id );

		// No data
		} else {
			$period_close = '';
		}

		return apply_filters( 'fct_get_form_period_close_date', esc_attr( $period_close ) );
	}

/** Form Dropdows *************************************************************/

/**
 * Output value period status dropdown
 *
 * @param int $period_id The period id to use
 * @param bool $disable Optional. Whether to disable the dropdown
 * @uses fct_get_form_period_status() To get the period status dropdown
 */
function fct_form_period_status_dropdown( $period_id = 0 ) {
	echo fct_get_form_period_status_dropdown( $period_id );
}
	/**
	 * Return the period status dropdown
	 *
	 * @param int $period_id The period id to use
	 * @param bool $disable Optional. Whether to disable the dropdown
	 * @uses fct_get_period_id() To check the period id
	 * @uses fct_get_period_status() To get the period status
	 * @uses fct_get_period_statuses() To get all period statuses
	 * @uses apply_filters() Calls 'fct_get_form_period_status_dropdown' with the
	 *                        status dropdown, period id, and period statuses
	 * @return string HTML select list for selecting period status
	 */
	function fct_get_form_period_status_dropdown( $period_id = 0 ) {
		$period_id     = fct_get_period_id( $period_id );
		$period_status = fct_get_period_status( $period_id );
		$statuses      = fct_get_period_statuses();

		// Disable dropdown
		$disable = fct_is_control_active() && ! current_user_can( 'fiscaat' );

		$status_output = '<select name="fct_period_status" id="fct_period_status_select" '. disabled( $disable, true, false ) .'>' . "\n";

		foreach ( $statuses as $value => $label ) {
			$status_output .= "\t" . '<option value="' . $value . '"' . selected( $period_status, $value, false ) . '>' . esc_html( $label ) . '</option>' . "\n";
		}

		$status_output .= '</select>';

		return apply_filters( 'fct_get_form_period_status_dropdown', $status_output, $period_id, $statuses );
	}

