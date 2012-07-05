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

/**
 * Synk CB
 * 
 * Supported custom Joomla Events
 * 
 * CBonAfterUserConfirm
 * CBonAfterUserRegistration
 * CBonAfterNewUser
 * CBonAfterUserUpdate
 * CBonAfterUserApproval
 * CBonUserActive
 * CBonAfterUserUpdate
 * CBonAfterLogin
 * CBonBeforeLogout <--- didn't work for me. onDoLogoutNow and onAfterLogout didn't work either! 
 * onLogoutUser (to replace the above non-working CB-event)
 * 
 * CBonAfterDeleteUser
 * 
 */
class plgUserSynkCB extends JPlugin {

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
	function plgUserSynkCB(& $subject = null, $config = null) {
		$this->__construct($subject, $config);
	}
	
	function __construct(&$subject = null, $config = null)
	{
		if(isset($subject) && isset($config)){
			// Called by Joomla
			
			$this->registerCBbridge();
			parent::__construct($subject, $config);
		
		} else {
			// Called by CB. Be quiet and pretend we are a CB plugin ;)
		}
	}
	
	// Dummy function to make this look like a CB plugin
	function _loadParams($pluginid, $extraParams) {}
	
	
	/******************************
	 * BRIDGE CB EVENTS TO JOOMLA *
	 ******************************/
	
	function registerCBbridge()
	{
		global $_PLUGINS;
		if(!empty($_PLUGINS)){
			// Registering the CB events
			$_PLUGINS->_loading = 1000;
			$_PLUGINS->_plugins[1000] = (object) array('published' => true, 'element' => '');
			
			$_PLUGINS->registerFunction('onAfterNewUser', 'CB_onAfterNewUser', 'plgUserSynkCB');
			$_PLUGINS->registerFunction('onAfterUserRegistration', 'CB_onAfterUserRegistration', 'plgUserSynkCB');
			$_PLUGINS->registerFunction('onAfterUserConfirm', 'CB_onAfterUserConfirm', 'plgUserSynkCB');
			$_PLUGINS->registerFunction('onAfterUserApproval', 'CB_onAfterUserApproval', 'plgUserSynkCB');
			$_PLUGINS->registerFunction('onUserActive', 'CB_onUserActive', 'plgUserSynkCB');
			
			// One is for frontend and the other for backend
			$_PLUGINS->registerFunction('onAfterDeleteUser', 'CB_onAfterDeleteUser', 'plgUserSynkCB');
			$_PLUGINS->registerFunction('onAfterUserDelete', 'CB_onAfterDeleteUser', 'plgUserSynkCB');
			
			// One is for frontend and the other for backend
			$_PLUGINS->registerFunction('onAfterUserUpdate', 'CB_onAfterUserUpdate', 'plgUserSynkCB');
			$_PLUGINS->registerFunction('onAfterUpdateUser', 'CB_onAfterUserUpdate', 'plgUserSynkCB');
			
			$_PLUGINS->registerFunction('onAfterLogin', 'CB_onAfterLogin', 'plgUserSynkCB');
			$_PLUGINS->registerFunction('onBeforeLogout', 'CB_onBeforeLogout', 'plgUserSynkCB');
		}
	}
	
	
	function CB_onAfterNewUser($row, $param2, $param3, $param4)
	{	
		// Trigger a normal Joomla Event
		$dispatcher =& JDispatcher::getInstance();
		$dispatcher->trigger('CB2Joomla', array('CBonAfterNewUser', $row->_cmsUser, '1'));
	}
	
	function CB_onAfterUserRegistration($row, $param2, $param3)
	{	
		// Trigger a normal Joomla Event
		$dispatcher =& JDispatcher::getInstance();
		$dispatcher->trigger('CB2Joomla', array('CBonAfterUserRegistration', $row->_cmsUser, '1'));
	}
	
	function CB_onAfterUserConfirm($row, $param2)
	{	
		// Trigger a normal Joomla Event
		$dispatcher =& JDispatcher::getInstance();
		$dispatcher->trigger('CB2Joomla', array('CBonAfterUserConfirm', $row->_cmsUser));
	}
	
