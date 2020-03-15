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
class rcp_live
{
	/**
	 * Array of admin objects
	 * @access private
	 */
	private $admins = array();

	/**
	 * Array of message strings
	 * @access public
	 */
	private $msgs = array();

	/**
	 * Array of container status values
	 * @access private
	 */
	private $containers = array();

	/**
	* StartUp constructor, loads the admin data
	*
	* @author hal.sascha
	*/
	public function __construct()
	{
		$this->updateSession(false);
	}

	/**
	* Updates the admins array with the current data from the admins.xml
	*
	* @param boolean $reload
	* @author hal.sascha
	*/
	public function updateSession($reload = false)
	{
		if($reload) {
			Core::getObject('session')->servers	= Core::getObject('session')->loadXML(Core::getSetting('xmlpath').'servers.xml');
			Core::getObject('session')->admins	= Core::getObject('session')->loadXML(Core::getSetting('xmlpath').'admins.xml');
			Core::getObject('session')->groups	= Core::getObject('session')->loadXML(Core::getSetting('xmlpath').'groups.xml');

			//Update plugin settings
			foreach(Plugins::getAllPlugins() AS $plugin)
			{
				Plugins::loadSettings($plugin->id);
			}
		}

		//Create remoteCP-Live admin array
		$this->admins = array();
		foreach(Core::getObject('session')->admins->children() as $node)
		{
			$admin = Core::getObject('session')->admins->xpath("//admins/admin[./tmaccount='". (string) $node->tmaccount ."']/servers/server[attribute::id='". Core::getObject('session')->server->id ."']/../..");
			if($admin[0]) {
				if(stristr((string) $node->active, 'true')) {
					$this->admins[(string) $node->tmaccount] = new rcp_admin();
					$this->admins[(string) $node->tmaccount]->updateData($node);
				}
			}
		}
	}

	/**
	* Returns the admins array
	*
	* @author hal.sascha
	*/
	public function getAdmins()
	{
		return $this->admins;
	}

	/**
	* Checks if player has admin permissions
	*
	* @param string $login
	* @author hal.sascha
	*/
	public function isAdmin($login, $nostatuscheck = false, $return = false)
	{
		if(in_array($login, array_keys(Core::getObject('live')->getAdmins()))) {
			if($nostatuscheck) return true;
			$player = Core::getObject('players')->get($login);
			if(Core::getObject('players')->check($player)) {
				if($player->getAdminStatus()) {
					return true;
				} else {
					Core::getObject('chat')->send('Can not validate you as admin, use the /su command to verify your admin account for this session', false, $login);
					return false;
				}
			} else {
				return false;
			}
		}
		return $return;
	}

	/**
	* Checks if a admin exists and has the permissionlevel
	*
	* @param string $value
	* @param string $login
	* @author hal.sascha
	*/
	public function checkPerm($value, $login, $silent = true)
	{
		if(array_key_exists($login, $this->admins)) {
			$group = Core::getObject('session')->groups->xpath("//groups/group[./id='{$this->admins[$login]->group}']");
			if($group[0]) {
				if(isset($group[0]->permissions->{$value}) || is_null($value)) {
					if(is_null($value)) {
						return true;
					} elseif(stristr((string) $group[0]->permissions->{$value}, 'true')) {
						return true;
					}
				}
			}

			//Drop chat message if no permission (only non silent mode)
			if($silent !== true) {
				Core::getObject('chat')->send('!hl!'. $silent .'!df!: no permission for this operation', false, $login);
			}
		}
		return false;
	}

	/**
	* Limits the execution time of each main-program-loop
	*
	* @author hal.sascha
	*/
	public function LoopLimiter()
	{
		static $startTime	= true;
		static $currentTime	= true;
		if($startTime === true) {
			$startTime = floor(microtime(true)*1000);
		}

		//Set end time and calc looping time
		$currentTime = floor(microtime(true)*1000);
		$loopTime = $currentTime - $startTime;

		//Limit the current loop
		if(Core::getSetting('idle')) {
			$sleepTime = 2000;
		} else {
			$sleepTime = ($loopTime > 90) ? 20 : 90;
		}
		usleep($sleepTime*1000);

		//Set start time for upcoming loop
		$startTime = floor(microtime(true)*1000);
	}

	/**
	* Adds a message to the global messagescontainer or chat
	*
	* @author hal.sascha
	*/
	public function addMsg($msg)
	{
		if(Core::getSetting('messagesui')) {
			$this->msgs[] = $msg;
			if(count($this->msgs) > 15) {
				array_shift($this->msgs);
			}
			Core::getObject('manialink')->updateContainer('messages');
		} else {
			Core::getObject('chat')->send($msg);
		}
	}

	/**
	* Returns a array of all currently available messages
	*
	* @author hal.sascha
	*/
	public function getMsgs()
	{
		if(!Core::getSetting('messagesui')) return;
		return $this->msgs;
	}

	/**
	* Resets the messages array
	*
	* @author hal.sascha
	*/
	public function resetMsgs()
	{
		if(!Core::getSetting('messagesui')) return;
		$this->msgs = array();
	}
}
?>