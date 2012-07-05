<?php
/**
* @package		Tienda
* @subpackage	Database
* @copyright	Copyright (C) 2010 Dioscouri
* @author		Daniele Rosario
* @license		GNU/GPL, see LICENSE.php
*/

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

// Includes the required class file for the XML-RPC Client
jimport('phpxmlrpc.xmlrpc');

/**
 * XmlRpc database driver
 *
 * @package		Joomla.Framework
 * @subpackage	Database
 * @since		1.0
 */
class JDatabaseXmlRpc extends JDatabase
{
	/**
	 * The database driver name
	 *
	 * @var string
	 */
	var $name			= 'xmlrpc';

	/**
	 *  The null/zero date string
	 *
	 * @var string
	 */
	var $_nullDate		= '0000-00-00 00:00:00';
	
	/**
	 * 	The xmlrpc client
	 * 
	 * @var object
	 */
	var $_client		= null;
	
	/**
	 * 	The xmlrpc user
	 * 
	 * @var object
	 */
	var $_username		= '';
	
	/**
	 * 	The xmlrpc password
	 * 
	 * @var object
	 */
	var $_password		= '';
	
	var $_numRows		= 0;
	
	var $_affectedRows	= 0;
	
	var $_insertid	= 0;

	/**
	* Database object constructor
	*
	* @access	public
	* @param	array	List of options used to configure the connection
	* @since	1.5
	* @see		JDatabase
	*/
	function __construct( $options )
	{
		$host		= array_key_exists('host', $options)	? $options['host']		: 'localhost';
		$username	= array_key_exists('user', $options)	? $options['user']		: '';
		$password	= array_key_exists('password',$options)	? $options['password']	: '';
		$isjoomla	= array_key_exists('isjoomla',$options)	? $options['isjoomla']	: true;	
		
		// Force http:// if the user has not written it
		if(stripos($host, 'http://') !== 0)
			$host = 'http://' . $host;
		// If the target is joomla (default), force it to the xmlrpc server path
		if($isjoomla)
			$host .= "/xmlrpc/index.php";		
			
		$this->_username = $username;
		$this->_password = $password;	
			
		// finalize initialization
		parent::__construct($options);

		// Construct the xmlrpc client
		$this->_client = new xmlrpc_client( $host );
		$this->_client->return_type = 'phpvals';
		
	}

	/**
	 * Always True
	 *
	 * @static
	 * @access public
	 * @return boolean  True on success, false otherwise.
	 */
	function test()
	{
		$parameters = array (
						new xmlrpcval($this->_username, "string"),
						new xmlrpcval($this->_password, "string"),
					);
		$message = new xmlrpcmsg("sync.ping", $parameters);
		$xmlrpcdoc = $this->_client->send($message);
		
		// Error
		if ($xmlrpcdoc->faultCode()){
			$this->setError(JText::_('ERROR_CONNECTING_XMLRPC').": ".$xmlrpcdoc->faultString());
			return false;
		}
		return true;
	}

	/**
	 * Always Connected
	 *
	 * @access	public
	 * @return	boolean
	 * @since	1.5
	 */
	function connected()
	{
		return true;
	}

	/**
	 * Determines UTF support
	 *
	 * @access	public
	 * @return boolean True - UTF is supported
	 */
	function hasUTF()
	{
		return true;
	}

	/**
	 * Custom settings for UTF support
	 *
	 * @access	public
	 */
	function setUTF()
	{
		
	}

	/**
	 * Get a database escaped string
	 *
	 * @param	string	The string to be escaped
	 * @param	boolean	Optional parameter to provide extra escaping
	 * @return	string
	 * @access	public
	 * @abstract
	 */
	function getEscaped( $text, $extra = false )
	{
		$result = mysql_real_escape_string( $text );
		if ($extra) {
			$result = addcslashes( $result, '%_' );
		}
		return $result;
	}

