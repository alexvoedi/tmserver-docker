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
class PCore extends rcp_liveplugin
{
	public    $title	= 'PCore';
	public    $author	= 'hal.ko.sascha';
	public    $version	= '4.0.3.6';
	public    $nsqlcon	= true;
	protected $enabled	= null;
	protected $active	= null;
	private   $infsort	= false;
	private   $ForceCLM	= false;
	private   $startUp	= true;

	public function onLoad()
	{
		Core::getObject('db')->fileImport($this->id);
		$this->addAdminOption('onMLAPCoreupdateSession');
		$this->addAdminOption('onMLAPCoreStopLive');
		Core::getObject('chat')->addCommand('fwk', 'onChatfwk', 'Shows or hides the manialink menus', '/fwk');
		if(Core::getSetting('usesu')) {
			Core::getObject('chat')->addCommand('su', 'onChatsu', 'Command for admin account confirmation', '/su <password>', false);
		}
	}

	public function onUnload()
	{
		Core::getObject('gbx')->query('SendHideManialinkPage');
	}

	public function onLive()
	{
		//CleanUp
		$this->onUnLoad();

		//Set defaults for first startup
		Core::getObject('gbx')->query('ChallengeRestart');
	}

	public function onPeriodicalUpdate()
	{
		static $sqlreconnect	= 0;
		static $clientreconnect	= 0;
		$this->debug('onPeriodicalUpdate');

		//Check client connection
		Core::getObject('messages')->add("[Connections]");
		if(!Core::getObject('gbx')->pingConnection()) {
			++$clientreconnect;
			//trigger_error('CLIENT CONNECTION LOST AND CAN\'T ESTABLISH A NEW CONNECTION [retry: '.$clientreconnect.' / 3]', E_USER_WARNING);
			Core::getObject('messages')->add(" - Server:\t lost connection, trying reconnect [retry: {$clientreconnect} / 3]");
		} else {
			Core::getObject('messages')->add(" - Server:\t ok");
			$clientreconnect = 0;
		}

		//Check SQL connection
		if(!Core::getObject('db')->pingConnection()) {
			++$sqlreconnect;
			//trigger_error('SQL CONNECTION LOST AND CAN\'T ESTABLISH A NEW CONNECTION [retry: '.$sqlreconnect.' / 3]', E_USER_WARNING);
			Core::getObject('messages')->add(" - Database:\t lost connection, trying reconnect [retry: {$sqlreconnect} / 3]");
		} else {
			Core::getObject('messages')->add(" - Database:\t ok");
			$sqlreconnect = 0;
		}

		//Force live shutdown because there is no client or sql connection
		if($clientreconnect > 3 || $sqlreconnect > 3) {
			Core::storeSetting('live', false);
			Core::getObject('chat')->send('remoteCP !hl![Live] !df!has lost connection to the game and/or database server, shutdown in progress.');
			Core::getObject('messages')->add('[Shutdown]\r\n - Live has lost connection to the game and/or database server');
			return;
		}

		//Forced update of important statusdata
		Core::getObject('status')->update(true);

		//Set idle status
		$this->setIdleMode();
	}

	public function onBeginChallenge($params)
	{
		//Update periodicalUpdate timer
		Core::getObject('timedevents')->update('PeriodicalUpdate', '+240 second', 0, 'onPeriodicalUpdate');

		//Load status data / check connection
		Plugins::triggerEvent('onPeriodicalUpdate');

		//Set NbLaps & NbCheckpoints
		Core::storeSetting('NbLaps', $params[0]['NbLaps']);
		Core::storeSetting('NbCheckpoints', $params[0]['NbCheckpoints']);

		//Load challenges
		$this->updateChallenges();

		//StartUp
		if($this->startUp) {
			$this->startUp = false;

			//Load players
			$i = 0;
			$PlayerList = array();
			while(true)
			{
				Core::getObject('gbx')->suppressNextError();
				if(!Core::getObject('gbx')->query('GetPlayerList', 50, $i, 1)) break;
				$i = $i + 50;
				$Players = Core::getObject('gbx')->getResponse();
				if(empty($Players)) break;
				$PlayerList = array_merge($PlayerList, $Players);
			}

			if(!empty($PlayerList)) {
				foreach($PlayerList AS $value)
				{
					Core::getObject('players')->get($value['Login']);
					Plugins::triggerEvent('onPlayerConnect', array($value['Login']));
				}
			}

			//Output Info
			Core::getObject('messages')->add('[Loading Data at onLive]');
			if(empty(Core::getObject('players')->players)) {
				Core::getObject('messages')->add(' - no player data loaded, currently nobody playing');
			} else {
				Core::getObject('messages')->add(' - successfully loaded player data');
			}

			if(empty(Core::getObject('challenges')->data) && !Core::getObject('status')->isrelay) {
				trigger_error('Can not load challenges', E_USER_ERROR);
			} elseif(Core::getObject('status')->isrelay) {
				Core::getObject('messages')->add(' - no challenge data loaded because this is a relay server');
			} else {
				Core::getObject('messages')->add(' - successfully loaded challenge data');
			}
		}

		//CleanUp players data
		if(!empty(Core::getObject('players')->players)) {
			foreach(Core::getObject('players')->players AS $player)
			{
				if(!Core::getObject('players')->check($player)) continue;
				$player->cleanUp();
			}
		}

		//Add new challenge message
		$this->debug('onBeginChallenge');
		$cchallenge = Core::getObject('challenges')->getCurrent();
		if(Core::getObject('challenges')->check($cchallenge)) {
			Core::getObject('live')->addMsg('--- Switched to challenge: '. (string) $cchallenge->Name .' $z---');
		}
	}

