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
class rcp_messages
{
	/**
	 * Array of message strings
	 */
	private $msgs = array();

	/**
	* Adds a new message to the messagelist
	*
	* @param string $text
	* @param boolean $type (false = message; true = error)
	* @author hal.sascha
	*/
	public function add($text, $type = false)
	{
		$this->msgs[] = array(
			'type' => $type,
			'text' => $text
		);
	}

	/**
	* Outputs the message-list to the browser
	*
	* @author hal.sascha
	*/
	public function getAll()
	{
		if(empty($this->msgs)) return '';

		if(Core::getSetting('live')) {
			foreach($this->msgs AS $value)
			{
				$prefix = $value['type'] ? '[X]' : '';
				echo "{$prefix} {$value['text']}\r\n";
			}
			$this->msgs = array();
		} else {
			$html = '';
			foreach($this->msgs AS $value)
			{
				$class = $value['type'] ? 'error' : 'info';
				$html .= "<li class='{$class}'>{$value['text']}</li>\r\n";
			}
			$this->msgs = array();
			return "<div class='messages'><ul>{$html}</ul></div>\r\n";
		}
	}
}
?>