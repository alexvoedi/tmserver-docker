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
class Records extends rcp_liveplugin
{
	public  $title			= 'Records';
	public  $author			= 'hal.ko.sascha';
	public  $version		= '4.0.3.6';
	private $infomaxranks	= 50;

	//RecordsUI
	private $data			= array();
	private $dataids		= array();
	private $minrecdisp		= 3;
	private $maxrecdisp		= 8;
	private $recdisp		= false;

	public function onLoadSettings($settings)
	{
		$this->infomaxranks	= (int) $settings->infomaxranks;
		$this->minrecdisp	= (int) $settings->ui->minrecdisp;
		$this->maxrecdisp	= (int) $settings->ui->maxrecdisp;
		$this->recdisp		= ((int) $settings->ui->enabled) ? true : false;
	}

	public function onLoad()
	{
		$records = Plugins::getPlugin('Menu')->menu->add('records', 'Records', 'Extreme', 40);
		if($records !== false) {
			$records->add('locals', 'Local', false, 1, 'onMLRecords');
		}
	}

	public function onBeginChallenge()
	{
		$challenge = Core::getObject('challenges')->getCurrent();
		if(!Core::getObject('challenges')->check($challenge)) return;

		//Update rec panel container
		if($this->recdisp) {
			$this->GetRecords();
		}

		//Output top record
		$top = $challenge->getRecord('Score');
		if($top) {
			Core::getObject('live')->addMsg("Current record on {$challenge->Name}!df!: !hl!". Core::getObject('tparse')->toRaceTime($top) ."!df! by ". $challenge->getRecord('NickName'));
		}

		//Read players PB
		if(empty(Core::getObject('players')->players)) return;
		foreach(Core::getObject('players')->players AS $player)
		{
			$this->getPlayerPB($player);
		}
	}

	public function onPlayerCheckpoint($params)
	{
		$score		= $params[2];
		$lap		= $params[3];
		$checkpoint	= $params[4];
		$challenge	= Core::getObject('challenges')->getCurrent();
		$player		= Core::getObject('players')->get($params[1]);
		if(!$score || !Core::getObject('challenges')->check($challenge) || !Core::getObject('players')->check($player)) return;

		//Get best checkpoint
		$cp = $challenge->getBestCheckPoint($checkpoint, $lap);
		if(empty($cp)) return;

		//Get score value
		$newscore	= $score - $cp;
		$tmpscore	= Core::getObject('tparse')->toRaceTime($newscore);
		$tmpscore	= ($newscore > 0) ? '$o$C00+'.$tmpscore : '$o$0C0'.$tmpscore;

		//Update checkpoint ML window
		$window = $player->ManiaFWK->getWindow('MLRecordsCPTime');
		if(!$window) return;
		$window->Reset();
		$window->Line();
		$window->Cell($tmpscore, '100%', null, array('halign' => 'center'));
	}

