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
class Players extends rcp_plugin
{
	public  $display		= 'main';
	public  $title			= 'Players';
	public  $author			= 'hal.ko.sascha';
	public  $version		= '4.0.3.6';
	public  $nservstatus	= array(3,4,5);
	public  $vpermissions	= array('viewplayers');
	public  $apermissions	= array(
		'Warn'				=> 'editplayers',
		'Kick'				=> 'editplayers',
		'Ban'				=> 'editplayers',
		'Ignore'			=> 'editplayers',
		'UnIgnore'			=> 'editplayers',
		'UnBan'				=> 'editplayers',
		'AddGuest'			=> 'editlists',
		'RemoveGuest'		=> 'editlists',
		'BlackList'			=> 'editlists',
		'UnBlackList'		=> 'editlists',
		'ForcePlayerTeam_0'	=> 'editplayers',
		'ForcePlayerTeam_1'	=> 'editplayers',
		'ForceSpectator_0'	=> 'editplayers',
		'ForceSpectator_1'	=> 'editplayers',
		'ForceSpectator_2'	=> 'editplayers',
		'ForceScores_0'		=> 'editplayers',
		'ForceScores_1'		=> 'editplayers'
	);
	private $list			= array();
	private $op;

	public function onLoad()
	{
		$this->op = $_REQUEST['op'] ? $_REQUEST['op'] : false;
	}

	public function onExec()
	{
		$this->readlist();
	}

