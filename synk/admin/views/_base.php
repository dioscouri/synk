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

jimport( 'joomla.application.component.view' );

class SynkViewBase extends JView
{
	function display($tpl=null)
	{
		JHTML::_('stylesheet', 'synk_admin.css', 'media/com_synk/css/');

		$this->displayTitle( $this->get('title') );

		if (!JRequest::getInt('hidemainmenu') && empty($this->hidemenu))
		{
			$this->displayMenubar();
		}

		$modules = JModuleHelper::getModules("synk_left");
		if ($modules && !JRequest::getInt('hidemainmenu'))
		{
			$this->displayWithLeftMenu($tpl=null);
		}
			else
		{
			parent::display($tpl);
		}
	}

	function displayTitle( $text = '' )
	{
		$title = $text ? JText::_($text) : JText::_( ucfirst(JRequest::getVar('view')) );
		JToolBarHelper::title( $title, Synk::getName() );
	}

	function getMenubar()
	{
		$views  = array();

		$views['dashboard']			= 'Dashboard';
        $views['synchronizations']	= 'Synchronizations';
		$views['databases']  		= 'Databases';
		$views['events']			= 'Events';
		$views['logs']				= 'Logs';
		$views['tools']				= 'Tools';
		$views['config']			= 'Configuration';

		return $views;
	}

	function displayMenubar()
	{
		$this->getMenubar();
		$views = $this->getMenubar();

		$left = array();
		if (isset($this->leftMenu)) { $left = $this->getLeftMenubar(); }

		foreach($views as $view => $title)
		{
			$current = strtolower( JRequest::getVar('view') );
			$active = ($view == $current );
			if (array_key_exists($current, $left) && $view == 'localization' ) { $active = true; }
			JSubMenuHelper::addEntry(JText::_($title), 'index.php?option=com_synk&view='.$view, $active );
		}
	}

	function getLeftMenubar()
	{
		$views  = array();
		return $views;
	}

	function displayLeftMenubar($name='leftmenu')
	{
		JLoader::import( 'com_synk.library.menu', JPATH_ADMINISTRATOR.DS.'components' );

		$views = $this->getLeftMenubar();

		foreach($views as $view => $title)
		{
			$active = ($view == strtolower( JRequest::getVar('view') ) );
			SynkMenu::addEntry(JText::_($title), 'index.php?option=com_synk&view='.$view, $active, $name );
		}

		echo SynkMenu::display( $name, "{$name}_admin.css" );
	}

	/**
	 *
	 * @param $tpl
	 * @return unknown_type
	 */
    public function displayWithLeftMenu($tpl=null)
    {
    	// TODO This is an ugly, quick hack - fix it
    	echo "<table width='100%'>";
    		echo "<tr>";
	    		echo "<td style='width: 180px; padding-right: 5px; vertical-align: top;' >";

					$modules = JModuleHelper::getModules("synk_left");
					$document	= &JFactory::getDocument();
					$renderer	= $document->loadRenderer('module');
					$attribs 	= array();
					$attribs['style'] = 'xhtml';
					foreach ( @$modules as $mod )
					{
						echo $renderer->render($mod, $attribs);
					}

	    		echo "</td>";
	    		echo "<td style='vertical-align: top;' >";
	    			parent::display($tpl);
	    		echo "</td>";
    		echo "</tr>";
    	echo "</table>";
    }

