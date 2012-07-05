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

JLoader::import( 'com_synk.tables._base', JPATH_ADMINISTRATOR.DS.'components' );

class TableTools extends SynkTable 
{
	/**
	 * Could this be abstracted into the base?
	 * 
	 * @param $db
	 * @return unknown_type
	 */
	function TableTools ( &$db ) 
	{
		
		$tbl_key 	= 'id';
		$tbl_suffix = 'plugins';
		$this->set( '_suffix', $tbl_suffix );
		$name 		= "synk";
		
		parent::__construct( "#__{$tbl_suffix}", $tbl_key, $db );	
	}
	
	function check()
	{		
		return true;
	}

}
