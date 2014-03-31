<?php

/**
 * Fiscaat Account Template Tags
 *
 * @package Fiscaat
 * @subpackage TemplateTags
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** Post Type *****************************************************************/

/**
 * Output the unique id of the custom post type for accounts
 *
 * @uses fiscaat_get_account_post_type() To get the account post type
 */
function fiscaat_account_post_type() {
	echo fiscaat_get_account_post_type();
}
	/**
	 * Return the unique id of the custom post type for accounts
	 *
	 * @uses apply_filters() Calls 'fiscaat_get_account_post_type' with the account
	 *                        post type id
	 * @return string The unique account post type id
	 */
	function fiscaat_get_account_post_type() {
		return apply_filters( 'fiscaat_get_account_post_type', fiscaat()->account_post_type );
	}

/** Account Type **************************************************************/

/**
 * Output the unique id of the result type for accounts
 *
 * @uses fiscaat_get_result_account_type() To get the account result type
 */
function fiscaat_result_account_type() {
	return fiscaat_get_result_account_type();
}
	/**
	 * Return the unique id of the result type for accounts
	 *
	 * @uses apply_filters() Calls 'fiscaat_get_result_account_type' with the result
	 *                         account type id
	 * @return string The unique result account type id
	 */
	function fiscaat_get_result_account_type() {
		return apply_filters( 'fiscaat_get_result_account_type', fiscaat()->result_type_id );
	}

/**
 * Output the unique id of the asset type for accounts
 *
 * @uses fiscaat_get_asset_account_type() To get the account asset type
 */
function fiscaat_asset_account_type() {
	return fiscaat_get_asset_account_type();
}
	/**
	 * Return the unique id of the asset type for accounts
	 *
	 * @uses apply_filters() Calls 'fiscaat_get_asset_account_type' with the asset
	 *                         account type id
	 * @return string The unique asset account type id
	 */
	function fiscaat_get_asset_account_type() {
		return apply_filters( 'fiscaat_get_asset_account_type', fiscaat()->asset_type_id );
	}

/** Account Loop ****************************************************************/

/**
 * The main account loop. WordPress makes this easy for us
 *
 * @param mixed $args All the arguments supported by {@link WP_Query}
 * @uses current_user_can() To check if the current user can edit other's accounts
 * @uses fiscaat_get_account_post_type() To get the account post type
 * @uses WP_Query To make query and get the accounts
 * @uses is_page() To check if it's a page
 * @uses fiscaat_is_single_year() To check if it's a year
 * @uses fiscaat_get_year_id() To get the year id
 * @uses fiscaat_get_paged() To get the current page value
 * @uses wpdb::get_results() To execute our query and get the results
 * @uses WP_Rewrite::using_permalinks() To check if the blog is using permalinks
 * @uses get_permalink() To get the permalink
 * @uses add_query_arg() To add custom args to the url
 * @uses apply_filters() Calls 'fiscaat_accounts_pagination' with the pagination args
 * @uses paginate_links() To paginate the links
 * @uses apply_filters() Calls 'fiscaat_has_accounts' with
 *                        bbPres::account_query::have_posts()
 *                        and bbPres::account_query
 * @return object Multidimensional array of account information
 */
function fiscaat_has_accounts( $args = '' ) {
	global $wp_rewrite;

	// What are the default allowed statuses (based on user caps)
	$post_statuses = array( fiscaat_get_public_status_id(), fiscaat_get_closed_status_id(), fiscaat_get_declined_status_id(), fiscaat_get_approved_status_id() );

	$default_account_search = ! empty( $_REQUEST['ts'] ) ? $_REQUEST['ts'] : false;
	$default_post_parent    = fiscaat_is_single_year() ? fiscaat_get_year_id() : 'any';
	$default_post_status    = join( ',', $post_statuses );

	// Default argument array
	$default = array(
		'post_type'      => fiscaat_get_account_post_type(), // Narrow query down to Fiscaat accounts
		'post_parent'    => $default_post_parent,            // Year ID
		'post_status'    => $default_post_status,            // Post Status
		'order'          => 'DESC',                          // 'ASC', 'DESC'
		'posts_per_page' => fiscaat_get_accounts_per_page(), // Accounts per page
		'paged'          => fiscaat_get_paged(),             // Page Number
		's'              => $default_account_search,         // Account Search
		'max_num_pages'  => false,                           // Maximum number of pages to show
	);

	$fiscaat_t = fiscaat_parse_args( $args, $default, 'has_accounts' );

	// Extract the query variables
	extract( $fiscaat_t );

	// Get Fiscaat
	$fiscaat = fiscaat();

	// Call the query
	$fiscaat->account_query = new WP_Query( $fiscaat_t );

	// Set post_parent back to 0 if originally set to 'any'
	if ( 'any' == $fiscaat_t['post_parent'] )
		$fiscaat_t['post_parent'] = $post_parent = 0;

	// Limited the number of pages shown
	if ( ! empty( $max_num_pages ) )
		$fiscaat->account_query->max_num_pages = $max_num_pages;

	// If no limit to posts per page, set it to the current post_count
	if ( -1 == $posts_per_page )
		$posts_per_page = $fiscaat->account_query->post_count;

	// Add pagination values to query object
	$fiscaat->account_query->posts_per_page = $posts_per_page;
	$fiscaat->account_query->paged          = $paged;

	// Only add pagination if query returned results
	if ( ( (int) $fiscaat->account_query->post_count || (int) $fiscaat->account_query->found_posts ) && (int) $fiscaat->account_query->posts_per_page ) {

		// Limit the number of accounts shown based on maximum allowed pages
		if ( ( ! empty( $max_num_pages ) ) && $fiscaat->account_query->found_posts > $fiscaat->account_query->max_num_pages * $fiscaat->account_query->post_count )
			$fiscaat->account_query->found_posts = $fiscaat->account_query->max_num_pages * $fiscaat->account_query->post_count;

		// If pretty permalinks are enabled, make our pagination pretty
		if ( $wp_rewrite->using_permalinks() ) {

			// Page or single post
			if ( is_page() || is_single() ) {
				$base = get_permalink();

			// Account archive
			} elseif ( fiscaat_is_account_archive() ) {
				$base = fiscaat_get_accounts_url();

			// Default
			} else {
				$base = get_permalink( $post_parent );
			}

			// Use pagination base
			$base = trailingslashit( $base ) . user_trailingslashit( $wp_rewrite->pagination_base . '/%#%/' );

		// Unpretty pagination
		} else {
			$base = add_query_arg( 'paged', '%#%' );
		}

		// Pagination settings with filter
		$fiscaat_account_pagination = apply_filters( 'fiscaat_account_pagination', array (
			'base'      => $base,
			'format'    => '',
			'total'     => $posts_per_page == $fiscaat->account_query->found_posts ? 1 : ceil( (int) $fiscaat->account_query->found_posts / (int) $posts_per_page ),
			'current'   => (int) $fiscaat->account_query->paged,
			'prev_text' => '&larr;',
			'next_text' => '&rarr;',
			'mid_size'  => 1
		) );

		// Add pagination to query object
		$fiscaat->account_query->pagination_links = paginate_links ( $fiscaat_account_pagination );

		// Remove first page from pagination
		$fiscaat->account_query->pagination_links = str_replace( $wp_rewrite->pagination_base . "/1/'", "'", $fiscaat->account_query->pagination_links );
	}

	// Return object
	return apply_filters( 'fiscaat_has_accounts', $fiscaat->account_query->have_posts(), $fiscaat->account_query );
}

/**
 * Whether there are more accounts available in the loop
 *
 * @uses WP_Query Fiscaat::account_query::have_posts()
 * @return object Account information
 */
function fiscaat_accounts() {

	// Put into variable to check against next
	$have_posts = fiscaat()->account_query->have_posts();

	// Reset the post data when finished
	if ( empty( $have_posts ) )
		wp_reset_postdata();

	return $have_posts;
}

/**
 * Loads up the current account in the loop
 *
 * @uses WP_Query Fiscaat::account_query::the_post()
 * @return object Account information
 */
function fiscaat_the_account() {
	return fiscaat()->account_query->the_post();
}

