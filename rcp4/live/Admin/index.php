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
class Admin extends rcp_liveplugin
{
	public  $title		= 'Admin';
	public  $author		= 'hal.ko.sascha';
	public  $version	= '4.0.3.6';
	public  $apermissions = array(
		'ChallengeRestart'	=> 'editmaps',
		'NextChallenge'		=> 'editmaps',
		'ForceEndRound'		=> 'editmaps',
		'CancelVote'		=> 'editplayers'
	);
	protected $enabled	= null;
	protected $active	= null;
  
	public function onLoad()
	{
		$admin = Plugins::getPlugin('Menu')->menu->add('admin', 'Admin', 'Options', 10, false, false, true);
		if($admin !== false) {
			$admin->add('admin1', 'Options', false, 1, 'onMLAdmin');
			if(!Core::getObject('status')->isrelay) {
				$admin->addSeperator(2);
				$admin->add('admin2', 'Restart', false, 3, 'onAdminRestart');
				$admin->add('admin3', 'Last', false, 4, 'onAdminLast');
				$admin->add('admin4', 'Next', false, 5, 'onAdminNext');
				$admin->add('admin5', 'Force End', false, 6, 'onAdminForceEnd');
				$admin->add('admin6', 'Cancel Vote', false, 7, 'onAdminCancelVote');
			}
		}
		$help = Plugins::getPlugin('Menu')->menu->add('help', 'Help', 'Puzzle', 99);
		if($help !== false) {
			$help->add('admins', 'Admins', false, 3, 'onMLAdminAdmins');
		}

		if(!Core::getObject('status')->isrelay) {
			Core::getObject('chat')->addCommand('restart'	, 'onAdminRestart'		, 'Restarts the current challenge'									, '/restart'	, 'editmaps');
			Core::getObject('chat')->addCommand('last'		, 'onAdminLast'			, 'Sends the server to the last played challenge in challengelist'	, '/last'		, 'editmaps');
			Core::getObject('chat')->addCommand('next'		, 'onAdminNext'			, 'Sends the server to the next challenge in challengelist'			, '/next'		, 'editmaps');
			Core::getObject('chat')->addCommand('forceend'	, 'onAdminForceEnd'		, 'Forces the end of the current round'								, '/forceend'	, 'editmaps');
			Core::getObject('chat')->addCommand('cancelvote', 'onAdminCancelVote'	, 'Cancels the current running vote'								, '/cancelvote'	, 'editplayers');
		}
		Core::getObject('chat')->addCommand('admins'	, 'onMLAdminAdmins'		, 'Shows a list of all admins, registered on this server'	, '/admins');
	}

	public function onMLAdmin($params)
	{
		$window = $params[0]->ManiaFWK->getWindow('MLWindow');
		if(!$window) return;
		$window->setOption('title', 'Administration');
		$window->setOption('icon', 'Options');
		$window->Reset();

		$entries= 16;
		$max	= count(Plugins::getAllPlugins());
		$index	= ($params[1]+0 < 0) ? 0 : $params[1]+0;
		$index	= ($index >= $max) ? $max - $entries : $index;
		$end	= $index+$entries;
		$end	= ($end >= $max) ? $max : $end;

		$window->Line();
		$window->Cell('$l['.Core::getSetting('httppath').']open remoteCP Webinterface$l', '100%', null, array('halign' => 'center'));

		$window->Line(array('class' => 'thead'));
		$window->Cell('Plugin Administration', '100%');
		if($max) {
			$i = 0;
			foreach(Plugins::getAllPlugins() AS $plugin)
			{
				if($end <= $i) break;
				if($i >= $index) {
					$window->Line();
					$enabled = $plugin->onMLAEnabled(array(null));
					$active  = $plugin->onMLAActive();
					$window->Cell('- '.$plugin->title, '60%', array('onMLAdminPlugin', $plugin->id));
					$window->Cell($enabled, '20%');
					$window->Cell($active, '20%');
				}
				++$i;
			}
		} else {
			$window->Line();
			$window->Cell('no plugin administration available', '100%', null, array('halign' => 'center'));
		}

		$prev = ($index <= 0) ? false : true;
		$next = ($index >= ($max-$entries)) ? false : true;
		$window->Line();
		if($prev || $next) {
			$window->Cell('previous', '25%', array('onMLAdmin',$index-$entries), array('hidecell' => !$prev,'class' => 'btn2n'));
			$window->Cell('', '50%');
			$window->Cell('next', '25%', array('onMLAdmin',$index+$entries), array('hidecell' => !$next,'class' => 'btn2n'));
		}
	}

	public function onMLAdminPlugin($params)
	{
		$plugin = Plugins::getPlugin($params[1]);
		$window = $params[0]->ManiaFWK->getWindow('MLWindow');
		if(!$window) return;
		$window->setOption('title', 'Administration');
		$window->setOption('icon', 'Options');
		$window->Reset();

		$window->Line(array('class' => 'thead'));
		$window->Cell($plugin->title, '100%');
		if(count($plugin->adminoptions)) {
			foreach($plugin->adminoptions AS $method)
			{
				//Call callback and the return value should be the title to display
				$label = $plugin->$method(null);
				$window->Line();
				$window->Cell(' - '. $label, '100%', array(array($method, $plugin->id), false));
			}
		}
		$window->Line();
		$window->Cell('main menu', '100%', 'onMLAdmin', array('class' => 'btn2n'));
	}

