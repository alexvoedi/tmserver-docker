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
require_once './includes/core/IXR_Library.inc.php';
class Update extends rcp_plugin
{
	public  $display	= 'quick';
	public  $title		= 'Update';
	public  $author		= 'hal.ko.sascha';
	public  $version	= '4.0.3.6';
	public  $nservcon	= false;

	public function onLoad()
	{
		$this->addQuickOption('ajax.php?plugin=Update', '400;0', 'modal', 'update', ct_update);
	}

	public function onOutput()
	{
		//connect to tmbase
		$connection = new IXR2_ClientMulticall('http://www.tmbase.de/server.php');
		$connection->addCall('remotecp.checkForUpdates', (string) 'rcp4');
		if(!$connection->query())
			trigger_error($connection->getErrorMessage());
		else
			$response = $connection->getResponse();

		//Output Standalone files to this family
		$submittedversion = str_replace('.', '', Core::getSetting('version')) + 0;
		foreach($response[0][0] AS $node)
		{
			$currentversion = str_replace('.', '', $node['version']) + 0;
			if(stristr((string) $node['family'], 'rcp4') && stristr((string) $node['typeB'], 'Tool')) {
				echo "	<div class='f-row'>
						<label>{$node['version']}</label>
						<div class='f-field'>{$node['tool']} <a href='". $node['download'] ."'>"; if($currentversion <= $submittedversion) { echo "<span style='color:#0c0;'>".pt_up2date."</span>"; } else { echo "<span style='color:#c00;'>".pt_update."</span>"; } echo "</a></div>
					</div>";
			}
		}

		//Output Plugin files to this family
		echo "<div class='legend'>Plugins</div>";
		foreach($response[0][0] AS $node)
		{
			$currentversion = str_replace('.', '', $node['version']) + 0;
			if(stristr((string) $node['family'], 'rcp4') && stristr((string) $node['typeA'], 'rcp4') && stristr((string) $node['typeB'], 'Plugin')) {
				echo "	<div class='f-row'>
						<label>{$node['version']}</label>
						<div class='f-field'>{$node['tool']} <a href='". $node['download'] ."'>"; if($currentversion <= $submittedversion) { echo "<span style='color:#0c0;'>".pt_up2date."</span>"; } else { echo "<span style='color:#c00;'>".pt_update."</span>"; } echo "</a></div>
					</div>";
			}
		}
	}
}
?>