	/**
	 * Execute the query
	 *
	 * @access	public
	 * @return mixed A database resource if successful, FALSE if not.
	 */
	function query()
	{
		// Take a local copy so that we don't modify the original query and cause issues later
		$sql = $this->_sql;
		if ($this->_limit > 0 || $this->_offset > 0) {
			$sql .= ' LIMIT '.$this->_offset.', '.$this->_limit;
		}
		if ($this->_debug) {
			$this->_ticker++;
			$this->_log[] = $sql;
		}
		$this->_errorNum = 0;
		$this->_errorMsg = '';
		
		// Preparing for querying the xmlrpc
		$parameters = array (
						new xmlrpcval($this->_username, "string"),
						new xmlrpcval($this->_password, "string"),
						new xmlrpcval($this->_sql, "string"),
					);
		$message = new xmlrpcmsg("sync.query", $parameters);
		$xmlrpcdoc = $this->_client->send($message);
		
		// Error
		if ($xmlrpcdoc->faultCode()){
			$this->_errorNum = $xmlrpcdoc->faultCode();
			$this->_errorMsg = JText::_('ERROR_QUERYING_XMLRPC').": ".$xmlrpcdoc->faultString();

			if ($this->_debug) {
				JError::raiseError(500, 'JDatabaseXmlRpc::query: '.$this->_errorNum.' - '.$this->_errorMsg );
			}
			return false;
		} else{
			// Store the response from the xmlrpc server
			$data = $xmlrpcdoc->value();
			$this->_numRows = $data['numRows'];
			$this->_affectedRows = $data['affectedRows'];
			$this->_insertid = $data['insertid'];
		}
		
		return true;
	}

	/**
	 * Description
	 *
	 * @access	public
	 * @return int The number of affected rows in the previous operation
	 * @since 1.0.5
	 */
	function getAffectedRows()
	{		
		return $this->_affetctedRows;
	}

	/**
	 * Execute a batch query
	 *
	 * @access	public
	 * @return mixed A database resource if successful, FALSE if not.
	 */
	function queryBatch( $abort_on_error=true, $p_transaction_safe = false)
	{
		$this->_errorNum = 0;
		$this->_errorMsg = '';
		if ($p_transaction_safe) {
			$this->_sql = rtrim($this->_sql, "; \t\r\n\0");
			$si = $this->getVersion();
			preg_match_all( "/(\d+)\.(\d+)\.(\d+)/i", $si, $m );
			if ($m[1] >= 4) {
				$this->_sql = 'START TRANSACTION;' . $this->_sql . '; COMMIT;';
			} else if ($m[2] >= 23 && $m[3] >= 19) {
				$this->_sql = 'BEGIN WORK;' . $this->_sql . '; COMMIT;';
			} else if ($m[2] >= 23 && $m[3] >= 17) {
				$this->_sql = 'BEGIN;' . $this->_sql . '; COMMIT;';
			}
		}
		$query_split = $this->splitSql($this->_sql);
		$error = 0;
		foreach ($query_split as $command_line) {
			$command_line = trim( $command_line );
			if ($command_line != '') {
				
				// Preparing for querying the xmlrpc
				$parameters = array (
								new xmlrpcval($this->_username, "string"),
								new xmlrpcval($this->_password, "string"),
								new xmlrpcval($command_line, "string"),
							);
				$message = new xmlrpcmsg("sync.query", $parameters);
				$xmlrpcdoc = $this->_client->send($message);
				
				if ($this->_debug) {
					$this->_ticker++;
					$this->_log[] = $command_line;
				}
				
				// Error
				if ($xmlrpcdoc->faultCode()){
					$this->_errorNum = mysql_errno( $xmlrpcdoc->faultCode() );
					$this->_errorMsg = JText::_('ERROR_QUERYING_XMLRPC').": ".$xmlrpcdoc->faultString();
					$error = 1;
					if ($abort_on_error) {
						return false;
					}
				} else{
					// Store the response from the xmlrpc server
					$this->_cursor = $xmlrpcdoc->value();
				}
			}
		}
		return $error ? false : true;
	}

