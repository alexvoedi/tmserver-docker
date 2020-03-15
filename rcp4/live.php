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

/**
 * Stop if argv is not available (argv is only available on CLI)
 */
if(!is_array($_SERVER['argv']) || empty($_SERVER['argv'])) {
	trigger_error('Script aborted, this file can only by called on commandline', E_USER_ERROR);
}

/**
 * Parameters handling
 */
$_REQUEST['serverid'] = $_SERVER['argv'][2];

/**
 * Create Core
 */
set_time_limit(0);
require_once './includes/core.class.php';
Core::storeCoreSettings();
Core::storeSetting('httppath', $_SERVER['argv'][3]); //old $_SERVER['argv'][5];
Core::storeSetting('debug', false);
Core::storeSetting('pluginpath', './live/');
Core::storeSetting('live', true);
Core::storeSetting('time', time());
Core::storeCoreObjects();

/**
 * Usage
 * Remember that rcp_usage class is only working if debug mode is enabled
 */
Core::getObject('usage')->type = 'rcp_timeusage'; //default usage type is rcp_ramusage
Core::getObject('usage')->outmode = false; //false outputs into console, true writes the cache/live_performance.txt

/**
 * Login Check
 */
if(!Core::getObject('session')->admin->isLogged()) {
	trigger_error(ct_permerr1, E_USER_WARNING);
	Core::getObject('messages')->getAll();
	exit();
}

/**
 * Output header
 */
Core::getObject('messages')->add('  ');
Core::getObject('messages')->add('/*');
Core::getObject('messages')->add(' * remoteCP '.Core::getSetting('version').' [Live]');
Core::getObject('messages')->add(' * (c) hal.sascha | www.tmbase.de');
Core::getObject('messages')->add(' * startup: '. date('c'));
Core::getObject('messages')->add(' */');
Core::getObject('messages')->add('  ');
Core::getObject('messages')->add('[Loaded Plugins]');

/**
 * Load plugin system
 */
require_once './includes/plugins.class.php';
Plugins::load();

/**
 * Execute
 */
Core::getObject('gbx')->enableCB();
Plugins::triggerEvent('onLive');
while(Core::getSetting('live'))
{
	//Console Output
	Core::getObject('messages')->getAll();

	//Callbacks
	Core::getObject('gbx')->flushCB();
	Core::getObject('gbx')->readCB(1);
	$calls = Core::getObject('gbx')->getCBResponses();
	if($calls) {
		foreach($calls as $call)
		{
			switch($call[0]) {
				//Player
				case 'TrackMania.PlayerManialinkPageAnswer':
					Core::getObject('manialink')->handlePageAnswer($call[1]);
				break;

				case 'TrackMania.PlayerCheckpoint':
					Plugins::triggerEvent('onPlayerCheckpoint', $call[1]);
				break;

				case 'TrackMania.PlayerChat':
					Plugins::triggerEvent('onPlayerChat', $call[1]);
				break;

				//Player - Flow
				case 'TrackMania.PlayerConnect':
					Plugins::triggerEvent('onPlayerConnect', $call[1]);
				break;

				case 'TrackMania.PlayerInfoChanged':
					Plugins::triggerEvent('onPlayerInfoChanged', $call[1]);
				break;

				case 'TrackMania.PlayerFinish':
					Plugins::triggerEvent('onPlayerFinish', $call[1]);
				break;

				case 'TrackMania.PlayerIncoherence':
					Plugins::triggerEvent('onPlayerIncoherence', $call[1]);
				break;

				case 'TrackMania.PlayerDisconnect':
					Plugins::triggerEvent('onPlayerDisconnect', $call[1]);
					Core::getObject('players')->remove($call[1][0]);
				break;

				//Challenges
				case 'TrackMania.ChallengeListModified':
					Plugins::triggerEvent('onChallengeListModified', $call[1]);
				break;

				//Challenge - Flow
				case 'TrackMania.BeginChallenge':
					Plugins::triggerEvent('onBeginChallenge', $call[1]);
				break;

				case 'TrackMania.BeginRace':
					Plugins::triggerEvent('onBeginRace', $call[1]);
				break;

				case 'TrackMania.BeginRound':
					Plugins::triggerEvent('onBeginRound', $call[1]);
				break;

				case 'TrackMania.EndRound':
					Plugins::triggerEvent('onEndRound', $call[1]);
				break;

				case 'TrackMania.EndRace':
					Plugins::triggerEvent('onEndRace', $call[1]);
				break;

				case 'TrackMania.EndChallenge':
					Plugins::triggerEvent('onEndChallenge', $call[1]);
				break;

				//Others
				case 'TrackMania.StatusChanged':
					Plugins::triggerEvent('onStatusChanged', $call[1]);
				break;

				case 'TrackMania.BillUpdated':
					Plugins::triggerEvent('onBillUpdated', $call[1]);
				break;

				case 'Trackmania.ServerStart':
					Plugins::triggerEvent('onServerStart', $call[1]);
				break;

				case 'TrackMania.ServerStop':
					Plugins::triggerEvent('onServerStop', $call[1]);
					Core::storeSetting('live', false);
				break;

				case 'Trackmania.TunnelDataReceived':
					Plugins::triggerEvent('onTunnelDataReceived', $call[1]);
				break;

				case 'Trackmania.ManualFlowControlTransition':
					Plugins::triggerEvent('onManualFlowControlTransition', $call[1]);
				break;

				case 'TrackMania.Echo':
					Plugins::triggerEvent('onEcho', $call[1]);
				break;
			}
		}
		$calls = null;
		unset($calls);
	}

	//CPU Limiter & Events
	Core::getObject('live')->LoopLimiter();
	Core::storeSetting('time', time());

	//Execute timedEvents
	Core::getObject('timedevents')->execute();

	//Update ManiaFWK
	Core::getObject('manialink')->display();

	//Execute Calls
	Core::getObject('actions')->Exec();
}
Plugins::unLoad();
?>