<?php

/**
 * Fiscaat Records Edit Class
 *
 * @package Fiscaat
 * @subpackage Administration
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Fiscaat_Admin_Records_Edit' ) ) :

/**
 * Loads Fiscaat new record admin area
 *
 * @package Fiscaat
 * @subpackage Administration
 */
class Fiscaat_Admin_Records_Edit extends Fiscaat_Admin_Records {

	/** Functions *************************************************************/

	/**
	 * The main Fiscaat admin loader
	 *
	 * @uses Fiscaat_Admin_Records_Edit::setup_globals() Setup the globals needed
	 * @uses Fiscaat_Admin_Records_Edit::setup_actions() Setup the hooks and actions
	 */
	public function __construct() {
		global $post_type_object;

		if ( empty( $post_type_object ) )
			$post_type_object = get_post_type_object( fiscaat_get_record_post_type() );

		parent::__construct( array(
			'page'       => 'edit',
			'menu_title' => $post_type_object->labels->edit_items,
			'page_title' => $post_type_object->labels->edit_items,
			'cap'        => $post_type_object->cap->edit_posts
			) );
	}

	/**
	 * Setup the admin hooks, actions and filters
	 *
	 * @access public
	 *
	 * @uses add_action() To add various actions
	 * @uses add_filter() To add various filters
	 */
	public function setup_actions() {

		// Page load hooks

		// Page head hooks
		
		// Form hooks
		add_action( $this->args->hook_prefix .'_form_top', array( $this, 'form_top' ) );

		// Table hooks
		add_filter( 'fiscaat_records_list_table_class',    array( $this, 'list_table_class' ) );
		add_action( 'fiscaat_records_list_table_tablenav', array( $this, 'extra_tablenav'   ) );

		// Column hooks
		add_action( 'fiscaat_records_posts_columns', array( $this, 'remove_column_cb' ) );

		// Messages
		// add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );

		// Check if there are any fiscaat_toggle_record_* requests on admin_init, also have a message displayed
		// add_action( 'load-edit.php',  array( $this, 'toggle_record'        ) );
		// add_action( 'admin_notices',  array( $this, 'toggle_record_notice' ) );

		// Fiscaat requires
		// add_action( 'load-edit.php',     array( $this, 'requires' ) );

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
		'title'		=> __('Editing Records'),
		'content'	=>
			'<p>' . __('Editing records is very similar to managing posts, and the screens can be customized in the same way.') . '</p>' .
			'<p>' . __('You can also perform the same types of actions, including narrowing the list by using the filters, acting on a page using the action links that appear when you hover over a row, or using the Bulk Actions menu to edit the metadata for multiple pages at once.') . '</p>'
		) );

		get_current_screen()->set_help_sidebar(
		'<p><strong>' . __('For more information:') . '</strong></p>' .
		'<p>' . __('<a href="http://www.vgsr.nl/plugins/fiscaat/" target="_blank">Documentation on Editing Records</a>') . '</p>' .
		'<p>' . __('<a href="http://www.vgsr.nl/forum/fiscaat/" target="_blank">Support Forums</a>') . '</p>'
		);
	}

	/**
	 * Output search box at form top
	 */
	public function form_top() {
		global $post_type_object;

		// Displays only if has_items()
		$this->list_table->search_box( $post_type_object->labels->search_items, 'post' );
	}

	/**
	 * Output list table extra tablenav
	 */
	public function extra_tablenav( $which ) {

		if ( $this->bail() ) return;

		if ( 'top' == $which && !is_singular() ) {

			$this->list_table->months_dropdown( $this->post_type );

			do_action( 'restrict_manage_posts' );
			submit_button( __( 'Filter' ), 'secondary', 'post-query-submit', false, array( 'id' => 'post-query-submit' ) );
		}
	}

	/**
	 * Remove checkbox column
	 */
	public function remove_column_cb( $columns ) {
		if ( $this->bail() ) return $columns;

		unset( $columns['cb'] );
		return $columns;
	}

	/**
	 * Show empty records on the new records page
	 * 
	 * @param array $items Found items
	 * @return array Create new empty records
	 */
	public function new_records_items( $items ) {

		if ( $this->bail() ) return $items;

		// Clean items array
		$items = array();

		// Setup default empty record
		$default_record = array(
			'ID'                  => 0,
			'post_parent'         => 0,
			'post_status'         => fiscaat_get_public_status_id(),
			'post_type'           => fiscaat_get_record_post_type(),
			'post_title'          => '',
			'post_content'        => '',
			'post_author'         => 0,
			'post_date'           => 0,
			'menu_order'          => 0,

			// Record meta
			'offset_account'      => false,
			'fiscaat_value'       => false,
			'fiscaat_value_type'  => false,
			);

		// Is this a redo ?
		$redo = isset( $_REQUEST['redo'] ) && 1 == $_REQUEST['redo'] && isset( $_REQUEST['records'] );

		// Show previously inserted records for a redo
		if ( $redo ) {

			// Retreive sent data and loop records
			foreach ( $_REQUEST['records'] as $k => $record ) {
				$args = apply_filters( 'fisacat_redo_new_records_items_args', array(
					'ID'                  => $k,
					'post_parent'         => $record['account_id'],
					'post_content'        => $record['description'],
					'offset_account'      => $record['offset_account'],
					'fiscaat_value'       => $record['value'],
					'fiscaat_value_type'  => $record['value_type']
				) );

				// Add redo record data to items
				$items[] = (object) fiscaat_parse_args( $args, $default_record, 'redo_new_record' );
			}

		// Default to some empty rows
		} else {

			// Create array with empty records
			$items = array_fill( 0, 4, (object) $default_record ); // get_option( '_fiscaat_records_per_page', 15 ), (object) $default_record );
		}

		return apply_filters( 'fiscaat_new_records_items', $items, $redo );
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

		if ( $this->bail() ) return;

		// Bail if not submitted
		if ( ! isset( $_REQUEST['fiscaat_insert_new_records_submit'] ) ) return;

		// Bail if no records posted
		if ( ! isset( $_REQUEST['fiscaat_new_record'] ) ) return;

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
	 * @return array Required record fields
	 */
	public function new_records_required_fields( $type = 'fields' ) {

		// Hook for extra required fields
		$fields = apply_filters( 'fiscaat_new_records_required_fields', array(), $type );
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
				'fiscaat_new_records[ledger_id][]',
				'fiscaat_new_records[account_id][]',
				'fiscaat_new_records[description][]',
				'fiscaat_new_records[debit][]',
				'fiscaat_new_records[credit][]',
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
		$messages   = $this->new_records_updated_messages();

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

		return apply_filters( 'fiscaat_new_records_updated_messages', $messages );
	}

}

endif; // class_exists check

/**
 * Setup Fiscaat Records Edit
 *
 * This is currently here to make hooking and unhooking of the admin UI easy.
 * It could use dependency injection in the future, but for now this is easier.
 *
 * @uses Fiscaat_Admin_Records_Edit
 */
function fiscaat_admin_records_edit() {
	fiscaat()->admin->records_edit = new Fiscaat_Admin_Records_Edit();
}
