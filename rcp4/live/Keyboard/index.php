<?php
/**
* remoteCP 4
* ütf-8 release
* DO NOT REMOVE OR CHANGE THIS PLUGIN!
*
* @package remoteCPlive
* @author hal.sascha
* @copyright (c) 2006-2009
* @version 4.0.3.6
*/
class Keyboard extends rcp_liveplugin
{
	public    $title	= 'Keyboard';
	public    $author	= 'hal.ko.sascha';
	public    $version	= '4.0.3.6';
	protected $enabled	= null;
	protected $active	= null;

	public function onNewPlayer($player)
	{
		$window = $player->ManiaFWK->addWindow('MLKeyboard', 'Keyboard', -17.5, 37, 35);
		if($window) {
			$window->setOption('header', false);
			$window->setOption('bg', false);
			$window->setOption('icon', 'ShareBlink');
		}

		$player->cdata[$this->id] = array();
		$player->cdata[$this->id]['value'] = '';
		$player->cdata[$this->id]['submitted'] = false;
		$player->cdata[$this->id]['callback'] = false;

		Core::getObject('chat')->addCommand('keyboard', 'onChatKeyboard', 'debugging chat command', '/keyboard');
	}

	public function onChatKeyboard($cmd)
	{
		$this->openKeyboard($cmd[0]);
	}

	public function onMLKeyboard($params)
	{
		$window = $params[0]->ManiaFWK->getWindow('MLKeyboard');
		if(!$window) return;

		//Get data
		$window->Reset();
		$window->Frame(0, 0, 30, false, false, null);

		$window->Line();
		$keys = array('1', '2', '3', '4', '5', '6', '7', '8', '9', ' 0 ');
		foreach($keys AS $key)
		{
			$window->Cell($key, array(3,3), array('onMLAKeyboard', $key), array('class' => 'btn2n'));
		}

		$window->Line();
		$keys = array('Q', 'W', 'E', 'R', 'T', 'Z', 'U', 'I', 'O', 'P');
		foreach($keys AS $key)
		{
			$window->Cell($key, array(3,3), array('onMLAKeyboard', $key), array('class' => 'btn2n'));
		}

		$window->Line();
		$keys = array('A', 'S', 'D', 'F', 'G', 'H', 'J', 'K', 'L', '_');
		foreach($keys AS $key)
		{
			$window->Cell($key, array(3,3), array('onMLAKeyboard', $key), array('class' => 'btn2n'));
		}

		$window->Line();
		$keys = array('Y', 'X', 'C', 'V', 'B', 'N', 'M', ',', '.', '-');
		foreach($keys AS $key)
		{
			$window->Cell($key, array(3,3), array('onMLAKeyboard', $key), array('class' => 'btn2n'));
		}

		$window->Frame(0, 0, 5, false, false, null);
		$window->Line();
		$window->Cell('Blank', array(5, 3), array('onMLAKeyboard', 'blank'), array('class' => 'btn2n'));
		$window->Line();
		$window->Cell('$$', array(2.5, 3), array('onMLAKeyboard', 'dollar'), array('class' => 'btn2n'));
		$window->Cell('*', array(2.5, 3), array('onMLAKeyboard', 'wildcard'), array('class' => 'btn2n'));
		$window->Line();
		$window->Cell('<--', array(5, 3), array('onMLAKeyboard', 'backspace'), array('class' => 'btn2n'));
		$window->Line();
		$window->Cell('Enter', array(5, 3), array('onMLAKeyboard', 'enter'), array('class' => 'btn2n'));

		$window->Frame(0, 0, 35, array('class' => 'btn1n'), false, false);
		$window->Line();
		$window->Cell($this->getPlayerInput($params[0]), '100%');
	}

	public function onMLAKeyboard($params)
	{
		$player = $params[0];
		$input  = trim($params[1]);

		//Handle special keys
		switch($input)
		{
			case 'blank':
				$input = ' ';
			break;

			case 'dollar':
				$input = '$';
			break;

			case 'wildcard':
				$input = '*';
			break;

			case 'backspace':
				$input = '';
				//Remove last char
				$length = strlen($player->cdata[$this->id]['value']) - 1;
				$player->cdata[$this->id]['value'] = substr($player->cdata[$this->id]['value'], 0, $length);
			break;

			case 'enter':
				//submit value
				Core::getObject('chat')->send('Keyboard submitted value: '. $this->getPlayerInput($player));

				//call callback
				if($player->cdata[$this->id]['callback']) {
					Plugins::triggerEvent($player->cdata[$this->id]['callback'], array($player, 'kbrtrn'));
				}

				//Reset
				$player->cdata[$this->id]['value'] = '';
				$player->cdata[$this->id]['submitted'] = true;
				$player->cdata[$this->id]['callback'] = false;

				//Cancel before handling normal keys
				return;
			break;
		}

		//Handle normal keys
		$player->cdata[$this->id]['value'] = $player->cdata[$this->id]['value'].$input;
		$player->cdata[$this->id]['submitted'] = false;

		//Render keyboard
		$this->onMLKeyboard(array($player));
	}

	/**
	* Opens the keyboard for the defined player
	*
	* @params rcp_player $player
	*/
	public function openKeyboard($player, $callback = false)
	{
		$this->onMLKeyboard(array($player));
		$player->cdata[$this->id]['callback'] = $callback;
	}

	/**
	* Returns the current keyboard input for the defined player
	*
	* @params rcp_player $player
	*/
	public function getPlayerInput($player)
	{
		return $player->cdata[$this->id]['value'];
	}
}
?>