/**
 * Output the account id
 *
 * @uses fiscaat_get_account_id() To get the account id
 */
function fiscaat_account_id( $account_id = 0) {
	echo fiscaat_get_account_id( $account_id );
}
	/**
	 * Return the account id
	 *
	 * @param $account_id Optional. Used to check emptiness
	 * @uses Fiscaat::account_query::post::ID To get the account id
	 * @uses fiscaat_is_single_account() To check if it's an account page
	 * @uses fiscaat_is_account_edit() To check if it's an account edit page
	 * @uses fiscaat_is_single_record() To check if it it's a record page
	 * @uses fiscaat_is_record_edit() To check if it's a record edit page
	 * @uses fiscaat_get_record_account_edit() To get the record account id
	 * @uses get_post_field() To get the post's post type
	 * @uses WP_Query::post::ID To get the account id
	 * @uses fiscaat_get_account_post_type() To get the account post type
	 * @uses apply_filters() Calls 'fiscaat_get_account_id' with the account id and
	 *                        supplied account id
	 * @return int The account id
	 */
	function fiscaat_get_account_id( $account_id = 0 ) {
		global $wp_query;

		$fiscaat = fiscaat();

		// Easy empty checking
		if ( ! empty( $account_id ) && is_numeric( $account_id ) )
			$fiscaat_account_id = $account_id;

		// Currently inside an account loop
		elseif ( ! empty( $fiscaat->account_query->in_the_loop ) && isset( $fiscaat->account_query->post->ID ) )
			$fiscaat_account_id = $fiscaat->account_query->post->ID;

		// Currently viewing a year
		elseif ( ( fiscaat_is_single_account() || fiscaat_is_account_edit() ) && ! empty( $fiscaat->current_account_id ) )
			$fiscaat_account_id = $fiscaat->current_account_id;

		// Currently viewing an account
		elseif ( ( fiscaat_is_single_account() || fiscaat_is_account_edit() ) && isset( $wp_query->post->ID ) )
			$fiscaat_account_id = $wp_query->post->ID;

		// Currently viewing an account
		elseif ( fiscaat_is_single_record() )
			$fiscaat_account_id = fiscaat_get_record_account_id();

		// Fallback
		else
			$fiscaat_account_id = 0;

		return (int) apply_filters( 'fiscaat_get_account_id', (int) $fiscaat_account_id, $account_id );
	}

/**
 * Return the account id of an account using the ledger id and year id
 * 
 * @param int $ledger_id Ledger id
 * @param int $year_id Optional. Year id. Defaults to current year
 * @uses fiscaat_get_year_id()
 * @uses fiscaat_get_ledger_id()
 * @uses fiscaat_get_account_post_type()
 * @uses apply_filters() Calls 'fiscaat_get_account_id_by_ledger_id' with
 *                        account id, ledger id, and year id
 * @return int Account's account id
 */
function fiscaat_get_account_id_by_ledger_id( $ledger_id, $year_id = 0 ) {
	$year_id    = fiscaat_get_year_id( $year_id );
	$ledger_id  = (int) $ledger_id;
	$account_id = 0;

	// Query for account with params
	if ( $accounts = new WP_Query( array(
		'post_type'      => fiscaat_get_account_post_type(),
		'post_parent'    => $year_id,
		'posts_per_page' => 1,
		'meta_key'       => '_fiscaat_ledger_id',
		'meta_value'     => $ledger_id,
		'fields'         => 'ids',
	) ) ) {
		foreach ( $accounts as $account )
			$account_id = fiscaat_get_account_id( $account );
	}

	return (int) apply_filters( 'fiscaat_get_account_id_by_ledger_id', (int) $account_id, $ledger_id, $year_id );
}

/**
 * Gets an account
 *
 * @param int|object $account Account id or account object
 * @param string $output Optional. OBJECT, ARRAY_A, or ARRAY_N. Default = OBJECT
 * @param string $filter Optional Sanitation filter. See {@link sanitize_post()}
 * @uses get_post() To get the account
 * @uses apply_filters() Calls 'fiscaat_get_account' with the account, output type and
 *                        sanitation filter
 * @return mixed Null if error or account (in specified form) if success
 */
function fiscaat_get_account( $account, $output = OBJECT, $filter = 'raw' ) {

	// Use account ID
	if ( empty( $account ) || is_numeric( $account ) )
		$account = fiscaat_get_account_id( $account );

	// Attempt to load the account
	$account = get_post( $account, OBJECT, $filter );
	if ( empty( $account ) )
		return $account;

	// Bail if post_type is not an account
	if ( $account->post_type !== fiscaat_get_account_post_type() )
		return null;

	// Tweak the data type to return
	if ( $output == OBJECT ) {
		return $account;

	} elseif ( $output == ARRAY_A ) {
		$_account = get_object_vars( $account );
		return $_account;

	} elseif ( $output == ARRAY_N ) {
		$_account = array_values( get_object_vars( $account ) );
		return $_account;

	}

	return apply_filters( 'fiscaat_get_account', $account, $output, $filter );
}

/**
 * Output the link to the account in the account loop
 *
 * @param int $account_id Optional. Account id
 * @param $string $redirect_to Optional. Pass a redirect value for use with
 *                              shortcodes and other fun things.
 * @uses fiscaat_get_account_permalink() To get the account permalink
 */
function fiscaat_account_permalink( $account_id = 0, $redirect_to = '' ) {
	echo fiscaat_get_account_permalink( $account_id, $redirect_to );
}
	/**
	 * Return the link to the account
	 *
	 * @param int $account_id Optional. Account id
	 * @param $string $redirect_to Optional. Pass a redirect value for use with
	 *                              shortcodes and other fun things.
	 * @uses fiscaat_get_account_id() To get the account id
	 * @uses get_permalink() To get the account permalink
	 * @uses esc_url_raw() To clean the redirect_to url
	 * @uses apply_filters() Calls 'fiscaat_get_account_permalink' with the link
	 *                        and account id
	 * @return string Permanent link to account
	 */
	function fiscaat_get_account_permalink( $account_id = 0, $redirect_to = '' ) {
		$account_id = fiscaat_get_account_id( $account_id );

		// Use the redirect address
		if ( ! empty( $redirect_to ) ) {
			$account_permalink = esc_url_raw( $redirect_to );

		// Use the account permalink
		} else {
			$account_permalink = get_permalink( $account_id );
		}

		return apply_filters( 'fiscaat_get_account_permalink', $account_permalink, $account_id );
	}

/**
 * Output the title of the account
 *
 * @param int $account_id Optional. Account id
 * @uses fiscaat_get_account_title() To get the account title
 */
function fiscaat_account_title( $account_id = 0 ) {
	echo fiscaat_get_account_title( $account_id );
}
	/**
	 * Return the title of the account
	 *
	 * @param int $account_id Optional. Account id
	 * @uses fiscaat_get_account_id() To get the account id
	 * @uses get_the_title() To get the title
	 * @uses apply_filters() Calls 'fiscaat_get_account_title' with the title and
	 *                        account id
	 * @return string Title of account
	 */
	function fiscaat_get_account_title( $account_id = 0 ) {
		$account_id = fiscaat_get_account_id( $account_id );
		$title      = get_the_title( $account_id );

		return apply_filters( 'fiscaat_get_account_title', $title, $account_id );
	}

/**
 * Output the account archive title
 *
 * @param string $title Default text to use as title
 */
function fiscaat_account_archive_title( $title = '' ) {
	echo fiscaat_get_account_archive_title( $title );
}
	/**
	 * Return the account archive title
	 *
	 * @param string $title Default text to use as title
	 *
	 * @uses fiscaat_get_page_by_path() Check if page exists at root path
	 * @uses get_the_title() Use the page title at the root path
	 * @uses get_post_type_object() Load the post type object
	 * @uses fiscaat_get_account_post_type() Get the account post type ID
	 * @uses get_post_type_labels() Get labels for account post type
	 * @uses apply_filters() Allow output to be manipulated
	 *
	 * @return string The account archive title
	 */
	function fiscaat_get_account_archive_title( $title = '' ) {

		// If no title was passed
		if ( empty( $title ) ) {

			// Set root text to page title
			$page = fiscaat_get_page_by_path( fiscaat_get_account_archive_slug() );
			if ( ! empty( $page ) ) {
				$title = get_the_title( $page->ID );

			// Default to account post type name label
			} else {
				$tto    = get_post_type_object( fiscaat_get_account_post_type() );
				$title  = $tto->labels->name;
			}
		}

		return apply_filters( 'fiscaat_get_account_archive_title', $title );
	}

