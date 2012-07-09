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

Synk::load( 'SynkViewBase', 'views.base' );

class SynkViewDashboard extends SynkViewBase  
{
    /*    
	function display($tpl=null) 
	{
		require_once( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_synk'.DS.'helpers'.DS.'_base.php' );
		JLoader::import( 'com_synk.library.grid', JPATH_ADMINISTRATOR.DS.'components' );
		JLoader::import( 'com_synk.library.select', JPATH_ADMINISTRATOR.DS.'components' );
        $model = $this->getModel();
        $state = $model->getState();
        $state->stats_interval = JRequest::getVar('stats_interval', 'last_thirty', 'POST');
        
        // set the model state
            $this->assign( 'state', $state );
            
		$stats_interval = $state->stats_interval;
		
        if (empty($this->hidestats))
        {
        	switch($stats_interval){
        		case 'today':
        			$this->stats_today();
        			break;
        		case 'yesterday':
        			$this->stats_yesterday();
        			break;
                case 'last_thirty':
                    $this->stats_lastThirty();
                    break;
        		case 'ytd':
        			$this->stats_this_year();
        			break;
        	}
        }
        
        // form
		$validate = JUtility::getToken();
		$form = array();
		$controller = strtolower( $this->get( '_controller', JRequest::getVar('controller', JRequest::getVar('view') ) ) );
		$view = strtolower( $this->get( '_view', JRequest::getVar('view') ) );
		$action = $this->get( '_action', "index.php?option=com_synk&controller={$controller}&view={$view}" );
		$form['action'] = $action;
		$form['validate'] = "<input type='hidden' name='{$validate}' value='1' />";
		$this->assign( 'form', $form );
            
		parent::display($tpl);
    }
    
    function stats_lastThirty()
    {
        $database = JFactory::getDBO();
        $base = new SynkHelperBase();
        $today = $base->getToday();
        $end_datetime = $today;
            $query = " SELECT DATE_SUB('".$today."', INTERVAL 1 MONTH) ";
            $database->setQuery( $query );
        $start_datetime = $database->loadResult();

        $runningtotal = 0;
        $runningsum = 0;
        $data = new stdClass();
        $num = 0;
        $result = array();
        $curdate = $start_datetime;
        $enddate = $end_datetime;
        while ($curdate <= $enddate)
        {
            // set working variables
                $variables = SynkHelperBase::setDateVariables( $curdate, $enddate, 'daily' );
                $thisdate = $variables->thisdate;
                $nextdate = $variables->nextdate;

            // grab all records
                $model = JModel::getInstance( 'Logs', 'SynkModel' );
                $model->setState( 'filter_date_from', $thisdate );
                $model->setState( 'filter_date_to', $nextdate );
                $rows = $model->getList();
                $total = count( $rows );
                //$total = $model->getTotal();

            //store the value in an array
            $result[$num]['rows']       = $rows;
            $result[$num]['datedata']   = getdate( strtotime($thisdate) );
            $result[$num]['countdata']  = $total;
            $runningtotal               = $runningtotal + $total;

            // increase curdate to the next value
            $curdate = $nextdate;
            $num++;

        } // end of the while loop

        $data->rows         = $result;
        $data->total        = $runningtotal;

        $this->getChartBarDaily( $data, 'Last Thirty Days', 'stats_left' );
        // $this->assign( 'lastThirtyData', $data );
        
        // Events statistics
		$database =& JFactory::getDBO();
		$query = "SELECT `#__synk_events`.`title`,`#__synk_logs`.`eventid`, COUNT(*) AS `cnt`".
				" FROM `#__synk_logs` LEFT JOIN `#__synk_events` ON `#__synk_logs`.`eventid`=`#__synk_events`.`id`".
				" WHERE `datetime`>=DATE_SUB(UTC_DATE(), INTERVAL 1 MONTH)".
				" GROUP BY `#__synk_logs`.`eventid`".
				" ORDER BY `#__synk_logs`.`eventid`";
		$database->setQuery($query);
		$logged_events = $database->loadObjectList();
		$this->assign( 'logged_events', $logged_events );
    }
    
    function stats_today()
    {
    	$database = JFactory::getDBO();
        $base = new SynkHelperBase();
        $today = $base->getToday();

        $runningtotal = 0;
        $runningsum = 0;
        $data = new stdClass();
        $num = 0;
        $result = array();
        
        for($curhour = 0; $curhour < 24; $curhour++)
        {
        	$thishour = $curhour<10?'0'.$curhour:$curhour;
        	
        	$start_ts = $today." $thishour:00:00";
        	$end_ts = $today." $thishour:59:59";
        	
            $query = "SELECT * FROM `#__synk_logs`".
            		" WHERE `datetime`>='$start_ts' && `datetime`<='$end_ts'";
            $database->setQuery($query);
            
            $rows = $database->loadObjectList();
            $total = count( $rows );

            //store the value in an array
            $result[$num]['rows']		= $rows;
            $result[$num]['datedata']	= $thishour.':00';
            $result[$num]['countdata']  = $total;
            
            $runningtotal               = $runningtotal + $total;

            $num++;

        } // end of the while loop

        $data->rows         = $result;
        $data->total        = $runningtotal;

        $this->getChartBarHourly( $data, 'Today (Hourly), UTC', 'stats_left' );
        
        // Events statistics
		$database =& JFactory::getDBO();
		$query = "SELECT `#__synk_events`.`title`,`#__synk_logs`.`eventid`, COUNT(*) AS `cnt`".
				" FROM `#__synk_logs` LEFT JOIN `#__synk_events` ON `#__synk_logs`.`eventid`=`#__synk_events`.`id`".
				" WHERE CONVERT(`datetime`, DATE)=UTC_DATE()".
				" GROUP BY `#__synk_logs`.`eventid`".
				" ORDER BY `#__synk_logs`.`eventid`";
		$database->setQuery($query);
		$logged_events = $database->loadObjectList();
		$this->assign( 'logged_events', $logged_events );
    }
    
    function stats_yesterday()
    {
    	$database = JFactory::getDBO();
        
    	$database =& JFactory::getDBO();
        $database->setQuery("SELECT SUBDATE(UTC_DATE(), INTERVAL 1 DAY)");
        $yesterday = $database->loadResult();

        $runningtotal = 0;
        $runningsum = 0;
        $data = new stdClass();
        $num = 0;
        $result = array();
        
        for($curhour = 0; $curhour < 24; $curhour++)
        {
        	$thishour = $curhour<10?'0'.$curhour:$curhour;
        	
        	$start_ts = $yesterday." $thishour:00:00";
        	$end_ts = $yesterday." $thishour:59:59";
        	
            $query = "SELECT * FROM `#__synk_logs`".
            		" WHERE `datetime`>='$start_ts' && `datetime`<='$end_ts'";
            $database->setQuery($query);
            
            $rows = $database->loadObjectList();
            $total = count( $rows );

            //store the value in an array
            $result[$num]['rows']		= $rows;
            $result[$num]['datedata']	= $thishour.':00';
            $result[$num]['countdata']  = $total;
            
            $runningtotal               = $runningtotal + $total;

            $num++;

        } // end of the while loop

        $data->rows         = $result;
        $data->total        = $runningtotal;

        $this->getChartBarHourly( $data, 'Yesterday (Hourly), UTC', 'stats_left' );
        
        // Events statistics
		$database =& JFactory::getDBO();
		$query = "SELECT `#__synk_events`.`title`,`#__synk_logs`.`eventid`, COUNT(*) AS `cnt`".
				" FROM `#__synk_logs` LEFT JOIN `#__synk_events` ON `#__synk_logs`.`eventid`=`#__synk_events`.`id`".
				" WHERE `datetime`=DATE_SUB(UTC_DATE(), INTERVAL 1 DAY)".
				" GROUP BY `#__synk_logs`.`eventid`".
				" ORDER BY `#__synk_logs`.`eventid`";
		$database->setQuery($query);
		$logged_events = $database->loadObjectList();
		$this->assign( 'logged_events', $logged_events );
    }
    
    function stats_this_year()
    {
    	$database = JFactory::getDBO();
        
		$year = gmdate('Y');

        $runningtotal = 0;
        $runningsum = 0;
        $data = new stdClass();
        $num = 0;
        $result = array();
        
        for($curmonth = 1; $curmonth <= 12; $curmonth++)
        {
            $query = "SELECT * FROM `#__synk_logs` WHERE MONTH(`datetime`)=$curmonth";
            $database->setQuery($query);
            
            $rows = $database->loadObjectList();
            $total = count( $rows );
            
            $thismonth = $curmonth<10?'0'.$curmonth:$curmonth;

            //store the value in an array
            $result[$num]['rows']		= $rows;
            $result[$num]['datedata']	= $thismonth;
            $result[$num]['countdata']  = $total;
            
            $runningtotal               = $runningtotal + $total;

            $num++;

        } // end of the while loop

        $data->rows         = $result;
        $data->total        = $runningtotal;

        $this->getChartBarHourly( $data, 'This Year (Monthly)', 'stats_left' );
        
        // Events statistics
		$database =& JFactory::getDBO();
		$query = "SELECT `#__synk_events`.`title`,`#__synk_logs`.`eventid`, COUNT(*) AS `cnt`".
				" FROM `#__synk_logs` LEFT JOIN `#__synk_events` ON `#__synk_logs`.`eventid`=`#__synk_events`.`id`".
				" WHERE `datetime`>=DATE_SUB(UTC_DATE(), INTERVAL 1 YEAR)".
				" GROUP BY `#__synk_logs`.`eventid`".
				" ORDER BY `#__synk_logs`.`eventid`";
		$database->setQuery($query);
		$logged_events = $database->loadObjectList();
		$this->assign( 'logged_events', $logged_events );
    }
    
    function getChartBarDaily( $data, $chart_title, $variable_name, $type='countdata' )
    {
        JLoader::import( 'com_synk.libchart.classes.libchart', JPATH_SITE.DS.'media' );
        $row = new JObject();
        // Create Chart from Data
            // first specify the chart type and dimensions
            $chart = new VerticalBarChart(600, 250);

            // then set title
            $row->title = JText::_( $chart_title );
            $chart->setTitle( null );

            // then create a dataset
            $dataSet = new XYDataSet();

            $max = 0;
            if (is_array($data->rows)) { foreach ($data->rows as $r) {
                if ($r[$type] > $max) { $max = $r[$type]; }
                $dataSet->addPoint(new Point($r['datedata']['mon']."/".$r['datedata']['mday'], $r[$type]));
            } } // end foreach

            // link dataset to chart
            $chart->setDataSet($dataSet);
            $chart->bound->setUpperBound($max);

            // render to a png file
            jimport('joomla.user.helper');
            $newfilename = JUserHelper::genRandomPassword();
            $tmp_path = JFactory::getApplication()->getCfg('tmp_path');
            $chart->render( $tmp_path.DS.$newfilename.'.png' );

            $row->image = JHTML::_( "image.site", "$newfilename.png", "../tmp/" );

        $this->assign( "$variable_name", $row );
    }
    
    function getChartBarHourly( $data, $chart_title, $variable_name, $type='countdata' )
    {
        JLoader::import( 'com_synk.libchart.classes.libchart', JPATH_SITE.DS.'media' );
        $row = new JObject();
        // Create Chart from Data
            // first specify the chart type and dimensions
            $chart = new VerticalBarChart(600, 250);

            // then set title
            $row->title = JText::_( $chart_title );
            $chart->setTitle( null );

            // then create a dataset
            $dataSet = new XYDataSet();

            $max = 0;
            if (is_array($data->rows)) { foreach ($data->rows as $r) {
                if ($r[$type] > $max) { $max = $r[$type]; }
                $dataSet->addPoint(new Point($r['datedata'], $r[$type]));
            } } // end foreach

            // link dataset to chart
            $chart->setDataSet($dataSet);
            $chart->bound->setUpperBound($max);

            // render to a png file
            jimport('joomla.user.helper');
            $newfilename = JUserHelper::genRandomPassword();
            $tmp_path = JFactory::getApplication()->getCfg('tmp_path');
            $chart->render( $tmp_path.DS.$newfilename.'.png' );

            $row->image = JHTML::_( "image.site", "$newfilename.png", "../tmp/" );

        $this->assign( "$variable_name", $row );
    }
    */   
}