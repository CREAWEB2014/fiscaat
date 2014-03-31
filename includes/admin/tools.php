<?php

/**
 * Fiscaat Admin Tools Page
 *
 * @package Fiscaat
 * @subpackage Administration
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** Repair ********************************************************************/

/**
 * Admin repair page
 *
 * @since Fiscaat (r2613)
 *
 * @uses fiscaat_admin_repair_list() To get the recount list
 * @uses check_admin_referer() To verify the nonce and the referer
 * @uses wp_cache_flush() To flush the cache
 * @uses do_action() Calls 'admin_notices' to display the notices
 * @uses screen_icon() To display the screen icon
 * @uses wp_nonce_field() To add a hidden nonce field
 */
function fiscaat_admin_repair() {
?>

	<div class="wrap">

		<?php screen_icon( 'tools' ); ?>

		<h2 class="nav-tab-wrapper"><?php fiscaat_tools_admin_tabs( __( 'Repair Years', 'fiscaat' ) ); ?></h2>

		<p><?php _e( 'Fiscaat keeps track of relationships between years, accounts, records, and account tags, and users. Occasionally these relationships become out of sync, most often after an import or migration. Use the tools below to manually recalculate these relationships.', 'fiscaat' ); ?></p>
		<p class="description"><?php _e( 'Some of these tools create substantial database overhead. Avoid running more than 1 repair job at a time.', 'fiscaat' ); ?></p>

		<form class="settings" method="post" action="">
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row"><?php _e( 'Relationships to Repair:', 'fiscaat' ) ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><span><?php _e( 'Repair', 'fiscaat' ) ?></span></legend>

								<?php foreach ( fiscaat_admin_repair_list() as $item ) : ?>

									<label><input type="checkbox" class="checkbox" name="<?php echo esc_attr( $item[0] ) . '" id="' . esc_attr( str_replace( '_', '-', $item[0] ) ); ?>" value="1" /> <?php echo esc_html( $item[1] ); ?></label><br />

								<?php endforeach; ?>

							</fieldset>
						</td>
					</tr>
				</tbody>
			</table>

			<fieldset class="submit">
				<input class="button-primary" type="submit" name="submit" value="<?php esc_attr_e( 'Repair Items', 'fiscaat' ); ?>" />
				<?php wp_nonce_field( 'fiscaat-do-counts' ); ?>
			</fieldset>
		</form>
	</div>

<?php
}

/**
 * Handle the processing and feedback of the admin tools page
 *
 * @since Fiscaat (r2613)
 *
 * @uses fiscaat_admin_repair_list() To get the recount list
 * @uses check_admin_referer() To verify the nonce and the referer
 * @uses wp_cache_flush() To flush the cache
 * @uses do_action() Calls 'admin_notices' to display the notices
 */
function fiscaat_admin_repair_handler() {

	if ( 'post' == strtolower( $_SERVER['REQUEST_METHOD'] ) ) {
		check_admin_referer( 'fiscaat-do-counts' );

		// Stores messages
		$messages = array();

		wp_cache_flush();

		foreach ( (array) fiscaat_admin_repair_list() as $item ) {
			if ( isset( $item[2] ) && isset( $_POST[$item[0]] ) && 1 == $_POST[$item[0]] && is_callable( $item[2] ) ) {
				$messages[] = call_user_func( $item[2] );
			}
		}

		if ( count( $messages ) ) {
			foreach ( $messages as $message ) {
				fiscaat_admin_tools_feedback( $message[1] );
			}
		}
	}
}

/**
 * Assemble the admin notices
 *
 * @since Fiscaat (r2613)
 *
 * @param string|WP_Error $message A message to be displayed or {@link WP_Error}
 * @param string $class Optional. A class to be added to the message div
 * @uses WP_Error::get_error_messages() To get the error messages of $message
 * @uses add_action() Adds the admin notice action with the message HTML
 * @return string The message HTML
 */
function fiscaat_admin_tools_feedback( $message, $class = false ) {
	if ( is_string( $message ) ) {
		$message = '<p>' . $message . '</p>';
		$class = $class ? $class : 'updated';
	} elseif ( is_wp_error( $message ) ) {
		$errors = $message->get_error_messages();

		switch ( count( $errors ) ) {
			case 0:
				return false;
				break;

			case 1:
				$message = '<p>' . $errors[0] . '</p>';
				break;

			default:
				$message = '<ul>' . "\n\t" . '<li>' . join( '</li>' . "\n\t" . '<li>', $errors ) . '</li>' . "\n" . '</ul>';
				break;
		}

		$class = $class ? $class : 'error';
	} else {
		return false;
	}

	$message = '<div id="message" class="' . esc_attr( $class ) . '">' . $message . '</div>';
	$message = str_replace( "'", "\'", $message );
	$lambda  = create_function( '', "echo '$message';" );

	add_action( 'admin_notices', $lambda );

	return $lambda;
}

/**
 * Get the array of the repair list
 *
 * @since Fiscaat (r2613)
 *
 * @uses apply_filters() Calls 'fiscaat_repair_list' with the list array
 * @return array Repair list of options
 */
