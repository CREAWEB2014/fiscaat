<?php

/**
 * Fiscaat Common Functions
 *
 * Common functions are ones that are used by more than one component, like
 * periods, accounts, records, users, account tags, etc...
 *
 * @package Fiscaat
 * @subpackage Functions
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Post **********************************************************************/

/**
 * Calls a post callback
 *
 * Enables post-type agnostic callbacks, with an interchangable
 * post type object element, like fct_get_{object}_post_type
 * 
 * @since 0.0.8
 * 
 * @param $callback Callback with post placeholder
 * @param string $post_type Optional. Post type name
 * @return mixed Callback response, False if callback is not valid
 */
function fct_post_callback( $callback, $post_type = '' ) {
	$type = fct_get_post_type_type( $post_type );
	$callback = sprintf( $callback, $type );
	if ( ! empty( $type ) && function_exists( $callback ) ) {
		return call_user_func( $callback );
	} else {
		return false;
	}
}

/** Formatting ****************************************************************/

/**
 * A Fiscaat specific method of formatting numeric values
 *
 * @param string $number Number to format
 * @param string $decimals Optional. Display decimals
 * @uses apply_filters() Calls 'fct_number_format' with the formatted values,
 *                        number and display decimals bool
 * @return string Formatted string
 */
function fct_number_format( $number = 0, $decimals = false, $decimal_point = '.', $thousands_sep = ',' ) {

	// If empty, set $number to (int) 0
	if ( ! is_numeric( $number ) )
		$number = 0;

	return apply_filters( 'fct_number_format', number_format( $number, $decimals, $decimal_point, $thousands_sep ), $number, $decimals, $decimal_point, $thousands_sep );
}

/**
 * A Fiscaat specific method of formatting numeric values
 *
 * @param string $number Number to format
 * @param string $decimals Optional. Display decimals
 * @uses apply_filters() Calls 'fct_number_format' with the formatted values,
 *                        number and display decimals bool
 * @return string Formatted string
 */
function fct_number_format_i18n( $number = 0, $decimals = false ) {

	// If empty, set $number to (int) 0
	if ( ! is_numeric( $number ) )
		$number = 0;

	return apply_filters( 'fct_number_format_i18n', number_format_i18n( $number, $decimals ), $number, $decimals );
}

/**
 * A Fiscaat specific method of formatting currency values to float
 * 
 * @param string $value Value to format
 * @uses fct_the_currency_format() To get the currency format
 * @uses fct_get_currency() To get the currency detail
 * @uses apply_filters() Calls 'fct_float_format' with the
 *                        floated value and currency format
 * @return float Floated value
 */
function fct_float_format( $value = '' ) {

	// Get currency format details
	$format = fct_the_currency_format();

	// Remove currency symbol if present
	$value = str_replace( fct_get_currency_symbol(), '', $value );

	// Value may already be a float. If not ...
	if ( (string) (float) $value !== $value ) {

		// ... Remove thousands separators
		$value = str_replace( $format['thousands_sep'], '', $value );

		// ... Change decimal separator to dot
		$value = str_replace( $format['decimal_point'], '.', $value );
	}

	// Remove whitespace
	$value = trim( $value );

	// Return value as float
	return (float) apply_filters( 'fct_float_format', $value );
}

/**
 * Convert time supplied from database query into specified date format.
 *
 * @uses mysql2date() To convert the format
 * @uses apply_filters() Calls 'fct_convert_date' with the time, date format
 *                        and translate bool
 *
 * @param int|object $time The database time to be converted
 * @param string $d Optional. Default is 'U'. Either 'G', 'U', or php date
 *                   format
 * @param bool $translate Optional. Default is false. Whether to translate the
 *                         result
 * @return string Returns timestamp
 */
function fct_convert_date( $time, $d = 'U', $translate = false ) {
	$time = mysql2date( $d, $time, $translate );

	return apply_filters( 'fct_convert_date', $time, $d, $translate );
}

/**
 * Return current time in given format
 * 
 * @uses current_time() To get the current time
 * @uses apply_filters() Calls 'fct_current_time' with the current time
 *                        and the format type
 *
 * @param string $type Optional. Defaults to 'mysql' database format
 * @param boolean $gmt Optional. Whether to return GMT
 * @return string Current time
 */
function fct_current_time( $type = 'mysql', $gmt = false ){
	return apply_filters( 'fct_current_time', current_time( $type, $gmt ), $type, $gmt );
}

/**
 * Output formatted time to display human readable time difference.
 *
 * @param string $older_date Unix timestamp from which the difference begins.
 * @param string $newer_date Optional. Unix timestamp from which the
 *                            difference ends. False for current time.
 * @uses fct_get_time_since() To get the formatted time
 */
