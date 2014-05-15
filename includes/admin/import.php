<?php

/**
 * Fiscaat Import Records Functions
 *
 * @package Fiscaat
 * @subpackage Administration
 *
 * @todo Support many banking APIs/formats:
 *  - CSV
 *  - OFX http://en.wikipedia.org/wiki/Open_Financial_Exchange
 *  - QIF http://en.wikipedia.org/wiki/Quicken_Interchange_Format
 *  - OFC http://en.wikipedia.org/wiki/Open_Financial_Connectivity -> obsolete
 *  - SFC Incassoos
 *  - MT940 ING
 *  - see http://stackoverflow.com/questions/3469628/banking-api-protocol
 *  - Open Bank Project API https://github.com/OpenBankProject/OBP-API/wiki
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Fiscaat_Records_Importer' ) ) :

/**
 * Loads Fiscaat import functions
 *
 * @package Fiscaat
 * @subpackage Administration
 */
class Fiscaat_Records_Importer {

	/** Functions *************************************************************/

	/**
	 * The main Fiscaat admin loader
	 *
	 * @uses Fiscaat_Records_Importer::setup_globals() Setup the globals needed
	 * @uses Fiscaat_Records_Importer::setup_actions() Setup the hooks and actions
	 */
	public function __construct() {
		$this->setup_actions();
	}

	/**
	 * Setup the admin hooks, actions and filters
	 *
	 * @access private
	 *
	 * @uses add_action() To add various actions
	 * @uses add_filter() To add various filters
	 */
	private function setup_actions() {

		// Import button
		add_action( 'fct_before_new_records_list_table', array( $this, 'import_button'   ) );

		// Import modal
		add_action( 'fct_admin_head',    array( $this, 'admin_head'      ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'fct_admin_footer',  array( $this, 'import_modal'    ) );

		// Handle import file
		add_action( 'wp_ajax_fct_records_import_process', array( $this, 'import_process' ) );

	}

	/**
	 * Should we bail out of this method?
	 *
	 * @return boolean
	 */
	private function bail() {
		$retval = true;

		if ( fiscaat()->admin->records->new_records() )
			$retval = false;

		return apply_filters( 'fct_import_bail', $retval );
	}

	/**
	 * Output import button on New Records page
	 * 
	 * @uses add_thickbox() To activate thickbox
	 * @uses fct_import_modal() To output the import modal
	 */
	public function import_button() {

		// Make sure thickbox is loaded
		add_thickbox();

		// Output import button at bulk actions position and hook thickbox to HTML button element
		// For some reason <a> element responds as expected to thickbox. <input> or <button> does not.
		echo '<a id="fct_records_import_button" class="thickbox button button-primary" name="'. __('Import Records', 'fiscaat') .'" href="#TB_inline?height=150&amp;width=300&amp;inlineId=fct_import_modal" tabindex="'. fct_get_tab_index() .'" >'. __('Import', 'fiscaat') .'</a>';
	}

	/**
	 * Admin scripts
	 */
	public function admin_head() { 

		if ( $this->bail() ) return; ?>

		<style type="text/css" media="screen">
			/*<![CDATA[*/

			#fct_import_records > p {
				float: left;
				width: 100%;
				margin: 2px 0;
			}

			div#fct_import_message p {
				margin: 0.5em 0;
				padding: 2px;
				float: left;
				clear: left;
			}

