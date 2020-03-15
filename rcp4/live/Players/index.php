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
class Players extends rcp_liveplugin
{
	public  $title		= 'Players';
	public  $author		= 'hal.ko.sascha';
	public  $version	= '4.0.3.6';
	public  $apermissions = array(
		'Kick'				=> 'editplayers',
		'Ban'				=> 'editplayers',
		'Ignore'			=> 'editplayers',
		'UnIgnore'			=> 'editplayers',
		'ForceSpectator'	=> 'editplayers'
	);
	private $messages	= array();
  
	public function onLoad()
	{
		Plugins::getPlugin('Menu')->menu->add('players', 'Players', 'Buddies', 30, 'onMLPlayers');
	}

	public function onLoadSettings($settings)
	{
		$this->messages['warning']	= (string) $settings->messages->warning;
		$this->messages['ignore']	= (string) $settings->messages->ignore;
	}

	public function onMLPlayers($params)
	{
		$window = $params[0]->ManiaFWK->getWindow('MLWindow');
		if(!$window) return;
		$window->Reset();
		$window->setOption('title', 'Players');
		$window->setOption('icon', 'Multiplayer');

		$entries= 18;
		$max	= count(Core::getObject('players')->players);
		$index	= ($params[1]+0 < 0) ? 0 : $params[1]+0;
		$index	= ($index >= $max) ? $max - $entries : $index;
		$end	= $index+$entries;
		$end	= ($end >= $max) ? $max : $end;

		if($max) {
			if(Core::getObject('live')->isAdmin($params[0]->Login, true)) {
				$window->Line(array('class' => 'thead'));
				$window->Cell('Name', '50%');
				$window->Cell('Options', '50%', null, array('halign' => 'center'));
			} else {
				$window->Line(array('class' => 'thead'));
				$window->Cell('Name', '50%');
				$window->Cell('Spec', '10%', null, array('halign' => 'center'));
				$window->Cell('Offic.', '10%', null, array('halign' => 'center'));
				$window->Cell('Ignored', '10%', null, array('halign' => 'center'));
				$window->Cell('Ladder', '20%', null, array('halign' => 'center'));
			}

			$playerlist = array_values(Core::getObject('players')->players);
			for($i = $index; $end > $i; ++$i)
			{
				$value = $playerlist[$i];
				if($value->NickName == Core::getObject('session')->server->name) continue;
				if(Core::getObject('live')->isAdmin($params[0]->Login, true)) {
					$pignore = Core::getObject('players')->get($value->Login);
					if(Core::getObject('players')->check($pignore)) {
						$ignore = $pignore->Ignored ? 'UnIgnore' : 'Ignore';
					} else {
						$ignore = 'N/A';
					}

					$window->Line();
					$window->Cell($value->NickName, '50%', array('onMLPlayersDetails', array($value->Login,$index,'onMLPlayers')));
					$window->Cell('Warn', '10%', array('Warn',$value->Login), array('halign' => 'center'));
					$window->Cell('Kick', '10%', array('Kick',$value->Login), array('halign' => 'center'));
					$window->Cell('Ban', '10%', array('Ban',$value->Login), array('halign' => 'center'));
					$window->Cell($ignore, '10%', array('Ignore',$value->Login), array('halign' => 'center'));
					$window->Cell('Spec.', '10%', array('ForceSpectator',$value->Login), array('halign' => 'center'));
				} else {
					$spec = $value->SpectatorStatus['Spectator'] ? 'x' :  '-';
					$offc = $value->LadderRanking ? 'x' :  '-';
					$igno = $value->Ignored ? 'x' :  '-';
					$window->Line();
					$window->Cell($value->NickName, '50%', array('onMLPlayersDetails', array($value->Login,$index,'onMLPlayers')));
					$window->Cell($spec, '10%', null, array('halign' => 'center'));
					$window->Cell($offc, '10%', null, array('halign' => 'center'));
					$window->Cell($igno, '10%', null, array('halign' => 'center'));
					$window->Cell($value->LadderRanking, '20%', null, array('halign' => 'right'));
				}
			}

			$prev = ($index <= 0) ? false : true;
			$next = ($index >= ($max-$entries)) ? false : true;
			if($prev || $next) {
				$window->Line();
				$window->Cell('previous', '25%', array('onMLPlayers',$index-$entries), array('hidecell' => !$prev,'class' => 'btn2n'));
				$window->Cell('', '50%');
				$window->Cell('next', '25%', array('onMLPlayers',$index+$entries), array('hidecell' => !$next,'class' => 'btn2n'));
			}
		} else {
			$window->Line();
			$window->Cell('no players available', '100%', null, array('halign' => 'center'));
		}
	}