	public function onBeginRace($params)
	{
		$this->debug('onBeginRace');
		Core::getObject('status')->updateVar('gamestate', true);
	}

	public function onEndRace()
	{
		$this->debug('onEndRace');
		Core::getObject('status')->updateVar('gamestate', false);
	}

	public function onEndChallenge($params)
	{
		$this->debug('onEndChallenge');

		//Update Wins
		if(!is_array($params[0]) && empty($params[0])) return;
		$update = Core::getObject('db')->getConnection()->prepare("UPDATE rcp_players SET Wins = Wins + 1 WHERE Login = :login");
		$update->bindParam('login', $params[0][0]['Login']);
		$update->execute();
		$update->closeCursor();
		$update = null;
	}

	public function onPlayerChat($params)
	{
		$input  = $params[2];
		$player = Core::getObject('players')->get($params[1]);

		if(!$input || !Core::getObject('players')->check($player) || $player->Ignored || substr($input, 0, 1) != '/') return;
		$array		= explode(' ', trim(str_replace('  ', ' ', $input)));
		$command	= array_shift($array);
		$cmd		= Core::getObject('chat')->getCommand($command, $player->Login);
		if($cmd) Plugins::triggerEvent($cmd['callback'], array($player, implode(' ', $array)));
	}

	public function onPlayerCheckpoint($params)
	{
		$score		= $params[2];
		$checkpoint	= $params[4];
		$player		= Core::getObject('players')->get($params[1]);
		if(!Core::getObject('players')->check($player) || !$score) return;

		//Reset the checkpoints on every new lap
		if(!$checkpoint) {
			$player->resetCheckpoints();
		}

		//Add new checkpoint
		$player->addCheckpoint($score);
	}

	public function onPlayerFinish($params)
	{
		if(empty($params[1])) return;
		$player	= Core::getObject('players')->get($params[1]);
		$score	= $params[2];
		if(!Core::getObject('players')->check($player) || !$score) return;

		//Update current personal best
		$player->setCurrentRecord($score);
	}

	public function onPlayerDisconnect($params)
	{
		$player = Core::getObject('players')->get($params[0]);
		if(!Core::getObject('players')->check($player)) return;

		//Update TimePlayed data
		$TimePlayed = Core::getSetting('time') - $player->Connected;
		$update = Core::getObject('db')->getConnection()->prepare("UPDATE rcp_players SET TimePlayed = TimePlayed + :played WHERE Id = :id");
		$update->bindParam('played', $TimePlayed);
		$update->bindParam('id', $player->Id);
		$update->execute();
		$update->closeCursor();
		$update = null;
	}

	public function onPlayerInfoChanged($params)
	{
		if(!is_array($params) || empty($params)) return;
		foreach($params AS $response)
		{
			$player = Core::getObject('players')->get($response['Login']);
			if(Core::getObject('players')->check($player)) {
				$player->updateInfo($response);
			}
		}
	}

	public function onPlayerIncoherence($params)
	{
		Core::getObject('gbx')->query('BlackListId', $params[0]);
	}

	public function ForceChallengeListModify()
	{
		$this->ForceCLM = true;
	}

