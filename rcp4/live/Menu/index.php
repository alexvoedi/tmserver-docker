<?php
/**
* remoteCP 4
* ütf-8 release
* DO NOT REMOVE OR CHANGE THIS PLUGIN!
*
* @package remoteCPlive
* @author hal.sascha
* @copyright (c) 2006-2009
* @version 4.0.3.6
*/
require_once('./live/Menu/rcp_menu.class.php');
class Menu extends rcp_liveplugin
{
	public    $title	= 'Menu';
	public    $author	= 'hal.ko.sascha';
	public    $version	= '4.0.3.6';
	protected $enabled	= null;
	protected $active	= null;
	private   $mm		= false;
	public    $menu		= false;

	public function onPlayerFinish($params)
	{
		//Return if score was submitted (this is not a retire-callback)
		if($params[2]) return;

		//Close all windows and menu, if player presses the "retire"-button
		$player = Core::getObject('players')->get($params[1]);
		if(!Core::getObject('players')->check($player)) return;
		$player->cdata['menu'] = array();
		$this->mm->render($player);
		$player->ManiaFWK->closeWindows();
	}

	public function onLoad()
	{
		$this->mm = new rcp_menu();
		$this->menu = $this->mm->create('mainmenu', 'Menu', 'United');
		if($this->menu === false) {
			trigger_error('Unable to create main menu', E_USER_ERROR);
		}
	}

	public function onLive()
	{
		$this->mm->validate();

		//Render for all players
		if(empty(Core::getObject('players')->players)) return;
		foreach(Core::getObject('players')->players AS $player)
		{
			$this->mm->render($player);
		}
	}

	public function onNewPlayer($player)
	{
		$window = $player->ManiaFWK->addWindow('MLMenu', ' ', -64, 37, 52);
		if($window) {
			$window->setOption('header', false);
			$window->setOption('static', true);
			$window->setOption('close', false);
			$window->setOption('bg', false);
		}
		$this->mm->render($player);
	}

	public function onMLAMenu($params)
	{
		$player = $params[0];

		//handle click
		if(is_array($params[1])) {
			$uniqueid = $params[1][0];
			$callback = $params[1][1];
			$params   = $params[1][2];
			Plugins::triggerEvent($callback, array($player, $params));
		} else {
			$uniqueid = $params[1];
		}
		$this->mm->handleClick($player, $uniqueid);

		//render
		$this->mm->render($player);
	}
}
?>