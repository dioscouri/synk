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

class SynkModelLogs extends SynkModelBase 
{
    protected function _buildQueryWhere(&$query)
    {
       	$filter     = $this->getState('filter');
       	$eventid    = $this->getState( 'filter_eventid' );
       	$synchronizationid = $this->getState( 'filter_synchronizationid' );
       	$databaseid = $this->getState( 'filter_databaseid' );
        $filter_id_from = $this->getState('filter_id_from');
        $filter_id_to   = $this->getState('filter_id_to');
        $success    = $this->getState( 'filter_success' );
        $filter_date_from   = $this->getState('filter_date_from');
        $filter_date_to     = $this->getState('filter_date_to');
        $filter_datetype    = $this->getState('filter_datetype');
        $filter_user    = $this->getState( 'filter_user' );
        $filter_article    = $this->getState( 'filter_article' );
        
       	if ($filter) 
       	{
			$key	= $this->_db->Quote('%'.$this->_db->getEscaped( trim( strtolower( $filter ) ) ).'%');

			$where = array();
			$where[] = 'LOWER(tbl.id) LIKE '.$key;
			$where[] = 'LOWER(tbl.title) LIKE '.$key;
			$where[] = 'LOWER(tbl.description) LIKE '.$key;
			$where[] = 'LOWER(tbl.userid) LIKE '.$key;
			$where[] = 'LOWER(tbl.contentid) LIKE '.$key;
			
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
        
       	if (strlen($eventid)) 
        {
        	$query->where('tbl.eventid = '.$eventid);
       	}
    	if (strlen($synchronizationid)) 
        {
        	$query->where('tbl.synchronizationid = '.$synchronizationid);
       	}
    	if (strlen($databaseid))
        {
        	$query->where('tbl.databaseid = '.$databaseid);
       	}
       	
        if (strlen($filter_date_from))
        {
            switch ($filter_datetype)
            {
                default:
                    $query->where("tbl.datetime >= '".$filter_date_from."'");
                  break;
            }
        }
        if (strlen($filter_date_to))
        {
            switch ($filter_datetype)
            {
                default:
                    $query->where("tbl.datetime <= '".$filter_date_to."'");
                  break;
            }
        }
        if (strlen($success)) 
        {
            $query->where('tbl.success = '.$success);
        }
        if (strlen($filter_user))
        {
            $key    = $this->_db->Quote('%'.$this->_db->getEscaped( trim( strtolower( $filter_user ) ) ).'%');
            $where = array();
            $where[] = 'LOWER(tbl.userid) LIKE '.$key;
            $where[] = 'LOWER(user.name) LIKE '.$key;
            $where[] = 'LOWER(user.email) LIKE '.$key;
            $where[] = 'LOWER(user.username) LIKE '.$key;
            $query->where('('.implode(' OR ', $where).')');
        }
        if (strlen($filter_article))
        {
            $key    = $this->_db->Quote('%'.$this->_db->getEscaped( trim( strtolower( $filter_article ) ) ).'%');
            $where = array();
            $where[] = 'LOWER(tbl.contentid) LIKE '.$key;
            $where[] = 'LOWER(c.title) LIKE '.$key;
            $query->where('('.implode(' OR ', $where).')');
        }
        
    }
    
	protected function _buildQueryJoins(&$query)
	{
		$query->join('LEFT', '#__synk_synchronizations as synk ON tbl.synchronizationid = synk.id');
		$query->join('LEFT', '#__synk_databases as d ON tbl.databaseid = d.id');
		$query->join('LEFT', '#__synk_events as e ON tbl.eventid = e.id');
		$query->join('LEFT', '#__users as user ON tbl.userid = user.id');
		$query->join('LEFT', '#__content as c ON tbl.contentid = c.id');
	}
	
	protected function _buildQueryFields(&$query)
	{
		$field = array();
		$field[] = " user.username as user_username ";
		$field[] = " user.name as user_name ";
		$field[] = " user.email as user_email ";
		$field[] = " c.title as content_title ";
		$field[] = " e.title as event_title ";
		$field[] = " d.title as database_title ";
		$field[] = " synk.title as synk_title ";
		
		$query->select( $this->getState( 'select', 'tbl.*' ) );		
		$query->select( $field );
	}
	
	public function getList()
	{
		$list = parent::getList(); 

		return $list;
	}
}
