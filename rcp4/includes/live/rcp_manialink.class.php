<?php
/**
* remoteCP 4
* ütf-8 release
*
* @package remoteCP
* @author hal.sascha
* @copyright (c) 2006-2009
* @version 4.0.3.2
*/
class rcp_manialink
{
	/**
	* Array of container status values
	* @access private
	*/
	private $containers = array();

	/**
	* Manual mania framework call
	*
	* Calls a specified callback function with it's parameters to an specified login.
	* If login is not specified, callback will be called for all players.
	* @param string $callback
	* @param mixed $param
	* @param string $login
	* @author hal.sascha
	*/
	public function call($callback, $param = false, $login = false)
	{
		if($login) {
			$player = Core::getObject('players')->get($login);
			if(!Core::getObject('players')->check($player)) return;
			Plugins::triggerEvent($callback, array($player, $param));
		} else {
			if(empty(Core::getObject('players')->players)) return;
			foreach(Core::getObject('players')->players AS $player)
			{
				if(!Core::getObject('players')->check($player)) continue;
				Plugins::triggerEvent($callback, array($player, $param));
			}
		}
	}

	/**
	* Handles the PlayerManialinkPageAnswer callback data
	* then checks and returns the Mania framework data
	*
	* @param array $call
	* @author hal.sascha
	*/
	public function handlePageAnswer($call)
	{
		//Check callback data
		if(!is_array($call) || empty($call)) return;

		//Call ManiaFWK for the called player
		$player = Core::getObject('players')->get($call[1]);
		if(!Core::getObject('players')->check($player)) return;

		//Call actions
		$action = $player->ManiaFWK->getAction($call[2]);
		if(is_array($action)) {
			Plugins::triggerEvent($action['call'], array($player, $action['param']));
		}
		Plugins::triggerEvent('onPlayerManialinkPageAnswer', array($player, $call, $action));

		//Get XML
		$xml = $player->ManiaFWK->getXml();
		if($xml) {
			Core::getObject('gbx')->query('SendDisplayManialinkPageToLogin', $player->Login, $xml, 0, false);
		}
	}

	/**
	* Checks and returns the Mania framework data
	*
	* @param array $call
	* @author hal.sascha
	*/
	public function display()
	{
		if(empty(Core::getObject('players')->players)) return;
		foreach(Core::getObject('players')->players AS $player)
		{
			if(Core::getObject('players')->check($player)) {
				//Update containers for all players
				if(!empty($this->containers)) {
					foreach($this->containers AS $container => $unused)
					{
						$window = $player->ManiaFWK->getWindow('MLContainer'.$container);
						if($window) {
							$window->Reset();
							Plugins::triggerEvent('onMLContainer'.$container, array($player, $window));
						}
					}
				}

				//Get XML
				$xml = $player->ManiaFWK->getXml();
				if($xml) {
					Core::getObject('gbx')->query('SendDisplayManialinkPageToLogin', $player->Login, $xml, 0, false);
				}
			}
		}
		$this->containers = array();
	}

	/**
	* Sets a container's status to "need rendering"
	*
	* @param string $name
	* @param rcp_player Object $instant
	* @author hal.sascha
	*/
	public function updateContainer($name, $instant = false)
	{
		//Updates the container for just a single player instantly
		if($instant && Core::getObject('players')->check($instant)) {
			$window = $instant->ManiaFWK->getWindow('MLContainer'.$name);
			if($window) {
				$window->Reset();
				Plugins::triggerEvent('onMLContainer'.$name, array($instant, $window));
			}
		} else {
			//Flags the container to be updated for all players
			$this->containers[$name] = false;
		}
	}
}

define('ML_STATIC'   , 3);
define('ML_MAXIMIZED', 2);
define('ML_MINIMIZED', 1);
define('ML_CLOSED'   , 0);
class rcp_maniafwk
{
	public  $windows;
	private $hidden;
	private $customui;
	private $customuic;
	private $windowallowedoptions;
	private $windowstyleregions;
	public  $player;

	public function loadPlayerObject(&$player)
	{
		$this->player =& $player;
	}

