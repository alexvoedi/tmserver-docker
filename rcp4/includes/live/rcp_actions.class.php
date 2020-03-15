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
class rcp_actions
{
	/**
	* Executes all calls added by addCall/addAction
	*
	* This function also calls the onExec callback
	* @author hal.sascha
	*/
	public function Exec()
	{
		Plugins::triggerEvent('onExec');
		if(Core::getObject('gbx')->multiquery()) {
			Core::getObject('gbx')->getResponse();
		}
	}

	/**
	* Adds a Action to the remoteCP action queue
	*
	* Checks permissions and add a XML-RPC call to the multiquery.
	* @param string $action
	* @param string $login
	* @param mixed $stc1
	* @param mixed $stc2
	* @param mixed $stc3
	* @param mixed $stc4
	* @author hal.sascha
	*/
	public function add($action, $login = false, $stc1 = null, $stc2 = null, $stc3 = null, $stc4 = null)
	{
		if(is_array($action) && Core::getObject('live')->isAdmin($login) && Core::getObject('live')->checkPerm($action[0]->apermissions[$action[1]], $login, $action[1])) {
			Core::getObject('gbx')->addCall($action[1],$stc1,$stc2,$stc3,$stc4);
			Core::getObject('activity')->add($login, "[{$action[0]->title}] ".$action[1]);
		}
	}
}
?>