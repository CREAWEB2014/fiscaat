<?php

/**
 * Fiscaat Admin Settings
 *
 * @package Fiscaat
 * @subpackage Administration
 *
 * @todo Use settings tabs
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** Sections ******************************************************************/

/**
 * Get the Fiscaat settings sections.
 *
 * @return array
 */
function fct_admin_get_settings_sections() {
	return (array) apply_filters( 'fct_admin_get_settings_sections', array(
		'fct_settings_features' => array(
			'title'    => __( 'Features', 'fiscaat' ),
			'callback' => 'fct_admin_setting_callback_features_section',
			'page'     => 'fiscaat',
		),
		'fct_settings_currency' => array(
			'title'    => __( 'Currency Settings', 'fiscaat' ),
			'callback' => 'fct_admin_setting_callback_currency_section',
			'page'     => 'fiscaat',
		),
		'fct_settings_editing' => array(
			'title'    => __( 'Editing', 'fiscaat' ),
			'callback' => 'fct_admin_setting_callback_editing_section',
			'page'     => 'fiscaat',
		),
		'fct_settings_accounts' => array(
			'title'    => __( 'Default Accounts', 'fiscaat' ),
			'callback' => 'fct_admin_setting_callback_accounts_section',
			'page'     => 'fiscaat',
		),
		'fct_settings_per_page' => array(
			'title'    => __( 'Per Page', 'fiscaat' ),
			'callback' => 'fct_admin_setting_callback_per_page_section',
			'page'     => 'fiscaat',
		),
		'fct_settings_root_slugs' => array(
			'title'    => __( 'Archive Slugs', 'fiscaat' ),
			'callback' => 'fct_admin_setting_callback_root_slugs_section',
			'page'     => 'fiscaat',
		),
		'fct_settings_single_slugs' => array(
			'title'    => __( 'Single Slugs', 'fiscaat' ),
			'callback' => 'fct_admin_setting_callback_single_slugs_section',
			'page'     => 'fiscaat',
		),
	) );
}

/**
 * Get all of the settings fields.
 *
 * @return array settings options
 */
function fct_admin_get_settings_fields() {
	return (array) apply_filters( 'fct_admin_get_settings_fields', array(

		/** Features Section **************************************************/

		'fct_settings_features' => array(

			// Enable control setting
			'_fct_enable_control' => array(
				'title'             => __( 'Control', 'fiscaat' ),
				'callback'          => 'fct_admin_setting_callback_enable_control',
				'sanitize_callback' => 'intval',
				'args'              => array()
			),

			// Enable comments setting
			'_fct_enable_comments' => array(
				'title'             => __( 'Comments', 'fiscaat' ),
				'callback'          => 'fct_admin_setting_callback_enable_comments',
				'sanitize_callback' => 'intval',
				'args'              => array()
			),

			// Enable comments setting
			'_fct_use_wp_editor' => array(
				'title'             => __( 'Use WordPress editor', 'fiscaat' ),
				'callback'          => 'fct_admin_setting_callback_use_wp_editor',
				'sanitize_callback' => 'intval',
				'args'              => array()
			),

		),

		/** Currency Section **************************************************/

		'fct_settings_currency' => array(

			// Currency setting
			'_fct_currency' => array(
				'title'             => __( 'Currency', 'fiscaat' ),
				'callback'          => 'fct_admin_setting_callback_currency',
				'sanitize_callback' => 'fct_sanitize_currency',
				'args'              => array()
			),

			// Currency position
			'_fct_currency_position' => array(
				'title'             => __( 'Currency Position', 'fiscaat' ),
				'callback'          => 'fct_admin_setting_callback_currency_position',
				'sanitize_callback' => 'fct_sanitize_currency_position',
				'args'              => array()
			),

			// Thousand separator
			'_fct_thousands_sep' => array(
				'title'             => __( 'Thousands Separator', 'fiscaat' ),
				'callback'          => 'fct_admin_setting_callback_thousands_sep',
				'sanitize_callback' => '',
				'args'              => array()
			),

			// Decimal separator
			'_fct_decimal_point' => array(
				'title'             => __( 'Decimal Point', 'fiscaat' ),
				'callback'          => 'fct_admin_setting_callback_decimal_point',
				'sanitize_callback' => '',
				'args'              => array()
			),

			// Number of decimals
			'_fct_num_decimals' => array(
				'title'             => __( 'Number of Decimals', 'fiscaat' ),
				'callback'          => 'fct_admin_setting_callback_num_decimals',
				'sanitize_callback' => 'intval',
				'args'              => array()
			),

		),

		/** Editing Section ***************************************************/

		'fct_settings_editing'/ => array(),

		/** Accounts Section **************************************************/

		'fct_settings_accounts' => array(

			// Main Bank Account
			'_fct_main_bank_account' => array(
				'title'             => __( 'Main Bank Account', 'fiscaat' ),
				'callback'          => 'fct_admin_setting_callback_main_bank_account',
				'sanitize_callback' => 'intval',
				'args'              => array()
			),

			// Second Bank Account
			'_fct_second_bank_account' => array(
				'title'             => __( 'Second Bank Account', 'fiscaat' ),
				'callback'          => 'fct_admin_setting_callback_second_bank_account',
				'sanitize_callback' => 'intval',
				'args'              => array()
			),

			// Third Bank Account
			'_fct_third_bank_account' => array(
				'title'             => __( 'Third Bank Account', 'fiscaat' ),
				'callback'          => 'fct_admin_setting_callback_third_bank_account',
				'sanitize_callback' => 'intval',
				'args'              => array()
			),

			// Balance Account
			'_fct_balance_ledger_id' => array(
				'title'             => __( 'Balance account', 'fiscaat' ),
				'callback'          => 'fct_admin_setting_callback_balance_ledger_id',
				'sanitize_callback' => 'intval',
				'args'              => array()
			),

			// Suspense Account
			'_fct_suspense_ledger_id' => array(
				'title'             => __( 'Suspense account', 'fiscaat' ),
				'callback'          => 'fct_admin_setting_callback_suspense_ledger_id',
				'sanitize_callback' => 'intval',
				'args'              => array()
			),

		),

		/** Per Page Section **************************************************/

		'fct_settings_per_page' => array(

			// Years per page setting
			'_fct_years_per_page' => array(
				'title'             => __( 'Years', 'fiscaat' ),
				'callback'          => 'fct_admin_setting_callback_years_per_page',
				'sanitize_callback' => 'intval',
				'args'              => array()
			),

			// Accounts per page setting
			'_fct_accounts_per_page' => array(
				'title'             => __( 'Accounts', 'fiscaat' ),
				'callback'          => 'fct_admin_setting_callback_accounts_per_page',
				'sanitize_callback' => 'intval',
				'args'              => array()
			),

			// Records per page setting
			'_fct_records_per_page' => array(
				'title'             => __( 'Records', 'fiscaat' ),
				'callback'          => 'fct_admin_setting_callback_records_per_page',
				'sanitize_callback' => 'intval',
				'args'              => array()
			)
		),

		/** Front Slugs *******************************************************/

		'fct_settings_root_slugs' => array(

			// Root slug setting
			'_fct_root_slug' => array(
				'title'             => __( 'Fiscaat base', 'fiscaat' ),
				'callback'          => 'fct_admin_setting_callback_root_slug',
				'sanitize_callback' => 'esc_sql',
				'args'              => array()
			),

			// Account archive setting
			'_fct_ledger_slug' => array(
				'title'             => __( 'Ledger base', 'fiscaat' ),
				'callback'          => 'fct_admin_setting_callback_ledger_slug',
				'sanitize_callback' => 'esc_sql',
				'args'              => array()
			)
		),

		/** Single Slugs ******************************************************/

		'fct_settings_single_slugs' => array(

			// Include root setting
			'_fct_include_root' => array(
				'title'             => __( 'Fiscaat Prefix', 'fiscaat' ),
				'callback'          => 'fct_admin_setting_callback_include_root',
				'sanitize_callback' => 'intval',
				'args'              => array()
			),

			// Year slug setting
			'_fct_year_slug' => array(
				'title'             => __( 'Year slug', 'fiscaat' ),
				'callback'          => 'fct_admin_setting_callback_year_slug',
				'sanitize_callback' => 'sanitize_title',
				'args'              => array()
			),

			// Account slug setting
			'_fct_account_slug' => array(
				'title'             => __( 'Account slug', 'fiscaat' ),
				'callback'          => 'fct_admin_setting_callback_account_slug',
				'sanitize_callback' => 'sanitize_title',
				'args'              => array()
			),

			// Record slug setting
			'_fct_record_slug' => array(
				'title'             => __( 'Record slug', 'fiscaat' ),
				'callback'          => 'fct_admin_setting_callback_record_slug',
				'sanitize_callback' => 'sanitize_title',
				'args'              => array()
			),
		),

	) );
}

