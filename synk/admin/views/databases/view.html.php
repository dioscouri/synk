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

JLoader::import( 'com_synk.views._base', JPATH_ADMINISTRATOR.DS.'components' );
require_once( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_synk'.DS.'helpers'.DS.'events.php' );

class SynkViewDatabases extends SynkViewBase 
{
	/**
	 * 
	 * @param $tpl
	 * @return unknown_type
	 */
	function display($tpl=null) 
	{
		$layout = $this->getLayout();
		switch(strtolower($layout))
		{
			case "view":
				$this->_form($tpl);
			  break;
			case "form":
				JRequest::setVar('hidemainmenu', '1');
				$this->_form($tpl);
				
				// Assign default values for Driver and Port
				if(!isset($this->row)){
					$this->row->driver = 'mysql';
					$this->row->port = '3306';
				} else if(!isset($this->row->driver) || trim($this->row->driver) == ''){
					$this->row->driver = 'mysql';
				} else if(!isset($this->row->port) || trim($this->row->port) == ''){
					$this->row->port = '3306';
				} 
			  break;
			case "default":
			default:
				$this->_default($tpl);
			  break;
		}
		parent::display($tpl);
	}
	
	function _defaultToolbar()
	{
		JToolBarHelper::publishList();
		JToolBarHelper::unpublishList();
		JToolBarHelper::divider();
		parent::_defaultToolbar();
	}
}
