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
class PPlugins extends rcp_liveplugin
{
	public  $title		= 'PPlugins';
	public  $author		= 'hal.ko.sascha';
	public  $version	= '4.0.3.6';

	public function onLoad()
	{
		$help = Plugins::getPlugin('Menu')->menu->add('help', 'Help', 'Puzzle', 99);
		if($help !== false) {
			$help->add('plugins', 'Plugins', false, 2, 'onChatpplugins');
		}
		Core::getObject('chat')->addCommand('plugins', 'onChatpplugins', 'Shows all currently running remoteCP-Live plugins', '/plugins');
	}

	public function onChatpplugins($cmd)
	{
		$this->onMLPPlugins(array($cmd[0]));
	}

	public function onMLPPlugins($params)
	{
		$window = $params[0]->ManiaFWK->getWindow('MLWindow');
		if(!$window) return;
		$window->setOption('title', 'Plugins');
		$window->setOption('icon', 'United');
		$window->Reset();

		$entries= 18;
		$max	= count(Plugins::getAllPlugins());
		$index	= ($params[1]+0 < 0) ? 0 : $params[1]+0;
		$index	= ($index >= $max) ? $max - $entries : $index;
		$end	= $index+$entries;
		$end	= ($end >= $max) ? $max : $end;

		$window->Line(array('class' => 'thead'));
		$window->Cell('Name', '40%');
		$window->Cell('Author', '30%');
		$window->Cell('Version', '30%', null, array('halign' => 'center'));
		$i = 0;
		foreach(Plugins::getAllPlugins() AS $obj)
		{
			if($end <= $i) break;
			if($i >= $index) {
				$window->Line();
				$window->Cell($obj->title, '40%');
				$window->Cell($obj->author, '30%');
				$window->Cell($obj->version, '30%', null, array('halign' => 'center'));
			}
			++$i;
		}

		$prev = ($index <= 0) ? false : true;
		$next = ($index >= ($max-$entries)) ? false : true;
		if($prev || $next) {
			$window->Line();
			$window->Cell('previous', '25%', array('onMLPPlugins',$index-$entries), array('hidecell' => !$prev,'class' => 'btn2n'));
			$window->Cell('', '50%');
			$window->Cell('next', '25%', array('onMLPPlugins',$index+$entries), array('hidecell' => !$next,'class' => 'btn2n'));
		}
	}
}
?>