<?php

/**
 * Fiscaat Records New Class
 *
 * @package Fiscaat
 * @subpackage Administration
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'Fiscaat_Admin_Records_New' ) ) :

/**
 * Loads Fiscaat new record admin area
 *
 * @package Fiscaat
 * @subpackage Administration
 */
class Fiscaat_Admin_Records_New extends Fiscaat_Admin_Records {

	/** Functions *************************************************************/

	/**
	 * The main Fiscaat admin loader
	 *
	 * @uses Fiscaat_Admin_Records_New::setup_globals() Setup the globals needed
	 * @uses Fiscaat_Admin_Records_New::setup_actions() Setup the hooks and actions
	 */
	public function __construct() {
		global $post_type_object;

		if ( empty( $post_type_object ) )
			$post_type_object = get_post_type_object( fiscaat_get_record_post_type() );

		parent::__construct( array(
			'page'       => 'new',
			'menu_title' => $post_type_object->labels->add_new,
			'page_title' => $post_type_object->labels->add_new,
			'cap'        => $post_type_object->cap->publish_posts
		) );
	}

	/**
	 * Setup the admin hooks, actions and filters
	 *
	 * @uses add_action() To add various actions
	 * @uses add_filter() To add various filters
	 */
	public function setup_actions() {

		// Redirect record post-new.php
		add_action( 'fiscaat_admin_head', array( $this, 'remove_submenu' )     );
		add_action( 'load-post-new.php',  array( $this, 'redirect'       ), -1 );

		// Page load hooks
		add_filter( 'fiscaat_records_list_table_custom_query', array( $this, 'return_true'    ) );
		add_filter( 'fiscaat_records_list_table_items',        array( $this, 'prepare_items'  ) );
		add_filter( $this->args->hook_prefix .'_submit',       array( $this, 'submit_records' ) );

		// Page head hooks
		add_action( $this->args->hook_prefix .'_head', array( $this, 'enqueue_page_scripts' ) );
		add_action( $this->args->hook_prefix .'_head', array( $this, 'page_head'            ) );

		// Table hooks
		add_filter( 'fiscaat_records_list_table_class',        array( $this, 'list_table_class'    ) );
		add_action( 'fiscaat_records_list_table_tablenav',     array( $this, 'add_num_rows_select' ) );
		add_action( $this->args->hook_prefix .'_title_append', array( $this, 'import_button'       ) );

		// Column hooks
		add_action( 'fiscaat_records_posts_columns', array( $this, 'remove_column_cb' ) );

		// Form hooks
		add_action( $this->args->hook_prefix .'_form_after',   array( $this, 'new_default_row' ) );


		// New Records
		// add_filter( 'the_posts',             array( $this, 'new_records_items'                 ), 9  );
		// add_action( 'load-edit.php',         array( $this, 'new_records_insert_records'        )     );
		// add_action( 'admin_notices',         array( $this, 'new_records_display_admin_notice'  )     );

		// Messages
		// add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );
	}

	/**
	 * Remove submenu post-new.php page
	 */
	public function remove_submenu() {
		remove_submenu_page( 'edit.php?post_type='. $this->post_type, 'post-new.php?post_type='. $this->post_type );
	}

	/**
	 * Redirect users from post-new.php to new record page
	 */
	public function redirect() {

		// Bail
		if ( $this->bail( false ) ) 
			return;

		// Safely redirect
		wp_safe_redirect( add_query_arg( array( 'post_type' => $this->post_type, 'page' => $this->args->page ), admin_url( 'edit.php' ) ) );
		exit;
	}

	/**
	 * Contextual help
	 */
	public function help() {
		get_current_screen()->add_help_tab( array(
		'id'		=> 'overview',
		'title'		=> __('Overview'),
		'content'	=>
			'<p>' . __('Records hold the key value information of your ledger.') . '</p>'
		) );
		get_current_screen()->add_help_tab( array(
		'id'		=> 'managing-records',
		'title'		=> __('Creating Records'),
		'content'	=>
			'<p>' . __('Creating records is very similar to managing posts, and the screens can be customized in the same way.') . '</p>' .
			'<p>' . __('You can also perform the same types of actions, including narrowing the list by using the filters, acting on a page using the action links that appear when you hover over a row, or using the Bulk Actions menu to edit the metadata for multiple pages at once.') . '</p>'
		) );

		get_current_screen()->set_help_sidebar(
		'<p><strong>' . __('For more information:') . '</strong></p>' .
		'<p>' . __('<a href="http://www.vgsr.nl/plugins/fiscaat/" target="_blank">Documentation on Creating Records</a>') . '</p>' .
		'<p>' . __('<a href="http://www.vgsr.nl/forum/fiscaat/" target="_blank">Support Forums</a>') . '</p>'
		);
	}