	public function __construct()
	{
		$this->hidden   = false;
		$this->windows  = array();

		$this->customuic = true;
		$this->customui  = array(
			'notice'			=> 'true',
			'challenge_info'	=> 'true',
			'chat'				=> 'true',
			'checkpoint_list'	=> 'true',
			'round_scores'		=> 'true',
			'scoretable'		=> 'true',
			'global'			=> 'true'
		);

		$this->windowallowedoptions = array(
			'icon',
			'title',
			'timeout',
			'static',
			'close',
			'header',
			'bg',
			'width',
			'posx',
			'posy',
			'lineheight',
			'display',
			'inspec'
		);

		$this->windowstyleregions = array(
			'label'		=> array('halign', 'valign', 'textid', 'url', 'autonewline', 'textcolor', 'textsize'),
			'quad'		=> array('bgcolor', 'image', 'imagefocus', 'style', 'substyle', 'url', 'manialink', 'maniazone', 'addplayerid', 'actionkey', 'scale'),
			'entry'		=> array('textsize', 'halign', 'valign', 'name', 'default'),
			'fileentry'	=> array('textsize', 'halign', 'valign', 'name', 'default'),
			'audio'		=> array('data', 'play', 'looping'),
			'music'		=> array('data'),
			'include'	=> array('url')
		);

		//Add Window for the action keys (F5,F6,F7)
		$window = $this->addWindow('ManiaFWKActionKeys', 'Mania Framework Action Key Window', 70, 70, 3);
		if($window) {
			$window->setOption('header', false);
			$window->setOption('static', true);
			$window->setOption('close', false);
			$window->Reset();
			$window->Line();
			$window->Cell('',1,'onMLAccesskey1',array('actionkey' => '1')); //F5
			$window->Cell('',1,'onMLAccesskey2',array('actionkey' => '2')); //F6
			$window->Cell('',1,'onMLAccesskey3',array('actionkey' => '3')); //F7
		}
	}

	public function __destruct()
	{
		foreach($this->windows AS $key => $window)
		{
			$this->windows[$key]->__destruct();
			$this->windows[$key] = null;
			unset($this->windows[$key]);
		}
	}

	/**
	* Returns the current ML xml
	*
	* @author hal.sascha
	*/
	public function getXml()
	{
		if(empty($this->windows)) {
			return false;
		}

		$body = '';
		foreach($this->windows AS $window)
		{
			//If hidden mode, render window with id 1
			//since this is the accesskey window and should be allways available
			if($window->id != 1 && $this->hidden) {
				break;
			}

			$xml = $window->getXml();
			if($xml) {
				$body .= $xml;
			}
		}

		if($body) {
			return "<manialinks>".$this->parseColors($body).$this->getCustomUi()."</manialinks>";
			//removed because of traffic reasons:
			/*<?xml version='1.0' encoding='utf-8' ?>*/
		}
		return false;
	}

	/**
	* Adds a new window to the framework
	*
	* This method will return a window-object if successfull
	* @param string $name
	* @param string $title
	* @param int $posx
	* @param int $posy
	* @param int $width
	* @author hal.sascha
	*/
	public function addWindow($name, $title, $posx, $posy, $width)
	{
		if(!array_key_exists($name, $this->windows)) {
			$this->windows[$name] = new rcp_maniawindow($this, count($this->windows)+1, $name, $title, $posx, $posy, $width);
			return $this->getWindow($name);
		}
		return false;
	}

	/**
	* Returns a window-object for the specified windowname
	*
	* @param string $name
	* @author hal.sascha
	*/
	public function getWindow($name)
	{
		if(array_key_exists($name, $this->windows)) {
			if(is_object($this->windows[$name])) {
				return $this->windows[$name];
			}
		}
		return false;
	}

	/**
	* Gets an callback array, if the submitted action was available
	*
	* @param int $action
	* @author hal.sascha
	*/
	public function getAction($action)
	{
		if(empty($this->windows)) {
			return false;
		}

		foreach($this->windows AS $window)
		{
			$result = $window->getAction($action);
			if($result) {
				$window->Close(true);
				return $result;
			}
		}
		return false;
	}

	/**
	* Closes all windows respecting it's closestatus
	*
	* If $id is specified the window with this id will be remain as open
	* @param int $id
	* @author hal.sascha
	*/
	public function closeWindows($id = false)
	{
		if(!empty($this->windows)) {
			foreach($this->windows AS $window)
			{
				if($window->id != $id && $window->status == ML_MAXIMIZED) {
					$window->Close();
				}
			}
		}
	}
	
	/**
	* Changes the status of the tm custom_ui
	*
	* @param string $element
	* @param boolean $value
	*/
	public function setCustomUi($element, $value = true)
	{
		if(!array_key_exists($element, $this->customui)) return false;
		$this->customui[$element] = $value ? 'true' : 'false';
		$this->customuic = true;
		return true;
	}

