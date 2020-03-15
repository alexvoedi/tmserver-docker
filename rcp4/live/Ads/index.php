<?php
/**
* remoteCP 4
* ütf-8 release
*
* @package remoteCPlive
* @author hal.sascha
* @copyright (c) 2006-2009
* @version 4.0.3.6
*/
class Ads extends rcp_liveplugin
{
	public  $title		= 'Ads';
	public  $author		= 'hal.ko.sascha';
	public  $version	= '4.0.3.6';
	private $ads		= array();
	private $reloadtime	= 10;
	private $lastadid	= false;

	public function onLoad()
	{
		Core::getObject('timedevents')->update('onMLAAds', '+'.$this->reloadtime.' minute', 0, 'onMLAAds');
	}

	public function onLoadSettings($settings)
	{
		$this->reloadtime = (int) $settings->reloadtime;
		$this->ads = array();
		foreach($settings->ads->children() AS $item)
		{
			$this->ads[] = array(
				'type'	=> (strtolower((string) $item['type']) == 'live') ? true : false,
				'text'	=> (string) $item
			);
		}
	}

	public function onMLAAds($params)
	{
		if(empty($this->ads)) return;
		$count = count($this->ads);
		while(true)
		{
			$key = rand(0, $count-1);
			if($count > 1 && $key == $this->lastadid) continue;
			if($this->ads[$key]['type']) {
				Core::getObject('live')->addMsg($this->ads[$key]['text']);
			} else {
				Core::getObject('chat')->send($this->ads[$key]['text']);
			}
			$this->lastadid = $key;
			break;
		}
	}
}
?>