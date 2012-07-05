<?php
/**
 * @version	0.1
 * @package	Synk
 * @author 	Dioscouri Design
 * @link 	http://www.dioscouri.com
 * @copyright Copyright (C) 2007 Dioscouri Design. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

/** ensure this file is being included by a parent file */
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

class SynkController extends JController 
{
    /**
     * @var array() instances of Models to be used by the controller
     */
	var $_models = array();
	
	/**
	 * constructor
	 */
	function __construct() 
	{
		parent::__construct();
		$this->set('suffix', 'dashboard');
					
		// Register Extra tasks
		$this->registerTask( 'list', 'display' );
		$this->registerTask( 'close', 'cancel' );
		$this->registerTask( 'add', 'edit' );
		$this->registerTask( 'new', 'edit' );
		$this->registerTask( 'apply', 'save' );
		$this->registerTask( 'savenew', 'save' );
		$this->registerTask( 'remove', 'delete' );
		$this->registerTask( 'publish', 'enable' );
		$this->registerTask( 'unpublish', 'enable' );
		$this->registerTask( 'disable', 'enable' );
		$this->registerTask( 'saveorder', 'ordering' );
		$this->registerTask( 'page_tooltip_enable', 'pagetooltip_switch' );
		$this->registerTask( 'page_tooltip_disable', 'pagetooltip_switch' );
	}

	/**
	* 	display the view
	*/
	function display($cachable=false)
	{
		// this sets the default view
		JRequest::setVar( 'view', JRequest::getVar( 'view', 'dashboard' ) );
		
		$document =& JFactory::getDocument();

		$viewType	= $document->getType();
		$viewName	= JRequest::getCmd( 'view', $this->getName() );
		$viewLayout	= JRequest::getCmd( 'layout', 'default' );

		$view = & $this->getView( $viewName, $viewType, '', array( 'base_path'=>$this->_basePath));

		// Get/Create the model
		if ($model = & $this->getModel($viewName)) 
		{
			// controller sets the model's state - this is why we override parent::display()
			$this->_setModelState();
			// Push the model into the view (as default)
			$view->setModel($model, true);
		}

		// Set the layout
		$view->setLayout($viewLayout);

		$dispatcher = JDispatcher::getInstance();
		$dispatcher->trigger('onBeforeDisplayAdminComponentSynk', array() );
		
		// Display the view
		if ($cachable && $viewType != 'feed') {
			global $option;
			$cache =& JFactory::getCache($option, 'view');
			$cache->get($view, 'display');
		} else {
			$view->display();
		}

		$dispatcher = JDispatcher::getInstance();
		$dispatcher->trigger('onAfterDisplayAdminComponentSynk', array() );
		
		$this->footer();
	}

    /**
     * 
     * @return unknown_type
     */
    function getNamespace()
    {
        $app = JFactory::getApplication();
        $model = $this->getModel( $this->get('suffix') );
        $ns = $app->getName().'::'.'com.synk.model.'.$model->getTable()->get('_suffix');
        return $ns;
    }
    
    /**
     * 
     * @return unknown_type
     */
    function _setModelState()
    {
        $app = JFactory::getApplication();
        $model = $this->getModel( $this->get('suffix') );
        $ns = $this->getNamespace();

        $state = array();
        
        $state['limit']     = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'), 'int');
        $state['limitstart'] = $app->getUserStateFromRequest($ns.'limitstart', 'limitstart', 0, 'int');
        $state['order']     = $app->getUserStateFromRequest($ns.'.filter_order', 'filter_order', 'tbl.'.$model->getTable()->getKeyName(), 'cmd');
        $state['direction'] = $app->getUserStateFromRequest($ns.'.filter_direction', 'filter_direction', 'ASC', 'word');
        $state['filter']    = $app->getUserStateFromRequest($ns.'.filter', 'filter', '', 'string');
        $state['id']        = JRequest::getVar('id', 'post', JRequest::getVar('id', 'get', '', 'int'), 'int');

        // TODO santize the filter
        // $state['filter']     = 

        foreach (@$state as $key=>$value)
        {
            $model->setState( $key, $value );   
        }
        return $state;
    }
    
