<?php

/**
 * Fiscaat Admin Metaboxes
 *
 * @package Fiscaat
 * @subpackage Administration
 */

/** Dashboard *****************************************************************/

/**
 * Fiscaat Dashboard Right Now Widget
 *
 * Adds a dashboard widget with year statistics
 *
 * @uses fiscaat_get_version() To get the current Fiscaat version
 * @uses fiscaat_get_statistics() To get the year statistics
 * @uses current_user_can() To check if the user is capable of doing things
 * @uses fiscaat_get_year_post_type() To get the year post type
 * @uses fiscaat_get_account_post_type() To get the account post type
 * @uses fiscaat_get_record_post_type() To get the record post type
 * @uses get_admin_url() To get the administration url
 * @uses add_query_arg() To add custom args to the url
 * @uses do_action() Calls 'fiscaat_dashboard_widget_right_now_content_table_end'
 *                    below the content table
 * @uses do_action() Calls 'fiscaat_dashboard_widget_right_now_table_end'
 *                    below the discussion table
 * @uses do_action() Calls 'fiscaat_dashboard_widget_right_now_discussion_table_end'
 *                    below the discussion table
 * @uses do_action() Calls 'fiscaat_dashboard_widget_right_now_end' below the widget
 */
function fiscaat_dashboard_widget_right_now() {

	// Get the statistics and extract them
	extract( fiscaat_get_statistics(), EXTR_SKIP ); ?>

	<div class="table table_content">

		<p class="sub"><?php _e( 'Content', 'fiscaat' ); ?></p>

		<table>

			<tr class="first">

				<?php
					$num  = $year_count;
					$text = _n( 'Year', 'Years', $year_count, 'fiscaat' );
					if ( current_user_can( 'fiscaat_spectate' ) ) {
						$link = add_query_arg( array( 'post_type' => fiscaat_get_year_post_type() ), get_admin_url( null, 'edit.php' ) );
						$num  = '<a href="' . $link . '">' . $num  . '</a>';
						$text = '<a href="' . $link . '">' . $text . '</a>';
					}
				?>

				<td class="first b b-accounts"><?php echo $num; ?></td>
				<td class="t accounts"><?php echo $text; ?></td>

			</tr>

			<tr>

				<?php
					$num  = $account_count;
					$text = _n( 'Account', 'Accounts', $account_count, 'fiscaat' );
					if ( current_user_can( 'fiscaat_spectate' ) ) {
						$link = add_query_arg( array( 'post_type' => fiscaat_get_account_post_type() ), get_admin_url( null, 'edit.php' ) );
						$num  = '<a href="' . $link . '">' . $num  . '</a>';
						$text = '<a href="' . $link . '">' . $text . '</a>';
					}
				?>

				<td class="first b b-accounts"><?php echo $num; ?></td>
				<td class="t accounts"><?php echo $text; ?></td>

			</tr>

			<tr>

				<?php
					$num  = $record_count;
					$text = _n( 'Record', 'Records', $record_count, 'fiscaat' );
					if ( current_user_can( 'fiscaat_spectate' ) ) {
						$link = add_query_arg( array( 'post_type' => fiscaat_get_record_post_type() ), get_admin_url( null, 'edit.php' ) );
						$num  = '<a href="' . $link . '">' . $num  . '</a>';
						$text = '<a href="' . $link . '">' . $text . '</a>';
					}
				?>

				<td class="first b b-accounts"><?php echo $num; ?></td>
				<td class="t accounts"><?php echo $text; ?></td>

			</tr>

			<tr>

				<?php
					$num  = $fiscus_count;
					$text = _n( 'Fiscus', 'Fisci', $fiscus_count, 'fiscaat' );
				?>

				<td class="first b b-accounts"><?php echo $num; ?></td>
				<td class="t accounts"><?php echo $text; ?></td>

			</tr>

			<tr>

				<?php
					$num  = $controller_count;
					$text = _n( 'Controller', 'Controllers', $controller_count, 'fiscaat' );
				?>

				<td class="first b b-accounts"><?php echo $num; ?></td>
				<td class="t accounts"><?php echo $text; ?></td>

			</tr>

			<tr>

				<?php
					$num  = $spectator_count;
					$text = _n( 'Spectator', 'Spectators', $spectator_count, 'fiscaat' );
				?>

				<td class="first b b-accounts"><?php echo $num; ?></td>
				<td class="t accounts"><?php echo $text; ?></td>

			</tr>

			<?php do_action( 'fiscaat_dashboard_widget_right_now_content_table_end' ); ?>

		</table>

	</div>


	<div class="table table_discussion">

		<p class="sub"><?php _e( 'Current Year', 'fiscaat' ); ?></p>

		<table>

			<tr class="first">

				<?php
					$num  = fiscaat_get_currency_format( $current_to_balance, true );
					$text = __( 'To Balance', 'fiscaat' );
					if ( current_user_can( 'fiscaat_spectate' ) ) {
						$link = add_query_arg( array( 'post_type' => fiscaat_get_account_post_type(), 'fiscaat_year_id' => fiscaat_get_current_year_id() ), get_admin_url( null, 'edit.php' ) );
						$class = $current_to_balance < 0 ? ' class="spam"' : ''; // Coloring
						$num  = '<a'. $class .' href="' . $link . '">' . $num  . '</a>';
						$text = '<a href="' . $link . '">' . $text . '</a>';
					}
				?>

				<td class="b b-to_balance"><span class="total-count"><?php echo $num; ?></span></td>
				<td class="last t to_balance"><?php echo $text; ?></td>

			</tr>

			<tr>

				<?php
					$num  = $current_record_count;
					$text = _n( 'Record', 'Records', $current_record_count, 'fiscaat' );
					if ( current_user_can( 'fiscaat_spectate' ) ) {
						$link = add_query_arg( array( 'post_type' => fiscaat_get_record_post_type(), 'fiscaat_year_id' => fiscaat_get_current_year_id() ), get_admin_url( null, 'edit.php' ) );
						$num  = '<a href="' . $link . '">' . $num  . '</a>';
						$text = '<a href="' . $link . '">' . $text . '</a>';
					}
				?>

				<td class="b b-records"><?php echo $num; ?></td>
				<td class="last t records"><?php echo $text; ?></td>

			</tr>

			<?php if ( fiscaat_is_control_active() ) : ?>

			<tr>

				<?php
					$num  = $current_approved_count;
					$text = __( 'Approved', 'fiscaat' );
					if ( current_user_can( 'fiscaat_spectate' ) ) {
						$link = add_query_arg( array( 'post_type' => fiscaat_get_record_post_type(), 'fiscaat_year_id' => fiscaat_get_current_year_id(), 'approval' => 1 ), get_admin_url( null, 'edit.php' ) );
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
					if ( current_user_can( 'fiscaat_spectate' ) ) {
						$link = add_query_arg( array( 'post_type' => fiscaat_get_record_post_type(), 'fiscaat_year_id' => fiscaat_get_current_year_id(), 'approval' => 0 ), get_admin_url( null, 'edit.php' ) );
						$num  = '<a href="' . $link . '">' . $num  . '</a>';
						$text = '<a class="waiting" href="' . $link . '">' . $text . '</a>';
					}
				?>

				<td class="b b-records-unapproved"><?php echo $num; ?></td>
				<td class="last t records-unapproved"><?php echo $text; ?></td>

			</tr>

			<tr>

				<?php
					$num  = $current_disapproved_count;
					$text = __( 'Disapproved', 'fiscaat' );
					if ( current_user_can( 'fiscaat_spectate' ) ) {
						$link = add_query_arg( array( 'post_type' => fiscaat_get_record_post_type(), 'fiscaat_year_id' => fiscaat_get_current_year_id(), 'approval' => 2 ), get_admin_url( null, 'edit.php' ) );
						$num  = '<a href="' . $link . '">' . $num  . '</a>';
						$text = '<a class="spam" href="' . $link . '">' . $text . '</a>';
					}
				?>

				<td class="b b-records-disapproved"><?php echo $num; ?></td>
				<td class="last t records-disapproved"><?php echo $text; ?></td>

			</tr>

			<?php endif; ?>

			<?php if ( fiscaat_is_comments_active() ) : ?>

			<tr>

				<?php
					$num  = $current_comment_count;
					$text = __( 'Comment', 'Comments', $current_comment_count, 'fiscaat' );
					if ( current_user_can( 'fiscaat_spectate' ) ) {
						$link = get_admin_url( null, 'users.php' ); // @todo Comment section
						$num  = '<a href="' . $link . '">' . $num  . '</a>';
						$text = '<a href="' . $link . '">' . $text . '</a>';
					}
				?>

				<td class="b b-to_balance"><span class="total-count"><?php echo $num; ?></span></td>
				<td class="last t to_balance"><?php echo $text; ?></td>

			</tr>

			<?php endif; ?>

			<?php do_action( 'fiscaat_dashboard_widget_right_now_discussion_table_end' ); ?>

		</table>

	</div>

	<?php do_action( 'fiscaat_dashboard_widget_right_now_table_end' ); ?>

	<div class="versions">

		<span id="wp-version-message">
			<?php printf( __( 'You are using <span class="b">Fiscaat %s</span>.', 'fiscaat' ), fiscaat_get_version() ); ?>
		</span>

	</div>

	<br class="clear" />

	<?php

	do_action( 'fiscaat_dashboard_widget_right_now_end' );
}

/** Years ********************************************************************/

/**
 * Year metabox
 *
 * The metabox that holds all of the additional year information
 *
 * @uses fiscaat_is_year_open() To check if a year is open or not
 * @uses fiscaat_is_year_closed() To check if a year is closed or not
 * @uses fiscaat_dropdown() To show a dropdown of the years for year parent
 * @uses do_action() Calls 'fiscaat_year_metabox'
 */
function fiscaat_year_metabox() {

	// Post ID
	$post_id     = get_the_ID();

	/** Status ****************************************************************/

	?>

	<p>
		<strong class="label"><?php _e( 'Status:', 'fiscaat' ); ?></strong>
		<label class="screen-reader-text" for="fiscaat_year_status_select"><?php _e( 'Status:', 'fiscaat' ) ?></label>
		<?php fiscaat_form_year_status_dropdown( $post_id ); ?>
	</p>

	<?php

	/** Start date ************************************************************/

	// Not on post-new.php
	if ( 'add' != get_current_screen()->action ) : ?>

	<p>
		<strong class="label"><?php _e( 'From:', 'Year start date', 'fiscaat' ); ?></strong>
		<label class="screen-reader-text" for="fiscaat_year_started"><?php _e( 'Start date:', 'fiscaat' ) ?></label>
		<?php fiscaat_form_year_started( $post_id ); ?>
	</p>

	<?php endif;

	/** Close date ************************************************************/

	if ( fiscaat_is_year_closed( $post_id ) ) : ?>

	<p>
		<strong class="label"><?php _e( 'To:', 'Year close date', 'fiscaat' ); ?></strong>
		<label class="screen-reader-text" for="fiscaat_year_closed"><?php _e( 'Close date:', 'fiscaat' ) ?></label>
		<?php fiscaat_form_year_closed( $post_id ); ?>

	</p>

	<?php endif;

	wp_nonce_field( 'fiscaat_year_metabox_save', 'fiscaat_year_metabox' );
	do_action( 'fiscaat_year_metabox', $post_id );
}

/** Accounts ********************************************************************/

/**
 * Account metabox
 *
 * The metabox that holds all of the additional account information
 *
 * @uses fiscaat_get_account_year_id() To get the account year id
 * @uses do_action() Calls 'fiscaat_account_metabox'
 */
function fiscaat_account_metabox() {

	// Post ID
	$post_id = get_the_ID();
	$year_id = fiscaat_get_account_year_id( $post_id );
	$year_id = ! empty( $year_id ) ? $year_id : fiscaat_get_current_year_id();

	/** Ledger ID *************************************************************/

	?>
	
	<p>
		<strong class="label"><?php _e( 'Number:', 'fiscaat' ); ?></strong>
		<label class="screen-reader-text" for="fiscaat_account_ledger_id"><?php _e( 'Account Number', 'fiscaat' ); ?></label>
		<input name="fiscaat_account_ledger_id" id="fiscaat_account_ledger_id" type="text" value="<?php echo esc_attr( fiscaat_get_account_ledger_id( $post_id ) ); ?>" <?php disabled( ! current_user_can( 'fiscaat' ) || fiscaat_is_account_closed( $post_id ) ); ?> />
		<img class="ajax-loading" src="<?php echo admin_url(); ?>images/wpspin_light.gif" />
	</p>

	<?php

	/** Account type **********************************************************/

	?>
	
	<p>
		<strong class="label"><?php _e( 'Type:', 'fiscaat' ); ?></strong>
		<label class="screen-reader-text" for="fiscaat_account_account_type"><?php _e( 'Account Type', 'fiscaat' ); ?></label>
		<?php fiscaat_form_account_account_type_select( $post_id ); ?>
	</p>

	<?php

	/** Year ******************************************************************/

	?>

	<p>
		<strong class="label"><?php _e( 'Year:', 'fiscaat' ); ?></strong>
		<label class="screen-reader-text" for="parent_id"><?php _e( 'Year', 'fiscaat' ); ?></label>
		<?php fiscaat_dropdown( array(
			'selected'           => $year_id,

			// Output-related
			'select_id'          => 'parent_id',
			'show_none'          => false,
			'none_found'         => false,
			'disabled'           => true,
		) ); ?>		
	</p>

	<?php
	wp_nonce_field( 'fiscaat_account_metabox_save', 'fiscaat_account_metabox' );
	do_action( 'fiscaat_account_metabox', $post_id );
}

/** Records *******************************************************************/

/**
 * Record metabox
 *
 * The metabox that holds all of the additional record information
 *
 * @uses fiscaat_get_account_post_type() To get the account post type
 * @uses do_action() Calls 'fiscaat_record_metabox'
 */
function fiscaat_record_metabox() {

	// Post ID
	$post_id = get_the_ID();

	// Get some meta
	$record_account_id = fiscaat_get_record_account_id( $post_id );
	$record_year_id    = fiscaat_get_record_year_id( $post_id );
	$record_year_id    = ! empty( $record_year_id ) ? $record_year_id : fiscaat_get_current_year_id();

	/** Year ******************************************************************/

	?>

	<p>
		<strong class="label"><?php _e( 'Year:', 'fiscaat' ); ?></strong>
		<label class="screen-reader-text" for="fiscaat_record_year_id"><?php _e( 'Year', 'fiscaat' ); ?></label>
		<?php fiscaat_dropdown( array(
			'selected'           => $record_year_id,

			// Output-related
			'select_id'          => 'fiscaat_year_id',
			'show_none'          => false,
			'none_found'         => false,
			'disabled'           => true,
		) ); ?>		
	</p>

	<?php

	/** Account ***************************************************************/

	?>
	
	<p>
		<strong class="label"><?php _e( 'Account Number:', 'fiscaat' ); ?></strong>
		<label class="screen-reader-text" for="fiscaat_record_account_ledger_id"><?php _e( 'Account Number', 'fiscaat' ); ?></label>
		<?php fiscaat_ledger_dropdown( array(
			'selected'           => $record_account_id,
			'child_of'           => $record_year_id,

			// Output-related
			'select_id'          => 'fiscaat_record_account_ledger_id',
			'show_none'          => __( '&mdash; No account &mdash;', 'fiscaat' ),
			'none_found'         => false,
			'disabled'           => ! current_user_can( 'fiscaat' ) || fiscaat_is_account_closed( $record_account_id ),
		) ); ?>

		<br/>

		<strong class="label"><?php _e( 'Account Title:', 'fiscaat' ); ?></strong>
		<label class="screen-reader-text" for="parent_id"><?php _e( 'Account Title', 'fiscaat' ); ?></label>
		<?php fiscaat_account_dropdown( array(
			'selected'           => $record_account_id,
			'child_of'           => $record_year_id,

			// Output-related
			'select_id'          => 'parent_id',
			'show_none'          => __( '&mdash; No account &mdash;', 'fiscaat' ),
			'none_found'         => false,
			'disabled'           => ! current_user_can( 'fiscaat' ) || fiscaat_is_account_closed( $record_account_id ),
		) ); ?>
	</p>

	<?php

	/** Value *****************************************************************/

	?>
	
	<p>
		<strong class="label"><?php _e( 'Value:', 'fiscaat' ); ?></strong>
		<label class="screen-reader-text" for="fiscaat_record_value"><?php _e( 'Value', 'fiscaat' ); ?></label>
		<input name="fiscaat_record_value" id="fiscaat_record_value" type="text" value="<?php echo esc_attr( fiscaat_get_record_value( $post_id ) ); ?>" disabled="disabled" />
	</p>

	<?php

	/** Value type ************************************************************/

	?>
	
	<p>
		<strong class="label"><?php _e( 'Type:', 'fiscaat' ); ?></strong>
		<label class="screen-reader-text" for="fiscaat_record_value_type"><?php _e( 'Value Type', 'fiscaat' ); ?></label>
		<?php fiscaat_form_record_value_type_select( $post_id, true ); ?>
	</p>

	<?php

	/** Offset account ********************************************************/

	?>
	
	<p>
		<strong class="label"><?php _e( 'Offset Account:', 'fiscaat' ); ?></strong>
		<label class="screen-reader-text" for="fiscaat_record_offset_account"><?php _e( 'Offset Account number', 'fiscaat' ); ?></label>
		<input name="fiscaat_record_offset_account" id="fiscaat_record_offset_account" type="text" value="<?php echo esc_attr( fiscaat_get_record_offset_account( $post_id ) ); ?>" />
	</p>

	<?php

	/** Status ****************************************************************/

	?>

	<p>
		<strong class="label"><?php _e( 'Status:', 'fiscaat' ); ?></strong>
		<label class="screen-reader-text" for="fiscaat_record_status"><?php _e( 'Status', 'fiscaat' ); ?></label>
		<?php fiscaat_form_record_status_dropdown( $post_id ); ?>
	</p>

	<?php 
	wp_nonce_field( 'fiscaat_record_metabox_save', 'fiscaat_record_metabox' );
	do_action( 'fiscaat_record_metabox', $post_id );
}

/** Comments ******************************************************************/


/** Spectators ****************************************************************/

