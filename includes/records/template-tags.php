<?php

/**
 * Fiscaat Record Template Tags
 *
 * @package Fiscaat
 * @subpackage TemplateTags
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** Post Type *****************************************************************/

/**
 * Output the unique id of the custom post type for records
 *
 * @uses fct_get_record_post_type() To get the record post type
 */
function fct_record_post_type() {
	echo fct_get_record_post_type();
}
	/**
	 * Return the unique id of the custom post type for records
	 *
	 * @uses apply_filters() Calls 'fct_get_year_post_type' with the record
	 *                        post type id
	 * @return string The unique record post type id
	 */
	function fct_get_record_post_type() {
		return apply_filters( 'fct_get_record_post_type', fiscaat()->record_post_type );
	}

/** Value Types ***************************************************************/

/**
 * Output the debit record value type id
 *
 * @uses fct_get_debit_record_type() To get the debti value type id
 */
function fct_debit_record_type() {
	echo fct_get_debit_record_type();
}
	/**
	 * Return the debit record value type id
	 *
	 * @uses apply_filters() Calls 'fct_get_debit_record_type' with the
	 *                        debit record value type id
	 * @return string The debit record value type
	 */
	function fct_get_debit_record_type() {
		return apply_filters( 'fct_get_debit_record_type', fiscaat()->debit_type_id );
	}

/**
 * Output the credit record value type id
 *
 * @uses fct_get_credit_record_type() To get the debti value type id
 */
function fct_credit_record_type() {
	echo fct_get_credit_record_type();
}
	/**
	 * Return the credit record value type id
	 *
	 * @uses apply_filters() Calls 'fct_get_credit_record_type' with the
	 *                        credit record value type id
	 * @return string The credit record value type
	 */
	function fct_get_credit_record_type() {
		return apply_filters( 'fct_get_credit_record_type', fiscaat()->credit_type_id );
	}

/** Record Loop Functions ******************************************************/

/**
 * The main record loop. WordPress makes this easy for us
 *
 * @param mixed $args All the arguments supported by {@link WP_Query}
 * @uses fct_show_lead_account() Are we showing the account as a lead?
 * @uses fct_get_account_id() To get the account id
 * @uses fct_get_record_post_type() To get the record post type
 * @uses fct_get_account_post_type() To get the account post type
 * @uses fct_is_query_name() To check if we are getting records for a widget
 * @uses get_option() To get the records per page option
 * @uses fct_get_paged() To get the current page value
 * @uses current_user_can() To check if the current user is capable of editing
 *                           others' records
 * @uses WP_Query To make query and get the records
 * @uses WP_Rewrite::using_permalinks() To check if the blog is using permalinks
 * @uses get_permalink() To get the permalink
 * @uses add_query_arg() To add custom args to the url
 * @uses apply_filters() Calls 'fct_records_pagination' with the pagination args
 * @uses paginate_links() To paginate the links
 * @uses apply_filters() Calls 'fct_has_records' with
 *                        bbPres::record_query::have_posts()
 *                        and bbPres::record_query
 * @return object Multidimensional array of record information
 */
function fct_has_records( $args = '' ) {
	global $wp_rewrite;

	// What are the default allowed statuses (based on user caps)
	$post_statuses = array( fct_get_public_status_id(), fct_get_declined_status_id(), fct_get_approved_status_id(), fct_get_closed_status_id() );

	$default_record_search = ! empty( $_REQUEST['rs'] ) ? $_REQUEST['rs'] : false;
	$default_post_parent   = ( fct_is_single_account() ) ? fct_get_account_id() : 'any';
	$default_post_type     = ( fct_is_single_account() && fct_show_lead_account() ) ? fct_get_record_post_type() : array( fct_get_account_post_type(), fct_get_record_post_type() );
	$default_post_status   = join( ',', $post_statuses );

	// Default query args
	$default = array(
		'post_type'      => $default_post_type,             // Only records
		'post_parent'    => $default_post_parent,           // Of this account
		'post_status'    => $default_post_status,           // Of this status
		'posts_per_page' => fct_get_records_per_page(), // This many
		'paged'          => fct_get_paged(),            // On this page
		'orderby'        => 'date',                         // Sorted by date
		'order'          => 'ASC',                          // Oldest to newest
		's'              => $default_record_search,         // Maybe search
	);

	// Set up account variables
	$fct_r = fct_parse_args( $args, $default, 'has_records' );

	// Extract the query variables
	extract( $fct_r );

	// Get Fiscaat
	$fiscaat = fiscaat();

	// Call the query
	$fiscaat->record_query = new WP_Query( $fct_r );
	
	// Add pagination values to query object
	$fiscaat->record_query->posts_per_page = $posts_per_page;
	$fiscaat->record_query->paged          = $paged;

	// Never home, regardless of what parse_query says
	$fiscaat->record_query->is_home        = false;

	// Reset is_single if single account
	if ( fct_is_single_account() ) {
		$fiscaat->record_query->is_single = true;
	}

	// Only add pagination if query returned results
	if ( (int) $fiscaat->record_query->found_posts && (int) $fiscaat->record_query->posts_per_page ) {

		// If pretty permalinks are enabled, make our pagination pretty
		if ( $wp_rewrite->using_permalinks() ) {

			// Page or single
			if ( is_page() || is_single() ) {
				$base = get_permalink();

			// User's records
			} elseif ( fct_is_single_user_records() ) {
				$base = fct_get_user_records_created_url( fct_get_displayed_user_id() );

			// Single account
			} else {
				$base = get_permalink( fct_get_account_id() );
			}

			$base = trailingslashit( $base ) . user_trailingslashit( $wp_rewrite->pagination_base . '/%#%/' );

		// Unpretty permalinks
		} else {
			$base = add_query_arg( 'paged', '%#%' );
		}

		// Add pagination to query object
		$fiscaat->record_query->pagination_links = paginate_links(
			apply_filters( 'fct_records_pagination', array(
				'base'      => $base,
				'format'    => '',
				'total'     => ceil( (int) $fiscaat->record_query->found_posts / (int) $posts_per_page ),
				'current'   => (int) $fiscaat->record_query->paged,
				'prev_text' => '&larr;',
				'next_text' => '&rarr;',
				'mid_size'  => 1,
				'add_args'  => ( fct_get_view_all() ) ? array( 'view' => 'all' ) : false
			) )
		);

		// Remove first page from pagination
		if ( $wp_rewrite->using_permalinks() ) {
			$fiscaat->record_query->pagination_links = str_replace( $wp_rewrite->pagination_base . '/1/', '', $fiscaat->record_query->pagination_links );
		} else {
			$fiscaat->record_query->pagination_links = str_replace( '&#038;paged=1', '', $fiscaat->record_query->pagination_links );
		}
	}

	// Return object
	return apply_filters( 'fct_has_records', $fiscaat->record_query->have_posts(), $fiscaat->record_query );
}

/**
 * Whether there are more records available in the loop
 *
 * @uses WP_Query Fiscaat::record_query::have_posts() To check if there are more
 *                                                    records available
 * @return object Records information
 */
