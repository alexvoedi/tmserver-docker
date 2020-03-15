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
class Donations extends rcp_liveplugin
{
	public  $title			= 'Donations';
	public  $author			= 'hal.ko.sascha';
	public  $version		= '4.0.3.6';

	public function onLoad()
	{
		$donations = Plugins::getPlugin('Menu')->menu->add('statistics', 'Statistics', 'Statistics', 70);
		if($ladder !== false) {
			$donations->add('donations', 'Donations', false, 0, 'onMLDonations');
		}
		Core::getObject('chat')->addCommand('donate', 'onChatdonate', 'Donates an amount of coppers to the serveraccount', '/donate 50');
		Core::getObject('chat')->addCommand('donations', 'onMLDonations', 'Shows the summary of all donation', '/donations');
	}

	public function onChatdonate($cmd)
	{
		if(!$cmd) return;
		$this->donate($cmd[0], $cmd[1]);
	}

	public function onMLADonationsDonate($params)
	{
		$this->donate($params[0], $params[1]);
	}

	private function donate($player, $coppers)
	{
		$coppers = (int) $coppers;
		if(Core::getObject('gbx')->query('SendBill', $player->Login, $coppers, 'Really want to donate coppers?', '')) {
			$billid = Core::getObject('gbx')->getResponse();
			Core::getObject('bills')->add($billid, $player->Login, $coppers, 'donation', "{$player->NickName}!df! donated !hl!{$coppers}!df! coppers, thank you!", false, false, false, true);
		}
	}

	public function onNewPlayer($player)
	{
		$window = $player->ManiaFWK->addWindow('MLDonationsbuttons', '', -10, -35, 20);
		if($window) {
			$window->setOption('static', true);
			$window->setOption('close', false);
			$window->setOption('header', false);
			$window->setOption('bg', false);
			$window->setOption('display', 'race');
			$window->setOption('inspec', false);

			$window->Reset();
			$window->Line();
			$window->Cell('Donate', '100%',false,array('textsize' => '0.90', 'valign' => 'bottom'));
			$window->Line();
			$window->Cell('30', '20%', array('onMLADonationsDonate',30), array('class' => 'btn1n'));
			$window->Cell('50', '20%', array('onMLADonationsDonate',50), array('class' => 'btn1n'));
			$window->Cell('100', '20%', array('onMLADonationsDonate',100), array('class' => 'btn1n'));
			$window->Cell('1000','20%', array('onMLADonationsDonate',1000), array('class' => 'btn1n'));
			$window->Cell('5000', '20%', array('onMLADonationsDonate',5000), array('class' => 'btn1n'));
		}
	}

	public function onMLDonations($params)
	{
		$window = $params[0]->ManiaFWK->getWindow('MLWindow');
		if(!$window) return;
		$window->setOption('title', 'Donations');
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
			SELECT b.Login, b.NickName, 
			(SELECT SUM(a.Coppers) FROM rcp_cptransactions AS a WHERE a.Login = b.Login && reason = 'donation' LIMIT {$index},{$entries}) AS Coppers 
			FROM rcp_players AS b
			ORDER BY Coppers desc
			LIMIT {$index},{$entries}
		");
		if($select->columnCount()) {
			$window->Line(array('class' => 'thead'));
			$window->Cell('Name', '65%');
			$window->Cell('Coppers', '35%');
			while($value = $select->fetch(PDO::FETCH_OBJ))
			{
				$window->Line();
				$window->Cell($value->NickName, '65%', array('onMLPlayersDetails', array($value->Login, $index, 'onMLDonations')));
				$window->Cell(empty($value->Coppers) ? ' 0' : $value->Coppers, '35%', null, array('halign' => 'right'));
			}
		} else {
			$window->Line();
			$window->Cell('no winners available', '100%', null, array('halign' => 'center'));
		}

		$prev = ($index <= 0) ? false : true;
		$next = ($index >= ($max-$entries)) ? false : true;
		$window->Line();
		$window->Cell('previous', '25%', array('onMLDonations', $index-$entries), array('hidecell' => !$prev,'class' => 'btn2n'));
		$window->Cell('', '50%');
		$window->Cell('next', '25%', array('onMLDonations', $index+$entries), array('hidecell' => !$next,'class' => 'btn2n'));
		$select->closeCursor();
		$select = null;
	}
}
?>