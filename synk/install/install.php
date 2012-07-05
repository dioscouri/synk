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

$thisextension = strtolower( "com_synk" );
$thisextensionname = substr ( $thisextension, 4 );

JLoader::import( 'com_synk.library.dscinstaller', JPATH_ADMINISTRATOR.DS.'components' );

// load the component language file
$language = &JFactory::getLanguage();
$language->load( $thisextension );

$status = new JObject();
$status->modules = array();
$status->plugins = array();
$status->templates = array();
$status->extras = array();

/***********************************************************************************************
 * ---------------------------------------------------------------------------------------------
 * // TEMPLATES INSTALLATION SECTION 
 * ---------------------------------------------------------------------------------------------
 ***********************************************************************************************/

$templates = &$this->manifest->getElementByPath('templates');
if (is_a($templates, 'JSimpleXMLElement') && count($templates->children())) {

	foreach ($templates->children() as $template)
	{
		$mname		= $template->attributes('template');
		$mpublish	= strtolower($template->attributes('publish'));
		$mclient	= JApplicationHelper::getClientInfo($template->attributes('client'), true);
		
		// Set the installation path
		if (!empty ($mname)) {
			$this->parent->setPath('extension_root', $mclient->path.DS.'templates'.DS.$mname);
		} else {
			$this->parent->abort(JText::_('Template').' '.JText::_('Install').': '.JText::_('Install Template File Missing'));
			return false;
		}
		
		/*
		 * fire the dioscouriInstaller with the foldername and folder entryType
		 */
		$pathToFolder = $this->parent->getPath('source').DS.$mname;
		$dscInstaller = new dscInstaller();
		if ($mpublish == "true") {
			$dscInstaller->set( '_publishExtension', true );
		}
		$result = $dscInstaller->installExtension($pathToFolder, 'folder');
		
		// track the message and status of installation from dscInstaller
		if ($result) 
		{
			$alt = JText::_( "Installed" );
			$mstatus = "<img src='images/tick.png' border='0' alt='{$alt}' />";
		} else {
			$alt = JText::_( "Failed" );
			$error = $dscInstaller->getError();
			$mstatus = "<img src='images/tick.png' border='0' alt='{$alt}' />";
			$mstatus .= " - ".$error;
		}
		
		$status->templates[] = array('name'=>$mname,'client'=>$mclient->name, 'status'=>$mstatus );
	}
}

/***********************************************************************************************
 * ---------------------------------------------------------------------------------------------
 * MODULE INSTALLATION SECTION
 * ---------------------------------------------------------------------------------------------
 ***********************************************************************************************/

$modules = &$this->manifest->getElementByPath('modules');
if (is_a($modules, 'JSimpleXMLElement') && count($modules->children())) {

	foreach ($modules->children() as $module)
	{
		$mname		= $module->attributes('module');
		$mpublish	= strtolower($module->attributes('publish'));
		$mposition	= $module->attributes('position');
		$mclient	= JApplicationHelper::getClientInfo($module->attributes('client'), true);
		
		// Set the installation path
		if (!empty ($mname)) {
			$this->parent->setPath('extension_root', $mclient->path.DS.'modules'.DS.$mname);
		} else {
			$this->parent->abort(JText::_('Module').' '.JText::_('Install').': '.JText::_('Install Module File Missing'));
			return false;
		}
		
		/*
		 * fire the dioscouriInstaller with the foldername and folder entryType
		 */
		$pathToFolder = $this->parent->getPath('source').DS.$mname;
		$dscInstaller = new dscInstaller();
		if ($mpublish == "true") {
			$dscInstaller->set( '_publishExtension', true );
		}
		$result = $dscInstaller->installExtension($pathToFolder, 'folder');
		
		// track the message and status of installation from dscInstaller
		if ($result) 
		{
			// update the module record if the position != left
			if (isset($mposition) && $mposition != 'left')
			{
				// set the position of the module
				$database = JFactory::getDBO();
				$query = "UPDATE #__modules SET `position` = '{$mposition}' WHERE `module` = '{$mname}';";
				$database->setQuery($query);
				$database->query();
			}
			$alt = JText::_( "Installed" );
			$mstatus = "<img src='images/tick.png' border='0' alt='{$alt}' />";
		} else {
			$alt = JText::_( "Failed" );
			$error = $dscInstaller->getError();
			$mstatus = "<img src='images/tick.png' border='0' alt='{$alt}' />";
			$mstatus .= " - ".$error;
		}
		
		$status->modules[] = array('name'=>$mname,'client'=>$mclient->name, 'status'=>$mstatus );
	}
}


