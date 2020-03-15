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
class rcp_chat
{
	/**
	 * Array of chatcommands
	 * @access public
	 */
	public $chatcommands = array();

	/**
	* Sends a chatmessage to the game client(s)
	*
	* Message will be send to the specified login ($tologin). If nickname is specified, the system will add this nickname as sender name.
	* @param string $msg
	* @param string $nickname
	* @param string $tologin
	* @author hal.sascha
	*/
	public function send($msg, $player = false, $tologin = false)
	{
		if(!$msg) return;
		$prefix = ($player && Core::getObject('players')->check($player)) ? '$z['.$player->NickName.'$z] ' : ' >> !df!';
		if(!$tologin) {
			Core::getObject('gbx')->addCall('ChatSendServerMessage', $this->parseColors($prefix.$msg));
		} else {
			Core::getObject('gbx')->addCall('ChatSendServerMessageToLogin', $this->parseColors($prefix.$msg), urldecode($tologin));
		}
	}

	/**
	* Adds a new chatcommand
	*
	* $admin specifies if this is an admin only command
	* @param string $command
	* @param string $callback
	* @param string $string
	* @param boolean $admin
	* @author hal.sascha
	*/
	public function addCommand($command, $callback, $help, $example, $admin = false)
	{
		$this->chatcommands[$command] = array(
			'command'	=> $command,
			'callback'	=> $callback,
			'help'		=> $help,
			'example'	=> $example,
			'admin'		=> $admin
		);
	}

	/**
	* Returns a chatcommand array
	*
	* @param string $command
	* @param string $login
	* @author hal.sascha
	*/
	public function getCommand($command, $login)
	{
		$command = substr($command, 1);
		if(array_key_exists($command, $this->chatcommands)) {
			$command = $this->chatcommands[$command];
			if(!$command['admin'] || ($command['admin'] && Core::getObject('live')->isAdmin($login) && Core::getObject('live')->checkPerm($command['admin'], $login, $command['command']))) {
				return $command;
			}
		}
		return false;
	}

	/**
	* Parses the chatcolor shortcuts
	*
	* @param string $message
	* @author hal.sascha
	*/
	private function parseColors($message)
	{
		$message = str_replace('!df!', (string) Core::getObject('settings')->live->colors->chat->default, $message);
		$message = str_replace('!hl!', (string) Core::getObject('settings')->live->colors->chat->highlight, $message);
		$message = str_replace('!si!', (string) Core::getObject('settings')->live->colors->chat->subitem, $message);
		$message = str_replace('!cm!', (string) Core::getObject('settings')->live->colors->chat->command, $message);
		return $message;
	}
}
?>