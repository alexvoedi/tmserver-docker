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
 * Disable caching
 */
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1999 05:00:00 GMT');

/**
 * Create Core
 */
$plugin = (array_key_exists('plugin', $_REQUEST)) ? $_REQUEST['plugin'] : '';
$action = (array_key_exists('action', $_REQUEST)) ? $_REQUEST['action'] : '';
require_once './includes/core.class.php';
Core::storeCoreSettings();
Core::storeCoreObjects();

/**
 * Execute
 */
ob_start();
if(Core::getObject('session')->admin->isLogged() && $plugin) {
	//Load plugin system
	require_once './includes/plugins.class.php';
	Plugins::load($plugin);

	//Display
	Core::getObject('actions')->Exec($action);
	Plugins::triggerEvent('onOutput');

	//Unload plugin system
	Plugins::unLoad();
} else {
	trigger_error((!Core::getObject('session')->admin->isLogged()) ? ct_permerr1 : ct_pluginerr2, E_USER_WARNING);
}

/**
 * Output
 */
$output = ob_get_contents();
ob_end_clean();

require_once Core::getSetting('style').'/templates/ajax/tpl.header.php';
echo Core::getObject('messages')->getAll();
require_once Core::getSetting('style').'/templates/ajax/tpl.body.php';
require_once Core::getSetting('style').'/templates/ajax/tpl.footer.php';
?>