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
class Clock extends rcp_liveplugin
{
	public  $title		= 'Clock';
	public  $author		= 'hal.ko.sascha';
	public  $version	= '4.0.3.6';
	private $format		= 'h:i a (T)';

	public function onLoadSettings($settings)
	{
		$this->format = (string) $settings->format;
	}

	public function onLoad()
	{
		Core::getObject('timedevents')->update('Clock', '+1 minute', 0, 'onMLAClock');
	}

	public function onLive()
	{
		$this->onMLAClock();
	}

	public function onNewPlayer($player)
	{
		$window = $player->ManiaFWK->addWindow('MLContainerClock', '', -64, 36.5, 10);
		if($window) {
			$window->setOption('static', true);
			$window->setOption('header', false);
			$window->setOption('bg', false);
		}
	}

	public function onMLAClock()
	{
		Core::getObject('manialink')->updateContainer('Clock');
	}

	public function onMLContainerClock($params)
	{
		$window	= $params[1];
		$window->setOption('posy', 40);
		$window->Line();
		$window->Cell(date($this->format), '100%');
	}
}
?>