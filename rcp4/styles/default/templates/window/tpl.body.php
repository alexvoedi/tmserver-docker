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
			<span id='currentchallenge'></span>
		</div>
		<div id='headr'></div>
		<div id='headcB' class='headc'>
			<?php
			if(Core::getObject('session')->admin->isLogged() && !array_key_exists('logout', $_REQUEST)) {
				echo "<span>".Core::getObject('session')->admin->username." | </span>";
				echo Core::getObject('session')->server->name ." (<a href='tmtp://#join=". Core::getObject('status')->systeminfo['ServerLogin'] ."'><span id='serverstatus'>". Core::getObject('status')->server['Name'] ."</span></a>)";
			}
			?>
		</div>
	</div>
</div>

<table id='mainbox'>
<div class='boxtt'>
	<div class='boxbb'>
		<div class='boxll'>
			<div class='boxrr'>
				<div class='boxtr'>
					<div class='boxtl'>
						<div class='boxbr'>
							<div class='boxbl'>
								<div class='boxh'><span id='sideareatitle'><?php $plgn = Plugins::getPlugin($_REQUEST['plugin']); echo $plgn->title; ?></span></div>
								<div class='boxc'>
									<ul id='sidetab' class='tabs'>
									<?php
									echo "<li><a href='ajax.php?plugin={$plgn->id}' rel='sidearea'";
									if($plgn->usejs) {
										echo " ref='".Core::getSetting('pluginpath')."{$plgn->id}/{$plgn->id}.js:{$plgn->id}'";
									}
									echo ">{$plgn->title}</a></li>\r\n";
									?>
									</ul>
									<div id='sidearea' class='tabsc'><?php echo $output2; ?></div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
</div>