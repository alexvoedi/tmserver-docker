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
class GameSettings extends rcp_plugin
{
	public  $display		= 'side';
	public  $title			= 'Gameset.';
	public  $author			= 'hal.ko.sascha';
	public  $version		= '4.0.3.6';
	public  $nservstatus	= array(3,4,5);
	public  $vpermissions	= array('viewgamesettings');
	public  $apermissions	= array(
		'SetGameInfos'				=> 'editgamesettings',
		'SetForceShowAllOpponents'	=> 'editgamesettings'
	);
	private $op;
	private $mode;

	public function onLoad()
	{
		$this->op = $_REQUEST['op'] ? $_REQUEST['op'] : false;
	}

	public function onOutput()
	{
		if(Core::getObject('gbx')->query('GetGameInfos',1)) {
			$GameInfos	= Core::getObject('gbx')->getResponse();
			$Current	= $GameInfos['CurrentGameInfos'];
			$Next		= $GameInfos['NextGameInfos'];

			//Change Milliseconds to seconds
			$Current['ChatTime']					= $Current['ChatTime']						? round($Current['ChatTime']/1000)						: 0;
			$Current['TimeAttackLimit'] 			= $Current['TimeAttackLimit']				? round($Current['TimeAttackLimit']/1000)				: 0;
			$Current['TimeAttackSynchStartPeriod']	= $Current['TimeAttackSynchStartPeriod']	? round($Current['TimeAttackSynchStartPeriod']/1000)	: 0;
			$Current['LapsTimeLimit']				= $Current['LapsTimeLimit']					? round($Current['LapsTimeLimit']/1000)					: 0;
			$Current['FinishTimeout'] 				= $Current['FinishTimeout']					? round($Current['FinishTimeout']/1000)					: 0;
			$Next['ChatTime']						= $Next['ChatTime']							? round($Next['ChatTime']/1000)							: 0;
			$Next['TimeAttackLimit'] 				= $Next['TimeAttackLimit'] 					? round($Next['TimeAttackLimit']/1000)					: 0;
			$Next['TimeAttackSynchStartPeriod']		= $Next['TimeAttackSynchStartPeriod']		? round($Next['TimeAttackSynchStartPeriod']/1000)		: 0;
			$Next['LapsTimeLimit']					= $Next['LapsTimeLimit']					? round($Next['LapsTimeLimit']/1000)					: 0;
			$Next['FinishTimeout'] 					= $Next['FinishTimeout']					? round($Next['FinishTimeout']/1000)					: 0;
		}
		
		if(Core::getObject('gbx')->query('GetForceShowAllOpponents')) {
			$ForceInfos	= Core::getObject('gbx')->getResponse();
			$Current['ForceShowAllOpponents']	= $ForceInfos['CurrentValue'];
			$Next['ForceShowAllOpponents']		= $ForceInfos['NextValue'];
		}

		//Get GameMode
		$this->mode = array_key_exists('GameMode',$_REQUEST) ? $_REQUEST['GameMode'] : $Next['GameMode'];

		switch($this->op) {
			case 'mode':
				$this->DisplayMode($Current, $Next);
			break;
		
			default:
				echo "<form action='ajax.php' method='post' id='gamesettings' name='gamesettings' class='postcmd' rel='{$this->display}area'>";
				echo "<fieldset>";
				echo "<div class='legend'>".pt_general."</div>";
				echo "	<div class='f-row'>
						<label for='mode'>".pt_mode."</label>
						<div class='f-field'>
							<div class='current'>"; if($Current['GameMode'] == 0) { echo pt_mode0; } elseif($Current['GameMode'] == 1) { echo pt_mode1; } elseif($Current['GameMode'] == 2) { echo pt_mode2; } elseif($Current['GameMode'] == 3) { echo pt_mode3; } elseif($Current['GameMode'] == 4) { echo pt_mode4; } elseif($Current['GameMode'] == 5) { echo pt_mode5; } else { echo "Undefined"; } echo "</div>
							<div class='next'>
								<select name='geupftwiegsprungen' class='getcmd' rel='modecontainer' href='ajax.php?plugin=GameSettings&op=mode&GameMode='>
									<option value='0'"; if($this->mode == 0) { echo " selected='selected'"; } echo "> ".pt_mode0." </option>
									<option value='1'"; if($this->mode == 1) { echo " selected='selected'"; } echo "> ".pt_mode1." </option>
									<option value='2'"; if($this->mode == 2) { echo " selected='selected'"; } echo "> ".pt_mode2." </option>
									<option value='3'"; if($this->mode == 3) { echo " selected='selected'"; } echo "> ".pt_mode3." </option>
									<option value='4'"; if($this->mode == 4) { echo " selected='selected'"; } echo "> ".pt_mode4." </option>
									<option value='5'"; if($this->mode == 5) { echo " selected='selected'"; } echo "> ".pt_mode5." </option>
								</select>
							</div>
						</div>
					</div>";
				echo "	<div class='f-row'>
						<label for='ChatTime'>".pt_chattime."</label>
						<div class='f-field'>
							<div class='current'>{$Current['ChatTime']} ".pt_sec."</div>
							<div class='next'><input type='text' name='ChatTime' value='{$Next['ChatTime']}' /></div>
						</div>
					</div>";
				echo "	<div class='f-row'>
						<label for='FinishTimeout'>".pt_finishtimeout."</label>
						<div class='f-field'>
							<div class='current'>{$Current['FinishTimeout']} ".pt_sec."</div>
							<div class='next'><input type='text' name='FinishTimeout' value='{$Next['FinishTimeout']}'/></div>
						</div>
					</div>";
				echo "	<div class='f-row'>
						<label for='AllWarmUpDuration'>".pt_warmupduration."</label>
						<div class='f-field'>
							<div class='current'>{$Current['AllWarmUpDuration']} ".pt_rnd."</div>
							<div class='next'><input type='text' name='AllWarmUpDuration' value='{$Next['AllWarmUpDuration']}'/></div>
						</div>
					</div>";
				echo "	<div class='f-row'>
						<label for='DisableRespawn'>".pt_disablerespawn."</label>
						<div class='f-field'>
							<div class='current'><input type='checkbox' class='checkbox' disabled='disabled'"; if($Current['DisableRespawn']) { echo " checked='checked'"; } echo " /></div>
							<div class='next'><input type='checkbox' class='checkbox' name='DisableRespawn'"; if($Next['DisableRespawn']) { echo " checked='checked'"; } echo " /></div>
						</div>
					</div>";
				echo "	<div class='f-row'>
						<label for='ForceShowAllOpponents'>".pt_forceshowallopponents."</label>
						<div class='f-field'>
							<div class='current'>{$Current['ForceShowAllOpponents']}</div>
							<div class='next'><input type='text' name='ForceShowAllOpponents' value='{$Next['ForceShowAllOpponents']}' /></div>
						</div>
					</div>";
				echo "</fieldset>";

				echo "<span id='modecontainer'>";
					$this->DisplayMode($Current, $Next);
				echo "</span>";

				if(Core::getObject('session')->checkPerm('editgamesettings')) {
					echo "<input type='hidden' name='plugin' value='{$this->id}' />";
					echo "<input type='hidden' name='action' value='SetGameInfos' />";
					echo "<button type='submit' title='".ct_submit."' class='wide'>".ct_submit."</button>";
				}
				echo "</form>";
			break;
		}
	}