	/**
	* Returns xml for the tm custom_ui
	*
	*/
	public function getCustomUi()
	{
		if(!$this->customuic) return '';
		$this->customuic = false;
		return "<custom_ui>
					<notice visible='{$this->customui['notice']}'/>
					<challenge_info visible='{$this->customui['challenge_info']}'/>
					<chat visible='{$this->customui['chat']}'/>
					<checkpoint_list visible='{$this->customui['checkpoint_list']}'/>
					<round_scores visible='{$this->customui['round_scores']}'/>
					<scoretable visible='{$this->customui['scoretable']}'/>
					<global visible='{$this->customui['global']}'/>
				</custom_ui>";
	}

	/**
	* Parses all framework related colors
	*
	* @param string $string
	* @author hal.sascha
	*/
	private function parseColors($string)
	{
		$string = str_replace('!df!',  (string) Core::getObject('settings')->live->colors->chat->default, $string);
		$string = str_replace('!hl!',  (string) Core::getObject('settings')->live->colors->chat->highlight, $string);
		$string = str_replace('!si!',  (string) Core::getObject('settings')->live->colors->chat->subitem, $string);
		$string = str_replace('!fhl!', (string) Core::getObject('settings')->live->colors->ml->fonts->highlight, $string);
		$string = str_replace('!fsi!', (string) Core::getObject('settings')->live->colors->ml->fonts->subitem, $string);
		return $string;
	}

	/**
	* Returns the allowed options for a window as array
	*
	* @author hal.sascha
	*/
	public function getWindowAllowedOptions()
	{
		return $this->windowallowedoptions;
	}

	/**
	* Returns the available style regions for a window as array
	*
	* @author hal.sascha
	*/
	public function getWindowStyleRegions()
	{
		return $this->windowstyleregions;
	}

	/**
	* Returns if the framework is currently hidden
	*
	* @param void
	* @return boolean
	* @author hal.sascha
	*/
	public function isHidden()
	{
		return $this->hidden;
	}

	/**
	* Toggles the frameworks hidden status
	*
	* @param string $login
	* @author hal.sascha
	*/
	public function toggleHidden($login)
	{
		//Refresh action keys window
		$window = $this->getWindow('ManiaFWKActionKeys');
		if(!$window) return;
		$window->Refresh();

		//Toggle hidden status
		if($this->hidden) {
			$this->hidden = false;
			//RePaint the framework
			$this->rePaint();
		} else {
			$this->hidden = true;
			//Remove manialinks
			Core::getObject('gbx')->query('SendHideManialinkPageToLogin', $login);
		}
	}

	/**
	* Refreshes all static and maximized windows
	*
	* @param void
	* @author hal.sascha
	*/
	private function rePaint()
	{
		if(empty($this->windows)) return;
		$this->customuic = true;
		foreach($this->windows AS $window)
		{
			if($window->status == ML_MAXIMIZED || $window->status == ML_STATIC) {
				$window->Refresh();
			}
		}
	}
}

class rcp_maniawindow
{
	private $base;
	public  $id;
	public  $name;
	public  $status;
	private $title;
	private $posx;
	private $posy;
	private $width;
	private $margin;
	private $actions;
	private $header;
	private $bg;
	private $static;
	private $close;
	private $timeout;
	private $timeoutt;
	private $icon;
	private $rendered;
	private $lheight;
	private $lineheight;
	private $display;
	private $displayls;
	private $inspec;
	private $inspecls;
	private $frames;
	private $cframe;
	private $ccframe;
	private $hidden;

	public function __construct(&$base, $id, $name, $title, $posx, $posy, $width)
	{
		$this->base			=& $base;
		$this->id			= $id;
		$this->name			= $name;
		$this->title		= $title;
		$this->posx			= $posx;
		$this->posy			= $posy;
		$this->width		= $width;
		$this->margin		= 0.5;
		$this->frames		= array();
		$this->cframe		= 0;
		$this->ccframe		= false;
		$this->hidden		= false;

		//Default options
		$this->status		= ML_CLOSED;
		$this->icon			= 'Forever';
		$this->actions		= array();
		$this->header		= true;
		$this->bg			= 'main';
		$this->static		= false;
		$this->close		= true;
		$this->timeout		= false;
		$this->timeoutt		= false;
		$this->rendered		= true;
		$this->lheight		= 0;
		$this->lineheight	= 3;
		$this->display		= 'all';
		$this->displayls	= true;
		$this->inspec		= true;
		$this->inspecls		= true;
	}

