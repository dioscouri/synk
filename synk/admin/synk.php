<?php
/**
 * @package Synk
 * @author  Dioscouri Design
 * @link    http://www.dioscouri.com
 * @copyright Copyright (C) 2007 Dioscouri Design. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

/** ensure this file is being included by a parent file */
defined('_JEXEC') or die('Restricted access');

// Check the registry to see if our Synk class has been overridden
if ( !class_exists('Synk') ) 
    JLoader::register( "Synk", JPATH_ADMINISTRATOR.DS."components".DS."com_synk".DS."defines.php" );

// before executing any tasks, check the integrity of the installation
Synk::getClass( 'SynkHelperDiagnostics', 'helpers.diagnostics' )->checkInstallation();

// Require the base controller
Synk::load( 'SynkController', 'controller' );

// Require specific controller if requested
$controller = JRequest::getWord('controller', JRequest::getVar( 'view' ) );
if (!Synk::load( 'SynkController'.$controller, "controllers.$controller" ))
    $controller = '';

if (empty($controller))
{
    // redirect to default
	$default_controller = new SynkController();
	$redirect = "index.php?option=com_synk&view=" . $default_controller->default_view;
    $redirect = JRoute::_( $redirect, false );
    JFactory::getApplication()->redirect( $redirect );
}

JHTML::_('stylesheet', 'admin.css', 'media/com_synk/css/');

$doc = JFactory::getDocument();
$uri = JURI::getInstance();
$js = "var com_synk = {};\n";
$js.= "com_synk.jbase = '".$uri->root()."';\n";
$doc->addScriptDeclaration($js);

$parentPath = JPATH_ADMINISTRATOR . '/components/com_synk/helpers';
DSCLoader::discover('SynkHelper', $parentPath, true);

$parentPath = JPATH_ADMINISTRATOR . '/components/com_synk/library';
DSCLoader::discover('Synk', $parentPath, true);

// load the plugins
JPluginHelper::importPlugin( 'synk' );

// Create the controller
$classname = 'SynkController'.$controller;
$controller = Synk::getClass( $classname );
    
// ensure a valid task exists
$task = JRequest::getVar('task');
if (empty($task))
{
    $task = 'display';  
}
JRequest::setVar( 'task', $task );

// Perform the requested task
$controller->execute( $task );

// Redirect if set by the controller
$controller->redirect();
?>