	/**
	 * Diagnostic function Disables
	 *
	 * @access	public
	 * @return	string
	 */
	function explain()
	{
		return false;
	}

	/**
	 * Description
	 *
	 * @access	public
	 * @return int The number of rows returned from the most recent query.
	 */
	function getNumRows( $cur=null )
	{
		return $this->_numRows;
	}
	
	/**
	 * Sets the SQL query string for later execution.
	 *
	 * This function replaces a string identifier <var>$prefix</var> with the
	 * string held is the <var>_table_prefix</var> class variable.
	 *
	 * @access public
	 * @param string The SQL query
	 * @param string The offset to start selection
	 * @param string The number of results to return
	 * @param string The common table prefix
	 */
	function setQuery( $sql, $offset = 0, $limit = 0, $prefix='#__' )
	{
		$this->_sql		= $sql;
		$this->_limit	= (int) $limit;
		$this->_offset	= (int) $offset;
	}

	/**
	 * This method loads the first field of the first row returned by the query.
	 *
	 * @access	public
	 * @return The value returned in the query or null if the query failed.
	 */
	function loadResult()
	{
		$ret = null;
		
		// Preparing for querying the xmlrpc
		$parameters = array (
						new xmlrpcval($this->_username, "string"),
						new xmlrpcval($this->_password, "string"),
						new xmlrpcval($this->_sql, "string")
					);
		$message = new xmlrpcmsg("sync.loadResult", $parameters);
		$xmlrpcdoc = $this->_client->send($message);
		
		// Error
		if ($xmlrpcdoc->faultCode()){
			$this->_errorNum = $xmlrpcdoc->faultCode();
			$this->_errorMsg = JText::_('ERROR_LOADRESULT_XMLRPC').": ".$xmlrpcdoc->faultString();

			if ($this->_debug) {
				JError::raiseError(500, 'JDatabaseXmlRpc::loadResult: '.$this->_errorNum.' - '.$this->_errorMsg );
			}
			return -1;
		}
		$ret = $xmlrpcdoc->value();
		
		return $ret;
	}

	/**
	 * Load an array of single field results into an array
	 *
	 * @access	public
	 */
	function loadResultArray($numinarray = 0)
	{
		$array = array();
		
		// Preparing for querying the xmlrpc
		$parameters = array (
						new xmlrpcval($this->_username, "string"),
						new xmlrpcval($this->_password, "string"),
						new xmlrpcval($this->_sql, "string"),
						new xmlrpcval($numinarray, "int"),
					);
		$message = new xmlrpcmsg("sync.loadResultArray", $parameters);
		$xmlrpcdoc = $this->_client->send($message);
		
		// Error
		if ($xmlrpcdoc->faultCode()){
			$this->_errorNum = $xmlrpcdoc->faultCode();
			$this->_errorMsg = JText::_('ERROR_LOADRESULTARRAY_XMLRPC').": ".$xmlrpcdoc->faultString();

			if ($this->_debug) {
				JError::raiseError(500, 'JDatabaseXmlRpc::loadResultArray: '.$this->_errorNum.' - '.$this->_errorMsg );
			}
			
			return array();
		}
		$array = $xmlrpcdoc->value();
		
		return $array;
	}

	/**
	* Fetch a result row as an associative array
	*
	* @access	public
	* @return array
	*/
	function loadAssoc()
	{
		$array = null;
		// Preparing for querying the xmlrpc
		$parameters = array (
						new xmlrpcval($this->_username, "string"),
						new xmlrpcval($this->_password, "string"),
						new xmlrpcval($this->_sql, "string"),
					);
		$message = new xmlrpcmsg("sync.loadAssoc", $parameters);
		$xmlrpcdoc = $this->_client->send($message);
		
		// Error
		if ($xmlrpcdoc->faultCode()){
			$this->_errorNum = $xmlrpcdoc->faultCode();
			$this->_errorMsg = JText::_('ERROR_LOADASSOC_XMLRPC').": ".$xmlrpcdoc->faultString();

			if ($this->_debug) {
				JError::raiseError(500, 'JDatabaseXmlRpc::loadAssoc: '.$this->_errorNum.' - '.$this->_errorMsg );
			}
			return array();
		}
		
		$array = $xmlrpcdoc->value();
		
		
		return $array;
	}

