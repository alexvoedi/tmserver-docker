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
class rcp_players
{
	/**
	 * Array of player objects
	 * @access public
	 */
	public $players = array();

	/**
	* Returns the player object
	*
	* If the player with the specified login does not exists it will be created
	* @param string $login
	* @author hal.sascha
	*/
	public function get($login)
	{
		return (!empty($login) && array_key_exists($login, $this->players)) ? $this->players[$login] : $this->add($login);
	}

	/**
	* Checks if a player object is valid
	*
	* @param object $player
	* @author hal.sascha
	*/
	public function check($player)
	{
		return (is_object($player) && !empty($player->Id) && is_object($player->ManiaFWK)) ? true : false;
	}

	/**
	* Adds a new player to the playerlist
	*
	* @param string $login
	* @author hal.sascha
	*/
	private function add($login)
	{
		if(!$login) return false;
		if(Core::getObject('gbx')->query('GetPlayerInfo', $login, 1)) {
			$player = Core::getObject('gbx')->getResponse();

			//Dont add user if its the server account or a relay server
			if(!Core::getObject('status')->isrelay && (floor($player['Flags']/100000) % 10) > 0) {
				return false;
			}

			//Get detailed info
			if(Core::getObject('gbx')->query('GetDetailedPlayerInfo', $login)) {
				$detailed = Core::getObject('gbx')->getResponse();
				$player['OnlineRights']	= $detailed['OnlineRights'];
				$player['Path']			= $detailed['Path'];
			}

			//Add the player
			$this->players[$login] = new rcp_player($player);
			if($this->check($this->players[$login])) {
				$this->players[$login]->ManiaFWK->loadPlayerObject($this->players[$login]);
				Plugins::triggerEvent('onNewPlayer', $this->players[$login]);

				//Load CDATA
				 $this->players[$login]->loadCDATA();

				//Get / set ingored status
				$this->loadIgnoreList();
			} else {
				$this->remove($login);
			}
		}
		return array_key_exists($login, $this->players) ? $this->players[$login] : false;
	}

	/**
	* Removes a player from the playerlist
	*
	* @param string $login
	* @author hal.sascha
	*/
	public function remove($login)
	{
		if($login && array_key_exists($login, $this->players)) {
			if($this->check($this->players[$login])) $this->players[$login]->__destruct();
			$this->players[$login] = null;
			unset($this->players[$login]);
			return true;
		}
		return false;
	}

	/**
	* Loads players from the servers IngoreList
	*
	* @author hal.sascha
	*/
	private function loadIgnoreList()
	{
		$ignored = array();
		$i = 0;
		while(true)
		{
			Core::getObject('gbx')->suppressNextError();
			if(!Core::getObject('gbx')->query('GetIgnoreList', 50, $i)) break;
			$i = $i + 50;
			$IgnoreList = Core::getObject('gbx')->getResponse();
			if(empty($IgnoreList)) break;
			foreach($IgnoreList AS $value)
			{
				$ignored[$value['Login']] = 1;
			}
		}

		//Check for players that have ignored status
		if(empty($this->players)) return;
		foreach($this->players AS $player)
		{
			if(!$this->check($player)) continue;

			//Get / set ignored status
			$player->Ignored = array_key_exists($player->Login, $ignored) ? true : false;
		}
	}

	public function fixLogin($login)
	{
		return substr($login, 0, strpos($login, '/'));
	}
}

class rcp_player
{
	public  $Id;
	public  $Login;
	public  $NickName;
	public  $PlayerId;
	public  $TeamId;
	public  $TeamId2;
	public  $SpectatorStatus;
	public  $LadderRanking;
	public  $Flags;
	public  $ManiaFWK;
	public  $Ignored;
	public  $New;
	public  $Connected;
	public  $OnlineRights;
	public  $Path;
	public  $cdata;
	private $Admin;
	private $Record;			//Alltime player record on the current map
	private $CurrentRecord;		//Current player record on the current map
	private $Checkpoints;

