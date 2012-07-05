<?php
/**
 * @version		$Id: eaimproved.php 10381 2009-07-22 03:35:53Z Skullbock 
 * @copyright	Copyright (C) 2009 Krochmal & Peroni Studio Associato. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );



class plgXMLRPCSync extends JPlugin
{
	function plgXMLRPCSync(&$subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage( '', JPATH_ADMINISTRATOR );
	}

	/**
	* @return array An array of associative arrays defining the available methods
	*/
	function onGetWebServices()
	{
		global $xmlrpcI4, $xmlrpcInt, $xmlrpcBoolean, $xmlrpcDouble, $xmlrpcString, $xmlrpcDateTime, $xmlrpcBase64, $xmlrpcArray, $xmlrpcStruct, $xmlrpcValue;

		return array
		(
				'sync.ping' => array(
					'function' => 'plgXMLRPCSyncServices::ping',
					'docstring' => JText::_('Check the xmlrpc Connection.'),
					'signature' => array(array($xmlrpcBoolean, $xmlrpcString, $xmlrpcString))
													),
				'sync.query' => array(
					'function' => 'plgXMLRPCSyncServices::query',
					'docstring' => JText::_('Query the local Database.'),
					'signature' => array(array($xmlrpcStruct, $xmlrpcString, $xmlrpcString, $xmlrpcString))
													),		
				'sync.loadResult' => array(
					'function' => 'plgXMLRPCSyncServices::loadResult',
					'docstring' => JText::_('Load the query result'),
					'signature' => array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString))
													),
				'sync.loadResultArray' => array(
					'function' => 'plgXMLRPCSyncServices::loadResultArray',
					'docstring' => JText::_('Load the query results'),
					'signature' => array(array($xmlrpcArray, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcInt))
													),
				'sync.loadAssoc' => array(
					'function' => 'plgXMLRPCSyncServices::loadAssoc',
					'docstring' => JText::_('Load the query result as associative array'),
					'signature' => array(array($xmlrpcStruct, $xmlrpcString, $xmlrpcString, $xmlrpcString))
													),
				'sync.loadAssocList' => array(
					'function' => 'plgXMLRPCSyncServices::loadAssocList',
					'docstring' => JText::_('Load the query result as a list of associative array'),
					'signature' => array(array($xmlrpcArray, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString))
													),	
				'sync.loadObject' => array(
					'function' => 'plgXMLRPCSyncServices::loadObject',
					'docstring' => JText::_('Load the query result as a object'),
					'signature' => array(array($xmlrpcStruct, $xmlrpcString, $xmlrpcString, $xmlrpcString))
													),	
				'sync.loadObjectList' => array(
					'function' => 'plgXMLRPCSyncServices::loadObjectList',
					'docstring' => JText::_('Load the query result as a list of object'),
					'signature' => array(array($xmlrpcArray, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString))
													),
				'sync.loadRow' => array(
					'function' => 'plgXMLRPCSyncServices::loadRow',
					'docstring' => JText::_('Load the query result as a row'),
					'signature' => array(array($xmlrpcArray, $xmlrpcString, $xmlrpcString, $xmlrpcString))
													),	
				'sync.loadRowList' => array(
					'function' => 'plgXMLRPCSyncServices::loadRowList',
					'docstring' => JText::_('Load the query result as a list of rows'),
					'signature' => array(array($xmlrpcArray, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString))
								),		
				'sync.getVersion' => array(
					'function' => 'plgXMLRPCSyncServices::getVersion',
					'docstring' => JText::_('Get Version'),
					'signature' => array(array($xmlrpcString, $xmlrpcString, $xmlrpcString))
													),					
		);
	}
}

class plgXMLRPCSyncServices
{
	function ping($username, $password){ 
	
		global $xmlrpcerruser, $xmlrpcStruct, $xmlrpcInt, $xmlrpcString, $xmlrpcArray, $xmlrpcBoolean;

		if(!plgXMLRPCSyncHelper::authenticateUser($username, $password)) {
			return new xmlrpcresp(0, $xmlrpcerruser+1, JText::_("Login Failed"));
		}

		$db = plgXMLRPCSyncHelper::getDb();
		if($db->connected()){
			return new xmlrpcresp(new xmlrpcval( true, $xmlrpcBoolean));
		} else{
			return new xmlrpcresp(new xmlrpcval( false, $xmlrpcBoolean));
		} 
	
	}
	
	function query($username, $password, $sql){
		global $xmlrpcerruser, $xmlrpcStruct, $xmlrpcInt, $xmlrpcString, $xmlrpcArray, $xmlrpcBoolean;

		if(!plgXMLRPCSyncHelper::authenticateUser($username, $password)) {
			return new xmlrpcresp(0, $xmlrpcerruser+1, JText::_("Login Failed"));
		}

		$db = plgXMLRPCSyncHelper::getDb();
		
		$db->setQuery($sql);
		$db->query();
		
		$data = array();
		$data['numRows'] = new xmlrpcval(@$db->getNumRows(), $xmlrpcString);
		$data['affectedRows'] = new xmlrpcval($db->getAffectedRows(), $xmlrpcString);
		$data['insertid'] = new xmlrpcval($db->insertid(), $xmlrpcString);
		
		return new xmlrpcresp( new xmlrpcval( $data, $xmlrpcStruct ) );
	}
		