/**
 * Get settings fields by section.
 *
 * @param string $section_id
 * @return mixed False if section is invalid, array of fields otherwise.
 */
function fct_admin_get_settings_fields_for_section( $section_id = '' ) {

	// Bail if section is empty
	if ( empty( $section_id ) )
		return false;

	$fields = fct_admin_get_settings_fields();
	$retval = isset( $fields[$section_id] ) ? $fields[$section_id] : false;

	return (array) apply_filters( 'fct_admin_get_settings_fields_for_section', $retval, $section_id );
}

/** Functionality Section *****************************************************/

/**
 * Main settings section description for the settings page
 */
function fct_admin_setting_callback_features_section() {
?>

	<p><?php _e( 'Main settings for enabling and disabling features.', 'fiscaat' ); ?></p>

<?php
}

/**
 * Enable control setting field
 *
 * @uses fct_form_option() To output the option value
 */
function fct_admin_setting_callback_enable_control() {
?>

	<input id="_fct_enable_control" name="_fct_enable_control" type="checkbox" value="1" <?php checked( fct_is_control_active() ); fct_maybe_admin_setting_disabled( '_fct_enable_control' ); ?> />
	<label for="_fct_enable_control"><?php _e( 'Enable controlling functions and the Controller role.', 'fiscaat' ); ?></label>

<?php
}

/**
 * Enable comments setting field
 *
 * @uses fct_form_option() To output the option value
 */
function fct_admin_setting_callback_enable_comments() {
?>

	<input id="_fct_enable_comments" name="_fct_enable_comments" type="checkbox" value="1" <?php checked( fct_is_comments_active() ); fct_maybe_admin_setting_disabled( '_fct_enable_comments' ); ?> />
	<label for="_fct_enable_comments"><?php _e( "Enable commenting on records in Fiscaat.", 'fiscaat' ); ?></label>

<?php
}

/**
 * Use WordPress editor setting field
 *
 * @uses fct_form_option() To output the option value
 */
