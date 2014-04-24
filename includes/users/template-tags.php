<?php

/**
 * Fiscaat User Template Tags
 *
 * @package Fiscaat
 * @subpackage TemplateTags
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** Users *********************************************************************/

/**
 * Output a validated user id
 *
 * @param int $user_id Optional. User id
 * @param bool $displayed_user_fallback Fallback on displayed user?
 * @param bool $current_user_fallback Fallback on current user?
 * @uses fct_get_user_id() To get the user id
 */
function fct_user_id( $user_id = 0, $displayed_user_fallback = true, $current_user_fallback ) {
	echo fct_get_user_id( $user_id, $displayed_user_fallback, $current_user_fallback );
}
	/**
	 * Return a validated user id
	 *
	 * @param int $user_id Optional. User id
	 * @uses get_current_user_id() To find the current user id
	 * @uses apply_filters() Calls 'fct_get_user_id' with the user id
	 * @return int Validated user id
	 */
	function fct_get_user_id( $user_id = 0, $displayed_user_fallback = true, $current_user_fallback = false ) {
		global $profileuser;

		// Easy empty checking
		if ( ! empty( $user_id ) && is_numeric( $user_id ) ) {
			$fct_user_id = $user_id;

		// Currently viewing or editing a user
		} elseif ( ( true == $displayed_user_fallback ) && ! empty( $profileuser ) ) {
			$fct_user_id = $profileuser->ID;

		// Maybe fallback on the current_user ID
		} elseif ( ( true == $current_user_fallback ) ) {
			$fct_user_id = get_current_user_id();

		// Failsafe
		} else {
			$fct_user_id = get_query_var( 'fct_user_id' ); // ?
		}

		return (int) apply_filters( 'fct_get_user_id', (int) $fct_user_id, $displayed_user_fallback, $current_user_fallback );
	}

/**
 * Output ID of current user
 *
 * @uses fct_get_current_user_id() To get the current user id
 */
function fct_current_user_id() {
	echo fct_get_current_user_id();
}
	/**
	 * Return ID of current user
	 *
	 * @uses fct_get_user_id() To get the current user id
	 * @uses apply_filters() Calls 'fct_get_current_user_id' with the id
	 * @return int Current user id
	 */
	function fct_get_current_user_id() {
		return apply_filters( 'fct_get_current_user_id', fct_get_user_id( 0, false, true ) );
	}

/**
 * Output ID of displayed user
 *
 * @uses fct_get_displayed_user_id() To get the displayed user id
 */
function fct_displayed_user_id() {
	echo fct_get_displayed_user_id();
}
	/**
	 * Return ID of displayed user
	 *
	 * @uses fct_get_user_id() To get the displayed user id
	 * @uses apply_filters() Calls 'fct_get_displayed_user_id' with the id
	 * @return int Displayed user id
	 */
	function fct_get_displayed_user_id() {
		return apply_filters( 'fct_get_displayed_user_id', fct_get_user_id( 0, true, false ) );
	}

/**
 * Output a user's main role for display
 *
 * @param int $user_id
 * @uses fct_get_user_display_role To get the user display role
 */
function fct_user_display_role( $user_id = 0 ) {
	echo fct_get_user_display_role( $user_id );
}
	/**
	 * Return a user's main role for display
	 *
	 * @param int $user_id
	 * @uses fct_get_user_id() to verify the user ID
	 * @uses is_super_admin() to check if user is a super admin
	 * @uses fct_is_user_inactive() to check if user is inactive
	 * @uses user_can() to check if user has special capabilities
	 * @uses apply_filters() Calls 'fct_get_user_display_role' with the
	 *                        display role, user id, and user role
	 * @return string
	 */
	function fct_get_user_display_role( $user_id = 0 ) {

		// Validate user id
		$user_id = fct_get_user_id( $user_id, false, false );

		// Fiscus
		if ( user_can( $user_id, 'fiscaat' ) ) {
			$role = __( 'Fiscus', 'fiscaat' );

		// Controller
		} elseif ( user_can( $user_id, 'control' ) ) {
			$role = __( 'Controller', 'fiscaat' );

		// Spectator
		} elseif ( user_can( $user_id, 'fct_spectate' ) ) {
			$role = __( 'Spectator', 'fiscaat' );

		// Anyone else
		} else {
			$role = __( 'Alien', 'fiscaat' );
		}

		return apply_filters( 'fct_get_user_display_role', $role, $user_id );
	}

