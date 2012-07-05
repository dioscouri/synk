<?php
/**
 * @version 1.5
 * @package Synk
 * @author  Dioscouri Design
 * @link    http://www.dioscouri.com
 * @copyright Copyright (C) 2007 Dioscouri Design. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

/** ensure this file is being included by a parent file */
defined('_JEXEC') or die('Restricted access');

class SynkHelperBase extends JObject
{
    /**
     * Determines whether/not a user can view a record
     *
     * @param $id                   id of commission
     * @param $userid [optional]    If absent, current logged-in user is used
     * @return boolean
     */
    function canView( $id, $userid=null )
    {
        $result = false;

        $user = JFactory::getUser( $userid );
        $userid = intval($user->id);

        // if the user is super admin, yes
            if ($user->gid == '25') { return true; }

        return $result;
    }

    function currency($amount)
    {
        $config = SynkConfig::getInstance();

        $num_decimals = $config->get('currency_num_decimals', '2');
        $thousands = $config->get('currency_thousands', ',');
        $decimal = $config->get('currency_decimal', '.');
        $pre = $config->get('currency_symbol_pre', '$');
        $post = $config->get('currency_symbol_post', '');

        $return = $pre.number_format($amount, $num_decimals, $decimal, $thousands).$post;
        return $return;
    }

    function number($number)
    {
        $config = SynkConfig::getInstance();

        $thousands = $config->get('currency_thousands', ',');
        $decimal = $config->get('currency_decimal', '.');

        $return = number_format($number, '0', $decimal, $thousands);
        return $return;
    }

    /**
     * Extracts a column from an array of arrays or objects
     *
     * @static
     * @param   array   $array  The source array
     * @param   string  $index  The index of the column or name of object property
     * @return  array   Column of values from the source array
     * @since   1.5
     */
    function getColumn(&$array, $index)
    {
        $result = array();

        if (is_array($array))
        {
            foreach (@$array as $item)
            {
                if (is_array($item) && isset($item[$index]))
                {
                    $result[] = $item[$index];
                }
                    elseif (is_object($item) && isset($item->$index))
                {
                    $result[] = $item->$index;
                }
            }
        }
        return $result;
    }

    /**
     * Takes an elements object and converts it to an array that can be binded to a JTable object
     *
     * @param $elements is an array of objects with ->name and ->value properties, all posted from a form
     * @return array[name] = value
     */
    function elementsToArray( $elements )
    {
        $return = array();

        foreach (@$elements as $element)
        {
            $name = $element->name;
            $value = $element->value;

            // if the name is an array, use the array's index
            if (strpos($name, '['))
            {
                $search = array( '[', ']' );
                $exploded = explode( '[', $name, '2' );
                $name = str_replace( $search, '', $exploded[1]);
            }

            if (empty($return[$name]) && !empty($name))
            {
                $return[$name] = $value;
            }
        }

        return $return;
    }

    /**
     *
     * @return unknown_type
     */
    function setDateVariables( $curdate, $enddate, $period )
    {
        $database = JFactory::getDBO();

        $return = new stdClass();
        $return->thisdate = '';
        $return->nextdate = '';

        switch ($period)
        {
            case "daily":
                    $thisdate = $curdate;
                    $query = " SELECT DATE_ADD('".$curdate."', INTERVAL 1 DAY) ";
                    $database->setQuery( $query );
                    $nextdate = $database->loadResult();
                $return->thisdate = $thisdate;
                $return->nextdate = $nextdate;
              break;
            case "weekly":
                $start  = getdate( strtotime($curdate) );

                // First period should be days between x day and the immediate Sunday
                    if ($start['wday'] < '1') {
                        $thisdate = $curdate;
                        $query = " SELECT DATE_ADD( '".$thisdate."', INTERVAL 1 DAY ) ";
                        $database->setQuery( $query );
                        $nextdate = $database->loadResult();
                    } elseif ($start['wday'] > '1') {
                        $interval = 8 - $start['wday'];
                        $thisdate = $curdate;
                        $query = " SELECT DATE_ADD( '".$thisdate."', INTERVAL {$interval} DAY ) ";
                        $database->setQuery( $query );
                        $nextdate = $database->loadResult();
                    } else {
                        // then every period following should be Mon-Sun
                        $thisdate = $curdate;
                        $query = " SELECT DATE_ADD( '".$thisdate."', INTERVAL 7 DAY ) ";
                        $database->setQuery( $query );
                        $nextdate = $database->loadResult();
                    }

                    if ( $nextdate > $enddate ) {
                        $query = " SELECT DATE_ADD( '".$nextdate."', INTERVAL 1 DAY ) ";
                        $database->setQuery( $query );
                        $nextdate = $database->loadResult();
                    }
                $return->thisdate = $thisdate;
                $return->nextdate = $nextdate;
              break;
            case "monthly":
                $start  = getdate( strtotime($curdate) );
                $start_datetime = date("Y-m-d", strtotime($start['year']."-".$start['mon']."-01"));
                    $thisdate = $start_datetime;
                    $query = " SELECT DATE_ADD( '".$thisdate."', INTERVAL 1 MONTH ) ";
                    $database->setQuery( $query );
                    $nextdate = $database->loadResult();

                $return->thisdate = $thisdate;
                $return->nextdate = $nextdate;
              break;
            default:
              break;
        }

        return $return;
    }

