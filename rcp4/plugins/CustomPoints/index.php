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
class CustomPoints extends rcp_plugin
{
	public  $display		= 'side';
	public  $title			= 'Points';
	public  $author			= 'hal.ko.sascha';
	public  $version		= '4.0.3.6';
	public  $nservstatus	= array(2,3,4,5);
	public  $vpermissions	= array('editgamesettings');
	public  $apermissions	= array(
		'setPoints'			=> 'editgamesettings',
		'setPointsPreset'	=> 'editgamesettings'
	);
	public  $presets;

	public function onLoad()
	{
		$this->presets = Core::getObject('session')->loadXML(Core::getSetting('pluginpath').$this->id.'/presets.xml');
	}

	public function onOutput()
	{
		if(Core::getObject('gbx')->query('GetRoundCustomPoints'))
			$CustomPoints = Core::getObject('gbx')->getResponse();

		if(!Core::getObject('session')->checkPerm('editgamesettings'))
			return;

		echo "<form action='ajax.php' method='post' id='custompoints' name='custompoints' class='postcmd' rel='{$this->display}area'>";
		echo "<fieldset>";
		echo "<div class='legend'>".pt_custom."</div>";
		echo "<div class='f-row'>
				<label for='points'>".pt_points."</label>
				<div class='f-field'>
					<input type='text' name='points' id='points' value='". implode(',', $CustomPoints) ."' />
					<div class='small'>".pt_commasep."</div>
				</div>";
		echo "</div>";
		echo "</fieldset>";
		echo "<input type='hidden' name='plugin' value='{$this->id}' />";
		echo "<input type='hidden' name='action' value='setPoints' />";
		echo "<button type='submit' title='".ct_submit."' class='wide'>".ct_submit."</button>";
		echo "</form>";

		echo "<form action='ajax.php' method='post' id='custompointspreset' name='custompointspreset' class='postcmd' rel='{$this->display}area'>";
		echo "<fieldset>";
		echo "<div class='legend'>".pt_presets."</div>";
		foreach($this->presets->children() as $preset)
		{
			echo "<div class='f-row'>
					<label>{$preset['name']}</label>
					<div class='f-field'>";
			echo "		<input style='width:25px;' type='radio' name='preset' value='{$preset['points']}' /> "; if(strlen($preset['points']) > 25) { echo substr($preset['points'], 0, 25)."..."; } else { echo $preset['points']; }
			echo "	</div>";
			echo "</div>";
		}
		echo "</fieldset>";
		echo "<input type='hidden' name='plugin' value='{$this->id}' />";
		echo "<input type='hidden' name='action' value='setPointsPreset' />";
		echo "<button type='submit' title='".ct_submit."' class='wide'>".ct_submit."</button>";
		echo "</form>";
	}

	public function setPoints()
	{
		if(!array_key_exists('points', $_REQUEST)) return;
		$str = preg_replace("/[^0-9,]/","",$_REQUEST['points']);
		$array = $this->makeIntArray(explode(',', $str));
		Core::getObject('actions')->add('SetRoundCustomPoints', $array, true);
	}

	public function setPointsPreset()
	{
		if(!array_key_exists('preset', $_REQUEST)) return;
		$str = preg_replace("/[^0-9,]/","",$_REQUEST['preset']);
		$array = $this->makeIntArray(explode(',', $str));
		Core::getObject('actions')->add('SetRoundCustomPoints', $array, true);
	}

	private function makeIntArray($array)
	{
		foreach($array AS $key => $value)
		{
			$array[$key] = (int) $value;
		}
		return $array;
	}
}
?>