	/**
	* Load a assoc list of database rows
	*
	* @access	public
	* @param string The field name of a primary key
	* @return array If <var>key</var> is empty as sequential list of returned records.
	*/
	function loadAssocList( $key='' )
	{
		$array = array();
		// Preparing for querying the xmlrpc
		$parameters = array (
						new xmlrpcval($this->_username, "string"),
						new xmlrpcval($this->_password, "string"),
						new xmlrpcval($this->_sql, "string"),
						new xmlrpcval($key, "string"),
					);
		$message = new xmlrpcmsg("sync.loadAssocList", $parameters);
		$xmlrpcdoc = $this->_client->send($message);
		
		// Error
		if ($xmlrpcdoc->faultCode()){
			$this->_errorNum = $xmlrpcdoc->faultCode();
			$this->_errorMsg = JText::_('ERROR_LOADASSOCLIST_XMLRPC').": ".$xmlrpcdoc->faultString();

			if ($this->_debug) {
				JError::raiseError(500, 'JDatabaseXmlRpc::loadAssocList: '.$this->_errorNum.' - '.$this->_errorMsg );
			}
			return array();
		}
		
		$array = $xmlrpcdoc->value();
		
		
		return $array;
	}

	/**
	* This global function loads the first row of a query into an object
	*
	* @access	public
	* @return 	object
	*/
	function loadObject( )
	{
		$ret = null;
		// Preparing for querying the xmlrpc
		$parameters = array (
						new xmlrpcval($this->_username, "string"),
						new xmlrpcval($this->_password, "string"),
						new xmlrpcval($this->_sql, "string"),
					);
		$message = new xmlrpcmsg("sync.loadObject", $parameters);
		$xmlrpcdoc = $this->_client->send($message);
		
		// Error
		if ($xmlrpcdoc->faultCode()){
			$this->_errorNum = $xmlrpcdoc->faultCode();
			$this->_errorMsg = JText::_('ERROR_LOADOBJECT_XMLRPC').": ".$xmlrpcdoc->faultString();

			if ($this->_debug) {
				JError::raiseError(500, 'JDatabaseXmlRpc::loadObject: '.$this->_errorNum.' - '.$this->_errorMsg );
			}
			return $ret;
		}
		
		$ret = $xmlrpcdoc->value();
		
		$ret = (object) $ret;
		
		return $ret;
	}

	/**
	* Load a list of database objects
	*
	* If <var>key</var> is not empty then the returned array is indexed by the value
	* the database key.  Returns <var>null</var> if the query fails.
	*
	* @access	public
	* @param string The field name of a primary key
	* @return array If <var>key</var> is empty as sequential list of returned records.
	*/
	function loadObjectList( $key='' )
	{
		
		$array = array();
		// Preparing for querying the xmlrpc
		$parameters = array (
						new xmlrpcval($this->_username, "string"),
						new xmlrpcval($this->_password, "string"),
						new xmlrpcval($this->_sql, "string"),
						new xmlrpcval($key, "string"),
					);
		$message = new xmlrpcmsg("sync.loadObjectList", $parameters);
		$xmlrpcdoc = $this->_client->send($message);
		
		// Error
		if ($xmlrpcdoc->faultCode()){
			$this->_errorNum = $xmlrpcdoc->faultCode();
			$this->_errorMsg = JText::_('ERROR_LOADAOBJECTLIST_XMLRPC').": ".$xmlrpcdoc->faultString();

			if ($this->_debug) {
				JError::raiseError(500, 'JDatabaseXmlRpc::loadObjectList: '.$this->_errorNum.' - '.$this->_errorMsg );
			}
			return array();
		}
		
		$array = $xmlrpcdoc->value();
		
		foreach ($array as &$a){
			$a = (object) $a;
		}
		
		return $array;
	}

