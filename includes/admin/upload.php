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
	 */
	public function __construct() {
		// Do stuff
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