/***********************************************************************************************
 * ---------------------------------------------------------------------------------------------
 * PLUGIN INSTALLATION SECTION
 * ---------------------------------------------------------------------------------------------
 ***********************************************************************************************/

$plugins = &$this->manifest->getElementByPath('plugins');
if (is_a($plugins, 'JSimpleXMLElement') && count($plugins->children())) {

	foreach ($plugins->children() as $plugin)
	{
		$pname		= $plugin->attributes('plugin');
		$ppublish	= strtolower($plugin->attributes('publish'));
		$pgroup		= $plugin->attributes('group');
		
		// Set the installation path
		if (!empty($pname) && !empty($pgroup)) {
			$this->parent->setPath('extension_root', JPATH_ROOT.DS.'plugins'.DS.$pgroup);
		} else {
			$this->parent->abort(JText::_('Plugin').' '.JText::_('Install').': '.JText::_('Install Plugin File Missing'));
			return false;
		}
		
		/*
		 * fire the dioscouriInstaller with the foldername and folder entryType
		 */
		$pathToFolder = $this->parent->getPath('source').DS.$pname;
		$dscInstaller = new dscInstaller();
		
		if ($ppublish == "true") {
			$dscInstaller->set( '_publishExtension', true );
		}
		$result = $dscInstaller->installExtension($pathToFolder, 'folder');
		
		// track the message and status of installation from dscInstaller
		if ($result) {
			$alt = JText::_( "Installed" );
			$pstatus = "<img src='images/tick.png' border='0' alt='{$alt}' />";	
		} else {
			$alt = JText::_( "Failed" );
			$error = $dscInstaller->getError();
			$pstatus = "<img src='images/tick.png' border='0' alt='{$alt}' /> ";
			$pstatus .= " - ".$error;	
		}

		$status->plugins[] = array('name'=>$pname,'group'=>$pgroup, 'status'=>$pstatus);
	}
}


/***********************************************************************************************
 * ---------------------------------------------------------------------------------------------
 * EXTRA FILES!
 * ---------------------------------------------------------------------------------------------
 ***********************************************************************************************/

$extras = &$this->manifest->getElementByPath('extras');
if (is_a($extras, 'JSimpleXMLElement') && count($extras->children())) {

	foreach ($extras->children() as $extra)
	{
		$pname		= $extra->attributes('name');
		$pfile		= $extra->attributes('file');
		$pdest		= $extra->attributes('destination');
		
		$base_path = $this->parent->getPath('source').DS.'extras'.DS;
		
		// Set the installation path
		if (!empty($pname) && !empty($pdest) && !empty($pfile)) {
			$pdest = str_replace(".", DS, $pdest);
			$dest = JPATH_SITE.DS.$pdest;
			if(!JFolder::exists($dest)){
				$this->parent->abort(JText::_('Extra').' '.JText::_('Install').': '.JText::_('Destination Path Does Not Exist'));
				return false;
			}
		} else {
			$this->parent->abort(JText::_('Extra').' '.JText::_('Install').': '.JText::_('Missing Extra File'));
			return false;
		}

		$result = JFile::copy($base_path.$pfile, $dest.DS.$pfile);
		
		// track the message and status of installation from dscInstaller
		if ($result) {
			$alt = JText::_( "Installed" );
			$pstatus = "<img src='images/tick.png' border='0' alt='{$alt}' />";	
		} else {
			$alt = JText::_( "Failed" );
			$error = $dscInstaller->getError();
			$pstatus = "<img src='images/tick.png' border='0' alt='{$alt}' /> ";
			$pstatus .= " - ".$error;	
		}

		$status->extras[] = array('name'=>$pname,'file'=>$pfile, 'dest'=>$pdest);
	}
}