function fiscaat_admin_repair_list() {
	$repair_list = array(
		0  => array( 'fiscaat-sync-account-meta',        __( 'Recalculate the parent account for each post',          'fiscaat' ), 'fiscaat_admin_repair_account_meta'               ),
		5  => array( 'fiscaat-sync-year-meta',        __( 'Recalculate the parent year for each post',          'fiscaat' ), 'fiscaat_admin_repair_year_meta'               ),
		10 => array( 'fiscaat-sync-year-visibility',  __( 'Recalculate private and hidden years',               'fiscaat' ), 'fiscaat_admin_repair_year_visibility'         ),
		15 => array( 'fiscaat-sync-all-accounts-years', __( 'Recalculate last activity in each account and year',   'fiscaat' ), 'fiscaat_admin_repair_freshness'                ),
		20 => array( 'fiscaat-group-years',           __( 'Repair BuddyPress Group Year relationships',         'fiscaat' ), 'fiscaat_admin_repair_group_year_relationship' ),
		25 => array( 'fiscaat-year-accounts',           __( 'Count accounts in each year',                          'fiscaat' ), 'fiscaat_admin_repair_year_account_count'        ),
		30 => array( 'fiscaat-year-records',          __( 'Count records in each year',                         'fiscaat' ), 'fiscaat_admin_repair_year_record_count'        ),
		35 => array( 'fiscaat-account-records',          __( 'Count records in each account',                         'fiscaat' ), 'fiscaat_admin_repair_account_record_count'        ),
		40 => array( 'fiscaat-account-voices',           __( 'Count voices in each account',                          'fiscaat' ), 'fiscaat_admin_repair_account_voice_count'        ),
		45 => array( 'fiscaat-account-hidden-records',   __( 'Count spammed & trashed records in each account',       'fiscaat' ), 'fiscaat_admin_repair_account_hidden_record_count' ),
		50 => array( 'fiscaat-user-records',           __( 'Count accounts for each user',                          'fiscaat' ), 'fiscaat_admin_repair_user_account_count'         ),
		55 => array( 'fiscaat-user-accounts',            __( 'Count records for each user',                         'fiscaat' ), 'fiscaat_admin_repair_user_record_count'         ),
		60 => array( 'fiscaat-user-favorites',         __( 'Remove trashed accounts from user favorites',           'fiscaat' ), 'fiscaat_admin_repair_user_favorites'           ),
		65 => array( 'fiscaat-user-subscriptions',     __( 'Remove trashed accounts from user subscriptions',       'fiscaat' ), 'fiscaat_admin_repair_user_subscriptions'       ),
		70 => array( 'fiscaat-user-role-map',          __( 'Remap existing users to default year roles',         'fiscaat' ), 'fiscaat_admin_repair_user_roles'               )
	);
	ksort( $repair_list );

	return (array) apply_filters( 'fiscaat_repair_list', $repair_list );
}

/**
 * Recount account records
 *
 * @since Fiscaat (r2613)
 *
 * @uses fiscaat_get_record_post_type() To get the record post type
 * @uses wpdb::query() To run our recount sql queries
 * @uses is_wp_error() To check if the executed query returned {@link WP_Error}
 * @return array An array of the status code and the message
 */
function fiscaat_admin_repair_account_record_count() {
	global $wpdb;

	$statement = __( 'Counting the number of records in each account&hellip; %s', 'fiscaat' );
	$result    = __( 'Failed!', 'fiscaat' );

	$sql_delete = "DELETE FROM `{$wpdb->postmeta}` WHERE `meta_key` = '_fiscaat_record_count';";
	if ( is_wp_error( $wpdb->query( $sql_delete ) ) )
		return array( 1, sprintf( $statement, $result ) );

	// Post types and status
	$tpt = fiscaat_get_account_post_type();
	$rpt = fiscaat_get_record_post_type();
	$pps = fiscaat_get_public_status_id();
	$cps = fiscaat_get_closed_status_id();

	$sql = "INSERT INTO `{$wpdb->postmeta}` (`post_id`, `meta_key`, `meta_value`) (
			SELECT `accounts`.`ID` AS `post_id`, '_fiscaat_record_count' AS `meta_key`, COUNT(`records`.`ID`) As `meta_value`
				FROM `{$wpdb->posts}` AS `accounts`
					LEFT JOIN `{$wpdb->posts}` as `records`
						ON  `records`.`post_parent` = `accounts`.`ID`
						AND `records`.`post_status` = '{$pps}'
						AND `records`.`post_type`   = '{$rpt}'
				WHERE `accounts`.`post_type` = '{$tpt}'
					AND `accounts`.`post_status` IN ( '{$pps}', '{$cps}' )
				GROUP BY `accounts`.`ID`);";

	if ( is_wp_error( $wpdb->query( $sql ) ) )
		return array( 2, sprintf( $statement, $result ) );

	return array( 0, sprintf( $statement, __( 'Complete!', 'fiscaat' ) ) );
}

/**
 * Recount account voices
 *
 * @since Fiscaat (r2613)
 *
 * @uses fiscaat_get_record_post_type() To get the record post type
 * @uses wpdb::query() To run our recount sql queries
 * @uses is_wp_error() To check if the executed query returned {@link WP_Error}
 * @return array An array of the status code and the message
 */
