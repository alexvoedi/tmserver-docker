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
require_once './includes/core/IXR_Library.inc.php';
class Dedimania extends rcp_liveplugin
{
	public  $title		= 'Dedimania';
	public  $author		= 'hal.ko.sascha';
	public  $version	= '4.0.3.6';

	//Data
	private $dedimania	= false;
	private $data		= array();
	private $dataids	= array();
	private $keepalive	= true;
	private $dediversion= false;
	private $lastcon	= false;

	//Settings
	private $readonly	= false;
	private $url		= '';
	private $maxrecords	= 30;
	private $nation		= 'GER';

	//RecordsUI
	private $minrecdisp	= 3;
	private $maxrecdisp	= 8;
	private $recdisp	= false;

	public function onLoad($reconnect = false)
	{
		if(!$reconnect) {
			$records = Plugins::getPlugin('Menu')->menu->add('records', 'Records', 'Extreme', 40);
			if($records !== false) {
				$records->add('dedimania', 'Dedimania', false, 2, 'onMLDedimania');
			}
			$this->addAdminOption('onMLADedimaniaRO');
		} else {
			//check connection and re-connect timer (every 60 minutes)
			if(!$this->dedimania && $this->lastcon >= (Core::getSetting('time') - 3600)) {
				return;
			}
		}

		//connect to dedimania server for authentification
		$this->dedimania = new IXR2_ClientMulticall('http://dedimania.net/RPC4/server.php');
		$this->dedimania->addCall('dedimania.CheckConnection');
		$this->dedimania->addCall('dedimania.Authenticate', $this->getAuthInfo());
		$this->dedimania->addCall('dedimania.ValidateAccount');
		$response = $this->queryDedimania('onLoad');

		//handle response
		if(!$response) {
			//cancel connection
			$this->dedimania = false;
			return;
		}

		//create main connection
		$this->dedimania = false;
		$this->dedimania = new IXR2_ClientMulticall($this->url);
		$this->lastcon   = Core::getSetting('time');
	}

	public function onLoadSettings($settings)
	{
		$this->readonly			= ((int) $settings->readonly) ? true : false;
		$this->url				= (string) $settings->url;
		$this->maxrecords		= (int) $settings->maxrecords;
		$this->nation			= (string) $settings->nation;
		$this->minrecdisp		= (int) $settings->ui->minrecdisp;
		$this->maxrecdisp		= (int) $settings->ui->maxrecdisp;
		$this->recdisp			= ((int) $settings->ui->enabled) ? true : false;

		//Config values check
		$this->debug('used Login: '. Core::getObject('session')->server->login);
		$this->debug('used CCode: '. Core::getObject('session')->server->connection['communitycode']);
		if(empty(Core::getObject('session')->server->login) || empty(Core::getObject('session')->server->connection['communitycode'])) {
			$this->debug('disabling plugin, missing login informations');
			$this->setEnabled(false);
		}
	}

	public function onPeriodicalUpdate()
	{
		//try reconnect if connection was lost
		if(!$this->dedimania) {
			$this->onLoad(true);
			return;
		}

		//check keepalive
		if(!$this->keepalive) {
			$this->keepalive = true;
			return;
		}

		//get players
		$players = $this->getPlayers();

		//do xmlrpc calls
		$this->dedimania->addCall('dedimania.Authenticate', $this->getAuthInfo());
		$this->dedimania->addCall('dedimania.ValidateAccount');
		$this->dedimania->addCall('dedimania.UpdateServerPlayers', (string) 'TMF', (int) Core::getObject('status')->gameinfo['GameMode'], (array) $this->getSrvInfo(), (array) $players);
		$this->dedimania->addCall('dedimania.WarningsAndTTR');
		$response = $this->queryDedimania('onPeriodicalUpdate');

		//handle response
		if(!$response) return;
		$this->keepalive = true;
	}

