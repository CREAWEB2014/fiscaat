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
 * Adds a dashboard widget with period statistics
 *
 * @uses fct_get_version() To get the current Fiscaat version
 * @uses fct_get_statistics() To get the period statistics
 * @uses current_user_can() To check if the user is capable of doing things
 * @uses fct_get_period_post_type() To get the period post type
 * @uses fct_get_account_post_type() To get the account post type
 * @uses fct_get_record_post_type() To get the record post type
 * @uses get_admin_url() To get the administration url
 * @uses add_query_arg() To add custom args to the url
 * @uses do_action() Calls 'fct_dashboard_widget_right_now_content_table_end'
 *                    below the content table
 * @uses do_action() Calls 'fct_dashboard_widget_right_now_table_end'
 *                    below the discussion table
 * @uses do_action() Calls 'fct_dashboard_widget_right_now_discussion_table_end'
 *                    below the discussion table
 * @uses do_action() Calls 'fct_dashboard_widget_right_now_end' below the widget
 */
function fct_dashboard_widget_right_now() {

	// Get the statistics and extract them
	extract( fct_get_statistics(), EXTR_SKIP ); ?>

	<div class="table table_content">
		<p class="sub"><?php _e( 'Content', 'fiscaat' ); ?></p>

		<table>
			<tr class="first">
				<?php
					$num  = $period_count;
					$text = _n( 'Period', 'Periods', $period_count, 'fiscaat' );
					if ( current_user_can( 'fct_spectate' ) ) {
						$link = add_query_arg( array( 'post_type' => fct_get_period_post_type() ), get_admin_url( null, 'edit.php' ) );
						$num  = '<a href="' . $link . '">' . $num  . '</a>';
						$text = '<a href="' . $link . '">' . $text . '</a>';
					}
				?>

				<td class="first b b-periods"><?php echo $num; ?></td>
				<td class="t periods"><?php echo $text; ?></td>
			</tr>

			<tr>
				<?php
					$num  = $account_count;
					$text = _n( 'Account', 'Accounts', $account_count, 'fiscaat' );
					if ( current_user_can( 'fct_spectate' ) ) {
						$link = add_query_arg( array( 'post_type' => fct_get_account_post_type() ), get_admin_url( null, 'edit.php' ) );
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
					if ( current_user_can( 'fct_spectate' ) ) {
						$link = add_query_arg( array( 'post_type' => fct_get_record_post_type() ), get_admin_url( null, 'edit.php' ) );
						$num  = '<a href="' . $link . '">' . $num  . '</a>';
						$text = '<a href="' . $link . '">' . $text . '</a>';
					}
				?>

				<td class="first b b-records"><?php echo $num; ?></td>
				<td class="t records"><?php echo $text; ?></td>
			</tr>

			<tr>
				<?php
					$num  = $fiscus_count;
					$text = _n( 'Fiscus', 'Fisci', $fiscus_count, 'fiscaat' );
				?>

				<td class="first b b-users"><?php echo $num; ?></td>
				<td class="t users"><?php echo $text; ?></td>
			</tr>

			<tr>
				<?php
					$num  = $spectator_count;
					$text = _n( 'Spectator', 'Spectators', $spectator_count, 'fiscaat' );
				?>

				<td class="first b b-users"><?php echo $num; ?></td>
				<td class="t users"><?php echo $text; ?></td>
			</tr>

			<?php do_action( 'fct_dashboard_widget_right_now_content_table_end' ); ?>
		</table>
	</div>

	<div class="table table_discussion">
		<p class="sub"><?php _e( 'Current Period', 'fiscaat' ); ?></p>

		<table>
			<tr class="first">
				<?php
					$num  = fct_get_currency_format( $current_end_value, true );
					$text = __( 'To Balance', 'fiscaat' );
					if ( current_user_can( 'fct_spectate' ) ) {
						$link = add_query_arg( array( 'post_type' => fct_get_account_post_type(), 'fct_period_id' => fct_get_current_period_id() ), get_admin_url( null, 'edit.php' ) );
						$class = $current_end_value < 0 ? ' class="spam"' : ''; // Coloring
						$num  = '<a'. $class .' href="' . $link . '">' . $num  . '</a>';
						$text = '<a href="' . $link . '">' . $text . '</a>';
					}
				?>

				<td class="b b-end_value"><span class="total-count"><?php echo $num; ?></span></td>
				<td class="last t end_value"><?php echo $text; ?></td>
			</tr>

			<tr>
				<?php
					$num  = $current_record_count;
					$text = _n( 'Record', 'Records', $current_record_count, 'fiscaat' );
					if ( current_user_can( 'fct_spectate' ) ) {
						$link = add_query_arg( array( 'post_type' => fct_get_record_post_type(), 'fct_period_id' => fct_get_current_period_id() ), get_admin_url( null, 'edit.php' ) );
						$num  = '<a href="' . $link . '">' . $num  . '</a>';
						$text = '<a href="' . $link . '">' . $text . '</a>';
					}
				?>

				<td class="b b-records"><?php echo $num; ?></td>
				<td class="last t records"><?php echo $text; ?></td>
			</tr>

			<?php if ( fct_is_comments_active() ) : ?>

			<tr>
				<?php
					$num  = $current_comment_count;
					$text = __( 'Comment', 'Comments', $current_comment_count, 'fiscaat' );
					if ( current_user_can( 'fct_spectate' ) ) {
						$link = get_admin_url( null, 'users.php' ); // @todo Comment section
						$num  = '<a href="' . $link . '">' . $num  . '</a>';
						$text = '<a href="' . $link . '">' . $text . '</a>';
					}
				?>

				<td class="b b-end_value"><span class="total-count"><?php echo $num; ?></span></td>
				<td class="last t end_value"><?php echo $text; ?></td>
			</tr>

			<?php endif; ?>

			<?php do_action( 'fct_dashboard_widget_right_now_discussion_table_end' ); ?>
		</table>
	</div>

	<?php do_action( 'fct_dashboard_widget_right_now_table_end' ); ?>

	<div class="versions">

		<span id="wp-version-message">
			<?php printf( __( 'You are using <span class="b">Fiscaat %s</span>.', 'fiscaat' ), fct_get_version() ); ?>
		</span>

	</div>

	<br class="clear" />

	<?php

	do_action( 'fct_dashboard_widget_right_now_end' );
}

/** Periods ********************************************************************/

/**
 * Period metabox
 *
 * The metabox that holds all of the additional period information
 *
 * @uses fct_is_period_open() To check if a period is open or not
 * @uses fct_is_period_closed() To check if a period is closed or not
 * @uses fct_dropdown() To show a dropdown of the periods for period parent
 * @uses do_action() Calls 'fct_period_metabox'
 */
function fct_period_metabox() {

	// Post ID
	$post_id     = get_the_ID();

	/** Status ****************************************************************/

	?>

	<p>
		<strong class="label"><?php _e( 'Status:', 'fiscaat' ); ?></strong>
		<label class="screen-reader-text" for="fct_period_status_select"><?php _e( 'Status:', 'fiscaat' ) ?></label>
		<?php fct_form_period_status_dropdown( $post_id ); ?>
	</p>

	<?php

	/** Start date ************************************************************/

	// Not on post-new.php
	if ( 'add' != get_current_screen()->action ) : ?>

	<p>
		<strong class="label"><?php _e( 'Start:', 'Period start date', 'fiscaat' ); ?></strong>
		<label class="screen-reader-text" for="fct_period_started"><?php _e( 'Start date:', 'fiscaat' ) ?></label>
		<?php fct_form_period_started( $post_id ); ?>
	</p>

	<?php endif;

	/** Close date ************************************************************/

	if ( fct_is_period_closed( $post_id ) ) : ?>

	<p>
		<strong class="label"><?php _e( 'Closed:', 'Period close date', 'fiscaat' ); ?></strong>
		<label class="screen-reader-text" for="fct_period_closed"><?php _e( 'Close date:', 'fiscaat' ) ?></label>
		<?php fct_form_period_closed( $post_id ); ?>

	</p>

	<?php endif;

	wp_nonce_field( 'fct_period_metabox_save', 'fct_period_metabox' );
	do_action( 'fct_period_metabox', $post_id );
}

/** Accounts ********************************************************************/

/**
 * Account metabox
 *
 * The metabox that holds all of the additional account information
 *
 * @uses fct_get_account_period_id() To get the account period id
 * @uses do_action() Calls 'fct_account_metabox'
 */
function fct_account_metabox() {

	// Post ID
	$post_id = get_the_ID();
	$period_id = fct_get_account_period_id( $post_id );
	$period_id = ! empty( $period_id ) ? $period_id : fct_get_current_period_id();

	/** Ledger ID *************************************************************/

	?>
	
	<p>
		<strong class="label"><?php _e( 'No.:', 'fiscaat' ); ?></strong>
		<label class="screen-reader-text" for="fct_account_ledger_id"><?php _e( 'Account Number', 'fiscaat' ); ?></label>
		<input name="fct_account_ledger_id" id="fct_account_ledger_id" type="text" class="medium-text" value="<?php echo esc_attr( fct_get_account_ledger_id( $post_id ) ); ?>" <?php disabled( ! current_user_can( 'fiscaat' ) || fct_is_account_closed( $post_id ) ); ?> />
		<img class="ajax-loading" src="<?php echo admin_url(); ?>images/spinner.gif" />
	</p>

	<?php

	/** Account type **********************************************************/

	?>
	
	<p>
		<strong class="label"><?php _e( 'Type:', 'fiscaat' ); ?></strong>
		<label class="screen-reader-text" for="fct_account_type"><?php _e( 'Account Type', 'fiscaat' ); ?></label>
		<?php fct_form_account_type_select( $post_id ); ?>
	</p>

	<?php

	/** Period ******************************************************************/

	?>

	<p>
		<strong class="label"><?php _e( 'Period:', 'fiscaat' ); ?></strong>
		<label class="screen-reader-text" for="parent_id"><?php _e( 'Period', 'fiscaat' ); ?></label>
		<?php fct_dropdown( array(
			'selected'           => $period_id,

			// Output-related
			'select_id'          => 'parent_id',
			'show_none'          => false,
			'none_found'         => false,
			'disabled'           => true,
		) ); ?>		
	</p>

	<?php
	wp_nonce_field( 'fct_account_metabox_save', 'fct_account_metabox' );
	do_action( 'fct_account_metabox', $post_id );
}

/** Records *******************************************************************/

/**
 * Record metabox
 *
 * The metabox that holds all of the additional record information
 *
 * @uses fct_get_account_post_type() To get the account post type
 * @uses do_action() Calls 'fct_record_metabox'
 */
function fct_record_metabox() {

	// Post ID
	$post_id = get_the_ID();

	// Get some meta
	$record_account_id = fct_get_record_account_id( $post_id );
	$record_period_id    = fct_get_record_period_id( $post_id );
	$record_period_id    = ! empty( $record_period_id ) ? $record_period_id : fct_get_current_period_id();

	/** Period ******************************************************************/

	?>

	<p>
		<strong class="label"><?php _e( 'Period:', 'fiscaat' ); ?></strong>
		<label class="screen-reader-text" for="fct_record_period_id"><?php _e( 'Period', 'fiscaat' ); ?></label>
		<?php fct_dropdown( array(
			'selected'           => $record_period_id,

			// Output-related
			'select_id'          => 'fct_period_id',
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
		<label class="screen-reader-text" for="fct_record_account_ledger_id"><?php _e( 'Account Number', 'fiscaat' ); ?></label>
		<?php fct_ledger_dropdown( array(
			'selected'           => $record_account_id,
			'child_of'           => $record_period_id,

			// Output-related
			'select_id'          => 'fct_record_account_ledger_id',
			'show_none'          => __( '&mdash; No account &mdash;', 'fiscaat' ),
			'none_found'         => false,
			'disabled'           => ! current_user_can( 'fiscaat' ) || fct_is_account_closed( $record_account_id ),
		) ); ?>

		<br/>

		<strong class="label"><?php _e( 'Account Title:', 'fiscaat' ); ?></strong>
		<label class="screen-reader-text" for="parent_id"><?php _e( 'Account Title', 'fiscaat' ); ?></label>
		<?php fct_account_dropdown( array(
			'selected'           => $record_account_id,
			'child_of'           => $record_period_id,

			// Output-related
			'select_id'          => 'parent_id',
			'show_none'          => __( '&mdash; No account &mdash;', 'fiscaat' ),
			'none_found'         => false,
			'disabled'           => ! current_user_can( 'fiscaat' ) || fct_is_account_closed( $record_account_id ),
		) ); ?>
	</p>

	<?php

	/** Amount ****************************************************************/

	?>
	
	<p>
		<strong class="label"><?php _e( 'Amount:', 'fiscaat' ); ?></strong>
		<label class="screen-reader-text" for="fct_record_amount"><?php _e( 'Amount', 'fiscaat' ); ?></label>
		<input name="fct_record_amount" id="fct_record_amount" type="text" value="<?php echo esc_attr( fct_get_record_amount( $post_id ) ); ?>" disabled="disabled" />
	</p>

	<?php

	/** Record type ***********************************************************/

	?>
	
	<p>
		<strong class="label"><?php _ex( 'Type:', 'Record type input label', 'fiscaat' ); ?></strong>
		<label class="screen-reader-text" for="fct_record_type"><?php _e( 'Record Type', 'fiscaat' ); ?></label>
		<?php fct_form_record_type_select( $post_id, true ); ?>
	</p>

	<?php

	/** Offset account ********************************************************/

	?>
	
	<p>
		<strong class="label"><?php _e( 'Offset Account:', 'fiscaat' ); ?></strong>
		<label class="screen-reader-text" for="fct_record_offset_account"><?php _e( 'Offset Account number', 'fiscaat' ); ?></label>
		<input name="fct_record_offset_account" id="fct_record_offset_account" type="text" value="<?php echo esc_attr( fct_get_record_offset_account( $post_id ) ); ?>" />
	</p>

	<?php

	/** Status ****************************************************************/

	?>

	<p>
		<strong class="label"><?php _e( 'Status:', 'fiscaat' ); ?></strong>
		<label class="screen-reader-text" for="fct_record_status"><?php _e( 'Status', 'fiscaat' ); ?></label>
		<?php fct_form_record_status_dropdown( $post_id ); ?>
	</p>

	<?php 
	wp_nonce_field( 'fiscaat_record_metabox_save', 'fiscaat_record_metabox' );
	do_action( 'fiscaat_record_metabox', $post_id );
}
