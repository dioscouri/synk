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

Synk::load( 'SynkModelBase', 'models.base' );

class SynkModelSynchronizations extends SynkModelBase 
{
    protected function _buildQueryWhere(&$query)
    {
       	$filter     = $this->getState('filter');
        $enabled    = $this->getState( 'filter_enabled' );
        $filter_id_from = $this->getState('filter_id_from');
        $filter_id_to   = $this->getState('filter_id_to');
       	
       	$databaseid = $this->getState( 'filter_databaseid' );
    	$eventid = $this->getState( 'filter_eventid' );
        $filter_title = $this->getState('filter_title');
        $filter_db  = $this->getState('filter_db');
       
       	if ($filter) 
       	{
       		$key	= $this->_db->Quote('%'.$this->_db->getEscaped( trim( strtolower( $filter ) ) ).'%');
       		$where = array();
       		$where[] = "LOWER(`tbl`.`id`) LIKE ".$key;
			$where[] = "LOWER(`tbl`.`title`) LIKE ".$key;
			$where[] = "LOWER(`tbl`.`description`) LIKE ".$key;
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
               	
    	if (strlen($databaseid)) 
        {
        	if(intval($databaseid) > 0){
        		$query->where("`tbl`.`databaseid` = '" . trim( strtolower( $databaseid ) ) . "'");
        	} elseif (intval($databaseid) == '-1') {
				$query->where("`tbl`.`databaseid` = '0'");
			}
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
        if (strlen($filter_db))
        {
            $key    = $this->_db->Quote('%'.$this->_db->getEscaped( trim( strtolower( $filter_db ) ) ).'%');
            $where = array();
            $where[] = 'LOWER(d.title) LIKE '.$key;
            $query->where('('.implode(' OR ', $where).')');
        }
        if (strlen($eventid))
        {
            $query->where('s2e.eventid = '.(int) $eventid);
        }
    }
    
	protected function _buildQueryJoins(&$query)
	{
		$query->join('LEFT', '`#__synk_databases` AS `d` ON `d`.`id` = `tbl`.`databaseid`');
		$query->join('LEFT', '`#__synk_s2e` AS `s2e` ON `tbl`.`id` = `s2e`.`synchronizationid`');
	}
	
	protected function _buildQueryFields(&$query)
	{
		$query->select("tbl.*");
		$query->select("`d`.`title` AS `database_title`");
	}
	
    protected function _buildQueryGroup(&$query)
    {
        $query->group('tbl.id');
    }
    
	public function getList($refresh = false)
	{ 
		$list = parent::getList($refresh = false);
		if(empty($list)) { return array(); }
		
		foreach (@$list as $item)
		{
			$events_list = "";

			// get events
			$data = SynkHelperSynchronizations::getEvents( $item->id );
			if($data){
				foreach (@$data as $d)
				{
					$events_list .= JText::_( $d->title );
					$events_list .= '<br>';
				}
			}
			$item->events_list = $events_list;

			// Set links
			$item->link = JRoute::_( 'index.php?option=com_synk&controller=synchronizations&task=edit&id='. $item->id );
			$item->link_selectevents = JRoute::_( 'index.php?option=com_synk&controller=synchronizations&task=selectevents&tmpl=component&id='. $item->id );
		}
		
		return $list;
	}
}