	/**
	 * Description
	 *
	 * @access	public
	 * @return The first row of the query.
	 */
	function loadRow()
	{
		$ret = null;
		// Preparing for querying the xmlrpc
		$parameters = array (
						new xmlrpcval($this->_username, "string"),
						new xmlrpcval($this->_password, "string"),
						new xmlrpcval($this->_sql, "string"),
					);
		$message = new xmlrpcmsg("sync.loadRow", $parameters);
		$xmlrpcdoc = $this->_client->send($message);
		
		// Error
		if ($xmlrpcdoc->faultCode()){
			$this->_errorNum = $xmlrpcdoc->faultCode();
			$this->_errorMsg = JText::_('ERROR_LOADROWLIST_XMLRPC').": ".$xmlrpcdoc->faultString();

			if ($this->_debug) {
				JError::raiseError(500, 'JDatabaseXmlRpc::loadRowList: '.$this->_errorNum.' - '.$this->_errorMsg );
			}
			return $ret;
		}
		
		$ret = $xmlrpcdoc->value();
		
		return $ret;
	}

	/**
	* Load a list of database rows (numeric column indexing)
	*
	* @access public
	* @param string The field name of a primary key
	* @return array If <var>key</var> is empty as sequential list of returned records.
	* If <var>key</var> is not empty then the returned array is indexed by the value
	* the database key.  Returns <var>null</var> if the query fails.
	*/
	function loadRowList( $key=null )
	{
		$array = array();
		// Preparing for querying the xmlrpc
		$parameters = array (
						new xmlrpcval($this->_username, "string"),
						new xmlrpcval($this->_password, "string"),
						new xmlrpcval($this->_sql, "string"),
						new xmlrpcval($key, "string"),
					);
		$message = new xmlrpcmsg("sync.loadRowList", $parameters);
		$xmlrpcdoc = $this->_client->send($message);
		
		// Error
		if ($xmlrpcdoc->faultCode()){
			$this->_errorNum = $xmlrpcdoc->faultCode();
			$this->_errorMsg = JText::_('ERROR_LOADROWLIST_XMLRPC').": ".$xmlrpcdoc->faultString();

			if ($this->_debug) {
				JError::raiseError(500, 'JDatabaseXmlRpc::loadRowList: '.$this->_errorNum.' - '.$this->_errorMsg );
			}
			return array();
		}
		
		$array = $xmlrpcdoc->value();
		
		return $array;
	}

	/**
	 * Inserts a row into a table based on an objects properties
	 *
	 * @access	public
	 * @param	string	The name of the table
	 * @param	object	An object whose properties match table fields
	 * @param	string	The name of the primary key. If provided the object property is updated.
	 */
	function insertObject( $table, &$object, $keyName = NULL )
	{
		$fmtsql = 'INSERT INTO '.$this->nameQuote($table).' ( %s ) VALUES ( %s ) ';
		$fields = array();
		foreach (get_object_vars( $object ) as $k => $v) {
			if (is_array($v) or is_object($v) or $v === NULL) {
				continue;
			}
			if ($k[0] == '_') { // internal field
				continue;
			}
			$fields[] = $this->nameQuote( $k );
			$values[] = $this->isQuoted( $k ) ? $this->Quote( $v ) : (int) $v;
			
		}
		$this->setQuery( sprintf( $fmtsql, implode( ",", $fields ) ,  implode( ",", $values ) ) );
		if (!$this->query()) {
			return false;
		}
		$id = $this->insertid();
		if ($keyName && $id) {
			$object->$keyName = $id;
		}
		return true;
	}