function fct_records() {

	// Put into variable to check against next
	$have_posts = fiscaat()->record_query->have_posts();

	// Reset the post data when finished
	if ( empty( $have_posts ) )
		wp_reset_postdata();

	return $have_posts;
}

/**
 * Loads up the current record in the loop
 *
 * @uses WP_Query Fiscaat::record_query::the_post() To get the current record
 * @return object Record information
 */
function fct_the_record() {
	return fiscaat()->record_query->the_post();
}

/**
 * Output record id
 *
 * @param $record_id Optional. Used to check emptiness
 * @uses fct_get_record_id() To get the record id
 */
function fct_record_id( $record_id = 0 ) {
	echo fct_get_record_id( $record_id );
}
	/**
	 * Return the id of the record in a records loop
	 *
	 * @since Fiscaat (r2553)
	 *
	 * @param $record_id Optional. Used to check emptiness
	 * @uses Fiscaat::record_query::post::ID To get the record id
	 * @uses fct_is_record() To check if it's a record page
	 * @uses fct_is_record_edit() To check if it's a record edit page
	 * @uses get_post_field() To get the post's post type
	 * @uses WP_Query::post::ID To get the record id
	 * @uses fct_get_record_post_type() To get the record post type
	 * @uses apply_filters() Calls 'fct_get_record_id' with the record id and
	 *                        supplied record id
	 * @return int The record id
	 */
	function fct_get_record_id( $record_id = 0 ) {
		global $wp_query;

		$fiscaat = fiscaat();

		// Easy empty checking
		if ( ! empty( $record_id ) && is_numeric( $record_id ) ) {
			$fct_record_id = $record_id;

		// Currently inside a records loop
		} elseif ( ! empty( $fiscaat->record_query->in_the_loop ) && isset( $fiscaat->record_query->post->ID ) ) {
			$fct_record_id = $fiscaat->record_query->post->ID;

		// Currently viewing a year
		} elseif ( ( fct_is_single_record() || fct_is_record_edit() ) && ! empty( $fiscaat->current_record_id ) ) {
			$fct_record_id = $fiscaat->current_record_id;

		// Currently viewing a record
		} elseif ( ( fct_is_single_record() || fct_is_record_edit() ) && isset( $wp_query->post->ID ) ) {
			$fct_record_id = $wp_query->post->ID;

		// Fallback
		} else {
			$fct_record_id = 0;
		}

		return (int) apply_filters( 'fct_get_record_id', $fct_record_id, $record_id );
	}

/**
 * Gets a record
 *
 * @param int|object $record record id or record object
 * @param string $output Optional. OBJECT, ARRAY_A, or ARRAY_N. Default = OBJECT
 * @param string $filter Optional Sanitation filter. See {@link sanitize_post()}
 * @uses get_post() To get the record
 * @uses fct_get_record_post_type() To get the record post type
 * @uses apply_filters() Calls 'fct_get_record' with the record, output type and
 *                        sanitation filter
 * @return mixed Null if error or record (in specified form) if success
 */
function fct_get_record( $record, $output = OBJECT, $filter = 'raw' ) {
	if ( empty( $record ) || is_numeric( $record ) )
		$record = fct_get_record_id( $record );

	$record = get_post( $record, OBJECT, $filter );
	if ( empty( $record ) )
		return $record;

	if ( $record->post_type !== fct_get_record_post_type() )
		return null;

	if ( $output == OBJECT ) {
		return $record;

	} elseif ( $output == ARRAY_A ) {
		$_record = get_object_vars( $record );
		return $_record;

	} elseif ( $output == ARRAY_N ) {
		$_record = array_values( get_object_vars( $record ) );
		return $_record;

	}

	return apply_filters( 'fct_get_record', $record, $output, $filter );
}

/**
 * Output the link to the record in the record loop
 *
 * @param int $record_id Optional. Record id
 * @uses fct_get_record_permalink() To get the record permalink
 */
function fct_record_permalink( $record_id = 0 ) {
	echo fct_get_record_permalink( $record_id );
}
	/**
	 * Return the link to the record
	 *
	 * @param int $record_id Optional. Record id
	 * @uses fct_get_record_id() To get the record id
	 * @uses get_permalink() To get the permalink of the record
	 * @uses apply_filters() Calls 'fct_get_record_permalink' with the link
	 *                        and record id
	 * @return string Permanent link to record
	 */
	function fct_get_record_permalink( $record_id = 0 ) {
		$record_id = fct_get_record_id( $record_id );

		return apply_filters( 'fct_get_record_permalink', get_permalink( $record_id ), $record_id );
	}
/**
 * Output the paginated url to the record in the record loop
 *
 * @param int $record_id Optional. Record id
 * @uses fct_get_record_url() To get the record url
 */
function fct_record_url( $record_id = 0 ) {
	echo fct_get_record_url( $record_id );
}
	/**
	 * Return the paginated url to the record in the record loop
	 *
	 * @param int $record_id Optional. Record id
	 * @param $string $redirect_to Optional. Pass a redirect value for use with
	 *                              shortcodes and other fun things.
	 * @uses fct_get_record_id() To get the record id
	 * @uses fct_get_record_account_id() To get the record account id
	 * @uses fct_get_account_permalink() To get the account permalink
	 * @uses fct_get_record_position() To get the record position
	 * @uses get_option() To get the records per page option
	 * @uses WP_Rewrite::using_permalinks() To check if the blog uses
	 *                                       permalinks
	 * @uses add_query_arg() To add custom args to the url
	 * @uses apply_filters() Calls 'fct_get_record_url' with the record url,
	 *                        record id and bool count hidden
	 * @return string Link to record relative to paginated account
	 */
	function fct_get_record_url( $record_id = 0, $redirect_to = '' ) {
		// @todo Fix record position

		// Set needed variables
		$record_id    = fct_get_record_id      ( $record_id );
		$account_id   = fct_get_record_account_id( $record_id );
		$record_page  = ceil( (int) fct_get_record_position( $record_id, $account_id ) / (int) fct_get_records_per_page() );
		$record_hash  = '#post-' . $record_id;
		$account_link = fct_get_account_permalink( $account_id, $redirect_to );
		$account_url  = remove_query_arg( 'view', $account_link );

		// Don't include pagination if on first page
		if ( 1 >= $record_page ) {
			$url = trailingslashit( $account_url ) . $record_hash;

		// Include pagination
		} else {
			global $wp_rewrite;

			// Pretty permalinks
			if ( $wp_rewrite->using_permalinks() ) {
				$url = trailingslashit( $account_url ) . trailingslashit( $wp_rewrite->pagination_base ) . trailingslashit( $record_page ) . $record_hash;

			// Yucky links
			} else {
				$url = add_query_arg( 'paged', $record_page, $account_url ) . $record_hash;
			}
		}

		// Add account view query arg back to end if it is set
		if ( fct_get_view_all() )
			$url = fct_add_view_all( $url );

		return apply_filters( 'fct_get_record_url', $url, $record_id, $redirect_to );
	}

/**
 * Output the title of the record
 *
 * @param int $record_id Optional. Record id
 * @uses fct_get_record_title() To get the record title
 */
