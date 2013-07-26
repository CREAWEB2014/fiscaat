<?php

/**
 * Fiscaat User Template Tags
 *
 * @package Fiscaat
 * @subpackage TemplateTags
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/** Users *********************************************************************/

/**
 * Output a validated user id
 *
 * @param int $user_id Optional. User id
 * @param bool $displayed_user_fallback Fallback on displayed user?
 * @param bool $current_user_fallback Fallback on current user?
 * @uses fiscaat_get_user_id() To get the user id
 */
function fiscaat_user_id( $user_id = 0, $displayed_user_fallback = true, $current_user_fallback ) {
	echo fiscaat_get_user_id( $user_id, $displayed_user_fallback, $current_user_fallback );
}
	/**
	 * Return a validated user id
	 *
	 * @param int $user_id Optional. User id
	 * @uses get_current_user_id() To find the current user id
	 * @uses apply_filters() Calls 'fiscaat_get_user_id' with the user id
	 * @return int Validated user id
	 */
	function fiscaat_get_user_id( $user_id = 0, $displayed_user_fallback = true, $current_user_fallback = false ) {
		global $profileuser;

		// Easy empty checking
		if ( !empty( $user_id ) && is_numeric( $user_id ) ) {
			$fiscaat_user_id = $user_id;

		// Currently viewing or editing a user
		} elseif ( ( true == $displayed_user_fallback ) && ! empty( $profileuser ) ) {
			$fiscaat_user_id = $profileuser->ID;

		// Maybe fallback on the current_user ID
		} elseif ( ( true == $current_user_fallback ) ) {
			$fiscaat_user_id = get_current_user_id();

		// Failsafe
		} else {
			$fiscaat_user_id = get_query_var( 'fiscaat_user_id' ); // ?
		}

		return (int) apply_filters( 'fiscaat_get_user_id', (int) $fiscaat_user_id, $displayed_user_fallback, $current_user_fallback );
	}

/**
 * Output ID of current user
 *
 * @uses fiscaat_get_current_user_id() To get the current user id
 */
function fiscaat_current_user_id() {
	echo fiscaat_get_current_user_id();
}
	/**
	 * Return ID of current user
	 *
	 * @uses fiscaat_get_user_id() To get the current user id
	 * @uses apply_filters() Calls 'fiscaat_get_current_user_id' with the id
	 * @return int Current user id
	 */
	function fiscaat_get_current_user_id() {
		return apply_filters( 'fiscaat_get_current_user_id', fiscaat_get_user_id( 0, false, true ) );
	}

/**
 * Output ID of displayed user
 *
 * @uses fiscaat_get_displayed_user_id() To get the displayed user id
 */
function fiscaat_displayed_user_id() {
	echo fiscaat_get_displayed_user_id();
}
	/**
	 * Return ID of displayed user
	 *
	 * @uses fiscaat_get_user_id() To get the displayed user id
	 * @uses apply_filters() Calls 'fiscaat_get_displayed_user_id' with the id
	 * @return int Displayed user id
	 */
	function fiscaat_get_displayed_user_id() {
		return apply_filters( 'fiscaat_get_displayed_user_id', fiscaat_get_user_id( 0, true, false ) );
	}

/**
 * Output a user's main role for display
 *
 * @param int $user_id
 * @uses fiscaat_get_user_display_role To get the user display role
 */
function fiscaat_user_display_role( $user_id = 0 ) {
	echo fiscaat_get_user_display_role( $user_id );
}
	/**
	 * Return a user's main role for display
	 *
	 * @param int $user_id
	 * @uses fiscaat_get_user_id() to verify the user ID
	 * @uses is_super_admin() to check if user is a super admin
	 * @uses fiscaat_is_user_inactive() to check if user is inactive
	 * @uses user_can() to check if user has special capabilities
	 * @uses apply_filters() Calls 'fiscaat_get_user_display_role' with the
	 *                        display role, user id, and user role
	 * @return string
	 */
	function fiscaat_get_user_display_role( $user_id = 0 ) {

		// Validate user id
		$user_id = fiscaat_get_user_id( $user_id, false, false );

		// Fiscus
		if ( user_can( $user_id, 'fiscaat' ) ) {
			$role = __( 'Fiscus', 'fiscaat' );

		// Controller
		} elseif ( user_can( $user_id, 'control' ) ) {
			$role = __( 'Controller', 'fiscaat' );

		// Spectator
		} elseif ( user_can( $user_id, 'fiscaat_spectate' ) ) {
			$role = __( 'Spectator', 'fiscaat' );

		// Anyone else
		} else {
			$role = __( 'Alien', 'fiscaat' );
		}

		return apply_filters( 'fiscaat_get_user_display_role', $role, $user_id );
	}

