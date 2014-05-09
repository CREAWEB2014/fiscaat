<?php

/**
 * Fiscaat 1.1 Converter
 *
 * @since Fiscaat (rxxxx)
 */
class Fiscaat1 extends Fiscaat_Converter_Base {

	/**
	 * Main constructor
	 *
	 * @uses Fiscaat1::setup_globals()
	 */
	function __construct() {
		parent::__construct();
		$this->setup_globals();
	}

	/**
	 * Sets up the field mappings
	 */
	public function setup_globals() {

		/** Period Section *****************************************************/

		// Period id (Stored in postmeta)
		$this->field_map[] = array(
			'from_tablename' => 'periods',
			'from_fieldname' => 'period_id',
			'to_type'        => 'period',
			'to_fieldname'   => '_fct_period_id'
		);

		// Period parent id (If no parent, 0. Stored in postmeta)
		$this->field_map[] = array(
			'from_tablename' => 'periods',
			'from_fieldname' => 'period_parent',
			'to_type'        => 'period',
			'to_fieldname'   => '_fct_period_parent_id'
		);

		// Period account count (Stored in postmeta)
		$this->field_map[] = array(
			'from_tablename' => 'periods',
			'from_fieldname' => 'accounts',
			'to_type'        => 'period',
			'to_fieldname'   => '_fct_account_count'
		);

		// Period record count (Stored in postmeta)
		$this->field_map[] = array(
			'from_tablename' => 'periods',
			'from_fieldname' => 'posts',
			'to_type'        => 'period',
			'to_fieldname'   => '_fct_record_count'
		);

		// Period account count (Stored in postmeta)
		$this->field_map[] = array(
			'from_tablename' => 'periods',
			'from_fieldname' => 'accounts',
			'to_type'        => 'period',
			'to_fieldname'   => '_fct_total_account_count'
		);

		// Period record count (Stored in postmeta)
		$this->field_map[] = array(
			'from_tablename' => 'periods',
			'from_fieldname' => 'posts',
			'to_type'        => 'period',
			'to_fieldname'   => '_fct_total_record_count'
		);

		// Period title.
		$this->field_map[] = array(
			'from_tablename' => 'periods',
			'from_fieldname' => 'period_name',
			'to_type'        => 'period',
			'to_fieldname'   => 'post_title'
		);

		// Period slug (Clean name to avoid confilcts)
		$this->field_map[] = array(
			'from_tablename'   => 'periods',
			'from_fieldname'   => 'period_slug',
			'to_type'          => 'period',
			'to_fieldname'     => 'post_name',
			'callback_method'  => 'callback_slug'
		);

		// Period description.
		$this->field_map[] = array(
			'from_tablename'   => 'periods',
			'from_fieldname'   => 'period_desc',
			'to_type'          => 'period',
			'to_fieldname'     => 'post_content',
			'callback_method'  => 'callback_null'
		);

		// Period display order (Starts from 1)
		$this->field_map[] = array(
			'from_tablename' => 'periods',
			'from_fieldname' => 'period_order',
			'to_type'        => 'period',
			'to_fieldname'   => 'menu_order'
		);

		// Period dates.
		$this->field_map[] = array(
			'to_type'      => 'period',
			'to_fieldname' => 'post_date',
			'default'      => date('Y-m-d H:i:s')
		);
		$this->field_map[] = array(
			'to_type'      => 'period',
			'to_fieldname' => 'post_date_gmt',
			'default'      => date('Y-m-d H:i:s')
		);
		$this->field_map[] = array(
			'to_type'      => 'period',
			'to_fieldname' => 'post_modified',
			'default'      => date('Y-m-d H:i:s')
		);
		$this->field_map[] = array(
			'to_type'      => 'period',
			'to_fieldname' => 'post_modified_gmt',
			'default'      => date('Y-m-d H:i:s')
		);

		/** Account Section *****************************************************/

		// Account id (Stored in postmeta)
		$this->field_map[] = array(
			'from_tablename' => 'accounts',
			'from_fieldname' => 'account_id',
			'to_type'        => 'account',
			'to_fieldname'   => '_fct_account_id'
		);

		// Record count (Stored in postmeta)
		$this->field_map[] = array(
			'from_tablename'  => 'accounts',
			'from_fieldname'  => 'account_posts',
			'to_type'         => 'account',
			'to_fieldname'    => '_fct_record_count',
			'callback_method' => 'callback_account_record_count'
		);

		// Period id (Stored in postmeta)
		$this->field_map[] = array(
			'from_tablename'  => 'accounts',
			'from_fieldname'  => 'period_id',
			'to_type'         => 'account',
			'to_fieldname'    => '_fct_period_id',
			'callback_method' => 'callback_periodid'
		);

		// Account author.
		$this->field_map[] = array(
			'from_tablename'  => 'accounts',
			'from_fieldname'  => 'account_poster',
			'to_type'         => 'account',
			'to_fieldname'    => 'post_author',
			'callback_method' => 'callback_userid'
		);

		// Account title.
		$this->field_map[] = array(
			'from_tablename' => 'accounts',
			'from_fieldname' => 'account_title',
			'to_type'        => 'account',
			'to_fieldname'   => 'post_title'
		);

		// Account slug (Clean name to avoid conflicts)
		$this->field_map[] = array(
			'from_tablename'  => 'accounts',
			'from_fieldname'  => 'account_title',
			'to_type'         => 'account',
			'to_fieldname'    => 'post_name',
			'callback_method' => 'callback_slug'
		);

		// Account content.
		// Note: We join the posts table because accounts do not have content.
		$this->field_map[] = array(
			'from_tablename'  => 'posts',
			'from_fieldname'  => 'post_text',
			'join_tablename'  => 'accounts',
			'join_type'       => 'INNER',
			'join_expression' => 'USING (account_id) WHERE posts.post_position IN (0,1)',
			'to_type'         => 'account',
			'to_fieldname'    => 'post_content',
			'callback_method' => 'callback_html'
		);

		// Account status.
		// Note: post_status is more accurate than account_status
		$this->field_map[] = array(
			'from_tablename'  => 'posts',
			'from_fieldname'  => 'post_status',
			'join_tablename'  => 'accounts',
			'join_type'       => 'INNER',
			'join_expression' => 'USING (account_id) WHERE posts.post_position IN (0,1)',
			'to_type'         => 'account',
			'to_fieldname'    => 'post_status',
			'callback_method' => 'callback_status'
		);

		// Author ip.
		$this->field_map[] = array(
			'from_tablename'  => 'posts',
			'from_fieldname'  => 'poster_ip',
			'join_tablename'  => 'accounts',
			'join_type'       => 'INNER',
			'join_expression' => 'USING (account_id) WHERE posts.post_position IN (0,1)',
			'to_type'         => 'account',
			'to_fieldname'    => '_fct_author_ip'
		);

		// Period id (If no parent, 0)
		$this->field_map[] = array(
			'from_tablename'  => 'accounts',
			'from_fieldname'  => 'period_id',
			'to_type'         => 'account',
			'to_fieldname'    => 'post_parent',
			'callback_method' => 'callback_periodid'
		);

		// Account dates.
		$this->field_map[] = array(
			'from_tablename' => 'accounts',
			'from_fieldname' => 'account_start_time',
			'to_type'        => 'account',
			'to_fieldname'   => 'post_date'
		);
		$this->field_map[] = array(
			'from_tablename' => 'accounts',
			'from_fieldname' => 'account_start_time',
			'to_type'        => 'account',
			'to_fieldname'   => 'post_date_gmt'
		);
		$this->field_map[] = array(
			'from_tablename' => 'accounts',
			'from_fieldname' => 'account_time',
			'to_type'        => 'account',
			'to_fieldname'   => 'post_modified'
		);
		$this->field_map[] = array(
			'from_tablename' => 'accounts',
			'from_fieldname' => 'account_time',
			'to_type'        => 'account',
			'to_fieldname'   => 'post_modified_gmt'
		);
		$this->field_map[] = array(
			'from_tablename' => 'accounts',
			'from_fieldname' => 'account_time',
			'to_type'        => 'account',
			'to_fieldname'   => '_fct_last_active_time'
		);

		/** Tags Section ******************************************************/

		// Account id.
		$this->field_map[] = array(
			'from_tablename'  => 'term_relationships',
			'from_fieldname'  => 'object_id',
			'to_type'         => 'tags',
			'to_fieldname'    => 'objectid',
			'callback_method' => 'callback_accountid'
		);

		// Taxonomy ID.
		$this->field_map[] = array(
			'from_tablename'  => 'term_taxonomy',
			'from_fieldname'  => 'term_taxonomy_id',
			'join_tablename'  => 'term_relationships',
			'join_type'       => 'INNER',
			'join_expression' => 'USING (term_taxonomy_id)',
			'to_type'         => 'tags',
			'to_fieldname'    => 'taxonomy'
		);

		// Term text.
		$this->field_map[] = array(
			'from_tablename'  => 'terms',
			'from_fieldname'  => 'name',
			'join_tablename'  => 'term_taxonomy',
			'join_type'       => 'INNER',
			'join_expression' => 'USING (term_id)',
			'to_type'         => 'tags',
			'to_fieldname'    => 'name'
		);

		/** Record Section *****************************************************/

		// Post id. Stores in postmeta.
		$this->field_map[] = array(
			'from_tablename'  => 'posts',
			'from_fieldname'  => 'post_id',
			'to_type'         => 'record',
			'to_fieldname'    => '_fct_post_id'
		);

		// Account id (Stores in postmeta)
		$this->field_map[] = array(
			'from_tablename'  => 'posts',
			'from_fieldname'  => 'account_id',
			'to_type'         => 'record',
			'to_fieldname'    => '_fct_account_id',
			'callback_method' => 'callback_accountid'
		);

		// Period id (Stored in postmeta)
		$this->field_map[] = array(
			'from_tablename'  => 'posts',
			'from_fieldname'  => 'period_id',
			'to_type'         => 'record',
			'to_fieldname'    => '_fct_period_id',
			'callback_method' => 'callback_periodid'
		);

		// Account title (for record title).
		$this->field_map[] = array(
			'from_tablename'  => 'accounts',
			'from_fieldname'  => 'account_title',
			'join_tablename'  => 'posts',
			'join_type'       => 'INNER',
			'join_expression' => 'USING (account_id) WHERE posts.post_position NOT IN (0,1)',
			'to_type'         => 'record',
			'to_fieldname'    => 'post_title',
			'callback_method' => 'callback_record_title'
		);

		// Author ip.
		$this->field_map[] = array(
			'from_tablename' => 'posts',
			'from_fieldname' => 'poster_ip',
			'to_type'        => 'record',
			'to_fieldname'   => '_fct_author_ip'
		);

		// Record author.
		$this->field_map[] = array(
			'from_tablename'  => 'posts',
			'from_fieldname'  => 'poster_id',
			'to_type'         => 'record',
			'to_fieldname'    => 'post_author',
			'callback_method' => 'callback_userid'
		);

		// Record status
		$this->field_map[] = array(
			'from_tablename'  => 'posts',
			'from_fieldname'  => 'post_status',
			'to_type'         => 'record',
			'to_fieldname'    => 'post_status',
			'callback_method' => 'callback_status'
		);

		// Record content.
		$this->field_map[] = array(
			'from_tablename'  => 'posts',
			'from_fieldname'  => 'post_text',
			'to_type'         => 'record',
			'to_fieldname'    => 'post_content',
			'callback_method' => 'callback_html'
		);

		// Record order.
		$this->field_map[] = array(
			'from_tablename'  => 'posts',
			'from_fieldname'  => 'post_position',
			'to_type'         => 'record',
			'to_fieldname'    => 'menu_order'
		);

		// Account id.  If no parent, than 0.
		$this->field_map[] = array(
			'from_tablename'  => 'posts',
			'from_fieldname'  => 'account_id',
			'to_type'         => 'record',
			'to_fieldname'    => 'post_parent',
			'callback_method' => 'callback_accountid'
		);

		// Record dates.
		$this->field_map[] = array(
			'from_tablename' => 'posts',
			'from_fieldname' => 'post_time',
			'to_type'        => 'record',
			'to_fieldname'   => 'post_date'
		);
		$this->field_map[] = array(
			'from_tablename' => 'posts',
			'from_fieldname' => 'post_time',
			'to_type'        => 'record',
			'to_fieldname'   => 'post_date_gmt'
		);
		$this->field_map[] = array(
			'from_tablename' => 'posts',
			'from_fieldname' => 'post_time',
			'to_type'        => 'record',
			'to_fieldname'   => 'post_modified'
		);
		$this->field_map[] = array(
			'from_tablename' => 'posts',
			'from_fieldname' => 'post_time',
			'to_type'        => 'record',
			'to_fieldname'   => 'post_modified_gmt'
		);

		/** User Section ******************************************************/

		// Store old User id. Stores in usermeta.
		$this->field_map[] = array(
			'from_tablename' => 'users',
			'from_fieldname' => 'ID',
			'to_type'        => 'user',
			'to_fieldname'   => '_fct_user_id'
		);

		// Store old User password. Stores in usermeta.
		$this->field_map[] = array(
			'from_tablename' => 'users',
			'from_fieldname' => 'user_pass',
			'to_type'        => 'user',
			'to_fieldname'   => '_fct_password'
		);

		// User name.
		$this->field_map[] = array(
			'from_tablename' => 'users',
			'from_fieldname' => 'user_login',
			'to_type'        => 'user',
			'to_fieldname'   => 'user_login'
		);

		// User nice name.
		$this->field_map[] = array(
			'from_tablename' => 'users',
			'from_fieldname' => 'user_nicename',
			'to_type'        => 'user',
			'to_fieldname'   => 'user_nicename'
		);

		// User email.
		$this->field_map[] = array(
			'from_tablename' => 'users',
			'from_fieldname' => 'user_email',
			'to_type'        => 'user',
			'to_fieldname'   => 'user_email'
		);

		// User homepage.
		$this->field_map[] = array(
			'from_tablename' => 'users',
			'from_fieldname' => 'user_url',
			'to_type'        => 'user',
			'to_fieldname'   => 'user_url'
		);

		// User registered.
		$this->field_map[] = array(
			'from_tablename' => 'users',
			'from_fieldname' => 'user_registered',
			'to_type'        => 'user',
			'to_fieldname'   => 'user_registered'
		);

		// User status.
		$this->field_map[] = array(
			'from_tablename' => 'users',
			'from_fieldname' => 'user_status',
			'to_type'        => 'user',
			'to_fieldname'   => 'user_status'
		);

		// User status.
		$this->field_map[] = array(
			'from_tablename' => 'users',
			'from_fieldname' => 'display_name',
			'to_type'        => 'user',
			'to_fieldname'   => 'display_name'
		);
	}

