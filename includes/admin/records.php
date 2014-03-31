<?php

/**
 * Fiscaat Records Admin Class
 *
 * @package Fiscaat
 * @subpackage Administration
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'Fiscaat_Records_Admin' ) ) :

/**
 * Loads Fiscaat records admin area
 *
 * @package Fiscaat
 * @subpackage Administration
 */
class Fiscaat_Records_Admin {

	/** Variables *************************************************************/

	/**
	 * @var The post type of this admin component
	 */
	private $post_type = '';

	/** Functions *************************************************************/

	/**
	 * The main Fiscaat admin loader
	 *
	 * @uses Fiscaat_Records_Admin::setup_globals() Setup the globals needed
	 * @uses Fiscaat_Records_Admin::setup_actions() Setup the hooks and actions
	 */
	public function __construct() {
		$this->setup_globals();
		$this->setup_actions();
	}

	/**
	 * Setup the admin hooks, actions and filters
	 *
	 * @access private
	 *
	 * @uses add_action() To add various actions
	 * @uses add_filter() To add various filters
	 * @uses fct_get_year_post_type() To get the year post type
	 * @uses fct_get_account_post_type() To get the account post type
	 * @uses fct_get_record_post_type() To get the record post type
	 */
	private function setup_actions() {

		// Add some general styling to the admin area
		add_action( 'fct_admin_head',    array( $this, 'admin_head'       ) );

		// Messages
		add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );

		// Record column headers.
		add_filter( 'manage_' . $this->post_type . '_posts_columns',  array( $this, 'records_column_headers' ) );

		// Record columns (in post row)
		add_action( 'manage_' . $this->post_type . '_posts_custom_column',   array( $this, 'records_column_data'      ), 10, 2 );
		add_filter( 'manage_edit-' . $this->post_type . '_sortable_columns', array( $this, 'records_sortable_columns' ), 10    );
		add_filter( 'post_row_actions',                                      array( $this, 'records_row_actions'      ), 10, 2 );

		// Record metabox actions
		add_action( 'add_meta_boxes', array( $this, 'record_attributes_metabox'      ) );
		add_action( 'save_post',      array( $this, 'record_attributes_metabox_save' ) );

		// Check if there are any fct_toggle_record_* requests on admin_init, also have a message displayed
		add_action( 'load-edit.php',  array( $this, 'toggle_record'        ) );
		add_action( 'admin_notices',  array( $this, 'toggle_record_notice' ) );

		// Add ability to filter accounts and records per year
		add_filter( 'restrict_manage_posts', array( $this, 'filter_dropdown'             )        );
		add_filter( 'fct_request',       array( $this, 'filter_post_rows'            )        );
		add_filter( 'the_posts',             array( $this, 'records_add_item_attributes' ), 99    );
		add_filter( 'the_posts',             array( $this, 'records_add_rows'            ), 99    );
		add_filter( 'post_class',            array( $this, 'row_post_class'              ), 10, 3 );

		// Filter list table views
		add_filter( 'views_edit-'. $this->post_type, array( $this, 'filter_views' ) );

		// Contextual Help
		add_action( 'load-edit.php',     array( $this, 'edit_help' ) );
		add_action( 'load-post-new.php', array( $this, 'new_help'  ) );

		// Fiscaat requires
		add_action( 'load-edit.php',     array( $this, 'requires' ) );

