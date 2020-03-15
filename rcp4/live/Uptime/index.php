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
class Uptime extends rcp_liveplugin
{
	public  $title		= 'Uptime';
	public  $author		= 'hal.ko.sascha';
	public  $version	= '4.0.3.6';

	public function onLoad()
	{
		Core::getObject('timedevents')->update('onMLAUptime', '+30 minute', 0, 'onMLAUptime');
	}

	public function onMLAUptime($params)
	{
		if(Core::getObject('gbx')->query('GetNetworkStats')) {
			$NetworkStats = Core::getObject('gbx')->getResponse();
			Core::getObject('live')->addMsg('Server running since '. $this->getUptime($NetworkStats['Uptime']));
		}
	}

	private function getUptime($mwtime)
	{
		$days	= floor($mwtime/86400);
		$hours	= floor(($mwtime%86400)/3600);
		$min	= floor((($mwtime%86400)%3600)/60);
		return sprintf("%d d %d h %2d m", $days, $hours, $min);
	}
}
?>