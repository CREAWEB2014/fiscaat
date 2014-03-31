<?php

/**
 * Fiscaat Users Admin Class
 *
 * @package Fiscaat
 * @subpackage Administration
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'Fiscaat_Users_Admin' ) ) :

/**
 * Loads Fiscaat users admin area
 *
 * @package Fiscaat
 * @subpackage Administration
 */
class Fiscaat_Users_Admin {

	/**
	 * The Fiscaat users admin loader
	 *
	 * @uses Fiscaat_Users_Admin::setup_globals() Setup the globals needed
	 * @uses Fiscaat_Users_Admin::setup_actions() Setup the hooks and actions
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
	 */
	function setup_actions() {

		// Bail if in network admin
		if ( is_network_admin() )
			return;

		// User profile edit/display actions
		add_action( 'edit_user_profile', array( $this, 'fiscaat_user_options' ) );
		add_action( 'show_user_profile', array( $this, 'fiscaat_user_options' ) );

		// WordPress user screen
		add_filter( 'manage_users_columns',                     array( $this, 'user_role_column'        )        );
		add_filter( 'manage_users_custom_column',               array( $this, 'user_role_row'           ), 10, 3 );
		add_filter( 'get_user_option_manageuserscolumnshidden', array( $this, 'user_role_column_hidden' )        );

	}

	/**
	 * Default interface for setting a year role
	 *
	 * @param WP_User $profileuser User data
	 * @return bool Always false
	 */
	public static function fiscaat_user_options( $profileuser ) {

		// Bail if current user cannot edit users
		if ( ! current_user_can( 'edit_user', $profileuser->ID ) || ! current_user_can( 'promote_users' ) )
			return;

		?><h3><?php _e( 'Fiscaat', 'fiscaat' ); ?></h3>

		<table class="form-table">
			<tbody>
				<tr>
					<th><label for="fiscaat-role"><?php _e( 'Fiscaat Role', 'fiscaat' ); ?></label></th>
					<td>

						<?php $dynamic_roles = fiscaat_get_dynamic_roles(); ?>

						<?php $user_role = fiscaat_get_user_role( $profileuser->ID ); ?>

						<select name="fiscaat-role" id="fiscaat-role">

							<?php if ( ! empty( $user_role ) ) : ?>

								<option value=""><?php _e( '&mdash; No role for Fiscaat &mdash;', 'fiscaat' ); ?></option>

							<?php else : ?>

								<option value="" selected="selected"><?php _e( '&mdash; No role for Fiscaat &mdash;', 'fiscaat' ); ?></option>

							<?php endif; ?>

							<?php foreach ( $dynamic_roles as $role => $details ) : ?>

								<option <?php selected( $user_role, $role ); ?> value="<?php echo esc_attr( $role ); ?>"><?php echo translate_user_role( $details['name'] ); ?></option>

							<?php endforeach; ?>

						</select>
					</td>
				</tr>

				<tr>
					<th><label for="fisaat-global-spectator"><?php _e( 'Global Spectator', 'fisaat' ); ?></label></th>
					<td>

						<?php $disable = user_can( $profileuser->ID, 'fiscaat' ) || user_can( $profileuser->ID, 'control' ); ?>

						<?php wp_nonce_field( 'fiscaat_global_spectator', 'fiscaat_global_spectator_nonce' ); ?>

						<input name="fiscaat-global-spectator" id="fiscaat-global-spectator" type="checkbox" value="1" <?php checked( fiscaat_user_can_view_all( $profileuser->ID ) ); ?> <?php disabled( $disable ); ?> />
						<label for="fiscaat-global-spectator"><?php _e('Give this user the ability to view all Fiscaat data.', 'fiscaat'); ?></label>

						<?php if ( $disable ) : ?>

							<p class="description"><?php _e( "This user already can view everything based on it's capabilities.", 'fiscaat' ); ?></p>

						<?php endif; ?>
					</td>
				</tr>

				<tr>
					<th><label for="fisaat-block-commenter"><?php _e( 'Block Commenter', 'fisaat' ); ?></label></th>
					<td>

						<?php wp_nonce_field( 'fiscaat_block_commenter', 'fiscaat_block_commenter_nonce' ); ?>

						<input name="fiscaat-block-commenter" id="fiscaat-block-commenter" type="checkbox" value="1" <?php checked( ! fiscaat_user_can_comment( $profileuser->ID ) ); ?> />
						<label for="fiscaat-block-commenter"><?php _e('Block this user from commenting in Fiscaat.', 'fiscaat'); ?></label>

					</td>
				</tr>

				<?php do_action( 'fiscaat_user_options_after', $profileuser ); ?>

			</tbody>
		</table>

		<?php
	}

	/**
	 * Add Fiscaat Role column to the WordPress Users table, and change the
	 * core role title to "Site Role"
	 *
	 * @param array $columns Users table columns
	 * @return array $columns
	 */
	public static function user_role_column( $columns = array() ) {
		$columns['role']              = __( 'Site Role',    'fiscaat' );
		$columns['fiscaat_user_role'] = __( 'Fiscaat Role', 'fiscaat' );

		return $columns;
	}

	/**
	 * Make Fiscaat Role column hidden by default
	 *
	 * @param array $hidden Users table hidden columns
	 * @return array $hidden
	 */
	public static function user_role_column_hidden( $hidden ) {
		$hidden[] = 'fiscaat_user_role';

		return $hidden;
	}

	/**
	 * Return user's years role for display in the WordPress Users list table
	 *
	 * @param string $retval
	 * @param string $column_name
	 * @param int $user_id
	 *
	 * @return string Displayable Fiscaat user role
	 */
	public static function user_role_row( $retval = '', $column_name = '', $user_id = 0 ) {

		// Only looking for Fiscaat's user role column
		if ( 'fiscaat_user_role' == $column_name ) {

			// Get the users role
			$user_role = fiscaat_get_user_role( $user_id );
			$retval    = false;

			// Translate user role for display
			if ( ! empty( $user_role ) ) {
				$roles  = fiscaat_get_dynamic_roles();
				$retval = translate_user_role( $roles[$user_role]['name'] );
			}
		}

		// Pass retval through
		return $retval;
	}
}

new Fiscaat_Users_Admin();

endif; // class exists
