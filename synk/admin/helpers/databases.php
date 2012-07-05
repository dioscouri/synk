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

require_once( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_synk'.DS.'helpers'.DS.'_base.php' );

class SynkHelperDatabases extends SynkHelperBase
{
	/**
	 * Returns an array of objects - all events associated with a synchronization
	 * 
	 * @return 
	 * @param object $id
	 */
	function getSynchronizations( $id, $published='1' )
	{
		$database = &JFactory::getDBO();

		$where = array();
		$lists = array();

		$db_query = " AND s.databaseid = '".$id."' ";
		$published_query = "";
		if ($published) { $published_query = " AND s.published = '".$published."' "; } 

		$query = "
			SELECT 
				s.* 
			FROM 
				#__synk_synchronizations AS s 
			WHERE 
				1
				{$db_query}
				{$published_query} 
		";

		$database->setQuery( $query );
		$data = $database->loadObjectList();

		return $data;
	}
	
	/**
	 * 
	 * @return unknown_type
	 */
	function getConnection( $id )
	{
		// TODO This is actually where we should be getting the DB connection...
	}
}
