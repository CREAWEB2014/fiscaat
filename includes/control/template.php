<?php

/**
 * Fiscaat Control Template Tags
 *
 * @package Fiscaat
 * @subpackage Control
 *
 * @todo fct_get_record_admin_links()
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** Periods *********************************************************************/

/**
 * Output total declined record count of a period 
 *
 * @param int $period_id Optional. Account id
 * @param boolean $integer Optional. Whether or not to format the result
 * @uses fct_get_period_record_count_declined() To get the period declined record count
 */
function fct_period_record_count_declined( $period_id = 0, $integer = false ) {
	echo fct_get_period_record_count_declined( $period_id, $integer );
}
	/**
	 * Return total declined record count of a period 
	 *
	 * @param int $period_id Optional. Account id
	 * @param boolean $integer Optional. Whether or not to format the result
	 * @uses fct_get_period_id() To get the period id
	 * @uses fct_get_period_meta() To get the declined record count
	 * @uses apply_filters() Calls 'fct_get_period_record_count_declined' with
	 *                        the declined record count and period id
	 * @return int Account declined record count
	 */
	function fct_get_period_record_count_declined( $period_id = 0, $integer = false ) {
		$period_id = fct_get_period_id( $period_id );
		$records   = (int) fct_get_period_meta( $period_id, 'record_count_declined' );
		$filter    = ( true === $integer ) ? 'fct_get_period_record_count_declined_int' : 'fct_get_period_record_count_declined';

		return apply_filters( $filter, $records, $period_id );
	}

/**
 * Output total unapproved record count of a period 
 *
 * @param int $period_id Optional. Account id
 * @param boolean $integer Optional. Whether or not to format the result
 * @uses fct_get_period_record_count_unapproved() To get the period unapproved record count
 */
function fct_period_record_count_unapproved( $period_id = 0, $integer = false ) {
	echo fct_get_period_record_count_unapproved( $period_id, $integer );
}
	/**
	 * Return total unapproved record count of a period 
	 *
	 * @param int $period_id Optional. Account id
	 * @param boolean $integer Optional. Whether or not to format the result
	 * @uses fct_get_period_id() To get the period id
	 * @uses fct_get_period_meta() To get the unapproved record count
	 * @uses apply_filters() Calls 'fct_get_period_record_count_unapproved' with
	 *                        the unapproved record count and period id
	 * @return int Account unapproved record count
	 */
	function fct_get_period_record_count_unapproved( $period_id = 0, $integer = false ) {
		$period_id = fct_get_period_id( $period_id );
		$records   = (int) fct_get_period_meta( $period_id, 'record_count_unapproved' );
		$filter    = ( true === $integer ) ? 'fct_get_period_record_count_unapproved_int' : 'fct_get_period_record_count_unapproved';

		return apply_filters( $filter, $records, $period_id );
	}

/** Accounts ******************************************************************/

/**
 * Output total declined record count of an account 
 *
 * @param int $account_id Optional. Account id
 * @param boolean $integer Optional. Whether or not to format the result
 * @uses fct_get_account_record_count_declined() To get the account declined record count
 */
function fct_account_record_count_declined( $account_id = 0, $integer = false ) {
	echo fct_get_account_record_count_declined( $account_id, $integer );
}
	/**
	 * Return total declined record count of an account 
	 *
	 * @param int $account_id Optional. Account id
	 * @param boolean $integer Optional. Whether or not to format the result
	 * @uses fct_get_account_id() To get the account id
	 * @uses fct_get_account_meta() To get the declined record count
	 * @uses apply_filters() Calls 'fct_get_account_record_count_declined' with
	 *                        the declined record count and account id
	 * @return int Account declined record count
	 */
	function fct_get_account_record_count_declined( $account_id = 0, $integer = false ) {
		$account_id = fct_get_account_id( $account_id );
		$records    = (int) fct_get_account_meta( $account_id, 'record_count_declined' );
		$filter     = ( true === $integer ) ? 'fct_get_account_record_count_declined_int' : 'fct_get_account_record_count_declined';

		return apply_filters( $filter, $records, $account_id );
	}

/**
 * Output total unapproved record count of an account 
 *
 * @param int $account_id Optional. Account id
 * @param boolean $integer Optional. Whether or not to format the result
 * @uses fct_get_account_record_count_unapproved() To get the account unapproved record count
 */