function fct_admin_setting_callback_use_wp_editor() {
?>

	<input id="_fct_use_wp_editor" name="_fct_use_wp_editor" type="checkbox" value="1" <?php checked( fct_use_wp_editor() ); fct_maybe_admin_setting_disabled( '_fct_use_wp_editor' ); ?> />
	<label for="_fct_use_wp_editor"><?php _e( "Use the WordPress editor if available.", 'fiscaat' ); ?></label>

<?php
}

/** Currency Section **********************************************************/

/**
 * Currency settings section description for the settings page
 */
function fct_admin_setting_callback_currency_section() {
?>

	<p><?php _e( 'The following settings affect how amounts in Fiscaat are displayed on your site.', 'fiscaat' ); ?></p>

<?php
}

/**
 * Currency setting field
 *
 * @uses fct_currency_form() To output the option input
 * @uses fct_get_currency() To get the option value
 * @uses fct_maybe_admin_setting_disabled()
 */
function fct_admin_setting_callback_currency() {
	fct_currency_dropdown( array( 
		'select_id' => '_fct_currency',
		'selected'  => fct_get_currency(),
		'disabled'  => fct_maybe_admin_setting_disabled( '_fct_currency' )
	) );
}

/**
 * Currency position setting field
 *
 * @uses fct_get_form_option() To get the option value
 * @uses fct_get_amount() To get a currency value representation
 */
function fct_admin_setting_callback_currency_position() {
	$options = fct_get_currency_positions(); ?>

	<select id="_fct_currency_position" name="_fct_currency_position" <?php fct_maybe_admin_setting_disabled( '_fct_currency_position' ); ?>>

		<?php foreach ( $options as $position => $label ) :

			echo "<option value='$position' " . selected( fct_get_form_option( '_fct_currency_position' ), $position, false ) . ">$label (" . fct_get_currency_format( '99.99', $position ) . ")</option>";

		endforeach; ?>

	</select>
	<label for="_fct_currency_position"><?php _e( 'Select the position of the currency symbol.', 'fiscaat'); ?></label>

<?php
}

/**
 * Thousand separator setting field
 *
 * @uses fct_form_option() To get the option value
 */
function fct_admin_setting_callback_thousands_sep() {
	global $wp_locale; ?>

	<input name="_fct_thousands_sep" type="text" id="_fct_thousands_sep" value="<?php fct_form_option( '_fct_thousands_sep', $wp_locale->number_format['thousands_sep'] ); ?>" class="small-text" <?php fct_maybe_admin_setting_disabled( '_fct_thousands_sep' ); ?> />

<?php
}

/**
 * Decimal separator setting field
 *
 * @uses fct_form_option() To get the option value
 */
function fct_admin_setting_callback_decimal_point() {
	global $wp_locale; ?>

	<input name="_fct_decimal_point" type="text" id="_fct_decimal_point" value="<?php fct_form_option( '_fct_decimal_point', $wp_locale->number_format['decimal_point'] ); ?>" class="small-text" <?php fct_maybe_admin_setting_disabled( '_fct_decimal_point' ); ?> />

<?php
}

/**
 * Decimal separator setting field
 *
 * @uses fct_form_option() To get the option value
 */
function fct_admin_setting_callback_num_decimals() {
?>

	<input name="_fct_num_decimals" type="number" id="_fct_num_decimals" value="<?php fct_form_option( '_fct_num_decimals', '2' ); ?>" class="small-text" <?php fct_maybe_admin_setting_disabled( '_fct_num_decimals' ); ?> />

<?php
}

/** Editing Section ***********************************************************/

/**
 * Editing settings section description for the settings page
 */
function fct_admin_setting_callback_editing_section() {
?>

	<p><?php _e( 'Settings for creating and editing records in Fiscaat.', 'fiscaat' ); ?></p>

<?php
}

/** Accounts Section **********************************************************/

/**
 * Accounts settings section description for the settings page
 */
function fct_admin_setting_callback_accounts_section() {
?>

	<p><?php _e( 'Here you can connect the dots between this system and your real life bank acccounts. Fiscaat uses the ledger ids to recognize and set the right record accounts when importing banking data.', 'fiscaat' ); ?></p>

<?php
}

/**
 * Main bank account setting field
 *
 * @uses fct_form_option() To output the option value
 *
 * @todo Use fct_ledger_dropdown ?
 */
function fct_admin_setting_callback_main_bank_account() {
?>

	<input name="_fct_main_bank_account" type="text" id="_fct_main_bank_account" value="<?php fct_form_option( '_fct_main_bank_account', '' ); ?>" />
	<label for="_fct_main_bank_account"><?php _e( 'Account number', 'fiscaat' ); ?></label>

	<br/>

	<input name="_fct_main_bank_ledger_id" type="text" id="_fct_main_bank_ledger_id" value="<?php fct_form_option( '_fct_main_bank_ledger_id', 102 ); ?>" />
	<label for="_fct_main_bank_ledger_id"><?php _e( 'Fiscaat Ledger id', 'fiscaat' ); ?></label>

<?php
}

/**
 * Second bank account setting field
 *
 * @uses fct_form_option() To output the option value
 */
function fct_admin_setting_callback_second_bank_account() {
?>

	<input name="_fct_second_bank_account" type="text" id="_fct_second_bank_account" value="<?php fct_form_option( '_fct_second_bank_account', '' ); ?>" />
	<label for="_fct_second_bank_account"><?php _e( 'Account number', 'fiscaat' ); ?></label>

	<br/>

	<input name="_fct_second_bank_ledger_id" type="text" id="_fct_second_bank_ledger_id" value="<?php fct_form_option( '_fct_second_bank_ledger_id', '' ); ?>" />
	<label for="_fct_second_bank_ledger_id"><?php _e( 'Fiscaat Ledger id', 'fiscaat' ); ?></label>

<?php
}

