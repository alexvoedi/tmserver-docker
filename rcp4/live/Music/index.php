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
require_once './includes/core/rcp_oggerator.class.php';
class Music extends rcp_liveplugin
{
	public  $title		= 'Music';
	public  $author		= 'hal.ko.sascha';
	public  $version	= '4.0.3.6';
	private $data		= array();
	private $forceddata	= array();
	private $forcemusic;
	private $autoopen;
	private $autochangetrack;
	private $forced;

	public function onLoad()
	{
		Core::getObject('chat')->addCommand('music', 'onChatMusic', 'open the music player', '/music');
		if(!empty($this->data)) {
			$music = Plugins::getPlugin('Menu')->menu->add('music', 'Music', array('Icons64x64_1', 'Music'), 60);
			if($music !== false) {
				$music->add('toggle', 'Toggle player', false, 1, 'onChatMusic');
				$music->add('list', 'Song list', false, 1, 'onMLMusicList');
			}
		}
		$this->addAdminOption('onMLAMusicToggleFM');
	}

	public function onLoadSettings($settings)
	{
		//General settings
		$this->forcemusic		= ((int) $settings->forcemusic) ? true : false;
		$this->autoopen			= ((int) $settings->autoopen) ? true : false;
		$this->autochangetrack	= ((int) $settings->autochangetrack) ? true : false;

		//Songs
		foreach($settings->data->children() AS $item)
		{
			$this->data[] = array(
				'name'		=> (string) $item->name,
				'file'		=> (string) $item->file,
				'comments'	=> false,
				'duration'	=> false
			);

			//Load ogg file (comments)
			if(strtoupper(substr((string) $item->file, -4)) != '.OGG') continue;

			$file = (string) $item->file;
			$info = new rcp_oggerator($file);
			if($info->getError()) {
				trigger_error($info->getError(), E_USER_WARNING);
			} else {
				$key = count($this->data)-1;
				$comments = $info->getComments();
				if(!empty($comments)) {
					$this->data[$key]['comments'] = array(
						'title'		=> $comments['TITLE'],
						'artist'	=> $comments['ARTIST'],
						'genre'		=> $comments['GENRE']
					);
				}
				$this->data[$key]['duration'] = $info->getValue('duration');
			}
			$info = null;
			unset($info);
		}

		//Set flags for forcemusic
		$this->onMLAMusicSetFlag(null, 'open', true);
		$this->onMLAMusicSetFlag(null, 'play', true);
		$this->onMLAMusicSetFlag(null, 'trackid', 0);
	}

	public function onBeginChallenge()
	{
		if($this->forcemusic) {
			//Change track if forcemusic is enabled
			$this->onMLAMusicCT(array(null, 'next'));
		} elseif($this->autochangetrack) {
			//Change to next track for all players with open music panel
			if(empty(Core::getObject('players')->players)) return;
			foreach(Core::getObject('players')->players AS $player)
			{
				if(empty($player->cdata[$this->id]['open'])) continue;
				$this->onMLAMusicCT(array($player, 'next'));
			}
		}
	}

	public function onBeginRace()
	{
		if(empty(Core::getObject('players')->players)) return;
		foreach(Core::getObject('players')->players AS $player)
		{
			if(empty($player->cdata[$this->id]['open'])) continue;
			$this->onMLMusic(array($player));
		}
	}

	public function onNewPlayer($player)
	{
		$window = $player->ManiaFWK->addWindow('MLMusic', 'Music Player', -61.5, -45.25, 35);
		if($window) {
			$window->setOption('header', false);
			$window->setOption('close', false);
			$window->setOption('static', true);
			$window->setOption('bg', false);
		}

		//Open music panel und play first track if autoopen setting is enabled
		if($this->autoopen || $this->forcemusic) {
			$this->onMLAMusicSetFlag($player, 'open', true);
			$this->onMLAMusicSetFlag($player, 'play', true);
			$this->onMLAMusicSetFlag($player, 'trackid', 0);
			$this->onMLMusic(array($player));
		}
	}

