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
class rcp_settings
{
	public $settings;
	public $database;
	public $live;
	public $globals;

	public function __construct()
	{
		//Global settings
		$this->globals = Core::getObject('session')->loadXML(Core::getSetting('xmlpath').'settings/settings.xml');
		Core::storeSetting('defstyle', (string) $this->globals->defstyle);
		Core::storeSetting('deflanguage', (string) $this->globals->deflanguage);
		Core::storeSetting('register', stristr((string) $this->globals->register, 'true') ? true : false);
		Core::storeSetting('style', 'styles/'. Core::getObject('session')->admin->getStyle());
		Core::storeSetting('language', Core::getObject('session')->admin->getLanguage());
		Core::storeSetting('timeout_connect', (int) $this->globals->timeouts->connect);
		Core::storeSetting('timeout_rw', (int) $this->globals->timeouts->readwrite);

		//Settingset settings
		$this->settings = Core::getObject('session')->loadXML(Core::getSetting('xmlpath').'settings/'.Core::getObject('session')->server->settingset.'/settings.xml');
		$this->database = Core::getObject('session')->loadXML(Core::getSetting('xmlpath').'settings/'.Core::getObject('session')->server->settingset.'/database.xml');
		if(Core::getSetting('live')) {
			$this->live = Core::getObject('session')->loadXML(Core::getSetting('xmlpath').'settings/'.Core::getObject('session')->server->settingset.'/live.xml');

			//Global Live settings
			Core::storeSetting('messagesui', stristr((string) $this->live->settings->messagesui, 'true') ? true : false);
			Core::storeSetting('usesu', stristr((string) $this->live->settings->usesu, 'true') ? true : false);
		}
	}
}
?>