	public function __destruct()
	{
		$this->base = null;
		unset($this->base);
	}

	/**
	* Changes the specified window option
	*
	* @param string $option
	* @param mixed $value
	* @author hal.sascha
	*/
	public function setOption($option, $value)
	{
		if(in_array($option, $this->base->getWindowAllowedOptions()) && isset($this->{$option})) {
			$this->{$option} = $value;
			return true;
		}
		return false;
	}

	/**
	* Returns if the window is currently hidden
	*
	* @param void
	* @return boolean
	* @author hal.sascha
	*/
	public function isHidden()
	{
		return $this->hidden;
	}

	/**
	* Toggles the window hidden status
	*
	* @param string $login
	* @author hal.sascha
	*/
	public function toggleHidden()
	{
		//Toggle hidden status
		if($this->hidden) {
			$this->hidden = false;
			$this->Refresh();
		} else {
			$this->hidden = true;
			$this->Close();
		}
	}

	/**
	* Refresh the window
	*
	* this doesn't create new xml code, it marks the window as 'not rendered', restarts the timeout and some other stuff.
	* if you need to change the windows position or something, use the Reset command!
	* But be aware, you'll need to rebuild the Frame/Line/Cell data too.
	*
	* @author hal.sascha
	*/
	public function Refresh()
	{
		//Mark as "has to be rendered"
		$this->rendered = false;

		//Window is static (possibly with timeout)  or maximized
		if($this->static || $this->timeout) {
			$this->setStatus(ML_STATIC);
		} else {
			$this->setStatus(ML_MAXIMIZED);
		}

		//Timeout time
		$this->timeoutt = $this->timeout ? Core::getSetting('time') + $this->timeout : false;

		//Close other window(s)
		if($this->status == ML_MAXIMIZED) {
			$this->base->closeWindows($this->id);
		}
	}

	/** 
	* Reset and refresh the window
	*
	* @author hal.sascha
	*/
	public function Reset()
	{
		$this->xml		= '';
		$this->actions	= array();
		$this->frames	= null;
		$this->frames	= array();
		$this->cframe	= 0;
		$this->ccframe	= false;
		$this->Refresh();
	}

	/**
	* Sets the status of the window
	*
	* @author hal.sascha
	*/
	private function setStatus($status)
	{
		$this->status = $status;
	}

	/**
	* Returns an action-array or executes the default closeaction
	*
	* @author hal.sascha
	*/
	public function getAction($action)
	{
		if(array_key_exists($action, $this->actions)) {
			if($this->actions[$action]['call'] == 'on'.$this->name.'Close') {
				return $this->Close();
			} else {
				return $this->actions[$action];
			}
		}
		return false;
	}

	/**
	* Adds a new action
	*
	* @param string $callback
	* @param string $plugin
	* @param mixed $params
	* @author hal.sascha
	*/
	private function addAction($callback, $plugin = false, $params = false)
	{
		$id = $this->id.count($this->actions);
		$this->actions[$id] = array(
			'call'	=> ($plugin) ? array($callback, $plugin) : $callback,
			'param'	=> $params
		);
		return $id;
	}

	/**
	* Flags the window as closed
	*
	* @param boolean $behavior
	* @author hal.sascha
	*/
	public function Close($behavior = false)
	{
		if($behavior && $this->close || !$behavior) {
			$this->timeoutt = false;
			$this->rendered = false;
			$this->setStatus(ML_MINIMIZED);
			return true;
		}
		return false;
	}

	/**
	* Checks the window for timeout
	*
	* @author hal.sascha
	*/
	private function checkTimeout()
	{
		if(!$this->timeout || !$this->timeoutt) return false;
		return ($this->timeoutt < Core::getSetting('time')) ? true : false;
	}

	/**
	* Checks if the window will be displayed, if the player is currently in spectator mode
	*
	* @author hal.sascha
	*/
	private function checkSpecStatus()
	{
		return (!$this->inspec && $this->base->player->SpectatorStatus['Spectator']) ? true : false;
	}

	/**
	* Checks the window for the current gamestate against the dipslay value
	*
	* @author hal.sascha
	*/
	private function checkDisplayStatus()
	{
		if($this->display == 'all') {
			return false;
		}

		if($this->status == ML_MAXIMIZED || $this->status == ML_STATIC) {
			if($this->display == 'race' && !Core::getObject('status')->gamestate) {
				return true;
			} elseif($this->display == 'score' && Core::getObject('status')->gamestate) {
				return true;
			}
		}
		return false;
	}

