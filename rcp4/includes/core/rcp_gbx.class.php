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
require_once './includes/core/GbxRemote.inc.php';
class rcp_gbx EXTENDS IXR_Client_Gbx
{
	private $calls = array();
	private $suppressError = false;

	/**
	* Creates a XML-RPC connection and authenticates with username and password
	*
	* @author hal.sascha
	*/
	public function newConnection()
	{
		if($this->InitWithIp(Core::getObject('session')->server->connection['host'],Core::getObject('session')->server->connection['port'])) {
			if($this->query('Authenticate','SuperAdmin',Core::getObject('session')->server->connection['password'])) {
				return true;
			}
		}
		return false;
	}

	/**
	* Checks if the socket connection still exists and reconnects
	*
	* @author hal.sascha
	*/
	public function checkConnection()
	{
		return ($this->socket) ? true : false;
	}

	/**
	* Checks if the socket connection still exists and reconnects, if connection was lost
	*
	* @author hal.sascha
	*/
	public function pingConnection()
	{
		if($this->checkConnection()) return true;

		//Try reconnect
		if($this->newConnection()) {
			return true;
		}
		return false;
	}

	/**
	* Adds a call to the XML-RPC multiquery
	*
	* @param mixed unlimited
	* @author hal.sascha
	*/
	public function addCall()
	{
		$args   = func_get_args();
		$method = array_shift($args);
		foreach($args as $key => $value)
		{
			if(is_null($value)) unset($args[$key]);
		}
		$this->calls[] = array('methodName' => $method, 'params' => $args);
		return (count($this->calls) - 1);
	}

	/**
	* Queries multible XML-RPC calls at once
	*
	* @author hal.sascha
	*/
	public function multiquery()
	{
		if(empty($this->calls))
			return false;

		$result = parent::query('system.multicall', $this->calls);
		$this->calls = array();
		return $result;
	}

	/**
	* Calls the default query method, but does some basic error handling
	*
	* @author hal.sascha
	*/
	public function query()
	{
		$args = func_get_args();
		$parent_class = get_parent_class($this);
		$return = call_user_func_array(array($parent_class, 'query'), $args);
		$code = $this->getErrorCode();

		//Error handling
		if($this->suppressError) {
			$this->suppressError = false;
		} else {
			if(!$return && $code && $code != -1000) {
				trigger_error($this->getErrorMessage()." [{$code}]");
			}
		}

		return $return;
	}

	/**
	* Next query will not (if it fails) output any error message
	*
	* @param mixed unlimited
	* @author hal.sascha
	*/
	public function suppressNextError()
	{
		$this->suppressError = true;
	}

	/**
	* Flushes the Callbacks
	*
	* @author hal.sascha
	*/
	public function flushCB()
	{
		flush();
		ob_flush();
	}

	/**
	* Enables callback support for the trackmania dedicated server
	*
	* @author hal.sascha
	*/
	public function enableCB()
	{
		$this->query('EnableCallbacks', true);
	}
}
?>