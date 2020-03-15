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
class TMX extends rcp_liveplugin
{
	public  $title			= 'TMX';
	public  $author			= 'hal.ko.sascha';
	public  $version		= '4.0.3.6';
	private $root			= false;
	private $slash			= '/';
	private $toprecord		= array();
	private $challengeontmx = false;
	private $tmxpath		= 'tm-exchange';
	private $tmxgetperm		= 'editmaps'; //set to false if you want to allow everybody to add maps from tmx
	private $modes = array(
		'tmnf'	=> 'tmnforever',
		'tmuf'	=> 'united',
		'tmn'	=> 'nations',
		'tms'	=> 'sunrise',
		'tmo'	=> 'original'
	);

	//RecordsUI
	private $minrecdisp		= 1;
	private $maxrecdisp		= 3;
	private $recdisp		= false;

	public function onLoadSettings($settings)
	{
		$this->tmxpath		= (string) $settings->tmxpath;
		$this->tmxgetperm	= (string) $settings->tmxgetpermission ? (string) $settings->tmxgetpermission : false;
		$this->minrecdisp	= (int) $settings->ui->minrecdisp;
		$this->maxrecdisp	= (int) $settings->ui->maxrecdisp;
		$this->recdisp		= ((int) $settings->ui->enabled) ? true : false;
	}

	public function onLoad()
	{
		$records = Plugins::getPlugin('Menu')->menu->add('records', 'Records', 'Extreme', 40);
		if($records !== false) {
			$records->add('tmxrecs', 'TM-Exchange', false, 3, 'onMLTMXRecords');
		}

		Core::getObject('chat')->addCommand('tmxget', 'onChattmxget', 'Searches for the specified value on tm-exchange. Available games: tmuf, tmnf, tmn, tms, tmo', array(
			'/tmxget game trackname',
			'/tmxget game id1234567'
		), $this->tmxgetperm);

		Core::getObject('gbx')->query('GetTracksDirectory');
		$this->root  = Core::getObject('gbx')->getResponse();
		$this->slash = strpos($this->root, ':\\') ? '\\' : '/';
	}

	public function onBeginChallenge($params)
	{
		$this->toprecord      = array();
		$this->challengeontmx = false;
		Core::getObject('manialink')->updateContainer('SidebarA');

		//Get challenge
		$challenge = Core::getObject('challenges')->getCurrent();
		if(!Core::getObject('challenges')->check($challenge)) return;

		//Get TMX top record(s)
		$file = Core::getObject('web')->checkURL('http://united.tm-exchange.com/apiget.aspx?action=apitrackinfo&uid='. $challenge->Uid, 'file');
		if(!empty($file) && is_array($file) && !stristr($file[0], chr(27))) {
			$united = explode(Chr(9), $file[0]);
			$this->challengeontmx = array($united[0], 'united');

			$file = Core::getObject('web')->checkURL('http://united.tm-exchange.com/apiget.aspx?action=apitrackrecords&id='.$united[0], 'file');
			if(!empty($file) && is_array($file)) {
				foreach($file AS $element)
				{
					if(stristr($element, chr(27))) continue;
					$record = explode(Chr(9), $element);
					if(array_key_exists(3, $record) && !empty($record[3])) {
						$this->toprecord[] = array(
							'ReplayId'	=> $record[0],
							'Name'		=> $record[2],
							'Score'		=> $record[3],
							'Url'		=> 'http://united.tm-exchange.com/get.aspx?action=recordgbx&id='.$record[0]
						);
					}
				}
			}
		} else {
			//Fallback to nations if nothing found on united.tmx
			$file = Core::getObject('web')->checkURL('http://tmnforever.tm-exchange.com/apiget.aspx?action=apitrackinfo&uid='. $challenge->Uid, 'file');
			if(!empty($file) && is_array($file) && !stristr($file[0], chr(27))) {
				$nations = explode(Chr(9), $file[0]);
				$this->challengeontmx = array($nations[0], 'tmnforever');

				$file = Core::getObject('web')->checkURL('http://tmnforever.tm-exchange.com/apiget.aspx?action=apitrackrecords&id='.$nations[0], 'file');
				if(!empty($file) && is_array($file)) {
					foreach($file AS $element)
					{
						if(stristr($element, chr(27))) continue;
						$record = explode(Chr(9), $element);
						if(array_key_exists(3, $record) && !empty($record[3])) {
							$this->toprecord[] = array(
								'ReplayId'	=> $record[0],
								'Name'		=> $record[2],
								'Score'		=> $record[3],
								'Url'		=> 'http://tmnforever.tm-exchange.com/get.aspx?action=recordgbx&id='.$record[0]
							);
						}
					}
				}
			}
		}
	}