function fct_time_since( $older_date, $newer_date = false ) {
	echo fct_get_time_since( $older_date, $newer_date = false );
}
	/**
	 * Return formatted time to display human readable time difference.
	 *
	 * @param string $older_date Unix timestamp from which the difference begins.
	 * @param string $newer_date Optional. Unix timestamp from which the
	 *                            difference ends. False for current time.
	 * @uses current_time() To get the current time in mysql format
	 * @uses human_time_diff() To get the time differene in since format
	 * @uses apply_filters() Calls 'fct_get_time_since' with the time
	 *                        difference and time
	 * @return string Formatted time
	 */
	function fct_get_time_since( $older_date, $newer_date = false ) {
		
		// Setup the strings
		$unknown_text   = apply_filters( 'fct_core_time_since_unknown_text',   __( 'sometime',  'fiscaat' ) );
		$right_now_text = apply_filters( 'fct_core_time_since_right_now_text', __( 'right now', 'fiscaat' ) );
		$ago_text       = apply_filters( 'fct_core_time_since_ago_text',       __( '%s ago',    'fiscaat' ) );

		// array of time year chunks
		$chunks = array(
			array( 60 * 60 * 24 * 365 , __( 'year',   'fiscaat' ), __( 'years',   'fiscaat' ) ),
			array( 60 * 60 * 24 * 30 ,  __( 'month',  'fiscaat' ), __( 'months',  'fiscaat' ) ),
			array( 60 * 60 * 24 * 7,    __( 'week',   'fiscaat' ), __( 'weeks',   'fiscaat' ) ),
			array( 60 * 60 * 24 ,       __( 'day',    'fiscaat' ), __( 'days',    'fiscaat' ) ),
			array( 60 * 60 ,            __( 'hour',   'fiscaat' ), __( 'hours',   'fiscaat' ) ),
			array( 60 ,                 __( 'minute', 'fiscaat' ), __( 'minutes', 'fiscaat' ) ),
			array( 1,                   __( 'second', 'fiscaat' ), __( 'seconds', 'fiscaat' ) )
		);

		if ( ! empty( $older_date ) && ! is_numeric( $older_date ) ) {
			$time_chunks = explode( ':', str_replace( ' ', ':', $older_date ) );
			$date_chunks = explode( '-', str_replace( ' ', '-', $older_date ) );
			$older_date  = gmmktime( (int) $time_chunks[1], (int) $time_chunks[2], (int) $time_chunks[3], (int) $date_chunks[1], (int) $date_chunks[2], (int) $date_chunks[0] );
		}

		// $newer_date will equal false if we want to know the time elapsed
		// between a date and the current time. $newer_date will have a value if
		// we want to work out time elapsed between two known dates.
		$newer_date = ( !$newer_date ) ? strtotime( current_time( 'mysql' ) ) : $newer_date;

		// Difference in seconds
		$since = $newer_date - $older_date;

		// Something went wrong with date calculation and we ended up with a negative date.
		if ( 0 > $since ) {
			$output = $unknown_text;

		// We only want to output two chunks of time here, eg:
		//     x years, xx months
		//     x days, xx hours
		// so there's only two bits of calculation below:
		} else {

			// Step one: the first chunk
			for ( $i = 0, $j = count( $chunks ); $i < $j; ++$i ) {
				$seconds = $chunks[$i][0];

				// Finding the biggest chunk (if the chunk fits, break)
				$count = floor( $since / $seconds );
				if ( 0 != $count ) {
					break;
				}
			}

			// If $i iterates all the way to $j, then the event happened 0 seconds ago
			if ( !isset( $chunks[$i] ) ) {
				$output = $right_now_text;

			} else {

				// Set output var
				$output = ( 1 == $count ) ? '1 '. $chunks[$i][1] : $count . ' ' . $chunks[$i][2];

				// Step two: the second chunk
				if ( $i + 2 < $j ) {
					$seconds2 = $chunks[$i + 1][0];
					$name2    = $chunks[$i + 1][1];
					$count2   = floor( ( $since - ( $seconds * $count ) ) / $seconds2 );

					// Add to output var
					if ( 0 != $count2 ) {
						$output .= ( 1 == $count2 ) ? _x( ',', 'Separator in time since', 'fiscaat' ) . ' 1 '. $name2 : _x( ',', 'Separator in time since', 'fiscaat' ) . ' ' . $count2 . ' ' . $chunks[$i + 1][2];
					}
				}

				// No output, so happened right now
				if ( ! (int) trim( $output ) ) {
					$output = $right_now_text;
				}
			}
		}

		// Append 'ago' to the end of time-since if not 'right now'
		if ( $output != $right_now_text ) {
			$output = sprintf( $ago_text, $output );
		}

		return apply_filters( 'fct_get_time_since', $output, $older_date, $newer_date );
	}

/** Misc **********************************************************************/

/**
 * Append 'view=all' to query string if it's already there from referer
 *
 * @param string $original_link Original Link to be modified
 * @param bool $force Override fct_get_view_all() check
 * @uses current_user_can() To check if the current user can moderate
 * @uses add_query_arg() To add args to the url
 * @uses apply_filters() Calls 'fct_add_view_all' with the link and original link
 * @return string The link with 'view=all' appended if necessary
 */
function fct_add_view_all( $original_link = '', $force = false ) {

	// Are we appending the view=all vars?
	if ( fct_get_view_all() || ! empty( $force ) )
		$link = add_query_arg( array( 'view' => 'all' ), $original_link );
	else
		$link = $original_link;

	return apply_filters( 'fct_add_view_all', $link, $original_link );
}

/**
 * Remove 'view=all' from query string
 *
 * @param string $original_link Original Link to be modified
 * @uses current_user_can() To check if the current user can fiscaat
 * @uses add_query_arg() To add args to the url
 * @uses apply_filters() Calls 'fct_add_view_all' with the link and original link
 * @return string The link with 'view=all' appended if necessary
 */
function fct_remove_view_all( $original_link = '' ) {
	return apply_filters( 'fct_add_view_all', remove_query_arg( 'view', $original_link ), $original_link );
}

/**
 * If current user can and is vewing all records
 *
 * @uses current_user_can() To check if the current user can fiscaat
 * @uses apply_filters() Calls 'fct_get_view_all' with the link and original link
 * @return bool Whether current user can and is viewing all
 */
function fct_get_view_all( $cap = 'fiscaat' ) {
	$retval = ( ( ! empty( $_GET['view'] ) && ( 'all' == $_GET['view'] ) && current_user_can( $cap ) ) );
	return apply_filters( 'fct_get_view_all', (bool) $retval );
}

/**
 * Assist pagination by returning correct page number
 *
 * @uses get_query_var() To get the 'paged' value
 * @return int Current page number
 */
function fct_get_paged() {
	global $wp_query;

	// Check the query var
	if ( get_query_var( 'paged' ) ) {
		$paged = get_query_var( 'paged' );

	// Check query paged
	} elseif ( ! empty( $wp_query->query['paged'] ) ) {
		$paged = $wp_query->query['paged'];
	}

	// Paged found
	if ( ! empty( $paged ) )
		return (int) $paged;

	// Default to first page
	return 1;
}

/**
 * Reorder and rename post statuses in Fiscaat
 *
 * Manipulates the $wp_post_statuses global to rename the publish 
 * post status label to 'Open' to better reflect the opposite state 
 * of 'Close'. Also moves the close post status next to publish.
 * 
 * @since 0.0.9
 *
 * @global $wp_post_statuses
 * @uses fct_get_public_status_id()
 * @uses fct_get_closed_status_id()
 * @uses get_post_type_object()
 */
