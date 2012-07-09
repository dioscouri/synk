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

class SynkHelperDiagnostics extends DSCHelperDiagnostics 
{
    /**
     * Performs basic checks on your installation to ensure it is OK
     * @return unknown_type
     */
    public function checkInstallation() 
    {
        $functions = array();

        $functions[] = 'checkConfigDBFieldNames';
        $functions[] = 'dropConfigDBFieldNames';
        
        foreach ($functions as $function)
        {
            if (!$this->{$function}())
            {
                return $this->redirect( JText::_("COM_SYNK_".$function."_FAILED") .' :: '. $this->getError(), 'error' );
            }
        }
    }
    
    public function checkConfigDBFieldNames()
    {
        if (Synk::getInstance()->get( __FUNCTION__, '0' ))
        {
            return true;
        }
    
        $table = '#__synk_config';
        $definitions = array();
        $fields = array();
    
        $fields[] = "id";
        $newnames["id"] = "config_id";
        $definitions["id"] = "int(11) NOT NULL auto_increment";
    
        $fields[] = "title";
        $newnames["title"] = "config_name";
        $definitions["title"] = "varchar(255) NOT NULL";
    
        if ($this->changeTableFields( $table, $fields, $definitions, $newnames ))
        {
            $this->setCompleted( __FUNCTION__ );
            return true;
        }
        return false;
    }
    
    public function dropConfigDBFieldNames()
    {
        if (Synk::getInstance()->get( __FUNCTION__, '0' ))
        {
            return true;
        }
    
        $table = '#__synk_config';
        $fields = array();
    
        $fields[] = "description";
        $fields[] = "ordering";
        $fields[] = "published";
        $fields[] = "checked_out";
        $fields[] = "checked_out_time";
    
        if ($this->dropTableFields( $table, $fields ))
        {
            $this->setCompleted( __FUNCTION__ );
            return true;
        }
        return false;
    }
    
    protected function setCompleted( $fieldname, $value='1' )
    {
        JTable::addIncludePath( JPATH_ADMINISTRATOR . '/components/com_synk/tables' );
        $config = JTable::getInstance( 'Config', 'SynkTable' );
        $config->load( array( 'config_name'=>$fieldname ) );
        $config->config_name = $fieldname;
        $config->value = '1';
        $config->save();
    }
}