	private function DisplayMode($Current, $Next)
	{
		switch($this->mode) {
			default:
				echo "<fieldset>";
				echo "<div class='legend'>".pt_rounds."</div>";
				echo "	<div class='f-row'>
						<label for='RoundsPointsLimit'>".pt_points."</label>
						<div class='f-field'>
							<div class='current'>{$Current['RoundsPointsLimit']} ".pt_pts."</div>
							<div class='next'><input type='text' name='RoundsPointsLimit' value='{$Next['RoundsPointsLimit']}' /></div>
						</div>
					</div>";
				echo "  <div class='f-row'>
						<label for='RoundsForcedLaps'>".pt_roundlaps."</label>
						<div class='f-field'>
							<div class='current'>{$Current['RoundsForcedLaps']} ".pt_laps."</div>
							<div class='next'><input type='text' name='RoundsForcedLaps' value='{$Next['RoundsForcedLaps']}' /></div>
						</div>
					</div>";
				echo "	<div class='f-row'>
						<label for='RoundsUseNewRules'>".pt_newrules."</label>
						<div class='f-field'>
							<div class='current'>"; if($Current['RoundsUseNewRules']) { echo "True"; } else { echo "False"; } echo "</div>
							<div class='next'><input type='checkbox' class='checkbox' name='RoundsUseNewRules'"; if($Next['RoundsUseNewRules']) { echo " checked='checked'"; } echo " /></div>
						</div>
					</div>";
				echo "  <div class='f-row'>
						<label for='RoundsPointsLimitNewRules'>".pt_pointslimitnewrules."</label>
						<div class='f-field'>
							<div class='current'>{$Current['RoundsPointsLimitNewRules']}</div>
							<div class='next'><input type='text' name='RoundsPointsLimitNewRules' value='{$Next['RoundsPointsLimitNewRules']}' /></div>
						</div>
					</div>";
				echo "</fieldset>";
				echo "<input type='hidden' name='GameMode' value='0' />";
			break;

			case 1:
				echo "<fieldset>";
				echo "<div class='legend'>".pt_timeattack."</div>";
				echo "	<div class='f-row'>
						<label for='TimeAttackLimit'>".pt_timelimit."</label>
						<div class='f-field'>
							<div class='current'>{$Current['TimeAttackLimit']} ".pt_sec."</div>
							<div class='next'><input type='text' name='TimeAttackLimit' value='{$Next['TimeAttackLimit']}' /></div>
						</div>
					</div>";
				echo "	<div class='f-row'>
						<label for='TimeAttackSynchStartPeriod'>".pt_syncstart."</label>
						<div class='f-field'>
							<div class='current'>{$Current['TimeAttackSynchStartPeriod']} ".pt_sec."</div>
							<div class='next'><input type='text' name='TimeAttackSynchStartPeriod' value='{$Next['TimeAttackSynchStartPeriod']}' /></div>
						</div>
					</div>";
				echo "</fieldset>";
				echo "<input type='hidden' name='GameMode' value='1' />";
			break;

			case 2:
				echo "<fieldset>";
				echo "<div class='legend'>".pt_team."</div>";
				echo "	<div class='f-row'>
						<label for='TeamPointsLimit'>".pt_points."</label>
						<div class='f-field'>
							<div class='current'>{$Current['TeamPointsLimit']} ".pt_pts."</div>
							<div class='next'><input type='text' name='TeamPointsLimit' value='{$Next['TeamPointsLimit']}' /></div>
						</div>
					</div>";
				echo "	<div class='f-row'>
						<label for='TeamMaxPoints'>".pt_maxpoints."</label>
						<div class='f-field'>
							<div class='current'>{$Current['TeamMaxPoints']} ".pt_pts."</div>
							<div class='next'><input type='text' name='TeamMaxPoints' value='{$Next['TeamMaxPoints']}'/></div>
						</div>
					</div>";
				echo "	<div class='f-row'>
						<label for='TeamUseNewRules'>".pt_newrules."</label>
						<div class='f-field'>
							<div class='current'>"; if($Current['TeamUseNewRules']) { echo "True"; } else { echo "False"; } echo "</div>
							<div class='next'><input type='checkbox' class='checkbox' name='TeamUseNewRules'"; if($Next['TeamUseNewRules']) { echo " checked='checked'"; } echo " /></div>
						</div>
					</div>";
				echo "  <div class='f-row'>
						<label for='TeamPointsLimitNewRules'>".pt_pointslimitnewrules."</label>
						<div class='f-field'>
							<div class='current'>{$Current['TeamPointsLimitNewRules']}</div>
							<div class='next'><input type='text' name='TeamPointsLimitNewRules' value='{$Next['TeamPointsLimitNewRules']}' /></div>
						</div>
					</div>";
				echo "</fieldset>";
				echo "<input type='hidden' name='GameMode' value='2' />";
			break;

			case 3:
				echo "<fieldset>";
				echo "<div class='legend'>Laps Mode</div>";
				echo "	<div class='f-row'>
						<label for='LapsNbLaps'>".pt_nolaps."</label>
						<div class='f-field'>
							<div class='current'>{$Current['LapsNbLaps']} ".pt_laps."</div>
							<div class='next'><input type='text' name='LapsNbLaps' value='{$Next['LapsNbLaps']}' /></div>
						</div>
					</div>";
				echo "	<div class='f-row'>
						<label for='LapsTimeLimit'>".pt_timelimit."</label>
						<div class='f-field'>
							<div class='current'>{$Current['LapsTimeLimit']} ".pt_sec."</div>
							<div class='next'><input type='text' name='LapsTimeLimit' value='{$Next['LapsTimeLimit']}' /></div>
						</div>
					</div>";
				echo "</fieldset>";
				echo "<input type='hidden' name='GameMode' value='3' />";
			break;

			case 4:
				echo "<input type='hidden' name='GameMode' value='4' />";
			break;

			case 5:
				echo "<fieldset>";
				echo "<div class='legend'>".pt_cup."</div>";
				echo "	<div class='f-row'>
						<label for='CupPointsLimit'>".pt_points."</label>
						<div class='f-field'>
							<div class='current'>{$Current['CupPointsLimit']} ".pt_pts."</div>
							<div class='next'><input type='text' name='CupPointsLimit' value='{$Next['CupPointsLimit']}' /></div>
						</div>
					</div>";
				echo "	<div class='f-row'>
						<label for='CupRoundsPerChallenge'>".pt_cuproundsperchallenge."</label>
						<div class='f-field'>
							<div class='current'>{$Current['CupRoundsPerChallenge']}</div>
							<div class='next'><input type='text' name='CupRoundsPerChallenge' value='{$Next['CupRoundsPerChallenge']}' /></div>
						</div>
					</div>";
				echo "	<div class='f-row'>
						<label for='CupNbWinners'>".pt_cupnbwinners."</label>
						<div class='f-field'>
							<div class='current'>{$Current['CupNbWinners']}</div>
							<div class='next'><input type='text' name='CupNbWinners' value='{$Next['CupNbWinners']}' /></div>
						</div>
					</div>";
				echo "	<div class='f-row'>
						<label for='CupWarmUpDuration'>".pt_warmupduration."</label>
						<div class='f-field'>
							<div class='current'>{$Current['CupWarmUpDuration']} ".pt_rnd."</div>
							<div class='next'><input type='text' name='CupWarmUpDuration' value='{$Next['CupWarmUpDuration']}' /></div>
						</div>
					</div>";
				echo "</fieldset>";
				echo "<input type='hidden' name='GameMode' value='5' />";
			break;
		}

		if($this->mode != 0) {
			echo "	<input type='hidden' name='RoundsPointsLimit' value='{$Next['RoundsPointsLimit']}' />";
			if($this->mode != 5) echo "	<input type='hidden' name='RoundsUseNewRules' value='{$Next['RoundsUseNewRules']}' />";
			echo "	<input type='hidden' name='RoundsForcedLaps' value='{$Next['RoundsForcedLaps']}' />
					<input type='hidden' name='RoundsPointsLimitNewRules' value='{$Next['RoundsPointsLimitNewRules']}' />";
		}

		if($this->mode != 1) {
			echo "	<input type='hidden' name='TimeAttackLimit' value='{$Next['TimeAttackLimit']}' />
					<input type='hidden' name='TimeAttackSynchStartPeriod' value='{$Next['TimeAttackSynchStartPeriod']}' />";
		}

		if($this->mode != 2) {
			echo "	<input type='hidden' name='TeamPointsLimit' value='{$Next['TeamPointsLimit']}' />";
			if($this->mode != 5) echo "	<input type='hidden' name='TeamUseNewRules' value='{$Next['TeamUseNewRules']}' />";
			echo "	<input type='hidden' name='TeamMaxPoints' value='{$Next['TeamMaxPoints']}' />
					<input type='hidden' name='TeamPointsLimitNewRules' value='{$Next['TeamPointsLimitNewRules']}' />";
		}

		if($this->mode != 3) {
			echo "	<input type='hidden' name='LapsNbLaps' value='{$Next['LapsNbLaps']}' />
					<input type='hidden' name='LapsTimeLimit' value='{$Next['LapsTimeLimit']}' />";
		}

		if($this->mode != 5) {
			echo "	<input type='hidden' name='CupPointsLimit' value='{$Next['CupPointsLimit']}' />
					<input type='hidden' name='CupRoundsPerChallenge' value='{$Next['CupRoundsPerChallenge']}' />
					<input type='hidden' name='CupNbWinners' value='{$Next['CupNbWinners']}' />
					<input type='hidden' name='CupWarmUpDuration' value='{$Next['CupWarmUpDuration']}' />";
		}
	}

