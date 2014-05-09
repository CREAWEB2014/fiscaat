<?php

/**
 * Fiscaat Accounts Admin Class
 *
 * @package Fiscaat
 * @subpackage Administration
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Fiscaat_Accounts_Admin' ) ) :
/**
 * Loads Fiscaat accounts admin area
 *
 * @package Fiscaat
 * @subpackage Administration
 */
class Fiscaat_Accounts_Admin {

	/** Variables *************************************************************/

	/**
	 * @var The post type of this admin component
	 */
	private $post_type = '';

	/** Functions *************************************************************/

	/**
	 * The main Fiscaat accounts admin loader
	 *
	 * @uses Fiscaat_Accounts_Admin::setup_globals() Setup the globals needed
	 * @uses Fiscaat_Accounts_Admin::setup_actions() Setup the hooks and actions
	 * @uses Fiscaat_Accounts_Admin::setup_help() Setup the help text
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
		add_action( 'fct_admin_head', array( $this, 'admin_head'              ) );

		// Account metabox actions
		add_action( 'add_meta_boxes', array( $this, 'attributes_metabox'      ) );
		add_action( 'save_post',      array( $this, 'attributes_metabox_save' ) );

		// Check if there are any fct_toggle_account_* requests on admin_init, also have a message displayed
		add_action( 'fct_admin_load_edit_accounts',  array( $this, 'toggle_account'         ) );
		add_action( 'fct_admin_notices',             array( $this, 'toggle_account_notice'  ) );

		// Contextual Help
		add_action( 'fct_admin_load_edit_accounts',  array( $this, 'edit_help'              ) );
		add_action( 'fct_admin_load_new_accounts',   array( $this, 'new_help'               ) );

		// Fiscaat requires
		add_action( 'fct_admin_load_new_accounts',   array( $this, 'no_period_redirect'       ) );
		
		// Page title
		add_action( 'fct_admin_accounts_page_title', array( $this, 'accounts_page_title'    ) );
		add_action( 'fct_admin_accounts_page_title', array( $this, 'post_new_link'          ) );

		/** Ajax **************************************************************/
		
		// Check ledger id
		add_action( 'wp_ajax_fct_check_ledger_id',   array( $this, 'check_ledger_id'        ) );

		/** Filters ***********************************************************/
		
