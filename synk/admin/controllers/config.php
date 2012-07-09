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

class SynkControllerConfig extends SynkController 
{
	/**
	 * constructor
	 */
	function __construct() 
	{
		parent::__construct();
		
		$this->set('suffix', 'config');
	}
	
	/**
	 * saves the config records
	 * @return void
	 */
	function save() 
	{
		$error = false;
		$errorMsg = "";
		$model 	= $this->getModel( $this->get('suffix') );
		$config = Synk::getInstance();
		$properties = $config->getProperties();
		 
		foreach (@$properties as $key => $value ) 
		{
			unset($row);
			$row = $model->getTable( 'config' );
			$newvalue = JRequest::getVar( $key );
			$value_exists = array_key_exists( $key, $_POST );
			if ( $value_exists && !empty($key) ) 
			{ 
				// proceed if newvalue present in request. prevents overwriting for non-existent values.
				$row->load( $key );
				$row->title = $key;
				$row->value = $newvalue;
				if ( !$row->save() ) 
				{
					$error = true;
					$errorMsg .= JText::_( "Could not store")." $key :: ".$row->getError()." - ";	
				}
			}
		}
		
		if ( !$error ) 
		{
			$this->messagetype 	= 'message';
			$this->message  	= JText::_( 'Saved' );
			
			$dispatcher = JDispatcher::getInstance();
			$dispatcher->trigger( 'onAfterSave'.$this->get('suffix'), array( $row ) );
		} 
			else 
		{
			$this->messagetype 	= 'notice';			
			$this->message 		= JText::_( 'Save Failed' )." - ".$errorMsg;
		}
		
    	$redirect = "index.php?option=com_synk";
    	$task = JRequest::getVar('task');
    	switch ($task)
    	{
    		default:
    			$redirect .= "&view=".$this->get('suffix');
    		  break;
    	}

    	$redirect = JRoute::_( $redirect, false );
		$this->setRedirect( $redirect, $this->message, $this->messagetype );
	}
	
}

?>