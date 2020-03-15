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
?>
<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN'
       'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>

<html xmlns='http://www.w3.org/1999/xhtml' lang='de' xml:lang='de'>
<head>
	<meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
	<meta http-equiv='Content-Script-Type' content='text/javascript' />
	<meta http-equiv='Content-Style-Type' content='text/css' />
	<title><?php echo ct_title; ?> [<?php echo Core::getSetting('version'); ?>]</title>
	<link rel='stylesheet' type='text/css' href='<?php echo Core::getSetting('style'); ?>/css/style.css' media='screen' />
	<link rel='stylesheet' type='text/css' href='<?php echo Core::getSetting('style'); ?>/css/SqueezeBox.css' media='screen' />
	<!--[if lte IE 7]><link rel='stylesheet' type='text/css' href='<?php echo Core::getSetting('style'); ?>/css/ie6.css' media='screen' /><![endif]-->
	<link rel='shortcut icon' href='favicon.ico' />
	<script type='text/javascript' src='styles/mootools.js'></script>
	<script type='text/javascript' src='styles/mootools-more.js'></script>
	<script type='text/javascript' src='styles/SqueezeBox.js'></script>
	<script type='text/javascript' src='styles/core.js'></script>
</head>
<body>
<noscript>
	<div id='noscript'><img src='<?php echo Core::getSetting('style'); ?>/close.gif' alt='' /><?php echo ct_noscript; ?><img src='<?php echo Core::getSetting('style'); ?>/close.gif' alt='' /></div>
</noscript>

<div id='modalbg'></div>
<div id='modal'>
	<div id='modaltitle'></div>
	<div id='modalclose'><a href='#' id='modalcloseA'><img src='<?php echo Core::getSetting('style'); ?>/close.gif' alt='' /></a></div>
	<div class='clear' id='modal_'></div>
</div>