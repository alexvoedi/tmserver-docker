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
class rcp_menu
{
	private $menu;

	public function create($id, $title, $icon = false)
	{
		$this->menu = new rcp_menu_option($id, $title, $icon);
		if($this->menu->getError()) {
			$this->menu = null;
			unset($this->menu);
			return false;
		}
		return $this->menu;
	}

	public function validate()
	{
		$this->menu->sort();
		$this->menu->validate(1, 1);
	}

	public function render($player)
	{
		$window = $player->ManiaFWK->getWindow('MLMenu');
		if(!$window) return;
		$window->Reset();
		$this->menu->render($player, $window);
	}

	public function getLevelById($uniqueid)
	{
		return $this->menu->getLevelById($uniqueid);
	}

	public function handleClick($player, $uniqueid)
	{
		//Toggle open status
		if(is_array($player->cdata['menu']) && array_key_exists($uniqueid, $player->cdata['menu'])) {
			$level = $player->cdata['menu'][$uniqueid];
			unset($player->cdata['menu'][$uniqueid]);
		} else {
			$player->cdata['menu'][$uniqueid] = $this->getLevelById($uniqueid);
			$level = $player->cdata['menu'][$uniqueid];
		}

		//Remove everything with a higher level as the current
		if(empty($player->cdata['menu'])) return;
		$array = array();
		foreach($player->cdata['menu'] AS $key => $value)
		{
			if($key == $uniqueid || $value < $level) {
				$array[$key] = $value;
			}
		}
		$player->cdata['menu'] = $array;
	}
}

class rcp_menu_option
{
	private $id;
	private $uniqueid;
	private $title;
	private $icon;
	private $sort;
	private $callback;
	private $params;
	private $admin;
	private $options;
	private $level;
	private $numberP;
	private $numberA;
	private $clickable;
	private $seperator;
	private $error;

	public function __construct($id, $title, $icon = false, $sort = 0, $callback = false, $params = false, $admin = false)
	{
		$this->id			= $id;
		$this->uniqueid		= false;
		$this->title		= $title;
		$this->icon			= $icon;
		$this->sort			= $sort;
		$this->callback		= $callback;
		$this->params		= $params;
		$this->admin		= $admin;
		$this->options		= array();
		$this->level		= false;
		$this->numberP		= false;
		$this->numberA		= false;
		$this->clickable	= false;
		$this->seperator	= false;
		$this->error		= false;

		//Check for invalid data
		if(empty($this->title)) {
			$this->error = true;
		}
	}

	public function getError()
	{
		return $this->error;
	}

	public function get($id)
	{
		return array_key_exists($id, $this->options) ? $this->options[$id] : false;
	}

	public function add($id, $title, $icon = false, $sort = 0, $callback = false, $params = false, $admin = false)
	{
		//do not overwrite if the added menu-option allready exist
		if(array_key_exists($id, $this->options)) return $this->options[$id];

		//create new option
		$this->options[$id] = new rcp_menu_option($id, $title, $icon, $sort, $callback, $params, $admin);
		if($this->options[$id]->getError()) {
			$this->options[$id] = null;
			unset($this->options[$id]);
			return false;
		}
		return $this->options[$id];
	}

	public function addSeperator($sort = 0)
	{
		static $seperators = 0;
		$option = $this->add('sep'.$seperators, ' ', false, $sort);
		$option->seperator = true;
		++$seperators;
	}

	public function sort()
	{
		if(empty($this->options)) return;
		usort($this->options, array($this, 'sortOptions'));
		foreach($this->options AS $option)
		{
			$option->sort();
		}
	}

	public function validate($uniqueid, $level, $iP = 0, $iA = 0)
	{
		//Set level
		$this->level = $level;

		//Set uniqueid
		$this->uniqueid = $uniqueid;

		//Set clickable state
		$this->clickable = (!empty($this->callback) || !empty($this->options)) ? true : false;

		//Setup suboptions
		if(!empty($this->options)) {
			foreach($this->options AS $key => $option)
			{
				$option->numberP = $iP;
				$option->numberA = $iA;
				++$uniqueid;
				$uniqueid = $option->validate($uniqueid, $level+1, $iP, $iA);
				if(!$option->admin) ++$iP;
				++$iA;
			}
		}
		return $uniqueid;
	}

	public function render($player, $window, $frameId = false)
	{
		//Check admin permission
		$number = (Core::getObject('live')->isAdmin($player->Login, true)) ? $this->numberA : $this->numberP;
		if($this->admin && !Core::getObject('live')->isAdmin($player->Login, true)) return;

		//Create main button frame
		if($frameId === false) {
			$frameId = $window->Frame(0, 0, 14, false, false, null);
		}

		$window->useCustomFrame($frameId);
		$this->renderOption($player, $window);
		$window->resetCustomFrame();

		//Render suboptions, if currently open
		if(!empty($this->options) && is_array($player->cdata['menu']) && array_key_exists($this->uniqueid, $player->cdata['menu'])) {
			//Render further options
			$frameId = $window->Frame(0, 0-($number*3), 11, false, false, null);
			foreach($this->options AS $key => $option)
			{
				$option->render($player, $window, $frameId);
			}
		}
	}

	private function renderOption($player, $window)
	{
		if($this->clickable) {
			$window->Line(array('class' => 'btn1n'), array(
				'onMLAMenu',
				($this->callback) ? array($this->uniqueid, $this->callback, $this->params) : $this->uniqueid
			));
		} else {
			$window->Line(array('class' => 'btn1n'));
		}

		$height = ($this->seperator) ? 1.5 : 3;
		if($this->icon) {
			if(is_array($this->icon)) {
				$window->CustomXML("<quad posn='0 -0.25 9' sizen='2.5 2.5' style='{$this->icon[0]}' substyle='{$this->icon[1]}' />");
			} else {
				$window->CustomXML("<quad posn='0 -0.25 9' sizen='2.5 2.5' style='Icons128x128_1' substyle='{$this->icon}' />");
			}
			$widthA = array(2.5, $height);
			$widthB = array(7.5, $height);
		} else {
			$widthA = array(0.5, $height);
			$widthB = array(9.5, $height);
		}

		$window->Cell('', $widthA);
		$window->Cell($this->title, $widthB);
	}

	public function getLevelById($id)
	{
		$level = false;
		if($this->uniqueid == $id) {
			return $this->level;
		}

		if(!empty($this->options)) {
			foreach($this->options AS $option)
			{
				$level = $option->getLevelById($id);
				if($level !== false) break;
			}
		}
		return $level;
	}

	private function sortOptions($a, $b)
	{
		if($a->sort == $b->sort) return 0;
		return ($a->sort < $b->sort) ? -1 : 1;
	}
}
?>