function fct_record_title( $record_id = 0 ) {
	echo fct_get_record_title( $record_id );
}

	/**
	 * Return the title of the record
	 *
	 * @param int $record_id Optional. Record id
	 * @uses fct_get_record_id() To get the record id
	 * @uses get_the_title() To get the record title
	 * @uses apply_filters() Calls 'fct_get_record_title' with the title and
	 *                        record id
	 * @return string Title of record
	 */
	function fct_get_record_title( $record_id = 0 ) {
		$record_id = fct_get_record_id( $record_id );

		return apply_filters( 'fct_get_record_title', get_the_title( $record_id ), $record_id );
	}

/**
 * Output the content of the record
 *
 * @param int $record_id Optional. record id
 * @uses fct_get_record_content() To get the record content
 */
function fct_record_content( $record_id = 0 ) {
	echo fct_get_record_content( $record_id );
}
	/**
	 * Return the content of the record
	 *
	 * @param int $record_id Optional. record id
	 * @uses fct_get_record_id() To get the record id
	 * @uses post_password_required() To check if the record requires pass
	 * @uses get_the_password_form() To get the password form
	 * @uses get_post_field() To get the content post field
	 * @uses apply_filters() Calls 'fct_get_record_content' with the content
	 *                        and record id
	 * @return string Content of the record
	 */
	function fct_get_record_content( $record_id = 0 ) {
		$record_id = fct_get_record_id( $record_id );
		$content   = get_post_field( 'post_content', $record_id );

		return apply_filters( 'fct_get_record_content', $content, $record_id );
	}

/**
 * Output the excerpt of the record
 *
 * @param int $record_id Optional. Record id
 * @param int $length Optional. Length of the excerpt. Defaults to 100 letters
 * @uses fct_get_record_excerpt() To get the record excerpt
 */
function fct_record_excerpt( $record_id = 0, $length = 100 ) {
	echo fct_get_record_excerpt( $record_id, $length );
}
	/**
	 * Return the excerpt of the record
	 *
	 * @param int $record_id Optional. Record id
	 * @param int $length Optional. Length of the excerpt. Defaults to 100
	 *                     letters
	 * @uses fct_get_record_id() To get the record id
	 * @uses get_post_field() To get the excerpt
	 * @uses fct_get_record_content() To get the record content
	 * @uses apply_filters() Calls 'fct_get_record_excerpt' with the excerpt,
	 *                        record id and length
	 * @return string Record Excerpt
	 */
	function fct_get_record_excerpt( $record_id = 0, $length = 100 ) {
		$record_id = fct_get_record_id( $record_id );
		$length    = (int) $length;
		$excerpt   = get_post_field( $record_id, 'post_excerpt' );

		if ( empty( $excerpt ) )
			$excerpt = fct_get_record_content( $record_id );

		$excerpt = trim ( strip_tags( $excerpt ) );

		if ( ! empty( $length ) && strlen( $excerpt ) > $length ) {
			$excerpt  = substr( $excerpt, 0, $length - 1 );
			$excerpt .= '&hellip;';
		}

		return apply_filters( 'fct_get_record_excerpt', $excerpt, $record_id, $length );
	}

/**
 * Output the post date and time of a record
 *
 * @param int $record_id Optional. Record id.
 * @param bool $humanize Optional. Humanize output using time_since
 * @param bool $gmt Optional. Use GMT
 * @uses fct_get_record_post_date() to get the output
 */
function fct_record_post_date( $record_id = 0, $humanize = false, $gmt = false ) {
	echo fct_get_record_post_date( $record_id, $humanize, $gmt );
}
	/**
	 * Return the post date and time of a record
	 *
	 * @param int $record_id Optional. Record id.
	 * @param bool $humanize Optional. Humanize output using time_since
	 * @param bool $gmt Optional. Use GMT
	 * @uses fct_get_record_id() To get the record id
	 * @uses get_post_time() to get the record post time
	 * @uses fct_time_since() to maybe humanize the record post time
	 * @return string
	 */
	function fct_get_record_post_date( $record_id = 0, $humanize = false, $gmt = false ) {
		$record_id = fct_get_record_id( $record_id );

		// 4 days, 4 hours ago
		if ( ! empty( $humanize ) ) {
			$gmt    = ! empty( $gmt ) ? 'G' : 'U';
			$date   = get_post_time( $gmt, $record_id );
			$time   = false; // For filter below
			$result = fct_time_since( $date );

		// August 4, 2012 at 2:37 pm
		} else {
			$date   = get_post_time( get_option( 'date_format' ), $gmt, $record_id );
			$time   = get_post_time( get_option( 'time_format' ), $gmt, $record_id );
			$result = sprintf( _x( '%1$s at %2$s', 'date at time', 'fiscaat' ), $date, $time );
		}

		return apply_filters( 'fct_get_record_post_date', $result, $record_id, $humanize, $gmt, $date, $time );
	}

/**
 * Output the status of the record
 *
 * @param int $record_id Optional. Record id
 * @uses fct_get_record_status() To get the record status
 */
function fct_record_status( $record_id = 0 ) {
	echo fct_get_record_status( $record_id );
}
	/**
	 * Return the status of the record
	 *
	 * @param int $record_id Optional. Record id
	 * @uses fct_get_record_id() To get the record id
	 * @uses get_post_status() To get the record status
	 * @uses apply_filters() Calls 'fct_get_record_status' with the record id
	 * @return string Status of record
	 */
	function fct_get_record_status( $record_id = 0 ) {
		$record_id = fct_get_record_id( $record_id );
		return apply_filters( 'fct_get_record_status', get_post_status( $record_id ), $record_id );
	}

/**
 * Is the record editable?
 *
 * @param int $record_id Optional. Account id
 * @uses fct_is_record_closed() To check if the record is closed
 * @return bool True if open, false if closed.
 */
function fct_is_record_open( $record_id = 0 ) {
	return !fct_is_record_closed( $record_id );
}

	/**
	 * Is the record not editable?
	 *
	 * @param int $record_id Optional. Account id
	 * @uses fct_get_record_status() To get the record status
	 * @uses apply_filters() Calls 'fct_is_record_closed' with the record id
	 *
	 * @return bool True if closed, false if not.
	 */
	function fct_is_record_closed( $record_id = 0 ) {
		$record_id = fct_get_record_id( $record_id );
		$closed    = fct_get_record_status( $record_id ) == fct_get_closed_status_id();

		return (bool) apply_filters( 'fct_is_record_closed', (bool) $closed, $record_id );
	}

/**
 * Is the record published?
 *
 * @param int $record_id Optional. Account id
 * @uses fct_get_record_id() To get the record id
 * @uses fct_get_record_status() To get the record status
 * @return bool True if published, false if not.
 */
function fct_is_record_published( $record_id = 0 ) {
	$record_id     = fct_get_record_id( $record_id );
	$record_status = fct_get_record_status( $record_id ) == fct_get_public_status_id();

	return (bool) apply_filters( 'fct_is_record_published', (bool) $record_status, $record_id );
}

