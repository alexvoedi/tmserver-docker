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
class ServerSettings extends rcp_plugin
{
	public  $display		= 'side';
	public  $title			= 'Serverset.';
	public  $author			= 'hal.ko.sascha';
	public  $version		= '4.0.3.6';
	public  $nservstatus	= array(3,4,5);
	public  $vpermissions	= array('viewserversettings');
	public  $apermissions	= array('SetServerOptions' => 'editserversettings');

	public function onOutput()
	{
		if(Core::getObject('gbx')->query('GetServerOptions', 1)) {
			$ServerOptions = Core::getObject('gbx')->getResponse();

			//Convert Milliseconds to seconds
			$ServerOptions['NextCallVoteTimeOut']	 = $ServerOptions['NextCallVoteTimeOut']	? $ServerOptions['NextCallVoteTimeOut']/1000	: 0;
			$ServerOptions['CurrentCallVoteTimeOut'] = $ServerOptions['CurrentCallVoteTimeOut']	? $ServerOptions['CurrentCallVoteTimeOut']/1000 : 0;
		}

		if(Core::getObject('gbx')->query('GetBuddyNotification', '')) {
			$_REQUEST['BuddyNotification'] = Core::getObject('gbx')->getResponse();
		}

		echo "<form action='ajax.php' method='post' name='serversettings' id='serversettings' rel='{$this->display}area' class='postcmd'>";
		echo "<fieldset>";
		echo "<div class='legend'>".pt_general."</div>";

		//if($this->reseller && array_key_exists('name', $this->fields)) {
		echo "	<div class='f-row'>
					<label for='Name'>".pt_name."</label>
					<div class='f-field'><input type='text' name='Name' value='{$ServerOptions['Name']}' /></div>
				</div>";
		echo "	<div class='f-row'>
					<label for='Comment' style='height:50px;'>".pt_comment."</label>
					<div class='f-field' style='height:50px;'><textarea name='Comment' cols='15' rows='3' style='height:50px;'>{$ServerOptions['Comment']}</textarea></div>
				</div>";
		echo "	<div class='f-row'>
					<label for='HideServer'>".pt_hideserver."</label>
					<div class='f-field'><select name='HideServer' id='HideServer'><option value='0'"; if($ServerOptions['HideServer'] == 0) { echo " selected='selected'"; } echo ">".pt_hideserver0."</option><option value='1'"; if($ServerOptions['HideServer'] == 1) { echo " selected='selected'"; } echo ">".pt_hideserver1."</option><option value='2'"; if($ServerOptions['HideServer'] == 2) { echo " selected='selected'"; } echo ">".pt_hideserver2."</option></select></div>
				</div>";
		echo "	<div class='f-row'>
					<label for='BuddyNotification'>".pt_buddynotification."</label>
					<div class='f-field'><input type='checkbox' class='checkbox' id='BuddyNotification' name='BuddyNotification'"; if($_REQUEST['BuddyNotification']) { echo " checked='checked'"; } echo " /></div>
				</div>";
		echo "	<div class='f-row'>
					<label for='RefereeMode'>".pt_refereemode."</label>
					<div class='f-field'><select name='RefereeMode' id='RefereeMode'><option value='0'"; if($ServerOptions['RefereeMode'] == 0) { echo " selected='selected'"; } echo ">".pt_refereemode0."</option><option value='1'"; if($ServerOptions['RefereeMode'] == 1) { echo " selected='selected'"; } echo ">".pt_refereemode1."</option></select></div>
				</div>";
		echo "	<div class='f-row'>
					<label for='AutoSaveValidationReplays'>".pt_autosavevalidationreplays."</label>
					<div class='f-field'><input type='checkbox' class='checkbox' name='AutoSaveValidationReplays'"; if($ServerOptions['AutoSaveValidationReplays']) { echo " checked='checked'"; } echo " /></div>
				</div>";
		echo "	<div class='f-row'>
					<label for='UseChangingValidationSeed'>".pt_usechangingvalidationseed."</label>
					<div class='f-field'>
						<div class='current'><input type='checkbox' class='checkbox' name='CurrentUseChangingValidationSeed' disabled='disabled'"; if($ServerOptions['CurrentUseChangingValidationSeed']) { echo " checked='checked'"; } echo " /></div>
						<div class='next'><input type='checkbox' class='checkbox' name='UseChangingValidationSeed'"; if($ServerOptions['NextUseChangingValidationSeed']) { echo " checked='checked'"; } echo " /></div>
					</div>
			</div>";
		echo "	<div class='f-row'>
					<label for='NextVehicleNetQuality'>".pt_vehiclequal."</label>
					<div class='f-field'>
						<div class='current'>"; if($ServerOptions['CurrentVehicleNetQuality'] == 0) { echo pt_vehiclequal0; } elseif($ServerOptions['CurrentVehicleNetQuality'] == 1) { echo pt_vehiclequal1; } echo "</div>
						<div class='next'>
							<select name='NextVehicleNetQuality' class='f-dw'>
								<option value='0'"; if($ServerOptions['NextVehicleNetQuality'] == 0) { echo " selected='selected'"; } echo "> ".pt_vehiclequal0." </option>
								<option value='1'"; if($ServerOptions['NextVehicleNetQuality'] == 1) { echo " selected='selected'"; } echo "> ".pt_vehiclequal1." </option>
							</select>
						</div>
					</div>
				</div>";
		echo "	<div class='f-row'>
					<label for='AllowChallengeDownload'>".pt_download."</label>
					<div class='f-field'><input type='checkbox' class='checkbox' name='AllowChallengeDownload'"; if($ServerOptions['AllowChallengeDownload']) { echo " checked='checked'"; } echo " /></div>
				</div>";
		echo "	<div class='f-row'>
					<label for='AutoSaveReplays'>".pt_replays."</label>
					<div class='f-field'><input type='checkbox' class='checkbox' name='AutoSaveReplays'"; if(array_key_exists('AutoSaveReplays', $ServerOptions)) { if($ServerOptions['AutoSaveReplays']) { echo " checked='checked'"; } } echo " /></div>
				</div>";
		echo "	<div class='f-row'>
					<label for='NextLadderMode'>Ladder Mode</label>
					<div class='f-field'>
						<div class='current'>"; if($ServerOptions['CurrentLadderMode'] == 0) { echo pt_ladder0; } elseif($ServerOptions['CurrentLadderMode'] == 1) { echo pt_ladder1; } elseif($ServerOptions['CurrentLadderMode'] == 2) { echo pt_ladder2; } else { echo "Undefined"; } echo "</div>
						<div class='next'>
							<select name='NextLadderMode' class='f-dw'>
								<option value='0'"; if($ServerOptions['NextLadderMode'] == 0) { echo " selected='selected'"; } echo "> ".pt_ladder0." </option>
								<option value='1'"; if($ServerOptions['NextLadderMode'] == 1) { echo " selected='selected'"; } echo "> ".pt_ladder1." </option>
							</select>
						</div>
					</div>
				</div>";
		echo "</fieldset>";

		echo "<fieldset>";
		echo "<div class='legend'>".pt_password."</div>";
		echo "	<div class='f-row'>
					<label for='Password'>".pt_playerpsw."</label>
					<div class='f-field'><input type='text' name='Password'  value='{$ServerOptions['Password']}' /></div>
				</div>";
		echo "	<div class='f-row'>
					<label for='PasswordForSpectator'>".pt_spectatorpsw."</label>
					<div class='f-field'><input type='text' name='PasswordForSpectator' value='{$ServerOptions['PasswordForSpectator']}' /></div>
				</div>";
		echo "	<div class='f-row'>
					<label for='PasswordForReferee'>".pt_refereepsw."</label>
					<div class='f-field'><input type='text' name='PasswordForReferee' value='{$ServerOptions['PasswordForReferee']}' /></div>
				</div>";
		echo "</fieldset>";

		echo "<fieldset>";
		echo "<div class='legend'>".pt_p2p."</div>";
		echo "	<div class='f-row'>
					<label for='IsP2PUpload'>".pt_p2pup."</label>
					<div class='f-field'><input type='checkbox' class='checkbox' name='IsP2PUpload'"; if($ServerOptions['IsP2PUpload']) { echo " checked='checked'"; } echo " /></div>
				</div>";
		echo "	<div class='f-row'>
					<label for='IsP2PDownload'>".pt_p2pdown."</label>
					<div class='f-field'><input type='checkbox' class='checkbox' name='IsP2PDownload'"; if($ServerOptions['IsP2PDownload']) { echo " checked='checked'"; } echo " /></div>
				</div>";
		echo "</fieldset>";
		echo "<fieldset>";
		echo "<div class='legend'>".pt_maxplayers."</div>";
		echo "	<div class='f-row'>
					<label for='NextMaxPlayers'>".pt_maxplayersp."</label>
					<div class='f-field'>
						<div class='current'>{$ServerOptions['CurrentMaxPlayers']}</div>
						<div class='next'><input type='text' name='NextMaxPlayers' value='{$ServerOptions['NextMaxPlayers']}' /></div>
					</div>
				</div>";
		echo "	<div class='f-row'>
					<label for='NextMaxSpectators'>".pt_maxplayerss."</label>
					<div class='f-field'>
						<div class='current'>{$ServerOptions['CurrentMaxSpectators']}</div>
						<div class='next'><input type='text' name='NextMaxSpectators' value='{$ServerOptions['NextMaxSpectators']}'/></div>
					</div>
				</div>";
		echo "</fieldset>";
		echo "<fieldset>";
		echo "<div class='legend'>".pt_callvote."</div>";
		echo "	<div class='f-row'>
					<label for='NextCallVoteTimeOut'>".pt_callvotet."</label>
					<div class='f-field'>
						<div class='current'>{$ServerOptions['CurrentCallVoteTimeOut']} ".pt_sec."</div>
						<div class='next'><input type='text' name='NextCallVoteTimeOut' value='{$ServerOptions['NextCallVoteTimeOut']}' /></div>
					</div>
				</div>";
		echo "	<div class='f-row'>
					<label for='CallVoteRatio'>".pt_callvoter."</label>
					<div class='f-field'><input type='text' name='CallVoteRatio' value='{$ServerOptions['CallVoteRatio']}' /></div>
				</div>";
		echo "</fieldset>";

		if(Core::getObject('session')->checkPerm('editserversettings')) {
			echo "<input type='hidden' name='plugin' value='{$this->id}' />";
			echo "<input type='hidden' name='action' value='SetServerOptions' />";
			echo "<button type='submit' title='".ct_submit."' class='wide'>".ct_submit."</button>";
		}
		echo "</form>";
	}