		// Records page title
		add_filter( 'load-edit.php', array( $this, 'records_page_title' ) );
	}

	/**
	 * Should we bail out of this method?
	 *
	 * @return boolean
	 */
	private function bail() {
		if ( ! isset( get_current_screen()->post_type ) || ( $this->post_type != get_current_screen()->post_type ) )
			return true;

		return false;
	}

	/**
	 * Admin globals
	 *
	 * @access private
	 */
	private function setup_globals() {
		$this->post_type = fct_get_record_post_type();
	}

	/** Contextual Help *******************************************************/

	/**
	 * Contextual help for Fiscaat record edit page
	 *
	 * @uses get_current_screen()
	 */
	public function edit_help() {

		if ( $this->bail() ) return;

		// Overview
		get_current_screen()->add_help_tab( array(
			'id'		=> 'overview',
			'title'		=> __( 'Overview', 'fiscaat' ),
			'content'	=>
				'<p>' . __( 'This screen provides access to all of your records. You can customize the display of this screen to suit your workflow.', 'fiscaat' ) . '</p>'
		) );

		// Screen Content
		get_current_screen()->add_help_tab( array(
			'id'		=> 'screen-content',
			'title'		=> __( 'Screen Content', 'fiscaat' ),
			'content'	=>
				'<p>' . __( 'You can customize the display of this screen&#8217;s contents in a number of ways:', 'fiscaat' ) . '</p>' .
				'<ul>' .
					'<li>' . __( 'You can hide/display columns based on your needs and decide how many records to list per screen using the Screen Options tab.',                                                                                                                                                                          'fiscaat' ) . '</li>' .
					'<li>' . __( 'You can filter the list of records by record status using the text links in the upper left to show All, Published, Draft, or Trashed records. The default view is to show all records.',                                                                                                                   'fiscaat' ) . '</li>' .
					'<li>' . __( 'You can view records in a simple title list or with an excerpt. Choose the view you prefer by clicking on the icons at the top of the list on the right.',                                                                                                                                             'fiscaat' ) . '</li>' .
					'<li>' . __( 'You can refine the list to show only records in a specific category or from a specific month by using the dropdown menus above the records list. Click the Filter button after making your selection. You also can refine the list by clicking on the record author, category or tag in the records list.', 'fiscaat' ) . '</li>' .
				'</ul>'
		) );

		// Available Actions
		get_current_screen()->add_help_tab( array(
			'id'		=> 'action-links',
			'title'		=> __( 'Available Actions', 'fiscaat' ),
			'content'	=>
				'<p>' . __( 'Hovering over a row in the records list will display action links that allow you to manage your record. You can perform the following actions:', 'fiscaat' ) . '</p>' .
				'<ul>' .
					'<li>' . __( '<strong>Edit</strong> takes you to the editing screen for that record. You can also reach that screen by clicking on the record title.',                                                                                 'fiscaat' ) . '</li>' .
					//'<li>' . __( '<strong>Quick Edit</strong> provides inline access to the metadata of your record, allowing you to update record details without leaving this screen.',                                                                  'fiscaat' ) . '</li>' .
					'<li>' . __( '<strong>Trash</strong> removes your record from this list and places it in the trash, from which you can permanently delete it.',                                                                                       'fiscaat' ) . '</li>' .
					'<li>' . __( '<strong>Spam</strong> removes your record from this list and places it in the spam queue, from which you can permanently delete it.',                                                                                   'fiscaat' ) . '</li>' .
					'<li>' . __( '<strong>Preview</strong> will show you what your draft record will look like if you publish it. View will take you to your live site to view the record. Which link is available depends on your record&#8217;s status.', 'fiscaat' ) . '</li>' .
				'</ul>'
		) );

		// Bulk Actions
		get_current_screen()->add_help_tab( array(
			'id'		=> 'bulk-actions',
			'title'		=> __( 'Bulk Actions', 'fiscaat' ),
			'content'	=>
				'<p>' . __( 'You can also edit or move multiple records to the trash at once. Select the records you want to act on using the checkboxes, then select the action you want to take from the Bulk Actions menu and click Apply.',           'fiscaat' ) . '</p>' .
				'<p>' . __( 'When using Bulk Edit, you can change the metadata (categories, author, etc.) for all selected records at once. To remove a record from the grouping, just click the x next to its name in the Bulk Edit area that appears.', 'fiscaat' ) . '</p>'
		) );

		// Help Sidebar
		get_current_screen()->set_help_sidebar(
			'<p><strong>' . __( 'For more information:', 'fiscaat' ) . '</strong></p>' .
			'<p>' . __( '<a href="http://codex.fiscaat.org" target="_blank">Fiscaat Documentation</a>',    'fiscaat' ) . '</p>' .
			'<p>' . __( '<a href="http://fiscaat.org/years/" target="_blank">Fiscaat Support Years</a>', 'fiscaat' ) . '</p>'
		);
	}

	/**
	 * Contextual help for Fiscaat record edit page
	 *
	 * @uses get_current_screen()
	 */
	public function new_help() {

		if ( $this->bail() ) return;

		$customize_display = '<p>' . __( 'The title field and the big record editing Area are fixed in place, but you can reposition all the other boxes using drag and drop, and can minimize or expand them by clicking the title bar of each box. Use the Screen Options tab to unhide more boxes (Excerpt, Send Trackbacks, Custom Fields, Discussion, Slug, Author) or to choose a 1- or 2-column layout for this screen.', 'fiscaat' ) . '</p>';

		get_current_screen()->add_help_tab( array(
			'id'      => 'customize-display',
			'title'   => __( 'Customizing This Display', 'fiscaat' ),
			'content' => $customize_display,
		) );

		get_current_screen()->add_help_tab( array(
			'id'      => 'title-record-editor',
			'title'   => __( 'Title and Record Editor', 'fiscaat' ),
			'content' =>
				'<p>' . __( '<strong>Title</strong> - Enter a title for your record. After you enter a title, you&#8217;ll see the permalink below, which you can edit.', 'fiscaat' ) . '</p>' .
				'<p>' . __( '<strong>Record Editor</strong> - Enter the text for your record. There are two modes of editing: Visual and HTML. Choose the mode by clicking on the appropriate tab. Visual mode gives you a WYSIWYG editor. Click the last icon in the row to get a second row of controls. The HTML mode allows you to enter raw HTML along with your record text. You can insert media files by clicking the icons above the record editor and following the directions. You can go to the distraction-free writing screen via the Fullscreen icon in Visual mode (second to last in the top row) or the Fullscreen button in HTML mode (last in the row). Once there, you can make buttons visible by hovering over the top area. Exit Fullscreen back to the regular record editor.', 'fiscaat' ) . '</p>'
		) );

		$publish_box = '<p>' . __( '<strong>Publish</strong> - You can set the terms of publishing your record in the Publish box. For Status, Visibility, and Publish (immediately), click on the Edit link to reveal more options. Visibility includes options for password-protecting a record or making it stay at the top of your blog indefinitely (sticky). Publish (immediately) allows you to set a future or past date and time, so you can schedule a record to be published in the future or backdate a record.', 'fiscaat' ) . '</p>';

		if ( current_theme_supports( 'record-formats' ) && record_type_supports( 'record', 'record-formats' ) ) {
			$publish_box .= '<p>' . __( '<strong>record Format</strong> - This designates how your theme will display a specific record. For example, you could have a <em>standard</em> blog record with a title and paragraphs, or a short <em>aside</em> that omits the title and contains a short text blurb. Please refer to the Codex for <a href="http://codex.wordpress.org/Post_Formats#Supported_Formats">descriptions of each record format</a>. Your theme could enable all or some of 10 possible formats.', 'fiscaat' ) . '</p>';
		}

		if ( current_theme_supports( 'record-thumbnails' ) && record_type_supports( 'record', 'thumbnail' ) ) {
			$publish_box .= '<p>' . __( '<strong>Featured Image</strong> - This allows you to associate an image with your record without inserting it. This is usually useful only if your theme makes use of the featured image as a record thumbnail on the home page, a custom header, etc.', 'fiscaat' ) . '</p>';
		}

		get_current_screen()->add_help_tab( array(
			'id'      => 'record-attributes',
			'title'   => __( 'Record Attributes', 'fiscaat' ),
			'content' =>
				'<p>' . __( 'Select the attributes that your record should have:', 'fiscaat' ) . '</p>' .
				'<ul>' .
					'<li>' . __( '<strong>Year</strong> dropdown determines the parent year that the record belongs to. Select the year, or leave the default (Use Year of Account) to post the record in year of the account.', 'fiscaat' ) . '</li>' .
					'<li>' . __( '<strong>Account</strong> determines the parent account that the record belongs to.', 'fiscaat' ) . '</li>' .
				'</ul>'
		) );

		get_current_screen()->add_help_tab( array(
			'id'      => 'publish-box',
			'title'   => __( 'Publish Box', 'fiscaat' ),
			'content' => $publish_box,
		) );

		get_current_screen()->add_help_tab( array(
			'id'      => 'discussion-settings',
			'title'   => __( 'Discussion Settings', 'fiscaat' ),
			'content' =>
				'<p>' . __( '<strong>Send Trackbacks</strong> - Trackbacks are a way to notify legacy blog systems that you&#8217;ve linked to them. Enter the URL(s) you want to send trackbacks. If you link to other WordPress sites they&#8217;ll be notified automatically using pingbacks, and this field is unnecessary.', 'fiscaat' ) . '</p>' .
				'<p>' . __( '<strong>Discussion</strong> - You can turn comments and pings on or off, and if there are comments on the record, you can see them here and moderate them.', 'fiscaat' ) . '</p>'
		) );

		get_current_screen()->set_help_sidebar(
			'<p><strong>' . __( 'For more information:', 'fiscaat' ) . '</strong></p>' .
			'<p>' . __( '<a href="http://codex.fiscaat.org" target="_blank">Fiscaat Documentation</a>',    'fiscaat' ) . '</p>' .
			'<p>' . __( '<a href="http://fiscaat.org/years/" target="_blank">Fiscaat Support Years</a>', 'fiscaat' ) . '</p>'
		);
	}

	/**
	 * Add the record attributes metabox
	 *
	 * @uses fct_get_record_post_type() To get the record post type
	 * @uses add_meta_box() To add the metabox
	 * @uses do_action() Calls 'fct_record_attributes_metabox'
	 */
	public function record_attributes_metabox() {

		if ( $this->bail() ) return;

		add_meta_box (
			'fct_record_attributes',
			__( 'Record Attributes', 'fiscaat' ),
			'fct_record_metabox',
			$this->post_type,
			'side',
			'high'
		);

		do_action( 'fct_record_attributes_metabox' );
	}

	/**
	 * Pass the record attributes for processing
	 *
	 * @param int $record_id Record id
	 * @uses current_user_can() To check if the current user is capable of
	 *                           editing the record
	 * @uses do_action() Calls 'fct_record_attributes_metabox_save' with the
	 *                    record id and parent id
	 * @return int Parent id
	 */
	public function record_attributes_metabox_save( $record_id ) {

		if ( $this->bail() ) return $record_id;

		// Bail if doing an autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return $record_id;

		// Bail if not a post request
		if ( 'POST' != strtoupper( $_SERVER['REQUEST_METHOD'] ) )
			return $record_id;

		// Check action exists
		if ( empty( $_POST['action'] ) )
			return $record_id;

		// Nonce check
		if ( empty( $_POST['fct_record_metabox'] ) || !wp_verify_nonce( $_POST['fct_record_metabox'], 'fct_record_metabox_save' ) )
			return $record_id;

		// Current user cannot edit this record
		if ( !current_user_can( 'edit_record', $record_id ) )
			return $record_id;

		// Get the record meta post values
		$account_id = ! empty( $_POST['parent_id'] )              ? (int) $_POST['parent_id'] : 0;
		$year_id    = ! empty( $_POST['fct_record_year_id'] ) ? (int) $_POST['fct_record_year_id'] : fct_get_account_year_id( $account_id );

		// Formally update the record
		fct_update_record( array( 
			'record_id'      => $record_id, 
			'account_id'     => $account_id,
			'year_id'        => $year_id,
			'value'          => ! empty( $_POST['fct_record_value'] )          ? $_POST['fct_record_value']          : 0,
			'value_type'     => ! empty( $_POST['fct_record_value_type'] )     ? $_POST['fct_record_value_type']     : '',
			'offset_account' => ! empty( $_POST['fct_record_offset_account'] ) ? $_POST['fct_record_offset_account'] : 0,
			'status'         => ! empty( $_POST['fct_record_status'] )         ? $_POST['fct_record_status']         : '',
		) );

		// Allow other fun things to happen
		do_action( 'fct_record_attributes_metabox_save', $record_id, $account_id, $year_id );

		return $record_id;
	}

	/**
	 * Add some general styling to the admin area
	 *
	 * @uses fct_get_year_post_type() To get the year post type
	 * @uses fct_get_account_post_type() To get the account post type
	 * @uses fct_get_record_post_type() To get the record post type
	 * @uses sanitize_html_class() To sanitize the classes
	 * @uses do_action() Calls 'fct_admin_head'
	 */
	public function admin_head() {

		if ( $this->bail() ) return; ?>

		<style type="text/css" media="screen">
		/*<![CDATA[*/

			strong.label {
				display: inline-block;
				width: 60px;
			}

			.column-author,
			.column-fct_record_author,
			.column-fct_account_author {
				width: 10% !important;
			}

			.column-fct_record_created,
			.column-fct_record_offset_account,
			.column-fct_record_year,
			.column-fct_record_account {
				width: 10% !important;
			}

			.column-fct_record_account {
				width: 193px !important;
			}

			.wp-list-table td.column-fct_record_created, 
			.wp-list-table td.column-fct_record_description, 
			.wp-list-table td.column-fct_record_account, 
			.wp-list-table td.column-fct_record_account_ledger_id, 
			.wp-list-table td.column-fct_record_offset_account {
				padding: 9px 7px;
			}

			.column-fct_record_value {
				width: 137px;
			}

			.column-fct_record_value.sortable,
			.column-fct_record_value.sorted {
				width: 151px;
			}

			.column-fct_record_value .small-text {
				text-align: right;
				width: 65px;
			}

			.column-fct_record_status {
				width: 45px;
				text-align: center;
			}

			.column-fct_record_status.sortable,
			.column-fct_record_status.sorted {
				width: 67px;
			}

			.status-closed {
				background-color: #eaeaea;
			}

			.status-declined {
				background-color: #faeaea;
			}

			.status-approved {
				background-color: #eafeaf;
			}

			.widefat tbody .record {
				height: 36px;
			}

			.widefat tbody th.check-column {
				padding-top: 10px;
			}

			.widefat tbody th.check-column {
				padding-bottom: 0;
			}

			#the-list .fiscaat-row .check-column input {
				display: none;
			}
			
			#the-list .fiscaat-row-total .column-fct_record_offset_account {
				text-align: right;
			}

			.fct_record_status_icon {
				width: 18px;
				height: 18px;
				margin: 3px auto 0;
				background: #bbb;
				background-repeat: no-repeat;
				background-position: center;
				-webkit-border-radius:100%;
				border-radius:100%;
			}

				.fct_record_status_icon img {
					margin-top: 3px;
				}

				.fct_record_status_icon.status_<?php echo fct_get_public_status_id(); ?> {
					background-color: #de9e0c;
				}

				.fct_record_status_icon.status_<?php echo fct_get_declined_status_id(); ?> {
					background-color: #d54e21;
				}

				.fct_record_status_icon.status_<?php echo fct_get_approved_status_id(); ?> {
					background-color: #21759b;
				}

				.fct_record_status_icon.status_<?php echo fct_get_closed_status_id(); ?> {
					background-color: #999;
				}

			.column-fct_record_account_ledger_id {
				width: 35px;
			}

		/*]]>*/
		</style>

		<?php
	}

	/**
	 * Toggle record
	 *
	 * Handles the admin-side approving/disapproving of records
	 *
	 * @uses fct_get_record() To get the record
	 * @uses current_user_can() To check if the user is capable of editing
	 *                           the record
	 * @uses wp_die() To die if the user isn't capable or the post wasn't
	 *                 found
	 * @uses check_admin_referer() To verify the nonce and check referer
	 * @uses fct_is_record_approved() To check if the record is marked as approved
	 * @uses fct_disapprove_record() To unmark the record as declined
	 * @uses fct_approve_record() To mark the record as approved
	 * @uses do_action() Calls 'fct_toggle_record_admin' with success, post
	 *                    data, action and message
	 * @uses add_query_arg() To add custom args to the url
	 * @uses wp_safe_redirect() Redirect the page to custom url
	 */
	public function toggle_record() {

		if ( $this->bail() ) return;

		// Only proceed if GET is a record toggle action
		if ( 'GET' == $_SERVER['REQUEST_METHOD'] && ! empty( $_GET['action'] ) && in_array( $_GET['action'], array( 'fct_toggle_record_spam' ) ) && ! empty( $_GET['record_id'] ) ) {
			$action    = $_GET['action'];             // What action is taking place?
			$record_id  = (int) $_GET['record_id'];   // What's the record id?
			$success   = false;                       // Flag
			$post_data = array( 'ID' => $record_id ); // Prelim array

			// Get record and die if empty
			$record = fct_get_record( $record_id );
			if ( empty( $record ) ) // Which record?
				wp_die( __( 'The record was not found!', 'fiscaat' ) );

			if ( !current_user_can( 'moderate', $record->ID ) ) // What is the user doing here?
				wp_die( __( 'You do not have the permission to do that!', 'fiscaat' ) );

			switch ( $action ) {
				case 'fct_toggle_record_approval' :
					check_admin_referer( 'approval-record_' . $record_id );

					$approve = fct_is_record_approved( $record_id );
					$message = $approve ? 'declined' : 'approved';
					$success = $approve ? fct_disapprove_record( $record_id ) : fct_approve_record( $record_id );

					break;
			}

			$success = wp_update_post( $post_data );
			$message = array( 'fct_record_toggle_notice' => $message, 'record_id' => $record->ID );

			if ( false == $success || is_wp_error( $success ) )
				$message['failed'] = '1';

			// Do additional record toggle actions (admin side)
			do_action( 'fct_toggle_record_admin', $success, $post_data, $action, $message );

			// Redirect back to the record
			$redirect = add_query_arg( $message, remove_query_arg( array( 'action', 'record_id' ) ) );
			wp_safe_redirect( $redirect );

			// For good measure
			exit();
		}
	}

	/**
	 * Toggle record notices
	 *
	 * Display the success/error notices from
	 * {@link Fiscaat_Admin::toggle_record()}
	 *
	 * @since Fiscaat (r2740)
	 *
	 * @uses fct_get_record() To get the record
	 * @uses fct_get_record_title() To get the record title of the record
	 * @uses esc_html() To sanitize the record title
	 * @uses apply_filters() Calls 'fct_toggle_record_notice_admin' with
	 *                        message, record id, notice and is it a failure
	 */
	public function toggle_record_notice() {

		if ( $this->bail() ) return;

		// Only proceed if GET is a record toggle action
		if ( 'GET' == $_SERVER['REQUEST_METHOD'] && ! empty( $_GET['fct_record_toggle_notice'] ) && in_array( $_GET['fct_record_toggle_notice'], array( 'spammed', 'unspammed' ) ) && ! empty( $_GET['record_id'] ) ) {
			$notice     = $_GET['fct_record_toggle_notice'];    // Which notice?
			$record_id  = (int) $_GET['record_id'];                 // What's the record id?
			$is_failure = ! empty( $_GET['failed'] ) ? true : false; // Was that a failure?

			// Empty? No record?
			if ( empty( $notice ) || empty( $record_id ) )
				return;

			// Get record and bail if empty
			$record = fct_get_record( $record_id );
			if ( empty( $record ) )
				return;

			$record_title = esc_html( fct_get_record_title( $record->ID ) );

			switch ( $notice ) {
				case 'approved' :
					$message = $is_failure == true ? sprintf( __( 'There was a problem marking the record "%1$s" as approved.',    'fiscaat' ), $record_title ) : sprintf( __( 'Record "%1$s" successfully marked as approved.',    'fiscaat' ), $record_title );
					break;

				case 'declined' :
					$message = $is_failure == true ? sprintf( __( 'There was a problem marking the record "%1$s" as declined.', 'fiscaat' ), $record_title ) : sprintf( __( 'Record "%1$s" successfully marked as declined.', 'fiscaat' ), $record_title );
					break;
			}

			// Do additional record toggle notice filters (admin side)
			$message = apply_filters( 'fct_toggle_record_notice_admin', $message, $record->ID, $notice, $is_failure );

			?>

			<div id="message" class="<?php echo $is_failure == true ? 'error' : 'updated'; ?> fade">
				<p style="line-height: 150%"><?php echo $message; ?></p>
			</div>

			<?php
		}
	}

	/**
	 * Manage the column headers for the records page
	 *
	 * @param array $columns The columns
	 * @uses apply_filters() Calls 'fct_admin_records_column_headers' with
	 *                        the columns
	 * @return array $columns Fiscaat record columns
	 */
	public function records_column_headers( $columns ) {

		if ( $this->bail() ) return $columns;

		$columns = array(
			'cb'                               => '<input type="checkbox" />',
			'fct_record_status'            => __( 'Status',                'fiscaat' ),
			'fct_record_created'           => __( 'Date',                  'fiscaat' ),
			'fct_record_account_ledger_id' => _x( 'No.', 'ledger id column name', 'fiscaat' ),
			'fct_record_account'           => __( 'Account',               'fiscaat' ),
			'fct_record_description'       => __( 'Description',           'fiscaat' ),
			'fct_record_offset_account'    => __( 'Offset Account',        'fiscaat' ),
			'fct_record_value'             => __( 'Debit/Credit',          'fiscaat' ),
			'comments'                         => __( 'Comments',              'fiscaat' ),
			// 'fct_record_author'            => __( 'Author',                'fiscaat' ),
			// 'fct_record_year'              => __( 'Year',                  'fiscaat' ),
		);

		// Control
		if ( ! fct_is_control_active() )
			unset( $columns['fct_record_status'] );

		// Comment section
		if ( ! fct_is_comments_active() )
			unset( $columns['comments'] );

		// Account records page doesn't need account rows
		if ( isset( $_GET['fct_account_id'] ) && ! empty( $_GET['fct_account_id'] ) )
			unset( $columns['fct_record_account_ledger_id'], $columns['fct_record_account'] );

		return apply_filters( 'fct_admin_records_column_headers', $columns );
	}

	/**
	 * Make records columns sortable
	 * 
	 * @param array $columns Sortable columns
	 * @return array Sortable records columns
	 */
	public function records_sortable_columns( $columns ) {

		if ( $this->bail() ) return $columns;

		// Make status column sortable
		$columns['fct_record_status'] = 'post_status';

		// Make date column sortable
		$columns['fct_record_created'] = 'record_created';

		// Make offset account column sortable
		$columns['fct_record_offset_account'] = 'record_offset_account';

		// Make debit/credit column sortable
		$columns['fct_record_value'] = 'record_value';

		return apply_filters( 'fct_records_sortable_columns', $columns );
	}

	/**
	 * Print extra columns for the records page
	 *
	 * @param string $column Column
	 * @param int $record_id record id
	 * @uses fct_get_record_account_id() To get the account id of the record
	 * @uses fct_account_title() To output the record's account title
	 * @uses apply_filters() Calls 'record_account_row_actions' with an array
	 *                        of record account actions
	 * @uses fct_get_account_permalink() To get the account permalink
	 * @uses fct_get_account_year_id() To get the year id of the account of
	 *                                 the record
	 * @uses fct_get_year_permalink() To get the year permalink
	 * @uses admin_url() To get the admin url of post.php
	 * @uses add_query_arg() To add custom args to the url
	 * @uses apply_filters() Calls 'record_account_year_row_actions' with an
	 *                        array of record account year actions
	 * @uses fct_record_author_display_name() To output the record author name
	 * @uses get_the_date() Get the record creation date
	 * @uses get_the_time() Get the record creation time
	 * @uses esc_attr() To sanitize the record creation time
	 * @uses fct_get_record_last_active_time() To get the time when the record was
	 *                                    last active
	 * @uses do_action() Calls 'fct_admin_records_column_data' with the
	 *                    column and record id
	 */
	public function records_column_data( $column, $record_id ) {
		global $post;

		if ( $this->bail() ) return;

		// Get account ID
		$account_id = fct_get_record_account_id( $record_id );

		// For normal rows
		if ( ! isset( $post->fct_row ) || ! $post->fct_row ) :

			// Populate Column Data
			switch ( $column ) {

				// Freshness
				case 'fct_record_created':
					echo get_the_date();
					break;

				// Account ledger ID
				case 'fct_record_account_ledger_id' :

					// Output ledger ID
					if ( ! empty( $account_id ) )
						fct_account_records_admin_link( $account_id, true );

					break;

				// Account
				case 'fct_record_account' :

					// Output title name
					if ( ! empty( $account_id ) ) {

						// Account Title
						$account_title = fct_get_account_records_admin_link( $account_id );
						if ( empty( $account_title ) ) {
							$account_title = __( 'No Account', 'fiscaat' );
						}

						// Output the title
						echo $account_title;

					// Record has no account
					} else {
						// _e( 'No Account', 'fiscaat' );
					}

					break;

				// Description
				case 'fct_record_description' :
					fct_record_excerpt( $record_id );
					break;

				// Offset account
				case 'fct_record_offset_account' :
					fct_record_offset_account( $record_id );
					break;

				// Debit/Credit
				case 'fct_record_value' :
				?>

					<input id="fct_record_<?php echo $post->ID; ?>_debit_value"  class="fct_record_debit_value small-text"  type="text" value="<?php if ( fct_get_debit_record_type()  == $post->fct_value_type ){ fct_currency_format( $post->fct_value ); } ?>" disabled="disabled" />
					<input id="fct_record_<?php echo $post->ID; ?>_credit_value" class="fct_record_credit_value small-text" type="text" value="<?php if ( fct_get_credit_record_type() == $post->fct_value_type ){ fct_currency_format( $post->fct_value ); } ?>" disabled="disabled" />

				<?php
					break;

				case 'fct_record_status' :
					fct_record_status_icon( $record_id );
					break;

				// Comments @todo Fix comment system
				case 'comments' :
				?>

					<td <?php echo $attributes ?>><div class="post-com-count-wrapper">
					<?php echo "<a href='" . esc_url( add_query_arg( 'p', $record_id, admin_url( 'edit-comments.php' ) ) ) . "' class='post-com-count'><span class='comment-count'>" . number_format_i18n( get_comments_number() ) . "</span></a>"; ?>
					</div></td>

				<?php
					break;

				// Author
				case 'fct_record_author' :
					fct_record_author_display_name( $record_id );
					break;

				// Year
				case 'fct_record_year' :

					// Get Year ID's
					$record_year_id = fct_get_record_year_id( $record_id );
					$account_year_id = fct_get_account_year_id( $account_id );

					// Output year name
					if ( ! empty( $record_year_id ) ) {

						// Year Title
						$year_title = fct_get_year_title( $record_year_id );
						if ( empty( $year_title ) ) {
							$year_title = __( 'No Year', 'fiscaat' );
						}

						// Alert capable users of record year mismatch
						if ( $record_year_id != $account_year_id ) {
							if ( current_user_can( 'edit_others_records' ) || current_user_can( 'fiscaat' ) ) {
								$year_title .= '<div class="attention">' . __( '(Mismatch)', 'fiscaat' ) . '</div>';
							}
						}

						// Output the title
						echo $year_title;

					// Record has no year
					} else {
						_e( 'No Year', 'fiscaat' );
					}

					break;

				// Do action for anything else
				default :
					do_action( 'fct_admin_records_column_data', $column, $record_id );
					break;

			} // switch normal rows

		// 'From Balance' & 'To Balance/Income Statement' row
		elseif ( ( isset( $post->fct_row_from ) && $post->fct_row_from ) 
			|| ( isset( $post->fct_row_to ) && $post->fct_row_to ) ) :

			// Get account id
			$account_id = (int) $_GET['fct_account_id'];

			// 'To Balance/Income Statement' row?
			$to_row = isset( $post->fct_row_to ) && $post->fct_row_to;

			switch ( $column ) {

				// Date
				case 'fct_record_created' :
					if ( ! $to_row ) {
						$account = fct_get_account( $account_id );
						echo fct_convert_date( $account->post_date, get_option( 'date_format' ), true );
					} else {
						echo fct_convert_date( fct_get_current_time(), get_option( 'date_format' ), true );
					}
					break;

				// Description
				case 'fct_record_description' :
					if ( fct_get_asset_account_type() == fct_get_account_account_type( $account_id ) )
						echo ! $to_row ? __('From Balance', 'fiscaat') : __('To Balance', 'fiscaat');
					else
						echo ! $to_row ? '' : __('To Income Statement', 'fiscaat');
					break;

				// Result values
				case 'fct_record_value' :
					$row   = ! $to_row ? 'from' : 'to'; ?>

					<input id="fct_records_<?php echo $row; ?>_value_debit"  class="fct_record_debit_value small-text"  type="text" value="<?php if ( fct_get_debit_record_type()  == $post->fct_value_type ){ fct_currency_format( $post->fct_value ); } ?>" disabled="disabled" />
					<input id="fct_records_<?php echo $row; ?>_value_credit" class="fct_record_credit_value small-text" type="text" value="<?php if ( fct_get_credit_record_type() == $post->fct_value_type ){ fct_currency_format( $post->fct_value ); } ?>" disabled="disabled" />

					<?php
					break;

				// Default hookable
				default :
					if ( ! $to_row )
						do_action( 'fct_admin_records_column_data_from', $column );
					else
						do_action( 'fct_admin_records_column_data_to',   $column );
					break;

			} // switch from/to balance

		// 'Total Sum' row
		elseif ( isset( $post->fct_row_total ) && $post->fct_row_total ) :

			// Check columns
			switch ( $column ) {

				// Description
				case 'fct_record_description' :
					_ex('Total', 'Total record sum', 'fiscaat');
					break;

				// Evaluate values
				case 'fct_record_value' :
				?>

					<input id="fct_records_debit_total"  class="fct_record_debit_value fct_record_total small-text"  type="text" value="<?php fct_currency_format( $post->fct_debit_total ); ?>" disabled="disabled" />
					<input id="fct_records_credit_total" class="fct_record_credit_value fct_record_total small-text" type="text" value="<?php fct_currency_format( $post->fct_credit_total ); ?>" disabled="disabled" />

				<?php
					break;

				// Default hookable
				default :
					do_action( 'fct_admin_records_column_data_total', $column );
					break;

			} // switch total sum

		endif;
	}

	/**
	 * Record Row actions
	 *
	 * Remove the quick-edit action link under the record title and add the
	 * content and spam link
	 *
	 * @param array $actions Actions
	 * @param array $record Record object
	 * @uses fct_get_record_post_type() To get the record post type
	 * @uses fct_record_content() To output record content
	 * @uses fct_get_record_permalink() To get the record link
	 * @uses fct_get_record_title() To get the record title
	 * @uses current_user_can() To check if the current user can edit or
	 *                           delete the record
	 * @uses fct_is_record_approved() To check if the record is marked as approved
	 * @uses get_post_type_object() To get the record post type object
	 * @uses add_query_arg() To add custom args to the url
	 * @uses remove_query_arg() To remove custom args from the url
	 * @uses wp_nonce_url() To nonce the url
	 * @uses get_delete_post_link() To get the delete post link of the record
	 * @return array $actions Actions
	 */
	public function records_row_actions( $actions, $record ) {

		if ( $this->bail() ) return $actions;

		unset( $actions['inline hide-if-no-js'] );

		// Record view links to account
		$actions['view'] = '<a href="' . fct_get_record_url( $record->ID ) . '" title="' . esc_attr( sprintf( __( 'View &#8220;%s&#8221;', 'fiscaat' ), fct_get_record_title( $record->ID ) ) ) . '" rel="permalink">' . __( 'View', 'fiscaat' ) . '</a>';

		// User cannot view records in trash
		if ( ( fct_get_trash_status_id() == $record->post_status ) && !current_user_can( 'view_trash' ) )
			unset( $actions['view'] );

		// Only show the actions if the user is capable of viewing them and record is open
		if ( current_user_can( 'control', $record->ID ) ) {
			if ( fct_record_is_open( $record->ID ) ) {
				$approval_uri  = esc_url( wp_nonce_url( add_query_arg( array( 'record_id' => $record->ID, 'action' => 'fct_toggle_record_approval' ), remove_query_arg( array( 'fct_record_toggle_notice', 'record_id', 'failed', 'super' ) ) ), 'approval-record_'  . $record->ID ) );
				if ( ! fct_is_record_approved( $record->ID ) ) {
					$actions['approval'] = '<a href="' . $approval_uri . '" title="' . esc_attr__( 'Mark this record as approved',    'fiscaat' ) . '">' . __( 'Approve',    'fiscaat' ) . '</a>';
				} elseif( ! fct_is_record_declined( $record->ID ) ) {
					$actions['approval'] = '<a href="' . $approval_uri . '" title="' . esc_attr__( 'Mark this record as declined', 'fiscaat' ) . '">' . __( 'Disapprove', 'fiscaat' ) . '</a>';
				}
			}
		}

		// Trash
		if ( current_user_can( 'delete_record', $record->ID ) ) {
			if ( fct_get_trash_status_id() == $record->post_status ) {
				$post_type_object = get_post_type_object( fct_get_record_post_type() );
				$actions['untrash'] = "<a title='" . esc_attr__( 'Restore this item from the Trash', 'fiscaat' ) . "' href='" . add_query_arg( array( '_wp_http_referer' => add_query_arg( array( 'post_type' => fct_get_record_post_type() ), admin_url( 'edit.php' ) ) ), wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $record->ID ) ), 'untrash-' . $record->post_type . '_' . $record->ID ) ) . "'>" . __( 'Restore', 'fiscaat' ) . "</a>";
			} elseif ( EMPTY_TRASH_DAYS ) {
				$actions['trash'] = "<a class='submitdelete' title='" . esc_attr__( 'Move this item to the Trash', 'fiscaat' ) . "' href='" . add_query_arg( array( '_wp_http_referer' => add_query_arg( array( 'post_type' => fct_get_record_post_type() ), admin_url( 'edit.php' ) ) ), get_delete_post_link( $record->ID ) ) . "'>" . __( 'Trash', 'fiscaat' ) . "</a>";
			}

			if ( fct_get_trash_status_id() == $record->post_status || !EMPTY_TRASH_DAYS ) {
				$actions['delete'] = "<a class='submitdelete' title='" . esc_attr__( 'Delete this item permanently', 'fiscaat' ) . "' href='" . add_query_arg( array( '_wp_http_referer' => add_query_arg( array( 'post_type' => fct_get_record_post_type() ), admin_url( 'edit.php' ) ) ), get_delete_post_link( $record->ID, '', true ) ) . "'>" . __( 'Delete Permanently', 'fiscaat' ) . "</a>";
			} elseif ( fct_get_spam_status_id() == $record->post_status ) {
				unset( $actions['trash'] );
			}
		}

		return $actions;
	}

	/**
	 * Add year dropdown to account and record list table filters
	 *
	 * @uses fct_get_record_post_type() To get the record post type
	 * @uses fct_get_account_post_type() To get the account post type
	 * @uses fct_dropdown() To generate a year dropdown
	 * @return bool False. If post type is not account or record
	 */
	public function filter_dropdown() {

		if ( $this->bail() ) return;

		// Get which year is selected
		$year_id = ! empty( $_REQUEST['fct_year_id'] ) ? (int) $_REQUEST['fct_year_id'] : fct_get_current_year_id();

		// Show the years dropdown
		fct_dropdown( array(
			'selected'   => $year_id,
			'show_none'  => __( 'In current year', 'fiscaat' )
		) );
		
		// Get which account is selected. With account id or ledger id
		$account_id = ! empty( $_REQUEST['fct_account_id'] )         ? (int) $_REQUEST['fct_account_id']         : 0;
		$ledger_id  = ! empty( $_REQUEST['fct_ledger_account_id']  ) ? (int) $_REQUEST['fct_ledger_account_id']  : '';

		// Ledger id was set, account id not
		if ( ! empty( $ledger_id ) && empty( $account_id ) )
			$account_id = fct_get_account_id_by_ledger_id( $ledger_id, $year_id );

		// Show the ledger dropdown
		fct_ledger_dropdown( array(
			'selected'   => $account_id,
			'year_id'    => $year_id,
			'show_none'  => '&mdash;',
			'none_found' => true,
		) );

		// Show the accounts dropdown
		fct_account_dropdown( array(
			'selected'  => $account_id,
			'year_id'   => $year_id,
		) );
	}

	/**
	 * Adjust the request query and include the year id
	 *
	 * @param array $query_vars Query variables from {@link WP_Query}
	 * @uses is_admin() To check if it's the admin section
	 * @uses fct_get_account_post_type() To get the account post type
	 * @uses fct_get_record_post_type() To get the record post type
	 * @return array Processed Query Vars
	 */
	public function filter_post_rows( $query_vars ) {

		if ( $this->bail() ) return $query_vars;

		// Setup meta query
		$meta_query = isset( $query_vars['meta_query'] ) ? $query_vars['meta_query'] : array();

		/** Year & Account ****************************************************/

		// Set the year id
		$meta_query[] = array(
			'key'   => '_fct_year_id',
			'value' => ! empty( $_REQUEST['fct_year_id'] ) ? (int) $_REQUEST['fct_year_id'] : fct_get_current_year_id()
		);
		
		// Set the parent if given
		if ( ! empty( $_REQUEST['fct_account_id'] ) ) {
			$query_vars['post_parent'] = (int) $_REQUEST['fct_account_id'];

		// Set the parent from ledger_id if given
		} elseif ( ! empty( $_REQUEST['fct_ledger_account_id'] ) ) {
			$query_vars['post_parent'] = (int) $_REQUEST['fct_ledger_account_id'];
		}

		/** Approval **********************************************************/

		// Check approval
		if ( isset( $_REQUEST['approval'] ) && fct_is_control_active() ) {

			// Check approval states
			switch ( (int) $_REQUEST['approval'] ) {

				// Unapproved
				case 0 :
					$query_vars['post_status'] = array( fct_get_public_status_id(), fct_get_declined_status_id() ); // + declined?
					break;

				// Approved
				case 1 :
					$query_vars['post_status'] = array( fct_get_approved_status_id(), fct_get_closed_status_id() ); // + closed?
					break;

				// Declined
				case 2 :
					$query_vars['post_status'] = fct_get_declined_status_id();
					break;
			}
		}

		/** Sorting ***********************************************************/

		// Handle sorting
		if ( isset( $_REQUEST['orderby'] ) ) {

			// Check order type
			switch ( $_REQUEST['orderby'] ) {

				// Record date. Reverse order
				case 'record_created' :
					$query_vars['orderby'] = 'post_date';
					$query_vars['order']   = isset( $_REQUEST['order'] ) && 'DESC' == strtoupper( $_REQUEST['order'] ) ? 'ASC' : 'DESC';
					break;

				// Record value
				case 'record_value' :
					$query_vars['meta_key'] = '_fct_value'; // No meta_query
					$query_vars['orderby']  = 'meta_value_num';
					break;

				// Record offset account
				case 'record_offset_acount' :
					$query_vars['meta_key'] = '_fct_offset_account'; // No meta_query
					$query_vars['orderby']  = 'meta_value'; // Account can be string
					break;
			}

			// Default sorting order
			if ( ! isset( $query_vars['order'] ) )
				$query_vars['order'] = isset( $_REQUEST['order'] ) ? strtoupper( $_REQUEST['order'] ) : 'ASC';
		}

		// Set meta query
		$query_vars['meta_query'] = $meta_query;

		// Return manipulated query_vars
		return $query_vars;
	}

	/**
	 * Adds custom attributes to records items
	 *
	 * @todo Make this happen for front-end too
	 * @todo Make 'Total Sum' row Pagination agnostic on last page
	 * @param array $items Found records
	 */
	public function records_add_item_attributes( $items ) {

		if ( $this->bail() ) return $items;

		// Add Fiscaat record arguments
		foreach ( $items as $k => $record ) {

			// Set record value and record value type
			if ( ! empty( $record->ID ) ) {
				$record->fct_value      = fct_get_record_value( $record->ID );
				$record->fct_value_type = fct_get_record_value_type( $record->ID );

			// Empty record
			} else {
				$record->fct_value      = false;
				$record->fct_value_type = false;
			}

			// Store new record
			$items[$k] = $record;
		}

		// Hook records
		return apply_filters( 'records_add_item_attributes', $items );
	}

	/**
	 * Adds custom rows on the account records page
	 *
	 * Creates the 'From Balance', 'To Balance/Income Statement' and 'Total Sum' rows.
	 * Admin pagination will take care of the right display.
	 *
	 * @todo Make this happen for front-end too
	 * @todo Make 'Total Sum' row Pagination agnostic on last page
	 * @param array $items Found records
	 */
	public function records_add_rows( $items ) {

		if ( $this->bail() ) return $items;

		// Setup empty row
		$new_row = array(
			'ID'                 => 0, // To be set per row
			'post_parent'        => 0,
			'post_status'        => fct_get_public_status_id(),
			'post_type'          => fct_get_record_post_type(),
			'post_title'         => '',
			'post_content'       => '',
			'post_author'        => 0,
			'post_date'          => 0,
			'menu_order'         => 0,

			// Fiscaat arguments
			'fct_row'        => true,
			'fct_value'      => 0,
			'fct_value_type' => '',
		);

		// Account records page
		if ( isset( $_GET['fct_account_id'] ) && ! empty( $_GET['fct_account_id'] ) ) {

			// Get account id
			$account_id       = (int) $_GET['fct_account_id'];
			$is_asset_account = fct_get_asset_account_type() == fct_get_account_account_type( $account_id );

			// Set 'From Balance' row if account is of asset type
			if ( $is_asset_account ) {
				$row_from = $new_row;

				// Set 'From Balance' row ID. Strings will be converted to (int) 0.
				$row_from['ID'] = -1;
				$row_from['fct_row_from'] = true;

				// Get and set 'From Balance' value
				$value = fct_get_account_from_value( $account_id );
				$row_from['fct_value']      = abs( $value );
				$row_from['fct_value_type'] = $value >= 0 ? fct_get_debit_record_type() : fct_get_credit_record_type();

				// Prepend 'From Balance' row to items
				array_unshift( $items, (object) apply_filters( 'fct_records_row_from', $row_from ) );

	 		// Bail if no records found for result type
			} elseif ( empty( $items ) ) {
				return $items;
			}

			$row_to = $new_row;

			// Set 'To Balance' row ID. Strings will be converted to (int) 0.
			$row_to['ID'] = -2;
			$row_to['fct_row_to'] = true;

			// Get 'To Balance/Income Statement' value
			$value = fct_get_account_to_value( $account_id );

			// Recalculate 'To Balance/Income Statement' value if none found
			if ( apply_filters( 'fct_force_recalculate_account_to_value', true ) || empty( $value ) ) {
				$value = 0;
				foreach ( $items as $record ) {
					$value += empty( $record->fct_value_type ) 
						? $record->fct_value 
						: fct_get_debit_record_type() == $record->fct_value_type
							? $record->fct_value       // Add debit values
							: $record->fct_value * -1; // Subtract credit values
				}

				// Update account to_value
				fct_update_account_to_value( $account_id, $value );
			}

			// Set 'To Balance/Income Statement' value and value type
			$row_to['fct_value']      = abs( $value );
			$row_to['fct_value_type'] = $value >= 0 ? fct_get_credit_record_type() : fct_get_debit_record_type();

			// Append 'To Balance' row to items for result and asset type
			$items[] = (object) apply_filters( 'fct_records_row_to', $row_to );
		}

		$row_total = $new_row;

		// Set 'Total Sum' row ID. Strings will be converted to (int) 0.
		$row_total['ID'] = -3;
		$row_total['fct_row_total'] = true;

		// Calculate 'Total Sum' values
		$debit_total = $credit_total = 0;
		foreach ( $items as $record ) {
			if ( fct_get_debit_record_type() == $record->fct_value_type )
				$debit_total  += $record->fct_value;
			elseif ( fct_get_credit_record_type() == $record->fct_value_type )
				$credit_total += $record->fct_value;
		}

		// Set total values
		$row_total['fct_debit_total']  = (float) $debit_total;
		$row_total['fct_credit_total'] = (float) $credit_total;

		// Always append 'Total Sum' row to items
		$items[] = (object) apply_filters( 'fct_records_row_total', $row_total );

		return apply_filters( 'fct_records_add_rows', $items );
	}

	/**
	 * Add fiscaat-row and fiscaat-row-{type} classes to custom table rows
	 */
	public function row_post_class( $classes, $class, $post_id ) {
		if ( $this->bail() ) return $classes;

		if ( in_array( $post_id, array( -1, -2, -3 ) ) ) {
			$classes[] = 'fiscaat-row';

			switch ( $post_id ) {
				case -1: 
					$classes[] = 'fiscaat-row-from';
					break;
				case -2:
					$classes[] = 'fiscaat-row-to';
					break;
				case -3:
					$classes[] = 'fiscaat-row-total';
					break;
			}
		}

		return $classes;
	}

	/**
	 * Return recount account records views
	 *
	 * Only difference is the use of fct_count_posts instead of
	 * wp_count_posts, which doesn't allow for count query filtering.
	 *
	 * Modified code from WP_Posts_List_Table::get_views().
	 * 
	 * @param array $views Records views
	 * @uses fct_count_posts() To count the account's records
	 * @return array Account records views
	 */
	public function filter_views( $views ) {

		if ( $this->bail() ) return $views;

		// Bail when not on account records page
		if ( ! isset( $_GET['fct_account_id'] ) || empty( $_GET['fct_account_id'] ) ) return $views;

		global $post_type_object, $avail_post_stati;

		$post_type  = $post_type_object->name;
		$account_id = (int) $_GET['fct_account_id'];

		$status_links = array();
		$num_posts = fct_count_posts( array( 'type' => $post_type, 'perm' => 'readable', 'parent' => $account_id ) );
		$class = '';
		$allposts = '';

		$current_user_id = get_current_user_id();
		$total_posts = array_sum( (array) $num_posts );

		// Subtract post types that are not included in the admin all list.
		foreach ( get_post_stati( array('show_in_admin_all_list' => false) ) as $state )
			$total_posts -= $num_posts->$state;

		$class = empty( $class ) && ( empty( $_REQUEST['post_status'] ) || 'all' == $_REQUEST['post_status'] ) ? ' class="current"' : '';
		$status_links['all'] = "<a href='edit.php?post_type=$post_type&amp;fct_account_id=$account_id{$allposts}'$class>" . sprintf( _nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $total_posts, 'posts' ), number_format_i18n( $total_posts ) ) . '</a>';

		foreach ( get_post_stati(array('show_in_admin_status_list' => true), 'objects') as $status ) {
			$class = '';

			$status_name = $status->name;

			if ( !in_array( $status_name, $avail_post_stati ) )
				continue;

			if ( empty( $num_posts->$status_name ) )
				continue;

			if ( isset($_REQUEST['post_status']) && $status_name == $_REQUEST['post_status'] )
				$class = ' class="current"';

			$status_links[$status_name] = "<a href='edit.php?post_status=$status_name&amp;post_type=$post_type&amp;fct_account_id=$account_id'$class>" . sprintf( translate_nooped_plural( $status->label_count, $num_posts->$status_name ), number_format_i18n( $num_posts->$status_name ) ) . '</a>';
		}

		return $status_links;
	}

	/**
	 * Redirect user to record edit page with correct message id
	 *
	 * @uses fct_has_open_year()
	 * @uses fct_has_open_account()
	 * @uses fct_get_record_post_type()
	 * @uses add_query_arg()
	 * @uses wp_safe_redirect()
	 */
	public function requires() {

		if ( $this->bail() ) return;

		// Bail if not on create page
		if ( isset( $_GET['action'] ) && 'create' != $_GET['action'] )
			return;

		// Check for message
		if ( isset( $_GET['message'] )
			&& ( ( 11 == $_GET['message'] && ! fct_has_open_year() )
				|| ( 12 == $_GET['message'] && ! fct_has_open_account() )
		) ) {
			return;

		// Install has no open year
		} elseif ( ! fct_has_open_year() ) {
			$message = 11;

		// Install has no open account
		} elseif ( ! fct_has_open_account() ) {
			$message = 12;

		// Everything okay
		} else {
			return;
		}

		// Redirect user with message
		wp_safe_redirect( add_query_arg( array( 'post_type' => fct_get_record_post_type(), 'message' => $message, 'page' => 'new' ), admin_url( 'edit.php' ) ) );
	}

	/**
	 * Custom user feedback messages for record post type
	 *
	 * @global int $post_ID
	 * @uses fct_get_account_permalink()
	 * @uses wp_post_revision_title()
	 * @uses esc_url()
	 * @uses add_query_arg()
	 *
	 * @param array $messages
	 *
	 * @return array
	 */
	public function updated_messages( $messages ) {
		global $post_ID;

		if ( $this->bail() ) return $messages;

		// URL for the current account
		$account_url = fct_get_account_permalink( fct_get_record_account_id( $post_ID ) );

		// Current record's post_date
		$post_date = fct_get_global_post_field( 'post_date', 'raw' );

		// Messages array
		$messages[$this->post_type] = array(
			0 =>  '', // Left empty on purpose

			// Updated
			1 =>  sprintf( __( 'Record updated. <a href="%s">View account</a>', 'fiscaat' ), $account_url ),

			// Custom field updated
			2 => __( 'Custom field updated.', 'fiscaat' ),

			// Custom field deleted
			3 => __( 'Custom field deleted.', 'fiscaat' ),

			// Record updated
			4 => __( 'Record updated.', 'fiscaat' ),

			// Restored from revision
			// translators: %s: date and time of the revision
			5 => isset( $_GET['revision'] )
					? sprintf( __( 'Record restored to revision from %s', 'fiscaat' ), wp_post_revision_title( (int) $_GET['revision'], false ) )
					: false,

			// Record created
			6 => sprintf( __( 'Record created. <a href="%s">View account</a>', 'fiscaat' ), $account_url ),

			// Record saved
			7 => __( 'Record saved.', 'fiscaat' ),

			// Record submitted
			8 => sprintf( __( 'Record submitted. <a target="_blank" href="%s">Preview account</a>', 'fiscaat' ), esc_url( add_query_arg( 'preview', 'true', $account_url ) ) ),

			// Record scheduled
			9 => sprintf( __( 'Record scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview account</a>', 'fiscaat' ),
					// translators: Publish box date format, see http://php.net/date
					date_i18n( __( 'M j, Y @ G:i', 'fiscaat' ),
					strtotime( $post_date ) ),
					$account_url ),

			// Record draft updated
			10 => sprintf( __( 'Record draft updated. <a target="_blank" href="%s">Preview account</a>', 'fiscaat' ), esc_url( add_query_arg( 'preview', 'true', $account_url ) ) ),

			// Require a year
			11 => sprintf( __('Using Fiscaat requires an open year to register records in. <a href="%s">Create a year first</a>.', 'fiscaat'), esc_url( add_query_arg( 'post_type', fct_get_year_post_type(), admin_url( 'post-new.php' ) ) ) ),

			// Require an account
			12 => sprintf( __('Using Fiscaat requires the current year to have an account to assign records to. <a href="%s">Create an account first</a>.', 'fiscaat'), esc_url( add_query_arg( 'post_type', fct_get_account_post_type(), admin_url( 'post-new.php' ) ) ) ),

			// Can not use post-new.php
			13 => sprintf( __('Don\'t know how you got here, but records are added otherwise. <a href="%s">Lead me there</a>.', 'fiscaat'), esc_url( add_query_arg( array( 'post_type' => fct_get_account_post_type(), 'page' => 'new' ), admin_url( 'edit.php' ) ) ) ),
		);

		return $messages;
	}

	/** Page Title ************************************************************/

	/**
	 * Modify the post type name label for record edit page
	 *
	 * @uses fct_get_record_post_type()
	 * @uses fct_get_account_ledger_id()
	 * @uses fct_get_account_title() To get the account title
	 * @uses apply_filters() Calls 'fct_records_page_title' with the
	 *                        new label name, and account id
	 * @return array Modified arguments
	 */
	public function records_page_title() {

		if ( $this->bail() ) return;

		global $wp_post_types;

		// Get post type labels
		$labels = $wp_post_types[fct_get_record_post_type()]->labels;

		// Modify post type name if account is set
		if ( isset( $_GET['fct_account_id'] ) && ! empty( $_GET['fct_account_id'] ) ) {

			// Fetch account id
			$account_id = (int) $_GET['fct_account_id'];

			// Create new label = post type - account number. account title
			$title = $labels->name .' &mdash; '. fct_get_account_ledger_id( $account_id ) .'. '. fct_get_account_title( $account_id );

			// Modify label
			$labels->name = apply_filters( 'fct_account_records_page_title', $title, $account_id );

		// Modify post type name if account isn't set
		} else {
			
			// Fetch year id
			$year_id = isset( $_GET['fct_year_id'] ) ? (int) $_GET['fct_year_id'] : fct_get_current_year_id();

			// Create new label = post type - account number. account title
			$title = $labels->name .' &mdash; '. fct_get_year_title( $year_id );

			// Modify label
			$labels->name = apply_filters( 'fct_year_records_page_title', $title, $year_id );
		}

		// Set post type labels
		$wp_post_types[fct_get_record_post_type()]->labels = $labels;
	}

}

endif; // class_exists check

/**
 * Setup Fiscaat Records Admin
 *
 * This is currently here to make hooking and unhooking of the admin UI easy.
 * It could use dependency injection in the future, but for now this is easier.
 *
 * @uses Fiscaat_Records_Admin
 */
function fct_admin_records() {
	fiscaat()->admin->records = new Fiscaat_Records_Admin();
}