function fct_arrange_post_statuses() {
	global $post_type, $wp_post_statuses;

	// Loop all post status ids
	foreach ( array_keys( $wp_post_statuses ) as $status ) {

		// Check post status
		switch ( $status ) {

			// Publish
			case fct_get_public_status_id() :

				// Rename publish post status labels
				$wp_post_statuses[ fct_get_public_status_id() ]->label       = __( 'Open', 'post', 'fiscaat' );
				$wp_post_statuses[ fct_get_public_status_id() ]->label_count = _nx_noop( 'Open <span class="count">(%s)</span>', 'Open <span class="count">(%s)</span>', 'post', 'fiscaat' );

				break;

			// Draft
			case 'draft' :

				// Remove from admin all list and show in admin status list conditionally
				$wp_post_statuses['draft']->show_in_admin_all_list    = false;
				$wp_post_statuses['draft']->show_in_admin_status_list = current_user_can( get_post_type_object( $post_type )->cap->create_posts );

				break;

			// Closed
			case fct_get_closed_status_id() :

				// Get close post status
				$close_status = $wp_post_statuses[ fct_get_closed_status_id() ];

				// Remove post status from current position
				unset( $wp_post_statuses[ fct_get_closed_status_id() ] );

				// Insert post status in position right after 'publish/open'. array_splice only does numeric keys
				$position = array_search( fct_get_public_status_id(), array_keys( $wp_post_statuses ) ) + 1;
				$wp_post_statuses = array_slice( $wp_post_statuses, 0, $position, true ) + array( 
					fct_get_closed_status_id() => $close_status
				) + array_slice( $wp_post_statuses, $position, null, true );

				break;
		}
	}
}

/** Statistics ****************************************************************/

/**
 * Get the Fiscaat statistics
 *
 * @param mixed $args Optional. The function supports these arguments (all
 *                     default to true):
 *  - count_users: Count users? If set to false, Fisci, Controllers and
 *                              Spectators are not counted.
 *  - count_periods: Count periods?
 *  - count_accounts: Count accounts?
 *  - count_records: Count records? 
 *  - count_current_records: Count records of current period? If set to false,
 *                           diapproved, unapproved, approved and closed records 
 *                           are also not counted.
 *  - count_end_value: Count to balance value of the current period?
 * @uses fct_get_total_fisci() To count the number of all Fisci
 * @uses fct_get_total_spectators() To count the number of all Spectators
 * @uses fct_count_posts() To count the number of created posts
 * @uses fct_get_period_post_type() To get the period post type
 * @uses fct_get_account_post_type() To get the account post type
 * @uses fct_get_record_post_type() To get the record post type
 * @uses wp_count_posts() To count the number of periods, accounts and records
 * @uses current_user_can() To check if the user is capable of doing things
 * @uses number_format_i18n() To format the number
 * @uses apply_filters() Calls 'fct_get_statistics' with the statistics and args
 * @return array Fiscaat statistics
 */
function fct_get_statistics( $args = '' ) {
	$r = fct_parse_args( $args, array(
		'count_users'            => true,
		'count_periods'          => true,
		'count_accounts'         => true,
		'count_records'          => true,
		'count_current_accounts' => true,
		'count_current_records'  => true,
		'count_end_value'        => true,
	), 'get_statistics' );

	// Users
	if ( ! empty( $r['count_users'] ) ) {
		$fiscus_count    = fct_get_total_fisci();
		$spectator_count = fct_get_total_spectators();	
	}

	// Periods
	if ( ! empty( $r['count_periods'] ) ) {
		$period_count = wp_count_posts( fct_get_period_post_type() );
		$period_count = array_sum( (array) $period_count ) - $period_count->{'auto-draft'};
	}

	// Accounts
	if ( ! empty( $r['count_accounts'] ) ) {
		$account_count = wp_count_posts( fct_get_account_post_type() );
		$account_count = array_sum( (array) $account_count ) - $account_count->{'auto-draft'};
	}

	// Records
	if ( ! empty( $r['count_records'] ) ) {
		$record_count = wp_count_posts( fct_get_record_post_type() );
		$record_count = array_sum( (array) $record_count ) - $record_count->{'auto-draft'};
	}

	// Current accounts in Fiscaat
	if ( ! empty( $r['count_current_accounts'] ) ) {

		// wp_count_posts has no filtering so use fct_count_posts
		$current_accounts = fct_count_posts( array( 
			'type'      => fct_get_account_post_type(), 
			'period_id' => fct_get_current_period_id() 
		) );

		// All accounts published
		$current_account_count = array_sum( (array) $current_accounts ) - $current_accounts->{'auto-draft'};
	}

	// Current records in Fiscaat
	if ( ! empty( $r['count_current_records'] ) ) {

		// wp_count_posts has no filtering so use fct_count_posts
		$current_records = fct_count_posts( array( 
			'type'      => fct_get_record_post_type(), 
			'period_id' => fct_get_current_period_id() 
		) );

		// All records published
		$current_record_count = array_sum( (array) $current_records ) - $current_records->{'auto-draft'};
	}

	// To Balance
	if ( ! empty( $r['count_end_value'] ) ) {
		$current_end_value = fct_get_period_end_value( fct_get_current_period_id() );
	}

	// Tally the tallies
	$stats = compact( 'fiscus_count', 'spectator_count', 'period_count', 'account_count', 'record_count', 'current_record_count', 'current_comment_count' );
	$stats = array_map( 'absint',             $stats );
	$stats = array_map( 'number_format_i18n', $stats );

	// Bypass formatting for current period end value, since it is a float.
	if ( isset( $current_end_value ) ) {
		$stats['current_end_value'] = $current_end_value;
	}

	return apply_filters( 'fct_get_statistics', $stats, $r );
}

/**
 * Count number of available posts
 * 
 * Mimics wp_count_posts() behavior with the added possibility to 
 * return counts per post parent and per meta key/value pair.
 *
 * @see wp_count_posts()
 *
 * @todo Improve cache keys with varying query vars.
 * 
 * @param mixed $args Optional. Arguments
 * @return object Number of posts for each status
 */