    /**
     * We override parent::getModel because parent::getModel was always creating a new Model instance
     *
     */
    function getModel( $name = '', $prefix = '', $config = array() )
    {
        if ( empty( $name ) ) {
            $name = $this->getName();
        }

        if ( empty( $prefix ) ) {
            $prefix = $this->getName() . 'Model';
        }
        
        $fullname = strtolower( $prefix.$name ); 
        if (empty($this->_models[$fullname]))
        {
            if ( $model = & $this->_createModel( $name, $prefix, $config ) )
            {
                // task is a reserved state
                $model->setState( 'task', $this->_task );
    
                // Lets get the application object and set menu information if its available
                $app    = &JFactory::getApplication();
                $menu   = &$app->getMenu();
                if (is_object( $menu ))
                {
                    if ($item = $menu->getActive())
                    {
                        $params =& $menu->getParams($item->id);
                        // Set Default State Data
                        $model->setState( 'parameters.menu', $params );
                    }
                }
            }
                else 
            {
                $model = new JModel();
            }
            $this->_models[$fullname] = $model;
        }

        return $this->_models[$fullname];
    }
	
	/**
	 * @return void
	 */
	function view() 
	{
		// TODO Couldn't these eventually be more like elementuser & elementarticle
		JRequest::setVar( 'view', $this->get('suffix') );
		JRequest::setVar( 'layout', 'view' );
		parent::display();
	}
	
	/**
	 * @return void
	 */
	function edit() 
	{
		// TODO Couldn't these eventually be more like elementuser & elementarticle
		// Checks if item is checkedout, and if so, redirects to view
		JRequest::setVar( 'view', $this->get('suffix') );
		$model 	= $this->getModel( $this->get('suffix') );
	    $row = $model->getTable();
	    $row->load( $model->getId() );
	    $userid = JFactory::getUser()->id;
		if (!JTable::isCheckedOut($userid, @$row->checked_out))
		{
			if ($row->checkout( $userid ))
			{
				JRequest::setVar( 'hidemainmenu', '1' );
				JRequest::setVar( 'layout', 'form' );
				parent::display();
			}
		} 
			else
		{
			JRequest::setVar( 'layout', 'view' );
			parent::display();
		}
	}
	
	function release() 
	{
		$model 	= $this->getModel( $this->get('suffix') );
	    $row = $model->getTable();
	    $row->load( $model->getId() );
		if (isset($row->checked_out) && !JTable::isCheckedOut( JFactory::getUser()->id, $row->checked_out) )
		{
			if ($row->checkin())
			{
				$this->message = JText::_( "Item Released" );
			}
		}
		
    	$redirect = "index.php?option=com_synk&controller=".$this->get('suffix')."&view=".$this->get('suffix')."&task=view&id=".$model->getId()."&donotcheckout=1";
    	$redirect = JRoute::_( $redirect, false );
		$this->setRedirect( $redirect, $this->message, $this->messagetype );		
	}

	/**
	 * cancel and redirect to main page
	 * @return void
	 */
	function cancel() 
	{
		$link = 'index.php?option=com_synk&view='.$this->get('suffix');
		
		$task = JRequest::getVar( 'task' );
		switch (strtolower($task))
		{
			case "cancel":
				$msg = JText::_( 'Operation Cancelled' );
				$type = "notice";
			  break;
			case "close":
			default:
				$model 	= $this->getModel( $this->get('suffix') );
			    $row = $model->getTable();
			    $row->load( $model->getId() );
				if (isset($row->checked_out) && !JTable::isCheckedOut( JFactory::getUser()->id, $row->checked_out) )
				{
					$row->checkin();
				}
				$msg = "";
				$type = "";				
			  break;
		}
	    
	    $this->setRedirect( $link, $msg, $type );		
	}
	
