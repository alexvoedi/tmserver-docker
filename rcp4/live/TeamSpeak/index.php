<?php
/**
* remoteCP 4
* ütf-8 release
*
* @package remoteCPlive
* @author |Black|Co2NTRA & Th3_Darkness
* @thanks to hal.sascha for his help and the old TS Plugin
* @copyright (c) 2006-2009
* @version 4.0.3.6
*/

class TeamSpeak extends rcp_liveplugin
{
	public  $title		= 'TeamSpeak';
	public  $author		= '|Black|Co2NTRA & Th3_Darkness';
	public  $version	= '4.0.3.6 v1.0';

	public function onLoadSettings($settings)
	{
		$this->tsip				= (string) $settings->ip;
		$this->tslogin			= (int) $settings->login->enabled;
		$this->tschannel		= (int) $settings->channel->enabled;
		$this->tschannelname	= (string) $settings->channel->cname;
		$this->tssubchannel		= (int) $settings->subchannel->enabled;
		$this->tssubchannelname	= (string) $settings->subchannel->scname;
	}

	public function onNewPlayer($player)
	{
		$ip = '?ip='.$this->tsip;
		if($this->tslogin == 1) {
				$login = $player->Login;
				$login = '?nickname='.$login;
		}

		if($this->tschannel == 1) {
			$channel = $this->tschannelname;
			$channel = '?channel='.$channel;

			if($this->tssubchannel == 1) {
				$subchannel = $this->tssubchannelname;
				$subchannel = '?subchannel='.$subchannel;
			}
		}

		$window = $player->ManiaFWK->addWindow('MLTeamSpeak', '', -63.5, 48, 5);
		if($window) {
			$window->setOption('static', true);
			$window->setOption('close', false);
			$window->setOption('header', false);
			$window->setOption('bg', false);
			$window->setOption('display', 'score');

			$window->Reset();
			$window->Line();
			$window->Cell('',array(5,5),null,array(
				'image' => 'http://img205.imageshack.us/img205/7472/teamspeakbutton3on5.png',
				'imagefocus' => 'http://img231.imageshack.us/img231/9222/teamspeakbutton2mf9.png',
				'url' => 'http://www.tmbase.de/ts2redirect.php'.$ip .$login .$channel .$subchannel
			));
		}
	}
}
?>