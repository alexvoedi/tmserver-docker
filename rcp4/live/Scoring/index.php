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
class Scoring extends rcp_liveplugin
{
	public  $title		= 'Scoring';
	public  $author		= 'hal.ko.sascha';
	public  $version	= '4.0.3.6';

	//Data
	private $maxdata	= 10;
	private $data		= array();
	private $points		= array();
	private $match		= array();
	private $running	= false; //defines if the match mode is currently active, can be set over the admin panel!
	private $warmup		= false;

	public function onLoad()
	{
		$this->addAdminOption('onMLAScoringResetMatch');
		$this->addAdminOption('onMLAScoringStartStopMatch');
	}

	public function onBeginChallenge($params)
	{
		$this->warmup = $params[1];
	}

	public function onBeginRace() {
		//Set plugin inactive, if gamemode is not...
		if(	Core::getObject('status')->gameinfo['GameMode'] == 0 || //rounds
			Core::getObject('status')->gameinfo['GameMode'] == 2) { //team
			$this->setActive(true);
		} else {
			$this->setActive('onBeginRace');
			return;
		}

		//Update point settings
		if(Core::getObject('gbx')->query('GetRoundCustomPoints')) {
			$this->points = Core::getObject('gbx')->getResponse();
			if(empty($this->points)) $this->points = array(10,6,4,3,2,1);
		}

		//Update match array / add current map
		$this->updateMatch(false);
	}

	public function onBeginRound()
	{
		//Reset data
		$this->data = array();

		//Update container
		Core::getObject('manialink')->updateContainer('SidebarB');
	}

	public function onPlayerFinish($params)
	{
		//Validate params
		if(empty($params[2])) return;
		$player = Core::getObject('players')->get($params[1]);
		if(!Core::getObject('players')->check($player)) return;

		//Fill data array
		$this->data[] = array(
			'TeamId'	=> $player->TeamId,
			'NickName'	=> $player->NickName,
			'Login'		=> $player->Login,
			'Score'		=> $params[2]
		);

		//Update container
		Core::getObject('manialink')->updateContainer('SidebarB');

		//ReSort data array
		usort($this->data, array($this, 'sortDataCB'));
	}

	public function onEndRound()
	{
		//Update team data
		if(Core::getObject('status')->gameinfo['GameMode'] == 2 && !empty($this->data)) {
			$mapkey		= count($this->match['maps'])-1; //current map is allways last map
			$ptTeamA	= 0;
			$ptTeamB	= 0;
			foreach($this->data AS $key => $player)
			{
				//Leave if all remaining positions getting no points
				if(!array_key_exists($key, $this->points)) break;

				//Add points to TeamA
				if($player['TeamId'] == $this->match['TeamA']) {
					$ptTeamA = $ptTeamA + $this->points[$key];
				}
				//Add points to TeamB
				elseif($player['TeamId'] == $this->match['TeamB']) {
					$ptTeamB = $ptTeamB + $this->points[$key];
				}
			}

			//Assign data
			$round = $this->match['maps'][$mapkey]['Round']+1;
			$this->match['maps'][$mapkey]['Round']			= $round;
			$this->match['maps'][$mapkey]['TeamA'][$round]	= $ptTeamA+0;
			$this->match['maps'][$mapkey]['TeamB'][$round]	= $ptTeamB+0;
		}

		//Update container
		Core::getObject('manialink')->updateContainer('SidebarB');
	}

	public function onNewPlayer($player)
	{
		//Remove default trackmania UI for round_scores
		$player->ManiaFWK->setCustomUi('round_scores', false);

		//Set recordsUI defaults
		Core::getObject('manialink')->updateContainer('SidebarB', $player);
	}

	public function onMLContainerSidebarB($params)
	{
		//Check and validate params / data
		$player = $params[0];
		$window = $params[1];

		$textcolor   = '!fhl!';
		$linestyle   = false;
		$framesize   = 20;
		$paddingsize = 10;
		$pointssize  = 8;
		$timesize    = 30;
		$nicksize    = 52;

		//Display round scores
		if(!empty($this->data)) {
			$fpx = 0;
			$icA = 'ArrowPrev';
			$icB = 'ArrowNext';

			$window->Frame($fpx, 0, $framesize, array('class' => 'tmf'), false, false);
			$window->Line();
			$window->Cell('', $paddingsize.'%', false);
			$window->Cell('', $pointssize.'%');
			$window->Cell('Results', $nicksize.'%');

			$i = 1;
			foreach($this->data AS $key => $result)
			{
				$points = array_key_exists($key, $this->points) ? $this->points[$key] : 0;
				if($player->Login == $result['Login']) {
					$textcolor = '!fsi!';
					$linestyle = array('class' => 'linehl', 'margin' => array(0.5,0.5));
				} else {
					$linestyle = array('margin' => array(0.5,0.5));
				}

				$window->Line($linestyle);
				$window->Cell('', $paddingsize.'%');
				$window->Cell($points, $pointssize.'%', null, array('halign' => 'right'));
				$window->Cell(Core::getObject('tparse')->toRaceTime($result['Score']), $timesize.'%', null, array('textcolor' => $textcolor));
				$window->Cell($result['NickName'], $nicksize.'%', null, array('halign' => 'left'));

				if($i == $this->maxdata) break;
				++$i;
			}
		}

		//Display matchscore
		if($this->running) {
			$window->Frame($fpx, 0, $framesize, array('class' => 'tmf'), false, false);
			$window->Line();
			$window->Cell('', $paddingsize.'%', false);
			$window->Cell('', $pointssize.'%');
			$window->Cell('Matchscore', $nicksize.'%');

			$i = 1;
			foreach($this->match['maps'] AS $key => $result)
			{
				$window->Line(false);
				$window->Cell('', $paddingsize.'%');
				$window->Cell('R'.$i, $pointssize.'%', null, array('halign' => 'right'));

				$array = $this->getRoundScore($result);
				$window->Cell("\$00F {$array[0]}\$z -\$F00 {$array[1]}", $timesize.'%');
				$window->Cell("\$00F (".array_sum($result['TeamA']).")\$z -\$F00 (".array_sum($result['TeamB']).")", $nicksize.'%');
				++$i;
			}
		}
	}

