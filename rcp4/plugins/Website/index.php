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
class Website extends rcp_plugin
{
	public  $display		= 'box';
	public  $title			= 'Website';
	public  $author			= 'hal.ko.sascha';
	public  $version		= '4.0.3.6';
	public  $nservstatus	= array(1,2,3,4,5);

	private $nocode			= false;
	private $Players		= false;
	private $Challenges		= false;
	private $xml			= false;
	private $error			= array();

	//Caching
	private $cache			= false;
	private $cachetime		= 0;
	private $cachefile		= false;

	public function onLoad()
	{
		$this->nocode		= array_key_exists('nocode', $_REQUEST)		? null	: false;
		$this->Players		= array_key_exists('players', $_REQUEST)	? true	: false;
		$this->Challenges	= array_key_exists('challenges', $_REQUEST)	? true	: false;
		$this->cache		= array_key_exists('cache', $_REQUEST)		? true	: false;
		//$this->cachefile	= $_SERVER['DOCUMENT_ROOT'].'/cache/website_'. Core::getObject('session')->server->login .'.xml';
		$this->cachefile	= 'cache/website_'. Core::getObject('session')->server->login .'.xml';
		$this->cachetime	= time();

		//Check cache
		if(!$this->cache) return;
		if(file_exists($this->cachefile)) {
			if(filemtime($this->cachefile) > ($this->cachetime-300)) {
				header("Content-Type: text/xml");
				readfile($this->cachefile);
				exit();
			}
		}
	}