	/**
	* Returns the xml for this window
	*
	* @author hal.sascha
	*/
	public function getXml()
	{
		if($this->isHidden()) {
			return false;
		//this constantly reload the windows xml, it's for testing purpose only
		//} elseif(empty($this->xml) && empty($this->frames)) {
			//$this->Close(); 
		}

		//Update window if specstate has changed since last check
		if(!$this->inspec && $this->base->player->SpectatorStatus['Spectator'] != $this->inspecls) {
			$this->Refresh();
			$this->inspecls = $this->base->player->SpectatorStatus['Spectator'];
		}

		//Update window if gamestate has changed since last check
		if($this->display != 'all' && Core::getObject('status')->gamestate != $this->displayls) {
			$this->Refresh();
			$this->displayls = Core::getObject('status')->gamestate;
		}

		//Check window stati
		if($this->rendered) {
			if($this->checkTimeout()) {
				$this->Close();
			} elseif($this->checkSpecStatus()) {
				$this->Close();
			} elseif($this->checkDisplayStatus()) {
				$this->Close();
			} else {
				return false;
			}
		}

		if($this->status == ML_CLOSED) {
			return false;
		} elseif($this->status == ML_MINIMIZED) {
			$this->setStatus(ML_CLOSED);
			$this->rendered = true;
			return "<manialink id='{$this->id}' />"; //removed because of traffic reason: </manialink>
		}

		//Return xml
		if($this->xml) {
			$this->rendered = true;
			return $this->xml;
		}

		//Generated xml
		$this->xml  = "<manialink id='{$this->id}'>"; //removed because of traffic reasons: <type>default</type>
		$this->xml .= "<frame posn='{$this->posx} {$this->posy} 0'><format". $this->applyStyles('format') ." />";
		$this->xml .= $this->getHeader();
		$this->xml .= $this->getFrames();
		$this->xml .= $this->getBg();
		$this->xml .= "</frame>";
		$this->xml .= "</manialink>";

		//Return xml
		$this->rendered = true;
		return $this->xml;
	}

	/**
	* Returns the header-xml for this window
	*
	* @author hal.sascha
	*/
	private function getHeader()
	{
		if(!$this->header) return '';
		$xml  = "<frame posn='0 0 9'>";
		$xml .= "<quad posn='-{$this->margin} 0 0' sizen='". ($this->width + ($this->margin*2)) ." 3'". $this->applyStyles('header', 'quad') ." />";
		$xml .= "<quad posn='-0.25 -0.3 1' sizen='2.5 2.5' style='Icons128x128_1' substyle='{$this->icon}' action='".$this->addAction('on'.$this->name.'Close')."' />";
		$xml .= "<quad posn='". ($this->width-2) ." -0.4 1' sizen='2 2' style='Icons64x64_1' substyle='Close' action='".$this->addAction('on'.$this->name.'Close')."' />";
		$xml .= "<label posn='2.5 -1.25 1' sizen='{$this->width} 0' text='{$this->title}'". $this->applyStyles('header') ." />";
		$xml .= "</frame>";
		return $xml;
	}

	/**
	* Returns the background-xml for this window
	*
	* @author hal.sascha
	*/
	private function getBg()
	{
		if(!$this->bg) return '';
		return "<frame posn='0 0 0'><quad posn='-{$this->margin} 0 0' sizen='". ($this->width + ($this->margin*2)) ." ". (abs($this->lheight)+$this->margin) ."'". $this->applyStyles($this->bg, 'quad') ." /></frame>";
	}

	/**
	* Opens a new frame
	*
	* @param string $name
	*/
	public function Frame($posx = false, $posy = false, $width = false, $styles = false, $mla = false, $align = true)
	{
		$this->cframe = count($this->frames) + 1;
		$this->frames[$this->cframe] = array(
			'posx'		=> ($posx)	? $posx		: 0,
			'posy'		=> ($posy)	? $posy		: 0,
			'width'		=> ($width)	? $width	: $this->width,
			'lines'		=> array(),
			'styles'	=> $styles,
			'action'	=> $this->getActionAttribute($mla),
			'align'		=> $align,
			'cline'		=> 0
		);
		return $this->cframe;
	}

	public function useCustomFrame($frameId)
	{
		if(array_key_exists($frameId, $this->frames)) {
			$this->ccframe = $frameId;
		}
	}