	public function onBeginRace()
	{
		//Set plugin inactive, if gamemode is stunts
		if(Core::getObject('status')->gameinfo['GameMode'] == 4) {
			$this->setActive('onBeginRace');
			return;
		} else {
			$this->setActive(true);
		}
		if(!$this->dedimania) return;

		//Reset data
		$this->data		= array();
		$this->dataids	= array();

		//RecordsUI
		Core::getObject('manialink')->updateContainer('SidebarA');

		//check Dedimania connection & get version
		$this->dedimania->addCall('dedimania.CheckConnection');
		$this->dedimania->addCall('dedimania.GetVersion');
		$response = $this->queryDedimania('onBeginRace connectioncheck');

		//handle response
		if(!empty($response[1][0])) {
			$this->dediversion = $response[1][0]['Version'];
			$this->maxrecords  = $response[1][0]['MaxRecords'];
			if($response[0][0]) {
				$this->debug(" - connected to: {$this->url}");
			}
		}

		//get challengeinfo
		$challenge = Core::getObject('challenges')->getCurrent();
		if(!Core::getObject('challenges')->check($challenge)) return;

		//get players
		$players = $this->getPlayers();

		//do xmlrpc calls
		$this->dedimania->addCall('dedimania.Authenticate', $this->getAuthInfo());
		$this->dedimania->addCall('dedimania.ValidateAccount');
		$this->dedimania->addCall('dedimania.CurrentChallenge', (string) $challenge->Uid, (string) $challenge->Name, (string) $challenge->Environment, (string) $challenge->Author, (string) 'TMF', (int) Core::getObject('status')->gameinfo['GameMode'], (array) $this->getSrvInfo(), (int) $this->maxrecords, (array) $players);
		$this->dedimania->addCall('dedimania.WarningsAndTTR');
		$response = $this->queryDedimania('onBeginRace read currentchallenge');

		//handle response
		if(!$response) return;
		$this->debug(" - loaded data for challenge: {$challenge->Name}");
		$this->keepalive = false;
		$this->handleResponse($response, 2);
	}

	public function onEndRace($params)
	{
		if(!$this->dedimania || $this->readonly || Core::getObject('status')->gameinfo['RoundsForcedLaps']) return;

		//get challengeinfo
		$challenge = Core::getObject('challenges')->getCurrent();
		if(!Core::getObject('challenges')->check($challenge)) return;

		//get times
		$times = array();
		$rankings = $params[0];
		if(!empty($rankings)) {
			foreach($rankings AS $player)
			{
				//Get players best lap
				$plobj = Core::getObject('players')->get($player['Login']);
				if(!Core::getObject('players')->check($plobj)) continue;
				$record = $plobj->getCurrentRecord(true);
				$score = $record[0];
				$checkpoints = $record[1];

				//if data is empty, leave now
				if(empty($score)) continue;

				//Get fastest lap in laps mode (3)
				if(Core::getObject('status')->gameinfo['GameMode'] == 3) {
					//Get laps count
					$laps = count($checkpoints) / Core::getSetting('NbCheckpoints');

					//Loop trough laps
					$fastest = false;
					while($laps)
					{
						//Get lap checkpoints
						$laplastkey  = ($laps * Core::getSetting('NbCheckpoints')) - 1;
						$lapfirstkey = ($laps * Core::getSetting('NbCheckpoints')) - Core::getSetting('NbCheckpoints');
						$lastfromlastlap = $lapfirstkey - 1;
						$lastfromlastlap = (array_key_exists($lastfromlastlap, $checkpoints)) ? $checkpoints[$lastfromlastlap] : 0;
						$lastfromthislap = $checkpoints[$laplastkey];

						//Get lap time
						$time = $lastfromthislap - $lastfromlastlap;

						//Check if fastest
						if($fastest === false || $time < $fastest[0]) {
							//Create checkpoint array
							$tmp = array_slice($checkpoints, $lapfirstkey, Core::getSetting('NbCheckpoints'));
							foreach($tmp AS $key => $value)
							{
								$tmp[$key] = $value - $lastfromlastlap;
							}
							//create fatest data-array
							$fastest = array($time, $tmp);
						}
						--$laps;
					}

					//Assign best lap data
					$score = $fastest[0];
					$checkpoints = $fastest[1];
				}


				//verify that checkpoint count is exactly NbCheckPoints
				$count = count($checkpoints);
				if($count != Core::getSetting('NbCheckpoints')) {
					trigger_error("Dedimania - {$plobj->Login}: Checkpoint count ({$count}) doesn\'t match NbCheckpoints (".Core::getSetting('NbCheckpoints').")", E_USER_WARNING);
					continue;
				}

				//verify that checkpoints indexes/times of a player run are positive and incremental
				if(empty($checkpoints)) continue;
				if(!$this->checkCheckpoints($checkpoints, $plobj->Login)) {
					trigger_error("Dedimania - {$plobj->Login}: Checkpoints check missed (incremental, positive)". print_r($checkpoints, true), E_USER_WARNING);
					continue;
				}

				//verify that the last checkpoint has the same time as Finish time
				$key = count($checkpoints)-1;
				if($checkpoints[$key] != $score) {
					trigger_error("Dedimania - {$plobj->Login}: Last Checkpoint ({$checkpoints[$key]} doesn\'t match BestTime ({$score})", E_USER_WARNING);
					continue;
				}

				//add record to times array
				$times[] = array(
					'Login'	=> (string) $plobj->Login,
					'Best'	=> (int) $score,
					'Checks'=> implode(',',$checkpoints) //older still valid version: 'Checks'=> (array) $checkpoints
				);
			}
		}

		//do xmlrpc calls
		$this->dedimania->addCall('dedimania.Authenticate', $this->getAuthInfo());
		$this->dedimania->addCall('dedimania.ValidateAccount');
		$this->dedimania->addCall('dedimania.ChallengeRaceTimes', (string) $challenge->Uid, (string) $challenge->Name, (string) $challenge->Environment, (string) $challenge->Author, (string) 'TMF', (int) Core::getObject('status')->gameinfo['GameMode'], (int) 0, (int) $this->maxrecords, (array) $times);
		$this->dedimania->addCall('dedimania.WarningsAndTTR');
		$response = $this->queryDedimania('onEndRace');

		//handle response
		if(!$response) return;
		$this->keepalive = false;
		$this->handleResponse($response, 2);
	}