function fct_count_posts( $args = '' ) {
	global $wpdb;

	// Parse default query args
	$r = fct_parse_args( $args, array(
		'type'        => 'post',
		'perm'        => '',
		'post_parent' => null,
		'period_id'   => null,
		'meta_key'    => null,
		'meta_value'  => null,
	), 'count_posts' );

	if ( ! post_type_exists( $r['type'] ) )
		return new stdClass;

	// Parse period parent query vars
	if ( ! empty( $r['period_id'] ) ) {
		$r['meta_key']   = '_fct_period_id';
		$r['meta_value'] = (int) $r['period_id'];
	}

	$cache_key = _count_posts_cache_key( $r['type'], $r['perm'] );
	$counts    = wp_cache_get( $cache_key, 'counts' );
	if ( false === $counts ) {

		$query['select'] = "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} p";

		// Setup join statement by meta key/value pair
		if ( ! empty( $r['meta_key'] ) && ! empty( $r['meta_value'] ) ) {
			$query['join'] = " INNER JOIN {$wpdb->postmeta} pm ON (p.ID = pm.post_id)";
		}

		// Setup where statement
		$query['where']  = " WHERE post_type = %s";
		if ( 'readable' == $r['perm'] && is_user_logged_in() ) {
			$post_type_object = get_post_type_object( $r['type'] );
			if ( ! current_user_can( $post_type_object->cap->read_private_posts ) ) {
				$query['where'] .= $wpdb->prepare( " AND (p.post_status != 'private' OR ( p.post_author = %d AND p.post_status = 'private' ))",
					get_current_user_id()
				);
			}
		}

		// Query for post parent
		if ( ! empty( $r['post_parent'] ) ) {
			$query['where'] .= $wpdb->prepare( " AND p.post_parent = %s", $r['post_parent'] );
		}

		// Query for post meta key/value pair
		if ( ! empty( $r['meta_key'] ) && ! empty( $r['meta_value'] ) ) {
			$query['where'] .= $wpdb->prepare( " AND pm.meta_key = %s AND pm.meta_value = %s",
				$r['meta_key'],
				$r['meta_value']
			);
		}

		$query['groupby'] = ' GROUP BY p.post_status';

		// Run the query
		$query = implode( ' ', $query );
		$results = (array) $wpdb->get_results( $wpdb->prepare( $query, $r['type'] ), ARRAY_A );
		$counts = array_fill_keys( get_post_stati(), 0 );

		foreach ( $results as $row )
			$counts[ $row['post_status'] ] = $row['num_posts'];

		$counts = (object) $counts;
		wp_cache_set( $cache_key, $counts, 'counts' );
	}

	/**
	 * Modify returned post counts by status for the current post type.
	 *
	 * @since 3.7.0
	 *
	 * @param object $counts    An object containing the current post_type's post
	 *                          counts by status.
	 * @param string $r['type'] Post type.
	 * @param string $r['perm'] The permission to determine if the posts are 'readable'
	 *                          by the current user.
	 */
	return apply_filters( 'wp_count_posts', $counts, $r['type'], $r['perm'] );
}

/** Queries *******************************************************************/

/**
 * Merge user defined arguments into defaults array.
 *
 * This function is used throughout Fiscaat to allow for either a string or array
 * to be merged into another array. It is identical to wp_parse_args() except
 * it allows for arguments to be passively or aggressively filtered using the
 * optional $filter_key parameter.
 *
 * @param string|array $args Value to merge with $defaults
 * @param array $defaults Array that serves as the defaults.
 * @param string $filter_key String to key the filters from
 * @return array Merged user defined values with defaults.
 */
function fct_parse_args( $args, $defaults = '', $filter_key = '' ) {

	// Setup a temporary array from $args
	if ( is_object( $args ) )
		$r = get_object_vars( $args );
	elseif ( is_array( $args ) )
		$r =& $args;
	else
		wp_parse_str( $args, $r );

	// Passively filter the args before the parse
	if ( ! empty( $filter_key ) )
		$r = apply_filters( 'fct_before_' . $filter_key . '_parse_args', $r );

	// Parse
	if ( is_array( $defaults ) )
		$r = array_merge( $defaults, $r );

	// Aggressively filter the args after the parse
	if ( ! empty( $filter_key ) )
		$r = apply_filters( 'fct_after_' . $filter_key . '_parse_args', $r );

	// Return the parsed results
	return $r;
}

/**
 * Query the DB and get a count of public children
 *
 * @param int $parent_id Parent id
 * @param string $post_type Post type. Defaults to 'post'
 * @uses fct_get_post_stati() To get the public post stati
 * @uses wp_cache_get() To check if there is a cache of the children count
 * @uses wpdb::prepare() To prepare the query
 * @uses wpdb::get_var() To get the result of the query in a variable
 * @uses wp_cache_set() To set the cache for future use
 * @uses apply_filters() Calls 'fct_get_public_child_count' with the child
 *                        count, parent id and post type
 * @return int The number of children
 */
function fct_get_public_child_count( $parent_id = 0, $post_type = 'post' ) {
	global $wpdb;

	// Bail if nothing passed
	if ( empty( $parent_id ) )
		return false;

	// The ID of the cached query
	$cache_id = 'fct_parent_' . $parent_id . '_type_' . $post_type . '_child_count';

	// Check for cache and set if needed
	$child_count = wp_cache_get( $cache_id, 'fiscaat' );
	if ( empty( $child_count ) ) {

		// Join post statuses together
		$post_status = "'" . implode( "', '", fct_get_post_stati( $post_type ) ) . "'";

		$child_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_parent = %d AND post_status IN ( {$post_status} ) AND post_type = '%s';", $parent_id, $post_type ) );
		wp_cache_set( $cache_id, $child_count, 'fiscaat' );
	}

	// Filter and return
	return apply_filters( 'fct_get_public_child_count', (int) $child_count, (int) $parent_id, $post_type );
}

/**
 * Query the DB and get the child id's of public children
 *
 * @param int $parent_id Parent id
 * @param string $post_type Post type. Defaults to 'post'
 * @uses fct_get_post_stati() To get the public post stati
 * @uses wp_cache_get() To check if there is a cache of the children
 * @uses wpdb::prepare() To prepare the query
 * @uses wpdb::get_col() To get the result of the query in an array
 * @uses wp_cache_set() To set the cache for future use
 * @uses apply_filters() Calls 'fct_get_public_child_ids' with the child ids,
 *                        parent id and post type
 * @return array The array of children
 */
function fct_get_public_child_ids( $parent_id = 0, $post_type = 'post' ) {
	global $wpdb;

	// Bail if nothing passed
	if ( empty( $parent_id ) )
		return false;

	// The ID of the cached query
	$cache_id = 'fct_parent_public_' . $parent_id . '_type_' . $post_type . '_child_ids';

	// Check for cache and set if needed
	$child_ids = wp_cache_get( $cache_id, 'fiscaat' );
	if ( empty( $child_ids ) ) {

		// Join post statuses together
		$post_status = "'" . implode( "', '", fct_get_post_stati( $post_type ) ) . "'";

		$child_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_parent = %d AND post_status IN ( {$post_status} ) AND post_type = %s ORDER BY ID DESC;", $parent_id, $post_type ) );
		wp_cache_set( $cache_id, $child_ids, 'fiscaat' );
	}

	// Filter and return
	return apply_filters( 'fct_get_public_child_ids', $child_ids, (int) $parent_id, $post_type );
}