	public function onMLAdminAdmins($params)
	{
		$window = $params[0]->ManiaFWK->getWindow('MLWindow');
		if(!$window) return;
		$window->setOption('title', 'Administrators');
		$window->setOption('icon', 'Options');
		$window->Reset();

		$admins	= array_values(Core::getObject('live')->getAdmins());
		$max	= count($admins);
		$entries= 18;
		$index	= ($params[1]+0 < 0) ? 0 : $params[1]+0;
		$index	= ($index >= $max) ? $max - $entries : $index;
		$end	= $index+$entries;
		$end	= ($end >= $max) ? $max : $end;

		if(!$max) {
			$window->Line();
			$window->Cell('no admins available', '100%');
			return;
		}

		$db = Core::getObject('db')->getConnection();
		$select = $db->prepare("SELECT NickName FROM rcp_players WHERE Login = :login");
		$window->Line(array('class' => 'thead'));
		$window->Cell('Login', '45%');
		$window->Cell('NickName', '45%');
		$window->Cell('Online', '10%');
		for($i = $index; $end > $i; ++$i)
		{
			$admin = $admins[$i];
			if(empty($admin->tmaccount)) continue;
			$select->bindParam('login', $admin->tmaccount);
			$select->execute();
			$data = $select->fetch(PDO::FETCH_OBJ);
			$select->closeCursor();

			$window->Line();
			$window->Cell($admin->tmaccount, '45%');
			$window->Cell($data->NickName ? $data->NickName : 'n/a', '45%');
			$window->Cell (' ', '3%');
			if(array_key_exists($admin->tmaccount, Core::getObject('players')->players)) {
				if(Core::getObject('players')->players[$admin->tmaccount]->SpectatorStatus['Spectator']) {
					$window->Cell('', array(2,2), null, array('style' => 'Icons64x64_1', 'substyle' => 'LvlYellow'));
				} else {
					$window->Cell('', array(2,2), null, array('style' => 'Icons64x64_1', 'substyle' => 'LvlGreen'));
				}
			} else {
				$window->Cell('', array(2,2), null, array('style' => 'Icons64x64_1', 'substyle' => 'LvlRed'));
			}
		}
		$select = null;

		$prev = ($index <= 0) ? false : true;
		$next = ($index >= ($max-$entries)) ? false : true;
		if($prev || $next) {
			$window->Line();
			$window->Cell('previous', '25%', array('onMLAdminAdmins',$index-$entries), array('hidecell' => !$prev,'class' => 'btn2n'));
			$window->Cell('', '50%');
			$window->Cell('next', '25%', array('onMLAdminAdmins',$index+$entries), array('hidecell' => !$next,'class' => 'btn2n'));
		}
	}

	public function onAdminLast($params)
	{
		$CurrentChallengeInfo = Core::getObject('challenges')->getCurrent();
		if(!Core::getObject('challenges')->check($CurrentChallengeInfo)) return;
		if(empty(Core::getObject('challenges')->data)) return;

		$i = 0;
		$ChallengesList   = array();
		$ChallengesSearch = array();
		foreach(Core::getObject('challenges')->data AS $key => $value)
		{
			$ChallengesList[] = array(
				'Name'		=> $value->Name,
				'FileName'	=> $value->FileName
			);
			$ChallengesSearch[] = $value->Name;
		}

		//Get Last Challenge
		$key = array_search($CurrentChallengeInfo->Name, $ChallengesSearch);
		$key = ($key == 0) ? count($ChallengesSearch)-1 : $key-1;
		$LastChallenge = $ChallengesList[$key];

		Core::getObject('gbx')->query('ChooseNextChallenge', $LastChallenge['FileName']);
		//Trigger PCore::onChallengeListModified with isModified flag
		//Bugfix: because dedicated server don't trigger the isModified flag on "ChooseNextChallenge" calls
		Plugins::getPlugin('PCore')->ForceChallengeListModify();
		Core::getObject('actions')->add(array($this,'NextChallenge'), $params[0]->Login);
	}

	public function onAdminRestart($params)
	{
		Core::getObject('actions')->add(array($this,'ChallengeRestart'), $params[0]->Login);
	}

	public function onAdminNext($params)
	{
		Core::getObject('actions')->add(array($this,'NextChallenge'), $params[0]->Login);
	}

	public function onAdminForceEnd($params)
	{
		Core::getObject('actions')->add(array($this,'ForceEndRound'), $params[0]->Login);
	}

	public function onAdminCancelVote($params)
	{
		Core::getObject('actions')->add(array($this,'CancelVote'), $params[0]->Login);
	}
}
?>