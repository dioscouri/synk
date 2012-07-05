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
defined( '_JEXEC' ) or die( 'Restricted access' );

class SynkControllerSynchronizations extends SynkController 
{
	/**
	 * constructor
	 */
	function __construct() 
	{
		parent::__construct();
		
		$this->set('suffix', 'synchronizations');
        $this->registerTask( 'selected_enable', 'selected_switch' );
        $this->registerTask( 'selected_disable', 'selected_switch' );
	}

	/**
	 * Controllers interact with the Request and set the model's state
	 * 
	 * @see synk/admin/SynkController#_setModelState()
	 */
    function _setModelState()
    {
    	$state = parent::_setModelState();   	
		$app = JFactory::getApplication();
		$model = $this->getModel( $this->get('suffix') );
    	$ns = $this->getNamespace();

    	$state['filter_eventid'] 	= $app->getUserStateFromRequest($ns.'eventid', 'filter_eventid', '', '');
      	$state['filter_databaseid'] 	= $app->getUserStateFromRequest($ns.'databaseid', 'filter_databaseid', '', '');
      	$state['filter_enabled'] 	= $app->getUserStateFromRequest($ns.'enabled', 'filter_enabled', '', '');
        $state['filter_id_from']    = $app->getUserStateFromRequest($ns.'id_from', 'filter_id_from', '', '');
        $state['filter_id_to']      = $app->getUserStateFromRequest($ns.'id_to', 'filter_id_to', '', '');
        $state['filter_title']     = $app->getUserStateFromRequest($ns.'title', 'filter_title', '', '');
        $state['filter_db']     = $app->getUserStateFromRequest($ns.'db', 'filter_db', '', '');

    	foreach (@$state as $key=>$value)
		{
			$model->setState( $key, $value );	
		}
  		return $state;
    }
	
    /**
     * Loads view for assigning synchronizations to events
     * 
     * @return unknown_type
     */
    function selectevents()
    {
        $this->set('suffix', 'events');
        $state = parent::_setModelState();
        $app = JFactory::getApplication();
        $model = $this->getModel( $this->get('suffix') );
        $ns = $this->getNamespace();

        foreach (@$state as $key=>$value)
        {
            $model->setState( $key, $value );   
        }
        
        $id = JRequest::getVar( 'id', JRequest::getVar( 'id', '0', 'post', 'int' ), 'get', 'int' );
        $row = $model->getTable( 'synchronizations' );
        $row->load( $id ); 
        $view   = $this->getView( 'synchronizations', 'html' );
        $view->set( '_controller', 'synchronizations' );
        $view->set( '_view', 'synchronizations' );
        $view->set( '_action', "index.php?option=com_synk&controller=synchronizations&task=selectevents&tmpl=component&id=".$model->getId() );
        $view->setModel( $model, true );
        $view->assign( 'state', $model->getState() );
        $view->assign( 'row', $row );
        $view->setLayout( 'selectevents' );
        $view->display();
    }
    
    /**
     * 
     * @return unknown_type
     */
    function selected_switch()
    {
        $error = false;
        $this->messagetype  = '';
        $this->message      = '';
                
        $model = $this->getModel($this->get('suffix'));
        $row = $model->getTable();  

        $id = JRequest::getVar( 'id', JRequest::getVar( 'id', '0', 'post', 'int' ), 'get', 'int' );
        $cids = JRequest::getVar('cid', array (0), 'request', 'array');
        $param = JRequest::getVar('parameter', array (0), 'request', 'array');
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
                $this->messagetype  = 'notice';
                $this->message      = JText::_( "Invalid Task" );
                $this->setRedirect( $redirect, $this->message, $this->messagetype );
                return;
              break;
        }
        
        foreach (@$cids as $cid)
        {
            $table = JTable::getInstance('SynchronizationEvents', 'Table');
            $table->load( $id, $cid );           
            if ($switch)
            {
                if (isset($table->synchronizationid)) 

                {
                	if (!$table->delete())
                    {
                        $this->message .= $cid.', ';
                        $this->messagetype = 'notice';
                        $error = true;
                    }
                } 
                    else 
                {                	
                    $table->synchronizationid = $id;
                    $table->eventid = $cid;
                    $table->parameter = $param[$cid]; 
                    if (!$table->save())
                    {
                        $this->message .= $cid.', ';
                        $this->messagetype = 'notice';
                        $error = true;                      
                    }
                }               
            }
                else
            {
                switch ($enable)
                {
                    case "1":
                        $table->synchronizationid = $id;
                        $table->eventid = $cid;
                        $table->parameter = $param[$cid];
                        if (!$table->save())
                        {
                            $this->message .= $cid.', ';
                            $this->messagetype = 'notice';
                            $error = true;
                        }
                      break;
                    case "0":
                    default:
                        if (!$table->delete())
                        {
                            $this->message .= $cid.', ';
                            $this->messagetype = 'notice';
                            $error = true;                      
                        }
                      break;
                }
            }         	
        }
        
        if ($error)
        {
            $this->message = JText::_('Error') . ": " . $this->message;
        }
            else
        {
            $this->message = "";
        }
 
        $redirect = JRequest::getVar( 'return' ) ?  
            base64_decode( JRequest::getVar( 'return' ) ) : "index.php?option=com_synk&controller=synchronizations&task=selectevents&tmpl=component&id=".$id;
        $redirect = JRoute::_( $redirect, false );
        
        $this->setRedirect( $redirect, $this->message, $this->messagetype );
    }
    
 function saveParameters()
    {
    	$error = false;
        $this->messagetype  = '';
        $this->message      = '';
                
        $model = $this->getModel($this->get('suffix'));
        $row = $model->getTable();  

        $id = JRequest::getVar( 'id', JRequest::getVar( 'id', '0', 'post', 'int' ), 'get', 'int' );
        $cids = JRequest::getVar('cid', array (0), 'request', 'array');
        $param = JRequest::getVar('parameter', array (0), 'request', 'array');
        $task = JRequest::getVar( 'task' );
        $field = $vals['0'];
        
        foreach (@$cids as $cid)
        {
            $table = JTable::getInstance('SynchronizationEvents', 'Table');
            $table->load( $id, $cid );
       
            $table->parameter = $param[$cid];
            // delete the record from table and then saves the record with updated parameters        
           	if($table->delete())
            {
                $table = JTable::getInstance('SynchronizationEvents', 'Table');
            	$table->load( $id, $cid );           
            
	           	$table->synchronizationid = $id;
	         	$table->eventid = $cid;
	            $table->parameter = $param[$cid];                                  
	            if (!$table->save())
	            {
	            	
	                $this->message .= $cid.', ';
	                $this->messagetype = 'notice';
	                $error = true;                      
	            }
                                         
            }
            
                     	
        }
        
        if ($error)
        {
            $this->message = JText::_('Error') . ": " . $this->message;
        }
            else
        {
            $this->message = "";
        }
 
        $redirect = JRequest::getVar( 'return' ) ?  
            base64_decode( JRequest::getVar( 'return' ) ) : "index.php?option=com_synk&controller=synchronizations&task=selectevents&tmpl=component&id=".$id;
        $redirect = JRoute::_( $redirect, false );
        
        $this->setRedirect( $redirect, $this->message, $this->messagetype );
    }

}

?>
