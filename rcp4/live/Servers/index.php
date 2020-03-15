<?php
/**
* remoteCP 4
* ütf-8 release
*
* @package remoteCPlive
* @author hal.sascha, |Black|Co2NTRA
* @copyright (c) 2006-2009
* @version 4.0.3.6
*/
class Servers extends rcp_liveplugin
{
	public  $title		= 'Servers';
	public  $author		= 'hal.ko.sascha, |Black|Co2NTRA';
	public  $version	= '4.0.3.6';
	private $data		= array();
  
	public function onLoad()
	{
		Plugins::getPlugin('Menu')->menu->add('servers', 'Servers', 'Browse', 50, 'onMLServers');
	}

	public function onPeriodicalUpdate()
	{
		//Get server data (new)
		foreach(Core::getObject('session')->servers->children() as $node)
		{
			//do nothing if current server
			if(Core::getObject('session')->server->id == (string) $node->id) continue;

			//connect
			$host = (string) $node->connection->host;
			$port = (string) $node->connection->port;
			$psw  = (string) $node->connection->password;

			$this->debug("opening connection to server ". (string) $node->id);
			$connection = new IXR_Client_Gbx;
			if($connection->InitWithIp($host, $port, 1.5)) {
				if($connection->query('Authenticate','SuperAdmin',$psw)) {
					//Version
					if($connection->query('GetVersion')) {
						$Version = $connection->getResponse();
					}

					//Server options
					if($connection->query('GetServerOptions', 1)) {
						$ServerOptions = $connection->getResponse();
					}

					//Players
					$i = 0;
					$PlayersCount = 0;
					$SpectatorsCount = 0;
					while(true)
					{
						Core::getObject('gbx')->suppressNextError();
						if(!$connection->query('GetPlayerList', 50, $i, 1)) break;
						$i = $i + 50;
						$Players = $connection->getResponse();
						if(empty($Players)) break;
						foreach($Players AS $value)
						{
							++$PlayersCount;
							if($value['SpectatorStatus']) ++$SpectatorsCount;
						}
					}

					//Challenges
					$i = 0;
					$ChallengesSearch = array();
					while(true)
					{
						Core::getObject('gbx')->suppressNextError();
						if(!$connection->query('GetChallengeList', 50, $i)) break;
						$i = $i + 50;
						$Challenges = $connection->getResponse();
						if(empty($Challenges)) break;
						foreach($Challenges AS $value)
						{
							$Challengesdata[] = array(
								'Name' => $value['Name'],
								'Envi' => $value['Environnement']
							);
							$ChallengesSearch[] = $value['Name'];
						}
					}

					//Current challenge
					if($connection->query('GetCurrentChallengeInfo')) {
						$CurrentChallengeInfo = $connection->getResponse();
					}

					//Next Challenge
					$key = array_search($CurrentChallengeInfo['Name'], $ChallengesSearch);
					$key = ($key == ($count-1)) ? 0 : $key+1;
					$NextChallenge = $Challengesdata[$key];

					//Ladderlimits
					if($connection->query('GetLadderServerLimits')) {
						$LadderLimits = $connection->getResponse();
					}

					//Packmask
					if($connection->query('GetServerPackMask')) {
						$packmask = $connection->getResponse();
					}

					//Gamemode
					if($connection->query('GetGameMode')) {
						$GameMode = $connection->getResponse();
						if($CurrentGameInfo['GameMode'] == 0) {
							$GameMode = 'Rounds';
						} elseif($CurrentGameInfo['GameMode'] == 1) {
							$GameMode = 'TimeAttack';
						} elseif($CurrentGameInfo['GameMode'] == 2) {
							$GameMode = 'Team';
						} elseif($CurrentGameInfo['GameMode'] == 3) {
							$GameMode = 'Laps';
						} elseif($CurrentGameInfo['GameMode'] == 4) {
							$GameMode = 'Stunts';
						} else {
							$GameMode = 'Undefined';
						}
					}

					//Store data
					$this->data[(string) $node->login] = array(
						'Name'						=> $ServerOptions['Name'],
						'GameMode'					=> $GameMode,
						'Players'					=> $PlayersCount,
						'Spectators'				=> $SpectatorsCount,
						'Laddermin'					=> $LadderLimits['LadderServerLimitMin'],
						'Laddermax'					=> $LadderLimits['LadderServerLimitMax'],
						'Packmask'					=> $packmask,
						'MaxPlayers'				=> $ServerOptions['CurrentMaxPlayers'],
						'MaxSpecators'				=> $ServerOptions['CurrentMaxSpectators'],
						'Cchallenge'				=> $CurrentChallengeInfo['Name'],
						'Nchallenge'				=> $NextChallenge['Name'],
						'HideServer'				=> $ServerOptions['HideServer']
					);
				}
				$connection->Terminate();
			}
			$connection = null;
			unset($connection);
		}
	}