/**
 * Third bank account setting field
 *
 * @uses fct_form_option() To output the option value
 */
function fct_admin_setting_callback_third_bank_account() {
?>

	<input name="_fct_third_bank_account" type="text" id="_fct_third_bank_account" value="<?php fct_form_option( '_fct_third_bank_account', '' ); ?>" />
	<label for="_fct_third_bank_account"><?php _e( 'Account number', 'fiscaat' ); ?></label>

	<br/>

	<input name="_fct_third_bank_ledger_id" type="text" id="_fct_third_bank_ledger_id" value="<?php fct_form_option( '_fct_third_bank_ledger_id', '' ); ?>" />
	<label for="_fct_third_bank_ledger_id"><?php _e( 'Fiscaat Ledger id', 'fiscaat' ); ?></label>

<?php
}

/**
 * Balance account setting field
 *
 * @uses get_option() To get the option value
 */
function fct_admin_setting_callback_balance_ledger_id() {
?>

	<input name="_fct_balance_ledger_id" type="text" id="_fct_balance_ledger_id" value="<?php fct_form_option( '_fct_balance_ledger_id', 199 ); ?>" />
	<label for="_fct_balance_ledger_id"><?php _e( 'Fiscaat Ledger id', 'fiscaat' ); ?></label>

<?php
}

/**
 * Suspense account setting field
 *
 * @uses get_option() To get the option value
 */
function fct_admin_setting_callback_suspense_ledger_id() {
?>

	<input name="_fct_suspense_ledger_id" type="text" id="_fct_suspense_ledger_id" value="<?php fct_form_option( '_fct_suspense_ledger_id', 999 ); ?>" />
	<label for="_fct_suspense_ledger_id"><?php _e( 'Fiscaat Ledger id', 'fiscaat' ); ?></label>

<?php
}

/** Per Page Section **********************************************************/

/**
 * Per page settings section description for the settings page
 */
function fct_admin_setting_callback_per_page_section() {
?>

	<p><?php _e( 'How many accounts and records to show per page', 'fiscaat' ); ?></p>

<?php
}

/**
 * Years per page setting field
 *
 * @uses fct_form_option() To output the option value
 */
function fct_admin_setting_callback_years_per_page() {
?>

	<input name="_fct_years_per_page" type="number" min="1" step="1" id="_fct_years_per_page" value="<?php fct_form_option( '_fct_years_per_page', '15' ); ?>" class="small-text" <?php fct_maybe_admin_setting_disabled( '_fct_years_per_page' ); ?> />
	<label for="_fct_years_per_page"><?php _e( 'per page', 'fiscaat' ); ?></label>

<?php
}

/**
 * Accounts per page setting field
 *
 * @uses fct_form_option() To output the option value
 */
function fct_admin_setting_callback_accounts_per_page() {
?>

	<input name="_fct_accounts_per_page" type="number" min="1" step="1" id="_fct_accounts_per_page" value="<?php fct_form_option( '_fct_accounts_per_page', '15' ); ?>" class="small-text" <?php fct_maybe_admin_setting_disabled( '_fct_accounts_per_page' ); ?> />
	<label for="_fct_accounts_per_page"><?php _e( 'per page', 'fiscaat' ); ?></label>

<?php
}

/**
 * Records per page setting field
 *
 * @uses fct_form_option() To output the option value
 */
function fct_admin_setting_callback_records_per_page() {
?>

	<input name="_fct_records_per_page" type="number" min="1" step="1" id="_fct_records_per_page" value="<?php fct_form_option( '_fct_records_per_page', '15' ); ?>" class="small-text" <?php fct_maybe_admin_setting_disabled( '_fct_records_per_page' ); ?> />
	<label for="_fct_records_per_page"><?php _e( 'per page', 'fiscaat' ); ?></label>

<?php
}

/** Slug Section **************************************************************/

/**
 * Slugs settings section description for the settings page
 */
function fct_admin_setting_callback_root_slugs_section() {

	// Flush rewrite rules when this section is saved
	if ( isset( $_GET['settings-updated'] ) && isset( $_GET['page'] ) )
		flush_rewrite_rules(); ?>

	<p><?php _e( 'Custom root slugs to prefix your years and accounts with. These can be partnered with WordPress pages to allow more flexibility.', 'fiscaat' ); ?></p>

<?php
}

/**
 * Root slug setting field
 *
 * @uses fct_form_option() To output the option value
 */
function fct_admin_setting_callback_root_slug() {
?>

		<input name="_fct_root_slug" type="text" id="_fct_root_slug" class="regular-text code" value="<?php fct_form_option( '_fct_root_slug', 'fiscaat', true ); ?>"<?php fct_maybe_admin_setting_disabled( '_fct_root_slug' ); ?> />

<?php
	// Slug Check
	fct_form_slug_conflict_check( '_fct_root_slug', 'fiscaat' );
}

/**
 * Ledger slug setting field
 *
 * @uses fct_form_option() To output the option value
 */
function fct_admin_setting_callback_ledger_slug() {
?>

	<input name="_fct_ledger_slug" type="text" id="_fct_ledger_slug" class="regular-text code" value="<?php fct_form_option( '_fct_ledger_slug', 'ledger', true ); ?>"<?php fct_maybe_admin_setting_disabled( '_fct_ledger_slug' ); ?> />

<?php
	// Slug Check
	fct_form_slug_conflict_check( '_fct_ledger_slug', 'ledger' );
}

