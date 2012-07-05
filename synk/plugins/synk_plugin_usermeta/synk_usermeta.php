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

class plgUserSynk_Usermeta extends JPlugin 
{

    /**
     * Constructor 
     *
     * For php4 compatability we must not use the __constructor as a constructor for plugins
     * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
     * This causes problems with cross-referencing necessary for the observer design pattern.
     *
     * @param object $subject The object to observe
     * @param   array  $config  An array that holds the plugin configuration
     * @since 1.5
     */
    function plgUserSynk_Usermeta(& $subject, $config) {
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
    function _isIncluded( $item )
    {
        $success = true;
        $exceptions_list = @preg_replace( '/\s/', '', $this->_getParameter( 'exceptions_list', '' ) );
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
        if (JFile::exists(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_synk'.DS.'helpers'.DS.'synk.php')) 
        {
            JTable::addIncludePath( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_synk'.DS.'tables' );
            require_once( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_synk'.DS.'helpers'.DS.'synk.php' );
            if (JFile::exists( JPATH_SITE.DS.'plugins'.DS.'system'.DS.'usermeta'.DS.'usermeta.php'))
            {
                JTable::addIncludePath( JPATH_SITE.DS.'plugins'.DS.'system'.DS.'usermeta' );
                $success = true;
            }    
        }
        
        return $success;
    }
    
    /**
     * Synk store user method
     *
     * Method is called before user data is stored in the database
     *
     * @param   array       holds the old user data
     * @param   boolean     true if a new user is stored
     */
    function onBeforeStoreUser($user, $isnew) 
    {
        $success = false;
        $database = &JFactory::getDBO();
        
        if (!$this->_isInstalled()) {
            return $success;
        }
        
        // Initialize variables
        $msg = new stdClass();
        $msg->error = '';
        $msg->message = '';
                
        // prep parameters for passing via single array
        $options = array();
        $options['user']    = $user;
        $options['isnew']   = $isnew;
        $options['success'] =& $success;
        $options['msg']     =& $msg;
        $options['synktype']= "user";
        $options['event']   = "onBeforeStoreUser";
        
        // grab all relevant synks
        $synchronizations = &SynkHelperSynchronizations::getSynchronizations( $options['event'] );

        // if not to be synchronized, continue
        if (!$this->_isIncluded( $user )) {
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
        
        // return to original db
        $table = & JTable::getInstance('user');     
        $table->setDBO( $database );
        
    }

    /**
     * Example store user method
     *
     * Method is called after user data is stored in the database
     *
     * @param   array       holds the new user data
     * @param   boolean     true if a new user is stored
     * @param   boolean     true if user was succesfully stored in the database
     * @param   string      message
     */
    function onAfterStoreUser($user, $isnew, $store_success, $store_message) {
        global $mainframe;
        $database = &JFactory::getDBO();
        if (!$this->_isInstalled()) {
            return false;
        }

        // Initialize variables
        $success = false;
        $msg = new stdClass();
        $msg->error = '';
        $msg->message = '';
                
        // prep parameters for passing via single array
        $options = array();
        $options['user']    = $user;
        $options['isnew']   = $isnew;
        $options['success'] = $success;
        $options['msg']     = $msg;
        $options['synktype']= "user";
        $options['event']   = "onAfterStoreUser";
        
        // grab all relevant synks
        $synchronizations = &SynkHelperSynchronizations::getSynchronizations( $options['event'] );

        // if not to be synchronized, continue
        if (!$this->_isIncluded( $user )) {
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
        
        // return to original db
        $table = & JTable::getInstance('user');     
        $table->setDBO( $database );
        
    }

    /**
     * Example store user method
     *
     * Method is called before user data is deleted from the database
     *
     * @param   array       holds the user data
     */
    function onBeforeDeleteUser($user) {
        global $mainframe;
        $database = &JFactory::getDBO();
        if (!$this->_isInstalled()) {
            return false;
        }

        // Initialize variables
        $success = false;
        $msg = new stdClass();
        $msg->error = '';
        $msg->message = '';
                
        // prep parameters for passing via single array
        $options = array();
        $options['user']    = $user;
        $options['isnew']   = false;
        $options['success'] = $success;
        $options['msg']     = $msg;
        $options['synktype']= "user";
        $options['event']   = "onBeforeDeleteUser";
        
        // grab all relevant synks
        $synchronizations = &SynkHelperSynchronizations::getSynchronizations( $options['event'] );

        // if not to be synchronized, continue
        if (!$this->_isIncluded( $user )) {
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
        
        // return to original db
        $table = & JTable::getInstance('user');     
        $table->setDBO( $database );
    }

    /**
     * Example store user method
     *
     * Method is called after user data is deleted from the database
     *
     * @param   array       holds the user data
     * @param   boolean     true if user was succesfully stored in the database
     * @param   string      message
     */
    function onAfterDeleteUser($user, $delete_success, $delete_message) {
        global $mainframe;
        $database = &JFactory::getDBO();
        if (!$this->_isInstalled()) {
            return false;
        }
        
        // Initialize variables
        $success = false;
        $msg = new stdClass();
        $msg->error = '';
        $msg->message = '';
        
        // prep parameters for passing via single array
        $options = array();
        $options['user']    = $user;
        $options['isnew']   = false;
        $options['success'] = $success;
        $options['msg']     = $msg;
        $options['synktype']= "user";
        $options['event']   = "onAfterDeleteUser";
        
        // grab all relevant synks
        $synchronizations = &SynkHelperSynchronizations::getSynchronizations( $options['event'] );

        // if not to be synchronized, continue
        if (!$this->_isIncluded( $user )) {
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
    
        }
        
        // return to original db
        $table = & JTable::getInstance('user');     
        $table->setDBO( $database );
    }

    /**
     * This method should handle any login logic and report back to the subject
     *
     * @access  public
     * @param   array   holds the user data
     * @param   array    extra options
     * @return  boolean True on success
     * @since   1.5
     */
    function onLoginUser($user, $options)
    {
        $success = false;
        
        $database = &JFactory::getDBO();
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
        $options['user']    = $user;
        $options['isnew']   = false;
        $options['success'] =& $success;
        $options['msg']     =& $msg;
        $options['synktype']= "user";
        $options['event']   = "onLoginUser";
        
        // grab all relevant synks
        $synchronizations = &SynkHelperSynchronizations::getSynchronizations( $options['event'] );

        // if not to be synchronized, continue
        if (!$this->_isIncluded( $user )) {
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
        
        // return to original db
        $table = & JTable::getInstance('user');     
        $table->setDBO( $database );
        
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
        
        $database = &JFactory::getDBO();
        if (!$this->_isInstalled()) {
            return false;
        }

        // Initialize variables
        $msg = new stdClass();
        $msg->error = '';
        $msg->message = '';
        
        // prep parameters for passing via single array
        $options = array();
        $options['user']    = $user;
        $options['isnew']   = false;
        $options['success'] = $success;
        $options['msg']     = $msg;
        $options['synktype']= "user";
        $options['event']   = "onLogoutUser";
        
        // grab all relevant synks
        $synchronizations = &SynkHelperSynchronizations::getSynchronizations( $options['event'] );

        // if not to be synchronized, continue
        if (!$this->_isIncluded( $user )) {
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
        
        // return to original db
        $table = & JTable::getInstance('user');     
        $table->setDBO( $database );
        
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
        $user       = $options['user'];
        $isnew      = $options['isnew'];
        $success    = &$options['success'];
        $msg        = &$options['msg'];
        $event      = $options['event'];
        
        $success = false;
                
        // if no user['id']
        if ( !isset($user['id']) )  return $success;
        
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
        $args = array();
        $args = $options;
        $args['synk'] = $synk;
        $dispatcher    =& JDispatcher::getInstance();
        $dispatcher->trigger('onBeforeRunSynchronizationSynk', array( $options, $args ) );  
        
        $query = "DELETE FROM `#__usermeta` WHERE `user_FK` = '".$user['id']."';";
        $synkdb->setQuery($query);
        if (!$synkdb->query())
        {
            $msg->message .= ' - '.JText::_( 'Target Delete Failed' ).': '.$synkdb->getErrorMsg();
            return $success;
        }
        
        $success = true;
        
        // execute published plugins
        $args['success'] = $success;
        $dispatcher    =& JDispatcher::getInstance();
        $dispatcher->trigger('onAfterRunSynchronizationSynk', array( $options, $args ) );

        return $success;
        
    }

    /**
     * Executes Synk
     * @param object A valid synchronization object
     * @param array See plugin for parameters
     * @return boolean
     */
    function _executeSynchronization( $synk, &$options )
    {
        // init variables
        $user       = $options['user'];
        $isnew      = $options['isnew'];
        $success    = &$options['success'];
        $msg        = &$options['msg'];
        $event      = $options['event'];
        
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
        
        // execute published plugins
        $args = array();
        $args = $options;
        $args['synk'] = $synk;
        $dispatcher    =& JDispatcher::getInstance();
        $dispatcher->trigger('onBeforeRunSynchronizationSynk', array($options, $args ) );
        
        // load the table
        $db = JFactory::getDBO();
        $query_usermeta = "SELECT * FROM #__usermeta WHERE `user_FK` = '".$user['id']."';";
        $db->setQuery( $query_usermeta );
        $localdata = $db->loadObject();
        $synkdb->setQuery( $query_usermeta );
        $targetdata = $synkdb->loadObject();
        
        $getfields = $db->getTableFields('#__usermeta');
        $fields = @$getfields['#__usermeta'];
        $pairings = array();
        foreach ($fields as $field => $type)
        {
        	if (!empty($targetdata->user_FK) && $field == 'user_FK') continue;
        	
            $value = $localdata->$field;
            $pairings[] = "`$field`='".$synkdb->getEscaped($value)."'";
        }
        
        if (empty($targetdata->user_FK))
        {
            $action = "INSERT INTO";
            $where = "";
        
        } else {
        	$action = "UPDATE";
        	$where = " WHERE `user_FK`={$localdata->user_FK}";
        }
        $query = $action." `#__usermeta` SET ".implode( ", ", $pairings ).$where;
        
        $synkdb->setQuery($query);
        if (!$synkdb->query())
        {
            $msg->message .= ' - '.JText::_( "Target $action Failed" ).': '.$synkdb->getErrorMsg();
            return $success;
        }
        
        // if here, everything worked
        $success = true;
        
        // execute published plugins
        $args['success'] = $success;
        $dispatcher    =& JDispatcher::getInstance();
        $dispatcher->trigger('onAfterRunSynchronizationSynk', array($options, $args ) );
                
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
                
        $success = false; 
        
        if (!isset($options['synktype'])) {
            return $success;
        }
        
        // check if synk can be run
        if (!($canRun = &$this->canRunSynchronization( $synk ))) {
            return $success;
        }
                
        $runSynk = &$this->_executeSynchronization( $synk, $options );

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
