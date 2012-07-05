<?php
/**
 * @version 0.1
 * @package Synk
 * @author  Dioscouri Design
 * @link    http://www.dioscouri.com
 * @copyright Copyright (C) 2007 Dioscouri Design. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

/** ensure this file is being included by a parent file */
defined( '_JEXEC' ) or die( 'Restricted access' );

JLoader::import( 'com_synk.tables._base', JPATH_ADMINISTRATOR.DS.'components' );

class SynkTableXref extends SynkTable
{
    /**
     * Gets the internal primary key name
     *
     * @return string
     * @since 1.5
     */
    function getKey2Name()
    {
        return $this->_tbl_key2;
    }
    
    /**
     * Loads a row from the database and binds the fields to the object properties
     *
     * @access  public
     * @return  boolean True if successful
     */
    function load( $oid1, $oid2 )
    {
        $oid1 = (int) $oid1;
        $oid2 = (int) $oid2;
        
        if (empty($oid1) || empty($oid2)) 
        {
            return false;
        }
        $this->reset();

        $db = $this->getDBO();

        $query = "SELECT *"
        . " FROM ".$this->getTableName()
        . " WHERE ".$this->getKeyName()." = '".$oid1."'"
        . " AND ".$this->getKey2Name()." = '".$oid2."'";
        $db->setQuery( $query );

        if ($result = $db->loadAssoc())
        {
            return $this->bind($result);
        }
            else
        {
            $this->setError( $db->getErrorMsg() );
            return false;
        }
    }

    /**
     * Inserts a new row if id is zero or updates an existing row in the database table
     *
     * Can be overloaded/supplemented by the child class
     *
     * @access public
     * @param boolean If false, null object variables are not updated
     * @return null|string null if successful otherwise returns and error message
     */
    function store( $updateNulls=false )
    {
        $dispatcher = JDispatcher::getInstance();
        $before = $dispatcher->trigger( 'onBeforeStore'.$this->get('_suffix'), array( $this ) );
        if (in_array(false, $before, true))
        {
            return false;
        }

            // check if a record exists with these two keys
            $already = clone $this;
            if ( $already->load( $this->getKeyName(), $this->getKey2Name() ) )
            {
                $ret = $this->updateObject( $updateNulls );
            }
                else
            {
                $ret = $this->insertObject();
            }
            
            if( !$ret )
            {
                $this->setError(get_class( $this ).'::store failed - '.$this->getError() );
                $return = false;
            }
                else
            {
                $return = true;
            }
        
        if ( $return )
        {
            $dispatcher = JDispatcher::getInstance();
            $dispatcher->trigger( 'onAfterStore'.$this->get('_suffix'), array( $this ) );
        }
        return $return;
    }

    /**
     * (non-PHPdoc)
     * @see synk/admin/tables/SynkTable#delete($oid)
     */
    function delete( $oid1='', $oid2='' )
    {
        $k1 = $this->getKeyName();
        $k2 = $this->getKey2Name();
        
        $oid1 = $oid1 ? (int) $oid1 : $this->$k1;
        $oid2 = $oid2 ? (int) $oid2 : $this->$k2;
        
        if (empty($oid1)) 
        {
            $this->setError(JText::_( "Missing Key")." :: ". $k1 );
            return false;
        }
        if (empty($oid2)) 
        {
            $this->setError(JText::_( "Missing Key")." :: ". $k2 );
            return false;
        }
        
        $db = $this->getDBO();

        $query = "DELETE"
        . " FROM ".$this->getTableName()
        . " WHERE ".$this->getKeyName()." = '".$oid1."'"
        . " AND ".$this->getKey2Name()." = '".$oid2."'";
        $db->setQuery( $query );

        if ($this->_db->query())
        {
            $dispatcher = JDispatcher::getInstance();
            $dispatcher->trigger( 'onAfterDelete'.$this->get('_suffix'), array( $this ) );
            return true;
        }
        else
        {
            $this->setError($this->_db->getErrorMsg());
            return false;
        }
    }
    
    /**
     * Inserts a row into a table based on an objects properties
     *
     * @access  public
     * @param   string  The name of the table
     * @param   object  An object whose properties match table fields
     * @param   string  The name of the primary key. If provided the object property is updated.
     */
    function insertObject()
    {
        $table = $this->getTableName();
        $fmtsql = 'INSERT INTO '.$this->_db->nameQuote($table).' ( %s ) VALUES ( %s ) ';
        $fields = array();
        foreach (get_object_vars( $this ) as $k => $v) {
            if (is_array($v) or is_object($v) or $v === NULL) {
                continue;
            }
            if ($k[0] == '_') { // internal field
                continue;
            }
            $fields[] = $this->_db->nameQuote( $k );
            $values[] = $this->_db->isQuoted( $k ) ? $this->_db->Quote( $v ) : (int) $v;
        }
        $this->_db->setQuery( sprintf( $fmtsql, implode( ",", $fields ) ,  implode( ",", $values ) ) );
        if (!$this->_db->query()) 
        {
            $this->setError( $this->_db->getErrorMsg() );
            return false;
        }
        return true;
    }

    /**
     * Updates an existing role
     *
     * @access public
     * @param [type] $updateNulls
     */
    function updateObject( $updateNulls=true )
    {
        $table = $this->getTableName();
        $fmtsql = 'UPDATE '.$this->_db->nameQuote($table).' SET %s WHERE %s';
        $tmp = array();
        $where = array();
        foreach (get_object_vars( $this ) as $k => $v)
        {
            if( is_array($v) or is_object($v) or $k[0] == '_' ) { // internal or NA field
                continue;
            }
            if( $k == $this->getKeyName() ) 
            { // PK not to be updated
                // TODO Use query builder
                // ->where()
                $where[] = $k . '=' . $this->Quote( $v );
                continue;
            }
            if( $k == $this->getKey2Name() ) 
            { // PK not to be updated
                // TODO Use query builder
                // ->where()
                $where[] = $k . '=' . $this->Quote( $v );
                continue;
            }
            if ($v === null)
            {
                if ($updateNulls) {
                    $val = 'NULL';
                } else {
                    continue;
                }
            } else {
                $val = $this->_db->isQuoted( $k ) ? $this->_db->Quote( $v ) : (int) $v;
            }
            $tmp[] = $this->_db->nameQuote( $k ) . '=' . $val;
        }
        $this->_db->setQuery( sprintf( $fmtsql, implode( ",", $tmp ) , implode( " AND ", $where ) ) );
        if (!$this->_db->query()) 
        {
            $this->setError( $this->_db->getErrorMsg() );
            return false;
        }
        return true;
    }
    
}