/**
 * Is the record declined?
 *
 * @param int $record_id Optional. Record id
 * @uses fct_get_record_id() To get the record id
 * @uses fct_get_record_status() To get the record status
 * @return bool True if declined, false if not.
 */
function fct_is_record_declined( $record_id = 0 ) {
	$record_id     = fct_get_record_id( $record_id );
	$record_status = fct_get_record_status( $record_id ) == fct_get_declined_status_id();
	
	return (bool) apply_filters( 'fct_is_record_declined', (bool) $record_status, $record_id );
}

/**
 * Is the record approved?
 *
 * @param int $record_id Optional. Account id
 * @uses fct_get_record_id() To get the record id
 * @uses fct_get_record_status() To get the record status
 * @return bool True if approved, false if not.
 */
function fct_is_record_approved( $record_id = 0 ) {
	$record_id     = fct_get_record_id( $record_id );
	$record_status = fct_get_record_status( $record_id ) == fct_get_approved_status_id();
	
	return (bool) apply_filters( 'fct_is_record_approved', (bool) $record_status, $record_id );
}

/**
 * Output the record's status icon
 * 
 * @param int $record_id Optional. Record id
 * @uses fct_get_record_status_icon() To get the record's status icon
 */
function fct_record_status_icon( $record_id = 0 ) {
	echo fct_get_record_status_icon( $record_id );
}
	/**
	 * Return the records' status icon
	 *
	 * @todo Fix retina icons
	 * 
	 * @param int $record_id Optional. Record id
	 * @uses ficsaat_get_record_id()
	 * @uses fct_get_record_status()
	 * @uses fct_get_public_status_id()
	 * @uses fct_get_declined_status_id()
	 * @uses fct_get_approved_status_id()
	 * @uses fct_get_closed_status_id()
	 * @uses apply_filters() Calls 'fct_get_record_status_icon' with
	 *                        the record status icon, record id, and status
	 * @return string Record's status icon img element
	 */
	function fct_get_record_status_icon( $record_id = 0 ) {
		$record_id = fct_get_record_id( $record_id );
		$status    = fct_get_record_status( $record_id );

		// Get image source
		switch ( $status ) {

			case fct_get_public_status_id() :
				$img = 'on-hold.png';
				break;

			case fct_get_declined_status_id() :
				$img = 'cancelled.png';
				break;

			case fct_get_approved_status_id() :
				$img = 'complete.png';
				break;

			case fct_get_closed_status_id() :
				$img = 'pending.png';
				break;
		}

		$retval = '<div class="fct_record_status_icon status_'. $status .'">
					<img src="'. fiscaat()->admin->admin_url .'images/'. $img .'" />
				</div>';

		return apply_filters( 'ficsaat_get_record_status_icon', $retval, $record_id, $status );
	}

/**
 * Output the author of the record
 *
 * @param int $record_id Optional. Record id
 * @uses fct_get_record_author() To get the record author
 */
function fct_record_author( $record_id = 0 ) {
	echo fct_get_record_author( $record_id );
}
	/**
	 * Return the author of the record
	 *
	 * @param int $record_id Optional. Record id
	 * @uses fct_get_record_id() To get the record id
	 * @uses get_the_author_meta() To get the record author display name
	 * @uses apply_filters() Calls 'fct_get_record_author' with the record
	 *                        author and record id
	 * @return string Author of record
	 */
	function fct_get_record_author( $record_id = 0 ) {
		$record_id = fct_get_record_id( $record_id );
		$author    = get_the_author_meta( 'display_name', fct_get_record_author_id( $record_id ) );

		return apply_filters( 'fct_get_record_author', $author, $record_id );
	}

/**
 * Output the author ID of the record
 *
 * @param int $record_id Optional. Record id
 * @uses fct_get_record_author_id() To get the record author id
 */
function fct_record_author_id( $record_id = 0 ) {
	echo fct_get_record_author_id( $record_id );
}
	/**
	 * Return the author ID of the record
	 *
	 * @param int $record_id Optional. Record id
	 * @uses fct_get_record_id() To get the record id
	 * @uses get_post_field() To get the record author id
	 * @uses apply_filters() Calls 'fct_get_record_author_id' with the author
	 *                        id and record id
	 * @return string Author id of record
	 */
	function fct_get_record_author_id( $record_id = 0 ) {
		$record_id  = fct_get_record_id( $record_id );
		$author_id = get_post_field( 'post_author', $record_id );

		return (int) apply_filters( 'fct_get_record_author_id', $author_id, $record_id );
	}

/**
 * Output the author display_name of the record
 *
 * @param int $record_id Optional. Record id
 * @uses fct_get_record_author_display_name()
 */
function fct_record_author_display_name( $record_id = 0 ) {
	echo fct_get_record_author_display_name( $record_id );
}
	/**
	 * Return the author display_name of the record
	 *
	 * @param int $record_id Optional. Record id
	 * @uses fct_get_record_id() To get the record id
	 * @uses fct_get_record_author_id() To get the record author id
	 * @uses get_the_author_meta() To get the record author's display name
	 * @uses apply_filters() Calls 'fct_get_record_author_display_name' with
	 *                        the author display name and record id
	 * @return string Record's author's display name
	 */
	function fct_get_record_author_display_name( $record_id = 0 ) {
		$record_id = fct_get_record_id( $record_id );

		// Get the author ID
		$author_id = fct_get_record_author_id( $record_id );

		// Try to get a display name
		$author_name = get_the_author_meta( 'display_name', $author_id );

		// Fall back to user login
		if ( empty( $author_name ) )
			$author_name = get_the_author_meta( 'user_login', $author_id );

		return apply_filters( 'fct_get_record_author_display_name', esc_attr( $author_name ), $record_id );
	}

/**
 * Output the author avatar of the record
 *
 * @param int $record_id Optional. Record id
 * @param int $size Optional. Size of the avatar. Defaults to 40
 * @uses fct_get_record_author_avatar() To get the record author id
 */
function fct_record_author_avatar( $record_id = 0, $size = 40 ) {
	echo fct_get_record_author_avatar( $record_id, $size );
}
	/**
	 * Return the author avatar of the record
	 *
	 * @param int $record_id Optional. Record id
	 * @param int $size Optional. Size of the avatar. Defaults to 40
	 * @uses fct_get_record_id() To get the record id
	 * @uses fct_get_record_author_id() To get the record author id
	 * @uses get_avatar() To get the avatar
	 * @uses apply_filters() Calls 'fct_get_record_author_avatar' with the
	 *                        author avatar, record id and size
	 * @return string Avatar of author of the record
	 */
	function fct_get_record_author_avatar( $record_id = 0, $size = 40 ) {
		$record_id = fct_get_record_id( $record_id );
		if ( ! empty( $record_id ) ) { 
			$author_avatar = get_avatar( fct_get_record_author_id( $record_id ), $size );
		} else {
			$author_avatar = '';
		}

		return apply_filters( 'fct_get_record_author_avatar', $author_avatar, $record_id, $size );
	}

/**
 * Output the account title a record belongs to
 *
 * @param int $record_id Optional. Record id
 * @uses fct_get_record_account_title() To get the record account title
 */
