<?php

/**
 * Fiscaat Control Admin Functions
 *
 * @package Fiscaat
 * @subpackage Control
 */

/** Metaboxes *****************************************************************/

/**
 * Output Control statistics in Fiscaat Dashboard Right Now Widget
 *
 * @uses fct_get_statistics() To get the period statistics
 * @uses current_user_can() To check if the user is capable of doing things
 * @uses fct_get_record_post_type() To get the record post type
 * @uses get_admin_url() To get the administration url
 * @uses add_query_arg() To add custom args to the url
 */
function fct_ctrl_admin_dashboard_widget_right_now() {

	// Get the statistics and extract them
	extract( fct_get_statistics(), EXTR_SKIP ); ?>

		<tr>
			<?php
				$num  = $current_approved_count;
				$text = __( 'Approved', 'fiscaat' );
				if ( current_user_can( 'fct_spectate' ) ) {
					$link = add_query_arg( array( 'post_type' => fct_get_record_post_type(), 'fct_period_id' => fct_get_current_period_id(), 'approval' => 1 ), get_admin_url( null, 'edit.php' ) );
					$num  = '<a href="' . $link . '">' . $num  . '</a>';
					$text = '<a class="approved" href="' . $link . '">' . $text . '</a>';
				}
			?>

			<td class="b b-records-approved"><?php echo $num; ?></td>
			<td class="last t records-approved"><?php echo $text; ?></td>
		</tr>

		<tr>
			<?php
				$num  = $current_unapproved_count;
				$text = __( 'Unapproved', 'fiscaat' );
				if ( current_user_can( 'fct_spectate' ) ) {
					$link = add_query_arg( array( 'post_type' => fct_get_record_post_type(), 'fct_period_id' => fct_get_current_period_id(), 'approval' => 0 ), get_admin_url( null, 'edit.php' ) );
					$num  = '<a href="' . $link . '">' . $num  . '</a>';
					$text = '<a class="waiting" href="' . $link . '">' . $text . '</a>';
				}
			?>

			<td class="b b-records-unapproved"><?php echo $num; ?></td>
			<td class="last t records-unapproved"><?php echo $text; ?></td>
		</tr>

		<tr>
			<?php
				$num  = $current_declined_count;
				$text = __( 'Declined', 'fiscaat' );
				if ( current_user_can( 'fct_spectate' ) ) {
					$link = add_query_arg( array( 'post_type' => fct_get_record_post_type(), 'fct_period_id' => fct_get_current_period_id(), 'approval' => 2 ), get_admin_url( null, 'edit.php' ) );
					$num  = '<a href="' . $link . '">' . $num  . '</a>';
					$text = '<a class="spam" href="' . $link . '">' . $text . '</a>';
				}
			?>

			<td class="b b-records-declined"><?php echo $num; ?></td>
			<td class="last t records-declined"><?php echo $text; ?></td>
		</tr>

	<?php
}

/**
 * Output the dashboard widget right now control content
 *
 * @uses fct_get_total_controllers()
 */
function fct_ctrl_dashboard_widget_right_now_content() {

	// Get controller count
	$controller_count = fct_get_total_controllers(); ?>

		<tr>
			<?php
				$num  = $controller_count;
				$text = _n( 'Controller', 'Controllers', $controller_count, 'fiscaat' );
			?>

			<td class="first b b-users"><?php echo $num; ?></td>
			<td class="t users"><?php echo $text; ?></td>
		</tr>

	<?php	
}

/** Periods *********************************************************************/

/**
 * Add control columns to admin periods list table
 *
 * @since 0.0.5
 * 
 * @param array $columns Columns
 * @return array Columns
 */
function fct_ctrl_admin_periods_columns( $columns ) {

	// Group record counts. array_splice only does numeric keys
	$position = array_search( 'fct_period_record_count', array_keys( $columns ) ) + 1;
	$columns  = array_slice( $columns, 0, $position, true ) + array( 
		'fct_period_record_count_unapproved' => __( 'Unapproved', 'fiscaat' ),
		'fct_period_record_count_declined'   => __( 'Declined',   'fiscaat' ),
	) + array_slice( $columns, $position, null, true );

	return $columns;
}

/**
 * Output control column content for admin periods list table
 *
 * @since 0.0.5
 *
 * @uses fct_get_period_record_count_unapproved()
 * @uses fct_get_period_record_count_declined()
 * @uses add_query_arg()
 * 
 * @param string $column Column name
 * @param int $period_id Account ID
 */