	public function SetServerOptions()
	{
		// Update servers.xml
		$server = Core::getObject('session')->servers->xpath("/servers/server[id='". Core::getObject('session')->server->id ."']");
		if($server[0]) {
			$server[0]->name = Core::getObject('tparse')->stripCode((string) $_REQUEST['Name']);
			Core::getObject('session')->servers->asXML(Core::getSetting('xmlpath').'servers.xml');
		} else {
			trigger_error(pt_nameupderr);
		}

		// Update buddy settings
		Core::getObject('actions')->add('SetBuddyNotification', '', (bool) $_REQUEST['BuddyNotification']);

		// Send new data to server
		$stc = array(
			'Name' 						=> (string) $_REQUEST['Name'],
			'Comment' 					=> (string) $_REQUEST['Comment'],
			'Password' 					=> (string) $_REQUEST['Password'],
			'PasswordForSpectator' 		=> (string) $_REQUEST['PasswordForSpectator'],
			'PasswordForReferee'		=> (string) $_REQUEST['PasswordForReferee'],
			'NextMaxPlayers' 			=> (int) $_REQUEST['NextMaxPlayers']+0,
			'NextMaxSpectators' 		=> (int) $_REQUEST['NextMaxSpectators']+0,
			'IsP2PUpload' 				=> (bool) array_key_exists('IsP2PUpload', $_REQUEST),
			'IsP2PDownload'				=> (bool) array_key_exists('IsP2PDownload', $_REQUEST),
			'NextLadderMode' 			=> (int) $_REQUEST['NextLadderMode']+0,
			'NextVehicleNetQuality' 	=> (int) $_REQUEST['NextVehicleNetQuality']+0,
			'NextCallVoteTimeOut' 		=> (int) $_REQUEST['NextCallVoteTimeOut']*1000,
			'CallVoteRatio' 			=> doubleval($_REQUEST['CallVoteRatio']),
			'AllowChallengeDownload' 	=> (bool) array_key_exists('AllowChallengeDownload', $_REQUEST),
			'AutoSaveReplays'			=> (bool) array_key_exists('AutoSaveReplays', $_REQUEST),
			'RefereeMode'				=> (int) $_REQUEST['RefereeMode']+0,
			'AutoSaveValidationReplays'	=> (bool) array_key_exists('AutoSaveValidationReplays', $_REQUEST),
			'HideServer'				=> (int) $_REQUEST['HideServer']+0,
			'UseChangingValidationSeed'	=> (bool) array_key_exists('UseChangingValidationSeed', $_REQUEST)
		);
		Core::getObject('actions')->add('SetServerOptions', $stc);
	}
}
?>