/**
 * Query the DB and get a the child id's of all children
 *
 * @param int $parent_id Parent id
 * @param string $post_type Post type. Defaults to 'post'
 * @uses fct_get_account_post_type() To get the account post type
 * @uses wp_cache_get() To check if there is a cache of the children
 * @uses wpdb::prepare() To prepare the query
 * @uses wpdb::get_col() To get the result of the query in an array
 * @uses wp_cache_set() To set the cache for future use
 * @uses apply_filters() Calls 'fct_get_public_child_ids' with the child ids,
 *                        parent id and post type
 * @return array The array of children
 */
function fct_get_all_child_ids( $parent_id = 0, $post_type = 'post' ) {
	global $wpdb;

	// Bail if nothing passed
	if ( empty( $parent_id ) )
		return false;

	// The ID of the cached query
	$cache_id = 'fct_parent_all_' . $parent_id . '_type_' . $post_type . '_child_ids';

	// Check for cache and set if needed
	$child_ids = wp_cache_get( $cache_id, 'fiscaat' );
	if ( empty( $child_ids ) ) {

		// Join post statuses together
		$post_status = "'" . join( "', '", fct_get_post_stati( $post_type, 'all' ) ) . "'";

		$child_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_parent = %d AND post_status IN ( {$post_status} ) AND post_type = '%s' ORDER BY ID DESC;", $parent_id, $post_type ) );
		wp_cache_set( $cache_id, $child_ids, 'fiscaat' );
	}

	// Filter and return
	return apply_filters( 'fct_get_all_child_ids', $child_ids, (int) $parent_id, $post_type );
}

/**
 * Return the public post stati per post type
 *
 * @since 0.0.9
 *
 * @uses fct_get_public_status_id() To get the publish post status
 * @uses fct_get_closed_status_id() To get the close post status
 * @uses fct_get_trash_status_id() To get the trash post status
 * @uses apply_filters() Calls 'fct_get_post_stati' with the
 *                        post stati and post type
 * 
 * @param string $post_type Post type
 * @param string $which Optional. Defaults to 'public'.
 * @return array Post stati
 */
function fct_get_post_stati( $post_type = '', $which = 'public' ) {

	// Setup default public post stati
	$post_stati = array( 
		fct_get_public_status_id(),
		fct_get_closed_status_id()
	);

	// Add to all post stati
	if ( 'all' == $which ) {
		$post_stati[] = fct_get_trash_status_id();
	}

	return apply_filters( 'fct_get_post_stati', $post_stati, $post_type, $which );
}

/**
 * Add checks for Fiscaat conditions to parse_query action
 *
 * If it's a period edit, WP_Query::fct_is_period_edit is set to true
 * If it's a account edit, WP_Query::fct_is_account_edit is set to true
 * If it's a record edit, WP_Query::fct_is_record_edit is set to true.
 *
 * If it's a view page, WP_Query::fct_is_view is set to true
 *
 * @param WP_Query $posts_query
 *
 * @uses get_query_var() To get {@link WP_Query} query var
 * @uses fct_get_period_post_type() To get the period post type
 * @uses fct_get_account_post_type() To get the account post type
 * @uses fct_get_record_post_type() To get the record post type
 * @uses remove_action() To remove the auto save post revision action
 */
function fct_parse_query( $posts_query ) {

	// Bail if $posts_query is not the main loop
	if ( ! $posts_query->is_main_query() )
		return;

	// Bail if filters are suppressed on this query
	if ( true == $posts_query->get( 'suppress_filters' ) )
		return;

	// Bail if in admin
	if ( is_admin() )
		return;

	// Get query variables
	$is_edit  = $posts_query->get( fct_get_edit_rewrite_id() );

	// Period/Account/Record Edit Page
	if ( ! empty( $is_edit ) ) {

		// Get the post type from the main query loop
		$post_type = $posts_query->get( 'post_type' );
		
		// Check which post_type we are editing, if any
		if ( ! empty( $post_type ) ) {
			switch( $post_type ) {

				// We are editing a period
				case fct_get_period_post_type() :
					$posts_query->fct_is_period_edit = true;
					$posts_query->fct_is_edit      = true;
					break;

				// We are editing a account
				case fct_get_account_post_type() :
					$posts_query->fct_is_account_edit = true;
					$posts_query->fct_is_edit         = true;
					break;

				// We are editing a record
				case fct_get_record_post_type() :
					$posts_query->fct_is_record_edit = true;
					$posts_query->fct_is_edit        = true;
					break;
			}
		}

		// We save post revisions on our own
		remove_action( 'pre_post_update', 'wp_save_post_revision' );
	}
}

/** Globals *******************************************************************/

/**
 * Get the unfiltered value of a global $post's key
 *
 * Used most frequently when editing a period/account/record
 *
 * @global WP_Query $post
 * @param string $field Name of the key
 * @param string $context How to sanitize - raw|edit|db|display|attribute|js
 * @return string Field value
 */
function fct_get_global_post_field( $field = 'ID', $context = 'edit' ) {
	global $post;

	$retval = isset( $post->$field ) ? $post->$field : '';
	$retval = sanitize_post_field( $field, $retval, $post->ID, $context );

	return apply_filters( 'fct_get_global_post_field', $retval, $post );
}

/** Templates ******************************************************************/

/**
 * Used to guess if page exists at requested path
 *
 * @uses get_option() To see if pretty permalinks are enabled
 * @uses get_page_by_path() To see if page exists at path
 *
 * @param string $path
 * @return mixed False if no page, Page object if true
 */
function fct_get_page_by_path( $path = '' ) {

	// Default to false
	$retval = false;

	// Path is not empty
	if ( ! empty( $path ) ) {

		// Pretty permalinks are on so path might exist
		if ( get_option( 'permalink_structure' ) ) {
			$retval = get_page_by_path( $path );
		}
	}

	return apply_filters( 'fct_get_page_by_path', $retval, $path );
}

/**
 * Sets the 404 status.
 *
 * Used primarily with periods/accounts/records on the front.
 *
 * @global WP_Query $wp_query
 * @uses WP_Query::set_404()
 */
function fct_set_404() {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Conditional query tags do not work before the query is run. Before then, they always return false.', 'fiscaat' ), '3.1' );
		return false;
	}

	$wp_query->set_404();
}

/** Currencies ****************************************************************/