	/**
	 * Verifies the fields in a submitted form.  Uses the table's check() method.
	 * Will often be overridden. Is expected to be called via Ajax 
	 * 
	 * @return unknown_type
	 */
	function validate()
	{
		$response = array();
		$response['msg'] = '';
		$response['error'] = '';
			
		// get elements from post
			$elements = json_decode( preg_replace('/[\n\r]+/', '\n', JRequest::getVar( 'elements', '', 'post', 'string' ) ) );

		// convert elements to array that can be binded 			
			require_once( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_synk'.DS.'helpers'.DS.'_base.php' );
			$values = SynkHelperBase::elementsToArray( $elements );

		// get table object
			$table = $this->getModel( $this->get('suffix') )->getTable();
		
		// bind to values
			$table->bind( $values );
		
		// validate it using table's ->check() method
			if (!$table->check())
			{
				// if it fails check, return message
				$response['error'] = '1';
				$response['msg'] = '
					<dl id="system-message">
					<dt class="notice">notice</dt>
					<dd class="notice message fade">
						<ul>'.
						$table->getError()						
						.'</ul>
					</dd>
					</dl>
					';
			}
			
		echo ( json_encode( $response ) );
		return;
	}
	
	/**
	 * save a record
	 * @return void
	 */
	function save() 
	{
		$model 	= $this->getModel( $this->get('suffix') );
		
	    $row = $model->getTable();
	    $row->load( $model->getId() );
		$row->bind( $_POST );
		
		if ( $row->save() ) 
		{
			$model->setId( $row->id );
			$this->messagetype 	= 'message';
			$this->message  	= JText::_( 'Saved' );
			
			$dispatcher = JDispatcher::getInstance();
			$dispatcher->trigger( 'onAfterSave'.$this->get('suffix'), array( $row ) );
		} 
			else 
		{
			$this->messagetype 	= 'notice';			
			$this->message 		= JText::_( 'Save Failed' )." - ".$row->getError();
		}
		
    	$redirect = "index.php?option=com_synk";
    	$task = JRequest::getVar('task');
    	switch ($task)
    	{
    		case "savenew":
    			$redirect .= '&view='.$this->get('suffix').'&layout=form';
    		  break;
    		case "apply":
    			$redirect .= '&view='.$this->get('suffix').'&layout=form&id='.$model->getId();
    		  break;
    		case "save":
    		default:
    			$redirect .= "&view=".$this->get('suffix');
    		  break;
    	}

    	$redirect = JRoute::_( $redirect, false );
		$this->setRedirect( $redirect, $this->message, $this->messagetype );
	}
	
	/*
	 * remove record(s)
	 */
	function delete()
	{
		$error = false;
		$this->messagetype	= '';
		$this->message 		= '';
		$redirect = 'index.php?option=com_synk&view='.$this->get('suffix');
		$redirect = JRoute::_( $redirect, false );
				
		$model = $this->getModel($this->get('suffix'));
		$row = $model->getTable();
		
		$cids = JRequest::getVar('cid', array (0), 'post', 'array');
		foreach (@$cids as $cid)
		{
			if (!$row->delete($cid))
			{
				$this->message .= $row->getError();
				$this->messagetype = 'notice';
				$error = true;
			}
		}
		
		if ($error)
		{
			$this->message = JText::_('Error') . " - " . $this->message;
		}
			else
		{
			$this->message = JText::_('Items Deleted');
		}
		
		$this->setRedirect( $redirect, $this->message, $this->messagetype );
	}
	
	/**
	 * save order
	 * @return void
	 */
	function order() 
	{
		$error = false;
		$this->messagetype	= '';
		$this->message 		= '';
		$redirect = 'index.php?option=com_synk&view='.$this->get('suffix');
		$redirect = JRoute::_( $redirect, false );
				
		$model = $this->getModel($this->get('suffix'));
		$row = $model->getTable();
		$row->load( $model->getId() );
		
		$change	= JRequest::getVar('order_change', '0', 'post', 'int');
		
		if ( !$row->move( $change ) )
		{
			$this->messagetype 	= 'notice';			
			$this->message 		= JText::_( 'Ordering Failed' )." - ".$row->getError();
		}
		
		$this->setRedirect( $redirect, $this->message, $this->messagetype );
	}
	
	/**
	 * save ordering
	 * @return void
	 */
	function ordering() 
	{
		$error = false;
		$this->messagetype	= '';
		$this->message 		= '';
		$redirect = 'index.php?option=com_synk&view='.$this->get('suffix');
		$redirect = JRoute::_( $redirect, false );
				
		$model = $this->getModel($this->get('suffix'));
		$row = $model->getTable();
		
		$ordering = JRequest::getVar('ordering', array(0), 'post', 'array');
		$cids = JRequest::getVar('cid', array (0), 'post', 'array');
		foreach (@$cids as $cid)
		{
			$row->load( $cid );
			$row->ordering = @$ordering[$cid];
			
			if (!$row->store())
			{
				$this->message .= $row->getError();
				$this->messagetype = 'notice';
				$error = true;
			}
		}
		
		$row->reorder();
		
		if ($error)
		{
			$this->message = JText::_('Error') . " - " . $this->message;
		}
			else
		{
			$this->message = JText::_('Items Ordered');
		}
		
		$this->setRedirect( $redirect, $this->message, $this->messagetype );
	}
	
	/*
	 * 
	 */
	function boolean()
	{
		$error = false;
		$this->messagetype	= '';
		$this->message 		= '';
		$redirect = 'index.php?option=com_synk&view='.$this->get('suffix');
		$redirect = JRoute::_( $redirect, false );
				
		$model = $this->getModel($this->get('suffix'));
		$row = $model->getTable();	

		$cids = JRequest::getVar('cid', array (0), 'post', 'array');
		$task = JRequest::getVar( 'task' );
		$vals = explode('_', $task);
		
		$field = $vals['0'];
		$action = $vals['1'];		
		
		switch (strtolower($action))
		{
			case "switch":
				$switch = '1';
			  break;
			case "disable":
				$enable = '0';
				$switch = '0';
			  break;
			case "enable":
				$enable = '1';
				$switch = '0';
			  break;
			default:
				$this->messagetype 	= 'notice';
				$this->message 		= JText::_( "Invalid Task" );
				$this->setRedirect( $redirect, $this->message, $this->messagetype );
				return;
			  break;
		}

		if ( !in_array( $field, array_keys( $row->getProperties() ) ) ) 
		{
			$this->messagetype 	= 'notice';
			$this->message 		= JText::_( "Invalid Field" ).": {$field}";
			$this->setRedirect( $redirect, $this->message, $this->messagetype );
			return;
		}
		
		foreach (@$cids as $cid)
		{
			$row->load( $cid );
			
			switch ($switch)
			{
				case "1":
					$row->$field = $row->$field ? '0' : '1';
				  break;
				case "0":
				default:
					$row->$field = $enable;
				  break;
			}
			
			if ( !$row->save() ) 
			{
				$this->message .= $row->getError();
				$this->messagetype = 'notice';
				$error = true;
			}
		}
		
		if ($error)
		{
			$this->message = JText::_('Error') . ": " . $this->message;
		}
			else
		{
			$this->message = JText::_('Status Changed');
		}
		
		$this->setRedirect( $redirect, $this->message, $this->messagetype );
	}
	
	/*
	 * enable record(s)
	 */
	function enable()
	{
		$task = JRequest::getVar( 'task' );
		switch (strtolower($task))
		{
			case "switch_publish":
				$field = 'published';
				$action = 'switch';
			  break;
			case "switch":
			case "switch_enable":
				$field = 'enabled';
				$action = 'switch';
			  break;
			case "unpublish":
				$field = 'published';
				$action = 'disable';
			  break;
			case "disable":
				$field = 'enabled';
				$action = 'disable';
			  break;
			case "publish":
				$field = 'published';
				$action = 'enable';
			  break;
			case "enable":
			default:
				$field = 'enabled';
				$action = 'enable';
			  break;
		}
		JRequest::setVar( 'task', $field.'_'.$action );
		$this->boolean();
	}
	
	/*
	 * 
	 */
	function pagetooltip_switch()
	{
		$msg = new stdClass();
		$msg->type 		= '';
		$msg->message 	= '';
		$view = JRequest::getVar('view');
		$msg->link 		= 'index.php?option=com_synk&view='.$view;
				
		$key = JRequest::getVar('key');
		$constant = 'page_tooltip_'.$key;
		$config_title = $constant."_disabled";

			$database = &JFactory::getDBO();
			JTable::addIncludePath( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_synk'.DS.'tables'.DS );
			unset($table);
			$table = JTable::getInstance( 'config', 'Table' );
			$table->load( $config_title );
			$table->title = $config_title;
			$table->value = '1';
		
		if (!$table->store())
		{
			$msg->type = 'notice';
			$msg->message = JText::_('Error') . ": " . $table->getError();
		}
				
		$this->setRedirect( $msg->link, $msg->message, $msg->type );
	}

    /**
     * Displays the footer
     * 
     * @return unknown_type
     */
    function footer()
    {
        $model  = $this->getModel( 'dashboard' );
        $view   = $this->getView( 'dashboard', 'html' );
        $view->hidemenu = true;
        $view->hidestats = true;
        $view->setModel( $model, true );
        $view->setLayout('footer');
        $view->display();
    }
    
	/**
	 * 
	 * @return 
	 */
	function doTask()
	{
		$success = true;
		$msg = new stdClass();
		$msg->message = '';
		$msg->error = '';
				
		// expects $element in URL and $elementTask
		$element = JRequest::getVar( 'element', '', 'request', 'string' );
		$elementTask = JRequest::getVar( 'elementTask', '', 'request', 'string' );

		$msg->error = '1';
		// $msg->message = "element: $element, elementTask: $elementTask";
		
		// gets the plugin named $element
		$import 	= JPluginHelper::importPlugin( 'synk', $element );
		$dispatcher	=& JDispatcher::getInstance();
		// executes the event $elementTask for the $element plugin
		// returns the html from the plugin
		// passing the element name allows the plugin to check if it's being called (protects against same-task-name issues)
		$result 	= $dispatcher->trigger( $elementTask, array( $element ) );
		// This should be a concatenated string of all the results, 
			// in case there are many plugins with this eventname 
			// that return null b/c their filename != element) 
		$msg->message = implode( '', $result );
			// $msg->message = @$result['0'];
						
		// encode and echo (need to echo to send back to browser)		
		echo $msg->message;
		$success = $msg->message;

		return $success;
	}
	
	/**
	 * 
	 * @return 
	 */
	function doTaskAjax()
	{
		JLoader::import( 'com_synk.library.json', JPATH_ADMINISTRATOR.DS.'components' );
		
		$success = true;
		$msg = new stdClass();
		$msg->message = '';
				
		// get elements $element and $elementTask in URL 
			$element = JRequest::getVar( 'element', '', 'request', 'string' );
			$elementTask = JRequest::getVar( 'elementTask', '', 'request', 'string' );
			
		// get elements from post
			// $elements = json_decode( preg_replace('/[\n\r]+/', '\n', JRequest::getVar( 'elements', '', 'post', 'string' ) ) );
			
		// for debugging
			// $msg->message = "element: $element, elementTask: $elementTask";

		// gets the plugin named $element
			$import 	= JPluginHelper::importPlugin( 'synk', $element );
			$dispatcher	=& JDispatcher::getInstance();
			
		// executes the event $elementTask for the $element plugin
		// returns the html from the plugin
		// passing the element name allows the plugin to check if it's being called (protects against same-task-name issues)
			$result 	= $dispatcher->trigger( $elementTask, array( $element ) );
		// This should be a concatenated string of all the results, 
			// in case there are many plugins with this eventname 
			// that return null b/c their filename != element)
			$msg->message = implode( '', $result );
			// $msg->message = @$result['0'];

		// set response array
			$response = array();
			$response['msg'] = $msg->message;
			
		// encode and echo (need to echo to send back to browser)
			echo ( json_encode( $response ) );

		return $success;
	}
	
	/**
	 * Article element
	 */
	function elementArticle() 
	{
		$model	= $this->getModel( 'elementarticle' );
		$view	= $this->getView( 'elementarticle' );
		include_once( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_content'.DS.'helper.php' );
		$view->setModel( $model, true );
		$view->display();
	}
	
	/**
	 * User element
	 */
	function elementUser() 
	{
		$model 	= $this->getModel( 'elementuser' );
		$view	= $this->getView( 'elementuser' );
		$view->setModel( $model, true );
		$view->display();
	}
}

?>