/**
 * Output the content of the account
 *
 * @param int $account_id Optional. Account id
 * @uses fiscaat_get_account_content() To get the account content
 */
function fiscaat_account_content( $account_id = 0 ) {
	echo fiscaat_get_account_content( $account_id );
}
	/**
	 * Return the content of the account
	 *
	 * @param int $account_id Optional. Account id
	 * @uses fiscaat_get_account_id() To get the account id
	 * @uses post_password_required() To check if the account requires pass
	 * @uses get_the_password_form() To get the password form
	 * @uses get_post_field() To get the content post field
	 * @uses apply_filters() Calls 'fiscaat_get_account_content' with the content
	 *                        and account id
	 * @return string Content of the account
	 */
	function fiscaat_get_account_content( $account_id = 0 ) {
		$account_id = fiscaat_get_account_id( $account_id );
		$content    = get_post_field( 'post_content', $account_id );

		return apply_filters( 'fiscaat_get_account_content', $content, $account_id );
	}

/**
 * Output pagination links of an account within the account loop
 *
 * @param mixed $args See {@link fiscaat_get_account_pagination()}
 * @uses fiscaat_get_account_pagination() To get the account pagination links
 */
function fiscaat_account_pagination( $args = '' ) {
	echo fiscaat_get_account_pagination( $args );
}
	/**
	 * Returns pagination links of an account within the account loop
	 *
	 * @param mixed $args This function supports these arguments:
	 *  - account_id: Account id
	 *  - before: Before the links
	 *  - after: After the links
	 * @uses fiscaat_get_account_id() To get the account id
	 * @uses WP_Rewrite::using_permalinks() To check if the blog is using
	 *                                       permalinks
	 * @uses user_trailingslashit() To add a trailing slash
	 * @uses trailingslashit() To add a trailing slash
	 * @uses get_permalink() To get the permalink of the account
	 * @uses add_query_arg() To add query args
	 * @uses fiscaat_get_account_record_count() To get account record count
	 * @uses fiscaat_show_account_lead() Are we showing the account as a lead?
	 * @uses get_option() To get records per page option
	 * @uses paginate_links() To paginate the links
	 * @uses apply_filters() Calls 'fiscaat_get_account_pagination' with the links
	 *                        and arguments
	 * @return string Pagination links
	 */
	function fiscaat_get_account_pagination( $args = '' ) {
		global $wp_rewrite;

		$defaults = array(
			'account_id' => fiscaat_get_account_id(),
			'before'   => '<span class="fiscaat-account-pagination">',
			'after'    => '</span>',
		);
		$r = fiscaat_parse_args( $args, $defaults, 'get_account_pagination' );
		extract( $r );

		// If pretty permalinks are enabled, make our pagination pretty
		if ( $wp_rewrite->using_permalinks() )
			$base = trailingslashit( get_permalink( $account_id ) ) . user_trailingslashit( $wp_rewrite->pagination_base . '/%#%/' );
		else
			$base = add_query_arg( 'paged', '%#%', get_permalink( $account_id ) );

		// Get total and add 1 if account is included in the record loop
		$total = fiscaat_get_account_record_count( $account_id, true );

		// Pagination settings
		$pagination = array(
			'base'      => $base,
			'format'    => '',
			'total'     => ceil( (int) $total / (int) fiscaat_get_records_per_page() ),
			'current'   => 0,
			'prev_next' => false,
			'mid_size'  => 2,
			'end_size'  => 3,
			'add_args'  => ( fiscaat_get_view_all() ) ? array( 'view' => 'all' ) : false
		);

		// Add pagination to query object
		$pagination_links = paginate_links( $pagination );
		if ( ! empty( $pagination_links ) ) {

			// Remove first page from pagination
			if ( $wp_rewrite->using_permalinks() ) {
				$pagination_links = str_replace( $wp_rewrite->pagination_base . '/1/', '', $pagination_links );
			} else {
				$pagination_links = str_replace( '&#038;paged=1', '', $pagination_links );
			}

			// Add before and after to pagination links
			$pagination_links = $before . $pagination_links . $after;
		}

		return apply_filters( 'fiscaat_get_account_pagination', $pagination_links, $args );
	}

/**
 * Output the status of the account
 *
 * @param int $account_id Optional. Account id
 * @uses fiscaat_get_account_status() To get the account status
 */
function fiscaat_account_status( $account_id = 0 ) {
	echo fiscaat_get_account_status( $account_id );
}
	/**
	 * Return the status of the account
	 *
	 * @param int $account_id Optional. Account id
	 * @uses fiscaat_get_account_id() To get the account id
	 * @uses get_post_status() To get the account status
	 * @uses apply_filters() Calls 'fiscaat_get_account_status' with the status
	 *                        and account id
	 * @return string Status of account
	 */
	function fiscaat_get_account_status( $account_id = 0 ) {
		$account_id = fiscaat_get_account_id( $account_id );

		return apply_filters( 'fiscaat_get_account_status', get_post_status( $account_id ), $account_id );
	}

/**
 * Is the account open to new records?
 *
 * @param int $account_id Optional. Account id
 * @uses fiscaat_is_account_closed() To check if the account is closed
 * @return bool True if open, false if closed.
 */
function fiscaat_is_account_open( $account_id = 0 ) {
	return !fiscaat_is_account_closed( $account_id );
}

	/**
	 * Is the account closed to new records?
	 *
	 * @param int $account_id Optional. Account id
	 * @uses fiscaat_get_account_status() To get the account status
	 * @uses apply_filters() Calls 'fiscaat_is_account_closed' with the account id
	 *
	 * @return bool True if closed, false if not.
	 */
	function fiscaat_is_account_closed( $account_id = 0 ) {
		$closed = fiscaat_get_account_status( $account_id ) == fiscaat_get_closed_status_id();
		return (bool) apply_filters( 'fiscaat_is_account_closed', (bool) $closed, $account_id );
	}

/**
 * Is the account published?
 *
 * @param int $account_id Optional. Account id
 * @uses fiscaat_get_account_id() To get the account id
 * @uses fiscaat_get_account_status() To get the account status
 * @uses apply_filters() Calls 'fiscaat_is_account_published' with the account id
 * @return bool True if published, false if not.
 */
function fiscaat_is_account_published( $account_id = 0 ) {
	$account_status = fiscaat_get_account_status( fiscaat_get_account_id( $account_id ) ) == fiscaat_get_public_status_id();
	return (bool) apply_filters( 'fiscaat_is_account_published', (bool) $account_status, $account_id );
}

/**
 * Output the author of the account
 *
 * @param int $account_id Optional. Account id
 * @uses fiscaat_get_account_author() To get the account author
 */
function fiscaat_account_author( $account_id = 0 ) {
	echo fiscaat_get_account_author( $account_id );
}
	/**
	 * Return the author of the account
	 *
	 * @param int $account_id Optional. Account id
	 * @uses fiscaat_get_account_id() To get the account id
	 * @uses fiscaat_get_account_author_id() To get the account author id
	 * @uses get_the_author_meta() To get the display name of the author
	 * @uses apply_filters() Calls 'fiscaat_get_account_author' with the author
	 *                        and account id
	 * @return string Author of account
	 */
	function fiscaat_get_account_author( $account_id = 0 ) {
		$account_id = fiscaat_get_account_id( $account_id );
		$author     = get_the_author_meta( 'display_name', fiscaat_get_account_author_id( $account_id ) );

		return apply_filters( 'fiscaat_get_account_author', $author, $account_id );
	}

/**
 * Output the author ID of the account
 *
 * @param int $account_id Optional. Account id
 * @uses fiscaat_get_account_author_id() To get the account author id
 */
