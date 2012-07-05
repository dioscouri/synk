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

class plgSystemSynk extends JPlugin 
{

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
	function plgSystemSynk(& $subject, $config) {
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
            JTable::addIncludePath( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_synk'.DS.'tables'.DS );
            require_once( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_synk'.DS.'helpers'.DS.'synchronizations.php' );
            $success = true;
        }
        
        return $success;
    }
	
	/**
	*
	*/
	function onAfterInitialise() {
		// Do nothing for now
		return;
		
		$return = false;
		$database = JFactory::getDBO();
		
		if (!$this->_isInstalled()) {
			return $return;
		}
		
		$thisUser = &JFactory::getUser();
		$user = array();
		$user['id'] = $thisUser->id;
		
		// prep parameters for passing via single array
		$options = array();
		$options['user']	= $user;
		$options['isnew']	= false;
		$options['success']	= false;
		$options['msg']		= false;
		$options['synktype']= "system";
		
		// grab all relevant synks
		$synchronizationsHourly 	= &SynkHelperSynchronizations::getSynchronizations( "HOURLY" );
		$synchronizationsDaily 		= &SynkHelperSynchronizations::getSynchronizations( "DAILY" );
		$synchronizationsWeekly 	= &SynkHelperSynchronizations::getSynchronizations( "WEEKLY" );
		$synchronizationsMonthly 	= &SynkHelperSynchronizations::getSynchronizations( "MONTHLY" );
		
		$synchronizations = array_merge( $synchronizationsHourly, $synchronizationsDaily);
		$synchronizations = array_merge( $synchronizations, $synchronizationsWeekly);
		$synchronizations = array_merge( $synchronizations, $synchronizationsMonthly);		
		
		// loop thru synks
		for ($i=0; $i<count($synchronizations); $i++) {
			$synk = $synchronizations[$i];
			
			if (isset($synk->event_title)) {
				$options['event'] = strtoupper( $synk->event_title );
							
				/**
				*
				* RUN THE SYNK
				*
				*/
				
				// Do nothing for now
				// $runSynk = &SynkHelperSynchronizations::runSynchronization( $synk, $options );
				
			}
	
		}
		
		// return to original db
		$table = & JTable::getInstance('user');		
		$table->setDBO( $database );
	}
}
