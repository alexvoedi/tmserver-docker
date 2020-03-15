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
	* Executes all added server calls
	*
	* This function also calls the onExec callback
	* @param mixed $actionHandler
	* @author hal.sascha
	*/
	public function Exec($actionHandler = false)
	{
		Plugins::triggerEvent('onExec');

		//Trigger actions
		if(is_array($actionHandler)) {
			$action =  $actionHandler[0];
			$obj    =& $actionHandler[1];
			if(Core::getObject('session')->checkPerm($obj->apermissions[$action])) {
				if(method_exists($obj, $action)) $obj->$action();
			}
		} else {
			foreach(Plugins::getAllPlugins() as $name => $obj)
			{
				if(Core::getObject('session')->checkPerm(Plugins::getPlugin($name)->apermissions[$actionHandler])) {
					if(method_exists($obj, $actionHandler)) Plugins::getPlugin($name)->$actionHandler();
				}
			}
		}

		//Execute calls
		if(Core::getObject('gbx')->multiquery()) {
			Core::getObject('gbx')->getResponse();
		}
	}


	/**
	* Adds a new action to the system
	*
	* @param string $action
	* @param mixed $stc1
	* @param mixed $stc2
	* @param mixed $stc3
	* @param mixed $stc4
	* @author hal.sascha
	*/
	public function add($action, $stc1 = null, $stc2 = null, $stc3 = null, $stc4 = null)
	{
		Core::getObject('gbx')->addCall($action, $stc1, $stc2, $stc3, $stc4);
		Core::getObject('activity')->add(Core::getObject('session')->admin->username, $action);
	}
}
?>