	public function resetCustomFrame()
	{
		$this->ccframe = false;
	}

	/**
	* Adds a new line to the window
	*
	* @param array $line
	* @author hal.sascha
	*/
	public function Line($styles = false, $mla = false)
	{
		//Check if at least default frame is available, else create it
		if(!$this->cframe) $this->Frame();

		//Get current frame
		$frame = ($this->ccframe !== false) ? $this->ccframe : $this->cframe;

		//Set current line for current frame
		$this->frames[$frame]['cline'] = count($this->frames[$frame]['lines']) + 1;

		//Get current line
		$line = $this->frames[$frame]['cline'];

		//Add line content
		$this->frames[$frame]['lines'][$line] = array(
			'cells'		=> array(),
			'customxml'	=> false,
			'styles'	=> $styles,
			'action'	=> $this->getActionAttribute($mla)
		);
	}

	/**
	* Adds a new cell to a line
	*
	* Cell($text, $width/$height[as array if height specified], $action/$param[as array if param specified], $attributes[as array]);
	* @param array $cell
	* @author hal.sascha
	*/
	public function Cell($text, $size, $mla = false, $attributes = false)
	{
		//Get current frame
		$frame = ($this->ccframe !== false) ? $this->ccframe : $this->cframe;

		//Get current line
		$line = $this->frames[$frame]['cline'];

		//Add cell content
		$this->frames[$frame]['lines'][$line]['cells'][] = array(
			'text'			=> specialchars($text),
			'size'			=> $size,
			'action'		=> $this->getActionAttribute($mla),
			'attributes'	=> $attributes,
			'hide'			=> (is_array($attributes) && array_key_exists('hidecell', $attributes) && $attributes['hidecell']) ? true : false
		);
	}

	/**
	* Adds custom XML code to the window
	*
	* If height isset, the system will respect the height and each later line will be use the new position offset
	* @param string $xml
	* @param int $height
	* @author hal.sascha
	*/
	public function CustomXML($xml, $height = false)
	{
		//Get current frame
		$frame = ($this->ccframe !== false) ? $this->ccframe : $this->cframe;

		//Get current line
		$line = $this->frames[$frame]['cline'];

		//Add custom content
		$this->frames[$frame]['lines'][$line]['customxml'] = array($xml, $height);
	}

