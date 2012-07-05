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
class plgSystemSynk_eventlist extends JPlugin {

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
	function plgSystemSynk_eventlist( &$subject, $params )
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
		$options['success']	=& $success;
		$options['msg']		=& $msg;
		$options['synktype']= "system";
		$options['event'] = "onSynkEventlist";
		
		// grab all relevant synks
		$synchronizations 	= &SynkHelperSynchronizations::getSynchronizations( $options['event'] );
		
		// loop through synks
		for ($i=0; $i<count($synchronizations); $i++) 
		{
			$synk = $synchronizations[$i];

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
                $runSynk = $this->runSynchronization( $synk, $options );
                
            }
		}
		
		// return to original db
		$dbtable =& JTable::getInstance('user');		
		$dbtable->setDBO( $database );
		
		$success = true;
		return $success;
	}
	
	function runSynchronization( $synk, $options )
	{
		// execute each of the synks if it is time for them to run
            if (!$this->canRunSynchronization( $synk )) {
                return null;
            }
            // check for item type to synk
            if ($this->_getParameter('events', 0)) {
                // synk events
                $return = $this->synkEvents( $synk, $options );
            }
            if ($this->_getParameter('venues', 0)) {
                // synk venues
                $return = $this->synkVenues( $synk, $options );
            }
            if ($this->_getParameter('categories', 0)) {
                // synk categories
                $return = $this->synkCategories( $synk, $options );
            }
            if ($this->_getParameter('groups', 0)) {
                // synk users
                $return = $this->synkGroups( $synk, $options );
            }
            
        return;
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
     * Synchronize and log 
     * @param $synk
     * @param $options
     * @return unknown_type
     */
	function synkEvents( $synk, $options ) {
		JPluginHelper::importPlugin( 'synk' );
		$runSynk = false; 
		$runSynk = &$this->_executeSynchronization('#__eventlist_events', $synk, $options );
		$logSynk = &$this->logSynchronization( $synk, $options, $runSynk );
		return $runSynk;
	}

    /**
     * Synchronize and log 
     * @param $synk
     * @param $options
     * @return unknown_type
     */
	function synkVenues( $synk, $options ) {
		JPluginHelper::importPlugin( 'synk' );
		$runSynk = false; 
		$runSynk = &$this->_executeSynchronization('#__eventlist_venues', $synk, $options );
		$logSynk = &$this->logSynchronization( $synk, $options, $runSynk );
		return $runSynk;
	}
	
    /**
     * Synchronize and log 
     * @param $synk
     * @param $options
     * @return unknown_type
     */
	function synkCategories( $synk, $options ) {
		JPluginHelper::importPlugin( 'synk' );
		$runSynk = false; 
		$runSynk = &$this->_executeSynchronization('#__eventlist_categories', $synk, $options );
		$logSynk = &$this->logSynchronization( $synk, $options, $runSynk );
		return $runSynk;
	}
	
    /**
     * Synchronize and log 
     * @param $synk
     * @param $options
     * @return unknown_type
     */
	function synkGroups( $synk, $options ) {
		JPluginHelper::importPlugin( 'synk' );
		$runSynk = false; 
		$runSynk = &$this->_executeSynchronization('#__eventlist_groups', $synk, $options );
		$logSynk = &$this->logSynchronization( $synk, $options, $runSynk );
		return $runSynk;
	}
	
	/**
	 * Execute a Synk
	 * @param object A valid synchronization object
	 * @param array See plugin for parameters
	 * @return boolean
	 */
	function &_executeSynchronization( $table, $synk, &$options )
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
		
		// load all the data from the local server for the table being synk'd
		$localdb = JFactory::getDBO();
		$localdb->setQuery( "SELECT * FROM `$table`" );
		$data = $localdb->loadObjectList();
		
		// setDBO to the remote Db
		$dbtable = & JTable::getInstance('user');
		$dbtable->setDBO( $synkdb );

		// execute published plugins
		$args = $options;
		$args['synk'] = $synk;
		$dispatcher	   =& JDispatcher::getInstance();
		$result = $dispatcher->trigger('onBeforeRunSynchronizationSynk', array($options, $args ) );

		//**                  WARNING                   **//
		//** This plugin will truncate the remote table **//
		//** at this point in order to import new data  **//
		//** with referential integrity                 **//
		//**                                            **//
		// check if target has entry
		$synkdb->setQuery( "TRUNCATE `$table`" );
		if (!($target = $synkdb->query())) {
			// returns false if unable to truncate the table
			return $success;
		}
		
		if($data == array()){ // Empty table. Nothing to Synk, so after truncation we should quit.
			$success = true;
			return $success;
		}

		// build column string for insert
		$cols = array();
		$tvalues = array();
		
		// for each row of data
		for ($i=0; $i<count($data); $i++) {
			// build values string array
			$item = get_object_vars($data[$i]);
			$tvalues[$i]  = "(";
			foreach($item as $im){
				$tvalues[$i] .= "'".$synkdb->getEscaped($im)."',";
			}
			$tvalues[$i] = substr($tvalues[$i], 0, -1).")";
		}
		foreach ($item AS $key => $values) {
			$cols[] = $key;
		}
		// create new entry

		$theValues = join(', ', $tvalues);
		$synkdb->setQuery( "INSERT INTO `$table` ( ".join(', ', $cols).") VALUES ".$theValues );
		if ( !$result = $synkdb->query() ) {
			$msg->message .= ' - '.JText::_( 'Unable to Synk Eventlist data ' ).': '.$synkdb->getErrorMsg();
			return $success;
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
