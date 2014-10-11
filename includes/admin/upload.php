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

		add_filter( 'fct_admin_records_page_title', array( $this, 'append_upload_link' ), 15 );
	}

	/**
	 * Append upload-new link to the page title
	 *
	 * @since 0.0.9
	 * 
	 * @param string $title Page title
	 * @return string Page title
	 */
	public function append_upload_link( $title ) {

		// Only when the user is capable
		if ( ! current_user_can( 'upload_records' ) && fct_admin_is_new_records() ) {
			$title .= sprintf( ' <a href="%s" class="add-new-h2">%s</a>', add_query_arg( array(
				'page' => 'fct-records',
				'mode' => 'upload',
			) ), esc_html__( 'Upload', 'fiscaat' ) );
		}

		return $title;
	}
}

endif; // class_exists

/**
 * Setup Fiscaat Records Upload
 *
 * @uses Fiscaat_Records_Upload
 */
function fct_admin_records_upload() {
	fiscaat()->admin->records->upload = new Fiscaat_Records_Upload();
}
