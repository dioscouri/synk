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

if(!class_exists('plgCommunitySynkAmbra'))
{
	class plgCommunitySynkAmbra extends CApplications 
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
		function plgCommunitySynkAmbra(& $subject, $config) {
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
		 * Check exception
		 * 
		 * @return boolean 
		 */
		function checkParameters( $item )
		{		
			$success = true;
			
			$except = plgCommunitySynkAmbra::_getParameter( 'exceptions_list', '' );
			
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
		 * @param mixed $value
		 */
		function setSynkUserParam($user, $value)
		{	
			$params =& $user->getParameters();			
			$user->setParam( 'synk', $value );
			$user->set( 'params', $params->toString());
			$user->save();	
		}
		
		/**
		 * 
		 * Method to get the user param synk
		 * @param $object $user
		 * @return mixed
		 */
		function getSynkUserParam($user)
		{
			return $user->getParam('synk', null);
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
	    /**
	     * 
	     * Method called before controller created
	     * @param string $controller
	     */
	    
	    function onBeforeControllerCreate($controller)
	    {
	   	
	    	$success = false;
	
		    if (!$this->_isInstalled()) return $success;
		    
		    $user = JFactory::getUSer();		
    
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
			$options['synk']		= plgCommunitySynkAmbra::getSynkUserParam($user);
			$options['success']		=& $success;
			$options['msg']			=& $msg;
			$options['synktype']	= "community";
			$options['event']		= "onBeforeControllerCreate";
		
			// grab all relevant synks
			$synchronizations = &SynkHelperSynchronizations::getSynchronizations( $options['event'] );
	
			// if not to be synchronized, continue
			if (!$this->checkParameters( $options['user'] )) {
				$success = true;
				return true;
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
			$synkFirst		= $options['synk'];		
			$success 		= &$options['success'];
			$msg 			= &$options['msg'];
			$event 			= $options['event'];
						
			// Get target database object
			if(!($synkdb = &$this->getDatabase( $synk->databaseid ))){
				$msg->message .= JText::_('Failed to get target Database Object');
				return $success;	
			}
			
			if ( $synkdb->getErrorNum() ) {
				$msg->message .= $synkdb->getErrorMsg();
				return $success;
			}		

			
			$localdb = JFactory::getDBO();
			//get the local jomsocial user points
	    	$localdb->setQuery( "SELECT `points` FROM `#__community_users` WHERE `userid`={$user['id']}" );	    	
			$points = $localdb->loadResult();				

			//check the target db if the user exist				
			$synkdb->setQuery( "SELECT * FROM `#__ambra_userdata` WHERE `user_id`={$user['id']}" );
			$targetUser = $synkdb->loadObject();
		
			if(empty($targetUser))
			{
				$msg->message .= JText::_("Target user with userid {$user['id']} does not exist.");
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

			//we add if its the first time
			if(is_null($options['synk']) || empty($options['synk']))
			{
				$query = "UPDATE `#__ambra_userdata` SET `points_total` = points_total+{$points}, `points_current` = points_current+{$points} " ;
			}
			else 
			{
				$query = "UPDATE `#__ambra_userdata` SET `points_total` = {$points}, `points_current` = {$points} " ;	
			}
			
			$query .= "WHERE `user_id`='{$user['id']}'";
		
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
				$this->setSynkUserParam(JFactory::getUser(), '1');
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
}