	public function onOutput()
	{
		switch($this->op)
		{
			case 'changemode':
				if($_REQUEST['selectedmode'] == 'ForceSpectator_1') {
					echo "	<div class='f-row'>
							<label for='Forcespectatortarget'>".pt_forcespectatortarget."</label>
							<div class='f-field'><select name='Forcespectatortarget'><option value='-1'>".pt_camtargetn1."</option><option value='0'>".pt_camtarget0."</option><option value='1'>".pt_camtarget1."</option><option value='2'>".pt_camtarget2."</option></select></div>
						</div>";
				}
			break;

			default:
				//Get PlayerList
				$i = 0;
				$PlayerList = array();
				while(true)
				{
					Core::getObject('gbx')->suppressNextError();
					if(!Core::getObject('gbx')->query('GetPlayerList', 50, $i, 2)) break;
					$i = $i + 50;
					$Players = Core::getObject('gbx')->getResponse();
					if(empty($Players)) break;
					$PlayerList = array_merge($PlayerList, $Players);
				}

				//Get CurrentRanking
				$i = 0;
				$CurrentRanking = array();
				while(true)
				{
					if(!Core::getObject('gbx')->query('GetCurrentRanking', 50, $i)) {
						break;
					} else {
						$i = $i + 50;
						$Rankings = Core::getObject('gbx')->getResponse();
						if(!empty($Rankings))
							$CurrentRanking = array_merge($CurrentRanking, $Rankings);
						else
							break;
					}
				}

				//Change PlayerList arraykeys
				if(!empty($PlayerList)) {
					$PlayerList2 = $PlayerList;
					$PlayerList  = array();
					foreach($PlayerList2 AS $Player)
					{
						$PlayerList[$Player['PlayerId']] = $Player;
					}
				}

				//Add the data from the Playerlist, to all Players inside CurrentRanking
				$List = array();
				if(!empty($CurrentRanking)) {
					foreach($CurrentRanking AS $Player)
					{
						$pid = $Player['PlayerId'];
						$List[$pid] = array(
							'Login'				=> $Player['Login'],
							'NickName'			=> $Player['NickName'],
							'PlayerId'			=> $Player['PlayerId'],
							'Rank'				=> $Player['Rank'],
							'BestTime'			=> $Player['BestTime'],
							'Score'				=> $Player['Score'],
							'NbrLapsFinished'	=> $Player['NbrLapsFinished'],
							'LadderScore'		=> $Player['LadderScore'],
							'BestCheckpoints'	=> $Player['BestCheckpoints']
						);

						//Add data from PlayerList
						if(array_key_exists($pid, $PlayerList)) {
							$List[$pid]['TeamId']			= $PlayerList[$pid]['TeamId'];
							$List[$pid]['SpectatorStatus']	= $PlayerList[$pid]['SpectatorStatus'];
							$List[$pid]['LadderRanking']	= $PlayerList[$pid]['LadderRanking'];
							$List[$pid]['Flags']			= $PlayerList[$pid]['Flags'];

							//Remove player from PlayerList array
							//to have only players without ranking in this list
							$PlayerList[$pid] = null;
							unset($PlayerList[$pid]);
						}

						//Add rcp custom data
						$List[$pid]['IsTeam']	= ($Player['PlayerId'] == 0 || $Player['PlayerId'] == 1) ? true : false;
					}
				}

				if(Core::getObject('session')->checkPerm('editplayers'))
					echo "<form action='ajax.php' method='post' name='playerlist' id='playerlist' rel='{$this->display}area' class='postcmd'>";

				//Display players without rankings, like specs (teammode), relay server and the server itself
				if(!empty($PlayerList)) {
					echo "<div class='bg-t'>";
					echo "<table>";
					echo "<colgroup>";
					if(Core::getObject('session')->checkPerm('editplayers')) {
						echo "	<col width='5%' />";
						echo "	<col width='45%' />";
					} else {
						echo "	<col width='50%' />";
					}
					echo "	<col width='20%' />";
					echo "	<col width='10%' />";
					echo "	<col width='10%' />";
					echo "	<col width='10%' />";
					echo "</colgroup>";
					echo "<thead>";
					echo "<tr>";
					if(Core::getObject('session')->checkPerm('editplayers'))
						echo "<th><input type='checkbox' name='playerlist[0]' value='all' class='checkall checkbox' /></th>";
					echo "	<th class='il'>".pt_nick." (". count($PlayerList) .")</th>";
					echo "	<th>".pt_status."</th>";
					echo "	<th>".pt_spec."</th>";
					echo "	<th>".pt_official."</th>";
					echo "	<th class='ir'>".pt_rank."</th>";
					echo "</tr>";
					echo "</thead>";
					echo "<tbody>";
					foreach($PlayerList AS $Player)
					{
						$Player['IsServer']	= (floor($Player['Flags']/100000) % 10) > 0 ? 1 : 0;
						$Player['IsServer']	= ($Player['IsServer'] && Core::getObject('status')->systeminfo['ServerLogin'] != $Player['Login']) ? 2 : $Player['IsServer'];

						echo "<tr>";
						if(Core::getObject('session')->checkPerm('editplayers'))
							echo "<td class='ic'>"; if($Player['IsServer'] != 1) { echo "<input type='checkbox' name='playerlist[]' value='". urlencode($Player['Login']) ."' class='selrow checkbox' />"; } echo "</td>";
						echo "<td><a href='ajax.php?plugin=Player&login={$Player['Login']}' rel='400;0' class='modal' title='{$Player['Login']}'>". Core::getObject('tparse')->toHTML($Player['NickName']) ."</a></td>";
						echo "<td class='ic'>"; if($Player['IsServer'] == 1) { echo pt_server1; } elseif($Player['IsServer'] == 2) { echo pt_server2; } else { echo pt_spectator; } echo "</td>";
						echo "<td class='ic'>"; if($Player['SpectatorStatus']) { echo "<img src='".Core::getSetting('style')."/icons/spec.gif' alt='".pt_spectator."' title='".pt_spectator."' />"; } else { echo "<img src='".Core::getSetting('style')."/icons/playing.gif' alt='".pt_playing."' title='".pt_playing."' />"; } echo "</td>";
						echo "<td class='ic'>"; if($Player['LadderRanking']) { echo "<img src='".Core::getSetting('style')."/icons/official.gif' alt='".pt_official."' title='".pt_official."' />"; } else { echo "<img src='".Core::getSetting('style')."/icons/nofficial.gif' alt='".pt_inofficial."' title='".pt_inofficial."' />"; } echo "</td>";
						echo "<td class='ir'>{$Player['LadderRanking']}</td>";
						echo "</tr>";
					}
					echo "</tbody>";
					echo "</table>";
					echo "</div>";
				}

				//Display Teammode
				if(Core::getObject('status')->gameinfo['GameMode'] == 2) {
					echo "<div class='bg-t'>";
					echo "<table>";
					echo "<colgroup>";
					if(Core::getObject('session')->checkPerm('editplayers')) {
						echo "	<col width='5%' />";
						echo "	<col width='45%' />";
					} else {
						echo "	<col width='50%' />";
					}
					echo "	<col width='15%' />";
					echo "	<col width='15%' />";
					echo "	<col width='20%' />";
					echo "</colgroup>";
					echo "<thead>";
					echo "	<tr>";
					if(Core::getObject('session')->checkPerm('editmaps'))
						echo "<th><input type='checkbox' name='playerlist[0]' value='all' class='checkall checkbox' /></th>";
					echo "		<th class='il'>".pt_nick." (". count($PlayerList) .")</th>";
					echo "		<th>".pt_spec."</th>";
					echo "		<th>".pt_official."</th>";
					echo "		<th class='ir'>".pt_rank."</th>";
					echo "	</tr>";
					echo "</thead>";
					echo "<tbody>";
					if(count($List)) {
						foreach($List AS $team)
						{
							if(!$team['IsTeam'])
								continue;

							echo "<tr>";
							echo "	<td colspan='4' class='legend'>". Core::getObject('tparse')->toHTML($team['NickName']) ."</td><td style='text-align:right;' class='legend'><input type='hidden' name='forcescores[]' value='{$team['PlayerId']}' /><input type='text' name='Score{$team['PlayerId']}' value='{$team['Score']}' style='width:30px; text-align:right;' /></td>";
							echo "</tr>";

							foreach($List AS $Player)
							{
								if($Player['TeamId'] != $team['TeamId'] || $Player['IsTeam'])
									continue;

								echo "<tr>";
								if(Core::getObject('session')->checkPerm('editplayers'))
									echo "	<td class='ic'><input type='checkbox' name='playerlist[]' value='". urlencode($Player['Login']) ."' class='selrow checkbox' /></td>";
								echo "	<td><a href='ajax.php?plugin=Player&login={$Player['Login']}' rel='400;0' class='modal' title='{$Player['Login']}'>". Core::getObject('tparse')->toHTML($Player['NickName']) ."</a></td>";
								echo "	<td class='ic'>"; if($Player['SpectatorStatus']) { echo "<img src='".Core::getSetting('style')."/icons/spec.gif' alt='".pt_spectator."' title='".pt_spectator."' />"; } else { echo "<img src='".Core::getSetting('style')."/icons/playing.gif' alt='".pt_playing."' title='".pt_playing."' />"; } echo "</td>";
								echo "	<td class='ic'>"; if($Player['LadderRanking']) { echo "<img src='".Core::getSetting('style')."/icons/official.gif' alt='".pt_official."' title='".pt_official."' />"; } else { echo "<img src='".Core::getSetting('style')."/icons/nofficial.gif' alt='".pt_inofficial."' title='".pt_inofficial."' />"; } echo "</td>";
								echo "	<td class='ir' style='padding-right:50px;'>{$Player['LadderRanking']}</td>";
								echo "</tr>";
							}
						}
					} else {
						echo "<tr>";
						echo "	<td colspan='"; if(Core::getObject('session')->checkPerm('editplayers')) { echo "5"; } else { echo "4"; } echo "'>".pt_noplayers."</td>";
						echo "</tr>";
					}
					echo "</tbody>";
					echo "</table>";
					echo "</div>";

				//Display other modes
				} else {
					echo "<div class='bg-t'>";
					echo "<table>";
					echo "<colgroup>";
					if(Core::getObject('session')->checkPerm('editplayers')) {
						echo "	<col width='5%' />";
						echo "	<col width='20%' />";
					} else {
						echo "	<col width='25%' />";
					}
					echo "	<col width='10%' />";
					echo "	<col width='10%' />";
					echo "	<col width='10%' />";
					echo "	<col width='10%' />";
					echo "	<col width='15%' />";
					echo "	<col width='5%' />";
					echo "	<col width='5%' />";
					echo "	<col width='10%' />";
					echo "</colgroup>";
					echo "<thead>";
					echo "<tr>";
					if(Core::getObject('session')->checkPerm('editplayers'))
						echo "<th><input type='checkbox' name='playerlist[0]' value='all' class='checkall checkbox' /></th>";
					echo "	<th class='il'>".pt_nick." (". count($PlayerList) .")</th>";
					echo "	<th class='ir'>".pt_rank."</th>";
					echo "	<th class='ir'>".pt_pb."</th>";
					echo "	<th class='ir'>".pt_score."</th>";
					echo "	<th class='ir'>".pt_laps."</th>";
					echo "	<th class='ir'>".pt_ladder."</th>";
					echo "	<th>".pt_spec."</th>";
					echo "	<th>".pt_official."</th>";
					echo "	<th class='ir'>".pt_rank."</th>";
					echo "</tr>";
					echo "</thead>";
					echo "<tbody>";
					if(count($List)) {
						foreach($List AS $Player)
						{
							echo "<tr>";
							if(Core::getObject('session')->checkPerm('editplayers'))
								echo "<td class='ic'><input type='checkbox' name='playerlist[]' value='". urlencode($Player['Login']) ."' class='selrow checkbox' /></td>";
							echo "<td><a href='ajax.php?plugin=Player&login={$Player['Login']}' rel='400;0' class='modal' title='{$Player['Login']}'>". Core::getObject('tparse')->toHTML($Player['NickName']) ."</a></td>";
							echo "<td class='ir'>{$Player['Rank']}</td>";
							echo "<td class='ir'>"; if($Player['BestTime'] == -1) { echo "-"; } else { echo Core::getObject('tparse')->toRaceTime($Player['BestTime']); } echo "</td>";
							echo "<td class='ir'>{$Player['Score']}</td>";
							echo "<td class='ir'>{$Player['NbrLapsFinished']}</td>";
							echo "<td class='ir'>{$Player['LadderScore']}</td>";
							echo "<td class='ic'>"; if($Player['SpectatorStatus']) { echo "<img src='".Core::getSetting('style')."/icons/spec.gif' alt='".pt_spectator."' title='".pt_spectator."' />"; } else { echo "<img src='".Core::getSetting('style')."/icons/playing.gif' alt='".pt_playing."' title='".pt_playing."' />"; } echo "</td>";
							echo "<td class='ic'>"; if($Player['LadderRanking']) { echo "<img src='".Core::getSetting('style')."/icons/official.gif' alt='".pt_official."' title='".pt_official."' />"; } else { echo "<img src='".Core::getSetting('style')."/icons/nofficial.gif' alt='".pt_inofficial."' title='".pt_inofficial."' />"; } echo "</td>";
							echo "<td class='ir'>{$Player['LadderRanking']}</td>";
							echo "</tr>";
						}
					} else {
						echo "<tr>";
						echo "	<td colspan='"; if(Core::getObject('session')->checkPerm('editplayers')) { echo "10"; } else { echo "9"; } echo "'>".pt_noplayers."</td>";
						echo "</tr>";
					}
					echo "</tbody>";
					echo "</table>";
					//echo "</div>";
				}

				if(Core::getObject('session')->checkPerm('editplayers')) {
					echo "<fieldset>";
					echo "<div class='legend'>".pt_options."</div>";
					echo "<div class='f-row'>
						<label for='action'></label>
						<div class='f-field'>";
					echo "<select name='action' class='getcmd' rel='specmodecontainer' href='ajax.php?plugin=Players&op=changemode&selectedmode='>
							<option value='Warn'>".pt_warn."</option>
							<option value='Kick'>".pt_kick."</option>
							<optgroup label='".ct_banlist."'>
								<option value='Ban'>".pt_add."</option>
								<option value='UnBan'>".pt_rem."</option>
							</optgroup>";
					echo "		<optgroup label='".ct_ignorelist."'>
								<option value='Ignore'>".pt_add."</option>
								<option value='UnIgnore'>".pt_rem."</option>
							</optgroup>";
					echo "		<optgroup label='".ct_guestlist."'>
								<option value='AddGuest'>".pt_add."</option>
								<option value='RemoveGuest'>".pt_rem."</option>
							</optgroup>
							<optgroup label='".ct_blacklist."'>
								<option value='BlackList'>".pt_add."</option>
								<option value='UnBlackList'>".pt_rem."</option>
							</optgroup>";
					if(Core::getObject('status')->gameinfo['GameMode'] == 2) {
						echo "		<optgroup label='Forceteam'>
									<option value='ForcePlayerTeam_0'>".pt_blue."</option>
									<option value='ForcePlayerTeam_1'>".pt_red."</option>
								</optgroup>";
					}
					echo "		<optgroup label='Forcespectator'>
								<option value='ForceSpectator_0'>".pt_usersel."</option>
								<option value='ForceSpectator_1'>".pt_spectator."</option>
								<option value='ForceSpectator_2'>".pt_player."</option>
							</optgroup>";
					echo "		<optgroup label='Forcescores'>
								<option value='ForceScores_0'>".pt_forcescores0."</option>
								<option value='ForceScores_1'>".pt_forcescores1."</option>
							</optgroup>";
					echo "	  </select>
						</div>
					      </div>";
					echo "<span id='specmodecontainer'></span>";
					echo "	<input type='hidden' name='plugin' value='{$this->id}' />
						<button type='submit' class='wide' title='".ct_submit."'>".ct_submit."</button>";
					echo "</fieldset>";
					echo "</form>";
				}
			break;
		}
	}