/**
 * Get the details of a single currency or a list of all available currencies
 * 
 * Items in the list have the following format: 
 * 'ISO-4217 code' => array( 'symbol', 'name' )
 * 
 * @link https://github.com/piwik/piwik/blob/master/core/DataFiles/Currencies.php
 *
 * @uses apply_filters() Calls 'fct_get_currencies' with the currencies
 * @param string $currency Optional. Currency ISO code
 * @return array All currencies or single currency if ISO code provided
 */
function fct_get_currencies( $currency = '' ){

	// Collect all currencies
	$currencies = array( 

		// Top 5 by global trading volume
		'USD' => array( 'symbol' => '$', 'name' => 'US dollar'),
		'EUR' => array( 'symbol' => '€', 'name' => 'Euro'),
		'JPY' => array( 'symbol' => '¥', 'name' => 'Japanese yen'),
		'GBP' => array( 'symbol' => '£', 'name' => 'British pound'),
		'CHF' => array( 'symbol' => 'Fr', 'name' => 'Swiss franc'),

		'AFN' => array( 'symbol' => '؋', 'name' => 'Afghan afghani'),
		'ALL' => array( 'symbol' => 'L', 'name' => 'Albanian lek'),
		'DZD' => array( 'symbol' => 'د.ج', 'name' => 'Algerian dinar'),
		'AOA' => array( 'symbol' => 'Kz', 'name' => 'Angolan kwanza'),
		'ARS' => array( 'symbol' => '$', 'name' => 'Argentine peso'),
		'AMD' => array( 'symbol' => 'դր.', 'name' => 'Armenian dram'),
		'AWG' => array( 'symbol' => 'ƒ', 'name' => 'Aruban florin'),
		'AUD' => array( 'symbol' => '$', 'name' => 'Australian dollar'),
		'AZN' => array( 'symbol' => 'm', 'name' => 'Azerbaijani manat'),
		'BSD' => array( 'symbol' => '$', 'name' => 'Bahamian dollar'),
		'BHD' => array( 'symbol' => '.د.ب', 'name' => 'Bahraini dinar'),
		'BDT' => array( 'symbol' => '৳', 'name' => 'Bangladeshi taka'),
		'BBD' => array( 'symbol' => '$', 'name' => 'Barbadian dollar'),
		'BYR' => array( 'symbol' => 'Br', 'name' => 'Belarusian ruble'),
		'BZD' => array( 'symbol' => '$', 'name' => 'Belize dollar'),
		'BMD' => array( 'symbol' => '$', 'name' => 'Bermudian dollar'),
		'BTN' => array( 'symbol' => 'Nu.', 'name' => 'Bhutanese ngultrum'),
		'BOB' => array( 'symbol' => 'Bs.', 'name' => 'Bolivian boliviano'),
		'BAM' => array( 'symbol' => 'KM', 'name' => 'Bosnia Herzegovina mark'),
		'BWP' => array( 'symbol' => 'P', 'name' => 'Botswana pula'),
		'BRL' => array( 'symbol' => 'R$', 'name' => 'Brazilian real'),
	//	'GBP' => array( 'symbol' => '£', 'name' => 'British pound'),
		'BND' => array( 'symbol' => '$', 'name' => 'Brunei dollar'),
		'BGN' => array( 'symbol' => 'лв', 'name' => 'Bulgarian lev'),
		'BIF' => array( 'symbol' => 'Fr', 'name' => 'Burundian franc'),
		'KHR' => array( 'symbol' => '៛', 'name' => 'Cambodian riel'),
		'CAD' => array( 'symbol' => '$', 'name' => 'Canadian dollar'),
		'CVE' => array( 'symbol' => '$', 'name' => 'Cape Verdean escudo'),
		'KYD' => array( 'symbol' => '$', 'name' => 'Cayman Islands dollar'),
		'XAF' => array( 'symbol' => 'Fr', 'name' => 'Central African CFA franc'),
		'CLP' => array( 'symbol' => '$', 'name' => 'Chilean peso'),
		'CNY' => array( 'symbol' => '元', 'name' => 'Chinese yuan'),
		'COP' => array( 'symbol' => '$', 'name' => 'Colombian peso'),
		'KMF' => array( 'symbol' => 'Fr', 'name' => 'Comorian franc'),
		'CDF' => array( 'symbol' => 'Fr', 'name' => 'Congolese franc'),
		'CRC' => array( 'symbol' => '₡', 'name' => 'Costa Rican colón'),
		'HRK' => array( 'symbol' => 'kn', 'name' => 'Croatian kuna'),
		'XPF' => array( 'symbol' => 'F', 'name' => 'CFP franc'),
		'CUC' => array( 'symbol' => '$', 'name' => 'Cuban convertible peso'),
		'CUP' => array( 'symbol' => '$', 'name' => 'Cuban peso'),
		'CMG' => array( 'symbol' => 'ƒ', 'name' => 'Curaçao and Sint Maarten guilder'),
		'CZK' => array( 'symbol' => 'Kč', 'name' => 'Czech koruna'),
		'DKK' => array( 'symbol' => 'kr', 'name' => 'Danish krone'),
		'DJF' => array( 'symbol' => 'Fr', 'name' => 'Djiboutian franc'),
		'DOP' => array( 'symbol' => '$', 'name' => 'Dominican peso'),
		'XCD' => array( 'symbol' => '$', 'name' => 'East Caribbean dollar'),
		'EGP' => array( 'symbol' => 'ج.م', 'name' => 'Egyptian pound'),
		'ERN' => array( 'symbol' => 'Nfk', 'name' => 'Eritrean nakfa'),
		'EEK' => array( 'symbol' => 'kr', 'name' => 'Estonian kroon'),
		'ETB' => array( 'symbol' => 'Br', 'name' => 'Ethiopian birr'),
	//	'EUR' => array( 'symbol' => '€', 'name' => 'Euro'),
		'FKP' => array( 'symbol' => '£', 'name' => 'Falkland Islands pound'),
		'FJD' => array( 'symbol' => '$', 'name' => 'Fijian dollar'),
		'GMD' => array( 'symbol' => 'D', 'name' => 'Gambian dalasi'),
		'GEL' => array( 'symbol' => 'ლ', 'name' => 'Georgian lari'),
		'GHS' => array( 'symbol' => '₵', 'name' => 'Ghanaian cedi'),
		'GIP' => array( 'symbol' => '£', 'name' => 'Gibraltar pound'),
		'GTQ' => array( 'symbol' => 'Q', 'name' => 'Guatemalan quetzal'),
		'GNF' => array( 'symbol' => 'Fr', 'name' => 'Guinean franc'),
		'GYD' => array( 'symbol' => '$', 'name' => 'Guyanese dollar'),
		'HTG' => array( 'symbol' => 'G', 'name' => 'Haitian gourde'),
		'HNL' => array( 'symbol' => 'L', 'name' => 'Honduran lempira'),
		'HKD' => array( 'symbol' => '$', 'name' => 'Hong Kong dollar'),
		'HUF' => array( 'symbol' => 'Ft', 'name' => 'Hungarian forint'),
		'ISK' => array( 'symbol' => 'kr', 'name' => 'Icelandic króna'),
		'INR' => array( 'symbol' => '‎₹', 'name' => 'Indian rupee'),
		'IDR' => array( 'symbol' => 'Rp', 'name' => 'Indonesian rupiah'),
		'IRR' => array( 'symbol' => '﷼', 'name' => 'Iranian rial'),
		'IQD' => array( 'symbol' => 'ع.د', 'name' => 'Iraqi dinar'),
		'ILS' => array( 'symbol' => '₪', 'name' => 'Israeli new shekel'),
		'JMD' => array( 'symbol' => '$', 'name' => 'Jamaican dollar'),
	//	'JPY' => array( 'symbol' => '¥', 'name' => 'Japanese yen'),
		'JOD' => array( 'symbol' => 'د.ا', 'name' => 'Jordanian dinar'),
		'KZT' => array( 'symbol' => '₸', 'name' => 'Kazakhstani tenge'),
		'KES' => array( 'symbol' => 'Sh', 'name' => 'Kenyan shilling'),
		'KWD' => array( 'symbol' => 'د.ك', 'name' => 'Kuwaiti dinar'),
		'KGS' => array( 'symbol' => 'лв', 'name' => 'Kyrgyzstani som'),
		'LAK' => array( 'symbol' => '₭', 'name' => 'Lao kip'),
		'LVL' => array( 'symbol' => 'Ls', 'name' => 'Latvian lats'),
		'LBP' => array( 'symbol' => 'ل.ل', 'name' => 'Lebanese pound'),
		'LSL' => array( 'symbol' => 'L', 'name' => 'Lesotho loti'),
		'LRD' => array( 'symbol' => '$', 'name' => 'Liberian dollar'),
		'LYD' => array( 'symbol' => 'ل.د', 'name' => 'Libyan dinar'),
		'LTL' => array( 'symbol' => 'Lt', 'name' => 'Lithuanian litas'),
		'MOP' => array( 'symbol' => 'P', 'name' => 'Macanese pataca'),
		'MKD' => array( 'symbol' => 'ден', 'name' => 'Macedonian denar'),
		'MGA' => array( 'symbol' => 'Ar', 'name' => 'Malagasy ariary'),
		'MWK' => array( 'symbol' => 'MK', 'name' => 'Malawian kwacha'),
		'MYR' => array( 'symbol' => 'RM', 'name' => 'Malaysian ringgit'),
		'MVR' => array( 'symbol' => 'ރ.', 'name' => 'Maldivian rufiyaa'),
		'MRO' => array( 'symbol' => 'UM', 'name' => 'Mauritanian ouguiya'),
		'MUR' => array( 'symbol' => '₨', 'name' => 'Mauritian rupee'),
		'MXN' => array( 'symbol' => '$', 'name' => 'Mexican peso'),
		'MDL' => array( 'symbol' => 'L', 'name' => 'Moldovan leu'),
		'MNT' => array( 'symbol' => '₮', 'name' => 'Mongolian tögrög'),
		'MAD' => array( 'symbol' => 'د.م.', 'name' => 'Moroccan dirham'),
		'MZN' => array( 'symbol' => 'MTn', 'name' => 'Mozambican metical'),
		'MMK' => array( 'symbol' => 'K', 'name' => 'Myanma kyat'),
		'NAD' => array( 'symbol' => '$', 'name' => 'Namibian dollar'),
		'NPR' => array( 'symbol' => '₨', 'name' => 'Nepalese rupee'),
		'ANG' => array( 'symbol' => 'ƒ', 'name' => 'Netherlands Antillean guilder'),
		'TWD' => array( 'symbol' => '$', 'name' => 'New Taiwan dollar'),
		'NZD' => array( 'symbol' => '$', 'name' => 'New Zealand dollar'),
		'NIO' => array( 'symbol' => 'C$', 'name' => 'Nicaraguan córdoba'),
		'NGN' => array( 'symbol' => '₦', 'name' => 'Nigerian naira'),
		'KPW' => array( 'symbol' => '₩', 'name' => 'North Korean won'),
		'NOK' => array( 'symbol' => 'kr', 'name' => 'Norwegian krone'),
		'OMR' => array( 'symbol' => 'ر.ع.', 'name' => 'Omani rial'),
		'PKR' => array( 'symbol' => '₨', 'name' => 'Pakistani rupee'),
		'PAB' => array( 'symbol' => 'B/.', 'name' => 'Panamanian balboa'),
		'PGK' => array( 'symbol' => 'K', 'name' => 'Papua New Guinean kina'),
		'PYG' => array( 'symbol' => '₲', 'name' => 'Paraguayan guaraní'),
		'PEN' => array( 'symbol' => 'S/.', 'name' => 'Peruvian nuevo sol'),
		'PHP' => array( 'symbol' => '₱', 'name' => 'Philippine peso'),
		'PLN' => array( 'symbol' => 'zł', 'name' => 'Polish złoty'),
		'QAR' => array( 'symbol' => 'ر.ق', 'name' => 'Qatari riyal'),
		'RON' => array( 'symbol' => 'L', 'name' => 'Romanian leu'),
		'RUB' => array( 'symbol' => 'руб.', 'name' => 'Russian ruble'),
		'RWF' => array( 'symbol' => 'Fr', 'name' => 'Rwandan franc'),
		'SHP' => array( 'symbol' => '£', 'name' => 'Saint Helena pound'),
		'SVC' => array( 'symbol' => '₡', 'name' => 'Salvadoran colón'),
		'WST' => array( 'symbol' => 'T', 'name' => 'Samoan tala'),
		'STD' => array( 'symbol' => 'Db', 'name' => 'São Tomé and Príncipe dobra'),
		'SAR' => array( 'symbol' => 'ر.س', 'name' => 'Saudi riyal'),
		'RSD' => array( 'symbol' => 'дин. or din.', 'name' => 'Serbian dinar'),
		'SCR' => array( 'symbol' => '₨', 'name' => 'Seychellois rupee'),
		'SLL' => array( 'symbol' => 'Le', 'name' => 'Sierra Leonean leone'),
		'SGD' => array( 'symbol' => '$', 'name' => 'Singapore dollar'),
		'SBD' => array( 'symbol' => '$', 'name' => 'Solomon Islands dollar'),
		'SOS' => array( 'symbol' => 'Sh', 'name' => 'Somali shilling'),
		'ZAR' => array( 'symbol' => 'R', 'name' => 'South African rand'),
		'KRW' => array( 'symbol' => '₩', 'name' => 'South Korean won'),
		'LKR' => array( 'symbol' => 'Rs', 'name' => 'Sri Lankan rupee'),
		'SDG' => array( 'symbol' => 'جنيه سوداني', 'name' => 'Sudanese pound'),
		'SRD' => array( 'symbol' => '$', 'name' => 'Surinamese dollar'),
		'SZL' => array( 'symbol' => 'L', 'name' => 'Swazi lilangeni'),
		'SEK' => array( 'symbol' => 'kr', 'name' => 'Swedish krona'),
	//	'CHF' => array( 'symbol' => 'Fr', 'name' => 'Swiss franc'),
		'SYP' => array( 'symbol' => 'ل.س', 'name' => 'Syrian pound'),
		'TJS' => array( 'symbol' => 'ЅМ', 'name' => 'Tajikistani somoni'),
		'TZS' => array( 'symbol' => 'Sh', 'name' => 'Tanzanian shilling'),
		'THB' => array( 'symbol' => '฿', 'name' => 'Thai baht'),
		'TOP' => array( 'symbol' => 'T$', 'name' => 'Tongan paʻanga'),
		'TTD' => array( 'symbol' => '$', 'name' => 'Trinidad and Tobago dollar'),
		'TND' => array( 'symbol' => 'د.ت', 'name' => 'Tunisian dinar'),
		'TRY' => array( 'symbol' => 'TL', 'name' => 'Turkish lira'),
		'TMM' => array( 'symbol' => 'm', 'name' => 'Turkmenistani manat'),
		'UGX' => array( 'symbol' => 'Sh', 'name' => 'Ugandan shilling'),
		'UAH' => array( 'symbol' => '₴', 'name' => 'Ukrainian hryvnia'),
		'AED' => array( 'symbol' => 'د.إ', 'name' => 'United Arab Emirates dirham'),
	//	'USD' => array( 'symbol' => '$', 'name' => 'United States dollar'),
		'UYU' => array( 'symbol' => '$', 'name' => 'Uruguayan peso'),
		'UZS' => array( 'symbol' => 'лв', 'name' => 'Uzbekistani som'),
		'VUV' => array( 'symbol' => 'Vt', 'name' => 'Vanuatu vatu'),
		'VEF' => array( 'symbol' => 'Bs F', 'name' => 'Venezuelan bolívar'),
		'VND' => array( 'symbol' => '₫', 'name' => 'Vietnamese đồng'),
		'XOF' => array( 'symbol' => 'Fr', 'name' => 'West African CFA franc'),
		'YER' => array( 'symbol' => '﷼', 'name' => 'Yemeni rial'),
		'ZMK' => array( 'symbol' => 'ZK', 'name' => 'Zambian kwacha'),
		'ZWL' => array( 'symbol' => '$', 'name' => 'Zimbabwean dollar'),
	);

	// Return single currency details if ISO is provided
	if ( ! empty( $currency ) ) {

		// Fallback to 'USD' if not found
		if ( ! isset( $currencies[$currency] ) ) {
			$currency = 'USD';
		} 

		$details = $currencies[$currency];

	// Return all currencies
	} else {
		$details = $currencies;
	}

	return apply_filters( 'fct_get_currencies', $details, $currency );
}

