<?php

/**
 * Implementation of phpBB converter.
 */
class phpBB extends Fiscaat_Converter_Base {
	function __construct() {
		parent::__construct();
		$this->setup_globals();
	}

	public function setup_globals() {

		/** Year Section ******************************************************/

		// Year id. Stored in postmeta.
		$this->field_map[] = array(
			'from_tablename' => 'years',
			'from_fieldname' => 'year_id',
			'to_type'        => 'year',
			'to_fieldname'   => '_fct_year_id'
		);
		
		// Year parent id.  If no parent, than 0. Stored in postmeta.
		$this->field_map[] = array(
			'from_tablename' => 'years',
			'from_fieldname' => 'parent_id',
			'to_type'        => 'year',
			'to_fieldname'   => '_fct_parent_id'
		);
		
		// Year title.
		$this->field_map[] = array(
			'from_tablename' => 'years',
			'from_fieldname' => 'year_name',
			'to_type'        => 'year',
			'to_fieldname'   => 'post_title'
		);
		
		// Year slug. Clean name.
		$this->field_map[] = array(
			'from_tablename'  => 'years',
			'from_fieldname'  => 'year_name',
			'to_type'         => 'year',
			'to_fieldname'    => 'post_name',
			'callback_method' => 'callback_slug'
		);
		
		// Year description.
		$this->field_map[] = array(
			'from_tablename'  => 'years',
			'from_fieldname'  => 'year_desc',
			'to_type'         => 'year',
			'to_fieldname'    => 'post_content',
			'callback_method' => 'callback_null'
		);
		
		// Year display order.  Starts from 1.
		$this->field_map[] = array(
			'from_tablename' => 'years',
			'from_fieldname' => 'display_on_index',
			'to_type'        => 'year',
			'to_fieldname'   => 'menu_order'
		);
		
		// Year date update.
		$this->field_map[] = array(
			'to_type'      => 'years',
			'to_fieldname' => 'year_last_post_time',
			'default'      => date('Y-m-d H:i:s')
		);
		$this->field_map[] = array(
			'to_type'      => 'years',
			'to_fieldname' => 'year_last_post_time',
			'default'      => date('Y-m-d H:i:s')
		);
		$this->field_map[] = array(
			'to_type'      => 'years',
			'to_fieldname' => 'year_last_post_time',
			'default'      => date('Y-m-d H:i:s')
		);
		$this->field_map[] = array(
			'to_type'      => 'years',
			'to_fieldname' => 'year_last_post_time',
			'default'      => date('Y-m-d H:i:s')
		);

		/** Account Section ******************************************************/

		// Account id. Stored in postmeta.
		$this->field_map[] = array(
			'from_tablename' => 'accounts',
			'from_fieldname' => 'account_id',
			'to_type'        => 'account',
			'to_fieldname'   => '_fct_account_id'
		);
		
		// Year id. Stored in postmeta.
		$this->field_map[] = array(
			'from_tablename'  => 'accounts',
			'from_fieldname'  => 'year_id',
			'to_type'         => 'account',
			'to_fieldname'    => '_fct_year_id',
			'callback_method' => 'callback_yearid'
		);
				
		// Account author.
		$this->field_map[] = array(
			'from_tablename'  => 'accounts',
			'from_fieldname'  => 'account_poster',
			'to_type'         => 'account',
			'to_fieldname'    => 'post_author',
			'callback_method' => 'callback_userid'
		);

		// Account content.
		$this->field_map[] = array(
			'from_tablename'  => 'posts',
			'from_fieldname'  => 'post_text',
			'join_tablename'  => 'accounts',
			'join_type'       => 'INNER',
			'join_expression' => 'USING (account_id) WHERE posts.post_id = accounts.account_first_post_id',
			'to_type'         => 'account',
			'to_fieldname'    => 'post_content',
			'callback_method' => 'callback_html'
		);	

		// Account title.
		$this->field_map[] = array(
			'from_tablename' => 'accounts',
			'from_fieldname' => 'account_title',
			'to_type'        => 'account',
			'to_fieldname'   => 'post_title'
		);
		
		// Account slug. Clean name.
		$this->field_map[] = array(
			'from_tablename'  => 'accounts',
			'from_fieldname'  => 'account_title',
			'to_type'         => 'account',
			'to_fieldname'    => 'post_name',
			'callback_method' => 'callback_slug'
		);
		
		// Year id.  If no parent, than 0.
		$this->field_map[] = array(
			'from_tablename'  => 'accounts',
			'from_fieldname'  => 'year_id',
			'to_type'         => 'account',
			'to_fieldname'    => 'post_parent',
			'callback_method' => 'callback_yearid'
		);

		// Account date update.
		$this->field_map[] = array(
			'from_tablename'  => 'accounts',
			'from_fieldname'  => 'account_time',
			'to_type'         => 'account',
			'to_fieldname'    => 'post_date',
			'callback_method' => 'callback_datetime'
		);
		$this->field_map[] = array(
			'from_tablename'  => 'accounts',
			'from_fieldname'  => 'account_time',
			'to_type'         => 'account',
			'to_fieldname'    => 'post_date_gmt',
			'callback_method' => 'callback_datetime'
		);
		$this->field_map[] = array(
			'from_tablename'  => 'accounts',
			'from_fieldname'  => 'account_time',
			'to_type'         => 'account',
			'to_fieldname'    => 'post_modified',
			'callback_method' => 'callback_datetime'
		);
		$this->field_map[] = array(
			'from_tablename'  => 'accounts',
			'from_fieldname'  => 'account_time',
			'to_type'         => 'account',
			'to_fieldname'    => 'post_modified_gmt',
			'callback_method' => 'callback_datetime'
		);
		
		/** Tags Section ******************************************************/
		/*
		// Account id.
		$this->field_map[] = array(
			'from_tablename'  => 'tagcontent',
			'from_fieldname'  => 'contentid',
			'to_type'         => 'tags',
			'to_fieldname'    => 'objectid',
			'callback_method' => 'callback_accountid'
		);
		
		// Tags text.
		$this->field_map[] = array(
			'from_tablename'  => 'tag',
			'from_fieldname'  => 'tagtext',
			'join_tablename'  => 'tagcontent',
			'join_type'       => 'INNER',
			'join_expression' => 'USING (tagid)',
			'to_type'         => 'tags',
			'to_fieldname'    => 'name'
		);		
		*/
		
		/** Post Section ******************************************************/

		// Post id. Stores in postmeta.
		$this->field_map[] = array(
			'from_tablename' => 'posts',
			'from_fieldname' => 'post_id',
			'to_type'        => 'record',
			'to_fieldname'   => '_fct_post_id'
		);
		
		// Account content.
		$this->field_map[] = array(
			'from_tablename'  => 'accounts',
			'from_fieldname'  => 'account_id',
			'join_tablename'  => 'posts',
			'join_type'       => 'LEFT',
			'join_expression' => 'USING (account_id) WHERE posts.post_id != accounts.account_first_post_id',
			'to_type'         => 'record'
		);	
		
		// Year id. Stores in postmeta.
		$this->field_map[] = array(
			'from_tablename'  => 'posts',
			'from_fieldname'  => 'year_id',
			'to_type'         => 'record',
			'to_fieldname'    => '_fct_year_id',
			'callback_method' => 'callback_accountid_to_yearid'
		);
		
		// Account id. Stores in postmeta.
		$this->field_map[] = array(
			'from_tablename'  => 'posts',
			'from_fieldname'  => 'account_id',
			'to_type'         => 'record',
			'to_fieldname'    => '_fct_account_id',
			'callback_method' => 'callback_accountid'
		);
		
		// Author ip.
		$this->field_map[] = array(
			'from_tablename' => 'posts',
			'from_fieldname' => 'poster_ip',
			'to_type'        => 'record',
			'to_fieldname'   => '_fct_author_ip'
		);	
			
		// Post author.
		$this->field_map[] = array(
			'from_tablename'  => 'posts',
			'from_fieldname'  => 'poster_id',
			'to_type'         => 'record',
			'to_fieldname'    => 'post_author',
			'callback_method' => 'callback_userid'
		);
		
		// Account title.
		$this->field_map[] = array(
			'from_tablename' => 'posts',
			'from_fieldname' => 'post_subject',
			'to_type'        => 'record',
			'to_fieldname'   => 'post_title'
		);
		
		// Account slug. Clean name.
		$this->field_map[] = array(
			'from_tablename'  => 'posts',
			'from_fieldname'  => 'post_subject',
			'to_type'         => 'record',
			'to_fieldname'    => 'post_name',
			'callback_method' => 'callback_slug'
		);
		
		// Post content.
		$this->field_map[] = array(
			'from_tablename'  => 'posts',
			'from_fieldname'  => 'post_text',
			'to_type'         => 'record',
			'to_fieldname'    => 'post_content',
			'callback_method' => 'callback_html'
		);
		
		// Account id.  If no parent, than 0.
		$this->field_map[] = array(
			'from_tablename'  => 'posts',
			'from_fieldname'  => 'account_id',
			'to_type'         => 'record',
			'to_fieldname'    => 'post_parent',
			'callback_method' => 'callback_accountid'
		);

		// Account date update.
		$this->field_map[] = array(
			'from_tablename'  => 'posts',
			'from_fieldname'  => 'post_time',
			'to_type'         => 'record',
			'to_fieldname'    => 'post_date',
			'callback_method' => 'callback_datetime'
		);
		$this->field_map[] = array(
			'from_tablename'  => 'posts',
			'from_fieldname'  => 'post_time',
			'to_type'         => 'record',
			'to_fieldname'    => 'post_date_gmt',
			'callback_method' => 'callback_datetime'
		);
		$this->field_map[] = array(
			'from_tablename'  => 'posts',
			'from_fieldname'  => 'post_time',
			'to_type'         => 'record',
			'to_fieldname'    => 'post_modified',
			'callback_method' => 'callback_datetime'
		);
		$this->field_map[] = array(
			'from_tablename'  => 'posts',
			'from_fieldname'  => 'post_time',
			'to_type'         => 'record',
			'to_fieldname'    => 'post_modified_gmt',
			'callback_method' => 'callback_datetime'
		);

		/** User Section ******************************************************/

		// Store old User id. Stores in usermeta.
		$this->field_map[] = array(
			'from_tablename' => 'users',
			'from_fieldname' => 'user_id',
			'to_type'        => 'user',
			'to_fieldname'   => '_fct_user_id'
		);
		
		// Store old User password. Stores in usermeta serialized with salt.
		$this->field_map[] = array(
			'from_tablename'  => 'users',
			'from_fieldname'  => 'user_password',
			'to_type'         => 'user',
			'to_fieldname'    => '_fct_password',
			'callback_method' => 'callback_savepass'
		);

		// Store old User Salt. This is only used for the SELECT row info for the above password save
		$this->field_map[] = array(
			'from_tablename' => 'users',
			'from_fieldname' => 'user_form_salt',
			'to_type'        => 'user',
			'to_fieldname'   => ''
		);
				
		// User password verify class. Stores in usermeta for verifying password.
		$this->field_map[] = array(
			'to_type'      => 'user',
			'to_fieldname' => '_fct_class',
			'default'      => 'phpBB'
		);
				
		// User name.
		$this->field_map[] = array(
			'from_tablename' => 'users',
			'from_fieldname' => 'username',
			'to_type'        => 'user',
			'to_fieldname'   => 'user_login'
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
			'from_fieldname' => 'user_website',
			'to_type'        => 'user',
			'to_fieldname'   => 'user_url'
		);
		
		// User registered.
		$this->field_map[] = array(
			'from_tablename'  => 'users',
			'from_fieldname'  => 'user_regdate',
			'to_type'         => 'user',
			'to_fieldname'    => 'user_registered',
			'callback_method' => 'callback_datetime'
		);
		
		// User aim.
		$this->field_map[] = array(
			'from_tablename' => 'users',
			'from_fieldname' => 'user_aim',
			'to_type'        => 'user',
			'to_fieldname'   => 'aim'
		);
		
		// User yahoo.
		$this->field_map[] = array(
			'from_tablename' => 'users',
			'from_fieldname' => 'user_yim',
			'to_type'        => 'user',
			'to_fieldname'   => 'yim'
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
	 * This method is to save the salt and password together.  That
	 * way when it is authenticate it we can get it out of the database
	 * as one value.
	 */
	public function callback_savepass( $field, $row ) {
		$pass_array = array('hash' => $field, 'salt' => $row['salt']);
		return $pass_array;
	}
	
	/**
	 * Check for correct password
	 *
	 * @param string $password The password in plain text
	 * @param string $hash The stored password hash
	 *
	 * @return bool Returns true if the password is correct, false if not.
	 */
	public function authenticate_pass($password, $serialized_pass) {
		$itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
		$pass_array = unserialize($serialized_pass);
		if (strlen($pass_array['hash']) == 34) {				
			return ($this->_hash_crypt_private($password, $pass_array['hash'], $itoa64) === $pass_array['hash']) ? true : false;
		}
	
		return (md5($password) === $pass_array['hash']) ? true : false;
	}
	
	/**
	 * The crypt function/replacement
	 */
	private function _hash_crypt_private($password, $setting, &$itoa64) {
		$output = '*';
	
		// Check for correct hash
		if (substr($setting, 0, 3) != '$H$') {
			return $output;
		}
	
		$count_log2 = strpos($itoa64, $setting[3]);
	
		if ($count_log2 < 7 || $count_log2 > 30) {
			return $output;
		}
	
		$count = 1 << $count_log2;
		$salt = substr($setting, 4, 8);
	
		if (strlen($salt) != 8) {
			return $output;
		}
	
		/**
		 * We're kind of forced to use MD5 here since it's the only
		 * cryptographic primitive available in all versions of PHP
		 * currently in use.  To implement our own low-level crypto
		 * in PHP would result in much worse performance and
		 * consequently in lower iteration counts and hashes that are
		 * quicker to crack (by non-PHP code).
		 */
		if (floatval(phpversion()) >= 5) {
			$hash = md5($salt . $password, true);
			do
			{
				$hash = md5($hash . $password, true);
			}
			while (--$count);
		} else {
			$hash = pack('H*', md5($salt . $password));
			do {
				$hash = pack('H*', md5($hash . $password));
			}
			while (--$count);
		}

		$output = substr($setting, 0, 12);
		$output .= $this->_hash_encode64($hash, 16, $itoa64);
	
		return $output;
	}
		
	/**
	 * Encode hash
	 */
	private function _hash_encode64($input, $count, &$itoa64) {
		$output = '';
		$i = 0;
	
		do {
			$value = ord($input[$i++]);
			$output .= $itoa64[$value & 0x3f];
	
			if ($i < $count) {
				$value |= ord($input[$i]) << 8;
			}
	
			$output .= $itoa64[($value >> 6) & 0x3f];
	
			if ($i++ >= $count) {
				break;
			}
	
			if ($i < $count) {
				$value |= ord($input[$i]) << 16;
			}
	
			$output .= $itoa64[($value >> 12) & 0x3f];
	
			if ($i++ >= $count) {
				break;
			}
	
			$output .= $itoa64[($value >> 18) & 0x3f];
		}
		while ($i < $count);
	
		return $output;
	}

}
