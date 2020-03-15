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

require_once './includes/core.functions.php';
class Core {
	/**
	 * Array of objects
	 * @access private
	 */
	private static $objects = array();

	/**
	 * Array of settings
	 * @access private
	 */
	private static $settings = array();

	/**
	 * The instance of the Core
	 * @access private
	 */
	private static $instance;

	/**
	 * Private constructor to prevent it being created directly
	 * @access private
	 */
	private function __construct()
	{
		// I'm uncallable :D
	}

	/**
	 * Singleton method used to access the object
	 * @access public
	 * @return 
	 */
	public static function singleton()
	{
		if(!isset(self::$instance)) {
			$obj = __CLASS__;
			self::$instance = new $obj;
		}
		return self::$instance;
	}

	/**
	 * Prevent cloning of the object: issues an E_USER_ERROR if this is attempted
	 */
	public function __clone()
	{
		throw new Exception('Cloning the Core is not permitted', 1001);
	}

	/**
	 * Loads global settings
	 * @param void
	 * @return void
	 */
	public function storeCoreSettings()
	{
		//Core settings
		$filename = substr($_SERVER['SCRIPT_URL'],strrpos($_SERVER['SCRIPT_URL'],'/')+1);
		self::$instance->storeSetting('httppath', str_replace($filename, '', $_SERVER['SCRIPT_URI']));
		self::$instance->storeSetting('debug', false);
		self::$instance->storeSetting('pluginpath', './plugins/');
		self::$instance->storeSetting('xmlpath', './xml/');
		self::$instance->storeSetting('version', '4.0.3.6');
		self::$instance->storeSetting('windowed', array_key_exists('windowed', $_REQUEST));
		self::$instance->storeSetting('offline', false);
	}

	/**
	 * Loads global objects
	 * @param void
	 * @return void
	 */
	public function storeCoreObjects()
	{
		//General core objects
		self::$instance->storeObject('core/rcp_messages', 'rcp_messages', 'messages');
		self::$instance->storeObject('core/rcp_textparser', 'rcp_textparser', 'tparse');
		self::$instance->storeObject('core/rcp_usage', 'rcp_usage', 'usage');
		self::$instance->storeObject('core/rcp_form', 'rcp_form', 'form');
		self::$instance->storeObject('core/rcp_web', 'rcp_web', 'web');
		self::$instance->storeObject('core/rcp_activity', 'rcp_activity', 'activity');

		//Important core objects (don't change the order)
		self::$instance->storeObject('core/rcp_startup', 'rcp_startup', 'startup');
		self::$instance->storeObject('core/rcp_session', 'rcp_session', 'session');
		self::$instance->storeObject('core/rcp_settings', 'rcp_settings', 'settings');
		self::$instance->storeObject('core/rcp_translations', 'rcp_translations', 'translations');
		self::$instance->storeObject('core/rcp_database', 'rcp_database', 'db');

		//Logged-In core objects
		if(!Core::getObject('session')->admin->isLogged()) return;
		self::$instance->storeObject('core/rcp_gbx', 'rcp_gbx', 'gbx');
		self::$instance->storeObject('core/rcp_status', 'rcp_status', 'status');

		//Live objects
		if(self::$instance->getSetting('live')) {
			self::$instance->storeObject('live/rcp_chat', 'rcp_chat', 'chat');
			self::$instance->storeObject('live/rcp_actions', 'rcp_actions', 'actions');
			self::$instance->storeObject('live/rcp_live', 'rcp_live', 'live');
			self::$instance->storeObject('live/rcp_bills', 'rcp_bills', 'bills');
			self::$instance->storeObject('live/rcp_timedevents', 'rcp_timedevents', 'timedevents');
			self::$instance->storeObject('live/rcp_players', 'rcp_players', 'players');
			self::$instance->storeObject('live/rcp_challenges', 'rcp_challenges', 'challenges');
			self::$instance->storeObject('live/rcp_manialink', 'rcp_manialink', 'manialink');
		//Webinterface objects
		} else {
			self::$instance->storeObject('web/rcp_chat', 'rcp_chat', 'chat');
			self::$instance->storeObject('web/rcp_actions', 'rcp_actions', 'actions');
		}

		//Output headers
		if(Core::getSetting('debug') && !empty($_SERVER)) {
			foreach($_SERVER AS $key => $value)
			{
				if(stristr($key, 'HTTP_')) {
					self::$instance->getObject('messages')->add("[{$key}] {$value}");
				}
			}
		}
	}

	/**
	 * Stores an object in the Core
	 * @param String $object the name of the object
	 * @param String $key the key for the array
	 * @return void
	 */
	public function storeObject($file, $object, $key = false)
	{
		//Require file
		require_once 'includes/'.$file.'.class.php';

		//Create object if key isset
		if($key) {
			self::$objects[$key] = new $object(self::$instance);
		}
	}

	/**
	 * Gets an object from the Core
	 * @param String $key the array key
	 * @return object
	 */
	public function getObject($key)
	{
		if(is_object(self::$objects[$key])) {
			return self::$objects[$key];
		}
	}

	/**
	 * Stores settings in the Core
	 * @param String $data
	 * @param String $key the key for the array
	 * @return void
	 */
	public function storeSetting($key, $data)
	{
		self::$settings[$key] = $data;
	}

	/**
	 * Gets a setting from the Core
	 * @param String $key the key in the array
	 * @return void
	 */
	public function getSetting($key)
	{
		return self::$settings[$key];
	}
}

/**
 * Create singleton instance
 */
Core::singleton();
?>