function fiscaat_account_author_id( $account_id = 0 ) {
	echo fiscaat_get_account_author_id( $account_id );
}
	/**
	 * Return the author ID of the account
	 *
	 * @param int $account_id Optional. Account id
	 * @uses fiscaat_get_account_id() To get the account id
	 * @uses get_post_field() To get the account author id
	 * @uses apply_filters() Calls 'fiscaat_get_account_author_id' with the author
	 *                        id and account id
	 * @return string Author of account
	 */
	function fiscaat_get_account_author_id( $account_id = 0 ) {
		$account_id = fiscaat_get_account_id( $account_id );
		$author_id  = get_post_field( 'post_author', $account_id );

		return (int) apply_filters( 'fiscaat_get_account_author_id', (int) $author_id, $account_id );
	}

/**
 * Output the title of the year an account belongs to
 *
 * @param int $account_id Optional. Account id
 * @uses fiscaat_get_account_year_title() To get the account's year title
 */
function fiscaat_account_year_title( $account_id = 0 ) {
	echo fiscaat_get_account_year_title( $account_id );
}
	/**
	 * Return the title of the year an account belongs to
	 *
	 * @param int $account_id Optional. Account id
	 * @uses fiscaat_get_account_id() To get account id
	 * @uses fiscaat_get_account_year_id() To get account's year id
	 * @uses apply_filters() Calls 'fiscaat_get_account_year' with the year
	 *                        title and account id
	 * @return string Account year title
	 */
	function fiscaat_get_account_year_title( $account_id = 0 ) {
		$account_id = fiscaat_get_account_id( $account_id );
		$year_id    = fiscaat_get_account_year_id( $account_id );

		return apply_filters( 'fiscaat_get_account_year', fiscaat_get_year_title( $year_id ), $account_id, $year_id );
	}

/**
 * Output the year id an account belongs to
 *
 * @param int $account_id Optional. Account id
 * @uses fiscaat_get_account_year_id()
 */
function fiscaat_account_year_id( $account_id = 0 ) {
	echo fiscaat_get_account_year_id( $account_id );
}
	/**
	 * Return the year id an account belongs to
	 *
	 * @param int $account_id Optional. Account id
	 * @uses fiscaat_get_account_id() To get account id
	 * @uses fiscaat_get_account_meta() To retrieve get account's year id meta
	 * @uses apply_filters() Calls 'fiscaat_get_account_year_id' with the year
	 *                        id and account id
	 * @return int Account year id
	 */
	function fiscaat_get_account_year_id( $account_id = 0 ) {
		$account_id = fiscaat_get_account_id( $account_id );
		$year_id    = (int) fiscaat_get_account_meta( $account_id, 'year_id' );

		return (int) apply_filters( 'fiscaat_get_account_year_id', $year_id, $account_id );
	}

/**
 * Output the ledger id of an account
 *
 * @param int $account_id Optional. Account id
 * @uses fiscaat_get_account_ledger_id()
 */
function fiscaat_account_ledger_id( $account_id = 0 ) {
	echo fiscaat_get_account_ledger_id( $account_id );
}
	/**
	 * Return the ledger id of an account
	 *
	 * @param int $account_id Optional. Account id
	 * @uses fiscaat_get_account_id() To get account id
	 * @uses fiscaat_get_account_meta() To retrieve get account's ledger id meta
	 * @uses apply_filters() Calls 'fiscaat_get_account_ledger_id' with the year
	 *                        id and account id
	 * @return int Account's ledger id
	 */
	function fiscaat_get_account_ledger_id( $account_id = 0 ) {
		$account_id = fiscaat_get_account_id( $account_id );
		$ledger_id  = (int) fiscaat_get_account_meta( $account_id, 'ledger_id' );

		return (int) apply_filters( 'fiscaat_get_account_ledger_id', $ledger_id, $account_id );
	}

/**
 * Output the account type of an account
 *
 * @param int $account_id Optional. Account id
 * @uses fiscaat_get_account_account_type()
 */
function fiscaat_account_account_type( $account_id = 0 ) {
	echo fiscaat_get_account_account_type( $account_id );
}
	/**
	 * Return the account type of an account
	 *
	 * @param int $account_id Optional. Account id
	 * @uses fiscaat_get_account_id() To get account id
	 * @uses fiscaat_get_account_meta() To retrieve get account's account type meta
	 * @uses apply_filters() Calls 'fiscaat_get_account_account_type' with the year
	 *                        id and account id
	 * @return int Account's account type
	 */
	function fiscaat_get_account_account_type( $account_id = 0 ) {
		$account_id   = fiscaat_get_account_id( $account_id );
		$account_type = fiscaat_get_account_meta( $account_id, 'account_type' );

		return apply_filters( 'fiscaat_get_account_account_type', $account_type, $account_id );
	}

/**
 * Output the from value of an account
 *
 * @param int $account_id Optional. Account id
 * @uses fiscaat_get_account_from_value()
 */
function fiscaat_account_from_value( $account_id = 0 ) {
	echo fiscaat_get_account_from_value( $account_id );
}
	/**
	 * Return the from value of an account
	 *
	 * @param int $account_id Optional. Account id
	 * @uses fiscaat_get_account_id() To get account id
	 * @uses fiscaat_get_account_meta() To retrieve get account's from value meta
	 * @uses apply_filters() Calls 'fiscaat_get_account_from_value' with the year
	 *                        id and account id
	 * @return int Account's from value
	 */
	function fiscaat_get_account_from_value( $account_id = 0 ) {
		$account_id = fiscaat_get_account_id( $account_id );
		$from_value = (float) fiscaat_get_account_meta( $account_id, 'from_value' );

		return (float) apply_filters( 'fiscaat_get_account_from_value', $from_value, $account_id );
	}

/**
 * Output the to value of an account
 *
 * @param int $account_id Optional. Account id
 * @uses fiscaat_get_account_to_value()
 */
function fiscaat_account_to_value( $account_id = 0 ) {
	echo fiscaat_get_account_to_value( $account_id );
}
	/**
	 * Return the to value of an account
	 *
	 * @param int $account_id Optional. Account id
	 * @uses fiscaat_get_account_id() To get account id
	 * @uses fiscaat_get_account_meta() To retrieve get account's to value meta
	 * @uses apply_filters() Calls 'fiscaat_get_account_to_value' with the year
	 *                        id and account id
	 * @return int Account's to value
	 */
	function fiscaat_get_account_to_value( $account_id = 0 ) {
		$account_id = fiscaat_get_account_id( $account_id );
		$to_value   = (float) fiscaat_get_account_meta( $account_id, 'to_value' );

		return (float) apply_filters( 'fiscaat_get_account_to_value', $to_value, $account_id );
	}

/**
 * Return the spectator ids of an account
 *
 * @param int $account_id Optional. Account id
 * @uses fiscaat_get_account_id() To get account id
 * @uses fiscaat_get_account_meta() To retrieve get account's spectator ids meta
 * @uses apply_filters() Calls 'fiscaat_get_account_spectators' with the year
 *                        id and account id
 * @return int Account's spectator ids
 */
function fiscaat_get_account_spectators( $account_id = 0 ) {
	$account_id = fiscaat_get_account_id( $account_id );
	$spectators = fiscaat_get_account_meta( $account_id, 'spectators' );

	return (array) apply_filters( 'fiscaat_get_account_spectators', $spectators, $account_id );
}

/** Account Records **************************************************************/

/**
 * Output the records link of the account
 *
 * @param int $account_id Optional. Account id
 * @uses fiscaat_get_account_records_link() To get the account records link
 */
function fiscaat_account_records_link( $account_id = 0 ) {
	echo fiscaat_get_account_records_link( $account_id );
}

	/**
	 * Return the records link of the account
	 *
	 * @param int $account_id Optional. Account id
	 * @uses fiscaat_get_account_id() To get the account id
	 * @uses fiscaat_get_account_record_count() To get the account record count
	 * @uses fiscaat_get_account_permalink() To get the account permalink
	 * @uses remove_view_all() To remove view all args from the url
	 * @uses fiscaat_get_view_all() To check if the current user can edit others
	 *                           records
	 * @uses apply_filters() Calls 'fiscaat_get_account_records_link' with the
	 *                        records link and account id
	 */
	function fiscaat_get_account_records_link( $account_id = 0 ) {

		$account_id   = fiscaat_get_account_id( $account_id );
		$record_count = fiscaat_get_account_record_count( $account_id, true );
		$records      = sprintf( _n( '%s record', '%s records', $record_count, 'fiscaat' ), $record_count );
		$retval       = '';

		// First link never has view=all
		if ( fiscaat_get_view_all( 'edit_others_records' ) )
			$retval .= "<a href='" . esc_url( fiscaat_remove_view_all( fiscaat_get_account_permalink( $account_id ) ) ) . "'>$records</a>";
		else
			$retval .= $records;

		return apply_filters( 'fiscaat_get_account_records_link', $retval, $account_id );
	}