	public function onPlayerFinish($params)
	{
		if(empty($params[1]) || empty($params[2])) return;
		$player	= Core::getObject('players')->get($params[1]);
		$score	= $params[2];
		if(!Core::getObject('players')->check($player)) return;

		//Get challenge
		$challenge = Core::getObject('challenges')->getCurrent();
		if(!Core::getObject('challenges')->check($challenge)) return;

		//Get new score position inside the array
		$rank = $this->getDataRank($player->Login, $score);
		$this->debug("getDataRank: {$rank} ({$player->Login})");

		//RecordsUI
		if(!empty($rank)) {
			Core::getObject('manialink')->updateContainer('SidebarA');
			Core::getObject('live')->addMsg("New #". ($rank) ." Dedimania record on ". $challenge->Name ."!df!: !hl!". Core::getObject('tparse')->toRaceTime($score) ."!df! by {$player->NickName}");
		//} elseif($rank > $this->maxrecords) { //WTF!? this never happens? because if rank has a value, the if NOT(!!!) empty will allways used, if rank has not a value this also not applies... *stupid*^^
		//	return;
		}

		//Remove existing data from player
		if(!empty($this->data)) {
			$oldrank = $this->data[$player->Login]['Rank'];
			$this->data[$player->Login]	= null;
			$this->dataids[$oldrank]		= null;
			unset($this->data[$player->Login]);
			unset($this->dataids[$oldrank]);
		}

		//Add new score
		$this->data[(string) $player->Login] = array(
			'Login'		=> (string) $player->Login,
			'NickName'	=> (string) $player->NickName,
			'Best'		=> (int) $score,
			'Rank'		=> (int) $rank,
			'Checks'	=> (array) array()
		);

		//fill id array
		//this is like a search index, allows to fetch players data for a rank very quickly
		//example: $this->data[$this->dataids[$ranknumber]]
		$this->dataids[$rank] = (string) $player->Login;

		//ReSort data array
		uasort($this->data, array($this, 'sortDataCB'));
	}