/**
 * Returns the currency format
 *
 * For international currency formatting support see
 * @link http://www.joelpeterson.com/blog/2011/03/formatting-over-100-currencies-in-php/
 * 
 * @uses apply_filters() Calls 'fct_the_currency_format' with the currency format
 * @return array Currency format
 */
function fct_the_currency_format(){
	return apply_filters( 'fct_the_currency_format', array(
		'thousands_sep' => get_option( '_fct_thousands_sep' ),
		'decimal_point' => get_option( '_fct_decimal_point' ),
		'decimals'      => get_option( '_fct_num_decimals'  ),
	) );
}

/**
 * Sanitize currency input to be a listed currency
 * 
 * @param string $input Currency input
 * @uses fct_get_currencies() To get available currencies
 * @return string Sanitized currency
 */
function fct_sanitize_currency( $input = '' ) {
	return in_array( $input, array_keys( fct_get_currencies() ) ) ? $input : 'USD';
}

/**
 * Return the available currency positions
 *
 * @since 0.0.8
 *
 * @uses apply_filters() Calls 'fct_get_currency_positions' with the
 *                        positions
 * @param string $position Optional. Requested position name
 * @return array Positions as array( key => description ) or single position
 */
function fct_get_currency_positions( $position = '' ) {

	// Default positions. Formats: %1$s: Currency symbol, %2$s: Value.
	$positions = array(
		'left'        => array( 'format' => '%1$s%2$s',  'label' => __( 'Left',             'fiscaat' ) ),
		'right'       => array( 'format' => '%2$s%1$s',  'label' => __( 'Right',            'fiscaat' ) ),
		'left_space'  => array( 'format' => '%1$s %2$s', 'label' => __( 'Left with space',  'fiscaat' ) ),
		'right_space' => array( 'format' => '%2$s %1$s', 'label' => __( 'Right with space', 'fiscaat' ) ),
	);

	// Select position if requested. Default to 'left_space'
	if ( ! empty( $position ) ) {
		$positions = in_array( $position, array_keys( $positions) ) ? $positions[ $position ] : $positions['left_space'];
	}

	return apply_filters( 'fct_get_currency_positions', $positions, $position );
}

/**
 * Sanitize currency position input
 * 
 * @param string $input Currency input
 * @uses fct_get_currencies() To get available currencies
 * @return string Sanitized currency
 */
function fct_sanitize_currency_position( $input = '' ) {
	return in_array( $input, array_keys( fct_get_currency_positions() ) ) ? $input : 'left_space';
}