function fiscaat_admin_repair_account_voice_count() {
	global $wpdb;

	$statement = __( 'Counting the number of voices in each account&hellip; %s', 'fiscaat' );
	$result    = __( 'Failed!', 'fiscaat' );

	$sql_delete = "DELETE FROM `{$wpdb->postmeta}` WHERE `meta_key` = '_fiscaat_voice_count';";
	if ( is_wp_error( $wpdb->query( $sql_delete ) ) )
		return array( 1, sprintf( $statement, $result ) );

	// Post types and status
	$tpt = fiscaat_get_account_post_type();
	$rpt = fiscaat_get_record_post_type();
	$pps = fiscaat_get_public_status_id();
	$cps = fiscaat_get_closed_status_id();

	$sql = "INSERT INTO `{$wpdb->postmeta}` (`post_id`, `meta_key`, `meta_value`) (
			SELECT `postmeta`.`meta_value`, '_fiscaat_voice_count', COUNT(DISTINCT `post_author`) as `meta_value`
				FROM `{$wpdb->posts}` AS `posts`
				LEFT JOIN `{$wpdb->postmeta}` AS `postmeta`
					ON `posts`.`ID` = `postmeta`.`post_id`
					AND `postmeta`.`meta_key` = '_fiscaat_account_id'
				WHERE `posts`.`post_type` IN ( '{$tpt}', '{$rpt}' )
					AND `posts`.`post_status` IN ( '{$pps}', '{$cps}' )
					AND `posts`.`post_author` != '0'
				GROUP BY `postmeta`.`meta_value`);";

	if ( is_wp_error( $wpdb->query( $sql ) ) )
		return array( 2, sprintf( $statement, $result ) );

	return array( 0, sprintf( $statement, __( 'Complete!', 'fiscaat' ) ) );
}

/**
 * Recount account hidden records (spammed/trashed)
 *
 * @since Fiscaat (r2747)
 *
 * @uses wpdb::query() To run our recount sql queries
 * @uses is_wp_error() To check if the executed query returned {@link WP_Error}
 * @return array An array of the status code and the message
 */
function fiscaat_admin_repair_account_hidden_record_count() {
	global $wpdb;

	$statement = __( 'Counting the number of spammed and trashed records in each account&hellip; %s', 'fiscaat' );
	$result    = __( 'Failed!', 'fiscaat' );

	$sql_delete = "DELETE FROM `{$wpdb->postmeta}` WHERE `meta_key` = '_fiscaat_record_count_hidden';";
	if ( is_wp_error( $wpdb->query( $sql_delete ) ) )
		return array( 1, sprintf( $statement, $result ) );

	$sql = "INSERT INTO `{$wpdb->postmeta}` (`post_id`, `meta_key`, `meta_value`) (SELECT `post_parent`, '_fiscaat_record_count_hidden', COUNT(`post_status`) as `meta_value` FROM `{$wpdb->posts}` WHERE `post_type` = '" . fiscaat_get_record_post_type() . "' AND `post_status` IN ( '" . join( "','", array( fiscaat_get_trash_status_id(), fiscaat_get_spam_status_id() ) ) . "') GROUP BY `post_parent`);";
	if ( is_wp_error( $wpdb->query( $sql ) ) )
		return array( 2, sprintf( $statement, $result ) );

	return array( 0, sprintf( $statement, __( 'Complete!', 'fiscaat' ) ) );
}

/**
 * Repair group year ID mappings after a Fiscaat 1.1 to Fiscaat 2.2 conversion
 *
 * @since Fiscaat (r4395)
 *
 * @global WPDB $wpdb
 * @return If a wp_error() occurs and no converted years are found
 */
