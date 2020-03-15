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
class rcp_startup
{
	public function __construct()
	{
		//Check PHP version
		if(str_replace('.', '', phpversion()) < 513) {
			trigger_error('remoteCP '.Core::getSetting('version').' needs PHP 5.1.3 or above', E_USER_WARNING);
		}

		//Check post/get vars
		foreach($_REQUEST as $key => $content)
		{
			if(!empty($_REQUEST[$key]) && is_string($_REQUEST[$key])) {
					$_REQUEST[$key] = strip_tags($_REQUEST[$key]);
					$_REQUEST[$key] = trim($_REQUEST[$key]);
			}
		}

		//Send default header 
		//TODO: we need to replace all & trough &amp; to write valid xhmtl... else FF/safari stop page rendering...)
		//if(stristr($_SERVER['HTTP_ACCEPT'], 'application/xhtml+xml')) {
		//	header('Content-type: application/xhtml+xml; charset=UTF-8');
		//} else {
			header('Content-type: text/html; charset=UTF-8');
		//}
	}
}
?>