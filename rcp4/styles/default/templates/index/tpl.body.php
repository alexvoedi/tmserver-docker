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
?>
<div id='head'>
	<div id='headl'>
		<div id='headcA' class='headc'>
			<?php
			if(Core::getObject('session')->checkPerm('editmaps')) {
				echo "&#160;<a href='ajax.php?plugin=PCore&action=LastChallenge' rel='serverstatus' class='getcmdc'><img src='".Core::getSetting('style')."/icons/last.gif' width='16' height='16' alt='".ct_last."' title='".ct_last."' /></a>";
				echo "&#160;<a href='ajax.php?plugin=PCore&action=ChallengeRestart' rel='serverstatus' class='getcmdc'><img src='".Core::getSetting('style')."/icons/restart.gif' width='16' height='16' alt='".ct_restart."' title='".ct_restart."' /></a>";
				echo "&#160;<a href='ajax.php?plugin=PCore&action=NextChallenge' rel='serverstatus' class='getcmdc'><img src='".Core::getSetting('style')."/icons/next.gif' width='16' height='16' alt='".ct_next."' title='".ct_next."' /></a>";
				echo "&#160;<a href='ajax.php?plugin=PCore&action=ForceEndRound' rel='serverstatus' class='getcmdc'><img src='".Core::getSetting('style')."/icons/forceend.gif' width='16' height='16' alt='".ct_forceend."' title='".ct_forceend."' /></a>";
			}
			?>
			<span id='currentchallenge'></span>
		</div>
		<div id='headr'></div>
		<div id='headcB' class='headc'>
			<?php
			if(Core::getObject('session')->admin->isLogged() && !array_key_exists('logout', $_REQUEST)) {
				echo "<span>".Core::getObject('session')->admin->username." | </span>";
				echo "<form action='index.php' name='srvchgform' id='srvchgform' style='display:inline;'>";
				echo "<input type='hidden' name='changeserver' value='1' />";
				echo "<select name='serverid' onchange='this.form.submit()' id='chngsrv'>";
				foreach(Core::getObject('session')->servers as $node)
				{
					$admin = Core::getObject('session')->admins->xpath("//admins/admin[id='".Core::getObject('session')->admin->id."']/servers/server[attribute::id='".(string) $node->id."']/../..");
					if($admin[0]) {
						echo "<option value='". (string) $node->id ."'";
						if((string) $node->id == Core::getObject('session')->server->id) { echo " selected='selected' class='selected'"; }
						echo ">". (string) $node->name ."</option>";
					}
				}
				echo "</select>";
				echo "</form>";
				echo " (<a href='tmtp://#join=". Core::getObject('status')->systeminfo['ServerLogin'] ."'><span id='serverstatus'>". Core::getObject('status')->server['Name'] ."</span></a>)";

				if(Core::getObject('session')->admin->username != 'Guest') {
					foreach(Plugins::getAllPlugins() AS $plugin)
					{
						if($plugin->display == 'quick' && !empty($plugin->quickoptions)) {
							foreach($plugin->quickoptions AS $option)
							{
								echo "&#160;<a href='{$option['url']}' rel='{$option['rel']}' class='{$option['class']}'><img src='".Core::getSetting('style')."/icons/{$option['icon']}.gif' width='16' height='16' alt='{$option['text']}' title='{$option['text']}' /></a>";
							}
						}
					}
				}
				echo "&#160;<a href='index.php?logout=true'><img src='".Core::getSetting('style')."/icons/logout.gif' width='16' height='16' alt='".ct_logout."' title='".ct_logout."' /></a>";
			}
			?>
		</div>
	</div>
</div>

{dumpmsgs}

<table id='mainbox'>
<colgroup>
	<col width='35%' />
	<col width='65%' />
</colgroup>
<tr><td>
	<div class='boxtt'>
		<div class='boxbb'>
			<div class='boxll'>
				<div class='boxrr'>
					<div class='boxtr'>
						<div class='boxtl'>
							<div class='boxbr'>
								<div class='boxbl'>
									<div class='boxh'><span id='sideareatitle'>CHAT</span></div>
									<div class='boxc'>
										<ul id='sidetab' class='tabs'>
										<?php
										foreach(Plugins::getAllPlugins() AS $plugin)
										{
											if($plugin->display == 'side') {
												echo "<li><a href='ajax.php?plugin={$plugin->id}&help=1' rel='sidearea'";
												if($plugin->usejs) {
													echo " ref='".Core::getSetting('pluginpath')."{$plugin->id}/{$plugin->id}.js:{$plugin->id}'";
												}
												echo ">{$plugin->title}</a></li>\r\n";
											}
										}
										?>
										</ul>
										<div id='sidearea' class='tabsc'></div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</td><td>
	<div class='boxtt'>
		<div class='boxbb'>
			<div class='boxll'>
				<div class='boxrr'>
					<div class='boxtr'>
						<div class='boxtl'>
							<div class='boxbr'>
								<div class='boxbl'>
									<div class='boxh'><span id='mainareatitle'>PLUGINS</span></div>
									<div class='boxc'>
										<ul id='maintab' class='tabs'>
										<?php
										foreach(Plugins::getAllPlugins() AS $plugin)
										{
											if($plugin->display == 'main') {
												echo "<li><a href='ajax.php?plugin={$plugin->id}&help=1' rel='mainarea'";
												if($plugin->usejs) {
													echo " ref='".Core::getSetting('pluginpath')."{$plugin->id}/{$plugin->id}.js:{$plugin->id}'";
												}
												echo ">{$plugin->title}</a></li>\r\n";
											}
										}
										?>
										</ul>
										<div id='mainarea' class='tabsc'></div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</td></tr>
</table>