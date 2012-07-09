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

class SynkHelperTools extends DSCTools
{
	/**
	 * Get the Synk plugins which should be configured in the Configuration menu of the Synk Component
	 * Info about available plugins comes from the Component's 'manifest.xml'
	 * 
	 * @return array of objects from the #__plugins table which refer to Synk plugins
	 */
	function getPluginsConfig()
	{
		JLoader::import( 'com_synk.library.query', JPATH_ADMINISTRATOR.DS.'components' );
		
		$xml = new JSimpleXML;
		$xml->loadFile(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_synk'.DS.'manifest.xml');
		
		$database = JFactory::getDBO();
		$language = JFactory::getLanguage();
		
		foreach($xml->document->plugins[0]->children() as $key => $plugin){
			$attr = $plugin->attributes();
			
			// Load language file of the plugin
            $language->load('plg_'.$attr['group'].'_'.$attr['element'], JPATH_ADMINISTRATOR);
            	
			// We could use JPluginHelper::getPlugin() but it would return only enabled plugins
			$query = "SELECT `id`,`name`,`params` FROM `#__plugins` ".
				" WHERE `folder`='{$attr['group']}' && `element`='{$attr['element']}'";
			
			$database->setQuery($query);
			
			// Could happen with non-installed plugins
			if(!($pluginConfig = $database->loadObject())) continue;
			
			$pid = $pluginConfig->id;
			
			$synkPlugins[$key] = array();
			$synkPlugins[$key]['folder'] = $attr['group'];
			$synkPlugins[$key]['element'] = $attr['element'];
			$synkPlugins[$key]['id'] = $pid;
			$synkPlugins[$key]['name'] = $pluginConfig->name;
			
			// Add prefixes to parameters to avoid duplicates in different plugins
			$db_params = array();
			foreach(explode("\n", $pluginConfig->params) as $param) {
				$param = trim($param);
				if($param == ''){
					continue;
				}
				
				$db_params[] = "pid_{$pid}_{$param}";
			}
			
			// Create JParameter Object
			$synkPlugins[$key]['jparam'] = new JParameter(implode("\n", $db_params));
			
			// Add the same prefixes in the XML parameters too and load them in the JParameter Object
			$plg_xml = new JSimpleXML;
            if ($plg_xml->loadFile(JPATH_ROOT.DS.'plugins'.DS.$attr['group'].DS.$attr['element'].'.xml')){
            	// Load parameters from XML
            	if(isset($plg_xml->document->params) && is_array($plg_xml->document->params)){
            		foreach (@$plg_xml->document->params[0]->children() as $k => $param){
            			$param_attr = $param->attributes();
            				
            			if(isset($param_attr['name'])){
            				$plg_xml->document->params[0]->_children[$k]->
            				addAttribute('name', "pid_{$pid}_{$param_attr['name']}");
            			}
            		}
            		$synkPlugins[$key]['jparam']->setXML( $plg_xml->document->params[0] );
            	}
			}
		}
		
		return $synkPlugins;
	}
}