	public function onEndChallenge($params)
	{
		if(is_array($this->challengeontmx) && !empty($this->challengeontmx)) {
			$challenge = Core::getObject('challenges')->getCurrent();
			if(!Core::getObject('challenges')->check($challenge)) return;
			Core::getObject('live')->addMsg('$l[http://'.$this->challengeontmx[1].'.tm-exchange.com/main.aspx?action=trackshow&id='.$this->challengeontmx[0].'#auto]Download '.$challenge->Name.' !df!from tm-exchange$l');
		}
	}

	public function onChattmxget($cmd)
	{
		if($cmd[1]) {
			$this->onMLTMXSearch(array($cmd[0], $cmd[1]));
		} else {
			//open keyboard
			Plugins::getPlugin('Keyboard')->openKeyboard($cmd[0], 'onMLTMXSearch');
		}
	}

	public function onNewPlayer($player)
	{
		//Set recordsUI defaults
		$player->cdata[$this->id]['exp'] = true;
		$player->cdata[$this->id]['max'] = false;
		Core::getObject('manialink')->updateContainer('SidebarA', $player);
	}

	public function onMLTMXRecords($params)
	{
		$window = $params[0]->ManiaFWK->getWindow('MLWindow');
		if(!$window) return;
		$window->setOption('title', 'TM-Exchange Records');
		$window->setOption('icon', 'Medium');
		$window->Reset();

		//Top Records
		$records = $this->toprecord;
		$entries = 18;
		$max	 = count($records);
		$index	 = ($params[1]+0 < 0) ? 0 : $params[1]+0;
		$index	 = ($index > $max) ? $max - $entries: $index;
		$end	 = $index+$entries;
		$end	 = ($end >= $max) ? $max : $end;

		$window->Line(array('class' => 'thead'));
		$window->Cell('Pos', '10%', null, array('halign' => 'center'));
		$window->Cell('NickName', '65%');
		$window->Cell('Record', '25%', null, array('halign' => 'right'));
		if($max) {
			for($i = $index; $end > $i; ++$i)
			{
				$data = $records[$i];
				$window->Line();
				$window->Cell($i+1, '10%', null, array('halign' => 'center'));
				$window->Cell($data['Name'], '65%');
				$window->Cell('$l['.$data['Url'].']'.Core::getObject('tparse')->toRaceTime($data['Score']).'$l', '25%', null, array('halign' => 'right'));
			}

			$prev = ($index <= 0) ? false : true;
			$next = ($index >= ($max-$entries)) ? false : true;
			if($prev || $next) {
				$window->Line();
				$window->Cell('previous', '25%', array('onMLTMXRecords',$index-$entries), array('hidecell' => !$prev,'class' => 'btn2n'));
				$window->Cell('', '50%');
				$window->Cell('next', '25%', array('onMLTMXRecords',$index+$entries), array('hidecell' => !$next,'class' => 'btn2n'));
			}
		} else {
			$window->Line();
			$window->Cell('no records available', '100%', null, array('halign' => 'center'));
		}
	}