	function CB_onAfterUserApproval($row, $param2, $param3)
	{	
		// Trigger a normal Joomla Event
		$dispatcher =& JDispatcher::getInstance();
		$dispatcher->trigger('CB2Joomla', array('CBonAfterUserApproval', $row->_cmsUser));
	}
	
	function CB_onUserActive($row, $param2)
	{	
		// Trigger a normal Joomla Event
		$dispatcher =& JDispatcher::getInstance();
		$dispatcher->trigger('CB2Joomla', array('CBonUserActive', $row->_cmsUser));
	}
	
	// To be used internally only. Keeps the user row for reference before changes occur
	function CB_onBeforeUserUpdate($row, $param2)
	{	
		if($row->_cmsUser['id']){
			// load the table
			$this->old_user =& JTable::getInstance('user');
			$this->old_user->load( $row->_cmsUser['id'] );
		}
	}
	
	function CB_onAfterUserUpdate($row, $param2, $param3)
	{	
		// Trigger a normal Joomla Event
		$dispatcher =& JDispatcher::getInstance();
		$dispatcher->trigger('CB2Joomla', array('CBonAfterUserUpdate', $row->_cmsUser));
	}
	
	function CB_onAfterDeleteUser($row, $param2)
	{
		// Trigger a normal Joomla Event
		$dispatcher =& JDispatcher::getInstance();
		
		// Here the "$row" has similar variables with JUser 
		$dispatcher->trigger('CB2Joomla', array('CBonAfterDeleteUser', $row));
	}
	
	function CB_onAfterLogin($row, $param2)
	{
		// Trigger a normal Joomla Event
		$dispatcher =& JDispatcher::getInstance();
		
		// Here the "$row" has similar variables with JUser 
		$dispatcher->trigger('CB2Joomla', array('CBonAfterLogin', $row));
	}
	
	function CB_onBeforeLogout($row)
	{
		// Trigger a normal Joomla Event
		$dispatcher =& JDispatcher::getInstance();
		
		// Here the "$row" has similar variables with JUser 
		$dispatcher->trigger('CB2Joomla', array('CBonBeforeLogout', $row));
	}
	
	
	/* Joomla Events
	 * 
	 */
	
	/**
	 * This method should handle any logout logic and report back to the subject
	 *
	 * @access public
	 * @param array holds the user data
	 * @return boolean True on success
	 * @since 1.5
	 */
	function onLogoutUser( $user ) 
	{
		if (!$this->_isInstalled()) {
			return null;
		}
		
		// trigger internal function
		$this->CB2Joomla( "onLogoutUser", $user );
			
		return null;
	}