	public function onPlayerConnect($params)
	{
		if(!$this->dedimania) return;

		$player = Core::getObject('players')->get($params[0]);
		if(!Core::getObject('players')->check($player)) return;

		//do xmlrpc calls
		$this->dedimania->addCall('dedimania.Authenticate', $this->getAuthInfo());
		$this->dedimania->addCall('dedimania.ValidateAccount');
		$this->dedimania->addCall('dedimania.PlayerArrive', (string) 'TMF', (string) $player->Login, (string) $player->NickName, (string) $this->nation, (string) $player->TeamId, (int) $player->LadderRanking, (bool) $player->SpectatorStatus['Spectator'], (bool) $player->LadderRanking);
		$this->dedimania->addCall('dedimania.WarningsAndTTR');
		$this->queryDedimania('onPlayerConnect');
	}

	public function onPlayerDisconnect($params)
	{
		if(!$this->dedimania) return;

		$player = Core::getObject('players')->get($params[0]);
		if(!Core::getObject('players')->check($player)) return;

		//do xmlrpc calls
		$this->dedimania->addCall('dedimania.Authenticate', $this->getAuthInfo());
		$this->dedimania->addCall('dedimania.ValidateAccount');
		$this->dedimania->addCall('dedimania.PlayerLeave', (string) 'TMF', (string) $player->Login);
		$this->dedimania->addCall('dedimania.WarningsAndTTR');
		$this->queryDedimania('onPlayerDisconnect');
	}

	public function onNewPlayer($player) {
		//Set recordsUI defaults
		$player->cdata[$this->id]['exp'] = true;
		$player->cdata[$this->id]['max'] = false;
		Core::getObject('manialink')->updateContainer('SidebarA', $player);
	}

	public function onMLDedimania($params)
	{
		$window = $params[0]->ManiaFWK->getWindow('MLWindow');
		if(!$window) return;
		$window->setOption('title', 'Dedimania Records');
		$window->setOption('icon', 'Medium');
		$window->Reset();

		//Top Records
		$records = $this->data;
		$entries = 18;
		$max	 = count($records);
		$index	 = ($params[1]+0 < 1) ? 1 : $params[1]+0;
		$index	 = ($index > $max) ? $max - $entries: $index;
		$end	 = $index+$entries;
		$end	 = ($end >= $max) ? $max : $end;

		$window->Line(array('class' => 'thead'));
		$window->Cell('Pos', '10%', null, array('halign' => 'center'));
		$window->Cell('NickName', '65%');
		$window->Cell('Record', '25%', null, array('halign' => 'right'));
		if($max) {
			for($i = $index; $end >= $i; ++$i)
			{
				$data = $records[$this->dataids[$i]];
				$window->Line();
				$window->Cell($data['Rank'], '10%', null, array('halign' => 'center'));
				$window->Cell($data['NickName'], '65%', array('onMLPlayersDetails', array($data['Login'],$index,'onMLDedimania')));
				$window->Cell(Core::getObject('tparse')->toRaceTime($data['Best']), '25%', null, array('halign' => 'right'));
			}

			$prev = ($index <= 1) ? false : true;
			$next = ($index >= ($max-$entries)) ? false : true;
			if($prev || $next) {
				$window->Line();
				$window->Cell('previous', '25%', array('onMLDedimania',$index-$entries), array('hidecell' => !$prev,'class' => 'btn2n'));
				$window->Cell('', '50%');
				$window->Cell('next', '25%', array('onMLDedimania',$index+$entries), array('hidecell' => !$next,'class' => 'btn2n'));
			}
		} else {
			$window->Line();
			$window->Cell('no records available', '100%', null, array('halign' => 'center'));
		}
	}

