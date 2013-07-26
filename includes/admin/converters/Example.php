<?php

/**
 * Implementation of Example converter.
 */
class Example_Converter extends Fiscaat_Converter_Base
{
	function __construct()
	{
		parent::__construct();
		$this->setup_globals();
	}

	public function setup_globals()
	{
		/** Year Section ******************************************************/

		// Year id. Stored in postmeta.
		$this->field_map[] = array(
			'from_tablename' => 'year', 'from_fieldname' => 'yearid',
			'to_type' => 'year', 'to_fieldname' => '_fiscaat_year_id'
		);
		
		// Year parent id.  If no parent, than 0. Stored in postmeta.
		$this->field_map[] = array(
			'from_tablename' => 'year', 'from_fieldname' => 'parentid',
			'to_type' => 'year', 'to_fieldname' => '_fiscaat_parent_id'
		);
		
		// Year title.
		$this->field_map[] = array(
			'from_tablename' => 'year', 'from_fieldname' => 'title',
			'to_type' => 'year', 'to_fieldname' => 'post_title'
		);
		
		// Year slug. Clean name.
		$this->field_map[] = array(
			'from_tablename' => 'year', 'from_fieldname' => 'title_clean',
			'to_type' => 'year', 'to_fieldname' => 'post_name',
			'callback_method' => 'callback_slug'
		);
		
		// Year description.
		$this->field_map[] = array(
			'from_tablename' => 'year', 'from_fieldname' => 'description',
			'to_type' => 'year', 'to_fieldname' => 'post_content',
			'callback_method' => 'callback_null'
		);
		
		// Year display order.  Starts from 1.
		$this->field_map[] = array(
			'from_tablename' => 'year', 'from_fieldname' => 'displayorder',
			'to_type' => 'year', 'to_fieldname' => 'menu_order'
		);
		
		// Year date update.
		$this->field_map[] = array(
			'to_type' => 'year', 'to_fieldname' => 'post_date',
			'default' => date('Y-m-d H:i:s')
		);
		$this->field_map[] = array(
			'to_type' => 'year', 'to_fieldname' => 'post_date_gmt',
			'default' => date('Y-m-d H:i:s')
		);
		$this->field_map[] = array(
			'to_type' => 'year', 'to_fieldname' => 'post_modified',
			'default' => date('Y-m-d H:i:s')
		);
		$this->field_map[] = array(
			'to_type' => 'year', 'to_fieldname' => 'post_modified_gmt',
			'default' => date('Y-m-d H:i:s')
		);

		/** Account Section ******************************************************/

		// Account id. Stored in postmeta.
		$this->field_map[] = array(
			'from_tablename' => 'thread', 'from_fieldname' => 'threadid',
			'to_type' => 'account', 'to_fieldname' => '_fiscaat_account_id'
		);
		
		// Year id. Stored in postmeta.
		$this->field_map[] = array(
			'from_tablename' => 'thread', 'from_fieldname' => 'yearid',
			'to_type' => 'account', 'to_fieldname' => '_fiscaat_year_id',
			'callback_method' => 'callback_yearid'
		);
				
		// Account author.
		$this->field_map[] = array(
			'from_tablename' => 'thread', 'from_fieldname' => 'postuserid',
			'to_type' => 'account', 'to_fieldname' => 'post_author',
			'callback_method' => 'callback_userid'
		);
		
		// Account title.
		$this->field_map[] = array(
			'from_tablename' => 'thread', 'from_fieldname' => 'title',
			'to_type' => 'account', 'to_fieldname' => 'post_title'
		);
		
		// Account slug. Clean name.
		$this->field_map[] = array(
			'from_tablename' => 'thread', 'from_fieldname' => 'title',
			'to_type' => 'account', 'to_fieldname' => 'post_name',
			'callback_method' => 'callback_slug'
		);
		
		// Year id.  If no parent, than 0.
		$this->field_map[] = array(
			'from_tablename' => 'thread', 'from_fieldname' => 'yearid',
			'to_type' => 'account', 'to_fieldname' => 'post_parent',
			'callback_method' => 'callback_yearid'
		);

		// Account date update.
		$this->field_map[] = array(
			'from_tablename' => 'thread', 'from_fieldname' => 'dateline',
			'to_type' => 'account', 'to_fieldname' => 'post_date',
			'callback_method' => 'callback_datetime'
		);
		$this->field_map[] = array(
			'from_tablename' => 'thread', 'from_fieldname' => 'dateline',
			'to_type' => 'account', 'to_fieldname' => 'post_date_gmt',
			'callback_method' => 'callback_datetime'
		);
		$this->field_map[] = array(
			'from_tablename' => 'thread', 'from_fieldname' => 'dateline',
			'to_type' => 'account', 'to_fieldname' => 'post_modified',
			'callback_method' => 'callback_datetime'
		);
		$this->field_map[] = array(
			'from_tablename' => 'thread', 'from_fieldname' => 'dateline',
			'to_type' => 'account', 'to_fieldname' => 'post_modified_gmt',
			'callback_method' => 'callback_datetime'
		);

		/** Tags Section ******************************************************/
		
		// Account id.
		$this->field_map[] = array(
			'from_tablename' => 'tagcontent', 'from_fieldname' => 'contentid',
			'to_type' => 'tags', 'to_fieldname' => 'objectid',
			'callback_method' => 'callback_accountid'
		);
		
		// Tags text.
		$this->field_map[] = array(
			'from_tablename' => 'tag', 'from_fieldname' => 'tagtext',
			'join_tablename' => 'tagcontent', 'join_type' => 'INNER', 'join_expression' => 'USING (tagid)',
			'to_type' => 'tags', 'to_fieldname' => 'name'
		);		

		/** Post Section ******************************************************/

		// Post id. Stores in postmeta.
		$this->field_map[] = array(
			'from_tablename' => 'post', 'from_fieldname' => 'postid',
			'to_type' => 'record', 'to_fieldname' => '_fiscaat_post_id'
		);
		
		// Year id. Stores in postmeta.
		$this->field_map[] = array(
			'from_tablename' => 'post', 'from_fieldname' => 'threadid',
			'to_type' => 'record', 'to_fieldname' => '_fiscaat_year_id',
			'callback_method' => 'callback_accountid_to_yearid'
		);
		
		// Account id. Stores in postmeta.
		$this->field_map[] = array(
			'from_tablename' => 'post', 'from_fieldname' => 'threadid',
			'to_type' => 'record', 'to_fieldname' => '_fiscaat_account_id',
			'callback_method' => 'callback_accountid'
		);
		
		// Author ip.
		$this->field_map[] = array(
			'from_tablename' => 'post', 'from_fieldname' => 'ipaddress',
			'to_type' => 'record', 'to_fieldname' => '__fiscaat_author_ip'
		);	
			
		// Post author.
		$this->field_map[] = array(
			'from_tablename' => 'post', 'from_fieldname' => 'userid',
			'to_type' => 'record', 'to_fieldname' => 'post_author',
			'callback_method' => 'callback_userid'
		);
		
		// Account title.
		$this->field_map[] = array(
			'from_tablename' => 'post', 'from_fieldname' => 'title',
			'to_type' => 'record', 'to_fieldname' => 'post_title'
		);
		
		// Account slug. Clean name.
		$this->field_map[] = array(
			'from_tablename' => 'post', 'from_fieldname' => 'title',
			'to_type' => 'record', 'to_fieldname' => 'post_name',
			'callback_method' => 'callback_slug'
		);
		
		// Post content.
		$this->field_map[] = array(
			'from_tablename' => 'post', 'from_fieldname' => 'pagetext',
			'to_type' => 'record', 'to_fieldname' => 'post_content',
			'callback_method' => 'callback_html'
		);
		
		// Account id.  If no parent, than 0.
		$this->field_map[] = array(
			'from_tablename' => 'post', 'from_fieldname' => 'threadid',
			'to_type' => 'record', 'to_fieldname' => 'post_parent',
			'callback_method' => 'callback_accountid'
		);

		// Account date update.
		$this->field_map[] = array(
			'from_tablename' => 'post', 'from_fieldname' => 'dateline',
			'to_type' => 'record', 'to_fieldname' => 'post_date',
			'callback_method' => 'callback_datetime'
		);
		$this->field_map[] = array(
			'from_tablename' => 'post', 'from_fieldname' => 'dateline',
			'to_type' => 'record', 'to_fieldname' => 'post_date_gmt',
			'callback_method' => 'callback_datetime'
		);
		$this->field_map[] = array(
			'from_tablename' => 'post', 'from_fieldname' => 'dateline',
			'to_type' => 'record', 'to_fieldname' => 'post_modified',
			'callback_method' => 'callback_datetime'
		);
		$this->field_map[] = array(
			'from_tablename' => 'post', 'from_fieldname' => 'dateline',
			'to_type' => 'record', 'to_fieldname' => 'post_modified_gmt',
			'callback_method' => 'callback_datetime'
		);

		/** User Section ******************************************************/

		// Store old User id. Stores in usermeta.
		$this->field_map[] = array(
			'from_tablename' => 'user', 'from_fieldname' => 'userid',
			'to_type' => 'user', 'to_fieldname' => '_fiscaat_user_id'
		);
		
		// Store old User password. Stores in usermeta serialized with salt.
		$this->field_map[] = array(
			'from_tablename' => 'user', 'from_fieldname' => 'password',
			'to_type' => 'user', 'to_fieldname' => '_fiscaat_password',
			'callback_method' => 'callback_savepass'
		);

		// Store old User Salt. This is only used for the SELECT row info for the above password save
		$this->field_map[] = array(
			'from_tablename' => 'user', 'from_fieldname' => 'salt',
			'to_type' => 'user', 'to_fieldname' => ''
		);
				
		// User password verify class. Stores in usermeta for verifying password.
		$this->field_map[] = array(
			'to_type' => 'user', 'to_fieldname' => '_fiscaat_class',
			'default' => 'Vbulletin'
		);
		
		// User name.
		$this->field_map[] = array(
			'from_tablename' => 'user', 'from_fieldname' => 'username',
			'to_type' => 'user', 'to_fieldname' => 'user_login'
		);
				
		// User email.
		$this->field_map[] = array(
			'from_tablename' => 'user', 'from_fieldname' => 'email',
			'to_type' => 'user', 'to_fieldname' => 'user_email'
		);
		
		// User homepage.
		$this->field_map[] = array(
			'from_tablename' => 'user', 'from_fieldname' => 'homepage',
			'to_type' => 'user', 'to_fieldname' => 'user_url'
		);
		
		// User registered.
		$this->field_map[] = array(
			'from_tablename' => 'user', 'from_fieldname' => 'joindate',
			'to_type' => 'user', 'to_fieldname' => 'user_registered',
			'callback_method' => 'callback_datetime'
		);
		
		// User aim.
		$this->field_map[] = array(
			'from_tablename' => 'user', 'from_fieldname' => 'aim',
			'to_type' => 'user', 'to_fieldname' => 'aim'
		);
		
		// User yahoo.
		$this->field_map[] = array(
			'from_tablename' => 'user', 'from_fieldname' => 'yahoo',
			'to_type' => 'user', 'to_fieldname' => 'yim'
		);	
	}
	
	/**
	 * This method allows us to indicates what is or is not converted for each
	 * converter.
	 */
	public function info()
	{
		return '';
	}

	/**
	 * This method is to save the salt and password together.  That
	 * way when we authenticate it we can get it out of the database
	 * as one value. Array values are auto sanitized by wordpress.
	 */
	public function callback_savepass( $field, $row )
	{
		$pass_array = array( 'hash' => $field, 'salt' => $row['salt'] );
		return $pass_array;
	}

	/**
	 * This method is to take the pass out of the database and compare
	 * to a pass the user has typed in.
	 */
	public function authenticate_pass( $password, $serialized_pass )
	{
		$pass_array = unserialize( $serialized_pass );
		return ( $pass_array['hash'] == md5( md5( $password ). $pass_array['salt'] ) );
	}
}
