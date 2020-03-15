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
class Plugins {
	/**
	 * Array of plugin objects
	 * @access private
	 */
	private static $plugins = array();

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
		throw new Exception('Cloning the Plugin-System is not permitted', 1001);
	}

	/**
	* Loads all plugins into the plugin system
	*
	* @author hal.sascha
	*/
	public function load($plugin = false)
	{
		//Load single plugin
		if($plugin) {
			Plugins::storePlugin($plugin);
			return;
		}

		//Load all plugins
		if(is_dir(Core::getSetting('pluginpath'))) {
			$source = (Core::getSetting('live')) ? 'live' : 'settings';
			foreach(Core::getObject('settings')->{$source}->plugins->children() AS $key => $plugin)
			{
				$plugin = (string) $plugin;
				Plugins::storePlugin($plugin);
				if(Core::getSetting('live')) {
					Core::getObject('messages')->add(' - '. $plugin);
				}
			}
		} else {
			trigger_error(ct_pluginerr1, E_USER_WARNING);
		}
	}

	/**
	* Unloads all plugins from the plugin system
	*
	* This function also calls the onUnload callback
	* @author hal.sascha
	*/
	public function unLoad()
	{
		self::$instance->triggerEvent('onUnload');

		if(Core::getObject('gbx')->checkConnection()) {
			Core::getObject('gbx')->Terminate();
		}

		foreach(self::$plugins AS $id => $name)
		{
			self::$instance->destroyPlugin($id);
		}
	}

	/**
	 * Stores an Plugin object in the Plugin-System
	 * @param String $plugin the name of the plugin object
	 * @return void
	 */
	public function storePlugin($plugin)
	{
		//Require file
		if(is_dir(Core::getSetting('pluginpath').$plugin.'/')) {
			require_once Core::getSetting('pluginpath').$plugin.'/index.php';
		} else {
			trigger_error(ct_pluginerr1, E_USER_WARNING);
			return false;
		}

		//Load plugin
		self::$instance->loadPlugin(new $plugin, $plugin);
	}

	/**
	 * Gets an object from the Core
	 * @param String $key the array key
	 * @return object
	 */
	public function getPlugin($key)
	{
		if(is_object(self::$plugins[$key])) {
			return self::$plugins[$key];
		}
	}

	/**
	 * Gets all plugin object as array
	 * @param void
	 * @return array
	 */
	public function getAllPlugins()
	{
		return self::$plugins;
	}

	/**
	* Loads a plugin into the plugin system
	*
	* @param object $plugin
	* @param string $id
	* @author hal.sascha
	*/
	private function loadPlugin(&$plugin, $id)
	{
		self::$plugins[$id] =& $plugin;
		$plugin->id = $id;

		//Check view permissions
		//Destroy plugin if there's no permission
		if(is_array($plugin->vpermissions)) {
			foreach($plugin->vpermissions AS $value)
			{
				if(!Core::getObject('session')->checkPerm($value)) {
					if($plugin->display == 'box') trigger_error(ct_vpermerr1, E_USER_WARNING);
					return self::$instance->destroyPlugin($plugin->id);
				}
			}
		} else {
			trigger_error(ct_vpermerr2, E_USER_WARNING);
			return self::$instance->destroyPlugin($plugin->id);
		}

		//Check server connection need
		if($plugin->nservcon && !Core::getObject('gbx')->checkConnection()) {
			if(Core::getObject('gbx')->newConnection()) {
				Core::getObject('status')->update();
			} else {
				//Set offline mode
				if(Core::getObject('session')->checkPerm('offlinelogin')) {
					Core::storeSetting('offline', true);

					//Destroy plugin if plugin has need for servcon
					return self::$instance->destroyPlugin($plugin->id);
				} else {
					Core::storeSetting('offline', false);
					//trigger_error(Core::getObject('gbx')->getError(), E_USER_WARNING);

					//Handle login error / redirect
					$code    = Core::getObject('gbx')->getErrorCode();
					$message = Core::getObject('gbx')->getErrorMessage();
					if(!Core::getSetting('live')) {
						if($code == -32300) redirect('index.php?refused=true');
						if($code == -1000 && $message == 'User unknown.') redirect('index.php?refused=true&usr=true');
						if($code == -1000 && $message == 'Password incorrect.') redirect('index.php?refused=true&psw=true');
					}
				}
			}
		}

		//Check SQL connection need
		if(Core::getObject('session')->server->sql['enabled'] && $plugin->nsqlcon && !Core::getObject('db')->checkConnection()) {
			$result = Core::getObject('db')->newConnection(
				'live1',
				Core::getObject('session')->server->sql['dsn'],
				Core::getObject('session')->server->sql['username'],
				Core::getObject('session')->server->sql['password']
			);

			//check connection
			if(!Core::getObject('db')->checkConnection()) {
				//Destroy plugin if plugin has need for sqlcon
				return self::$instance->destroyPlugin($plugin->id);
			}
		}

		//Load translation
		Core::getObject('translations')->createPluginTranslations($plugin->id);

		//Load plugin settings
		self::$instance->loadSettings($plugin->id);

		//Startup plugin
		self::$instance->triggerEvent(array('onLoad', $plugin->id));
	}

	/**
	* Destroys a plugin
	*
	* @param string $id
	* @author hal.sascha
	*/
	protected function destroyPlugin($id)
	{
		self::$plugins[$id] = null;
		unset(self::$plugins[$id]);
	}

	/**
	* Loads the settings.xml file and calls the LoadSettings callback if available
	*
	* @author hal.sascha
	*/
	public function loadSettings($pluginid)
	{
		if(file_exists(Core::getSetting('pluginpath').$pluginid.'/settings_'.Core::getObject('session')->server->id.'.xml')) {
			$file = Core::getObject('session')->loadXML(Core::getSetting('pluginpath').$pluginid.'/settings_'.Core::getObject('session')->server->id.'.xml');
		} elseif(file_exists(Core::getSetting('pluginpath').$pluginid.'/settings.xml')) {
			$file = Core::getObject('session')->loadXML(Core::getSetting('pluginpath').$pluginid.'/settings.xml');
		}

		if($file) {
			if(isset($file->enabled)) {
				if((int) $file->enabled) {
					self::$instance->getPlugin($pluginid)->setEnabled(true);
				} else {
					self::$instance->getPlugin($pluginid)->setEnabled(false);
				}
			}
			if(isset($file->debugging)) {
				if((int) $file->debugging) {
					self::$instance->getPlugin($pluginid)->setDebug(true);
				} else {
					self::$instance->getPlugin($pluginid)->setDebug(false);
				}
			}
			self::$instance->triggerEvent(array('onLoadSettings', $pluginid), $file);
		}
	}

	/**
	* Calls a callback function
	*
	* eventHandler can also be an array with an plugin-object that will be called.
	* If eventHandler is a string, the function will search the callback in all plugins.
	* @param mixed $eventHandler
	* @param mixed $params
	* @return mixed
	* @author hal.sascha
	*/
   	public function triggerEvent($eventHandler, $params = false)
	{
		if(is_array($eventHandler) && array_key_exists($eventHandler[1], self::$plugins)) {
			$return = false;
			$method = $eventHandler[0];
			$plugin = self::$instance->getPlugin($eventHandler[1]);
			if($plugin->isEnabled($method) && $plugin->isActive($method) && in_array(Core::getObject('status')->server['Code'], $plugin->nservstatus)) {
				if(method_exists($plugin, $method)) {
					Core::getObject('usage')->beginCheck($eventHandler[1]->id.'-'.$eventHandler[0]);
					$return = $plugin->$method($params);
					Core::getObject('usage')->stopCheck($eventHandler[1]->id.'-'.$eventHandler[0]);
				}
			}
		} else {
			$return = array();
			foreach(self::$plugins as $plugin)
			{
				if($plugin->isEnabled($eventHandler) && $plugin->isActive($eventHandler) && in_array(Core::getObject('status')->server['Code'], $plugin->nservstatus)) {
					if(method_exists($plugin, $eventHandler)) {
						Core::getObject('usage')->beginCheck($plugin->id.'-'.$eventHandler);
						$return[$plugin->id] = self::$instance->getPlugin($plugin->id)->$eventHandler($params);
						Core::getObject('usage')->stopCheck($plugin->id.'-'.$eventHandler);
					}
				}
			}
		}
		return $return;
	}
}

