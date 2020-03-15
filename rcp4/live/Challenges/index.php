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
class Challenges extends rcp_liveplugin
{
	public  $title			= 'Challenges';
	public  $author			= 'hal.ko.sascha';
	public  $version		= '4.0.3.6';
	private $jukeboxenabled	= true;
	private $jukeboxprice	= 20;
	private $jukeboxdata	= array();
	private $voting			= true;
	private $nextchallenge	= true;
	private $adminjukeboxing= false;
	private $curSortLogin	= false;
	private $isRestart		= false;

	public function onLoad()
	{
		$challenges = Plugins::getPlugin('Menu')->menu->add('challenges', 'Challenges', 'Browse', 20);
		if($challenges !== false) {
			$challenges->add('list', 'List', false, 1, 'onMLChallenges');
			$challenges->add('jukebox', 'Jukebox', false, 2, 'onMLChallengesJukebox');
		}
		$this->addAdminOption('onMLAChallengesJukeboxReset');
	}

	public function onLoadSettings($settings)
	{
		$this->jukeboxenabled	= ((int) $settings->jukebox->enabled) ? true : false;
		$this->adminjukeboxing	= ((int) $settings->jukebox->admin) ? true : false;
		$this->jukeboxprice		=  (int) $settings->jukebox->price;
		$this->nextchallenge	= ((int) $settings->popups->nextchallenge) ? true : false;
		$this->voting			= ((int) $settings->popups->voting) ? true : false;
	}

	public function onPeriodicalUpdate()
	{
		$this->getChallengeRating();
	}

	public function onBeginChallenge($params)
	{
		$this->setChallengeInfoDisplay(false);
		$this->getChallengeRating();

		//Jukeboxing
		if(!$this->isRestart && ($this->jukeboxenabled || !empty($this->jukeboxdata))) {
			array_shift($this->jukeboxdata);
		}
		$this->isRestart = false;

		//Reset votes
		if(empty(Core::getObject('players')->players)) return;
		foreach(Core::getObject('players')->players AS $player)
		{
			$player->cdata[$this->id]['civoted'] = 0;
		}
	}

	public function onEndChallenge($params)
	{
		$this->setChallengeInfoDisplay(true);
		$this->getChallengeRating();

		//Check if this is a restart, don't jukebox then
		if($params[4]) {
			$this->isRestart = true;
			return;
		}

		//Jukeboxing
		$challenge = $params[1];
		if($this->jukeboxenabled && count($this->jukeboxdata) && !empty($this->jukeboxdata[0]['challenge']['FileName'])) {
			if($challenge['FileName'] != $this->jukeboxdata[0]['challenge']['FileName']) {
				if(Core::getObject('gbx')->query('ChooseNextChallenge', $this->jukeboxdata[0]['challenge']['FileName'])) {
					$index = Core::getObject('challenges')->getIndexByFileName($this->jukeboxdata[0]['challenge']['FileName']);
					if($index !== false) Core::getObject('challenges')->setNext($index);

					//Trigger PCore::onChallengeListModified with isModified flag
					//Bugfix: because dedicated server don't trigger the isModified flag on "ChooseNextChallenge" calls
					Plugins::getPlugin('PCore')->ForceChallengeListModify();
				}
			}
			Core::getObject('live')->addMsg("Next challenge will be {$this->jukeboxdata[0]['challenge']['Name']}!df! as requested by {$this->jukeboxdata[0]['NickName']}");
		}
	}

	public function onPlayerChat($params)
	{
		$player	= Core::getObject('players')->get($params[1]);
		$input	= $params[2];

		//Handle chatcommand
		if(!$input || !Core::getObject('players')->check($player) || $player->Ignored) return;
		if($input == '++') {
			$this->onMLAChallengesVote(array($player, 1));
		} elseif($input == '--') {
			$this->onMLAChallengesVote(array($player, 0));
		}
	}

