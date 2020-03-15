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
class Overview extends rcp_plugin
{
	public  $display	= 'main';
	public  $title		= 'Overview';
	public  $author		= 'hal.ko.sascha';
	public  $version	= '4.0.3.6';
	public  $nservstatus= array(2,3,4,5);
   
	public function onOutput()
	{
		if(Core::getObject('gbx')->query('GetVersion')) {
			$Version = Core::getObject('gbx')->getResponse();
		}

		echo "<fieldset>";
		echo "<div class='legend'>".pt_status."</div>";
		echo "	<div class='f-row'>
				<label>".pt_status."</label>
				<div class='f-field'>";
		if(Core::getObject('status')->server['Code'] == 2) {
			echo pt_bussy;
		} else {
			echo Core::getObject('status')->server['Name'];
		}
		echo "</div></div>";

		if(Core::getObject('status')->server['Code'] == 4) {
			if(Core::getObject('gbx')->query('GetGameInfos', 1)) {
				$GameInfos = Core::getObject('gbx')->getResponse();
			}

			if(Core::getObject('session')->checkPerm('viewmaps')) {
				if(Core::getObject('gbx')->query('GetCurrentChallengeInfo')) {
					$CurrentChallengeInfo = Core::getObject('gbx')->getResponse();
					$CurrentChallengeInfo = array(
						'Name'		=> Core::getObject('tparse')->toHTML($CurrentChallengeInfo['Name']),
						'UId'		=> $CurrentChallengeInfo['UId'],
						'FileName'	=> urlencode($CurrentChallengeInfo['FileName'])
					);
				}

				if(isset($CurrentChallengeInfo)) {
					echo "	<div class='f-row'>
							<label>".pt_challenge."</label>
							<div class='f-field'><a href='ajax.php?plugin=Challenge&file=". $CurrentChallengeInfo['FileName'] ."' rel='400;0' class='modal' title='".pt_challenge."'>". $CurrentChallengeInfo['Name'] ."</a>";
					echo "	</div></div>";
				}
			}

			if(Core::getObject('session')->checkPerm('viewplayers')) {
				$i = 0;
				$PlayerList = array();
				while(true)
				{
					Core::getObject('gbx')->suppressNextError();
					if(!Core::getObject('gbx')->query('GetPlayerList', 50, $i, 1)) break;
					$i = $i + 50;
					$Players = Core::getObject('gbx')->getResponse();
					if(empty($Players)) break;
					$PlayerList = array_merge($PlayerList, $Players);
				}
				$players = count($PlayerList);

				if(Core::getObject('gbx')->query('GetServerOptions', 1)) {
					$ServerOptions = Core::getObject('gbx')->getResponse();
				}

				echo "	<div class='f-row'>
						<label>".pt_players."</label>
						<div class='f-field'>{$players}/{$ServerOptions['CurrentMaxPlayers']}</div>
					</div>";
			}
		}

		if(Core::getObject('gbx')->query('GetLadderServerLimits')) {
			$LadderLimit = Core::getObject('gbx')->getResponse();
			echo "	<div class='f-row'>
					<label>".pt_ladderlimit."</label>
					<div class='f-field'>{$LadderLimit['LadderServerLimitMin']}/{$LadderLimit['LadderServerLimitMax']}</div>
				</div>";
		}

		if(Core::getObject('status')->getGameFromPackmask() == 'United') {
			if(Core::getObject('gbx')->query('GetServerCoppers')) {
				$Coppers = Core::getObject('gbx')->getResponse();
			}

			echo "	<div class='f-row'>
					<label>".pt_coppers."</label>
					<div class='f-field'>{$Coppers}</div>
				</div>";
			echo "</fieldset>";
		}

		echo "<fieldset>";
		echo "<div class='legend'>".pt_connectedto." ".Core::getObject('status')->systeminfo['PublishedIp']."</div>";
		echo "	<div class='f-row'>
				<label>".pt_server."</label>
				<div class='f-field'>{$Version['Name']} - ". Core::getObject('status')->getGameFromPackmask() ."</div>
			</div>";
		echo "  <div class='f-row'>
				<label>".pt_version."</label>
				<div class='f-field'>{$Version['Version']}</div>
			</div>";
		echo "  <div class='f-row'>
				<label>".pt_port."</label>
				<div class='f-field'>".Core::getObject('status')->systeminfo['Port']." / ".Core::getObject('status')->systeminfo['P2PPort']." / ".Core::getObject('session')->server->connection['port']."</div>
			</div>";
		echo "</fieldset>";

		if(Core::getObject('gbx')->query('GetNetworkStats')) {
			$NetworkStats = Core::getObject('gbx')->getResponse();
			$NetworkStats['Uptime'] = $this->uptime($NetworkStats['Uptime']);
		}
		echo "<fieldset>";
		echo "<div class='legend'>".pt_network."</div>";
		echo "  <div class='f-row'>
				<label>".pt_uptime."</label>
				<div class='f-field'>{$NetworkStats['Uptime']}</div>
			</div>";
		echo "  <div class='f-row'>
				<label>".pt_connections."</label>
				<div class='f-field'>{$NetworkStats['NbrConnection']}</div>
			</div>";
		echo "  <div class='f-row'>
				<label>&#216; ".pt_ctime."</label>
				<div class='f-field'>{$NetworkStats['MeanConnectionTime']}</div>
			</div>";
		echo "  <div class='f-row'>
				<label>&#216; ".pt_players."</label>
				<div class='f-field'>{$NetworkStats['MeanNbrPlayer']}</div>
			</div>";
		echo "  <div class='f-row'>
				<label>".pt_recvnetrate."</label>
				<div class='f-field'>{$NetworkStats['RecvNetRate']}</div>
			</div>";
		echo "  <div class='f-row'>
				<label>".pt_totalrecvsize."</label>
				<div class='f-field'>{$NetworkStats['TotalReceivingSize']}</div>
			</div>";
		echo "  <div class='f-row'>
				<label>".pt_sendnetrate."</label>
				<div class='f-field'>{$NetworkStats['SendNetRate']}</div>
			</div>";
		echo "	<div class='f-row'>
				<label>".pt_totalsendingsize."</label>
				<div class='f-field'>{$NetworkStats['TotalSendingSize']}</div>
			</div>";
		echo "</fieldset>";
	}

	private function uptime($mwtime)
	{
		$days	= floor($mwtime/86400);
		$hours	= floor(($mwtime%86400)/3600);
		$min	= floor((($mwtime%86400)%3600)/60);
		return sprintf("%d d %d h %2d m", $days, $hours, $min);
	}
}
?>