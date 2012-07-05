<?php
/**
 * @version	1.5
 * @package	Synk
 * @author 	Dioscouri Design
 * @link 	http://www.dioscouri.com
 * @copyright Copyright (C) 2007 Dioscouri Design. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

/** ensure this file is being included by a parent file */
defined('_JEXEC') or die('Restricted access');

// Import library dependencies
jimport('joomla.plugin.plugin');

class plgUserSynkphpbb3 extends JPlugin
{
	/**
	 * Constructor 
	 *
	 * For php4 compatability we must not use the __constructor as a constructor for plugins
	 * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
	 * This causes problems with cross-referencing necessary for the observer design pattern.
	 *
	 * @param object $subject The object to observe
	 * @param 	array  $config  An array that holds the plugin configuration
	 * @since 1.5
	 */
	function plgUserSynkphpbb3(& $subject, $config) {
		parent::__construct($subject, $config);		
	}

	/**
	 * Gets a parameter value
	 *
	 * @access public
	 * @return mixed Parameter value
	 * @since 1.5
	 */
	function _getParameter( $name, $default='' ) {
		$return = "";
		$return = $this->params->get( $name, $default );
		return $return;
	}

	/**
	 * 
	 * @return 
	 * @param $article Object
	 */
	function _checkParameters( $item )
	{
		$success = true;
		$exceptions_list = @preg_replace( '/\s/', '', $this->_getParameter( 'exceptions_list', '' ) );
		$exceptions_array = explode( ',', $exceptions_list );
		
		foreach($exceptions_array as $k => $val){
			$exceptions_array[$k] = strtolower(trim($val));
		}
		
		// check exceptions (Usernames not User IDs like in other User Plugins) not to SYNK
		if (in_array(strtolower(trim($item['username'])), $exceptions_array)) { $success = false; }
				
		return $success;
	}
	
