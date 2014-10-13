<?php

/**
 * Fiscaat Records Upload Class
 * 
 * @package Fiscaat
 * @subpackage Administration
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Fiscaat_Records_Upload' ) ) :
/**
 * Loads Fiscaat records admin upload area
 *
 * @package Fiscaat
 * @subpackage Administration
 */
class Fiscaat_Records_Upload {

	/**
	 * The main Fiscaat records upload loader
	 *
	 * @uses Fiscaat_Records_Upload::setup_actions() Setup the hooks and actions
	 */
	public function __construct() {
		$this->setup_actions();
	}

	/**
	 * Setup the upload hooks, actions and filters
	 *
	 * @access private
	 *
	 * @uses add_action() To add various actions
	 * @uses add_filter() To add various filters
	 */
	private function setup_actions() {

		// Setup upload page
		add_action( 'fct_admin_load_records', array( $this, 'setup_upload_page' ), 9 );

		// Setup drag-and-drop on new mode
		add_action( 'fct_admin_load_new_records', array( $this, 'setup_upload_window' ) );

		// Setup page title
		add_filter( 'fct_admin_records_page_title', array( $this, 'setup_page_title' ) );
	}

	/**
	 * Should we bail out of this method?
	 *
	 * @since 0.0.9
	 * 
	 * @return boolean
	 */
	private function bail() {
		if ( ! isset( get_current_screen()->post_type ) || ( fct_get_record_post_type() != get_current_screen()->post_type ) || ! fct_admin_is_upload_records() )
			return true;

		return false;
	}

	/**
	 * Remove default records page load hooks to replace with upload page hooks
	 *
	 * @since 0.0.9
	 *
	 * @uses remove_action()
	 * @uses add_action()
	 */
	public function setup_upload_page() {
		if ( $this->bail() )
			return;

		// Unhook default record mode hooks
		remove_action( 'fct_admin_load_records', array( fiscaat()->admin->records, 'page_mode_actions' ) );

		// Unhook list table setup
		remove_action( 'fct_admin_load_records', 'fct_admin_setup_list_table' );

		// Hook upload mode hook
		add_action( 'fct_admin_load_records', array( $this, 'load_upload_records' ) );

		// Handle file upload
		add_action( 'fct_admin_load_upload_records', array( $this, 'handle_upload_file' ) );

		// Display upload form
		add_action( 'fct_admin_records_page', array( $this, 'display_upload_form' ) );
	}

	/**
	 * Setup upload records mode hook
	 *
	 * @since 0.0.9
	 * 
	 * @uses do_action() Calls 'fct_admin_load_upload_records'
	 */
	public function load_upload_records() {
		do_action( 'fct_admin_load_upload_records' );
	}

	/**
	 * Run logic when loading the upload page
	 * 
	 * @since 0.0.9
	 * 
	 * @see wp-admin/media-new.php
	 */
	public function handle_upload_file() {
		if ( ! current_user_can( 'upload_records' ) )
			wp_die( __( 'You do not have permission to upload records.', 'fiscaat' ) );

		// Enqueue plupload scripts
		wp_enqueue_script( 'plupload-handlers' );

		if ( $_POST ) {
			$location = admin_url( add_query_arg( array( 
				'page' => 'fct-records',
				'mode' => fct_admin_get_new_records_mode()
			) ) );

			if ( isset( $_POST['html-upload'] ) && ! empty( $_FILES ) ) {
				check_admin_referer( 'fct-upload-records-form' );
				// Upload File button was clicked
				$id = media_handle_upload( 'async-upload', $post_id );
				if ( is_wp_error( $id ) )
					$location = add_query_arg( 'message', 3, $location );
			}
			wp_redirect( $location );
			exit;
		} 
	}