	/**
	 * This method allows us to indicates what is or is not converted for each
	 * converter.
	 */
	public function info() {
		return '';
	}

	/**
	 * Translate the post status from Fiscaat 1's numeric's to WordPress's
	 * strings.
	 *
	 * @param int $status Fiscaat 1.x numeric status
	 * @return string WordPress safe
	 */
	public function callback_status( $status = 0 ) {
		switch ( $status ) {
			case 2 :
				$status = 'spam';    // fct_get_spam_status_id()
				break;

			case 1 :
				$status = 'trash';   // fct_get_trash_status_id()
				break;

			case 0  :
			default :
				$status = 'publish'; // fct_get_public_status_id()
				break;
		}
		return $status;
	}

	/**
	 * Verify the account record count.
	 *
	 * @param int $count Fiscaat 1.x record count
	 * @return string WordPress safe
	 */
	public function callback_account_record_count( $count = 1 ) {
		$count = absint( (int) $count - 1 );
		return $count;
	}

	/**
	 * Set the record title
	 *
	 * @param string $title Fiscaat 1.x account title of this record
	 * @return string Prefixed account title, or empty string
	 */
	public function callback_record_title( $title = '' ) {
		$title = ! empty( $title ) ? __( 'Re: ', 'fiscaat' ) . html_entity_decode( $title ) : '';
		return $title;
	}

	/**
	 * This method is to save the salt and password together. That
	 * way when we authenticate it we can get it out of the database
	 * as one value. Array values are auto sanitized by wordpress.
	 */
	public function callback_savepass( $field, $row ) {
		return false;
	}

	/**
	 * This method is to take the pass out of the database and compare
	 * to a pass the user has typed in.
	 */
	public function authenticate_pass( $password, $serialized_pass ) {
		return false;
	}
}