/** Edit User *****************************************************************/

/**
 * Output fiscaat role selector (for user edit)
 *
 * @uses fct_get_user_role()
 * @uses fct_get_displayed_user_id()
 * @uses fct_get_dynamic_roles()
 */
function fct_edit_user_fct_role() {

	// Get the user's role
	$user_role     = fct_get_user_role( fct_get_displayed_user_id() );

	// Get the roles
	$dynamic_roles = fct_get_dynamic_roles(); ?>

	<select name="fiscaat-role" id="fiscaat-role">
		<option value=""><?php _e( '&mdash; No role for Fiscaat &mdash;', 'fiscaat' ); ?></option>

		<?php foreach ( $dynamic_roles as $role => $details ) : ?>

			<option <?php selected( $user_role, $role ); ?> value="<?php echo esc_attr( $role ); ?>"><?php echo translate_user_role( $details['name'] ); ?></option>

		<?php endforeach; ?>

	</select>

	<?php
}

/**
 * Output checkbox to make user global Spectator
 *
 * @uses fct_user_can_view_all()
 * @uses fct_get_displayed_user_id()
 * @uses user_can()
 */
function fct_edit_user_global_spectator() {

	// Setup disabled if user is capable
	$disable = user_can( fct_displayed_user_id(), 'fiscaat' ) || user_can( fct_displayed_user_id(), 'control' );

	// Create nonce
	wp_nonce_field( 'fct_global_spectator', 'fct_global_spectator_nonce' ); ?>

	<input name="fiscaat-global-spectator" id="fiscaat-global-spectator" type="checkbox" value="1" <?php checked( fct_user_can_view_all( fct_get_displayed_user_id() ) ); ?> <?php disabled( $disable ); ?> />
	<label for="fiscaat-global-spectator"><?php _e('Give this user the ability to view all Fiscaat data.', 'fiscaat'); ?></label>

	<?php if ( $disable ) : ?>

		<p class="description"><?php _e( "This user already can view everything based on it's capabilities.", 'fiscaat' ); ?></p>

	<?php endif;
}

/**
 * Output checkbox to set user as blocked Fiscaat commenter
 *
 * @uses fct_user_can_comment()
 * @uses fct_get_displayed_user_id()
 */
function fct_edit_user_block_commenter() {

	// Create nonce
	wp_nonce_field( 'fct_block_commenter', 'fct_block_commenter_nonce' ); ?>

	<input name="fiscaat-block-commenter" id="fiscaat-block-commenter" type="checkbox" value="1" <?php checked( ! fct_user_can_comment( fct_get_displayed_user_id() ) ); ?> />
	<label for="fiscaat-block-commenter"><?php _e('Block this user from commenting in Fiscaat.', 'fiscaat'); ?></label>

	<?php
}

/** Capabilities **************************************************************/

/**
 * Check if the user can access all Fiscaat pages
 *
 * @uses fct_get_user_id()
 * @uses current_user_can()
 * @uses apply_filters() Calls 'fct_user_can_view_all' with
 *                        match and user id
 *
 * @return bool User can view all
 */
function fct_user_can_view_all( $user_id = 0 ) {

	// Validate parsed values
	$user_id = fct_get_user_id( $user_id, false, true );
	$match   = false;

	// User is fiscus or controller
	if ( current_user_can( 'fiscaat' ) || current_user_can( 'control' ) ) {
		$match = true;

	// Spectator can see all
	} elseif ( fct_user_is_global_spectator( $user_id ) ) {
		$match = true;
	}

	return (bool) apply_filters( 'fct_user_can_view_all', $match, $user_id );
}

/**
 * Check if user can access a given account
 * 
 * @param int $account_id Optional. Account id
 * @param int $user_id Optional. User id
 * @uses fct_is_record()
 * @uses fct_get_record_id()
 * @uses fct_get_record_account_id()
 * @uses fct_get_account_id()
 * @uses fct_get_user_id()
 * @uses fct_get_account_ledger_id()
 * @uses fct_user_can_view_all()
 * @uses fct_get_account_post_type()
 * @uses fct_user_in_account_spectators()
 * @uses apply_filters() Calls 'fct_user_can_view_account' with
 *                        match, account id, and user id
 * @return bool User can view account
 */