	/**
	 * Description
	 *
	 * @access public
	 * @param [type] $updateNulls
	 */
	function updateObject( $table, &$object, $keyName, $updateNulls=true )
	{
		$fmtsql = 'UPDATE '.$this->nameQuote($table).' SET %s WHERE %s';
		$tmp = array();
		foreach (get_object_vars( $object ) as $k => $v)
		{
			if( is_array($v) or is_object($v) or $k[0] == '_' ) { // internal or NA field
				continue;
			}
			if( $k == $keyName ) { // PK not to be updated
				$where = $keyName . '=' . $this->Quote( $v );
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
				$val = $this->isQuoted( $k ) ? $this->Quote( $v ) : (int) $v;
			}
			$tmp[] = $this->nameQuote( $k ) . '=' . $val;
		}
		$this->setQuery( sprintf( $fmtsql, implode( ",", $tmp ) , $where ) );
		return $this->query();
	}

	/**
	 * Description
	 *
	 * @access public
	 */
	function insertid()
	{
		return $this->_insertid;
	}

	/**
	 * Description
	 *
	 * @access public
	 */
	function getVersion()
	{
		// Preparing for querying the xmlrpc
		$parameters = array (
						new xmlrpcval($this->_username, "string"),
						new xmlrpcval($this->_password, "string"),
					);
		$message = new xmlrpcmsg("sync.getVersion", $parameters);
		$xmlrpcdoc = $this->_client->send($message);
		
		// Error
		if ($xmlrpcdoc->faultCode()){
			$this->_errorNum = $xmlrpcdoc->faultCode();
			$this->_errorMsg = JText::_('ERROR_GETVERSION_XMLRPC').": ".$xmlrpcdoc->faultString();

			if ($this->_debug) {
				JError::raiseError(500, 'JDatabaseXmlRpc::getVersion: '.$this->_errorNum.' - '.$this->_errorMsg );
			}
			return '';
		}
		
		$ret = $xmlrpcdoc->value();
		return $ret;
	}

	/**
	 * Assumes database collation in use by sampling one text field in one table
	 *
	 * @access	public
	 * @return string Collation in use
	 */
	function getCollation ()
	{
		if ( $this->hasUTF() ) {
			$this->setQuery( 'SHOW FULL COLUMNS FROM #__content' );
			$array = $this->loadAssocList();
			return $array['4']['Collation'];
		} else {
			return "N/A (mySQL < 4.1.2)";
		}
	}

	/**
	 * Description
	 *
	 * @access	public
	 * @return array A list of all the tables in the database
	 */
	function getTableList()
	{
		$this->setQuery( 'SHOW TABLES' );
		return $this->loadResultArray();
	}

	/**
	 * Shows the CREATE TABLE statement that creates the given tables
	 *
	 * @access	public
	 * @param 	array|string 	A table name or a list of table names
	 * @return 	array A list the create SQL for the tables
	 */
	function getTableCreate( $tables )
	{
		settype($tables, 'array'); //force to array
		$result = array();

		foreach ($tables as $tblval) {
			$this->setQuery( 'SHOW CREATE table ' . $this->getEscaped( $tblval ) );
			$rows = $this->loadRowList();
			foreach ($rows as $row) {
				$result[$tblval] = $row[1];
			}
		}

		return $result;
	}

	/**
	 * Retrieves information about the given tables
	 *
	 * @access	public
	 * @param 	array|string 	A table name or a list of table names
	 * @param	boolean			Only return field types, default true
	 * @return	array An array of fields by table
	 */
	function getTableFields( $tables, $typeonly = true )
	{
		settype($tables, 'array'); //force to array
		$result = array();

		foreach ($tables as $tblval)
		{
			$this->setQuery( 'SHOW FIELDS FROM ' . $tblval );
			$fields = $this->loadObjectList();

			if($typeonly)
			{
				foreach ($fields as $field) {
					$result[$tblval][$field->Field] = preg_replace("/[(0-9)]/",'', $field->Type );
				}
			}
			else
			{
				foreach ($fields as $field) {
					$result[$tblval][$field->Field] = $field;
				}
			}
		}

		return $result;
	}
}
