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
 * Redirect to index.php if installation prozess was allready finished
 */
if(file_exists('./cache/installed')) {
	redirect('index.php');
	exit;
}

ob_start();
/**
 * Get (sub) page
 */
$sub = $_REQUEST['sub'] ? $_REQUEST['sub'] : 'index';
switch($sub)
{
	case 'index':
		require_once './plugins/Install/sub.index.php';
	break;
	case 'install':
		require_once './plugins/Install/sub.install.php';
	break;
}

/**
 * Output
 */
$output = ob_get_contents();
ob_end_clean();
?>

<div style='margin-top:20px; width:100%; height:100px; background:url(<?php echo Core::getSetting('style'); ?>/logo.jpg) left center no-repeat;'></div>

<div class="text">
	<H2>New Installation of remoteCP!</H2>
	Follow the wizard to setup your database.
</div>
<br /><br />

<?php echo str_replace('{dumpmsgs}', Core::getObject('messages')->getAll(), $output); ?>

<div class="ic">
	<span style="color:#bb5500">remoteCP</span> Installer 1.0.1 by <a href='http://www.wiki.tmu-xrated.de'>Merlin</a>
</div>