	public function onChatMusic($params) {
		//toggle musicplayer
		if(!empty($params[0]->cdata[$this->id]['open'])) {
			$window = $params[0]->ManiaFWK->getWindow('MLMusic');
			if(!$window) return;
			$window->Close();
			$this->onMLAMusicSetFlag($params[0], 'open', false);
		} else {
			$this->onMLAMusicSetFlag($params[0], 'open', true);
			$this->onMLAMusicSetFlag($params[0], 'play', false);
			$this->onMLAMusicSetFlag($params[0], 'trackid', 0);
			$this->onMLMusic(array($params[0]));
		}
	}

	public function onMLMusic($params)
	{
		$window = $params[0]->ManiaFWK->getWindow('MLMusic');
		if(!$window) return;

		$track = ($this->forcemusic) ? $this->data[$this->forceddata['trackid']] : $this->data[$params[0]->cdata[$this->id]['trackid']];
		$name  = !empty($track['comments']['artist']) ? '$o'. $track['comments']['artist'].'$z - ' : '';
		$name .= !empty($track['comments']['title']) ? $track['comments']['title'] : $track['name'];
		$name .= !empty($track['comments']['genre']) ? ' [$n'. $track['comments']['genre'] .'$z]'  : '';

		$window->Reset();
		$window->Line();
		if($this->forcemusic) {
			$window->Cell('', array(2,2), null, array('style' => 'Icons64x64_1', 'substyle' => 'Music'));
			$window->Cell($name, array('100%',2));
			return;
		}

		$window->Cell('', array(2,2), array('onMLAMusicCT','first'), array('style' => 'Icons64x64_1', 'substyle' => 'ArrowFirst'));
		$window->Cell('', array(2,2), array('onMLAMusicCT','prev'), array('style' => 'Icons64x64_1', 'substyle' => 'ArrowPrev'));
		if($params[0]->cdata[$this->id]['play']) {
			$window->Cell('', array(2,2), 'onMLAMusicPlay', array('style' => 'Icons64x64_1', 'substyle' => 'MediaStop'));
		} else {
			$window->Cell('', array(2,2), 'onMLAMusicPlay', array('style' => 'Icons64x64_1', 'substyle' => 'MediaPlay'));
		}
		$window->Cell('', array(2,2), array('onMLAMusicCT','next'), array('style' => 'Icons64x64_1', 'substyle' => 'ArrowNext'));
		$window->Cell('', array(2,2), array('onMLAMusicCT','last'), array('style' => 'Icons64x64_1', 'substyle' => 'ArrowLast'));
		$window->Cell($name, array('60%',2));

		//Move play cell outside the visible area
		$window->Frame(-70,-70,10);
		$window->Line();
		if($params[0]->cdata[$this->id]['play']) {
			$window->Cell('', array(2,2), null, array('tagtype' => 'audio', 'data' => $track['file'], 'play' => 1));
		} else {
			$window->Cell('', array(2,2), null, array('tagtype' => 'audio', 'data' => $track['file'], 'play' => 0));
		}
	}

	public function onMLMusicList($params)
	{
		$window = $params[0]->ManiaFWK->getWindow('MLWindow');
		if(!$window) return;

		//Pre-calculations
		$entries= 18;
		$max	= count($this->data);
		$index	= ($params[1]+0 < 0) ? 0 : $params[1]+0;
		$index	= ($index >= $max) ? $max - $entries : $index;
		$end	= $index+$entries;
		$end	= ($end >= $max) ? $max : $end;

		//Create header ml
		$window->setOption('title', 'Music - Songs');
		$window->setOption('icon', 'Music');
		$window->Reset();
		$window->Line(array('class' => 'thead'));
		$window->Cell('Title', '30%');
		$window->Cell('Artist', '30%');
		$window->Cell('Genre', '20%');
		$window->Cell('Duration', '20%');

		if($max) {
			//Create list ml
			for($i = $index; $end > $i; ++$i)
			{
				$value = $this->data[$i];
				$window->Line();
				$window->Cell($value['comments']['title'], '30%', array('onMLAMusicCT', $i));
				$window->Cell($value['comments']['artist'], '30%');
				$window->Cell($value['comments']['genre'] ? $value['comments']['genre'] : '--', '20%');
				$window->Cell($value['duration'].' s', '20%');
			}
		} else {
			$window->Line();
			$window->Cell('no songs available', '100%', null, array('halign' => 'center'));
		}

		$prev = ($index <= 0) ? false : true;
		$next = ($index >= ($max-$entries)) ? false : true;
		$window->Line();
		if($prev || $next || $this->jukeboxenabled) {
			$window->Cell('previous', '25%', array('onMLMusicList',$index-$entries), array('hidecell' => !$prev,'class' => 'btn2n'));
			$window->Cell('', '50%');
			$window->Cell('next', '25%', array('onMLMusicList',$index+$entries), array('hidecell' => !$next,'class' => 'btn2n'));
		}
	}