	/**
	 * Generate new empty list table items
	 */
	public function prepare_items() {
		global $wp_query;

		// Bail
		if ( $this->bail() ) 
			return;

		// Fill $wp_query
		$wp_query->post_count = $wp_query->found_posts = apply_filters( $this->args->hook_prefix .'_post_count', 8 );
		$wp_query->max_num_pages = 1;
		$wp_query->posts = array_fill( 0, $wp_query->post_count, (object) array(
			'ID'                 => 0,
			'post_parent'        => 0,
			'post_status'        => fiscaat_get_public_status_id(),
			'post_type'          => fiscaat_get_record_post_type(),
			'post_title'         => '',
			'post_content'       => '',
			'post_author'        => 0,
			'post_date'          => fiscaat_get_current_time(),
			'menu_order'         => 0,

			// Record meta
			'offset_account'     => false,
			'fiscaat_value'      => false,
			'fiscaat_value_type' => false,
		) );

		// Return avail_post_stati
		return apply_filters( $this->args->hook_prefix .'_prepare_items', array() );
	}

	/**
	 * Output demo import button
	 */
	public function import_button() {
	?>
		<a href="#" class="add-new-h2"><?php _e('Import Records', 'fiscaat'); ?></a>
	<?php
	}

	/**
	 * Remove checkbox column
	 */
	public function remove_column_cb( $columns ) {
		
		// Bail
		if ( $this->bail() ) 
			return $columns;

		unset( $columns['cb'] );
		return $columns;
	}

	/**
	 * Output add num rows input section
	 */
	public function add_num_rows_select( $which ) {
		
		// Bail
		if ( $this->bail() ) 
			return;

		// Only on table top
		if ( 'top' != $which && ! is_singular() ) : ?>

			<label for="num-rows">
				<?php printf( __('Add %s rows', 'fiscaat'), 
				'<input type="number" name="num-rows" id="num-rows" class="small-text" step="1" min="1" value="1" />' ); ?>
			</label>
			<?php submit_button( __('Add', 'fiscaat'), 'secondary', false, false, array( 'id' => 'add-num-rows' ) ); ?>

		<?php endif;
	}

	/**
	 * Output hidden new default row
	 * 
	 * Wraps a <tr> in a hidden <table>
	 */
	public function new_default_row() {
		global $wp_query;

		// Bail
		if ( $this->bail() ) 
			return; ?>

			<table id="new-default-container" style="display:none;">
				<?php $this->list_table->single_row( $wp_query->posts[0] ); ?>
			</table>
			
		<?php
	}

	/**
	 * Enqueue admin specific scripts
	 */
	public function enqueue_page_scripts() {

		// Bail
		if ( $this->bail() ) 
			return;

		// Enqueue & localize New Records script
		wp_register_script( 'fiscaat-records-new', fiscaat()->admin->admin_url . 'scripts/fiscaat-records-new.js', array( 'jquery', 'livequery', 'format-currency' ) );
		wp_enqueue_script ( 'fiscaat-records-new' );
		wp_localize_script( 'fiscaat-records-new', 'fiscaat_records_newL10n', array(
			'currency_format' => fiscaat_the_currency_format( fiscaat_get_currency() ),
			'currency'        => fiscaat_get_currency( 'symbol' ), // Represents %s
			'positive'        => '%n', // Default is %s%n
			'negative'        => '&ndash; %n', // Default is (%s%n)

			'required_fields' => $this->new_records_required_fields( 'names' )
		) );
	}

