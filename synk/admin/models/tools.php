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

JLoader::import( 'com_synk.models._base', JPATH_ADMINISTRATOR.DS.'components' );

class SynkModelTools extends SynkModelBase 
{	
    protected function _buildQueryWhere(&$query)
    {
       	$filter     = $this->getState('filter');

       	if ($filter) 
       	{
			$key	= $this->_db->Quote('%'.$this->_db->getEscaped( trim( strtolower( $filter ) ) ).'%');

			$where = array();
			$where[] = 'LOWER(tbl.id) LIKE '.$key;
			$where[] = 'LOWER(tbl.name) LIKE '.$key;
			$where[] = 'LOWER(tbl.element) LIKE '.$key;
			
			$query->where('('.implode(' OR ', $where).')');
       	}
       	
		$query->where("LOWER(tbl.folder) = 'synk'");
    }
    	
	public function getList()
	{
		$list = parent::getList();
		if(empty($list)) return array();
		
		foreach(@$list as $item)
		{
			$item->link = 'index.php?option=com_synk&controller=tools&view=tools&task=edit&id='.$item->id;
		}
		return $list;
	}
}