	public function onOutput()
	{
		//Version
		Core::getObject('gbx')->query('GetVersion');
		$Version = Core::getObject('gbx')->getResponse();

		//Server options
		Core::getObject('gbx')->query('GetServerOptions', 1);
		$ServerOptions = Core::getObject('gbx')->getResponse();

		//Game infos
		Core::getObject('gbx')->query('GetGameInfos', 1);
		$GameInfos = Core::getObject('gbx')->getResponse();
		$CurrentGameInfo = $GameInfos['CurrentGameInfos'];

		//Players
		$i = 0;
		$SpectatorsCount = 0;
		while(true)
		{
			Core::getObject('gbx')->suppressNextError();
			if(!Core::getObject('gbx')->query('GetPlayerList', 50, $i, 1)) break;
			$i = $i + 50;
			$Players = Core::getObject('gbx')->getResponse();
			if(empty($Players)) break;
			foreach($Players AS $value)
			{
				$Playersdata[] = array(
					'NickName' => $value['NickName']
				);
				if($value['SpectatorStatus']) ++$SpectatorsCount;
			}
		}

		//Challenges
		$i = 0;
		$ChallengesSearch = array();
		while(true)
		{
			Core::getObject('gbx')->suppressNextError();
			if(!Core::getObject('gbx')->query('GetChallengeList', 50, $i)) break;
			$i = $i + 50;
			$Challenges = Core::getObject('gbx')->getResponse();
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
		Core::getObject('gbx')->query('GetCurrentChallengeInfo');
		$CurrentChallengeInfo = Core::getObject('gbx')->getResponse();

		//Last Challenge
		$count = count($ChallengesSearch);
		$key = array_search($CurrentChallengeInfo['Name'], $ChallengesSearch);
		$key = ($key == 0) ? $count-1 : $key-1;
		$LastChallenge = $Challengesdata[$key];

		//Next Challenge
		$key = array_search($CurrentChallengeInfo['Name'], $ChallengesSearch);
		$key = ($key == ($count-1)) ? 0 : $key+1;
		$NextChallenge = $Challengesdata[$key];

		//Coppers
		Core::getObject('gbx')->query('GetServerCoppers');
		$Coppers = Core::getObject('gbx')->getResponse() + 0;

		//Ladderlimits
		Core::getObject('gbx')->query('GetLadderServerLimits');
		$LadderLimits = Core::getObject('gbx')->getResponse();

		//Gamemode
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

		//Laddermode
		if($ServerOptions['CurrentLadderMode'] == 0) {
			$CurrentLadderMode = 'Inactiv';
		} elseif($ServerOptions['CurrentLadderMode'] == 1) {
			$CurrentLadderMode = 'Normal';
		} elseif($ServerOptions['CurrentLadderMode'] == 2) {
			$CurrentLadderMode = 'Forced';
		} else {
			$CurrentLadderMode = 'Undefined';
		}

		//Other
		$AllowChallengeDownload	= ($ServerOptions['AllowChallengeDownload'])	? 'yes' : 'no';
		$IsP2PUpload			= ($ServerOptions['IsP2PUpload']) 				? 'yes' : 'no';
		$IsP2PDownload			= ($ServerOptions['IsP2PDownload']) 			? 'yes' : 'no';

		//XML output
		$this->xml   = array();
		$this->xml[] = "<serverlogin>".Core::getObject('status')->systeminfo['ServerLogin']."</serverlogin>";
		$this->xml[] = "<name>". urlencode(Core::getObject('tparse')->toHTML($ServerOptions['Name'], $this->nocode)) ."</name>";
		$this->xml[] = "<comment>". urlencode(Core::getObject('tparse')->toHTML($ServerOptions['Comment'], $this->nocode)) ."</comment>";
		$this->xml[] = "<gamemode value=\"{$CurrentGameInfo['GameMode']}\">{$GameMode}</gamemode>";
		$this->xml[] = "<laddermode value=\"{$ServerOptions['CurrentLadderMode']}\">{$CurrentLadderMode}</laddermode>";
		$this->xml[] = "<ladderlimitmin>{$LadderLimits['LadderServerLimitMin']}</ladderlimitmin>";
		$this->xml[] = "<ladderlimitmax>{$LadderLimits['LadderServerLimitMax']}</ladderlimitmax>";
		$this->xml[] = "<type>{$Version['Name']}</type>";
		$this->xml[] = "<version>{$Version['Version']}</version>";
		$this->xml[] = "<status>".Core::getObject('status')->server['Name']."</status>";
		$this->xml[] = "<coppers>{$Coppers}</coppers>";
		$this->xml[] = "<challengedownload>{$AllowChallengeDownload}</challengedownload>";
		$this->xml[] = "<p2pupload>{$IsP2PUpload}</p2pupload>";
		$this->xml[] = "<p2pdownload>{$IsP2PDownload}</p2pdownload>";
		$this->xml[] = "<maxplayers>{$ServerOptions['CurrentMaxPlayers']}</maxplayers>";
		$this->xml[] = "<maxspectators>{$ServerOptions['CurrentMaxSpectators']}</maxspectators>";
		$this->xml[] = "<lastchallenge>". urlencode(Core::getObject('tparse')->toHTML($LastChallenge['Name'], $this->nocode)) ."</lastchallenge>";
		$this->xml[] = "<currentchallenge>". urlencode(Core::getObject('tparse')->toHTML($CurrentChallengeInfo['Name'], $this->nocode)) ."</currentchallenge>";
		$this->xml[] = "<nextchallenge>". urlencode(Core::getObject('tparse')->toHTML($NextChallenge['Name'], $this->nocode)) ."</nextchallenge>";
		$this->xml[] = "<challengescount>". count($Challengesdata) ."</challengescount>";
		$this->xml[] = "<playerscount>". count($Playersdata) ."</playerscount>";
		$this->xml[] = "<spectatorscount>{$SpectatorsCount}</spectatorscount>";
		if($this->Challenges) {
			if(!empty($Challengesdata)) {
				$this->xml[] = "<challenges>";
				foreach($Challengesdata AS $value)
				{
					$this->xml[] = "\t<item>". urlencode(Core::getObject('tparse')->toHTML($value['Name'], $this->nocode)) ." ({$value['Envi']})</item>";
				}
				$this->xml[] = "</challenges>";
			}
		}

		if($this->Players) {
			if(!empty($Playersdata)) {
				$this->xml[] = "<players>";
				foreach($Playersdata AS $value)
				{
					$this->xml[] = "\t<item>". urlencode(Core::getObject('tparse')->toHTML($value['NickName'], $this->nocode)) ."</item>";
				}
				$this->xml[] = "</players>";
			}
		}

		//Output & write cache file
		if($this->cache) {
			ob_start();
			$this->OutputXML(true);
			$file = fopen($this->cachefile, 'w');
			if($file) {
				fputs($file, ob_get_contents());
				fclose($file);
			} else {
				$this->error[] = '[0001] unable to write cachefile';
			}
			ob_end_clean();
		}

		header("Content-Type: text/xml");
		$this->OutputXML(false);
		exit();
	}

	private function OutputXML($cache = false)
	{
		echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n";
		echo "<server>\r\n";
		if($cache) echo "\t<cache time=\"{$this->cachetime}\">{$this->cachefile}</cache>\r\n";
		if(!empty($this->error)) {
			echo "\t<errors>\r\n";
			foreach($this->error AS $error)
			{
				echo "\t\t<item>{$error}</item>\r\n";
			}
			echo "\t</errors>\r\n";
		}

		if(!empty($this->xml)) {
			foreach($this->xml AS $xml)
			{
				echo "\t{$xml}\r\n";
			}
		}
		echo "</server>\r\n";
	}
}
?>