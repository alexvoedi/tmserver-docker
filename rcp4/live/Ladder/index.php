<?php
/**
* remoteCP 4
* ütf-8 release
*
* @package remoteCPlive
* @author hal.sascha
* @copyright (c) 2006-2010
* @version 4.0.3.6
*/
class Ladder extends rcp_liveplugin
{
	public  $title        = 'Ladder';
	public  $author       = 'hal.ko.sascha & Speedy';
	public  $version      = '4.0.3.6';
	private $minrecords   = 3;
	private $maxrecords   = 50;
	private $laddertype   = 0;
	private $ladderoption = 0;
	private $lpdecimals   = 0;
	private $lpmulti      = 1;
	private $laddertable  = '';

	public function onLoad()
	{
		Core::getObject('db')->fileImport($this->id);
		$db = Core::getObject('db')->getConnection();
		$this->laddertable = "rcp_ladder_".(string) Core::getObject('session')->server->id."";
		$init = $db->prepare("
				CREATE TABLE IF NOT EXISTS `{$this->laddertable}` (
					`id` mediumint(9) unsigned NOT NULL AUTO_INCREMENT,
					`PlayerId` mediumint(9) unsigned NOT NULL DEFAULT '0',
					`TeamId` mediumint(9) unsigned NOT NULL DEFAULT '0',
					`ranksum` mediumint(9) unsigned NOT NULL,
					`rankcount` mediumint(9) unsigned NOT NULL,
					`rankvalue` decimal(10,5) unsigned NOT NULL DEFAULT '0.00000',
				PRIMARY KEY  (`id`),
				KEY `PlayerId` (`PlayerId`),
				KEY `TeamId` (`TeamId`),
				KEY `Rank` (`rankvalue`)
				)
				ENGINE=MEMORY DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1
		");
		$init->execute();
		$init->closeCursor();
		$init = null;
		$ladder = Plugins::getPlugin('Menu')->menu->add('ladder', 'Ladder', array('BgRaceScore2', 'LadderRank'), 60);
		if($ladder !== false) {
			$ladder->add('players', 'Players', false, 1, 'onMLLadder');
			$ladder->add('teams', 'Teams', false, 2, 'onMLLadderTeams');
		}
		Core::getObject('chat')->addCommand('team', 'onChatteam', 'Adds supports for team chat and additional stuff', array('/team <chatmessage>', '/team create <teamname>', '/team join <joincode>', '/team leave'));
		Core::getObject('chat')->addCommand('t', 'onChatteam', 'Alternative command for team command', 'see /team command');
	}

	public function onLoadSettings($settings)
	{
		$this->minrecords   = (int) $settings->minrecords;
		$this->maxrecords   = (int) $settings->maxrecords;
		$this->laddertype   = (int) $settings->laddertype;
		$this->ladderoption = (int) $settings->ladderoption;
		$this->lpdecimals   = (int) $settings->lpdecimals;
		$this->lpmulti      = (int) $settings->lpmulti;

		if ($this->laddertype == 0) {
			$this->laddertypetext = 'Points';
		} elseif ($this->laddertype == 1) {
			$this->laddertypetext = 'Average';
			// Set lpmulti = 1 if laddertype is Average 
			$this->lpmulti = 1;
		} else {
			$this->laddertypetext = 'Value';
		}
	}

	public function onNewPlayer($player)
	{
		$window = $player->ManiaFWK->addWindow('MLContainerLadderpanel', '', -66, 23.25, 9);
		if($window) {
			$window->setOption('header', false);
			$window->setOption('static', true);
			$window->setOption('bg', false);
			$window->setOption('lineheight', 2.25);
			$window->setOption('display', 'score');
		}
	}

	public function onPlayerConnect($params)
	{
		Core::getObject('manialink')->updateContainer('Ladderpanel',$params[0]->Login);
	}
	
	public function onMLContainerLadderpanel($params)
	{
		$window = $params[0]->ManiaFWK->getWindow('MLContainerLadderpanel');
		if(!$window) return;
		$window->Reset();

		$db = Core::getObject('db')->getConnection();
		$select = $db->prepare("SELECT COUNT(*) AS Count FROM {$this->laddertable}");
		$select->execute();
		$data = $select->fetch(PDO::FETCH_OBJ);
		$select->closeCursor();
		$select = null;

		$total   = $data->Count;
		//Check if data is available
		if(!$total) return;

		//Personal Rank
		$select = $db->prepare("
			SELECT a.NickName, b.rankcount, b.rankvalue
			FROM rcp_players AS a
			LEFT JOIN {$this->laddertable} AS b
				ON a.Id = b.PlayerId
			WHERE a.Login = :login
			LIMIT 0,1");
		$select->bindValue('login', $params[0]->Login);
		$select->execute();
		$pr = $select->fetch(PDO::FETCH_OBJ);
		if($select) {
			$window->Frame(0, 0, 15.25, array('class' => 'tmf'),  false, false);
			$window->Line();
			$window->Cell('','15%');
			$window->Cell('Serverladder', '80%');
			$window->Cell('','5%');
			if($pr->rankvalue) {
				$window->Line();
				$window->Cell('','15%');
				$window->Cell('Rank:', '27%');
				$window->Cell($pr->rankcount.'!df! / $z'.$total, '53%', null, array('halign' => 'center', 'textcolor' => '!fsi!'));
				$window->Cell('','5%');
				$window->Line();
				$window->Cell('','15%');
				$window->Cell($this->laddertypetext.':', '27%');
				$window->Cell(number_format($pr->rankvalue * $this->lpmulti, $this->lpdecimals, '.', ''), '53%', null, array('halign' => 'center', 'textcolor' => '!fsi!'));
				$window->Cell('','5%');
			} else {
				$window->Line();
				$window->Cell('','15%');
				$window->Cell('You need !hl!'.$this->minrecords.'$z records!', '80%', null, array('halign' => 'center', 'textcolor' => '!fsi!'));
				$window->Cell('','5%');
			}
		}
		$select->closeCursor();
		$select = null;
	}

	public function onBeginChallenge()
	{
		$this->CreateLadder();
		Core::getObject('manialink')->updateContainer('Ladderpanel');
	}

	public function onChatteam($params)
	{
		$player  = $params[0];
		$command = $params[1];
		$cmd     = explode(' ', $command);
		$db      = Core::getObject('db')->getConnection();

		switch($cmd[0])
		{
			default:
				if($player->TeamId2) {
					if(empty($command)) {
						$this->onMLLadderTeamsDetails(array($player,array($player->TeamId2,0,false)));
						return;
					}
					$select = $db->prepare("SELECT Login FROM rcp_players WHERE TeamId = :teamid ");
					$select->bindParam('teamid', $player->TeamId2);
					$select->execute();
					if($select->columnCount()) {
						while($tp = $select->fetch(PDO::FETCH_OBJ))
						{
							Core::getObject('chat')->send($command, $player, $tp->Login);
						}
					}
					$select->closeCursor();
					$select = null;
				} else {
					Core::getObject('chat')->send('Can not send team chatmessage, you are currently not in a team', false, $player->Login);
				}
			break;

			case 'join':
				$joincode = $cmd[1];
				$select = $db->prepare("SELECT Id, Name FROM rcp_teams WHERE JoinCode = :joincode");
				$select->bindParam('joincode', $joincode);
				$select->execute();
				if($select->columnCount()) {
					$data = $select->fetch(PDO::FETCH_OBJ);
					$select2 = $db->prepare("UPDATE rcp_players SET TeamId = :teamid WHERE Login = :login ");
					$select2->bindParam('teamid', $data->Id);
					$select2->bindParam('login', $player->Login);
					$select2->execute();
					$player->TeamId2 = $data->Id;
					Core::getObject('chat')->send("Team: {$data->Name} !df!joined", false, $player->Login);
					$select2->closeCursor();
					$select->closeCursor();
					$select2 = null;
					$select = null;
				} else {
					Core::getObject('chat')->send('Invalid joincode, no team with this JoinCode found', false, $player->Login);
				}
			break;

			case 'leave':
				if($player->TeamId2) {
					$player->TeamId2 = 0;
					$update = $db->prepare("UPDATE rcp_players SET TeamId = '0' WHERE Login = :login");
					$update->bindParam('login', $player->Login);
					$update->execute();
					$update->closeCursor();
					$update = null;

					$select = $db->prepare("SELECT Name FROM rcp_teams WHERE Id = :teamid");
					$select->bindParam('teamid', $player->TeamId2);
					$select->execute();
					$team = $select->fetch(PDO::FETCH_OBJ);
					Core::getObject('chat')->send("Team: {$team->Name} !df!left", false, $player->Login);
					$select->closeCursor();
					$select = null;
				} else {
					Core::getObject('chat')->send('Can not leave team, you are currently not in a team', false, $player->Login);
				}
			break;

			case 'create':
				if(!$player->TeamId2 && $player->Id) {
					$tmp      = array_shift($cmd);
					$name     = implode(' ', $cmd);
					$joincode = uniqid(rand(0,99));

					$insert = $db->prepare("INSERT INTO rcp_teams (Name,JoinCode,LeaderId) VALUES (:name, :joincode, :leaderid)");
					$insert->bindParam('name', $name);
					$insert->bindParam('joincode', $joincode);
					$insert->bindParam('leaderid', $player->Id);
					$insert->execute();
					$insert->closeCursor();
					$insert = null;

					$select = $db->query("SELECT LAST_INSERT_ID() AS LastId FROM rcp_teams");
					if(!$select) return;
					$data = $select->fetch(PDO::FETCH_OBJ);
					$select->closeCursor();
					$select = null;

					$select = $db->prepare("UPDATE rcp_players SET TeamId = :teamid WHERE Login = :login");
					$select->bindParam('teamid', $data->LastId);
					$select->bindParam('login', $player->Login);
					$select->execute();
					$select->closeCursor();
					$select = null;

					Core::getObject('chat')->send("Team: {$name} !df!successfully created", false, $player->Login);
				} else {
					Core::getObject('chat')->send('Can not create team, you are currently in a team, please leave this team first', false, $player->Login);
				}
			break;
		}
	}

	public function onMLLadder($params)
	{
		$window = $params[0]->ManiaFWK->getWindow('MLWindow');
		if(!$window) return;
		$window->setOption('title', 'Serverladder');
		$window->setOption('icon', 'Ladder');
		$window->Reset();

		$db = Core::getObject('db')->getConnection();
		$select = $db->prepare("SELECT COUNT(*) AS Count FROM {$this->laddertable}");
		$select->execute();
		$data = $select->fetch(PDO::FETCH_OBJ);
		$select->closeCursor();
		$select = null;

		$entries = 18;
		$max     = $data->Count;
		$index   = ($params[1]+0 < 0) ? 0 : $params[1]+0;
		$index   = ($index > $max) ? $max - $entries : $index;
		$end     = $index+$entries;
		$end     = ($end >= $max) ? $max : $end;

		//Check if data is available
		if(!$max) {
			$window->Line();
			$window->Cell('no ladder stats available', '100%', null, array('halign' => 'center'));
			return;
		}

		//Personal Rank
		$select = $db->prepare("
			SELECT a.NickName, b.rankcount, b.rankvalue
			FROM rcp_players AS a
			LEFT JOIN {$this->laddertable} AS b
				ON a.Id = b.PlayerId
			WHERE a.Login = :login
			LIMIT 0,1");
		$select->bindValue('login', $params[0]->Login);
		$select->execute();
		$pr = $select->fetch(PDO::FETCH_OBJ);
		if($select && $pr->rankvalue) {
			$window->Line();
			$window->Cell('>>>', '10%', null, array('halign' => 'center'));
			$window->Cell('Personal Rank:', '20%', null, array('textcolor' => '!fsi!'));
			$window->Cell('Position', '15%', null, array('halign' => 'right'));
			$window->Cell($pr->rankcount, '10%', null, array('halign' => 'right','textcolor' => '!fsi!'));
			$window->Cell('>>>', '10%', null, array('halign' => 'center'));
			$window->Cell($this->laddertypetext, '10%', null, array('halign' => 'right'));
			$window->Cell(number_format($pr->rankvalue * $this->lpmulti, $this->lpdecimals, '.', ''), '25%', null, array('halign' => 'right','textcolor' => '!fsi!'));
		}
		$select->closeCursor();
		$select = null;

		$select = $db->prepare("
			SELECT a.PlayerId, a.rankcount, a.rankvalue, b.NickName, b.Login
			FROM {$this->laddertable} AS a
			LEFT JOIN rcp_players AS b
				ON a.PlayerId = b.Id
			ORDER BY rankcount asc
			LIMIT {$index},{$entries}
		");
		$select->execute();
		$window->Line(array('class' => 'thead'));
		$window->Cell('Pos', '10%', null, array('halign' => 'center'));
		$window->Cell('NickName', '65%');
		$window->Cell($this->laddertypetext, '25%', null, array('halign' => 'right'));
		if($select->columnCount()) {
			while($data = $select->fetch(PDO::FETCH_OBJ))
			{
				$window->Line();
				$window->Cell($data->rankcount, '10%', null, array('halign' => 'center'));
				$window->Cell($data->NickName, '65%', array('onMLPlayersDetails', array($data->Login,$index,'onMLLadder')));
				$window->Cell(number_format($data->rankvalue * $this->lpmulti, $this->lpdecimals, '.', ''), '25%', null, array('halign' => 'right'));
			}
			$prev = ($index <= 0) ? false : true;
			$next = ($index >= ($max-$entries)) ? false : true;
			$window->Line();
			$window->Cell('previous', '25%', array('onMLLadder',$index-$entries), array('hidecell' => !$prev,'class' => 'btn2n'));
			$window->Cell('', '50%');
			$window->Cell('next', '25%', array('onMLLadder',$index+$entries), array('hidecell' => !$next,'class' => 'btn2n'));
		}
		$select->closeCursor();
		$select = null;
	}

	public function onMLLadderTeams($params)
	{
		$window = $params[0]->ManiaFWK->getWindow('MLWindow');
		if(!$window) return;
		$window->setOption('title', 'Teamladder');
		$window->setOption('icon', 'Teams');
		$window->Reset();

		$db     = Core::getObject('db')->getConnection();
		$select = $db->query("SELECT COUNT(*) AS Count FROM rcp_teams");
		$data   = $select->fetch(PDO::FETCH_OBJ);
		$select->closeCursor();
		$select = null;

		$entries = 18;
		$max     = $data->Count;
		$index   = ($params[1]+0 < 0) ? 0 : $params[1]+0;
		$index   = ($index > $max) ? $max - $entries : $index;
		$end     = $index+$entries;
		$end     = ($end >= $max) ? $max : $end;

		//Check if data is available
		if(!$max) {
			$window->Line();
			$window->Cell('no ladder stats available', '100%', null, array('halign' => 'center'));
			return;
		}

		$select = $db->prepare("
			SELECT a.Id, a.Name,
				(  ((SELECT COUNT(*) FROM {$this->laddertable} AS c WHERE c.TeamId = a.Id)*{$this->lpmulti})
					-
					(SELECT SUM(b.rankvalue) FROM {$this->laddertable} AS b WHERE b.TeamId = a.Id)
				)+0 AS rankvalue
			FROM rcp_teams AS a
			ORDER BY rankvalue asc
			LIMIT {$index},{$entries}
		");
		$select->execute();

		$window->Line(array('class' => 'thead'));
		$window->Cell('Pos', '10%', null, array('halign' => 'center'));
		$window->Cell('Name', '65%');
		$window->Cell($this->laddertypetext, '25%', null, array('halign' => 'right'));
		if($select->columnCount()) {
			$i = 0;
			while($data = $select->fetch(PDO::FETCH_OBJ))
			{
				$i++;
				$window->Line();
				$window->Cell($i+$index, '10%', null, array('halign' => 'center'));
				$window->Cell($data->Name, '65%', array('onMLLadderTeamsDetails', array($data->Id,$index,'onMLLadderTeams')));
				$window->Cell(number_format($data->rankvalue * $this->lpmulti, $this->lpdecimals, '.', ''), '25%', null, array('halign' => 'right'));
			}
		}
		$prev = ($index <= 0) ? false : true;
		$next = ($index >= ($max-$entries)) ? false : true;
		$window->Line();
		$window->Cell('previous', '25%', array('onMLLadderTeams',$index-$entries), array('hidecell' => !$prev,'class' => 'btn2n'));
		$window->Cell('', '50%');
		$window->Cell('next', '25%', array('onMLLadderTeams',$index+$entries), array('hidecell' => !$next,'class' => 'btn2n'));
		$select->closeCursor();
		$select = null;
	}

	public function onMLLadderTeamsDetails($params)
	{
		$teamid = $params[1][0];
		$index  = $params[1][1];
		$goback = $params[1][2];
		$window = $params[0]->ManiaFWK->getWindow('MLWindow');
		if(!$window) return;
		$window->setOption('title', 'Team details');
		$window->setOption('icon', 'Teams');
		$window->Reset();

		$db     = Core::getObject('db')->getConnection();
		$select = $db->prepare("
			SELECT a.Id, a.Name, a.JoinCode, a.LeaderId, b.NickName,
				(SELECT COUNT(*) FROM rcp_players AS c WHERE c.TeamId = a.Id)+0 AS Members
			FROM rcp_teams AS a
			LEFT JOIN rcp_players AS b
				ON a.LeaderId = b.Id
			WHERE a.Id = :id
		");
		$select->bindValue('id', $teamid);
		$select->execute();

		if(!$select) return;
		$data = $select->fetch(PDO::FETCH_OBJ);
		$select->closeCursor();
		$select = null;
		$leader = ($params[0]->Id == $data->LeaderId) ? true : false;

		$window->Line();
		$window->Cell('Name:', '30%');
		$window->Cell($data->Name, '70%');
		$window->Line();
		$window->Cell('Leader:', '30%');
		$window->Cell($data->NickName, '70%');
		if($leader) {
			$window->Line();
			$window->Cell('JoinCode:', '30%');
			$window->Cell($data->JoinCode, '70%');
		}
		$window->Line();
		$window->Cell('Members:', '30%');
		$window->Cell($data->Members, '70%');

		$select = $db->prepare("SELECT COUNT(*) AS Count FROM rcp_players WHERE TeamId = :teamid");
		$select->bindParam('teamid', $data->Id);
		$select->execute();
		$count  = $select->fetch(PDO::FETCH_OBJ);
		$select->closeCursor();
		$select = null;

		$entries = 10;
		$max     = $count->Count;
		$index   = ($index+0 < 0) ? 0 : $index+0;
		$index   = ($index > $max) ? $max - $entries : $index;
		$end     = $index+$entries;
		$end     = ($end >= $max) ? $max : $end;
		$select  = $db->prepare("
			SELECT Id, Login, NickName
			FROM rcp_players
			WHERE TeamId = :teamid
			LIMIT {$index},{$entries}
		");
		$select->bindParam('teamid', $data->Id);
		$select->execute();
		if(!$select) return;
		if($select->columnCount()) {
			$window->Line(array('class' => 'thead'));
			$window->Cell('Players:', '100%');
			while($tp = $select->fetch(PDO::FETCH_OBJ))
			{
				if($leader) {
					$window->Line();
					$window->Cell($tp->NickName, '70%');
					$window->Cell('delete', '30%',array('onMLALadderTeamsDelete',array($tp->Id,$data->Id,$index,$goback)),array('halign' => 'right'));
				} else {
					$window->Line();
					$window->Cell($tp->NickName, '100%');
				}
			}

			$prev = ($index <= 0) ? false : true;
			$next = ($index >= ($max-$entries)) ? false : true;
			if($prev || $next) {
				$window->Line();
				$window->Cell('previous', '25%',array('onMLLadderTeamsDetails',$index-$entries),array('hidecell' => !$prev,'class' => 'btn2n'));
				$window->Cell('', '50%');
				$window->Cell('next', '25%',array('onMLLadderTeamsDetails',$index+$entries),array('hidecell' => !$next,'class' => 'btn2n'));
			}
			$select->closeCursor();
			$select = null;
		}

		if($goback) {
			$window->Line();
			$window->Cell('go back', '100%',array($goback,$index),array('class' => 'btn2n'));
		}
	}

	public function onMLALadderTeamsDelete($params)
	{
		$playerid = $params[1][0];
		$teamid   = $params[1][1];
		$index    = $params[1][2];
		$goback   = $params[1][3];
		$db       = Core::getObject('db')->getConnection();

		$select = $db->prepare("
			SELECT b.Login
			FROM rcp_teams AS a
			LEFT JOIN rcp_players AS b
				ON a.LeaderId = b.Id
			WHERE a.Id = :teamid
		");
		$select->bindParam('teamid', $teamid);
		$select->execute();

		if(!$select) return;
		if($select->columnCount()) {
			$data = $select->fetch(PDO::FETCH_OBJ);
			if($data->Login == $params[0]->Login) {
				$update = $db->prepare("UPDATE rcp_players SET TeamId = '0' WHERE Id = :playerid");
				$update->bindParam('playerid', $playerid);
				$update->execute();
				$update->closeCursor();
				$update = null;
			}
		}
		$this->onMLLadderTeamsDetails(array($params[0],array($teamid,$index,$goback)));
	}

	/*
	 * Class Methods
	 */
	public function CreateLadder()
	{
		$this->debug("<pre>CreateLadder Start: ". date("H:i:s") ."</pre>");
		//Prepare vars
		$db         = Core::getObject('db')->getConnection();
		$players    = array();
		$tracks     = array();
		$trackranks = array();

		//Delete old stats
		$delete = $db->prepare("TRUNCATE `{$this->laddertable}`");
		$delete->execute();
		$delete->closeCursor();
		$delete = null;

		//Get Challenges
		$i = 0;
		while(true)
		{
			Core::getObject('gbx')->suppressNextError();
			if(!Core::getObject('gbx')->query('GetChallengeList', 50, $i)) break;
			$i = $i + 50;
			$Challenges = Core::getObject('gbx')->getResponse();
			if(empty($Challenges)) break;
			$select = $db->prepare("SELECT Id FROM rcp_challenges WHERE Uid = :uid");
			foreach($Challenges AS $value)
			{
				$select->bindValue('uid', $value['UId']);
				$select->execute();
				if($select->columnCount()) {
					$data = $select->fetch(PDO::FETCH_OBJ);
					$id = $data->Id;
					$select->closeCursor();
				} else {
					continue;
				}
				if($id) $tracks[] = $id;
			}
			$select = null;
		}
		if (empty($tracks)) return;
		$this->debug("<pre>tracks: ". print_r($tracks, true) ."</pre>");

		//Get Players with at least 'minrecords' ranked records
		//'minrecords' limits the amount of data, this increases the ladder performance
		$select = $db->prepare("
			SELECT PlayerId, COUNT(*) AS cnt
			FROM rcp_records
			WHERE ChallengeId IN (".implode(',',$tracks).")
			GROUP BY PlayerId
			HAVING cnt >= :minrecords
		");
		$select->bindValue('minrecords', $this->minrecords);
		$select->execute();
		if(!$select->columnCount()) return;
		foreach($select AS $value)
		{
			$players[] = $value['PlayerId'];
		}
		$select->closeCursor();
		$select = null;
		if (empty($players)) return;
		$this->debug("<pre>players: ". print_r($players, true) ."</pre>");

		//Get ranked records for all tracks
		$order = (Core::getObject('status')->gameinfo['GameMode'] == 4) ? 'DESC' : 'ASC';
		$select = $db->prepare("
			SELECT ChallengeId, PlayerId
			FROM rcp_records
			WHERE ChallengeId IN (".implode(',',$tracks).")
			  AND PlayerId IN (".implode(',',$players).")
			ORDER BY ChallengeId ASC, Score {$order}, Date ASC
		");
		$select->execute();
		if($select->columnCount()) {
			$i = 1;
			foreach($select AS $row)
			{
				if ($row['ChallengeId'] <> $old) {
					$old = $row['ChallengeId'];
					$i = 1;
					$trackranks[$row['ChallengeId']] = array();
				}
				if ($i > $this->maxrecords) continue;

				$trackranks[$row['ChallengeId']][$row['PlayerId']] = $i++;
			}
		}
		$select->closeCursor();
		$select = null;
		$this->debug("<pre>trackranks: ". print_r($trackranks, true) ."</pre>");

		//Write ladder
		$trackscount = count($tracks);
		$i = 0;
		$values = array();
		foreach($players AS $player)
		{
			++$i;
			//Get average score
			$sum = 0;
			foreach($tracks as $track)
			{
				if ($this->ladderoption == 0) {
					if (isset($trackranks[$track])) {
						$sum += (array_key_exists($player, $trackranks[$track])) ? $trackranks[$track][$player] : $this->maxrecords;
					} else {
						$sum += $this->maxrecords;
					}
				} else {
					if (isset($trackranks[$track])) {
						$sum += (array_key_exists($player, $trackranks[$track])) ? ($this->maxrecords + 1 - $trackranks[$track][$player]) : 0;
					} else {
						$sum += 0;
					}
				}
			}
			$avg = round($sum / $trackscount, 5);
			$values[] = '('.$player.',0,'.$sum.',0,'.$avg.')';
			//Insert into database
			if ($i == 100) {
				$insert = $db->prepare("
					INSERT INTO {$this->laddertable}
					(PlayerId, TeamId, ranksum, rankcount, rankvalue)
					VALUES
					".implode(', ', $values)."
				");
				$insert->execute();
				$insert->closeCursor();
				$insert = null;
				$i = 0;
				$values = array();
			}
		}
		if (!empty($values)) {
			//Insert into database
			$insert = $db->prepare("
				INSERT INTO {$this->laddertable}
				(PlayerId, TeamId, ranksum, rankcount, rankvalue)
				VALUES
				".implode(', ', $values)."
			");
			$insert->execute();
			$insert->closeCursor();
			$insert = null;
			$i = 0;
			$values = array();
		}

		//Calculate Ranking position by player
		if($this->ladderoption == 0) {
			$order = 'ASC';
		} else {
			$order = 'DESC';
		}
		$select = $db->prepare("SELECT id FROM {$this->laddertable} ORDER BY rankvalue {$order}");
		$select->execute();
		if($select->columnCount()) {
			$rows = $select->fetchAll(PDO::FETCH_OBJ);
			$i = 0;
			$update = $db->prepare("UPDATE {$this->laddertable} SET rankcount = :rankcount WHERE id = :id");
			foreach($rows AS $row)
			{
				++$i;
				$update->bindValue('rankcount', $i);
				$update->bindValue('id', $row->id);
				$update->execute();
				$update->closeCursor();
			}
			$update = null;
		}
		$this->debug("<pre>CreateLadder Ende: ". date("H:i:s") ."</pre>");
	}
}
?>