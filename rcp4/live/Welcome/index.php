<?php
/**
* remoteCP 4
* ütf-8 release
*
* @package remoteCPlive
* @author hal.sascha
* @copyright (c) 2006-2009
* @version 4.0.3.6
*/
class Welcome extends rcp_liveplugin
{
	public  $title		= 'Welcome';
	public  $author		= 'hal.ko.sascha';
	public  $version	= '4.0.3.6';
	private $messages	= array();

	public function onLoadSettings($settings)
	{
		$this->messages = array();
		$this->messages['welcome']		= (string) $settings->messages->welcome;
		$this->messages['adminjoin']	= (string) $settings->messages->connect->admin;
		$this->messages['playerjoin']	= (string) $settings->messages->connect->player;
		$this->messages['adminleft']	= (string) $settings->messages->disconnect->admin;
		$this->messages['playerleft']	= (string) $settings->messages->disconnect->player;
	}

	public function onPlayerConnect($params)
	{
		$player = Core::getObject('players')->get($params[0]);
		if(!Core::getObject('players')->check($player)) return;

		Core::getObject('chat')->send(sprintf($this->messages['welcome'], Core::getObject('session')->server->name, Core::getSetting('version')), false, $params[0]);
		if(Core::getObject('live')->isAdmin($player->Login,true)) {
			$admins = Core::getObject('live')->getAdmins();
			Core::getObject('chat')->send(sprintf($this->messages['adminjoin'], $this->getGroupName($admins[$player->Login]->group), $player->NickName, implode('!df! > !si!', $player->Path)));
		} else {
			Core::getObject('chat')->send(sprintf($this->messages['playerjoin'], $player->NickName, implode('!df! > !si!', $player->Path)));
		}
	}

	public function onPlayerDisconnect($params)
	{
		$player = Core::getObject('players')->get($params[0]);
		if(!Core::getObject('players')->check($player)) return;

		if(Core::getObject('live')->isAdmin($player->Login,true)) {
			$admins = Core::getObject('live')->getAdmins();
			Core::getObject('chat')->send(sprintf($this->messages['adminleft'], $this->getGroupName($admins[$player->Login]->group), $player->NickName, $player->getPlaytime($player->Connected)));
		} else {
			Core::getObject('chat')->send(sprintf($this->messages['playerleft'], $player->NickName, $player->getPlaytime($player->Connected)));
		}
	}

	private function getGroupName($groupid)
	{
		//Get the group name
		$group = Core::getObject('session')->groups->xpath("/groups/group[id='{$groupid}']");
		if($group[0]) {
			return (string) $group[0]->name;
		}
		return 'invalid group';
	}
}
?>