/**
 * Output the records admin link of the account
 * 
 * @param int $account_id Optional. Account id
 * @param bool $number Optional. Output account number instead of account title
 * @uses fiscaat_get_account_records_admin_link() To get the admin link
 */
function fiscaat_account_records_admin_link( $account_id = 0, $number = false ) {
	echo fiscaat_get_account_records_admin_link( $account_id, $number );
}
	/**
	 * Return the records admin link of the account
	 *
	 * @param int $account_id Optional. Account id
	 * @param bool $number Optional. Output account number instead of account title
	 * @uses fiscaat_get_account_id() To get the account id
	 * @uses add_query_arg() To build the admin link
	 * @uses remove_view_all() To remove view all args from the url
	 * @uses fiscaat_get_view_all() To check if the current user can edit others
	 *                           records
	 * @uses apply_filters() Calls 'fiscaat_get_account_records_link' with the
	 *                        records link and account id
	 */
	function fiscaat_get_account_records_admin_link( $account_id = 0, $number = false ) {
		
		$account_id = fiscaat_get_account_id( $account_id );
		$title      = ! $number ? fiscaat_get_account_title( $account_id ) : fiscaat_get_account_ledger_id( $account_id );
		$retval     = '';

		// First link never has view=all
		// if ( fiscaat_get_view_all( 'edit_others_records' ) )
			$retval .= "<a href='" . esc_url( fiscaat_remove_view_all( add_query_arg( array( 'post_type' => fiscaat_get_record_post_type(), 'fiscaat_account_id' => $account_id ), admin_url( 'edit.php' ) ) ) ) . "'>$title</a>";
		// else
			// $retval .= $title;

		return apply_filters( 'fiscaat_get_account_records_admin_link', $retval, $account_id, $number );
	}

/**
 * Output total record count of an account
 *
 * @param int $account_id Optional. Account id
 * @param boolean $integer Optional. Whether or not to format the result
 * @uses fiscaat_get_account_record_count() To get the account record count
 */
function fiscaat_account_record_count( $account_id = 0, $integer = false ) {
	echo fiscaat_get_account_record_count( $account_id, $integer );
}
	/**
	 * Return total record count of an account
	 *
	 * @param int $account_id Optional. Account id
	 * @param boolean $integer Optional. Whether or not to format the result
	 * @uses fiscaat_get_account_id() To get the account id
	 * @uses fiscaat_get_account_meta() To get the account record count meta
	 * @uses apply_filters() Calls 'fiscaat_get_account_record_count' with the
	 *                        record count and account id
	 * @return int Record count
	 */
	function fiscaat_get_account_record_count( $account_id = 0, $integer = false ) {
		$account_id = fiscaat_get_account_id( $account_id );
		$records    = (int) fiscaat_get_account_meta( $account_id, 'record_count' );
		$filter     = ( true === $integer ) ? 'fiscaat_get_account_record_count_int' : 'fiscaat_get_account_record_count';

		return apply_filters( $filter, $records, $account_id );
	}

/**
 * Output total declined record count of an account 
 *
 * @param int $account_id Optional. Account id
 * @param boolean $integer Optional. Whether or not to format the result
 * @uses fiscaat_get_account_record_count_declined() To get the account declined record count
 */
function fiscaat_account_record_count_declined( $account_id = 0, $integer = false ) {
	echo fiscaat_get_account_record_count_declined( $account_id, $integer );
}
	/**
	 * Return total declined record count of an account 
	 *
	 * @param int $account_id Optional. Account id
	 * @param boolean $integer Optional. Whether or not to format the result
	 * @uses fiscaat_get_account_id() To get the account id
	 * @uses fiscaat_get_account_meta() To get the declined record count
	 * @uses apply_filters() Calls 'fiscaat_get_account_record_count_declined' with
	 *                        the declined record count and account id
	 * @return int Account declined record count
	 */
	function fiscaat_get_account_record_count_declined( $account_id = 0, $integer = false ) {
		$account_id = fiscaat_get_account_id( $account_id );
		$records    = (int) fiscaat_get_account_meta( $account_id, 'record_count_declined' );
		$filter     = ( true === $integer ) ? 'fiscaat_get_account_record_count_declined_int' : 'fiscaat_get_account_record_count_declined';

		return apply_filters( $filter, $records, $account_id );
	}

/**
 * Output total unapproved record count of an account 
 *
 * @param int $account_id Optional. Account id
 * @param boolean $integer Optional. Whether or not to format the result
 * @uses fiscaat_get_account_record_count_unapproved() To get the account unapproved record count
 */
function fiscaat_account_record_count_unapproved( $account_id = 0, $integer = false ) {
	echo fiscaat_get_account_record_count_unapproved( $account_id, $integer );
}
	/**
	 * Return total unapproved record count of an account 
	 *
	 * @param int $account_id Optional. Account id
	 * @param boolean $integer Optional. Whether or not to format the result
	 * @uses fiscaat_get_account_id() To get the account id
	 * @uses fiscaat_get_account_meta() To get the unapproved record count
	 * @uses apply_filters() Calls 'fiscaat_get_account_record_count_unapproved' with
	 *                        the unapproved record count and account id
	 * @return int Account unapproved record count
	 */
	function fiscaat_get_account_record_count_unapproved( $account_id = 0, $integer = false ) {
		$account_id = fiscaat_get_account_id( $account_id );
		$records    = (int) fiscaat_get_account_meta( $account_id, 'record_count_unapproved' );
		$filter     = ( true === $integer ) ? 'fiscaat_get_account_record_count_unapproved_int' : 'fiscaat_get_account_record_count_unapproved';

		return apply_filters( $filter, $records, $account_id );
	}

/**
 * Output the row class of an account
 *
 * @param int $account_id Optional. Account id
 * @uses fiscaat_get_account_class() To get the account class
 */
function fiscaat_account_class( $account_id = 0 ) {
	echo fiscaat_get_account_class( $account_id );
}
	/**
	 * Return the row class of an account
	 *
	 * @param int $account_id Optional. Account id
	 * @uses fiscaat_is_account_sticky() To check if the account is a sticky
	 * @uses fiscaat_is_account_super_sticky() To check if the account is a super sticky
	 * @uses fiscaat_get_account_year_id() To get the account year id
	 * @uses get_post_class() To get the account classes
	 * @uses apply_filters() Calls 'fiscaat_get_account_class' with the classes
	 *                        and account id
	 * @return string Row class of an account
	 */
	function fiscaat_get_account_class( $account_id = 0 ) {
		$fiscaat    = fiscaat();
		$account_id = fiscaat_get_account_id( $account_id );
		$count      = isset( $fiscaat->account_query->current_post ) ? $fiscaat->account_query->current_post : 1;
		$classes    = array();
		$classes[]  = ( (int) $count % 2 ) ? 'even' : 'odd';
		$classes[]  = 'fiscaat-parent-year-' . fiscaat_get_account_year_id( $account_id );
		$classes[]  = fiscaat_is_account_closed( $account_id ) ? 'fiscaat-account-closed' : 'fiscaat-account-open';
		$classes    = array_filter( $classes );
		$classes    = get_post_class( $classes, $account_id );
		$classes    = apply_filters( 'fiscaat_get_account_class', $classes, $account_id );
		$retval     = 'class="' . join( ' ', $classes ) . '"';

		return $retval;
	}

/** Account Admin Links *********************************************************/

/**
 * Output admin links for account
 *
 * @param mixed $args See {@link fiscaat_get_account_admin_links()}
 * @uses fiscaat_get_account_admin_links() To get the account admin links
 */
