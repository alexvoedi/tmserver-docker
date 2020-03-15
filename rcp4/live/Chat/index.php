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
class Chat extends rcp_liveplugin
{
	public  $title		= 'Chat';
	public  $author		= 'hal.ko.sascha';
	public  $version	= '4.0.3.6';
	private $messages	= array();
  
	public function onLoad()
	{
		Core::getObject('chat')->addCommand('gg'			, 'onChatgg'			, 'Displays a goodgame message'					, '/gg Playername');
		Core::getObject('chat')->addCommand('hi'			, 'onChathi'			, 'Displays a hello message'					, '/hi Playername');
		Core::getObject('chat')->addCommand('bb'			, 'onChatbb'			, 'Displays a good bye message'					, '/bb everybody');
		Core::getObject('chat')->addCommand('gl'			, 'onChatgl'			, 'Displays a good luck message'				, '/gl Team');
		Core::getObject('chat')->addCommand('hf'			, 'onChathf'			, 'Displays a have fun message'					, '/hf Team');
		Core::getObject('chat')->addCommand('glhf'			, 'onChatglhf'			, 'Displays a good luck and have fun message'	, '/glhf Team');
		Core::getObject('chat')->addCommand('fuck'			, 'onChatfuck'			, 'Displays a fuck message...'					, '/fuck');
		Core::getObject('chat')->addCommand('serverlogin'	, 'onChatserverlogin'	, 'Displays the serverlogin...'					, '/serverlogin');
	}

	public function onLoadSettings($settings)
	{
		$this->messages['gg']	= (string) $settings->messages->gg;
		$this->messages['hi']	= (string) $settings->messages->hi;
		$this->messages['bb']	= (string) $settings->messages->bb;
		$this->messages['gl']	= (string) $settings->messages->gl;
		$this->messages['hf']	= (string) $settings->messages->hf;
		$this->messages['glhf']	= (string) $settings->messages->glhf;
		$this->messages['fuck']	= (string) $settings->messages->fuck;
	}

	public function onChatgg($cmd)
	{
		Core::getObject('chat')->send(sprintf($this->messages['gg'], $cmd[1]), $cmd[0]);
	}

	public function onChathi($cmd)
	{
		Core::getObject('chat')->send(sprintf($this->messages['hi'], $cmd[1]), $cmd[0]);
	}

	public function onChatbb($cmd)
	{
		Core::getObject('chat')->send(sprintf($this->messages['bb'], $cmd[1]), $cmd[0]);
	}

	public function onChatgl($cmd)
	{
		Core::getObject('chat')->send(sprintf($this->messages['gl'], $cmd[1]), $cmd[0]);
	}

	public function onChathf($cmd)
	{
		Core::getObject('chat')->send(sprintf($this->messages['hf'], $cmd[1]), $cmd[0]);
	}

	public function onChatglhf($cmd)
	{
		Core::getObject('chat')->send(sprintf($this->messages['glhf'], $cmd[1]), $cmd[0]);
	}

	public function onChatfuck($cmd)
	{
		Core::getObject('chat')->send($this->messages['fuck'], $cmd[0]);
	}
}
?>