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
class Training extends rcp_liveplugin
{
	public  $title		= 'Training';
	public  $author		= 'hal.ko.sascha';
	public  $version	= '4.0.3.6';
	private $maxtimes	= 10;

	public function onLoad()
	{
		Core::getObject('chat')->addCommand('training', 'onChattraining', 'Shows training mode option window', '/training');
	}

	public function onLoadSettings($settings)
	{
		$this->maxtimes = (int) $settings->maxtimes;
	}

	public function onBeginRace()
	{
		if(empty(Core::getObject('players')->players)) return;
		foreach(Core::getObject('players')->players AS $player)
		{
			if(array_key_exists($this->id, $player->cdata) && count($player->cdata[$this->id]['times']) && $player->cdata[$this->id]['enabled']) {
				$this->onMLATrainingReset(array($player), true);
			}
		}
	}

	public function onEndRace()
	{
		if(empty(Core::getObject('players')->players)) return;
		foreach(Core::getObject('players')->players AS $player)
		{
			if(array_key_exists($this->id, $player->cdata) && count($player->cdata[$this->id]['times']) && $player->cdata[$this->id]['enabled']) {
				$this->onMLTrainingTimes(array($player));
			}
		}
	}

	public function onPlayerFinish($params)
	{
		if(empty($params[2])) return;
		$player	= Core::getObject('players')->get($params[1]);
		$score	= $params[2];

		if(!Core::getObject('players')->check($player) || !array_key_exists($this->id, $player->cdata) || !$player->cdata[$this->id]['enabled']) return;
		$player->cdata[$this->id]['times'][] = $score;
		$player->cdata[$this->id]['count']   = $player->cdata[$this->id]['count']+1;
		if($player->cdata[$this->id]['count'] == $this->maxtimes) {
			$this->onMLTrainingTimes(array($player));
			$player->cdata[$this->id]['count'] = 0;
		}
	}

	public function onChattraining($cmd)
	{
		$this->onMLTraining(array($cmd[0]));
	}

	public function onMLTraining($params)
	{
		$window = $params[0]->ManiaFWK->getWindow('MLWindow');
		if(!$window) return;
		$window->setOption('title', 'Training');
		$window->setOption('icon', 'Race');
		$window->Reset();

		$window->Line();
		if($params[0]->cdata[$this->id]['enabled']) {
			$window->Cell('- Stop training', '100%', 'onMLATrainingStop');
		} else {
			$window->Cell('- Start training', '100%', 'onMLATrainingStart');
		}
		$window->Line();
		$window->Cell('- Show training times', '100%', 'onMLTrainingTimes');
		$window->Line();
		$window->Cell('- Reset training times', '100%', 'onMLATrainingReset');
	}

	public function onMLTrainingTimes($params)
	{
		//Get Challenge
		$challenge = Core::getObject('challenges')->getCurrent();
		if(!Core::getObject('challenges')->check($challenge)) return;

		//Get Window
		$window = $params[0]->ManiaFWK->getWindow('MLWindow');
		if(!$window) return;
		$window->setOption('title', 'Training Times');
		$window->setOption('icon', 'Race');
		$window->Reset();

		if(count($params[0]->cdata[$this->id]['times'])) {
			$file = fopen('cache/training_'.$params[0]->Login.'.txt', "w");
			fwrite($file, "[quote]\r\n");
			fwrite($file, "\t[u][b]remoteCP [Live] Training[/b][/u]\r\n");
			fwrite($file, "\t[b]Track:[/b]\t ".Core::getObject('tparse')->stripCode($challenge->Name)."\r\n");
			fwrite($file, "\t[b]Player:[/b]\t ".Core::getObject('tparse')->stripCode($params[0]->NickName)."\r\n");

			$avg = array_sum($params[0]->cdata[$this->id]['times']) / count($params[0]->cdata[$this->id]['times']);
			$window->Line();
			$window->Cell('>>>', '10%', null, array('halign' => 'center'));
			$window->Cell('Avg. Time:', '50%', null, array('textcolor' => '!fsi!'));
			$window->Cell(Core::getObject('tparse')->toRaceTime($avg), '40%', null, array('halign' => 'right','textcolor' => '!fhl!'));
			fwrite($file, "\t[b]Avg. Time:[/b]\t".Core::getObject('tparse')->toRaceTime($avg)."\r\n");

			$window->Line(array('class' => 'thead'));
			$window->Cell('#', '10%', null, array('halign' => 'center'));
			$window->Cell('NickName', '50%');
			$window->Cell('Time ', '40%', null, array('halign' => 'right'));
			$i = 0;
			foreach($params[0]->cdata[$this->id]['times'] AS $value)
			{
				++$i;
				$window->Line();
				$window->Cell($i, '10%', null, array('halign' => 'center'));
				$window->Cell($params[0]->NickName, '50%');
				$window->Cell(Core::getObject('tparse')->toRaceTime($value), '40%', null, array('halign' => 'right'));
				fwrite($file, "\t[b]Time #{$i}:[/b]\t".Core::getObject('tparse')->toRaceTime($value)."\r\n");
			}
			$window->Line();
			$window->Cell('$l['.Core::getSetting('httppath').'cache/training_'.$params[0]->Login.'.txt]grap training data as bbcode formated text$l', '100%', 'onMLTraining', array('class' => 'btn2n'));
			fwrite($file, "[/quote]\r\n");
			fclose($file);
		} else {
			$window->Line();
			$window->Cell('no training times available', '100%', null, array('halign' => 'center'));
		}
		$window->Line();
		$window->Cell('main menu', '100%', 'onMLTraining', array('class' => 'btn2n'));
	}

	public function onMLATrainingStart($params)
	{
		$params[0]->cdata[$this->id]['enabled'] = true;
		$params[0]->cdata[$this->id]['times']   = array();
		$params[0]->cdata[$this->id]['count']   = 0;
		$this->onMLTraining(array($params[0]));
	}

	public function onMLATrainingStop($params)
	{
		$params[0]->cdata[$this->id]['enabled'] = false;
		$this->onMLTraining(array($params[0]));
	}

	public function onMLATrainingReset($params, $silent = false)
	{
		$params[0]->cdata[$this->id]['times']   = array();
		$params[0]->cdata[$this->id]['count']   = 0;
		if(!$silent) {
			$this->onMLTraining(array($params[0]));
		}
	}
}
?>