<?php

/**
 * Fiscaat Period Admin Class
 *
 * @package Fiscaat
 * @subpackage Administration
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Fiscaat_Periods_Admin' ) ) :
/**
 * Loads Fiscaat periods admin area
 *
 * @package Fiscaat
 * @subpackage Administration
 */
class Fiscaat_Periods_Admin {

	/** Variables *************************************************************/

	/**
	 * @var The post type of this admin component
	 */
	private $post_type = '';

	/** Functions *************************************************************/

	/**
	 * The main Fiscaat periods admin loader
	 *
	 * @uses Fiscaat_Periods_Admin::setup_globals() Setup the globals needed
	 * @uses Fiscaat_Periods_Admin::setup_actions() Setup the hooks and actions
	 * @uses Fiscaat_Periods_Admin::setup_help() Setup the help text
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

		/** Actions ***********************************************************/

		// Add some general styling to the admin area
		add_action( 'fct_admin_head', array( $this, 'admin_head' ) );

		// Metabox actions
		add_action( 'add_meta_boxes', array( $this, 'attributes_metabox'      ) );
		add_action( 'save_post',      array( $this, 'attributes_metabox_save' ) );

		// Check if there are any fct_toggle_period_* requests on admin_init, also have a message displayed
		add_action( 'fct_admin_load_edit_periods',  array( $this, 'toggle_period'        ) );
		add_action( 'fct_admin_notices',            array( $this, 'toggle_period_notice' ) );

		// Contextual Help
		add_action( 'fct_admin_load_edit_periods',  array( $this, 'edit_help' ) );
		add_action( 'fct_admin_load_post_period',   array( $this, 'new_help'  ) );

		// Post stati
		add_action( 'fct_admin_load_edit_periods',  array( $this, 'arrange_post_statuses' ) );

		// Page title
		add_action( 'fct_admin_periods_page_title', array( $this, 'post_new_link' ) );

		/** Filters ***********************************************************/

		// Filter periods
		add_filter( 'fct_request',           array( $this, 'filter_post_rows' ) );

