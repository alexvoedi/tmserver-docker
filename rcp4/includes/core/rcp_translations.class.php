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
class rcp_translations
{
	public function __construct()
	{
		//Create core translations
		$translations = Core::getObject('session')->loadXML(Core::getSetting('xmlpath').'language/'.Core::getSetting('language').'.xml');
		foreach($translations->core->children() AS $key => $node)
		{
			$var = 'ct_'.$key;
			define("$var", (string) $node);
		}
	}

	/**
	* Creates php constants for plugin translation
	*
	* @param boolean $core
	* @author hal.sascha
	*/
	public function createPluginTranslations($id)
	{
		$lang = file_exists(Core::getSetting('pluginpath').$id.'/'.Core::getSetting('language').'.xml') ? Core::getSetting('language') : Core::getSetting('deflanguage');
		if(file_exists(Core::getSetting('pluginpath').$id.'/'.$lang.'.xml')) {
			$translations = Core::getObject('session')->loadXML(Core::getSetting('pluginpath').$id.'/'.$lang.'.xml');
			foreach($translations->plugin->children() AS $key => $node)
			{
				$var = 'pt_'.$key;
				define("$var", (string) $node);
			}
		}
	}
}
?>