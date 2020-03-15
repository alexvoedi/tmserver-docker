<?php
/**
* remoteCP 4
* ütf-8 release
*
* @package remoteCP
* @author hal.sascha
* @copyright (c) 2006-2009
* @version 4.0.3.2
*/
class Chat extends rcp_plugin
{
	public  $display		= 'side';
	public  $title			= 'Chat';
	public  $author			= 'hal.ko.sascha';
	public  $version		= '4.0.3.2';
	public  $usejs			= true;
	public  $nservstatus	= array(3,4,5);
	public  $vpermissions	= array('viewchat');
	public  $apermissions	= array(
		'sendChat'			=> 'sendchat',
		'changeChatFilters'	=> 'globalmaintenance'
	);
	private $op;
	private $tologin;
	private $chattext;
	private $fmessages;
	private $fcommands;
    
	public function onLoad()
	{
		$this->op			= (array_key_exists('op', $_REQUEST))			? $_REQUEST['op']		: false;
		$this->tologin		= (array_key_exists('tologin', $_REQUEST))		? $_REQUEST['tologin']	: false;
		$this->chattext		= (array_key_exists('ChatText', $_REQUEST))		? $_REQUEST['ChatText']	: false;

		//Load Filter settings from session
		$this->fmessages	= (array_key_exists($this->id.'fmessages', $_SESSION))	? $_SESSION[$this->id.'fmessages']	: true;
		$this->fcommands	= (array_key_exists($this->id.'fcommands', $_SESSION))	? $_SESSION[$this->id.'fcommands']	: true;
	}

	public function onOutput()
	{
		if(Core::getObject('gbx')->query('GetChatLines', 40, 0)) {
			$ChatLines = array();
			$response  = Core::getObject('gbx')->getResponse();
			foreach($response AS $value)
			{
				//Check if message is a player-message
				$result = preg_match('!\[\$<(.*)\$>\] (.*)!is', $value, $results);
				if($result) {
					$nick = $results[1];
					$text = $results[2];

					//Filter at least the /su command anyway
					if(stristr($text, '/su')) continue;

					//Leave if message is a chatcommand
					if($this->fcommands) {
						if(substr($text, 0, 1) == '/') continue;
					}
				//Leave if message wasn't a player-message
				} elseif($this->fmessages) {
					continue;
				} else {
					$nick = false;
					$text = $value;
				}

				//Trim blanks
				$nick = trim($nick);
				$text = trim($text);

				//Save message to the filtered chatlines array
				$ChatLines[] = array(
					'nick' => Core::getObject('tparse')->toHTML($nick),
					'text' => Core::getObject('tparse')->toHTML($text)
				);
			}
		}

		if($this->op == 'load') {
			$this->OutputLines($ChatLines);
		} else {
			if(Core::getObject('session')->checkPerm('sendchat')) {
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

				echo "<form action='ajax.php' method='post' id='formchat' name='formChat' rel='chatcontent:ChatSubmitCallback' class='postcmd'>";
				echo "  <input type='hidden' name='plugin' value='{$this->id}' />";
				echo "  <input type='hidden' name='action' value='sendChat' />";
				echo "  <input type='hidden' name='op' value='load' />";
				echo "<fieldset>";
				echo "<div class='legend'>".pt_reply."</div>";
				echo "	<div class='f-row'>
						<label for='ChatText'>".pt_msg."</label>
						<div class='f-field'><input type='text' name='ChatText' id='ChatText' value='' /></div>
					</div>";
				echo "	<div class='f-row'>
						<label for='tologin'>".pt_tologin."</label>
						<div class='f-field'>
							<select name='tologin'>
								<option value='0'>".pt_all."</option>";
				foreach($PlayerList AS $value)
				{
					echo "			<option value='{$value['Login']}'>". Core::getObject('tparse')->toHTML($value['NickName']) ."</option>";
				}
				echo "			</select>
						</div>
					</div>";
				echo "	<button type='submit' title='".pt_send."' class='wide'><span id='chatupd'>".pt_send."</span></button>";
				echo "</fieldset>";
				echo "</form>";
			}

			echo "<div id='chatcontent' style='overflow:auto;'>";
			$this->OutputLines($ChatLines);
			echo "</div>";

			if(!Core::getSetting('windowed')) {
				echo "<div class='legend ic'><a href='index.php?windowed=1&plugin=Chat' rel='chatwindow:640:480' class='newwindow' target='_blank'>". pt_newwin ."</a></div>";
			}

			if(Core::getObject('session')->checkPerm($this->apermissions['changeChatFilters'])) {
				echo "<form action='ajax.php' method='post' id='formChatFilters' name='formChatFilters' rel='{$this->display}area' class='postcmd'>";
				echo "  <input type='hidden' name='plugin' value='{$this->id}' />";
				echo "  <input type='hidden' name='action' value='changeChatFilters' />";
				echo "<fieldset>";
				echo "<div class='legend'>".pt_foptions."</div>";
				echo "	<div class='f-row'>
							<label for='fmessages'>".pt_fmessages."</label>
							<div class='f-field'><input type='checkbox' class='checkbox' id='fmessages' name='fmessages'"; if($this->fmessages) { echo " checked='checked'"; } echo " /></div>
						</div>";
				echo "	<div class='f-row'>
							<label for='fcommands'>".pt_fcommands."</label>
							<div class='f-field'><input type='checkbox' class='checkbox' id='fcommands' name='fcommands'"; if($this->fcommands) { echo " checked='checked'"; } echo " /></div>
						</div>";
				echo "<button type='submit' title='".ct_submit."' class='wide'>".pt_send."</button>";
				echo "</fieldset>";
				echo "</form>";
			}
		}
	}

	public function sendChat()
	{
		Core::getObject('chat')->send($this->chattext, $this->tologin);
	}

	public function changeChatFilters()
	{
		//Save Filter settings to session
		$this->fmessages	= (array_key_exists('fmessages', $_REQUEST))	? true	: false;
		$this->fcommands	= (array_key_exists('fcommands', $_REQUEST))	? true	: false;
		Core::getObject('session')->initVar($this->id.'fmessages', $this->fmessages);
		Core::getObject('session')->initVar($this->id.'fcommands', $this->fcommands);
	}

	private function OutputLines($ChatLines)
	{
		$ChatLines = array_reverse($ChatLines);
		echo "<p>";
		foreach($ChatLines AS $value)
		{
			$nick = empty($value['nick']) ? '' : '['.$value['nick'].'] ';
			echo "<div>{$nick}{$value['text']}</div>";
		}
		echo "</p>";
	}
}
?>