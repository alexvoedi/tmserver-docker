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

//Secure file against direct call
$forceError = Core::getObject('tparse')->toHTML('muhahaha');

//Get system info
$phpinfo = getServerInfo();

//Get file permissions
$cacheperms = (substr(decoct(fileperms('./cache/')), 2) > 700) ? true : false;
$xmlperms = (substr(decoct(fileperms('./xml/')), 2) > 700) ? true : false;
?>

<div>
	<h3>System Info</h3>
</div>
{dumpmsgs}

<ul>
	<li><span style="font-weight:bold;">System:</span> <?php echo $phpinfo['phpinfo']['System']; ?></li>
	<li><span style="font-weight:bold;">PHP version:</span> <?php echo $phpinfo['phpinfo']['PHP Version']; ?></li>
	<li><span style="font-weight:bold;">Server API:</span> <?php echo $phpinfo['phpinfo']['Server API']; ?></li>
	<li><span style="font-weight:bold;">Safe Mode:</span> <?php echo $phpinfo['PHP Core']['safe_mode'][0]; ?></li>
</ul>

<br /><br />

<div>
	<h3>File permissions</h3>
</div>

<ul>
	<li><span style="font-weight:bold;">cache directory:</span> <?php if($cacheperms) { echo 'ok'; } else { echo 'error'; } ?></li>
	<li><span style="font-weight:bold;">xml directory:</span> <?php if($xmlperms) { echo 'ok'; } else { echo 'error'; } ?></li>
</ul>

<br /><br />

<div>
	<h3>Install</h3>
	Click on Start button to continue.
</div>

<?php 
if($cacheperms && $xmlperms) {
?>
<div>
	<input class="installbutton" type="button" name="install_wdb" value="Install with remoteCP-Database" onclick="document.location.href='index.php?page=install&sub=install&db=1'" /> <input class="installbutton" type="button" name="install_wdb" value="Install without remoteCP-Database" onclick="document.location.href='index.php?page=install&sub=install&db=0'" />
</div>
<?php 
} else {
?>
<div>
	Installation impossible, please check your file permissions.
</div>
<?php 
}
?>

<br /><br />