function fct_user_can_view_account( $account_id = 0, $user_id = 0 ) {

	// Validate account
	if ( fct_is_record( $account_id ) ){
		$record_id  = fct_get_record_id( $account_id );
		$account_id = fct_get_record_account_id( $record_id );
	} else {
		$account_id = fct_get_account_id( $account_id );
	}

	$user_id   = fct_get_user_id( $user_id, false, true );
	$ledger_id = fct_get_account_ledger_id( $account_id );
	$match     = fct_user_can_view_all( $user_id );

	// Search for match if none yet
	if ( ! $match ) {
		
		// Fetch accounts with same ledger id
		if ( $accounts = new WP_Query( array( 
		'suppress_filters' => true,
		'post_type'        => fct_get_account_post_type(),
		'post_status'      => 'any',
		'meta_key'         => '_fct_ledger_id',
		'meta_value'       => $ledger_id,
		'posts_per_page'   => -1,
		'nopaging'         => true,
		'fields'           => 'ids'
		) ) ) {
			foreach ( $accounts->posts as $account ) {
				$match = fct_user_in_account_spectators( $account, $user_id );

				// Stop searching for a match
				if ( $match )
					break;
			}
		}
	}

	return (bool) apply_filters( 'fct_user_can_view_account', $match, $account_id, $user_id );
}

/** Forms *********************************************************************/

/**
 * Performs a series of checks to ensure the current user can create years.
 *
 * Super admins are not privileged.
 *
 * @since 0.0.1
 *
 * @uses fct_is_year_edit()
 * @uses current_user_can()
 * @uses fct_get_year_id()
 *
 * @return bool
 */
function fct_current_user_can_access_create_year_form() {

	// Users need to earn access
	$retval = false;

	// Looking at a single year & year is open
	if ( ( is_page() || is_single() ) && fct_is_year_open() ) {
		$retval = fct_current_user_can_publish_years();

	// User can edit this account
	} elseif ( fct_is_year_edit() ) {
		$retval = current_user_can( 'edit_year', fct_get_year_id() );
	}

	// Allow access to be filtered
	return (bool) apply_filters( 'fct_current_user_can_access_create_year_form', (bool) $retval );
}

/**
 * Performs a series of checks to ensure the current user can create accounts.
 *
 * Super admins are not privileged.
 *
 * @since 0.0.1
 *
 * @uses fct_is_account_edit()
 * @uses current_user_can()
 * @uses fct_get_account_id()
 * @uses fct_allow_anonymous()
 * @uses is_user_logged_in()
 *
 * @return bool
 */
function fct_current_user_can_access_create_account_form() {

	// Users need to earn access
	$retval = false;

	// Looking at a single year & year is open
	if ( ( fct_is_single_year() || is_page() || is_single() ) && fct_is_year_open() ) {
		$retval = fct_current_user_can_publish_accounts();

	// User can edit this account
	} elseif ( fct_is_account_edit() ) {
		$retval = current_user_can( 'edit_account', fct_get_account_id() );
	}

	// Allow access to be filtered
	return (bool) apply_filters( 'fct_current_user_can_access_create_account_form', (bool) $retval );
}

/**
 * Performs a series of checks to ensure the current user can create records.
 *
 * Super admins are not privileged.
 *
 * @since 0.0.1
 *
 * @uses fct_is_account_edit()
 * @uses current_user_can()
 * @uses fct_get_account_id()
 * @uses fct_allow_anonymous()
 * @uses is_user_logged_in()
 *
 * @return bool
 */
function fct_current_user_can_access_create_record_form() {

	// Users need to earn access
	$retval = false;

	// Looking at a single account, account is open, and year is open
	if ( ( fct_is_single_account() || is_page() || is_single() ) && fct_is_account_open() && fct_is_year_open() ) {
		$retval = fct_current_user_can_publish_records();

	// User can edit this account
	} elseif ( fct_is_record_edit() ) {
		$retval = current_user_can( 'edit_record', fct_get_record_id() );
	}

	// Allow access to be filtered
	return (bool) apply_filters( 'fct_current_user_can_access_create_record_form', (bool) $retval );
}