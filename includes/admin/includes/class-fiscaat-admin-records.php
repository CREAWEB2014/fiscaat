<?php

/**
 * Fiscsaat Admin Records Base Class
 *
 * @package Fiscaat
 * @subpackage Administration
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Fiscaat_Admin_Records' ) ) :

/**
 * Loads Fiscaat record admin area
 *
 * @package Fiscaat
 * @subpackage Administration
 */
class Fiscaat_Admin_Records {

	/** Variables *************************************************************/

	/**
	 * @var The post type of this admin component
	 */
	public $post_type = '';

	/**
	 * @var The page args of this admin component
	 */
	public $args = '';

	/**
	 * @var Holds the list table object for this admin component
	 */
	public $list_table = '';

	/** Functions *************************************************************/

	/**
	 * The main Fiscaat record admin loader
	 *
	 * @uses Fiscaat_Admin_Records::setup_globals() Setup the globals needed
	 * @uses Fiscaat_Admin_Records::class_actions() Setup the default hooks and actions
	 * @uses Fiscaat_Admin_Records::setup_actions() Setup child hooks and actions
	 *
	 * @param array $args Child class construct arguments:
	 *  - string $page       Required. The page slug
	 *  - string $menu_title Optional. The menu title
	 *  - string $page_title Optional. The page title
	 *  - string $cap        Optional. The required page capability
	 */
	public function __construct( $args = array() ) {
		$this->setup_globals( $args );
		$this->class_actions();
		$this->setup_actions();
	}

	/**
	 * Setup the class admin hooks, actions and filters
	 *
	 * @access private
	 *
	 * @uses add_action() To add various actions
	 * @uses add_filter() To add various filters
	 */
	private function class_actions() {

		// Insert admin page
		add_action( 'fct_admin_menu', array( $this, 'admin_menu' ) );

		// Page load hooks
		add_action( $this->args->hook_prefix .'_load',   array( $this, 'load_list_table'  )        );
		add_action( $this->args->hook_prefix .'_load',   array( $this, 'help'             )        );
		add_action( $this->args->hook_prefix .'_load',   array( $this, 'remove_add_rows'  )        );
		add_filter( 'fct_records_list_table_action',     array( $this, 'submit_action'    )        );
		add_filter( $this->args->hook_prefix .'_action', array( $this, 'submit_do_action' ), 10, 2 );

		// Page head hooks
		add_action( $this->args->hook_prefix .'_head', array( $this, 'enqueue_scripts' ) );
		add_action( $this->args->hook_prefix .'_head', array( $this, 'admin_styles'    ) );

		// Item hooks
		remove_filter( 'the_posts', array( 'Fiscaat_Records_Admin', 'records_add_rows' ), 99 );

		// Table hooks
		add_action( 'fct_records_list_table_pagination', array( $this, 'submit_button' ), 1 );
	}

	/**
	 * Admin globals
	 *
	 * @access private
	 */
	private function setup_globals( $args = array() ) {
		global $post_type_object;

		$this->post_type = fct_get_record_post_type();

		if ( empty( $post_type_object ) )
			$post_type_object = get_post_type_object( $this->post_type );

		$default = array(
			'page'       => '',
			'menu_title' => $post_type_object->labels->name,
			'page_title' => $post_type_object->labels->name,
			'cap'        => $post_type_object->cap->edit_posts,
		);
		$args = wp_parse_args( $args, $default );

		// Require page
		if ( empty( $args['page'] ) )
			wp_die( sprintf( __( 'Missing paramater <em>%s</em> for class Fiscaat_Admin_Records.', 'fiscaat'), 'page' ) );

		$args['hook_prefix'] = 'fct_admin_records_'. $args['page'];
		$this->args = (object) $args;
	}

	/**
	 * Setup sub-class admin hooks, actions and filters
	 */
	public function setup_actions() {
		die( __('function Fiscaat_Admin_Records::setup_actions must be over-ridden in a sub-class.', 'fiscaat' ) );
	}

	/**
	 * Should we bail out of this method?
	 *
	 * @param boolean $check_page Whether to check for the current page
	 * @return boolean
	 */
	protected function bail( $check_page = true ) {
		if ( ! isset( get_current_screen()->post_type ) || ( $this->post_type != get_current_screen()->post_type ) )
			return true;

		if ( $check_page && isset( $_GET['page'] ) && $this->args->page != $_GET['page'] ) 
			return true;

		return false;
	}