function fiscaat_account_admin_links( $args = '' ) {
	echo fiscaat_get_account_admin_links( $args );
}
	/**
	 * Return admin links for account.
	 *
	 * Move account functionality is handled by the edit account page.
	 *
	 * @param mixed $args This function supports these arguments:
	 *  - id: Optional. Account id
	 *  - before: Before the links
	 *  - after: After the links
	 *  - sep: Links separator
	 *  - links: Account admin links array
	 * @uses current_user_can() To check if the current user can edit/delete
	 *                           the account
	 * @uses fiscaat_get_account_edit_link() To get the account edit link
	 * @uses fiscaat_get_account_trash_link() To get the account trash link
	 * @uses fiscaat_get_account_close_link() To get the account close link
	 * @uses fiscaat_get_account_spam_link() To get the account spam link
	 * @uses fiscaat_get_account_stick_link() To get the account stick link
	 * @uses fiscaat_get_account_merge_link() To get the account merge link
	 * @uses fiscaat_get_account_status() To get the account status
	 * @uses apply_filters() Calls 'fiscaat_get_account_admin_links' with the
	 *                        account admin links and args
	 * @return string Account admin links
	 */
	function fiscaat_get_account_admin_links( $args = '' ) {

		if ( !fiscaat_is_single_account() )
			return;

		$defaults = array (
			'id'     => fiscaat_get_account_id(),
			'before' => '<span class="fiscaat-admin-links">',
			'after'  => '</span>',
			'sep'    => ' | ',
			'links'  => array()
		);
		$r = fiscaat_parse_args( $args, $defaults, 'get_account_admin_links' );

		if ( !current_user_can( 'edit_account', $r['id'] ) )
			return;

		if ( empty( $r['links'] ) ) {
			$r['links'] = array(
				'edit'  => fiscaat_get_account_edit_link ( $r ),
				'close' => fiscaat_get_account_close_link( $r ),
			);
		}

		// Check caps for trashing the account
		if ( !current_user_can( 'delete_account', $r['id'] ) && ! empty( $r['links']['trash'] ) )
			unset( $r['links']['trash'] );

		// See if links need to be unset
		$account_status = fiscaat_get_account_status( $r['id'] );
		if ( in_array( $account_status, array( fiscaat_get_spam_status_id(), fiscaat_get_trash_status_id() ) ) ) {

			// Close link shouldn't be visible on trashed/spammed accounts
			unset( $r['links']['close'] );

			// Spam link shouldn't be visible on trashed accounts
			if ( $account_status == fiscaat_get_trash_status_id() )
				unset( $r['links']['spam'] );

			// Trash link shouldn't be visible on spam accounts
			elseif ( $account_status == fiscaat_get_spam_status_id() )
				unset( $r['links']['trash'] );
		}

		// Process the admin links
		$links = implode( $r['sep'], array_filter( $r['links'] ) );

		return apply_filters( 'fiscaat_get_account_admin_links', $r['before'] . $links . $r['after'], $args );
	}

/**
 * Output the edit link of the account
 *
 * @param mixed $args See {@link fiscaat_get_account_edit_link()}
 * @uses fiscaat_get_account_edit_link() To get the account edit link
 */
function fiscaat_account_edit_link( $args = '' ) {
	echo fiscaat_get_account_edit_link( $args );
}

	/**
	 * Return the edit link of the account
	 *
	 * @since Fiscaat (r2727)
	 *
	 * @param mixed $args This function supports these args:
	 *  - id: Optional. Account id
	 *  - link_before: Before the link
	 *  - link_after: After the link
	 *  - edit_text: Edit text
	 * @uses fiscaat_get_account_id() To get the account id
	 * @uses fiscaat_get_account() To get the account
	 * @uses current_user_can() To check if the current user can edit the
	 *                           account
	 * @uses fiscaat_get_account_edit_url() To get the account edit url
	 * @uses apply_filters() Calls 'fiscaat_get_account_edit_link' with the link
	 *                        and args
	 * @return string Account edit link
	 */
	function fiscaat_get_account_edit_link( $args = '' ) {
		$defaults = array (
			'id'           => 0,
			'link_before'  => '',
			'link_after'   => '',
			'edit_text'    => __( 'Edit', 'fiscaat' )
		);
		$r = fiscaat_parse_args( $args, $defaults, 'get_account_edit_link' );
		extract( $r );

		$account = fiscaat_get_account( fiscaat_get_account_id( (int) $id ) );

		// Bypass check if user has caps
		if ( !current_user_can( 'edit_others_accounts' ) ) {

			// User cannot edit or it is past the lock time
			if ( empty( $account ) || !current_user_can( 'edit_account', $account->ID ) || fiscaat_past_edit_lock( $account->post_date_gmt ) ) {
				return;
			}
		}

		// Get uri
		$uri = fiscaat_get_account_edit_url( $id );

		// Bail if no uri
		if ( empty( $uri ) )
			return;

		$retval = $link_before . '<a href="' . $uri . '">' . $edit_text . '</a>' . $link_after;

		return apply_filters( 'fiscaat_get_account_edit_link', $retval, $args );
	}

/**
 * Output URL to the account edit page
 *
 * @since Fiscaat (r2753)
 *
 * @param int $account_id Optional. Account id
 * @uses fiscaat_get_account_edit_url() To get the account edit url
 */
function fiscaat_account_edit_url( $account_id = 0 ) {
	echo fiscaat_get_account_edit_url( $account_id );
}
	/**
	 * Return URL to the account edit page
	 *
	 * @since Fiscaat (r2753)
	 *
	 * @param int $account_id Optional. Account id
	 * @uses fiscaat_get_account_id() To get the account id
	 * @uses fiscaat_get_account() To get the account
	 * @uses add_query_arg() To add custom args to the url
	 * @uses apply_filters() Calls 'fiscaat_get_account_edit_url' with the edit
	 *                        url and account id
	 * @return string Account edit url
	 */
	function fiscaat_get_account_edit_url( $account_id = 0 ) {
		global $wp_rewrite;

		$fiscaat = fiscaat();

		$account = fiscaat_get_account( fiscaat_get_account_id( $account_id ) );
		if ( empty( $account ) )
			return;

		// Remove view=all link from edit
		$account_link = fiscaat_remove_view_all( fiscaat_get_account_permalink( $account_id ) );

		// Pretty permalinks
		if ( $wp_rewrite->using_permalinks() ) {
			$url = trailingslashit( $account_link ) . $fiscaat->edit_id;
			$url = trailingslashit( $url );

		// Unpretty permalinks
		} else {
			$url = add_query_arg( array( fiscaat_get_account_post_type() => $account->post_name, $fiscaat->edit_id => '1' ), $account_link );
		}

		// Maybe add view=all
		$url = fiscaat_add_view_all( $url );

		return apply_filters( 'fiscaat_get_account_edit_url', $url, $account_id );
	}

/**
 * Output the close link of the account
 *
 * @since Fiscaat (r2727)
 *
 * @param mixed $args See {@link fiscaat_get_account_close_link()}
 * @uses fiscaat_get_account_close_link() To get the account close link
 */
