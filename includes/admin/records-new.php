<?php

/**
 * Fiscaat Records New Class
 *
 * @package Fiscaat
 * @subpackage Administration
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

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
			$post_type_object = get_post_type_object( fct_get_record_post_type() );

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

		// Redirect from record post-new.php
		add_action( 'fct_admin_head',             array( $this, 'remove_submenu' )     );
		add_action( 'fct_records_admin_load_new', array( $this, 'redirect'       ), -1 );

		// Page load hooks
		add_filter( 'fct_records_list_table_custom_query', array( $this, 'return_true'    ) );
		add_filter( 'fct_records_list_table_items',        array( $this, 'prepare_items'  ) );
		add_filter( $this->args->hook_prefix .'_submit',   array( $this, 'submit_records' ) );

		// Page head hooks
		add_action( $this->args->hook_prefix .'_head', array( $this, 'enqueue_page_scripts' ) );
		add_action( $this->args->hook_prefix .'_head', array( $this, 'page_head'            ) );

		// Table hooks
		add_filter( 'fct_records_list_table_class',          array( $this, 'list_table_class'    ) );
		add_action( 'fct_records_list_table_tablenav',       array( $this, 'add_num_rows_select' ) );
		add_filter( $this->args->hook_prefix .'_page_title', array( $this, 'import_button'       ) );

		// Column hooks
		add_action( 'fct_records_posts_columns', array( $this, 'remove_column_cb' ) );

		// Form hooks
		add_action( $this->args->hook_prefix .'_form_after', array( $this, 'new_default_row' ) );


		// New Records
		// add_filter( 'the_posts',                   array( $this, 'new_records_items'                 ), 9  );
		// add_action( 'fct_records_admin_load_edit', array( $this, 'new_records_insert_records'        )     );
		// add_action( 'admin_notices',               array( $this, 'new_records_display_admin_notice'  )     );

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
			'post_status'        => fct_get_public_status_id(),
			'post_type'          => fct_get_record_post_type(),
			'post_title'         => '',
			'post_content'       => '',
			'post_author'        => 0,
			'post_date'          => fct_current_time(),
			'menu_order'         => 0,

			// Record meta
			'offset_account'     => false,
			'fct_value'      => false,
			'fct_value_type' => false,
		) );

		// Return avail_post_stati
		return apply_filters( $this->args->hook_prefix .'_prepare_items', array() );
	}

	/**
	 * Append demo import button to page title
	 */
	public function import_button( $title ) {
		return $title .' <a href="#" class="add-new-h2">'. __('Import Records', 'fiscaat') .'</a>';
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
		wp_localize_script( 'fiscaat-records-new', 'fct_records_newL10n', array(
			'currency_format' => fct_the_currency_format( fct_get_currency() ),
			'currency'        => fct_get_currency( 'symbol' ), // Represents %s
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
				$('.record td.fct_record_value').not('#post--3 td.fct_record_value').find('input').removeAttr('disabled');
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

				if ( ! fct_insert_record( $record->data, $record->meta ) )
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
		if ( ! isset( $_POST['fct_new_record'] ) )
			return false;

		$fields  = $_POST['fct_new_record'];
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
							$v = fct_float_format( $v );
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
	 * @uses fct_float_format()
	 * @uses fct_get_debit_record_type_id()
	 * @uses fct_get_credit_record_type_id()
	 * @uses fct_insert_record()
	 * @uses fct_get_record_post_type()
	 * @uses wp_safe_redirect() To redirect the user
	 */
	public function new_records_insert_records() {

		// Bail
		if ( $this->bail() ) 
			return;

		// Bail if not submitted
		if ( ! isset( $_REQUEST['fct_insert_new_records_submit'] ) ) 
			return;

		// Bail if no records posted
		if ( ! isset( $_REQUEST['fct_new_record'] ) ) 
			return;

		// Setup records array
		$_records = array();

		// Rewrite input records as record => fields instead of field => records
		foreach ( (array) $_REQUEST['fct_new_record'] as $field => $records ) {
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
				$value      = fct_float_format( $record['debit'] );
				$value_type = fct_get_debit_record_type_id();
			} elseif ( $record['credit' ] ) {
				$value      = fct_float_format( $record['credit'] );
				$value_type = fct_get_credit_record_type_id();
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
				$records[] = fct_insert_record( 
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
			wp_safe_redirect( add_query_arg( array( 'post_type' => fct_get_record_post_type(), 'page' => 'new', 'message' => $message, 'failure' => $failure ), admin_url( 'edit.php' ) ) );

			// For good measure
			exit;
		}
	}

	/**
	 * Return required fields for new records
	 *
	 * @uses apply_filters() Calls 'fct_new_records_required_fields'
	 *                        with empty array
	 * @return array Required record fields
	 */
	public function new_records_required_fields( $type = 'fields' ) {

		// Hook for extra required fields
		$fields = apply_filters( 'fct_new_records_required_fields', array(), $type );
		$core   = array();

		// Required fields
		if ( 'fields' == $type )
			$core = array( 
				'ledger_id'   => _x('No.', 'Account number', 'fiscaat'),
				'account_id'  => __('Account',               'fiscaat'),
				'description' => __('Description',           'fiscaat'),
				'debit'       => __('Debit',                 'fiscaat'), // Debit/Credit ?
				'credit'      => __('Credit',                'fiscaat'), // Debit/Credit ?
			);

		// Required field names
		if ( 'names' == $type )
			$core = array(
				'fct_new_records[ledger_id][]',
				'fct_new_records[account_id][]',
				'fct_new_records[description][]',
				'fct_new_records[debit][]',
				'fct_new_records[credit][]',
			);

		return array_merge( $fields, $core );
	}

	/**
	 * Output admin message for New Records page
	 * 
	 * @uses self::new_records_updated_messages()
	 */
	public function new_records_display_admin_notice() {

		if ( $this->bail() ) return;

		// Only display if message available
		if ( ! isset( $_GET['message'] ) ) return;

		// Get vars
		$notice     = (int) $_GET['message'];
		$is_failure = ! empty( $_GET['failure'] ) ? true : false;
		$messages   = $this->updated_messages();

		// Output message
		if ( isset( $messages[$notice] ) ) {
			?>

			<div id="message" class="<?php echo $is_failure == true ? 'error' : 'updated'; ?> fade">
				<p style="line-height: 150%"><?php echo $messages[$notice]; ?></p>
			</div>

			<?php
		}
	}

	/**
	 * Return array of admin messages for New Records page
	 * 
	 * @return array Admin messages
	 */
	public function updated_messages() {

		// Setup messages array
		$messages = array(

			// Saved successfully
			1 => isset( $_GET['record_count'] )
				? sprintf( __('Records saved. %d new records were saved to the database.', 'fiscaat'), (int) $_GET['record_count'] )
				: __('Records saved.', 'fiscaat'),

			// Something went wrong
			2 => __('Something went wrong with saving the records. Please try again', 'fiscaat'),

			// No records to save
			3 => __('There were no records found to be saved. Make sure you fill at least two rows before submitting.', 'fiscaat'),

			// Missing required fields
			4 => sprintf( __('Values are missing. All new records require at least the following fields: %s.', 'fiscaat'), join( ', ', $this->new_records_required_fields() ) ),

			// Records approved
			5 => isset( $_GET['record_count'] )
				? sprintf( __('Records approved. %d records were approved.', 'fiscaat'), (int) $_GET['record_count'] )
				: __('Records approved.', 'fiscaat'),

			// Records declined
			6 => isset( $_GET['record_count'] )
				? sprintf( __('Records declined. %d records were declined.', 'fiscaat'), (int) $_GET['record_count'] )
				: __('Records declined.', 'fiscaat'),

			// Records closed
			7 => isset( $_GET['record_count'] )
				? sprintf( __('Records closed. %d records were closed.', 'fiscaat'), (int) $_GET['record_count'] )
				: __('Records closed.', 'fiscaat'),

			);

		return apply_filters( 'fct_new_records_updated_messages', $messages );
	}

}

endif; // class_exists check

/**
 * Setup Fiscaat Records New
 *
 * This is currently here to make hooking and unhooking of the admin UI easy.
 * It could use dependency injection in the future, but for now this is easier.
 *
 * @uses Fiscaat_Admin_Records_New
 */
function fct_admin_records_new() {
	fiscaat()->admin->records_new = new Fiscaat_Admin_Records_New();
}