class rcp_plugin
{
	public    $id;
	public    $display		= 'main';
	public    $author		= 'n/a';
	public    $version		= 'n/a';
	public    $usejs		= false;
	public    $usecss		= false;
	public    $nservcon		= true;
	public    $nsqlcon		= false;
	public    $nservstatus	= array(0,1,2,3,4,5);
	public    $vpermissions = array(null);
	public    $apermissions	= array();
	public    $quickoptions	= array();
	protected $enabled		= true;
	protected $active		= true;
	protected $debugging	= false;

	public function isEnabled($method = '')
	{
		if($this->enabled === true || is_null($this->enabled)) {
			return true;
		} elseif($method == 'onMLAEnabled' || $method == 'onNewPlayer') {
			return true;
		} elseif(is_string($this->enabled) && $method == $this->enabled) {
			return true;
		}
		return false;
	}

	public function isActive($method = '')
	{
		if($this->active === true || is_null($this->active)) {
			return true;
		} elseif($method == 'onMLAActive' || $method == 'onNewPlayer') {
			return true;
		} elseif(is_string($this->active) && $method == $this->active) {
			return true;
		}
		return false;
	}

	public function isDebug()
	{
		return ($this->debugging) ? true : false;
	}

	/**
	* Sets the enabled status
	* It's impossible to change the enabled-status, if the plugin or the value has "null" as status
	*
	* @param mixed $value
	* @author hal.sascha
	*/
	public function setEnabled($value)
	{
		if(is_null($this->enabled) || is_null($value)) return;
		$this->enabled = $value;
	}

