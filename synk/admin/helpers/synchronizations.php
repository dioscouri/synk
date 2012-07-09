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

Synk::load( 'SynkHelperBase', 'helpers.base' );

class pseudoJDatabase
{
	var $_errorNum;
	var $_errorMsg;
	
	function getErrorNum(){ return $this->_errorNum; }
	function getErrorMsg(){ return $this->_errorMsg; }
}

class SynkHelperSynchronizations extends SynkHelperBase
{
    /**
     * Gets the connection to the external DB 
     * TODO Move this to SynkHelperDatabases::getConnection($id)
     * 
     * @param integer the ID of the Database row to fetch
     * @return row as an object
     */
    function &getDatabase($id)
    {
        static $instances;
        
        if (!is_object($instances[$id]) || $instances[$id]->_errorNum ) 
        {
            JTable::addIncludePath( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_synk'.DS.'tables' );
            $row = JTable::getInstance('databases', 'Table');
            $row->load($id);
            
            if (!$row->id) {
                $instances[$id] = new pseudoJDatabase();
                $instances[$id]->_errorNum = '-212';
                $instances[$id]->_errorMsg = JText::_( 'This database record does not exist in SYNK' );
            } else {
                // verify connection
                $option['driver']   = $row->driver;            // Database driver name
                $option['host']     = $row->host;           // Database host name
                if ($row->port != '3306') { $option['host'] .= ":".$row->port; } // alternative ports
                $option['user']     = $row->user;         // User for database authentication
                $option['password'] = $row->password;    // Password for database authentication
                $option['database'] = $row->database;        // Database name
                $option['prefix']   = $row->prefix;               // Database prefix (may be empty)

                $database = JDatabase::getInstance( $option );

                if ( method_exists( $database, 'test' ) )
                {
                    // success
                    $instances[$id] = $database;
                }
                else
                {
                    $instances[$id] = new pseudoJDatabase();
                    $instances[$id]->_errorNum = '-213';
                    $instances[$id]->_errorMsg = JText::_( 'Could not connect to this DB.' );
                }
            }
        }
        
        return $instances[$id];
    }
	
	/**
	 * Returns an array of objects - all events associated with a synchronization
	 * 
	 * @return 
	 * @param integer Synchronization ID
	 * @param integer 1 for published (default), 0 for not published
	 */
	function getEvents( $id, $published='1' )
	{
		JLoader::import( 'com_synk.library.query', JPATH_ADMINISTRATOR.DS.'components' );
		
		$database = &JFactory::getDBO();
		
		$query = new SynkQuery();
		$query->select('`e`.*');
		$query->from('`#__synk_events` AS `e`');
		$query->leftjoin('`#__synk_s2e` AS `s2e` ON `s2e`.`eventid` = `e`.`id`');
		$query->where(array("`s2e`.`synchronizationid` = '$id'",
							"`e`.`published` = '$published'"));
		
		$database->setQuery( $query );
		$data = $database->loadObjectList();

		return $data;
	}
	
	/**
	 * Returns a list of synchronizations
	 * @param mixed A published event from the _events table
	 * @param mixed Boolean
	 * @param mixed Boolean
	 * @return array
	 */
	function &getSynchronizations( $event, $published=1, $verified=1 ) 
	{
		JLoader::import( 'com_synk.library.query', JPATH_ADMINISTRATOR.DS.'components' );
		$database = &JFactory::getDBO();
		
		$query = new SynkQuery();
		$query->select(array(
			'`s`.*',
			'`s2e`.`eventid`',
			'`e`.`title` AS `event_title`',
			'`s2e`.`parameter`'));
		$query->from('`#__synk_synchronizations` AS `s`');
		$query->leftjoin('`#__synk_databases` AS `d` ON `d`.`id` = `s`.`databaseid`');
		$query->leftjoin('`#__synk_s2e` AS `s2e` ON `s2e`.`synchronizationid` = `s`.`id`');
		$query->leftjoin('`#__synk_events` AS `e` ON `e`.`id` = `s2e`.`eventid`');
		$query->where(array(
			" LOWER(`e`.`title`) = '".$database->getEscaped(trim(strtolower($event)))."'",
			"`d`.`verified` = '".$database->getEscaped(trim($verified))."'",
			"`s`.`published` = '".$database->getEscaped(trim($published))."'"));
		
		$database->setQuery( $query );
		$data = $database->loadObjectList();

		return $data;
	}
	
    
    /**
     * Returns a list of synchronization hits
     * @param mixed an ID number
     * @param mixed period type  
     * @param mixed start datetime
     * @param mixed end datetime
     * @return array
     */
    function getHits( $id, $type="DAY", $start_datetime=NULL, $end_datetime=NULL  ) {
        $success = false;
        $database = &JFactory::getDBO();
        $date = &JFactory::getDate();
        if (!$start_datetime) { $start_datetime = $date->toMySQL(); }
        /**
        *
        * Valid 'type' values are HOUR, DAY, WEEK, MONTH, YEAR, CUSTOM
        *
        */
        switch ($type) {
            case "HOUR":
                $query = "SELECT DATE('".$start_datetime."')";      
                $database->setQuery( $query );
                $this_date = $database->loadResult();
                
                $query = "SELECT EXTRACT(HOUR FROM '".$start_datetime."')";     
                $database->setQuery( $query );
                $hour = $database->loadResult();
                
                $start = $this_date." ".$hour.":00:00";
                
                $query = "SELECT DATE_ADD('".$start."', INTERVAL 1 HOUR)";
                $database->setQuery( $query );
                $end = $database->loadResult();             
              break;
            case "DAY":
                $query = "SELECT DATE('".$start_datetime."')";      
                $database->setQuery( $query );
                $start = $database->loadResult();
                
                $query = "SELECT DATE_ADD('".$start_datetime."', INTERVAL 1 DAY)";
                $database->setQuery( $query );
                $end = $database->loadResult();             
              break;
            case "WEEK":
                $query = "SELECT DATE_SUB('".$start_datetime."', INTERVAL WEEKDAY('".$start_datetime."') DAY) ";        
                $database->setQuery( $query );
                $start = $database->loadResult();

                $query = "SELECT DATE_ADD('".$start."', INTERVAL 7 DAY)";
                $database->setQuery( $query );
                $end = $database->loadResult();             
              break;
            case "MONTH":
                $query = "SELECT EXTRACT(YEAR FROM '".$start_datetime."')";     
                $database->setQuery( $query );
                $year = $database->loadResult();

                $query = "SELECT EXTRACT(MONTH FROM '".$start_datetime."')";        
                $database->setQuery( $query );
                $month = $database->loadResult();
                
                $start = $year."-".$month."-01";

                $query = "SELECT DATE_ADD('".$start."', INTERVAL 1 MONTH)";
                $database->setQuery( $query );
                $end = $database->loadResult();             
              break;
            case "YEAR":
                $query = "SELECT EXTRACT(YEAR FROM '".$start_datetime."')";     
                $database->setQuery( $query );
                $year = $database->loadResult();
                
                $start = $year."-01-01";

                $query = "SELECT DATE_ADD('".$start."', INTERVAL 1 YEAR)";
                $database->setQuery( $query );
                $end = $database->loadResult();
              break;
            case "CUSTOM":
                $query = "SELECT CAST('".$start_datetime."' AS DATETIME)";
                $database->setQuery( $query );
                $start = $database->loadResult();

                $query = "SELECT CAST('".$end_datetime."' AS DATETIME)";
                $database->setQuery( $query );
                $end = $database->loadResult();
              break;
            default: 
                return $success; 
              break;
        }

        $date_query = " AND `datetime` > '".$start."' AND `datetime` < '".$end."' ";
        
        // all records      
        $query = "SELECT * FROM #__synk_logs "
        . " WHERE 1 "
        . $date_query
        . " AND `synchronizationid` = '".$id."' "
        ;

        $database->setQuery( $query );
        $data = $database->loadObjectList();

        return $data;
    }
    
    /**
     * Wrapper for backwards compatability
     * 
     * @param unknown_type $id
     * @param unknown_type $type
     * @param unknown_type $start_datetime
     * @param unknown_type $end_datetime
     * @return unknown_type
     */
    function &getSynchronizationHits( $id, $type="DAY", $start_datetime=NULL, $end_datetime=NULL  ) 
    {
    	$return = SynkHelperSynchronizations::getHits( $id, $type, $start_datetime, $end_datetime );
        return $return; 
    }
	
    /**
     * Checks that Synk can be run
     * @param object A valid synchronization object
     * @return boolean
     */
    function &canRun( $synk ) 
    {
        global $mainframe;
        $database = &JFactory::getDBO();
        $success = false;
        
        // is it published?
        if ($synk->published != 1) {
            return $success;
        }
        
        // has its publication period expired?
        $date =& JFactory::getDate();
        if ($date->toMySQL() < $synk->publish_up) {
            return $success;
        }
        if ($date->toMySQL() > $synk->publish_down && $synk->publish_down > $database->getNullDate() ) {
            return $success;
        }
                
        // is the database verified?
        $synkdb = &$this->getDatabase( $synk->databaseid );
        if (!$synkdb || $synkdb->_errorNum ) {
            return $success;
        }
                
        // has it reached any limits?
        // Hourly:
        if (intval($synk->limit_hourly) > 0 || strtoupper($synk->event_title) == "HOURLY") {
            $data = &SynkHelperSynchronizations::getSynchronizationHits( $synk->id, "HOUR" );
            if (count($data) > $synk->limit_hourly) { return $success; }
            if ( count($data) > 0 && strtoupper($synk->event_title) == "HOURLY") { return $success; }
        }
        // Daily:   
        if (intval($synk->limit_daily) > 0 || strtoupper($synk->event_title) == "DAILY") {
            $data = &SynkHelperSynchronizations::getSynchronizationHits( $synk->id, "DAY" );
            if (count($data) > $synk->limit_daily) { return $success; }             
            if ( count($data) > 0 && strtoupper($synk->event_title) == "DAILY") { return $success; }
        }
        // Weekly:  
        if (intval($synk->limit_weekly) > 0 || strtoupper($synk->event_title) == "WEEKLY") {
            $data = &SynkHelperSynchronizations::getSynchronizationHits( $synk->id, "WEEK" );
            if (count($data) > $synk->limit_weekly) { return $success; }
            if ( count($data) > 0 && strtoupper($synk->event_title) == "WEEKLY") { return $success; }
        }
        // Monthly:     
        if (intval($synk->limit_monthly) > 0 || strtoupper($synk->event_title) == "MONTHLY") {
            $data = &SynkHelperSynchronizations::getSynchronizationHits( $synk->id, "MONTH" );
            if (count($data) > $synk->limit_monthly) { return $success; }
            if ( count($data) > 0 && strtoupper($synk->event_title) == "MONTHLY") { return $success; }
        }
        // Yearly:      
        if (intval($synk->limit_yearly) > 0  || strtoupper($synk->event_title) == "YEARLY") {
            $data = &SynkHelperSynchronizations::getSynchronizationHits( $synk->id, "YEAR" );
            if (count($data) > $synk->limit_yearly) { return $success; }
            if ( count($data) > 0 && strtoupper($synk->event_title) == "YEARLY") { return $success; }
        }
        
        // if it is a time-based synchronization, has it reached its parameter setting?
        $start_datetime = $date->toMySQL();
        switch (strtoupper($synk->event_title)) {
            case "HOURLY":
                $query = "SELECT EXTRACT(MINUTE FROM '".$start_datetime."')";       
                $database->setQuery( $query );
                $current = $database->loadResult();
                if (intval($current) < intval($synk->parameter)) { return $success; }
              break;
            case "DAILY":
                $query = "SELECT EXTRACT(HOUR FROM '".$start_datetime."')";
                $database->setQuery( $query );
                $current = $database->loadResult();
                if (intval($current) < intval($synk->parameter)) { return $success; }
              break;
            case "WEEKLY":
                $query = "SELECT WEEKDAY('".$start_datetime."')";
                $database->setQuery( $query );
                $current = $database->loadResult();
                if (intval($current) < intval($synk->parameter)) { return $success; }
              break;
            case "MONTHLY":
                $query = "SELECT EXTRACT(DAY FROM '".$start_datetime."')";
                $database->setQuery( $query );
                $current = $database->loadResult();
                if (intval($current) < intval($synk->parameter)) { return $success; }
              break;
            default:
              break;
        }
        
        $success = true;
        return $success;
    }
    
    /**
     * Wrapper for backwards compatability
     * 
     * @param $synk
     * @return unknown_type
     */
    function canRunSynchronization($synk)
    {
        return SynkHelperSynchronizations::canRun($synk);
    }
    
    /**
     * Log a Synk
     * @param object A valid synchronization object
     * @param array See plugin for parameters
     * @param boolean If synk was successful or no
     * @return boolean
     */
    function createLog( $synk, &$options, $runSynk ) {
        $success = false; 
        
        $database = &JFactory::getDBO();
        $date = gmdate("Y-m-d H:i:s");

        // init variables
        $user       = $options['user'];
        $isnew      = $options['isnew'];
        $success    = $options['success'];
        $msg        = &$options['msg'];
        $event      = $options['event'];
        $article    = empty( $options['article'] ) ? "" : $options['article']; 
            
        unset($log);
        
        $log = JTable::getInstance( 'logs', 'Table' );
         
        $log->description           = $msg->message;
        $log->synchronizationid     = $synk->id;
        $log->databaseid            = $synk->databaseid;
        $log->eventid               = $synk->eventid;
        $log->userid                = $user['id'];
        $log->contentid             = isset($article->id) ? $article->id : '';
        $log->datetime              = $date;
        $log->success               = $runSynk;

        // execute published plugins
        $args = $options;
        $args['synk'] = $synk;
        $args['runSynk'] = $runSynk;
        $dispatcher    =& JDispatcher::getInstance();
        $dispatcher->trigger('onBeforeLogSynchronizationSynk', array( $args ) );    
                
        // log the synk
        if ( !$log->store() ) { 
            $msg->message .= ' - '.JText::_( 'Store Log Failed' ); 
        } else { 
            $success = true; 
        }

        // execute published plugins
        $args = $options;
        $args['success'] = $success;
        $dispatcher    =& JDispatcher::getInstance();
        $dispatcher->trigger('onAfterLogSynchronizationSynk', array( $args ) ); 
                    
        return $success;
    }

    /**
     * Wrapper for backwards compatability
     * 
     * @param $synk
     * @param $options
     * @param $runSynk
     * @return unknown_type
     */
    function logSynchronization($synk, &$options, $runSynk)
    {
        return SynkHelperSynchronizations::createLog($synk, $options, $runSynk);
    }
}