function fct_ctrl_admin_periods_column_data( $column, $period_id ) {

	// Check column name
	switch ( $column ) {

		// Unapproved record count
		case 'fct_period_record_count_unapproved' :
			if ( ( $count = fct_get_period_record_count_unapproved( $period_id ) ) && $count > 0 ) {
				printf( '<a href="%s">%s</a>', add_query_arg( array( 'page' => 'fct-records', 'fct_period_id' => $period_id, 'post_status' => 'unapproved' ) ), $count );
			} else {
				echo $count;
			}
			break;

		// Declined record count
		case 'fct_period_record_count_declined' :
			if ( ( $count = fct_get_period_record_count_declined( $period_id ) ) && $count > 0 ) {
				printf( '<a href="%s">%s</a>', add_query_arg( array( 'page' => 'fct-records', 'fct_period_id' => $period_id, 'post_status' => fct_get_declined_status_id() ) ), $count );
			} else {
				echo $count;
			}
			break;
	}
}

/**
 * Make control column headers sortable on admin periods list table
 *
 * @since 0.0.5
 * 
 * @param array $columns Sortable columns
 * @return array Sortable columns
 */
function fct_ctrl_admin_periods_sortable_columns( $columns ) {
	return array_merge( $columns, array(
		'fct_period_record_count_unapproved' => array( 'record_count_unapproved', true ),
		'fct_period_record_count_declined'   => array( 'record_count_declined',   true ),
	) );
}

/**
 * Adjust the request query to order by control column
 *
 * @since 0.0.5
 * 
 * @param array $query_vars Query vars
 * @return array Query vars
 */
function fct_ctrl_admin_periods_request( $query_vars ) {

	// Handle ordering by unapproved record count
	if ( isset( $_REQUEST['orderby'] ) && 'record_count_unapproved' == $_REQUEST['orderby'] ) {
		$query_vars['meta_key'] = '_fct_record_count_unapproved';
		$query_vars['orderby']  = 'meta_value_num';
		$query_vars['order']    = isset( $_REQUEST['order'] ) ? strtoupper( $_REQUEST['order'] ) : 'DESC';

	// Handle ordering by declined record count
	} elseif ( isset( $_REQUEST['orderby'] ) && 'record_count_declined' == $_REQUEST['orderby'] ) {
		$query_vars['meta_key'] = '_fct_record_count_declined';
		$query_vars['orderby']  = 'meta_value_num';
		$query_vars['order']    = isset( $_REQUEST['order'] ) ? strtoupper( $_REQUEST['order'] ) : 'DESC';
	}

	// Return manipulated query_vars
	return $query_vars;
}

/** Accounts ******************************************************************/

/**
 * Add control columns to admin accounts list table
 *
 * @since 0.0.5
 * 
 * @param array $columns Columns
 * @return array Columns
 */
function fct_ctrl_admin_accounts_columns( $columns ) {

	// Group record counts. array_splice only does numeric keys
	$position = array_search( 'fct_account_record_count', array_keys( $columns ) ) + 1;
	$columns  = array_slice( $columns, 0, $position, true ) + array( 
		'fct_account_record_count_unapproved' => __( 'Unapproved', 'fiscaat' ),
		'fct_account_record_count_declined'   => __( 'Declined',   'fiscaat' ),
	) + array_slice( $columns, $position, null, true );

	return $columns;
}

/**
 * Output control column content for admin accounts list table
 *
 * @since 0.0.5
 *
 * @uses fct_get_account_record_count_unapproved()
 * @uses fct_get_account_record_count_declined()
 * @uses add_query_arg()
 * 
 * @param string $column Column name
 * @param int $account_id Account ID
 */
function fct_ctrl_admin_accounts_column_data( $column, $account_id ) {

	// Check column name
	switch ( $column ) {

		// Unapproved record count
		case 'fct_account_record_count_unapproved' :
			if ( ( $count = fct_get_account_record_count_unapproved( $account_id ) ) && $count > 0 ) {
				printf( '<a href="%s">%s</a>', add_query_arg( array( 'page' => 'fct-records', 'fct_account_id' => $account_id, 'post_status' => 'unapproved' ) ), $count );
			} else {
				echo $count;
			}
			break;

		// Declined record count
		case 'fct_account_record_count_declined' :
			if ( ( $count = fct_get_account_record_count_declined( $account_id ) ) && $count > 0 ) {
				printf( '<a href="%s">%s</a>', add_query_arg( array( 'page' => 'fct-records', 'fct_account_id' => $account_id, 'post_status' => fct_get_declined_status_id() ) ), $count );
			} else {
				echo $count;
			}
			break;
	}
}

/**
 * Make control column headers sortable on admin accounts list table
 *
 * @since 0.0.5
 * 
 * @param array $columns Sortable columns
 * @return array Sortable columns
 */
