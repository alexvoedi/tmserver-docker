<?php
/**
* remoteCP 4
* ütf-8 release
*
* @package remoteCP
* @author lukefwk / hal.sascha
* @copyright (c) 2006-2009
* @version 4.0.3.6
*/
class Settings extends rcp_plugin
{
	public  $display		= 'quick';
	public  $title			= 'Settings';
	public  $author			= 'hal.ko.sascha';
	public  $version		= '4.0.3.6';
	public  $nservcon		= false;

	public function onLoad()
	{
		$this->op= array_key_exists('op', $_REQUEST) ? $_REQUEST['op'] : false;
		$this->addQuickOption('ajax.php?plugin=Settings', '400;0', 'modal', 'settings', ct_settings);
	}

	public function onOutput()
	{
		if(Core::getObject('session')->admin->username == 'Guest') {
			trigger_error(pt_err3);
			return;
		}

		$_REQUEST['username']	= empty($_REQUEST['username'])	? Core::getObject('session')->admin->username	: $_REQUEST['username'];
		$_REQUEST['tmaccount']	= empty($_REQUEST['tmaccount'])	? Core::getObject('session')->admin->tmaccount	: $_REQUEST['tmaccount'];
		$_REQUEST['language']	= empty($_REQUEST['language'])	? Core::getSetting('language')	: $_REQUEST['language'];
		$_REQUEST['style']		= empty($_REQUEST['style'])		? Core::getSetting('style')		: $_REQUEST['style'];

		if(!empty($_POST)) {
			$form = array();
			$form['username'] = empty($_REQUEST['username']) ? true : false;
			if(Core::getObject('form')->check($form)) {
				$admin = Core::getObject('session')->admins->xpath("//admins/admin[./id='".Core::getObject('session')->admin->id."']/servers/server[attribute::id='".Core::getObject('session')->server->id."']/../..");
				if($admin[0]) {
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
					//Core::getObject('session')->admins->asXML(Core::getSetting('xmlpath').'admins.xml');
					Core::getObject('session')->saveXML(Core::getObject('session')->admins->asXML(), Core::getSetting('xmlpath').'admins.xml');
					Core::getObject('messages')->add(pt_success);

					//Update session
					Core::getObject('session')->updateAdmin($admin[0]->username, $password, Core::getObject('session')->server->id);

					//Update global settings
					Core::storeSetting('language', $admin[0]->language); 
					Core::storeSetting('style', $admin[0]->style); 
				} else {
					trigger_error(pt_err1);
				}
			} 
		} else {
			$_REQUEST['username'] 	= Core::getObject('session')->admin->username; 
			$_REQUEST['tmaccount'] 	= Core::getObject('session')->admin->tmaccount; 
			$_REQUEST['language'] 	= Core::getSetting('language'); 
			$_REQUEST['style'] 		= Core::getSetting('style'); 
			$_REQUEST['nocode']		= Core::getObject('session')->admin->nocode;
		}

		echo "<form action='ajax.php' method='post' id='settings2' name='settings2' rel='{$this->display}area' class='postcmd'>";
		echo "<fieldset>";
		echo "<div class='legend'>".pt_settings."</div>";
		echo "	<div class='f-row'>
				<label for='username'>".pt_username."</label>
				<div class='f-field'><input type='text' name='username' value='{$_REQUEST['username']}' />"; if($form['username']) { echo " <div class='iform'>Invalid form</div>"; } echo "</div>
			</div>";
		echo "	<div class='f-row'>
				<label for='password'>".pt_password."</label>
				<div class='f-field'><input type='password' name='password' value='' /></div>
			</div>";
		echo "	<div class='f-row'>
				<label for='tmaccount'>".pt_tmacc."</label>
				<div class='f-field'><input type='text' name='tmaccount' value='{$_REQUEST['tmaccount']}' /></div>
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
				<div class='f-field'><select name='style' id='style'>";
		$path = './styles/';
		if(is_dir($path)) {
			$dir = opendir($path);
			while($element = readdir($dir))
			{
				if(filetype($path.$element) == 'dir' && $element != '.' && $element != '..')
					echo "<option value='{$element}'"; if('./'.$_REQUEST['style'] == $path.$element) { echo " selected='selected'"; } echo ">{$element}</option>";
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
		echo "<input type='hidden' name='plugin' value='{$this->id}' />";
		echo "<button type='submit' title='".ct_submit."' class='wide'>".ct_submit."</button>";
		echo "</fieldset>";
		echo "</form>\n";
	}

	private function createUniqueId($prefix)
	{
		$id  = $prefix;
		$id .= chr(rand(65,90));
		$id .= time();
		$id .= uniqid($prefix);
		return $id;
	}
}
?>