function fct_record_account_title( $record_id = 0 ) {
	echo fct_get_record_account_title( $record_id );
}
	/**
	 * Return the account title a record belongs to
	 *
	 * @param int $record_id Optional. Record id
	 * @uses fct_get_record_id() To get the record id
	 * @uses fct_get_record_account_id() To get the record account id
	 * @uses fct_get_account_title() To get the record account title
	 * @uses apply_filters() Calls 'fct_get_record_account_title' with the
	 *                        account title and record id
	 * @return string Record's account's title
	 */
	function fct_get_record_account_title( $record_id = 0 ) {
		$record_id  = fct_get_record_id( $record_id );
		$account_id = fct_get_record_account_id( $record_id );

		return apply_filters( 'fct_get_record_account_title', fct_get_account_title( $account_id ), $record_id );
	}

/**
 * Output the account id a record belongs to
 *
 * @param int $record_id Optional. Record id
 * @uses fct_get_record_account_id() To get the record account id
 */
function fct_record_account_id( $record_id = 0 ) {
	echo fct_get_record_account_id( $record_id );
}
	/**
	 * Return the account id a record belongs to
	 *
	 * @param int $record_id Optional. Record id
	 * @uses fct_get_record_id() To get the record id
	 * @uses fct_get_record_meta() To get the record account id from meta
	 * @uses apply_filters() Calls 'fct_get_record_account_id' with the account
	 *                        id and record id
	 * @return int Record's account id
	 */
	function fct_get_record_account_id( $record_id = 0 ) {
		$record_id  = fct_get_record_id( $record_id );
		$account_id = (int) fct_get_record_meta( $record_id, 'account_id' );

		return (int) apply_filters( 'fct_get_record_account_id', $account_id, $record_id );
	}

/**
 * Output the year id a record belongs to
 *
 * @param int $record_id Optional. Record id
 * @uses fct_get_record_year_id() To get the record year id
 */
function fct_record_year_id( $record_id = 0 ) {
	echo fct_get_record_year_id( $record_id );
}
	/**
	 * Return the year id a record belongs to
	 *
	 * @param int $record_id Optional. Record id
	 * @uses fct_get_record_id() To get the record id
	 * @uses fct_get_record_meta() To get the record year id
	 * @uses apply_filters() Calls 'fct_get_record_year_id' with the year
	 *                        id and record id
	 * @return int Record's year id
	 */
	function fct_get_record_year_id( $record_id = 0 ) {
		$record_id = fct_get_record_id( $record_id );
		$year_id   = (int) fct_get_record_meta( $record_id, 'year_id' );

		return (int) apply_filters( 'fct_get_record_year_id', $year_id, $record_id );
	}

/**
 * Output the offset account of a record
 *
 * @param int $record_id Optional. Record id
 * @uses fct_get_record_offset_account() To get the record offset account
 */
function fct_record_offset_account( $record_id = 0 ) {
	echo fct_get_record_offset_account( $record_id );
}
	/**
	 * Return the offset account of a record
	 *
	 * @param int $record_id Optional. Record id
	 * @uses fct_get_record_id() To get the record id
	 * @uses fct_get_record_meta() To get the record offset account
	 * @uses apply_filters() Calls 'fct_get_record_offset_account' with the year
	 *                        id and record id
	 * @return int Record's offset account
	 */
	function fct_get_record_offset_account( $record_id = 0 ) {
		$record_id      = fct_get_record_id( $record_id );
		$offset_account = fct_get_record_meta( $record_id, 'offset_account' );

		return apply_filters( 'fct_get_record_offset_account', $offset_account, $record_id );
	}

/**
 * Output the value of a record
 *
 * @param int $record_id Optional. Record id
 * @uses fct_get_record_value() To get the record value
 */
function fct_record_value( $record_id = 0 ) {
	echo fct_get_record_value( $record_id );
}
	/**
	 * Return the value of a record
	 *
	 * @param int $record_id Optional. Record id
	 * @uses fct_get_record_id() To get the record id
	 * @uses fct_get_record_meta() To get the record value
	 * @uses apply_filters() Calls 'fct_get_record_value' with the year
	 *                        id and record id
	 * @return int Record's value
	 */
	function fct_get_record_value( $record_id = 0 ) {
		$record_id = fct_get_record_id( $record_id );
		$value     = (float) fct_get_record_meta( $record_id, 'value' );

		return (float) apply_filters( 'fct_get_record_value', $value, $record_id );
	}

/**
 * Output the value type of a record
 *
 * @param int $record_id Optional. Record id
 * @uses fct_get_record_value() To get the record value type
 */
function fct_record_value_type( $record_id = 0 ) {
	echo fct_get_record_value_type( $record_id );
}
	/**
	 * Return the value type of a record
	 *
	 * @param int $record_id Optional. Record id
	 * @uses fct_get_record_id() To get the record id
	 * @uses fct_get_record_meta() To get the record value type
	 * @uses apply_filters() Calls 'fct_get_record_value_type' with the year
	 *                        id and record id
	 * @return int Record's value type
	 */
	function fct_get_record_value_type( $record_id = 0 ) {
		$record_id  = fct_get_record_id( $record_id );
		$value_type = fct_get_record_meta( $record_id, 'value_type' );

		return apply_filters( 'fct_get_record_value_type', $value_type, $record_id );
	}

/** Record Admin Links *********************************************************/

/**
 * Output admin links for record
 *
 * @param mixed $args See {@link fct_get_record_admin_links()}
 * @uses fct_get_record_admin_links() To get the record admin links
 */
