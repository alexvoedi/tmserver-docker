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
class Maintenance extends rcp_plugin
{
	public  $display		= 'main';
	public  $title			= 'Maintenance';
	public  $author			= 'hal.ko.sascha';
	public  $version		= '4.0.3.6';
	public  $nservcon		= false;
	public  $vpermissions	= array('maintenance');
	public  $apermissions	= array(
		'StopServer'			=> 'globalmaintenance',
		'QuitGame'				=> 'globalmaintenance',
		'StartServerInternet'	=> 'globalmaintenance',
		'StartServerLan'		=> 'globalmaintenance',
		'SetServerPackMask'		=> 'globalmaintenance'
	);
	private $op				= false;
	private $edit			= false;
   
	public function onLoad()
	{
		$this->op	= array_key_exists('op', $_REQUEST)	 ? $_REQUEST['op']	: false;
		$this->edit	= array_key_exists('edit', $_REQUEST)? $_REQUEST['edit']: false;
	}

	public function onOutput()
	{
		switch($this->op) {
			default:
				echo "<fieldset>";
				echo "<div class='legend'>".pt_admins." | <a style='float:none' href='ajax.php?plugin={$this->id}&op=createadmin' rel='{$this->display}area' class='getcmd'>".pt_addadmin."</a></div>";
				echo "</fieldset>";

				echo "<table>";
				echo "<colgroup>";
				echo "	<col width='45%' />";
				echo "	<col width='20%' />";
				echo "	<col width='20%' />";
				echo "	<col width='15%' />";
				echo "</colgroup>";
				echo "<thead>";
				echo "<tr>";
				echo "	<th class='il'>". pt_username ."</th>";
				echo "	<th>". pt_active ."</th>";
				echo "	<th>". pt_assigned ."</th>";
				echo "	<th></th>";
				echo "</tr>";
				echo "</thead>";
				echo "<tbody>";
				foreach(Core::getObject('session')->admins->children() as $node)
				{
					$server = Core::getObject('session')->admins->xpath("//admins/admin[./username='".(string) $node->username."']/servers/server[attribute::id='".(string) Core::getObject('session')->server->id."']");
					if($server[0] || Core::getObject('session')->checkPerm('globalmaintenance')) {
						$tablecolor = ($tablecolor == 'l') ? 'm' : 'l';
						echo "<tr class='bg-{$tablecolor}'>";
						echo "	<td>{$node->username}</td>";
						echo "	<td class='ic'>"; if(stristr((string) $node->active, 'true')) echo "x"; else echo "-"; echo "</td>";
						echo "	<td class='ic'>"; if($server[0]) echo "x"; else echo "-"; echo "</td>";
						echo "	<td class='ic'>
									<a href='ajax.php?plugin={$this->id}&op=createadmin&edit=true&id=".(string) $node->id ."' rel='{$this->display}area' class='getcmd'><img src='".Core::getSetting('style')."/icons/edit.gif' alt='".ct_edit."' title='".ct_edit."' /></a>";
									if(Core::getObject('session')->checkPerm('globalmaintenance')) echo " <a href='ajax.php?plugin={$this->id}&op=deladmin&id=".(string) $node->id ."' rel='{$this->display}area' class='getcmdc'><img src='".Core::getSetting('style')."/icons/del.gif' alt='".ct_delete."' title='".ct_delete."' /></a>";
						echo "	</td>";
						echo "</tr>";
					}
				}
				echo "</tbody>";
				echo "</table>";

				if(Core::getObject('session')->checkPerm('globalmaintenance')) {
					echo "<fieldset>";
					echo "<div class='legend'>".pt_servers." | <a style='float:none' href='ajax.php?plugin={$this->id}&op=createserver' rel='{$this->display}area' class='getcmd'>".pt_addserver."</a></div>";
					echo "</fieldset>";

					echo "<table>";
					echo "<colgroup>";
					echo "	<col width='45%' />";
					echo "	<col width='20%' />";
					echo "	<col width='20%' />";
					echo "	<col width='15%' />";
					echo "</colgroup>";
					echo "<thead>";
					echo "<tr>";
					echo "	<th class='il'>". pt_servername ."</th>";
					echo "	<th class='il'>". pt_serverlogin ."</th>";
					echo "	<th>". pt_assigned ."</th>";
					echo "	<th></th>";
					echo "</tr>";
					echo "</thead>";
					echo "<tbody>";
					foreach(Core::getObject('session')->servers->children() as $node)
					{
							$login = (string) $node->login ? (string) $node->login : 'n/a';
							$admins = Core::getObject('session')->admins->xpath("//admins/admin/servers/server[attribute::id='".(string) $node->id."']/../..");
							$admins = count($admins)+0;
							$tablecolor = ($tablecolor == 'l') ? 'm' : 'l';
							echo "<tr class='bg-{$tablecolor}'>";
							echo "	<td>{$node->name}</td>";
							echo "	<td>{$login}</td>";
							echo "	<td class='ic'>{$admins}</td>";
							echo "	<td class='ic'>
										<a href='ajax.php?plugin={$this->id}&op=createserver&edit=true&id={$node->id}' rel='{$this->display}area' class='getcmd'><img src='".Core::getSetting('style')."/icons/edit.gif' alt='".ct_edit."' title='".ct_edit."' /></a>
										<a href='ajax.php?plugin={$this->id}&op=delserver&id={$node->id}' rel='{$this->display}area' class='getcmdc'><img src='".Core::getSetting('style')."/icons/del.gif' alt='".ct_delete."' title='".ct_delete."' /></a>";
							echo "	</td>";
							echo "</tr>";
					}
					echo "</tbody>";
					echo "</table>";

					echo "<fieldset>";
					echo "<div class='legend'>".pt_groups." | <a style='float:none' href='ajax.php?plugin={$this->id}&op=creategroup' rel='{$this->display}area' class='getcmd'>".pt_groupadd."</a></div>";
					echo "</fieldset>";

					echo "<table>";
					echo "<colgroup>";
					echo "	<col width='65%' />";
					echo "	<col width='20%' />";
					echo "	<col width='15%' />";
					echo "</colgroup>";
					echo "<thead>";
					echo "<tr>";
					echo "	<th class='il'>". pt_groupname ."</th>";
					echo "	<th>". pt_assigned ."</th>";
					echo "	<th></th>";
					echo "</tr>";
					echo "</thead>";
					echo "<tbody>";
					foreach(Core::getObject('session')->groups->children() as $node)
					{
							$admins = Core::getObject('session')->admins->xpath("//admins/admin/servers/server[attribute::group='".(string) $node->id."']/../..");
							$admins = count($admins)+0;
							$tablecolor = ($tablecolor == 'l') ? 'm' : 'l';
							echo "<tr class='bg-{$tablecolor}'>";
							echo "	<td>{$node->name}</td>";
							echo "	<td class='ic'>{$admins}</td>";
							echo "	<td class='ic'>
										<a href='ajax.php?plugin={$this->id}&op=creategroup&edit=true&id={$node->id}' rel='{$this->display}area' class='getcmd'><img src='".Core::getSetting('style')."/icons/edit.gif' alt='".ct_edit."' title='".ct_edit."' /></a>
										<a href='ajax.php?plugin={$this->id}&op=delgroup&id={$node->id}' rel='{$this->display}area' class='getcmdc'><img src='".Core::getSetting('style')."/icons/del.gif' alt='".ct_delete."' title='".ct_delete."' /></a>";
							echo "	</td>";
							echo "</tr>";
					}
					echo "</tbody>";
					echo "</table>";
				}
			break;

			case 'createadmin':
				$_REQUEST['id']			= empty($_REQUEST['id'])		? ''		: $_REQUEST['id'];
				$_REQUEST['active']		= empty($_REQUEST['active'])	? '0'		: $_REQUEST['active'];
				$_REQUEST['username']	= empty($_REQUEST['username'])	? ''		: $_REQUEST['username'];
				$_REQUEST['tmaccount']	= empty($_REQUEST['tmaccount'])	? ''		: $_REQUEST['tmaccount'];
				$_REQUEST['language']	= empty($_REQUEST['language'])	? 'en'		: $_REQUEST['language'];
				$_REQUEST['style']		= empty($_REQUEST['style'])		? 'default'	: $_REQUEST['style'];
				$_REQUEST['nocode']		= empty($_REQUEST['nocode'])	? '0'		: $_REQUEST['nocode'];

				if(!empty($_POST)) {
					$form = array();
					$form['username'] = empty($_REQUEST['username']) ? true : false;
					if(Core::getObject('form')->check($form)) {
						if($this->edit) {
							$admin = Core::getObject('session')->admins->xpath("//admins/admin[id='{$_REQUEST['id']}']");
							if($admin[0]) {
								$admin[0]->active		= $_REQUEST['active'] ? 'true' : 'false';
								$admin[0]->username		= $_REQUEST['username'];
								$admin[0]->tmaccount	= $_REQUEST['tmaccount'];
								$admin[0]->language		= $_REQUEST['language'];
								$admin[0]->style		= $_REQUEST['style'];
								$admin[0]->nocode		= $_REQUEST['nocode']  ? 'true' : 'false';

								if(!empty($_REQUEST['password'])) {
									$password			= $_REQUEST['password'];
									$admin[0]->password	= md5($password);
								} else {
									$password			= Core::getObject('session')->admin->password;
								}

								$admin[0]->servers = false;
								foreach(Core::getObject('session')->servers->children() as $server)
								{
									if($_REQUEST["server-".(string) $server->id] == 'on') {
										$node = $admin[0]->servers->addChild('server');
										$node->addAttribute('id'	, $server->id);
										$node->addAttribute('group'	, $_REQUEST["group-".(string) $server->id]);
									}
								}

								Core::getObject('session')->saveXML(Core::getObject('session')->admins->asXML(), Core::getSetting('xmlpath').'admins.xml');
								Core::getObject('messages')->add(pt_editsuccess);

								//Update Session @ selfedit
								if(Core::getObject('session')->admin->username == $admin[0]->username) {
									Core::getObject('session')->updateAdmin($admin[0]->username, $password, Core::getObject('session')->server->id);
								}
							} else {
								trigger_error(pt_saveerr);
							}
						} else {
							$admin = Core::getObject('session')->admins->xpath("//admins/admin[username='{$_REQUEST['username']}']");
							if($admin[0]) {
								trigger_error(pt_existserr);
							} else {
								$node = Core::getObject('session')->admins->addChild('admin');
								$id  = $this->createUniqueId('A');
								$node->addChild('id', $id);
								$node->addChild('active', $_REQUEST['active'] ? 'true' : 'false');
								$node->addChild('username', $_REQUEST['username']);
								$node->addChild('tmaccount', $_REQUEST['tmaccount']);
								$node->addChild('language', $_REQUEST['language']);
								$node->addChild('style', $_REQUEST['style']);
								$node->addChild('nocode',  $_REQUEST['nocode']  ? 'true' : 'false');

								if(!empty($_REQUEST['password'])) {
									$node->addChild('password', md5($_REQUEST['password']));
								}

								$servers = $node->addChild('servers');
								if(Core::getObject('session')->checkPerm('globalmaintenance')) {
									foreach(Core::getObject('session')->servers->children() as $server)
									{
										if($_REQUEST["server-".(string) $server->id] == 'on') {
											$node2 = $servers->addChild('server');
											$node2->addAttribute('id'		, $server->id);
											$node2->addAttribute('group'	, $_REQUEST["group-".(string) $server->id]);
										}
									}
								} else {
									$node2 = $servers->addChild('server');
									$node2->addAttribute('id', Core::getObject('session')->server->id);
									$node2->addAttribute('group', Core::getObject('session')->admin->group);
									$_REQUEST['server-'.Core::getObject('session')->server->id] = 'on';
									$_REQUEST['group-'.Core::getObject('session')->server->id] = Core::getObject('session')->admin->group;
								}

								Core::getObject('session')->saveXML(Core::getObject('session')->admins->asXML(), Core::getSetting('xmlpath').'admins.xml');
								Core::getObject('messages')->add(pt_addsuccess);
								$_REQUEST['id']	= $id;
								$this->edit		= true;
							}
						}
					}
				} elseif($this->edit) {
					$id = array_key_exists('id', $_REQUEST) ? $_REQUEST['id'] : trigger_error('Invalid admin');
					if($id) {
						$admin = Core::getObject('session')->admins->xpath("//admins/admin[id='{$id}']");
						if($admin[0]) {
							$_REQUEST['id'] 		= (string) $admin[0]->id;
							$_REQUEST['active']		= stristr((string) $admin[0]->active, 'true') ? true : false;
							foreach(Core::getObject('session')->servers->children() as $server)
							{
								//Check if server is assigned to this admin
								$check = Core::getObject('session')->admins->xpath("//admins/admin[./id='".(string) $id."']/servers/server[attribute::id='".(string) $server->id."']");
								$_REQUEST['server-'.(string) $server->id] = $check ? true : false;

								//Get assigned group
								$_REQUEST['group-'.(string) $server->id]  = $check ? (string) $check[0]['group'] : false;
							}
							$_REQUEST['username']	= (string) $admin[0]->username;
							$_REQUEST['tmaccount']	= (string) $admin[0]->tmaccount;
							$_REQUEST['language']	= (string) $admin[0]->language;
							$_REQUEST['style']		= (string) $admin[0]->style;
							$_REQUEST['nocode']		= stristr((string) $admin[0]->nocode, 'true')  ? true : false;
						} else {
		 					trigger_error(pt_editerr);
						}
					}
				}
	
				echo "<form action='ajax.php' method='post' id='createadmin' name='createadmin' rel='{$this->display}area' class='postcmd'>";
				echo "<fieldset>";
				echo "<div class='legend'>".pt_adminedit."</div>";
				echo "	<div class='f-row'>
						<label for='nocode'>".pt_active."</label>";
						if(Core::getObject('session')->checkPerm('globalmaintenance')) {
							echo "<div class='f-field'><input type='checkbox' class='checkbox' name='active'"; if($_REQUEST['active']) { echo " checked='checked'"; } echo " /></div>";
						} else {
							echo "<div class='f-field'><input type='hidden' name='active' value='"; if($_REQUEST['active']) { echo "1"; } else { echo "0"; } echo "' /><input type='checkbox' class='checkbox' disabled='disabled'"; if($_REQUEST['active']) { echo " checked='checked'"; } echo " /></div>";
						}
				echo "	</div>";
				echo "	<div class='f-row'>
						<label for='username'>".pt_username."</label>
						<div class='f-field'><input type='text' name='username' value='{$_REQUEST['username']}' />"; if($form['username']) { echo " <div class='iform'>Invalid form</div>"; } echo "</div>
						</div>";
				echo "	<div class='f-row'>
						<label for='password'>".pt_password."</label>
						<div class='f-field'><input type='password' name='password' value='' /><div>".pt_passwordi."</div></div>
						</div>";
				echo "	<div class='f-row'>
						<label for='tmaccount'>".pt_tmacc."</label>
						<div class='f-field'><input type='text' name='tmaccount' value='{$_REQUEST['tmaccount']}' /><div>".pt_tmacci."</div></div>
						</div>";
				echo "	<div class='f-row'>
						<label for='language'>".pt_language."</label>
						<div class='f-field'><select name='language'>";
				$path = './xml/language/';
				$i    = 0;
				if(is_dir($path)) {
					$dir = opendir($path);
					while($element = readdir($dir))
					{
						$element = str_replace('.xml', '', $element);
						if(is_file($path.$element.'.xml') && $element != '.' && $element != '..') {
							++$i;
							echo "<option value='{$element}'"; if($_REQUEST['language'] == $element) { echo " selected='selected'"; } echo ">{$element}</option>";
						}
					}
					closedir($dir);
				}
				if(!$i) echo "<option value='en'>en</option>";
				echo "		</select></div>
						</div>";
				echo "	<div class='f-row'>
						<label for='style'>".pt_style."</label>
						<div class='f-field'><select name='style'>";
				$path = './styles/';
				if(is_dir($path)) {
					$dir = opendir($path);
					while($element = readdir($dir))
					{
						if(filetype($path.$element) == 'dir' && $element != '.' && $element != '..') {
							echo "<option value='{$element}'"; if($_REQUEST['style'] == $element) { echo " selected='selected'"; } echo ">{$element}</option>";
						}
					}
					closedir($dir);
				} else {
					echo "<option value='default'>default</option>";
				}
				echo "		</select></div>
						</div>";
				echo "	<div class='f-row'>
						<label for='nocode'>".pt_nocode."</label>
						<div class='f-field'><input type='checkbox' class='checkbox' name='nocode'"; if($_REQUEST['nocode']) { echo " checked='checked'"; } echo " /></div>
						</div>";
				echo "</fieldset>";
				if(Core::getObject('session')->checkPerm('globalmaintenance')) {
					echo "<fieldset>";
					echo "<div class='legend'>".pt_adminservers."</div>";
					
					foreach(Core::getObject('session')->servers->children() as $server)
					{
					echo "	<div class='f-row'>
							<label for='server-".(string) $server->id."'>".(string) $server->name."</label>
								<div class='f-field'>
								<input type='checkbox' class='checkbox' name='server-".(string) $server->id."'"; if($_REQUEST["server-".(string) $server->id]) { echo " checked='checked'"; } echo " />";

								echo " ".pt_group." <select name='group-".(string) $server->id."' style='width:150px;'>";
								foreach(Core::getObject('session')->groups AS $group)
								{
									echo "<option value='".(string) $group->id ."'"; if($_REQUEST['group-'.(string) $server->id] == (string) $group->id) { echo " selected='selected'"; } echo ">{$group->name}</option>";
								}
								echo "</select>";

					echo "		</div>";
					echo "	</div>";
					}
					echo "</fieldset>";
				} else {
					foreach(Core::getObject('session')->servers->children() as $server)
					{
						echo "<input type='hidden' name='server-".(string) $server->id."'"; if($_REQUEST["server-".(string) $server->id]) echo " value='on'"; echo " />";

						foreach(Core::getObject('session')->groups AS $group)
						{
							if((string) $group->id != $_REQUEST['group-'.(string) $server->id]) continue;
							echo "<input type='hidden' name='group-".(string) $server->id."' value='".(string) $group->id ."' /> {$group->name}";
						}
					}
				}

				echo "<input type='hidden' name='op' value='{$this->op}' />";
				echo "<input type='hidden' name='edit' value='{$this->edit}' />";
				echo "<input type='hidden' name='id' value='{$_REQUEST['id']}' />";
				echo "<input type='hidden' name='plugin' value='{$this->id}' />";
				echo "<button type='submit' title='".ct_submit."' class='wide'>".ct_submit."</button>";
				echo "</form>";
			break;

			case 'createserver':
				$_REQUEST['id']				= empty($_REQUEST['id'])			? ''	: $_REQUEST['id'];
				$_REQUEST['name']			= empty($_REQUEST['name'])			? ''	: $_REQUEST['name'];
				$_REQUEST['login']			= empty($_REQUEST['login'])			? ''	: $_REQUEST['login'];
				$_REQUEST['settingset']		= empty($_REQUEST['settingset'])	? ''	: $_REQUEST['settingset'];
				$_REQUEST['filepath']		= empty($_REQUEST['filepath'])		? ''	: $_REQUEST['filepath'];

				$_REQUEST['host']			= empty($_REQUEST['host'])			? 'localhost'	: $_REQUEST['host'];
				$_REQUEST['port']			= empty($_REQUEST['port'])			? '5000'		: $_REQUEST['port'];
				$_REQUEST['password']		= empty($_REQUEST['password'])		? 'SuperAdmin'	: $_REQUEST['password'];
				$_REQUEST['communitycode']	= empty($_REQUEST['communitycode'])	? '000000'		: $_REQUEST['communitycode'];

				$_REQUEST['ftp']			= empty($_REQUEST['ftp'])			? '0'					: $_REQUEST['ftp'];
				$_REQUEST['ftphost']		= empty($_REQUEST['ftphost'])		? ''					: $_REQUEST['ftphost'];
				$_REQUEST['ftpport']		= empty($_REQUEST['ftpport'])		? ''					: $_REQUEST['ftpport'];
				$_REQUEST['ftpusername']	= empty($_REQUEST['ftpusername'])	? ''					: $_REQUEST['ftpusername'];
				$_REQUEST['ftppassword']	= empty($_REQUEST['ftppassword'])	? ''					: $_REQUEST['ftppassword'];
				$_REQUEST['ftppath']		= empty($_REQUEST['ftppath'])		? '/GameData/Tracks/'	: $_REQUEST['ftppath'];

				$_REQUEST['sql']			= empty($_REQUEST['sql'])			? '0'			: $_REQUEST['sql'];
				$_REQUEST['sqldsn']			= empty($_REQUEST['sqldsn'])		? 'mysql:dbname=remotecp;host=localhost' : $_REQUEST['sqldsn'];
				$_REQUEST['sqlusername']	= empty($_REQUEST['sqlusername'])	? ''			: $_REQUEST['sqlusername'];
				$_REQUEST['sqlpassword']	= empty($_REQUEST['sqlpassword'])	? ''			: $_REQUEST['sqlpassword'];

				$_REQUEST['listsguest']		= empty($_REQUEST['listsguest'])	? 'guestlist.txt'	: $_REQUEST['listsguest'];
				$_REQUEST['listsblack']		= empty($_REQUEST['listsblack'])	? 'blacklist.txt'	: $_REQUEST['listsblack'];

				if(!empty($_POST)) {
					$form = array();
					$form['name'] = empty($_REQUEST['name']) ? true : false;
					if(Core::getObject('form')->check($form)) {
						if($this->edit) {
							$server = Core::getObject('session')->servers->xpath("//servers/server[id='{$_REQUEST['id']}']");
							if($server[0]) {
								$server[0]->name		= $_REQUEST['name'];
								$server[0]->login		= $_REQUEST['login'];
								$server[0]->settingset	= $_REQUEST['settingset'];
								$server[0]->filepath	= $_REQUEST['filepath'];

								$server[0]->connection->host			= $_REQUEST['host'];
								$server[0]->connection->port			= $_REQUEST['port'];
								$server[0]->connection->password		= $_REQUEST['password'];
								$server[0]->connection->commmunitycode	= $_REQUEST['communitycode'];

								$server[0]->ftp['enabled']	= $_REQUEST['ftp'] ? 'true' : 'false';
								$server[0]->ftp->host		= $_REQUEST['ftphost'];
								$server[0]->ftp->port		= $_REQUEST['ftpport'];
								$server[0]->ftp->username	= $_REQUEST['ftpusername'];
								$server[0]->ftp->password	= $_REQUEST['ftppassword'];
								$server[0]->ftp->path		= $_REQUEST['ftppath'];

								$server[0]->sql['enabled']	= $_REQUEST['sql'] ? 'true' : 'false';
								$server[0]->sql->dsn		= $_REQUEST['sqldsn'];
								$server[0]->sql->username	= $_REQUEST['sqlusername'];
								$server[0]->sql->password	= $_REQUEST['sqlpassword'];

								$server[0]->lists->guestlist= $_REQUEST['listsguest'];
								$server[0]->lists->blacklist= $_REQUEST['listsblack'];

								Core::getObject('session')->saveXML(Core::getObject('session')->servers->asXML(), Core::getSetting('xmlpath').'servers.xml');
								Core::getObject('messages')->add(pt_editsuccess);
							} else {
								trigger_error(pt_saveerr);
							}
						} else {
							$server1 = Core::getObject('session')->servers->xpath("//servers/server[login='{$_REQUEST['login']}']");
							$server2 = Core::getObject('session')->servers->xpath("//servers/server[name='{$_REQUEST['name']}']");
							if($server1[0] || $server2[0]) {
								trigger_error(pt_existserr);
							} else {
								$sid = $this->createUniqueId('S');
								$node = Core::getObject('session')->servers->addChild('server');
								$node->addChild('id', $sid);
								$node->addChild('login', $_REQUEST['login']);
								$node->addChild('name', $_REQUEST['name']);
								$node->addChild('settingset', $_REQUEST['settingset']);
								$node->addChild('filepath', $_REQUEST['filepath']);

								$con = $node->addChild('connection');
								$con->addChild('host', $_REQUEST['host']);
								$con->addChild('port', $_REQUEST['port']);
								$con->addChild('password', $_REQUEST['password']);
								$con->addChild('communitycode', $_REQUEST['communitycode']);

								$ftp = $node->addChild('ftp');
								$ftp->addAttribute('enabled', $_REQUEST['ftp'] ? 'true' : 'false');
								$ftp->addChild('host', $_REQUEST['ftphost']);
								$ftp->addChild('port', $_REQUEST['ftpport']);
								$ftp->addChild('username', $_REQUEST['ftpusername']);
								$ftp->addChild('password', $_REQUEST['ftppassword']);
								$ftp->addChild('path', $_REQUEST['ftppath']);

								$sql = $node->addChild('sql');
								$sql->addAttribute('enabled', $_REQUEST['sql'] ? 'true' : 'false');
								$sql->addChild('dsn', $_REQUEST['sqldsn']);
								$sql->addChild('username', $_REQUEST['sqlusername']);
								$sql->addChild('password', $_REQUEST['sqlpassword']);

								$lists = $node->addChild('lists');
								$lists->addChild('guestlist', $_REQUEST['listsguest']);
								$lists->addChild('blacklist', $_REQUEST['listsblack']);

								Core::getObject('session')->saveXML(Core::getObject('session')->servers->asXML(), Core::getSetting('xmlpath').'servers.xml');
								Core::getObject('messages')->add(pt_addsuccess);
								$_REQUEST['id']	= $id;
								$this->edit		= true;
							}
						}
					}
				}
				elseif($this->edit)
				{
					$id = array_key_exists('id', $_REQUEST) ? $_REQUEST['id'] : trigger_error('Invalid server');
					if($id) {
						$server = Core::getObject('session')->servers->xpath("//servers/server[id='{$id}']");
						if($server[0]) {
							$_REQUEST['login'] 			= (string) $server[0]->login;
							$_REQUEST['name'] 			= (string) $server[0]->name;
							$_REQUEST['settingset']		= (string) $server[0]->settingset;
							$_REQUEST['filepath'] 		= (string) $server[0]->filepath;

							$_REQUEST['host']			= (string) $server[0]->connection->host;
							$_REQUEST['port']			= (string) $server[0]->connection->port;
							$_REQUEST['password']		= (string) $server[0]->connection->password;
							$_REQUEST['communitycode']	= (string) $server[0]->connection->communitycode;

							$_REQUEST['ftp']			= stristr((string) $server[0]->ftp['enabled'], 'true')  ? true : false;
							$_REQUEST['ftphost']		= (string) $server[0]->ftp->host;
							$_REQUEST['ftpport']		= (string) $server[0]->ftp->port;
							$_REQUEST['ftpusername']	= (string) $server[0]->ftp->username;
							$_REQUEST['ftppassword']	= (string) $server[0]->ftp->password;
							$_REQUEST['ftppath']		= (string) $server[0]->ftp->path;

							$_REQUEST['sql']			= stristr((string) $server[0]->sql['enabled'], 'true')  ? true : false;
							$_REQUEST['sqldsn']			= (string) $server[0]->sql->dsn;
							$_REQUEST['sqlusername']	= (string) $server[0]->sql->username;
							$_REQUEST['sqlpassword']	= (string) $server[0]->sql->password;

							$_REQUEST['listsguest']		= (string) $server[0]->lists->guestlist;
							$_REQUEST['listsblack']		= (string) $server[0]->lists->blacklist;
						} else {
		 					trigger_error(pt_editerr);
						}
					}
				}
	
				echo "<form action='ajax.php' method='post' id='createserver' name='createserver' rel='{$this->display}area' class='postcmd'>";
				echo "<fieldset>";
				echo "<div class='legend'>".pt_serveredit."</div>";
				echo "	<div class='f-row'>
						<label for='login'>".pt_serverlogin."</label>
					<div class='f-field'><input type='text' name='login' value='{$_REQUEST['login']}' /></div>
				</div>";
				echo "	<div class='f-row'>
						<label for='name'>".pt_servername."</label>
						<div class='f-field'><input type='text' name='name' value='{$_REQUEST['name']}' />"; if($form['name']) { echo " <div class='iform'>Invalid form</div>"; } echo "</div>
					</div>";
				echo "	<div class='f-row'>
						<label for='settingset'>".pt_settingset."</label>
					<div class='f-field'><input type='text' name='settingset' value='{$_REQUEST['settingset']}' /></div>
					</div>";
				echo "	<div class='f-row'>
						<label for='filepath'>".pt_filepath."</label>
					<div class='f-field'><input type='text' name='filepath' value='{$_REQUEST['filepath']}' /></div>
					</div>";
				echo "</fieldset>";

				echo "<fieldset>";
				echo "<div class='legend'>".pt_connection."</div>";
				echo "	<div class='f-row'>
						<label for='host'>".pt_host."</label>
						<div class='f-field'><input type='text' name='host' value='{$_REQUEST['host']}' /></div>
					</div>";
				echo "	<div class='f-row'>
						<label for='port'>".pt_port."</label>
						<div class='f-field'><input type='text' name='port' value='{$_REQUEST['port']}' /></div>
					</div>";
				echo "	<div class='f-row'>
						<label for='password'>".pt_password."</label>
						<div class='f-field'><input type='text' name='password' value='{$_REQUEST['password']}' /></div>
					</div>";
				echo "	<div class='f-row'>
						<label for='communitycode'>".pt_communitycode."</label>
						<div class='f-field'><input type='text' name='communitycode' value='{$_REQUEST['communitycode']}' /></div>
					</div>";
				echo "</fieldset>";

				echo "<fieldset>";
				echo "<div class='legend'>".pt_ftp."</div>";
				echo "	<div class='f-row'>
						<label for='ftp'>".pt_enabled."</label>
						<div class='f-field'><input type='checkbox' class='checkbox' name='ftp'"; if($_REQUEST['ftp']) { echo " checked='checked'"; } echo " /></div>
					</div>";
				echo "	<div class='f-row'>
						<label for='ftphost'>".pt_host."</label>
						<div class='f-field'><input type='text' name='ftphost' value='{$_REQUEST['ftphost']}' /></div>
					</div>";
				echo "	<div class='f-row'>
						<label for='ftphost'>".pt_port."</label>
						<div class='f-field'><input type='text' name='ftpport' value='{$_REQUEST['ftpport']}' /></div>
					</div>";
				echo "	<div class='f-row'>
						<label for='ftpusername'>".pt_username."</label>
						<div class='f-field'><input type='text' name='ftpusername' value='{$_REQUEST['ftpusername']}' /></div>
					</div>";
				echo "	<div class='f-row'>
						<label for='ftppassword'>".pt_password."</label>
						<div class='f-field'><input type='text' name='ftppassword' value='{$_REQUEST['ftppassword']}' /></div>
					</div>";
				echo "	<div class='f-row'>
						<label for='ftppath'>".pt_path."</label>
						<div class='f-field'><input type='text' name='ftppath' value='{$_REQUEST['ftppath']}' /></div>
					</div>";
				echo "</fieldset>";

				echo "<fieldset>";
				echo "<div class='legend'>".pt_sql."</div>";
				echo "	<div class='f-row'>
						<label for='sql'>".pt_enabled."</label>
						<div class='f-field'><input type='checkbox' class='checkbox' name='sql'"; if($_REQUEST['sql']) { echo " checked='checked'"; } echo " /></div>
					</div>";
				echo "	<div class='f-row'>
						<label for='sqldsn'>".pt_dsn."</label>
						<div class='f-field'><input type='text' name='sqldsn' value='{$_REQUEST['sqldsn']}' /></div>
					</div>";
				echo "	<div class='f-row'>
						<label for='sqlusername'>".pt_username."</label>
						<div class='f-field'><input type='text' name='sqlusername' value='{$_REQUEST['sqlusername']}' /></div>
					</div>";
				echo "	<div class='f-row'>
						<label for='sqlpassword'>".pt_password."</label>
						<div class='f-field'><input type='text' name='sqlpassword' value='{$_REQUEST['sqlpassword']}' /></div>
					</div>";
				echo "</fieldset>";

				echo "<fieldset>";
				echo "<div class='legend'>".pt_lists."</div>";
				echo "	<div class='f-row'>
						<label for='listsguest'>".pt_guestlist."</label>
						<div class='f-field'><input type='text' name='listsguest' value='{$_REQUEST['listsguest']}' /></div>
					</div>";
				echo "	<div class='f-row'>
						<label for='listsblack'>".pt_blacklist."</label>
						<div class='f-field'><input type='text' name='listsblack' value='{$_REQUEST['listsblack']}' /></div>
					</div>";
				echo "</fieldset>";

				echo "<input type='hidden' name='op' value='{$this->op}' />";
				echo "<input type='hidden' name='edit' value='{$this->edit}' />";
				echo "<input type='hidden' name='id' value='{$_REQUEST['id']}' />";
				echo "<input type='hidden' name='plugin' value='{$this->id}' />";
				echo "<button type='submit' title='".ct_submit."' class='wide'>".ct_submit."</button>";
				echo "</form>";
			break;

			case 'creategroup':
				$_REQUEST['id']					= empty($_REQUEST['id'])				? ''	: $_REQUEST['id'];
				$_REQUEST['name']				= empty($_REQUEST['name'])				? ''	: $_REQUEST['name'];
				$_REQUEST['viewplayers']		= empty($_REQUEST['viewplayers'])		? '0'	: $_REQUEST['viewplayers'];
				$_REQUEST['editplayers']		= empty($_REQUEST['editplayers'])		? '0'	: $_REQUEST['editplayers'];
				$_REQUEST['viewmaps']			= empty($_REQUEST['viewmaps'])			? '0'	: $_REQUEST['viewmaps'];
				$_REQUEST['editmaps']			= empty($_REQUEST['editmaps'])			? '0'	: $_REQUEST['editmaps'];
				$_REQUEST['viewchat']			= empty($_REQUEST['viewchat'])			? '0'	: $_REQUEST['viewchat'];
				$_REQUEST['sendchat']			= empty($_REQUEST['sendchat'])			? '0'	: $_REQUEST['sendchat'];
				$_REQUEST['viewserversettings']	= empty($_REQUEST['viewserversettings'])? '0'	: $_REQUEST['viewserversettings'];
				$_REQUEST['editserversettings']	= empty($_REQUEST['editserversettings'])? '0'	: $_REQUEST['editserversettings'];
				$_REQUEST['viewgamesettings']	= empty($_REQUEST['viewgamesettings'])	? '0'	: $_REQUEST['viewgamesettings'];
				$_REQUEST['editgamesettings']	= empty($_REQUEST['editgamesettings'])	? '0'	: $_REQUEST['editgamesettings'];
				$_REQUEST['viewlists']			= empty($_REQUEST['viewlists'])			? '0'	: $_REQUEST['viewlists'];
				$_REQUEST['editlists']			= empty($_REQUEST['editlists'])			? '0'	: $_REQUEST['editlists'];
				$_REQUEST['browse']				= empty($_REQUEST['browse'])			? '0'	: $_REQUEST['browse'];
				$_REQUEST['upload']				= empty($_REQUEST['upload'])			? '0'	: $_REQUEST['upload'];
				$_REQUEST['payment']			= empty($_REQUEST['payment'])			? '0'	: $_REQUEST['payment'];
				$_REQUEST['database']			= empty($_REQUEST['database'])			? '0'	: $_REQUEST['database'];
				$_REQUEST['maintenance']		= empty($_REQUEST['maintenance'])		? '0'	: $_REQUEST['maintenance'];
				$_REQUEST['globalmaintenance']	= empty($_REQUEST['globalmaintenance'])	? '0'	: $_REQUEST['globalmaintenance'];
				$_REQUEST['offlinelogin']		= empty($_REQUEST['offlinelogin'])		? '0'	: $_REQUEST['offlinelogin'];

				if(!empty($_POST)) {
					$form = array();
					$form['name'] = empty($_REQUEST['name']) ? true : false;
					if(Core::getObject('form')->check($form)) {
						if($this->edit) {
							$group = Core::getObject('session')->groups->xpath("//groups/group[id='{$_REQUEST['id']}']");
							if($group[0]) {
								$group[0]->name								= $_REQUEST['name'];
								$group[0]->permissions->viewplayers			= $_REQUEST['viewplayers'] ? 'true' : 'false';
								$group[0]->permissions->editplayers			= $_REQUEST['editplayers'] ? 'true' : 'false';
								$group[0]->permissions->viewmaps			= $_REQUEST['viewmaps'] ? 'true' : 'false';
								$group[0]->permissions->editmaps			= $_REQUEST['editmaps'] ? 'true' : 'false';
								$group[0]->permissions->viewchat			= $_REQUEST['viewchat'] ? 'true' : 'false';
								$group[0]->permissions->sendchat			= $_REQUEST['sendchat'] ? 'true' : 'false';
								$group[0]->permissions->viewserversettings	= $_REQUEST['viewserversettings'] ? 'true' : 'false';
								$group[0]->permissions->editserversettings	= $_REQUEST['editserversettings'] ? 'true' : 'false';
								$group[0]->permissions->viewgamesettings	= $_REQUEST['viewgamesettings'] ? 'true' : 'false';
								$group[0]->permissions->editgamesettings	= $_REQUEST['editgamesettings'] ? 'true' : 'false';
								$group[0]->permissions->viewlists			= $_REQUEST['viewlists'] ? 'true' : 'false';
								$group[0]->permissions->editlists			= $_REQUEST['editlists'] ? 'true' : 'false';
								$group[0]->permissions->browse				= $_REQUEST['browse'] ? 'true' : 'false';
								$group[0]->permissions->upload				= $_REQUEST['upload'] ? 'true' : 'false';
								$group[0]->permissions->payment				= $_REQUEST['payment'] ? 'true' : 'false';
								$group[0]->permissions->database			= $_REQUEST['database'] ? 'true' : 'false';
								$group[0]->permissions->maintenance			= $_REQUEST['maintenance'] ? 'true' : 'false';
								$group[0]->permissions->globalmaintenance	= $_REQUEST['globalmaintenance'] ? 'true' : 'false';
								$group[0]->permissions->offlinelogin		= $_REQUEST['offlinelogin'] ? 'true' : 'false';

								Core::getObject('session')->saveXML(Core::getObject('session')->groups->asXML(), Core::getSetting('xmlpath').'groups.xml');
								Core::getObject('messages')->add(pt_editsuccess);
							} else {
								trigger_error(pt_saveerr);
							}
						} else {
							$group = Core::getObject('session')->groups->xpath("//groups/group[name='{$_REQUEST['name']}']");
							if($group[0]) {
								trigger_error(pt_existserr);
							} else {
								$id = $this->createUniqueId('A');
								$node = Core::getObject('session')->groups->addChild('group');
								$node->addChild('id', $id);
								$node->addChild('name', $_REQUEST['name']);
								$perm = $node->addChild('permissions');
								$perm->addChild('viewplayers', $_REQUEST['viewplayers'] ? 'true' : 'false');
								$perm->addChild('editplayers', $_REQUEST['editplayers'] ? 'true' : 'false');
								$perm->addChild('viewmaps', $_REQUEST['viewmaps'] ? 'true' : 'false');
								$perm->addChild('editmaps', $_REQUEST['editmaps'] ? 'true' : 'false');
								$perm->addChild('viewchat', $_REQUEST['viewchat'] ? 'true' : 'false');
								$perm->addChild('sendchat', $_REQUEST['sendchat'] ? 'true' : 'false');
								$perm->addChild('viewserversettings', $_REQUEST['viewserversettings'] ? 'true' : 'false');
								$perm->addChild('editserversettings', $_REQUEST['editserversettings'] ? 'true' : 'false');
								$perm->addChild('viewgamesettings', $_REQUEST['viewgamesettings'] ? 'true' : 'false');
								$perm->addChild('editgamesettings', $_REQUEST['editgamesettings'] ? 'true' : 'false');
								$perm->addChild('viewlists', $_REQUEST['viewlists'] ? 'true' : 'false');
								$perm->addChild('editlists', $_REQUEST['editlists'] ? 'true' : 'false');
								$perm->addChild('browse', $_REQUEST['browse'] ? 'true' : 'false');
								$perm->addChild('upload', $_REQUEST['upload'] ? 'true' : 'false');
								$perm->addChild('payment', $_REQUEST['payment'] ? 'true' : 'false');
								$perm->addChild('database', $_REQUEST['database'] ? 'true' : 'false');
								$perm->addChild('maintenance', $_REQUEST['maintenance'] ? 'true' : 'false');
								$perm->addChild('globalmaintenance', $_REQUEST['globalmaintenance'] ? 'true' : 'false');
								$perm->addChild('offlinelogin', $_REQUEST['offlinelogin'] ? 'true' : 'false');

								Core::getObject('session')->saveXML(Core::getObject('session')->groups->asXML(), Core::getSetting('xmlpath').'groups.xml');
								Core::getObject('messages')->add(pt_addsuccess);
								$_REQUEST['id']	= $id;
								$this->edit		= true;
							}
						}
					}
				} elseif($this->edit) {
					$id = array_key_exists('id', $_REQUEST) ? $_REQUEST['id'] : trigger_error('Invalid group');
					if($id) {
						$group = Core::getObject('session')->groups->xpath("//groups/group[id='{$id}']");
						if($group[0]) {
							$_REQUEST['id'] 				= (string) $group[0]->id;
							$_REQUEST['name'] 				= (string) $group[0]->name;
							$_REQUEST['viewplayers']		= stristr((string) $group[0]->permissions->viewplayers, 'true') ? true : false;
							$_REQUEST['editplayers']		= stristr((string) $group[0]->permissions->editplayers, 'true') ? true : false;
							$_REQUEST['viewmaps']			= stristr((string) $group[0]->permissions->viewmaps, 'true') ? true : false;
							$_REQUEST['editmaps']			= stristr((string) $group[0]->permissions->editmaps, 'true') ? true : false;
							$_REQUEST['viewchat']			= stristr((string) $group[0]->permissions->viewchat, 'true') ? true : false;
							$_REQUEST['sendchat']			= stristr((string) $group[0]->permissions->sendchat, 'true') ? true : false;
							$_REQUEST['viewserversettings']	= stristr((string) $group[0]->permissions->viewserversettings, 'true') ? true : false;
							$_REQUEST['editserversettings']	= stristr((string) $group[0]->permissions->editserversettings, 'true') ? true : false;
							$_REQUEST['viewgamesettings']	= stristr((string) $group[0]->permissions->viewgamesettings, 'true') ? true : false;
							$_REQUEST['editgamesettings']	= stristr((string) $group[0]->permissions->editgamesettings, 'true') ? true : false;
							$_REQUEST['viewlists']			= stristr((string) $group[0]->permissions->viewlists, 'true') ? true : false;
							$_REQUEST['editlists']			= stristr((string) $group[0]->permissions->editlists, 'true') ? true : false;
							$_REQUEST['browse']				= stristr((string) $group[0]->permissions->browse, 'true') ? true : false;
							$_REQUEST['upload']				= stristr((string) $group[0]->permissions->upload, 'true') ? true : false;
							$_REQUEST['payment']			= stristr((string) $group[0]->permissions->payment, 'true') ? true : false;
							$_REQUEST['database']			= stristr((string) $group[0]->permissions->database, 'true') ? true : false;
							$_REQUEST['maintenance']		= stristr((string) $group[0]->permissions->maintenance, 'true') ? true : false;
							$_REQUEST['globalmaintenance']	= stristr((string) $group[0]->permissions->globalmaintenance, 'true') ? true : false;
							$_REQUEST['offlinelogin']		= stristr((string) $group[0]->permissions->offlinelogin, 'true') ? true : false;
						} else {
		 					trigger_error(pt_editerr);
						}
					}
				}
	
				echo "<form action='ajax.php' method='post' id='creategroup' name='creategroup' rel='{$this->display}area' class='postcmd'>";
				echo "<fieldset>";
				echo "<div class='legend'>".pt_groupedit."</div>";
				echo "	<div class='f-row'>
						<label for='username'>".pt_groupname."</label>
						<div class='f-field'><input type='text' name='name' value='{$_REQUEST['name']}' />"; if($form['name']) { echo " <div class='iform'>Invalid form</div>"; } echo "</div>
					</div>";
				echo "</fieldset>";
				echo "<fieldset>";
				echo "<div class='legend'>".pt_groupperm."</div>";
				echo "	<div class='f-row'>
						<label for='viewplayers'>viewplayers</label>
						<div class='f-field'><input type='checkbox' class='checkbox' name='viewplayers'"; if($_REQUEST['viewplayers']) { echo " checked='checked'"; } echo " /></div>
					</div>";
				echo "	<div class='f-row'>
						<label for='editplayers'>editplayers</label>
						<div class='f-field'><input type='checkbox' class='checkbox' name='editplayers'"; if($_REQUEST['editplayers']) { echo " checked='checked'"; } echo " /></div>
					</div>";
				echo "	<div class='f-row'>
						<label for='viewmaps'>viewmaps</label>
						<div class='f-field'><input type='checkbox' class='checkbox' name='viewmaps'"; if($_REQUEST['viewmaps']) { echo " checked='checked'"; } echo " /></div>
					</div>";
				echo "	<div class='f-row'>
						<label for='editmaps'>editmaps</label>
						<div class='f-field'><input type='checkbox' class='checkbox' name='editmaps'"; if($_REQUEST['editmaps']) { echo " checked='checked'"; } echo " /></div>
					</div>";
				echo "	<div class='f-row'>
						<label for='viewchat'>viewchat</label>
						<div class='f-field'><input type='checkbox' class='checkbox' name='viewchat'"; if($_REQUEST['viewchat']) { echo " checked='checked'"; } echo " /></div>
					</div>";
				echo "	<div class='f-row'>
						<label for='sendchat'>sendchat</label>
						<div class='f-field'><input type='checkbox' class='checkbox' name='sendchat'"; if($_REQUEST['sendchat']) { echo " checked='checked'"; } echo " /></div>
					</div>";
				echo "	<div class='f-row'>
						<label for='viewserversettings'>viewserverset.</label>
						<div class='f-field'><input type='checkbox' class='checkbox' name='viewserversettings'"; if($_REQUEST['viewserversettings']) { echo " checked='checked'"; } echo " /></div>
					</div>";
				echo "	<div class='f-row'>
						<label for='editserversettings'>editserverset.</label>
						<div class='f-field'><input type='checkbox' class='checkbox' name='editserversettings'"; if($_REQUEST['editserversettings']) { echo " checked='checked'"; } echo " /></div>
					</div>";
				echo "	<div class='f-row'>
						<label for='viewgamesettings'>viewgameset.</label>
						<div class='f-field'><input type='checkbox' class='checkbox' name='viewgamesettings'"; if($_REQUEST['viewgamesettings']) { echo " checked='checked'"; } echo " /></div>
					</div>";
				echo "	<div class='f-row'>
						<label for='editgamesettings'>editgameset.</label>
						<div class='f-field'><input type='checkbox' class='checkbox' name='editgamesettings'"; if($_REQUEST['editgamesettings']) { echo " checked='checked'"; } echo " /></div>
					</div>";
				echo "	<div class='f-row'>
						<label for='viewlists'>viewlists</label>
						<div class='f-field'><input type='checkbox' class='checkbox' name='viewlists'"; if($_REQUEST['viewlists']) { echo " checked='checked'"; } echo " /></div>
					</div>";
				echo "	<div class='f-row'>
						<label for='editlists'>editlists</label>
						<div class='f-field'><input type='checkbox' class='checkbox' name='editlists'"; if($_REQUEST['editlists']) { echo " checked='checked'"; } echo " /></div>
					</div>";
				echo "	<div class='f-row'>
						<label for='browse'>browse</label>
						<div class='f-field'><input type='checkbox' class='checkbox' name='browse'"; if($_REQUEST['browse']) { echo " checked='checked'"; } echo " /></div>
					</div>";
				echo "	<div class='f-row'>
						<label for='upload'>upload</label>
						<div class='f-field'><input type='checkbox' class='checkbox' name='upload'"; if($_REQUEST['upload']) { echo " checked='checked'"; } echo " /></div>
					</div>";
				echo "	<div class='f-row'>
						<label for='payment'>payment</label>
						<div class='f-field'><input type='checkbox' class='checkbox' name='payment'"; if($_REQUEST['payment']) { echo " checked='checked'"; } echo " /></div>
					</div>";
				echo "	<div class='f-row'>
						<label for='database'>database</label>
						<div class='f-field'><input type='checkbox' class='checkbox' name='database'"; if($_REQUEST['database']) { echo " checked='checked'"; } echo " /></div>
					</div>";
				echo "	<div class='f-row'>
						<label for='maintenance'>maintenance</label>
						<div class='f-field'><input type='checkbox' class='checkbox' name='maintenance'"; if($_REQUEST['maintenance']) { echo " checked='checked'"; } echo " /></div>
					</div>";
				if(Core::getObject('session')->checkPerm('globalmaintenance')) {
				echo "	<div class='f-row'>
						<label for='globalmaintenance'>globalmaintenance</label>
						<div class='f-field'><input type='checkbox' class='checkbox' name='globalmaintenance'"; if($_REQUEST['globalmaintenance']) { echo " checked='checked'"; } echo " /></div>
					</div>";
				} else {
					echo "<input type='hidden' name='globalmaintenance'"; if($_REQUEST['globalmaintenance']) { echo " value='on'"; } echo " />";
				}
				echo "	<div class='f-row'>
						<label for='offlinelogin'>offlinelogin</label>
						<div class='f-field'><input type='checkbox' class='checkbox' name='offlinelogin'"; if($_REQUEST['offlinelogin']) { echo " checked='checked'"; } echo " /></div>
					</div>";
				echo "</fieldset>";
				echo "<input type='hidden' name='op' value='{$this->op}' />";
				echo "<input type='hidden' name='edit' value='{$this->edit}' />";
				echo "<input type='hidden' name='id' value='{$_REQUEST['id']}' />";
				echo "<input type='hidden' name='plugin' value='{$this->id}' />";
				echo "<button type='submit' title='".ct_submit."' class='wide'>".ct_submit."</button>";
				echo "</form>";
			break;

			case 'deladmin':
				$id = array_key_exists('id', $_REQUEST) ? $_REQUEST['id'] : trigger_error('Invalid admin');
				if($id) {
					$sxml = dom_import_simplexml(Core::getObject('session')->admins);
					$dom  = new DOMDocument('1.0');
					$sxml = $dom->importNode($sxml, true);
					$sxml = $dom->appendChild($sxml);

					$xpath  = new DOMXPath($dom); 
					$result = $xpath->query('//admin[id=\''. $id .'\']'); 
					if($result !== false && $result->length > 0) {
						$element = $result->item(0); 
						$element->parentNode->removeChild($element); 
					} 

					$sxml = simplexml_import_dom($dom);
					$sxml->asXML(Core::getSetting('xmlpath').'admins.xml');
					Core::getObject('messages')->add(pt_delsuccess);
				}
			break;

			case 'delserver':
				$id = array_key_exists('id', $_REQUEST) ? $_REQUEST['id'] : trigger_error('Invalid server');
				if($id) {
					$sxml = dom_import_simplexml(Core::getObject('session')->servers);
					$dom  = new DOMDocument('1.0');
					$sxml = $dom->importNode($sxml, true);
					$sxml = $dom->appendChild($sxml);

					$xpath  = new DOMXPath($dom); 
					$result = $xpath->query('//server[id=\''. $id .'\']'); 
					if($result !== false && $result->length > 0) {
						$element = $result->item(0); 
						$element->parentNode->removeChild($element); 
					} 

					$sxml = simplexml_import_dom($dom);
					$sxml->asXML(Core::getSetting('xmlpath').'servers.xml');
					Core::getObject('messages')->add(pt_delsuccess);
				}
			break;

			case 'delgroup':
				$id = array_key_exists('id', $_REQUEST) ? $_REQUEST['id'] : trigger_error('Invalid group');
				if($id) {
					$sxml = dom_import_simplexml(Core::getObject('session')->groups);
					$dom  = new DOMDocument('1.0');
					$sxml = $dom->importNode($sxml, true);
					$sxml = $dom->appendChild($sxml);

					$xpath  = new DOMXPath($dom); 
					$result = $xpath->query('//group[id=\''. $id .'\']'); 
					if($result !== false && $result->length > 0) {
						$element = $result->item(0); 
						$element->parentNode->removeChild($element); 
					} 

					$sxml = simplexml_import_dom($dom);
					$sxml->asXML(Core::getSetting('xmlpath').'groups.xml');
					Core::getObject('messages')->add(pt_delsuccess);
				}
			break;
		}
	}

	private function createUniqueId($prefix)
	{
		$id  = $prefix;
		$id .= chr(rand(65,90));
		$id .= time();
		$id .= uniqid($prefix);
		return $id;
	}

	public function StopServer()
	{
		Core::getObject('actions')->add('StopServer');
	}

	public function QuitGame()
	{
		if($_REQUEST['quitmode']) {
			Core::getObject('actions')->add('SaveGuestList', (string) Core::getObject('session')->server->lists->guestlist);
			Core::getObject('actions')->add('SaveBlackList', (string) Core::getObject('session')->server->lists->blacklist);
		}
		Core::getObject('actions')->add('QuitGame');
	}

	public function StartServerInternet()
	{
		Core::getObject('actions')->add('StartServerInternet', array(
			'Login' 	=> $_REQUEST['Login'],
			'Password' 	=> $_REQUEST['psw']
		));
	}

	public function StartServerLan()
	{
		Core::getObject('actions')->add('StartServerLan', array(
			'Login' 	=> $_REQUEST['Login'],
			'Password' 	=> $_REQUEST['psw']
		));
	}
}
?>