	private function readlist()
	{
		if(array_key_exists('playerlist', $_POST))
			$players = array_values($_POST['playerlist']);

		if (!empty($players)) {
			foreach($players as $value)
			{
				$this->list[] = urldecode($value);
			}
		}
	}

	public function Warn()
	{
		if(empty($this->list)) return;
		foreach($this->list AS $value)
		{
			Core::getObject('chat')->send(pt_warnmsg, $value);
		}
	}

	public function Kick()
	{
		if(empty($this->list)) return;
		foreach($this->list AS $value)
		{
			Core::getObject('actions')->add('Kick', $value);
		}
	}

	public function Ban()
	{
		if(empty($this->list)) return;
		foreach($this->list AS $value)
		{
			Core::getObject('actions')->add('Ban', $value);
		}
	}

	public function Ignore()
	{
		if(empty($this->list)) return;
		foreach($this->list AS $value)
		{
			Core::getObject('actions')->add('Ignore', $value);
		}
	}

	public function UnIgnore()
	{
		if(empty($this->list)) return;
		foreach($this->list AS $value)
		{
			Core::getObject('actions')->add('UnIgnore', $value);
		}
	}

	public function UnBan()
	{
		if(empty($this->list)) return;
		foreach($this->list AS $value)
		{
			Core::getObject('actions')->add('UnBan', $value);
		}
	}