function fct_record_admin_links( $args = '' ) {
	echo fct_get_record_admin_links( $args );
}
	/**
	 * Return admin links for record
	 *
	 * @param mixed $args This function supports these arguments:
	 *  - id: Optional. Record id
	 *  - before: HTML before the links. Defaults to
	 *             '<span class="fiscaat-admin-links">'
	 *  - after: HTML after the links. Defaults to '</span>'
	 *  - sep: Separator. Defaults to ' | '
	 *  - links: Array of the links to display. By default, edit, trash,
	 *            spam and account split links are displayed
	 * @uses fct_is_account() To check if it's the account page
	 * @uses fct_is_record() To check if it's the record page
	 * @uses fct_get_record_id() To get the record id
	 * @uses fct_get_record_edit_link() To get the record edit link
	 * @uses fct_get_record_trash_link() To get the record trash link
	 * @uses fct_get_record_spam_link() To get the record spam link
	 * @uses fct_get_account_split_link() To get the account split link
	 * @uses current_user_can() To check if the current user can edit or
	 *                           delete the record
	 * @uses apply_filters() Calls 'fct_get_record_admin_links' with the
	 *                        record admin links and args
	 * @return string Record admin links
	 */
	function fct_get_record_admin_links( $args = '' ) {

		$defaults = array (
			'id'     => 0,
			'before' => '<span class="fiscaat-admin-links">',
			'after'  => '</span>',
			'sep'    => ' | ',
			'links'  => array()
		);
		$r = fct_parse_args( $args, $defaults, 'get_record_admin_links' );

		$r['id'] = fct_get_record_id( (int) $r['id'] );

		// If post is a account, return the account admin links instead
		if ( fct_is_account( $r['id'] ) )
			return fct_get_account_admin_links( $args );

		// If post is not a record, return
		if ( !fct_is_record( $r['id'] ) )
			return;

		// Make sure user can edit this record
		if ( !current_user_can( 'edit_record', $r['id'] ) )
			return;

		// If account is closed, do not show admin links
		if ( fct_is_account_closed( fct_get_record_account_id( $r['id'] ) ) )
			return;

		// If no links were passed, default to the standard
		if ( empty( $r['links'] ) ) {
			$r['links'] = array (
				'edit'    => fct_get_record_edit_link   ( $r ),
				'decline' => fct_get_record_decline_link( $r ),
				'approve' => fct_get_record_approve_link( $r ),
			);
		}

		// See if links need to be unset
		$record_status = fct_get_record_status( $r['id'] );
		if ( in_array( $record_status, array( fct_get_declined_status_id(), fct_get_approved_status_id() ) ) ) {

			// Decline link shouldn't be visible on declined accounts
			if ( $record_status == fct_get_declined_status_id() ) {
				unset( $r['links']['decline'] );

			// Approve link shouldn't be visible on approved accounts
			} elseif ( isset( $r['links']['approve'] ) && ( fct_get_approved_status_id() == $record_status ) ) {
				unset( $r['links']['approve'] );
			}
		}

		// Process the admin links
		$links  = implode( $r['sep'], array_filter( $r['links'] ) );
		$retval = $r['before'] . $links . $r['after'];

		return apply_filters( 'fct_get_record_admin_links', $retval, $args );
	}

/**
 * Output the edit link of the record
 *
 * @param mixed $args See {@link fct_get_record_edit_link()}
 * @uses fct_get_record_edit_link() To get the record edit link
 */
function fct_record_edit_link( $args = '' ) {
	echo fct_get_record_edit_link( $args );
}

	/**
	 * Return the edit link of the record
	 *
	 * @param mixed $args This function supports these arguments:
	 *  - id: Record id
	 *  - link_before: HTML before the link
	 *  - link_after: HTML after the link
	 *  - edit_text: Edit text. Defaults to 'Edit'
	 * @uses fct_get_record_id() To get the record id
	 * @uses fct_get_record() To get the record
	 * @uses current_user_can() To check if the current user can edit the
	 *                           record
	 * @uses fct_get_record_edit_url() To get the record edit url
	 * @uses apply_filters() Calls 'fct_get_record_edit_link' with the record
	 *                        edit link and args
	 * @return string Record edit link
	 */
	function fct_get_record_edit_link( $args = '' ) {
		$defaults = array (
			'id'           => 0,
			'link_before'  => '',
			'link_after'   => '',
			'edit_text'    => __( 'Edit', 'fiscaat' )
		);
		$r = fct_parse_args( $args, $defaults, 'get_record_edit_link' );
		extract( $r );

		$record = fct_get_record( fct_get_record_id( (int) $id ) );

		// Bypass check if user has caps
		if ( !current_user_can( 'edit_others_records' ) ) {

			// User cannot edit or it is past the lock time
			if ( empty( $record ) || !current_user_can( 'edit_record', $record->ID ) )
				return;
		}

		// Get uri
		$uri = fct_get_record_edit_url( $id );

		// Bail if no uri
		if ( empty( $uri ) )
			return;

		$retval = $link_before . '<a href="' . $uri . '">' . $edit_text . '</a>' . $link_after;

		return apply_filters( 'fct_get_record_edit_link', $retval, $args );
	}

/**
 * Output URL to the record edit page
 *
 * @param int $record_id Optional. Record id
 * @uses fct_get_record_edit_url() To get the record edit url
 */
function fct_record_edit_url( $record_id = 0 ) {
	echo fct_get_record_edit_url( $record_id );
}
	/**
	 * Return URL to the record edit page
	 *
	 * @param int $record_id Optional. Record id
	 * @uses fct_get_record_id() To get the record id
	 * @uses fct_get_record() To get the record
	 * @uses fct_get_record_post_type() To get the record post type
	 * @uses add_query_arg() To add custom args to the url
	 * @uses apply_filters() Calls 'fct_get_record_edit_url' with the edit
	 *                        url and record id
	 * @return string Record edit url
	 */
	function fct_get_record_edit_url( $record_id = 0 ) {
		global $wp_rewrite;

		$fiscaat   = fiscaat();
		$record = fct_get_record( fct_get_record_id( $record_id ) );
		if ( empty( $record ) )
			return;

		$record_link = fct_remove_view_all( fct_get_record_permalink( $record_id ) );

		// Pretty permalinks
		if ( $wp_rewrite->using_permalinks() ) {
			$url = trailingslashit( $record_link ) . $fiscaat->edit_id;
			$url = trailingslashit( $url );

		// Unpretty permalinks
		} else {
			$url = add_query_arg( array( fct_get_record_post_type() => $record->post_name, $fiscaat->edit_id => '1' ), $record_link );
		}

		// Maybe add view all
		$url = fct_add_view_all( $url );

		return apply_filters( 'fct_get_record_edit_url', $url, $record_id );
	}

/**
 * Output the decline link of the record
 *
 * @param mixed $args See {@link fct_get_record_decline_link()}
 * @uses fct_get_record_decline_link() To get the record decline link
 */
function fct_record_decline_link( $args = '' ) {
	echo fct_get_record_decline_link( $args );
}

	/**
	 * Return the decline link of the record
	 *
	 * @param mixed $args This function supports these arguments:
	 *  - id: Record id
	 *  - link_before: HTML before the link
	 *  - link_after: HTML after the link
	 *  - decline_text: Decline text
	 * @uses fct_get_record_id() To get the record id
	 * @uses fct_get_record() To get the record
	 * @uses current_user_can() To check if the current user can edit the
	 *                           record
	 * @uses add_query_arg() To add custom args to the url
	 * @uses wp_nonce_url() To nonce the url
	 * @uses esc_url() To escape the url
	 * @uses fct_get_record_edit_url() To get the record edit url
	 * @uses apply_filters() Calls 'fct_get_record_decline_link' with the record
	 *                        decline link and args
	 * @return string Record decline link
	 */
	function fct_get_record_decline_link( $args = '' ) {
		$defaults = array (
			'id'           => 0,
			'link_before'  => '',
			'link_after'   => '',
			'decline_text' => __( 'Decline', 'fiscaat' )
		);
		$r = fct_parse_args( $args, $defaults, 'get_record_decline_link' );
		extract( $r );

		$record = fct_get_record( fct_get_record_id( (int) $id ) );

		if ( empty( $record ) || !current_user_can( 'control', $record->ID ) )
			return;

		$uri      = add_query_arg( array( 'action' => 'fct_toggle_record_approval', 'record_id' => $record->ID ) );
		$uri      = esc_url( wp_nonce_url( $uri, 'approval-record_' . $record->ID ) );
		$retval   = $link_before . '<a href="' . $uri . '">' . $decline_text . '</a>' . $link_after;

		return apply_filters( 'fct_get_record_decline_link', $retval, $args );
	}