	function loadResult($username, $password, $sql){
		global $xmlrpcerruser, $xmlrpcStruct, $xmlrpcInt, $xmlrpcString, $xmlrpcArray, $xmlrpcBoolean;

		if(!plgXMLRPCSyncHelper::authenticateUser($username, $password)) {
			return new xmlrpcresp(0, $xmlrpcerruser+1, JText::_("Login Failed"));
		}
		
		$db = plgXMLRPCSyncHelper::getDB();
		
		$db->setQuery($sql);
		$ret = $db->loadResult();
		
		
		return new xmlrpcresp(new xmlrpcval( $ret, $xmlrpcString));
		
	}
	
	function loadResultArray($username, $password, $sql, $numinarray){
		global $xmlrpcerruser, $xmlrpcStruct, $xmlrpcInt, $xmlrpcString, $xmlrpcArray, $xmlrpcBoolean;

		if(!plgXMLRPCSyncHelper::authenticateUser($username, $password)) {
			return new xmlrpcresp(0, $xmlrpcerruser+1, JText::_("Login Failed"));
		}
		
		$db = plgXMLRPCSyncHelper::getDb();
		$db->setQuery($sql);
		$array = $db->loadResultArray($numinarray);
				
		$ret = plgXMLRPCSyncHelper::arrayToXmlRpc($array);
		
		return new xmlrpcresp(new xmlrpcval( $ret, $xmlrpcArray));
		
	}
	
	function loadAssoc($username, $password, $sql){
		global $xmlrpcerruser, $xmlrpcStruct, $xmlrpcInt, $xmlrpcString, $xmlrpcArray, $xmlrpcBoolean;

		if(!plgXMLRPCSyncHelper::authenticateUser($username, $password)) {
			return new xmlrpcresp(0, $xmlrpcerruser+1, JText::_("Login Failed"));
		}
		
		$db = plgXMLRPCSyncHelper::getDb();
		$db->setQuery($sql);
		$array = $db->loadAssoc();
				
		$ret = plgXMLRPCSyncHelper::arrayToXmlRpc($array);
		
		return new xmlrpcresp(new xmlrpcval( $ret, $xmlrpcStruct));
		
	}
	
	function loadAssocList($username, $password, $sql, $key){
		global $xmlrpcerruser, $xmlrpcStruct, $xmlrpcInt, $xmlrpcString, $xmlrpcArray, $xmlrpcBoolean;

		if(!plgXMLRPCSyncHelper::authenticateUser($username, $password)) {
			return new xmlrpcresp(0, $xmlrpcerruser+1, JText::_("Login Failed"));
		}
		
		$db = plgXMLRPCSyncHelper::getDb();
		$db->setQuery($sql);
		$array = $db->loadAssocList($key);
				
		$ret = plgXMLRPCSyncHelper::arrayToXmlRpc($array);
		
		return new xmlrpcresp(new xmlrpcval( $ret, $xmlrpcStruct));
		
	}
	
	function loadObject($username, $password, $sql){
		global $xmlrpcerruser, $xmlrpcStruct, $xmlrpcInt, $xmlrpcString, $xmlrpcArray, $xmlrpcBoolean;

		if(!plgXMLRPCSyncHelper::authenticateUser($username, $password)) {
			return new xmlrpcresp(0, $xmlrpcerruser+1, JText::_("Login Failed"));
		}
		
		$db = plgXMLRPCSyncHelper::getDb();
		$db->setQuery($sql);
		$obj = $db->loadObject();
				
		$ret = plgXMLRPCSyncHelper::objectToXmlRpc($obj);
		
		return new xmlrpcresp(new xmlrpcval( $ret, $xmlrpcStruct));
		
	}
	
	function loadObjectList($username, $password, $sql, $key){
		global $xmlrpcerruser, $xmlrpcStruct, $xmlrpcInt, $xmlrpcString, $xmlrpcArray, $xmlrpcBoolean;

		if(!plgXMLRPCSyncHelper::authenticateUser($username, $password)) {
			return new xmlrpcresp(0, $xmlrpcerruser+1, JText::_("Login Failed"));
		}
		
		$db = plgXMLRPCSyncHelper::getDb();
		$db->setQuery($sql);
		$array = $db->loadObjectList($key);
				
		$ret = plgXMLRPCSyncHelper::arrayToXmlRpc($array);
		
		return new xmlrpcresp(new xmlrpcval( $ret, $xmlrpcArray));
		
	}
	
