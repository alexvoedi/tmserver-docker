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
class PCore extends rcp_plugin
{
	public  $display	= 'quick';
	public  $title		= 'PCore';
	public  $author		= 'hal.ko.sascha';
	public  $version	= '4.0.3.6';
	public  $usejs		= true;
	public  $apermissions	= array(
		'ChallengeRestart'	=> 'editmaps',
		'NextChallenge'		=> 'editmaps',
		'LastChallenge'		=> 'editmaps',
		'ForceEndRound'		=> 'editmaps'
	);
	private $op;

	public function onLoad()
	{
		$this->op = $_REQUEST['op'] ? $_REQUEST['op'] : false;
	}
   
	public function onOutput()
	{
		switch($this->op) {
			default:
				if(Core::getObject('gbx')->query('GetStatus')) {
					$status = Core::getObject('gbx')->getResponse();
					echo $status['Name'];
				}
			break;

			case 'challenge':
				if(Core::getObject('gbx')->query('GetCurrentChallengeInfo')) {
					$challenge = Core::getObject('gbx')->getResponse();
					echo Core::getObject('tparse')->toHTML($challenge['Name']);
				}
			break;
		}
	}

	public function ChallengeRestart()
	{
		Core::getObject('actions')->add('ChallengeRestart');
	}

	public function NextChallenge()
	{
		Core::getObject('actions')->add('NextChallenge');
	}

	public function LastChallenge()
	{
		$i = 0;
		$ChallengesList   = array();
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
				$ChallengesList[] = array(
					'Name'		=> $value['Name'],
					'FileName'	=> $value['FileName']
				);
				$ChallengesSearch[] = $value['Name'];
			}
		}

		if(Core::getObject('gbx')->query('GetCurrentChallengeInfo'))
			$CurrentChallengeInfo = Core::getObject('gbx')->getResponse();

		//Get Last Challenge
		$key = array_search($CurrentChallengeInfo['Name'], $ChallengesSearch);
		$key = ($key == 0) ? count($ChallengesSearch)-1 : $key-1;
		$LastChallenge = $ChallengesList[$key];

		Core::getObject('actions')->add('ChooseNextChallenge', $LastChallenge['FileName']);
		Core::getObject('actions')->add('NextChallenge');
	}

	public function ForceEndRound()
	{
		Core::getObject('actions')->add('ForceEndRound');
	}
}
?>