function fiscaat_account_close_link( $args = '' ) {
	echo fiscaat_get_account_close_link( $args );
}

	/**
	 * Return the close link of the account
	 *
	 * @since Fiscaat (r2727)
	 *
	 * @param mixed $args This function supports these args:
	 *  - id: Optional. Account id
	 *  - link_before: Before the link
	 *  - link_after: After the link
	 *  - close_text: Close text
	 *  - open_text: Open text
	 * @uses fiscaat_get_account_id() To get the account id
	 * @uses fiscaat_get_account() To get the account
	 * @uses current_user_can() To check if the current user can edit the account
	 * @uses fiscaat_is_account_open() To check if the account is open
	 * @uses add_query_arg() To add custom args to the url
	 * @uses wp_nonce_url() To nonce the url
	 * @uses esc_url() To escape the url
	 * @uses apply_filters() Calls 'fiscaat_get_account_close_link' with the link
	 *                        and args
	 * @return string Account close link
	 */
	function fiscaat_get_account_close_link( $args = '' ) {
		$defaults = array (
			'id'          => 0,
			'link_before' => '',
			'link_after'  => '',
			'sep'         => ' | ',
			'close_text'  => _x( 'Close', 'Account Status', 'fiscaat' ),
			'open_text'   => _x( 'Open',  'Account Status', 'fiscaat' )
		);
		$r = fiscaat_parse_args( $args, $defaults, 'get_account_close_link' );
		extract( $r );

		$account = fiscaat_get_account( fiscaat_get_account_id( (int) $id ) );

		if ( empty( $account ) || !current_user_can( 'moderate', $account->ID ) )
			return;

		$display = fiscaat_is_account_open( $account->ID ) ? $close_text : $open_text;
		$uri     = add_query_arg( array( 'action' => 'fiscaat_toggle_account_close', 'account_id' => $account->ID ) );
		$uri     = esc_url( wp_nonce_url( $uri, 'close-account_' . $account->ID ) );
		$retval  = $link_before . '<a href="' . $uri . '">' . $display . '</a>' . $link_after;

		return apply_filters( 'fiscaat_get_account_close_link', $retval, $args );
	}

/** Account Pagination **********************************************************/

/**
 * Output the pagination count
 *
 * @since Fiscaat (r2519)
 *
 * @uses fiscaat_get_year_pagination_count() To get the year pagination count
 */
function fiscaat_year_pagination_count() {
	echo fiscaat_get_year_pagination_count();
}
	/**
	 * Return the pagination count
	 *
	 * @since Fiscaat (r2519)
	 *
	 * @uses fiscaat_number_format() To format the number value
	 * @uses apply_filters() Calls 'fiscaat_get_year_pagination_count' with the
	 *                        pagination count
	 * @return string Year Pagintion count
	 */
	function fiscaat_get_year_pagination_count() {
		$fiscaat = fiscaat();

		if ( empty( $fiscaat->account_query ) )
			return false;

		// Set pagination values
		$start_num = intval( ( $fiscaat->account_query->paged - 1 ) * $fiscaat->account_query->posts_per_page ) + 1;
		$from_num  = fiscaat_number_format( $start_num );
		$to_num    = fiscaat_number_format( ( $start_num + ( $fiscaat->account_query->posts_per_page - 1 ) > $fiscaat->account_query->found_posts ) ? $fiscaat->account_query->found_posts : $start_num + ( $fiscaat->account_query->posts_per_page - 1 ) );
		$total_int = (int) ! empty( $fiscaat->account_query->found_posts ) ? $fiscaat->account_query->found_posts : $fiscaat->account_query->post_count;
		$total     = fiscaat_number_format( $total_int );

		// Several accounts in a year with a single page
		if ( empty( $to_num ) ) {
			$retstr = sprintf( _n( 'Viewing %1$s account', 'Viewing %1$s accounts', $total_int, 'fiscaat' ), $total );

		// Several accounts in a year with several pages
		} else {
			$retstr = sprintf( _n( 'Viewing account %2$s (of %4$s total)', 'Viewing %1$s accounts - %2$s through %3$s (of %4$s total)', $total_int, 'fiscaat' ), $fiscaat->account_query->post_count, $from_num, $to_num, $total );
		}

		// Filter and return
		return apply_filters( 'fiscaat_get_account_pagination_count', $retstr );
	}

/**
 * Output pagination links
 *
 * @since Fiscaat (r2519)
 *
 * @uses fiscaat_get_year_pagination_links() To get the pagination links
 */
function fiscaat_year_pagination_links() {
	echo fiscaat_get_year_pagination_links();
}
	/**
	 * Return pagination links
	 *
	 * @since Fiscaat (r2519)
	 *
	 * @uses Fiscaat::account_query::pagination_links To get the links
	 * @return string Pagination links
	 */
	function fiscaat_get_year_pagination_links() {
		$fiscaat = fiscaat();

		if ( empty( $fiscaat->account_query ) )
			return false;

		return apply_filters( 'fiscaat_get_year_pagination_links', $fiscaat->account_query->pagination_links );
	}

/** Single Account **************************************************************/

/**
 * Output a fancy description of the current account, including total accounts,
 * total records, and last activity.
 *
 * @since Fiscaat (r2860)
 *
 * @param array $args See {@link fiscaat_get_single_account_description()}
 * @uses fiscaat_get_single_account_description() Return the eventual output
 */
function fiscaat_single_account_description( $args = '' ) {
	echo fiscaat_get_single_account_description( $args );
}
	/**
	 * Return a fancy description of the current account, including total accounts,
	 * total records, and last activity.
	 *
	 * @since Fiscaat (r2860)
	 *
	 * @param mixed $args This function supports these arguments:
	 *  - account_id: Account id
	 *  - before: Before the text
	 *  - after: After the text
	 *  - size: Size of the avatar
	 * @uses fiscaat_get_account_id() To get the account id
	 * @uses fiscaat_get_account_decline_count() To get the account voice count
	 * @uses fiscaat_get_account_record_count() To get the account record count
	 * @uses fiscaat_get_account_freshness_link() To get the account freshness link
	 * @uses fiscaat_get_account_last_active_id() To get the account last active id
	 * @uses fiscaat_get_record_author_link() To get the record author link
	 * @uses apply_filters() Calls 'fiscaat_get_single_account_description' with
	 *                        the description and args
	 * @return string Filtered account description
	 */
	function fiscaat_get_single_account_description( $args = '' ) {

		// Default arguments
		$defaults = array (
			'account_id'  => 0,
			'before'    => '<div class="fiscaat-template-notice info"><p class="fiscaat-account-description">',
			'after'     => '</p></div>',
			'size'      => 14
		);
		$r = fiscaat_parse_args( $args, $defaults, 'get_single_account_description' );
		extract( $r );

		// Validate account_id
		$account_id = fiscaat_get_account_id( $account_id );

		// Unhook the 'view all' query var adder
		remove_filter( 'fiscaat_get_account_permalink', 'fiscaat_add_view_all' );

		// Build the account description
		$record_count  = fiscaat_get_account_records_link  ( $account_id );
		$decline_count = fiscaat_get_account_decline_count ( $account_id );
		$time_since    = fiscaat_get_account_freshness_link( $account_id );

		// Singular/Plural
		$decline_count = sprintf( _n( '%s decline', '%s declines', $decline_count, 'fiscaat' ), $decline_count );

		// Account has records
		$last_record = fiscaat_get_account_last_active_id( $account_id );
		if ( ! empty( $last_record ) ) {
			$last_updated_by = fiscaat_get_author_link( array( 'post_id' => $last_record, 'size' => $size ) );
			$retstr          = sprintf( __( 'This account contains %1$s, has %2$s, and was last updated by %3$s %4$s.', 'fiscaat' ), $record_count, $decline_count, $last_updated_by, $time_since );

		// Account has no records
		} elseif ( ! empty( $decline_count ) && ! empty( $record_count ) ) {
			$retstr = sprintf( __( 'This account contains %1$s and has %2$s.', 'fiscaat' ), $decline_count, $record_count );

		// Account has no records and no declines
		} elseif ( empty( $decline_count ) && empty( $record_count ) ) {
			$retstr = sprintf( __( 'This account has no records.', 'fiscaat' ), $decline_count, $record_count );
		}

		// Add the 'view all' filter back
		add_filter( 'fiscaat_get_account_permalink', 'fiscaat_add_view_all' );

		// Combine the elements together
		$retstr = $before . $retstr . $after;

		// Return filtered result
		return apply_filters( 'fiscaat_get_single_account_description', $retstr, $args );
	}

/** Forms *********************************************************************/

/**
 * Output the value of account title field
 *
 * @uses fiscaat_get_form_account_title() To get the value of account title field
 */