	/**
	* Sets the active status, $value could be also a method name that is allowed to call even if the plugin is inactive
	* It's impossible to change the active-status, if the plugin or the value has "null" as status
	*
	* @param mixed $value
	* @author hal.sascha
	*/
	public function setActive($value)
	{
		if(is_null($this->active) || is_null($value)) return;
		$this->active = $value;
	}

	/**
	* Sets the debugging status
	*
	* @param boolean $value
	* @author hal.sascha
	*/
	public function setDebug($value)
	{
		$this->debugging = $value;
	}

	/**
	* Adds a new icon to the quickoption bar in the upper right corner of the webinterface
	*
	* @param string $url
	* @param string $rel
	* @param string $class
	* @param string $icon
	* @param string $text
	* @author hal.sascha
	*/
	public function addQuickOption($url, $rel, $class, $icon, $text)
	{
		$this->quickoptions[] = array(
			'url'	=> $url,
			'rel'	=> $rel,
			'class'	=> $class,
			'icon'	=> $icon,
			'text'	=> $text
		);
	}

	/**
	* Adds a new debugging message to the messagelist
	*
	* @param string $text
	* @author hal.sascha
	*/
	public function debug($text)
	{
		if(Core::getSetting('debug') || $this->debugging) {
			Core::getObject('messages')->add("[{$this->id}] {$text}");
		}
	}
}

class rcp_liveplugin extends rcp_plugin
{
	public $adminoptions = array();

	public function __construct()
	{
		// Add default enable/disable admin option
		if(!is_null($this->enabled)) {
			$this->addAdminOption('onMLAEnabled');
			$this->addAdminOption('onMLADebug');
		}
	}

	/**
	* Adds a  new line to the the plugin option
	*
	* @param array $callback
	* @author hal.sascha
	*/
	public function addAdminOption($callback)
	{
		$this->adminoptions[] = $callback;
	}

	/**
	* Default enable, disable method
	*
	* @param mixed $params
	* @author hal.sascha
	*/
	public function onMLAEnabled($params)
	{
		if(is_null($params)) {
			if($this->enabled === true) {
				return 'Disable plugin';
			} elseif($this->enabled === false) {
				return 'Enable plugin';
			} else {
				return 'Enable/Disable not available';
			}
		}

		if(is_array($params) && is_null($params[0])) {
			if($this->enabled === true) {
				return 'enabled';
			} elseif($this->enabled === false) {
				return 'disabled';
			} elseif(is_null($this->enabled)) {
				return 'blocked';
			} else {
				return 'error';
			}
		}

		if(!Core::getObject('players')->check($params[0]) || !Core::getObject('live')->isAdmin($params[0]->Login) || !Core::getObject('live')->checkPerm('globalmaintenance', $params[0]->Login, 'En/disable '.$this->title.' plugin')) return;
		if($this->enabled) {
			$this->setEnabled(false);
			Core::getObject('live')->addMsg($this->title.' plugin !hl!disabled');
		} else {
			$this->setEnabled(true);
			Core::getObject('live')->addMsg($this->title.' plugin !hl!enabled');
		}
	}

	/**
	* Returns the current active status for the plugin
	*
	* @param void
	* @author hal.sascha
	*/
	public function onMLAActive()
	{
		return ($this->isActive()) ? 'active' : 'inactive';
	}

	/**
	* Default enable debugging, disable debugging method
	*
	* @param mixed $params
	* @author hal.sascha
	*/
	public function onMLADebug($params)
	{
		if(is_null($params)) {
			return ($this->debugging) ? 'Disable debugging' : 'Enable debugging';
		}

		if(is_array($params) && is_null($params[0])) {
			return ($this->isDebug()) ? 'enabled' : 'disabled';
		}

		if(!Core::getObject('players')->check($params[0]) || !Core::getObject('live')->isAdmin($params[0]->Login) || !Core::getObject('live')->checkPerm('globalmaintenance', $params[0]->Login, 'En/disable '.$this->title.' debugging')) return;
		if($this->debugging) {
			$this->setDebug(false);
			Core::getObject('live')->addMsg($this->title.' debugging !hl!disabled');
		} else {
			$this->setDebug(true);
			Core::getObject('live')->addMsg($this->title.' debugging !hl!enabled');
		}
	}
}

/**
 * Create singleton instance
 */
Plugins::singleton();
?>