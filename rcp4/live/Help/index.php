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
class Help extends rcp_liveplugin
{
	public  $title		= 'Help';
	public  $author		= 'hal.ko.sascha';
	public  $version	= '4.0.3.6';
   
	public function onLoad()
	{
		$help = Plugins::getPlugin('Menu')->menu->add('help', 'Help', 'Puzzle', 99);
		if($help !== false) {
			$help->add('chatcmd', 'Chat', false, 1, 'onMLHelp');
		}
	}

	public function onMLHelp($params)
	{
		$window = $params[0]->ManiaFWK->getWindow('MLWindow');
		if(!$window || empty(Core::getObject('chat')->chatcommands)) return;
		$window->setOption('title', 'Help');
		$window->setOption('icon', 'Puzzle');
		$window->Reset();

		$max = 0;
		foreach(Core::getObject('chat')->chatcommands AS $value)
		{
			if($value['admin']) {
				if(Core::getObject('live')->isAdmin($params[0]->Login,true)) {
					++$max;
				}
			} else {
				++$max;
			}
		}

		$entries= 19;
		$index	= ($params[1]+0 < 0) ? 0 : $params[1]+0;
		$index	= ($index >= $max) ? $max - $entries : $index;
		$end	= $index+$entries;
		$end	= ($end >= $max) ? $max : $end;

		$i = 0;
		foreach(Core::getObject('chat')->chatcommands AS $key => $value)
		{
			if($end <= $i) break;
			if($i >= $index) {
				if($value['admin'] && Core::getObject('live')->isAdmin($params[0]->Login, true)) {
					$window->Line();
					$window->Cell('/'.$value['command'], '25%', array('onMLHelpDetails', array($key,$index)));
					$window->Cell($value['help'], '75%', array('onMLHelpDetails', array($key,$index)));
				} else {
					$window->Line();
					$window->Cell('/'.$value['command'], '25%', array('onMLHelpDetails', array($key,$index)));
					$window->Cell($value['help'], '75%', array('onMLHelpDetails', array($key,$index)));
				}
			}
			++$i;
		}

		$prev = ($index <= 0) ? false : true;
		$next = ($index >= ($max-$entries)) ? false : true;
		if($prev || $next) {
			$window->Line();
			$window->Cell('previous', '25%', array('onMLHelp',$index-$entries), array('hidecell' => !$prev,'class' => 'btn2n'));
			$window->Cell('', '50%');
			$window->Cell('next', '25%', array('onMLHelp',$index+$entries), array('hidecell' => !$next,'class' => 'btn2n'));
		}
	}

	public function onMLHelpDetails($params)
	{
		$window = $params[0]->ManiaFWK->getWindow('MLWindow');
		if(!$window) return;
		$window->setOption('title', 'Help details');
		$window->setOption('icon', 'Puzzle');
		$window->Reset();

		$data = Core::getObject('chat')->chatcommands[$params[1][0]];
		$window->Line();
		$window->Cell('Command:', '30%');
		$window->Cell($data['command'], '70%');
		$window->Line();
		$window->Cell('Admin:', '30%');
		$window->Cell($data['admin'] ? 'yes' : 'no', '70%');

		if(is_array($data['example'])) {
			foreach($data['example'] AS $key => $example)
			{
				$window->Line();
				$window->Cell('Example #'. ($key+1) .':', '30%');
				$window->Cell($example ? $example : 'not available', '70%');
			}
		} else {
			$window->Line();
			$window->Cell('Example:', '30%');
			$window->Cell($data['example'] ? $data['example'] : 'not available', '70%');
		}
		$window->Line();
		$window->Cell($data['help'], '100%', null, array('autonewline' => '1'));
		$window->Line();
		$window->Cell('main menu', '100%', array('onMLHelp',$params[1][1]), array('class' => 'btn2n'));
	}
}
?>