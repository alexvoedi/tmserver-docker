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
class TALimit extends rcp_liveplugin
{
	public  $title			= 'TALimit';
	public  $author			= 'hal.ko.sascha';
	public  $version		= '4.0.3.6';
	private $limit			= 60000;
	private $allgamemodes	= false;
	private $limitmulti		= 7;
	private $limitmax		= 10;
	private $limitmin		= 3;
   
	public function onLoadSettings($settings)
	{
		$this->allgamemodes	= (int) $settings->allgamemodes ? true : false;
		$this->limitmulti	= (int) $settings->limitmulti;
		$this->limitmax		= (int) $settings->limitmax;
		$this->limitmin		= (int) $settings->limitmin;
	}

	public function onBeginRace()
	{
		//Set plugin inactive, if gamemode is not timeattack
		//Respects the allgamemodes setting option!
		if(!$this->allgamemodes && Core::getObject('status')->gameinfo['GameMode'] != 1) {
			$this->setActive('onBeginRace');
			return;
		} else {
			$this->setActive(true);
		}
	}

	public function onEndChallenge($params)
	{
		$nchallenge = Core::getObject('challenges')->getNext();
		if(!Core::getObject('challenges')->check($nchallenge)) return;

		$newlimit		= (int) $nchallenge->AuthorTime * (int) $this->limitmulti;
		$newlimit		= ((int) $newlimit > ((int) $this->limitmax*60000)) ? (int) $this->limitmax*60000 : (int) $newlimit;
		$newlimit		= ((int) $newlimit < ((int) $this->limitmin*60000)) ? (int) $this->limitmin*60000 : (int) $newlimit;
		$this->limit	= (int) $newlimit;
		Core::getObject('gbx')->query('SetTimeAttackLimit', (int) $this->limit);
	}
}
?>