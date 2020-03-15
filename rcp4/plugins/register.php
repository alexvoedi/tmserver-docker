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
if(!Core::getSetting('register')) {
	trigger_error('You can not register, registration is disabled');
	return;
}

function createUniqueId($prefix)
{
	$id  = $prefix;
	$id .= chr(rand(65,90));
	$id .= time();
	$id .= uniqid($prefix);
	return $id;
}

$_REQUEST['username']	= empty($_REQUEST['username'])	? ''		: $_REQUEST['username'];
$_REQUEST['tmaccount']	= empty($_REQUEST['tmaccount'])	? ''		: $_REQUEST['tmaccount'];
$_REQUEST['language']	= empty($_REQUEST['language'])	? 'en'		: $_REQUEST['language'];
$_REQUEST['style']		= empty($_REQUEST['style'])		? 'default'	: $_REQUEST['style'];
$_REQUEST['nocode']		= empty($_REQUEST['nocode'])	? '0'		: $_REQUEST['nocode'];

if(!empty($_POST)) {
	$form = array();
	$form['username'] = empty($_REQUEST['username']) ? true : false;
	$form['password'] = empty($_REQUEST['password']) ? true : false;

	if(Core::getObject('form')->check($form)) {
		$admin = false;
		foreach(Core::getObject('session')->admins->children() as $node)
		{
			if ((string) $node->username == $_REQUEST['username']) {
				$admin = $node;
				break;
			}
		}

		if($admin) {
			trigger_error('Admin allready exists');
		} else {
			$id  = createUniqueId('A');
			$node = Core::getObject('session')->admins->addChild('admin');
			$node->addChild('id', $id);
			$node->addChild('active', 'false');
			$node->addChild('group', 'G1');
			$node->addChild('username', $_REQUEST['username']);
			$node->addChild('password', md5($_REQUEST['password']));
			$node->addChild('tmaccount', $_REQUEST['tmaccount']);
			$node->addChild('language', $_REQUEST['language']);
			$node->addChild('style', $_REQUEST['style']);
			$node->addChild('nocode',  $_REQUEST['nocode']  ? 'true' : 'false');
			$servers = $node->addChild('servers');
			$servers->addChild('id', '1');
			Core::getObject('session')->admins->asXML(Core::getSetting('xmlpath').'admins.xml');
			Core::getObject('messages')->add('Account successfull registered, this account needs activation, you can\'t use it now!');
			$hideform = true;
		}
	}
}

?>
<div style='margin-top:20px; width:100%; height:100px; background:url(<?php echo Core::getSetting('style'); ?>/logo.jpg) left center no-repeat;'></div>
{dumpmsgs}
<div class='boxtt'>
	<div class='boxbb'>
		<div class='boxll'>
			<div class='boxrr'>
				<div class='boxtr'>
					<div class='boxtl'>
						<div class='boxbr'>
							<div class='boxbl'>
								<div class='boxh'>ACCESS</div>
								<div class='boxc'>
<?php

if(!$hideform) {
	echo "<form action='index.php' method='post'>";
	echo "<fieldset>";
	echo "<div class='legend'>Register new account</div>";
	echo "	<div class='f-row'>
			<label for='username'>Username</label>
			<div class='f-field'><input type='text' name='username' value='{$_REQUEST['username']}' />"; if($form['username']) { echo " <div class='iform'>Enter a username, please</div>"; } echo "</div>
		</div>";
	echo "	<div class='f-row'>
			<label for='password'>Password</label>
			<div class='f-field'><input type='password' name='password' value='' />"; if($form['password']) { echo " <div class='iform'>Enter a password, please</div>"; } echo "</div>
		</div>";
	echo "	<div class='f-row'>
		<label for='tmaccount'>TM-Account</label>
		<div class='f-field'><input type='text' name='tmaccount' value='{$_REQUEST['tmaccount']}' /><div>Enter TM-Account for Authentication from inside remoteCP[Live]</div></div>
	</div>";
	echo "	<div class='f-row'>
			<label for='language'>Language</label>
			<div class='f-field'><select name='language'>";
	$path = './xml/language/';
	$i    = 0;
	if(is_dir($path)) {
		$dir = opendir($path);
		while($element = readdir($dir))
		{
			if(filetype($path.$element) == 'dir' && $element != '.' && $element != '..') {
				++$i;
				echo "<option value='{$element}'"; if($_REQUEST['language'] == $element) { echo " selected='selected'"; } echo ">{$element}</option>";
			}
		}
		closedir($dir);
	}
	if(!$i)
		echo "<option value='en'>en</option>";
	echo "		</select></div>
		</div>";
	echo "	<div class='f-row'>
			<label for='style'>Style</label>
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
			<label for='nocode'>remove TMCode</label>
			<div class='f-field'><input type='checkbox' class='checkbox' name='nocode'"; if($_REQUEST['nocode']) { echo " checked='checked'"; } echo " /></div>
		</div>";
	echo "</fieldset>";
	echo "<fieldset>";
	echo "<input type='hidden' value='register' name='page' />";
	echo "<button type='submit' title='".ct_submit."' class='wide'>".ct_submit."</button>";
	echo "</form>";
}
?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>