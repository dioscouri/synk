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

Synk::load( 'SynkHelperBase', 'helpers.base' );

class SynkHelperEvents extends SynkHelperBase
{
	/**
	 * 
	 * @return 
	 */
	public static function getType( $id ) 
	{
		switch( $id )
		{
			case "1": 
				$data = JText::_('Joomla Event'); 
			  break;
			case "0":
				$data = JText::_('Custom Event'); 
			  break;
			default:
			  	$data = JText::_('Other');
			  break;
		}
					
		return $data;
	}
	
	/**
	 * 
	 * @return 
	 */
	public static function getTypes() 
	{
		static $items;
		
		if (empty($items)) 
		{
			$items = array();
			$items[] = JHTML::_('select.option', '0', JText::_('Custom Event') );
			$items[] = JHTML::_('select.option', '1', JText::_('Joomla Event') );
			$items[] = JHTML::_('select.option', '-1', JText::_('Other') );
		}
		
		return $items;
	}
	
	/**
	 * Returns an array of objects - all synks associated with an event
	 * 
	 * @return 
	 * @param object $id
	 */
	public static function getSynchronizations( $id, $published='1' )
	{
		$database = JFactory::getDBO();

		$where = array();
		$lists = array();

		$event_query = " AND s2e.eventid = '".$id."' ";
		$published_query = "";
		if ($published) { $published_query = " AND s.published = '".$published."' "; } 

		$query = "
			SELECT 
				s.* 
			FROM 
				#__synk_synchronizations AS s
			LEFT JOIN 
				#__synk_s2e AS s2e ON s2e.synchronizationid = s.id 
			WHERE 
				1
				{$event_query}
				{$published_query} 
		";

		$database->setQuery( $query );
		$data = $database->loadObjectList();

		return $data;
	}
}
