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
class Idle extends rcp_liveplugin
{
	public  $title		= 'Idle';
	public  $author		= 'hal.ko.sascha';
	public  $version	= '4.0.3.6';
	private $idlers		= array();
	private $playerkick	= 3;
	private $speckick	= 6;
	private $message	= false;

	public function onLoadSettings($settings)
	{
		$this->playerkick	= (int) $settings->playerkick;
		$this->speckick		= (int) $settings->speckick;
		$this->message		= (string) $settings->message;
	}

	public function onBeginChallenge()
	{
		if(empty(Core::getObject('players')->players)) return;
		foreach(Core::getObject('players')->players AS $player)
		{
			if(!array_key_exists($this->id, $player->cdata) || $player->Flags['IsServer']) continue;
			if(($player->SpectatorStatus['Spectator'] && $player->cdata[$this->id]['counter'] >= $this->speckick) || (!$player->SpectatorStatus['Spectator'] && $player->cdata[$this->id]['counter'] >= $this->playerkick)) {
				Core::getObject('gbx')->addCall('Kick', $player->Login, $this->message);
				Core::getObject('gbx')->addCall('SendNotice', 'Idlekick for '.$player->NickName, '', 2);
				Core::getObject('live')->addMsg('Idlekick for '.$player->NickName);
			} else {
				++$player->cdata[$this->id]['counter'];
			}
		}
	}

	public function onPlayerConnect($params)
	{
		$this->onResetIdler($params[0]);
	}

	public function onPlayerFinish($params)
	{
		if(!$params[2]) return;
		$this->onResetIdler($params[1]);
	}

	public function onPlayerCheckpoint($params)
	{
		if(!$params[2]) return;
		$this->onResetIdler($params[1]);
	}

	public function onPlayerChat($params)
	{
		if(!$params[2]) return;
		$this->onResetIdler($params[1]);
	}

	public function onResetIdler($login)
	{
		$player = Core::getObject('players')->get($login);
		if(Core::getObject('players')->check($player)) {
			$player->cdata[$this->id]['counter'] = 0;
		}
	}
}
?>