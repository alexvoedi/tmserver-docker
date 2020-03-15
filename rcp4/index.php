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
 * Create Core
 */
require_once './includes/core.class.php';
Core::storeCoreSettings();
Core::storeSetting('pluginpath', './plugins/');
Core::storeCoreObjects();

/**
 * Execute
 */
ob_start();
$template = Core::getSetting('windowed') ? 'window' : 'index';
if(Core::getObject('session')->admin->isLogged()) {
	//Load plugin system
	require_once './includes/plugins.class.php';
	Plugins::load();

	//Offline mode
	if(Core::getSetting('offline')) {
		trigger_error(ct_offlinelogininfo, E_USER_NOTICE);
	}

	//Display
	require_once Core::getSetting('style').'/templates/'.$template.'/tpl.body.php';

	//Unload plugin system
	Plugins::unLoad();
} else {
	switch($_REQUEST['page']) {
		case 'install':
			require_once Core::getSetting('pluginpath').'install.php';
		break;

		case 'register':
			require_once Core::getSetting('pluginpath').'register.php';
		break;

		default:
			require_once Core::getSetting('pluginpath').'access.php';
		break;
	}
}

/**
 * Output
 */
$output = ob_get_contents();
ob_end_clean();

require_once Core::getSetting('style').'/templates/'.$template.'/tpl.header.php';
echo str_replace('{dumpmsgs}', Core::getObject('messages')->getAll(), $output);
require_once Core::getSetting('style').'/templates/'.$template.'/tpl.footer.php';
?>