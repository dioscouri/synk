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

jimport('joomla.filesystem.file');

if (JFile::exists(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_synk'.DS.'helpers'.DS.'synk.php')) {
	require_once( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_synk'.DS.'helpers'.DS.'synchronizations.php' );
	if (!defined('synkFileExists')) {
		DEFINE( "synkFileExists", '1');
	}
}

// Import library dependencies
jimport('joomla.plugin.plugin');

/**
 * Synk
 */
class plgSystemSynk_categories extends JPlugin {

	/**
	 * Constructor
	 *
	 * For php4 compatability we must not use the __constructor as a constructor for plugins
	 * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
	 * This causes problems with cross-referencing necessary for the observer design pattern.
	 *
	 * @access	protected
	 * @param	object	$subject The object to observe
	 * @param 	array   $config  An array that holds the plugin configuration
	 * @since	1.0
	 */
	function plgSystemSynk_categories(& $subject, $config) {
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
		$success = false;
		$categories_all = $this->_getParameter( 'categories_all', '1' );
		$categories_list = @preg_replace( '/\s/', '', $this->_getParameter( 'categories_list', '' ) );
		$categories_array = explode( ',', $categories_list );
		
		// check categories
		if ($categories_all == '1') { $success = true; }
		elseif (in_array($item->id, $categories_array)) { $success = true; }
				
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
	*
	*/
	function onAfterInitialise() 
	{
		$success = false;
		$database = JFactory::getDBO();
		
	    if (!$this->_isInstalled()) {
            return $success;
        }
		
		$thisUser = &JFactory::getUser();
		$user = array();
		$user['id'] = $thisUser->id;
		
		$msg = new stdClass();
		$msg->error = '';
		$msg->message = '';
		
		// prep parameters for passing via single array
		$options = array();
		$options['user']	= $user;
		$options['isnew']	= false;
		$options['success']	=& $success;
		$options['msg']		=& $msg;
		$options['synktype']= 'system';
		$options['event']	= 'onSynkCategories';
				
		// grab all relevant synks
		$synchronizations 	= &SynkHelperSynchronizations::getSynchronizations( $options['event'] );
		
		// loop thru synks
		for ($i=0; $i<count($synchronizations); $i++) {
			$synk = $synchronizations[$i];

			// get all the synk's events WHERE
			// title != $event AND title IN (HOURLY, DAILY, WEEKLY, MONTHLY)
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
				$runSynk = &$this->runSynchronization( $synk, $options );
				
			}
		}
		
		// return to original db
		$table = & JTable::getInstance('user');		
		$table->setDBO( $database );
	}

	/**
	 * Returns a list
	 * @param mixed Boolean
	 * @param mixed Boolean
	 * @return array
	 */
	function &getSynkEvents( $synk, $exceptions='' ) {
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
	 * Wrapper to run a Synk
	 * @param object A valid synchronization object
	 * @param array See plugin for parameters
	 * @return boolean
	 */
	function &runSynchronization( $synk, $options ) {
		// load the plugins
		JPluginHelper::importPlugin( 'synk' );
				
		$success = false; 
		
		if (!isset($options['synktype'])) {
			return $success;
		}

		/**
		*
		* CAN THE SYNK BE RUN
		*
		*/
		if ( !$this->canRunSynchronization( $synk ) ) {
			return $success;
		}
				
		/**
		*
		* RUN THE SYNK
		*
		*/
		$runSynk = &$this->_executeSynchronization( $synk, $options );

		/**
		*
		* LOG THE SYNK
		*
		*/
		$logSynk = &$this->logSynchronization( $synk, $options, $runSynk );
		
		return $runSynk;
	}

	/**
	 * Execute a Synk
	 * @param object A valid synchronization object
	 * @param array See plugin for parameters
	 * @return boolean
	 */
	function &_executeSynchronization( $synk, &$options ) {
		$success = false;

		// init variables
		$user 		= $options['user'];
		$isnew 		= $options['isnew'];
		$success 	= &$options['success'];
		$msg 		= &$options['msg'];
		$event 		= $options['event'];

		// get synk database
		$synkdb = &$this->getDatabase( $synk->databaseid );
		if ( !$synkdb || $synkdb->_errorNum ) {
			$msg->message .= $synkdb->getErrorMsg();
			return false;
		}
		
		// load all the categories from the local server
		$localdb = JFactory::getDBO();
		$localdb->setQuery( "SELECT * FROM #__categories" );
		$categories = $localdb->loadObjectList();
		
		for ($i=0; $i<count($categories); $i++) {
			// foreach category, store on the target db
			$item = $categories[$i];

			// if not to be synchronized, continue
			if (!$this->checkParameters( $item )) {
				continue;
			}
			
			// load the table
			unset($table);
			$table = & JTable::getInstance( 'category' );
			$table->load( $item->id );
			
			// setDBO to the db from the synk
			$table->setDBO( $synkdb );

			// execute published plugins
			$args = $options;
			$args['synk'] = $synk;
			$dispatcher	   =& JDispatcher::getInstance();
			$result = $dispatcher->trigger('onBeforeRunSynchronizationSynk', array( $args ) );	
			
			// check if target has entry
			$synkdb->setQuery( "SELECT * FROM #__categories WHERE `id` = '".$item->id."' " );
			$target = $synkdb->loadObject();
			if (empty($target->id)) { $isnew = true; }
						
			if ($isnew) {
				// create new entry
				$synkdb->setQuery( "INSERT INTO #__categories SET `id` = '".$item->id."' " );
				if ( !$synkdb->query() ) { 
					$msg->message .= ' - '.JText::_( 'New Category Insertion Failed' ).': '.$synkdb->getErrorMsg();
					return false;
				}
			}
						
			// store using new values
			if ( !$table->store() ) { 
				$msg->message .= ' - '.JText::_( 'Update Content Failed' ).': '.$table->getError();
				return false;
			}
	
			$success = true;
			
			// execute published plugins
			$args = $options;
			$args['success'] = $success;
			$dispatcher	   =& JDispatcher::getInstance();
			$dispatcher->trigger('onAfterRunSynchronizationSynk', array( $args ) );
			
		}

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