	/**
	 * Return true boolean value when not to bail
	 */
	public function return_true() {
		if ( $this->bail() ) return false;
		return true;
	}

	/**
	 * Create record admin menu structure
	 */
	public function admin_menu() {

		// Create record new page
		$hook = add_submenu_page( 
			'edit.php?post_type='. $this->post_type, 
			$this->args->menu_title, 
			$this->args->page_title, 
			$this->args->cap, 
			$this->args->page,
			array( $this, 'admin_page' ) 
		);

		// Admin page hooks
		add_action( 'load-'         . $hook, array( $this, 'admin_load'   ) );
		add_action( 'admin_head-'   . $hook, array( $this, 'admin_head'   ) );
		add_action( 'admin_footer-' . $hook, array( $this, 'admin_footer' ) );
	}

	/**
	 * Piggy back admin page load action
	 */
	public function admin_load() {
		do_action( $this->args->hook_prefix .'_load' );
	}

	/**
	 * Piggy back admin page head action
	 */
	public function admin_head() {
		do_action(  $this->args->hook_prefix .'_head' );
	}

	/**
	 * Piggy back admin page footer action
	 */
	public function admin_footer() {
		do_action(  $this->args->hook_prefix .'_footer' );
	}

	/**
	 * Output admin styles
	 */
	public function admin_styles() {
		if ( $this->bail() ) return; ?>

		<script type="text/javascript">

			/* Communicate between account id and ledger id dropdowns per each record row */
			jQuery(document).ready(function($) {
				var rows      = $( '#the-list .record' ),
				    dropdowns = [ 'select.fct_new_record_account_id', 'select.fct_new_record_ledger_id' ];

				$.each( [ rows.find(dropdowns[0]), rows.find(dropdowns[1]) ], function( i ){
					var other = ( i == 1 ) ? 0 : 1;

					this.livequery( 'change', function(){
						$(this).parents( '.record' ).find( dropdowns[other] + ' option[value='+ this.value +']' ).attr( 'selected', true );
					});
				});
			});

		</script>

		<style type="text/css" media="screen">
		/*<![CDATA[*/

			.record input {
				padding-top: 4px;
			}

			.column-fct_record_created input,
			.column-fct_record_description textarea,
			.column-fct_record_offset_account input {
				width: 100%;
			}

			.records-<?php echo $this->args->page; ?> .column-fct_record_account_ledger_id {
				width: 52px;
			}

			.records-<?php echo $this->args->page; ?> .column-fct_record_created {
				width: 72px !important;
			}

			.wp-list-table.records-<?php echo $this->args->page; ?> td.column-fct_record_created, 
			.wp-list-table.records-<?php echo $this->args->page; ?> td.column-fct_record_description, 
			.wp-list-table.records-<?php echo $this->args->page; ?> td.column-fct_record_account, 
			.wp-list-table.records-<?php echo $this->args->page; ?> td.column-fct_record_account_ledger_id, 
			.wp-list-table.records-<?php echo $this->args->page; ?> td.column-fct_record_offset_account,
			.wp-list-table.records-<?php echo $this->args->page; ?> td.column-fct_record_value {
				padding: 4px 7px 1px;
			}

		/*]]>*/
		</style>

		<?php
	}

	/**
	 * Enqueue scripts
	 */
	public function enqueue_scripts() {
		if ( $this->bail() ) return;

		// Enqueue jQuery livequery plugin
		wp_register_script( 'livequery', fiscaat()->admin->admin_url . 'scripts/jquery.livequery.min.js', array( 'jquery' ), '1.1.1' );
		wp_enqueue_script ( 'livequery' );	

		// Enqueue jQuery formatCurrency plugin
		wp_register_script( 'format-currency', fiscaat()->admin->admin_url . 'scripts/jquery.formatcurrency.min.js', array( 'jquery' ), '1.4.0' );
		wp_enqueue_script ( 'format-currency' );
	}

	/**
	 * Contextual help
	 */
	public function help() {
		die( __('function Fiscaat_Admin_Records::help() must be over-ridden in a sub-class', 'fiscaat') );
	}

