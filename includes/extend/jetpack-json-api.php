<?php
/**
* Fiscaat & Jetpack REST API Compatibility
* Enables Fiscaat to work with the Jetpack REST API
*
* @link http://plugins.trac.wordpress.org/browser/jetpack/trunk/class.jetpack-bbpress-json-api-compat.php
*/
class Fiscaat_Jetpack_REST_API {

	private static $instance;

	public static function instance() {
		if ( isset( self::$instance ) )
			return self::$instance;

		self::$instance = new self;
	}

	private function __construct() {
		add_filter( 'rest_api_allowed_post_types', array( $this, 'allow_fiscaat_post_types' ) );
		add_filter( 'fiscaat_map_meta_caps', array( $this, 'adjust_meta_caps' ), 10, 4 );
		add_filter( 'rest_api_allowed_public_metadata', array( $this, 'allow_fiscaat_public_metadata' ) );
	}

	function allow_fiscaat_post_types( $allowed_post_types ) {

		// only run for REST API requests
		if ( ! defined( 'REST_API_REQUEST' ) || ! REST_API_REQUEST )
			return $allowed_post_types;

		$allowed_post_types[] = 'year';
		$allowed_post_types[] = 'account';
		$allowed_post_types[] = 'record';
		return $allowed_post_types;
	}

	function allow_fiscaat_public_metadata( $allowed_meta_keys ) {

		// only run for REST API requests
		if ( ! defined( 'REST_API_REQUEST' ) || ! REST_API_REQUEST )
			return $allowed_meta_keys;

		$allowed_meta_keys[] = '_fiscaat_year_id';
		$allowed_meta_keys[] = '_fiscaat_account_id';
		$allowed_meta_keys[] = '_fiscaat_status';
		$allowed_meta_keys[] = '_fiscaat_year_type';
		$allowed_meta_keys[] = '_fiscaat_record_count';
		$allowed_meta_keys[] = '_fiscaat_total_record_count';
		$allowed_meta_keys[] = '_fiscaat_account_count';
		$allowed_meta_keys[] = '_fiscaat_total_account_count';
		$allowed_meta_keys[] = '_fiscaat_account_count_hidden';
		$allowed_meta_keys[] = '_fiscaat_last_account_id';
		$allowed_meta_keys[] = '_fiscaat_last_record_id';
		$allowed_meta_keys[] = '_fiscaat_last_active_time';
		$allowed_meta_keys[] = '_fiscaat_last_active_id';
		$allowed_meta_keys[] = '_fiscaat_voice_count';
		$allowed_meta_keys[] = '_fiscaat_record_count_hidden';

		return $allowed_meta_keys;
	}

	function adjust_meta_caps( $caps, $cap, $user_id, $args ) {

		// only run for REST API requests
		if ( ! defined( 'REST_API_REQUEST' ) || ! REST_API_REQUEST )
			return $caps;

		// only modify caps for meta caps and for Fiscaat meta keys
		if ( ! in_array( $cap, array( 'edit_post_meta', 'delete_post_meta', 'add_post_meta' ) ) || empty( $args[1] ) || false === strpos( $args[1], '_fiscaat_' ) )
			return $caps;

		// $args[0] could be a post ID or a post_type string
		if ( is_int( $args[0] ) ) {
			$_post = get_post( $args[0] );
			if ( ! empty( $_post ) ) {
				$post_type = get_post_type_object( $_post->post_type );
			}
		} elseif ( is_string( $args[0] ) ) {
			$post_type = get_post_type_object( $args[0] );
		}

		// no post type found, bail
		if ( empty( $post_type ) )
			return $caps;

		// reset the needed caps
		$caps = array();

		// Fisci can always edit meta
		if ( user_can( $user_id, 'fiscaat' ) ) {
			$caps[] = 'fiscaat';

		// Unknown so map to edit_posts
		} else {
			$caps[] = $post_type->cap->edit_posts;
		}

		return $caps;
	}

}

Fiscaat_Jetpack_REST_API::instance();