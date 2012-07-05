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

class plgContentSynk extends JPlugin 
{

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
	function plgContentSynk( &$subject, $params )
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
	 * @return 
	 * @param $article Object
	 */
	function checkParameters( $article )
	{
		$success = false;
		$articles_all 	= $this->_getParameter( 'articles_all', '1' );
		$articles_frontpage_all = $this->_getParameter( 'articles_frontpage_all', '1' );
		$categories_all = $this->_getParameter( 'categories_all', '1' );
		$sections_all 	= $this->_getParameter( 'sections_all', '1' );

		$articles_list 		= @preg_replace( '/\s/', '', $this->_getParameter( 'articles_list', '' ) );
		$categories_list 	= @preg_replace( '/\s/', '', $this->_getParameter( 'categories_list', '' ) );
		$sections_list 		= @preg_replace( '/\s/', '', $this->_getParameter( 'sections_list', '' ) );
		
		// Get the frontpage article IDs
		$db = &JFactory::getDBO();
		$db->setQuery("SELECT `content_id` FROM `#__content_frontpage`");
		$results = $db->loadObjectList();
		$articles_frontpage_array = array();
		foreach($results as $res) { $articles_frontpage_array[] = $res->content_id; }
		
		$articles_array		= explode( ',', $articles_list );
		$categories_array	= explode( ',', $categories_list );
		$sections_array		= explode( ',', $sections_list );
		
		// check articles
		if ($articles_all == '1') { $success = true; }
		elseif (in_array($article->id, $articles_array)) { $success = true; }
		elseif (in_array($article->id, $articles_frontpage_array)) { $success = true; }
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
	 * Example after save content method
	 * Article is passed by reference, but after the save, so no changes will be saved.
	 * Method is called right after the content is saved
	 *
	 *
	 * @param 	object		A JTableContent object
	 * @param 	bool		If the content is just about to be created
	 * @return	void		
	 */
	function onAfterContentSave( &$article, $isNew )
	{
		$success = false;
		
	    if (!$this->_isInstalled()) {
            return $success;
        }
        
        // Initialize variables
		$msg = new stdClass();
		$msg->error = '';
		$msg->message = '';
		
		$database = &JFactory::getDBO();
		$userObject = &JFactory::getUser();
		
		$user = array();
		$user = JArrayHelper::fromObject( $userObject );
		
		// prep parameters for passing via single array
		$options = array();
		$options['user']	= $user;
		$options['article']	= $article;
		$options['isnew']	= $isNew;
		$options['success']	=& $success;
		$options['msg']		=& $msg;
		$options['synktype']= "content";
		$options['event']	= "onAfterContentSave";
		
		// if article is not to be synchronized, break
		if (!$this->checkParameters( $article )) {
			$success = true;
			return $success;
		}
		
		// grab all relevant synks
		$synchronizations = &SynkHelperSynchronizations::getSynchronizations( $options['event'] );
		
		// loop through synks
		for ($i=0; $i<count($synchronizations); $i++) {
			$synk = $synchronizations[$i];
						
			/**
			*
			* RUN THE SYNK
			*
			*/
			$runSynk = &$this->runSynchronization( $synk, $options );
		}
		
		// return to original db
		$table = & JTable::getInstance('content');
		$table->setDBO( $database );

		$success = true;
		return $success;
	}
	
	/**
	 * Runs a Custom Content Synk
	 * @param object A valid synchronization object
	 * @param array See plugin for parameters
	 * @return boolean
	 */
	function &runCustomContentSynchronization( $synk, &$options )
	{
		// init variables
		$user 		= $options['user'];
		$isnew 		= $options['isnew'];
		$success 	= &$options['success'];
		$msg 		= &$options['msg'];
		$event 		= $options['event'];
		$article 	= $options['article'];
		
		$success = false;
		
		// get synk database
		if(!($synkdb = &$this->getDatabase( $synk->databaseid ))){
			$msg->message .= JText::_('Failed to get target Database Object');
			return false;	
		}
		if ( $synkdb->getErrorNum() ) {
			$msg->message .= $synkdb->getErrorMsg();
			return false;
		}
		
		// format custom query
		// replace any instances of {userid} with user['id']
		$query = stripslashes( $synk->custom_query );
		$query = preg_replace( '({userid})', $user['id'], $query );

		// replace any instances of {contentid} with article->id
		$query = stripslashes( $synk->custom_query );
		$query = preg_replace( '({contentid})', $article->id, $query );			
		
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
	 * Runs a Content Synk
	 * @param object A valid synchronization object
	 * @param array See plugin for parameters
	 * @return boolean
	 */
	function &runContentSynchronization( $synk, &$options ) 
	{
		// init variables
		$user 		= $options['user'];
		$isnew 		= $options['isnew'];
		$success 	= &$options['success'];
		$msg 		= &$options['msg'];
		$event 		= $options['event'];
		$article 	= $options['article'];
		
		// get synk database
		if(!($synkdb = &$this->getDatabase( $synk->databaseid ))){
			$msg->message .= JText::_('Failed to get target Database Object');
			return false;	
		}
		if ( $synkdb->getErrorNum() ) {
			$msg->message .= $synkdb->getErrorMsg();
			return false;
		}
	
		// load the table
		$table = & JTable::getInstance('content');
		$table->load( $article->id );
		
		// setDBO to the db from the synk
		$table->setDBO( $synkdb );
		
		// check if target has entry
		$synkdb->setQuery( "SELECT * FROM #__content WHERE `id` = '".$article->id."' " );
		$target = $synkdb->loadObject();
		if (!$target || !$target->id) { $is_target_new = true; }
		else $is_target_new = false;
		
		// TODO currently if a newely created local artical has the same ID with an existing
		// previously created article in the target DB, the target will be overwritten with the local one.
		if ($is_target_new) {
			// create new entry
			$synkdb->setQuery( "INSERT INTO #__content SET `id` = '".$article->id."' " );
			if (!$synkdb->query()) {
				$msg->message .= ' - '.JText::_( 'Insert New Article Failed' ).': '.$synkdb->getErrorMsg();
				return false;
			}
		}

		// execute published plugins
		$args = $options;
		$args['synk'] = $synk;
		$dispatcher	   =& JDispatcher::getInstance();
		$dispatcher->trigger('onBeforeRunSynchronizationSynk', array( $args ) );	
				
		// store using new values
		if ( !$table->store() ) { 
			$msg->message .= ' - '.JText::_( 'Content Store Failed' ).': '.$table->getError();
			return false;
		}
		
		// if set to do so, update the __content_frontpage setting
		if ($this->params->get( 'articles_frontpage', '1' ))
		{
			$localdb = JFactory::getDBO();
            $localdb->setQuery( "SELECT * FROM #__content_frontpage WHERE `content_id` = '".$article->id."' " );
            // make sure the synkdb also has the same settings
            if ($frontpage = $localdb->loadObject())
            {
                // insert/update the fp status on synkdb
                $synkdb->setQuery( "SELECT * FROM #__content_frontpage WHERE `content_id` = '".$article->id."' " );
            	if ($frontpage_synkdb = $synkdb->loadObject())
            	{
            		// update
	                $synkdb->setQuery( "UPDATE #__content_frontpage SET `ordering` = '$frontpage->ordering' WHERE `content_id` = '".$article->id."' " );
	                $synkdb->query();
            	}
            	   else
            	{
            		// insert
                    $synkdb->setQuery( "INSERT INTO #__content_frontpage SET `content_id` = '".$article->id."', `ordering` = '$frontpage->ordering' " );
                    $synkdb->query();
            	}
            } 
                else
            {
            	// delete on synkdb
                $synkdb->setQuery( "DELETE FROM #__content_frontpage WHERE `content_id` = '".$article->id."' " );
                $synkdb->query();	
            }
		}

		// if here, everything worked
		$success = true;
		
		// execute published plugins
		$args = $options;
		$args['success'] = $success;
		$dispatcher	   =& JDispatcher::getInstance();
		$dispatcher->trigger('onAfterRunSynchronizationSynk', array( $args ) );	
				
		return $success;
		
	}
	
	/**
	 * Wrapper to run a Synk
	 * @param object A valid synchronization object
	 * @param array See plugin for parameters
	 * @return boolean
	 */
	function &runSynchronization( $synk, &$options ) {
		// load the plugins
		//JPluginHelper::importPlugin( 'synk' );
		
		$success =& $options['success'];
		$success = false; 
		
		if (!isset($options['synktype'])) {
			return $success;
		}

		// check if synk can be run
		if ( !$canRun = &$this->canRunSynchronization( $synk ) ) {
			return $success;
		}
				
		$synktype = strtolower($options['synktype']);
					
		switch ($synktype) {
			case "content":			
				// run the synk
				if ($synk->use_custom == 1) {
					$runSynk = &$this->runCustomContentSynchronization( $synk, $options );
				} else {
					$runSynk = &$this->runContentSynchronization( $synk, $options );
				}
			  break;
			default:
				// should insert a custom event here
				return $success;
			  break;
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