	public function onMLAScoringStartStopMatch($params)
	{
		if(is_null($params)) {
			return $this->running ? 'Stop current match' : 'Start current match';
		}

		if(!Core::getObject('live')->isAdmin($params[0]->Login) || !Core::getObject('live')->checkPerm('globalmaintenance', $params[0]->Login, 'Start/stop current match')) return;
		if($this->running) {
			$this->running = false;
			Core::getObject('live')->addMsg('Match successfully !hl!stopped');
		} else {
			$this->running = true;
			Core::getObject('live')->addMsg('Match successfully !hl!started');
		}
	}

	public function onMLAScoringResetMatch($params)
	{
		if(is_null($params)) {
			return 'Reset current match data';
		}

		if(!Core::getObject('live')->isAdmin($params[0]->Login) || !Core::getObject('live')->checkPerm('globalmaintenance', $params[0]->Login, 'reset match scoring')) return;
		if($this->running) {
			Core::getObject('chat')->send('Unable to reset the match data, because match is still running', false, $params[0]->Login);
			return;
		}
		$this->updateMatch(true);
		Core::getObject('live')->addMsg('Matchdata !hl!reset !df!succesfull');
	}

	private function getRoundScore($results)
	{
		if(empty($results)) return array(0,0);
		$teamA = 0;
		$teamB = 0;
		foreach($results['TeamA'] AS $key => $round)
		{
			if($round > $results['TeamB'][$key]) {
				++$teamA;
			} elseif($round < $results['TeamB'][$key]) {
				++$teamB;
			}
		}
		return array($teamA, $teamB);
	}

	private function sortDataCB($a, $b)
	{
		//If score is equal
		if($a['Score'] == $b['Score']) {
			//Get player objects
			$playerA = Core::getObject('players')->get($a['Login']);
			$playerB = Core::getObject('players')->get($b['Login']);
			if(!Core::getObject('players')->check($playerA) || !Core::getObject('players')->check($playerB)) return 0;

			//Sort by personal best
			if($playerA->getCurrentRecord() != $playerB->getCurrentRecord()) {
				return($playerA->getCurrentRecord() > $playerB->getCurrentRecord()) ? -1 : 1;
			}

			//Sort by playerUID as last possible option
			return ($playerA->PlayerId < $playerB->PlayerId) ? -1 : 1;
		}

		//Sort by score
		return ($a['Score'] < $b['Score']) ? -1 : 1;
	}

	private function updateMatch($reset = false)
	{
		if(Core::getObject('status')->gameinfo['GameMode'] != 2) {
			$this->running = false;
			return;
		}

		//(re)Create match array
		if($reset || empty($this->match) || !$this->running) {
			$this->running	= false;
			$this->match	= array(
				'TeamA'		=> 0, //Blue
				'TeamB'		=> 1, //Red
				'maps'		=> array()
			);
		}

		//Check warmup mode
		if($this->warmup) {
			$this->warmup = false;
			return;
		}

		//Get points settings
		$this->points = array();
		if(Core::getObject('gbx')->query('GetGameInfos', 1)) {
			$GameInfos	= Core::getObject('gbx')->getResponse();
			$Current	= $GameInfos['CurrentGameInfos'];
			if($Current['TeamUseNewRules']) {
				$max = $Current['TeamMaxPoints'] ? $Current['TeamMaxPoints'] : count(Core::getObject('players')->players);
				for($i = $max; $i > 0; --$i) 
				{
					$this->points[] = $i;
				}
			} else {
				$this->points = array(1, 0);
			}
		}

		//Add map
		$cchallenge = Core::getObject('challenges')->getCurrent();
		if(!Core::getObject('challenges')->check($cchallenge)) return;
		$this->match['maps'][] = array(
			'Name'		=> $cchallenge->Name,
			'TeamA'		=> array(),
			'TeamB'		=> array()
		);
	}
}
?>