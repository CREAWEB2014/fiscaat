<?php

/**
 * Fiscaat Records Admin Class
 *
 * @package Fiscaat
 * @subpackage Administration
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

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

		add_action( 'fct_admin_load_records',  array( $this, 'page_mode_actions' ) );

		/** Actions ***********************************************************/

		// Set records's parents earlier
		add_action( 'fct_admin_load_records',  array( $this, 'setup_request_args' ), 0 );

		// Insert Records
		add_action( 'fct_admin_load_records',  array( $this, 'bulk_insert_records' ), 9 ); // Before fct_admin_setup_list_table
		add_action( 'fct_admin_notices',       array( $this, 'bulk_insert_notices' ) );

		// Add some general styling to the admin area
		add_action( 'fct_admin_head', array( $this, 'admin_head' ) );

		// Record metabox actions
		add_action( 'add_meta_boxes', array( $this, 'record_attributes_metabox'      ) );
		add_action( 'save_post',      array( $this, 'record_attributes_metabox_save' ) );

		// Check if there are any fct_toggle_record_* requests on admin_init, also have a message displayed
		add_action( 'fct_admin_load_edit_records',  array( $this, 'toggle_record'        ) );
		add_action( 'fct_admin_notices',            array( $this, 'toggle_record_notice' ) );

		// Contextual Help
		add_action( 'fct_admin_load_view_records',  array( $this, 'edit_help' ) );
		add_action( 'fct_admin_load_edit_records',  array( $this, 'edit_help' ) );
		add_action( 'fct_admin_load_new_records',   array( $this, 'new_help'  ) );

		// Check if there is a missing open period or account on record add/edit, also have a message displayed
		add_action( 'fct_admin_load_edit_records',  array( $this, 'missing_redirect' ) );
		add_action( 'fct_admin_load_new_records',   array( $this, 'missing_redirect' ) );
		add_action( 'fct_admin_notices',            array( $this, 'missing_notices'  ) );

		// Add ability to filter accounts and records per period
		add_action( 'fct_admin_load_records', array( $this, 'search_records'  ) );
		add_action( 'restrict_manage_posts',  array( $this, 'filter_dropdown' ) );

		// Modify page heading
		add_action( 'fct_admin_records_page', array( $this, 'page_description' ), 9 );

		/** Redirect **********************************************************/

		add_action( 'fct_admin_load_post_record', array( $this, 'redirect_post_new_page' ), 0 );

		/** Filters ***********************************************************/

		// Admin body class
		add_filter( 'fct_admin_body_class',  array( $this, 'admin_body_class' ) );

		// Messages
		add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );

		// Add ability to filter accounts and records per period
		add_filter( 'fct_request',           array( $this, 'filter_post_rows' ) );

		// Record columns (in post row)
		add_filter( 'fct_admin_records_get_columns', array( $this, 'records_column_headers' )        );

		// Modify page title
		add_filter( 'fct_admin_records_page_title', array( $this, 'records_page_title' ) );
		add_filter( 'fct_admin_records_page_title', array( $this, 'post_new_link'      ) );
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
		if ( ! isset( $_POST['fct_record_metabox'] ) || ! wp_verify_nonce( $_POST['fct_record_metabox'], 'fct_record_metabox_save' ) )
			return $record_id;

		// Current user cannot edit this record
		if ( ! current_user_can( 'edit_record', $record_id ) )
			return $record_id;

		// Get the record meta post values
		$account_id = ! empty( $_POST['parent_id'] ) ? (int) $_POST['parent_id'] : 0;
		$period_id  = ! empty( $_POST['period_id'] ) ? (int) $_POST['period_id'] : fct_get_account_period_id( $account_id );

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

		// Get Fiscaat
		$fct = fiscaat();

		// Desktop only
		if ( ! wp_is_mobile() ) {
			wp_enqueue_script( 'fct-table-scroll', $fct->admin->admin_url . 'js/table-scroll.js', array( 'jquery' ), $fct->version, 1 );
		}

		wp_enqueue_script( 'fct-records', $fct->admin->admin_url . 'js/records.js', array( 'jquery', 'jquery-ui-datepicker' ), $fct->version, 1 );
		wp_localize_script( 'fct-records', '_fctRecordsL10n', array(
			'settings' => array(
				'currencyFormat' => fct_the_currency_format()
			)
		) );

		// Fetch jQuery UI styles from Google CDN
		wp_enqueue_style( 'jquery-ui', '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/themes/smoothness/jquery-ui.css' );
		wp_enqueue_style( 'jquery-ui-datepicker', $fct->admin->admin_url . 'css/jquery-ui-datepicker.css' );

		// Get record modes
		$view = fct_admin_get_view_records_mode();
		$new  = fct_admin_get_new_records_mode();
		$edit = fct_admin_get_edit_records_mode(); ?>

		<style type="text/css" media="screen">
		/*<![CDATA[*/

			.tablenav .alignleft > * {
				float: left;
			}

			.tablenav .alignleft input[type="search"],
			.fct_record_dates {
				margin-right: 6px;
			}

				.fct_record_dates > i {
					line-height: 1.4em;
					cursor: pointer;
				}

				.fct_record_dates input {
					width: 93px;
				}

			strong.label {
				display: inline-block;
				width: 60px;
			}

			.<?php echo $view; ?>-records .widefat tr > td,
			.widefat .total-records > td {
				padding: 0 10px;
				line-height: 46px;
			}

			.widefat .column-author,
			.widefat .column-fct_record_author,
			.widefat .column-fct_account_author {
				width: 10%;
			}

			.<?php echo $view; ?>-records .widefat .column-fct_record_post_date,
			.<?php echo $view; ?>-records .widefat .column-fct_record_date {
				width: 93px;
			}

			.<?php echo $new;  ?>-records .widefat .column-fct_record_post_date,
			.<?php echo $edit; ?>-records .widefat .column-fct_record_post_date,
			.<?php echo $new;  ?>-records .widefat .column-fct_record_date,
			.<?php echo $edit; ?>-records .widefat .column-fct_record_date {
				width: 85px;
			}

			.widefat .column-fct_record_description {
				min-width: 15%;
			}

			.widefat .column-fct_record_offset_account {
				width: 15%;
			}

			.widefat .column-fct_record_account {
				width: 20%;
				max-width: 193px;
			}

			.widefat .column-fct_record_account_ledger_id {
				width: 35px;
			}

			.widefat .column-fct_record_period {
				width: 10%;
			}

			.widefat .column-fct_record_amount {
				width: 138px;
			}

				.widefat .column-fct_record_amount.sortable,
				.widefat .column-fct_record_amount.sorted {
					width: 158px;
				}

			.widefat .column-fct_record_status {
				width: 45px;
				text-align: center;
			}

				.widefat .column-fct_record_status.sortable,
				.widefat .column-fct_record_status.sorted {
					width: 67px;
				}

			.status-closed {
				background-color: #eaeaea;
			}

			.widefat .total-records > * {
				border-top: 1px solid #e1e1e1;
			}

				.widefat .total-records .column-fct_record_description:after {
					content: " (" counter(row_count) ")"; /* append total row count to Total */
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

				.<?php echo $new; ?>-records .widefat .iedit select.fct_record_ledger_id {
					width: 70px;
					margin-right: 9px;
				}

				.<?php echo $new; ?>-records .widefat .iedit select.fct_record_account_id {
					width: calc( 100% - 79px);
				}

			.widefat .iedit textarea,
			.widefat .iedit input[type="text"],
			.widefat .iedit input[type="number"],
			.widefat .iedit input[type="date"] {
				width: 100%;
				padding: 3px 5px;
				height: 28px;
			}

				.widefat .column-fct_record_amount input.small-text {
					text-align: right;
					width: 65px;
				}

			.<?php echo $new;  ?>-records .widefat .record td.column-fct_record_description,
			.<?php echo $edit; ?>-records .widefat .record td.column-fct_record_description {
				padding: 9px 10px 4px;
			}

			.widefat.records {
				counter-reset: row_count;
			}

				.widefat.records .record {
					counter-increment: row_count;
				}

					.<?php echo $new;  ?>-records .widefat .check-column,
					.<?php echo $edit; ?>-records .widefat .check-column {
						width: 26px;
						text-align: right;
					}

						.<?php echo $new;  ?>-records .widefat .check-column input,
						.<?php echo $edit; ?>-records .widefat .check-column input {
							display: none;
						}

						.<?php echo $new;  ?>-records .widefat .record .check-column:before,
						.<?php echo $edit; ?>-records .widefat .record .check-column:before {
							content: counter(row_count) ".";
						}

			.widefat #fct-total-records.mismatch {
				background: #ff5959;
			}

				.widefat #fct-total-records.mismatch td {
					color: #f9f9f9;
				}

				.widefat #fct-total-records .column-fct_record_offset_account input {
					float: right;
					margin: 10px 0 10px 10px;
				}

			/** Table scrolling ***********************************************/

			.fct-table-scroll #table-top-container, 
			.fct-table-scroll #table-bottom-container {
				box-shadow: none;
				border-top: none;
				border-bottom: none;
			}

			.fct-table-scroll #table-top-container {
				display: none;
				z-index: 999;
			}

			.fct-table-scroll #table-bottom-container {
				z-index: 1;
			}

			.fct-table-scroll thead,
			.fct-table-scroll tbody tr:last-child,
			.fct-table-scroll tfoot {
				background: #fff;
			}

		/*]]>*/
		</style>

		<?php
	}

	/**
	 * Add custom classes to the Fiscaat admin body class
	 *
	 * @since 0.0.9
	 * 
	 * @param array $classes Fiscaat admin body classes
	 * @return array Fiscaat admin body classes
	 */
	public function admin_body_class( $classes ) {
		if ( $this->bail() )
			return $classes;

		// The records mode
		$classes[] = fct_admin_get_records_mode() . '-records';

		return $classes;
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
		if ( isset( $_REQUEST['account_id'] ) && ! empty( $_REQUEST['account_id'] ) ) {
			unset( $columns['fct_record_account_ledger_id'], $columns['fct_record_account'] );
		}

		// Only unspecified period queries require period column
		if ( ! isset( $_REQUEST['period_id'] ) || ! empty( $_REQUEST['period_id'] ) ) {
			unset( $columns['fct_record_period'] );
		}

		return $columns;
	}

	/**
	 * Determine the correct queried records' parents
	 *
	 * When querying records by the filter dropdowns, make the
	 * period leading in determining which account's records to
	 * select and update $_REQUEST global accordingly.
	 * 
	 * @since 0.0.9
	 *
	 * @uses fct_get_account_period_id() To get the account's period id
	 * @uses fct_get_account_ledger_id() To get the account's ledger id
	 * @uses fct_get_account_id_by_ledger_id() To get the period's account id
	 */
	public function setup_request_args() {

		// Bail if not viewing records
		if ( ! fct_admin_is_view_records() )
			return;

		// Get filter vars
		$period_id  = ! empty( $_REQUEST['period_id']  ) ? (int) $_REQUEST['period_id']  : isset( $_REQUEST['period_id'] );
		$account_id = ! empty( $_REQUEST['account_id'] ) ? (int) $_REQUEST['account_id'] : 0;
		$ledger_id  = ! empty( $_REQUEST['ledger_id']  ) ? (int) $_REQUEST['ledger_id']  : 0;

		// Setup period
		if ( ! $period_id ) {

			// By account id
			if ( ! empty( $account_id ) ) {
				$period_id = fct_get_account_period_id( $account_id );

			// Default to current period
			} else {
				$period_id = fct_get_current_period_id();
			}

			$_REQUEST['period_id'] = $_GET['period_id'] = $period_id;
		}

		// Setup account
		if ( empty( $account_id ) && ! empty( $ledger_id ) ) {
			$account_id = fct_get_account_id_by_ledger_id( $ledger_id, $period_id );
		}

		// Correct account for requested year
		if ( fct_get_account_period_id( $account_id ) != $period_id ) {

			// Get the account's ledger id
			$ledger_id  = fct_get_account_ledger_id( $account_id );

			// Find the period's queried account id
			$account_id = fct_get_account_id_by_ledger_id( $ledger_id, $period_id );
		}

		// Set the correct account id
		$_REQUEST['account_id'] = $_GET['account_id'] = ! empty( $account_id ) ? $account_id : null;
	}

	/**
	 * Replace search filter for record list tables
	 *
	 * @since 0.0.9
	 *
	 * @uses remove_action()
	 * @uses add_action()
	 */
	public function search_records() {
		if ( $this->bail() )
			return;

		// Views. Remove default views
		remove_action( 'fct_admin_records_page', 'fct_admin_list_table_search_box', 8 );
		add_action( 'restrict_manage_posts', 'fct_admin_list_table_search_box', 8 );
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
		$period_id = ! empty( $_REQUEST['period_id'] ) ? (int) $_REQUEST['period_id'] : fct_get_current_period_id();

		// Show the periods dropdown
		fct_period_dropdown( array(
			'select_id'   => 'fct_period_id_filter',
			'select_name' => 'period_id',
			'selected'    => $period_id,
			'show_none'   => false
		) );
		
		// Get which account is selected. With account id or ledger id
		$account_id = ! empty( $_REQUEST['account_id'] ) ? (int) $_REQUEST['account_id'] : 0;
		$ledger_id  = ! empty( $_REQUEST['ledger_id']  ) ? (int) $_REQUEST['ledger_id']  : 0;

		// Account id is not set, but ledger id is
		if ( empty( $account_id ) && ! empty( $ledger_id ) ) {
			$account_id = fct_get_account_id_by_ledger_id( $ledger_id, $period_id );
		// Ledger id is not set, but account id is
		} else if ( empty( $ledger_id ) && ! empty( $account_id ) ) {
			$ledger_id = fct_get_account_ledger_id( $account_id );
		}

		// Show the ledger dropdown
		fct_ledger_dropdown( array(
			'select_id'   => 'fct_ledger_id_filter',
			'select_name' => 'ledger_id',
			'selected'    => $ledger_id,
			'post_parent' => $period_id,
		) );

		// Show the accounts dropdown
		fct_account_dropdown( array(
			'select_id'   => 'fct_account_id_filter',
			'select_name' => 'account_id',
			'selected'    => $account_id,
			'post_parent' => $period_id,
		) );

		// Show the queried record dates
		/* translators: 1: Select records start date field, 2: Select records end date field */
		printf( '<span class="fct_record_dates"><i class="dashicons dashicons-calendar-alt"></i> ' . _x( '%1$s - %2$s', 'Start date to end date', 'fiscaat' ) . '</span>', 
			sprintf( '<input id="fct_record_date_from" type="text" name="date_from" class="fct_record_date datepicker" value="%1$s" placeholder="%2$s" />',
				! empty( $_REQUEST['date_from'] ) ? $_REQUEST['date_from'] : '', // Date value
				_x( 'dd-mm-yyyy', 'date input field format', 'fiscaat' )         // Date placeholder
			), 
			sprintf( '<input id="fct_record_date_to" type="text" name="date_to" class="fct_record_date datepicker" value="%1$s" placeholder="%2$s" />',
				! empty( $_REQUEST['date_to'] ) ? $_REQUEST['date_to'] : '', // Date value
				_x( 'dd-mm-yyyy', 'date input field format', 'fiscaat' )     // Date placeholder
		) );
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
		$period_id  = ! empty( $_REQUEST['period_id'] ) ? (int) $_REQUEST['period_id'] : fct_get_current_period_id();

		/** Period & Account ****************************************************/

		// Set the period id. Default to the current period
		$meta_query[] = array(
			'key'   => '_fct_period_id',
			'value' => $period_id
		);
		
		// Set the parent if given
		if ( ! empty( $_REQUEST['account_id'] ) ) {
			$query_vars['post_parent'] = $_REQUEST['account_id'];

		// Set the parent from ledger_id if given
		} elseif ( ! empty( $_REQUEST['ledger_id'] ) ) {
			$query_vars['post_parent'] = fct_get_account_id_by_ledger_id( $_REQUEST['ledger_id'], $period_id );
		}

		/** Dates *************************************************************/

		// @todo Needs testing and fixing. Probably better not used with WP_Meta_Query
		//        http://stackoverflow.com/questions/1861489/converting-a-date-in-mysql-from-string-field
		//        http://stackoverflow.com/questions/2758486/mysql-compare-date-string-with-string-from-datetime-field
		// Handle dates
		if ( ! empty( $_REQUEST['date_from'] ) || ! empty( $_REQUEST['date_to'] ) ) {

			// Handle valid start date
			if ( ! empty( $_REQUEST['date_from'] ) && false !== ( $strdate = strtotime( str_replace( '/', '-', $_REQUEST['date_from'] ) ) ) ) {

				// Subtract one day to include selected date
				$strdate -= DAY_IN_SECONDS;

				// Collect after this date
				$meta_query[] = array(
					'key'     => '_fct_record_date',
					'value'   => date( 'Y-m-d 23:59:59', $strdate ),
					'compare' => '>',
				);
			}

			// Handle valid end date
			if ( ! empty( $_REQUEST['date_to'] ) && false !== ( $strdate = strtotime( str_replace( '/', '-', $_REQUEST['date_to'] ) ) ) ) {

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
		if ( ! empty( $_REQUEST['orderby'] ) ) {

			// Check order type
			switch ( $_REQUEST['orderby'] ) {

				// Record date. Reverse order
				case 'record_date' :
					$query_vars['meta_key'] = '_fct_record_date';
					$query_vars['orderby']  = 'meta_value';
					$query_vars['order']    = ! empty( $_REQUEST['order'] ) && 'DESC' == strtoupper( $_REQUEST['order'] ) ? 'ASC' : 'DESC';
					break;

				// @todo Fix ordering by account/ledger id. Goes
				//        beyond setting meta_key query var.

				// Record account ledger id.
				// Order by parent's _fct_ledger_id meta key
				// case 'ledger_id' :
				// 	$query_vars['meta_key'] = '_fct_account_id';
				// 	$query_vars['orderby']  = 'meta_value_num';
				// 	break;

				// Record offset account
				case 'offset_acount' :
					$query_vars['meta_key'] = '_fct_offset_account';
					$query_vars['orderby']  = 'meta_value';
					break;

				// Record value
				case 'amount' :
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
		return apply_filters( 'fct_admin_records_request', $query_vars );
	}

	/** Insert Records ********************************************************/

	/**
	 * Process the records to be bulk inserted
	 *
	 * @since 0.0.9
	 * 
	 * @uses wp_verify_nonce()
	 * @uses current_user_can()
	 * @uses fct_transform_records_input()
	 * @uses fct_bulk_insert_records()
	 * @uses wp_redirect()
	 */
	public function bulk_insert_records() {

		// Bail when nonce does not check
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-insert-records' ) )
			return;

		// Bail when user is not capable
		if ( ! current_user_can( 'create_records' ) || ! current_user_can( 'edit_records' ) )
			return;

		// Get the records input data
		$data = fct_transform_records_input( 'records' );

		// Fetch records, transform them and insert/update 'em
		$record_ids = fct_bulk_insert_records( $data );

		// Something went wrong
		if ( fct_has_errors() ) {

			// Overwrite the request global
			if ( ! empty( $data ) ) {
				$_REQUEST[ 'records' ] = $data;
			}

			/**
			 * Do not redirect the page after this point to ensure the $_REQUEST
			 * data and errors are properly reported on the rendered page. This
			 * global var is used in {@link fct_admin_setup_list_table()} to redirect.
			 */
			unset( $_REQUEST['_wp_http_referer'] );

		// Redirect when successful
		} elseif ( ! empty( $record_ids ) ) {
			$args = array( 'records' => count( $record_ids ) );
			if ( fct_admin_is_edit_records() ) 
				$args['edited'] = 1;
			wp_redirect( add_query_arg( $args, wp_get_referer() ) );
			exit;
		}
	}

	/**
	 * Display notices from the bulk insert records response
	 *
	 * @since 0.0.9
	 */
	public function bulk_insert_notices() {
		if ( $this->bail() ) 
			return;

		// Only proceed if GET is a record toggle action
		if ( 'GET' == $_SERVER['REQUEST_METHOD'] && ! empty( $_REQUEST['records'] ) ) {
			$records = (int) $_REQUEST['records'];     // How many records?
			$is_edit = ! empty( $_REQUEST['edited'] ); // Were they edited?

			// Empty?
			if ( empty( $records ) )
				return;

			// Edited?
			if ( ! $is_edit ) {
				$message = sprintf( __( '%d new records were created.', 'fiscaat' ), $records );
			} else {
				$message = sprintf( __( '%d records were edited.', 'fiscaat' ), $records );
			} ?>

			<div id="message" class="updated fade">
				<p style="line-height: 150%"><?php echo $message; ?></p>
			</div>

			<?php
		}
	}

	/** Post Actions **********************************************************/

	/**
	 * Toggle record
	 *
	 * Handles the admin-side of toggling records
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
		if ( 'GET' == $_SERVER['REQUEST_METHOD'] && ! empty( $_REQUEST['action'] ) && false !== strpos( $_REQUEST['action'], 'fct_toggle_record_' ) && ! empty( $_REQUEST['record_id'] ) ) {
			$action    = $_REQUEST['action'];             // What action is taking place?
			$record_id = (int) $_REQUEST['record_id'];    // What's the record id?
			$success   = false;                       // Flag
			$post_data = array( 'ID' => $record_id ); // Prelim array
			$result    = array( false, '' );          // Default result

			// Get record and die if empty
			$record = fct_get_record( $record_id );
			if ( empty( $record ) ) // Which record?
				wp_die( __( 'The record was not found!', 'fiscaat' ) );

			/**
			 * Perform toggle action and return the result
			 *
			 * Filters should run their own capability checks.
			 * 
			 * @since 0.0.9
			 *
			 * @param array $result {
			 *   @var bool $success Whether the toggle action was successful
			 *   @var string $message Toggle notice name
			 * }
			 * @param string $action Toggle action name
			 * @param int $record_id Record ID
			 */
			list( $success, $message ) = apply_filters( 'fct_toggle_record', $result, $action, $record->ID );
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
	 */
	public function toggle_record_notice() {
		if ( $this->bail() ) 
			return;

		// Only proceed if GET is a record toggle action
		if ( 'GET' == $_SERVER['REQUEST_METHOD'] && ! empty( $_REQUEST['fct_record_toggle_notice'] ) && in_array( $_REQUEST['fct_record_toggle_notice'], array( 'spammed', 'unspammed' ) ) && ! empty( $_REQUEST['record_id'] ) ) {
			$notice     = $_REQUEST['fct_record_toggle_notice'];         // Which notice?
			$record_id  = (int) $_REQUEST['record_id'];   // What's the record id?
			$is_failure = ! empty( $_REQUEST['failed'] ); // Was that a failure?

			// Empty? No record?
			if ( empty( $notice ) || empty( $record_id ) )
				return;

			// Get record and bail if empty
			$record = fct_get_record( $record_id );
			if ( empty( $record ) )
				return;

			// Do additional record toggle notice filters (admin side)
			$message = apply_filters( 'fct_toggle_record_notice_admin', '', $record->ID, $notice, $is_failure );

			if ( ! empty( $message ) ) : ?>

			<div id="message" class="<?php echo $is_failure == true ? 'error' : 'updated'; ?> fade">
				<p style="line-height: 150%"><?php echo $message; ?></p>
			</div>

			<?php endif;
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
			5 => isset( $_REQUEST['revision'] )
					? sprintf( __( 'Record restored to revision from %s', 'fiscaat' ), wp_post_revision_title( (int) $_REQUEST['revision'], false ) )
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
			13 => sprintf( __( 'Don\'t know how you got here, but records are added otherwise. <a href="%s">Lead me there</a>.', 'fiscaat' ), esc_url( add_query_arg( array( 'page' => 'fct-records', 'mode' => fct_admin_get_new_records_mode() ), admin_url( 'admin.php' ) ) ) ),
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
	 * @uses fct_get_object_type_by_post_type()
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

	/** Page Heading **********************************************************/

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
		if ( ! empty( $_REQUEST['account_id'] ) ) {

			// Fetch account id
			$account_id = fct_get_account_id( $_REQUEST['account_id'] );

			if ( ! empty( $account_id ) ) {
				// Format: {account number}. {account title}
				$title = fct_get_account_ledger_id( $account_id ) . '. ' . fct_get_account_title( $account_id );
			}
		}

		// Period records
		if ( ! empty( $_REQUEST['period_id'] ) ) {

			// Fetch period id
			$period_id = fct_get_period_id( $_REQUEST['period_id'] );

			if ( ! empty( $period_id ) ) {
				// Format: {title} -- {period title}
				$title .= ' &mdash; ' . fct_get_period_title( $period_id );
			}
		}

		// New records
		if ( fct_admin_is_new_records() ) {
			$title = __( 'New Records', 'fiscaat' );
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
	 * @uses fct_admin_page_title_get_add_new_link()
	 * @param string $title Page title
	 * @return string Page title
	 */
	public function post_new_link( $title ) {

		// Require open period and account, as long as we're not already there
		if ( fct_has_open_period() && fct_has_open_account() && ! fct_admin_is_new_records() ) {
			$title .= fct_admin_page_title_get_add_new_link();
		}

		return $title;
	}

	/**
	 * Display the description of the current page's context
	 *
	 * @since 0.0.9
	 *
	 * @uses fct_get_account_id()
	 * @uses fct_get_account_content()
	 */
	public function page_description() {

		// Account records
		if ( ! empty( $_REQUEST['account_id'] ) ) {

			// Fetch account id
			$account_id = fct_get_account_id( $_REQUEST['account_id'] );

			if ( ! empty( $account_id ) ) {

				// Display account description
				fct_account_content( $account_id );
			}
		}
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
