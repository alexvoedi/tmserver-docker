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
class SplitTimes extends rcp_liveplugin
{
	public  $title		= 'SplitTimes';
	public  $author		= 'hal.ko.sascha';
	public  $version	= '4.0.3.6';

	//Data
	private $data		= array();
	private $sectors	= 4;
	private $checkpoints= 0;
	private $finished	= false;

	public function onLoadSettings($settings)
	{
		$this->sectors = (int) $settings->sectors;
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

		//Set default data
		$this->data			= array();
		$this->checkpoints	= false;
		$this->finished		= false;

		//Update container
		Core::getObject('manialink')->updateContainer('SidebarB');
	}

	public function onPlayerCheckpoint($params)
	{
		$player = Core::getObject('players')->get($params[1]);
		if(!Core::getObject('players')->check($player)) return;

		$score		= $params[2];
		$lap		= $params[3];
		$checkpoint	= $params[4];
		$textcolor	= '!fhl!';

		//Update split times by player
		$updateit = false;
		if(array_key_exists($checkpoint, $player->cdata[$this->id]['splittimes'])) {
			if($player->cdata[$this->id]['splittimes'][$checkpoint] > $score) {
				$updateit = true;
			}
		} else {
			$updateit = true;
		}

		if($updateit) {
			$player->cdata[$this->id]['splittimes'][$checkpoint] = $score;
		}

		//Update split times
		$updateit = false;
		if(array_key_exists($checkpoint, $this->data)) {
			if($this->data[$checkpoint]['Score'] > $score) {
				$updateit = true;
			}
		} else {
			$updateit = true;
		}

		if($updateit) {
			Core::getObject('manialink')->updateContainer('SidebarB');
			$this->data[$checkpoint] = array(
				'NickName'	=> $player->NickName,
				'Login'		=> $player->Login,
				'Score'		=> $score,
				'Lap'		=> $lap
			);
		}

		//Check if checkpoint-count is available
		if(!$this->checkpoints || !$this->finished) return;

		//Update container
		Core::getObject('manialink')->updateContainer('SidebarB', $player);
	}

	public function onPlayerFinish($params)
	{
		if(empty($this->data) || empty($params[2])) return;
		$this->checkpoints	= count($this->data);
		$this->finished		= true;
	}

	public function onNewPlayer($player)
	{
		//Set recordsUI defaults
		$player->cdata[$this->id]['exp'] = true;
		$player->cdata[$this->id]['splittimes'] = array();
		Core::getObject('manialink')->updateContainer('SidebarB', $player);
	}

	public function onMLContainerSidebarB($params)
	{
		if(empty($this->data) || !$this->finished) return;
		$player = $params[0];
		$window = $params[1];

		//Calculate sectors
		$i			= 1;
		$sector		= 1;
		$sectorstep	= round($this->checkpoints / $this->sectors, 0);
		$sectorlt	= 0;
		$playerlt	= 0;

		//Update manialink
		$exp = $player->cdata[$this->id]['exp'];

		$framesize   = $exp ? 20 : 10;
		$paddingsize = $exp ? 10 : 20;
		$ranksize    = $exp ? 8  : 16;
		$timesize    = $exp ? 30 : 64;
		$nicksize    = $exp ? 32 : 0;
		$pmtimesize  = $exp ? 20 : 0;

		if(Core::getObject('status')->gameinfo['GameMode'] == 1) {
			$fpx = 0;
			$lr  = true;
			$icA = 'ArrowPrev';
			$icB = 'ArrowNext';
		} else {
			$fpx = ($exp) ? 0 : 10;
			$lr  = false;
			$icA = 'ArrowNext';
			$icB = 'ArrowPrev';
		}

		$window->Frame($fpx, 0, $framesize, array('class' => 'tmf'), false, false);
		$window->Line();
		if($lr) $window->Cell('', $paddingsize.'%', false);
		$window->Cell('', $ranksize.'%');
		if($exp) $window->Cell('Split times', $nicksize.'%');
		$window->Cell('', array(2,2));
		$window->Cell('', array(2,2), 'onMLASplitTimesChangeExp', array('style' => 'Icons64x64_1', 'substyle' => $exp ? $icA : $icB));
		if(!$lr) $window->Cell('', $paddingsize.'%', false);

		foreach($this->data AS $key => $data)
		{
			if($i == ($sector * $sectorstep) || $i == $this->checkpoints || $this->checkpoints < $this->sectors) {
				$stime  = $data['Score'] - $sectorlt;
				$ptime  = $player->cdata[$this->id]['splittimes'][$key] - $playerlt;
				$pmtime = $stime - $ptime;

				$window->Line();
				if($lr) $window->Cell('', $paddingsize.'%');
				$window->Cell($sector.'.', $ranksize.'%', null, array('halign' => 'right'));
				$window->Cell(Core::getObject('tparse')->toRaceTime($stime), $timesize.'%', null, array('textcolor' => $textcolor));
				if($exp) $window->Cell($data['NickName'], $nicksize.'%', null, array('halign' => 'left'));
				if($exp) $window->Cell(Core::getObject('tparse')->toRaceTime($pmtime, true), $pmtimesize.'%');
				if(!$lr) $window->Cell('', $paddingsize.'%');

				++$sector;
				$sectorlt = $data['Score'];
				$playerlt = $player->cdata[$this->id]['splittimes'][$key];
			}
			++$i;
		}
	}

	public function onMLASplitTimesChangeExp($params)
	{
		$params[0]->cdata[$this->id]['exp'] = ($params[0]->cdata[$this->id]['exp']) ? false : true;
		Core::getObject('manialink')->updateContainer('SidebarB', $params[0]);
	}
}
?>