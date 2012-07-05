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
 * Synk Eventlist
 */
class plgSystemSynk_weblinks extends JPlugin {

	/**
	 * Constructor
	 *
	 * For php4 compatability we must not use the __constructor as a constructor for plugins
	 * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
	 * This causes problems with cross-referencing necessary for the observer design pattern.
	 *
	 * @param object $subject The object to observe
	 * @param object $params  The object that holds the plugin parameters
	 * @since 1.5
	 */
	function plgSystemSynk_weblinks( &$subject, $params )
	{
		parent::__construct( $subject, $params );
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
	 * 
	 * @return unknown_type
	 */
	function onAfterInitialise()
	{
		$success = false;
		$database = JFactory::getDBO();	
	
        if (!$this->_isInstalled()) {
			return $success;
		}
		
		$userObject = &JFactory::getUser();
		$user = array();
		$user = JArrayHelper::fromObject( $userObject );
		
		$msg = new stdClass();
		$msg->error = 0;
		$msg->message = '';
		
		$options = array();
		$options['user']	= $user;
		$options['isnew']	= false;
		$options['success']	= &$success;
		$options['msg']		= &$msg;
		$options['synktype']= "system";
		$options['event'] = "onSynkWeblinks";
		
		// grab all relevant synks
		$synchronizations 	= &SynkHelperSynchronizations::getSynchronizations( $options['event'] );
		
		// loop through synks
		for ($i=0; $i<count($synchronizations); $i++) {			
			$synk = $synchronizations[$i];
			
			if (!$this->canRunSynchronization( $synk )) continue;

		    // get all the synk's events WHERE
            // title != $event AND title IN (HOURLY, DAILY, WEEKLY, MONTHLY)
            // this prevents synchronization from happening with every page refresh
            $events = &$this->getSynkEvents( $synk, $options['event'] );

            // foreach event returned, set options['event'], and runSynchronization
            for ($e=0; $e<count($events); $e++) {
                $current = $events[$e];
                
                $synk->event_title = $current->title;
                $options['event'] = strtoupper($current->title);
                
                /**
                *
                * RUN THE SYNK
                *
                */
				$runSynk = $this->_executeSynchronization($synk, $options );
				
				/**
				 * LOG THE SYNK
				 */
				
                $this->logSynchronization( $synk, $options, $runSynk );
            }
		}
		
		return $success;
	}
	

    /**
     * Returns a list
     * @param mixed Boolean
     * @param mixed Boolean
     * @return array
     */
    function &getSynkEvents( $synk, $exceptions='' ) 
    {
        $database = &JFactory::getDBO();
        
        $exception_query = '';
        if (!empty($exceptions)) {
            if (!is_array($exceptions)) {
                $exceptions = array( $exceptions );
            }
            $string = "'".implode( "', '", $exceptions )."'"; 
            $exception_query = " AND e.title NOT IN (".$string.") ";
        }
  
        $synk_query = " AND s2e.synchronizationid = '".$synk->id."' "; 
    
        // all records      
        $query = "SELECT e.id, e.title, s2e.parameter FROM #__synk_events AS e "
        . " LEFT JOIN #__synk_s2e as s2e on s2e.eventid = e.id "
        . " WHERE 1 "
        . " AND LOWER(e.title) IN ('hourly', 'daily', 'weekly', 'monthly') "
        . $exception_query
        . $synk_query
        ;
        
        $database->setQuery( $query );
        $data = $database->loadObjectList();

        return $data;
    }
    
    
	/**
	 * Execute a Synk
	 * @param object A valid synchronization object
	 * @param array See plugin for parameters
	 * @return boolean
	 */
	function &_executeSynchronization( $synk, &$options )
	{
		// init variables
		$user 		= $options['user'];
		$isnew 		= $options['isnew'];
		$success 	= &$options['success'];
		$msg 		= &$options['msg'];
		$event 		= $options['event'];
		
		$success = false;
		
		// Local Database
		$localdb = JFactory::getDBO();
		
		// get synk database
		if(!($synkdb = &$this->getDatabase( $synk->databaseid ))){
			$msg->message .= JText::_('Failed to get target Database Object');
			return $success;
		}
		if ( $synkdb->getErrorNum() ) {
			$msg->message .= $synkdb->getErrorMsg();
			return $success;
		}
		
		// execute published plugins
		$args = $options;
		$args['synk'] = $synk;
		$dispatcher	   =& JDispatcher::getInstance();
		$result = $dispatcher->trigger('onBeforeRunSynchronizationSynk', array($options, $args ) );
		
		/* SYNCHRONIZE CATEGORIES
		 * -== WARNING: Existing com_weblinks Categories in target DB will be DELETED ==-
		 */
		
		$localdb->setQuery("SELECT * FROM `#__categories` WHERE `section`='com_weblinks'");
		$categories = $localdb->loadObjectList();
		
		// Delete remote com_weblinks categories
		$synkdb->setQuery("DELETE FROM `#__categories` WHERE `section`='com_weblinks'");
		if(!$synkdb->query()){
			$msg->message .= ' - '.JText::_('Failed to delete Weblinks Categories from target DB. ').$synkdb->getErrorMsg();
			return $success;
		}
		
		// Synchronize local categories to target DB
		foreach(@$categories as $category){
			if(!($synkdb->insertObject('#__categories', $category, 'id'))){
				$msg .= ' - '.JText::_('Failed to SYNK weblinks to target DB. ').$synkdb->getErrorMsg();
				return $success;
			}
		}
		
		// load all the data from the local server for the table being synk'd
		$localdb->setQuery( "SELECT * FROM `#__weblinks`" );
		$weblinks = $localdb->loadObjectList();

		//**                  WARNING                   **//
		//** This plugin will truncate the remote table **//
		//** at this point in order to import new data  **//
		//** with referential integrity                 **//
		//**                                            **//
		// check if target has entry
		$synkdb->setQuery( "TRUNCATE `#__weblinks`" );
		if (!$target = $synkdb->query()) {
			// returns false if unable to truncate the table
			return $success;
		}
		
		foreach(@$weblinks as $weblink){
			if(!($synkdb->insertObject('#__weblinks', $weblink, 'id'))){
				$msg .= ' - '.JText::_('Failed to SYNK weblinks to target DB. ').$synkdb->getErrorMsg();
				return $success;
			}
		}

		$success = true;

		// execute published plugins
		$args = $options;
		$args['success'] = $success;
		$dispatcher	   =& JDispatcher::getInstance();
		$dispatcher->trigger('onAfterRunSynchronizationSynk', array($options, $args ) );
		
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
        $db =& SynkHelperSynchronizations::getDatabase( $id );
        return $db;
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
