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
class rcp_web
{
	/**
	* Checks if a url is available, returns false if not
	*
	* @param string $url
	* @param string $error message
	* @author hal.sascha
	*/
	public function checkURL($url, $return = false)
	{
		//Parse given url
		$data = parse_url($url);
		if($data === false || empty($data)) {
			trigger_error('Web URL check missed, unable to parse: '. $url, E_USER_WARNING);
			return false;
		}

		//Check parsed data
		$data['port'] = empty($data['port']) ? 80 : $data['port'];

		//Check connection
		$socket = @fsockopen($data['host'], $data['port'], $errno, $errstr, Core::getSetting('timeout_connect'));
		if($socket === false) {
			trigger_error('Web URL check missed, host not available: '. $data['host'] .':'. $data['port'] .' - '. $errno .' '. $errstr, E_USER_WARNING);
			return false;
		}
		fclose($socket);

		//Success!
		if(!$return) return true;

		//Return
		switch($return)
		{
			case 'file':
				return @file($url);
			break;

			case 'file_get_contents':
				return @file_get_contents($url);
			break;
		}
	}
}
?>