	public function onMLContainerSidebarA($params)
	{
		if(!$this->recdisp || !is_array($this->toprecord) || empty($this->toprecord)) return;
		$player = $params[0];
		$window = $params[1];

		$exp = $player->cdata[$this->id]['exp'];
		$max = $player->cdata[$this->id]['max'];

		$framesize   = $exp ? 20 : 10;
		$paddingsize = $exp ? 10 : 20;
		$ranksize    = $exp ? 8  : 16;
		$timesize    = $exp ? 30 : 64;
		$nicksize    = $exp ? 52 : 0;

		if(Core::getObject('status')->gameinfo['GameMode'] == 1) {
			$fpx = ($exp) ? 0 : 10;
			$lr  = false;
			$icA = 'ArrowNext';
			$icB = 'ArrowPrev';
		} else {
			$fpx = 0;
			$lr  = true;
			$icA = 'ArrowPrev';
			$icB = 'ArrowNext';
		}

		$window->Frame($fpx, 0, $framesize, array('class' => 'tmf'), false, false);
		$window->Line();
		if($lr) $window->Cell('', $paddingsize.'%', false);
		$window->Cell('', $ranksize.'%');
		if($exp) $window->Cell('TM-Exchange', $nicksize.'%');
		$window->Cell('', array(2,2), 'onMLATMXChangeMax', array('style' => 'Icons64x64_1', 'substyle' => $max ? 'ArrowUp' : 'ArrowDown'));
		$window->Cell('', array(2,2), 'onMLATMXChangeExp', array('style' => 'Icons64x64_1', 'substyle' => $exp ? $icA : $icB));
		if(!$lr) $window->Cell('', $paddingsize.'%', false);

		$i = 1;
		foreach($this->toprecord AS $record)
		{
			if(!$max && $i > $this->minrecdisp) {
				break;
			} elseif($max && $i > $this->maxrecdisp) {
				break;
			}

			$window->Line(array('margin' => array(0.5,0.5)));
			if($lr) $window->Cell('', $paddingsize.'%');
			$window->Cell($i.'.', $ranksize.'%', null, array('halign' => 'right'));
			$window->Cell('$l['. $record['Url'] .']'. Core::getObject('tparse')->toRaceTime($record['Score']) .'$l', $timesize.'%', null, array('textcolor' => '!fsi!'));
			if($exp) $window->Cell(Core::getObject('tparse')->stripCode($record['Name']), $nicksize.'%', null, array('halign' => 'left'));
			if(!$lr) $window->Cell('', $paddingsize.'%');
			++$i;
		}
	}

	public function onMLATMXChangeExp($params)
	{
		$params[0]->cdata[$this->id]['exp'] = ($params[0]->cdata[$this->id]['exp']) ? false : true;
		Core::getObject('manialink')->updateContainer('SidebarA', $params[0]);
	}

	public function onMLATMXChangeMax($params)
	{
		$params[0]->cdata[$this->id]['max'] = ($params[0]->cdata[$this->id]['max']) ? false : true;
		Core::getObject('manialink')->updateContainer('SidebarA', $params[0]);
	}

