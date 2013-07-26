<?php

/**
 * Fiscaat Common Functions
 *
 * Common functions are ones that are used by more than one component, like
 * years, accounts, records, users, account tags, etc...
 *
 * @package Fiscaat
 * @subpackage Functions
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/** Formatting ****************************************************************/

/**
 * A Fiscaat specific method of formatting numeric values
 *
 * @param string $number Number to format
 * @param string $decimals Optional. Display decimals
 * @uses apply_filters() Calls 'fiscaat_number_format' with the formatted values,
 *                        number and display decimals bool
 * @return string Formatted string
 */
function fiscaat_number_format( $number = 0, $decimals = false, $dec_point = '.', $thousands_sep = ',' ) {

	// If empty, set $number to (int) 0
	if ( ! is_numeric( $number ) )
		$number = 0;

	return apply_filters( 'fiscaat_number_format', number_format( $number, $decimals, $dec_point, $thousands_sep ), $number, $decimals, $dec_point, $thousands_sep );
}

/**
 * A Fiscaat specific method of formatting numeric values
 *
 * @param string $number Number to format
 * @param string $decimals Optional. Display decimals
 * @uses apply_filters() Calls 'fiscaat_number_format' with the formatted values,
 *                        number and display decimals bool
 * @return string Formatted string
 */
function fiscaat_number_format_i18n( $number = 0, $decimals = false ) {

	// If empty, set $number to (int) 0
	if ( ! is_numeric( $number ) )
		$number = 0;

	return apply_filters( 'fiscaat_number_format_i18n', number_format_i18n( $number, $decimals ), $number, $decimals );
}

/**
 * Output a Fiscaat specific method of formatting values by currency
 *
 * @param string $value Number to format
 * @param bool $symbol Whether to return with currency symbol
 * @return string Formatted string
 */
function fiscaat_currency_format( $number = 0, $symbol = false ) {
	echo fiscaat_get_currency_format( $number, $symbol );
}
	/**
	 * Return a Fiscaat specific method of formatting values by currency
	 *
	 * Enables currency being different from locale.
	 * 
	 * @param string $value Number to format
	 * @param bool $symbol Whether to return with currency symbol
	 * @uses fiscaat_get_currency() To get the currency
	 * @uses fiscaat_the_currency_format_INR() To handle INR currency format
	 * @uses fiscaat_the_currency_format() To get the currency format
	 * @uses apply_filters() Calls 'fiscaat_currency_format_symbol' with whether
	 *                        to append the symbol
	 * @uses apply_filters() Calls 'fiscaat_get_currency_format' with the formatted
	 *                        value, original number, and whether to add symbol
	 * @return string Formatted string
	 */
	function fiscaat_get_currency_format( $number = 0, $symbol = false ) {
		$currency = fiscaat_get_currency();

		// Treat INR currency differently
		if ( 'INR' == $currency ) 
			$retval = fiscaat_the_currency_format_INR( $number );

		else {
			$format = fiscaat_the_currency_format( $currency );
			$retval = number_format( $number, $format['decimals'], $format['dec_point'], $format['thousands_sep'] );
		}

		// Prepend currency symbol
		if ( apply_filters( 'fiscaat_currency_format_symbol', $symbol ) )
			$retval = fiscaat_get_currency( 'symbol' ) .' '. $retval;

		return apply_filters( 'fiscaat_get_currency_format', $retval, $number, $symbol );
	}

/**
 * A Fiscaat specific method of formatting currency values to float
 * 
 * @param string $value Value to format
 * @uses fiscaat_the_currency_format() To get the currency format
 * @uses fiscaat_get_currency() To get the currency detail
 * @uses apply_filters() Calls 'fiscaat_float_format' with the
 *                        floated value and currency format
 * @return float Floated value
 */
function fiscaat_float_format( $value = '' ) {

	// Get currency format details
	$format = fiscaat_the_currency_format( fiscaat_get_currency() );

	// Remove currency symbol if present
	$value = str_replace( fiscaat_get_currency( 'symbol' ), '', $value );

	// Remove thousands separators
	$value = str_replace( $format['thousands_sep'], '', $value );

	// Change decimal delimiter to dot
	$value = str_replace( $format['dec_point'], '.', $value );

	// Remove whitespace
	$value = trim( $value );

	return (float) apply_filters( 'fiscaat_float_format', $value, $format );
}

/**
 * Convert time supplied from database query into specified date format.
 *
 * @param int|object $time The database time to be converted
 * @param string $d Optional. Default is 'U'. Either 'G', 'U', or php date
 *                             format
 * @param bool $translate Optional. Default is false. Whether to translate the
 *                                   result
 * @uses mysql2date() To convert the format
 * @uses apply_filters() Calls 'fiscaat_convert_date' with the time, date format
 *                        and translate bool
 * @return string Returns timestamp
 */
function fiscaat_convert_date( $time, $d = 'U', $translate = false ) {
	$time = mysql2date( $d, $time, $translate );

	return apply_filters( 'fiscaat_convert_date', $time, $d, $translate );
}

/**
 * Return current time in given format
 * 
 * @param string $type Optional. Defaults to 'mysql' database format
 * @param boolean $gmt Optional. Whether to return GMT
 * @uses current_time() To get the current time
 * @uses apply_filters() Calls 'fiscaat_get_current_time' with the current time
 *                        and the format type
 * @return string Current time
 */
function fiscaat_get_current_time( $type = 'mysql', $gmt = false ){
	return apply_filters( 'fiscaat_get_current_time', current_time( $type, $gmt ), $type, $gmt );
}

/**
 * Output formatted time to display human readable time difference.
 *
 * @param string $older_date Unix timestamp from which the difference begins.
 * @param string $newer_date Optional. Unix timestamp from which the
 *                            difference ends. False for current time.
 * @uses fiscaat_get_time_since() To get the formatted time
 */
