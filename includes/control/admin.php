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
 * Add control column headers to admin periods list table
 *
 * @since 0.0.5
 * 
 * @param array $columns Column headers
 * @return array Column headers
 *
 * @todo Fix column order
 */
function fct_ctrl_admin_periods_column_headers( $columns ) {
	return array_merge( $columns, array(
		'fct_period_record_count_declined'   => __( 'Declined',   'fiscaat' ),
		'fct_period_record_count_unapproved' => __( 'Unapproved', 'fiscaat' ),
	) );
}

/**
 * Output control column content for admin periods list table
 *
 * @since 0.0.5
 * 
 * @param string $column Column name
 * @param int $period_id Account ID
 */
function fct_ctrl_admin_periods_column_data( $column, $period_id ) {

	// Check column name
	switch ( $column ) {

		// Declined record count
		case 'fct_period_record_count_declined' :
			fct_period_record_count_declined( $period_id );
			break;

		// Unapproved record count
		case 'fct_period_record_count_unapproved' :
			fct_period_record_count_unapproved( $period_id );
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
		'fct_period_record_count_declined'   => 'record_count_declined',
		'fct_period_record_count_unapproved' => 'record_count_unapproved',
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

	// Handle ordering by declined record count
	if ( isset( $_REQUEST['orderby'] ) && 'record_count_declined' == $_REQUEST['orderby'] ) {
		$query_vars['meta_key'] = '_fct_record_count_declined';
		$query_vars['orderby']  = 'meta_value_num';
		$query_vars['order']    = isset( $_REQUEST['order'] ) ? strtoupper( $_REQUEST['order'] ) : 'DESC';

	// Handle ordering by unapproved record count
	} elseif ( isset( $_REQUEST['orderby'] ) && 'record_count_unapproved' == $_REQUEST['orderby'] ) {
		$query_vars['meta_key'] = '_fct_record_count_unapproved';
		$query_vars['orderby']  = 'meta_value_num';
		$query_vars['order']    = isset( $_REQUEST['order'] ) ? strtoupper( $_REQUEST['order'] ) : 'DESC';
	}

	// Return manipulated query_vars
	return $query_vars;
}

/** Accounts ******************************************************************/

/**
 * Add control column headers to admin accounts list table
 *
 * @since 0.0.5
 * 
 * @param array $columns Column headers
 * @return array Column headers
 *
 * @todo Fix column order
 */
function fct_ctrl_admin_accounts_column_headers( $columns ) {
	return array_merge( $columns, array(
		'fct_account_record_count_declined'   => __( 'Declined',   'fiscaat' ),
		'fct_account_record_count_unapproved' => __( 'Unapproved', 'fiscaat' ),
	) );
}

/**
 * Output control column content for admin accounts list table
 *
 * @since 0.0.5
 * 
 * @param string $column Column name
 * @param int $account_id Account ID
 */
function fct_ctrl_admin_accounts_column_data( $column, $account_id ) {

	// Check column name
	switch ( $column ) {

		// Declined record count
		case 'fct_account_record_count_declined' :
			fct_account_record_count_declined( $account_id );
			break;

		// Unapproved record count
		case 'fct_account_record_count_unapproved' :
			fct_account_record_count_unapproved( $account_id );
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
		'fct_account_record_count_declined'   => 'record_count_declined',
		'fct_account_record_count_unapproved' => 'record_count_unapproved',
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

	// Handle ordering by declined record count
	if ( isset( $_REQUEST['orderby'] ) && 'record_count_declined' == $_REQUEST['orderby'] ) {
		$query_vars['meta_key'] = '_fct_record_count_declined';
		$query_vars['orderby']  = 'meta_value_num';
		$query_vars['order']    = isset( $_REQUEST['order'] ) ? strtoupper( $_REQUEST['order'] ) : 'DESC';

	// Handle ordering by unapproved record count
	} elseif ( isset( $_REQUEST['orderby'] ) && 'record_count_unapproved' == $_REQUEST['orderby'] ) {
		$query_vars['meta_key'] = '_fct_record_count_unapproved';
		$query_vars['orderby']  = 'meta_value_num';
		$query_vars['order']    = isset( $_REQUEST['order'] ) ? strtoupper( $_REQUEST['order'] ) : 'DESC';
	}

	// Return manipulated query_vars
	return $query_vars;
}

/** Records *******************************************************************/
