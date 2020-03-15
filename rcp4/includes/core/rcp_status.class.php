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
class rcp_status
{
	public  $server		= false;
	public  $gameinfo	= false;
	public  $gamestate	= false;
	public  $packmask	= false;
	public  $systeminfo	= false;
	public  $isrelay	= false;
	public  $hidestatus	= false;
	private $updated	= false;

	public function updateVar($var, $value)
	{
		if(!isset($this->{$var})) return;
		$this->{$var} = $value;
	}

	/**
	* Reads the most important data from the server
	*
	* @author hal.sascha
	*/
	public function update($forced = false)
	{
		//Return from method if update was called multiple times without forced param
		if(!$forced && $this->updated) return;

		$this->updated = true;
		if(Core::getObject('gbx')->query('GetCurrentGameInfo', 1))
			Core::getObject('status')->updateVar('gameinfo', Core::getObject('gbx')->getResponse());

		if(Core::getObject('gbx')->query('GetStatus'))
			Core::getObject('status')->updateVar('server', Core::getObject('gbx')->getResponse());

		if(Core::getObject('gbx')->query('GetServerPackMask'))
			Core::getObject('status')->updateVar('packmask', Core::getObject('gbx')->getResponse());

		if(Core::getObject('gbx')->query('IsRelayServer'))
			Core::getObject('status')->updateVar('isrelay', Core::getObject('gbx')->getResponse());

		if(Core::getObject('gbx')->query('GetSystemInfo'))
			Core::getObject('status')->updateVar('systeminfo', Core::getObject('gbx')->getResponse());

		if(Core::getObject('gbx')->query('GetHideServer'))
			Core::getObject('status')->updateVar('hidestatus', Core::getObject('gbx')->getResponse());

		if(Core::getObject('gbx')->query('GetServerName'))
			Core::getObject('session')->server->name = Core::getObject('gbx')->getResponse();
	}

	/**
	* Returns a game value for the current packmask
	*
	* @author hal.sascha
	*/
	public function getGameFromPackmask()
	{
		if(Core::getObject('status')->packmask == 'Nations' || Core::getObject('status')->packmask == 'Stadium') {
			return 'Nations';
		} else {
			return 'United';
		}
	}
}
?>