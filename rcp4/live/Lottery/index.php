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
class Lottery extends rcp_liveplugin
{
	public  $title		= 'Lottery';
	public  $author		= 'hal.ko.sascha';
	public  $version	= '4.0.3.6';
	private $coppers	= 40;
	private $mincoppers	= 1000;
	private $minplayers	= 3;
	private $last		= false;
   
	public function onLoad()
	{
		$donations = Plugins::getPlugin('Menu')->menu->add('statistics', 'Statistics', 'Statistics', 70);
		if($ladder !== false) {
			$donations->add('lottery', 'Lottery', false, 0, 'onMLLottery');
		}
		Core::getObject('chat')->addCommand('lottery', 'onMLLottery', 'Shows the summary of all lottery winners', '/lottery');
	}

	public function onLoadSettings($settings)
	{
		$this->coppers		= (int) $settings->coppers;
		$this->mincoppers	= (int) $settings->mincoppers;
		$this->minplayers	= (int) $settings->minplayers;
	}

	public function onBeginChallenge($params)
	{
		//Get challenge
		$challenge = Core::getObject('challenges')->getCurrent();
		if(!Core::getObject('challenges')->check($challenge)) return;

		//Set
		$this->last = time()+(($challenge->BronzeTime/1000)*2);
	}

	public function onEndChallenge()
	{
		if($this->last > time() || !count(Core::getObject('players')->players)) return;

		//Get server coppers
		if(Core::getObject('gbx')->query('GetServerCoppers')) {
			$coppers = Core::getObject('gbx')->getResponse();
		}

		$limit = $coppers-($this->coppers*2);
		if($coppers && $coppers > $this->mincoppers && $limit > $this->coppers && count(Core::getObject('players')->players) > $this->minplayers) {
			//Get Winner
			$array = array();
			foreach(Core::getObject('players')->players AS $key => $value)
			{
				if($value->OnlineRights) $array[] = $key;
			}

			$count  = count($array)-1;
			$key    = mt_rand(0, $count);
			$player = Core::getObject('players')->players[$array[$key]];

			if($player->Login) {
				if(Core::getObject('gbx')->query('Pay', $player->Login, (int) $this->coppers, Core::getObject('session')->server->name.' Lottery')) {
					$billid = Core::getObject('gbx')->getResponse();
					Core::getObject('bills')->add($billid, $player->Login, 0-$this->coppers, 'lottery', "Congratulations to {$player->NickName}!df!, winning !hl!{$this->coppers}!df! coppers on ". Core::getObject('session')->server->name ."!df! lottery", false, false, false, true);
				}
			} else {
				Core::getObject('live')->addMsg('Lottery !hl!not available!df!, no winner');
			}
		} else {
			if(count(Core::getObject('players')->players) < $this->minplayers) {
				Core::getObject('live')->addMsg('Lottery !hl!not available!df!, not enough players on server');
			} else {
				Core::getObject('live')->addMsg('Lottery !hl!not available!df!, not enough coppers available');
			}
		}
	}

	public function onMLLottery($params)
	{
		$window = $params[0]->ManiaFWK->getWindow('MLWindow');
		if(!$window) return;
		$window->setOption('title', 'Lottery');
		$window->setOption('icon', 'Coppers');
		$window->Reset();

		$db = Core::getObject('db')->getConnection();
		$select = $db->query("SELECT COUNT(Id) AS Count FROM rcp_players");
		$data   = $select->fetch(PDO::FETCH_OBJ);
		$select->closeCursor();
		$select = null;

		$entries= 18;
		$max	= $data->Count;
		$index	= ($params[1]+0 < 0) ? 0 : $params[1]+0;
		$index	= ($index >= $max) ? $max - $entries : $index;
		$end	= $index+$entries;
		$end	= ($end >= $max) ? $max : $end;

		$select = $db->query("
			SELECT a.NickName, a.Login,
				ABS((SELECT SUM(b.Coppers)
					 FROM rcp_cptransactions AS b
					 WHERE b.Login = a.Login && b.Reason = 'lottery'
				)) AS cps
			FROM rcp_players AS a
			ORDER BY cps desc
			LIMIT {$index},{$entries}
		");
		if($select->columnCount()) {
			$window->Line(array('class' => 'thead'));
			$window->Cell('Name', '65%');
			$window->Cell('Coppers', '35%');
			while($value = $select->fetch(PDO::FETCH_OBJ))
			{
				$window->Line();
				$window->Cell($value->NickName, '65%', array('onMLPlayersDetails', array($value->Login, $index, 'onMLLottery')));
				$window->Cell(empty($value->cps) ? ' 0' : $value->cps, '35%', null, array('halign' => 'right'));
			}
		} else {
			$window->Line();
			$window->Cell('no winners available', '100%', null, array('halign' => 'center'));
		}

		$prev = ($index <= 0) ? false : true;
		$next = ($index >= ($max-$entries)) ? false : true;
		$window->Line();
		$window->Cell('previous', '25%', array('onMLLottery', $index-$entries), array('hidecell' => !$prev,'class' => 'btn2n'));
		$window->Cell('', '50%');
		$window->Cell('next', '25%', array('onMLLottery', $index+$entries), array('hidecell' => !$next,'class' => 'btn2n'));
		$select->closeCursor();
		$select = null;
	}
}
?>