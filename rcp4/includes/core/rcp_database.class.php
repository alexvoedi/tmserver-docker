<?php
/**
* hal-ko tryst-cms
* ütf-8 release
*
* @package tryst-cms
* @author hal.sascha
* Modified for remoteCP usage
*/
class rcp_database
{
	/**
	 * Array of database connection handles
	 */
	private $connections = array();

	/**
	 * Tells the DB object which connection to use
	 * setActiveConnection($id) allows us to change this
	 */
	private $activeConnection = false;

	/**
	 * Create a new database connection
	 * @param String database hostname
	 * @param String database username
	 * @param String database password
	 * @param String database we are using
	 * @return mixed the id of the new connection
	 */
	public function newConnection($id, $dsn, $username, $password)
	{
		// Get dsn type
		$type = explode(':', $dsn);
		$type = $type[0];

		// Connect
		try {
			$this->connections[$id] = array(
				'handle'	=> new PDO($dsn, $username, $password),
				'dsn'		=> $dsn,
				'username'	=> $username,
				'password'	=> $password,
				'type'		=> $type
			);
		} catch(PDOException $e) {
			trigger_error($e->getMessage(), E_USER_WARNING);
			return false;
		}

		$this->activeConnection = $id;
		$db = $this->getConnection();
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		// UTF8-ish the connection
		$db->exec("SET NAMES 'utf8'");
		$db->exec("SET CHARACTER SET 'utf8'");
		return $this->activeConnection;
	}

	/**
	 * Returns the currently active PDO connection
	 * @return object
	 */
	public function getConnection()
	{
		return $this->connections[$this->activeConnection]['handle'];
	}

	/**
	 * Returns the current status of a PDO connection
	 * @return boolean
	 */
	public function checkConnection()
	{
		return ($this->connections[$this->activeConnection]['handle']) ? true : false;
	}

	/**
	 * Pings the current PDO connection, reconnects if needed
	 * @return boolean
	 */
	public function pingConnection()
	{
		// Check if connection allready exists
		if($this->checkConnection()) return true;

		// Try reconnect
		$this->closeConnection();
		if($this->newConnection(
			$this->activeConnection,
			$this->connections[$this->activeConnection]['dsn'],
			$this->connections[$this->activeConnection]['username'],
			$this->connections[$this->activeConnection]['password']
		)) {
			return true;
		}

		return false;
	}

	/**
	 * Close the active connection
	 * @return void
	 */
	public function closeConnection()
	{
		$this->connections[$this->activeConnection]['handle'] = null;
		unset($this->connections[$this->activeConnection]);
	}

	/**
	 * Change which database connection is actively used for the next operation
	 * @param int the new connection id
	 * @return void
	 */
	public function setActiveConnection($id)
	{
		$this->activeConnection = $id;
	}

	/**
	 * Deconstruct the object
	 * close all of the database connections
	 */
	public function __deconstruct()
	{
		foreach($this->connections as $connection)
		{
			$connection['handle'] = null;
		}
	}

	/**
	 * Imports a sql file into the database
	 * @param string $plugin
	 */
	public function fileImport($plugin, $pluginpath = false)
	{
		// Check connection
		if(!$this->checkConnection()) return;
		$db = $this->getConnection();

		// Get pluginpath
		$pluginpath = ($pluginpath === false) ? Core::getSetting('pluginpath') : $pluginpath;

		// Get type
		$type = $this->connections[$this->activeConnection]['type'];

		// Load file
		$import = file_get_contents($pluginpath.'/'.$plugin.'/'.$type.'_'.$plugin.'.sql');

		// Remove comments
		$import = preg_replace("%/\*(.*)\*/%Us", '', $import);
		$import = preg_replace("%^--(.*)\n%mU", '', $import);
		$import = preg_replace("%^$\n%mU", '', $import);

		// Get query chunks
		$import = explode (";", $import);

		// Exec queries
		foreach($import as $query)
		{
			if(!empty($query)) {
				$db->exec($query);
			}
		}
	}
}
?>