/***********************************************************************************************
 * ---------------------------------------------------------------------------------------------
 * SETUP DEFAULTS
 * ---------------------------------------------------------------------------------------------
 ***********************************************************************************************/

// None

/***********************************************************************************************
 * ---------------------------------------------------------------------------------------------
 * OUTPUT TO SCREEN
 * ---------------------------------------------------------------------------------------------
 ***********************************************************************************************/
$rows = 0;
?>

<h2><?php echo JText::_('Installation Results'); ?></h2>
<table class="adminlist">
	<thead>
		<tr>
			<th colspan="2"><?php echo JText::_('Extension'); ?></th>
			<th width="30%"><?php echo JText::_('Status'); ?></th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="3"></td>
		</tr>
	</tfoot>
	<tbody>
		<tr class="row0">
			<td class="key" colspan="2"><?php echo JText::_( $thisextension ); ?></td>
			<td class="key"><center><?php $alt = JText::_('Installed'); echo "<img src='images/tick.png' border='0' alt='{$alt}' />"; ?></center></td>
		</tr>
<?php if (count($status->modules)) : ?>
		<tr>
			<th><?php echo JText::_('Module'); ?></th>
			<th><?php echo JText::_('Client'); ?></th>
			<th></th>
		</tr>
	<?php foreach ($status->modules as $module) : ?>
		<tr class="row<?php echo (++ $rows % 2); ?>">
			<td class="key"><?php echo $module['name']; ?></td>
			<td class="key"><?php echo ucfirst($module['client']); ?></td>
			<td class="key"><center><?php echo $module['status']; ?></center></td>
		</tr>
	<?php endforeach;
endif;
if (count($status->plugins)) : ?>
		<tr>
			<th><?php echo JText::_('Plugin'); ?></th>
			<th><?php echo JText::_('Group'); ?></th>
			<th></th>
		</tr>
	<?php foreach ($status->plugins as $plugin) : ?>
		<tr class="row<?php echo (++ $rows % 2); ?>">
			<td class="key"><?php echo $plugin['name']; ?></td>
			<td class="key"><?php echo $plugin['group']; ?></td>
			<td class="key"><center><?php echo $plugin['status']; ?></center></td>
		</tr>
	<?php endforeach;
endif;
if (count($status->templates)) : ?>
		<tr>
			<th><?php echo JText::_('Template'); ?></th>
			<th><?php echo JText::_('Client'); ?></th>
			<th></th>
		</tr>
	<?php foreach ($status->templates as $template) : ?>
		<tr class="row<?php echo (++ $rows % 2); ?>">
			<td class="key"><?php echo $template['name']; ?></td>
			<td class="key"><?php echo $template['client']; ?></td>
			<td class="key"><center><?php echo $template['status']; ?></center></td>
		</tr>
	<?php endforeach;
endif; ?>
<?php
if (count($status->extras)) : ?>
		<tr>
			<th><?php echo JText::_('Extras'); ?></th>
			<th><?php echo JText::_('File'); ?></th>
			<th><?php echo JText::_('Destination'); ?></th>
		</tr>
	<?php foreach ($status->extras as $extra) : ?>
		<tr class="row<?php echo (++ $rows % 2); ?>">
			<td class="key"><?php echo $extra['name']; ?></td>
			<td class="key"><?php echo $extra['file']; ?></td>
			<td class="key"><center><?php echo $extra['dest']; ?></center></td>
		</tr>
	<?php endforeach;
endif; ?>
	</tbody>
</table>