	//Toggles the current songs play status
	public function onMLAMusicPlay($params)
	{
		if($this->forcemusic) {
			$this->onMLAMusicSetFlag(null, 'play', ($this->forceddata['play']) ? false : true);
			$this->forceMusic(false);
		} else {
			if(!array_key_exists($this->id, $params[0]->cdata)) return;
			$this->onMLAMusicSetFlag($params[0], 'play', ($params[0]->cdata[$this->id]['play']) ? false : true);
			$this->onMLMusic(array($params[0]));
		}
	}

	//Changes the track
	public function onMLAMusicCT($params)
	{
		if($this->forcemusic) {
			$data = $this->forceddata;
		} else {
			if(!array_key_exists($this->id, $params[0]->cdata)) return;
			$data = $params[0]->cdata[$this->id];
		}

		$max = count($this->data)-1;
		if($params[1] == 'first') {
			$id = 0;
		} elseif($params[1] == 'last') {
			$id = $max;
		} elseif($params[1] == 'next') {
			$i  = $data['trackid']+1;
			$id = $i > $max ? 0 : $i;
		} elseif($params[1] == 'prev') {
			$i  = $data['trackid']-1;
			$id = $i < 0 ? $max : $i;
		} else {
			//set to specific track
			$id = $params[1];
		}

		if($this->forcemusic) {
			$this->onMLAMusicSetFlag(null, 'trackid', $id);
			$this->forceMusic(false);
		} else {
			Core::getObject('timedevents')->update('MLMusicAutoCT_'.$params[0]->Login, '+'. $this->data[$id]['duration'] .' second', 1, 'onMLAMusicCT', array($params[0], 'next'));
			$this->onMLAMusicSetFlag($params[0], 'trackid', $id);
			$this->onMLMusic(array($params[0]));
		}
	}

	//Creates or sets the delivered custom data
	private function onMLAMusicSetFlag($player, $key, $value)
	{
		if(is_null($player)) {
			$this->forceddata[$key] = $value;
		} else {
			$player->cdata[$this->id][$key] = $value;
		}
	}

	//Admin functions
	public function onMLAMusicToggleFM($params)
	{
		if(is_null($params)) {
			return ($this->forcemusic) ? 'Disable forceMusic' : 'Enable forceMusic';
		}

		if(!Core::getObject('live')->isAdmin($params[0]->Login)) return;
		$this->forcemusic = $this->forcemusic ? false : true;
		$label = $this->forcemusic ? 'enabled' : 'disabled';
		Core::getObject('live')->addMsg('Music forceMusic mode !hl!'. $label);
		$this->forceMusic(false);
	}

	private function forceMusic($forced = true)
	{
		if(!$forced) $this->forced = false;
		if($this->forcemusic && !$this->forced) {
			$track = $this->data[$this->forceddata['trackid']];
			$this->forced = true;
			Core::getObject('gbx')->query('SetForcedMusic', true, $track['file']);
			$this->onBeginRace();
		} elseif($this->forced) {
			$this->forced = false;
			Core::getObject('gbx')->query('SetForcedMusic', false, '');
		}
	}
}
?>