	public function AddGuest()
	{
		if(empty($this->list)) return;
		foreach($this->list AS $value)
		{
			Core::getObject('actions')->add('AddGuest', $value);
		}
		Core::getObject('actions')->add('SaveGuestList', (string) Core::getObject('session')->server->lists->guestlist);
	}

	public function RemoveGuest()
	{
		if(empty($this->list)) return;
		foreach($this->list AS $value)
		{
			Core::getObject('actions')->add('RemoveGuest', $value);
		}
		Core::getObject('actions')->add('SaveGuestList', (string) Core::getObject('session')->server->lists->guestlist);
	}

	public function BlackList()
	{
		if(empty($this->list)) return;
		foreach($this->list AS $value)
		{
			Core::getObject('actions')->add('BlackList', $value);
		}
		Core::getObject('actions')->add('SaveBlackList', (string) Core::getObject('session')->server->lists->blacklist);
	}

	public function UnBlackList()
	{
		if(empty($this->list)) return;
		foreach($this->list AS $value)
		{
			Core::getObject('actions')->add('UnBlackList', $value);
		}
		Core::getObject('actions')->add('SaveBlackList', (string) Core::getObject('session')->server->lists->blacklist);
	}

	public function ForcePlayerTeam_0() { $this->ForcePlayerTeam(0); }
	public function ForcePlayerTeam_1() { $this->ForcePlayerTeam(1); }
	public function ForceSpectator_0() { $this->ForceSpectator(0); }
	public function ForceSpectator_1() { $this->ForceSpectator(1); }
	public function ForceSpectator_2() { $this->ForceSpectator(2); }

	public function ForcePlayerTeam($mode)
	{
		if(empty($this->list)) return;
		foreach($this->list AS $value)
		{
			Core::getObject('actions')->add('ForcePlayerTeam', $value, (int) $mode);
		}
	}

	public function ForceSpectator($mode)
	{
		if(empty($this->list)) return;
		foreach($this->list AS $value)
		{
			Core::getObject('actions')->add('ForceSpectator', $value, (int) $mode);
			Core::getObject('actions')->add('ForceSpectatorTarget', $value, '', (int) $_REQUEST['Forcespectatortarget']);
		}
	}

	public function ForceScores_0() { $this->ForceScores(0); }
	public function ForceScores_1() { $this->ForceScores(1); }

	public function ForceScores($silent)
	{
		$list = array();
		if(array_key_exists('forcescores', $_POST))
			$forcescores = array_values($_POST['forcescores']);

		if (!empty($forcescores)) {
			foreach($forcescores as $value)
			{
				$tmp = 'Score'.$value;
				$list[] = array(
					'PlayerId'	=> (int) $value,
					'Score'		=> (int) $_REQUEST[$tmp]
				);
			}
			Core::getObject('actions')->add('ForceScores', $list, (boolean) $silent);
		}
	}
}
?>