	/**
	 * Display the file upload form
	 *
	 * @since 0.0.9
	 * 
	 * @see wp-admin/media-new.php
	 *
	 * @uses fct_admin_get_upload_records_mode()
	 * @uses media_upload_form()
	 */
	public function display_upload_form() { 

		// Filter plupload args
		add_filter( 'upload_post_params', array( $this, 'plupload_post_params' ) );
		add_filter( 'plupload_init', array( $this, 'plupload_settings_args' ) );

		$upload_url = admin_url( add_query_arg( array( 
			'page' => 'fct-records',
			'mode' => fct_admin_get_upload_records_mode()
		) ) );

		$form_class = 'media-upload-form type-form validate';

		if ( get_user_setting( 'uploader' ) || isset( $_GET['browser-uploader'] ) )
			$form_class .= ' html-uploader';
		?>

		<form enctype="multipart/form-data" method="post" action="<?php echo admin_url( $upload_url ); ?>" class="<?php echo esc_attr( $form_class ); ?>" id="fct-file-form">

			<?php media_upload_form(); ?>

			<?php wp_nonce_field( 'fct-upload-records-form' ); ?>
			<div id="media-items" class="hide-if-no-js"></div>
		</form>

		<?php
	}

	/**
	 * Enqueue scripts for the drag-and-drop upload window
	 *
	 * Using wp_enqueue_media() is a little too much for just the window
	 * uploader, but it works for nwow.
	 *
	 * @since 0.0.9
	 *
	 * @uses wp_register_script()
	 * @uses wp_enqueue_media()
	 * @uses wp_enqueue_script()
	 */
	public function setup_upload_window() {
		$fct = fiscaat();

		wp_register_script( 'fct-admin-upload', $fct->admin->admin_url . 'js/admin-upload.js', array( 'media-views' ), $fct->version, 1 );

		// Filter plupload
		add_filter( 'plupload_default_settings', array( $this, 'plupload_settings_args' ) );
		add_filter( 'plupload_default_params', array( $this, 'plupload_post_params' ) );

		wp_enqueue_media();
		wp_enqueue_script( 'fct-admin-upload' );
	}

	/**
	 * Filter the plupload post parameters
	 * 
	 * Since wp_plupload_default_settings() overwrites the '_wpnonce' in the
	 * 'multipart_params' argument after this filter, we cannot change it here.
	 *
	 * @since 0.0.9
	 * 
	 * @param array $params Post params
	 * @return array Params
	 */
	public function plupload_post_params( $params ) {
		$params = wp_parse_args( array(
			'action' => 'upload-records',
		), $params );

		return $params;
	}

	/**
	 * Filter the plupload settings arguments
	 *
	 * Since wp_plupload_default_settings() overwrites the 'multipart_params'
	 * argument after this filter, we cannot change it here.
	 *
	 * @since 0.0.9
	 * 
	 * @param array $args Args
	 * @return array Args
	 */
	public function plupload_settings_args( $args ) {
		$args = wp_parse_args( array(
			// Use our own async-upload logic
			'url' => fiscaat()->admin->includes_url . 'async-upload.php',
		), $args );

		return $args;
	}

	/**
	 * Append upload-new link to the page title
	 *
	 * @since 0.0.9
	 * 
	 * @uses fct_admin_is_new_records()
	 * @uses fct_admin_get_upload_records_mode()
	 * @param string $title Page title
	 * @return string Page title
	 */
	public function setup_page_title( $title ) {

		// Modify page title when uploading
		if ( fct_admin_is_upload_records() ) {
			$title = __( 'Upload Records', 'fiscaat' );

		// Append upload link on New Records page
		} elseif ( fct_admin_is_new_records() && current_user_can( 'upload_records' ) ) {
			$title .= sprintf( ' <a href="%s" class="add-new-h2">%s</a>', add_query_arg( array(
				'page' => 'fct-records',
				'mode' => fct_admin_get_upload_records_mode()
			) ), esc_html__( 'Upload', 'fiscaat' ) );
		}

		return $title;
	}
}

endif; // class_exists

/**
 * Setup Fiscaat Records Upload
 * 
 * @since 0.0.9
 *
 * @uses Fiscaat_Records_Upload
 */
function fct_admin_records_upload() {
	fiscaat()->admin->records->upload = new Fiscaat_Records_Upload();
}
