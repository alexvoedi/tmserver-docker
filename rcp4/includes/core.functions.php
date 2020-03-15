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

/**
 * PHP Settings
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL ^ E_NOTICE);
set_error_handler('errorHandler');
date_default_timezone_set('Europe/Berlin');
//require_once './includes/crashmail.php';

/**
* Error handler
*
* @params none
* @author hal.sascha
*/
function errorHandler($errno, $errstr, $errfile, $errline)
{
	//Check error suppression
	if(error_reporting() == 0) return;

	//Default config values
	$kill = false;
	$umsg = false;

	//Check error type
	switch($errno) {
		case E_USER_ERROR:
			$message = "[".date('c')."] [remoteCP Error] $errstr on line $errline in file $errfile";
			$kill = true;
		break;

		case E_USER_WARNING:
			$message = "[".date('c')."] [remoteCP Warning] $errstr on line $errline in file $errfile";
			$umsg = true;
		break;

		case E_USER_NOTICE:
			$message = "[".date('c')."] [remoteCP Notice] $errstr";
			$umsg = true;
		break;

		case E_ERROR:
			$message = "[".date('c')."] [PHP Error] $errstr on line $errline in file $errfile";
			$kill = true;
		break;

		case E_WARNING:
			$message = "[".date('c')."] [PHP Warning] $errstr on line $errline in file $errfile";
			$umsg = true;
		break;

		default:
			return;
		break;
	}

	//Output error
	if($umsg) {
		Core::getObject('messages')->add($message, true);
	} else {
		echo $message;
	}

	//Log the error
	if(!empty(Core::getObject('session')->server->login)) {
		$login = '_'.Core::getObject('session')->server->login;
	} else {
		$login = '';
	}
	writeLog('errors'.$login, $message);

	//Kill the script @ error
	if($kill) {
		//Dump messages
		echo Core::getObject('messages')->getAll();

		//Disable output caching
		if(ob_get_level()) {
			ob_end_flush();
		}

		//Send crashmail
		if(function_exists('crashmail')) crashmail($login, $message);

		//Kill! uargggg ... :p
		die();
	}
}

/**
* Logs errors into file
*
* @params string $msg
* @author hal.sascha
*/
function writeLog($key, $msg)
{
	static $handles = array();

	//Get logfile handle
	if(array_key_exists($key, $handles) && $handles[$key] !== false) {
		//do nothing ...
	} else {
		$handles[$key] = fopen('./cache/'.$key.'.log', 'a+');
	}

	//Assign handle
	if(!$handles[$key]) return;
	if($msg === false) {
		fclose($handles[$key]);
		$handle = false;
	} else {
		fwrite($handles[$key], '['.date("c").'] '.$msg."\r\n");
	}
}

/**
* Makes a header http-redirect
*
* @param string $extra
* @param boolean $external
* @return void
* @author hal.sascha
*/
function redirect($extra, $external = false)
{
	$host = $_SERVER['HTTP_HOST'];
	$uri  = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
	header('Location: http://'.$host.$uri.'/'.$extra);
}

/**
* Strips or encodes all invalid signs for output at xml/html
*
* @param string $string
* @param boolean $decode
* @return string
* @author hal.sascha
*/
function specialchars($string, $decode = false)
{
	if($decode) return htmlspecialchars_decode($string, ENT_QUOTES);
	return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
* Returns some Infodata about the used server
*
* @return array
* @author xr.merlin
*/
function getServerInfo()
{
	ob_start();
	phpinfo(-1);
	$phpinfo = array('phpinfo' => array());
	if(preg_match_all('#(?:<h2>(?:<a name=".*?">)?(.*?)(?:</a>)?</h2>)|(?:<tr(?: class=".*?")?><t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>(?:<t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>(?:<t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>)?)?</tr>)#s', ob_get_clean(), $matches, PREG_SET_ORDER))
	foreach($matches as $match)
	{
		if(strlen($match[1])) { 
			$phpinfo[$match[1]] = array();
		} elseif(isset($match[3])) {
			$phpinfo[end(array_keys($phpinfo))][$match[2]] = isset($match[4]) ? array($match[3], $match[4]) : $match[3];
		} else {
			$phpinfo[end(array_keys($phpinfo))][] = $match[2];
		}
	}
	return $phpinfo;
}
?>