	public function onNewPlayer($player)
	{
		$player->ManiaFWK->setCustomUi('challenge_info', false);
		Core::getObject('manialink')->updateContainer('challenge_info');

		//Set initial sorting options
		$player->cdata[$this->id]['listsort'] = array();
		$player->cdata[$this->id]['listsort']['key'] = 'Number';
		$player->cdata[$this->id]['listsort']['value'] = true;
	}

	public function onMLContainerchallenge_info($params)
	{
		$player = $params[0];
		$window = $params[1];

		$nchallenge = Core::getObject('challenges')->getNext();
		$cchallenge = Core::getObject('challenges')->getCurrent();
		if(!Core::getObject('challenges')->check($nchallenge) || !Core::getObject('challenges')->check($cchallenge)) return;

		$width = 0;
		if($this->voting && $player->cdata[$this->id]['ciexp']) {
			$window->Frame(0, 0, 5, false, false, null);
			$window->Line();
			$window->Cell('Vote', '100%');
			$width = $width + 5;

			$window->Frame(0, 0, 20, array('class' => 'tmf'), false, null);
			$window->Line();
			$window->CustomXML("<quad posn='0 0 0' sizen='2.5 2.5' style='Icons128x128_1' substyle='Challenge' />");
			$window->Cell('', '10%');
			$window->Cell($cchallenge->Name, '90%');
			$window->Line();
			if($player->cdata[$this->id]['civoted']) {
				$window->Cell("{$cchallenge->Rating['count']} votes ({$cchallenge->Rating['value']})", '100%',false,array('halign' => 'center'));

				//Calculate Slider Position (middle)
				$sl_pos = 15 / 2;
				$sl_offset = 1;

				if(is_array($cchallenge->Rating) && !empty($cchallenge->Rating['count'])) {
					//Get percent value from votes count and rating value
					$sl_percent = ($cchallenge->Rating['value'] * 100) / $cchallenge->Rating['count'];
					$sl_percent = round($sl_percent);

					//Get slider position
					$sl_posnew = ($sl_pos * $sl_percent) / 100;
					$sl_posnew = $sl_pos + $sl_posnew + $sl_offset;
				} else {
					$sl_posnew = $sl_pos + $sl_offset;
				}

				$window->Line();
				$window->CustomXML("
					<quad posn='{$sl_posnew} 0.5 0' sizen='2 2' style='Icons64x64_1' substyle='YellowLow' />
					<quad posn='{$sl_offset} 1 0' sizen='16 4' style='Icons128x32_1' substyle='SliderBar2' />
				");
				$window->Cell('', '100%');
			} else {
				$window->Cell('', '100%');
				$window->Line();
				$window->Cell('', '10%');
				$window->Cell('GOOD', '35%',array('onMLAChallengesVote',1),array('class' => 'btn2n'));
				$window->Cell('', '10%');
				$window->Cell('BAD', '35%',array('onMLAChallengesVote',0),array('class' => 'btn2n'));
				$window->Cell('', '10%');
			}
			$width = $width + 20;
		}

		if($this->nextchallenge && $player->cdata[$this->id]['ciexp']) {
			$window->Frame(0, 0, 5, false, false, null);
			$window->Line();
			$window->Cell('Next', '100%');
			$width = $width + 5;

			$window->Frame(0, 0, 20, array('class' => 'tmf'), false, null);
			$window->Line();
			$window->CustomXML("<quad posn='0 0 0' sizen='2.5 2.5' style='Icons128x128_1' substyle='Challenge' />");
			$window->Cell('', '10%');
			$window->Cell($nchallenge->Name, '90%');
			$window->Line();
			$window->CustomXML("<quad posn='0 0 0' sizen='2.5 2.5' style='BgRaceScore2' substyle='ScoreReplay' />");
			$window->Cell('', '10%');
			$window->Cell(Core::getObject('tparse')->toRaceTime($nchallenge->AuthorTime), '90%');
			$window->Line();
			$window->CustomXML("<quad posn='0 0 0' sizen='2.5 2.5' style='Icons128x128_1' substyle='ChallengeAuthor' />");
			$window->Cell('', '10%');
			$window->Cell($nchallenge->Author, '90%');
			$width = $width + 20;
		}

		$window->Frame(0, 0, 20, false, 'onMLAChallengesChangeCI', null);
		$window->Line();
		$window->CustomXML("<quad posn='0 0 0' sizen='2.5 2.5' style='Icons128x128_1' substyle='Challenge' />");
		$window->Cell('', '10%');
		$window->Cell($cchallenge->Name, '90%');
		$window->Line();
		$window->CustomXML("<quad posn='0 0 0' sizen='2.5 2.5' style='BgRaceScore2' substyle='ScoreReplay' />");
		$window->Cell('', '10%');
		$window->Cell(Core::getObject('tparse')->toRaceTime($cchallenge->AuthorTime), '90%');
		$window->Line();
		$window->CustomXML("<quad posn='0 0 0' sizen='2.5 2.5' style='Icons128x128_1' substyle='ChallengeAuthor' />");
		$window->Cell('', '10%');
		$window->Cell($cchallenge->Author, '90%');
		$width = $width + 20;

		$window->setOption('lineheight', 2.5);
		$window->setOption('posx', 45-($width-20));
		$window->setOption('width', $width);
	}

	public function onMLAChallengesChangeSort($params)
	{
		//Forced sorting direction
		$value = true;
		if(array_key_exists(2, $params[1])) {
			$value = $params[1][2];
		//Toggle sorting direction
		} elseif($params[1][1] == $params[0]->cdata[$this->id]['listsort']['key']) {
			$value = ($params[0]->cdata[$this->id]['listsort']['value']) ? false : true;
		}

		//Set players listsort array
		$params[0]->cdata[$this->id]['listsort'] = array(
			'key'	=> $params[1][1],
			'value'	=> $value
		);

		//Call ML window
		$this->onMLChallenges(array($params[0], $params[1][0]));
	}

	private function sortChallengesCB($a, $b)
	{
		//Get player
		$player = Core::getObject('players')->get($this->curSortLogin);
		if(!Core::getObject('players')->check($player)) return 0;

		//Sortby?
		$sortby  = $player->cdata[$this->id]['listsort']['key'];
		$sortasc = $player->cdata[$this->id]['listsort']['value'];

		//Sort
		if($a->{$sortby} == $b->{$sortby}) return 0;
		if($sortasc) {
			return ($a->{$sortby} < $b->{$sortby}) ? -1 : 1;
		} else {
			return ($a->{$sortby} > $b->{$sortby}) ? -1 : 1;
		}
	}

	public function onMLChallenges($params)
	{
		$window = $params[0]->ManiaFWK->getWindow('MLWindow');
		if(!$window) return;

		//Copy challenges array
		$challenges = Core::getObject('challenges')->data;

		//Get db connection
		$db = Core::getObject('db')->getConnection();

		//Pre-calculations
		$entries= 18;
		$max	= count($challenges);
		$index	= ($params[1]+0 < 0) ? 0 : $params[1]+0;
		$index	= ($index >= $max) ? $max - $entries : $index;
		$end	= $index+$entries;
		$end	= ($end >= $max) ? $max : $end;

		//Add personal record-data to the challenges object-array-copy
		$ids = '';
		for($i = $index; $end > $i; ++$i)
		{
			$value = $challenges[$i];
			if(!Core::getObject('challenges')->check($value)) continue;
			$ids .= $db->quote($value->Id);
			if($i+1 < $end) $ids .= ',';
		}

		$order  = (Core::getObject('status')->gameinfo['GameMode'] == 4) ? '>' : '<';
		$select = $db->prepare("
			SELECT	c.Id,
					(SELECT r1.Score FROM rcp_records AS r1 WHERE r1.ChallengeId = c.Id && r1.PlayerId = :playerid ORDER BY Score asc LIMIT 0,1) AS OwnScore,
					(SELECT COUNT(Id) as Rank FROM rcp_records AS r3 WHERE ((r3.Score {$order} OwnScore) || (r3.Score = OwnScore)) && r3.ChallengeId = c.Id) AS Rank
			FROM rcp_challenges AS c
			WHERE c.Id IN(". $ids .")
			ORDER BY FIELD(c.Id,". $ids .")
		"); //c.Name
		$select->bindParam('playerid', $params[0]->Id);
		$select->execute();
		for($i = $index; $end > $i; ++$i)
		{
			$value = $challenges[$i];
			if(!Core::getObject('challenges')->check($value)) continue;
			$record = $select->fetch(PDO::FETCH_OBJ);
			$value->PBRecord	= $record->OwnScore;
			$value->PBRank		= $record->Rank;
		}

		//Sort the array-copy
		$this->curSortLogin	= $params[0]->Login;
		usort($challenges, array($this, 'sortChallengesCB'));

		//Set sorting strings
		$NameSort			= ($params[0]->cdata[$this->id]['listsort']['key'] == 'Name')			? ($params[0]->cdata[$this->id]['listsort']['value']) ? '^' : 'v' : '';
		$EnvironmentSort	= ($params[0]->cdata[$this->id]['listsort']['key'] == 'Environment')	? ($params[0]->cdata[$this->id]['listsort']['value']) ? '^' : 'v' : '';
		$AuthorSort			= ($params[0]->cdata[$this->id]['listsort']['key'] == 'Author')			? ($params[0]->cdata[$this->id]['listsort']['value']) ? '^' : 'v' : '';
		$RecordSort			= ($params[0]->cdata[$this->id]['listsort']['key'] == 'PBRecord')		? ($params[0]->cdata[$this->id]['listsort']['value']) ? '^' : 'v' : '';

		//Create header ml
		$auwidth = ($this->jukeboxenabled) ? 15 : 30;
		$window->setOption('title', 'Challenges');
		$window->setOption('icon', 'Browse');
		$window->Reset();
		$window->Line(array('class' => 'thead'));

		$window->Cell('Name !hl!'.$NameSort, '35%', array('onMLAChallengesChangeSort', array($index, 'Name')));
		$window->Cell('Env. !hl!'.$EnvironmentSort, '15%', array('onMLAChallengesChangeSort', array($index, 'Environment')));
		$window->Cell('Author !hl!'.$AuthorSort, $auwidth.'%', array('onMLAChallengesChangeSort', array($index, 'Author')));
		$window->Cell('PB !hl!'.$RecordSort, '20%', array('onMLAChallengesChangeSort', array($index, 'PBRecord')));
		if($this->jukeboxenabled) $window->Cell('Jukebox', '15%', null, array('halign' => 'center'));

		if($max) {
			//Get current challenge
			$currentchallenge = Core::getObject('challenges')->getCurrent();
			if(!Core::getObject('challenges')->check($currentchallenge)) return;

			//Create list ml
			for($i = $index; $end > $i; ++$i)
			{
				$value = $challenges[$i];
				if(!Core::getObject('challenges')->check($value)) continue;

				//Show challenge
				if($currentchallenge->Uid == $value->Uid) {
					$window->Line();
					$window->Cell($value->Name, '35%', array('onMLChallengesDetails', array($value->Uid,$index,'onMLChallenges')), array('textcolor' => '!fsi!'));
					$window->Cell($value->Environment, '15%', null, array('textcolor' => '!fsi!'));
					$window->Cell($value->Author, $auwidth.'%', null, array('textcolor' => '!fsi!'));

					$window->Cell($value->PBRank, '5%', null, array('textcolor' => '!fsi!', 'halign' => 'right'));
					$window->Cell(Core::getObject('tparse')->toRaceTime($value->PBRecord), '15%', null, array('textcolor' => '!fsi!', 'halign' => 'right'));

					if($this->jukeboxenabled) $window->Cell('---', '15%', null, array('textcolor' => '!fsi!', 'halign' => 'center'));
				} else {
					$window->Line();
					$window->Cell($value->Name, '40%', array('onMLChallengesDetails', array($value->Uid,$index,'onMLChallenges')));
					$window->Cell($value->Environment, '15%');
					$window->Cell($value->Author, $auwidth.'%');

					$window->Cell($value->PBRank, '5%', null, array('halign' => 'right'));
					$window->Cell(Core::getObject('tparse')->toRaceTime($value->PBRecord), '15%', null, array('halign' => 'right'));

					if($this->jukeboxenabled) $window->Cell('add', '15%', array('onMLAJukebox',urlencode($value->FileName)), array('halign' => 'center'));
				}
			}
		} else {
			$window->Line();
			$window->Cell('no challenges available', '100%', null, array('halign' => 'center'));
		}

		$prev = ($index <= 0) ? false : true;
		$next = ($index >= ($max-$entries)) ? false : true;
		$window->Line();
		if($prev || $next || $this->jukeboxenabled) {
			$window->Cell('previous', '25%', array('onMLChallenges',$index-$entries), array('hidecell' => !$prev,'class' => 'btn2n'));
			$window->Cell('Jukebox', '25%', 'onMLChallengesJukebox', array('hidecell' => !$this->jukeboxenabled,'class' => 'btn2n'));
			$window->Cell('Reset sorting', '25%', array('onMLAChallengesChangeSort', array($index, 'Number', true)), array('class' => 'btn2n'));
			$window->Cell('next', '25%', array('onMLChallenges',$index+$entries), array('hidecell' => !$next,'class' => 'btn2n'));
		}
	}

	public function onMLChallengesJukebox($params)
	{
		if(!$this->jukeboxenabled) return;
		$window = $params[0]->ManiaFWK->getWindow('MLWindow');
		if(!$window) return;
		$window->setOption('title', 'Challenges Jukebox');
		$window->setOption('icon', 'Browse');
		$window->Reset();

		$entries= 15;
		$max	= count($this->jukeboxdata);
		$index	= ($params[1]+0 < 0) ? 0 : $params[1]+0;
		$index	= ($index >= $max) ? $max - $entries : $index;
		$end	= $index+$entries;
		$end	= ($end >= $max) ? $max : $end;

		if($max) {
			$window->Line(array('class' => 'thead'));
			$window->Cell('Name', '40%');
			$window->Cell('Env.', '15%');
			$window->Cell('Author', '20%');
			$window->Cell('Added by', '25%', null, array('halign' => 'center'));

			for($i = $index; $end > $i; ++$i) {
				$value = $this->jukeboxdata[$i];
				$window->Line();
				$window->Cell($value['challenge']['Name'], '40%', array('onMLChallengesDetails',array($value['challenge']['UId'],$index,'onMLChallengesJukebox')));
				$window->Cell($value['challenge']['Environnement'], '15%');
				$window->Cell($value['challenge']['Author'], '20%');
				$window->Cell($value['NickName'], '25%');
			}
		} else {
			$window->Line();
			$window->Cell('no jukebox challenges available', '100%', null, array('halign' => 'center'));
		}

		$prev = ($index <= 0) ? false : true;
		$next = ($index >= ($max-$entries)) ? false : true;
		$window->Line();
		$window->Cell('previous', '25%', array('onMLChallengesJukebox',$index-$entries), array('hidecell' => !$prev,'class' => 'btn2n'));
		$window->Cell('back to challenges', '50%', 'onMLChallenges', array('class' => 'btn2n'));
		$window->Cell('next', '25%', array('onMLChallengesJukebox',$index+$entries), array('hidecell' => !$next,'class' => 'btn2n'));
	}

	public function onMLChallengesDetails($params)
	{
		$window = $params[0]->ManiaFWK->getWindow('MLWindow');
		if(!$window) return;
		$window->setOption('title', 'Challenge details');
		$window->setOption('icon', 'Browse');
		$window->Reset();

		$db = Core::getObject('db')->getConnection();
		$select = $db->prepare("
			SELECT a.Id, a.Uid, a.Name, a.Author, a.Environment,
				(SELECT SUM(Score) FROM rcp_votes WHERE ChallengeId = a.Id)+0 AS Rating,
				(SELECT COUNT(Score) FROM rcp_votes WHERE ChallengeId = a.Id)+0 AS Votes,
				(SELECT COUNT(Id) FROM rcp_records WHERE ChallengeId = a.Id)+0 AS Records
			FROM rcp_challenges AS a
			WHERE Uid = :uid
		");
		$select->bindParam('uid', $params[1][0]);
		$select->execute();
		if(!$select) return;
		$data = $select->fetch(PDO::FETCH_OBJ);
		$select->closeCursor();
		$select = null;

		$order = (Core::getObject('status')->gameinfo['GameMode'] == 4) ? 'desc' : 'asc';
		$select = $db->prepare("
			SELECT a.Score, b.NickName
			FROM rcp_records AS a
			LEFT JOIN rcp_players AS b
				ON a.PlayerId = b.Id
			WHERE a.ChallengeId = :cid
			ORDER BY a.Score {$order}, a.Date, a.PlayerId
			LIMIT 0,10
		");
		$select->bindParam('cid', $data->Id);
		$select->execute();
		if(!$select) return;

		$window->Line();
		$window->Cell('Name:', '30%');
		$window->Cell($data->Name, '70%');
		$window->Line();
		$window->Cell('Uid:', '30%');
		$window->Cell($data->Uid, '70%');
		$window->Line();
		$window->Cell('Author:', '30%');
		$window->Cell($data->Author, '70%');
		$window->Line();
		$window->Cell('Environment:', '30%');
		$window->Cell($data->Environment, '70%');
		$window->Line();
		$window->Cell('Rating:', '30%');
		$window->Cell($data->Rating+0 .' ('. $data->Votes .' votes)', '70%');
		$window->Line();
		$window->Cell('Records:', '30%');
		$window->Cell($data->Records, '70%');

		if($select->columnCount()) {
			$window->Line();
			$window->Cell('Top 10 Records:', '100%');
			while($data = $select->fetch(PDO::FETCH_OBJ))
			{
				$window->Line();
				$window->Cell($data->NickName, '50%');
				$window->Cell(Core::getObject('tparse')->toRaceTime($data->Score), '50%', null, array('halign' => 'right'));
			}
		}
		$window->Line();
		$window->Cell('go back', '100%', array($params[1][2],$params[1][1]), array('class' => 'btn2n'));
		$select->closeCursor();
		$select = null;
	}

	public function onMLAChallengesChangeCI($params)
	{
		$params[0]->cdata[$this->id]['ciexp'] = ($params[0]->cdata[$this->id]['ciexp']) ? false : true;
		Core::getObject('manialink')->updateContainer('challenge_info', $params[0]);
	}

	public function onMLAJukebox($params)
	{
		$challenge = urldecode($params[1]);
		if(!$this->jukeboxenabled || empty($challenge)) return;

		if(Core::getObject('gbx')->query('GetChallengeInfo', $challenge)) {
			$challenge = Core::getObject('gbx')->getResponse();

			//Check if challenge is not allready in the jukebox
			foreach($this->jukeboxdata AS $jukeboxchallenge)
			{
				if($jukeboxchallenge['challenge']['FileName'] == $challenge['FileName']) {
					Core::getObject('chat')->send("This challenges has been allready added to the jukebox, unable to add: {$challenge['Name']}", false, $params[0]->Login);
					return;
				}
			}

			$msg = "!hl!{$params[0]->NickName}!df! added {$challenge['Name']}!df! to the jukebox";
			if(($this->adminjukeboxing && Core::getObject('live')->isAdmin($params[0]->Login, true)) || !$this->jukeboxprice) {
				$this->onMLAJukeboxBilled(array($params[0],$challenge));
				Core::getObject('live')->addMsg($msg);
			} else {
				if(Core::getObject('gbx')->query('SendBill', $params[0]->Login, $this->jukeboxprice, "Really want to add {$challenge['Name']}\$z to the jukebox?", ''))
				{
					$billid = Core::getObject('gbx')->getResponse();
					Core::getObject('bills')->add($billid, $params[0]->Login, $this->jukeboxprice, 'jukebox', $msg, array('onMLAJukeboxBilled', $this->id), false, array($params[0], $challenge), true);
				}
			}
		}
	}

	public function onMLAJukeboxBilled($params)
	{
		$challenge = $params[1];
		if(!$this->jukeboxenabled || !$challenge) return;

		if($this->adminjukeboxing && Core::getObject('live')->isAdmin($params[0]->Login, true)) {
			array_unshift($this->jukeboxdata, array(
				'NickName'	=> $params[0]->NickName,
				'challenge'	=> $challenge
			));
		} else {
			$this->jukeboxdata[] = array(
				'NickName'	=> $params[0]->NickName,
				'challenge'	=> $challenge
			);
		}
	}

	public function onMLAChallengesJukeboxReset($params)
	{
		if(is_null($params)) {
			return 'Reset the Jukebox';
		}

		if(Core::getObject('live')->isAdmin($params[0]->Login) && Core::getObject('live')->checkPerm('globalmaintenance', $params[0]->Login, 'Reset Jukebox')) {
			$this->jukeboxdata = array();
		}
	}

	public function onMLAChallengesVote($params)
	{
		if(!$this->voting) return;

		//Get challenge
		$challenge = Core::getObject('challenges')->getCurrent();
		if(!Core::getObject('challenges')->check($challenge)) return;

		//Do voting
		$option = urldecode($params[1]);
		$Score  = ($option) ? 1 : -1;
		$insert = Core::getObject('db')->getConnection()->prepare("
			INSERT INTO rcp_votes 
			(PlayerId, ChallengeId, Score)
			VALUES
			(:playerid, :challengeid, :score)
			ON DUPLICATE KEY UPDATE Id = LAST_INSERT_ID(Id), Score = VALUES(Score)
		");
		$insert->bindParam('playerid', $params[0]->Id);
		$insert->bindParam('challengeid', $challenge->Id);
		$insert->bindParam('score', $Score);
		$insert->execute();
		$insert->closeCursor();
		$insert = null;

		//Refresh vote box
		$params[0]->cdata[$this->id]['civoted'] = $Score;
		$params[0]->cdata[$this->id]['ciexp'] = true;
		$this->getChallengeRating();
		Core::getObject('manialink')->updateContainer('challenge_info', true);
	}

	/*
	 * Class Methods
	 */
	private function setChallengeInfoDisplay($bool)
	{
		if(empty(Core::getObject('players')->players)) return;
		Core::getObject('manialink')->updateContainer('challenge_info');
		foreach(Core::getObject('players')->players AS $player)
		{
			$player->cdata[$this->id]['ciexp'] = $bool;
		}
	}

	private function getChallengeRating()
	{
		//get challenge and reset rating value
		$cchallenge = Core::getObject('challenges')->getCurrent();
		if(!Core::getObject('challenges')->check($cchallenge)) return;
		$cchallenge->Rating = array(
			'value' => 0,
			'count' => 0
		);

		//get new rating data
		$select = Core::getObject('db')->getConnection()->prepare("
			SELECT SUM(Score) AS Rating, COUNT(Score) AS Count
			FROM rcp_votes
			WHERE ChallengeId = :challengeid
		");
		$select->bindParam('challengeid', $cchallenge->Id);
		$select->execute();
		if(!$select || !$select->columnCount()) return;
		$data = $select->fetch(PDO::FETCH_OBJ);
		$cchallenge->Rating = array(
			'value' => $data->Rating,
			'count' => $data->Count
		);
		$select->columnCount();
	}
}
?>