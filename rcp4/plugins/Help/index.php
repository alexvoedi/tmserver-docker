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
class Help extends rcp_plugin
{
	public  $display		= 'quick';
	public  $title			= 'Help';
	public  $author			= 'hal.ko.sascha';
	public  $version		= '4.0.3.6';

	public function onOutput()
	{
		//Vars
		$id = $_REQUEST['id'];

		//Check availabillity
		$translations = $this->checkLanguageFile($id, Core::getSetting('language'));
		if($translations === false) {
			$translations = $this->checkLanguageFile($id, Core::getSetting('deflanguage'));
		}

		//Get / output help
		echo "<form action='ajax.php' method='post' id='helpfake' name='helpfake'>";
		if($translations) {
			//Categories
			foreach($translations->help->children() AS $key => $catnode)
			{
				echo "<fieldset>";
				echo "<div class='legend'>". (string) $catnode['title'] ."</div>";

				//Infos
				foreach($catnode->children() AS $key => $itemnode)
				{
					echo "	<div class='f-row'>
								<label>". (string) $itemnode['title'] ."</label>
								<div class='f-field'>". (string) $itemnode ."</div>
							</div>";
				}
				echo "</fieldset>";
			}
		} else {
			//N/A message
			Core::getObject('messages')->add(pt_na);
		}
		echo "</form>\n";
	}

	private function checkLanguageFile($id, $lang)
	{
		if(file_exists(Core::getSetting('pluginpath').$id.'/'.$lang.'.xml')) {
			$translations = Core::getObject('session')->loadXML(Core::getSetting('pluginpath').$id.'/'.$lang.'.xml');
			if(isset($translations->help) && $translations->help instanceof SimpleXMLElement) {
				return $translations;
			}
		}
		return false;
	}
}
?>