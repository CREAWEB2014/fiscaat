<?php

/**
 * Fiscaat Control Admin Functions
 *
 * @package Fiscaat
 * @subpackage Administration
 */

/** Dashboard *****************************************************************/

/**
 * Output Control statistics in Fiscaat Dashboard Right Now Widget
 *
 * @uses fct_get_statistics() To get the year statistics
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
				$link = add_query_arg( array( 'post_type' => fct_get_record_post_type(), 'fct_year_id' => fct_get_current_year_id(), 'approval' => 1 ), get_admin_url( null, 'edit.php' ) );
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
				$link = add_query_arg( array( 'post_type' => fct_get_record_post_type(), 'fct_year_id' => fct_get_current_year_id(), 'approval' => 0 ), get_admin_url( null, 'edit.php' ) );
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
				$link = add_query_arg( array( 'post_type' => fct_get_record_post_type(), 'fct_year_id' => fct_get_current_year_id(), 'approval' => 2 ), get_admin_url( null, 'edit.php' ) );
				$num  = '<a href="' . $link . '">' . $num  . '</a>';
				$text = '<a class="spam" href="' . $link . '">' . $text . '</a>';
			}
		?>

		<td class="b b-records-declined"><?php echo $num; ?></td>
		<td class="last t records-declined"><?php echo $text; ?></td>
	</tr>

	<?php
}