	public function onMLContainerSidebarA($params)
	{
		if(!$this->recdisp || empty($this->data)) return;
		$player = $params[0];
		$window = $params[1];

		$exp = $player->cdata[$this->id]['exp'];
		$max = $player->cdata[$this->id]['max'];

		$framesize   = $exp ? 20 : 10;
		$paddingsize = $exp ? 10 : 20;
		$ranksize    = $exp ? 8  : 16;
		$timesize    = $exp ? 30 : 64;
		$nicksize    = $exp ? 52 : 0;

		if(Core::getObject('status')->gameinfo['GameMode'] == 1) {
			$fpx = ($exp) ? 0 : 10;
			$lr  = false;
			$icA = 'ArrowNext';
			$icB = 'ArrowPrev';
		} else {
			$fpx = 0;
			$lr  = true;
			$icA = 'ArrowPrev';
			$icB = 'ArrowNext';
		}

		$personalb = false;
		$textcolor = '!fhl!';
		$linestyle = false;

		$window->Frame($fpx, 0, $framesize, array('class' => 'tmf'), false, false);
		$window->Line();
		if($lr) $window->Cell('', $paddingsize.'%', false);
		$window->Cell('', $ranksize.'%');
		if($exp) $window->Cell('Dedimania', $nicksize.'%');
		$window->Cell('', array(2,2), 'onMLADedimaniaChangeMax', array('style' => 'Icons64x64_1', 'substyle' => $max ? 'ArrowUp' : 'ArrowDown'));
		$window->Cell('', array(2,2), 'onMLADedimaniaChangeExp', array('style' => 'Icons64x64_1', 'substyle' => $exp ? $icA : $icB));
		if(!$lr) $window->Cell('', $paddingsize.'%', false);

		foreach($this->data AS $key => $record)
		{
			if(!$max && $record['Rank'] > $this->minrecdisp) {
				break;
			} elseif($max && $record['Rank'] > $this->maxrecdisp) {
				break;
			}

			if($player->Login == $record['Login']) {
				$personalb = true;
				$textcolor = '!fsi!';
				$linestyle = array('class' => 'linehl', 'margin' => array(0.5,0.5));
			} else {
				$linestyle = array('margin' => array(0.5,0.5));
			}

			$window->Line($linestyle);
			if($lr) $window->Cell('', $paddingsize.'%');
			$window->Cell($record['Rank'].'.', $ranksize.'%', null, array('halign' => 'right'));
			$window->Cell(Core::getObject('tparse')->toRaceTime($record['Best']), $timesize.'%', null, array('textcolor' => $textcolor));
			if($exp) $window->Cell($record['NickName'], $nicksize.'%', null, array('halign' => 'left'));
			if(!$lr) $window->Cell('', $paddingsize.'%');
		}

		//Display PersonalBest if it was not in displayed records
		if($personalb === true) return;

		//Get players current dataset
		$pbdata = array_key_exists($player->Login, $this->data) ? $this->data[$player->Login] : false;
		if(!$pbdata) return;

		//Display seperator
		$window->Line();
		if($lr) $window->Cell('', $paddingsize.'%');
		$window->Cell('...', '100%');

		//Vars
		$key		= $pbdata['Rank'] - 1;
		$textcolor	= '!fhl!';
		$linestyle	= false;
		for($i = 2; $i != 0; --$i)
		{
			if(!array_key_exists($key, $this->dataids)) break;
			$data = $this->data[$this->dataids[$key]];

			if($player->Login == $data['Login']) {
				$textcolor = '!fsi!';
				$linestyle = array('class' => 'linehl', 'margin' => array(0.5,0.5));
			} else {
				$linestyle = array('margin' => array(0.5,0.5));
			}

			$window->Line($linestyle);
			if($lr) $window->Cell('', $paddingsize.'%');
			$window->Cell($data['Rank'].'.', $ranksize.'%', null, array('halign' => 'right'));
			$window->Cell(Core::getObject('tparse')->toRaceTime($data['Best']), $timesize.'%', null, array('textcolor' => $textcolor));
			if($exp) $window->Cell($data['NickName'], $nicksize.'%', null, array('halign' => 'left'));
			if(!$lr) $window->Cell('', $paddingsize.'%');
			++$key;
		}
	}