/** Single Slugs **************************************************************/

/**
 * Slugs settings section description for the settings page
 */
function fct_admin_setting_callback_single_slugs_section() {
?>

	<p><?php printf( __( 'Custom slugs for single years, accounts and records here. If you change these, existing permalinks will also change.', 'fiscaat' ), get_admin_url( null, 'options-permalink.php' ) ); ?></p>

<?php
}

/**
 * Include root slug setting field
 *
 * @uses checked() To display the checked attribute
 */
function fct_admin_setting_callback_include_root() {
?>

	<input id="_fct_include_root" name="_fct_include_root" type="checkbox" id="_fct_include_root" value="1" <?php checked( get_option( '_fct_include_root', true ) ); fct_maybe_admin_setting_disabled( '_fct_include_root' ); ?> />
	<label for="_fct_include_root"><?php _e( 'Prefix your Fiscaat area with the Fiscaat Base slug (Recommended)', 'fiscaat' ); ?></label>

<?php
}

/**
 * Year slug setting field
 *
 * @uses fct_form_option() To output the option value
 */
function fct_admin_setting_callback_year_slug() {
?>

	<input name="_fct_year_slug" type="text" id="_fct_year_slug" class="regular-text code" value="<?php fct_form_option( '_fct_year_slug', 'year', true ); ?>"<?php fct_maybe_admin_setting_disabled( '_fct_year_slug' ); ?> />

<?php
	// Slug Check
	fct_form_slug_conflict_check( '_fct_year_slug', 'year' );
}

/**
 * Account slug setting field
 *
 * @uses fct_form_option() To output the option value
 */
function fct_admin_setting_callback_account_slug() {
?>

	<input name="_fct_account_slug" type="text" id="_fct_account_slug" class="regular-text code" value="<?php fct_form_option( '_fct_account_slug', 'account', true ); ?>"<?php fct_maybe_admin_setting_disabled( '_fct_account_slug' ); ?> />

<?php
	// Slug Check
	fct_form_slug_conflict_check( '_fct_account_slug', 'account' );
}

/**
 * Record slug setting field
 *
 * @uses fct_form_option() To output the option value
 */
function fct_admin_setting_callback_record_slug() {
?>

	<input name="_fct_record_slug" type="text" id="_fct_record_slug" class="regular-text code" value="<?php fct_form_option( '_fct_record_slug', 'record', true ); ?>"<?php fct_maybe_admin_setting_disabled( '_fct_record_slug' ); ?> />

<?php
	// Slug Check
	fct_form_slug_conflict_check( '_fct_record_slug', 'record' );
}

/** Settings Page *************************************************************/

/**
 * The main settings page
 *
 * @uses screen_icon() To display the screen icon
 * @uses settings_fields() To output the hidden fields for the form
 * @uses do_settings_sections() To output the settings sections
 */
function fct_admin_settings() {
?>

	<div class="wrap">

		<?php screen_icon(); ?>

		<h2><?php _e( 'Fiscaat Settings', 'fiscaat' ) ?></h2>

		<form action="options.php" method="post">

			<?php settings_fields( 'fiscaat' ); ?>

			<?php do_settings_sections( 'fiscaat' ); ?>

			<p class="submit">
				<input type="submit" name="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'fiscaat' ); ?>" />
			</p>
		</form>
	</div>

<?php
}

/** Converter Section *********************************************************/

/**
 * Main settings section description for the settings page
 */
function fct_converter_setting_callback_main_section() {
?>

	<p><?php _e( 'Information about your previous years database so that they can be converted. <strong>Backup your database before proceeding.</strong>', 'fiscaat' ); ?></p>

<?php
}

/**
 * Edit Platform setting field
 *
 * @since Fiscaat (r3813)
 */
function fct_converter_setting_callback_platform() {

	$platform_options = '';
	$curdir           = opendir( fiscaat()->admin->admin_dir . 'converters/' );

	// Bail if no directory was found (how did this happen?)
	if ( empty( $curdir ) )
		return;

	// Loop through files in the converters folder and assemble some options
	while ( $file = readdir( $curdir ) ) {
		if ( ( stristr( $file, '.php' ) ) && ( stristr( $file, 'index' ) === false ) ) {
			$file              = preg_replace( '/.php/', '', $file );
			$platform_options .= '<option value="' . $file . '">' . $file . '</option>';
		}
	}

	closedir( $curdir ); ?>

	<select name="_fct_converter_platform" id="_fct_converter_platform" /><?php echo $platform_options ?></select>
	<label for="_fct_converter_platform"><?php _e( 'is the previous year software', 'fiscaat' ); ?></label>

<?php
}

/**
 * Edit Database Server setting field
 *
 * @since Fiscaat (r3813)
 */
function fct_converter_setting_callback_dbserver() {
?>

	<input name="_fct_converter_db_server" type="text" id="_fct_converter_db_server" value="<?php fct_form_option( '_fct_converter_db_server', 'localhost' ); ?>" class="medium-text" />
	<label for="_fct_converter_db_server"><?php _e( 'IP or hostname', 'fiscaat' ); ?></label>

<?php
}

/**
 * Edit Database Server Port setting field
 *
 * @since Fiscaat (r3813)
 */
function fct_converter_setting_callback_dbport() {
?>

	<input name="_fct_converter_db_port" type="text" id="_fct_converter_db_port" value="<?php fct_form_option( '_fct_converter_db_port', '3306' ); ?>" class="small-text" />
	<label for="_fct_converter_db_port"><?php _e( 'Use default 3306 if unsure', 'fiscaat' ); ?></label>

<?php
}

