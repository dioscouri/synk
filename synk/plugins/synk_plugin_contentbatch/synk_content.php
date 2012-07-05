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
jimport('joomla.plugin.plugin');

class plgSystemSynk_content extends JPlugin {

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
	function plgSystemSynk_content(& $subject, $config) {
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
     * @return unknown_type
     */
    function _isInstalled()
    {
        $success = false;
        
        jimport('joomla.filesystem.file');
        if (JFile::exists(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_synk'.DS.'helpers'.DS.'synchronizations.php')) 
        {
        	require_once( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_synk'.DS.'helpers'.DS.'synchronizations.php' );
            JTable::addIncludePath( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_synk'.DS.'tables'.DS );
            $success = true;
        }
        
        return $success;
    }
	
	/**
	 * 
	 * @return 
	 * @param $article Object
	 */
	function checkParameters( $article )
	{
		$success = false;
		$articles_all 	= $this->_getParameter( 'articles_all', '1' );
		$categories_all = $this->_getParameter( 'categories_all', '1' );
		$sections_all 	= $this->_getParameter( 'sections_all', '1' );

		$articles_list 		= @preg_replace( '/\s/', '', $this->_getParameter( 'articles_list', '' ) );
		$categories_list 	= @preg_replace( '/\s/', '', $this->_getParameter( 'categories_list', '' ) );
		$sections_list 		= @preg_replace( '/\s/', '', $this->_getParameter( 'sections_list', '' ) );

		$articles_array		= explode( ',', $articles_list );
		$categories_array	= explode( ',', $categories_list );
		$sections_array		= explode( ',', $sections_list );
		
		// check articles
		if ($articles_all == '1') { $success = true; }
		elseif (in_array($article->id, $articles_array)) { $success = true; }
		else {
			// check categories
			if ($categories_all == '1') { $success = true; }
			elseif (in_array($article->catid, $categories_array)) { $success = true; }
			else {
				// check sections
				if ($sections_all == '1') { $success = true; }
				elseif (in_array($article->sectionid, $sections_array)) { $success = true; }
			}				
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
		
		$userObject = &JFactory::getUser();
		$user = array();
		$user = JArrayHelper::fromObject( $userObject );

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
		$options['event']	= 'onSynkContent';
				
		// grab all relevant synks
		$synchronizations 	= &SynkHelperSynchronizations::getSynchronizations( $options['event'] );
		
		// loop thru synks
		for ($i=0; $i<count($synchronizations); $i++) {
			$synk = $synchronizations[$i];

			// get all the synk's events WHERE
			// title != $event AND title IN (HOURLY, DAILY, WEEKLY, MONTHLY)
			$events = &plgSystemSynk_content::getSynkEvents( $synk, $options['event'] );

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
	 * 
	 * @return 
	 * @param object $refresh[optional]
	 */
	function getManagedArticles( $refresh='0' )
	{
		static $managed;
		
		if (!is_object($managed) || $refresh == '1' )
		{
			$managed = new stdClass();
			$managed->articles 		= array(); // an array of id numbers
			$managed->categories 	= array(); 
			$managed->sections 		= array(); 
			$managed->articles_string 	= ''; // csv string of id numbers
			$managed->categories_string = '';
			$managed->sections_string 	= '';
			
			$articles_all 	= $this->_getParameter( 'articles_all', '1' );
			$categories_all = $this->_getParameter( 'categories_all', '1' );
			$sections_all 	= $this->_getParameter( 'sections_all', '1' );
	
			$articles_list 		= @preg_replace( '/\s/', '', $this->_getParameter( 'articles_list', '' ) );
			$categories_list 	= @preg_replace( '/\s/', '', $this->_getParameter( 'categories_list', '' ) );
			$sections_list 		= @preg_replace( '/\s/', '', $this->_getParameter( 'sections_list', '' ) );
	
			$articles_array		= explode( ',', $articles_list );
			$categories_array	= explode( ',', $categories_list );
			$sections_array		= explode( ',', $sections_list );
			
			// check articles
			if ($articles_all == '1') 
			{ 
				$localdb = JFactory::getDBO();
				$localdb->setQuery( "SELECT `id` FROM #__content " );
				$items = $localdb->loadResultArray();
				$managed->articles = $items;
			} else {
				$managed->articles = $articles_array;
			}

			// check categories
			if ($categories_all == '1')
			{ 
				$localdb = JFactory::getDBO();
				$localdb->setQuery( "SELECT `id` FROM #__categories " );
				$items = $localdb->loadResultArray();
				$managed->categories = $items; 
			} else { 
				$managed->categories = $categories_array; 
			}

			// check sections
			if ($sections_all == '1') 
			{ 
				$localdb = JFactory::getDBO();
				$localdb->setQuery( "SELECT `id` FROM #__sections " );
				$items = $localdb->loadResultArray();
				$managed->sections = $items;
			} else {
				$managed->sections = $sections_array;
			}
			
			$managed->articles_string 	= "'".implode( "', '", $managed->articles )."'";
			$managed->categories_string = "'".implode( "', '", $managed->categories )."'";
			$managed->sections_string 	= "'".implode( "', '", $managed->sections )."'";
		}
		
		return $managed;
	}
	
	/**
	 * Wrapper to run a Synk
	 * @param object A valid synchronization object
	 * @param array See plugin for parameters
	 * @return boolean
	 */
	function &runSynchronization( $synk, &$options ) {
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
	function &_executeSynchronization( $synk, &$options )
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
			$msg->message .= $synkdb->_errorMsg;
			return $success;
		}
		
		// load all the categories from the local server
		$localdb = JFactory::getDBO();
		$localdb->setQuery( "SELECT * FROM #__content" );
		$items = $localdb->loadObjectList();
				
		for ($i=0; $i<count($items); $i++) {
			// foreach, store on the target db
			$item = $items[$i];

			// if not to be synchronized, continue
			if (!$this->checkParameters( $item )) {
				continue;
			}
			
			// load the table
			unset($table);
			$table = & JTable::getInstance( 'content' );
			$table->load( $item->id );
			
			// setDBO to the db from the synk
			$table->setDBO( $synkdb );

			// execute published plugins
			$args = $options;
			$args['synk'] = $synk;
			$dispatcher	   =& JDispatcher::getInstance();
			$result = $dispatcher->trigger('onBeforeRunSynchronizationSynk', array( $args ) );	
			
			// check if target has entry
			$synkdb->setQuery( "SELECT * FROM #__content WHERE `id` = '".$item->id."' " );
			$target = $synkdb->loadObject();
			if (empty($target->id)) { $is_target_new = true; }
			else $is_target_new = false;

			// TODO currently if a newely created local artical has the same ID with an existing
			// previously created article in the target DB, the target will be overwritten with the local one.
			if ($is_target_new) {
				// create new entry
				$synkdb->setQuery( "INSERT INTO #__content SET `id` = '".$item->id."' " );
				if ( !$synkdb->query() ) { 
					$msg->message .= ' - '.JText::_( 'New Content Insertion Failed' ).': '.$synkdb->getErrorMsg();
					return false;
				}
			}

			// store using new values
			if ( !$table->store() ) { 
				$msg->message .= ' - '.JText::_( 'Update Content Failed' ).': '.$table->getError();
				return false;
			}
			
		    // if set to do so, update the __content_frontpage setting
	        if ($this->params->get( 'articles_frontpage', '1' ))
	        {
	            $localdb = JFactory::getDBO();
	            $localdb->setQuery( "SELECT * FROM #__content_frontpage WHERE `content_id` = '".$item->id."' " );
	            // make sure the synkdb also has the same settings
	            if ($frontpage = $localdb->loadObject())
	            {
	                // insert/update the fp status on synkdb
	                $synkdb->setQuery( "SELECT * FROM #__content_frontpage WHERE `content_id` = '".$item->id."' " );
	                if ($frontpage_synkdb = $synkdb->loadObject())
	                {
	                    // update
	                    $synkdb->setQuery( "UPDATE #__content_frontpage SET `ordering` = '$frontpage->ordering' WHERE `content_id` = '".$item->id."' " );
	                    $synkdb->query();
	                }
	                   else
	                {
	                    // insert
	                    $synkdb->setQuery( "INSERT INTO #__content_frontpage SET `content_id` = '".$item->id."', `ordering` = '$frontpage->ordering' " );
	                    $synkdb->query();
	                }
	            } 
	                else
	            {
	                // delete on synkdb
	                $synkdb->setQuery( "DELETE FROM #__content_frontpage WHERE `content_id` = '".$item->id."' " );
	                $synkdb->query();   
	            }
	        }
			
			$success = true;
			
			// execute published plugins
			$args = $options;
			$args['success'] = $success;
			$dispatcher	   =& JDispatcher::getInstance();
			$dispatcher->trigger('onAfterRunSynchronizationSynk', array( $args ) );
			
		}

		$deletefromtarget 	= $this->_getParameter( 'deletefromtarget', '0' );
		// if to be deleted, continue
		if ($deletefromtarget) {
			$success = $this->_executeSynchronizationDelete( $synk, $options, $items );
		}

		return $success;
	}
	
	/**
	 * 
	 * @return 
	 * @param object $synk
	 * @param object $options
	 * @param object $items
	 */
	function &_executeSynchronizationDelete( $synk, &$options, $items )
	{
		$count = 0;

		// init variables
		$user 		= $options['user'];
		$isnew 		= $options['isnew'];
		$success 	= &$options['success'];
		$msg 		= &$options['msg'];
		$event 		= $options['event'];
		$article 	= $options['article'];
		
		$success = false;
				
		// get all articles from source
		$localdb = JFactory::getDBO();
		$localdb->setQuery( "SELECT `id` FROM #__content " );
		$allSourceArticles = $localdb->loadResultArray();
		
		// get managed articles from source
		$managed = plgSystemSynk_content::getManagedArticles();

		// get synk database
		if(!($synkdb = &$this->getDatabase( $synk->databaseid ))){
			$msg->message .= JText::_('Failed to get target Database Object');
			return $success;
		}
		if ( $synkdb->getErrorNum() ) {
			$msg->message .= $synkdb->_errorMsg;
			return $success;
		}
		
		// select all ids from target that are "managed_articles" based on their id, catid or sectionid
			$query = "
				SELECT
					`id`
				FROM
					#__content
				WHERE
					`id` IN ({$managed->articles_string})
					OR `catid` IN ({$managed->categories_string})
					OR `sectionid` IN ({$managed->sections_string})
			";			
			$synkdb->setQuery( $query );
			$managedTargetArticles = $synkdb->loadObjectList();
		
		// loop through ids
		for ($t=0; $t<count($managedTargetArticles); $t++)
		{
			$tArticle = $managedTargetArticles[$t];
			
			// if not present on source, add to array of ids to be deleted from target
			if (!in_array($tArticle->id, $allSourceArticles))
			{
				if (!is_array($ids2del)) { $ids2del = array(); }
				
				$ids2del[] = $tArticle->id; 
				$count++;				
			}
		}
		
		// delete from target
		if ($count > '0') {
			$string = "'".implode( "', '", $ids2del )."'";
			$query = "
				DELETE FROM
					#__content
				WHERE
					`id` IN ({$string})
			";			
			$synkdb->setQuery( $query );
			if ( !$synkdb->query() ) { 
				$msg->message .= ' - '.JText::_( 'Content Delete Failed' ).': '.$synkdb->getErrorMsg();
				return false;
			}
		}
		
		$success = true;
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