/**
 * Output the approve link of the record
 *
 * @param mixed $args See {@link fct_get_record_approve_link()}
 * @uses fct_get_record_approve_link() To get the record approve link
 */
function fct_record_approve_link( $args = '' ) {
	echo fct_get_record_approve_link( $args );
}

	/**
	 * Return the approve link of the record
	 *
	 * @param mixed $args This function supports these arguments:
	 *  - id: Record id
	 *  - link_before: HTML before the link
	 *  - link_after: HTML after the link
	 *  - approve_text: Approve text
	 * @uses fct_get_record_id() To get the record id
	 * @uses fct_get_record() To get the record
	 * @uses current_user_can() To check if the current user can edit the
	 *                           record
	 * @uses add_query_arg() To add custom args to the url
	 * @uses wp_nonce_url() To nonce the url
	 * @uses esc_url() To escape the url
	 * @uses fct_get_record_edit_url() To get the record edit url
	 * @uses apply_filters() Calls 'fct_get_record_approve_link' with the record
	 *                        approve link and args
	 * @return string Record approve link
	 */
	function fct_get_record_approve_link( $args = '' ) {
		$defaults = array (
			'id'           => 0,
			'link_before'  => '',
			'link_after'   => '',
			'approve_text' => __( 'Approve', 'fiscaat' )
		);
		$r = fct_parse_args( $args, $defaults, 'get_record_approve_link' );
		extract( $r );

		$record = fct_get_record( fct_get_record_id( (int) $id ) );

		if ( empty( $record ) || !current_user_can( 'control', $record->ID ) )
			return;

		$uri      = add_query_arg( array( 'action' => 'fct_toggle_record_approval', 'record_id' => $record->ID ) );
		$uri      = esc_url( wp_nonce_url( $uri, 'approval-record_' . $record->ID ) );
		$retval   = $link_before . '<a href="' . $uri . '">' . $approve_text . '</a>' . $link_after;

		return apply_filters( 'fct_get_record_approve_link', $retval, $args );
	}

/**
 * Output the suspense link of the record
 *
 * @param mixed $args See {@link fct_get_record_suspense_link()}
 * @uses fct_get_record_suspense_link() To get the record suspense link
 */
function fct_record_suspense_link( $args = '' ) {
	echo fct_get_record_suspense_link( $args );
}

	/**
	 * Return the suspense link of the record
	 *
	 * @param mixed $args This function supports these arguments:
	 *  - id: Record id
	 *  - link_before: HTML before the link
	 *  - link_after: HTML after the link
	 *  - suspense_text: Suspense text
	 * @uses fct_get_record_id() To get the record id
	 * @uses fct_get_record() To get the record
	 * @uses current_user_can() To check if the current user can edit the
	 *                           record
	 * @uses add_query_arg() To add custom args to the url
	 * @uses wp_nonce_url() To nonce the url
	 * @uses esc_url() To escape the url
	 * @uses fct_get_record_edit_url() To get the record edit url
	 * @uses apply_filters() Calls 'fct_get_record_suspense_link' with the record
	 *                        suspense link and args
	 * @return string Record suspense link
	 */
	function fct_get_record_suspense_link( $args = '' ) {
		$defaults = array (
			'id'           => 0,
			'link_before'  => '',
			'link_after'   => '',
			'suspense_text' => __( 'Suspense', 'fiscaat' )
		);
		$r = fct_parse_args( $args, $defaults, 'get_record_suspense_link' );
		extract( $r );

		$record = fct_get_record( fct_get_record_id( (int) $id ) );

		if ( empty( $record ) || !current_user_can( 'control', $record->ID ) )
			return;

		$uri      = add_query_arg( array( 'action' => 'fct_set_record_suspense', 'record_id' => $record->ID ) );
		$uri      = esc_url( wp_nonce_url( $uri, 'suspense-record_' . $record->ID ) );
		$retval   = $link_before . '<a href="' . $uri . '">' . $suspense_text . '</a>' . $link_after;

		return apply_filters( 'fct_get_record_suspense_link', $retval, $args );
	}

/**
 * Output the row class of a record
 *
 * @param int $record_id Optional. Record ID
 * @uses fct_get_record_class() To get the record class
 */
function fct_record_class( $record_id = 0 ) {
	echo fct_get_record_class( $record_id );
}
	/**
	 * Return the row class of a record
	 *
	 * @param int $record_id Optional. Record ID
	 * @uses fct_get_record_id() To validate the record id
	 * @uses fct_get_record_year_id() To get the record's year id
	 * @uses fct_get_record_account_id() To get the record's account id
	 * @uses get_post_class() To get all the classes including ours
	 * @uses apply_filters() Calls 'fct_get_record_class' with the classes
	 * @return string Row class of the record
	 */
	function fct_get_record_class( $record_id = 0 ) {
		$fiscaat   = fiscaat();
		$record_id = fct_get_record_id( $record_id );
		$count     = isset( $fiscaat->record_query->current_post ) ? $fiscaat->record_query->current_post : 1;
		$classes   = array();
		$classes[] = ( (int) $count % 2 ) ? 'even' : 'odd';
		$classes[] = 'fiscaat-parent-year-'    . fct_get_record_year_id   ( $record_id );
		$classes[] = 'fiscaat-parent-account-' . fct_get_record_account_id( $record_id );
		$classes[] = 'user-id-'                . fct_get_record_author_id ( $record_id );
		$classes[] = 'fiscaat-record-status-'  . fct_get_record_status    ( $record_id );
		$classes   = array_filter( $classes );
		$classes   = get_post_class( $classes, $record_id );
		$classes   = apply_filters( 'fct_get_record_class', $classes, $record_id );
		$retval    = 'class="' . join( ' ', $classes ) . '"';

		return $retval;
	}

/**
 * Output the account pagination count
 *
 * @uses fct_get_account_pagination_count() To get the account pagination count
 */
