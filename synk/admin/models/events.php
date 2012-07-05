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

class SynkModelEvents extends SynkModelBase 
{
    protected function _buildQueryWhere(&$query)
    {
       	$filter	= $this->getState('filter');
       	$typeid = $this->getState( 'filter_typeid' );
    	$synchronizationid = $this->getState( 'filter_synchronizationid' );
       	$enabled = $this->getState( 'filter_enabled' );
        $filter_id_from = $this->getState('filter_id_from');
        $filter_id_to   = $this->getState('filter_id_to');
       	$filter_title = $this->getState('filter_title');

       	if ($filter) 
       	{
			$key	= $this->_db->Quote('%'.$this->_db->getEscaped( trim( strtolower( $filter ) ) ).'%');

			$where = array();
			$where[] = 'LOWER(tbl.id) LIKE '.$key;
			$where[] = 'LOWER(tbl.title) LIKE '.$key;
			$where[] = 'LOWER(tbl.description) LIKE '.$key;
			
			$query->where('('.implode(' OR ', $where).')');
       	}
        if (strlen($filter_id_from))
        {
            if (strlen($filter_id_to))
            {
                $query->where('tbl.id >= '.(int) $filter_id_from);
            }
                else
            {
                $query->where('tbl.id = '.(int) $filter_id_from);
            }
        }
        if (strlen($filter_id_to))
        {
            $query->where('tbl.id <= '.(int) $filter_id_to);
        }
        
       	if (strlen($typeid))
       	{
       		if ($typeid == '1' || $typeid == '0') 
       		{
       			$query->where('tbl.type = '.$typeid);
       		} 
       			elseif ( intval($typeid) != 0 && intval($typeid) != 1 ) 
       		{
       			$query->where("tbl.type NOT IN ( '0', '1' )");
       		}
       	}
    	if (strlen($synchronizationid) && intval($synchronizationid) > 0) 
        {
        	$query->where('s2e.synchronizationid = '.$synchronizationid);
       	}
    	if (strlen($enabled)) 
    	{
    		$query->where('tbl.published = '.$enabled);
       	}
        if (strlen($filter_title))
        {
            $key    = $this->_db->Quote('%'.$this->_db->getEscaped( trim( strtolower( $filter_title ) ) ).'%');
            $where = array();
            $where[] = 'LOWER(tbl.title) LIKE '.$key;
            $query->where('('.implode(' OR ', $where).')');
        }
       	
    }
    
	protected function _buildQueryJoins(&$query)
	{
		$query->join('LEFT', '#__synk_s2e AS s2e ON tbl.id = s2e.eventid');
	}
	
	protected function _buildQueryFields(&$query)
	{
		$query->select("tbl.*");		
		$query->select("`s2e`.*");
	}
	
	protected function _buildQueryGroup(&$query)
	{
		$query->group("id");
	}
	
	public function getList()
	{
        require_once( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_synk'.DS.'helpers'.DS.'events.php' );
        
		$list = parent::getList();
		if(empty($list)) return array();
		
		foreach (@$list as $item)
		{
			$item->link = JRoute::_( 'index.php?option=com_synk&controller=events&task=edit&id='. $item->id );
				
			// get and set synchronizations list
			$synks = "";
			$data = SynkHelperEvents::getSynchronizations( $item->id, '0' );
			foreach (@$data as $d)
			{
				$synks .= JText::_( $d->title )."<br />";
			}
			$item->synchronizations_list = $synks;
				
			// get type title
			$item->type_title = SynkHelperEvents::getType( $item->type );
		}

		return $list;
	}
}
