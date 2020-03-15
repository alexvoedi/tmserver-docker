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
class rcp_bills
{
	/**
	 * Array of bill objects
	 * @access public
	 */
	public $bills = array();

	/**
	* Adds a bill to the billlist
	*
	* no informations available
	* @param int $id
	* @param string $login
	* @param int $coppers
	* @param string $reason
	* @param string $msg
	* @param string $callback
	* @param mixed $params
	* @param boolean $display
	* @author hal.sascha
	*/
	public function add($id, $login, $coppers, $reason, $msg, $successcallback, $errorcallback, $params, $display = false)
	{
		$this->bills[$id] = new rcp_bill($id, $login, $coppers, $reason, $msg, $successcallback, $errorcallback, $params, $display);
	}

	/**
	* Returns a bill-object
	*
	* @param int $id
	* @author hal.sascha
	*/
	public function get($id)
	{
		return $this->bills[$id];
	}

	/**
	* Removes a bill from the billlist
	*
	* @param int $id
	* @author hal.sascha
	*/
	public function remove($id)
	{
		if(isset($this->bills[$id])) {
			$this->bills[$id] = null;
			unset($this->bills[$id]);
			return true;
		}
		return false;
	}
}

class rcp_bill
{
	public $id;
	public $login;
	public $coppers;
	public $reason;
	public $msg;
	public $successcallback;
	public $errorcallback;
	public $params;
	public $display;

	public function __construct($id, $login, $coppers, $reason, $msg, $successcallback, $errorcallback, $params, $display)
	{
		$this->id		= $id;
		$this->login	= $login;
		$this->coppers	= $coppers;
		$this->reason	= $reason;
		$this->msg		= $msg;
		$this->successcallback = $successcallback;
		$this->errorcallback = $errorcallback;
		$this->params	= $params;
		$this->display	= $display;
	}
}
?>