	/**
	 * Output scripts and styles
	 */
	public function page_head() {
		?>
		<script type="text/javascript">
			jQuery(document).ready( function($){
				// Enable inputs on doc ready
				$('.record td.fiscaat_record_value').not('#post--3 td.fiscaat_record_value').find('input').removeAttr('disabled');
			});
		</script>
		<?php
	}

	/**
	 * Handle submit POST action and return redirect location
	 */
	public function submit_records( $loc ) {

		// Do stuff.
		// @ parent class?

		$records = $this->fetch_records();

		if ( $records ) {
			$loc = add_query_arg( array( 'records' => count( $records ) ), $loc );

			// Register insert errors
			$error = 0;
			foreach ( $records as $r ) {
				$record = (object) array(
					'data' => array(), // Default post data
					'meta' => array()  // Other post meta
					);

				if ( ! fiscaat_insert_record( $record->data, $record->meta ) )
					$error++;
			}
			
			// Set query args
			if ( $error > 0 )
				$loc = add_query_arg( array( 'message' => 1, 'error' => $error ), $loc );
			else 
				$loc = add_query_arg( array( 'message' => 2 ), $loc );

		} else {
			$loc = add_query_arg( array( 'message' => 1 ), $loc );
		}

		return $loc;
	}

	public function fetch_records() {

		// Bail if no records submitted
		if ( ! isset( $_POST['fiscaat_new_record'] ) )
			return false;

		$fields  = $_POST['fiscaat_new_record'];
		$records = $error = array();
		
		// Setup records
		foreach ( $fields as $field => $values ) {
			foreach ( $values as $r => $value ) {
				$records[$r][$field] = $value;
			}
		}

		// Handle fields
		foreach ( $records as $record => $fields ) {
			$error[$record] = array();

			foreach ( array_keys( $fields ) as $field ) {
				$v =& $records[$record][$field];

				switch ( $field ) {

					// Require account ids
					case 'ledger_id'  :
					case 'account_id' :
						if ( empty( $v ) )
							$error[$record][] = $field;
						break;

					// Require description
					case 'description' :
						if ( empty( $v ) )
							$error[$record][] = $field;
						// else
							// $v = 
						break;

					case 'offset_account' :
						// $v = 
						break;

					// Require debit or credit
					case 'debit'  :
					case 'credit' :
						// Require debit or credit
						$other = 'credit' == $field ? 'debit' : 'credit';
						if ( empty( $v ) && empty( $records[$record][$other] ) )
							$error[$record][] = $field;
						elseif ( ! empty( $v ) && is_numeric( $v ) )
							$v = fiscaat_float_format( $v );
						else
							$v = '';
						break;

					default :
						// do_action( $this->args->hook_prefix .'_fetch_field', &$v, $field, $records, &$error );
						break;
				}
			}

			// Handle required fields
			$req_fields = apply_filters( $this->args->hook_prefix .'_fetch_required_fields', array(
				'ledger_id', 'account_id', 'description', 'debit', 'credit'
				) );
			if ( array() == array_diff( $req_fields, $error[$record] ) )
				unset( $records[$record] );
			elseif ( empty( $error[$record] ) )
				unset( $error[$record] );
		}

		var_dump( $records ); exit;

		if ( $error ) {
			// Do stuff
		}

		return false; //$records;
	}