/** Edit User *****************************************************************/

/**
 * Output fiscaat role selector (for user edit)
 *
 * @uses fiscaat_get_user_role()
 * @uses fiscaat_get_displayed_user_id()
 * @uses fiscaat_get_dynamic_roles()
 */
function fiscaat_edit_user_fiscaat_role() {

	// Get the user's role
	$user_role     = fiscaat_get_user_role( fiscaat_get_displayed_user_id() );

	// Get the roles
	$dynamic_roles = fiscaat_get_dynamic_roles(); ?>

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
 * @uses fiscaat_user_can_view_all()
 * @uses fiscaat_get_displayed_user_id()
 * @uses user_can()
 */
function fiscaat_edit_user_global_spectator() {

	// Setup disabled if user is capable
	$disable = user_can( fiscaat_displayed_user_id(), 'fiscaat' ) || user_can( fiscaat_displayed_user_id(), 'control' );

	// Create nonce
	wp_nonce_field( 'fiscaat_global_spectator', 'fiscaat_global_spectator_nonce' ); ?>

	<input name="fiscaat-global-spectator" id="fiscaat-global-spectator" type="checkbox" value="1" <?php checked( fiscaat_user_can_view_all( fiscaat_get_displayed_user_id() ) ); ?> <?php disabled( $disable ); ?> />
	<label for="fiscaat-global-spectator"><?php _e('Give this user the ability to view all Fiscaat data.', 'fiscaat'); ?></label>

	<?php if ( $disable ) : ?>

		<p class="description"><?php _e( "This user already can view everything based on it's capabilities.", 'fiscaat' ); ?></p>

	<?php endif;
}

/**
 * Output checkbox to set user as blocked Fiscaat commenter
 *
 * @uses fiscaat_user_can_comment()
 * @uses fiscaat_get_displayed_user_id()
 */
function fiscaat_edit_user_block_commenter() {

	// Create nonce
	wp_nonce_field( 'fiscaat_block_commenter', 'fiscaat_block_commenter_nonce' ); ?>

	<input name="fiscaat-block-commenter" id="fiscaat-block-commenter" type="checkbox" value="1" <?php checked( ! fiscaat_user_can_comment( fiscaat_get_displayed_user_id() ) ); ?> />
	<label for="fiscaat-block-commenter"><?php _e('Block this user from commenting in Fiscaat.', 'fiscaat'); ?></label>

	<?php
}

/** Capabilities **************************************************************/

/**
 * Check if the user can access all Fiscaat pages
 *
 * @uses fiscaat_get_user_id()
 * @uses current_user_can()
 * @uses apply_filters() Calls 'fiscaat_user_can_view_all' with
 *                        match and user id
 *
 * @return bool User can view all
 */
function fiscaat_user_can_view_all( $user_id = 0 ) {

	// Validate parsed values
	$user_id = fiscaat_get_user_id( $user_id, false, true );
	$match   = false;

	// User is fiscus or controller
	if ( current_user_can( 'fiscaat' ) || current_user_can( 'control' ) ) {
		$match = true;

	// Spectator can see all
	} elseif ( fiscaat_user_is_global_spectator( $user_id ) ) {
		$match = true;
	}

	return (bool) apply_filters( 'fiscaat_user_can_view_all', $match, $user_id );
}

/**
 * Check if user can access a given account
 * 
 * @param int $account_id Optional. Account id
 * @param int $user_id Optional. User id
 * @uses fiscaat_is_record()
 * @uses fiscaat_get_record_id()
 * @uses fiscaat_get_record_account_id()
 * @uses fiscaat_get_account_id()
 * @uses fiscaat_get_user_id()
 * @uses fiscaat_get_account_ledger_id()
 * @uses fiscaat_user_can_view_all()
 * @uses fiscaat_get_account_post_type()
 * @uses fiscaat_user_in_account_spectators()
 * @uses apply_filters() Calls 'fiscaat_user_can_view_account' with
 *                        match, account id, and user id
 * @return bool User can view account
 */
function fiscaat_user_can_view_account( $account_id = 0, $user_id = 0 ) {

	// Validate account
	if ( fiscaat_is_record( $account_id ) ){
		$record_id  = fiscaat_get_record_id( $account_id );
		$account_id = fiscaat_get_record_account_id( $record_id );
	} else {
		$account_id = fiscaat_get_account_id( $account_id );
	}

	$user_id   = fiscaat_get_user_id( $user_id, false, true );
	$ledger_id = fiscaat_get_account_ledger_id( $account_id );
	$match     = fiscaat_user_can_view_all( $user_id );

	// Search for match if none yet
	if ( ! $match ) {
		
		// Fetch accounts with same ledger id
		if ( $accounts = new WP_Query( array( 
		'suppress_filters' => true,
		'post_type'        => fiscaat_get_account_post_type(),
		'post_status'      => 'any',
		'meta_key'         => '_fiscaat_ledger_id',
		'meta_value'       => $ledger_id,
		'posts_per_page'   => -1,
		'nopaging'         => true,
		'fields'           => 'ids'
		) ) ) {
			foreach ( $accounts->posts as $account ) {
				$match = fiscaat_user_in_account_spectators( $account, $user_id );

				// Stop searching for a match
				if ( $match )
					break;
			}
		}
	}

	return (bool) apply_filters( 'fiscaat_user_can_view_account', $match, $account_id, $user_id );
}

/** Forms *********************************************************************/

/**
 * Performs a series of checks to ensure the current user can create years.
 *
 * @since Fiscaat (r3549)
 *
 * @uses fiscaat_is_year_edit()
 * @uses current_user_can()
 * @uses fiscaat_get_year_id()
 *
 * @return bool
 */
function fiscaat_current_user_can_access_create_year_form() {

	// Users need to earn access
	$retval = false;

	// Always allow super admins
	if ( is_super_admin() ) {
		$retval = true;

	// Looking at a single year & year is open
	} elseif ( ( is_page() || is_single() ) && fiscaat_is_year_open() ) {
		$retval = fiscaat_current_user_can_publish_years();

	// User can edit this account
	} elseif ( fiscaat_is_year_edit() ) {
		$retval = current_user_can( 'edit_year', fiscaat_get_year_id() );
	}

	// Allow access to be filtered
	return (bool) apply_filters( 'fiscaat_current_user_can_access_create_year_form', (bool) $retval );
}

/**
 * Performs a series of checks to ensure the current user can create accounts.
 *
 * @since Fiscaat (r3127)
 *
 * @uses fiscaat_is_account_edit()
 * @uses current_user_can()
 * @uses fiscaat_get_account_id()
 * @uses fiscaat_allow_anonymous()
 * @uses is_user_logged_in()
 *
 * @return bool
 */
function fiscaat_current_user_can_access_create_account_form() {

	// Users need to earn access
	$retval = false;

	// Always allow super admins
	if ( is_super_admin() ) {
		$retval = true;

	// Looking at a single year & year is open
	} elseif ( ( fiscaat_is_single_year() || is_page() || is_single() ) && fiscaat_is_year_open() ) {
		$retval = fiscaat_current_user_can_publish_accounts();

	// User can edit this account
	} elseif ( fiscaat_is_account_edit() ) {
		$retval = current_user_can( 'edit_account', fiscaat_get_account_id() );
	}

	// Allow access to be filtered
	return (bool) apply_filters( 'fiscaat_current_user_can_access_create_account_form', (bool) $retval );
}

/**
 * Performs a series of checks to ensure the current user can create records.
 *
 * @since Fiscaat (r3127)
 *
 * @uses fiscaat_is_account_edit()
 * @uses current_user_can()
 * @uses fiscaat_get_account_id()
 * @uses fiscaat_allow_anonymous()
 * @uses is_user_logged_in()
 *
 * @return bool
 */
function fiscaat_current_user_can_access_create_record_form() {

	// Users need to earn access
	$retval = false;

	// Always allow super admins
	if ( is_super_admin() ) {
		$retval = true;

	// Looking at a single account, account is open, and year is open
	} elseif ( ( fiscaat_is_single_account() || is_page() || is_single() ) && fiscaat_is_account_open() && fiscaat_is_year_open() ) {
		$retval = fiscaat_current_user_can_publish_records();

	// User can edit this account
	} elseif ( fiscaat_is_record_edit() ) {
		$retval = current_user_can( 'edit_record', fiscaat_get_record_id() );
	}

	// Allow access to be filtered
	return (bool) apply_filters( 'fiscaat_current_user_can_access_create_record_form', (bool) $retval );
}