			div#fct_import_message p.loading {
				padding: 2px 20px 2px 2px;
				background-image: url('<?php echo admin_url(); ?>images/wpspin_light.gif');
				background-repeat: no-repeat;
				background-position: center right;
			}

			#fct_import_stop {
				display: none;
			}

			/*]]>*/
		</style>

	<?php
	}

	/**
	 * Enqueue any import scripts we might need
	 */
	public function enqueue_scripts() {

		if ( $this->bail() ) return;

		// Ajax form handling
		wp_enqueue_script( 'jquery-form' );

		// Import script
		wp_register_script( 'fiscaat-import', fiscaat()->admin->admin_url . 'scripts/fiscaat-import.js', array( 'jquery', 'jquery-form' ) );
		wp_enqueue_script( 'fiscaat-import' );
		wp_localize_script( 'fiscaat-import', 'fct_importL10n', array( 
			'uploading' => __('Uploading file', 'fiscaat'),
			'error'     => __('Import failed. Please try again.', 'fiscaat'),
			'complete'  => __('Conversion Complete', 'fiscaat'),
			'redirect'  => __('Importing Records', 'fiscaat'),
		) );
	}

	/**
	 * Output the import records modal
	 */
	public function import_modal(){

		if ( $this->bail() ) return;

	?>
		<div id="fct_import_modal" style="display: none;">
			<div id="fct_records_import_wrapper">

				<form id="fct_import_records" action="<?php echo admin_url( 'admin-ajax.php' ); ?>" method="post" enctype="multipart/form-data">
					<?php wp_nonce_field( 'fct_record_importer', 'fct_record_importer_nonce' ); ?>

					<p class="type">
						<strong><?php _e('Import file type', 'fiscaat'); ?></strong><br/>
						<select name="_fct_import_file_type" id="_fct_import_file_type">

							<option value="-1"><?php _e('Select file type', 'fiscaat'); ?></option>

						<?php foreach ( fct_records_import_filetypes() as $type => $attrs ) : ?>

							<option value="<?php echo esc_attr( $type ); ?>"><?php echo $attrs['label']; ?></option>

						<?php endforeach; ?>

						</select>
					</p>
					
					<p class="file">
						<strong><?php _e('Select file', 'fiscaat'); ?></strong><br/>
						<input type="file" name="_fct_import_file" />
					</p>

					<?php do_action( 'fct_import_records_form' ); ?>
					
					<p class="submit">
						<?php wp_nonce_field( 'fct_records_import_process', '_ajax_nonce' ); ?>
						<input type="hidden" name="action" value="fct_records_import_process" />
						<input type="hidden" name="fct_import_restart" id="fct_import_restart" value="1" />
						<?php submit_button( __('Start Import', 'fiscaat'), 'secondary', 'fct_import_start', false ); ?>
						<?php submit_button( __('Stop Import',  'fiscaat'), 'secondary', 'fct_import_stop',  false ); // why? ?>
					</p>

					<div id="fct_import_message"></div>

				</form>

			</div>
		</div>
	<?php
	}

	/**
	 * Wrap the import output in paragraph tags, so styling can be applied
	 *
	 * @param string $output
	 */
	private static function import_output( $output = '', $class = '' ) {

		// Get class
		if ( ! empty( $class ) )
			$class = ' '. esc_attr( $class );

		// Setup output vars
		$before = '<p class="loading'. $class .'">';
		$after  = '</p>';

		echo $before . $output . $after;
	}

	/**
	 * Import processor
	 */
	public function import_process() {

		// Verify intent
		check_ajax_referer( 'fct_records_import_process' );

		// Get step from db, else restart at 1
		$step = ! (bool) $_POST['fct_import_restart'] ? (int) get_option( '_fct_importer_step', 1 ) : 1;

		// Bail if file type is not valid
		$file_type = fct_records_import_get_filetype( ! empty( $_POST['_fct_import_file_type' ] ) ? $_POST['_fct_import_file_type' ] : '' );
		if ( empty( $file_type ) )
			return $this->import_output( __('No import file type selected.', 'fiscaat'), 'error' );

		switch ( $step ) {

			// STEP 1. Verify file
			case 1 :
				if ( $this->verify_file( $file_type ) ) {
					update_option( '_fct_importer_step', $step + 1 );
					$this->import_output( __('Converting records', 'fiscaat') ); // Anticipate next step
				} else {
					delete_option( '_fct_importer_step' );
				}

				break;

			// STEP 2. Convert records
			case 2 :
				if ( $records = $this->convert_records( $file_type ) ) {
					update_option( '_fct_importer_step', $step + 1 );
					update_option( '_fct_importer_records', $records );
					$this->import_output( __('Conversion Complete', 'fiscaat') );
				} else {
					delete_option( '_fct_importer_step' );
					$this->import_output( __('Failed converting records.', 'fiscaat'), 'error' );
				}

				break;

			// STEP 3. Redirect
			case 3 :
				if ( $records = get_option( '_fct_importer_records' ) ) {
					delete_option( '_fct_importer_step' );
					delete_option( '_fct_importer_records' );

					// Redirect to New Records page
					wp_safe_redirect( add_query_arg( array( 'records' => urlencode_deep( $records ), 'fiscaat' => 'create-new', 'post_type' => fct_get_record_post_type() ), admin_url( 'edit.php' ) ) );

					// Good measure
					exit;
				} else {
					delete_option( '_fct_importer_step' );
					$this->import_output( __('Converted records were lost.', 'fiscaat'), 'error' );
				}

				break;

			default :
				delete_option( '_fct_importer_step' );
				delete_option( '_fct_importer_records' );

				break;
		}
	}

	/**
	 * Verify submitted file before import
	 * 
	 * @return bool Verification result
	 */
	public function verify_file( $file_type = '' ) {

		// Check file upload
		if ( ! isset( $_FILES['_fct_import_file'] ) ) {
			$this->import_output( __('The upload form is corrupted.', 'error'), 'fiscaat' );
			return false;

		// Setup local vars
		} else {
			$_file   = $_FILES['_fct_import_file'];
			$message = '';
		}

		// Something went wrong
		if ( $_file['error'] != UPLOAD_ERR_OK || $_file['size'] == 0 ) {

			// Error messages
			$errors = array(
				0 => __('File uploaded successfully.', 'fiscaat'),
				1 => __('The uploaded file exceeds the upload_max_filesize directive in php.ini.', 'fiscaat'),
				2 => __('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.', 'fiscaat'),
				3 => __('The uploaded file was only partially uploaded.', 'fiscaat'),
				4 => __('No file was uploaded.', 'fiscaat'),
				6 => __('Missing a temporary folder.', 'fiscaat'),
			);

			// Output error message
			if ( isset( $errors[$_file['error']] ) )
				$message = $errors[$_file['error']];
			else
				$message = __('An unknown error occured during file upload.', 'fiscaat');

		// Check file type
		} else {

			// Mimetype mismatch
			if ( ! in_array( $_file['type'], $file_type['mimetypes'] ) ) {
				$message = __('The uploaded file mimetype is not allowed.', 'fiscaat');

			// Extension mismatch
			} elseif ( substr( strrchr( $_file['name'], '.' ), 1 ) != $file_type['extension'] ) {
				$message = __('The uploaded file does not match the submitted file type.', 'fiscaat');
			} 
		}

		// Output any errors
		if ( ! empty( $message ) ) {
			$this->import_output( $message, 'error' );
			return false;
		}

		// Hook verification
		return apply_filters( 'fct_records_import_verify_file', true );
	}

	/**
	 * Send file to importer to convert records
	 *
	 * @uses apply_filters() Calls 'fct_import_records_convert_records' with the
	 *                        converted records, the file type, and file contents
	 */
	public function convert_records( $file_type ) {

		// Get file contents
		$_file = file_get_contents( $_FILES['_fct_import_file']['tmp_name'] );

		// Get import converter
		$importer = fct_records_import_converter( $file_type );

		// Do file conversion
		$records = $importer->convert( $_file );

		return apply_filters( 'fct_import_records_convert_records', $records, $file_type, $_file );
	}
}

