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
require_once './includes/core/IXR_Library.inc.php';
class LiveStatus extends rcp_liveplugin
{
	public  $title			= 'LiveStatus';
	public  $author			= 'hal.ko.sascha';
	public  $version		= '4.0.3.6';

	public function onPeriodicalUpdate()
	{
		//Get challenge data
		$cchallenge = Core::getObject('challenges')->getCurrent();
		$nchallenge = Core::getObject('challenges')->getNext();
		if(!Core::getObject('challenges')->check($cchallenge) || !Core::getObject('challenges')->check($nchallenge)) return;

		Core::getObject('gbx')->query('GetMaxPlayers');
		$maxplayers = Core::getObject('gbx')->getResponse();
		$maxplayers = $maxplayers['CurrentValue'];

		Core::getObject('gbx')->query('GetMaxSpectators');
		$maxspecs = Core::getObject('gbx')->getResponse();
		$maxspecs = $maxspecs['CurrentValue'];

		Core::getObject('gbx')->query('GetLadderServerLimits');
		$LadderLimits = Core::getObject('gbx')->getResponse();
		$laddermin = $LadderLimits['LadderServerLimitMin'];
		$laddermax = $LadderLimits['LadderServerLimitMax'];

		$players = 0;
		$specs = 0;
		if(!empty(Core::getObject('players')->players)) {
			foreach(Core::getObject('players')->players AS $player)
			{
				if($player->SpectatorStatus['Spectator']) {
					++$specs;
				} else {
					++$players;
				}
			}
		}

		$login		= urlencode(Core::getObject('status')->systeminfo['ServerLogin']);
		$name		= urlencode(Core::getObject('session')->server->name);
		$cchallenge	= urlencode($cchallenge->Name);
		$nchallenge	= urlencode($nchallenge->Name);
		$version	= urlencode(Core::getSetting('version'));
		$tool		= urlencode('remoteCP');

		//connect to tmbase
		$connection = new IXR2_ClientMulticall('http://www.tmbase.de/server.php');
		$connection->addCall('remotecp.setServerStatus', $login, $name, $maxplayers, $players, $maxspecs, $specs, $cchallenge, $nchallenge, $version, $laddermin, $laddermax, $tool);
		if(!$connection->query()) {
			trigger_error($connection->getErrorMessage());
		}
	}
}
?>