	public function onMLTMXSearch($params)
	{
		if(!$params[1]) return;

		$window = $params[0]->ManiaFWK->getWindow('MLWindow');
		if(!$window) return;
		$window->setOption('title', 'TMX Search');
		$window->setOption('icon', 'NewTrack');
		$window->Reset();
		$window->Line(array('class' => 'thead'));
		$window->Cell('Name', '45%');
		$window->Cell('Env.', '20%');
		$window->Cell('Author', '20%');
		$window->Cell('Option', '15%', null, array('halign' => 'center'));

		//Keyboard mode
		if($params[1] == 'kbrtrn') {
			$input = Plugins::getPlugin('Keyboard')->getPlayerInput($params[0]);
			$params[1] = 'tmuf '. $input;
		}

		//Get TMX apiget data [search]
		$params[1] = is_array($params[1]) ? $params[1] : array($params[1]);
		$page	= array_key_exists(1, $params[1]) ? $params[1][1] : 0;
		$search	= explode(' ', $params[1][0]);
		$mode	= array_shift($search);
		$mode	= $this->getMode($mode);

		//Check searchmode
		$search = implode(' ', $search);
		if(preg_match('!id([0-9]{4,7}.*)!i', $search, $array)) {
			$searchtype = 'trackid';
			$search = $array[1];
		} else {
			$searchtype = 'track';
		}

		//Search
		$file = Core::getObject('web')->checkURL('http://'.$mode.'.tm-exchange.com/apiget.aspx?action=apisearch&'.$searchtype.'='.$search.'&page='.$page, 'file');
		if(!empty($file) && is_array($file) && !stristr($file[0], chr(27))) {
			foreach($file AS $value)
			{
				$tmx = explode(Chr(9), $value);
				$tmx = array(
					'Id'			=> $tmx[0],
					'Name'			=> $tmx[1],
					'Author'		=> $tmx[3],
					'Type'			=> $tmx[4],
					'Environment'	=> $tmx[5],
					'Mood'			=> $tmx[6],
					'Routes'		=> $tmx[8],
					'Length'		=> $tmx[9],
					'Difficult'		=> $tmx[10]
				);
				$window->Line();
				$window->Cell($tmx['Name'], '45%');
				$window->Cell($tmx['Environment'], '20%');
				$window->Cell($tmx['Author'], '20%');
				$window->Cell('add', '15%', array('onMLATMXGet', array($tmx['Id'], $mode)), array('halign' => 'center'));
			}

			$prev = (($page-1) >= 1) ? true : false;
			$next = (count($file) >= 20) ? true : false;
			if($prev || $next) {
				$window->Line();
				$tmp = $page-1;
				$window->Cell('previous', '25%', array('onMLTMXSearch', array($mode.' '.$search, $tmp)), array('hidecell' => !$prev,'class' => 'btn2n'));
				$window->Cell('', '50%');
				$tmp = $page+1;
				$window->Cell('next', '25%', array('onMLTMXSearch', array($mode.' '.$search, $tmp)), array('hidecell' => !$next,'class' => 'btn2n'));
			}
		} else {
			$window->Line();
			$window->Cell('no challenges found', '100%', null, array('halign' => 'center'));
		}
	}

	public function onMLATMXGet($params)
	{
		if(!$params[1]) return;

		//check permission
		if($this->tmxgetperm) {
			if(!Core::getObject('live')->isAdmin($params[0]->Login) || !Core::getObject('live')->checkPerm($this->tmxgetperm, $params[0]->Login, 'TMXGet command')) return;
		}

		//read challenge file
		$tmxid = $params[1][0];
		$mode  = array_key_exists(1, $params[1]) ? $params[1][1] : 'united';
		$file  = Core::getObject('web')->checkURL('http://'.$mode.'.tm-exchange.com/get.aspx?action=trackgbx&id='. $tmxid, 'file_get_contents');
		$tmp   = preg_match('!<header(.*?)<\/header>!sim', $file, $results);
		$map   = new SimpleXMLElement('<challenge>'. $results[0] .'</challenge>', null);

		if((string) $map->header['type'] == 'challenge') {
			$filepath = !empty(Core::getObject('session')->server->filepath) ? Core::getObject('session')->server->filepath.$this->slash : '';
			$obj64    = new IXR_Base64($file);
			$filename = urldecode((string) $map->header->ident['name']);
			$filename = preg_replace('/[^ a-zA-Z_-]/','',Core::getObject('tparse')->stripCode($filename));
			$tmp      = $filepath.'Challenges'.$this->slash.$this->tmxpath.$this->slash.$filename.'.Challenge.Gbx';
			if(Core::getObject('gbx')->query('WriteFile', $tmp, $obj64)) {
				Core::getObject('gbx')->addCall('AddChallenge', $tmp);
				Core::getObject('chat')->send("Uploaded and added TMX-File: {$filename}", false, $params[0]->Login);
				Core::getObject('messages')->add("Uploaded and added TMX-File: $tmp");
				Core::getObject('chat')->send("{$params[0]->NickName} !df!added new challenge from TMX: {$filename}");
			}
		} else {
			Core::getObject('chat')->send('Invalid TM-Exchange Id: '.$tmxid, false, $params[0]->Login);
		}
	}

	private function getMode($mode)
	{
		if(!empty($mode) && array_key_exists($mode, $this->modes)) {
			return $this->modes[$mode];
		}
		return 'united';
	}
}
?>