    /**
     * 
     * @return unknown_type
     */
    function _isInstalled()
    {
        $success = false;
        
        jimport('joomla.filesystem.file');
        if (JFile::exists(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_synk'.DS.'helpers'.DS.'synchronizations.php')) 
        {
            JTable::addIncludePath( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_synk'.DS.'tables'.DS );
            require_once( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_synk'.DS.'helpers'.DS.'synchronizations.php' );
            $success = true;
        }
        
        return $success;
    }
    
	/**
	 * This method should handle any login logic and report back to the subject
	 *
	 * @access	public
	 * @param 	array 	holds the user data
	 * @param 	array    extra options
	 * @return	boolean	True on success
	 * @since	1.5
	 */
	function onLoginUser( $user, $extra_options ) 
	{
		if (!$this->_isInstalled()) {
			return null;
		}

		jimport('joomla.user.helper');
		// assign the userid to user['id'] (onLogin doesn't populate this field in the array) 
		$user['id'] = intval( JUserHelper::getUserId($user['username']) );
		
		// trigger internal function
		$this->_doEvent( "onLoginUser", $user);
			
		return null;
		
	}
	
	
	/**
	 * Synk store user method
	 *
	 * Method is called before user data is stored in the database
	 * The user array, contains the old data, before the changes get saved
	 * in local Joomla DB
	 *
	 * @param 	array		holds the old user data
	 * @param 	boolean		true if a new user is stored
	 */
	function onBeforeStoreUser($user, $isnew) 
	{
		if(!$isnew){
			// load the table
			$this->old_user =& JTable::getInstance('user');
			$this->old_user->load( $user['id'] );
		}
	}
	
	
	/**
	 * Example store user method
	 *
	 * Method is called after user data is stored in the database
	 *
	 * @param 	array		holds the new user data
	 * @param 	boolean		true if a new user is stored
	 * @param	boolean		true if user was succesfully stored in the database
	 * @param	string		message
	 */
	function onAfterStoreUser($user, $isnew, $store_success, $store_message)
	{
		if (!$this->_isInstalled()) {
			return null;
		}
		
		// trigger internal function
		$this->_doEvent( "onAfterStoreUser", $user );
			
		return null;
	}
	
	
	/**
	 * Delete user from phpBB
	 *
	 * Method is called after user data is deleted from the database
	 *
	 * @param 	array		holds the user data
	 * @param	boolean		true if user was succesfully stored in the database
	 * @param	string		message
	 */
	function onAfterDeleteUser( $user, $delete_result, $error_result) 
	{	
		if (!$this->_isInstalled()) {
			return null;
		}
		
		// trigger internal function
		$this->_doEvent( "onAfterDeleteUser", $user );
			
		return null;
	}
	
	
	/**
	 * Prepare the various variables and trigger the actual SYNK functions
	 * after a few checks
	 * 
	 * @access private
	 * @param string The name of the event
	 * @param object A Joomla User Object
	 * @param boolean Is this a new Joomla user?
	 * @param string Plugin type
	 * @return unknown_type
	 */
	function _doEvent( $eventname, $user, $isnew=false, $type='user')
	{
		JTable::addIncludePath( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_synk'.DS.'tables'.DS );
		
		if (is_object($user)) {
			jimport('joomla.utilities.arrayhelper');
			$synkUser = JArrayHelper::fromObject( $user );	
		} elseif (is_array($user)) {
			$synkUser = $user;	
		}
		
		if (!isset($synkUser) || !$synkUser['id']) {
			return null;
		}
		
		$msg = new stdClass();
		$msg->error = '';
		$msg->message = '';
		
		$options = array();
		$options['user']	= $synkUser;
		$options['isnew']	= $isnew;
		$options['success']	= false;
		$options['msg']		= $msg;
		$options['synktype']= $type;
		$options['event'] 	= $eventname;
		
		$success = &$options['success'];
		
		// grab all relevant synks
		$synchronizations = &SynkHelperSynchronizations::getSynchronizations( $options['event'] );
		
		// if not to be synchronized, continue
		if (!($doSynk = $this->_checkParameters( $options['user'] ))) {
			$success = true;
			return $success;
		}
		
		// Define phpBB variables
		$this->define_phpBB3_variables();
		
		// Loop through Synks
		for ($i = 0; $i < count($synchronizations); $i++) {
			$synk = $synchronizations[$i];
			
			if (!$this->canRunSynchronization( $synk )) {
				return $success;
			}
					
			/**
			*
			* RUN THE SYNK
			*
			*/
			switch( strtolower($options['event']) )
			{
				case "onafterstoreuser":
				case "onbeforedeleteuser":
				case "onloginuser":
					$runSynk = $this->create_or_update_phpBB3_user( $synk, $options ); 
				  break;
				case "onafterdeleteuser":
					$runSynk = $this->delete_phpBB3_user( $synk, $options ); 
				  break;
				default:
					break;
			}
			
			/**
			 * 
			 * LOG The SYNK
			 * 
			 */
			if(isset($runSynk)){
				$this->logSynchronization( $synk, $options, $runSynk );
				unset($runSynk);
			}
		}
	}
	
	/**
     * Checks that Synk can be run
     * @param object A valid synchronization object
     * @return boolean
     */
    function canRunSynchronization( $synk )
    {
        return SynkHelperSynchronizations::canRun( $synk );
    }
    
	/**
     * Log a Synk
     * @param object A valid synchronization object
     * @param array See plugin for parameters
     * @param boolean If synk was successful or no
     * @return boolean
     */
    function logSynchronization( $synk, &$options, $runSynk )
    {
        return SynkHelperSynchronizations::createLog( $synk, $options, $runSynk );
    }
    
    
    /**
     * Define parameters as used in phpBB3 code
     */
    function define_phpBB3_variables()
    {
    	// phpBB Tables Prefix in target DB
    	$table_prefix = $this->_getParameter('table_prefix');
    	
    	// phpBB definitions located in includes/constants.php
    	
    	// User related
    	define('ANONYMOUS', 1);
    	
    	define('AVATAR_UPLOAD', 1);
    	
    	define('USER_NORMAL', 0);
		define('USER_INACTIVE', 1);
		define('USER_IGNORE', 2);
		define('USER_FOUNDER', 3);
    	
    	define('INACTIVE_REGISTER', 1);
    	
    	// Group related
    	define('GROUP_SPECIAL', 3);
    	
    	// Private messaging - Do NOT change these values
		define('PRIVMSGS_NO_BOX', -3);
		
		// Notify methods
		define('NOTIFY_EMAIL', 0);
    	
    	// Table names
		define('ACL_GROUPS_TABLE',			$table_prefix . 'acl_groups');
		define('ACL_OPTIONS_TABLE',			$table_prefix . 'acl_options');
		define('ACL_ROLES_DATA_TABLE',		$table_prefix . 'acl_roles_data');
		define('ACL_ROLES_TABLE',			$table_prefix . 'acl_roles');
		define('ACL_USERS_TABLE',			$table_prefix . 'acl_users');
		define('ATTACHMENTS_TABLE',			$table_prefix . 'attachments');
		define('BANLIST_TABLE',				$table_prefix . 'banlist');
		define('BBCODES_TABLE',				$table_prefix . 'bbcodes');
		define('BOOKMARKS_TABLE',			$table_prefix . 'bookmarks');
		define('BOTS_TABLE',				$table_prefix . 'bots');
		define('CONFIG_TABLE',				$table_prefix . 'config');
		define('CONFIRM_TABLE',				$table_prefix . 'confirm');
		define('DISALLOW_TABLE',			$table_prefix . 'disallow');
		define('DRAFTS_TABLE',				$table_prefix . 'drafts');
		define('EXTENSIONS_TABLE',			$table_prefix . 'extensions');
		define('EXTENSION_GROUPS_TABLE',	$table_prefix . 'extension_groups');
		define('FORUMS_TABLE',				$table_prefix . 'forums');
		define('FORUMS_ACCESS_TABLE',		$table_prefix . 'forums_access');
		define('FORUMS_TRACK_TABLE',		$table_prefix . 'forums_track');
		define('FORUMS_WATCH_TABLE',		$table_prefix . 'forums_watch');
		define('GROUPS_TABLE',				$table_prefix . 'groups');
		define('ICONS_TABLE',				$table_prefix . 'icons');
		define('LANG_TABLE',				$table_prefix . 'lang');
		define('LOG_TABLE',					$table_prefix . 'log');
		define('MODERATOR_CACHE_TABLE',		$table_prefix . 'moderator_cache');
		define('MODULES_TABLE',				$table_prefix . 'modules');
		define('POLL_OPTIONS_TABLE',		$table_prefix . 'poll_options');
		define('POLL_VOTES_TABLE',			$table_prefix . 'poll_votes');
		define('POSTS_TABLE',				$table_prefix . 'posts');
		define('PRIVMSGS_TABLE',			$table_prefix . 'privmsgs');
		define('PRIVMSGS_FOLDER_TABLE',		$table_prefix . 'privmsgs_folder');
		define('PRIVMSGS_RULES_TABLE',		$table_prefix . 'privmsgs_rules');
		define('PRIVMSGS_TO_TABLE',			$table_prefix . 'privmsgs_to');
		define('PROFILE_FIELDS_TABLE',		$table_prefix . 'profile_fields');
		define('PROFILE_FIELDS_DATA_TABLE',	$table_prefix . 'profile_fields_data');
		define('PROFILE_FIELDS_LANG_TABLE',	$table_prefix . 'profile_fields_lang');
		define('PROFILE_LANG_TABLE',		$table_prefix . 'profile_lang');
		define('RANKS_TABLE',				$table_prefix . 'ranks');
		define('REPORTS_TABLE',				$table_prefix . 'reports');
		define('REPORTS_REASONS_TABLE',		$table_prefix . 'reports_reasons');
		define('SEARCH_RESULTS_TABLE',		$table_prefix . 'search_results');
		define('SEARCH_WORDLIST_TABLE',		$table_prefix . 'search_wordlist');
		define('SEARCH_WORDMATCH_TABLE',	$table_prefix . 'search_wordmatch');
		define('SESSIONS_TABLE',			$table_prefix . 'sessions');
		define('SESSIONS_KEYS_TABLE',		$table_prefix . 'sessions_keys');
		define('SITELIST_TABLE',			$table_prefix . 'sitelist');
		define('SMILIES_TABLE',				$table_prefix . 'smilies');
		define('STYLES_TABLE',				$table_prefix . 'styles');
		define('STYLES_TEMPLATE_TABLE',		$table_prefix . 'styles_template');
		define('STYLES_TEMPLATE_DATA_TABLE',$table_prefix . 'styles_template_data');
		define('STYLES_THEME_TABLE',		$table_prefix . 'styles_theme');
		define('STYLES_IMAGESET_TABLE',		$table_prefix . 'styles_imageset');
		define('STYLES_IMAGESET_DATA_TABLE',$table_prefix . 'styles_imageset_data');
		define('TOPICS_TABLE',				$table_prefix . 'topics');
		define('TOPICS_POSTED_TABLE',		$table_prefix . 'topics_posted');
		define('TOPICS_TRACK_TABLE',		$table_prefix . 'topics_track');
		define('TOPICS_WATCH_TABLE',		$table_prefix . 'topics_watch');
		define('USER_GROUP_TABLE',			$table_prefix . 'user_group');
		define('USERS_TABLE',				$table_prefix . 'users');
		define('WARNINGS_TABLE',			$table_prefix . 'warnings');
		define('WORDS_TABLE',				$table_prefix . 'words');
		define('ZEBRA_TABLE',				$table_prefix . 'zebra');
    }
    
    /**
     * Create a new user of update an axisting one, depending on if
     * the Joomla username exists on the target phpBB3 DB as well
     * 
     * @access private
     * @param object The synk Object
     * @param object The options Object
     * @return boolean True or false
     */
    function create_or_update_phpBB3_user($synk, &$options)
    {
    	$user 		= $options['user'];
    	$success 	= &$options['success'];
		$msg 		= &$options['msg'];
		
		$success = false;
		
		// Get target database object
    	if(!($synkdb = &$this->getDatabase( $synk->databaseid ))){
			$msg->message .= JText::_('Failed to get target Database Object');
			return $success;
		}
		if ( $synkdb->getErrorNum() ) {
			$msg->message .= $synkdb->getErrorMsg();
			return $success;
		}
		
		// Save target DB Object for other members of this class
		$this->synkdb =& $synkdb;
		
		// Load phpBB3 config table
		$synkdb->setQuery("SELECT * FROM `".CONFIG_TABLE."`");
		if(!($rows = $synkdb->loadObjectList())){
			$msg->message .= ' - '.JText::_('Failed to load phpBB3 config').$synkdb->getErrorMsg();
			return $success;
		}
		
		unset($this->phpBB3_config);
		
		// Save phpBB3 $config. for other members of this class
		foreach($rows as $row) $this->phpBB3_config[$row->config_name] = $row->config_value;
			
    	
		if(isset($this->old_user) && $this->old_user->username != ''){
			$query = "SELECT `user_id` FROM `".USERS_TABLE."` WHERE `username`='{$this->old_user->username}'";
		} else {
			$query = "SELECT `user_id` FROM `".USERS_TABLE."` WHERE `username`='{$user['username']}'";
		}
		$synkdb->setQuery($query);
		
		if(!($obj = $synkdb->loadObject())){
			// Create the user
    		return $this->create_phpBB3_user($synk, $options);
		
		} else {
			// Update the existing user
			return $this->update_phpBB3_user($synk, $options);
		}
    }
    
    /**
     * Add a new phpBB3 user
     * 
     * @access private
     * @param object The synk Object
     * @param object The options Object
     * @return boolean True or false
     */
    function create_phpBB3_user($synk, &$options)
    {
    	// init variables
		$user 		= $options['user'];
		$isnew 		= $options['isnew'];
		$success 	= &$options['success'];
		$msg 		= &$options['msg'];
		$event 		= $options['event'];
		
		$success = false;
		
		$username = $user['username'];
		$user_password = $this->phpbb_hash($user['password']);
		$user_email = $user['email'];
		$user_email_hash = crc32($user_email).strlen($user_email);
		
		// Execute published plugins
		// Before this the coder should make sure global instances of default Joomla Tables,
		// have not been left by setDBO() pointing to the target DB.
		$args = array();
		$args = $options;
		$args['synk'] = $synk;
		$dispatcher	   =& JDispatcher::getInstance();
		$dispatcher->trigger('onBeforeRunSynchronizationSynk', array( $options, $args ) );
		
		// phpBB3 config table
		$config =& $this->phpBB3_config;
		
		// The target DB Object
		$synkdb =& $this->synkdb;
		
		// Get the group_id to use
    	$sql = "SELECT `group_id` FROM `".GROUPS_TABLE."`".
				" WHERE `group_name`='REGISTERED' AND `group_type`=".GROUP_SPECIAL;
    	$synkdb->setQuery($sql);
    	$synkdb->query($sql);
    	if(!($obj = $synkdb->loadObject())){
    		$msg->message .= ' - '.JText::_('Group REGISTERED was not found').$synkdb->getErrorMsg();
    		return $success;
    	}
    	
    	$group_id = $obj->group_id;
		
		$sql_ary = array(
			'username'				=> $user['username'],
			'username_clean'		=> trim(strtolower($user['username'])), // TODO utf8_clean_string() should be used
			'user_password'			=> $this->phpbb_hash($user['password_clear']),
			'user_pass_convert'		=> 0,
			'user_email'			=> strtolower($user['email']),
			'user_email_hash'		=> crc32(strtolower($user['email'])) . strlen($user['email']),
			'group_id'				=> $group_id,
			'user_type'				=> USER_NORMAL,
			
			'user_permissions'		=> '',
			'user_timezone'			=> $config['board_timezone'],
			'user_dateformat'		=> $config['default_dateformat'],
			'user_lang'				=> $config['default_lang'],
			'user_style'			=> (int) $config['default_style'],
			'user_actkey'			=> '',
			'user_ip'				=> '',
			'user_regdate'			=> time(),
			'user_passchg'			=> time(),
			'user_options'			=> 895,
	
			'user_inactive_reason'	=> 0,
			'user_inactive_time'	=> 0,
			'user_lastmark'			=> time(),
			'user_lastvisit'		=> 0,
			'user_lastpost_time'	=> 0,
			'user_lastpage'			=> '',
			'user_posts'			=> 0,
			'user_dst'				=> (int) $config['board_dst'],
			'user_colour'			=> '',
			'user_occ'				=> '',
			'user_interests'		=> '',
			'user_avatar'			=> '',
			'user_avatar_type'		=> 0,
			'user_avatar_width'		=> 0,
			'user_avatar_height'	=> 0,
			'user_new_privmsg'		=> 0,
			'user_unread_privmsg'	=> 0,
			'user_last_privmsg'		=> 0,
			'user_message_rules'	=> 0,
			'user_full_folder'		=> PRIVMSGS_NO_BOX,
			'user_emailtime'		=> 0,
	
			'user_notify'			=> 0,
			'user_notify_pm'		=> 1,
			'user_notify_type'		=> NOTIFY_EMAIL,
			'user_allow_pm'			=> 1,
			'user_allow_viewonline'	=> 1,
			'user_allow_viewemail'	=> 1,
			'user_allow_massemail'	=> 1,
	
			'user_sig'					=> '',
			'user_sig_bbcode_uid'		=> '',
			'user_sig_bbcode_bitfield'	=> '',
	
			'user_form_salt'		=> $this->unique_id(),
		);
	
		$sql = 'INSERT INTO ' . USERS_TABLE . ' SET ';
		
		foreach($sql_ary as $key => $val) $sql .= "`$key`='".$synkdb->getEscaped($val)."',";
		$sql = substr($sql, 0, -1);
		
		$synkdb->setQuery($sql);
		if(!$synkdb->query()){
			$msg->message .= ' - '.JText::_('New User Insert Into phpBB3 users table failed').$synkdb->getErrorMsg();
			return $success;
		}
	
		$user_id = $synkdb->insertid();
	
		// Place into appropriate group...
		$sql = "INSERT INTO `".USER_GROUP_TABLE."` SET ".
			"`user_id`=$user_id, ".
			"`group_id`=$group_id, ".
			"`user_pending`=0";
		$synkdb->setQuery($sql);
		if(!$synkdb->query()){
			$msg->message .= ' - '.JText::_('Failed to insert entry in').' '.USER_GROUP_TABLE;
			return $success;
		}
	
		// Now make it the users default group...
		if(!$this->group_set_user_default($msg, $sql_ary['group_id'], array($user_id), false)){
			$msg->message .= ' - '.JText::_('group_set_user_default() failed');
			return $success;
		}
	
		// set the newest user and adjust the user count if the user is a normal user and no activation mail is sent
		if ($sql_ary['user_type'] == USER_NORMAL)
		{
			if(!$this->set_config('newest_user_id', $user_id, true) ||
				!$this->set_config('newest_username', $sql_ary['username'], true) ||
				!$this->set_config_count('num_users', 1, true)){
					
				$msg->message .= ' - '.JText::_('set_config() failed (1)');
				return $success;
			}
	
			$sql = 'SELECT group_colour
				FROM ' . GROUPS_TABLE . '
				WHERE group_id = ' . (int) $sql_ary['group_id'];
			$synkdb->setQuery($sql);
			
			if(!($row = $synkdb->loadAssoc())){
				$msg->message .= ' - '.JText::_('Failed to load group_color field from ').' '.GROUPS_TABLE;
				return $success;
			}
	
			if(!$this->set_config('newest_user_colour', $row['group_colour'], true)){
				$msg->message .= ' - '.JText::_('set_config() failed (2)');
				return $success;
			}
		}
		
		// if here, everything worked
		$success = true;
		
		// Execute published plugins
		// Before this the coder should make sure global instances of default Joomla Tables,
		// have not been left by setDBO() pointing to the target DB.
		$args['success'] = $success;
		$dispatcher	   =& JDispatcher::getInstance();
		$dispatcher->trigger('onAfterRunSynchronizationSynk', array($options, $args ) );
				
		return $success;
    }
    
    
    /**
     * Update a phpBB3 user
     * 
     * @access private
     * @param object The synk Object
     * @param object The options Object
     * @return boolean True or false
     */
    function update_phpBB3_user($synk, &$options)
    {
   		// init variables
		$user 		= $options['user'];
		$isnew 		= $options['isnew'];
		$success 	= &$options['success'];
		$msg 		= &$options['msg'];
		$event 		= $options['event'];
		
		$success = false;
		
		$synkdb =& $this->synkdb;
		
		
		$username = $user['username'];
		
		if(isset($user['password_clear']) && $user['password_clear'] != ''){
			$user_password = $this->phpbb_hash($user['password_clear']);
		} else {
			$user_password = $this->phpbb_hash($user['password']);
		}
		$user_email = $user['email'];
		$user_email_hash = crc32($user_email).strlen($user_email);
		
		// Execute published plugins
		// Before this the coder should make sure global instances of default Joomla Tables,
		// have not been left by setDBO() pointing to the target DB.
		$args = array();
		$args = $options;
		$args['synk'] = $synk;
		$dispatcher	   =& JDispatcher::getInstance();
		$dispatcher->trigger('onBeforeRunSynchronizationSynk', array( $options, $args ) );
		
		if(isset($this->old_user) && $this->old_user->username != ''){
			$sql = "SELECT `user_id` FROM `".USERS_TABLE."` WHERE `username`='{$this->old_user->username}'";
		} else {
			$sql = "SELECT `user_id` FROM `".USERS_TABLE."` WHERE `username`='$username'";
		}
		$synkdb->setQuery($sql);
		if(!($obj = $synkdb->loadObject())){
			$msg->message .= ' - '.JText::_('The username to SYNK was not found in phpBB3');
    		return $success;
		}
		
		$sql = "UPDATE `".USERS_TABLE."` SET ".
				"`username`='".$synkdb->getEscaped($username)."', ".
				"`user_password`='".$synkdb->getEscaped($user_password)."', ".
				"`user_email`='".$synkdb->getEscaped(strtolower($user_email))."', ".
				"`user_email_hash`='".$synkdb->getEscaped($user_email_hash)."'";
		
		// If username changed
		// TODO use the utf8_clean_string(). But its a mess to extract this.... thing from phpBB3
		// so expect problems if non latin characters usernames are used
		if(isset($this->old_user) && $this->old_user->username != $username){
			$sql .= ", `username_clean`='".$synkdb->getEscaped(trim(strtolower($username)))."'";
		}
		
		$sql .= " WHERE `user_id`={$obj->user_id}";
		
		$synkdb->setQuery($sql);
		if(!$synkdb->query()){
			$msg->message .= ' - '.JText::_('Error while updating user details: ').$synkdb->getErrorMsg();
    		return $success;
		}
		
		// If username changed update a few more tables
		if(isset($this->old_user) && $this->old_user->username != $username){
			// Code from update_user_name() function in phpBB3 includes/functions_user.php
			$update_ary = array(
				FORUMS_TABLE			=> array('forum_last_poster_name'),
				MODERATOR_CACHE_TABLE	=> array('username'),
				POSTS_TABLE				=> array('post_username'),
				TOPICS_TABLE			=> array('topic_first_poster_name', 'topic_last_poster_name'),
			);

			foreach ($update_ary as $table => $field_ary)
			{
				foreach ($field_ary as $field)
				{
					$sql = "UPDATE `$table`	SET ".
						"`$field` = '" . $synkdb->getEscaped($username) . "'
						WHERE `$field` = '" . $synkdb->getEscaped($this->old_user->username) . "'";
					$synkdb->setQuery($sql);
					
					if(!$synkdb->query()){
						$msg->message .= ' - '.JText::_('Error while updating phpBB3 table ').$table.': '.$synkdb->getErrorMsg();
    					return $success;
					}
				}
			}
			
			// TODO Implement the cache cleaning below for SYNK. (A phpBB3 bridge-plugin is recommended though).
			// This would need to delete a file in the target installation's cache dir.
			// It can be done if target phpBB3 is on the same server and permissions allow it only.
			// See phpBB3: includes/acm/acm_file.php for more 
			//
			// Because some tables/caches use username-specific data we need to purge this here.
			// $cache->destroy('sql', MODERATOR_CACHE_TABLE);
		}
		
		$success = true;
		
		// Execute published plugins
		// Before this the coder should make sure global instances of default Joomla Tables,
		// have not been left by setDBO() pointing to the target DB.
		$args['success'] = $success;
		$dispatcher	   =& JDispatcher::getInstance();
		$dispatcher->trigger('onAfterRunSynchronizationSynk', array( $options, $args ) );
		
		return $success;
    }
    
	/**
     * Delete a user from phpBB3
     * 
	 * @access private
     * @param object The synk Object
     * @param object The options Object
     * @return boolean True or false
     */
	function delete_phpBB3_user($synk, &$options)
	{
    	// init variables
		$user 		= $options['user'];
		$isnew 		= $options['isnew'];
		$success 	= &$options['success'];
		$msg 		= &$options['msg'];
		$event 		= $options['event'];
		
		$success = false;
		
		// Get target database object
		if(!($synkdb = &$this->getDatabase( $synk->databaseid ))){
			$msg->message .= JText::_('Failed to get target Database Object');
			return $success;
		}
		if ( $synkdb->getErrorNum() ) {
			$msg->message .= $synkdb->getErrorMsg();
			return $success;
		}
		
		// Save target DB Object for other members of this class
		$this->synkdb =& $synkdb;
		
		// Load phpBB3 config table
		$synkdb->setQuery("SELECT * FROM `".CONFIG_TABLE."`");
		if(!($rows = $synkdb->loadObjectList())){
			$msg->message .= ' - '.JText::_('Failed to load phpBB3 config').$synkdb->getErrorMsg();
			return $success;
		}
		
		unset($this->phpBB3_config);
		
		// Save phpBB3 $config. for other members of this class
		foreach($rows as $row) $this->phpBB3_config[$row->config_name] = $row->config_value;
		
		// execute published plugins
		$args = array();
		$args = $options;
		$args['synk'] = $synk;
		$dispatcher	   =& JDispatcher::getInstance();
		$dispatcher->trigger('onBeforeRunSynchronizationSynk', array( $options, $args ) );
		
		// Get target User' User ID
		$query = "SELECT * FROM `".USERS_TABLE."` WHERE `username`='".$synkdb->getEscaped($user['username'])."'";
		$synkdb->setQuery($query);
		if(!($user_row = $synkdb->loadAssoc())){
			$msg->message .= JText::_('Failed to locate on target phpBB3 DB the user: ').$user['username'];
			return $success;
		}
		
		$user_id = $user_row['user_id'];
		
		// Below is code from phpBB3 includes/functions_user.php , function user_delete(),
		// converted for SYNK
		
		// [ START OF CONVERTED phpBB3 code ] 
	
		// Before we begin, we will remove the reports the user issued.
		$sql = 'SELECT r.post_id, p.topic_id
			FROM ' . REPORTS_TABLE . ' r, ' . POSTS_TABLE . ' p
			WHERE r.user_id = ' . $user_id . '
				AND p.post_id = r.post_id';
		$synkdb->setQuery($sql);
		$rows = $synkdb->loadAssocList();
		
		if($synkdb->getErrorNum()){
			$msg->message .= ' - '.JText::_('Error in query').": $sql :".$synkdb->getErrorMsg();
			return $success;
		}
	
		$report_posts = $report_topics = array();
		foreach($rows as $row)
		{
			$report_posts[] = $row['post_id'];
			$report_topics[] = $row['topic_id'];
		}
	
		if (sizeof($report_posts))
		{
			$report_posts = array_unique($report_posts);
			$report_topics = array_unique($report_topics);
	
			// Get a list of topics that still contain reported posts
			$sql = 'SELECT DISTINCT topic_id
				FROM ' . POSTS_TABLE . '
				WHERE `topic_id` IN (' .implode(',', $report_topics). ')
					AND post_reported = 1
					AND `post_id` IN(' .implode(',', $report_posts). ')';
			$synkdb->setQuery($sql);
			
			if($synkdb->getErrorNum()){
				$msg->message .= ' - '.JText::_('Error in query').": $sql :".$synkdb->getErrorMsg();
				return $success;
			}
			
			$rows = $synkdb->loadAssocList();
	
			$keep_report_topics = array();
			foreach($rows as $row)
			{
				$keep_report_topics[] = $row['topic_id'];
			}
	
			if (sizeof($keep_report_topics))
			{
				$report_topics = array_diff($report_topics, $keep_report_topics);
			}
			unset($keep_report_topics);
	
			// Now set the flags back
			$sql = 'UPDATE ' . POSTS_TABLE . '
				SET post_reported = 0
				WHERE `post_id` IN (' .implode(',', $report_posts). ')';
			$synkdb->setQuery($sql);
			
			if(!($synkdb->query())){
				$msg->message .= ' - '.JText::_('Error in query').": $sql :".$synkdb->getErrorMsg();
				return $success;
			}
	
			if (sizeof($report_topics))
			{
				$sql = 'UPDATE ' . TOPICS_TABLE . '
					SET topic_reported = 0
					WHERE `topic_id` IN (' .implode(',', $report_topics). ')';
				$synkdb->setQuery($sql);
				
				if(!($synkdb->query())){
					$msg->message .= ' - '.JText::_('Error in query').": $sql :".$synkdb->getErrorMsg();
					return $success;
				}
			}
		}
	
		// Remove reports
		$sql = "DELETE FROM `".REPORTS_TABLE."` WHERE `user_id`=$user_id";
		$synkdb->setQuery($sql);
		
    	if(!($synkdb->query())){
			$msg->message .= ' - '.JText::_('Error in query').": $sql :".$synkdb->getErrorMsg();
			return $success;
		}
		
		
		if ($user_row['user_avatar'] && $user_row['user_avatar_type'] == AVATAR_UPLOAD)
		{
			avatar_delete('user', $user_row);
		}
		
		
		// The username to use for posts belonging to the deleted user
		// TODO Support deleting posts when a user is deleted (too complicated). Now posts remain as user's GUEST.
		$post_username = 'GUEST';
	
		// If the user is inactive and newly registered we assume no posts from this user being there...
		if ($user_row['user_type'] == USER_INACTIVE && $user_row['user_inactive_reason'] == INACTIVE_REGISTER && !$user_row['user_posts'])
		{
		}
		else
		{
			$sql = 'UPDATE ' . FORUMS_TABLE . '
				SET forum_last_poster_id = ' . ANONYMOUS . ", forum_last_poster_name = '" . $synkdb->getEscaped($post_username) . "', forum_last_poster_colour = ''
				WHERE forum_last_poster_id = $user_id";
			$synkdb->setQuery($sql);
			if(!($synkdb->query())){
				$msg->message .= ' - '.JText::_('Error in query').": $sql :".$synkdb->getErrorMsg();
				return $success;
			}

			$sql = 'UPDATE ' . POSTS_TABLE . '
				SET poster_id = ' . ANONYMOUS . ", post_username = '" . $synkdb->getEscaped($post_username) . "'
				WHERE poster_id = $user_id";
			$synkdb->setQuery($sql);
			if(!($synkdb->query())){
				$msg->message .= ' - '.JText::_('Error in query').": $sql :".$synkdb->getErrorMsg();
				return $success;
			}

			$sql = 'UPDATE ' . POSTS_TABLE . '
				SET post_edit_user = ' . ANONYMOUS . "
				WHERE post_edit_user = $user_id";
			$synkdb->setQuery($sql);
			if(!($synkdb->query())){
				$msg->message .= ' - '.JText::_('Error in query').": $sql :".$synkdb->getErrorMsg();
				return $success;
			}

			$sql = 'UPDATE ' . TOPICS_TABLE . '
				SET topic_poster = ' . ANONYMOUS . ", topic_first_poster_name = '" . $synkdb->getEscaped($post_username) . "', topic_first_poster_colour = ''
				WHERE topic_poster = $user_id";
			$synkdb->setQuery($sql);
			if(!($synkdb->query())){
				$msg->message .= ' - '.JText::_('Error in query').": $sql :".$synkdb->getErrorMsg();
				return $success;
			}

			$sql = 'UPDATE ' . TOPICS_TABLE . '
				SET topic_last_poster_id = ' . ANONYMOUS . ", topic_last_poster_name = '" . $synkdb->getEscaped($post_username) . "', topic_last_poster_colour = ''
				WHERE topic_last_poster_id = $user_id";
			$synkdb->setQuery($sql);
			if(!($synkdb->query())){
				$msg->message .= ' - '.JText::_('Error in query').": $sql :".$synkdb->getErrorMsg();
				return $success;
			}

			// Since we change every post by this author, we need to count this amount towards the anonymous user

			// Update the post count for the anonymous user
			if ($user_row['user_posts'])
			{
				$sql = 'UPDATE ' . USERS_TABLE . '
					SET user_posts = user_posts + ' . $user_row['user_posts'] . '
					WHERE user_id = ' . ANONYMOUS;
				$synkdb->setQuery($sql);
				if(!($synkdb->query())){
					$msg->message .= ' - '.JText::_('Error in query').": $sql :".$synkdb->getErrorMsg();
					return $success;
				}
			}
		}
		
	
		$table_ary = array(USERS_TABLE, USER_GROUP_TABLE, TOPICS_WATCH_TABLE, FORUMS_WATCH_TABLE, ACL_USERS_TABLE, TOPICS_TRACK_TABLE, TOPICS_POSTED_TABLE, FORUMS_TRACK_TABLE, PROFILE_FIELDS_DATA_TABLE, MODERATOR_CACHE_TABLE, DRAFTS_TABLE, BOOKMARKS_TABLE, SESSIONS_KEYS_TABLE);
	
		foreach ($table_ary as $table)
		{
			$sql = "DELETE FROM $table
				WHERE user_id = $user_id";
			$synkdb->setQuery($sql);
			
			if(!($synkdb->query())){
				$msg->message .= ' - '.JText::_('Error in query').": $sql :".$synkdb->getErrorMsg();
				return $success;
			}
		}
		
		// TODO caching destroy requires access to the filesystem
		// $cache->destroy('sql', MODERATOR_CACHE_TABLE);
	
		// Delete the user_id from the banlist
		$sql = 'DELETE FROM ' . BANLIST_TABLE . '
			WHERE ban_userid = ' . $user_id;
		$synkdb->setQuery($sql);
    	if(!($synkdb->query())){
			$msg->message .= ' - '.JText::_('Error in query').": $sql :".$synkdb->getErrorMsg();
			return $success;
		}
	
		// Delete the user_id from the session table
		$sql = 'DELETE FROM ' . SESSIONS_TABLE . '
			WHERE session_user_id = ' . $user_id;
		$synkdb->setQuery($sql);
    	if(!($synkdb->query())){
			$msg->message .= ' - '.JText::_('Error in query').": $sql :".$synkdb->getErrorMsg();
			return $success;
		}
	
		// Remove any undelivered mails...
		$sql = 'SELECT msg_id, user_id
			FROM ' . PRIVMSGS_TO_TABLE . '
			WHERE author_id = ' . $user_id . '
				AND folder_id = ' . PRIVMSGS_NO_BOX;
		$synkdb->setQuery($sql);
		$rows = $synkdb->loadAssocList();
		
    	if($synkdb->getErrorNum()){
			$msg->message .= ' - '.JText::_('Error in query').": $sql :".$synkdb->getErrorMsg();
			return $success;
		}
	
		$undelivered_msg = $undelivered_user = array();
		foreach($rows as $row)
		{
			$undelivered_msg[] = $row['msg_id'];
			$undelivered_user[$row['user_id']][] = true;
		}
	
		if (sizeof($undelivered_msg))
		{
			$sql = 'DELETE FROM ' . PRIVMSGS_TABLE . '
				WHERE `msg_id` IN (' .implode(',', $undelivered_msg).")";
			$synkdb->setQuery($sql);
			
			if(!($synkdb->query())){
				$msg->message .= ' - '.JText::_('Error in query').": $sql :".$synkdb->getErrorMsg();
				return $success;
			}
		}
	
		$sql = 'DELETE FROM ' . PRIVMSGS_TO_TABLE . '
			WHERE author_id = ' . $user_id . '
				AND folder_id = ' . PRIVMSGS_NO_BOX;
		$synkdb->setQuery($sql);
    	
		if(!($synkdb->query())){
			$msg->message .= ' - '.JText::_('Error in query').": $sql :".$synkdb->getErrorMsg();
			return $success;
		}
	
		// Delete all to-information
		$sql = 'DELETE FROM ' . PRIVMSGS_TO_TABLE . '
			WHERE user_id = ' . $user_id;
		$synkdb->setQuery($sql);
    	
		if(!($synkdb->query())){
			$msg->message .= ' - '.JText::_('Error in query').": $sql :".$synkdb->getErrorMsg();
			return $success;
		}
	
		// Set the remaining author id to anonymous - this way users are still able to read messages from users being removed
		$sql = 'UPDATE ' . PRIVMSGS_TO_TABLE . '
			SET author_id = ' . ANONYMOUS . '
			WHERE author_id = ' . $user_id;
		$synkdb->setQuery($sql);
		
    	if(!($synkdb->query())){
			$msg->message .= ' - '.JText::_('Error in query').": $sql :".$synkdb->getErrorMsg();
			return $success;
		}
	
		$sql = 'UPDATE ' . PRIVMSGS_TABLE . '
			SET author_id = ' . ANONYMOUS . '
			WHERE author_id = ' . $user_id;
		$synkdb->setQuery($sql);
		
    	if(!($synkdb->query())){
			$msg->message .= ' - '.JText::_('Error in query').": $sql :".$synkdb->getErrorMsg();
			return $success;
		}
	
		foreach ($undelivered_user as $_user_id => $ary)
		{
			if ($_user_id == $user_id)
			{
				continue;
			}
	
			$sql = 'UPDATE ' . USERS_TABLE . '
				SET user_new_privmsg = user_new_privmsg - ' . sizeof($ary) . ',
					user_unread_privmsg = user_unread_privmsg - ' . sizeof($ary) . '
				WHERE user_id = ' . $_user_id;
			$synkdb->setQuery($sql);
			
			if(!($synkdb->query())){
				$msg->message .= ' - '.JText::_('Error in query').": $sql :".$synkdb->getErrorMsg();
				return $success;
			}
		}
		
		// Reset newest user info if appropriate
		if ($config['newest_user_id'] == $user_id)
		{
			if(!$this->update_last_username()){
				$msg->message .= ' - '.JText::_('update_last_username() failed');
				return $success;
			}
		}
	
		// Decrement number of users if this user is active
		if ($user_row['user_type'] != USER_INACTIVE && $user_row['user_type'] != USER_IGNORE)
		{
			if(!$this->set_config_count('num_users', -1, true)){
				$msg->message .= ' - '.JText::_('set_config_count() failed (1)');
				return $success;
			}
		}
		
		// [ END OF CONVERTED phpBB3 code ] 
		
		$success = true;
		
		// execute published plugins
		$args['success'] = $success;
		$dispatcher	   =& JDispatcher::getInstance();
		$dispatcher->trigger('onAfterRunSynchronizationSynk', array( $options, $args ) );
		
		return $success;
    }
    
    
	/**
     * Returns a new database connection
     * 
     * @param mixed An ID number
     * @return object
     */
    function &getDatabase( $id )
    {
        return SynkHelperSynchronizations::getDatabase( $id );
    }

    
/*
 * Functions below are from phpBB 3.0.5, converted where needed for SYNK
 */
    
    
    /**
     *
     * @version Version 0.1 / slightly modified for phpBB 3.0.x (using $H$ as hash type identifier)
     *
     * Portable PHP password hashing framework.
     *
     * Written by Solar Designer <solar at openwall.com> in 2004-2006 and placed in
     * the public domain.
     *
     * There's absolutely no warranty.
     *
     * The homepage URL for this framework is:
     *
     *	http://www.openwall.com/phpass/
     *
     * Please be sure to update the Version line if you edit this file in any way.
     * It is suggested that you leave the main version number intact, but indicate
     * your project name (after the slash) and add your own revision information.
     *
     * Please do not change the "private" password hashing method implemented in
     * here, thereby making your hashes incompatible.  However, if you must, please
     * change the hash type identifier (the "$P$") to something different.
     *
     * Obviously, since this code is in the public domain, the above are not
     * requirements (there can be none), but merely suggestions.
     *
     *
     * Hash the password
     */
    function phpbb_hash($password)
    {
    	$itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    	$random_state = $this->unique_id();
    	$random = '';
    	$count = 6;
		
    	/*
    	if (($fh = @fopen('/dev/urandom', 'rb')))
    	{
    		$random = fread($fh, $count);
    		fclose($fh);
    	}
    	*/
    	
    	// Hack for SYNK so it won't need the UNIX based code from above
    	for($i = 0; $i < $count; $i++) $random .= chr(mt_rand(0, 255));
    	

    	if (strlen($random) < $count)
    	{
    		$random = '';

    		for ($i = 0; $i < $count; $i += 16)
    		{
    			$random_state = md5($this->unique_id() . $random_state);
    			$random .= pack('H*', md5($random_state));
    		}
    		$random = substr($random, 0, $count);
    	}

    	$hash = $this->_hash_crypt_private($password, $this->_hash_gensalt_private($random, $itoa64), $itoa64);

    	if (strlen($hash) == 34)
    	{
    		return $hash;
    	}

    	return md5($password);
    }
    
    /**
     * Return unique id
     * @param string $extra additional entropy
     */
    function unique_id($extra = 'c')
    {
    	// Function Replacement for SYNK
    	return substr(md5(mt_rand().microtime(true).$extra), 4, 16);
    	
    	/*
    	static $dss_seeded = false;
    	global $config;

    	$val = $config['rand_seed'] . microtime();
    	$val = md5($val);
    	$config['rand_seed'] = md5($config['rand_seed'] . $val . $extra);

    	if ($dss_seeded !== true && ($config['rand_seed_last_update'] < time() - rand(1,10)))
    	{
    		set_config('rand_seed', $config['rand_seed'], true);
    		set_config('rand_seed_last_update', time(), true);
    		$dss_seeded = true;
    	}

    	return substr($val, 4, 16);
    	*/
    }
    
    /**
     * The crypt function/replacement
     */
    function _hash_crypt_private($password, $setting, &$itoa64)
    {
    	$output = '*';

    	// Check for correct hash
    	if (substr($setting, 0, 3) != '$H$')
    	{
    		return $output;
    	}

    	$count_log2 = strpos($itoa64, $setting[3]);

    	if ($count_log2 < 7 || $count_log2 > 30)
    	{
    		return $output;
    	}

    	$count = 1 << $count_log2;
    	$salt = substr($setting, 4, 8);

    	if (strlen($salt) != 8)
    	{
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
    	if (PHP_VERSION >= 5)
    	{
    		$hash = md5($salt . $password, true);
    		do
    		{
    			$hash = md5($hash . $password, true);
    		}
    		while (--$count);
    	}
    	else
    	{
    		$hash = pack('H*', md5($salt . $password));
    		do
    		{
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
    function _hash_encode64($input, $count, &$itoa64)
    {
    	$output = '';
    	$i = 0;

    	do
    	{
    		$value = ord($input[$i++]);
    		$output .= $itoa64[$value & 0x3f];

    		if ($i < $count)
    		{
    			$value |= ord($input[$i]) << 8;
    		}

    		$output .= $itoa64[($value >> 6) & 0x3f];

    		if ($i++ >= $count)
    		{
    			break;
    		}

    		if ($i < $count)
    		{
    			$value |= ord($input[$i]) << 16;
    		}

    		$output .= $itoa64[($value >> 12) & 0x3f];

    		if ($i++ >= $count)
    		{
    			break;
    		}

    		$output .= $itoa64[($value >> 18) & 0x3f];
    	}
    	while ($i < $count);

    	return $output;
    }
    
    /**
     * Generate salt for hash generation
     */
    function _hash_gensalt_private($input, &$itoa64, $iteration_count_log2 = 6)
    {
    	if ($iteration_count_log2 < 4 || $iteration_count_log2 > 31)
    	{
    		$iteration_count_log2 = 8;
    	}

    	$output = '$H$';
    	$output .= $itoa64[min($iteration_count_log2 + ((PHP_VERSION >= 5) ? 5 : 3), 30)];
    	$output .= $this->_hash_encode64($input, 6, $itoa64);

    	return $output;
    }
    
    
	/**
	* Set users default group
	*
	* @access private
	*/
	function group_set_user_default(&$msg, $group_id, $user_id_ary, $group_attributes = false, $update_listing = false)
	{
		$synkdb =& $this->synkdb;
		$config =& $this->phpBB3_config;
		
		if (empty($user_id_ary))
		{
			return true;
		}
	
		$attribute_ary = array(
			'group_colour'			=> 'string',
			'group_rank'			=> 'int',
			'group_avatar'			=> 'string',
			'group_avatar_type'		=> 'int',
			'group_avatar_width'	=> 'int',
			'group_avatar_height'	=> 'int',
		);
	
		$sql_ary = array(
			'group_id'		=> $group_id
		);
	
		// Were group attributes passed to the function? If not we need to obtain them
		if ($group_attributes === false)
		{
			$sql = 'SELECT ' . implode(', ', array_keys($attribute_ary)) . '
				FROM ' . GROUPS_TABLE . "
				WHERE group_id = $group_id";
			$synkdb->setQuery($sql);
			$group_attributes = $synkdb->loadAssoc();
			
			if($synkdb->getErrorNum()){
				$msg->message .= ' - '.JText::_('Error in query').": $sql :".$synkdb->getErrorMsg();
				return false;
			}
		}
	
		foreach ($attribute_ary as $attribute => $type)
		{
			if (isset($group_attributes[$attribute]))
			{
				// If we are about to set an avatar or rank, we will not overwrite with empty, unless we are not actually changing the default group
				if ((strpos($attribute, 'group_avatar') === 0 || strpos($attribute, 'group_rank') === 0) && !$group_attributes[$attribute])
				{
					continue;
				}
	
				settype($group_attributes[$attribute], $type);
				$sql_ary[str_replace('group_', 'user_', $attribute)] = $group_attributes[$attribute];
			}
		}
	
		// Before we update the user attributes, we will make a list of those having now the group avatar assigned 
		if (isset($sql_ary['user_avatar']))
		{
			// Ok, get the original avatar data from users having an uploaded one (we need to remove these from the filesystem)
			$sql = 'SELECT user_id, group_id, user_avatar
				FROM ' . USERS_TABLE . '
				WHERE `user_id` IN (' .implode(',', $user_id_ary). ') 
					AND user_avatar_type = ' . AVATAR_UPLOAD;
			$synkdb->setQuery($sql);
			$rows = $synkdb->loadAssocList();
			
			if($synkdb->getErrorNum()){
				$msg->message .= ' - '.JText::_('Error in query').": $sql :".$synkdb->getErrorMsg();
				return false;
			}
	
			foreach($rows as $row)
			{
				if(!avatar_delete('user', $row)){
					return false;
				}
			}
		}
		else
		{
			unset($sql_ary['user_avatar_type']);
			unset($sql_ary['user_avatar_height']);
			unset($sql_ary['user_avatar_width']);
		}
	
		$sql = 'UPDATE ' . USERS_TABLE . ' SET ';
		foreach($sql_ary as $key => $val){
			$sql .= "`$key`='".$synkdb->getEscaped($val)."',";
		}
		$sql = substr($sql, 0, -1);
		
		$sql .= " WHERE `user_id` IN (".implode(',', $user_id_ary).")";
		
		$synkdb->setQuery($sql);
		
		if(!($synkdb->query())){
			$msg->message .= ' - '.JText::_('Error in query').": $sql :".$synkdb->getErrorMsg();
			return false;
		}
	
		if (isset($sql_ary['user_colour']))
		{
			// Update any cached colour information for these users
			$sql = 'UPDATE ' . FORUMS_TABLE . " SET forum_last_poster_colour = '" . $synkdb->getEscaped($sql_ary['user_colour']) . "'
				WHERE `forum_last_poster_id` IN (" .implode(',', $user_id_ary). ")";
			$synkdb->setQuery($sql);
			if(!($synkdb->query())){
				$msg->message .= ' - '.JText::_('Error in query').": $sql :".$synkdb->getErrorMsg();
				return false;
			}
	
			$sql = 'UPDATE ' . TOPICS_TABLE . " SET topic_first_poster_colour = '" . $synkdb->getEscaped($sql_ary['user_colour']) . "'
				WHERE `topic_poster` IN (" .implode(',', $user_id_ary). ")";
			$synkdb->setQuery($sql);
			if(!($synkdb->query())){
				$msg->message .= ' - '.JText::_('Error in query').": $sql :".$synkdb->getErrorMsg();
				return false;
			}
	
			$sql = 'UPDATE ' . TOPICS_TABLE . " SET topic_last_poster_colour = '" . $synkdb->getEscaped($sql_ary['user_colour']) . "'
				WHERE `topic_last_poster_id` IN (" .implode(',', $user_id_ary). ")";
			$synkdb->setQuery($sql);
			if(!($synkdb->query())){
				$msg->message .= ' - '.JText::_('Error in query').": $sql :".$synkdb->getErrorMsg();
				return $success;
			}
	
			if (in_array($config['newest_user_id'], $user_id_ary))
			{
				if(!$this->set_config('newest_user_colour', $sql_ary['user_colour'], true)){
					return false;
				}
			}
		}
		
		/* TODO currently $update_listing is always false, so no need to implement it for SYNK.
		if ($update_listing)
		{
			group_update_listings($group_id);
		}
		*/
		
		return true;
	}
	
	
	/**
	 * Set config value. Creates missing config entry.
	 */
	function set_config($config_name, $config_value, $is_dynamic = false)
	{
		$synkdb =& $this->synkdb;
		$config =& $this->phpBB3_config;
		
		$sql = "UPDATE `".CONFIG_TABLE."` SET ".
			"`config_value`='" . $synkdb->getEscaped($config_value) . "'".
			" WHERE `config_name`='" . $synkdb->getEscaped($config_name) . "'";
		$synkdb->setQuery($sql);
		
		if(!($synkdb->query())){
			return false;
		}

		if (!$synkdb->getAffectedRows() && !isset($config[$config_name]))
		{
			$sql = "INSERT INTO `".CONFIG_TABLE."` SET ".
				"`config_name`='".$synkdb->getEscaped($config_name)."', ".
				"`config_value`='".$synkdb->getEscaped($config_value)."', ".
				"`is_dynamic`=".($is_dynamic ? '1':'0');
			$synkdb->setQuery($sql);
			
			if(!($synkdb->query())){
				return false;
			}
		}

		$config[$config_name] = $config_value;
		
		/* TODO cache destroy would require filesystem access to be implemented
		if (!$is_dynamic)
		{
			$cache->destroy('config');
		}
		*/
		
		return true;
	}
	
	
	/**
	 * Set dynamic config value with arithmetic operation.
	 */
	function set_config_count($config_name, $increment, $is_dynamic = false)
	{
		$synkdb =& $this->synkdb;
		
		/* TODO Find out the target DB type. For now we assume the default type is used
		 * 
		switch ($db->sql_layer)
		{
			case 'firebird':
				$sql_update = 'CAST(CAST(config_value as integer) + ' . (int) $increment . ' as CHAR)';
				break;

			case 'postgres':
				$sql_update = 'int4(config_value) + ' . (int) $increment;
				break;

				// MySQL, SQlite, mssql, mssql_odbc, oracle
			default:
		*/
				$sql_update = '`config_value` + ' . (int) $increment;
		/*		break;
		}
		*/
		
		$sql = "UPDATE `".CONFIG_TABLE."` SET ".
				"`config_value`=". $sql_update .
				" WHERE `config_name` = '" . $synkdb->getEscaped($config_name) . "'";
		$synkdb->setQuery($sql);
		
		if(!($synkdb->query())){
			return false;
		}
		
		
		/* TODO cache destroy implementation would need filesystem access
		if (!$is_dynamic)
		{
			$cache->destroy('config');
		}
		*/
		
		return true;
	}
	
	
	/**
	 * Get latest registered username and update database to reflect it
	 */
	function update_last_username()
	{
		$synkdb =& $this->synkdb;
		
		// Get latest username
		$sql = 'SELECT user_id, username, user_colour
		FROM ' . USERS_TABLE . '
		WHERE user_type IN (' . USER_NORMAL . ', ' . USER_FOUNDER . ')
		ORDER BY user_id DESC';
		
		$synkdb->setQuery($query);
		$row = $synkdb->loadAssoc();
		
		if(!($synkdb->query())){
			return false;
		}
		
		if ($row)
		{
			if(!$this->set_config('newest_user_id', $row['user_id'], true) ||
				!$this->set_config('newest_username', $row['username'], true) ||
				!$this->set_config('newest_user_colour', $row['user_colour'], true)){
				
				return false;
			}
		}
	}
	
	
	/**
	 * Remove avatar
	 */
	// TODO cannot implement avatar deletion from filesystem in SYNK, only from DB
	function avatar_delete($mode, $row, $clean_db = false)
	{
		// Check if the users avatar is actually *not* a group avatar
		if ($mode == 'user')
		{
			if (strpos($row['user_avatar'], 'g') === 0 || (((int)$row['user_avatar'] !== 0) && ((int)$row['user_avatar'] !== (int)$row['user_id'])))
			{
				return false;
			}
		}

		if ($clean_db)
		{
			if(!$this->avatar_remove_db($row[$mode . '_avatar'])){
				return false;
			}
		}
		
		/*
		$filename = get_avatar_filename($row[$mode . '_avatar']);
		if (file_exists($phpbb_root_path . $config['avatar_path'] . '/' . $filename))
		{
			@unlink($phpbb_root_path . $config['avatar_path'] . '/' . $filename);
			return true;
		}

		return false;
		*/
		
		return true;
	}
	
	
	/**
	 * Remove avatar also for users not having the group as default
	 */
	function avatar_remove_db($avatar_name)
	{
		$synkdb =& $this->synkdb;
		
		$sql = 'UPDATE ' . USERS_TABLE . "
		SET user_avatar = '',
		user_avatar_type = 0
		WHERE user_avatar = '" . $synkdb->getEscaped($avatar_name) . '\'';
		$synkdb->setQuery($sql);
		
		if(!($synkdb->query())){
			return false;
		}
		
		return true;
	}
}
