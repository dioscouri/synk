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

class SynkControllerDatabases extends SynkController 
{
	/**
	 * constructor
	 */
	function __construct() 
	{
		parent::__construct();
		
		$this->set('suffix', 'databases');
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

        $state['filter_id_from']    = $app->getUserStateFromRequest($ns.'id_from', 'filter_id_from', '', '');
        $state['filter_id_to']      = $app->getUserStateFromRequest($ns.'id_to', 'filter_id_to', '', '');
      	$state['filter_synchronizationid'] 	= $app->getUserStateFromRequest($ns.'synchronizationid', 'filter_synchronizationid', '', '');
      	$state['filter_enabled'] 	= $app->getUserStateFromRequest($ns.'enabled', 'filter_enabled', '', '');
      	$state['filter_verified']     = $app->getUserStateFromRequest($ns.'verified', 'filter_verified', '', '');
      	$state['filter_host']     = $app->getUserStateFromRequest($ns.'host', 'filter_host', '', '');
      	$state['filter_title']     = $app->getUserStateFromRequest($ns.'title', 'filter_title', '', '');
      	$state['filter_db']     = $app->getUserStateFromRequest($ns.'db', 'filter_db', '', '');

    	foreach (@$state as $key=>$value)
		{
			$model->setState( $key, $value );	
		}
  		return $state;
    }
	
	/**
	 * Method to connect to a database
	 *
	 */
	function connect( $id='' ) 
	{
		if (empty($id))
		{
			$id = $this->getModel( $this->get('suffix') )->getId();
		}
		
		$row = $this->getModel( $this->get('suffix') )->getTable();
	    $row->load( $id );

		if (empty($row->id)) 
		{
			$this->setError( JText::_( 'Invalid ID' ) );
			return false;
		}
		
		// verify connection
		$option['driver']   = $row->driver;            // Database driver name
		$option['host']     = $row->host;    		// Database host name
		if($row->driver != 'xmlrpc')	
			if ($row->port != '3306') { $option['host'] .= ":".$row->port; } // alternative ports
		$option['user']     = $row->user;         // User for database authentication
		$option['password'] = $row->password;    // Password for database authentication
		$option['database'] = $row->database;        // Database name
		$option['prefix']   = $row->prefix;               // Database prefix (may be empty)
		
		$database = JDatabase::getInstance( $option );
				
		if ( method_exists( $database, 'test' ) ) 
		{
			// success
			return $database;
		} 
			else 
		{
			$this->setError( JText::_( 'Connection Failed' ) );		
			return false;
		}
	}
	
	/**
	 * verify (and update verified)
	 * @return void
	 */
	function verify()
	{
		$error = false;
		$this->messagetype	= '';
		$this->message 		= '';
		$redirect = 'index.php?option=com_synk&view='.$this->get('suffix');
		$redirect = JRoute::_( $redirect, false );
		
		$model 	= $this->getModel( $this->get('suffix') );
		
	    $row = $model->getTable();
	    $row->load( $model->getId() );

		if (empty($row->id)) 
		{
			$this->messagetype	= 'notice';
			$this->message = JText::_( 'Invalid ID' );
			$this->setRedirect( $redirect, $this->message, $this->messagetype );
			return;		
		}
		
		// verify connection
		if ( !$db = $this->connect( $row->id ) ) 
		{
			$this->messagetype	= 'notice';
			$this->message = JText::_( 'Connection Failed' );
			
			$row->verified = 0;
		} 
			else
		{
			// success connecting to db, now test it
			if ($db->test()) 
			{
				$row->verified = 1;			
			} 
				else 
			{
				$this->messagetype	= 'notice';
				$this->message = JText::_( 'Testing DB Failed::'.$db->getError() );
				
				$row->verified = 0;
			}			
		}		
	
		if (!$row->save()) 
		{
			$this->message .= $row->getError();
			$this->setRedirect( $redirect, $this->message, $this->messagetype );
			return false;
		}
		
		$this->setRedirect( $redirect, $this->message, $this->messagetype );
	}
	
}

?>
