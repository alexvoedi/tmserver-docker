<?php
/**
* remoteCP 4
* ütf-8 release
*
* @package remoteCP
* @author hal.sascha
* @copyright (c) 2006-2009
* @version 4.0.2.6
*/
class Mods extends rcp_plugin
{
	public  $display		= 'side';
	public  $title			= 'Mods';
	public  $author			= 'hal.ko.sascha';
	public  $version		= '4.0.3.6';
	public  $nservstatus	= array(2,3,4,5);
	public  $vpermissions	= array('editserversettings');
	public  $apermissions	= array(
		'setMods'	=> 'editserversettings',
		'setMusic'	=> 'editserversettings'
	);

	private $mods			= array();
	private $music			= array();

	public function onLoadSettings($settings)
	{
		// Set defaults
		$this->mods = array();
		$this->music = array();

		// Read mods settings
		if(!$settings->mods) return;
		foreach($settings->mods->children() AS $env)
		{
			if(!$env) continue;
			$tmp = (string) $env->getName();
			foreach($env->children() AS $item)
			{
				$this->mods[$tmp][] = array(
					'url'	=> (string) $item,
					'name'	=> (string) $item['name']
				);
			}
		}

		// Read music settings
		if(!$settings->music) return;
		foreach($settings->music->children() AS $song)
		{
			$this->music[] = array(
				'url'	=> (string) $song,
				'name'	=> (string) $song['name']
			);
		}
	}

	public function onOutput() {
		if(Core::getObject('gbx')->query('GetForcedMods')) {
			$ForcedMods = Core::getObject('gbx')->getResponse();

			if(!empty($ForcedMods)) {
				echo "<fieldset>";
				echo "<div class='legend'>".pt_forcedmods."</div>";
				foreach($ForcedMods['Mods'] as $mod)
				{
					echo "<div class='f-row'>
							<label>{$mod['Env']}</label>
							<div class='f-field'>". $this->getModName($mod['Env'], $mod['Url']) ."</div>";
					echo "</div>";
				}
				echo "</fieldset>";
			}
		}

		echo "<form action='ajax.php' method='post' id='forcemods' name='forcemods' class='postcmd' rel='{$this->display}area'>";
		echo "<fieldset>";
		echo "<div class='legend'>".pt_forcemods."</div>";
		echo "	<div class='f-row'>
					<label for='points'>".pt_stadium."</label>
					<div class='f-field'>
						<select name='modstadium'>";
						echo "<option value='0'>".pt_none."</option>";
						foreach($this->mods['Stadium'] AS $mod)
						{
							echo "<option value='{$mod['url']}'>{$mod['name']}</option>";
						}
		echo "			</select>
					</div>
				</div>";
		echo "	<div class='f-row'>
					<label for='points'>".pt_island."</label>
					<div class='f-field'>
						<select name='modisland'>";
						echo "<option value='0'>".pt_none."</option>";
						foreach($this->mods['Island'] AS $mod)
						{
							echo "<option value='{$mod['url']}'>{$mod['name']}</option>";
						}
		echo "			</select>
					</div>
				</div>";
		echo "<div class='f-row'>
					<label for='points'>".pt_bay."</label>
					<div class='f-field'>
						<select name='modbay'>";
						echo "<option value='0'>".pt_none."</option>";
						foreach($this->mods['Bay'] AS $mod)
						{
							echo "<option value='{$mod['url']}'>{$mod['name']}</option>";
						}
		echo "			</select>
					</div>
				</div>";
		echo "	<div class='f-row'>
					<label for='points'>".pt_coast."</label>
					<div class='f-field'>
						<select name='modcoast'>";
						echo "<option value='0'>".pt_none."</option>";
						foreach($this->mods['Coast'] AS $mod)
						{
							echo "<option value='{$mod['url']}'>{$mod['name']}</option>";
						}
		echo "			</select>
					</div>
				</div>";
		echo "	<div class='f-row'>
					<label for='points'>".pt_speed."</label>
					<div class='f-field'>
						<select name='modspeed'>";
						echo "<option value='0'>".pt_none."</option>";
						foreach($this->mods['Speed'] AS $mod)
						{
							echo "<option value='{$mod['url']}'>{$mod['name']}</option>";
						}
		echo "			</select>
					</div>
				</div>";
		echo "	<div class='f-row'>
					<label for='points'>".pt_alpine."</label>
					<div class='f-field'>
						<select name='modalpine'>";
						echo "<option value='0'>".pt_none."</option>";
						foreach($this->mods['Alpine'] AS $mod)
						{
							echo "<option value='{$mod['url']}'>{$mod['name']}</option>";
						}
		echo "			</select>
					</div>
				</div>";
		echo "	<div class='f-row'>
					<label for='points'>".pt_rally."</label>
					<div class='f-field'>
						<select name='modrally'>";
						echo "<option value='0'>".pt_none."</option>";
						foreach($this->mods['Rally'] AS $mod)
						{
							echo "<option value='{$mod['url']}'>{$mod['name']}</option>";
						}
		echo "			</select>
					</div>
				</div>";
		echo "	<div class='f-row'>
					<label for='DisableAllMods'>".pt_disableall."</label>
					<div class='f-field'><input type='checkbox' class='checkbox' name='DisableAllMods' /></div>
				</div>";
		echo "</fieldset>";
		echo "<input type='hidden' name='plugin' value='{$this->id}' />";
		echo "<input type='hidden' name='action' value='setMods' />";
		echo "<button type='submit' title='".ct_submit."' class='wide'>".ct_submit."</button>";
		echo "</form>";

		if(Core::getObject('gbx')->query('GetForcedMusic')) {
			$ForcedMusic = Core::getObject('gbx')->getResponse();

			if(empty($ForcedMusic)) {
				echo "<fieldset>";
				echo "<div class='legend'>".pt_forcedmusic."</div>";
				echo "<div class='f-row'>
						<label>{$ForcedMusic['File']}</label>
						<div class='f-field'>{$ForcedMusic['Url']}</div>";
				echo "</div>";
				echo "</fieldset>";
			}
		}

		echo "<form action='ajax.php' method='post' id='forcemusic' name='forcemusic' class='postcmd' rel='{$this->display}area'>";
		echo "<fieldset>";
		echo "<div class='legend'>".pt_forcemusic."</div>";
		echo "<div class='f-row'>
				<label for='song'>".pt_song."</label>
				<div class='f-field'>
					<select name='song'>";
						foreach($this->music AS $song)
						{
							echo "<option value='{$song['url']}'>{$song['name']}</option>";
						}
		echo "		</select>
				</div>";
		echo "	<div class='f-row'>
					<label for='DisableAllMusic'>".pt_disableall."</label>
					<div class='f-field'><input type='checkbox' class='checkbox' name='DisableAllMusic' /></div>
				</div>";
		echo "</fieldset>";
		echo "<input type='hidden' name='plugin' value='{$this->id}' />";
		echo "<input type='hidden' name='action' value='setMusic' />";
		echo "<button type='submit' title='".ct_submit."' class='wide'>".ct_submit."</button>";
		echo "</form>";
	}

