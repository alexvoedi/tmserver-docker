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
class Player extends rcp_plugin
{
	public  $display	= 'box';
	public  $title		= 'Player Info';
	public  $author		= 'hal.ko.sascha';
	public  $version	= '4.0.3.6';
	public  $nservstatus	= array(3,4,5);
	public  $vpermissions	= array('viewplayers');
	private $login;
    
	public function onLoad()
	{
        	$this->login = (array_key_exists('login', $_REQUEST)) ? urldecode($_REQUEST['login']) : '';
	}

	public function onOutput()
	{
		/*
		GetDetailedPlayerInfo
		struct GetDetailedPlayerInfo(string)
		Returns a struct containing the infos on the player with the specified login.
		The structure contains the following fields :
			Login, NickName, PlayerId, TeamId, IPAddress,
			DownloadRate, UploadRate, Language, IsSpectator, IsInOfficialMode,
			a structure named Avatar,
			an array of structures named Skins,
			a structure named LadderStats, 
			HoursSinceZoneInscription
			and OnlineRights (0: nations account, 3: united account).
		*/
		if(Core::getObject('gbx')->query('GetDetailedPlayerInfo', $this->login)) {
			$PlayerInfo = Core::getObject('gbx')->getResponse();
			$accounttype = !$PlayerInfo['OnlineRights'] ? 'Nations' : 'United';
		}
		?>
		<fieldset>
			<div class='legend'><?php echo pt_gcd; ?></div>
			<div class='f-row'>
				<label><?php echo pt_name; ?></label>
				<div class='f-field'><?php echo Core::getObject('tparse')->toHTML($PlayerInfo['NickName']); ?></div>
			</div>
			<div class='f-row'>
				<label><?php echo pt_name2; ?></label>
				<div class='f-field'><?php echo $PlayerInfo['NickName']; ?></div>
			</div>
			<div class='f-row'>
				<label><?php echo pt_loginid; ?></label>
				<div class='f-field'><?php echo $PlayerInfo['Login'] .' / '. $PlayerInfo['PlayerId']; ?></div>
			</div>
			<div class='f-row'>
				<label><?php echo pt_accounttype; ?></label>
				<div class='f-field'><?php echo $accounttype; ?></div>
			</div>
			<div class='f-row'>
				<label><?php echo pt_ip; ?></label>
				<div class='f-field'><?php echo $PlayerInfo['IPAddress']; ?></div>
			</div>
			<div class='f-row'>
				<label><?php echo pt_rates; ?></label>
				<div class='f-field'><?php echo $PlayerInfo['DownloadRate'] .'kb/s / '. $PlayerInfo['UploadRate'] .' kb/s'; ?></div>
			</div>
			<div class='f-row'>
				<label><?php echo pt_language; ?></label>
				<div class='f-field'><?php echo $PlayerInfo['Language']; ?></div>
			</div>
		</fieldset>
		<?php
	}
}
?>