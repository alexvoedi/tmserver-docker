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
if(array_key_exists('help', $_REQUEST)) {
?>
<div class='helpp'>
	<a href='ajax.php?plugin=Help&id=<?php echo $plugin; ?>' rel='480;0' class='modal'><img src='<?php echo Core::getSetting('style'); ?>/icons/help.gif' width='16' height='16' alt='<?php echo ct_help; ?>' title='<?php echo ct_help; ?>' /></a>
</div>
<?php
}
?>