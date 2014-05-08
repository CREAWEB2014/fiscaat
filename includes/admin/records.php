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
	 * @uses fct_get_year_post_type() To get the year post type
	 * @uses fct_get_account_post_type() To get the account post type
	 * @uses fct_get_record_post_type() To get the record post type
	 */
	private function setup_actions() {

		/** Sub-Actions *******************************************************/

		add_action( 'load-edit.php', array( $this, 'setup_new_posts' ) );

		/** Actions ***********************************************************/

		// Add some general styling to the admin area
		add_action( 'fct_admin_head',    array( $this, 'admin_head'   ) );

		// Record metabox actions
		add_action( 'add_meta_boxes',    array( $this, 'record_attributes_metabox'      ) );
		add_action( 'save_post',         array( $this, 'record_attributes_metabox_save' ) );

		// Check if there are any fct_toggle_record_* requests on admin_init, also have a message displayed
		add_action( 'fct_admin_load_edit_records',  array( $this, 'toggle_record'         ) );
		add_action( 'fct_admin_notices',            array( $this, 'toggle_record_notice'  ) );

		// Contextual Help
		add_action( 'fct_admin_load_edit_records',  array( $this, 'edit_help'             ) );
		add_action( 'fct_admin_load_new_records',   array( $this, 'new_help'              ) );

		// Modify page title
		add_action( 'fct_admin_records_page_title', array( $this, 'records_page_title'    ) );
		add_action( 'fct_admin_records_page_title', array( $this, 'post_new_link'         ) );

		/** Redirect **********************************************************/

		add_action( 'load-post-new.php', array( $this, 'redirect_post_new_page' ), 0 );

		/** Filters ***********************************************************/

		// Messages
		add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );

		// Record columns (in post row)
		add_filter( 'fct_admin_records_get_columns', array( $this, 'records_column_headers' )        );
		add_filter( 'post_row_actions',              array( $this, 'records_row_actions'    ), 10, 2 );

		// Add ability to filter accounts and records per year
		add_filter( 'restrict_manage_posts', array( $this, 'filter_dropdown'             )        );
		add_filter( 'fct_request',           array( $this, 'filter_post_rows'            )        );
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
	 * Setup new posts page hook
	 *
	 * @since 0.0.8
	 *
	 * @uses add_action()
	 */
	public function setup_new_posts() {
		if ( $this->bail() || ! fct_admin_is_new_records() )
			return;

		// Setup load-new.php hook for new records mode
		add_action( current_filter(), 'fct_admin_load_new_records' );
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
			'<p>' . __( '<a href="http://fiscaat.org/years/" target="_blank">Fiscaat Support Years</a>', 'fiscaat' ) . '</p>'
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

	/** Records Meta **********************************************************/

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
		$account_id = ! empty( $_POST['parent_id'] )          ? (int) $_POST['parent_id'] : 0;
		$year_id    = ! empty( $_POST['fct_record_year_id'] ) ? (int) $_POST['fct_record_year_id'] : fct_get_account_year_id( $account_id );

		// Formally update the record
		fct_update_record( array( 
			'record_id'      => $record_id, 
			'account_id'     => $account_id,
			'year_id'        => $year_id,
			'amount'         => ! empty( $_POST['fct_record_amount'] )         ? $_POST['fct_record_amount']         : 0,
			'record_type'    => ! empty( $_POST['fct_record_type'] )           ? $_POST['fct_record_type']           : '',
			'offset_account' => ! empty( $_POST['fct_record_offset_account'] ) ? $_POST['fct_record_offset_account'] : '',

			// @todo Move to Control
			'status'         => ! empty( $_POST['fct_record_status'] )         ? $_POST['fct_record_status']         : '',
		) );

		// Allow other fun things to happen
		do_action( 'fct_record_attributes_metabox_save', $record_id, $account_id, $year_id );

		return $record_id;
	}

	/** Styles ****************************************************************/

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
				width: 100px;
			}

			.column-fct_record_offset_account {
				width: 15%;
			}

			.column-fct_record_account {
				width: 20%;
			}

			.column-fct_record_account_ledger_id,
			.column-fct_record_year {
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

				.fct_record_status_icon.status_<?php echo fct_get_closed_status_id(); ?> {
					background-color: #999;
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

			/** Edit / New Records Mode ***************************************/

			.widefat .new-records-row  select,
			.widefat .edit-records-row select {
				width: 100%;
				max-width: 173px;
			}

			.widefat .new-records-row  textarea,
			.widefat .edit-records-row textarea,
			.widefat .new-records-row  input,
			.widefat .edit-records-row input {
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

		<?php
	}

	/** Records List Table ****************************************************/

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
		if ( isset( $_GET['fct_account_id'] ) && ! empty( $_GET['fct_account_id'] ) )
			unset( $columns['fct_record_account_ledger_id'], $columns['fct_record_account'] );

		return $columns;
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
		if ( $this->bail() ) 
			return;

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
		if ( $this->bail() ) 
			return $query_vars;

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

		// @todo Move to Control
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

				// Record account
				// Order by parent's title
				// case 'record_account' :
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

	/** Record Actions ********************************************************/

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

		unset( $actions['inline hide-if-no-js'] );

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
	 * @since Fiscaat (r2740)
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

	/** Record Messages *******************************************************/

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

			// Require a year
			11 => sprintf( __( 'Using Fiscaat requires an open year to register records in. <a href="%s">Create a year first</a>.', 'fiscaat' ), esc_url( add_query_arg( 'post_type', fct_get_year_post_type(), admin_url( 'post-new.php' ) ) ) ),

			// Require an account
			12 => sprintf( __( 'Using Fiscaat requires the current year to have an account to assign records to. <a href="%s">Create an account first</a>.', 'fiscaat' ), esc_url( add_query_arg( 'post_type', fct_get_account_post_type(), admin_url( 'post-new.php' ) ) ) ),

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

		$args = array( 
			'page' => 'fct-records', 
			'mode' => fct_admin_get_new_records_mode() 
		);
		wp_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}

	/** Page Title ************************************************************/

	/**
	 * Manipulate the records posts page title
	 *
	 * @uses fct_get_account_id()
	 * @uses fct_get_account_ledger_id()
	 * @uses fct_get_account_title() To get the account title
	 * @uses fct_get_year_id()
	 * @uses fct_get_year_title() To get the account title
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
				$title = fct_get_account_ledger_id( $account_id ) .'. '. fct_get_account_title( $account_id );
			}
		}

		// Year records
		if ( isset( $_REQUEST['fct_year_id'] ) && ! empty( $_REQUEST['fct_year_id'] ) ) {
			
			// Fetch year id
			$year_id = fct_get_year_id( $_REQUEST['fct_year_id'] );

			if ( ! empty( $year_id ) ) {
				// Format: {title} -- {year title}
				$title .= ' &mdash; '. fct_get_year_title( $year_id );
			}
		}

		// New records
		if ( fct_admin_is_new_records() ) {
			$title = get_post_type_object( fct_get_record_post_type() )->labels->add_new;
		}

		return $title;
	}

	/**
	 * Append post-new link to page title
	 *
	 * @since 0.0.8
	 *
	 * @uses fct_has_open_account()
	 * @uses fct_has_open_year()
	 * @uses fct_admin_page_title_add_new()
	 * @param string $title Page title
	 * @return string Page title
	 */
	public function post_new_link( $title ) {

		// Require open year and account, as long as we're not already there
		if ( fct_has_open_year() && fct_has_open_account() && ! fct_admin_is_new_records() ) {
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
