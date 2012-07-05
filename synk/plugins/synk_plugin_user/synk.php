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

class plgUserSynk extends JPlugin 
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
	function plgUserSynk(& $subject, $config) {
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
	function checkParameters( $item )
	{
		$success = true;
		$exceptions_list = @preg_replace( '/\s/', '', plgUserSynk::_getParameter( 'exceptions_list', '' ) );
		$exceptions_array = explode( ',', $exceptions_list );
		
		// check exceptions
		if (in_array($item['id'], $exceptions_array)) { $success = false; }
				
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
	 *
	 * Method is called after user data is stored in the database
	 * The user array contains the new data.
	 *
	 * @param 	array		holds the new user data
	 * @param 	boolean		true if a new user is stored
	 * @param	boolean		true if user was succesfully stored in the database
	 * @param	string		message
	 */
	function onAfterStoreUser($user, $isnew, $store_success, $store_message)
	{
		$success = false;
		
	    if (!$this->_isInstalled()) {
            return $success;
        }

		// Initialize variables
		
		$msg = new stdClass();
		$msg->error = '';
		$msg->message = '';
				
		// prep parameters for passing via single array
		$options = array();
		$options['user']	= $user;
		$options['isnew']	= $isnew;
		$options['success']	=& $success;
		$options['msg']		=& $msg;
		$options['synktype']= "user";
		$options['event']	= "onAfterStoreUser";
		
		// grab all relevant synks
		$synchronizations = &SynkHelperSynchronizations::getSynchronizations( $options['event'] );

		// if not to be synchronized, continue
		if (!$this->checkParameters( $user )) {
			$success = true;
			return $success;
		}
		
		// Loop through synks
		for ($i=0; $i<count($synchronizations); $i++) {
			$synk = $synchronizations[$i];
						
			/**
			*
			* RUN THE SYNK
			*
			*/
			
			$this->runSynchronization( $synk, $options );
		}
	}

	/**
	 * Example store user method
	 *
	 * Method is called before user data is deleted from the database
	 *
	 * @param 	array		holds the user data
	 */
	function onBeforeDeleteUser($user)
	{
		$success = false;
		
	    if (!$this->_isInstalled()) {
            return $success;
        }

		// Initialize variables
		$msg = new stdClass();
		$msg->error = '';
		$msg->message = '';
				
		// prep parameters for passing via single array
		$options = array();
		$options['user']	= $user;
		$options['isnew']	= false;
		$options['success']	=& $success;
		$options['msg']		=& $msg;
		$options['synktype']= "user";
		$options['event']	= "onBeforeDeleteUser";
		
		// grab all relevant synks
		$synchronizations = &SynkHelperSynchronizations::getSynchronizations( $options['event'] );

		// if not to be synchronized, continue
		if (!$this->checkParameters( $user )) {
			$return = true;
			return $return;
		}
		
		// loop thru synks
		for ($i=0; $i<count($synchronizations); $i++) {
			$synk = $synchronizations[$i];
						
			/**
			*
			* RUN THE SYNK
			*
			*/
			$runSynk = &$this->runSynchronization( $synk, $options );
	
		}
	}

	/**
	 * Example store user method
	 *
	 * Method is called after user data is deleted from the database
	 *
	 * @param 	array		holds the user data
	 * @param	boolean		true if user was succesfully stored in the database
	 * @param	string		message
	 */
	function onAfterDeleteUser($user, $delete_success, $delete_message)
	{
		$success = false;
		
	    if (!$this->_isInstalled()) {
            return $success;
        }
		
		// Initialize variables
		$msg = new stdClass();
		$msg->error = '';
		$msg->message = '';
		
		// prep parameters for passing via single array
		$options = array();
		$options['user']	= $user;
		$options['isnew']	= false;
		$options['success']	=& $success;
		$options['msg']		=& $msg;
		$options['synktype']= "user";
		$options['event']	= "onAfterDeleteUser";
		
		// grab all relevant synks
		$synchronizations = &SynkHelperSynchronizations::getSynchronizations( $options['event'] );

		// if not to be synchronized, continue
		if (!$this->checkParameters( $user )) {
			$return = true;
			return $return;
		}
		
		// loop thru synks
		for ($i=0; $i<count($synchronizations); $i++) {
			$synk = $synchronizations[$i];
						
			/**
			*
			* RUN THE SYNK
			*
			*/
			$runSynk = &$this->_executeSynchronizationDelete( $synk, $options );
			
			/**
			 *
			 * LOG THE SYNK
			 *
			 */
			$logSynk = $this->logSynchronization( $synk, $options, $runSynk );
		}
		
		$return = true;
		return $return;
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
	function onLoginUser($user, $options)
	{
		$success = false;
		
	    if (!$this->_isInstalled()) {
            return $success;
        }
		
		// assign the userid to user['id'] (onLogin doesn't populate this field in the array) 
		$user['id'] = intval(JUserHelper::getUserId($user['username']));

		// Initialize variables
		$msg = new stdClass();
		$msg->error = '';
		$msg->message = '';

		// prep parameters for passing via single array
		$options = array();
		$options['user']	= $user;
		$options['isnew']	= false;
		$options['success']	=& $success;
		$options['msg']		=& $msg;
		$options['synktype']= "user";
		$options['event']	= "onLoginUser";
		
		// grab all relevant synks
		$synchronizations = &SynkHelperSynchronizations::getSynchronizations( $options['event'] );

		// if not to be synchronized, continue
		if (!$this->checkParameters( $user )) {
			$return = true;
			return $return;
		}
		
		// loop thru synks
		for ($i=0; $i<count($synchronizations); $i++) {
			$synk = $synchronizations[$i];
						
			/**
			*
			* RUN THE SYNK
			*
			*/
			$runSynk = &$this->runSynchronization( $synk, $options );
	
		}
		
		$success = true;
		return $success;
	}

	/**
	 * This method should handle any logout logic and report back to the subject
	 *
	 * @access public
	 * @param array holds the user data
	 * @return boolean True on success
	 * @since 1.5
	 */
	function onLogoutUser($user)
	{
		$success = false;
		
	    if (!$this->_isInstalled()) {
            return $success;
        }

		// Initialize variables
		$msg = new stdClass();
		$msg->error = '';
		$msg->message = '';
		
		// prep parameters for passing via single array
		$options = array();
		$options['user']	= $user;
		$options['isnew']	= false;
		$options['success']	=& $success;
		$options['msg']		=& $msg;
		$options['synktype']= "user";
		$options['event']	= "onLogoutUser";
		
		// grab all relevant synks
		$synchronizations = &SynkHelperSynchronizations::getSynchronizations( $options['event'] );

		// if not to be synchronized, continue
		if (!$this->checkParameters( $user )) {
			$return = true;
			return $return;
		}
		
		// loop thru synks
		for ($i=0; $i<count($synchronizations); $i++) {
			$synk = $synchronizations[$i];
						
			/**
			*
			* RUN THE SYNK
			*
			*/
			$runSynk = &$this->runSynchronization( $synk, $options );
	
		}
		
		$success = true;
		return $success;
	}
	
	
	/*
	 * Synk the #__core_acl_aro_groups table for the given ID,
	 * creating all the missing entries in the tree if needed
	 * 
	 * @access public
	 * @param object Target DB Object
	 * @param integer User's Group ID
	 * @param object Return Message used in SYNK logging
	 * @return boolean True or false on error
	 */
	function synk_aro_groups($synkdb, $gid, &$options)
	{
		$success = &$options['success'];
		$msg = &$options['msg'];
		$localdb =& JFactory::getDBO();
		
		$success = false;
		
		// Table #__core_acl_aro_groups
		$table_aro_groups =& JTable::getInstance('arogroup');
		$table_aro_groups->_tbl_key = 'id'; // Bug in Joomla 1.5.14, setting as primary key the non-existing group_id
		
		$table_aro_groups->setDBO( $synkdb );
		if(!$table_aro_groups->load( $gid )){
			$table_aro_groups->setDBO( $localdb );
			if(!($table_aro_groups->load( $gid ))){
				$msg->message .= ' - '.JText::_('Failed to load ARO Group').$table_aro_groups->getError();
				return $success;
			}
			
			$parent_gid = $table_aro_groups->parent_id;
			
			$table_aro_groups->setDBO( $synkdb );
			if (!$table_aro_groups->store()){
				$table_aro_groups->setDBO( $localbd );
				$msg->message .= ' - '.JText::_( 'ARO Group Store Failed' ).': '.$table_aro_groups->getError();
				return $success;
			}
			
			// Create parent group entries if don't exist
			// TODO Creating Parent Groups by name too instead of by Group ID only?  
			
			return $this->synk_aro_groups($synkdb, $parent_gid, $options);
			
		}
		$table_aro_groups->setDBO( $localbd );
		
		$success = true;
		return $success;
	} 
	

	/**
	 * Execute a Synk
	 * @param object A valid synchronization object
	 * @param array See plugin for parameters
	 * @return boolean
	 */
	function &_executeSynchronizationDelete( $synk, &$options )
	{
		// init variables
		$user 		= $options['user'];
		$isnew 		= $options['isnew'];
		$success 	= &$options['success'];
		$msg 		= &$options['msg'];
		$event 		= $options['event'];
		
		$success = false;
				
		// if no user['id']
		if ( !isset($user['id']) )	return $success;
		
		// get synk database
		if(!($synkdb = &$this->getDatabase( $synk->databaseid ))){
			$msg->message .= JText::_('Failed to get target Database Object');
			return $success;	
		}
		
		if ( $synkdb->getErrorNum() ) {
			$msg->message .= $synkdb->getErrorMsg();
			return $success;
		}
		
		// Execute published plugins
		// Before this the coder should make sure global instances of default Joomla Tables,
		// have not been left by setDBO() pointing to the target DB.
		$args = array();
		$args = $options;
		$args['synk'] = $synk;
		$dispatcher	   =& JDispatcher::getInstance();
		$dispatcher->trigger('onBeforeRunSynchronizationSynk', array( $options, $args ) );
		
		$synk_mode = $this->_getParameter('synk_mode');
		
		if($synk_mode == '0' || $synk_mode == '1'){
			// Try to locate first the same username in the target DB
			$query = "SELECT * FROM `#__users` WHERE `username`='{$user['username']}'";
			$synkdb->setQuery($query);
			$target = $synkdb->loadObject();
			
			if($target == null && $synk_mode == '1'){
				// Username was not found. Try to locate the same User ID in the target DB
				$query = "SELECT * FROM `#__users` WHERE `id`={$user['id']}";
				$synkdb->setQuery($query);
				$target = $synkdb->loadObject();
			}
			
		} else if($synk_mode == '2' || $synk_mode == '3'){
			// Try to locate first the same User ID in the target DB
			$query = "SELECT * FROM `#__users` WHERE `id`={$user['id']}";
			$synkdb->setQuery($query);
			$target = $synkdb->loadObject();
			
			if($target == null && $synk_mode == '3'){
				// User ID was not found. Try to locate the same Username in the target DB
				$query = "SELECT * FROM `#__users` WHERE `username`='{$user['username']}'";
				$synkdb->setQuery($query);
				$target = $synkdb->loadObject();
			}		
		
		} else {
			$msg->message .= ' - '.JText::_( 'Internal error: synk_mode parameter was not found/recognized.' );
			return $success;
		}
		
		if(!isset($target->id)) {
			$msg->message .= ' - '.JText::_('The user deleted was not found in the target DB');
			return $success;
		}
		
		// Delete user from target's DB #__users table
		$synkdb->setQuery("DELETE FROM `#__users` WHERE `id`=".$target->id);
		if($synkdb->query() === false){
			$msg->message .= ' - '.JText::_( 'Deletion from "users" table failed' ).': '.$synkdb->getErrorMsg();
			return $success;
		}
		
		$synkdb->setQuery("SELECT `id` FROM `#__core_acl_aro` WHERE `section_value`='users' && `value`={$target->id}");
		if(($aro_row = $synkdb->loadObject()) == null){
			$msg->message .= ' - '.JText::_( 'User ID not found in "core_acl_aro" table' ).': '.$synkdb->getErrorMsg();
			return $success;
		}
		
		// Delete user entry from target's DB #__core_acl_aro table
		$synkdb->setQuery("DELETE FROM `#__core_acl_aro` WHERE `id`={$aro_row->id}");
		if($synkdb->query() === false){
			$msg->message .= ' - '.JText::_( 'Deletion from "core_acl_aro" failed' ).': '.$synkdb->getErrorMsg();
			return $success;
		}
		
		// Delete user entry from target's DB #__core_acl_groups_aro_map table
		$synkdb->setQuery("DELETE FROM `#__core_acl_groups_aro_map` WHERE `aro_id`={$aro_row->id}");
		if($synkdb->query() === false){
			$msg->message .= ' - '.JText::_( 'Deletion from "core_acl_groups_aro_map" failed' ).': '.$synkdb->getErrorMsg();
			return $success;
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
	 * Runs a Custom User Synk
	 * @param object A valid synchronization object
	 * @param array See plugin for parameters
	 * @return boolean
	 */
	function &runCustomUserSynchronization( $synk, &$options )
	{	
		// init variables
		$user 		= $options['user'];
		$isnew 		= $options['isnew'];
		$success 	= &$options['success'];
		$msg 		= &$options['msg'];
		$event 		= $options['event'];
		
		$success = false;

		// get synk database
		if(!($synkdb = &$this->getDatabase( $synk->databaseid ))){
			$msg->message .= JText::_('Failed to get target Database Object');
			return $success;	
		}
		
		if ( $synkdb->getErrorNum() ) {
			$msg->message .= $synkdb->getErrorMsg();
			return $success;
		}
		
		// format custom query
		// replace any instances of {userid} with user['id']
		$query = stripslashes( $synk->custom_query );
		$query = preg_replace( '({userid})', $user['id'], $query );
			
		// set the query
		$synkdb->setQuery( $query );
		
		// execute published plugins
		$args = $options;
		$args['synk'] = $synk;
		$dispatcher	   =& JDispatcher::getInstance();
		$dispatcher->trigger('onBeforeRunSynchronizationSynk', array( $args ) );		
		
		// run the query
		if (!$synkdb->query()) {
			$msg->message .= ' - '.JText::_( 'Synk Failed' ).': '.$synkdb->getErrorMsg();	
		} else {
			$success = true;
		}

		// execute published plugins
		$args['success'] = $success;
		$dispatcher	   =& JDispatcher::getInstance();
		$dispatcher->trigger('onAfterRunSynchronizationSynk', array( $args ) );	
		
		return $success;
	}

	/**
	 * Runs a User Synk
	 * @param object A valid synchronization object
	 * @param array See plugin for parameters
	 * @return boolean
	 */
	function &runUserSynchronization( $synk, &$options )
	{
		// init variables
		$user 		= $options['user'];
		$isnew 		= $options['isnew'];
		$success 	= &$options['success'];
		$msg 		= &$options['msg'];
		$event 		= $options['event'];
		$localdb =& JFactory::getDBO();
		
		$success = false;

		// get synk database
		if(!($synkdb = &$this->getDatabase( $synk->databaseid ))){
			$msg->message .= JText::_('Failed to get target Database Object');
			return false;	
		}
		
		if ( $synkdb->getErrorNum() ) {
			$msg->message .= ' - '.JText::_('getDatabase() failed').$synkdb->getErrorMsg();
			return $success;
		}
	
		// load the table
		$table =& JTable::getInstance('user');
		if(!($table->load( $user['id'] ))){
			$msg->message .= ' - '.JText::_('Failed to load User from local "users" table').$table->getError();
			return $success;
		}
		
		// check if target has entry
		$synk_mode = $this->_getParameter('synk_mode');
		
		if($synk_mode == '0' || $synk_mode == '1'){
			// Try to locate first the same username in the target DB
			// The username may have changed so we use the old one to search target DB
			if(isset($this->old_user) && $this->old_user->username != ''){
				$query = "SELECT * FROM `#__users` WHERE `username`='{$this->old_user->username}'";
			} else {
				$query = "SELECT * FROM `#__users` WHERE `username`='{$user['username']}'";
			}
			
			$synkdb->setQuery($query);
			$target = $synkdb->loadObject();
			
			if($target == null && $synk_mode == '1'){
				// Username was not found. Try to locate the same User ID in the target DB
				$query = "SELECT * FROM `#__users` WHERE `id`={$user['id']}";
				$synkdb->setQuery($query);
				$target = $synkdb->loadObject();
			}
			
		} else if($synk_mode == '2' || $synk_mode == '3'){
			// Try to locate first the same User ID in the target DB
			$query = "SELECT * FROM `#__users` WHERE `id`={$user['id']}";
			$synkdb->setQuery($query);
			$target = $synkdb->loadObject();
			
			if($target == null && $synk_mode == '3'){
				// User ID was not found. Try to locate the same Username in the target DB
				if(isset($this->old_user) && $this->old_user->username != ''){
					$query = "SELECT * FROM `#__users` WHERE `username`='{$this->old_user->username}'";
				} else {
					$query = "SELECT * FROM `#__users` WHERE `username`='{$user['username']}'";
				}
				$synkdb->setQuery($query);
				$target = $synkdb->loadObject();
			}		
		
		} else {
			$msg->message .= ' - '.JText::_( 'Internal error: synk_mode parameter was not found/recognized.' );
			return $success;
		}
		
		// Execute published plugins
		// Before this the coder should make sure global instances of default Joomla Tables,
		// have not been left by setDBO() pointing to the target DB.
		$args = array();
		$args = $options;
		$args['synk'] = $synk;
		$dispatcher	   =& JDispatcher::getInstance();
		$dispatcher->trigger('onBeforeRunSynchronizationSynk', array($options, $args ) );
		
		if (!isset($target) || !isset($target->id)){
			// Create a new user entry.
			// The User ID and/or Username was not found in target DB.
			if($synk_mode == '0' || $synk_mode == '1'){
				// Create a user entry with the same Username
				$synkdb->setQuery( "INSERT INTO `#__users` SET `username`='{$user['username']}'" );
				if (!$synkdb->query()) {
					$msg->message .= ' - '.JText::_( 'Inserting New User By Username Failed' ).': '.$synkdb->getErrorMsg();
					return $success;
				}
				
			} else if($synk_mode == '2' || $synk_mode == '3'){
				// Create a user entry with the same User ID
				// In case of Synk Mode by User ID only, we need to check for
				// the same username existing in the target DB.
				if($synk_mode == '2'){
					$synkdb->setQuery("SELECT * FROM `#__users` WHERE `username`='{$user['username']}'");
					$synkdb->query();
					if($synkdb->loadObject() != null){
						$msg->message .= ' - '.JText::_( 'Username exists in target DB. Try Synk mode "By User ID, then by Username" instead' ).': '.$synkdb->getErrorMsg();
						return $success;
					}
				}
				 
				$synkdb->setQuery( "INSERT INTO `#__users` SET `id`={$user['id']}" );
				if (!$synkdb->query()) {
					$msg->message .= ' - '.JText::_( 'Inserting New User By User ID Failed' ).': '.$synkdb->getErrorMsg();
					return $success;
				}
			}
			
			// The User ID in the target DB
			if(!isset($target)) $target = new stdClass();
			$target->id = $synkdb->insertid();
			
			// do acl & aro inserts
			// Table #__core_acl_aro
			$query = "SELECT * FROM `#__core_acl_aro` WHERE `section_value`='users' && `value`={$target->id}";
			$synkdb->setQuery($query);
			
			if(($obj = $synkdb->loadObject()) == null){
				// load the aro table
				$aroTable =& JTable::getInstance('aro');
					
				// setDBO to the db from the synk
				$aroTable->setDBO( $synkdb );
				$aroTable->_tbl_key = 'id'; // Bug in Joomla 1.5.14, setting as primary key the non-existing aro_id
				$aroTable->set( 'section_value', 'users' );
				$aroTable->set( 'value', $target->id );
				$aroTable->set( 'name', $user['username'] );
				if (!$aroTable->store()){
					$aroTable->setDBO( $localdb );
					$msg->message .= ' - '.JText::_( 'ARO Store Failed' ).': '.$aroTable->getError();
					return $success;
				}
				$aro_id = $synkdb->insertid();
				
				$aroTable->setDBO( $localdb );
			
			} else $aro_id = $obj->id;
			
			// Table #__core_acl_aro_groups
			if(!$this->synk_aro_groups($synkdb, $table->gid, $options)) return $success;
			
			// Reset success to false again
			$success = false;
			
			// Table #__core_acl_groups_aro_map
			$query = "INSERT IGNORE INTO `#__core_acl_groups_aro_map` SET ".
					"`group_id`={$table->gid}, `aro_id`=$aro_id";
			$synkdb->setQuery($query);
			$synkdb->query();
			
			// End of new user creation
		} else {
			// User already exists, Update the ARO entries in case they have changed
			
			// Search Local Table #__core_acl_aro
			$query = "SELECT * FROM `#__core_acl_aro` WHERE `section_value`='users' && `value`={$user['id']}";
			$localdb->setQuery($query);
			$local_aro = $localdb->loadObject();
			
			// Search Target Table #__core_acl_aro
			$query = "SELECT * FROM `#__core_acl_aro` WHERE `section_value`='users' && `value`={$target->id}";
			$synkdb->setQuery($query);
			if(!($target_aro = $synkdb->loadObject())){
				// This should never happen, but handles the case where the user exists in the #__users table but not in AROs
				$target_aro = $local_aro;
				$target_aro->id = 0;
				$target_aro->value = $target->id;
				
				if(!$synkdb->insertObject('#__core_acl_aro', $target_aro, 'id')){
					$msg->message .= ' - '.JText::_('Inserting ARO entries into target DB failed: ').$synkdb->getErrorMsg();
					return $success;
				}
				
				$target_aro->id = $synkdb->insertid();
			}
			
			// Synk Table #__core_acl_aro_groups
			if(!$this->synk_aro_groups($synkdb, $table->gid, $options)) return $success;
			
			// Search Local Table #__core_acl_groups_aro_map
			$query = "SELECT * FROM `#__core_acl_groups_aro_map` WHERE `aro_id`={$local_aro->id}";
			$localdb->setQuery($query);
			$local_aro_map = $localdb->loadObject();
			
			// Update Target Table #__core_acl_groups_aro_map
			$query = "UPDATE `#__core_acl_groups_aro_map` SET ".
					"`group_id`={$table->gid}, ".
					"`section_value`='{$local_aro_map->section_value}'".
					" WHERE `aro_id`={$target_aro->id}";
			$synkdb->setQuery($query);
			$synkdb->query();
		}
		
		$table->id = $target->id;
		
		// store target user entry
		$table->setDBO( $synkdb );
		if ( !$table->store() ) {
			$table->setDBO( $localdb );
			$msg->message .= ' - '.JText::_( 'User Store Failed' ).': '.$table->getError();
			return $success;
		}
		$table->setDBO( $localdb );
		
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
	 * Wrapper to run a Synk
	 * @param object A valid synchronization object
	 * @param array See plugin for parameters
	 * @return boolean
	 */
	function &runSynchronization( $synk, &$options )
	{		
		$success =& $options['success'];
		$success = false; 
		
		if (!isset($options['synktype'])) {
			return $success;
		}		
		
		if ($synk->use_custom == 1) {
			$runSynk =& $this->runCustomUserSynchronization( $synk, $options );
		} else {
			$runSynk =& $this->runUserSynchronization( $synk, $options );
		}

		/**
		*
		* LOG THE SYNK
		*
		*/
		$logSynk = $this->logSynchronization( $synk, $options, $runSynk );
		
		return $runSynk;
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
	
}