	public function SetGameInfos()
	{
		//Set whether to override the players preferences and always display all opponents (0=no override, 1=show all, other value=minimum number of opponents). Only available to Admin. Requires a challenge restart to be taken into account.
		//$ForceShowAllOpponents = (array_key_exists('ForceShowAllOpponents', $_REQUEST)) ? 1 : 0;
		$ForceShowAllOpponents = (int) $_REQUEST['ForceShowAllOpponents'];
		$stc = array(
			'GameMode' 						=> (int) $_REQUEST['GameMode'],
			'ChatTime' 						=> (int) round($_REQUEST['ChatTime']*1000),
			'RoundsPointsLimit' 			=> (int) $_REQUEST['RoundsPointsLimit'],
			'RoundsUseNewRules' 			=> array_key_exists('RoundsUseNewRules', $_REQUEST),
			'RoundsForcedLaps' 				=> (int) $_REQUEST['RoundsForcedLaps'],

			'TimeAttackLimit' 				=> (int) round($_REQUEST['TimeAttackLimit']*1000),
			'TimeAttackSynchStartPeriod' 	=> (int) round($_REQUEST['TimeAttackSynchStartPeriod']*1000),

			'TeamPointsLimit' 				=> (int) $_REQUEST['TeamPointsLimit'],
			'TeamMaxPoints' 				=> (int) $_REQUEST['TeamMaxPoints'],
			'TeamUseNewRules' 				=> array_key_exists('TeamUseNewRules', $_REQUEST),

			'LapsNbLaps' 					=> (int) $_REQUEST['LapsNbLaps'],
			'LapsTimeLimit' 				=> (int) round($_REQUEST['LapsTimeLimit']*1000),

			'FinishTimeout' 				=> (int) round($_REQUEST['FinishTimeout']*1000),

			'AllWarmUpDuration' 			=> (int) $_REQUEST['AllWarmUpDuration'],
			'DisableRespawn' 				=> array_key_exists('DisableRespawn', $_REQUEST),
			'ForceShowAllOpponents'			=> $ForceShowAllOpponents,

			'RoundsPointsLimitNewRules'		=> (int) $_REQUEST['RoundsPointsLimitNewRules'],
			'TeamPointsLimitNewRules' 		=> (int) $_REQUEST['TeamPointsLimitNewRules'],

			'CupPointsLimit' 				=> (int) $_REQUEST['CupPointsLimit'],
			'CupRoundsPerChallenge' 		=> (int) $_REQUEST['CupRoundsPerChallenge'],
			'CupNbWinners' 					=> (int) $_REQUEST['CupNbWinners'],
			'CupWarmUpDuration' 			=> (int) $_REQUEST['CupWarmUpDuration']
		);
		Core::getObject('actions')->add('SetGameInfos', $stc);
		Core::getObject('actions')->add('SetForceShowAllOpponents', $ForceShowAllOpponents);
	}
}
?>