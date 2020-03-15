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
class Shell extends rcp_plugin
{
	public  $display		= 'side';
	public  $title			= 'Shell';
	public  $author			= 'hal.ko.sascha';
	public  $version		= '4.0.3.6';
	public  $nservcon		= false;
	private $vpermission	= array('globalmaintenance');
   
	public function onOutput()
	{
		//Settings
		$serverlogin = Core::getObject('session')->server->login;
		$serverid = Core::getObject('session')->server->id;

		//Check params
		$typeB = false;
		switch($_REQUEST['typeB'])
		{
			case 'start':
				$typeB = 'start';
			break;

			case 'stop':
				$typeB = 'stop';
			break;

			case 'restart':
				$typeB = 'restart';
			break;

			case 'status':
				$typeB = 'status';
			break;
		}

		//Call cmd
		if($typeB && Core::getObject('session')->checkPerm('globalmaintenance')) {
			$cmd = './plugins/Shell/shell.script.tpl controller '.$typeB.' '.$serverlogin.' '.$serverid;
			$return = shell_exec($cmd);
		}

		//Get current server status in boolean/int mode
		$cmd = './plugins/Shell/shell.script.tpl controller status2 '.$serverlogin.' '.$serverid;
		$status = shell_exec($cmd);
		$status = (int) $status ? true : false;
		$statuslabel = $status ? '<b>online</b>' : '<i>offline</i>';

		echo "<form action='ajax.php' method='post' id='shell' name='shell' class='postcmd' rel='{$this->display}area'>";
		echo "	<input type='hidden' name='plugin' value='{$this->id}' />";

		echo "	<fieldset>";
		echo "	<div class='legend'>Status</div>";
		echo "	<div class='f-row'>
					<label>Controller status</label>
					<div class='f-field'>{$statuslabel}</div>
				</div>";
		echo "	</fieldset>";

		echo "	<fieldset>";
		echo "	<div class='legend'>Options</div>";
		echo "	<div class='f-row'>
					<label>Status</label>
					<div class='f-field'><input type='radio' name='typeB' value='status' /></div>
				</div>";
		if($status) {
			echo "	<div class='f-row'>
						<label>Stop</label>
						<div class='f-field'><input type='radio' name='typeB' value='stop' /></div>
					</div>";
			echo "	<div class='f-row'>
						<label>Restart</label>
						<div class='f-field'><input type='radio' name='typeB' value='restart' /></div>
					</div>";
		} else {
			echo "	<div class='f-row'>
						<label>Start</label>
						<div class='f-field'><input type='radio' name='typeB' value='start' /></div>
					</div>";
		}
		echo "	</fieldset>";
		echo "	<button type='submit' title='".ct_submit."' class='wide'>".ct_submit."</button>";
		echo "</form>";
		echo "<div id='chatcontent' style='overflow:auto;'>";
		echo "	<pre>$return</pre>";
		echo "</div>";
	}
}
?>