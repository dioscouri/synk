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

JLoader::import( 'com_synk.tables._base', JPATH_ADMINISTRATOR.DS.'components' );

class TableEvents extends SynkTable {
/**
	 * 
	 * 
	 * @param $db
	 * @return unknown_type
	 */
	function TableEvents ( &$db ) 
	{
		
		$tbl_key 	= 'id';
		$tbl_suffix = 'events';
		$this->set( '_suffix', $tbl_suffix );
		$name 		= 'synk';
		
		parent::__construct( "#__{$name}_{$tbl_suffix}", $tbl_key, $db );	
	}
	
	function check()
	{		
		$db			= &JFactory::getDBO();
		$nullDate	= $db->getNullDate();
		if ($this->datetime == $nullDate)
		{
			$date = JFactory::getDate();
			$this->datetime = $date->toMysql();
		}	
		return true;
	}

}