	public function onChallengeListModified($params)
	{
		$this->debug('onChallengeListModified');
		if($params[2] || $this->ForceCLM) $this->updateChallenges();
		$this->ForceCLM = false;
	}

	public function onBillUpdated($params)
	{
		$bill = Core::getObject('bills')->get($params[0]);
		if($params[1] == 4) {
			Plugins::triggerEvent($bill->successcallback, $bill->params);

			//Return if the bill was not send by remoteCP[Live]
			if(!$bill) return;

			if($bill->display) Core::getObject('live')->addMsg($bill->msg);
			$insert = Core::getObject('db')->getConnection()->prepare("
				INSERT INTO rcp_cptransactions
				(Login, Reason, Billid, Coppers, Date)
				VALUES
				(:login, :reason, :id, :coppers, NOW())
			");
			$insert->bindParam('login', $bill->login);
			$insert->bindParam('reason', $bill->reason);
			$insert->bindParam('reason', $bill->reason);
			$insert->bindParam('id', $bill->id);
			$insert->bindParam('coppers', $bill->coppers);
			$insert->execute();
			$insert->closeCursor();
			$insert = null;
			Core::getObject('bills')->remove($params[0]);
			$this->debug("{$bill->login} payed {$bill->coppers} (id: {$bill->id} | {$bill->reason})");
		} elseif($params[1] == 5) {
			if($bill->errorcallback) Plugins::triggerEvent($bill->errorcallback, $bill->params);
			Core::getObject('bills')->remove($params[0]);
		}
	}

	public function onChatfwk($cmd)
	{
		$this->onMLAccesskey3($cmd);
	}

	public function onChatsu($cmd)
	{
		if(!Core::getSetting('usesu') || !Core::getObject('live')->isAdmin($cmd[0]->Login, true)) return;

		$admin = false;
		foreach(Core::getObject('session')->admins->admin AS $node)
		{
			if((string) $node->tmaccount == $cmd[0]->Login) {
				$admin = $node;
				break;
			}
		}

		if(md5($cmd[1]) == $admin->password) {
			$cmd[0]->setAdminStatus();
			Core::getObject('chat')->send('You have been successfully verified with your admin account', false, $cmd[0]->Login);
		} else {
			Core::getObject('chat')->send('Your admin verification missed, invalid password or no admin account', false, $cmd[0]->Login);
		}
	}

	public function onNewPlayer($player)
	{
		//default main window (center area)
		$window = $player->ManiaFWK->addWindow('MLWindow', 'Window', -24, 32, 48);
		if($window) {
			$window->setOption('icon', 'united');
		}

		//pre defined containers
		$window = $player->ManiaFWK->addWindow('MLContainerSidebarA', '', -66, 10, 20); //default left sidebar
		if($window) {
			$window->setOption('header', false);
			$window->setOption('static', true);
			$window->setOption('close', false);
			$window->setOption('bg', false);
			$window->setOption('lineheight', 2.25);
			$window->setOption('display', 'race');
		}

		$window = $player->ManiaFWK->addWindow('MLContainerSidebarB', '', 46, 10, 20); //default right sidebar
		if($window) {
			$window->setOption('header', false);
			$window->setOption('static', true);
			$window->setOption('close', false);
			$window->setOption('bg', false);
			$window->setOption('lineheight', 2.25);
			$window->setOption('display', 'race');
		}

		$window = $player->ManiaFWK->addWindow('MLContainermessages', '', -53, 46, 40);
		if($window) {
			$window->setOption('header', false);
			$window->setOption('static', true);
			$window->setOption('close', false);
			$window->setOption('bg', false);
			$window->setOption('lineheight', 2);
		}

		//default UI replacement containers
		$window = $player->ManiaFWK->addWindow('MLContainerchallenge_info', '', 45, 48, 20);
		if($window) {
			$window->setOption('header', false);
			$window->setOption('static', true);
			$window->setOption('close', false);
			$window->setOption('bg', 'tmf');
		}

		//if /su is disabled, define player as admin
		if(!Core::getSetting('usesu')) {
			$player->setAdminStatus();
		}

		//update message container
		Core::getObject('manialink')->updateContainer('messages');

		//Set idle status
		$this->setIdleMode();
	}

	public function onMLContainerSidebarA($params)
	{
		//Change container position if gamemode = rounds
		$params[1]->setOption('posx', (Core::getObject('status')->gameinfo['GameMode'] == 1) ? 46 : -66);
	}

	public function onMLContainerSidebarB($params)
	{
		//Change container position if gamemode == rounds
		$params[1]->setOption('posx', (Core::getObject('status')->gameinfo['GameMode'] == 1) ? -66 : 46);
	}

	public function onMLContainermessages($params)
	{
		$player	= $params[0];
		$window	= $params[1];
		$msgs	= Core::getObject('live')->getMsgs();
		$count	= count($msgs);
		if(!$count) return;
		$show	= 4;
		$show	= ($count > $show) ? $show : $count;

		$window->Frame(0, 0, 40, false, 'onMLPCoreMessages');
		for($i = $count; $i > $count-$show; --$i)
		{
			$window->Line();
			$window->Cell($msgs[$i], '100%');
		}
	}

	public function onMLPCoreMessages($params)
	{
		$window	= $params[0]->ManiaFWK->getWindow('MLWindow');
		$msgs	= Core::getObject('live')->getMsgs();
		if(!$window || empty($msgs)) return;
		$window->setOption('title', 'Live Messages');
		$window->setOption('icon', 'chat');
		$window->Reset();
		foreach($msgs AS $msg)
		{
			$window->Line();
			$window->Cell($msg, '100%');
		}
	}

	public function onMLAccesskey3($params)
	{
		$params[0]->ManiaFWK->toggleHidden($params[0]->Login);
	}

	public function onMLAPCoreupdateSession($params)
	{
		if(is_null($params)) {
			return 'Update session';
		}

		if(!Core::getObject('live')->isAdmin($params[0]->Login) || !Core::getObject('live')->checkPerm('globalmaintenance', $params[0]->Login, 'Update session')) return;
		Core::getObject('live')->updateSession(true);
	}

	public function onMLAPCoreStopLive($params)
	{
		if(is_null($params)) {
			return 'Stop live';
		}

		if(!Core::getObject('live')->isAdmin($params[0]->Login) || !Core::getObject('live')->checkPerm('globalmaintenance', $params[0]->Login, 'Stop live')) return;
		Core::storeSetting('live', false);
		Core::getObject('chat')->send('remoteCP !hl![Live] !df!stopped');
	}

	/*
	 * Class methods
	 */
	private function updateChallenges()
	{
		//Don't load challengedata on a relay server
		if(Core::getObject('status')->isrelay) return;

		//Console message
		$title = '['.$this->title.'::updateChallenges] ';
		Core::getObject('messages')->add($title);

		//Read data
		if(!Core::getObject('gbx')->query('GetCurrentChallengeInfo')) {
			//Core::getObject('challenges')->update(array()); //???
			trigger_error($title. 'Unable to get CurrentChallengeInfo', E_USER_ERROR);
			return;
		}

		$current	= Core::getObject('gbx')->getResponse();
		$i			= 0;
		$response	= array();
		while(true)
		{
			Core::getObject('gbx')->suppressNextError();
			if(!Core::getObject('gbx')->query('GetChallengeList', 50, $i)) {
				if($i == 0) {
					trigger_error($title. 'Unable to read at least one challenge', E_USER_ERROR);
				}
				break;
			}

			$dataset = Core::getObject('gbx')->getResponse();
			if(empty($dataset)) {
				if($i == 0) {
					trigger_error($title. 'Invalid GetChallengeList response, unable to read at least one challenge', E_USER_ERROR);
				}
				break;
			}

			foreach($dataset AS $challenge)
			{
				//Check for current challenge index
				if($challenge['UId'] == $current['UId']) {
					$current = $challenge['UId'];
				}

				//Read full challenge data and add to response
				if(Core::getObject('gbx')->query('GetChallengeInfo', $challenge['FileName'])) {
					$response[] = Core::getObject('gbx')->getResponse();
				}
			}
			$i = $i + 50;
		}

		//Set new challenge data
		Core::getObject('challenges')->update($response);

		//Get current challenge index
		$current = Core::getObject('challenges')->getIndexByUid($current);
		if($current === false) {
			trigger_error($title. 'invalid value of current challenge index, returned false', E_USER_ERROR);
		}

		//Get next challenge index
		$next = (($current+1) > (count(Core::getObject('challenges')->data)-1)) ? 0 : $current+1;

		//Set challenge indexes
		Core::getObject('challenges')->setCurrent($current);
		Core::getObject('challenges')->setNext($next);
	}

	private function setIdleMode()
	{
		Core::storeSetting('idle', count(Core::getObject('players')->players) ? false : true);
	}
}
?>