/**
 * Edit Database User setting field
 *
 * @since Fiscaat (r3813)
 */
function fct_converter_setting_callback_dbuser() {
?>

	<input name="_fct_converter_db_user" type="text" id="_fct_converter_db_user" value="<?php fct_form_option( '_fct_converter_db_user' ); ?>" class="medium-text" />
	<label for="_fct_converter_db_user"><?php _e( 'User for your database connection', 'fiscaat' ); ?></label>

<?php
}

/**
 * Edit Database Pass setting field
 *
 * @since Fiscaat (r3813)
 */
function fct_converter_setting_callback_dbpass() {
?>

	<input name="_fct_converter_db_pass" type="password" id="_fct_converter_db_pass" value="<?php fct_form_option( '_fct_converter_db_pass' ); ?>" class="medium-text" />
	<label for="_fct_converter_db_pass"><?php _e( 'Password to access the database', 'fiscaat' ); ?></label>

<?php
}

/**
 * Edit Database Name setting field
 *
 * @since Fiscaat (r3813)
 */
function fct_converter_setting_callback_dbname() {
?>

	<input name="_fct_converter_db_name" type="text" id="_fct_converter_db_name" value="<?php fct_form_option( '_fct_converter_db_name' ); ?>" class="medium-text" />
	<label for="_fct_converter_db_name"><?php _e( 'Name of the database with your old year data', 'fiscaat' ); ?></label>

<?php
}

/**
 * Main settings section description for the settings page
 *
 * @since Fiscaat (r3813)
 */
function fct_converter_setting_callback_options_section() {
?>

	<p><?php _e( 'Some optional parameters to help tune the conversion process.', 'fiscaat' ); ?></p>

<?php
}

/**
 * Edit Table Prefix setting field
 *
 * @since Fiscaat (r3813)
 */
function fct_converter_setting_callback_dbprefix() {
?>

	<input name="_fct_converter_db_prefix" type="text" id="_fct_converter_db_prefix" value="<?php fct_form_option( '_fct_converter_db_prefix' ); ?>" class="medium-text" />
	<label for="_fct_converter_db_prefix"><?php _e( '(If converting from BuddyPress Years, use "wp_bb_" or your custom prefix)', 'fiscaat' ); ?></label>

<?php
}

/**
 * Edit Rows Limit setting field
 *
 * @since Fiscaat (r3813)
 */
function fct_converter_setting_callback_rows() {
?>

	<input name="_fct_converter_rows" type="text" id="_fct_converter_rows" value="<?php fct_form_option( '_fct_converter_rows', '100' ); ?>" class="small-text" />
	<label for="_fct_converter_rows"><?php _e( 'rows to process at a time', 'fiscaat' ); ?></label>
	<p class="description"><?php _e( 'Keep this low if you experience out-of-memory issues.', 'fiscaat' ); ?></p>

<?php
}

/**
 * Edit Delay Time setting field
 *
 * @since Fiscaat (r3813)
 */
function fct_converter_setting_callback_delay_time() {
?>

	<input name="_fct_converter_delay_time" type="text" id="_fct_converter_delay_time" value="<?php fct_form_option( '_fct_converter_delay_time', '1' ); ?>" class="small-text" />
	<label for="_fct_converter_delay_time"><?php _e( 'second(s) delay between each group of rows', 'fiscaat' ); ?></label>
	<p class="description"><?php _e( 'Keep this high to prevent too-many-connection issues.', 'fiscaat' ); ?></p>

<?php
}

/**
 * Edit Restart setting field
 *
 * @since Fiscaat (r3813)
 */
function fct_converter_setting_callback_restart() {
?>

	<input id="_fct_converter_restart" name="_fct_converter_restart" type="checkbox" id="_fct_converter_restart" value="1" <?php checked( get_option( '_fct_converter_restart', false ) ); ?> />
	<label for="_fct_converter_restart"><?php _e( 'Start a fresh conversion from the beginning', 'fiscaat' ); ?></label>
	<p class="description"><?php _e( 'You should clean old conversion information before starting over.', 'fiscaat' ); ?></p>

<?php
}

/**
 * Edit Clean setting field
 *
 * @since Fiscaat (r3813)
 */
function fct_converter_setting_callback_clean() {
?>

	<input id="_fct_converter_clean" name="_fct_converter_clean" type="checkbox" id="_fct_converter_clean" value="1" <?php checked( get_option( '_fct_converter_clean', false ) ); ?> />
	<label for="_fct_converter_clean"><?php _e( 'Purge all information from a previously attempted import', 'fiscaat' ); ?></label>
	<p class="description"><?php _e( 'Use this if an import failed and you want to remove that incomplete data.', 'fiscaat' ); ?></p>

<?php
}

/**
 * Edit Convert Users setting field
 *
 * @since Fiscaat (r3813)
 */
function fct_converter_setting_callback_convert_users() {
?>

	<input id="_fct_converter_convert_users" name="_fct_converter_convert_users" type="checkbox" id="_fct_converter_convert_users" value="1" <?php checked( get_option( '_fct_converter_convert_users', false ) ); ?> />
	<label for="_fct_converter_convert_users"><?php _e( 'Attempt to import user accounts from previous years', 'fiscaat' ); ?></label>
	<p class="description"><?php _e( 'Non-Fiscaat passwords cannot be automatically converted. They will be converted as each user logs in.', 'fiscaat' ); ?></p>

<?php
}