function fiscaat_form_account_title() {
	echo fiscaat_get_form_account_title();
}
	/**
	 * Return the value of account title field
	 *
	 * @uses fiscaat_is_account_edit() To check if it's account edit page
	 * @uses apply_filters() Calls 'fiscaat_get_form_account_title' with the title
	 * @return string Value of account title field
	 */
	function fiscaat_get_form_account_title() {

		// Get _POST data
		if ( 'POST' == strtoupper( $_SERVER['REQUEST_METHOD'] ) && isset( $_POST['fiscaat_account_title'] ) )
			$account_title = $_POST['fiscaat_account_title'];

		// Get edit data
		elseif ( fiscaat_is_account_edit() )
			$account_title = fiscaat_get_global_post_field( 'post_title', 'raw' );

		// No data
		else
			$account_title = '';

		return apply_filters( 'fiscaat_get_form_account_title', esc_attr( $account_title ) );
	}

/**
 * Output the value of account content field
 *
 * @uses fiscaat_get_form_account_content() To get value of account content field
 */
function fiscaat_form_account_content() {
	echo fiscaat_get_form_account_content();
}
	/**
	 * Return the value of account content field
	 *
	 * @uses fiscaat_is_account_edit() To check if it's the account edit page
	 * @uses apply_filters() Calls 'fiscaat_get_form_account_content' with the content
	 * @return string Value of account content field
	 */
	function fiscaat_get_form_account_content() {

		// Get _POST data
		if ( 'POST' == strtoupper( $_SERVER['REQUEST_METHOD'] ) && isset( $_POST['fiscaat_account_content'] ) )
			$account_content = $_POST['fiscaat_account_content'];

		// Get edit data
		elseif ( fiscaat_is_account_edit() )
			$account_content = fiscaat_get_global_post_field( 'post_content', 'raw' );

		// No data
		else
			$account_content = '';

		return apply_filters( 'fiscaat_get_form_account_content', esc_textarea( $account_content ) );
	}

/**
 * Allow account rows to have adminstrative actions
 *
 * @uses do_action()
 * @todo Links and filter
 */
function fiscaat_account_row_actions() {
	do_action( 'fiscaat_account_row_actions' );
}

/**
 * Output value of account year
 *
 * @uses fiscaat_get_form_account_year() To get the account's year id
 */
function fiscaat_form_account_year() {
	echo fiscaat_get_form_account_year();
}
	/**
	 * Return value of account year
	 *
	 * @uses fiscaat_is_account_edit() To check if it's the account edit page
	 * @uses fiscaat_get_account_year_id() To get the account year id
	 * @uses apply_filters() Calls 'fiscaat_get_form_account_year' with the year
	 * @return string Value of account content field
	 */
	function fiscaat_get_form_account_year() {

		// Get _POST data
		if ( 'POST' == strtoupper( $_SERVER['REQUEST_METHOD'] ) && isset( $_POST['parent_id'] ) )
			$account_year = $_POST['parent_id'];

		// Get edit data
		elseif ( fiscaat_is_account_edit() )
			$account_year = fiscaat_get_account_year_id();

		// No data
		else
			$account_year = 0;

		return apply_filters( 'fiscaat_get_form_account_year', esc_attr( $account_year ) );
	}

/**
 * Output the account's account type select
 * 
 * @param int $account_id Optional. Account id
 * @uses fiscaat_get_form_account_account_type_select()
 */
function fiscaat_form_account_account_type_select( $account_id = 0 ) {
	echo fiscaat_get_form_account_account_type_select( $account_id );
}
	/**
	 * Return the account's account type select
	 * 
	 * @param int $account_id Optional. Account id
	 * @uses fiscaat_get_account_type()
	 * @uses fiscaat_get_account_account_type()
	 * @uses fiscaat_get_result_account_type()
	 * @uses fiscaat_get_asset_account_type()
	 * @uses apply_filters() Calls 'fiscaat_get_form_account_account_type_select' with
	 *                        account account type select, account id, and account types
	 * @return string Account's account type select
	 */
	function fiscaat_get_form_account_account_type_select( $account_id = 0 ) {
		$account_id   = fiscaat_get_account_id( $account_id );
		$account_type = fiscaat_get_account_account_type( $account_id );
		$types        = apply_filters( 'fiscaat_account_account_types', array(
			fiscaat_get_result_account_type() => __('Result',          'fiscaat'),
			fiscaat_get_asset_account_type()  => __('Asset/Liability', 'fiscaat'),
		) );

		// Disable select
		$disable = fiscaat_is_account_closed() ? true : false;

		$type_output = '<select name="fiscaat_account_account_type" id="fiscaat_account_account_type" '. disabled( $disable, true, false ) .'>' . "\n";

		foreach( $types as $value => $label )
			$type_output .= "\t" . '<option value="' . $value . '"' . selected( $account_type, $value, false ) . '>' . esc_html( $label ) . '</option>' . "\n";

		$type_output .= '</select>';

		return apply_filters( 'fiscaat_get_form_account_account_type_select', $type_output, $account_id, $types );
	}

/** Dropdowns *****************************************************************/

/**
 * Output a select box allowing to pick which account to show.
 *
 * @param mixed $args See {@link fiscaat_get_dropdown()} for arguments
 */

function fiscaat_account_dropdown( $args = '' ) {
	echo fiscaat_get_account_dropdown( $args );
}
	/**
	 * Return a select box allowing to pick which account to show.
	 * 
	 * @param mixed $args See {@link fiscaat_get_dropdown()} for arguments
	 * @return string The dropdown
	 */
	function fiscaat_get_account_dropdown( $args = '' ) {

		/** Arguments *********************************************************/

		$defaults = array (
			'post_type'          => fiscaat_get_account_post_type(),
			'selected'           => 0,
			'sort_column'        => 'title',
			'child_of'           => fiscaat_get_current_year_id(),
			'orderby'            => 'title',

			// Output-related
			'select_id'          => 'fiscaat_account_id',
			'show_none'          => __('In all accounts', 'fiscaat'),
		);

		$r = fiscaat_parse_args( $args, $defaults, 'get_account_dropdown' );

		/** Drop Down *********************************************************/

		$retval = fiscaat_get_dropdown( $r );

		return apply_filters( 'fiscaat_get_account_dropdown', $retval, $args );
	}

/**
 * Output a select box allowing to pick which account to show by ledger id
 *
 * @param mixed $args See {@link fiscaat_get_dropdown()} for arguments
 */

function fiscaat_ledger_dropdown( $args = '' ) {
	echo fiscaat_get_ledger_dropdown( $args );
}
	/**
	 * Return a select box allowing to pick which account to show ledger id
	 * 
	 * @param mixed $args See {@link fiscaat_get_dropdown()} for arguments
	 * @return string The dropdown
	 */
	function fiscaat_get_ledger_dropdown( $args = '' ) {

		/** Arguments *********************************************************/

		$defaults = array (
			'post_type'          => fiscaat_get_account_post_type(),
			'selected'           => 0,
			'sort_column'        => 'meta_value_num',
			'child_of'           => fiscaat_get_current_year_id(),
			'meta_key'           => '_fiscaat_ledger_id',
			'orderby'            => 'meta_value_num',

			// Output-related
			'select_id'          => 'fiscaat_ledger_account_id',
			'show_none'          => '',
		);

		$r = fiscaat_parse_args( $args, $defaults, 'get_ledger_dropdown' );

		/** Drop Down *********************************************************/

		// Adjust dropdown title
		add_filter( 'fiscaat_walker_dropdown_post_title', 'fiscaat_filter_ledger_dropdown_title', 10, 5 );

		// Get the dropdown
		$retval = fiscaat_get_dropdown( $r );

		// Remove filter
		remove_filter( 'fiscaat_walker_dropdown_post_title', 'fiscaat_filter_ledger_dropdown_title' );

		return apply_filters( 'fiscaat_get_ledger_dropdown', $retval, $args );
	}

	/**
	 * Return post title for ledger dropdown
	 * 
	 * @param string $post_title
	 * @param string $output 
	 * @param object $_post 
	 * @param int $depth 
	 * @param array $args 
	 * @return string Post title
	 */
	function fiscaat_filter_ledger_dropdown_title( $post_title, $output, $_post, $depth, $args ) {
		$account_id = fiscaat_get_account_id( $_post->ID );

		// Validate account
		if ( ! fiscaat_is_account( $account_id ) )
			return $post_title;

		// Set post title
		$post_title = fiscaat_get_account_ledger_id( $account_id );

		return apply_filters( 'fiscaat_filter_ledger_dropdown_title', $post_title, $account_id );
	}