function fct_ctrl_admin_accounts_sortable_columns( $columns ) {
	return array_merge( $columns, array(
		'fct_account_record_count_unapproved' => array( 'record_count_unapproved', true ),
		'fct_account_record_count_declined'   => array( 'record_count_declined',   true ),
	) );
}

/**
 * Adjust the request query to order by control column
 *
 * @since 0.0.5
 * 
 * @param array $query_vars Query vars
 * @return array Query vars
 */
function fct_ctrl_admin_accounts_request( $query_vars ) {

	// Handle ordering by unapproved record count
	if ( isset( $_REQUEST['orderby'] ) && 'record_count_unapproved' == $_REQUEST['orderby'] ) {
		$query_vars['meta_key'] = '_fct_record_count_unapproved';
		$query_vars['orderby']  = 'meta_value_num';
		$query_vars['order']    = isset( $_REQUEST['order'] ) ? strtoupper( $_REQUEST['order'] ) : 'DESC';

	// Handle ordering by declined record count
	} elseif ( isset( $_REQUEST['orderby'] ) && 'record_count_declined' == $_REQUEST['orderby'] ) {
		$query_vars['meta_key'] = '_fct_record_count_declined';
		$query_vars['orderby']  = 'meta_value_num';
		$query_vars['order']    = isset( $_REQUEST['order'] ) ? strtoupper( $_REQUEST['order'] ) : 'DESC';
	}

	// Return manipulated query_vars
	return $query_vars;
}

/** Records *******************************************************************/

/**
 * Toggle record
 *
 * Handles the admin-side approving/disapproving of records
 *
 * @since 0.0.9
 *
 * @uses current_user_can() To check if the user is capable of editing
 *                           the record
 * @uses check_admin_referer() To verify the nonce and check referer
 * @uses fct_is_record_approved() To check if the record is marked as approved
 * @uses fct_decline_record() To unmark the record as declined
 * @uses fct_approve_record() To mark the record as approved
 *
 * @param array $result Toggle result
 * @param string $action Toggle action name
 * @param int $record_id Record ID
 * @return array Result params
 */
function fct_ctrl_admin_records_toggle_record( $result, $action, $record_id ) {

	// Toggle record approval
	if ( 'fct_toggle_record_approve' == $action ) {
		check_admin_referer( 'approve-record_' . $record_id );

		if ( ! current_user_can( 'approve_record', $record_id ) ) // What is the user doing here?
			wp_die( __( 'You do not have the permission to do that!', 'fiscaat' ) );

		// Either approve or decline based on current status
		$approve = fct_is_record_approved( $record_id );
		$message = $approve ? 'declined' : 'approved';
		$success = $approve ? fct_decline_record( $record_id ) : fct_approve_record( $record_id );

		// Setup retval
		$result = array( $success, $message );
	}

	return $result;
}

/**
 * Handle record approval notices
 *
 * @since 0.0.9
 * 
 * @uses fct_get_record() To get the record
 * @uses fct_get_record_title() To get the record title of the record
 * @uses esc_html() To sanitize the record title
 *
 * @param string $message The toggle notice message
 * @param int $record_id Record ID
 * @param string $notice The action notice executed
 * @param bool $is_failure Whether the action was successful
 * @return string Toggle notice message
 */
function fct_ctrl_admin_records_toggle_record_notice( $message, $record_id, $notice, $is_failure ) {
	$record_id    = fct_get_record_id( $record_id );
	$record_title = esc_html( fct_get_record_title( $record_id ) );

	// Check toggle notice
	switch ( $notice ) {
		case 'approved' :
			$message = $is_failure == true ? sprintf( __( 'There was a problem approving the record "%1$s".', 'fiscaat' ), $record_title ) : sprintf( __( 'Record "%1$s" successfully approved.', 'fiscaat' ), $record_title );
			break;

		case 'declined' :
			$message = $is_failure == true ? sprintf( __( 'There was a problem declining the record "%1$s".', 'fiscaat' ), $record_title ) : sprintf( __( 'Record "%1$s" successfully declined.', 'fiscaat' ), $record_title );
			break;
	}

	return $message;
}

/**
 * Adjust the request query to return the right records
 *
 * @since 0.0.9
 *
 * @uses fct_get_public_status_id()
 * @uses fct_get_declined_status_id()
 * 
 * @param array $query_vars Query vars
 * @return array Query vars
 */
function fct_ctrl_admin_records_request( $query_vars ) {

	// Handle unapproved quasi post status
	if ( isset( $_REQUEST['post_status'] ) && 'unapproved' == $_REQUEST['post_status'] ) {

		// Select publish and declined post status
		$query_vars['post_status'] = implode( ',', array( fct_get_public_status_id(), fct_get_declined_status_id() ) );
	}

	// Return manipulated query_vars
	return $query_vars;
}