function fct_account_record_count_unapproved( $account_id = 0, $integer = false ) {
	echo fct_get_account_record_count_unapproved( $account_id, $integer );
}
	/**
	 * Return total unapproved record count of an account 
	 *
	 * @param int $account_id Optional. Account id
	 * @param boolean $integer Optional. Whether or not to format the result
	 * @uses fct_get_account_id() To get the account id
	 * @uses fct_get_account_meta() To get the unapproved record count
	 * @uses apply_filters() Calls 'fct_get_account_record_count_unapproved' with
	 *                        the unapproved record count and account id
	 * @return int Account unapproved record count
	 */
	function fct_get_account_record_count_unapproved( $account_id = 0, $integer = false ) {
		$account_id = fct_get_account_id( $account_id );
		$records    = (int) fct_get_account_meta( $account_id, 'record_count_unapproved' );
		$filter     = ( true === $integer ) ? 'fct_get_account_record_count_unapproved_int' : 'fct_get_account_record_count_unapproved';

		return apply_filters( $filter, $records, $account_id );
	}

/** Records *******************************************************************/

/**
 * Is the record declined?
 *
 * @uses fct_get_record_id() To get the record id
 * @uses fct_get_record_status() To get the record status
 * @param int $record_id Optional. Record id
 * @return bool True if declined, false if not.
 */
function fct_is_record_declined( $record_id = 0 ) {
	$record_id     = fct_get_record_id( $record_id );
	$record_status = fct_get_record_status( $record_id ) == fct_get_declined_status_id();
	
	return (bool) apply_filters( 'fct_is_record_declined', (bool) $record_status, $record_id );
}

/**
 * Is the record unapproved?
 *
 * @uses fct_is_record_approved() To check if the record is approved
 * @param int $record_id Optional. Record id
 * @return bool True if declined, false if not.
 */
function fct_is_record_unapproved( $record_id = 0 ) {
	return ! fct_is_record_approved( $record_id );
}

	/**
	 * Is the record approved?
	 *
	 * @uses fct_get_record_id() To get the record id
	 * @uses fct_get_record_status() To get the record status
	 * @param int $record_id Optional. Account id
	 * @return bool True if approved, false if not.
	 */
	function fct_is_record_approved( $record_id = 0 ) {
		$record_id     = fct_get_record_id( $record_id );
		$record_status = fct_get_record_status( $record_id ) == fct_get_approved_status_id();
		
		return (bool) apply_filters( 'fct_is_record_approved', (bool) $record_status, $record_id );
	}

/**
 * Output the approve link of the record
 *
 * @uses fct_get_record_approve_link() To get the record approve link
 * @param mixed $args See {@link fct_get_record_approve_link()}
 */
function fct_record_approve_link( $args = '' ) {
	echo fct_get_record_approve_link( $args );
}

	/**
	 * Return the approve link of the record
	 *
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
	 *
	 * @param mixed $args This function supports these arguments:
	 *  - id: Record id
	 *  - link_before: HTML before the link
	 *  - link_after: HTML after the link
	 *  - approve_text: Approve text
	 * @return string Record approve link
	 */
	function fct_get_record_approve_link( $args = '' ) {
		$r = fct_parse_args( $args, array(
			'id'           => 0,
			'link_before'  => '',
			'link_after'   => '',
			'approve_text' => __( 'Approve', 'fiscaat' )
		), 'get_record_approve_link' );
		extract( $r );

		$record = fct_get_record( fct_get_record_id( (int) $id ) );

		if ( empty( $record ) || ! current_user_can( 'control', $record->ID ) )
			return;

		$uri    = add_query_arg( array( 'action' => 'fct_toggle_record_approve', 'record_id' => $record->ID ) );
		$uri    = esc_url( wp_nonce_url( $uri, 'approve-record_' . $record->ID ) );
		$retval = $link_before . '<a href="' . $uri . '">' . $approve_text . '</a>' . $link_after;

		return apply_filters( 'fct_get_record_approve_link', $retval, $args );
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
	 *
	 * @param mixed $args This function supports these arguments:
	 *  - id: Record id
	 *  - link_before: HTML before the link
	 *  - link_after: HTML after the link
	 *  - decline_text: Suspense text
	 * @return string Record decline link
	 */
	function fct_get_record_decline_link( $args = '' ) {
		$r = fct_parse_args( $args, array(
			'id'           => 0,
			'link_before'  => '',
			'link_after'   => '',
			'decline_text' => __( 'Decline', 'fiscaat' )
		), 'get_record_decline_link' );
		extract( $r );

		$record = fct_get_record( fct_get_record_id( (int) $id ) );

		if ( empty( $record ) || ! current_user_can( 'control', $record->ID ) )
			return;

		$uri    = add_query_arg( array( 'action' => 'fct_set_record_decline', 'record_id' => $record->ID ) );
		$uri    = esc_url( wp_nonce_url( $uri, 'decline-record_' . $record->ID ) );
		$retval = $link_before . '<a href="' . $uri . '">' . $decline_text . '</a>' . $link_after;

		return apply_filters( 'fct_get_record_decline_link', $retval, $args );
	}