endif; // class_exists check

function fct_records_import_converter( $file_type = '' ) {
	return false;
}

/**
 * Return the allowed records import filetype
 * 
 * @param string $type Type name
 * @uses fct_records_import_filetypes()
 * @return array Filetype
 */
function fct_records_import_get_filetype( $type = '' ) {
	$types = fct_records_import_filetypes();
	return isset( $types[$type] ) ? $types[$type] : array();
}

/**
 * Return array of record import methods
 *
 * @uses apply_filters() Calls 'fct_records_import_filetypes' with
 *                        the import methods
 */
function fct_records_import_filetypes() {

	// Create types array as extension => attributes
	$types = array(

		// CSV
		'csv' => array(
			'label'     => __('Comma separated', 'fiscaat'),
			'extension' => 'csv',
			'mimetypes' => array(
				'text/comma-separated-values',
				'text/csv',
				'application/csv',
				'application/excel',
				'application/vnd.ms-excel',
				'application/vnd.msexcel',
				'text/anytext',
				),
			'callback'  => 'fct_records_import_csv',
		),

		// MT940
		'mt940' => array(
			'label'     => __('MT940 banking', 'fiscaat'),
			'extension' => '940',
			'mimetypes' => array(
				'application/octet-stream' // Unknown type
				),
			'callback'  => 'fct_records_import_940',
		),

		// Incassoos
		'incassoos' => array(
			'label'     => __('Incassoos', 'fiscaat'),
			'extension' => 'sfc',
			'mimetypes' => array(
				'application/octet-stream' // Unknown type
				),
			'callback'  => 'fct_records_import_sfc',
		),
	);

	return apply_filters( 'fct_records_import_filetypes', $types );
}
