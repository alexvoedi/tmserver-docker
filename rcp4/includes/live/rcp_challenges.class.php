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
class rcp_challenges
{
	public  $data	 = array();
	private $current = false;
	private $next	 = false;
	private $last	 = false;

	/**
	* Updates challengelist data
	*
	* $challenges must be a valid XML-RPC response from GetChallengeList method
	* @param array $challenges
	* @author hal.sascha
	*/
	public function update($response)
	{
		if(is_array($response) && !empty($response)) {
			$this->reset();
			foreach($response AS $value)
			{
				$this->data[] = new rcp_challenge($value);
			}
		}
	}

	/**
	* Resets the challengelist data
	*
	* @param void
	* @author hal.sascha
	*/
	private function reset()
	{
		$this->data = null;
		$this->data = array();
	}

	/**
	* Checks if a challenges-object is valid
	*
	* @param object $challenge
	* @author hal.sascha
	*/
	public function check($challenge)
	{
		return (is_object($challenge) && !empty($challenge->Id)) ? true : false;
	}

	/**
	* Set the current challenge index
	*
	* @param int $index
	* @author hal.sascha
	*/
	public function setCurrent($index)
	{
		$this->current = $index;
	}

	/**
	* Set the next challenge index
	*
	* @param int $index
	* @author hal.sascha
	*/
	public function setNext($index)
	{
		$this->next = $index;
	}

	/**
	* Returns the current challenge object
	*
	* @author hal.sascha
	*/
	public function getCurrent()
	{
		if(empty($this->data) || !is_int($this->current)) return false;
		return array_key_exists($this->current, $this->data) ? $this->data[$this->current] : false;
	}

	/**
	* Returns the next challenge object
	*
	* @author hal.sascha
	*/
	public function getNext()
	{
		if(empty($this->data) || !is_int($this->next)) return false;
		return array_key_exists($this->next, $this->data) ? $this->data[$this->next] : false;
	}

	/**
	* Returns an array of all challenge objects
	*
	* @author hal.sascha
	*/
	public function getAll()
	{
		return array_keys($this->data);
	}

	/**
	* Returns the challenge index by its FileName
	*
	* @param string $filename
	* @author hal.sascha
	*/
	public function getIndexByFileName($filename)
	{
		if(!empty($this->data)) {
			foreach($this->data AS $index => $challenge)
			{
				if($challenge->FileName == $filename) return $index;
			}
		}
		return false;
	}

	/**
	* Returns the challenge index by its Uid
	*
	* @param string $Uid
	* @author hal.sascha
	*/
	public function getIndexByUid($Uid)
	{
		if(!empty($this->data)) {
			foreach($this->data AS $index => $challenge)
			{
				if($challenge->Uid == $Uid) return $index;
			}
		}
		return false;
	}
}

class rcp_challenge
{
	public  $Id;
	public  $Uid;
	public  $Name;
	public  $FileName;
	public  $Author;
	public  $Environment;
	public  $Mood;
	public  $BronzeTime;
	public  $SilverTime;
	public  $GoldTime;
	public  $AuthorTime;
	public  $CopperPrice;
	public  $LapRace;
	public  $Rating;
	public  $BestCheckPoints;
	private $Record;

	public function __construct($response)
	{
		$this->Uid				= $response['UId'];
		$this->Name				= $this->cleanUpStr($response['Name']);
		$this->FileName			= $response['FileName'];
		$this->Author			= $response['Author'];
		$this->Environment		= $response['Environnement'];
		$this->Mood				= $response['Mood'];
		$this->BronzeTime		= $response['BronzeTime'];
		$this->SilverTime		= $response['SilverTime'];
		$this->GoldTime			= $response['GoldTime'];
		$this->AuthorTime		= $response['AuthorTime'];
		$this->CopperPrice		= $response['CopperPrice'];
		$this->LapRace			= false; //$response['LapRace'];
		$this->Rating			= array(
			'value' => 0,
			'count' => 0
		);
		$this->cleanUp();

		//Check if db data is needed
		$db = Core::getObject('db')->getConnection();

		//Update challenge database
		$insert = $db->prepare("
			INSERT INTO rcp_challenges 
			(Uid, Name, Author, Environment)
			VALUES
			(:uid, :name, :author, :environment)
			ON DUPLICATE KEY UPDATE Id = LAST_INSERT_ID(Id), Uid = VALUES(Uid), Name = VALUES(Name), Author = VALUES(Author), Environment = VALUES(Environment)
		");
		$insert->bindParam('uid', $this->Uid);
		$insert->bindParam('name', $this->Name);
		$insert->bindParam('author', $this->Author);
		$insert->bindParam('environment', $this->Environment);
		$insert->execute();
		if($insert) {
			$insert->closeCursor();
			$insert = null;
		}

		//Set database values
		$select = $db->prepare("SELECT Id FROM rcp_challenges WHERE Uid = :uid");
		$select->bindParam('uid', $this->Uid);
		$select->execute();
		if($select) {
			$data = $select->fetch(PDO::FETCH_OBJ);
			$this->Id = $data->Id;
			$select->closeCursor();
			$select = null;
		} else {
			$this->Id = 0;
		}

		//Read top record
		$order = (Core::getObject('status')->gameinfo['GameMode'] == 4) ? 'desc' : 'asc';
		$select = $db->prepare("
			SELECT a.Score, a.CheckPoints, b.Login, b.NickName
			FROM rcp_records AS a
			LEFT JOIN rcp_players AS b
				ON a.PlayerId = b.Id
			WHERE a.ChallengeId = :cid
			ORDER BY a.Score {$order}, a.Date asc, a.PlayerId asc
			LIMIT 0,1
		");
		$select->bindParam('cid', $this->Id);
		$select->execute();
		if(!$select) return;
		if($select->columnCount()) {
			$row = $select->fetch(PDO::FETCH_OBJ);
			$this->setRecord($row->Login, $row->NickName, $row->Score);
			$this->setBestCheckPoints(explode(':', $row->CheckPoints));
			$select->closeCursor();
			$select = null;
		}
	}

	public function cleanUp()
	{
		$this->Record = false;
		$this->BestCheckPoints = array();
	}

	//Current Record methods
	public function setRecord($Login, $NickName, $Score)
	{
		$this->Record = array(
			'Login'		=> $Login,
			'NickName'	=> $NickName,
			'Score'		=> $Score
		);
	}

	public function getRecord($type)
	{
		if(!is_array($this->Record) || !array_key_exists($type, $this->Record)) return 0;
		return $this->Record[$type];
	}

	//Current Checkpoint(s) methods
	public function setBestCheckPoints($BestCheckPoints)
	{
		$this->BestCheckPoints = $BestCheckPoints;
	}

	public function getBestCheckPoints()
	{
		return $this->BestCheckPoints;
	}

	public function getBestCheckPoint($checkpoint, $lap)
	{
		if($this->LapRace) {
			//workaround for lap beeing +1 to high on finish CP
			if($checkpoint == Core::getSetting('NbCheckpoints')-1) --$lap;
			$checkpoint = (Core::getSetting('NbCheckpoints') * $lap) + $checkpoint;
		}

		if(!array_key_exists($checkpoint, $this->BestCheckPoints)) return;
		return $this->BestCheckPoints[$checkpoint];
	}

	//Helper methods
	private function cleanUpStr($string)
	{
		return str_replace("'", '', $string);
	}
}
?>