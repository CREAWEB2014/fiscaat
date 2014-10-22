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
						$link = add_query_arg( array( 'page' => 'fct-periods' ), admin_url( 'admin.php' ) );
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
						$link = add_query_arg( array( 'page' => 'fct-accounts' ), admin_url( 'admin.php' ) );
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
						$link = add_query_arg( array( 'page' => 'fct-records' ), admin_url( 'admin.php' ) );
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
						$link = add_query_arg( array( 'page' => 'fct-reports', 'period_id' => fct_get_current_period_id() ), admin_url( 'admin.php' ) );
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
						$link = add_query_arg( array( 'page' => 'fct-records', 'period_id' => fct_get_current_period_id() ), admin_url( 'admin.php' ) );
						$num  = '<a href="' . $link . '">' . $num  . '</a>';
						$text = '<a href="' . $link . '">' . $text . '</a>';
					}
				?>

				<td class="b b-records"><?php echo $num; ?></td>
				<td class="last t records"><?php echo $text; ?></td>
			</tr>

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
	$post_id = get_the_ID();

	/** Status ****************************************************************/

	?>

	<p>
		<strong class="label"><?php _e( 'Status:', 'fiscaat' ); ?></strong>
		<label class="screen-reader-text" for="fct_period_status_select"><?php _e( 'Status:', 'fiscaat' ); ?></label>
		<?php fct_form_period_status_dropdown( $post_id ); ?>
	</p>

	<?php

	/** Inherit ***************************************************************/

	// Should new period inherit accounts from previous period
	if ( ! fct_get_period_account_count( $post_id )

		// Query latest period
		&& $prev_period = get_posts( array( 
			'post_type'   => fct_get_period_post_type(),
			'post_status' => fct_get_closed_status_id(),
			'meta_key'    => '_fct_close_date', 
			'orderby'     => 'meta_value',
			'order'       => 'ASC',
			'fields'      => 'ids',
			'numberposts' => 1,
	) ) ) : 

		$_period = $prev_period[0]; ?>

	<p>
		<strong class="label"><?php _e( 'Inherit:', 'fiscaat' ); ?></strong>
		<label class="screen-reader-text" for="fct_period_inherit_accounts"><?php _e( 'Inherit:', 'fiscaat' ); ?></label>
		<input name="fct_period_inherit_from" type="hidden" value="<?php echo $_period; ?>" />
		<input name="fct_period_inherit_accounts" type="checkbox" value="1" id="fct_period_inherit_accounts" checked="checked" /><br />
		<label for="fct_period_inherit_accounts"><?php printf( __( 'Inherit all accounts from "%s".', 'fiscaat' ), fct_get_period_title( $_period ) ); ?></label>
	</p>

	<?php endif;

	/** Start date ************************************************************/

	// Not on post-new.php
	if ( isset( get_current_screen()->action ) && 'add' != get_current_screen()->action ) : ?>

	<p>
		<strong class="label"><?php _e( 'Start:', 'Period start date', 'fiscaat' ); ?></strong>
		<label class="screen-reader-text" for="fct_period_post_date"><?php _e( 'Start date:', 'fiscaat' ); ?></label>
		<?php fct_form_period_post_date( $post_id ); ?>
	</p>

	<?php endif;

	/** Close date ************************************************************/

	// Period is closed or reopened.
	if ( $date = fct_get_period_close_date( $post_id ) && ! empty( $date ) ) : ?>

	<p>
		<strong class="label"><?php _e( 'Closed:', 'Period close date', 'fiscaat' ); ?></strong>
		<label class="screen-reader-text" for="fct_period_closed"><?php _e( 'Close date:', 'fiscaat' ); ?></label>
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
	$post_id   = get_the_ID();
	$period_id = fct_get_account_period_id( $post_id );
	$period_id = fct_get_period_id( $period_id );

	/** Ledger ID *************************************************************/

	?>
	
	<p>
		<strong class="label"><?php _e( 'No.:', 'fiscaat' ); ?></strong>
		<label class="screen-reader-text" for="fct_account_ledger_id"><?php _e( 'Account Number', 'fiscaat' ); ?></label>
		<input name="fct_account_ledger_id" id="fct_account_ledger_id" type="text" class="medium-text" value="<?php echo esc_attr( fct_get_account_ledger_id( $post_id ) ); ?>" <?php disabled( ! current_user_can( 'fiscaat' ) || fct_is_account_closed( $post_id ) ); ?> />
		<span class="spinner" style="float:none;"></span>
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
	$account_id = fct_get_record_account_id( $post_id );
	$period_id  = fct_get_record_period_id( $post_id );
	$period_id  = fct_get_period_id( $period_id );

	/** Period ****************************************************************/

	?>

	<p>
		<strong class="label"><?php _e( 'Period:', 'fiscaat' ); ?></strong>
		<label class="screen-reader-text" for="fct_record_period_id"><?php _e( 'Period', 'fiscaat' ); ?></label>
		<?php fct_period_dropdown( array(
			'selected'           => $period_id,

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
		<strong class="label"><?php _e( 'Account:', 'fiscaat' ); ?></strong>
		<label class="screen-reader-text" for="parent_id"><?php _e( 'Account', 'fiscaat' ); ?></label>
		<?php fct_account_full_dropdown( array(
			'selected'           => $account_id,
			'post_parent'        => $period_id,

			// Output-related
			'select_id'          => 'parent_id',
			'show_none'          => __( '&mdash; No account &mdash;', 'fiscaat' ),
			'none_found'         => false,
			'disabled'           => fct_is_account_closed( $account_id ),
		) ); ?>
	</p>

	<?php

	/** Amount ****************************************************************/

	?>
	
	<p>
		<strong class="label"><?php _e( 'Amount:', 'fiscaat' ); ?></strong>
		<span><?php fct_currency_format( fct_get_record_amount( $post_id ), true ); ?></span>
	</p>

	<?php

	/** Record type ***********************************************************/

	?>
	
	<p>
		<strong class="label"><?php _ex( 'Type:', 'Record type input label', 'fiscaat' ); ?></strong>
		<span><?php fct_record_type( $post_id ); ?></span>
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

/** Title & Editor ************************************************************/

/**
 * Post details metabox
 * 
 * The metabox that holds the post title and description
 *
 * @since 0.0.9
 *
 * @uses wp_editor()
 */
function fct_post_name_metabox( $post ) { ?>

	<div id="post-body-content">
		<div id="postdiv">
			<div id="fct_post_name" class="postbox">
				<h3><?php _e( 'Title and Description', 'fiscaat' ); ?></h3>
				<div class="inside">
					<input type="text" name="post_title" id="title" value="<?php echo esc_attr( stripslashes( $post->post_title ) ) ?>" />

					<?php wp_editor( stripslashes( $post->post_content ), 'content', array( 
						'media_buttons' => false, 
						'teeny'         => true, 
						'textarea_rows' => 5, 
						'quicktags'     => array( 
							'buttons' => 'strong,em,link,block,del,ins,img,code,spell,close' 
						) 
					) ); ?>
				</div>
			</div>
		</div>
	</div><!-- #post-body-content -->

	<?php
}