	/**
	 * Insert new records on edit page load
	 *
	 * @uses self::new_records_required_fields()
	 * @uses fiscaat_float_format()
	 * @uses fiscaat_get_debit_record_type()
	 * @uses fiscaat_get_credit_record_type()
	 * @uses fiscaat_insert_record()
	 * @uses fiscaat_get_record_post_type()
	 * @uses wp_safe_redirect() To redirect the user
	 */
	public function new_records_insert_records() {

		// Bail
		if ( $this->bail() ) 
			return;

		// Bail if not submitted
		if ( ! isset( $_REQUEST['fiscaat_insert_new_records_submit'] ) ) 
			return;

		// Bail if no records posted
		if ( ! isset( $_REQUEST['fiscaat_new_record'] ) ) 
			return;

		// Setup records array
		$_records = array();

		// Rewrite input records as record => fields instead of field => records
		foreach ( (array) $_REQUEST['fiscaat_new_record'] as $field => $records ) {
			foreach ( $records as $k => $value ) {
				$_records[$k][$field] = $value;
			}
		}

		// Get required fields
		$required_fields = $this->new_records_required_fields();
		$required_count  = count( $required_fields );

		// Check for empty required fields
		foreach ( $required_fields as $field => $list ) {

			// Setup fail list
			$list = array();

			// Loop required records per field
			foreach ( $_records as $k => $record ) {

				// Check each record field
				switch ( $field ) {

					// Special treat debit/credit
					case 'debit'  :
					case 'credit' :
						$other = 'debit' == $field ? 'credit' : 'debit';

						// Register fail if both empty or both filled
						if ( ( 
							   ( ! isset( $record[$field] ) || empty( $record[$field] ) )
							&& ( ! isset( $record[$other] ) || empty( $record[$other] ) )
							) || ( 
							   ( isset( $record[$field] ) && ! empty( $record[$field] ) )
							&& ( isset( $record[$other] ) && ! empty( $record[$other] ) )
							) ) {
							$record[$field] = false;
							$list[] = $k;
						}
						break;

					// Default to register when just empty
					default :
						if ( ! isset( $record[$field] ) || empty( $record[$field] ) ) {	
							$record[$field] = false;
							$list[] = $k;
						}
						break;
				}

				// Reset sanitized record
				$_records[$k] = $record;
			}

			// Handle fail list
			if ( count( $list ) )
				$required_fields[$field] = $list;
			else
				unset( $required_fields[$field] );
		}

		// Remove complete empty items from records
		foreach ( $_records as $k => $record ) {
			$missing = 0;

			// Walk all required fields
			foreach ( $required_fields as $field => $v )
				if ( empty( $record[$field] ) ) $missing++;

			// Record lacks all required fields, thus is empty
			if ( $missing == $required_count )
				unset( $_records[$k] );
		}

		// Setup record value and record value type
		foreach ( $_records as $k => $record ) {
			
			// Handle types
			if ( $record['debit'] ) {
				$value      = fiscaat_float_format( $record['debit'] );
				$value_type = fiscaat_get_debit_record_type();
			} elseif ( $record['credit' ] ) {
				$value      = fiscaat_float_format( $record['credit'] );
				$value_type = fiscaat_get_credit_record_type();
			} else {
				$value      = false;
				$value_type = false;
			}

			// Store vars
			$_records[$k]['value']      = $value;
			$_records[$k]['value_type'] = $value_type;
			unset( $_records[$k]['debit'], $_records[$k]['credit'] );
		}

		// Redirect when requirements are missing
		if ( count( $required_fields ) && ! empty( $_records ) ) {

			// Redirect. Send missing fields along with all record data
			wp_safe_redirect( add_query_arg( array( 'message' => 4, 'failure' => 1, 'redo' => 1, 'records' => urlencode_deep( $_records ), 'fiscaat' => 'create-new' ), wp_get_referer() ) );

			// For good measure
			exit;

		// Ready to get insertin'!
		} else {

			// Setup local variable
			$records = array();

			// Insert new record
			foreach ( $_records as $k => $record ) {

				// Save record
				$records[] = fiscaat_insert_record( 
					array(
						'post_parent'    => (int) $record['account_id'],
						'post_content'   => $record['description']
					),
					array( 
						'account_id'     => (int) $record['account_id'],
						'offset_account' => $record['offset_account'],
						'value'          => $record['value'],
						'value_type'     => $record['value_type']
					) );
			}

			// Assume no failure
			$failure = 0;

			// Return fail message
			if ( in_array( false, $records ) ) {
				$message = 2;
				$failure = 1;

			// No records to save
			} elseif ( empty( $records ) && empty( $_records ) ) {
				$message = 3;
				$failure = 1;

			// Success message
			} else {
				$message = 1;
			}

			// Redirect to clean New Records page
			wp_safe_redirect( add_query_arg( array( 'post_type' => fiscaat_get_record_post_type(), 'page' => 'new', 'message' => $message, 'failure' => $failure ), admin_url( 'edit.php' ) ) );

			// For good measure
			exit;
		}
	}

	/**
	 * Return required fields for new records
	 *
	 * @uses apply_filters() Calls 'fiscaat_new_records_required_fields'
	 *                        with empty array
	 * @ret