	/**
	* Returns the xml for the window frames
	*
	* @author hal.sascha
	*/
	private function getFrames()
	{
		//Get Frames
		$fxml = '';
		$fx = 0;
		$fy = 0;
		$fh = 0;
		$fw = 0;
		foreach($this->frames AS $frame)
		{
			//Aligns
			// null  = horizontal
			// false = vertical
			// true  = relative (default)
			$fx = (is_null($frame['align'])) ? ($frame['posx'] + $fx) + $fw : $frame['posx'];
			$fy = (!$frame['align'] && !is_null($frame['align'])) ? ($frame['posy'] + $fy) - $fh : $frame['posy'];
			$fw = $frame['width'];
			$fh = 0;

			//Get Lines
			$lxml = '';
			$lx = 0;
			$ly = $this->header ? -3 : -0.5;
			$lw = $fw;
			$lh = $this->lineheight;
			$llc = false;
			foreach($frame['lines'] AS $line)
			{
				//Get Cells
				$cxml = '';
				$cx = 0;
				$cy = 0;
				$cw = 0;
				$ch = 0;
				$hch = 0;

				if(is_array($line['customxml'])) {
					$cxml	= $line['customxml'][0];
					$ch		= ($line['customxml'][1] !== false) ? $line['customxml'][1] : 0;
				}

				//Calculate margin(s)
				$margin = $this->getMargin($line['styles']);
				if($margin) {
					if($llc) {
						$lx = $llx;
						$lw = $llw;
						//$ly = $lly;
						//$lh = $llh;
					}

					$llx = $lx;
					$llw = $lw;
					//$lly = $ly;
					//$llh = $lh;
					$llc = true;

					$lx = $lx + $margin[0];					//left
					$lw = $lw - ($margin[0] + $margin[1]);	//right
					//$ly = $ly - $margin[2];					//top
					//$lh = $lh - $margin[2] - $margin[3];	//bottom
				}

				foreach($line['cells'] AS $cell)
				{
					//Calc size
					if(is_array($cell['size'])) {
						$cw = $this->getCellSize($cell['size'][0], $lw);
						$ch = $this->getCellSize($cell['size'][1], $lh);
					} else {
						$cw = $this->getCellSize($cell['size'], $lw);
						$ch = $lh;
					}
					$ch = ($hch < $ch) ? $ch : $hch;

					if(!$cell['hide']) {
						//Set Defaults
						$cell['attributes']['valign'] = !empty($cell['attributes']['valign']) ? $cell['attributes']['valign'] : 'center';

						//Create quad attributes
						$attributes = $this->applyStyles(true, 'quad', $cell['attributes']);
						if($attributes || !empty($cell['action'])) {
							$cxml .= "<quad posn='{$cx} {$cy} 1' sizen='{$cw} {$ch}'{$cell['action']}". implode('', $attributes) ." />";
						}

						//Element attributes
						switch($cell['attributes']['tagtype']) {
							default:
								if(!empty($cell['text'])) {
									$attributes	 = $this->applyStyles(true, 'label', $cell['attributes']);
									$nx			 = $this->getPosX($cx, $cw, $cell['attributes']['halign']);
									$ny			 = $this->getPosY($cy, $ch, $cell['attributes']['valign']);
									$cxml		.= "<label posn='{$nx} {$ny} 1' sizen='{$cw} {$ch}' text=' {$cell['text']} '". implode('', $attributes) ." />";
								}
							break;

							case 'entry':
								$attributes	 = $this->applyStyles(true, 'entry', $cell['attributes']);
								$nx			 = $this->getPosX($cx, $cw, $cell['attributes']['halign']);
								$ny			 = $this->getPosY($cy, $ch, 0);
								$cxml		.= "<entry posn='{$nx} {$ny} 1' sizen='{$cw} {$ch}'". implode('', $attributes) ." />";
							break;

							case 'fileentry':
								$attributes	 = $this->applyStyles(true, 'fileentry', $cell['attributes']);
								$nx			 = $this->getPosX($cx, $cw, $cell['attributes']['halign']);
								$ny			 = $this->getPosY($cy, $ch, 0);
								$cxml		.= "<fileentry posn='{$nx} {$ny} 1' sizen='{$cw} {$ch}'". implode('', $attributes) ." />";
							break;

							case 'audio':
								$attributes	 = $this->applyStyles(true, 'audio', $cell['attributes']);
								$ny			 = $this->getPosY($cy, $ch, 'center');
								$cxml		.= "<audio posn='{$cx} {$ny} 1' sizen='{$cw} {$ch}'". implode('', $attributes) ." />";
							break;

							case 'music':
								$attributes	 = $this->applyStyles(true, 'music', $cell['attributes']);
								$cxml		.= "<music". implode('', $attributes) ." />";
							break;

							case 'include':
								$attributes	 = $this->applyStyles(true, 'include', $cell['attributes']);
								$cxml		.= "<include". implode('', $attributes) ." />";
							break;
						}
					}

					//Next cell position
					$cx  = $cx + $cw;
					$hch = $ch;
				}

				//Cells XML generated
				//Generate Line XML
				$lh = $hch;
				if(is_array($line['styles']) || !empty($line['action'])) {
					$attributes	 = $this->applyStyles(true, 'quad', $line['styles']);
					$cxml		.= "<quad posn='0 0 0' sizen='{$lw} {$lh}'{$line['action']}". implode('', $attributes) ." />";
				}
				$lxml .= "<frame posn='{$lx} {$ly} 1'>{$cxml}</frame>";
				//Next line position
				$ly = $ly - $lh;
			}

			//Lines XML generated
			$fh = abs($ly-$this->margin);
			$this->lheight = $ly;

			//Generate Frame XML
			if(is_array($frame['styles']) || !empty($frame['action'])) {
				$attributes	 = $this->applyStyles(true, 'quad', $frame['styles']);
				$lxml		.= "<quad posn='0 0 0' sizen='{$fw} {$fh}'{$frame['action']}". implode('', $attributes) ." />";
			}
			$fxml .= "<frame posn='{$fx} {$fy} 1'>{$lxml}</frame>";
		}

		//Frames XML generated
		//Reset frame data
		$this->frames = null;
		$this->frames = array();
		//Return Frames XML
		return $fxml;
	}