		// Messages
		add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );

		// Account columns (in post row)
		add_filter( 'fct_admin_accounts_get_columns', array( $this, 'accounts_column_headers' )        );
		add_filter( 'post_row_actions',               array( $this, 'accounts_row_actions'    ), 10, 2 );

		// Add ability to filter accounts and records per period
		add_filter( 'restrict_manage_posts', array( $this, 'filter_dropdown'  ) );
		add_filter( 'fct_request',           array( $this, 'filter_post_rows' ) );

		// Account records view link
		// add_filter( 'get_edit_post_link', array( $this, 'accounts_edit_post_link' ), 10, 3 ); // Uncontrolled behavior
	}

	/**
	 * Setup default admin class globals
	 *
	 * @access private
	 */
	private function setup_globals() {
		$this->post_type = fct_get_account_post_type();
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
	 * Contextual help for Fiscaat account edit page
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
				'<p>' . __( 'This screen displays the individual accounts on your site. You can customize the display of this screen to suit your workflow.', 'fiscaat' ) . '</p>'
		) );

		// Screen Content
		get_current_screen()->add_help_tab( array(
			'id'		=> 'screen-content',
			'title'		=> __( 'Screen Content', 'fiscaat' ),
			'content'	=>
				'<p>' . __( 'You can customize the display of this screen&#8217;s contents in a number of ways:', 'fiscaat' ) . '</p>' .
				'<ul>' .
					'<li>' . __( 'You can hide/display columns based on your needs and decide how many accounts to list per screen using the Screen Options tab.',                                                                                                                                'fiscaat' ) . '</li>' .
					'<li>' . __( 'You can filter the list of accounts by account status using the text links in the upper left to show All, Published, or Trashed accounts. The default view is to show all accounts.',                                                                                 'fiscaat' ) . '</li>' .
					'<li>' . __( 'You can refine the list to show only accounts from a specific month by using the dropdown menus above the accounts list. Click the Filter button after making your selection. You also can refine the list by clicking on the account creator in the accounts list.', 'fiscaat' ) . '</li>' .
				'</ul>'
		) );

		// Available Actions
		get_current_screen()->add_help_tab( array(
			'id'		=> 'action-links',
			'title'		=> __( 'Available Actions', 'fiscaat' ),
			'content'	=>
				'<p>' . __( 'Hovering over a row in the accounts list will display action links that allow you to manage your account. You can perform the following actions:', 'fiscaat' ) . '</p>' .
				'<ul>' .
					'<li>' . __( '<strong>Edit</strong> takes you to the editing screen for that account. You can also reach that screen by clicking on the account title.',                                                                                 'fiscaat' ) . '</li>' .
					'<li>' . __( '<strong>Trash</strong> removes your account from this list and places it in the trash, from which you can permanently delete it.',                                                                                       'fiscaat' ) . '</li>' .
					'<li>' . __( '<strong>Spam</strong> removes your account from this list and places it in the spam queue, from which you can permanently delete it.',                                                                                   'fiscaat' ) . '</li>' .
					'<li>' . __( '<strong>Preview</strong> will show you what your draft account will look like if you publish it. View will take you to your live site to view the account. Which link is available depends on your account&#8217;s status.', 'fiscaat' ) . '</li>' .
					'<li>' . __( '<strong>Close</strong> will mark the selected account as &#8217;closed&#8217; and disable the option to post new records to the account.',                                                                                 'fiscaat' ) . '</li>' .
					'<li>' . __( '<strong>Stick</strong> will keep the selected account &#8217;pinned&#8217; to the top the parent period account list.',                                                                                                     'fiscaat' ) . '</li>' .
					'<li>' . __( '<strong>Stick <em>(to front)</em></strong> will keep the selected account &#8217;pinned&#8217; to the top of ALL periods and be visable in any periods accounts list.',                                                      'fiscaat' ) . '</li>' .
				'</ul>'
		) );

		// Bulk Actions
		get_current_screen()->add_help_tab( array(
			'id'		=> 'bulk-actions',
			'title'		=> __( 'Bulk Actions', 'fiscaat' ),
			'content'	=>
				'<p>' . __( 'You can also edit or move multiple accounts to the trash at once. Select the accounts you want to act on using the checkboxes, then select the action you want to take from the Bulk Actions menu and click Apply.',           'fiscaat' ) . '</p>' .
				'<p>' . __( 'When using Bulk Edit, you can change the metadata (categories, author, etc.) for all selected accounts at once. To remove a account from the grouping, just click the x next to its name in the Bulk Edit area that appears.', 'fiscaat' ) . '</p>'
		) );

		// Help Sidebar
		get_current_screen()->set_help_sidebar(
			'<p><strong>' . __( 'For more information:', 'fiscaat' ) . '</strong></p>' .
			'<p>' . __( '<a href="http://codex.fiscaat.org" target="_blank">Fiscaat Documentation</a>',     'fiscaat' ) . '</p>' .
			'<p>' . __( '<a href="http://fiscaat.org/periods/" target="_blank">Fiscaat Support Periods</a>',  'fiscaat' ) . '</p>'
		);
	}

	/**
	 * Contextual help for Fiscaat account edit page
	 *
	 * @uses get_current_screen()
	 */
	public function new_help() {
		if ( $this->bail() ) 
			return;

		$customize_display = '<p>' . __( 'The title field and the big account editing Area are fixed in place, but you can reposition all the other boxes using drag and drop, and can minimize or expand them by clicking the title bar of each box. Use the Screen Options tab to unhide more boxes (Excerpt, Send Trackbacks, Custom Fields, Discussion, Slug, Author) or to choose a 1- or 2-column layout for this screen.', 'fiscaat' ) . '</p>';

		get_current_screen()->add_help_tab( array(
			'id'      => 'customize-display',
			'title'   => __( 'Customizing This Display', 'fiscaat' ),
			'content' => $customize_display,
		) );

		get_current_screen()->add_help_tab( array(
			'id'      => 'title-account-editor',
			'title'   => __( 'Title and Account Editor', 'fiscaat' ),
			'content' =>
				'<p>' . __( '<strong>Title</strong> - Enter a title for your account. After you enter a title, you&#8217;ll see the permalink below, which you can edit.', 'fiscaat' ) . '</p>' .
				'<p>' . __( '<strong>Account Editor</strong> - Enter the text for your account. There are two modes of editing: Visual and HTML. Choose the mode by clicking on the appropriate tab. Visual mode gives you a WYSIWYG editor. Click the last icon in the row to get a second row of controls. The HTML mode allows you to enter raw HTML along with your account text. You can insert media files by clicking the icons above the account editor and following the directions. You can go to the distraction-free writing screen via the Fullscreen icon in Visual mode (second to last in the top row) or the Fullscreen button in HTML mode (last in the row). Once there, you can make buttons visible by hovering over the top area. Exit Fullscreen back to the regular account editor.', 'fiscaat' ) . '</p>'
		) );

		$publish_box = '<p>' . __( '<strong>Publish</strong> - You can set the terms of publishing your account in the Publish box. For Status, Visibility, and Publish (immediately), click on the Edit link to reveal more options. Visibility includes options for password-protecting a account or making it stay at the top of your blog indefinitely (sticky). Publish (immediately) allows you to set a future or past date and time, so you can schedule a account to be published in the future or backdate a account.', 'fiscaat' ) . '</p>';

		if ( current_theme_supports( 'account-formats' ) && account_type_supports( 'account', 'account-formats' ) ) {
			$publish_box .= '<p>' . __( '<strong>account Format</strong> - This designates how your theme will display a specific account. For example, you could have a <em>standard</em> blog account with a title and paragraphs, or a short <em>aside</em> that omits the title and contains a short text blurb. Please refer to the Codex for <a href="http://codex.wordpress.org/Post_Formats#Supported_Formats">descriptions of each account format</a>. Your theme could enable all or some of 10 possible formats.', 'fiscaat' ) . '</p>';
		}

		if ( current_theme_supports( 'account-thumbnails' ) && account_type_supports( 'account', 'thumbnail' ) ) {
			$publish_box .= '<p>' . __( '<strong>Featured Image</strong> - This allows you to associate an image with your account without inserting it. This is usually useful only if your theme makes use of the featured image as a account thumbnail on the home page, a custom header, etc.', 'fiscaat' ) . '</p>';
		}

		get_current_screen()->add_help_tab( array(
			'id'      => 'account-attributes',
			'title'   => __( 'Account Attributes', 'fiscaat' ),
			'content' =>
				'<p>' . __( 'Select the attributes that your account should have:', 'fiscaat' ) . '</p>' .
				'<ul>' .
					'<li>' . __( '<strong>Period</strong> dropdown determines the parent period that the account belongs to. Select the period or category from the dropdown, or leave the default (No Period) to post the account without an assigned period.', 'fiscaat' ) . '</li>' .
					'<li>' . __( '<strong>Account Type</strong> dropdown indicates the sticky status of the account. Selecting the super sticky option would stick the account to the front of your periods, i.e. the account index, sticky option would stick the account to its respective period. Selecting normal would not stick the account anywhere.', 'fiscaat' ) . '</li>' .
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
				'<p>' . __( '<strong>Discussion</strong> - You can turn comments and pings on or off, and if there are comments on the account, you can see them here and moderate them.', 'fiscaat' ) . '</p>'
		) );

		get_current_screen()->set_help_sidebar(
			'<p><strong>' . __( 'For more information:', 'fiscaat' ) . '</strong></p>' .
			'<p>' . __( '<a href="http://codex.fiscaat.org" target="_blank">Fiscaat Documentation</a>',    'fiscaat' ) . '</p>' .
			'<p>' . __( '<a href="http://fiscaat.org/periods/" target="_blank">Fiscaat Support Periods</a>', 'fiscaat' ) . '</p>'
		);
	}

	/** Account Meta **********************************************************/

	/**
	 * Add the account attributes metabox
	 *
	 * @since 0.0.1
	 *
	 * @uses fct_get_account_post_type() To get the account post type
	 * @uses add_meta_box() To add the metabox
	 * @uses do_action() Calls 'fct_account_attributes_metabox'
	 */
	public function attributes_metabox() {
		if ( $this->bail() ) 
			return;

		add_meta_box (
			'fct_account_attributes',
			__( 'Account Attributes', 'fiscaat' ),
			'fct_account_metabox',
			$this->post_type,
			'side',
			'high'
		);

		do_action( 'fct_account_attributes_metabox' );
	}

	/**
	 * Pass the account attributes for processing
	 *
	 * @param int $account_id Account id
	 * @uses current_user_can() To check if the current user is capable of
	 *                           editing the account
	 * @uses do_action() Calls 'fct_account_attributes_metabox_save' with the
	 *                    account id and parent id
	 * @return int Parent id
	 */
	public function attributes_metabox_save( $account_id ) {
		if ( $this->bail() ) 
			return $account_id;

		// Bail if doing an autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return $account_id;

		// Bail if not a post request
		if ( 'POST' != strtoupper( $_SERVER['REQUEST_METHOD'] ) )
			return $account_id;

		// Nonce check
		if ( empty( $_POST['fct_account_metabox'] ) || ! wp_verify_nonce( $_POST['fct_account_metabox'], 'fct_account_metabox_save' ) )
			return $account_id;

		// Bail if current user cannot edit this account
		if ( ! current_user_can( 'edit_account', $account_id ) )
			return $account_id;

		// Get the period ID
		$period_id   = ! empty( $_POST['parent_id'] ) ? (int) $_POST['parent_id'] : fct_get_current_period_id();

		// Get the ledger ID
		$ledger_id = ! empty( $_POST['fct_account_ledger_id'] ) ? (int) $_POST['fct_account_ledger_id'] : 0;

		// Check for ledger id conflict
		fct_check_ledger_id( $account_id, $ledger_id );

		// Formally update the account
		fct_update_account( array( 
			'account_id'   => $account_id, 
			'period_id'    => $period_id,
			'ledger_id'    => $ledger_id,
			'account_type' => ! empty( $_POST['fct_account_type'] ) ? $_POST['fct_account_type'] : '',
		) );

		// Allow other fun things to happen
		do_action( 'fct_account_attributes_metabox_save', $account_id, $period_id );

		return $account_id;
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

			strong.label {
				display: inline-block;
				width: 60px;
			}

			.column-fct_period_account_count,
			.column-fct_period_record_count,
			.column-fct_account_ledger_id,
			.column-fct_account_type,
			.column-fct_account_record_count,
			.column-fct_account_end_value {
				width: 10%;
			}

			.column-author,
			.column-fct_record_author,
			.column-fct_account_author {
				width: 10%;
			}

			.column-fct_account_value,
			.column-fct_account_period,
			.column-fct_record_period,
			.column-fct_record_account {
				width: 10%;
			}

			.status-closed {
				background-color: #eaeaea;
			}

			#fct_account_attributes .ajax-loading {
				vertical-align: middle;
			}

		/*]]>*/
		</style>

		<?php // On post and post-new pages ?>
		<?php if ( 'post' == get_current_screen()->id ) : ?>

		<script type="text/javascript">
			jQuery(document).ready( function($) {
				var $ledger_id = $('input#fct_account_ledger_id'),
				    orig_val   = $ledger_id.attr('value');
				
				// On input change (blur)
				$ledger_id.change( function() {
					if ( this.value ) {
						$loader = $ledger_id.siblings('.ajax-loading').css('visibility', 'visible').show();

						$.post( 
							ajaxurl, 
							{
								action: 'fct_check_ledger_id',
								account_id: <?php echo get_the_ID(); ?>,
								ledger_id: this.value
							}, 
							function ( response ) {
								var icon = response.success ? 'fct-icon-success' : 'fct-icon-error',
								    msg  = response.success ? '' : response.data.post.post_title;

								// Show response icon
								$('<span class="dashicons-before ' + icon + '" title="' + msg + '"></span>')
									.appendTo( $loader.hide().parent() ).delay(800).fadeOut( function() {
										$(this).remove();
									});

								// Reset original value on error
								if ( ! response.success ) 
									$ledger_id.attr('value', orig_val);
							}
						);
					}
				});
			});
		</script>

		<?php endif;
	}

	/** Ajax ******************************************************************/

	/**
	 * Ajax action for facilitating the ledger id check
	 *
	 * @uses get_posts()
	 * @uses fct_get_account_post_type()
	 * @uses fct_get_account_period_id()
	 * @uses wp_send_json_error() To return that an account was found
	 * @uses wp_send_json_success() To return that no account was found
	 */
	public function check_ledger_id() {

		// Find any matching ledger id in the account's period
		$query = get_posts( array(
			'post_type'    => fct_get_account_post_type(),
			'post_parent'  => fct_get_account_period_id( $_REQUEST['account_id'] ),
			'meta_key'     => '_fct_ledger_id',
			'meta_value'   => (int) like_escape( $_REQUEST['ledger_id'] ),
			'post__not_in' => array( (int) $_REQUEST['account_id'] ),
			'numberposts'  => 1
		) );

		// If we found an account, report to user
		if ( ! empty( $query ) ) {
			wp_send_json_error( array( 'post' => $query[0] ) );

		// Report no match
		} else {
			wp_send_json_success();
		}
	}

	/** List Table ************************************************************/

	/**
	 * Manage the column headers for the accounts page
	 *
	 * @param array $columns The columns
	 * @return array $columns Fiscaat account columns
	 */
	public function accounts_column_headers( $columns ) {
		if ( $this->bail() ) 
			return $columns;

		// Hide period column if showing period accounts. When there
		// is no period selection, current period accounts are showed.
		if ( ! isset( $_GET['fct_period_id'] ) || ! empty( $_GET['fct_period_id'] ) ) {
			unset( $columns['fct_account_period'] );
		}

		return $columns;
	}

	/**
	 * Return account records view link instead of edit post link
	 * 
	 * @param string $link Edit post link
	 * @param int $post_id Current post id
	 * @param mixed $context Context
	 * @return string Account records view link
	 */
	public function accounts_edit_post_link( $link, $post_id, $context ) {
		if ( $this->bail() ) 
			return $link;

		// Distinguish edit post links
		if ( true === $context )
			return $link;

		// Build account records view link
		$link = add_query_arg( array( 'post_type' => fct_get_record_post_type(), 'fct_account_id' => $post_id ), admin_url( 'edit.php' ) );

		return apply_filters( 'fct_admin_accounts_edit_post_link', $link, $post_id, $context );
	}

	/**
	 * Add period dropdown to account and record list table filters
	 *
	 * @uses fct_dropdown() To generate a period dropdown
	 * @return bool False. If post type is not account or record
	 */
	public function filter_dropdown() {
		if ( $this->bail() ) 
			return;

		// Show the number dropdown
		fct_ledger_dropdown( array(
			'selected'  => isset( $_GET['fct_ledger_account_id'] ) ? $_GET['fct_ledger_account_id'] : '',
			'show_none' => '&mdash;'
		) );

		// Get which period is selected. Default to current period
		$selected = isset( $_GET['fct_period_id'] ) ? $_GET['fct_period_id'] : fct_get_current_period_id();

		// Show the periods dropdown
		fct_dropdown( array(
			'selected'  => $selected,
			'show_none' => __( 'In all periods', 'fiscaat' )
		) );
	}

	/**
	 * Adjust the request query and include the parent period id
	 *
	 * @param array $query_vars Query variables from {@link WP_Query}
	 * @return array Processed Query Vars
	 */
	function filter_post_rows( $query_vars ) {
		if ( $this->bail() ) 
			return $query_vars;

		// Setup meta query
		$meta_query = isset( $query_vars['meta_query'] ) ? $query_vars['meta_query'] : array();

		/** Period **************************************************************/

		// Set the parent from period id if given or current period. Empty
		// period results in all periods.
		$query_vars['post_parent'] = isset( $_REQUEST['fct_period_id'] ) ? $_REQUEST['fct_period_id'] : fct_get_current_period_id();

		/** Ledger ************************************************************/

		// Query by ledger id
		if ( ! empty( $_REQUEST['fct_ledger_account_id'] ) ) {
			$meta_query[] = array(
				'key'   => '_fct_ledger_id',
				'value' => fct_get_account_ledger_id( (int) $_REQUEST['fct_ledger_account_id'] )
			);
		}

		/** Sorting ***********************************************************/

		// Handle sorting
		$orderby = ! empty( $_REQUEST['orderby'] ) ? $_REQUEST['orderby'] : '';

		// Check order type
		switch ( $orderby ) {

			// Account type
			case 'account_type' :
				$query_vars['meta_key'] = '_fct_account_type';
				$query_vars['orderby']  = 'meta_value'; // Also 2nd item (ledger id)?
				break;

			// Account record count
			case 'account_record_count' :
				$query_vars['meta_key'] = '_fct_record_count';
				$query_vars['orderby']  = 'meta_value_num';
				break;

			// Account end value
			case 'account_end_value' :
				$query_vars['meta_key'] = '_fct_end_value';
				$query_vars['orderby']  = 'meta_value_num';
				break;

			// Account ledger id. Default order when none requested
			case 'account_ledger_id' :
			case '':
				$query_vars['meta_key'] = '_fct_ledger_id';
				$query_vars['orderby']  = 'meta_value_num';
				break;
		}

		// Default sorting order
		if ( ! isset( $query_vars['order'] ) ) {
			$query_vars['order'] = isset( $_REQUEST['order'] ) ? strtoupper( $_REQUEST['order'] ) : 'ASC';
		}

		// Set meta query
		$query_vars['meta_query'] = $meta_query;

		// Return manipulated query_vars
		return $query_vars;
	}

	/** Post Actions **********************************************************/

	/**
	 * Account Row actions
	 *
	 * Add the view/records/close/open/delete action links under the account title
	 *
	 * @since 0.0.1
	 *
	 * @param array $actions Actions
	 * @param array $account Account object
	 * @uses fct_get_account_post_type() To get the account post type
	 * @uses fct_get_account_permalink() To get the account link
	 * @uses fct_get_account_title() To get the account title
	 * @uses current_user_can() To check if the current user can edit or
	 *                           delete the account
	 * @uses fct_is_account_open() To check if the account is open
	 * @uses add_query_arg() To add custom args to the url
	 * @uses remove_query_arg() To remove custom args from the url
	 * @uses wp_nonce_url() To nonce the url
	 * @uses get_delete_post_link() To get the delete post link of the account
	 * @return array $actions Actions
	 */
	public function accounts_row_actions( $actions, $account ) {
		if ( $this->bail() ) 
			return $actions;

		// Show view link if it's not set, the account is trashed and the user can view trashed accounts
		if ( empty( $actions['view'] ) && ( fct_get_trash_status_id() == $account->post_status ) && current_user_can( 'view_trash' ) ) {
			$actions['view'] = '<a href="' . fct_get_account_permalink( $account->ID ) . '" title="' . esc_attr( sprintf( __( 'View &#8220;%s&#8221;', 'fiscaat' ), fct_get_account_title( $account->ID ) ) ) . '" rel="permalink">' . __( 'View', 'fiscaat' ) . '</a>';
		}

		// Show records link
		if ( current_user_can( 'read_account', $account->ID ) ) {
			$actions['records'] = '<a href="' . add_query_arg( array( 'page' => 'fct-records', 'fct_account_id' => $account->ID ), admin_url( 'admin.php' ) ) .'" title="' . esc_attr( sprintf( __( 'Show all records of &#8220;%s&#8221;',  'fiscaat' ), fct_get_account_title( $account->ID ) ) ) . '">' . __( 'Records', 'fiscaat' ) . '</a>';
		}

		// Show the close and open link
		if ( current_user_can( 'edit_account', $account->ID ) ) {
			$toggle_uri = esc_url( wp_nonce_url( add_query_arg( array( 'account_id' => $account->ID, 'action' => 'fct_toggle_account_close' ), remove_query_arg( array( 'fct_account_toggle_notice', 'account_id', 'failed', 'super' ) ) ), 'close-account_' . $account->ID ) );
			if ( fct_is_account_open( $account->ID ) ) {
				$actions['closed'] = '<a href="' . $toggle_uri . '" title="' . esc_attr__( 'Close this account', 'fiscaat' ) . '">' . _x( 'Close', 'Close the account', 'fiscaat' ) . '</a>';
			} else {
				$actions['open'] = '<a href="' . $toggle_uri . '" title="' . esc_attr__( 'Open this account',  'fiscaat' ) . '">' . _x( 'Open',  'Open the account',  'fiscaat' ) . '</a>';
			}
		}

		// Only show delete links for empty accounts. No trash
		if ( current_user_can( 'delete_account', $account->ID ) && ! fct_account_has_records() ) {
			$actions['delete'] = "<a class='submitdelete' title='" . esc_attr__( 'Delete this item permanently', 'fiscaat' ) . "' href='" . add_query_arg( array( '_wp_http_referer' => add_query_arg( array( 'post_type' => fct_get_account_post_type() ), admin_url( 'edit.php' ) ) ), get_delete_post_link( $account->ID, '', true ) ) . "'>" . __( 'Delete', 'fiscaat' ) . "</a>";
		}

		return $actions;
	}

	/**
	 * Toggle account
	 *
	 * Handles the admin-side opening/closing of accounts
	 *
	 * @uses fct_get_account() To get the account
	 * @uses current_user_can() To check if the user is capable of editing
	 *                           the account
	 * @uses wp_die() To die if the user isn't capable or the post wasn't
	 *                 found
	 * @uses check_admin_referer() To verify the nonce and check referer
	 * @uses fct_is_account_open() To check if the account is open
	 * @uses fct_close_account() To close the account
	 * @uses fct_open_account() To open the account
	 * @uses do_action() Calls 'fct_toggle_account_admin' with success, post
	 *                    data, action and message
	 * @uses add_query_arg() To add custom args to the url
	 * @uses wp_safe_redirect() Redirect the page to custom url
	 */
	public function toggle_account() {
		if ( $this->bail() ) 
			return;

		// Only proceed if GET is a account toggle action
		if ( 'GET' == $_SERVER['REQUEST_METHOD'] && ! empty( $_GET['action'] ) && in_array( $_GET['action'], array( 'fct_toggle_account_close' ) ) && ! empty( $_GET['account_id'] ) ) {
			$action     = $_GET['action'];                // What action is taking place?
			$account_id = (int) $_GET['account_id'];      // What's the account id?
			$success    = false;                          // Flag
			$post_data  = array( 'ID' => $account_id );   // Prelim array
			$account    = fct_get_account( $account_id );

			// Bail if account is missing
			if ( empty( $account ) )
				wp_die( __( 'The account was not found!', 'fiscaat' ) );

			if ( !current_user_can( 'control', $account->ID ) ) // What is the user doing here?
				wp_die( __( 'You do not have the permission to do that!', 'fiscaat' ) );

			switch ( $action ) {
				case 'fct_toggle_account_close' :
					check_admin_referer( 'close-account_' . $account_id );

					$is_open = fct_is_account_open( $account_id );
					$message = true == $is_open ? 'closed' : 'opened';
					$success = true == $is_open ? fct_close_account( $account_id ) : fct_open_account( $account_id );

					break;
			}

			$message = array( 'fct_account_toggle_notice' => $message, 'account_id' => $account->ID );

			if ( false == $success || is_wp_error( $success ) )
				$message['failed'] = '1';

			// Do additional account toggle actions (admin side)
			do_action( 'fct_toggle_account_admin', $success, $post_data, $action, $message );

			// Redirect back to the account
			$redirect = add_query_arg( $message, remove_query_arg( array( 'action', 'account_id' ) ) );
			wp_safe_redirect( $redirect );

			// For good measure
			exit();
		}
	}

	/**
	 * Toggle account notices
	 *
	 * Display the success/error notices from
	 * {@link Fiscaat_Accounts_Admin::toggle_account()}
	 *
	 * @since 0.0.1
	 *
	 * @uses fct_get_account() To get the account
	 * @uses fct_get_account_title() To get the account title of the account
	 * @uses esc_html() To sanitize the account title
	 * @uses apply_filters() Calls 'fct_toggle_account_notice_admin' with
	 *                        message, account id, notice and is it a failure
	 */
	public function toggle_account_notice() {
		if ( $this->bail() ) 
			return;

		// Only proceed if GET is a account toggle action
		if ( 'GET' == $_SERVER['REQUEST_METHOD'] && ! empty( $_GET['fct_account_toggle_notice'] ) && in_array( $_GET['fct_account_toggle_notice'], array( 'opened', 'closed' ) ) && !empty( $_GET['account_id'] ) ) {
			$notice     = $_GET['fct_account_toggle_notice'];       // Which notice?
			$account_id = (int) $_GET['account_id'];                // What's the account id?
			$is_failure = !empty( $_GET['failed'] ) ? true : false; // Was that a failure?

			// Bais if no account_id or notice
			if ( empty( $notice ) || empty( $account_id ) )
				return;

			// Bail if account is missing
			$account = fct_get_account( $account_id );
			if ( empty( $account ) )
				return;

			$account_title = esc_html( fct_get_account_title( $account->ID ) );

			switch ( $notice ) {
				case 'opened' :
					$message = $is_failure == true ? sprintf( __( 'There was a problem opening the account "%1$s".', 'fiscaat' ), $account_title ) : sprintf( __( 'Account "%1$s" successfully opened.', 'fiscaat' ), $account_title );
					break;

				case 'closed' :
					$message = $is_failure == true ? sprintf( __( 'There was a problem closing the account "%1$s".', 'fiscaat' ), $account_title ) : sprintf( __( 'Account "%1$s" successfully closed.', 'fiscaat' ), $account_title );
					break;
			}

			// Do additional account toggle notice filters (admin side)
			$message = apply_filters( 'fct_toggle_account_notice_admin', $message, $account->ID, $notice, $is_failure );

			?>

			<div id="message" class="<?php echo $is_failure == true ? 'error' : 'updated'; ?> fade">
				<p style="line-height: 150%"><?php echo $message; ?></p>
			</div>

			<?php
		}
	}

	/** Redirect **************************************************************/

	/**
	 * Redirect user to record post-new page with correct message id
	 *
	 * @uses fct_has_open_period()
	 * @uses fct_get_record_post_type()
	 * @uses add_query_arg()
	 * @uses wp_safe_redirect()
	 */
	public function no_period_redirect() {
		if ( $this->bail() ) 
			return;

		// Check for existing message
		if ( isset( $_GET['message'] ) )
			return;

		// Install has no open period
		if ( ! fct_has_open_period() ) {
			$message = 11;

		// Everything okay
		} else {
			return;
		}

		// Redirect user with message
		wp_safe_redirect( add_query_arg( array( 'message' => $message, 'post_type' => fct_get_account_post_type() ), admin_url( 'post-new.php' ) ) );
	}

	/** Messages **************************************************************/

	/**
	 * Custom user feedback messages for account post type
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

		if ( $this->bail() ) 
			return $messages;

		// URL for the current account
		$account_url = fct_get_account_permalink( $post_ID );

		// Current account's post_date
		$post_date = fct_get_global_post_field( 'post_date', 'raw' );

		// Messages array
		$messages[$this->post_type] = array(
			0 =>  '', // Left empty on purpose

			// Updated
			1 =>  sprintf( __( 'Account updated. <a href="%s">View account</a>', 'fiscaat' ), $account_url ),

			// Custom field updated
			2 => __( 'Custom field updated.', 'fiscaat' ),

			// Custom field deleted
			3 => __( 'Custom field deleted.', 'fiscaat' ),

			// Account updated
			4 => __( 'Account updated.', 'fiscaat' ),

			// Restored from revision
			// translators: %s: date and time of the revision
			5 => isset( $_GET['revision'] )
					? sprintf( __( 'Account restored to revision from %s', 'fiscaat' ), wp_post_revision_title( (int) $_GET['revision'], false ) )
					: false,

			// Account created
			6 => sprintf( __( 'Account created. <a href="%s">View account</a>', 'fiscaat' ), $account_url ),

			// Account saved
			7 => __( 'Account saved.', 'fiscaat' ),

			// Account submitted
			8 => sprintf( __( 'Account submitted. <a target="_blank" href="%s">Preview account</a>', 'fiscaat' ), esc_url( add_query_arg( 'preview', 'true', $account_url ) ) ),

			// Account scheduled
			9 => sprintf( __( 'Account scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview account</a>', 'fiscaat' ),
					// translators: Publish box date format, see http://php.net/date
					date_i18n( __( 'M j, Y @ G:i', 'fiscaat' ),
					strtotime( $post_date ) ),
					$account_url ),

			// Account draft updated
			10 => sprintf( __( 'Account draft updated. <a target="_blank" href="%s">Preview account</a>', 'fiscaat' ), esc_url( add_query_arg( 'preview', 'true', $account_url ) ) ),

			// Require a period
			11 => sprintf( __( 'Using Fiscaat requires an open period to register accounts in. <a href="%s">Create a period first</a>.', 'fiscaat' ), esc_url( add_query_arg( 'post_type', fct_get_period_post_type(), admin_url( 'post-new.php' ) ) ) ),

			// Account number already taken
			12 => isset( $_GET['fct_ledger_id'] )
					? sprintf( __( 'The account number <strong>%d</strong> is already taken by <a href="%s">%s</a>. Use another number!', 'fiscaat' ), (int) $_GET['fct_ledger_id'], esc_url( add_query_arg( array( 'post' => fct_get_account_id_by_ledger_id( (int) $_GET['fct_ledger_id'] ), 'action' => 'edit' ), admin_url( 'post.php' ) ) ), fct_get_account_title( fct_get_account_id_by_ledger_id( (int) $_GET['fct_ledger_id'] ) ) )
					: false,

			// Account number required
			13 => __( 'No account number submitted. Please assign a unique number to this account.', 'fiscaat' ),
		);

		return $messages;
	}

	/** Page Title ************************************************************/

	/**
	 * Manipulate the accounts posts page title
	 * 
	 * @uses fct_get_period_title() To get the period title
	 * @return array Modified arguments
	 */
	public function accounts_page_title( $title ) {

		// Period accounts
		if ( ! isset( $_GET['fct_period_id'] ) || ! empty( $_GET['fct_period_id'] ) ) {

			// Check period id
			$selected  = isset( $_GET['fct_period_id'] ) ? $_GET['fct_period_id'] : fct_get_current_period_id();
			$period_id = fct_get_period_id( $selected );

			if ( ! empty( $period_id ) ) {
				// Format: {title} -- {period title}
				$title .= ' &mdash; '. fct_get_period_title( $period_id );
			}
		}

		return $title;
	}

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

		// Require open period
		if ( fct_has_open_period() ) {
			$title = fct_admin_page_title_add_new( $title );
		}

		return $title;
	}
}

endif; // class_exists check

/**
 * Setup Fiscaat Accounts Admin
 *
 * This is currently here to make hooking and unhooking of the admin UI easy.
 * It could use dependency injection in the future, but for now this is easier.
 *
 * @uses Fiscaat_Accounts_Admin
 */
function fct_admin_accounts() {
	fiscaat()->admin->accounts = new Fiscaat_Accounts_Admin();
}
