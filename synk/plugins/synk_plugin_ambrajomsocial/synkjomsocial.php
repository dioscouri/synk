<?php
/** ensure this file is being included by a parent file */
defined('_JEXEC') or die('Restricted access');

/** Import library dependencies */
jimport('joomla.plugin.plugin');

/**
 * @version	1.5
 * @author 	Dioscouri Design
 * @link 	http://www.dioscouri.com
 * @copyright Copyright (C) 2007 Dioscouri Design. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

class plgAmbraSynkJomsocial extends JPlugin 
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
	function plgAmbraSynkJomsocial(& $subject, $config) {
		parent::__construct($subject, $config);	
		$this->localdb =& JFactory::getDBO();	
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
	 * Check exception
	 * 
	 * @return 
	 * @param $article Object
	 */
	function checkParameters( $item )
	{		
		$success = true;
		
		$except = $this->_getParameter( 'exceptions_list', '' );
		
		if(!empty($except))
		{
			$exceptions_list = @preg_replace( '/\s/', '', $except );
			$exceptions_array = explode( ',', $exceptions_list );
			// check exceptions
			if (in_array($item['id'], $exceptions_array)) $success = false; 
		}
							
		return $success;
	}
	
	/**
	 * 
	 * Method to set the custom user param
	 * @param object $user
	 * @param mixed $value
	 * @param string $key
	 */
	function _setSynkUserParam($user, $value, $key='synk')
	{		
		$params =& $user->getParameters();			
		$user->setParam( $key, $value );
		$user->set( 'params', $params->toString());
		$user->save();	
	}
		
	/**
	 * 
	 * Method to get the user param synk
	 * @param $object $user
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	function _getSynkUserParam($user, $key='synk', $default='')
	{
		return $user->getParam($key, $default);
	}
	
	/**
     * Checking if synk is installed
     * 
     * @return boolean
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
    
    function onBeforeStorePointHistory($obj)
    {   	    	   	
		if(!empty($obj->pointhistory_id))
		{
			$this->localdb->setQuery( "SELECT `points` FROM `#__ambra_pointhistory` WHERE `pointhistory_id`={$obj->pointhistory_id}" );
			$this->oldpoints = $this->localdb->loadResult();
		}     	
	}
  
    /**
	 *
	 * Method is called after pointshistory is stored in the database
	 * The user array contains the new data.
	 *
	 * @param 	array		holds the new points
	 * @param 	boolean		true if a new user is stored
	 * @param	boolean		true if user was succesfully stored in the database
	 * @param	string		message
	 */
	function onAfterStorePointHistory($obj)
	{
		$success = false;
	    if (!$this->_isInstalled()) return $success;
	    $user = JFactory::getUSer($obj->user_id);
    
		if (is_object($user)) {
			jimport('joomla.utilities.arrayhelper');
			$synkUser = JArrayHelper::fromObject( $user );	
		} elseif (is_array($user)) {
			$synkUser = $user;	
		}
	
		if (!isset($synkUser) || !$synkUser['id']) {
			return null;
		}
	
        // Initialize variables		
		$msg = new stdClass();
		$msg->error = '';
		$msg->message = '';
				
		// prep parameters for passing via single array
		$options = array();
		$options['user']		= $synkUser;
		$options['userpoints']	= $obj;
		$options['synk']		= $this->_getSynkUserParam($user);
		$options['success']		=& $success;
		$options['msg']			=& $msg;
		$options['synktype']	= "ambra";
		$options['event']		= "onAfterStorePointHistory";		
	
		// grab all relevant synks
		$synchronizations = &SynkHelperSynchronizations::getSynchronizations( $options['event'] );

		// if not to be synchronized, continue
		if (!plgAmbraSynkJomsocial::checkParameters( $options['user'] )) {
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
	 * Runs a User Points Synk
	 * @param object A valid synchronization object
	 * @param array See plugin for parameters
	 * @return boolean
	 */
	function &runUserPointsSynchronization( $synk, &$options )
	{
		// init variables
		$user 			= $options['user'];
		$userpointsObj	= $options['userpoints'];		
		$success 		= &$options['success'];
		$msg 			= &$options['msg'];
		$event 			= $options['event'];
	
		$success = false;
							
		//need to know if its an edit of points history
		//need to get the difference
		if(isset($this->oldpoints) && ($this->oldpoints != $userpointsObj->points))
		{			
			$pointsToAdd = $userpointsObj->points - $this->oldpoints;
			
		}
		else 
		{
			$pointsToAdd = $userpointsObj->points;
		}
	
		// Get target database object
		if(!($synkdb = &$this->getDatabase( $synk->databaseid ))){
			$msg->message .= JText::_('Failed to get target Database Object');
			return $success;	
		}
		
		if ( $synkdb->getErrorNum() ) {
			$msg->message .= $synkdb->getErrorMsg();
			return $success;
		}		

		//check the target db if the user exist				
		$synkdb->setQuery( "SELECT * FROM `#__community_users` WHERE `userid`='{$user['id']}'" );
		$targetUser = $synkdb->loadObject();

		$localUser = JFactory::getUser($user['id']);
		
		if(empty($targetUser))
		{
			//the user doesnt exist in the #__community_users yet since the jomsocial user synchronization is not yet triggered
			$msg->message .= JText::_("Target user with userid {$user['id']} does not exist in table #__community_users.");
			
			//save the userpoints that is not added to JomSocial table
			$savePoints = (int) $this->_getSynkUserParam($localUser, 'ambrapoints');		
			$this->_setSynkUserParam($localUser, $savePoints+$pointsToAdd, 'ambrapoints');			
			return $success;
		}
		else 
		{			
			$pointsToAdd = $pointsToAdd + (int) $this->_getSynkUserParam($localUser, 'ambrapoints');
		}
		
		// Execute published plugins
		// Before this the coder should make sure global instances of default Joomla Tables,
		// have not been left by setDBO() pointing to the target DB.
		$args = array();
		$args = $options;
		$args['synk'] = $synk;
		$dispatcher	   =& JDispatcher::getInstance();
		$dispatcher->trigger('onBeforeRunSynchronizationSynk', array( $options, $args ) );				
		
		//we add if its the first time
		if(is_null($options['synk']) || empty($options['synk']))
		{
			//add the points from localdb ambra to the targedb
			$this->localdb->setQuery("SELECT `points_total` FROM `#__ambra_userdata` WHERE `user_id`='{$user['id']}'");
			$pointsToAdd = $this->localdb->loadResult();
			
			$query = "UPDATE `#__community_users` SET `points` = points +{$pointsToAdd} " ;
			
		}
		else 
		{
			$query = "UPDATE `#__community_users` SET `points` = points +{$pointsToAdd} " ;			
		}

		$query .= "WHERE `userid`='{$user['id']}'";		
		$synkdb->setQuery( $query );		
		$synkdb->query();
		if ( $synkdb->getErrorNum() ) {
			$msg->message .= $synkdb->getErrorMsg();
			return $success;
		}

		// if here, everything worked
		$success = true;
		
		//set the synk user para
		if(is_null($options['synk']) || empty($options['synk'])) 
		{
			$this->_setSynkUserParam($localUser, '1');
		}	
				
		// execute published plugins
		$args['success'] = $success;
		$dispatcher	   =& JDispatcher::getInstance();
		$dispatcher->trigger('onAfterRunSynchronizationSynk', array( $options, $args ) );		
		
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
		/**
		 * 
		 * RUN THE SYNK
		 * 
		 */

		$runSynk =& $this->runUserPointsSynchronization( $synk, $options );			

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
