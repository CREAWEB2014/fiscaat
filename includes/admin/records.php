<?php

/**
 * Fiscaat Records Admin Class
 *
 * @package Fiscaat
 * @subpackage Administration
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Fiscaat_Records_Admin' ) ) :
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
	 * @uses fct_get_period_post_type() To get the period post type
	 * @uses fct_get_account_post_type() To get the account post type
	 * @uses fct_get_record_post_type() To get the record post type
	 */
	private function setup_actions() {

		/** Sub-Actions *******************************************************/

		add_action( 'fct_admin_load_list_records',  array( $this, 'page_mode_actions' ) );

		/** Actions ***********************************************************/

		// Add some general styling to the admin area
		add_action( 'fct_admin_head', array( $this, 'admin_head' ) );

		// Record metabox actions
		add_action( 'add_meta_boxes', array( $this, 'record_attributes_metabox'      ) );
		add_action( 'save_post',      array( $this, 'record_attributes_metabox_save' ) );

		// Set records's parents earlier
		add_action( 'fct_admin_load_view_records',  array( $this, 'records_parents' ), 5 );

		// Check if there are any fct_toggle_record_* requests on admin_init, also have a message displayed
		add_action( 'fct_admin_load_edit_records',  array( $this, 'toggle_record'        ) );
		add_action( 'fct_admin_notices',            array( $this, 'toggle_record_notice' ) );

		// Contextual Help
		add_action( 'fct_admin_load_view_records',  array( $this, 'edit_help' ) );
		add_action( 'fct_admin_load_new_records',   array( $this, 'new_help'  ) );
		add_action( 'fct_admin_load_edit_records',  array( $this, 'edit_help' ) );

		// Check if there is a missing open period or account on record add/edit, also have a message displayed
		add_action( 'fct_admin_load_new_records',   array( $this, 'missing_redirect' ) );
		add_action( 'fct_admin_load_edit_records',  array( $this, 'missing_redirect' ) );
		add_action( 'fct_admin_notices',            array( $this, 'missing_notices'  ) );

		// Modify page title
		add_action( 'fct_admin_records_page_title', array( $this, 'records_page_title' ) );
		add_action( 'fct_admin_records_page_title', array( $this, 'post_new_link'      ) );

		/** Redirect **********************************************************/

		add_action( 'fct_admin_load_post_record', array( $this, 'redirect_post_new_page' ), 0 );

		/** Filters ***********************************************************/

		// Messages
		add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );

		// Add ability to filter accounts and records per period
		add_filter( 'restrict_manage_posts', array( $this, 'filter_dropdown'  ) );
		add_filter( 'fct_request',           array( $this, 'filter_post_rows' ) );

		// Record columns (in post row)
		add_filter( 'fct_admin_records_get_columns', array( $this, 'records_column_headers' )        );
		add_filter( 'post_row_actions',              array( $this, 'records_row_actions'    ), 10, 2 );
	}

	/**
	 * Setup default admin class globals
	 *
	 * @access private
	 */
	private function setup_globals() {
		$this->post_type = fct_get_record_post_type();
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

	/** Sub-Actions ***********************************************************/

	/**
	 * Setup record's page mode load hooks
	 *
	 * @since 0.0.8
	 *
	 * @uses fct_admin_is_new_records()
	 * @uses fct_admin_is_edit_records()
	 * @uses fct_admin_is_view_records()
	 * @uses do_action() Calls 'fct_admin_load_new_records'
	 * @uses do_action() Calls 'fct_admin_load_edit_records'
	 * @uses do_action() Calls 'fct_admin_load_view_records'
	 */
	public function page_mode_actions() {
		if ( $this->bail() )
			return;

		// Fire load hook for new records mode
		if ( fct_admin_is_new_records() ) {
			do_action( 'fct_admin_load_new_records' );

		// Fire load hook for edit records mode
		} elseif ( fct_admin_is_edit_records() ) {
			do_action( 'fct_admin_load_edit_records' );

		// Fire load hook for view records mode
		} elseif ( fct_admin_is_view_records() ) {
			do_action( 'fct_admin_load_view_records' );
		}
	}

	/** Contextual Help *******************************************************/

	/**
	 * Contextual help for Fiscaat record edit page
	 *
	 * @uses get_current_screen()
	 */
	public function edit_help() {
		if ( $this->bail() ) 
			return;

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
			'<p>' . __( '<a href="http://fiscaat.org/periods/" target="_blank">Fiscaat Support Periods</a>', 'fiscaat' ) . '</p>'
		);
	}

	/**
	 * Contextual help for Fiscaat record edit page
	 *
	 * @uses get_current_screen()
	 */
	public function new_help() {

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
					'<li>' . __( '<strong>Period</strong> dropdown determines the parent period that the record belongs to. Select the period, or leave the default (Use Period of Account) to post the record in period of the account.', 'fiscaat' ) . '</li>' .
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
			'<p>' . __( '<a href="http://fiscaat.org/periods/" target="_blank">Fiscaat Support Periods</a>', 'fiscaat' ) . '</p>'
		);
	}

	/** Record Meta ***********************************************************/

	/**
	 * Add the record attributes metabox
	 *
	 * @uses fct_get_record_post_type() To get the record post type
	 * @uses add_meta_box() To add the metabox
	 * @uses do_action() Calls 'fct_record_attributes_metabox'
	 */
	public function record_attributes_metabox() {
		if ( $this->bail() ) 
			return;

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
		if ( $this->bail() ) 
			return $record_id;

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
		if ( empty( $_POST['fct_record_metabox'] ) || ! wp_verify_nonce( $_POST['fct_record_metabox'], 'fct_record_metabox_save' ) )
			return $record_id;

		// Current user cannot edit this record
		if ( ! current_user_can( 'edit_record', $record_id ) )
			return $record_id;

		// Get the record meta post values
		$account_id = ! empty( $_POST['parent_id'] ) ? (int) $_POST['parent_id'] : 0;
		$period_id  = ! empty( $_POST['fct_record_period_id'] ) ? (int) $_POST['fct_record_period_id'] : fct_get_account_period_id( $account_id );

		// Formally update the record
		fct_update_record( array( 
			'record_id'      => $record_id, 
			'account_id'     => $account_id,
			'period_id'      => $period_id,
			'amount'         => ! empty( $_POST['fct_record_amount'] )         ? $_POST['fct_record_amount']         : 0,
			'record_type'    => ! empty( $_POST['fct_record_type'] )           ? $_POST['fct_record_type']           : '',
			'offset_account' => ! empty( $_POST['fct_record_offset_account'] ) ? $_POST['fct_record_offset_account'] : '',

			// @todo Move to Control
			'status'         => ! empty( $_POST['fct_record_status'] )         ? $_POST['fct_record_status']         : '',
			'is_edit'        => (bool) isset( $_POST['save'] ),
		) );

		// Allow other fun things to happen
		do_action( 'fct_record_attributes_metabox_save', $record_id, $account_id, $period_id );

		return $record_id;
	}

	/** Styles ****************************************************************/

	/**
	 * Add some general styling to the admin area
	 *
	 * @uses fct_get_period_post_type() To get the period post type
	 * @uses fct_get_account_post_type() To get the account post type
	 * @uses fct_get_record_post_type() To get the record post type
	 * @uses sanitize_html_class() To sanitize the classes
	 * @uses do_action() Calls 'fct_admin_head'
	 */
	public function admin_head() {
		if ( $this->bail() ) 
			return; 

		// Determine records mode
		$mode = fct_admin_get_records_mode(); ?>

		<style type="text/css" media="screen">
		/*<![CDATA[*/

			strong.label {
				display: inline-block;
				width: 60px;
			}

			.column-author,
			.column-fct_record_author,
			.column-fct_account_author {
				width: 10%;
			}

			.column-fct_record_post_date,
			.column-fct_record_date {
				width: 106px;
			}

			.column-fct_record_description {
				min-width: 15%;
			}

			.column-fct_record_offset_account {
				width: 15%;
			}

			.column-fct_record_account {
				width: 20%;
			}

			.column-fct_record_account_ledger_id,
			.column-fct_record_period {
				width: 10%;
			}

			.column-fct_record_amount {
				width: 157px;
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

			.widefat .column-fct_record_amount input.small-text {
				text-align: right;
				width: 65px;
			}

			.widefat .records-start-row .column-fct_record_description,
			.widefat .records-end-row   .column-fct_record_description,
			.widefat .records-total-row .column-fct_record_description {
				vertical-align: middle;
			}

			.fct_record_dates {
				float: left;
				margin-right: 6px;
			}

			/* WP core style */
			input[type="date"] {
				border: 1px solid #ddd;
				-webkit-box-shadow: inset 0 1px 2px rgba(0,0,0,.07);
				box-shadow: inset 0 1px 2px rgba(0,0,0,.07);
				background-color: #fff;
				color: #333;
				-webkit-transition: .05s border-color ease-in-out;
				transition: .05s border-color ease-in-out;
				padding: 3px 5px 2px;
				max-width: 140px; /* at least in Chrome */
			}

			/** Edit / New Records Mode ***************************************/

			.widefat .iedit select {
				width: 100%;
				max-width: 173px;
			}

			.widefat .iedit textarea,
			.widefat .iedit input {
				width: 100%;
				padding: 3px 5px;
				height: 28px;
			}

			.widefat .new-records-row  td.column-fct_record_description,
			.widefat .edit-records-row td.column-fct_record_description {
				padding: 9px 10px 4px;
			}

		/*]]>*/
		</style>

		<script type="text/javascript">

			/* Connect primary account id and ledger id dropdowns */
			jQuery(document).ready(function($) {
				var dropdowns = [ 
					$('select#fct_account_ledger_id, select#fct_record_account_ledger_id'), // Account ledger dropdowns
					$('select#fct_account_id, select#parent_id') // Account dropdowns
				];

				// Make dropdowns listen to their co-dropdown
				$.each( dropdowns, function( i ){
					var other_dd = ( i == 1 ) ? 0 : 1;

					// For each change in a dropdown of the one kind, change the 
					// matching dropdown of the other kind.
					$.each( this, function( j ) {
						$(this).change( function(){
							$( dropdowns[other_dd][j] ).find('option[value="'+ this.value +'"]').attr('selected', true );
						});
					});
				});
			});
		</script>

		<?php
	}

	/** List Table ************************************************************/

	/**
	 * Manage the column headers for the records page
	 *
	 * @param array $columns The columns
	 * @return array $columns Fiscaat record columns
	 */
	public function records_column_headers( $columns ) {
		if ( $this->bail() ) 
			return $columns;

		// Account records pages do not need account details
		if ( isset( $_GET['fct_account_id'] ) && ! empty( $_GET['fct_account_id'] ) ) {
			unset( $columns['fct_record_account_ledger_id'], $columns['fct_record_account'] );
		}

		// Only unspecified period queries require period column
		if ( ! isset( $_GET['fct_period_id'] ) || ! empty( $_GET['fct_period_id'] ) ) {
			unset( $columns['fct_record_period'] );
		}

		return $columns;
	}

	/**
	 * Determine the correct queried records's parents
	 *
	 * When querying records by the filter dropdowns, make the
	 * year leading in determining which account's records to
	 * select and update $_REQUEST global accordingly.
	 * 
	 * @since 0.0.9
	 *
	 * @uses fct_get_account_period_id() To get the account's period id
	 * @uses fct_get_account_ledger_id() To get the account's ledger id
	 * @uses fct_get_account_id_by_ledger_id() To get the period's account id
	 */
	public function records_parents() {

		// Get filter vars
		$period_id  = ! empty( $_REQUEST['fct_period_id']  )        ? (int) $_REQUEST['fct_period_id']         : isset( $_REQUEST['fct_period_id'] );
		$account_id = ! empty( $_REQUEST['fct_account_id'] )        ? (int) $_REQUEST['fct_account_id']        : 0;
		$ledger_id  = ! empty( $_REQUEST['fct_account_ledger_id'] ) ? (int) $_REQUEST['fct_account_ledger_id'] : 0;

		// Ledger id was set, account id not, so find out
		if ( empty( $account_id ) && ! empty( $ledger_id ) && is_numeric( $period_id ) ) {
			$account_id = $_REQUEST['fct_account_id'] = fct_get_account_id_by_ledger_id( $ledger_id, $period_id );
		}

		// The account's period does not match the queried period
		if ( is_numeric( $period_id ) && fct_get_account_period_id( $account_id ) != $period_id ) {

			// Get the account's ledger id
			$ledger_id  = fct_get_account_ledger_id( $account_id );

			// Find the period's queried account id
			$account_id = fct_get_account_id_by_ledger_id( $ledger_id, $period_id );

			// And set it as the correct account id
			$_REQUEST['fct_account_id'] = ! empty( $account_id ) ? $account_id : null;

		// No period explicated
		} elseif ( ! $period_id && ! empty( $account_id ) ) {

			// Set period from queried account
			$_REQUEST['fct_period_id'] = fct_get_account_period_id( $_REQUEST['fct_account_id'] );
		}
	}

	/**
	 * Add period dropdown to account and record list table filters
	 *
	 * @uses fct_get_record_post_type() To get the record post type
	 * @uses fct_get_account_post_type() To get the account post type
	 * @uses fct_period_dropdown() To generate a period dropdown
	 * @uses fct_account_ledger_dropdown() To generate an account ledger dropdown
	 * @uses fct_account_dropdown() To generate an account dropdown
	 * @return bool False. If post type is not account or record
	 */
	public function filter_dropdown() {
		global $wp_list_table;

		if ( $this->bail() ) 
			return;

		// Record created dropdown
		$wp_list_table->months_dropdown( fct_get_record_post_type() );

		// Get queried period id
		$period_id = ! empty( $_REQUEST['fct_period_id'] ) ? (int) $_REQUEST['fct_period_id'] : fct_get_current_period_id();

		// Show the periods dropdown
		fct_period_dropdown( array(
			'selected'  => $period_id,
		) );
		
		// Get which account is selected. With account id or ledger id
		$account_id = ! empty( $_REQUEST['fct_account_id'] )        ? (int) $_REQUEST['fct_account_id']        : 0;
		$ledger_id  = ! empty( $_REQUEST['fct_account_ledger_id'] ) ? (int) $_REQUEST['fct_account_ledger_id'] : 0;

		// Ledger id was set, account id not
		if ( ! empty( $ledger_id ) && empty( $account_id ) ) {
			$account_id = fct_get_account_id_by_ledger_id( $ledger_id, $period_id );
		}

		// Show the ledger dropdown
		fct_account_ledger_dropdown( array(
			'selected'    => $account_id,
			'post_parent' => $period_id,
		) );

		// Show the accounts dropdown
		fct_account_dropdown( array(
			'selected'    => $account_id,
			'post_parent' => $period_id,
		) );

		// Get the queried dates
		$date_from = ! empty( $_REQUEST['fct_date_from'] ) ? $_REQUEST['fct_date_from'] : '';
		$date_to   = ! empty( $_REQUEST['fct_date_to']   ) ? $_REQUEST['fct_date_to']   : '';

		/* translators: 1: Select records start date field, 2: Select records end date field */
		printf( '<span class="fct_record_dates">' . __( 'From %1$s to %2$s', 'fiscaat' ) . '</span>',
			"<input type=\"date\" name=\"fct_date_from\" class=\"fct_record_date\" value=\"{$date_from}\" placeholder=\"" . _x( 'yyyy-mm-dd', 'input date format', 'fiscaat' ) . "\" />",
			"<input type=\"date\" name=\"fct_date_to\"   class=\"fct_record_date\" value=\"{$date_to}\"   placeholder=\"" . _x( 'yyyy-mm-dd', 'input date format', 'fiscaat' ) . "\" />" 
		);
	}

	/**
	 * Adjust the request query and include the period id
	 *
	 * @param array $query_vars Query variables from {@link WP_Query}
	 * @uses is_admin() To check if it's the admin section
	 * @uses fct_get_account_post_type() To get the account post type
	 * @uses fct_get_record_post_type() To get the record post type
	 * @return array Processed Query Vars
	 */
	public function filter_post_rows( $query_vars ) {
		if ( $this->bail() ) 
			return $query_vars;

		// Setup meta query
		$meta_query = isset( $query_vars['meta_query'] ) ? $query_vars['meta_query'] : array();

		/** Period & Account ****************************************************/

		// Set the period id
		$meta_query[] = array(
			'key'   => '_fct_period_id',
			'value' => ! empty( $_REQUEST['fct_period_id'] ) ? (int) $_REQUEST['fct_period_id'] : fct_get_current_period_id()
		);
		
		// Set the parent if given
		if ( isset( $_REQUEST['fct_account_id'] ) ) {
			$query_vars['post_parent'] = $_REQUEST['fct_account_id'];

		// Set the parent from ledger_id if given
		} elseif ( isset( $_REQUEST['fct_account_ledger_id'] ) ) {
			$query_vars['post_parent'] = $_REQUEST['fct_account_ledger_id'];
		}

		/** Approval **********************************************************/

		// @todo Move to Control. Use 'unapproved' in request 
		//        post_status to set query vars here
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

		/** Dates *************************************************************/

		// @todo Needs testing
		// Handle dates
		if ( isset( $_REQUEST['fct_date_from'] ) || isset( $_REQUEST['fct_date_to'] ) ) {

			// Handle start date
			if ( isset( $_REQUEST['fct_date_from'] ) && false !== ( $strdate = strtotime( str_replace( '/', '-', $_REQUEST['fct_date_from'] ) ) ) ) {

				// Push one day to include selected date
				$strdate -= DAY_IN_SECONDS;

				// Collect after this date
				$meta_query[] = array(
					'key'     => '_fct_record_date',
					'value'   => date( 'Y-m-d 23:59:59', $strdate ),
					'compare' => '>',
				);
			}

			// Handle end date
			if ( isset( $_REQUEST['fct_date_to'] ) && false !== ( $strdate = strtotime( str_replace( '/', '-', $_REQUEST['fct_date_to'] ) ) ) ) {

				// Push one day to include selected date
				$strdate += DAY_IN_SECONDS;

				// Collect before this date
				$meta_query[] = array(
					'key'     => '_fct_record_date',
					'value'   => date( 'Y-m-d 00:00:00', $strdate ),
					'compare' => '<',
				);
			}
		}

		/** Sorting ***********************************************************/

		// Handle sorting
		if ( isset( $_REQUEST['orderby'] ) ) {

			// Check order type
			switch ( $_REQUEST['orderby'] ) {

				// Record date. Reverse order
				case 'record_date' :
					$query_vars['meta_key'] = '_fct_record_date';
					$query_vars['orderby']  = 'meta_value';
					$query_vars['order']    = isset( $_REQUEST['order'] ) && 'DESC' == strtoupper( $_REQUEST['order'] ) ? 'ASC' : 'DESC';
					break;

				// @todo Fix ordering by account/ledger id. Goes
				//        beyond setting meta_key query var.

				// Record account ledger id.
				// Order by parent's _fct_ledger_id meta key
				// case 'record_account_ledger_id' :
				// 	$query_vars['meta_key'] = '_fct_account_id';
				// 	$query_vars['orderby']  = 'meta_value_num';
				// 	break;

				// Record offset account
				case 'record_offset_acount' :
					$query_vars['meta_key'] = '_fct_offset_account';
					$query_vars['orderby']  = 'meta_value';
					break;

				// Record value
				case 'record_amount' :
					$query_vars['meta_key'] = '_fct_amount';
					$query_vars['orderby']  = 'meta_value_num';
					break;
			}

			// Default sorting order
			if ( ! isset( $query_vars['order'] ) ) {
				$query_vars['order'] = isset( $_REQUEST['order'] ) ? strtoupper( $_REQUEST['order'] ) : 'ASC';
			}
		}

		// Set meta query
		$query_vars['meta_query'] = $meta_query;

		// Return manipulated query_vars
		return $query_vars;
	}

	/** Post Actions **********************************************************/

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
		if ( $this->bail() ) 
			return $actions;

		// Record view links to account
		$actions['view'] = '<a href="' . fct_get_record_url( $record->ID ) . '" title="' . esc_attr( sprintf( __( 'View &#8220;%s&#8221;', 'fiscaat' ), fct_get_record_title( $record->ID ) ) ) . '" rel="permalink">' . __( 'View', 'fiscaat' ) . '</a>';

		// User cannot view records in trash
		if ( ( fct_get_trash_status_id() == $record->post_status ) && !current_user_can( 'view_trash' ) )
			unset( $actions['view'] );

		// Only show the actions if the user is capable of viewing them and record is open
		// @todo Move to Control
		if ( current_user_can( 'control', $record->ID ) ) {
			if ( fct_record_is_open( $record->ID ) ) {
				$approval_uri  = esc_url( wp_nonce_url( add_query_arg( array( 'record_id' => $record->ID, 'action' => 'fct_toggle_record_approval' ), remove_query_arg( array( 'fct_record_toggle_notice', 'record_id', 'failed', 'super' ) ) ), 'approval-record_'  . $record->ID ) );
				if ( ! fct_is_record_approved( $record->ID ) ) {
					$actions['approval'] = '<a href="' . $approval_uri . '" title="' . esc_attr__( 'Mark this record as approved',    'fiscaat' ) . '">' . __( 'Approve',    'fiscaat' ) . '</a>';
				} elseif( ! fct_is_record_declined( $record->ID ) ) {
					$actions['approval'] = '<a href="' . $approval_uri . '" title="' . esc_attr__( 'Mark this record as declined', 'fiscaat' ) . '">' . __( 'Decline', 'fiscaat' ) . '</a>';
				}
			}
		}

		// Trash
		if ( current_user_can( 'delete_record', $record->ID ) ) {
			if ( fct_get_trash_status_id() == $record->post_status ) {
				$post_type_object = get_post_type_object( fct_get_record_post_type() );
				$actions['untrash'] = "<a title='" . esc_attr__( 'Restore this item from the Trash', 'fiscaat' ) . "' href='" . add_query_arg( array( '_wp_http_referer' => add_query_arg( array( 'page' => 'fct-records' ), admin_url( 'admin.php' ) ) ), wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $record->ID ) ), 'untrash-post_' . $record->ID ) ) . "'>" . __( 'Restore', 'fiscaat' ) . "</a>";
			} elseif ( EMPTY_TRASH_DAYS ) {
				$actions['trash'] = "<a class='submitdelete' title='" . esc_attr__( 'Move this item to the Trash', 'fiscaat' ) . "' href='" . add_query_arg( array( '_wp_http_referer' => add_query_arg( array( 'page' => 'fct-records' ), admin_url( 'admin.php' ) ) ), get_delete_post_link( $record->ID ) ) . "'>" . __( 'Trash', 'fiscaat' ) . "</a>";
			}

			if ( fct_get_trash_status_id() == $record->post_status || !EMPTY_TRASH_DAYS ) {
				$actions['delete'] = "<a class='submitdelete' title='" . esc_attr__( 'Delete this item permanently', 'fiscaat' ) . "' href='" . add_query_arg( array( '_wp_http_referer' => add_query_arg( array( 'page' => 'fct-records' ), admin_url( 'admin.php' ) ) ), get_delete_post_link( $record->ID, '', true ) ) . "'>" . __( 'Delete Permanently', 'fiscaat' ) . "</a>";
			} elseif ( fct_get_spam_status_id() == $record->post_status ) {
				unset( $actions['trash'] );
			}
		}

		return $actions;
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
	 * @uses fct_decline_record() To unmark the record as declined
	 * @uses fct_approve_record() To mark the record as approved
	 * @uses do_action() Calls 'fct_toggle_record_admin' with success, post
	 *                    data, action and message
	 * @uses add_query_arg() To add custom args to the url
	 * @uses wp_safe_redirect() Redirect the page to custom url
	 */
	public function toggle_record() {

		// Only proceed if GET is a record toggle action
		if ( 'GET' == $_SERVER['REQUEST_METHOD'] && ! empty( $_GET['action'] ) && in_array( $_GET['action'], array( 'fct_toggle_record_spam' ) ) && ! empty( $_GET['record_id'] ) ) {
			$action    = $_GET['action'];             // What action is taking place?
			$record_id = (int) $_GET['record_id'];    // What's the record id?
			$success   = false;                       // Flag
			$post_data = array( 'ID' => $record_id ); // Prelim array

			// Get record and die if empty
			$record = fct_get_record( $record_id );
			if ( empty( $record ) ) // Which record?
				wp_die( __( 'The record was not found!', 'fiscaat' ) );

			if ( ! current_user_can( 'edit_record', $record->ID ) ) // What is the user doing here?
				wp_die( __( 'You do not have the permission to do that!', 'fiscaat' ) );

			switch ( $action ) {
				case 'fct_toggle_record_approval' :
					check_admin_referer( 'approval-record_' . $record_id );

					$approve = fct_is_record_approved( $record_id );
					$message = $approve ? 'declined' : 'approved';
					$success = $approve ? fct_decline_record( $record_id ) : fct_approve_record( $record_id );

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
	 * @uses fct_get_record() To get the record
	 * @uses fct_get_record_title() To get the record title of the record
	 * @uses esc_html() To sanitize the record title
	 * @uses apply_filters() Calls 'fct_toggle_record_notice_admin' with
	 *                        message, record id, notice and is it a failure
	 *
	 * @todo Move to Control
	 */
	public function toggle_record_notice() {
		if ( $this->bail() ) 
			return;

		// Only proceed if GET is a record toggle action
		if ( 'GET' == $_SERVER['REQUEST_METHOD'] && ! empty( $_GET['fct_record_toggle_notice'] ) && in_array( $_GET['fct_record_toggle_notice'], array( 'spammed', 'unspammed' ) ) && ! empty( $_GET['record_id'] ) ) {
			$notice     = $_GET['fct_record_toggle_notice'];         // Which notice?
			$record_id  = (int) $_GET['record_id'];                  // What's the record id?
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

	/** Missing ***************************************************************/

	/**
	 * Redirect user to records page when missing an open period or account
	 *
	 * @since 0.0.9
	 *
	 * @uses fct_has_open_period()
	 * @uses fct_has_open_account()
	 * @uses add_query_arg()
	 * @uses wp_safe_redirect()
	 */
	public function missing_redirect() {
		if ( $this->bail() ) 
			return;

		// Bail if not in new or edit records mode
		if ( fct_admin_is_view_records() )
			return;

		// Fiscaat has no open period or open account
		if ( ! fct_has_open_period() || ! fct_has_open_account() ) {

			// Redirect to records page
			wp_safe_redirect( add_query_arg( array( 'page' => 'fct-records' ), admin_url( 'admin.php' ) ) );
			exit;
		}
	}

	/**
	 * Display missing notice
	 *
	 * @since 0.0.9
	 *
	 * @uses fct_has_open_period()
	 * @uses fct_has_open_account()
	 * @uses current_user_can()
	 * @uses add_query_arg()
	 * @uses fct_get_period_post_type()
	 */
	public function missing_notices() {
		if ( $this->bail() ) 
			return;

		// Fiscaat has no open period
		if ( ! fct_has_open_period() && current_user_can( 'create_periods' ) ) : ?>

			<div id="message" class="error">
				<p style="line-height: 150%"><?php printf( __( 'There is currently no open period to manage records in. <a href="%s">Create a new period</a>.', 'fiscaat' ), add_query_arg( 'post_type', fct_get_period_post_type(), admin_url( 'post-new.php' ) ) ); ?></p>
			</div>

		<?php

		// Fiscaat has no open account
		elseif ( ! fct_has_open_account() && current_user_can( 'create_accounts' ) ) : ?>

			<div id="message" class="error">
				<p style="line-height: 150%"><?php printf( __( 'There is currently no open account to manage records in. <a href="%s">Open an existing account</a> or <a href="%s">create a new account</a>.', 'fiscaat' ), add_query_arg( 'page', 'fct-accounts', admin_url( 'admin.php' ) ), add_query_arg( 'post_type', fct_get_account_post_type(), admin_url( 'post-new.php' ) ) ); ?></p>
			</div>

		<?php endif;
	}

	/** Messages **************************************************************/

	/**
	 * Custom user feedback messages for record post type
	 *
	 * @uses fct_get_account_permalink()
	 * @uses wp_post_revision_title()
	 * @uses esc_url()
	 * @uses add_query_arg()
	 * @global int $post_ID
	 * 
	 * @param array $messages
	 * @return array
	 */
	public function updated_messages( $messages ) {
		global $post_ID;

		if ( $this->bail() ) 
			return $messages;

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

			// Require a period
			11 => sprintf( __( 'Using Fiscaat requires an open period to register records in. <a href="%s">Create a period first</a>.', 'fiscaat' ), esc_url( add_query_arg( 'post_type', fct_get_period_post_type(), admin_url( 'post-new.php' ) ) ) ),

			// Require an account
			12 => sprintf( __( 'Using Fiscaat requires the current period to have an account to assign records to. <a href="%s">Create an account first</a>.', 'fiscaat' ), esc_url( add_query_arg( 'post_type', fct_get_account_post_type(), admin_url( 'post-new.php' ) ) ) ),

			// Can not use post-new.php
			13 => sprintf( __( 'Don\'t know how you got here, but records are added otherwise. <a href="%s">Lead me there</a>.', 'fiscaat' ), esc_url( add_query_arg( array( 'post_type' => fct_get_account_post_type(), 'page' => 'new' ), admin_url( 'edit.php' ) ) ) ),
		);

		return $messages;
	}

	/** Redirect **************************************************************/

	/**
	 * Redirect from post-new.php to Fiscaat's own new records page
	 *
	 * @since 0.0.8
	 *
	 * @uses fct_admin_get_new_records_mode()
	 * @uses fct_get_post_type_type()
	 * @uses wp_redirect()
	 */
	public function redirect_post_new_page() {
		if ( $this->bail() )
			return;

		// Setup local var
		$args = array( 'page' => 'fct-records' );

		// Send to new records mode
		if ( current_user_can( 'create_posts' ) ) {
			$args['mode'] = fct_admin_get_new_records_mode();
		}

		// Redirect to 
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}

	/** Page Title ************************************************************/

	/**
	 * Manipulate the records posts page title
	 *
	 * @uses fct_get_account_id()
	 * @uses fct_get_account_ledger_id()
	 * @uses fct_get_account_title() To get the account title
	 * @uses fct_get_period_id()
	 * @uses fct_get_period_title() To get the account title
	 * @param string $title Page title
	 * @return string Page title
	 */
	public function records_page_title( $title ) {

		// Account records
		if ( isset( $_REQUEST['fct_account_id'] ) && ! empty( $_REQUEST['fct_account_id'] ) ) {

			// Fetch account id
			$account_id = fct_get_account_id( $_REQUEST['fct_account_id'] );

			if ( ! empty( $account_id ) ) {
				// Format: {account number}. {account title}
				$title = fct_get_account_ledger_id( $account_id ) . '. ' . fct_get_account_title( $account_id );
			}
		}

		// Period records
		if ( isset( $_REQUEST['fct_period_id'] ) && ! empty( $_REQUEST['fct_period_id'] ) ) {

			// Fetch period id
			$period_id = fct_get_period_id( $_REQUEST['fct_period_id'] );

			if ( ! empty( $period_id ) ) {
				// Format: {title} -- {period title}
				$title .= ' &mdash; ' . fct_get_period_title( $period_id );
			}
		}

		// New records
		if ( fct_admin_is_new_records() ) {
			$title = __( 'New Records', 'fiscaat' ) . ' &mdash; ' . fct_get_period_title( fct_get_current_period_id() );
		}

		return $title;
	}

	/**
	 * Append post-new link to page title
	 *
	 * @since 0.0.8
	 *
	 * @uses fct_has_open_account()
	 * @uses fct_has_open_period()
	 * @uses fct_admin_page_title_add_new()
	 * @param string $title Page title
	 * @return string Page title
	 */
	public function post_new_link( $title ) {

		// Require open period and account, as long as we're not already there
		if ( fct_has_open_period() && fct_has_open_account() && ! fct_admin_is_new_records() ) {
			$title = fct_admin_page_title_add_new( $title );
		}

		return $title;
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