	public function __construct($response)
	{
		//Default trackmania playerinfo data
		$this->updateInfo($response);

		//Additional playerinfo data
		$this->OnlineRights		= $response['OnlineRights'];
		$this->Path				= explode('|', $response['Path']);
		array_shift($this->Path);

		//Live additional data
		$this->Ignored			= false;
		$this->New				= true;
		$this->Connected		= Core::getSetting('time');
		$this->ManiaFWK			= new rcp_maniafwk();
		$this->cdata			= array();
		$this->Admin			= false;

		//Reset some data
		$this->cleanUp();

		//Update player database
		$db = Core::getObject('db')->getConnection();
		$insert = $db->prepare("
			INSERT INTO rcp_players 
			(Login, NickName, UpdatedAt)
			VALUES
			(:login, :nickname, NOW())
			ON DUPLICATE KEY UPDATE Id = LAST_INSERT_ID(Id), NickName = VALUES(NickName), UpdatedAt = VALUES(UpdatedAt)
		");
		$insert->bindParam('login', $this->Login);
		$insert->bindParam('nickname', $this->NickName);
		$insert->execute();
		if($insert) {
			$insert->closeCursor();
			$insert = null;
		}

		//Set database values
		$select = $db->prepare("SELECT Id, TeamId FROM rcp_players WHERE Login = :login");
		$select->bindParam('login', $this->Login);
		$select->execute();
		if($select) {
			$data = $select->fetch(PDO::FETCH_OBJ);
			$this->Id		= $data->Id;
			$this->TeamId2	= $data->TeamId;
			$select->closeCursor();
			$select = null;
		} else {
			$this->Id		= 0;
			$this->TeamId2	= 0;
		}
	}

	public function __destruct()
	{
		$this->saveCDATA();

		//Destroy the mania framework
		if(is_object($this->ManiaFWK)) {
			$this->ManiaFWK->__destruct();
		}
	}

	public function updateInfo($response)
	{
		$this->Login			= $response['Login'];
		$this->NickName			= $this->cleanUpStr($response['NickName']);
		$this->PlayerId			= $response['PlayerId'];
		$this->TeamId			= $response['TeamId'];
		$this->LadderRanking	= $response['LadderRanking'];

		//Get player flags
		$this->Flags = array();
		$this->Flags['ForceSpectator']				= $response['Flags'] % 10;
		$this->Flags['IsReferee']					= (floor($response['Flags']/10) % 10) > 0;
		$this->Flags['IsPodiumReady']				= (floor($response['Flags']/100) % 10) > 0;
		$this->Flags['IsUsingStereoscopy']			= (floor($response['Flags']/1000) % 10) > 0;
		$this->Flags['IsManagedByAnOtherServer']	= (floor($response['Flags']/10000) % 10) > 0;
		$this->Flags['IsServer']					= (floor($response['Flags']/100000) % 10) > 0;
		$this->Flags['HasPlayerSlot']				= (floor($response['Flags']/1000000) % 10) > 0;

		//Get spectatorStatus flags
		$this->SpectatorStatus = array();
		$this->SpectatorStatus['Spectator']				= $response['SpectatorStatus'] % 10;
		$this->SpectatorStatus['TemporarySpectator']	= (floor($response['SpectatorStatus']/10) % 10) > 0;
		$this->SpectatorStatus['PureSpectator']			= (floor($response['SpectatorStatus']/100) % 10) > 0;
		$this->SpectatorStatus['AutoTarget']			= (floor($response['SpectatorStatus']/1000) % 10) > 0;
		$this->SpectatorStatus['CurrentTargetId']		= (int) floor($response['SpectatorStatus']/10000);
	}

	public function loadCDATA()
	{
		//Load cdata data from rcp_players_cache
		if(!$this->Id) return;
		$db = Core::getObject('db')->getConnection();
		$select = $db->prepare("SELECT Plugin, Var, Value FROM rcp_players_cache WHERE PlayerId = :playerid && ServerId = :serverid");
		$select->bindValue('playerid', $this->Id);
		$select->bindValue('serverid', (string) Core::getObject('session')->server->id);
		$select->execute();
		if($select) {
			$data = $select->fetchAll(PDO::FETCH_ASSOC);
			foreach($data AS $key => $value)
			{
				$this->cdata[$value['Plugin']] = array($value['Var'] => unserialize($value['Value']));
			}
			$select->closeCursor();
		}
		$select = null;
		echo "cdata from: ". $this->Login ."\r\n";
		print_r($this->cdata);
	}

	private function saveCDATA()
	{
		//Save cdata data to rcp_players_cache
		if(!$this->Id || empty($this->cdata)) return;
		$db = Core::getObject('db')->getConnection();
		$insert = $db->prepare("
			INSERT INTO rcp_players_cache
			(PlayerId, ServerId, Plugin, Var, Value)
			VALUES
			(:playerid, :serverid, :plugin, :var, :value)
			ON DUPLICATE KEY UPDATE PlayerId = LAST_INSERT_ID(PlayerId), Plugin = VALUES(Plugin), Var = VALUES(Var), Value = VALUES(Value)
		");
		foreach($this->cdata AS $plugin => $data)
		{
			if(empty($data)) continue;
			foreach($data AS $var => $value)
			{
				$value = serialize($value);
				if(strlen($value) > 255) continue;
				$insert->bindValue('playerid', $this->Id);
				$insert->bindValue('serverid', (string) Core::getObject('session')->server->id);
				$insert->bindValue('plugin', $plugin);
				$insert->bindValue('var', $var);
				$insert->bindValue('value', $value);
				$insert->execute();
				if($insert) {
					$insert->closeCursor();
				}
			}
		}
		$insert = null;
	}

	public function cleanUp()
	{
		$this->Record = false;
		$this->CurrentRecord = false;
		$this->resetCheckpoints();
	}

	//Admin methods
	public function setAdminStatus()
	{
		$this->Admin = true;
	}

	public function getAdminStatus()
	{
		return $this->Admin;
	}

	//Alltime Record methods
	public function setRecord($score)
	{
		if(empty($this->Record) || (Core::getObject('status')->gameinfo['GameMode'] != 4 && $this->Record > $score) || (Core::getObject('status')->gameinfo['GameMode'] == 4 && $this->Record < $score)) {
			$this->Record = $score;
		}
	}

	public function getRecord()
	{
		return $this->Record;
	}

	//Current Record methods
	public function setCurrentRecord($score)
	{
		if(empty($this->CurrentRecord) || (Core::getObject('status')->gameinfo['GameMode'] != 4 && $this->CurrentRecord[0] > $score) || (Core::getObject('status')->gameinfo['GameMode'] == 4 && $this->CurrentRecord[0] < $score)) {
			//Get this rounds checkpoints
			$checkpoints = $this->getCheckpoints();

			//Update record
			$this->CurrentRecord = array($score, $checkpoints);
		}
	}

	public function getCurrentRecord($withcheckpoints = false)
	{
		return ($withcheckpoints) ? $this->CurrentRecord : $this->CurrentRecord[0];
	}

	//Current Checkpoint(s) methods
	public function transferCheckpointsToChallenge(&$challenge)
	{
		$challenge->setBestCheckPoints($this->Checkpoints);
	}

	public function getCheckpoints()
	{
		return $this->Checkpoints;
	}

	public function addCheckpoint($score)
	{
		$this->Checkpoints[] = $score;
	}

	public function resetCheckpoints()
	{
		$this->Checkpoints = array();
	}

	//Helper methods
	private function cleanUpStr($string)
	{
		return str_replace("'", '', $string);
	}

	public function getPlaytime($original)
	{
		// array of time period chunks
		$chunks = array(
			array(60 * 60 * 24 * 365 , 'year'),
			array(60 * 60 * 24 * 30 , 'month'),
			array(60 * 60 * 24 * 7, 'week'),
			array(60 * 60 * 24 , 'day'),
			array(60 * 60 , 'hour'),
			array(60 , 'minute'),
		);

		$today = Core::getSetting('time');
		$since = $today - $original;
		//$j saves performing the count function each time around the loop
		for($i = 0, $j = count($chunks); $i < $j; ++$i)
		{
			$seconds = $chunks[$i][0];
			$name = $chunks[$i][1];
			//Finding the biggest chunk (if the chunk fits, break)
			if(($count = floor($since / $seconds)) != 0) {
				//DEBUG print "<!-- It's $name -->\n";
				break;
			}
		}

		$print = ($count == 1) ? '1 '.$name : "$count {$name}s";
		if($i + 1 < $j) {
			//Now getting the second item
			$seconds2 = $chunks[$i + 1][0];
			$name2 = $chunks[$i + 1][1];
			//Add second item if it's greater than 0
			if(($count2 = floor(($since - ($seconds * $count)) / $seconds2)) != 0) {
				$print .= ($count2 == 1) ? ', 1 '.$name2 : ", $count2 {$name2}s";
			}
		}
		return $print;
	}
}
?>