function fiscaat_admin_repair_group_year_relationship() {
	global $wpdb;

	$statement = __( 'Repairing BuddyPress group-year relationships&hellip; %s', 'fiscaat' );
	$g_count     = 0;
	$f_count     = 0;

	// Copy the BuddyPress filter here, incase BuddyPress is not active
	$prefix    = apply_filters( 'bp_core_get_table_prefix', $wpdb->base_prefix );
	$tablename = $prefix . 'bp_groups_groupmeta';

	// Get the converted year IDs
	$year_ids = $wpdb->query( "SELECT `year`.`ID`, `yearmeta`.`meta_value`
								FROM `{$wpdb->posts}` AS `year`
									LEFT JOIN `{$wpdb->postmeta}` AS `yearmeta`
										ON `year`.`ID` = `yearmeta`.`post_id`
										AND `yearmeta`.`meta_key` = '_fiscaat_old_year_id'
								WHERE `year`.`post_type` = 'year'
								GROUP BY `year`.`ID`;" );

	// Bail if year IDs returned an error
	if ( is_wp_error( $year_ids ) || empty( $wpdb->last_result ) )
		return array( 2, sprintf( $statement, __( 'Failed!', 'fiscaat' ) ) );

	// Stash the last results
	$results = $wpdb->last_result;

	// Update each group year
	foreach ( $results as $group_years ) {

		// Only update if is a converted year
		if ( ! isset( $group_years->meta_value ) )
			continue;

		// Attempt to update group meta
		$updated = $wpdb->query( "UPDATE `{$tablename}` SET `meta_value` = '{$group_years->ID}' WHERE `meta_key` = 'year_id' AND `meta_value` = '{$group_years->meta_value}';" );

		// Bump the count
		if ( ! empty( $updated ) && ! is_wp_error( $updated ) ) {
			++$g_count;
		}

		// Update group's year metadata
		$group_id = (int) $wpdb->get_var( "SELECT `group_id` FROM `{$tablename}` WHERE `meta_key` = 'year_id' AND `meta_value` = '{$group_years->ID}';" );
		if ( ! empty( $group_id ) ) {
			update_post_meta( $group_years->ID, '_fiscaat_group_ids', array( $group_id ) );
			++$f_count;
		}
	}

	// Make some logical guesses at the old group root year
	if ( function_exists( 'bp_years_parent_year_id' ) ) {
		$old_default_year_id = bp_years_parent_year_id();
	} elseif ( defined( 'BP_FORUMS_PARENT_FORUM_ID' ) ) {
		$old_default_year_id = (int) BP_FORUMS_PARENT_FORUM_ID;
	} else {
		$old_default_year_id = 1;
	}

	// Try to get the group root year
	$posts = get_posts( array(
		'post_type'   => fiscaat_get_year_post_type(),
		'meta_key'    => '_fiscaat_old_year_id',
		'meta_value'  => $old_default_year_id,
		'numberposts' => 1
	) );

	// Found the group root year
	if ( ! empty( $posts ) ) {

		// Rename 'Default Year'  since it's now visible in sitewide years
		if ( 'Default Year' == $posts[0]->post_title ) {
			wp_update_post( array(
				'ID'         => $posts[0]->ID,
				'post_title' => __( 'Group Years', 'fiscaat' ),
			) );
		}

		// Update the group years root metadata
		update_option( '_fiscaat_group_years_root_id', $posts[0]->ID );
	}

	// Complete results
	$result = sprintf( __( 'Complete! %s groups updated; %s years updated.', 'fiscaat' ), fiscaat_number_format( $g_count ), fiscaat_number_format( $f_count ) );
	return array( 0, sprintf( $statement, $result ) );
}

/**
 * Recount year accounts
 *
 * @since Fiscaat (r2613)
 *
 * @uses wpdb::query() To run our recount sql queries
 * @uses is_wp_error() To check if the executed query returned {@link WP_Error}
 * @uses fiscaat_get_year_post_type() To get the year post type
 * @uses get_posts() To get the years
 * @uses fiscaat_update_year_account_count() To update the year account count
 * @return array An array of the status code and the message
 */
function fiscaat_admin_repair_year_account_count() {
	global $wpdb;

	$statement = __( 'Counting the number of accounts in each year&hellip; %s', 'fiscaat' );
	$result    = __( 'Failed!', 'fiscaat' );

	$sql_delete = "DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ( '_fiscaat_account_count', '_fiscaat_total_account_count' );";
	if ( is_wp_error( $wpdb->query( $sql_delete ) ) )
		return array( 1, sprintf( $statement, $result ) );

	$years = get_posts( array( 'post_type' => fiscaat_get_year_post_type(), 'numberposts' => -1 ) );
	if ( ! empty( $years ) ) {
		foreach( $years as $year ) {
			fiscaat_update_year_account_count( $year->ID );
		}
	} else {
		return array( 2, sprintf( $statement, $result ) );
	}

	return array( 0, sprintf( $statement, __( 'Complete!', 'fiscaat' ) ) );
}

/**
 * Recount year records
 *
 * @since Fiscaat (r2613)
 *
 * @uses wpdb::query() To run our recount sql queries
 * @uses is_wp_error() To check if the executed query returned {@link WP_Error}
 * @uses fiscaat_get_year_post_type() To get the year post type
 * @uses get_posts() To get the years
 * @uses fiscaat_update_year_record_count() To update the year record count
 * @return array An array of the status code and the message
 */
function fiscaat_admin_repair_year_record_count() {
	global $wpdb;

	$statement = __( 'Counting the number of records in each year&hellip; %s', 'fiscaat' );
	$result    = __( 'Failed!', 'fiscaat' );

	$sql_delete = "DELETE FROM `{$wpdb->postmeta}` WHERE `meta_key` IN ( '_fiscaat_record_count', '_fiscaat_total_record_count' );";
	if ( is_wp_error( $wpdb->query( $sql_delete ) ) )
		return array( 1, sprintf( $statement, $result ) );

	$years = get_posts( array( 'post_type' => fiscaat_get_year_post_type(), 'numberposts' => -1 ) );
	if ( ! empty( $years ) ) {
		foreach( $years as $year ) {
			fiscaat_update_year_record_count( $year->ID );
		}
	} else {
		return array( 2, sprintf( $statement, $result ) );
	}

	return array( 0, sprintf( $statement, __( 'Complete!', 'fiscaat' ) ) );
}

/**
 * Recount accounts by the users
 *
 * @since Fiscaat (r3889)
 *
 * @uses fiscaat_get_record_post_type() To get the record post type
 * @uses wpdb::query() To run our recount sql queries
 * @uses is_wp_error() To check if the executed query returned {@link WP_Error}
 * @return array An array of the status code and the message
 */
function fiscaat_admin_repair_user_account_count() {
	global $wpdb;

	$statement   = __( 'Counting the number of accounts each user has created&hellip; %s', 'fiscaat' );
	$result      = __( 'Failed!', 'fiscaat' );
	$sql_select  = "SELECT `post_author`, COUNT(DISTINCT `ID`) as `_count` FROM `{$wpdb->posts}` WHERE `post_type` = '" . fiscaat_get_account_post_type() . "' AND `post_status` = '" . fiscaat_get_public_status_id() . "' GROUP BY `post_author`;";
	$insert_rows = $wpdb->get_results( $sql_select );

	if ( is_wp_error( $insert_rows ) )
		return array( 1, sprintf( $statement, $result ) );

	$key           = $wpdb->prefix . '_fiscaat_account_count';
	$insert_values = array();
	foreach ( $insert_rows as $insert_row )
		$insert_values[] = "('{$insert_row->post_author}', '{$key}', '{$insert_row->_count}')";

	if ( !count( $insert_values ) )
		return array( 2, sprintf( $statement, $result ) );

	$sql_delete = "DELETE FROM `{$wpdb->usermeta}` WHERE `meta_key` = '{$key}';";
	if ( is_wp_error( $wpdb->query( $sql_delete ) ) )
		return array( 3, sprintf( $statement, $result ) );

	foreach ( array_chunk( $insert_values, 10000 ) as $chunk ) {
		$chunk = "\n" . join( ",\n", $chunk );
		$sql_insert = "INSERT INTO `{$wpdb->usermeta}` (`user_id`, `meta_key`, `meta_value`) VALUES $chunk;";

		if ( is_wp_error( $wpdb->query( $sql_insert ) ) ) {
			return array( 4, sprintf( $statement, $result ) );
		}
	}

	return array( 0, sprintf( $statement, __( 'Complete!', 'fiscaat' ) ) );
}

/**
 * Recount account replied by the users
 *
 * @since Fiscaat (r2613)
 *
 * @uses fiscaat_get_record_post_type() To get the record post type
 * @uses wpdb::query() To run our recount sql queries
 * @uses is_wp_error() To check if the executed query returned {@link WP_Error}
 * @return array An array of the status code and the message
 */
function fiscaat_admin_repair_user_record_count() {
	global $wpdb;

	$statement   = __( 'Counting the number of accounts to which each user has replied&hellip; %s', 'fiscaat' );
	$result      = __( 'Failed!', 'fiscaat' );
	$sql_select  = "SELECT `post_author`, COUNT(DISTINCT `ID`) as `_count` FROM `{$wpdb->posts}` WHERE `post_type` = '" . fiscaat_get_record_post_type() . "' AND `post_status` = '" . fiscaat_get_public_status_id() . "' GROUP BY `post_author`;";
	$insert_rows = $wpdb->get_results( $sql_select );

	if ( is_wp_error( $insert_rows ) )
		return array( 1, sprintf( $statement, $result ) );

	$key           = $wpdb->prefix . '_fiscaat_record_count';
	$insert_values = array();
	foreach ( $insert_rows as $insert_row )
		$insert_values[] = "('{$insert_row->post_author}', '{$key}', '{$insert_row->_count}')";

	if ( !count( $insert_values ) )
		return array( 2, sprintf( $statement, $result ) );

	$sql_delete = "DELETE FROM `{$wpdb->usermeta}` WHERE `meta_key` = '{$key}';";
	if ( is_wp_error( $wpdb->query( $sql_delete ) ) )
		return array( 3, sprintf( $statement, $result ) );

	foreach ( array_chunk( $insert_values, 10000 ) as $chunk ) {
		$chunk = "\n" . join( ",\n", $chunk );
		$sql_insert = "INSERT INTO `{$wpdb->usermeta}` (`user_id`, `meta_key`, `meta_value`) VALUES $chunk;";

		if ( is_wp_error( $wpdb->query( $sql_insert ) ) ) {
			return array( 4, sprintf( $statement, $result ) );
		}
	}

	return array( 0, sprintf( $statement, __( 'Complete!', 'fiscaat' ) ) );
}

/**
 * Clean the users' favorites
 *
 * @since Fiscaat (r2613)
 *
 * @uses fiscaat_get_account_post_type() To get the account post type
 * @uses wpdb::query() To run our recount sql queries
 * @uses is_wp_error() To check if the executed query returned {@link WP_Error}
 * @return array An array of the status code and the message
 */
function fiscaat_admin_repair_user_favorites() {
	global $wpdb;

	$statement = __( 'Removing trashed accounts from user favorites&hellip; %s', 'fiscaat' );
	$result    = __( 'Failed!', 'fiscaat' );
	$key       = $wpdb->prefix . '_fiscaat_favorites';
	$users     = $wpdb->get_results( "SELECT `user_id`, `meta_value` AS `favorites` FROM `{$wpdb->usermeta}` WHERE `meta_key` = '{$key}';" );

	if ( is_wp_error( $users ) )
		return array( 1, sprintf( $statement, $result ) );

	$accounts = $wpdb->get_col( "SELECT `ID` FROM `{$wpdb->posts}` WHERE `post_type` = '" . fiscaat_get_account_post_type() . "' AND `post_status` = '" . fiscaat_get_public_status_id() . "';" );

	if ( is_wp_error( $accounts ) )
		return array( 2, sprintf( $statement, $result ) );

	$values = array();
	foreach ( $users as $user ) {
		if ( empty( $user->favorites ) || !is_string( $user->favorites ) )
			continue;

		$favorites = array_intersect( $accounts, (array) explode( ',', $user->favorites ) );
		if ( empty( $favorites ) || !is_array( $favorites ) )
			continue;

		$favorites_joined = join( ',', $favorites );
		$values[]         = "('{$user->user_id}', '{$key}, '{$favorites_joined}')";

		// Cleanup
		unset( $favorites, $favorites_joined );
	}

	if ( !count( $values ) ) {
		$result = __( 'Nothing to remove!', 'fiscaat' );
		return array( 0, sprintf( $statement, $result ) );
	}

	$sql_delete = "DELETE FROM `{$wpdb->usermeta}` WHERE `meta_key` = '{$key}';";
	if ( is_wp_error( $wpdb->query( $sql_delete ) ) )
		return array( 4, sprintf( $statement, $result ) );

	foreach ( array_chunk( $values, 10000 ) as $chunk ) {
		$chunk = "\n" . join( ",\n", $chunk );
		$sql_insert = "INSERT INTO `$wpdb->usermeta` (`user_id`, `meta_key`, `meta_value`) VALUES $chunk;";
		if ( is_wp_error( $wpdb->query( $sql_insert ) ) ) {
			return array( 5, sprintf( $statement, $result ) );
		}
	}

	return array( 0, sprintf( $statement, __( 'Complete!', 'fiscaat' ) ) );
}

/**
 * Clean the users' subscriptions
 *
 * @since Fiscaat (r2668)
 *
 * @uses fiscaat_get_account_post_type() To get the account post type
 * @uses wpdb::query() To run our recount sql queries
 * @uses is_wp_error() To check if the executed query returned {@link WP_Error}
 * @return array An array of the status code and the message
 */
function fiscaat_admin_repair_user_subscriptions() {
	global $wpdb;

	$statement = __( 'Removing trashed accounts from user subscriptions&hellip; %s', 'fiscaat' );
	$result    = __( 'Failed!', 'fiscaat' );
	$key       = $wpdb->prefix . '_fiscaat_subscriptions';
	$users     = $wpdb->get_results( "SELECT `user_id`, `meta_value` AS `subscriptions` FROM `{$wpdb->usermeta}` WHERE `meta_key` = '{$key}';" );

	if ( is_wp_error( $users ) )
		return array( 1, sprintf( $statement, $result ) );

	$accounts = $wpdb->get_col( "SELECT `ID` FROM `{$wpdb->posts}` WHERE `post_type` = '" . fiscaat_get_account_post_type() . "' AND `post_status` = '" . fiscaat_get_public_status_id() . "';" );
	if ( is_wp_error( $accounts ) )
		return array( 2, sprintf( $statement, $result ) );

	$values = array();
	foreach ( $users as $user ) {
		if ( empty( $user->subscriptions ) || !is_string( $user->subscriptions ) )
			continue;

		$subscriptions = array_intersect( $accounts, (array) explode( ',', $user->subscriptions ) );
		if ( empty( $subscriptions ) || !is_array( $subscriptions ) )
			continue;

		$subscriptions_joined = join( ',', $subscriptions );
		$values[]             = "('{$user->user_id}', '{$key}', '{$subscriptions_joined}')";

		// Cleanup
		unset( $subscriptions, $subscriptions_joined );
	}

	if ( !count( $values ) ) {
		$result = __( 'Nothing to remove!', 'fiscaat' );
		return array( 0, sprintf( $statement, $result ) );
	}

	$sql_delete = "DELETE FROM `{$wpdb->usermeta}` WHERE `meta_key` = '{$key}';";
	if ( is_wp_error( $wpdb->query( $sql_delete ) ) )
		return array( 4, sprintf( $statement, $result ) );

	foreach ( array_chunk( $values, 10000 ) as $chunk ) {
		$chunk = "\n" . join( ",\n", $chunk );
		$sql_insert = "INSERT INTO `{$wpdb->usermeta}` (`user_id`, `meta_key`, `meta_value`) VALUES $chunk;";
		if ( is_wp_error( $wpdb->query( $sql_insert ) ) ) {
			return array( 5, sprintf( $statement, $result ) );
		}
	}

	return array( 0, sprintf( $statement, __( 'Complete!', 'fiscaat' ) ) );
}

/**
 * This repair tool will map each user of the current site to their respective
 * years role. By default, Admins will be Key Masters, and every other role
 * will be the default role defined in Settings > Years (Participant).
 *
 * @since Fiscaat (r4340)
 *
 * @uses fiscaat_get_user_role_map() To get the map of user roles
 * @uses get_editable_roles() To get the current WordPress roles
 * @uses get_users() To get the users of each role (limited to ID field)
 * @uses fiscaat_set_user_role() To set each user's years role
 */
function fiscaat_admin_repair_user_roles() {

	$statement = __( 'Remapping Fiscaat role for each user on this site&hellip; %s', 'fiscaat' );
	$changed   = 0;
	$role_map  = fiscaat_get_user_role_map();

	// Bail if no role map exists
	if ( empty( $role_map ) )
		return array( 1, sprintf( $statement, __( 'Failed!', 'fiscaat' ) ) );

	// Iterate through each role...
	foreach ( array_keys( get_editable_roles() ) as $role ) {

		// Reset the offset
		$offset = 0;

		// Get users of this site, limited to 1000
		while ( $users = get_users( array(
				'role'   => $role,
				'fields' => 'ID',
				'number' => 1000,
				'offset' => $offset
			) ) ) {

			// Iterate through each user of $role and try to set it
			foreach ( (array) $users as $user_id ) {
				if ( fiscaat_set_user_role( $user_id, $role_map[$role] ) ) {
					++$changed; // Keep a count to display at the end
				}
			}

			// Bump the offset for the next query iteration
			$offset = $offset + 1000;
		}
	}

	$result = sprintf( __( 'Complete! %s users updated.', 'fiscaat' ), fiscaat_number_format( $changed ) );
	return array( 0, sprintf( $statement, $result ) );
}

/**
 * Recaches the private and hidden years
 *
 * @since Fiscaat (r4104)
 *
 * @uses delete_option() to delete private and hidden year pointers
 * @uses WP_Query() To query post IDs
 * @uses is_wp_error() To return if error occurred
 * @uses update_option() To update the private and hidden post ID pointers
 * @return array An array of the status code and the message
 */
function fiscaat_admin_repair_year_visibility() {

	$statement = __( 'Recalculating year visibility &hellip; %s', 'fiscaat' );
	$result    = __( 'Failed!', 'fiscaat' );

	// First, delete everything.
	delete_option( '_fiscaat_private_years' );
	delete_option( '_fiscaat_hidden_years'  );

	// Next, get all the private and hidden years
	$private_years = new WP_Query( array(
		'suppress_filters' => true,
		'nopaging'         => true,
		'post_type'        => fiscaat_get_year_post_type(),
		'post_status'      => fiscaat_get_private_status_id(),
		'fields'           => 'ids'
	) );
	$hidden_years = new WP_Query( array(
		'suppress_filters' => true,
		'nopaging'         => true,
		'post_type'        => fiscaat_get_year_post_type(),
		'post_status'      => fiscaat_get_hidden_status_id(),
		'fields'           => 'ids'
	) );

	// Bail if queries returned errors
	if ( is_wp_error( $private_years ) || is_wp_error( $hidden_years ) )
		return array( 2, sprintf( $statement, $result ) );

	update_option( '_fiscaat_private_years', $private_years->posts ); // Private years
	update_option( '_fiscaat_hidden_years',  $hidden_years->posts  ); // Hidden years

	// Reset the $post global
	wp_reset_postdata();

	// Complete results
	return array( 0, sprintf( $statement, __( 'Complete!', 'fiscaat' ) ) );
}

/**
 * Recaches the year for each post
 *
 * @since Fiscaat (r3876)
 *
 * @uses wpdb::query() To run our recount sql queries
 * @uses is_wp_error() To check if the executed query returned {@link WP_Error}
 * @return array An array of the status code and the message
 */
function fiscaat_admin_repair_year_meta() {
	global $wpdb;

	$statement = __( 'Recalculating the year for each post &hellip; %s', 'fiscaat' );
	$result    = __( 'Failed!', 'fiscaat' );

	// First, delete everything.
	if ( is_wp_error( $wpdb->query( "DELETE FROM `$wpdb->postmeta` WHERE `meta_key` = '_fiscaat_year_id';" ) ) )
		return array( 1, sprintf( $statement, $result ) );

	// Next, give all the accounts with records the ID their last record.
	if ( is_wp_error( $wpdb->query( "INSERT INTO `$wpdb->postmeta` (`post_id`, `meta_key`, `meta_value`)
			( SELECT `year`.`ID`, '_fiscaat_year_id', `year`.`post_parent`
			FROM `$wpdb->posts`
				AS `year`
			WHERE `year`.`post_type` = 'year'
			GROUP BY `year`.`ID` );" ) ) )
		return array( 2, sprintf( $statement, $result ) );

	// Next, give all the accounts with records the ID their last record.
	if ( is_wp_error( $wpdb->query( "INSERT INTO `$wpdb->postmeta` (`post_id`, `meta_key`, `meta_value`)
			( SELECT `account`.`ID`, '_fiscaat_year_id', `account`.`post_parent`
			FROM `$wpdb->posts`
				AS `account`
			WHERE `account`.`post_type` = 'account'
			GROUP BY `account`.`ID` );" ) ) )
		return array( 3, sprintf( $statement, $result ) );

	// Next, give all the accounts with records the ID their last record.
	if ( is_wp_error( $wpdb->query( "INSERT INTO `$wpdb->postmeta` (`post_id`, `meta_key`, `meta_value`)
			( SELECT `record`.`ID`, '_fiscaat_year_id', `account`.`post_parent`
			FROM `$wpdb->posts`
				AS `record`
			INNER JOIN `$wpdb->posts`
				AS `account`
				ON `record`.`post_parent` = `account`.`ID`
			WHERE `account`.`post_type` = 'account'
				AND `record`.`post_type` = 'record'
			GROUP BY `record`.`ID` );" ) ) )
		return array( 4, sprintf( $statement, $result ) );

	// Complete results
	return array( 0, sprintf( $statement, __( 'Complete!', 'fiscaat' ) ) );
}

/**
 * Recaches the account for each post
 *
 * @since Fiscaat (r3876)
 *
 * @uses wpdb::query() To run our recount sql queries
 * @uses is_wp_error() To check if the executed query returned {@link WP_Error}
 * @return array An array of the status code and the message
 */
function fiscaat_admin_repair_account_meta() {
	global $wpdb;

	$statement = __( 'Recalculating the account for each post &hellip; %s', 'fiscaat' );
	$result    = __( 'Failed!', 'fiscaat' );

	// First, delete everything.
	if ( is_wp_error( $wpdb->query( "DELETE FROM `$wpdb->postmeta` WHERE `meta_key` = '_fiscaat_account_id';" ) ) )
		return array( 1, sprintf( $statement, $result ) );

	// Next, give all the accounts with records the ID their last record.
	if ( is_wp_error( $wpdb->query( "INSERT INTO `$wpdb->postmeta` (`post_id`, `meta_key`, `meta_value`)
			( SELECT `account`.`ID`, '_fiscaat_account_id', `account`.`ID`
			FROM `$wpdb->posts`
				AS `account`
			WHERE `account`.`post_type` = 'account'
			GROUP BY `account`.`ID` );" ) ) )
		return array( 3, sprintf( $statement, $result ) );

	// Next, give all the accounts with records the ID their last record.
	if ( is_wp_error( $wpdb->query( "INSERT INTO `$wpdb->postmeta` (`post_id`, `meta_key`, `meta_value`)
			( SELECT `record`.`ID`, '_fiscaat_account_id', `account`.`ID`
			FROM `$wpdb->posts`
				AS `record`
			INNER JOIN `$wpdb->posts`
				AS `account`
				ON `record`.`post_parent` = `account`.`ID`
			WHERE `account`.`post_type` = 'account'
				AND `record`.`post_type` = 'record'
			GROUP BY `record`.`ID` );" ) ) )
		return array( 4, sprintf( $statement, $result ) );

	// Complete results
	return array( 0, sprintf( $statement, __( 'Complete!', 'fiscaat' ) ) );
}

/** Reset ********************************************************************/

// @todo read page id
function fiscaat_is_reset() {
	return false;
}

/**
 * Admin reset page
 *
 * @since Fiscaat (r2613)
 *
 * @uses check_admin_referer() To verify the nonce and the referer
 * @uses do_action() Calls 'admin_notices' to display the notices
 * @uses screen_icon() To display the screen icon
 * @uses wp_nonce_field() To add a hidden nonce field
 */
function fiscaat_admin_reset() {
?>

	<div class="wrap">

		<?php screen_icon( 'tools' ); ?>

		<h2 class="nav-tab-wrapper"><?php fiscaat_tools_admin_tabs( __( 'Reset Years', 'fiscaat' ) ); ?></h2>
		<p><?php _e( 'This will revert your years back to a brand new installation. This process cannot be undone. <strong>Backup your database before proceeding</strong>.', 'fiscaat' ); ?></p>

		<form class="settings" method="post" action="">
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row"><?php _e( 'The following data will be removed:', 'fiscaat' ) ?></th>
						<td>
							<?php _e( 'All Years',           'fiscaat' ); ?><br />
							<?php _e( 'All Accounts',           'fiscaat' ); ?><br />
							<?php _e( 'All Records',          'fiscaat' ); ?><br />
							<?php _e( 'All Account Tags',       'fiscaat' ); ?><br />
							<?php _e( 'Related Meta Data',    'fiscaat' ); ?><br />
							<?php _e( 'Year Settings',       'fiscaat' ); ?><br />
							<?php _e( 'Year Activity',       'fiscaat' ); ?><br />
							<?php _e( 'Year User Roles',     'fiscaat' ); ?><br />
							<?php _e( 'Importer Helper Data', 'fiscaat' ); ?><br />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Are you sure you want to do this?', 'fiscaat' ) ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><span><?php _e( "Say it ain't so!", 'fiscaat' ) ?></span></legend>
								<label><input type="checkbox" class="checkbox" name="fiscaat-are-you-sure" id="fiscaat-are-you-sure" value="1" /> <?php _e( 'This process cannot be undone.', 'fiscaat' ); ?></label>
							</fieldset>
						</td>
					</tr>
				</tbody>
			</table>

			<fieldset class="submit">
				<input class="button-primary" type="submit" name="submit" value="<?php esc_attr_e( 'Reset Fiscaat', 'fiscaat' ); ?>" />
				<?php wp_nonce_field( 'fiscaat-reset' ); ?>
			</fieldset>
		</form>
	</div>

<?php
}

/**
 * Handle the processing and feedback of the admin tools page
 *
 * @since Fiscaat (r2613)
 *
 * @uses check_admin_referer() To verify the nonce and the referer
 * @uses wp_cache_flush() To flush the cache
 */
function fiscaat_admin_reset_handler() {
	if ( 'post' == strtolower( $_SERVER['REQUEST_METHOD'] ) && ! empty( $_POST['fiscaat-are-you-sure'] ) ) {
		check_admin_referer( 'fiscaat-reset' );

		global $wpdb;

		// Stores messages
		$messages = array();
		$failed   = __( 'Failed',   'fiscaat' );
		$success  = __( 'Success!', 'fiscaat' );

		// Flush the cache; things are about to get ugly.
		wp_cache_flush();

		/** Posts *************************************************************/

		$statement  = __( 'Deleting Posts&hellip; %s', 'fiscaat' );
		$sql_posts  = $wpdb->get_results( "SELECT `ID` FROM `{$wpdb->posts}` WHERE `post_type` IN ('year', 'account', 'record')", OBJECT_K );
		$sql_delete = "DELETE FROM `{$wpdb->posts}` WHERE `post_type` IN ('year', 'account', 'record')";
		$result     = is_wp_error( $wpdb->query( $sql_delete ) ) ? $failed : $success;
		$messages[] = sprintf( $statement, $result );


		/** Post Meta *********************************************************/

		if ( ! empty( $sql_posts ) ) {
			foreach( $sql_posts as $key => $value ) {
				$sql_meta[] = $key;
			}
			$statement  = __( 'Deleting Post Meta&hellip; %s', 'fiscaat' );
			$sql_meta   = implode( "', '", $sql_meta );
			$sql_delete = "DELETE FROM `{$wpdb->postmeta}` WHERE `post_id` IN ('{$sql_meta}');";
			$result     = is_wp_error( $wpdb->query( $sql_delete ) ) ? $failed : $success;
			$messages[] = sprintf( $statement, $result );
		}

		/** Account Tags ********************************************************/

		// @todo

		/** User Meta *********************************************************/

		$statement  = __( 'Deleting User Meta&hellip; %s', 'fiscaat' );
		$sql_delete = "DELETE FROM `{$wpdb->usermeta}` WHERE `meta_key` LIKE '%%_fiscaat_%%';";
		$result     = is_wp_error( $wpdb->query( $sql_delete ) ) ? $failed : $success;
		$messages[] = sprintf( $statement, $result );

		/** Converter *********************************************************/

		$statement  = __( 'Deleting Conversion Table&hellip; %s', 'fiscaat' );
		$table_name = $wpdb->prefix . 'fiscaat_converter_translator';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) == $table_name ) {
			$wpdb->query( "DROP TABLE {$table_name}" );
			$result = $success;
		} else {
			$result = $failed;
		}
		$messages[] = sprintf( $statement, $result );

		/** Options ***********************************************************/

		$statement  = __( 'Deleting Settings&hellip; %s', 'fiscaat' );
		$sql_delete = fiscaat_delete_options();
		$messages[] = sprintf( $statement, $success );

		/** Roles *************************************************************/

		$statement  = __( 'Deleting Roles and Capabilities&hellip; %s', 'fiscaat' );
		$sql_delete = fiscaat_remove_roles();
		$sql_delete = fiscaat_remove_caps();
		$messages[] = sprintf( $statement, $success );

		/** Output ************************************************************/

		if ( count( $messages ) ) {
			foreach ( $messages as $message ) {
				fiscaat_admin_tools_feedback( $message );
			}
		}
	}
}