	public function setMods()
	{
		$array = array();
		$override = true;
		if(!array_key_exists('DisableAllMods', $_REQUEST)) {
			if($_REQUEST['modstadium'])
				$array[] = array('Env' => 'Stadium', 'Url' => $_REQUEST['modstadium']);
			if($_REQUEST['modisland'])
				$array[] = array('Env' => 'Island'	, 'Url' => $_REQUEST['modisland']);
			if($_REQUEST['modbay'])
				$array[] = array('Env' => 'Bay'	, 'Url' => $_REQUEST['modbay']);
			if($_REQUEST['modcoast'])
				$array[] = array('Env' => 'Coast'	, 'Url' => $_REQUEST['modcoast']);
			if($_REQUEST['modspeed'])
				$array[] = array('Env' => 'Speed'	, 'Url' => $_REQUEST['modspeed']);
			if($_REQUEST['modalpine'])
				$array[] = array('Env' => 'Alpine'	, 'Url' => $_REQUEST['modalpine']);
			if($_REQUEST['modrally'])
				$array[] = array('Env' => 'Rally'	, 'Url' => $_REQUEST['modrally']);
		} else {
			$override = false;
		}
		Core::getObject('actions')->add('SetForcedMods', $override, $array);
	}

	public function setMusic()
	{
		$url = '';
		$override = true;
		if(!array_key_exists('DisableAllMusic', $_REQUEST)) {
			$url = $_REQUEST['song'];
		} else {
			$override = false;
		}
		Core::getObject('actions')->add('SetForcedMusic', $override, $url);
	}

	private function getModName($env, $url)
	{
		foreach($this->mods[$env] AS $value)
		{
			if($url == $value['url']) {
				return $value['name'];
			}
		}
	}
}
?>