/** Converter Page ************************************************************/

/**
 * The main settings page
 *
 * @uses screen_icon() To display the screen icon
 * @uses settings_fields() To output the hidden fields for the form
 * @uses do_settings_sections() To output the settings sections
 */
function fct_converter_settings() {
?>

	<div class="wrap">

		<?php screen_icon( 'tools' ); ?>

		<h2 class="nav-tab-wrapper"><?php fct_tools_admin_tabs( __( 'Import Years', 'fiscaat' ) ); ?></h2>

		<form action="#" method="post" id="fct-converter-settings">

			<?php settings_fields( 'fct_converter' ); ?>

			<?php do_settings_sections( 'fct_converter' ); ?>

			<p class="submit">
				<input type="button" name="submit" class="button-primary" id="fct-converter-start" value="<?php esc_attr_e( 'Start', 'fiscaat' ); ?>" onclick="bbconverter_start()" />
				<input type="button" name="submit" class="button-primary" id="fct-converter-stop" value="<?php esc_attr_e( 'Stop', 'fiscaat' ); ?>" onclick="bbconverter_stop()" />
				<img id="fct-converter-progress" src="">
			</p>

			<div class="fct-converter-updated" id="fct-converter-message"></div>
		</form>
	</div>

<?php
}

/** Helpers *******************************************************************/

/**
 * Contextual help for Years settings page
 *
 * @since Fiscaat (r3119)
 * @uses get_current_screen()
 */
function fct_admin_settings_help() {

	$current_screen = get_current_screen();

	// Bail if current screen could not be found
	if ( empty( $current_screen ) )
		return;

	// Overview
	$current_screen->add_help_tab( array(
		'id'      => 'overview',
		'title'   => __( 'Overview', 'fiscaat' ),
		'content' => '<p>' . __( 'This screen provides access to all of the Years settings.',                          'fiscaat' ) . '</p>' .
					 '<p>' . __( 'Please see the additional help tabs for more information on each indiviual section.', 'fiscaat' ) . '</p>'
	) );

	// Main Settings
	$current_screen->add_help_tab( array(
		'id'      => 'main_settings',
		'title'   => __( 'Main Settings', 'fiscaat' ),
		'content' => '<p>' . __( 'In the Main Settings you have a number of options:', 'fiscaat' ) . '</p>' .
					 '<p>' .
						'<ul>' .
							'<li>' . __( 'You can choose to lock a post after a certain number of minutes. "Locking post editing" will prevent the author from editing some amount of time after saving a post.',              'fiscaat' ) . '</li>' .
							'<li>' . __( '"Throttle time" is the amount of time required between posts from a single author. The higher the throttle time, the longer a user will need to wait between posting to the year.', 'fiscaat' ) . '</li>' .
							'<li>' . __( 'Favorites are a way for users to save and later return to accounts they favor. This is enabled by default.',                                                                           'fiscaat' ) . '</li>' .
							'<li>' . __( 'Subscriptions allow users to subscribe for notifications to accounts that interest them. This is enabled by default.',                                                                 'fiscaat' ) . '</li>' .
							'<li>' . __( 'Account-Tags allow users to filter accounts between years. This is enabled by default.',                                                                                                'fiscaat' ) . '</li>' .
							'<li>' . __( '"Anonymous Posting" allows guest users who do not have accounts on your site to both create accounts as well as records.',                                                             'fiscaat' ) . '</li>' .
							'<li>' . __( 'The Fancy Editor brings the luxury of the Visual editor and HTML editor from the traditional WordPress dashboard into your theme.',                                                  'fiscaat' ) . '</li>' .
							'<li>' . __( 'Auto-embed will embed the media content from a URL directly into the records. For example: links to Flickr and YouTube.',                                                            'fiscaat' ) . '</li>' .
						'</ul>' .
					'</p>' .
					'<p>' . __( 'You must click the Save Changes button at the bottom of the screen for new settings to take effect.', 'fiscaat' ) . '</p>'
	) );

	// Per Page
	$current_screen->add_help_tab( array(
		'id'      => 'per_page',
		'title'   => __( 'Per Page', 'fiscaat' ),
		'content' => '<p>' . __( 'Per Page settings allow you to control the number of accounts and records appear on each page.',                                                    'fiscaat' ) . '</p>' .
					 '<p>' . __( 'This is comparable to the WordPress "Reading Settings" page, where you can set the number of posts that should show on blog pages and in feeds.', 'fiscaat' ) . '</p>' .
					 '<p>' . __( 'These are broken up into two separate groups: one for what appears in your theme, another for RSS feeds.',                                        'fiscaat' ) . '</p>'
	) );

	// Slugs
	$current_screen->add_help_tab( array(
		'id'      => 'slus',
		'title'   => __( 'Slugs', 'fiscaat' ),
		'content' => '<p>' . __( 'The Slugs section allows you to control the permalink structure for your years.',                                                                                                            'fiscaat' ) . '</p>' .
					 '<p>' . __( '"Archive Slugs" are used as the "root" for your years and accounts. If you combine these values with existing page slugs, Fiscaat will attempt to output the most correct title and content.', 'fiscaat' ) . '</p>' .
					 '<p>' . __( '"Single Slugs" are used as a prefix when viewing an individual year, account, record, user, or view.',                                                                                          'fiscaat' ) . '</p>' .
					 '<p>' . __( 'In the event of a slug collision with WordPress or BuddyPress, a warning will appear next to the problem slug(s).', 'fiscaat' ) . '</p>'
	) );

	// Help Sidebar
	$current_screen->set_help_sidebar(
		'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
		'<p>' . __( '<a href="http://codex.fiscaat.org" target="_blank">Fiscaat Documentation</a>',    'fiscaat' ) . '</p>' .
		'<p>' . __( '<a href="http://fiscaat.org/years/" target="_blank">Fiscaat Support Years</a>', 'fiscaat' ) . '</p>'
	);
}