	/**
	* Returns a parsable action attribute string
	*
	* @param mixed $mla
	* @author hal.sascha
	* @examples for $mla:
	*		string format:
	*			'onMLWhatever'
	*		array format with plugin:
	*			array(array('onMLWhatever', 'plugin'))
	*		array format with plugin and params:
	*			array(array('onMLWhatever', 'plugin'), array('params1', 'param2', 'param3'))
	*		array format without plugin, but with params:
	*			array('onMLWhatever', array('params1', 'param2', 'param3'))
	*/
	private function getActionAttribute($mla)
	{
		//Check if action has params
		if(is_array($mla)) {
			if(is_array($mla[0])) {
				$action = $mla[0][0];
				$plugin = $mla[0][1];
			} else {
				$action = $mla[0];
				$plugin = false;
			}
			$params = empty($mla[1]) ? false : $mla[1];
		} else {
			$action = $mla;
			$plugin = false;
			$params = false;
		}
		return !empty($action) ? " action='".$this->addAction($action, $plugin, $params)."'" : '';
	}

	/**
	* Returns the x position for a label respecting it's align
	*
	* @param int $posx
	* @param int $width
	* @param string $align
	* @author hal.sascha
	*/
	private function getPosX($posx, $width, $align = false)
	{
		if(!empty($align)) {
			if($align == 'center') {
				return $posx + ($width / 2);
			} elseif($align == 'right') {
				return $posx + $width;
			}
		}
		return $posx;
	}

	/**
	* Returns the y position for a label respecting it's align
	*
	* @param int $posy
	* @param int $height
	* @param string $align
	* @author hal.sascha
	*/
	private function getPosY($posy, $height, $align = false, $offset = 0.20)
	{
		if(!empty($align)) {
			if($align == 'center') {
				return $posy - ($height / 2) + $offset;
			} elseif($align == 'bottom') {
				return $posy - $height + $offset;
			}
		}
		return $posy;
	}

	/**
	* Returns a style definition from live.xml setting file
	*
	* @param string $name
	* @author hal.sascha
	*/
	private function applyStyles($name, $region = 'label', &$attributes = false)
	{
		$styleregions = $this->base->getWindowStyleRegions();

		if($name === true) {
			//applies all styles from a styleset + the delivered external styles and overwrite the class
			$return = array();
			if(is_array($attributes) && array_key_exists($region, $styleregions)) {
				foreach($styleregions[$region] AS $attribute)
				{
					if(array_key_exists($attribute, $attributes)) {
						$return[$attribute] = " {$attribute}='{$attributes[$attribute]}'";
						continue; //jumps over class check, it's like overwriting the class value
					}

					//rcp only styles
					if(!empty($attributes['class'])) {
						$value = $this->getStyle($attributes['class'], $attribute);
						if($value) {
							$return[$attribute] = " {$attribute}='{$value}'";
							$attributes[$attribute] = $value;
						}
					}
				}
			}
			return $return;
		} else {
			//applies all styles from a styleset
			$return = '';
			if(array_key_exists($region, $styleregions)) {
				foreach($styleregions[$region] AS $attribute)
				{
					$value = $this->getStyle($name, $attribute);
					if($value) {
						$return .= " {$attribute}='{$value}'";
						$attributes[$attribute] = $value;
					}
				}
			}
			return $return;
		}
	}

	/**
	* Returns a style string by class and attribute
	*
	* @param string $name
	* @param string $attribute
	* @author hal.sascha
	*/
	private function getStyle($name, $attribute)
	{
		return isset(Core::getObject('settings')->live->colors->ml->styles->{$name}[$attribute]) ? (string) Core::getObject('settings')->live->colors->ml->styles->{$name}[$attribute] : false;
	}

	/**
	* Returns Manialink size value, respecting the rcp internal percentual values
	*
	* @param mixed $value
	* @param float $linesize
	* @author hal.sascha
	*/
	private function getCellSize($value, $linesize) {
		if(is_string($value)) {
			$value = (int) str_replace('%', '', $value);
			$value = !empty($value) ? $linesize / 100 * $value : 0;
		}
		return $value;
	}

	/**
	* Returns a array with margin values from attributes
	*
	* @param array $attributes
	* @author hal.sascha
	*/
	private function getMargin($attributes)
	{
		if(is_array($attributes['margin'])) {
			$ml = array_key_exists(0, $attributes['margin']) ? $attributes['margin'][0] : 0;
			$mr = array_key_exists(1, $attributes['margin']) ? $attributes['margin'][1] : 0;
			$mt = array_key_exists(2, $attributes['margin']) ? $attributes['margin'][2] : 0;
			$mb = array_key_exists(3, $attributes['margin']) ? $attributes['margin'][3] : 0;
			return array($ml, $mr, $mt, $mb);
		}
		return false;
	}
}
?>