	public function onMLPlayersDetails($params)
	{
		$window = $params[0]->ManiaFWK->getWindow('MLWindow');
		if(!$window) return;
		$window->Reset();
		$window->setOption('title', 'Player details');
		$window->setOption('icon', 'PlayerPage');

		$db = Core::getObject('db')->getConnection();
		$select = $db->prepare("
			SELECT a.Login, a.NickName, a.Wins, DATE_FORMAT(a.UpdatedAt,'%d-%m-%y %H:%i') AS LastActive, a.TimePlayed,
				(SELECT COUNT(Id) FROM rcp_records WHERE PlayerId = a.Id)+0 AS Records,
				(SELECT SUM(Coppers) FROM rcp_cptransactions WHERE Login = a.Login && Reason = 'lottery')+0 AS LotteryCPs,
				(SELECT SUM(Coppers) FROM rcp_cptransactions WHERE Login = a.Login && Reason = 'donation')+0 AS DonationCPs,
				(SELECT COUNT(Id) FROM rcp_challenges WHERE Author = a.Login)+0 AS ChallengesInDB
			FROM rcp_players AS a
			WHERE a.Login = :login
		");
		$select->bindParam('login', $params[1][0]);
		$select->execute();
		if(!$select) return;
		$data = $select->fetch(PDO::FETCH_OBJ);
		$select->closeCursor();
		$select = null;
		$tmpplayer = Core::getObject('players')->get($params[1][0]);

		$data->LotteryCPs = -1*($data->LotteryCPs);
		$window->Line();
		$window->Cell('NickName:', '35%');
		$window->Cell($data->NickName, '65%');
		$window->Line();
		$window->Cell('Login:', '35%');
		$window->Cell($data->Login, '65%');
		$window->Line();
		$window->Cell('Game:', '35%');
		$window->Cell(($tmpplayer->OnlineRights) ? 'United' : 'Nations', '65%');
		$window->Line();
		$window->Cell('Wins:', '35%');
		$window->Cell($data->Wins, '65%');
		$window->Line();
		$window->Cell('Records:', '35%');
		$window->Cell($data->Records, '65%');
		$window->Line();
		$window->Cell('Last active:', '35%');
		$window->Cell($data->LastActive, '65%');
		$window->Line();
		$window->Cell('Time played:', '35%');
		$window->Cell($params[0]->getPlaytime(Core::getSetting('time') - $data->TimePlayed), '65%');
		$window->Line();
		$window->Cell('Donated Coppers:', '35%');
		$window->Cell(empty($data->DonationCPs) ? ' 0' : $data->DonationCPs, '65%');
		$window->Line();
		$window->Cell('Lottery Coppers:', '35%');
		$window->Cell(empty($data->LotteryCPs) ? ' 0' : $data->LotteryCPs, '65%');
		if(!empty($data->ChallengesInDB)) {
			$window->Line();
			$window->Cell('Challenges @ Database:', '35%');
			$window->Cell($data->ChallengesInDB, '65%');
		}
		$window->Line();
		$window->Cell('go back', '100%', array($params[1][2],$params[1][1]), array('class' => 'btn2n'));
	}
	
	/*
	 * Actions
	 */
	public function Warn($params)
	{
		if(Core::getObject('live')->checkPerm('editplayers', $params[0]->Login, 'Warn')) {
			Core::getObject('chat')->send($this->messages['warning'], false, $params[1]);
		}
	}

	public function Kick($params)
	{
		Core::getObject('actions')->add(array($this,'Kick'), $params[0]->Login, $params[1]);
	}

	public function Ban($params)
	{
		Core::getObject('actions')->add(array($this,'Ban'), $params[0]->Login, $params[1]);
	}

	public function ForceSpectator($params)
	{
		Core::getObject('actions')->add(array($this,'ForceSpectator'), $params[0]->Login, 1);
		Core::getObject('actions')->add(array($this,'ForceSpectator'), $params[0]->Login, 0);
	}

	public function Ignore($params)
	{
		$playerA = $params[0];
		$playerB = Core::getObject('players')->get($params[1]);
		if(Core::getObject('players')->check($playerB)) {
			$ignore           = $playerB->Ignored ? 'UnIgnore' : 'Ignore';
			$playerB->Ignored = $playerB->Ignored ? false : true;

			if(Core::getObject('live')->checkPerm('editplayers', $playerA->Login, $ignore)) {
				Core::getObject('gbx')->query($ignore, $playerB->Login);
				Core::getObject('chat')->send($this->messages['ignore'], false, $playerB->Login);
			}
		}
	}	
}
?>