	public function onMLADedimaniaChangeExp($params)
	{
		$params[0]->cdata[$this->id]['exp'] = ($params[0]->cdata[$this->id]['exp']) ? false : true;
		Core::getObject('manialink')->updateContainer('SidebarA', $params[0]);
	}

	public function onMLADedimaniaChangeMax($params) {
		$params[0]->cdata[$this->id]['max'] = ($params[0]->cdata[$this->id]['max']) ? false : true;
		Core::getObject('manialink')->updateContainer('SidebarA', $params[0]);
	}

	public function onMLADedimaniaRO($params)
	{
		if(is_null($params)) {
			return ($this->readonly) ? 'Disable readonly mode' : 'Enable readonly mode';
		}

		if(!Core::getObject('live')->isAdmin($params[0]->Login) || !Core::getObject('live')->checkPerm('globalmaintenance', $params[0]->Login, 'En/disable Dedimania readonly mode')) return;
		if($this->readonly) {
			$this->readonly = false;
			Core::getObject('live')->addMsg('Dedimania readonly mode !hl!disabled');
		} else {
			$this->readonly = true;
			Core::getObject('live')->addMsg('Dedimania readonly mode !hl!enabled');
		}
	}

	/*
	 * Methods
	 */
	private function handleResponse($response, $key)
	{
		if(is_array($response[$key][0]) && is_array($response[$key][0]['Records']) && !empty($response[$key][0]['Records'])) {
			$this->maxrecords	= (int) $response[$key]['ServerMaxRecords'];
			$this->data			= array();
			$this->dataids		= array();
			foreach($response[$key][0]['Records'] AS $record)
			{
				$this->data[(string) $record['Login']] = array(
					'Login'		=> (string) $record['Login'],
					'NickName'	=> (string) $record['NickName'],
					'Best'		=> (int) $record['Best'],
					'Rank'		=> (int) $record['Rank'],
					'Checks'	=> (array) $record['Checks']
				);

				//fill id array
				//this is like a search index, allows to fetch players data for a rank very quickly
				//example: $this->data[$this->dataids[$ranknumber]]
				$this->dataids[(int) $record['Rank']] = (string) $record['Login'];
			}
		}
	}

	private function getPlayers()
	{
		$players = array();
		if(!empty(Core::getObject('players')->players)) {
			foreach(Core::getObject('players')->players AS $player)
			{
				$players[] = array(
					'Login'		=> (string) $player->Login,
					'Nation'	=> (string) $this->nation,
					'TeamName'	=> (string) $player->TeamId,
					'TeamId'	=> (int) $player->TeamId,
					'IsSpec'	=> (bool) $player->SpectatorStatus['Spectator'],
					'Ranking'	=> (int) $player->LadderRanking,
					'IsOff'		=> (bool) $player->LadderRanking
				);
			}
		}
		return $players;
	}

	private function getAuthInfo()
	{
		return array(
			'Game'		=> 'TMF',
			'Login'		=> Core::getObject('session')->server->login,
			'Password'	=> Core::getObject('session')->server->connection['communitycode'],
			'Tool'		=> 'RCP',
			'Version'	=> $this->version,
			'Nation'	=> $this->nation,
			'Packmask'	=> (Core::getObject('status')->packmask) ? Core::getObject('status')->packmask : 'United',
		);
	}

	private function getSrvInfo()
	{
		if(Core::getObject('gbx')->query('GetServerOptions')) {
			$serveroptions = Core::getObject('gbx')->getResponse();
		}

		$current = Core::getObject('challenges')->getCurrent();
		if(!Core::getObject('challenges')->check($current)) return;

		return array(
			'SrvName'		=> (string) Core::getObject('session')->server->name,
			'Comment'		=> (string) $serveroptions['Comment'],
			'Private'		=> (empty($serveroptions['Password'])) ? false : true,
			'SrvIP'			=> (string) Core::getObject('status')->systeminfo['PublishedIp'],
			'SrvPort'		=> (int) Core::getObject('status')->systeminfo['Port'],
			'XmlrpcPort'	=> (int) Core::getObject('session')->server->connection['port'],
			'NumPlayers'	=> (int) 0, //this info need to be inserted, it's just a dummy :[
			'MaxPlayers'	=> (int) $serveroptions['CurrentMaxPlayers'],
			'NumSpecs'		=> (int) 0, //this info need to be inserted, it's just a dummy :[
			'MaxSpecs'		=> (int) $serveroptions['CurrentMaxSpectators'],
			'LadderMode'	=> (int) $serveroptions['CurrentLadderMode'],
			'NextFiveUID'	=> (string) $current->Uid
		);
	}