	function loadRow($username, $password, $sql){
		global $xmlrpcerruser, $xmlrpcStruct, $xmlrpcInt, $xmlrpcString, $xmlrpcArray, $xmlrpcBoolean;

		if(!plgXMLRPCSyncHelper::authenticateUser($username, $password)) {
			return new xmlrpcresp(0, $xmlrpcerruser+1, JText::_("Login Failed"));
		}
		
		$db = plgXMLRPCSyncHelper::getDb();
		$db->setQuery($sql);
		$array = $db->loadRow();
				
		$ret = plgXMLRPCSyncHelper::arrayToXmlRpc($array);	
		
		return new xmlrpcresp(new xmlrpcval( $ret, $xmlrpcArray));
		
	}
	
	function loadRowList($username, $password, $sql){
		global $xmlrpcerruser, $xmlrpcStruct, $xmlrpcInt, $xmlrpcString, $xmlrpcArray, $xmlrpcBoolean;

		if(!plgXMLRPCSyncHelper::authenticateUser($username, $password)) {
			return new xmlrpcresp(0, $xmlrpcerruser+1, JText::_("Login Failed"));
		}
		
		$db = plgXMLRPCSyncHelper::getDb();
		$db->setQuery($sql);
		$array = $db->loadAssocList();
				
		$ret = plgXMLRPCSyncHelper::arrayToXmlRpc($array);	
		
		return new xmlrpcresp(new xmlrpcval( $ret, $xmlrpcArray));
		
	}
	
	function getVersion($username, $password){
		global $xmlrpcerruser, $xmlrpcStruct, $xmlrpcInt, $xmlrpcString, $xmlrpcArray, $xmlrpcBoolean;

		if(!plgXMLRPCSyncHelper::authenticateUser($username, $password)) {
			return new xmlrpcresp(0, $xmlrpcerruser+1, JText::_("Login Failed"));
		}
		
		$db = plgXMLRPCSyncHelper::getDB();
		
		$id = $db->getVersion();	
		
		return new xmlrpcresp(new xmlrpcval( $id, $xmlrpcString));
		
	}	
		
}
	

class plgXMLRPCSyncHelper
{
	// Only backend user can login!
	function authenticateUser($username, $password)
	{
		// Get the global JAuthentication object
		jimport( 'joomla.user.authentication');
		$auth = & JAuthentication::getInstance();
		$credentials = array( 'username' => $username, 'password' => $password );
		$options = array();
		//The minimum group
		$options['group'] = 'Public Backend';
		 //Make sure users are not autoregistered
		$options['autoregister'] = false;
		$response = $auth->authenticate($credentials, $options);

		return $response->status === JAUTHENTICATE_STATUS_SUCCESS;
	}
	
	function getDB(){
		
		$data = plgXMLRPCSyncData::getInstance();
		
		return $data->db;
		
	}
	
	function arrayToXmlRpc($array){
		
		global $xmlrpcString, $xmlrpcStruct;
		
		foreach(@$array as $k => $v){
			if(is_array($v))
				$ret[$k] = new xmlrpcval(plgXMLRPCSyncHelper::arrayToXmlRpc($v), $xmlrpcStruct);
			else
				if(is_object($v)){
					$ret[$k] = new xmlrpcval(plgXMLRPCSyncHelper::objectToXmlRpc($v), $xmlrpcStruct);
				}
				else
					$ret[$k] = new xmlrpcval($v, $xmlrpcString);
		}
		return $ret;
	}
	
	function objectToXmlRpc($object){
		
		global $xmlrpcString;
		
		$array = get_object_vars($object);
		foreach(@$array as $k => $v){
			if(is_array($v))
				$ret->$k = new xmlrpcval(plgXMLRPCSyncHelper::arrayToXmlRpc($v), $xmlrpcStruct);
			else
				if(is_object($v)){
					$temp = get_object_vars($v);
					$ret->$k = new xmlrpcval(plgXMLRPCSyncHelper::arrayToXmlRpc($temp), $xmlrpcStruct);
				}
				else
					$ret->$k = new xmlrpcval($v, $xmlrpcString);
		}
		return $ret;
	}
	
}


class plgXMLRPCSyncData {

	protected static $_instance;
	
	// Db object
	var $db = null;
	
	// Protected constructor
	protected function plgXMLRPCSyncData(){
		
		$conf =& JFactory::getConfig();

		$host 		= $conf->getValue('config.host');
		$user 		= $conf->getValue('config.user');
		$password 	= $conf->getValue('config.password');
		$database	= $conf->getValue('config.db');
		$prefix 	= $conf->getValue('config.dbprefix');
		$driver 	= $conf->getValue('config.dbtype');
		$debug 		= $conf->getValue('config.debug');

		$options	= array ( 'driver' => $driver, 'host' => $host, 'user' => $user, 'password' => $password, 'database' => $database, 'prefix' => $prefix, 'sync' => 'true' );
		
		$this->db =& JDatabase::getInstance( $options );
	}
	
	public static function getInstance() 
    {
      if( self::$_instance === NULL ) {
        self::$_instance = new self();
      }
      return self::$_instance;
    }
    
	
}