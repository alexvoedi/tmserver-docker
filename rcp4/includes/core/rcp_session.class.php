<?php
/**
* remoteCP 4
* ütf-8 release
*
* @package remoteCP
* @author hal.sascha
* @copyright (c) 2006-2009
* @version 4.0.3.6
*/
class rcp_session
{
	public  $servers;
	public  $admins;
	public  $groups;
	public  $admin;
	public  $server;
	private $isLogin;

	public function __construct()
	{
		//Load data
		$this->servers = $this->loadXML(Core::getSetting('xmlpath').'servers.xml');
		$this->admins  = $this->loadXML(Core::getSetting('xmlpath').'admins.xml');
		$this->groups  = $this->loadXML(Core::getSetting('xmlpath').'groups.xml');
		$this->isLogin = false;

		//Handle session
		if(!@session_start()) {
			trigger_error('Session error, can not start session', E_USER_WARNING);
		}

		//Change server
		if(array_key_exists('changeserver', $_REQUEST)) {
			$this->updateServer($_REQUEST['serverid']);
		}

		//Logout
		if(array_key_exists('logout', $_REQUEST) || array_key_exists('refused', $_REQUEST)) {
			$this->doLogout();
		}

		//Prepare Login
		$serverid = array_key_exists('serverid', $_REQUEST) ? $_REQUEST['serverid'] : false;
		if(array_key_exists('autologin', $_REQUEST)) {
			$username = 'Guest';
			$password = 'Guest';
		} elseif(Core::getSetting('live')) {
			$username = 'rcplive';
			$password = 'rcplivepsw';
		} else {
			$username = array_key_exists('username', $_REQUEST) ? $_REQUEST['username'] : false;
			$password = array_key_exists('password', $_REQUEST) ? $_REQUEST['password'] : false;
		}

		//Block rcplive login on webinterface
		if(!Core::getSetting('live') && $username == 'rcplive') {
			trigger_error('Login error, account rcplive is blocked for webinterface login', E_USER_WARNING);
			$username = false;
			$password = false;
		}

		//Create data objects
		$this->admin  = new rcp_admin();
		$this->server = new rcp_server();

		if($serverid !== false && $username !== false && $password !== false) {
			if($this->updateAdmin($username, $password, $serverid)) {
				$this->updateServer($serverid);
			}

			if(!session_id()) {
				trigger_error('Session error, impossible to create session', E_USER_WARNING);
			}
		}

		//Login
		$this->doLogin();
	}

	/**
	* Updates the admin session
	*
	* @param string $username
	* @param string $password
	* @param string $serverid
	* @author hal.sascha
	*/
	public function updateAdmin($username, $password, $serverid)
	{
		$admin = $this->admins->xpath("//admins/admin[./username='{$username}']/servers/server[attribute::id='{$serverid}']/../..");
		if($admin[0]) {
			if(stristr((string) $admin[0]->active, 'true')) {
				if((string) $admin[0]->password == md5($password)) {
					$this->initVar('id'			, (string) $admin[0]->id);
					$this->initVar('username'	, (string) $admin[0]->username);
					$this->initVar('password'	, (string) $admin[0]->password);
					Core::getObject('activity')->add((string) $admin[0]->username, 'login');
					$this->isLogin = true;
					return true;
				} else {
					trigger_error('Error, password invalid');
				}
			} else {
				trigger_error('Error, user inactive');
			}
		} else {
			trigger_error('Error, user not found');
		}
		return false;
	}

	/**
	* Updates the server session
	*
	* @param string $serverid
	* @author hal.sascha
	*/
	public function updateServer($serverid)
	{
		$server = $this->servers->xpath("//servers/server[./id='{$serverid}']");
		if($server[0]) {
			$this->initVar('serverid', (string) $server[0]->id);
			return true;
		}
		trigger_error('Error, server not found');
		return false;
	}

	/**
	* Logs into the remoteCP core system and creates admin and server data
	*
	* @author hal.sascha
	*/
	public function doLogin()
	{
		if(!array_key_exists('username', $_SESSION) || !array_key_exists('password', $_SESSION) || !array_key_exists('serverid', $_SESSION))
			return;

		$admin = $this->admins->xpath("//admins/admin[./username='{$_SESSION['username']}']/servers/server[attribute::id='{$_SESSION['serverid']}']/../..");
		if($admin[0]) {
			if(stristr((string) $admin[0]->active, 'true')) {
				$this->admin->updateData($admin[0]);

				$server = $this->servers->xpath("/servers/server[id='{$_SESSION['serverid']}']");
				if($server[0]) {
					$this->server->updateData($server[0]);
					$this->admin->setLogged();
				}
			} else {
				trigger_error('Error, user inactive');
			}
		}
	}

	/**
	* Logs out from the remoteCP core system and removes the sessions and all data
	*
	* @author hal.sascha
	*/
	public function doLogout()
	{
		Core::getObject('activity')->add($_SESSION['username'], 'logout');
		@session_unset();
		@session_destroy();
		if(session_id()) {
			trigger_error('Error, can not destroy session', E_USER_WARNING); 
		}
	}

