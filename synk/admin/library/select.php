<?php
/**
* @version		0.1.0
* @package		Synk
* @copyright	Copyright (C) 2009 DT Design Inc. All rights reserved.
* @license		GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
* @link 		http://www.dioscouri.com
*/

class SynkSelect extends DSCSelect
{
	/**
	 * 
	 * @param $selected
	 * @param $name
	 * @param $attribs
	 * @param $idtag
	 * @param $allowAny
	 * @return unknown_type
	 */
	public static function typetype($selected, $name = 'filter_type', $attribs = array('class' => 'inputbox', 'size' => '1'), $idtag = null, $allowAny = false, $allowNone = false )
 	{
		// Build list
        $list = array();
		if($allowAny) {
			$list[] =  self::option('', "- ".JText::_( 'Select Type' )." -", 'id', 'title' );
		}

		require_once( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_synk'.DS.'helpers'.DS.'events.php' );
        
		$items = SynkHelperEvents::getTypes();
		
		if(!empty($items)){
        	foreach (@$items as $item)
        	{
        		$list[] =  self::option( $item->value, JText::_($item->text), 'id', 'title' );
        	}
		}

		return self::genericlist($list, $name, $attribs, 'id', 'title', $selected, $idtag );
 	}
 	
	/**
	 * 
	 * @param $selected
	 * @param $name
	 * @param $attribs
	 * @param $idtag
	 * @param $allowAny
	 * @return unknown_type
	 */
	public static function synchronization($selected, $name = 'filter_synchronizationid', $attribs = array('class' => 'inputbox', 'size' => '1'), $idtag = null, $allowAny = false, $allowNone = false, $title = 'Select Synchronization', $title_none = 'No Synchronization' )
 	{
		// Build list
        $list = array();
 		if($allowAny) {
			$list[] =  self::option('', "- ".JText::_( $title )." -", 'id', 'title' );
		}
 		if($allowNone) {
			$list[] =  self::option('0', "- ".JText::_( $title_none )." -", 'id', 'title' );
		}

		JModel::addIncludePath( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_synk'.DS.'models' );
		$model = JModel::getInstance( 'Synchronizations', 'SynkModel' );
		$model->setState( 'order', 'title' );
		$model->setState( 'direction', 'ASC' );
		$items = $model->getAll();
		
		if(!empty($items)){
        	foreach (@$items as $item)
        	{
        		$list[] =  self::option( $item->id, JText::_($item->title), 'id', 'title' );
        	}
		}
		return self::genericlist($list, $name, $attribs, 'id', 'title', $selected, $idtag );
 	}
	
	/**
	 * 
	 * @param $selected
	 * @param $name
	 * @param $attribs
	 * @param $idtag
	 * @param $allowAny
	 * @return unknown_type
	 */
	public static function database($selected, $name = 'filter_databaseid', $attribs = array('class' => 'inputbox', 'size' => '1'), $idtag = null, $allowAny = false, $allowNone = false, $title = 'Select Database', $title_none = 'No Database' )
 	{
		// Build list
        $list = array();
 		if($allowAny) {
			$list[] =  self::option('', "- ".JText::_( $title )." -", 'id', 'title' );
		}
 		if($allowNone) {
			$list[] =  self::option('0', "- ".JText::_( $title_none )." -", 'id', 'title' );
		}

		JModel::addIncludePath( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_synk'.DS.'models' );
		$model = JModel::getInstance( 'Databases', 'SynkModel' );
		$model->setState( 'order', 'title' );
		$model->setState( 'direction', 'ASC' );
		$items = $model->getAll();
		
		if(!empty($items)){
        	foreach (@$items as $item)
        	{
        		$list[] =  self::option( $item->id, JText::_($item->title), 'id', 'title' );
        	}
		}
		return self::genericlist($list, $name, $attribs, 'id', 'title', $selected, $idtag );
 	}
 	
 		/**
	 * 
	 * @param $selected
	 * @param $name
	 * @param $attribs
	 * @param $idtag
	 * @param $allowAny
	 * @return unknown_type
	 */
	public static function event($selected, $name = 'filter_eventid', $attribs = array('class' => 'inputbox', 'size' => '1'), $idtag = null, $allowAny = false, $allowNone = false, $title = 'Select Event', $title_none = 'No Event' )
 	{
		// Build list
        $list = array();
 		if($allowAny) {
			$list[] =  self::option('', "- ".JText::_( $title )." -", 'id', 'title' );
		}
 		if($allowNone) {
			$list[] =  self::option('0', "- ".JText::_( $title_none )." -", 'id', 'title' );
		}

		JModel::addIncludePath( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_synk'.DS.'models' );
		$model = JModel::getInstance( 'Events', 'SynkModel' );
		$model->setState( 'order', 'title' );
		$model->setState( 'direction', 'ASC' );
		$items = $model->getAll();
		
		if(!empty($items)){
        	foreach (@$items as $item)
        	{
        		$list[] =  self::option( $item->id, JText::_($item->title), 'id', 'title' );
        	}
		}
		return self::genericlist($list, $name, $attribs, 'id', 'title', $selected, $idtag );
 	}
 	
    /**
    * Generates weekday list
    *
    * @param string The value of the HTML name attribute
    * @param string Additional HTML attributes for the <select> tag
    * @param mixed The key that is selected
    * @returns string HTML for the radio list
    */
    public static function weekday( $selected, $name = 'filter_weekday', $attribs = array('class' => 'inputbox', 'size' => '1'), $idtag = null, $allowAny = false, $title = 'Select Day' )
    {
        $list = array();
        if($allowAny) {
            $list[] =  self::option('', "- ".JText::_( $title )." -" );
        }

        $list[] = JHTML::_('select.option', 0, JText::_( 'Monday' ) );
        $list[] = JHTML::_('select.option', 1, JText::_( 'Tuesday' ) );
        $list[] = JHTML::_('select.option', 2, JText::_( 'Wednesday' ) );
        $list[] = JHTML::_('select.option', 3, JText::_( 'Thursday' ) );
        $list[] = JHTML::_('select.option', 4, JText::_( 'Friday' ) );
        $list[] = JHTML::_('select.option', 5, JText::_( 'Saturday' ) );
        $list[] = JHTML::_('select.option', 6, JText::_( 'Sunday' ) );

        return self::genericlist($list, $name, $attribs, 'value', 'text', $selected, $idtag );
    }
}