function fct_account_pagination_count() {
	echo fct_get_account_pagination_count();
}
	/**
	 * Return the account pagination count
	 *
	 * @uses fct_number_format() To format the number value
	 * @uses fct_show_lead_account() Are we showing the account as a lead?
	 * @uses apply_filters() Calls 'fct_get_account_pagination_count' with the
	 *                        pagination count
	 * @return string Account pagination count
	 */
	function fct_get_account_pagination_count() {
		$fiscaat = fiscaat();

		// Define local variable(s)
		$retstr = '';

		// Set pagination values
		$start_num = intval( ( $fiscaat->record_query->paged - 1 ) * $fiscaat->record_query->posts_per_page ) + 1;
		$from_num  = fct_number_format( $start_num );
		$to_num    = fct_number_format( ( $start_num + ( $fiscaat->record_query->posts_per_page - 1 ) > $fiscaat->record_query->found_posts ) ? $fiscaat->record_query->found_posts : $start_num + ( $fiscaat->record_query->posts_per_page - 1 ) );
		$total_int = (int) $fiscaat->record_query->found_posts;
		$total     = fct_number_format( $total_int );

		// We are not including the lead account
		if ( fct_show_lead_account() ) {

			// Several records in a account with a single page
			if ( empty( $to_num ) ) {
				$retstr = sprintf( _n( 'Viewing %1$s record', 'Viewing %1$s records', $total_int, 'fiscaat' ), $total );

			// Several records in a account with several pages
			} else {
				$retstr = sprintf( _n( 'Viewing %2$s records (of %4$s total)', 'Viewing %1$s records - %2$s through %3$s (of %4$s total)', $fiscaat->record_query->post_count, 'fiscaat' ), $fiscaat->record_query->post_count, $from_num, $to_num, $total );
			}

		// We are including the lead account
		} else {

			// Several posts in a account with a single page
			if ( empty( $to_num ) ) {
				$retstr = sprintf( _n( 'Viewing %1$s post', 'Viewing %1$s posts', $total_int, 'fiscaat' ), $total );

			// Several posts in a account with several pages
			} else {
				$retstr = sprintf( _n( 'Viewing %2$s post (of %4$s total)', 'Viewing %1$s posts - %2$s through %3$s (of %4$s total)', $fiscaat->record_query->post_count, 'fiscaat' ), $fiscaat->record_query->post_count, $from_num, $to_num, $total );
			}
		}

		// Filter and return
		return apply_filters( 'fct_get_account_pagination_count', $retstr );
	}

/**
 * Output account pagination links
 *
 * @uses fct_get_account_pagination_links() To get the account pagination links
 */
function fct_account_pagination_links() {
	echo fct_get_account_pagination_links();
}
	/**
	 * Return account pagination links
	 *
	 * @uses apply_filters() Calls 'fct_get_account_pagination_links' with the
	 *                        pagination links
	 * @return string Account pagination links
	 */
	function fct_get_account_pagination_links() {
		$fiscaat = fiscaat();

		if ( !isset( $fiscaat->record_query->pagination_links ) || empty( $fiscaat->record_query->pagination_links ) )
			return false;

		return apply_filters( 'fct_get_account_pagination_links', $fiscaat->record_query->pagination_links );
	}

/** Forms *********************************************************************/

/**
 * Output the value of record title field
 *
 * @uses fct_get_form_record_title() To get value of record title field
 */
function fct_form_record_title() {
	echo fct_get_form_record_title();
}
	/**
	 * Return the value of record title field
	 *
	 * @uses fct_is_record_edit() To check if it's the record edit page
	 * @uses apply_filters() Calls 'fct_get_form_record_title' with the title
	 * @return string Value of record title field
	 */
	function fct_get_form_record_title() {

		// Get _POST data
		if ( 'POST' == strtoupper( $_SERVER['REQUEST_METHOD'] ) && isset( $_POST['fct_record_title'] ) )
			$record_title = $_POST['fct_record_title'];

		// Get edit data
		elseif ( fct_is_record_edit() )
			$record_title = fct_get_global_post_field( 'post_title', 'raw' );

		// No data
		else
			$record_title = '';

		return apply_filters( 'fct_get_form_record_title', esc_attr( $record_title ) );
	}

/**
 * Output the value of record content field
 *
 * @uses fct_get_form_record_content() To get value of record content field
 */
function fct_form_record_content() {
	echo fct_get_form_record_content();
}
	/**
	 * Return the value of record content field
	 *
	 * @uses fct_is_record_edit() To check if it's the record edit page
	 * @uses apply_filters() Calls 'fct_get_form_record_content' with the content
	 * @return string Value of record content field
	 */
	function fct_get_form_record_content() {

		// Get _POST data
		if ( 'POST' == strtoupper( $_SERVER['REQUEST_METHOD'] ) && isset( $_POST['fct_record_content'] ) )
			$record_content = $_POST['fct_record_content'];

		// Get edit data
		elseif ( fct_is_record_edit() )
			$record_content = fct_get_global_post_field( 'post_content', 'raw' );

		// No data
		else
			$record_content = '';

		return apply_filters( 'fct_get_form_record_content', esc_textarea( $record_content ) );
	}

/**
 * Output hte record status dropdown
 * 
 * @param int $record_id Optional. Record id
 * @param bool $disable Optional. Whether to disable the dropdown
 * @uses fct_get_form_record_status_dropdown() To get the record status dropdown
 */
function fct_form_record_status_dropdown( $record_id = 0 ) {
	echo fct_get_form_record_status_dropdown( $record_id );
}
	/**
	 * Return the record status dropdown
	 * 
	 * @param int $record_id Optional. Record id
	 * @param bool $disable Optional. Whether to disable the dropdown
	 * @uses fct_get_record_id()
	 * @uses fct_get_record_status()
	 * @uses fct_get_approved_status_id()
	 * @uses fct_get_declined_status_id()
	 * @uses fct_get_closed_status_id()
	 * @uses apply_filters() Calls 'fct_get_form_record_status_dropdown' with the
	 *                        status dropdown, record id, and record statuses
	 * @return strign Record status dropdown
	 */
	function fct_get_form_record_status_dropdown( $record_id = 0 ) {
		$record_id     = fct_get_record_id( $record_id );
		$record_status = fct_get_record_status( $record_id );
		$statuses      = apply_filters( 'fct_record_statuses', array(
			fct_get_public_status_id() => __('Open',   'fiscaat'),
			fct_get_closed_status_id() => __('Closed', 'fiscaat')
		) );

		$disabled = disabled( apply_filters( 'fct_record_status_dropdown_disable', current_user_can( 'fiscaat' ) ), true, false );
		$status_output = '<select name="fct_record_status" id="fct_record_status" ' . $disabled . '>' . "\n";

		foreach ( $statuses as $value => $label ) {
			$disabled = ' ' . disabled( apply_filters( 'fct_record_status_dropdown_option_disable', false, $value ), true, false );
			$status_output .= "\t" . '<option value="' . $value . '" ' . selected( $record_status, $value, false ) . $disabled . '>' . esc_html( $label ) . '</option>' . "\n";
		}

		$status_output .= '</select>';

		return apply_filters( 'fct_get_form_record_status_dropdown', $status_output, $record_id, $statuses );
	}

/**
 * Output the record's value type select
 * 
 * @param int $record_id Optional. Record id
 * @param bool $disable Optional. Whether to disable the value type select
 * @uses fct_get_form_record_value_type_select()
 */
function fct_form_record_value_type_select( $record_id = 0 ) {
	echo fct_get_form_record_value_type_select( $record_id );
}
	/**
	 * Return the record's value type select
	 * 
	 * @param int $record_id. Optional. Record id
	 * @param bool $disable Optional. Whether to disable the value type select
	 * @uses fct_get_record_id()
	 * @uses fct_get_record_value_type()
	 * @uses fct_get_debit_record_type()
	 * @uses fct_get_credit_record_type()
	 * @uses apply_filters() Calls 'fct_get_form_record_value_type_select' with
	 *                        the record's value type select, record id, and value types
	 * @return string Record value type select
	 */
	function fct_get_form_record_value_type_select( $record_id = 0 ) {
		$record_i