	/**
	 * Undo add fiscaat rows filter
	 */
	public function remove_add_rows() {
		remove_filter( 'the_posts', array( 'Fiscaat_Records_Admin', 'records_add_rows' ), 99 );
	}

	/**
	 * Setup records list table
	 */
	public function load_list_table() {
		require_once( fiscaat()->admin->admin_dir . 'includes/class-fiscaat-records-list-table.php' );
		$this->list_table = new Fiscaat_Records_List_Table;

		// Handle actions
		$do_action = $this->list_table->current_action();
		// var_dump( $do_action ); exit;
		if ( $do_action ) {
			check_admin_referer('bulk-records');

			$sendback = remove_query_arg( array('ids'), wp_get_referer() );

			switch ( $do_action ) {
				default :
					$sendback = apply_filters( $this->args->hook_prefix .'_action', $sendback, $do_action );
					break;
			}

			$sendback = remove_query_arg( array('action', 'action2', 'tags_input', 'post_author', 'comment_status', 'ping_status', '_status', 'post', 'bulk_edit', 'post_view'), $sendback );

			wp_redirect( $sendback );
			exit();
		} elseif ( ! empty($_REQUEST['_wp_http_referer']) ) {
			wp_redirect( remove_query_arg( array('_wp_http_referer', '_wpnonce'), stripslashes($_SERVER['REQUEST_URI']) ) );
			exit;
		}

		// Setup list table items
		$this->list_table->prepare_items();
	}

	/**
	 * Output record new admin page
	 */
	public function admin_page() {
		?>

	<div class="wrap">
		<?php screen_icon(); ?>
		<h2><?php $this->page_title(); ?></h2>

		<?php do_action( $this->args->hook_prefix .'_form_before' ); ?>

		<form id="posts-filter" action="" method="post">

			<?php do_action( $this->args->hook_prefix .'_form_top' ); ?>

			<input type="hidden" name="page" class="post_page_page" value="<?php echo $this->args->page; ?>" />
			<input type="hidden" name="post_status" class="post_status_page" value="<?php echo ! empty($_REQUEST['post_status']) ? esc_attr($_REQUEST['post_status']) : 'all'; ?>" />
			<input type="hidden" name="post_type" class="post_type_page" value="<?php echo $this->post_type; ?>" />

			<?php $this->list_table->display(); ?>

			<?php do_action( $this->args->hook_prefix .'_form_bottom' ); ?>

		</form>

		<?php do_action( $this->args->hook_prefix .'_form_after' ); ?>

		<?php
		if ( $this->list_table->has_items() )
			$this->list_table->inline_edit();
		?>

		<div id="ajax-response"></div>
		<br class="clear" />
	</div>

		<?php
	}

	/**
	 * Output the page title
	 * 
	 * @uses apply_filters() Calls '{$hook_prefix}_page_title' with the page title
	 */
	public function page_title() {
		echo apply_filters( $this->args->hook_prefix . '_page_title', $this->args->page_title );
	}

	/**
	 * Add list table class
	 */
	public function list_table_class( $class ) {
		if ( $this->bail() ) return $class;

		$class[] = 'records-'. $this->args->page;
		return $class;
	}

	/**
	 * Output submit button
	 */
	public function submit_button( $which ) {
		if ( $this->bail() ) return;

		$name = 'fct_records_submit';
		if ( 'top' != $which )
			$name .= '2';

		?>
		<div class="tablenav-pages">
			<?php submit_button( __( 'Save Records', 'fiscaat' ), 'primary', $name, false, array( 'tab_index' => 9999 ) ); ?>
		</div>
	<?php
	}

	/**
	 * Hook page specific submit action
	 */
	public function submit_action( $action ) {
		if ( $this->bail() ) return $action;

		if ( isset( $_REQUEST['fct_records_submit'] ) || isset( $_REQUEST['fct_records_submit2'] ) )
			$action = 'records_'. $this->args->page .'_submit';

		return $action;
	}

	/**
	 * Register submit action
	 */
	public function submit_do_action( $sendback, $do_action ) {

		// Bail if not this page submit action
		if ( 'records_'. $this->args->page .'_submit' != $do_action )
			return $sendback;

		return apply_filters( $this->args->hook_prefix .'_submit', $sendback );
	}

}

endif; // class_exists check
