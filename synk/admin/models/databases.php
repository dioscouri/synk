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

class SynkModelDatabases extends SynkModelBase 
{
    protected function _buildQueryWhere(&$query)
    {
       	$filter	    = $this->getState('filter');
       	$typeid     = $this->getState( 'filter_typeid' );
    	$synchronizationid = $this->getState( 'filter_synchronizationid' );
       	$enabled    = $this->getState( 'filter_enabled' );
       	
        $filter_id_from = $this->getState('filter_id_from');
        $filter_id_to   = $this->getState('filter_id_to');
        
        $filter_verified    = $this->getState( 'filter_verified' );
        $filter_host   = $this->getState('filter_host');
        $filter_title   = $this->getState('filter_title');
        $filter_db   = $this->getState('filter_db');

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
       	
        if (strlen($synchronizationid) && intval($synchronizationid) > 0) 
        {
        	$query->where('s.id = '.$synchronizationid);
       	}
    	if (strlen($enabled)) 
    	{
    		$query->where('tbl.published = '.$enabled);
       	}
        if (strlen($filter_verified)) 
        {
            $query->where('tbl.verified = '.$filter_verified);
        }
        if (strlen($filter_host))
        {
            $key    = $this->_db->Quote('%'.$this->_db->getEscaped( trim( strtolower( $filter_host ) ) ).'%');
            $where = array();
            $where[] = 'LOWER(tbl.host) LIKE '.$key;
            $query->where('('.implode(' OR ', $where).')');
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
            $where[] = 'LOWER(tbl.database) LIKE '.$key;
            $query->where('('.implode(' OR ', $where).')');
        }
    }
    
	protected function _buildQueryJoins(&$query)
	{
		$query->join('LEFT', '#__synk_synchronizations AS s ON tbl.id = s.databaseid');
	}
	
	protected function _buildQueryGroup(&$query)
	{
		$query->group("id");
	}
		
	public function getList()
	{
		$list = parent::getList();
		if(empty($list)) return array();
		
		foreach (@$list as $item)
		{
			$item->link = JRoute::_( 'index.php?option=com_synk&controller=databases&task=edit&id='. $item->id );
			$item->link_verify = JRoute::_( 'index.php?option=com_synk&controller=databases&task=verify&id='. $item->id );
				
			// get and set synchronizations list
			require_once( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_synk'.DS.'helpers'.DS.'databases.php' );
			$synks = "";
			$data = SynkHelperDatabases::getSynchronizations( $item->id, '0' );
			foreach (@$data as $d)
			{
				$synks .= JText::_( $d->title )."<br />";
			}
			$item->synchronizations_list = $synks;
				
			// verification images
			$item->img_u = $item->verified ? 'tick.png' : 'publish_x.png';
			$item->alt_u = $item->verified ? JText::_( 'Verified' ) : JText::_( 'Unverified' );
		}
		
		return $list;
	}
}