function fiscaat_time_since( $older_date, $newer_date = false ) {
	echo fiscaat_get_time_since( $older_date, $newer_date = false );
}
	/**
	 * Return formatted time to display human readable time difference.
	 *
	 * @param string $older_date Unix timestamp from which the difference begins.
	 * @param string $newer_date Optional. Unix timestamp from which the
	 *                            difference ends. False for current time.
	 * @uses current_time() To get the current time in mysql format
	 * @uses human_time_diff() To get the time differene in since format
	 * @uses apply_filters() Calls 'fiscaat_get_time_since' with the time
	 *                        difference and time
	 * @return string Formatted time
	 */
	function fiscaat_get_time_since( $older_date, $newer_date = false ) {
		
		// Setup the strings
		$unknown_text   = apply_filters( 'fiscaat_core_time_since_unknown_text',   __( 'sometime',  'fiscaat' ) );
		$right_now_text = apply_filters( 'fiscaat_core_time_since_right_now_text', __( 'right now', 'fiscaat' ) );
		$ago_text       = apply_filters( 'fiscaat_core_time_since_ago_text',       __( '%s ago',    'fiscaat' ) );

		// array of time period chunks
		$chunks = array(
			array( 60 * 60 * 24 * 365 , __( 'year',   'fiscaat' ), __( 'years',   'fiscaat' ) ),
			array( 60 * 60 * 24 * 30 ,  __( 'month',  'fiscaat' ), __( 'months',  'fiscaat' ) ),
			array( 60 * 60 * 24 * 7,    __( 'week',   'fiscaat' ), __( 'weeks',   'fiscaat' ) ),
			array( 60 * 60 * 24 ,       __( 'day',    'fiscaat' ), __( 'days',    'fiscaat' ) ),
			array( 60 * 60 ,            __( 'hour',   'fiscaat' ), __( 'hours',   'fiscaat' ) ),
			array( 60 ,                 __( 'minute', 'fiscaat' ), __( 'minutes', 'fiscaat' ) ),
			array( 1,                   __( 'second', 'fiscaat' ), __( 'seconds', 'fiscaat' ) )
		);

		if ( !empty( $older_date ) && !is_numeric( $older_date ) ) {
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

		return apply_filters( 'fiscaat_get_time_since', $output, $older_date, $newer_date );
	}

/** Misc **********************************************************************/

/**
 * Append 'view=all' to query string if it's already there from referer
 *
 * @param string $original_link Original Link to be modified
 * @param bool $force Override fiscaat_get_view_all() check
 * @uses current_user_can() To check if the current user can moderate
 * @uses add_query_arg() To add args to the url
 * @uses apply_filters() Calls 'fiscaat_add_view_all' with the link and original link
 * @return string The link with 'view=all' appended if necessary
 */
function fiscaat_add_view_all( $original_link = '', $force = false ) {

	// Are we appending the view=all vars?
	if ( fiscaat_get_view_all() || !empty( $force ) )
		$link = add_query_arg( array( 'view' => 'all' ), $original_link );
	else
		$link = $original_link;

	return apply_filters( 'fiscaat_add_view_all', $link, $original_link );
}

/**
 * Remove 'view=all' from query string
 *
 * @param string $original_link Original Link to be modified
 * @uses current_user_can() To check if the current user can moderate
 * @uses add_query_arg() To add args to the url
 * @uses apply_filters() Calls 'fiscaat_add_view_all' with the link and original link
 * @return string The link with 'view=all' appended if necessary
 */
function fiscaat_remove_view_all( $original_link = '' ) {
	return apply_filters( 'fiscaat_add_view_all', remove_query_arg( 'view', $original_link ), $original_link );
}

/**
 * If current user can and is vewing all records
 *
 * @uses current_user_can() To check if the current user can moderate
 * @uses apply_filters() Calls 'fiscaat_get_view_all' with the link and original link
 * @return bool Whether current user can and is viewing all
 */
function fiscaat_get_view_all( $cap = 'moderate' ) {
	$retval = ( ( !empty( $_GET['view'] ) && ( 'all' == $_GET['view'] ) && current_user_can( $cap ) ) );
	return apply_filters( 'fiscaat_get_view_all', (bool) $retval );
}

/**
 * Assist pagination by returning correct page number
 *
 * @uses get_query_var() To get the 'paged' value
 * @return int Current page number
 */
function fiscaat_get_paged() {
	global $wp_query;

	// Check the query var
	if ( get_query_var( 'paged' ) ) {
		$paged = get_query_var( 'paged' );

	// Check query paged
	} elseif ( !empty( $wp_query->query['paged'] ) ) {
		$paged = $wp_query->query['paged'];
	}

	// Paged found
	if ( !empty( $paged ) )
		return (int) $paged;

	// Default to first page
	return 1;
}

/** Statistics ****************************************************************/

/**
 * Get the Fiscaat statistics
 *
 * @param mixed $args Optional. The function supports these arguments (all
 *                     default to true):
 *  - count_users: Count users? If set to false, Fisci, Controllers and
 *                              Spectators are not counted.
 *  - count_years: Count years?
 *  - count_accounts: Count accounts?
 *  - count_records: Count records? 
 *  - count_current_records: Count records of current year? If set to false,
 *                           diapproved, unapproved, approved and closed records 
 *                           are also not counted.
 *  - count_approved_records: Count approved records of the current year?
 *  - count_unapproved_records: Count unapproved records of the current year?
 *  - count_disapproved_records: Count disapproved records of the current year?
 *  - count_to_balance: Count to balance value of the current year?
 *  - count_current_comments: Count comments of the current year?
 * @uses fiscaat_count_users() To count the number of registered users
 * @uses fiscaat_get_year_post_type() To get the year post type
 * @uses fiscaat_get_account_post_type() To get the account post type
 * @uses fiscaat_get_record_post_type() To get the record post type
 * @uses wp_count_posts() To count the number of years, accounts and records
 * @uses wp_count_terms() To count the number of account tags
 * @uses current_user_can() To check if the user is capable of doing things
 * @uses number_format_i18n() To format the number
 * @uses apply_filters() Calls 'fiscaat_get_statistics' with the statistics and args
 * @return array Fiscaat statistics
 */
function fiscaat_get_statistics( $args = '' ) {

	$defaults = array (
		'count_users'               => true,
		'count_years'               => true,
		'count_accounts'            => true,
		'count_records'             => true,
		'count_current_records'     => true,
		'count_approved_records'    => true,
		'count_unapproved_records'  => true,
		'count_disapproved_records' => true,
		'count_to_balance'          => true,
		'count_comments'            => true,
	);
	$r = fiscaat_parse_args( $args, $defaults, 'get_statistics' );
	extract( $r );

	// Users
	if ( !empty( $count_users ) ) {
		$fiscus_count     = fiscaat_get_total_fisci();
		$controller_count = fiscaat_get_total_controllers();
		$spectator_count  = fiscaat_get_total_spectators();	
	}

	// Years
	if ( !empty( $count_years ) ) {
		$year_count = wp_count_posts( fiscaat_get_year_post_type() );
		$year_count = array_sum( (array) $year_count ) - $year_count->{'auto-draft'};
	}

	// Accounts
	if ( !empty( $count_accounts ) ) {
		$account_count = wp_count_posts( fiscaat_get_account_post_type() );
		$account_count = array_sum( (array) $account_count ) - $account_count->{'auto-draft'};
	}

	// Records
	if ( !empty( $count_records ) ) {
		$record_count = wp_count_posts( fiscaat_get_record_post_type() );
		$record_count = array_sum( (array) $record_count ) - $record_count->{'auto-draft'};
	}

	// Currently in Fiscaat
	if ( !empty( $count_current_records ) ) {

		// wp_count_posts has no filtering so use fiscaat_count_posts
		$current_records = fiscaat_count_posts( array( 'type' => fiscaat_get_record_post_type(), 'year_id' => fiscaat_get_current_year_id() ) );

		// All records published
		$current_record_count = array_sum( (array) $current_records ) - $current_records->{'auto-draft'};

		// Post statuses
		$disapproved = fiscaat_get_disapproved_status_id();
		$approved    = fiscaat_get_approved_status_id();
		$closed      = fiscaat_get_closed_status_id();

		// Approved
		$current_approved_count = $current_records->{$approved} + $current_records->{$closed};

		// Unapproved
		$current_unapproved_count = $current_records->publish + $current_records->{$disapproved};

		// Disapproved
		$current_disapproved_count = $current_records->{$disapproved};
	}

	// To Balance
	if ( !empty( $count_to_balance ) ) {
		$current_to_balance = fiscaat_get_year_to_balance( fiscaat_get_current_year_id() );
	}

	// Comments
	if ( !empty( $count_comments ) ) {

		// @todo fix comment system
		$current_comment_count = (int) 0;
	}

	// Tally the tallies
	$statistics = compact( 'fiscus_count', 'controller_count', 'spectator_count', 'year_count', 'account_count', 'record_count', 'current_record_count', 'current_disapproved_count', 'current_unapproved_count', 'current_approved_count', 'current_comment_count' );
	$statistics = array_map( 'absint',             $statistics );
	$statistics = array_map( 'number_format_i18n', $statistics );

	// Add the to_balance title attribute strings because we don't need to run the math functions on these (see above)
	if ( isset( $current_to_balance ) )
		$statistics['current_to_balance'] = $current_to_balance;

	return apply_filters( 'fiscaat_get_statistics', $statistics, $args );
}

/**
 * Mimic wp_count_posts with the possibility for querying with post_parent
 * 
 * @param mixed $args Optional. Arguments
 * @return object Number of posts for each status
 */
function fiscaat_count_posts( $args = '' ) {
	global $wpdb;

	$defaults = array(
		'type'   => 'post',
		'perm'   => '',
		'parent' => false
		);
	$r = fiscaat_parse_args( $args, $defaults, 'count_posts' );
	extract( $r );

	$user = wp_get_current_user();

	$cache_key = $type;

	$query = "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = %s";
	if ( 'readable' == $perm && is_user_logged_in() ) {
		$post_type_object = get_post_type_object($type);
		if ( !current_user_can( $post_type_object->cap->read_private_posts ) ) {
			$cache_key .= '_' . $perm . '_' . $user->ID;
			$query .= " AND (post_status != 'private' OR ( post_author = '$user->ID' AND post_status = 'private' ))";
		}
	}

	// Added post_parent querying
	if ( !empty( $parent ) ) {
		$query .= " AND post_parent = %s";
	}

	$query .= ' GROUP BY post_status';

	$count = wp_cache_get($cache_key, 'fiscaat_counts');
	if ( false !== $count )
		return $count;

	$count = $wpdb->get_results( $wpdb->prepare( $query, $type, $parent ), ARRAY_A );

	$stats = array();
	foreach ( get_post_stati() as $state )
		$stats[$state] = 0;

	foreach ( (array) $count as $row )
		$stats[$row['post_status']] = $row['num_posts'];

	$stats = (object) $stats;
	wp_cache_set($cache_key, $stats, 'fiscaat_counts');

	return $stats;
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
function fiscaat_parse_args( $args, $defaults = '', $filter_key = '' ) {

	// Setup a temporary array from $args
	if ( is_object( $args ) )
		$r = get_object_vars( $args );
	elseif ( is_array( $args ) )
		$r =& $args;
	else
		wp_parse_str( $args, $r );

	// Passively filter the args before the parse
	if ( !empty( $filter_key ) )
		$r = apply_filters( 'fiscaat_before_' . $filter_key . '_parse_args', $r );

	// Parse
	if ( is_array( $defaults ) )
		$r = array_merge( $defaults, $r );

	// Aggressively filter the args after the parse
	if ( !empty( $filter_key ) )
		$r = apply_filters( 'fiscaat_after_' . $filter_key . '_parse_args', $r );

	// Return the parsed results
	return $r;
}

/**
 * Query the DB and get a count of public children
 *
 * @param int $parent_id Parent id
 * @param string $post_type Post type. Defaults to 'post'
 * @uses fiscaat_get_account_post_type() To get the account post type
 * @uses wp_cache_get() To check if there is a cache of the children count
 * @uses wpdb::prepare() To prepare the query
 * @uses wpdb::get_var() To get the result of the query in a variable
 * @uses wp_cache_set() To set the cache for future use
 * @uses apply_filters() Calls 'fiscaat_get_public_child_count' with the child
 *                        count, parent id and post type
 * @return int The number of children
 */
function fiscaat_get_public_child_count( $parent_id = 0, $post_type = 'post' ) {
	global $wpdb;

	// Bail if nothing passed
	if ( empty( $parent_id ) )
		return false;

	// The ID of the cached query
	$cache_id    = 'fiscaat_parent_' . $parent_id . '_type_' . $post_type . '_child_count';
	$post_status = array( 
		fiscaat_get_public_status_id(),
		fiscaat_get_disapproved_status_id(),
		fiscaat_get_approved_status_id(),
		fiscaat_get_closed_status_id()
	);

	// Join post statuses together
	$post_status = "'" . join( "', '", $post_status ) . "'";

	// Check for cache and set if needed
	$child_count = wp_cache_get( $cache_id, 'fiscaat' );
	if ( empty( $child_count ) ) {
		$child_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_parent = %d AND post_status IN ( {$post_status} ) AND post_type = '%s';", $parent_id, $post_type ) );
		wp_cache_set( $cache_id, $child_count, 'fiscaat' );
	}

	// Filter and return
	return apply_filters( 'fiscaat_get_public_child_count', (int) $child_count, (int) $parent_id, $post_type );
}

/**
 * Query the DB and get a the child id's of public children
 *
 * @param int $parent_id Parent id
 * @param string $post_type Post type. Defaults to 'post'
 * @uses fiscaat_get_account_post_type() To get the account post type
 * @uses wp_cache_get() To check if there is a cache of the children
 * @uses wpdb::prepare() To prepare the query
 * @uses wpdb::get_col() To get the result of the query in an array
 * @uses wp_cache_set() To set the cache for future use
 * @uses apply_filters() Calls 'fiscaat_get_public_child_ids' with the child ids,
 *                        parent id and post type
 * @return array The array of children
 */
function fiscaat_get_public_child_ids( $parent_id = 0, $post_type = 'post' ) {
	global $wpdb;

	// Bail if nothing passed
	if ( empty( $parent_id ) )
		return false;

	// The ID of the cached query
	$cache_id    = 'fiscaat_parent_public_' . $parent_id . '_type_' . $post_type . '_child_ids';
	$post_status = array( 
		fiscaat_get_public_status_id(),
		fiscaat_get_disapproved_status_id(),
		fiscaat_get_approved_status_id(),
		fiscaat_get_closed_status_id()
	);

	// Join post statuses together
	$post_status = "'" . join( "', '", $post_status ) . "'";

	// Check for cache and set if needed
	$child_ids = wp_cache_get( $cache_id, 'fiscaat' );
	if ( empty( $child_ids ) ) {
		$child_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_parent = %d AND post_status IN ( {$post_status} ) AND post_type = '%s' ORDER BY ID DESC;", $parent_id, $post_type ) );
		wp_cache_set( $cache_id, $child_ids, 'fiscaat' );
	}

	// Filter and return
	return apply_filters( 'fiscaat_get_public_child_ids', $child_ids, (int) $parent_id, $post_type );
}

/**
 * Query the DB and get a the child id's of all children
 *
 * @param int $parent_id Parent id
 * @param string $post_type Post type. Defaults to 'post'
 * @uses fiscaat_get_account_post_type() To get the account post type
 * @uses wp_cache_get() To check if there is a cache of the children
 * @uses wpdb::prepare() To prepare the query
 * @uses wpdb::get_col() To get the result of the query in an array
 * @uses wp_cache_set() To set the cache for future use
 * @uses apply_filters() Calls 'fiscaat_get_public_child_ids' with the child ids,
 *                        parent id and post type
 * @return array The array of children
 */
function fiscaat_get_all_child_ids( $parent_id = 0, $post_type = 'post' ) {
	global $wpdb;

	// Bail if nothing passed
	if ( empty( $parent_id ) )
		return false;

	// The ID of the cached query
	$cache_id    = 'fiscaat_parent_all_' . $parent_id . '_type_' . $post_type . '_child_ids';
	$post_status = array( fiscaat_get_public_status_id() );

	// Extra post statuses based on post type
	switch ( $post_type ) {

		// Year
		case fiscaat_get_year_post_type() :
			break;

		// Account
		case fiscaat_get_account_post_type() :
			$post_status[] = fiscaat_get_closed_status_id();
			break;

		// Record
		case fiscaat_get_record_post_type() :
			$post_status[] = fiscaat_get_disapproved_status_id();
			$post_status[] = fiscaat_get_approved_status_id();
			$post_status[] = fiscaat_get_closed_status_id();
			break;
	}

	// Join post statuses together
	$post_status = "'" . join( "', '", $post_status ) . "'";

	// Check for cache and set if needed
	$child_ids = wp_cache_get( $cache_id, 'fiscaat' );
	if ( empty( $child_ids ) ) {
		$child_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_parent = %d AND post_status IN ( {$post_status} ) AND post_type = '%s' ORDER BY ID DESC;", $parent_id, $post_type ) );
		wp_cache_set( $cache_id, $child_ids, 'fiscaat' );
	}

	// Filter and return
	return apply_filters( 'fiscaat_get_all_child_ids', $child_ids, (int) $parent_id, $post_type );
}

/**
 * Add checks for Fiscaat conditions to parse_query action
 *
 * If it's a year edit, WP_Query::fiscaat_is_year_edit is set to true
 * If it's a account edit, WP_Query::fiscaat_is_account_edit is set to true
 * If it's a record edit, WP_Query::fiscaat_is_record_edit is set to true.
 *
 * If it's a view page, WP_Query::fiscaat_is_view is set to true
 *
 * @param WP_Query $posts_query
 *
 * @uses get_query_var() To get {@link WP_Query} query var
 * @uses fiscaat_get_year_post_type() To get the year post type
 * @uses fiscaat_get_account_post_type() To get the account post type
 * @uses fiscaat_get_record_post_type() To get the record post type
 * @uses remove_action() To remove the auto save post revision action
 */
function fiscaat_parse_query( $posts_query ) {

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
	$is_edit  = $posts_query->get( fiscaat_get_edit_rewrite_id() );

	// Year/Account/Record Edit Page
	if ( !empty( $is_edit ) ) {

		// Get the post type from the main query loop
		$post_type = $posts_query->get( 'post_type' );
		
		// Check which post_type we are editing, if any
		if ( !empty( $post_type ) ) {
			switch( $post_type ) {

				// We are editing a year
				case fiscaat_get_year_post_type() :
					$posts_query->fiscaat_is_year_edit = true;
					$posts_query->fiscaat_is_edit      = true;
					break;

				// We are editing a account
				case fiscaat_get_account_post_type() :
					$posts_query->fiscaat_is_account_edit = true;
					$posts_query->fiscaat_is_edit         = true;
					break;

				// We are editing a record
				case fiscaat_get_record_post_type() :
					$posts_query->fiscaat_is_record_edit = true;
					$posts_query->fiscaat_is_edit        = true;
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
 * Used most frequently when editing a year/account/record
 *
 * @global WP_Query $post
 * @param string $field Name of the key
 * @param string $context How to sanitize - raw|edit|db|display|attribute|js
 * @return string Field value
 */
function fiscaat_get_global_post_field( $field = 'ID', $context = 'edit' ) {
	global $post;

	$retval = isset( $post->$field ) ? $post->$field : '';
	$retval = sanitize_post_field( $field, $retval, $post->ID, $context );

	return apply_filters( 'fiscaat_get_global_post_field', $retval, $post );
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
function fiscaat_get_page_by_path( $path = '' ) {

	// Default to false
	$retval = false;

	// Path is not empty
	if ( !empty( $path ) ) {

		// Pretty permalinks are on so path might exist
		if ( get_option( 'permalink_structure' ) ) {
			$retval = get_page_by_path( $path );
		}
	}

	return apply_filters( 'fiscaat_get_page_by_path', $retval, $path );
}

/**
 * Sets the 404 status.
 *
 * Used primarily with years/accounts/records on the front.
 *
 * @global WP_Query $wp_query
 * @uses WP_Query::set_404()
 */
function fiscaat_set_404() {
	global $wp_query;

	if ( ! isset( $wp_query ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Conditional query tags do not work before the query is run. Before then, they always return false.', 'fiscaat' ), '3.1' );
		return false;
	}

	$wp_query->set_404();
}

/** Currencies ****************************************************************/

/**
 * Returns list of currencies
 * 
 * @see https://github.com/piwik/piwik/blob/master/core/DataFiles/Currencies.php
 *
 * @uses apply_filters() Calls 'fiscaat_get_currencies' with the currencies
 */
function fiscaat_get_currencies(){

	$currencies = array( 
		// 'ISO-4217 CODE' => array( 'symbol' => 'currency symbol', 'desc' => 'description'),

		// Top 5 by global trading volume
		'USD' => array( 'symbol' => '$', 'desc' => 'US dollar'),
		'EUR' => array( 'symbol' => '€', 'desc' => 'Euro'),
		'JPY' => array( 'symbol' => '¥', 'desc' => 'Japanese yen'),
		'GBP' => array( 'symbol' => '£', 'desc' => 'British pound'),
		'CHF' => array( 'symbol' => 'Fr', 'desc' => 'Swiss franc'),

		'AFN' => array( 'symbol' => '؋', 'desc' => 'Afghan afghani'),
		'ALL' => array( 'symbol' => 'L', 'desc' => 'Albanian lek'),
		'DZD' => array( 'symbol' => 'د.ج', 'desc' => 'Algerian dinar'),
		'AOA' => array( 'symbol' => 'Kz', 'desc' => 'Angolan kwanza'),
		'ARS' => array( 'symbol' => '$', 'desc' => 'Argentine peso'),
		'AMD' => array( 'symbol' => 'դր.', 'desc' => 'Armenian dram'),
		'AWG' => array( 'symbol' => 'ƒ', 'desc' => 'Aruban florin'),
		'AUD' => array( 'symbol' => '$', 'desc' => 'Australian dollar'),
		'AZN' => array( 'symbol' => 'm', 'desc' => 'Azerbaijani manat'),
		'BSD' => array( 'symbol' => '$', 'desc' => 'Bahamian dollar'),
		'BHD' => array( 'symbol' => '.د.ب', 'desc' => 'Bahraini dinar'),
		'BDT' => array( 'symbol' => '৳', 'desc' => 'Bangladeshi taka'),
		'BBD' => array( 'symbol' => '$', 'desc' => 'Barbadian dollar'),
		'BYR' => array( 'symbol' => 'Br', 'desc' => 'Belarusian ruble'),
		'BZD' => array( 'symbol' => '$', 'desc' => 'Belize dollar'),
		'BMD' => array( 'symbol' => '$', 'desc' => 'Bermudian dollar'),
		'BTN' => array( 'symbol' => 'Nu.', 'desc' => 'Bhutanese ngultrum'),
		'BOB' => array( 'symbol' => 'Bs.', 'desc' => 'Bolivian boliviano'),
		'BAM' => array( 'symbol' => 'KM', 'desc' => 'Bosnia Herzegovina mark'),
		'BWP' => array( 'symbol' => 'P', 'desc' => 'Botswana pula'),
		'BRL' => array( 'symbol' => 'R$', 'desc' => 'Brazilian real'),
	//	'GBP' => array( 'symbol' => '£', 'desc' => 'British pound'),
		'BND' => array( 'symbol' => '$', 'desc' => 'Brunei dollar'),
		'BGN' => array( 'symbol' => 'лв', 'desc' => 'Bulgarian lev'),
		'BIF' => array( 'symbol' => 'Fr', 'desc' => 'Burundian franc'),
		'KHR' => array( 'symbol' => '៛', 'desc' => 'Cambodian riel'),
		'CAD' => array( 'symbol' => '$', 'desc' => 'Canadian dollar'),
		'CVE' => array( 'symbol' => '$', 'desc' => 'Cape Verdean escudo'),
		'KYD' => array( 'symbol' => '$', 'desc' => 'Cayman Islands dollar'),
		'XAF' => array( 'symbol' => 'Fr', 'desc' => 'Central African CFA franc'),
		'CLP' => array( 'symbol' => '$', 'desc' => 'Chilean peso'),
		'CNY' => array( 'symbol' => '元', 'desc' => 'Chinese yuan'),
		'COP' => array( 'symbol' => '$', 'desc' => 'Colombian peso'),
		'KMF' => array( 'symbol' => 'Fr', 'desc' => 'Comorian franc'),
		'CDF' => array( 'symbol' => 'Fr', 'desc' => 'Congolese franc'),
		'CRC' => array( 'symbol' => '₡', 'desc' => 'Costa Rican colón'),
		'HRK' => array( 'symbol' => 'kn', 'desc' => 'Croatian kuna'),
		'XPF' => array( 'symbol' => 'F', 'desc' => 'CFP franc'),
		'CUC' => array( 'symbol' => '$', 'desc' => 'Cuban convertible peso'),
		'CUP' => array( 'symbol' => '$', 'desc' => 'Cuban peso'),
		'CMG' => array( 'symbol' => 'ƒ', 'desc' => 'Curaçao and Sint Maarten guilder'),
		'CZK' => array( 'symbol' => 'Kč', 'desc' => 'Czech koruna'),
		'DKK' => array( 'symbol' => 'kr', 'desc' => 'Danish krone'),
		'DJF' => array( 'symbol' => 'Fr', 'desc' => 'Djiboutian franc'),
		'DOP' => array( 'symbol' => '$', 'desc' => 'Dominican peso'),
		'XCD' => array( 'symbol' => '$', 'desc' => 'East Caribbean dollar'),
		'EGP' => array( 'symbol' => 'ج.م', 'desc' => 'Egyptian pound'),
		'ERN' => array( 'symbol' => 'Nfk', 'desc' => 'Eritrean nakfa'),
		'EEK' => array( 'symbol' => 'kr', 'desc' => 'Estonian kroon'),
		'ETB' => array( 'symbol' => 'Br', 'desc' => 'Ethiopian birr'),
	//	'EUR' => array( 'symbol' => '€', 'desc' => 'Euro'),
		'FKP' => array( 'symbol' => '£', 'desc' => 'Falkland Islands pound'),
		'FJD' => array( 'symbol' => '$', 'desc' => 'Fijian dollar'),
		'GMD' => array( 'symbol' => 'D', 'desc' => 'Gambian dalasi'),
		'GEL' => array( 'symbol' => 'ლ', 'desc' => 'Georgian lari'),
		'GHS' => array( 'symbol' => '₵', 'desc' => 'Ghanaian cedi'),
		'GIP' => array( 'symbol' => '£', 'desc' => 'Gibraltar pound'),
		'GTQ' => array( 'symbol' => 'Q', 'desc' => 'Guatemalan quetzal'),
		'GNF' => array( 'symbol' => 'Fr', 'desc' => 'Guinean franc'),
		'GYD' => array( 'symbol' => '$', 'desc' => 'Guyanese dollar'),
		'HTG' => array( 'symbol' => 'G', 'desc' => 'Haitian gourde'),
		'HNL' => array( 'symbol' => 'L', 'desc' => 'Honduran lempira'),
		'HKD' => array( 'symbol' => '$', 'desc' => 'Hong Kong dollar'),
		'HUF' => array( 'symbol' => 'Ft', 'desc' => 'Hungarian forint'),
		'ISK' => array( 'symbol' => 'kr', 'desc' => 'Icelandic króna'),
		'INR' => array( 'symbol' => '‎₹', 'desc' => 'Indian rupee'),
		'IDR' => array( 'symbol' => 'Rp', 'desc' => 'Indonesian rupiah'),
		'IRR' => array( 'symbol' => '﷼', 'desc' => 'Iranian rial'),
		'IQD' => array( 'symbol' => 'ع.د', 'desc' => 'Iraqi dinar'),
		'ILS' => array( 'symbol' => '₪', 'desc' => 'Israeli new shekel'),
		'JMD' => array( 'symbol' => '$', 'desc' => 'Jamaican dollar'),
	//	'JPY' => array( 'symbol' => '¥', 'desc' => 'Japanese yen'),
		'JOD' => array( 'symbol' => 'د.ا', 'desc' => 'Jordanian dinar'),
		'KZT' => array( 'symbol' => '₸', 'desc' => 'Kazakhstani tenge'),
		'KES' => array( 'symbol' => 'Sh', 'desc' => 'Kenyan shilling'),
		'KWD' => array( 'symbol' => 'د.ك', 'desc' => 'Kuwaiti dinar'),
		'KGS' => array( 'symbol' => 'лв', 'desc' => 'Kyrgyzstani som'),
		'LAK' => array( 'symbol' => '₭', 'desc' => 'Lao kip'),
		'LVL' => array( 'symbol' => 'Ls', 'desc' => 'Latvian lats'),
		'LBP' => array( 'symbol' => 'ل.ل', 'desc' => 'Lebanese pound'),
		'LSL' => array( 'symbol' => 'L', 'desc' => 'Lesotho loti'),
		'LRD' => array( 'symbol' => '$', 'desc' => 'Liberian dollar'),
		'LYD' => array( 'symbol' => 'ل.د', 'desc' => 'Libyan dinar'),
		'LTL' => array( 'symbol' => 'Lt', 'desc' => 'Lithuanian litas'),
		'MOP' => array( 'symbol' => 'P', 'desc' => 'Macanese pataca'),
		'MKD' => array( 'symbol' => 'ден', 'desc' => 'Macedonian denar'),
		'MGA' => array( 'symbol' => 'Ar', 'desc' => 'Malagasy ariary'),
		'MWK' => array( 'symbol' => 'MK', 'desc' => 'Malawian kwacha'),
		'MYR' => array( 'symbol' => 'RM', 'desc' => 'Malaysian ringgit'),
		'MVR' => array( 'symbol' => 'ރ.', 'desc' => 'Maldivian rufiyaa'),
		'MRO' => array( 'symbol' => 'UM', 'desc' => 'Mauritanian ouguiya'),
		'MUR' => array( 'symbol' => '₨', 'desc' => 'Mauritian rupee'),
		'MXN' => array( 'symbol' => '$', 'desc' => 'Mexican peso'),
		'MDL' => array( 'symbol' => 'L', 'desc' => 'Moldovan leu'),
		'MNT' => array( 'symbol' => '₮', 'desc' => 'Mongolian tögrög'),
		'MAD' => array( 'symbol' => 'د.م.', 'desc' => 'Moroccan dirham'),
		'MZN' => array( 'symbol' => 'MTn', 'desc' => 'Mozambican metical'),
		'MMK' => array( 'symbol' => 'K', 'desc' => 'Myanma kyat'),
		'NAD' => array( 'symbol' => '$', 'desc' => 'Namibian dollar'),
		'NPR' => array( 'symbol' => '₨', 'desc' => 'Nepalese rupee'),
		'ANG' => array( 'symbol' => 'ƒ', 'desc' => 'Netherlands Antillean guilder'),
		'TWD' => array( 'symbol' => '$', 'desc' => 'New Taiwan dollar'),
		'NZD' => array( 'symbol' => '$', 'desc' => 'New Zealand dollar'),
		'NIO' => array( 'symbol' => 'C$', 'desc' => 'Nicaraguan córdoba'),
		'NGN' => array( 'symbol' => '₦', 'desc' => 'Nigerian naira'),
		'KPW' => array( 'symbol' => '₩', 'desc' => 'North Korean won'),
		'NOK' => array( 'symbol' => 'kr', 'desc' => 'Norwegian krone'),
		'OMR' => array( 'symbol' => 'ر.ع.', 'desc' => 'Omani rial'),
		'PKR' => array( 'symbol' => '₨', 'desc' => 'Pakistani rupee'),
		'PAB' => array( 'symbol' => 'B/.', 'desc' => 'Panamanian balboa'),
		'PGK' => array( 'symbol' => 'K', 'desc' => 'Papua New Guinean kina'),
		'PYG' => array( 'symbol' => '₲', 'desc' => 'Paraguayan guaraní'),
		'PEN' => array( 'symbol' => 'S/.', 'desc' => 'Peruvian nuevo sol'),
		'PHP' => array( 'symbol' => '₱', 'desc' => 'Philippine peso'),
		'PLN' => array( 'symbol' => 'zł', 'desc' => 'Polish złoty'),
		'QAR' => array( 'symbol' => 'ر.ق', 'desc' => 'Qatari riyal'),
		'RON' => array( 'symbol' => 'L', 'desc' => 'Romanian leu'),
		'RUB' => array( 'symbol' => 'руб.', 'desc' => 'Russian ruble'),
		'RWF' => array( 'symbol' => 'Fr', 'desc' => 'Rwandan franc'),
		'SHP' => array( 'symbol' => '£', 'desc' => 'Saint Helena pound'),
		'SVC' => array( 'symbol' => '₡', 'desc' => 'Salvadoran colón'),
		'WST' => array( 'symbol' => 'T', 'desc' => 'Samoan tala'),
		'STD' => array( 'symbol' => 'Db', 'desc' => 'São Tomé and Príncipe dobra'),
		'SAR' => array( 'symbol' => 'ر.س', 'desc' => 'Saudi riyal'),
		'RSD' => array( 'symbol' => 'дин. or din.', 'desc' => 'Serbian dinar'),
		'SCR' => array( 'symbol' => '₨', 'desc' => 'Seychellois rupee'),
		'SLL' => array( 'symbol' => 'Le', 'desc' => 'Sierra Leonean leone'),
		'SGD' => array( 'symbol' => '$', 'desc' => 'Singapore dollar'),
		'SBD' => array( 'symbol' => '$', 'desc' => 'Solomon Islands dollar'),
		'SOS' => array( 'symbol' => 'Sh', 'desc' => 'Somali shilling'),
		'ZAR' => array( 'symbol' => 'R', 'desc' => 'South African rand'),
		'KRW' => array( 'symbol' => '₩', 'desc' => 'South Korean won'),
		'LKR' => array( 'symbol' => 'Rs', 'desc' => 'Sri Lankan rupee'),
		'SDG' => array( 'symbol' => 'جنيه سوداني', 'desc' => 'Sudanese pound'),
		'SRD' => array( 'symbol' => '$', 'desc' => 'Surinamese dollar'),
		'SZL' => array( 'symbol' => 'L', 'desc' => 'Swazi lilangeni'),
		'SEK' => array( 'symbol' => 'kr', 'desc' => 'Swedish krona'),
	//	'CHF' => array( 'symbol' => 'Fr', 'desc' => 'Swiss franc'),
		'SYP' => array( 'symbol' => 'ل.س', 'desc' => 'Syrian pound'),
		'TJS' => array( 'symbol' => 'ЅМ', 'desc' => 'Tajikistani somoni'),
		'TZS' => array( 'symbol' => 'Sh', 'desc' => 'Tanzanian shilling'),
		'THB' => array( 'symbol' => '฿', 'desc' => 'Thai baht'),
		'TOP' => array( 'symbol' => 'T$', 'desc' => 'Tongan paʻanga'),
		'TTD' => array( 'symbol' => '$', 'desc' => 'Trinidad and Tobago dollar'),
		'TND' => array( 'symbol' => 'د.ت', 'desc' => 'Tunisian dinar'),
		'TRY' => array( 'symbol' => 'TL', 'desc' => 'Turkish lira'),
		'TMM' => array( 'symbol' => 'm', 'desc' => 'Turkmenistani manat'),
		'UGX' => array( 'symbol' => 'Sh', 'desc' => 'Ugandan shilling'),
		'UAH' => array( 'symbol' => '₴', 'desc' => 'Ukrainian hryvnia'),
		'AED' => array( 'symbol' => 'د.إ', 'desc' => 'United Arab Emirates dirham'),
	//	'USD' => array( 'symbol' => '$', 'desc' => 'United States dollar'),
		'UYU' => array( 'symbol' => '$', 'desc' => 'Uruguayan peso'),
		'UZS' => array( 'symbol' => 'лв', 'desc' => 'Uzbekistani som'),
		'VUV' => array( 'symbol' => 'Vt', 'desc' => 'Vanuatu vatu'),
		'VEF' => array( 'symbol' => 'Bs F', 'desc' => 'Venezuelan bolívar'),
		'VND' => array( 'symbol' => '₫', 'desc' => 'Vietnamese đồng'),
		'XOF' => array( 'symbol' => 'Fr', 'desc' => 'West African CFA franc'),
		'YER' => array( 'symbol' => '﷼', 'desc' => 'Yemeni rial'),
		'ZMK' => array( 'symbol' => 'ZK', 'desc' => 'Zambian kwacha'),
		'ZWL' => array( 'symbol' => '$', 'desc' => 'Zimbabwean dollar'),
	);

	return apply_filters( 'fiscaat_get_currencies', $currencies );
}

/**
 * Returns currency format for given currency
 * 
 * @see http://www.joelpeterson.com/blog/2011/03/formatting-over-100-currencies-in-php/
 * 
 * @param string $currency The currency iso code. Defaults to USD
 * @uses apply_filters() Calls 'fiscaat_the_currency_format' with the
 *                        currency format, and currency
 * @return array Currency format
 */
function fiscaat_the_currency_format( $currency = '' ){

	$formats = array(
		'ARS' => array( 'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.' ),
		'AMD' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'AWG' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'AUD' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ' ' ),
		'BSD' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'BHD' => array( 'decimals' => 3, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'BDT' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'BZD' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'BMD' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'BOB' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'BAM' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'BWP' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'BRL' => array( 'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.' ),
		'BND' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'CAD' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'KYD' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'CLP' => array( 'decimals' => 0, 'dec_point' => '',  'thousands_sep' => '.' ),
		'CNY' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'COP' => array( 'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.' ),
		'CRC' => array( 'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.' ),
		'HRK' => array( 'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.' ),
		'CUC' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'CUP' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'CYP' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'CZK' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'DKK' => array( 'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.' ),
		'DOP' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'XCD' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'EGP' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'SVC' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'ATS' => array( 'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.' ),
		'BEF' => array( 'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.' ),
		'DEM' => array( 'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.' ),
		'EEK' => array( 'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.' ),
		'ESP' => array( 'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.' ),
		'EUR' => array( 'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.' ),
		'FIM' => array( 'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.' ),
		'FRF' => array( 'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.' ),
		'GRD' => array( 'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.' ),
		'IEP' => array( 'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.' ),
		'ITL' => array( 'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.' ),
		'LUF' => array( 'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.' ),
		'NLG' => array( 'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.' ),
		'PTE' => array( 'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.' ),
		'GHC' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'GIP' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'GTQ' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'HNL' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'HKD' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'HUF' => array( 'decimals' => 0, 'dec_point' => '',  'thousands_sep' => '.' ),
		'ISK' => array( 'decimals' => 0, 'dec_point' => '',  'thousands_sep' => '.' ),
		'INR' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'IDR' => array( 'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.' ),
		'IRR' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'JMD' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'JPY' => array( 'decimals' => 0, 'dec_point' => '',  'thousands_sep' => ',' ),
		'JOD' => array( 'decimals' => 3, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'KES' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'KWD' => array( 'decimals' => 3, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'LVL' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'LBP' => array( 'decimals' => 0, 'dec_point' => '',  'thousands_sep' => ' ' ),
		'LTL' => array( 'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => ' ' ),
		'MKD' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'MYR' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'MTL' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'MUR' => array( 'decimals' => 0, 'dec_point' => '',  'thousands_sep' => ',' ),
		'MXN' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'MZM' => array( 'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.' ),
		'NPR' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'ANG' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'ILS' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'TRY' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'NZD' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'NOK' => array( 'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.' ),
		'PKR' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'PEN' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'UYU' => array( 'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.' ),
		'PHP' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'PLN' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ' ' ),
		'GBP' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'OMR' => array( 'decimals' => 3, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'RON' => array( 'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.' ),
		'ROL' => array( 'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.' ),
		'RUB' => array( 'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.' ),
		'SAR' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'SGD' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'SKK' => array( 'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => ' ' ),
		'SIT' => array( 'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.' ),
		'ZAR' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ' ' ),
		'KRW' => array( 'decimals' => 0, 'dec_point' => '',  'thousands_sep' => ',' ),
		'SZL' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ', ' ),
		'SEK' => array( 'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.' ),
		'CHF' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => '\'' ),
		'TZS' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'THB' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'TOP' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'AED' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'UAH' => array( 'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => ' ' ),
		'USD' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',' ),
		'VUV' => array( 'decimals' => 0, 'dec_point' => '',  'thousands_sep' => ',' ),
		'VEF' => array( 'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.' ),
		'VEB' => array( 'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.' ),
		'VND' => array( 'decimals' => 0, 'dec_point' => '',  'thousands_sep' => '.' ),
		'ZWD' => array( 'decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ' ' )
	);

	if ( ! isset( $formats[$currency] ) )
		$currency = 'USD';

	return apply_filters( 'fiscaat_the_currency_formats', $formats[$currency], $currency );
}

/**
 * A Fiscaat specific method for formatting values for INR currency
 *
 * @see http://www.joelpeterson.com/blog/2011/03/formatting-over-100-currencies-in-php/
 * 
 * @param float $number Number to format
 * @uses fiscaat_the_currency_format() To get the INR currency format
 * @uses apply_filters() Calls 'fiscaat_the_currency_format_INR' with the
 *                        formatted number, and initial number
 * @return string Formatted number
 */
function fiscaat_the_currency_format_INR( $number = 0 ) {
	$format = fiscaat_the_currency_format( 'INR' );
	$dec    = '';

	// Has value decimals
	if ( $pos = strpos( $number, '.' ) ) {
		$dec    = substr( round( substr( $number, $pos ), $format['decimals'] ), 1 );
		$number = substr( $number, 0, $pos );
	}

	// Setup number parts
	$retval = substr( $number, -3 );
	$number = substr( $number, 0, -3 );

	// Add seperators
	while ( strlen( $number ) > 0 ) {
		$retval = substr( $number, -2 ) . $format['thousands_sep'] . $retval;
		$number = substr( $number, 0, -2 );
	}

	return apply_filters( 'fiscaat_the_currency_format_INR', $retval . $dec, $number );
}

/**
 * Sanitize currency input to be an existing currency
 * 
 * @param string $input Currency input
 * @uses fiscaat_get_currencies() To get available currencies
 * @return string Sanitized currency
 */
function fiscaat_sanitize_currency( $input = '' ) {
	return in_array( $input, array_keys( fiscaat_get_currencies() ) ) ? $input : 'USD';
}
