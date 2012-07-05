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

JLoader::import( 'com_synk.tables._basexref', JPATH_ADMINISTRATOR.DS.'components' );

class TableSynchronizationEvents extends SynkTableXref 
{
	/** 
	 * @param $db
	 * @return unknown_type
	 */
	function TableSynchronizationEvents ( &$db ) 
	{		
		$tbl_key 	= 'synchronizationid';
		$tbl_key2	= 'eventid';
		$tbl_suffix = 's2e';
		$name 		= 'synk';
		
		$this->set( '_tbl_key', $tbl_key );
		$this->set( '_tbl_key2', $tbl_key2 );
		$this->set( '_suffix', $tbl_suffix );
		
		parent::__construct( "#__{$name}_{$tbl_suffix}", $tbl_key, $db );	
	}
	
	function check()
	{
		if (empty($this->synchronizationid))
		{
			$this->setError( JText::_( "Synchronization Required" ) );
			return false;
		}
		if (empty($this->eventid))
		{
			$this->setError( JText::_( "Event Required" ) );
			return false;
		}
		
		return true;
	}
}
