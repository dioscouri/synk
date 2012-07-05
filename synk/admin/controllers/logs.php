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
defined( '_JEXEC' ) or die( 'Restricted access' );

class SynkControllerLogs extends SynkController 
{
	/**
	 * constructor
	 */
	function __construct() 
	{
		parent::__construct();
		
		$this->set('suffix', 'logs');
	}
	
	/**
	 * Controllers interact with the Request and set the model's state
	 * 
	 * @see synk/admin/SynkController#_setModelState()
	 */
    function _setModelState()
    {
    	$state = parent::_setModelState();   	
		$app = JFactory::getApplication();
		$model = $this->getModel( $this->get('suffix') );
    	$ns = $this->getNamespace();

    	$state['filter_eventid'] 	= $app->getUserStateFromRequest($ns.'eventid', 'filter_eventid', '', '');
      	$state['filter_synchronizationid'] 	= $app->getUserStateFromRequest($ns.'synchronizationid', 'filter_synchronizationid', '', '');
      	$state['filter_databaseid'] 	= $app->getUserStateFromRequest($ns.'databaseid', 'filter_databaseid', '', '');
        $state['filter_success']    = $app->getUserStateFromRequest($ns.'success', 'filter_success', '', '');
        $state['filter_id_from']    = $app->getUserStateFromRequest($ns.'id_from', 'filter_id_from', '', '');
        $state['filter_id_to']      = $app->getUserStateFromRequest($ns.'id_to', 'filter_id_to', '', '');
        $state['filter_date_from']  = $app->getUserStateFromRequest($ns.'date_from', 'filter_date_from', '', '');
        $state['filter_date_to']        = $app->getUserStateFromRequest($ns.'date_to', 'filter_date_to', '', '');
        $state['filter_datetype']   = $app->getUserStateFromRequest($ns.'datetype', 'filter_datetype', '', '');
		$state['filter_user']    = $app->getUserStateFromRequest($ns.'user', 'filter_user', '', '');
		$state['filter_article']    = $app->getUserStateFromRequest($ns.'article', 'filter_article', '', '');
		
    	foreach (@$state as $key=>$value)
		{
			$model->setState( $key, $value );	
		}
  		return $state;
    }
	
}

?>