    /**
     *
     * @return unknown_type
     */
    function getToday()
    {
        static $today;

        if (empty($today))
        {
        	$database =& JFactory::getDBO();
        	$database->setQuery("SELECT UTC_DATE()");
        	$today = $database->loadResult();
        	
        	/* Commented out, so we trust the DB above to get the UTC today
        	 * 
        	 
            $config = JFactory::getConfig();
            $offset = $config->getValue('config.offset');
            $date = JFactory::getDate();
            $today = $date->toFormat( "%Y-%m-%d 00:00:00" );

            if ($offset > 0) {
                $command = 'DATE_ADD';
            } elseif ($offset < 0) {
                $command = 'DATE_SUB';
            } else {
                return $today;
            }

            $database = JFactory::getDBO();
            $query = "
                SELECT
                    {$command}( '{$today}', INTERVAL {$offset} HOUR )
                ";

            $database->setQuery( $query );
            $today = $database->loadResult();
            */
        }
        return $today;
    }

    /**
     *
     * @param $date
     * @return unknown_type
     */
    function getOffsetDate( $date )
    {
        // start with first day of the period, corrected for offset
        $config = JFactory::getConfig();
        $offset = $config->getValue('config.offset');
        if ($offset > 0) {
            $command = 'DATE_ADD';
        } elseif ($offset < 0) {
            $command = 'DATE_SUB';
        } else {
            $command = '';
        }
        if ($command)
        {
            $database = JFactory::getDBO();
            $query = "
                SELECT
                    {$command}( '{$date}', INTERVAL {$offset} HOUR )
                ";

            $database->setQuery( $query );
            $date = $database->loadResult();
        }
        return $date;
    }

    function getPeriodData( $start_datetime, $end_datetime, $period='daily', $select="tbl.*", $type='list' )
    {
        static $items;

        if (empty($items[$start_datetime][$end_datetime][$period][$select]))
        {
            $runningtotal = 0;
            $return = new stdClass();
            $database = JFactory::getDBO();

            // the following would be used if there were an additional filter in the Inputs
            $filter_where   = "";
            $filter_select  = "";
            $filter_join    = "";
            $filter_typeid  = "";
            if ($filter_typeid) {
                $filter_where   = "";
                $filter_select  = "";
                $filter_join    = "";
            }

            $start_datetime = strval( htmlspecialchars( $start_datetime ) );
            $end_datetime = strval( htmlspecialchars( $end_datetime ) );

            $start  = getdate( strtotime($start_datetime) );

            // start with first day of the period, corrected for offset
            $mainframe = JFactory::getApplication();
            $offset = $mainframe->getCfg( 'offset' );
            if ($offset > 0) {
                $command = 'DATE_ADD';
            } elseif ($offset < 0) {
                $command = 'DATE_SUB';
            } else {
                $command = '';
            }
            if ($command)
            {
                $database = JFactory::getDBO();
                $query = "
                    SELECT
                        {$command}( '{$start_datetime}', INTERVAL {$offset} HOUR )
                    ";

                $database->setQuery( $query );
                $curdate = $database->loadResult();

                $query = "
                    SELECT
                        {$command}( '{$end_datetime}', INTERVAL {$offset} HOUR )
                    ";

                $database->setQuery( $query );
                $enddate = $database->loadResult();
            }
                else
            {
                $curdate = $start_datetime;
                $enddate = $end_datetime;
            }

            // while the current date <= end_date
            // grab data for the period
            $num = 0;
            $result = array();
            while ($curdate <= $enddate)
            {
                // set working variables
                    $variables = SynkHelperBase::setDateVariables( $curdate, $enddate, $period );
                    $thisdate = $variables->thisdate;
                    $nextdate = $variables->nextdate;

                // grab all records
                // TODO Set the query here
                    $query = new SynkQuery();
                    $query->select( $select );
                    $rows = $this->selectPeriodData( $thisdate, $nextdate, $select, $type );
                    $total = $this->selectPeriodData( $thisdate, $nextdate, "COUNT(*)", "result" );

                //store the value in an array
                $result[$num]['rows']       = $rows;
                $result[$num]['datedata']   = getdate( strtotime($thisdate) );
                $result[$num]['countdata']  = $total;
                $runningtotal               = $runningtotal + $total;

                // increase curdate to the next value
                $curdate = $nextdate;
                $num++;

            } // end of the while loop

            $return->rows       = $result;
            $return->total      = $runningtotal;
            $items[$start_datetime][$end_datetime][$period][$select] = $return;
        }

        return $items[$start_datetime][$end_datetime][$period][$select];
    }
}