		// Messages
		add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );

		// Columns (in post row)
		add_filter( 'display_post_states', array( $this, 'periods_post_states' ), 10, 2 );
		add_filter( 'post_row_actions',    array( $this, 'periods_row_actions' ), 10, 2 );
	}

	/**
	 * Setup default admin class globals
	 *
	 * @access private
	 */
	private function setup_globals() {
		$this->post_type = fct_get_period_post_type();
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

	/** Contextual Help *******************************************************/

	/**
	 * Contextual help for Fiscaat period edit page
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
				'<p>' . __( 'This screen displays the individual periods on your site. You can customize the display of this screen to suit your workflow.', 'fiscaat' ) . '</p>'
		) );

		// Screen Content
		get_current_screen()->add_help_tab( array(
			'id'		=> 'screen-content',
			'title'		=> __( 'Screen Content', 'fiscaat' ),
			'content'	=>
				'<p>' . __( 'You can customize the display of this screen&#8217;s contents in a number of ways:', 'fiscaat' ) . '</p>' .
				'<ul>' .
					'<li>' . __( 'You can hide/display columns based on your needs and decide how many periods to list per screen using the Screen Options tab.',                                                                                                                                'fiscaat' ) . '</li>' .
					'<li>' . __( 'You can filter the list of periods by period status using the text links in the upper left to show All, Published, or Trashed periods. The default view is to show all periods.',                                                                                 'fiscaat' ) . '</li>' .
					'<li>' . __( 'You can refine the list to show only periods from a specific month by using the dropdown menus above the periods list. Click the Filter button after making your selection. You also can refine the list by clicking on the period creator in the periods list.', 'fiscaat' ) . '</li>' .
				'</ul>'
		) );

		// Available Actions
		get_current_screen()->add_help_tab( array(
			'id'		=> 'action-links',
			'title'		=> __( 'Available Actions', 'fiscaat' ),
			'content'	=>
				'<p>' . __( 'Hovering over a row in the periods list will display action links that allow you to manage your period. You can perform the following actions:', 'fiscaat' ) . '</p>' .
				'<ul>' .
					'<li>' . __( '<strong>Edit</strong> takes you to the editing screen for that period. You can also reach that screen by clicking on the period title.',                                                                              'fiscaat' ) . '</li>' .
					'<li>' . __( '<strong>Trash</strong> removes your period from this list and places it in the trash, from which you can permanently delete it.',                                                                                    'fiscaat' ) . '</li>' .
					'<li>' . __( '<strong>View</strong> will show you what your draft period will look like if you publish it. View will take you to your live site to view the period. Which link is available depends on your period&#8217;s status.', 'fiscaat' ) . '</li>' .
				'</ul>'
		) );

		// Bulk Actions
		get_current_screen()->add_help_tab( array(
			'id'		=> 'bulk-actions',
			'title'		=> __( 'Bulk Actions', 'fiscaat' ),
			'content'	=>
				'<p>' . __( 'You can also edit or move multiple periods to the trash at once. Select the periods you want to act on using the checkboxes, then select the action you want to take from the Bulk Actions menu and click Apply.',           'fiscaat' ) . '</p>' .
				'<p>' . __( 'When using Bulk Edit, you can change the metadata (categories, author, etc.) for all selected periods at once. To remove a period from the grouping, just click the x next to its name in the Bulk Edit area that appears.', 'fiscaat' ) . '</p>'
		) );

		// Help Sidebar
		get_current_screen()->set_help_sidebar(
			'<p><strong>' . __( 'For more information:', 'fiscaat' ) . '</strong></p>' .
			'<p>' . __( '<a href="http://codex.fiscaat.org" target="_blank">Fiscaat Documentation</a>',    'fiscaat' ) . '</p>' .
			'<p>' . __( '<a href="http://fiscaat.org/periods/" target="_blank">Fiscaat Support Periods</a>', 'fiscaat' ) . '</p>'
		);
	}

	/**
	 * Contextual help for Fiscaat period edit page
	 *
	 * @since Fiscaat (r3119)
	 * @uses get_current_screen()
	 */
	public function new_help() {
		if ( $this->bail() ) 
			return;

		$customize_display = '<p>' . __( 'The title field and the big period editing Area are fixed in place, but you can reposition all the other boxes using drag and drop, and can minimize or expand them by clicking the title bar of each box. Use the Screen Options tab to unhide more boxes (Excerpt, Send Trackbacks, Custom Fields, Discussion, Slug, Author) or to choose a 1- or 2-column layout for this screen.', 'fiscaat' ) . '</p>';

		get_current_screen()->add_help_tab( array(
			'id'      => 'customize-display',
			'title'   => __( 'Customizing This Display', 'fiscaat' ),
			'content' => $customize_display,
		) );

		get_current_screen()->add_help_tab( array(
			'id'      => 'title-period-editor',
			'title'   => __( 'Title and Period Editor', 'fiscaat' ),
			'content' =>
				'<p>' . __( '<strong>Title</strong> - Enter a title for your period. After you enter a title, you&#8217;ll see the permalink below, which you can edit.', 'fiscaat' ) . '</p>' .
				'<p>' . __( '<strong>Period Editor</strong> - Enter the text for your period. There are two modes of editing: Visual and HTML. Choose the mode by clicking on the appropriate tab. Visual mode gives you a WYSIWYG editor. Click the last icon in the row to get a second row of controls. The HTML mode allows you to enter raw HTML along with your period text. You can insert media files by clicking the icons above the period editor and following the directions. You can go to the distraction-free writing screen via the Fullscreen icon in Visual mode (second to last in the top row) or the Fullscreen button in HTML mode (last in the row). Once there, you can make buttons visible by hovering over the top area. Exit Fullscreen back to the regular period editor.', 'fiscaat' ) . '</p>'
		) );

		$publish_box = '<p>' . __( '<strong>Publish</strong> - You can set the terms of publishing your period in the Publish box. For Status, Visibility, and Publish (immediately), click on the Edit link to reveal more options. Visibility includes options for password-protecting a period or making it stay at the top of your blog indefinitely (sticky). Publish (immediately) allows you to set a future or past date and time, so you can schedule a period to be published in the future or backdate a period.', 'fiscaat' ) . '</p>';

		if ( current_theme_supports( 'period-formats' ) && period_type_supports( 'period', 'period-formats' ) ) {
			$publish_box .= '<p>' . __( '<strong>period Format</strong> - This designates how your theme will display a specific period. For example, you could have a <em>standard</em> blog period with a title and paragraphs, or a short <em>aside</em> that omits the title and contains a short text blurb. Please refer to the Codex for <a href="http://codex.wordpress.org/Post_Formats#Supported_Formats">descriptions of each period format</a>. Your theme could enable all or some of 10 possible formats.', 'fiscaat' ) . '</p>';
		}

		if ( current_theme_supports( 'period-thumbnails' ) && period_type_supports( 'period', 'thumbnail' ) ) {
			$publish_box .= '<p>' . __( '<strong>Featured Image</strong> - This allows you to associate an image with your period without inserting it. This is usually useful only if your theme makes use of the featured image as a period thumbnail on the home page, a custom header, etc.', 'fiscaat' ) . '</p>';
		}

		get_current_screen()->add_help_tab( array(
			'id'      => 'period-attributes',
			'title'   => __( 'Period Attributes', 'fiscaat' ),
			'content' =>
				'<p>' . __( 'Select the attributes that your period should have:', 'fiscaat' ) . '</p>' .
				'<ul>' .
					'<li>' . __( '<strong>Type</strong> indicates if the period is a category or period. Categories generally contain other periods.',                                                                                'fiscaat' ) . '</li>' .
					'<li>' . __( '<strong>Status</strong> allows you to close a period to new accounts and periods.',                                                                                                                  'fiscaat' ) . '</li>' .
					'<li>' . __( '<strong>Visibility</strong> lets you pick the scope of each period and what users are allowed to access it.',                                                                                     'fiscaat' ) . '</li>' .
					'<li>' . __( '<strong>Parent</strong> dropdown determines the parent period. Select the period or category from the dropdown, or leave the default (No Parent) to create the period at the root of your periods.', 'fiscaat' ) . '</li>' .
					'<li>' . __( '<strong>Order</strong> allows you to order your periods numerically.',                                                                                                                            'fiscaat' ) . '</li>' .
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
				'<p>' . __( '<strong>Discussion</strong> - You can turn comments and pings on or off, and if there are comments on the period, you can see them here and moderate them.', 'fiscaat' ) . '</p>'
		) );

		get_current_screen()->set_help_sidebar(
			'<p><strong>' . __( 'For more information:', 'fiscaat' ) . '</strong></p>' .
			'<p>' . __( '<a href="http://codex.fiscaat.org" target="_blank">Fiscaat Documentation</a>',    'fiscaat' ) . '</p>' .
			'<p>' . __( '<a href="http://fiscaat.org/periods/" target="_blank">Fiscaat Support Periods</a>', 'fiscaat' ) . '</p>'
		);
	}

	/** Period Meta *************************************************************/

	/**
	 * Add the period attributes metabox
	 *
	 * @uses fct_get_period_post_type() To get the period post type
	 * @uses add_meta_box() To add the metabox
	 * @uses do_action() Calls 'fct_period_attributes_metabox'
	 */
	public function attributes_metabox() {
		if ( $this->bail() ) 
			return;

		add_meta_box (
			'fct_period_attributes',
			__( 'Period Attributes', 'fiscaat' ),
			'fct_period_metabox',
			$this->post_type,
			'side',
			'high'
		);

		do_action( 'fct_period_attributes_metabox' );
	}

	/**
	 * Pass the period attributes for processing
	 *
	 * @param int $period_id Period id
	 * @uses current_user_can() To check if the current user is capable of
	 *                           editing the period
	 * @uses do_action() Calls 'fct_period_attributes_metabox_save' with the
	 *                    period id
	 * @return int Period id
	 */
	public function attributes_metabox_save( $period_id ) {
		if ( $this->bail() ) 
			return $period_id;

		// Bail if doing an autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return $period_id;

		// Bail if not a post request
		if ( 'POST' != strtoupper( $_SERVER['REQUEST_METHOD'] ) )
			return $period_id;

		// Nonce check
		if ( empty( $_POST['fct_period_metabox'] ) || ! wp_verify_nonce( $_POST['fct_period_metabox'], 'fct_period_metabox_save' ) )
			return $period_id;

		// Only save for period post-types
		if ( ! fct_is_period( $period_id ) )
			return $period_id;

		// Bail if current user cannot edit this period
		if ( ! current_user_can( 'edit_period', $period_id ) )
			return $period_id;

		// Update the period meta bidness
		fct_update_period( array( 'period_id' => $period_id ) );

		do_action( 'fct_period_attributes_metabox_save', $period_id );

		return $period_id;
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
			return; ?>

		<style type="text/css" media="screen">
		/*<![CDATA[*/

			#misc-publishing-actions,
			#save-post {
				display: none;
			}

			strong.label {
				display: inline-block;
				width: 60px;
			}

			#fct_period_attributes hr {
				border-style: solid;
				border-width: 1px;
				border-color: #ccc #fff #fff #ccc;
			}

			.column-fct_period_account_count,
			.column-fct_period_record_count,
			.column-fct_account_record_count,
			.column-fct_period_end_value {
				width: 10%;
			}

			.column-author,
			.column-fct_record_author,
			.column-fct_account_author {
				width: 10%;
			}

			.column-fct_account_period,
			.column-fct_record_period,
			.column-fct_record_account {
				width: 10%;
			}

			.column-date,
			.column-fct_period_started,
			.column-fct_period_closed,
			.column-fct_record_created {
				width: 10%;
			}

			.status-closed {
				background-color: #eaeaea;
			}

		/*]]>*/
		</style>

		<?php
	}

	/** List Table ************************************************************/

	/**
	 * Adjust the request query
	 *
	 * @param array $query_vars Query variables from {@link WP_Query}
	 * @return array Processed Query Vars
	 */
	function filter_post_rows( $query_vars ) {
		if ( $this->bail() ) 
			return $query_vars;

		/** Post Status *******************************************************/

		// Query only public post statuses (no draft) by default
		if ( ! isset( $_REQUEST['post_status'] ) || empty( $_REQUEST['post_status'] ) ) {
			$query_vars['post_status'] = implode( ',', fct_get_post_stati( fct_get_period_post_type() ) );
		}

		/** Sorting ***********************************************************/

		// Handle sorting
		if ( isset( $_REQUEST['orderby'] ) ) {

			// Check order type
			switch ( $_REQUEST['orderby'] ) {

				// Period closed date. Reverse order
				case 'period_closed' :
					$query_vars['meta_key'] = '_fct_closed';
					$query_vars['orderby']  = 'meta_value';
					$query_vars['order']   = isset( $_REQUEST['order'] ) && 'DESC' == strtoupper( $_REQUEST['order'] ) ? 'ASC' : 'DESC';
					break;

				// Period account count
				case 'period_account_count' :
					$query_vars['meta_key'] = '_fct_account_type';
					$query_vars['orderby']  = 'meta_value';
					break;

				// Period record count
				case 'period_record_count' :
					$query_vars['meta_key'] = '_fct_record_count';
					$query_vars['orderby']  = 'meta_value_num';
					break;

				// Period end value
				case 'period_end_value' :
					$query_vars['meta_key'] = '_fct_end_value';
					$query_vars['orderby']  = 'meta_value_num';
					break;
			}

			// Default sorting order
			if ( ! isset( $query_vars['order'] ) ) {
				$query_vars['order'] = isset( $_REQEUEST['order'] ) ? strtoupper( $_REQEUEST['order'] ) : 'ASC';
			}
		}

		// Return manipulated query_vars
		return $query_vars;
	}

	/**
	 * Reorder and rename period post statuses
	 *
	 * Manipulates the $wp_post_statuses global to rename the publish 
	 * post status label to 'Open' to better reflect the opposite state 
	 * of 'Close'. Also moves the close post status next to publish.
	 * 
	 * @since 0.0.9
	 *
	 * @global $wp_post_statuses
	 * @uses fct_get_public_status_id()
	 * @uses fct_get_closed_status_id()
	 */
	public function arrange_post_statuses() {
		global $wp_post_statuses;

		if ( $this->bail() )
			return;

		// Loop all post status ids
		foreach ( array_keys( $wp_post_statuses ) as $status ) {

			// Check post status
			switch ( $status ) {

				// Publish
				case fct_get_public_status_id() :

					// Rename publish post status labels
					$wp_post_statuses[ fct_get_public_status_id() ]->label       = __( 'Open', 'post', 'fiscaat' );
					$wp_post_statuses[ fct_get_public_status_id() ]->label_count = _nx_noop( 'Open <span class="count">(%s)</span>', 'Open <span class="count">(%s)</span>', 'post', 'fiscaat' );

					break;

				// Draft
				case 'draft' :

					// Remove from admin all list and show in admin status list conditionally
					$wp_post_statuses['draft']->show_in_admin_all_list    = false;
					$wp_post_statuses['draft']->show_in_admin_status_list = current_user_can( 'edit_periods' );

					break;

				// Closed
				case fct_get_closed_status_id() :

					// Get close post status
					$close_status = $wp_post_statuses[ fct_get_closed_status_id() ];

					// Remove post status from array
					unset( $wp_post_statuses[ fct_get_closed_status_id() ] );

					// Insert post status in position
					array_splice( $wp_post_statuses, array_search( fct_get_public_status_id(), array_keys( $wp_post_statuses ) ) + 1, 0, array( 
						fct_get_closed_status_id() => $close_status
					) );

					break;
			}
		}
	}

	/**
	 * Define post states that are appended to the post title
	 *
	 * @since 0.0.9
	 *
	 * @uses fct_is_period_closed()
	 * @uses fct_get_closed_status_id()
	 * 
	 * @param array $post_states Post states
	 * @param object $period Period post data
	 * @return array Post states
	 */
	public function periods_post_states( $post_states, $period ) {
		if ( $this->bail() )
			return $post_states;

		// Closed post state
		if ( fct_is_period_closed( $period->ID ) ) {
			$post_states[ fct_get_closed_status_id() ] = __( 'Closed', 'fiscaat' );
		}

		return $post_states;
	}

	/** Post Actions **********************************************************/

	/**
	 * Period Row actions
	 *
	 * Add the view/accounts/records/close/open/trash/delete action links 
	 * under the period title
	 *
	 * @param array $actions Actions
	 * @param array $period Period object
	 * @uses the_content() To output period description
	 * @return array $actions Actions
	 */
	public function periods_row_actions( $actions, $period ) {
		if ( $this->bail() ) 
			return $actions;

		// Show view link if it's not set, the period is trashed and the user can view trashed accounts
		if ( empty( $actions['view'] ) && ( fct_get_trash_status_id() != $period->post_status ) ) {
			$actions['view'] = '<a href="' . fct_get_account_permalink( $period->ID ) . '" title="' . esc_attr( sprintf( __( 'View &#8220;%s&#8221;', 'fiscaat' ), fct_get_period_title( $period->ID ) ) ) . '" rel="permalink">' . __( 'View', 'fiscaat' ) . '</a>';
		}

		// Show accounts and records link
		$actions['accounts'] = '<a href="' . add_query_arg( array( 'page' => 'fct-accounts', 'fct_period_id' => $period->ID ), admin_url( 'admin.php' ) ) .'" title="' . esc_attr( sprintf( __( 'Show all accounts of &#8220;%s&#8221;', 'fiscaat' ), fct_get_period_title( $period->ID ) ) ) . '">' . __( 'Accounts', 'fiscaat' ) . '</a>';
		$actions['records']  = '<a href="' . add_query_arg( array( 'page' => 'fct-records',  'fct_period_id' => $period->ID ), admin_url( 'admin.php' ) ) .'" title="' . esc_attr( sprintf( __( 'Show all records of &#8220;%s&#8221;',  'fiscaat' ), fct_get_period_title( $period->ID ) ) ) . '">' . __( 'Records',  'fiscaat' ) . '</a>';

		// Show the close and open link
		if ( current_user_can( 'close_period', $period->ID ) ) {
			$close_uri = esc_url( wp_nonce_url( add_query_arg( array( 'period_id' => $period->ID, 'action' => 'fct_toggle_period_close' ), remove_query_arg( array( 'fct_period_toggle_notice', 'account_id', 'failed', 'super' ) ) ), 'close-period_' . $period->ID ) );
			if ( fct_is_period_open( $period->ID ) ) {

				// Show close link if the period has no open accounts
				if ( ! fct_has_open_account() ) {
					$actions['close'] = '<a href="' . $close_uri . '" title="' . esc_attr__( 'Close this period', 'fiscaat' ) . '">' . _x( 'Close', 'Close the period', 'fiscaat' ) . '</a>';
				}
			} else {
				$actions['open'] = '<a href="' . $close_uri . '" title="' . esc_attr__( 'Open this period',  'fiscaat' ) . '">' . _x( 'Open',  'Open the period',  'fiscaat' ) . '</a>';
			}
		}

		// Only show delete links for closed or empty periods
		if ( current_user_can( 'delete_period', $period->ID ) && ( fct_is_period_closed() || ! fct_period_has_records() ) ) {
			if ( fct_get_trash_status_id() == $period->post_status ) {
				$post_type_object = get_post_type_object( fct_get_period_post_type() );
				$actions['untrash'] = "<a title='" . esc_attr__( 'Restore this item from the Trash', 'fiscaat' ) . "' href='" . add_query_arg( array( '_wp_http_referer' => add_query_arg( array( 'page' => 'fct-periods' ), admin_url( 'admin.php' ) ) ), wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $period->ID ) ), 'untrash-post_' . $period->ID ) ) . "'>" . __( 'Restore', 'fiscaat' ) . "</a>";
			} elseif ( EMPTY_TRASH_DAYS ) {
				$actions['trash'] = "<a class='submitdelete' title='" . esc_attr__( 'Move this item to the Trash', 'fiscaat' ) . "' href='" . add_query_arg( array( '_wp_http_referer' => add_query_arg( array( 'page' => 'fct-periods' ), admin_url( 'admin.php' ) ) ), get_delete_post_link( $period->ID ) ) . "'>" . __( 'Trash', 'fiscaat' ) . "</a>";
			}

			if ( fct_get_trash_status_id() == $period->post_status || ! EMPTY_TRASH_DAYS ) {
				$actions['delete'] = "<a class='submitdelete' title='" . esc_attr__( 'Delete this item permanently', 'fiscaat' ) . "' href='" . add_query_arg( array( '_wp_http_referer' => add_query_arg( array( 'page' => 'fct-periods' ), admin_url( 'admin.php' ) ) ), get_delete_post_link( $period->ID, '', true ) ) . "'>" . __( 'Delete Permanently', 'fiscaat' ) . "</a>";
			}
		}

		return $actions;
	}

	/**
	 * Toggle period
	 *
	 * Handles the admin-side opening/closing of periods
	 *
	 * @uses fct_get_period() To get the period
	 * @uses current_user_can() To check if the user is capable of editing
	 *                           the period
	 * @uses wp_die() To die if the user isn't capable or the post wasn't
	 *                 found
	 * @uses check_admin_referer() To verify the nonce and check referer
	 * @uses fct_is_period_open() To check if the period is open
	 * @uses fct_close_period() To close the period
	 * @uses fct_open_period() To open the period
	 * @uses do_action() Calls 'fct_toggle_period_admin' with success, post
	 *                    data, action and message
	 * @uses add_query_arg() To add custom args to the url
	 * @uses wp_safe_redirect() Redirect the page to custom url
	 */
	public function toggle_period() {
		if ( $this->bail() ) 
			return;

		// Only proceed if GET is an period toggle action
		if ( 'GET' == $_SERVER['REQUEST_METHOD'] && ! empty( $_GET['action'] ) && in_array( $_GET['action'], array( 'fct_toggle_period_close' ) ) && ! empty( $_GET['period_id'] ) ) {
			$action    = $_GET['action'];             // What action is taking place?
			$period_id = (int) $_GET['period_id'];    // What's the period id?
			$success   = false;                       // Flag
			$post_data = array( 'ID' => $period_id ); // Prelim array
			$period    = fct_get_period( $period_id );

			// Bail if period is missing
			if ( empty( $period ) )
				wp_die( __( 'The period was not found!', 'fiscaat' ) );

			switch ( $action ) {
				case 'fct_toggle_period_close' :
					check_admin_referer( 'close-period_' . $period_id );

					if ( ! current_user_can( 'close_period', $period->ID ) ) // What is the user doing here?
						wp_die( __( 'You do not have the permission to do that!', 'fiscaat' ) );

					$is_open = fct_is_period_open( $period_id );
					$message = true == $is_open ? 'closed' : 'opened';
					$success = true == $is_open ? fct_close_period( $period_id ) : fct_open_period( $period_id );

					break;
			}

			$message = array( 'fct_period_toggle_notice' => $message, 'period_id' => $period->ID );

			if ( false == $success || is_wp_error( $success ) )
				$message['failed'] = '1';

			// Do additional period toggle actions (admin side)
			do_action( 'fct_toggle_period_admin', $success, $post_data, $action, $message );

			// Redirect back to the period
			$redirect = add_query_arg( $message, remove_query_arg( array( 'action', 'period_id', '_wpnonce' ) ) );
			wp_safe_redirect( $redirect );

			// For good measure
			exit();
		}
	}

	/**
	 * Toggle period notices
	 *
	 * Display the success/error notices from
	 * {@link Fiscaat_Accounts_Admin::toggle_period()}
	 *
	 * @since 0.0.1
	 *
	 * @uses fct_get_period() To get the period
	 * @uses fct_get_period_title() To get the period title of the period
	 * @uses esc_html() To sanitize the period title
	 * @uses apply_filters() Calls 'fct_toggle_period_notice_admin' with
	 *                        message, period id, notice and is it a failure
	 */
	public function toggle_period_notice() {
		if ( $this->bail() ) 
			return;

		// Only proceed if GET is a period toggle action
		if ( 'GET' == $_SERVER['REQUEST_METHOD'] && ! empty( $_GET['fct_period_toggle_notice'] ) && in_array( $_GET['fct_period_toggle_notice'], array( 'opened', 'closed' ) ) && !empty( $_GET['period_id'] ) ) {
			$notice     = $_GET['fct_period_toggle_notice'];        // Which notice?
			$period_id  = (int) $_GET['period_id'];                 // What's the period id?
			$is_failure = !empty( $_GET['failed'] ) ? true : false; // Was that a failure?

			// Bais if no period_id or notice
			if ( empty( $notice ) || empty( $period_id ) )
				return;

			// Bail if period is missing
			$period = fct_get_period( $period_id );
			if ( empty( $period ) )
				return;

			$period_title = esc_html( fct_get_period_title( $period->ID ) );

			switch ( $notice ) {
				case 'opened' :
					$message = $is_failure == true ? sprintf( __( 'There was a problem opening the period "%1$s".', 'fiscaat' ), $period_title ) : sprintf( __( 'Period "%1$s" successfully opened.', 'fiscaat' ), $period_title );
					break;

				case 'closed' :
					$message = $is_failure == true ? sprintf( __( 'There was a problem closing the period "%1$s".', 'fiscaat' ), $period_title ) : sprintf( __( 'Period "%1$s" successfully closed.', 'fiscaat' ), $period_title );
					break;
			}

			// Do additional period toggle notice filters (admin side)
			$message = apply_filters( 'fct_toggle_period_notice_admin', $message, $period->ID, $notice, $is_failure );

			?>

			<div id="message" class="<?php echo $is_failure == true ? 'error' : 'updated'; ?> fade">
				<p style="line-height: 150%"><?php echo $message; ?></p>
			</div>

			<?php
		}
	}

	/** Messages **************************************************************/

	/**
	 * Custom user feedback messages for period post type
	 *
	 * @global int $post_ID
	 * @uses fct_get_period_permalink()
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

		if ( $this->bail() ) 
			return $messages;

		// URL for the current period
		$period_url = fct_get_period_permalink( $post_ID );

		// Current period's post_date
		$post_date = fct_get_global_post_field( 'post_date', 'raw' );

		// Messages array
		$messages[$this->post_type] = array(
			0 =>  '', // Left empty on purpose

			// Updated
			1 =>  sprintf( __( 'Period updated. <a href="%s">View period</a>', 'fiscaat' ), $period_url ),

			// Custom field updated
			2 => __( 'Custom field updated.', 'fiscaat' ),

			// Custom field deleted
			3 => __( 'Custom field deleted.', 'fiscaat' ),

			// Period updated
			4 => __( 'Period updated.', 'fiscaat' ),

			// Restored from revision
			// translators: %s: date and time of the revision
			5 => isset( $_GET['revision'] )
					? sprintf( __( 'Period restored to revision from %s', 'fiscaat' ), wp_post_revision_title( (int) $_GET['revision'], false ) )
					: false,

			// Period created
			6 => sprintf( __( 'Period created. <a href="%s">View period</a>', 'fiscaat' ), $period_url ),

			// Period saved
			7 => __( 'Period saved.', 'fiscaat' ),

			// Period submitted
			8 => sprintf( __( 'Period submitted. <a target="_blank" href="%s">Preview period</a>', 'fiscaat' ), esc_url( add_query_arg( 'preview', 'true', $period_url ) ) ),

			// Period scheduled
			9 => sprintf( __( 'Period scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview period</a>', 'fiscaat' ),
					// translators: Publish box date format, see http://php.net/date
					date_i18n( __( 'M j, Y @ G:i', 'fiscaat' ),
					strtotime( $post_date ) ),
					$period_url ),

			// Period draft updated
			10 => sprintf( __( 'Period draft updated. <a target="_blank" href="%s">Preview period</a>', 'fiscaat' ), esc_url( add_query_arg( 'preview', 'true', $period_url ) ) ),

		);

		return $messages;
	}

	/** Page Title ************************************************************/

	/**
	 * Append post-new link to page title
	 *
	 * @since 0.0.8
	 *
	 * @uses fct_has_open_period()
	 * @uses fct_admin_page_title_add_new()
	 * @param string $title Page title
	 * @return string Page title
	 */
	public function post_new_link( $title ) {

		// When there's no open period
		if ( ! fct_has_open_period() ) {
			$title = fct_admin_page_title_add_new( $title );
		}

		return $title;
	}
}

endif; // class_exists check

/**
 * Setup Fiscaat Periods Admin
 *
 * This is currently here to make hooking and unhooking of the admin UI easy.
 * It could use dependency injection in the future, but for now this is easier.
 *
 * @uses Fiscaat_Periods_Admin
 */
function fct_admin_periods() {
	fiscaat()->admin->periods = new Fiscaat_Periods_Admin();
}