	/**
	 * 
	 * @return unknown_type
	 */
	function _isInstalled()
	{
		$success = false;
		
		jimport('joomla.filesystem.file');
		if (JFile::exists(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_synk'.DS.'helpers'.DS.'synchronizations.php')) {
			require_once( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_synk'.DS.'helpers'.DS.'synchronizations.php' );
			$success = true;
		}
		
		return $success;
	}
	
	/**
	 * Gets a parameter value
	 *
	 * @access public
	 * @return mixed Parameter value
	 * @since 1.5
	 */
	function _getParameter( $name, $default='' )
	{
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
		$exceptions_list = preg_replace( '/\s/', '', $this->params->get( 'exceptions_list', '' ) );
		$exceptions_array = explode( ',', $exceptions_list );
		
		// check exceptions
		if (in_array($item['id'], $exceptions_array)) { $success = false; }
				
		return $success;
	}
	
	/**
	 * 
	 * @param $eventname
	 * @param $user
	 * @param $isNew
	 * @return unknown_type
	 */
	function CB2Joomla( $eventname, $user, $isNew='0', $type = 'user')
	{
	    if (!$this->_isInstalled()) {
            return null;
        }
        
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
		$options['isnew']	= $isNew;
		$options['success']	= false;
		$options['msg']		= $msg;
		$options['synktype']= $type;
		$options['event'] = $eventname;
		
		$success = &$options['success'];
		
		// grab all relevant synks
		$synchronizations = &SynkHelperSynchronizations::getSynchronizations( $options['event'] );
		
		// if not to be synchronized, continue
		if (!($doSynk = $this->_checkParameters( $options['user'] ))) {
			$success = true;
			return $success;
		}
		
		// loop thru synks
		for ($i=0; $i<count($synchronizations); $i++) {
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
				case "cbonafterdeleteuser":
					$runSynk = $this->_executeDeleteUser( $synk, $options );
				  break;
				
				case "cbonafternewuser":
				case "cbonafteruserregistration":
				case "cbonafteruserconfirm":
				case "cbonafteruserapproval":
				case "cbonuseractive":
				case "cbonafteruserupdate":
				case "cbonafterlogin":
				case "onlogoutuser":
					$runSynk = $this->_executeStoreUser( $synk, $options ); 
				  break;
				default:
					break;
			}
			
			/**
			 * 
			 * LOG The SYNK
			 * 
			 */
			if (isset($runSynk)) {
				$logSynk = $this->logSynchronization( $synk, $options, $runSynk );
				unset($runSynk);
			}
		}
	}

	/**
	 * 
	 * @return unknown_type
	 */
	function _checkFields($synk, &$options)
	{
		$msg = &$options['msg'];
		$success = &$options['success'];
		
		$success = false;
		
		// Initialize DB Objects
		$localdb =& JFactory::getDBO();
		if(!($synkdb = &$this->getDatabase( $synk->databaseid ))){
			$msg->message .= JText::_('Failed to get target Database Object');
			return false;	
		}
		if ( $synkdb->getErrorNum() ) {
			$options['msg']->message .= $synkdb->getErrorMsg();
			return $success;
		}
		
		// Get local #__comprofiler (Community Builder Users) Table structure
		$localdb->setQuery("SHOW FULL COLUMNS FROM `#__comprofiler`");
		if(!($local_profiler = $localdb->loadObjectList())){
			$msg->message .= ' - '.JText::_( 'Error while listing local table #__comprofiler' ).': '.$localdb->getErrorMsg();
			return $success;
		}
		
		// Get target #__comprofiler (Community Builder Users) Table structure
		$synkdb->setQuery("SHOW FULL COLUMNS FROM `#__comprofiler`");
		if(!($target_profiler = $synkdb->loadObjectList())){
			$msg->message .= ' - '.JText::_( 'Error while listing target table #__comprofiler' ).': '.$synkdb->getErrorMsg();
			return $success;
		}
		
		// Comparing local and remote tables' fields.
		foreach($local_profiler as $lc_prf){
			if(substr($lc_prf->Field, 0, 3) != 'cb_') continue;
			
			$found = false;
			foreach($target_profiler as $tg_prf){
				if(substr($tg_prf->Field, 0, 3) != 'cb_') continue;
				if($lc_prf->Field == $tg_prf->Field){
					$found = true;
					break;
				}
			}
			
			// If field was not found in the target DB's table, we'll create it.
			if($found === false){
				// Alter the target #__comprofiler table to add the missing column
				$query = "ALTER TABLE `#__comprofiler` ADD COLUMN `{$lc_prf->Field}` ".
						" {$lc_prf->Type} COLLATE {$lc_prf->Collation} ".
						($lc_prf->Null=="YES"?" NULL ":" NOT NULL ").
						($lc_prf->Default==NULL?" DEFAULT NULL ":" DEFAULT '{$lc_prf->Default}' ").
						" {$lc_prf->Extra} COMMENT '{$lc_prf->Comment}'";	
				
				$synkdb->setQuery($query);
				if($synkdb->query() === false){
					$msg->message .= ' - '.JText::_( 'New Field Creation Failed in target #__comprofiler' ).': '.$synkdb->getErrorMsg();
					return $success;
				}
				
				// Insert the new field's (if not exists) into in the #__comprofiler_fields table too.
				$localdb->setQuery("SELECT * FROM `#__comprofiler_fields` WHERE `name`='{$lc_prf->Field}'");
				$newfld = $localdb->loadAssoc();
				
				$query = "INSERT IGNORE INTO `#__comprofiler_fields` SET ";
				foreach($newfld as $col => $val){
					$query .= "`$col`='".$synkdb->getEscaped($val)."',";
				}
				$query = substr($query, 0, -1);
				
				$synkdb->setQuery($query);
				if($synkdb->query() === false){
					$msg->message .= ' - '.JText::_( 'Field Creation failed in target #__comprofiler_fields' ).': '.$synkdb->getErrorMsg();
					return $success;
				}
			}
		} // End of foreach()
		
		
		// Make sure the values for custom fields are up to date in #__comprofiler_field_values
		foreach($local_profiler as $lc_prf){
			if(substr($lc_prf->Field, 0, 3) != 'cb_') continue;
			
			$query = "SELECT * FROM `#__comprofiler_fields` WHERE `name`='$lc_prf->Field'";
			$localdb->setQuery($query);
			$field = $localdb->loadObject();
			
			$query = "SELECT * FROM `#__comprofiler_field_values` WHERE `fieldid`=$field->fieldid";
			$localdb->setQuery($query);
			$values = $localdb->loadObjectList();
			
			if(!empty($values)){
				$target_keys = array();
				
				foreach($values as $value){
					$query = "SELECT * FROM `#__comprofiler_field_values`".
							" WHERE `fieldid`=$value->fieldid && ".
							"`fieldtitle`='$value->fieldtitle'";
					$synkdb->setQuery($query);
					
					if($target_value = $synkdb->loadObject()){
						// Save keys
						$target_keys[] = $target_value->fieldvalueid;
						
						// Value exists. Update it if needed
						if($value->ordering != $target_value->ordering ||
							$value->sys != $target_value->sys){
							
							$query = "UPDATE `#__comprofiler_field_values` SET ".
									"`ordering`=$value->ordering, ".
									"`sys`=$value->sys".
									" WHERE `fieldvalueid`=$target_value->fieldvalueid";
							$synkdb->setQuery($query);
						}
					
					} else {
						// Value doesn't exist. Insert it.
						$value->fieldvalueid = 0; // A new key will be assigned to this 
						$synkdb->insertObject('#__comprofiler_field_values', $value);
						
						// Save keys
						$target_keys[] = $synkdb->insertid();
					}
				}
				
				if(!empty($target_keys)){
					// Remove values not found for this field
					$query = "DELETE FROM `#__comprofiler_field_values`".
							" WHERE `fieldid`=$field->fieldid && ". 
							"`fieldvalueid` NOT IN(".implode(',', $target_keys).")";
					
					$synkdb->setQuery($query);
					$synkdb->query();
				}
			}
		} // End of foreach()
		
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
	 * 
	 * @param $synk
	 * @param $options
	 * @return unknown_type
	 */
	function _executeStoreUser( $synk, &$options )
	{	
		// init variables
		$user 		= $options['user'];
		$isnew 		= $options['isnew'];
		$success 	= &$options['success'];
		$msg 		= &$options['msg'];
		$event 		= $options['event'];
		
		$success = false;
		
		// Get local DB Object
		$localdb =& JFactory::getDBO();
		
		// Create/Update user entry in target #__comprofiler table
		$query = "SELECT * FROM `#__comprofiler` WHERE `user_id`={$user['id']}";
		
		$localdb->setQuery($query);
		if(!($comprofiler_obj = $localdb->loadObject())){
			if($localdb->getErrorMsg() == ''){
				$msg->message .= ' - '.JText::_( '1st call for onAfterStoreUser is normal to fail. Report if this happens twice in the row for the same user. User ID ').$user['id'];
			} else {
				$msg->message .= ' - '.JText::_( 'Error while loading #__comprofiler row for User ID ').$user['id'].': '.$localdb->getErrorMsg();
			}
			return $success;
		}
		
		
		if (!$this->_checkFields($synk, $options))
		{
			return $success;
		}

		// Get target database object
		if(!($synkdb = &$this->getDatabase( $synk->databaseid ))){
			$msg->message .= JText::_('Failed to get target Database Object');
			return false;	
		}
		if ( $synkdb->getErrorNum() ) {
			$msg->message .= $synkdb->getErrorMsg();
			return $success;
		}
	
		// load the #__users table
		$table =& JTable::getInstance('user');
		$table->load( $user['id'] );
		
		// setDBO to the db from the synk
		$table->setDBO( $synkdb );
		
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
		$dispatcher->trigger('onBeforeRunSynchronizationSynk', array( $options, $args ) );
		
		// New user entry
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
				$aroTable = & JTable::getInstance('aro');
					
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
			}
			
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
		
		$comprofiler_obj->id = $target->id;
		unset($comprofiler_obj->avatar);
		
		/* We check if the entry exists
		 * Joomla API doc mentions insertObject will Update the entry instead of Inserting it,
		 * if the Table's Primary Key is given... which is not the case!
		 * http://api.joomla.org/Joomla-Framework/Database/JDatabase.html#insertObject
		 *
		 * So we do this manually here
		 */
		$query = "SELECT `id` FROM `#__comprofiler` WHERE `id`={$comprofiler_obj->id}";
		$synkdb->setQuery($query);
		
		if($synkdb->loadObject() == null){
			// Entry doesn't exist, we Insert it
			if(!$synkdb->insertObject('#__comprofiler', $comprofiler_obj, 'id')){
				$msg->message .= ' - '.JText::_( 'Error while storing #__comprofiler row in target DB for user ID ').$comprofiler_obj->id.': '.$synkdb->getErrorMsg();
				return $success;
			}
			
		} else {
			// Entry exists we Update it
			if(!$synkdb->updateObject('#__comprofiler', $comprofiler_obj, 'id')){
				$msg->message .= ' - '.JText::_( 'Error while updating #__comprofiler row in target DB for user ID ').$comprofiler_obj->id.': '.$synkdb->getErrorMsg();
				return $success;
			}
		}
		
		// if here, everything worked
		$success = true;
		
		// execute published plugins
		$args['success'] = $success;
		$dispatcher	   =& JDispatcher::getInstance();
		$dispatcher->trigger('onAfterRunSynchronizationSynk', array( $options, $args ) );
				
		return $success;
	}
	
	/**
	 * 
	 * @param $synk
	 * @param $options
	 * @return boolean
	 */
	function _executeDeleteUser( $synk, &$options ) 
	{
		// init variables
		$user 		= $options['user'];
		$isnew 		= $options['isnew'];
		$success 	= &$options['success'];
		$msg 		= &$options['msg'];
		$event 		= $options['event'];
		
		$success = false;
				
		// if no user['id']
		if ( !isset($user['id']) ) return $success;
		
		// get synk database
		if(!($synkdb = &$this->getDatabase( $synk->databaseid ))){
			$msg->message .= JText::_('Failed to get target Database Object');
			return false;	
		}
		if ( $synkdb->getErrorNum() ) {
			$msg->message .= $synkdb->getErrorMsg();
			return $success;
		}
		
		// check if target has entry
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
		}
		
		// Execute published plugins
		// Before this the coder should make sure global instances of default Joomla Tables,
		// have not been left by setDBO() pointing to the target DB.
		$args = array();
		$args = $options;
		$args['synk'] = $synk;
		$dispatcher	   =& JDispatcher::getInstance();
		$dispatcher->trigger('onBeforeRunSynchronizationSynk', array( $options, $args ) );
		
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
		
		// Delete user entry from the #__comprofiler (Community Builder) table
		$synkdb->setQuery("DELETE FROM `#__comprofiler` WHERE `user_id`=".$target->id);
		if($synkdb->query() === false){
			$msg->message .= ' - '.JText::_( 'CB User Delete Failed' ).': '.$synkdb->getErrorMsg();
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
