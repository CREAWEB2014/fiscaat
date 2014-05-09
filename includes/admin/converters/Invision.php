<?php

/**
 * Implementation of Invision Power Board converter.
 */
class Invision extends Fiscaat_Converter_Base
{
	function __construct()
	{
		parent::__construct();
		$this->setup_globals();
	}

	public function setup_globals()
	{
		/** Period Section ******************************************************/

		// Period id. Stored in postmeta.
		$this->field_map[] = array(
			'from_tablename' => 'periods', 'from_fieldname' => 'id',
			'to_type' => 'period', 'to_fieldname' => '_fct_period_id'
		);
		
		// Period parent id.  If no parent, than 0. Stored in postmeta.
		$this->field_map[] = array(
			'from_tablename' => 'periods', 'from_fieldname' => 'parent_id',
			'to_type' => 'period', 'to_fieldname' => '_fct_parent_id'
		);
		
		// Period title.
		$this->field_map[] = array(
			'from_tablename' => 'periods', 'from_fieldname' => 'name',
			'to_type' => 'period', 'to_fieldname' => 'post_title'
		);
		
		// Period slug. Clean name.
		$this->field_map[] = array(
			'from_tablename' => 'periods', 'from_fieldname' => 'name',
			'to_type' => 'period', 'to_fieldname' => 'post_name',
			'callback_method' => 'callback_slug'
		);
		
		// Period description.
		$this->field_map[] = array(
			'from_tablename' => 'periods', 'from_fieldname' => 'description',
			'to_type' => 'period', 'to_fieldname' => 'post_content',
			'callback_method' => 'callback_null'
		);
		
		// Period display order.  Starts from 1.
		$this->field_map[] = array(
			'from_tablename' => 'periods', 'from_fieldname' => 'position',
			'to_type' => 'period', 'to_fieldname' => 'menu_order'
		);
		
		// Period date update.
		$this->field_map[] = array(
			'to_type' => 'period', 'to_fieldname' => 'post_date',
			'default' => date('Y-m-d H:i:s')
		);
		$this->field_map[] = array(
			'to_type' => 'period', 'to_fieldname' => 'post_date_gmt',
			'default' => date('Y-m-d H:i:s')
		);
		$this->field_map[] = array(
			'to_type' => 'period', 'to_fieldname' => 'post_modified',
			'default' => date('Y-m-d H:i:s')
		);
		$this->field_map[] = array(
			'to_type' => 'period', 'to_fieldname' => 'post_modified_gmt',
			'default' => date('Y-m-d H:i:s')
		);

		/** Account Section ******************************************************/

		// Account id. Stored in postmeta.
		$this->field_map[] = array(
			'from_tablename' => 'accounts', 'from_fieldname' => 'tid',
			'to_type' => 'account', 'to_fieldname' => '_fct_account_id'
		);
		
		// Period id. Stored in postmeta.
		$this->field_map[] = array(
			'from_tablename' => 'accounts', 'from_fieldname' => 'period_id',
			'to_type' => 'account', 'to_fieldname' => '_fct_period_id',
			'callback_method' => 'callback_periodid'
		);
				
		// Account author.
		$this->field_map[] = array(
			'from_tablename' => 'accounts', 'from_fieldname' => 'starter_id',
			'to_type' => 'account', 'to_fieldname' => 'post_author',
			'callback_method' => 'callback_userid'
		);
			
		// Account content.
		$this->field_map[] = array(
			'from_tablename' => 'posts', 'from_fieldname' => 'post',
			'join_tablename' => 'accounts', 'join_type' => 'INNER', 'join_expression' => 'ON(accounts.tid = posts.account_id) WHERE posts.new_account = 1',
			'to_type' => 'account', 'to_fieldname' => 'post_content',
			'callback_method' => 'callback_html'
		);	
		
		// Account title.
		$this->field_map[] = array(
			'from_tablename' => 'accounts', 'from_fieldname' => 'title',
			'to_type' => 'account', 'to_fieldname' => 'post_title'
		);
		
		// Account slug. Clean name.
		$this->field_map[] = array(
			'from_tablename' => 'accounts', 'from_fieldname' => 'title',
			'to_type' => 'account', 'to_fieldname' => 'post_name',
			'callback_method' => 'callback_slug'
		);
		
		// Period id.  If no parent, than 0.
		$this->field_map[] = array(
			'from_tablename' => 'accounts', 'from_fieldname' => 'period_id',
			'to_type' => 'account', 'to_fieldname' => 'post_parent',
			'callback_method' => 'callback_periodid'
		);

		// Account date update.
		$this->field_map[] = array(
			'from_tablename' => 'accounts', 'from_fieldname' => 'start_date',
			'to_type' => 'account', 'to_fieldname' => 'post_date',
			'callback_method' => 'callback_datetime'
		);
		$this->field_map[] = array(
			'from_tablename' => 'accounts', 'from_fieldname' => 'start_date',
			'to_type' => 'account', 'to_fieldname' => 'post_date_gmt',
			'callback_method' => 'callback_datetime'
		);
		$this->field_map[] = array(
			'from_tablename' => 'accounts', 'from_fieldname' => 'last_post',
			'to_type' => 'account', 'to_fieldname' => 'post_modified',
			'callback_method' => 'callback_datetime'
		);
		$this->field_map[] = array(
			'from_tablename' => 'accounts', 'from_fieldname' => 'last_post',
			'to_type' => 'account', 'to_fieldname' => 'post_modified_gmt',
			'callback_method' => 'callback_datetime'
		);

		/** Tags Section ******************************************************/
		
		// Account id.
		$this->field_map[] = array(
			'from_tablename' => 'core_tags', 'from_fieldname' => 'tag_meta_id',
			'to_type' => 'tags', 'to_fieldname' => 'objectid',
			'callback_method' => 'callback_accountid'
		);
		
		// Tags text.
		$this->field_map[] = array(
			'from_tablename' => 'core_tags', 'from_fieldname' => 'tag_text',
			'to_type' => 'tags', 'to_fieldname' => 'name'
		);	
		
		/** Post Section ******************************************************/

		// Post id. Stores in postmeta.
		$this->field_map[] = array(
			'from_tablename' => 'posts', 'from_fieldname' => 'pid', 'from_expression' => 'WHERE posts.new_account = 0',
			'to_type' => 'record', 'to_fieldname' => '_fct_post_id'
		);
		
		// Period id. Stores in postmeta.
		$this->field_map[] = array(
			'from_tablename' => 'posts', 'from_fieldname' => 'account_id',
			'to_type' => 'record', 'to_fieldname' => '_fct_period_id',
			'callback_method' => 'callback_accountid_to_periodid'
		);
		
		// Account id. Stores in postmeta.
		$this->field_map[] = array(
			'from_tablename' => 'posts', 'from_fieldname' => 'account_id',
			'to_type' => 'record', 'to_fieldname' => '_fct_account_id',
			'callback_method' => 'callback_accountid'
		);
		
		// Author ip.
		$this->field_map[] = array(
			'from_tablename' => 'posts', 'from_fieldname' => 'ip_address',
			'to_type' => 'record', 'to_fieldname' => '__fct_author_ip'
		);	
			
		// Post author.
		$this->field_map[] = array(
			'from_tablename' => 'posts', 'from_fieldname' => 'author_id',
			'to_type' => 'record', 'to_fieldname' => 'post_author',
			'callback_method' => 'callback_userid'
		);
		
		// Account title.
		$this->field_map[] = array(
			'from_tablename' => 'posts', 'from_fieldname' => 'post_title',
			'to_type' => 'record', 'to_fieldname' => 'post_title'
		);
		
		// Account slug. Clean name.
		$this->field_map[] = array(
			'from_tablename' => 'posts', 'from_fieldname' => 'post_title',
			'to_type' => 'record', 'to_fieldname' => 'post_name',
			'callback_method' => 'callback_slug'
		);
		
		// Post content.
		$this->field_map[] = array(
			'from_tablename' => 'posts', 'from_fieldname' => 'post',
			'to_type' => 'record', 'to_fieldname' => 'post_content',
			'callback_method' => 'callback_html'
		);
		
		// Account id.  If no parent, than 0.
		$this->field_map[] = array(
			'from_tablename' => 'posts', 'from_fieldname' => 'account_id',
			'to_type' => 'record', 'to_fieldname' => 'post_parent',
			'callback_method' => 'callback_accountid'
		);

		// Account date update.
		$this->field_map[] = array(
			'from_tablename' => 'posts', 'from_fieldname' => 'post_date',
			'to_type' => 'record', 'to_fieldname' => 'post_date',
			'callback_method' => 'callback_datetime'
		);
		$this->field_map[] = array(
			'from_tablename' => 'posts', 'from_fieldname' => 'post_date',
			'to_type' => 'record', 'to_fieldname' => 'post_date_gmt',
			'callback_method' => 'callback_datetime'
		);
		$this->field_map[] = array(
			'from_tablename' => 'posts', 'from_fieldname' => 'edit_time',
			'to_type' => 'record', 'to_fieldname' => 'post_modified',
			'callback_method' => 'callback_datetime'
		);
		$this->field_map[] = array(
			'from_tablename' => 'posts', 'from_fieldname' => 'edit_time',
			'to_type' => 'record', 'to_fieldname' => 'post_modified_gmt',
			'callback_method' => 'callback_datetime'
		);

		/** User Section ******************************************************/

		// Store old User id. Stores in usermeta.
		$this->field_map[] = array(
			'from_tablename' => 'members', 'from_fieldname' => 'member_id',
			'to_type' => 'user', 'to_fieldname' => '_fct_user_id'
		);
		
		// Store old User password. Stores in usermeta serialized with salt.
		$this->field_map[] = array(
			'from_tablename' => 'members', 'from_fieldname' => 'members_pass_hash',
			'to_type' => 'user', 'to_fieldname' => '_fct_password',
			'callback_method' => 'callback_savepass'
		);

		// Store old User Salt. This is only used for the SELECT row info for the above password save
		$this->field_map[] = array(
			'from_tablename' => 'members', 'from_fieldname' => 'members_pass_salt',
			'to_type' => 'user', 'to_fieldname' => ''
		);
				
		// User password verify class. Stores in usermeta for verifying password.
		$this->field_map[] = array(
			'to_type' => 'user', 'to_fieldname' => '_fct_class',
			'default' => 'Invision'
		);
		
		// User name.
		$this->field_map[] = array(
			'from_tablename' => 'members', 'from_fieldname' => 'name',
			'to_type' => 'user', 'to_fieldname' => 'user_login'
		);
				
		// User email.
		$this->field_map[] = array(
			'from_tablename' => 'members', 'from_fieldname' => 'email',
			'to_type' => 'user', 'to_fieldname' => 'user_email'
		);
		
		// User registered.
		$this->field_map[] = array(
			'from_tablename' => 'members', 'from_fieldname' => 'joined',
			'to_type' => 'user', 'to_fieldname' => 'user_registered',
			'callback_method' => 'callback_datetime'
		);
				
/*	
 * Table pfields_content AND pfields_data	
		// User homepage.
		$this->field_map[] = array(
			'from_tablename' => 'members', 'from_fieldname' => 'homepage',
			'to_type' => 'user', 'to_fieldname' => 'user_url'
		);		
		
		// User aim.
		$this->field_map[] = array(
			'from_tablename' => 'members', 'from_fieldname' => 'aim',
			'to_type' => 'user', 'to_fieldname' => 'aim'
		);
		
		// User yahoo.
		$this->field_map[] = array(
			'from_tablename' => 'members', 'from_fieldname' => 'yahoo',
			'to_type' => 'user', 'to_fieldname' => 'yim'
		);
*/		
		
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
		$pass_array = array( 'hash' => $field, 'salt' => $row['members_pass_salt'] );
		return $pass_array;
	}

	/**
	 * This method is to take the pass out of the database and compare
	 * to a pass the user has typed in.
	 */
	public function authenticate_pass( $password, $serialized_pass )
	{
		$pass_array = unserialize( $serialized_pass );
		return ( $pass_array['hash'] == md5( md5( $pass_array['salt'] ) . md5( $this->to_char( $password ) ) ) );
	}

	public function to_char( $input )
	{
		$output = "";
		for( $i = 0; $i < strlen( $input ); $i++ )
		{
			$j = ord( $input{$i} );
			if( ( $j >= 65 && $j <= 90 )
				|| ( $j >= 97 && $j <= 122 )
				|| ( $j >= 48 && $j <= 57 ) )
			{
				$output .= $input{$i};
			}
			else
			{
				$output .= "&#" . ord( $input{$i} ) . ";";
			}
		}
		return $output;
	}
}