	private function queryDedimania($nfostr)
	{
		if($this->dedimania->query()) {
			$response = $this->dedimania->getResponse();
			if($this->checkForErrors($response, $nfostr)) {
				return $response;
			}
		} else {
			trigger_error($nfostr.' | '. $this->dedimania->getErrorCode().':'.$this->dedimania->getErrorMessage(), E_USER_WARNING);
		}
		return false;
	}

	private function checkForErrors($response)
	{
		if(!is_array($response)) return;
		$errors = 0;
		foreach($response AS $array)
		{
			if(is_array($array[0]) && array_key_exists('methods', $array[0]) && is_array($array[0]['methods'])) {
				foreach($array[0]['methods'] AS $method)
				{
					if($method['errors']) {
						trigger_error($nfostr.' | Dedimania - '. $method['errors'], E_USER_WARNING);
						++$errors;
					}
				}
			}
		}
		return ($errors) ? false : true;
	}

	private function checkCheckpoints($checkpoints, $login)
	{
		if(!is_array($checkpoints)) {
			trigger_error("Dedimania checkpoints check - {$login}: Checkpoints is not an array", E_USER_WARNING);
			return false;
		}

		$lastKey	= 0;
		$lastValue	= 0;
		foreach($checkpoints AS $key => $value)
		{
			if($key < $lastKey) {
				trigger_error("Dedimania checkpoints check - {$login}: checkpoint #{$key} is not incremental", E_USER_WARNING);
				return false;
			}

			if($value < 0 || $value < $lastValue) {
				trigger_error("Dedimania checkpoints check - {$login}: checkpoint #{$key} time is null or not incremental", E_USER_WARNING);
				return false;
			}

			$lastKey	= $key;
			$lastValue	= $value;
		}
		return true;
	}

	private function sortDataCB($a, $b)
	{
		//If score is equal
		if($a['Best'] == $b['Best']) {
			//Get player objects
			$playerA = Core::getObject('players')->get($a['Login']);
			$playerB = Core::getObject('players')->get($b['Login']);
			if(!Core::getObject('players')->check($playerA) || !Core::getObject('players')->check($playerB)) return 0;

			//Sort by personal best
			if($playerA->getCurrentRecord() != $playerB->getCurrentRecord()) {
				return($playerA->getCurrentRecord() < $playerB->getCurrentRecord()) ? -1 : 1;
			}

			//Sort by playerUID as last possible option
			return ($playerA->PlayerId > $playerB->PlayerId) ? -1 : 1;
		}

		//Sort by score
		return ($a['Best'] < $b['Best']) ? -1 : 1;
	}

	/**
	* Returns the dedimania rank to the current available dedimania data
	*
	* @param $login string
	* @param $score int
	* @author hal.sascha
	* @return // null = no output // false = error // int = rank
	*/
	private function getDataRank($login, $score)
	{
		//Check data
		if(empty($this->data)) return 1;

		//Check score against badest/last record, return null if score is bader
		$lastkey = count($this->dataids);
		if($score > $this->data[$this->dataids[$lastkey]]['Best']) return null;

		//Check score against currently available player rank, return null if current record is better
		if($score > $this->data[$login]['Best']) return null;

		//Get rank
		foreach($this->data AS $key => $value)
		{
			if($value['Best'] > $score) return $value['Rank'];
		}
		return false;
	}

	/**
	* CrossPlugin Method, returns the dedimania data as array
	*
	* @author hal.sascha
	*/
	public function getData()
	{
		if(empty($this->data)) return false;
		return $this->data;
	}
}
?>