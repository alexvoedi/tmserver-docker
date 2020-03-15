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
	* Sends a chat message to the game client(s)
	*
	* @param string $msg
	* @param string $tologin
	* @author hal.sascha
	*/
	public function send($msg, $tologin = false)
	{
		if(!$msg) return;
		if(!$tologin) {
			Core::getObject('gbx')->query('ChatSendServerMessage', '[$<'. (string) Core::getObject('settings')->settings->colors->chat->name.Core::getObject('session')->admin->username.'$>] '.$msg);
		} else {
			Core::getObject('gbx')->query('ChatSendServerMessageToLogin', '[$<'. (string) Core::getObject('settings')->settings->colors->chat->name.Core::getObject('session')->admin->username.'$>] '.$msg, urldecode($tologin));
		}
	}
}
?>