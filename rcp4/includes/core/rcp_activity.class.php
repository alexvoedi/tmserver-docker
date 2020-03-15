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
class rcp_activity
{
	/**
	* Adds a new acitivty to the logfile
	*
	* @param string $username
	* @param string $activity
	* @author hal.sascha
	*/
	public function add($username, $activity)
	{
		$suffix = Core::getSetting('live') ? 'live' : 'web';
		$login  = Core::getObject('session')->server->login;
		writeLog('activity_'.$login.'_'.$suffix, "[{$username}] {$activity}");
	}
}
?>