/**
 * Disable a settings field if the value is forcibly set in Fiscaat's global
 * options array.
 *
 * @param string $option_key
 */
function fct_maybe_admin_setting_disabled( $option_key = '' ) {
	disabled( isset( fiscaat()->options[$option_key] ) );
}

/**
 * Output settings API option
 *
 * @uses fct_get_fct_form_option()
 *
 * @param string $option
 * @param string $default
 * @param bool $slug
 */
function fct_form_option( $option, $default = '' , $slug = false ) {
	echo fct_get_form_option( $option, $default, $slug );
}
	/**
	 * Return settings API option
	 *
	 * @uses get_option()
	 * @uses esc_attr()
	 * @uses apply_filters()
	 *
	 * @param string $option
	 * @param string $default
	 * @param bool $slug
	 */
	function fct_get_form_option( $option, $default = '', $slug = false ) {

		// Get the option and sanitize it
		$value = get_option( $option, $default );

		// Slug?
		if ( true === $slug ) {
			$value = esc_attr( apply_filters( 'editable_slug', $value ) );

		// Not a slug
		} else {
			$value = esc_attr( $value );
		}

		// Fallback to default
		if ( empty( $value ) )
			$value = $default;

		// Allow plugins to further filter the output
		return apply_filters( 'fct_get_form_option', $value, $option );
	}

/**
 * Used to check if a Fiscaat slug conflicts with an existing known slug.
 *
 * @param string $slug
 * @param string $default
 *
 * @uses fct_get_form_option() To get a sanitized slug string
 */
function fct_form_slug_conflict_check( $slug, $default ) {

	// Only set the slugs once ver page load
	static $the_core_slugs = array();

	// Get the form value
	$this_slug = fct_get_form_option( $slug, $default, true );

	if ( empty( $the_core_slugs ) ) {

		// Slugs to check
		$core_slugs = apply_filters( 'fct_slug_conflict_check', array(

			/** WordPress Core ****************************************************/

			// Core Post Types
			'post_base'       => array( 'name' => __( 'Posts',         'fiscaat' ), 'default' => 'post',          'context' => 'WordPress' ),
			'page_base'       => array( 'name' => __( 'Pages',         'fiscaat' ), 'default' => 'page',          'context' => 'WordPress' ),
			'revision_base'   => array( 'name' => __( 'Revisions',     'fiscaat' ), 'default' => 'revision',      'context' => 'WordPress' ),
			'attachment_base' => array( 'name' => __( 'Attachments',   'fiscaat' ), 'default' => 'attachment',    'context' => 'WordPress' ),
			'nav_menu_base'   => array( 'name' => __( 'Menus',         'fiscaat' ), 'default' => 'nav_menu_item', 'context' => 'WordPress' ),

			// Post Tags
			'tag_base'        => array( 'name' => __( 'Tag base',      'fiscaat' ), 'default' => 'tag',           'context' => 'WordPress' ),

			// Post Categories
			'category_base'   => array( 'name' => __( 'Category base', 'fiscaat' ), 'default' => 'category',      'context' => 'WordPress' ),

			/** Fiscaat Core ******************************************************/

			// Year archive slug
			'_fct_root_slug'         => array( 'name' => __( 'Fiscaat base', 'fiscaat' ), 'default' => 'fiscaat', 'context' => 'Fiscaat' ),

			// Year slug
			'_fct_year_slug'         => array( 'name' => __( 'Year slug',    'fiscaat' ), 'default' => 'year',    'context' => 'Fiscaat' ),

			// Account slug
			'_fct_account_slug'      => array( 'name' => __( 'Account slug', 'fiscaat' ), 'default' => 'account', 'context' => 'Fiscaat' ),

			// Record slug
			'_fct_record_slug'       => array( 'name' => __( 'Record slug',  'fiscaat' ), 'default' => 'record',  'context' => 'Fiscaat' ),

		) );

		/** BuddyPress Core *******************************************************/

		if ( defined( 'BP_VERSION' ) ) {
			$bp = buddypress();

			// Loop through root slugs and check for conflict
			if ( ! empty( $bp->pages ) ) {
				foreach ( $bp->pages as $page => $page_data ) {
					$page_base    = $page . '_base';
					$page_title   = sprintf( __( '%s page', 'fiscaat' ), $page_data->title );
					$core_slugs[$page_base] = array( 'name' => $page_title, 'default' => $page_data->slug, 'context' => 'BuddyPress' );
				}
			}
		}

		// Set the static
		$the_core_slugs = apply_filters( 'fct_slug_conflict', $core_slugs );
	}

	// Loop through slugs to check
	foreach ( $the_core_slugs as $key => $value ) {

		// Get the slug
		$slug_check = fct_get_form_option( $key, $value['default'], true );

		// Compare
		if ( ( $slug != $key ) && ( $slug_check == $this_slug ) ) : ?>

			<span class="attention"><?php printf( __( 'Possible %1$s conflict: <strong>%2$s</strong>', 'fiscaat' ), $value['context'], $value['name'] ); ?></span>

		<?php endif;
	}
}