	public function onMLServers($params)
	{
		$window = $params[0]->ManiaFWK->getWindow('MLWindow');
		if(!$window) return;

		$window->setOption('title', 'Servers');
		$window->setOption('icon', 'Server');
		$window->Reset();

		$window->Line(array('class' => 'thead'));
		$window->Cell('Name', '35%');
		$window->Cell('Players', '15%', null, array('halign' => 'center'));
		$window->Cell('Spectators', '15%', null, array('halign' => 'center'));
		$window->Cell('Ladder', '20%', null, array('halign' => 'center'));
		$window->Cell('', '15%');

		foreach(Core::getObject('session')->servers->children() as $node)
		{
			//do nothing if current server
			if(Core::getObject('session')->server->id == (string) $node->id) continue;

			//Read from preloaded data
			$key = (string) $node->login;
			if(!array_key_exists($key, $this->data)) continue;

			//do not show hidden servers
			//(0 = visible, 1 = always hidden, 2 = hidden from nations)
			if($this->data[$key]['HideServer'] == 1) continue;
			if($player->OnlineRights == 0 && $this->data[$key]['HideServer'] == 2) continue;

			$window->Line();
			$window->Cell("\$l[tmtp://#join={$node->login}]". (string) $node->name ."\$l ", '35%');
			$window->Cell(' '.sprintf("%03d", $this->data[$key]['Players']).' ', '15%', null, array('halign' => 'center'));
			$window->Cell(' '.sprintf("%03d", $this->data[$key]['Spectators']).' ', '15%', null, array('halign' => 'center'));
			$window->Cell($this->data[$key]['Laddermin'].' - '.$this->data[$key]['Laddermax'], '20%', null, array('halign' => 'center'));
			$window->Cell('More', '15%', array('onMLServersMore', $key), array('class' => 'btn2n'));
		}
	}

	public function onMLServersMore($params)
	{
		$window = $params[0]->ManiaFWK->getWindow('MLWindow');
		if(!$window) return;

		//Get data for the selected server
		$window->Reset();

		//Read from preloaded database data
		$key = $params[1];
		$on  = array_key_exists($key, $this->data);

		$window->Line();
		$window->Cell('$iServerstatus', '50%');
		$window->Cell($on ? 'online' : 'offline', '50%');

		if($on) {
			$window->setOption('title', $this->data[$key]['Name']);
			$window->setOption('icon', 'Server');
			$window->Line();
			$window->Cell('$iGametyp', '50%');
			$window->Cell($this->data[$key]['Packmask'].' ', '50%');
			$window->Line();
			$window->Cell('$iActual Players', '50%');
			$window->Cell($this->data[$key]['Players'].' ', '50%');
			$window->Line();
			$window->Cell('$iMaximal Players', '50%');
			$window->Cell($this->data[$key]['MaxPlayers'].' ', '50%');
			$window->Line();
			$window->Cell('$iActual Spectators', '50%');
			$window->Cell($this->data[$key]['Spectators'].' ', '50%');
			$window->Line();
			$window->Cell('$iMaximal Spectators', '50%');
			$window->Cell($this->data[$key]['MaxSpecators'].' ', '50%');
			$window->Line();
			$window->Cell('$iLadderlimit Min.', '50%');
			$window->Cell($this->data[$key]['Laddermin'].' ', '50%');
			$window->Line();
			$window->Cell('$iLadderlimit Max.', '50%');
			$window->Cell($this->data[$key]['Laddermax'].' ', '50%');
			$window->Line();
			$window->Cell('$iCurrent Challenge', '50%');
			$window->Cell($this->data[$key]['Cchallenge'], '50%');
			$window->Line();
			$window->Cell('$iNext Challenge', '50%');
			$window->Cell($this->data[$key]['Nchallenge'], '50%');
		}

		$window->Line();
		if($on) {
			$window->Cell("\$l[tmtp://#join={$key}]". (string) '$w$iJoin$i$w' ."\$l ", '33%', null, array('class' => 'btn2n'));
			$window->Cell("\$l[tmtp://#spectate={$key}]". (string) '$w$iSpectate$i$w' ."\$l ", '34%', null, array('class' => 'btn2n'));
		} else {
			$window->Cell('', '67%');
		}
		$window->Cell("\$l[tmtp://#addfavourite={$key}]". (string) '$w$iAdd Favourite$i$w' ."\$l ", '33%', null, array('class' => 'btn2n'));

		$window->Line();
		$window->Cell('', '25%');
		$window->Cell('Back to Servers', '50%', 'onMLServers', array('class' => 'btn2n'));
		$window->Cell('', '25%');
	}
}
?>