	public function onPlayerFinish($params)
	{
		if(empty($params[1])) return;
		$challenge	=& Core::getObject('challenges')->getCurrent();
		$player		= Core::getObject('players')->get($params[1]);
		$newScore	= $params[2];
		if(!Core::getObject('players')->check($player) || empty($player->Id) || !Core::getObject('challenges')->check($challenge) || empty($challenge->Id) || !$newScore) return;

		//Insert/Update record
		//$tmpScore	= (Core::getObject('status')->gameinfo['GameMode'] == 4) ? 0 : 999999999999;
		//$oldScore	= empty($pbScore) ? $tmpScore : $pbScore;
		$pbScore = $player->getRecord();
		$player->setRecord($newScore);

		if(empty($pbScore) || (Core::getObject('status')->gameinfo['GameMode'] != 4 && $pbScore > $newScore) || (Core::getObject('status')->gameinfo['GameMode'] == 4 && $pbScore < $newScore)) {
			//Get db connection
			$db = Core::getObject('db')->getConnection();

			//Insert new record
			$insert = $db->prepare("
				INSERT INTO rcp_records
				(ChallengeId, PlayerId, Score, CheckPoints, Date)
				VALUES
				(:challengeid, :playerid, :score, :checkpoints, NOW())
				ON DUPLICATE KEY UPDATE Id = LAST_INSERT_ID(Id), ChallengeId = VALUES(ChallengeId), PlayerId = VALUES(PlayerId), Score = VALUES(Score), CheckPoints = VALUES(CheckPoints), Date = VALUES(Date)
			");
			$insert->bindParam('challengeid', $challenge->Id);
			$insert->bindParam('playerid', $player->Id);
			$insert->bindParam('score', $newScore);
			$insert->bindParam('checkpoints', implode(':', $player->getCheckpoints()));
			$insert->execute();
			if(!$insert) return;
			$insert->closeCursor();
			$insert = null;

			//Check if new record is top record
			$order  = (Core::getObject('status')->gameinfo['GameMode'] == 4) ? '>' : '<';
			$select = $db->prepare("
				SELECT COUNT(Id) as Rank
				FROM rcp_records
				WHERE ((Score {$order} :scorea) || (Score = :scoreb)) && ChallengeId = :challengeid
			");
			$select->bindParam('scorea', $newScore);
			$select->bindParam('scoreb', $newScore);
			$select->bindParam('challengeid', $challenge->Id);
			$select->execute();
			if(!$select) return;
			$row	= $select->fetch(PDO::FETCH_OBJ);
			$rank	= $row->Rank;
			$select->closeCursor();
			$select = null;

			//Update record panel
			if($rank <= $this->maxrecdisp) {
				$this->GetRecords();
			}

			if($rank <= 1) {
				$challenge->setRecord($player->Login, $player->NickName, $newScore);
				Core::getObject('live')->addMsg("!hl!New top record on {$challenge->Name}!hl!: ". Core::getObject('tparse')->toRaceTime($challenge->getRecord('Score')) ."!hl! by ". $challenge->getRecord('NickName'));

				//Set BestCheckPoints
				$player->transferCheckpointsToChallenge($challenge);
			} elseif($rank <= $this->infomaxranks) {
				Core::getObject('live')->addMsg("Record added from {$player->NickName}!df!: ". Core::getObject('tparse')->toRaceTime($newScore) ." (New rank: !hl!{$rank}!df!)");
			}
		}
	}

	public function onNewPlayer($player)
	{
		$window = $player->ManiaFWK->addWindow('MLRecordsCPTime', ' ', -10, 17, 20);
		if($window) {
			$window->setOption('timeout', 2);
			$window->setOption('header', false);
			$window->setOption('bg', false);
		}

		//Set recordsUI defaults
		$player->cdata[$this->id]['exp'] = true;
		$player->cdata[$this->id]['max'] = false;
		Core::getObject('manialink')->updateContainer('SidebarA', $player);

		//Get PB
		$this->getPlayerPB($player);
	}

	public function onMLRecords($params)
	{
		$window = $params[0]->ManiaFWK->getWindow('MLWindow');
		if(!$window) return;
		$window->setOption('title', 'Top Records');
		$window->setOption('icon', 'Medium');
		$window->Reset();
		$challenge =& Core::getObject('challenges')->getCurrent();
		if(!Core::getObject('challenges')->check($challenge)) return;

		//Get db connection
		$db = Core::getObject('db')->getConnection();

		//Top Records
		$select = $db->prepare("SELECT COUNT(Id) AS Count FROM rcp_records WHERE ChallengeId = :challengeid");
		$select->bindParam('challengeid', $challenge->Id);
		$select->execute();
		$row = $select->fetch(PDO::FETCH_OBJ);
		$select->closeCursor();
		$select = null;

		$entries = 18;
		$max	 = $row->Count;
		$index	 = ($params[1]+0 < 0) ? 0 : $params[1]+0;
		$index	 = ($index > $max) ? $max - $entries: $index;
		$end	 = $index+$entries;
		$end	 = ($end >= $max) ? $max : $end;

		//Check if record data is available
		if(!$max) {
			$window->Line();
			$window->Cell('no records available', '100%', null, array('halign' => 'center'));
			return;
		}

		//Personal best
		$select2 = $db->prepare("
			SELECT a.Score, DATE_FORMAT(a.Date,'%d-%m-%y %H:%i') AS Date, b.NickName
			FROM rcp_records AS a
			LEFT JOIN rcp_players AS b
				ON a.PlayerId = b.Id
			WHERE a.ChallengeId = :challengeid && a.PlayerId = :playerid
			ORDER BY a.Score {$order}
			LIMIT 0,1
		");
		$select2->bindParam('challengeid', $challenge->Id);
		$select2->bindParam('playerid', $params[0]->Id);
		$select2->execute();

		$pb = $select2->fetch(PDO::FETCH_OBJ);
		if($pb->Score) {
			$window->Line();
			$window->Cell('>>>', '10%', null, array('halign' => 'center'));
			$window->Cell('Personal Best:', '40%', null, array('textcolor' => '!fsi!'));
			$window->Cell($pb->Date, '25%', null, array('halign' => 'center'));
			$window->Cell(Core::getObject('tparse')->toRaceTime($pb->Score), '25%', null, array('halign' => 'right','textcolor' => '!fhl!'));
		}
		$select2->closeCursor();
		$select2 = null;

		$order = (Core::getObject('status')->gameinfo['GameMode'] == 4) ? 'desc' : 'asc';
		$select = $db->prepare("
			SELECT a.Score, DATE_FORMAT(a.Date,'%d-%m-%y %H:%i') AS Date, b.NickName, b.Login
			FROM rcp_records AS a
			LEFT JOIN rcp_players AS b
				ON a.PlayerId = b.Id
			WHERE a.ChallengeId = :challengeid
			ORDER BY a.Score {$order}, a.Date asc, a.PlayerId asc
			LIMIT {$index},{$entries}
		");
		$select->bindParam('challengeid', $challenge->Id);
		$select->execute();

		$window->Line(array('class' => 'thead'));
		$window->Cell('Pos', '10%', null, array('halign' => 'center'));
		$window->Cell('NickName', '40%');
		$window->Cell('Date', '25%', null, array('halign' => 'center'));
		$window->Cell('Record', '25%', null, array('halign' => 'right'));
		if($select->columnCount()) {
			$i = 0;
			while($data = $select->fetch(PDO::FETCH_OBJ))
			{
				++$i;
				$window->Line();
				$window->Cell($i+$index, '10%', null, array('halign' => 'center'));
				$window->Cell($data->NickName, '40%', array('onMLPlayersDetails', array($data->Login,$index,'onMLRecords')));
				$window->Cell($data->Date, '25%', null, array('halign' => 'center'));
				$window->Cell(Core::getObject('tparse')->toRaceTime($data->Score), '25%', null, array('halign' => 'right'));
			}

			$prev = ($index <= 0) ? false : true;
			$next = ($index >= ($max-$entries)) ? false : true;
			if($prev || $next) {
				$window->Line();
				$window->Cell('previous', '25%', array('onMLRecords',$index-$entries), array('hidecell' => !$prev,'class' => 'btn2n'));
				$window->Cell('', '50%');
				$window->Cell('next', '25%', array('onMLRecords',$index+$entries), array('hidecell' => !$next,'class' => 'btn2n'));
			}
		}
		$select->closeCursor();
		$select = null;
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
		if($exp) $window->Cell('Local Records', $nicksize.'%');
		$window->Cell('', array(2,2), 'onMLARecordsChangeMax', array('style' => 'Icons64x64_1', 'substyle' => $max ? 'ArrowUp' : 'ArrowDown'));
		$window->Cell('', array(2,2), 'onMLARecordsChangeExp', array('style' => 'Icons64x64_1', 'substyle' => $exp ? $icA : $icB));
		if(!$lr) $window->Cell('', $paddingsize.'%', false);

		foreach($this->data AS $record)
		{
			if(!$max && $record->Rank > $this->minrecdisp) {
				break;
			} elseif($max && $record->Rank > $this->maxrecdisp) {
				break;
			}

			if($player->Id == $record->PlayerId) {
				$personalb = true;
				$textcolor = '!fsi!';
				$linestyle = array('class' => 'linehl', 'margin' => array(0.5,0.5));
			} else {
				$linestyle = array('margin' => array(0.5,0.5));
			}

			$window->Line($linestyle);
			if($lr) $window->Cell('', $paddingsize.'%');
			$window->Cell($record->Rank.'.', $ranksize.'%', null, array('halign' => 'right'));
			$window->Cell(Core::getObject('tparse')->toRaceTime($record->Score), $timesize.'%', null, array('textcolor' => $textcolor));
			if($exp) $window->Cell($record->NickName, $nicksize.'%', null, array('halign' => 'left'));
			if(!$lr) $window->Cell('', $paddingsize.'%');
		}

		//Display PersonalBest if it was not in displayed records
		if($personalb === true) return;

		//Get players current dataset
		$pbdata = array_key_exists($player->Id, $this->data) ? $this->data[$player->Id] : false;
		if(!$pbdata) return;

		//Display seperator
		$window->Line();
		if($lr) $window->Cell('', $paddingsize.'%');
		$window->Cell('...', '100%');

		//Vars
		$key		= $pbdata->Rank - 1;
		$textcolor	= '!fhl!';
		$linestyle	= false;
		for($i = 2; $i != 0; --$i)
		{
			if(!array_key_exists($key, $this->dataids)) break;
			$data = $this->data[$this->dataids[$key]];

			if($player->Id == $data->PlayerId) {
				$textcolor = '!fsi!';
				$linestyle = array('class' => 'linehl', 'margin' => array(0.5,0.5));
			} else {
				$linestyle = array('margin' => array(0.5,0.5));
			}

			$window->Line($linestyle);
			if($lr) $window->Cell('', $paddingsize.'%');
			$window->Cell($data->Rank.'.', $ranksize.'%', null, array('halign' => 'right'));
			$window->Cell(Core::getObject('tparse')->toRaceTime($data->Score), $timesize.'%', null, array('textcolor' => $textcolor));
			if($exp) $window->Cell($data->NickName, $nicksize.'%', null, array('halign' => 'left'));
			if(!$lr) $window->Cell('', $paddingsize.'%');
			++$key;
		}
	}

	public function onMLARecordsChangeExp($params)
	{
		$params[0]->cdata[$this->id]['exp'] = ($params[0]->cdata[$this->id]['exp']) ? false : true;
		Core::getObject('manialink')->updateContainer('SidebarA', $params[0]);
	}

	public function onMLARecordsChangeMax($params)
	{
		$params[0]->cdata[$this->id]['max'] = ($params[0]->cdata[$this->id]['max']) ? false : true;
		Core::getObject('manialink')->updateContainer('SidebarA', $params[0]);
	}

	/*
	 * Class methods
	 */
	public function GetRecords()
	{
		if(!$this->recdisp) return;

		$this->data		= array();
		$this->dataids	= array();
		$challenge = Core::getObject('challenges')->getCurrent();
		if(!Core::getObject('challenges')->check($challenge)) return;

		//Get db connection
		$db = Core::getObject('db')->getConnection();

		//Get data
		$order = (Core::getObject('status')->gameinfo['GameMode'] == 4) ? 'desc' : 'asc';
		$db->exec("SET @rank = 0");
		$select = $db->prepare("
			SELECT r.PlayerId, p.Login, p.NickName, r.Score, (@rank:=(@rank+1)) as Rank 
			FROM rcp_records As r
			LEFT JOIN rcp_players AS p
				ON r.PlayerId = p.Id
			WHERE r.ChallengeId = :challengeid
			ORDER BY r.Score {$order}, r.Date asc, r.PlayerId asc
			LIMIT 0,{$this->infomaxranks}
		");
		$select->bindParam('challengeid', $challenge->Id);
		$select->execute();
		if(!$select || !$select->columnCount()) return;
		while($data = $select->fetch(PDO::FETCH_OBJ))
		{
			$this->data[$data->PlayerId] = $data;

			//fill id array
			//this is like a search index, allows to fetch players data for a rank very quickly
			//example: $this->data[$this->dataids[$ranknumber]]
			$this->dataids[$data->Rank]  = $data->PlayerId;
		}

		//Update container
		Core::getObject('manialink')->updateContainer('SidebarA');
	}

	private function getPlayerPB($player)
	{
		//Check player object
		if(!Core::getObject('players')->check($player)) return;

		//Get current challenge
		$challenge = Core::getObject('challenges')->getCurrent();
		if(!Core::getObject('challenges')->check($challenge)) return;

		//Get PB from database
		$select = Core::getObject('db')->getConnection()->prepare("
			SELECT Score AS pb
			FROM rcp_records
			WHERE ChallengeId = :challengeid && PlayerId = :playerid
		");
		$select->bindParam('challengeid', $challenge->Id);
		$select->bindParam('playerid', $player->Id);
		$select->execute();
		if(!$select) return;

		$record = $select->fetch(PDO::FETCH_OBJ);
		$player->setRecord($record->pb);
		$select->closeCursor();
		$select = null;
	}
}
?>