	/**
	* Updates a session value
	*
	* @param string $var
	* @param mixed $value
	* @author hal.sascha
	*/
	public function initVar($var, $value)
	{
		if($var) {
			$_SESSION[$var] = $value;
			if(!isset($GLOBALS[$var])) {
				$GLOBALS[$var] = $value;
			}
			return 1;
		}
		return 0;
	}

	/**
	* Checks the permission for the current admin (current session)
	*
	* $value specifies the permission level
	* @param string $value
	* @author hal.sascha
	*/
	public function checkPerm($value)
	{
		$group = $this->groups->xpath("/groups/group[id='{$this->admin->group}']");
		if($group[0]) {
			if(isset($group[0]->permissions->{$value}) || is_null($value)) {
				if(is_null($value)) {
					return true;
				} elseif(stristr((string) $group[0]->permissions->{$value}, 'true')) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	* Loads xml data from a file
	*
	* @param string $file
	* @author hal.sascha
	*/
	public function loadXML($file)
	{
		try {
			$return = new SimpleXMLElement($file, null, true);
		} catch(Exception $e) {
			$return = false;
			trigger_error('Failed to load XML file: '. $file, E_USER_ERROR);
		}
		return $return;
	}

	/**
	* Saves xml data to a file
	*
	* @param string $xml
	* @param string $file
	* @author hal.sascha
	*/
	public function saveXML($xml, $file)
	{
		$doc = new DOMDocument('1.0');
		$doc->preserveWhiteSpace = false;
		$doc->loadXML($xml);
		$doc->formatOutput = true;
		$doc->encoding = 'utf-8';
		$doc->save($file);
	}
}

class rcp_admin
{
	public $id;
	public $servers;
	public $username;
	public $password;
	public $nocode;
	public $tmaccount;
	public $language;
	public $style;
	public $group;
	private $logged;

	public function __construct()
	{
		$this->logged	= false;
		$this->style	= false;
		$this->language	= false;
	}

	public function updateData(SimpleXMLElement $admin)
	{
		$this->id			= (string) $admin->id;
		$this->servers		= $admin->servers;
		$this->username		= (string) $admin->username;
		$this->password		= (string) $admin->password;
		$this->nocode		= stristr((string) $admin->nocode, 'true')		? true	: false;
		$this->tmaccount	= stristr((string) $admin->tmaccount, 'false')	? false	: (string) $admin->tmaccount;
		$this->language		= (string) $admin->language;
		$this->style		= (string) $admin->style;

		//Get group ID for the current server (locked to the currently available session-serverID
		$group = $admin->xpath("//admin[./username='{$this->username}']/servers/server[attribute::id='{$_SESSION['serverid']}']");
		$this->group		= (string) $group[0]['group'];
	}

	public function setLogged()
	{
		$this->logged = true;
	}

	public function isLogged()
	{
		return $this->logged;
	}

	public function getStyle()
	{
		return ($this->style) ? $this->style : Core::getSetting('defstyle');
	}

	public function getLanguage()
	{
		return ($this->language) ? $this->language : Core::getSetting('deflanguage');
	}
}

class rcp_server
{
	public $id;
	public $login;
	public $name;
	public $filepath;
	public $connection;
	public $ftp;
	public $sql;
	public $lists;

	public function __construct()
	{
		$this->settingset = 'default';
	}

	public function updateData(SimpleXMLElement $server)
	{
		$this->id			= (string) $server->id;
		$this->login		= (string) $server->login;
		$this->name			= (string) $server->name;
		$this->filepath		= trim((string) $server->filepath);
		$this->settingset	= trim((string) $server->settingset);
		$this->settingset	= empty($this->settingset) ? 'default' : $this->settingset;
		$this->connection = array(
			'host'			=> (string) $server->connection->host,
			'port'			=> (string) $server->connection->port,
			'password'		=> (string) $server->connection->password,
			'communitycode'	=> (string) $server->connection->communitycode
		);
		$this->ftp = array(
			'enabled'		=> stristr((string) $server->ftp['enabled'], 'true') ? true : false,
			'host'			=> (string) $server->ftp->host,
			'port'			=> (string) $server->ftp->port ? (string) $server->ftp->port : null,
			'username'		=> (string) $server->ftp->username,
			'password'		=> (string) $server->ftp->password,
			'path'			=> (string) $server->ftp->path
		);
		$this->sql = array(
			'enabled'		=> stristr((string) $server->sql['enabled'], 'true') ? true : false,
			'dsn'			=> (string) $server->sql->dsn,
			'username'		=> (string) $server->sql->username,
			'password'		=> (string) $server->sql->password
		);
		$this->lists = array(
			'guestlist'		=> (string) $server->lists->guestlist,
			'blacklist'		=> (string) $server->lists->blacklist
		);
	}
}
?>