	/**
	 * Basic commands for displaying a list
	 *
	 * @param $tpl
	 * @return unknown_type
	 */
	function _default($tpl='')
	{
		JLoader::import( 'com_synk.library.select', JPATH_ADMINISTRATOR.DS.'components' );
		JLoader::import( 'com_synk.library.grid', JPATH_ADMINISTRATOR.DS.'components' );
		JLoader::import( 'com_synk.library.url', JPATH_ADMINISTRATOR.DS.'components' );
		$model = $this->getModel();

		// set the model state
			$this->assign( 'state', $model->getState() );

		// check config
			$this->assign( 'config', SynkConfig::getInstance() );

		// add toolbar buttons
			$this->_defaultToolbar();

		// page-navigation
			$this->assign( 'pagination', $model->getPagination() );

		// list of items
			$this->assign('items', $model->getList());

		// form
			$validate = JUtility::getToken();
			$form = array();
			$controller = strtolower( $this->get( '_controller', JRequest::getVar('controller', JRequest::getVar('view') ) ) );
			$view = strtolower( $this->get( '_view', JRequest::getVar('view') ) );
			$action = $this->get( '_action', "index.php?option=com_synk&controller={$controller}&view={$view}" );
			$form['action'] = $action;
			$form['validate'] = "<input type='hidden' name='{$validate}' value='1' />";
			$this->assign( 'form', $form );
	}

	/**
	 * Basic methods for a form
	 * @param $tpl
	 * @return unknown_type
	 */
	function _form($tpl='')
	{
		JLoader::import( 'com_synk.library.select', JPATH_ADMINISTRATOR.DS.'components' );
		JLoader::import( 'com_synk.library.grid', JPATH_ADMINISTRATOR.DS.'components' );
		$model = $this->getModel();

		// set the model state
			$this->assign( 'state', $model->getState() );

		// check config
			$this->assign( 'config', SynkConfig::getInstance() );

		// get the data
			// not using getItem here to enable ->checkout (which requires JTable object)
			$row = $model->getTable();
			$row->load( (int) $model->getId() );
			// TODO Check if the item is checked out and if so, setlayout to view

		// set toolbar
			$layout = $this->getLayout();
			$isNew = ($row->id < 1);
			switch(strtolower($layout))
			{
				case "view":
					$this->_viewToolbar($isNew);
				  break;
				case "form":
				default:
					// Checkout the item if it isn't already checked out
					$row->checkout( JFactory::getUser()->id );
					$this->_formToolbar($isNew);
				  break;
			}
			$view = strtolower( JRequest::getVar('view') );
			$this->displayTitle( 'Edit '.$view );

		// form
			$validate = JUtility::getToken();
			$form = array();
			$controller = strtolower( $this->get( '_controller', JRequest::getVar('controller', JRequest::getVar('view') ) ) );
			$view = strtolower( $this->get( '_view', JRequest::getVar('view') ) );
			$action = $this->get( '_action', "index.php?option=com_synk&controller={$controller}&view={$view}&layout=form&id=".$model->getId() );
			$validation = $this->get( '_validation', "index.php?option=com_synk&controller={$controller}&task=validate&format=raw" );
			$form['action'] = $action;
			$form['validation'] = $validation;
			$form['validate'] = "<input type='hidden' name='{$validate}' value='1' />";
			$form['id'] = $model->getId();
			$this->assign( 'form', $form );
			$this->assign('row', $model->getItem() );

		// set the required image
		// TODO Fix this
			$required = new stdClass();
			$required->text = JText::_( 'Required' );
			$required->image = "<img src='".JURI::root()."/media/com_synk/images/required_16.png' alt='{$required->text}'>";
			$this->assign('required', $required );
	}

	/**
	 *
	 *
	 */
	function _defaultToolbar()
	{
		JToolBarHelper::editList();
		JToolBarHelper::deleteList( JText::_( 'VALIDDELETEITEMS' ) );
		JToolBarHelper::addnew();
	}

	/**
	 *
	 *
	 */
	function _formToolbar( $isNew=null )
	{
		JToolBarHelper::custom('savenew', "savenew", "savenew", JText::_( 'Save + New' ), false);
		JToolBarHelper::save('save');
		JToolBarHelper::apply('apply');

		if ($isNew)
		{
			JToolBarHelper::cancel();
		}
			else
		{
			JToolBarHelper::cancel( 'close', JText::_( 'Close' ) );
		}
	}

	/**
	 *
	 *
	 */
	function _viewToolbar( $isNew=null )
	{
		JToolBarHelper::cancel( 'close', JText::_( 'Close' ) );
	}

}