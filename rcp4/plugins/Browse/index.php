<?php
/**
* remoteCP 4
* ütf-8 release
* JsFix() and some other additions by assemblermaniac
*
* @package remoteCP
* @author hal.sascha
* @copyright (c) 2006-2009
* @version 4.0.3.6
*/
class Browse extends rcp_plugin
{
	public  $display		= 'main';
	public  $title			= 'Browser';
	public  $author			= 'hal.ko.sascha';
	public  $version		= '4.0.3.6';
	public  $usejs			= true;
	public  $vpermissions	= array('browse');
	public  $apermissions	= array(
		'AddChallenge'						=> 'editmaps',
		'InsertChallenge'		 			=> 'editmaps',
		'DeleteFiles'			 			=> 'browse',
		'SaveMatchSettings'		 			=> 'browse',
		'SaveNewMatchSettings'		 		=> 'browse',
		'LoadMatchSettings'		 			=> 'browse',
		'AppendPlaylistFromMatchSettings'	=> 'browse',
		'SaveNewServerSettings'				=> 'browse',
		'SaveServerSettings'				=> 'browse',
		'LoadServerSettings'		 		=> 'browse'
	);
	private $open			= false;
	private $op				= false;
	private $filesort		= array();
	private $root			= false;
	private $files			= array();
	private $queue			= array();
	private $types			= array(
		'rcpxzpfa'		=> array('folder.gif'			, 'Folder'			, 'Folder'),
		'challenge.gbx'	=> array('challenge.gbx.gif'	, '.challenge.gbx'	, 'Trackmania Challenge'),
		'replay.gbx'	=> array('video.gif'			, '.replay.gbx'		, 'Trackmania Replay'),
		'gbx'			=> array('gbx.gif'				, '.gbx'			, 'Gbx File'),
		'txt'			=> array('txt.gif'				, '.txt'			, 'Text Document'),
		'cfg'			=> array('matchsettings.gif'	, '.cfg'			, 'Config File'),
		'xml'			=> array('xml.gif'				, '.xml'			, 'XML Document'),
		'php'			=> array('php.gif'				, '.php'			, 'PHP Document'),
		'zip'			=> array('zip.gif'				, '.zip'			, 'Compressed ZIP'),
		'jpg'			=> array('image.gif'			, '.jpg'			, 'JPG Image'),
		'jpeg'			=> array('image.gif'			, '.jpeg'			, 'JPEG Image'),
		'gif'			=> array('image.gif'			, '.gif'			, 'GIF Image'),
		'png'			=> array('image.gif'			, '.png'			, 'PNG Image'),
		'dds'			=> array('image.gif'			, '.dds'			, 'DDS Image'),
		'mux'			=> array('audio.gif'			, '.mux'			, 'MUX Audio'),
		'mp3'			=> array('audio.gif'			, '.mp3'			, 'MP3 Audio'),
		'ogg'			=> array('audio.gif'			, '.ogg'			, 'OGG Vorbis Audio'),
		'default'		=> array('default.gif'			, ''				, 'unknown Filetype'),
		'tmss'			=> array('xml.gif'				, '.tmss'			, 'Trackmania Server Settings')
	);

	//FTP
	private $ftp			= false;
	private $ftpstream;
	private $ftplogin;
	private $ftppasv		= true;
	private $filepath		= '';
	private $ftpsystype		= false;

	//Settings
	private $slash			= '/';
	private $recursionlimit	= 2;
	//protected $debugging	= true;

	public function onLoad()
	{
		Core::storeObject('core/rcp_serversettings', 'rcp_serversettings', 'serversettings');
		$this->op	= array_key_exists('op', $_REQUEST) ? $_REQUEST['op'] : false;
		$sort		= array_key_exists('filesort', $_REQUEST) ? explode('|', $_REQUEST['filesort']) : array('name', true);
		$this->filesort['key'] = $sort[0];
		$this->filesort['dir'] = $sort[1];
		$this->filesort['dirN']= ($sort[1]) ? 0 : 1;
	}

	public function onExec()
	{
		$this->debug('sort array: '. print_r($this->filesort,true));

		if(Core::getObject('session')->server->ftp['enabled']) {
			$this->ftp = true;
			$this->ftpstream = ftp_connect(Core::getObject('session')->server->ftp['host'],Core::getObject('session')->server->ftp['port']);
			if($this->ftp_chkstream()) {
				$this->ftplogin = ftp_login($this->ftpstream, Core::getObject('session')->server->ftp['username'], Core::getObject('session')->server->ftp['password']);
				if($this->ftplogin) {
					$this->root = Core::getObject('session')->server->ftp['path'];
					$this->debug('ftp connection successfully established');

					//Use FTP Passive mode?
					$mode = ftp_pasv($this->ftpstream, $this->ftppasv);
					$this->debug('unable to change ftp mode');

					//Get FTP systype
					$this->ftpsystype = strtolower(ftp_systype($this->ftpstream));
					$this->debug('ftpsystype: '. $this->ftpsystype);
				} else {
					$this->debug('error login in ftp account');
					trigger_error(pt_ftperr1);
				}
			} else {
				$this->debug('error opening ftp stream');
				trigger_error(pt_ftperr2);
			}
		} else {
			Core::getObject('gbx')->query('GetTracksDirectory');
			$this->root = Core::getObject('gbx')->getResponse();

			//If we're running on windows, flip the slash to backslash
			if(strpos($this->root, ':\\')) {
				$this->debug('Windows-Mode: flipped slash to backslash');
				$this->slash = '\\';
			}
		}

		$this->root = $this->checkdir($this->root, true);
		$this->debug('root path: '. $this->root);
		$this->open = array_key_exists('open', $_REQUEST) ? $this->checkdir($_REQUEST['open']) : $this->root;
		$this->debug('open path: '. $this->open);

		//FilePath Stuff
		$this->filepath = !empty(Core::getObject('session')->server->filepath) ? Core::getObject('session')->server->filepath.$this->slash : '';
		$this->debug('filepath: '. $this->filepath);
		if($this->ftp && $this->ftp_chkstream()) {
			//Create directories
			if(!$this->ftp_is_dir($this->root.$this->filepath)) {
				ftp_mkdir($this->ftpstream, $this->root.$this->filepath);
				ftp_mkdir($this->ftpstream, $this->root.$this->filepath.'Challenges'.$this->slash);    //create challenges folder
				ftp_mkdir($this->ftpstream, $this->root.$this->filepath.'MatchSettings'.$this->slash); //create matchsettings folder
			}
		} else {
			//Create directories
			if(!is_dir($this->root.$this->filepath)) {
				mkdir($this->root.$this->filepath);
				mkdir($this->root.$this->filepath.'Challenges'.$this->slash);    //create challenges folder
				mkdir($this->root.$this->filepath.'MatchSettings'.$this->slash); //create matchsettings folder
			}
		}
	}

	public function onOutput()
	{
		switch($this->op) {
			case 'uploadtype':
				switch($_REQUEST['type']) {
					case 'tmx':
						echo "	<div class='f-row'>
									<label for='tmxtm'>".pt_tmxtype."</label>
									<div class='f-field'><select name='tmxtm'><option value='united'>United</option><option value='tmnforever'>Nations Forever</option></select></div>
								</div>";
						echo "	<div class='f-row'>
									<label for='tmxtm'>".pt_tmxid."</label>
									<div class='f-field'><input type='text' name='tmxid' /></div>
								</div>";
					break;

					default:
						echo "	<div class='f-row'>
									<label for='file'>".pt_file."</label>
									<div class='f-field'><input type='file' name='file' /></div>
								</div>";
					break;
				}
			break;

			case 'upload':
				//Displays the iframe things for the uploader
				if(Core::getObject('session')->checkPerm('upload')) {
					if(array_key_exists('tmxid', $_REQUEST) && array_key_exists('tmxtm', $_REQUEST)) {
						$tm    = $_REQUEST['tmxtm'];
						$tmxid = $_REQUEST['tmxid'];

						//Read challenge file
						$file = file_get_contents('http://'.$tm.'.tm-exchange.com/get.aspx?action=trackgbx&id='. $tmxid);
						$tmp  = preg_match('!<header(.*?)<\/header>!sim', $file, $results);
						$map  = new SimpleXMLElement('<challenge>'. $results[0] .'</challenge>', null);

						if((string) $map->header['type'] == 'challenge') {
							$obj64    = new IXR_Base64($file);
							$filename = Core::getObject('tparse')->stripCode(urldecode((string) $map->header->ident['name']));
							$tmp      = $this->replacedir($this->checkdir($_REQUEST['dir'])).$this->checkfilename($filename.'.Challenge.Gbx');
							$this->debug('workpath: '.$tmp);
							if(Core::getObject('gbx')->query('WriteFile', $tmp, $obj64)) {
								echo pt_uploaded.": $filename (TMX Challenge)";
							}
						} else {
							trigger_error(pt_uptmxerr1);
						}
					} elseif(array_key_exists('file', $_FILES)) {
						if($_FILES['file']['error'] == UPLOAD_ERR_OK) {
							// Check if file is a zipfile
							$filename	= $_FILES['file']['name'];
							$type		= $_FILES['file']['type'];
							$name		= explode('.', $filename); // $name[0] returns the name of the file. $name[1] returns the extension (zip)

							// Ensures that the correct file type was chosen
							$accepted_types = array('application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/x-compressed');
							foreach($accepted_types as $mime_type)
							{
								if($mime_type == $type) {
									$isZip = true;
									break;
								}
							}

							// Safari/Chrome don't register zip mime types, check for extension
							$isZip = (strtolower($name[1]) == 'zip' || strtolower($name[2]) == 'zip') ? true : false;

							// Upload ZIP file
							if($isZip) {
								$error	= false;
								$zip	= new ZipArchive;
								$res	= $zip->open($_FILES['file']['tmp_name'], ZIPARCHIVE::CHECKCONS);
								if($res === true) {
									$tmp = $this->checkdir($_REQUEST['dir']);
									$this->debug('workpath: '.$tmp);
									if(!$zip->extractTo($tmp)) {
										trigger_error(pt_upziperr1);
										$error = true;
									}

									if(!$zip->close()) {
										trigger_error(pt_upziperr2);
										$error = true;
									}
								} elseif($res == ZIPARCHIVE::ER_NOZIP) {
									trigger_error(pt_upziperr3);
									$error = true;
								} else {
									trigger_error(pt_upziperr4);
									$error = true;
								}
							// Upload  other file
							} else {
								$file	= file_get_contents($_FILES['file']['tmp_name']);
								$obj64	= new IXR_Base64($file);
								$tmp	= $this->replacedir($this->checkdir($_REQUEST['dir'])).$this->checkfilename($_FILES['file']['name']);
								$this->debug('file tmp_name: '. $_FILES['file']['tmp_name']);
								$this->debug('workpath: '.$tmp);
								if(!Core::getObject('gbx')->query('WriteFile', $tmp, $obj64)) {
									$error = true;
								}
							}

							if(!$error) {
								echo pt_uploaded.": {$_FILES['file']['name']} ({$_FILES['file']['type']}, {$_FILES['file']['size']} bytes)";
							}
						} elseif($_FILES['file']['error'] == UPLOAD_ERR_INI_SIZE) {
							$this->debug('upload: file to big');
							trigger_error(pt_uperr1);
						} elseif($_FILES['file']['error'] == UPLOAD_ERR_PARTIAL) {
							$this->debug('upload: file only partialy uploaded');
							trigger_error(pt_uperr2);
						} elseif($_FILES['file']['error'] == UPLOAD_ERR_FORM_SIZE) {
							$this->debug('upload: file to big, do not match max form size');
							trigger_error(pt_uperr3);
						} elseif($_FILES['file']['error'] == UPLOAD_ERR_NO_FILE) {
							$this->debug('upload: no file uploaded');
							trigger_error(pt_uperr4);
						}
					} else {
						$this->debug('upload: no file submitted');
						trigger_error(pt_uperr5);
					}
				} else {
					$this->debug('upload: no permission for uploading');
				}
			break;

			case 'getdata':
				$this->GetData();
			break;

			default:
				//Main Form
				echo "<fieldset>";
				echo "<div class='legend'>"; if($this->ftp) { echo pt_ftpmode; } else { echo pt_locmode; } echo "</div>";
				echo "<div id='browsecontent' style='border-bottom:1px solid #333333;'>";
				$this->GetData();
				echo "</div>";
				echo "</fieldset>";

				echo "<form action='ajax.php' method='post' name='fbrowsequeue' id='fbrowsequeue' class='postcmdc' rel='browsecontent:browse_emptyq'>";
				echo "<fieldset><div class='legend'>".pt_queue."</div>";
				echo "<div id='browsequeue'></div>";
				echo "<div class='f-row'>";
				echo "	<label><a href='#' onclick=\"browse_emptyq(); return false;\">[".pt_emptyq."]</a></label>";
				echo "	<div class='f-field'>";
				echo "		<select name='action'>";
				if(Core::getObject('session')->checkPerm('editmaps')) {
					echo "			<option value='InsertChallenges'>".pt_insertfiles."</option>";
					echo "			<option value='AddChallenges'>".pt_addfiles."</option>";
				}
				echo "			<option value='DeleteFiles'>".pt_deletefiles."</option>";
				echo "		</select>";
				echo "		<input type='hidden' name='plugin' value='{$this->id}' />";
				echo "		<input type='hidden' id='formdir' name='open' value='{$this->open}' />";
				echo "		<input type='hidden' name='op' value='getdata' />";
				echo "	</div>";
				echo "	<button type='submit' class='wide' title='".ct_submit."'>".ct_submit."</button>";
				echo "</fieldset>";
				echo "</form>";

				if(Core::getObject('session')->checkPerm('upload')) {
					echo "<form>";
					echo "<input type='hidden' name='dir' id='updir' value='{$this->open}' />";
					echo "<fieldset><div class='legend'>".pt_upload."</div>";
					echo "	<div class='f-row'>
							<label for='type'>".pt_uploadtype."</label>
							<div class='f-field'><select name='type' class='getcmd' rel='uploadtype' href='ajax.php?plugin=Browse&op=uploadtype&type='><option value='def'>Direct</option><option value='tmx'>TM-Exchange</option></select></div>
						</div>";
					echo "	<div id='uploadtype'>
							<div class='f-row'>
								<label for='file'>".pt_file."</label>
								<div class='f-field'><input type='file' name='file' /></div>
							</div>
						</div>";
					echo "	<div id='upload_1'>&nbsp;</div>";
					echo "	<button class='wide' onClick=\"browse_upload(this.form,'ajax.php?plugin={$this->id}&op=upload','upload_1','Loading...','Error in upload'); return false;\" type='submit' title='".ct_submit."'>".ct_submit."</button>";
					echo "</fieldset>";
					echo "</form>";
				}

				echo "<form action='ajax.php' method='post' id='fbrowsems' name='fbrowsems' class='postcmdc' rel='browsecontent:browse_updatefiles'>";
				echo "<input type='hidden' name='dir' id='msdir' value='{$this->open}' />";
				echo "<fieldset><div class='legend'>".pt_savems."</div>";
				echo "	<div class='f-row'>
						<label for='file'>".pt_filename."</label>
						<div class='f-field'><input type='text' name='file' value='' /></div>
					</div>";
				echo "	<input type='hidden' name='action' value='SaveNewMatchSettings' />";
				echo "	<input type='hidden' name='plugin' value='{$this->id}' />";
				echo "	<input type='hidden' name='op' value='getdata' />";
				echo "	<button type='submit' class='wide' title='".ct_submit."'>".ct_submit."</button>";
				echo "</fieldset>";
				echo "</form>";

				echo "<form action='ajax.php' method='post' id='fbrowsess' name='fbrowsess' class='postcmdc' rel='browsecontent:browse_updatefiles'>";
				echo "<input type='hidden' name='dir' id='ssdir' value='{$this->open}' />";
				echo "<fieldset><div class='legend'>".pt_savess."</div>";
				echo "	<div class='f-row'>
						<label for='file'>".pt_filename."</label>
						<div class='f-field'><input type='text' name='file' value='' /></div>
					</div>";
				echo "	<input type='hidden' name='action' value='SaveNewServerSettings' />";
				echo "	<input type='hidden' name='plugin' value='{$this->id}' />";
				echo "	<input type='hidden' name='op' value='getdata' />";
				echo "	<button type='submit' class='wide' title='".ct_submit."'>".ct_submit."</button>";
				echo "</fieldset>";
				echo "</form>";
			break;
		}
	}

	public function onUnload()
	{
		if($this->ftp) {
			ftp_close($this->ftpstream);
		}
	}

	private function GetData()
	{
		if(!$this->open) return;
		if($this->ftp) {
			$data = $this->ftp_getData($this->open);
		} else {
			$data = $this->loc_getData($this->open);
		}

		//Create goup path
		$goup = strrchr(substr($this->open, 0, -1), $this->slash).$this->slash;
		$goup = str_replace($goup, '', $this->open).$this->slash;

		echo "<div class='legend'>root{$this->open}</div>";
		echo "<table>";
		echo "<colspan>";
		echo "	<col width='5%' />";
		echo "	<col width='5%' />";
		echo "	<col width='45%' />";
		echo "	<col width='20%' />";
		echo "	<col width='15%' />";
		echo "	<col width='10%' />";
		echo "</colspan>";
		echo "<thead>";
		echo "<tr>";
		echo "	<th><input type='checkbox' name='checkall' class='browsechkall checkbox' /></th>";
		echo "	<th></th>";
		echo "	<th class='il' onclick=\"browse_cdir(false, '". $this->jsFix($this->open) ."', 'name|{$this->filesort['dirN']}'); return false;\">".pt_sbname; if($this->filesort['key'] == 'name') { echo "<img src='". Core::getSetting('style') ."/icons/sort{$this->filesort['dirN']}.gif' width='16' height='16' alt='' />"; } echo "</th>";
		echo "	<th class='il'>".pt_sbauthor."</th>";
		echo "	<th class='il' onclick=\"browse_cdir(false, '". $this->jsFix($this->open) ."', 'time|{$this->filesort['dirN']}'); return false;\">".pt_sbtime; if($this->filesort['key'] == 'time') { echo "<img src='". Core::getSetting('style') ."/icons/sort{$this->filesort['dirN']}.gif' width='16' height='16' alt='' />"; } echo "</th>";
		echo "	<th class='ir' onclick=\"browse_cdir(false, '". $this->jsFix($this->open) ."', 'size|{$this->filesort['dirN']}'); return false;\">".pt_sbsize; if($this->filesort['key'] == 'size') { echo "<img src='". Core::getSetting('style') ."/icons/sort{$this->filesort['dirN']}.gif' width='16' height='16' alt='' />"; } echo "</th>";
		echo "</tr>";
		echo "</thead>";
		echo "<tbody>";
		if($this->open != $this->root) {
			echo "<tr class='bg-l'>";
			echo "	<td></td>";
			echo "	<td class='ic'><img src='". Core::getSetting('style') ."/ftypes/folderup.gif' width='16' height='16' alt='' /></td>";
			echo "	<td colspan='4'><a href='#' onclick=\"browse_cdir(this, '". $this->jsFix($goup) ."', '{$this->filesort['key']}|{$this->filesort['dirN']}'); return false;\">".pt_folderup."</a></span></td>";
			echo "</tr>";
		}
		if(!empty($data) && is_array($data)) {
			usort($data, array($this, 'filesort'));

			$i = 1;
			foreach($data AS $element)
			{
				//change row color
				$tablecolor = ($tablecolor == 'l') ? 'm' : 'l';

				//isFile
				if($element['isfile']) {
					$name	= (is_array($element)) ? (is_array($element['header']) ? (array_key_exists('name', $element['header'])		? $element['header']['name']	: $element['name'])	: $element['name'])	: $element['name'];
					$name	= ($element['header']) ? str_ireplace($element['filetype'][1],'',Core::getObject('tparse')->toHTML($name))	: $name;
					$envir	= (is_array($element)) ? (is_array($element['header']) ? (array_key_exists('envir', $element['header'])		? $element['header']['envir']	: '--')				: '--')				: '--';
					$author	= (is_array($element)) ? (is_array($element['header']) ? (array_key_exists('author', $element['header'])	? $element['header']['author']	: '--')				: '--')				: '--';
					$size	= $element['size'];
				//isFolder
				} else {
					$name	= $element['name'];
					$envir	= '--';
					$author	= '--';
					$size	= '--';
				}

				echo "<tr class='bg-{$tablecolor}'>";
				echo "	<td class='ic'>"; if($element['isfile']) { echo "<input type='checkbox' name='chk{$i}' class='browsechk checkbox' rel='". $element['dir'] ."' />"; } echo "</td>";
				echo "	<td class='ic'><img src='". Core::getSetting('style') ."/ftypes/{$element['filetype'][0]}' width='16' height='16' alt='' /></td>";
				echo "	<td>";
				if($element['isfile']) {
					echo "<span title='{$element['filetype'][2]} {$envir}'>{$name}</span>";
				} else {
					echo "<span title='{$element['filetype'][2]} {$envir}'><a href='#' onclick=\"browse_cdir(this, '". $this->jsFix($element['dir']) ."', 'name|0'); return false;\">{$name}</a></span>";
				}

				//Matchsetting / Serversettings addiotional options
				if($element['filetype'][1] == '.cfg' || $element['filetype'][1] == '.txt' || $element['filetype'][1] == '.xml') {
					echo "	<a href='ajax.php?plugin={$this->id}&open=". $this->jsFix($this->open) ."&file=".$this->jsFix($element['dir'])."&op=getdata&action=LoadMatchSettings' rel='browsecontent' class='getcmd'><img src='". Core::getSetting('style') ."/icons/load.gif' alt='".pt_loadms."' title='".pt_loadms."' /></a>
							<a href='ajax.php?plugin={$this->id}&open=". $this->jsFix($this->open) ."&file=".$this->jsFix($element['dir'])."&op=getdata&action=AppendPlaylistFromMatchSettings' rel='browsecontent' class='getcmd'><img src='". Core::getSetting('style') ."/icons/append.gif' alt='".pt_appms."' title='".pt_appms."' /></a>
							<a href='ajax.php?plugin={$this->id}&open=". $this->jsFix($this->open) ."&file=".$this->jsFix($element['dir'])."&op=getdata&action=SaveMatchSettings' rel='browsecontent' class='getcmd'><img src='". Core::getSetting('style') ."/icons/save.gif' alt='".pt_savems."' title='".pt_savems."' /></a>";
				} elseif($element['filetype'][1] == '.tmss') {
					echo "	<a href='ajax.php?plugin={$this->id}&open=". $this->jsFix($this->open) ."&file=".$this->jsFix($element['dir'])."&op=getdata&action=LoadServerSettings' rel='browsecontent' class='getcmd'><img src='". Core::getSetting('style') ."/icons/load.gif' alt='".pt_loadss."' title='".pt_loadss."' /></a>
							<a href='ajax.php?plugin={$this->id}&open=". $this->jsFix($this->open) ."&file=".$this->jsFix($element['dir'])."&op=getdata&action=SaveServerSettings' rel='browsecontent' class='getcmd'><img src='". Core::getSetting('style') ."/icons/save.gif' alt='".pt_savess."' title='".pt_savess."' /></a>";
				}

				echo "	</td>";
				echo "	<td>{$author}</td>";
				echo "	<td>". date("M d Y H:i:s", $element['time']) ."</td>";
				echo "	<td class='ir'>{$size}</td>";
				echo "</tr>";
				++$i;
			}
		} else {
			echo "<tr><td colspan='6'>".pt_nofiles."</td></tr>";
		}
		echo "</tbody>";
		echo "</table>";
	}

	private function ftp_getData($dir)
	{
		//Check ftp stream
		if(!$this->ftp_chkstream()) {
			return false;
		}

		//Read dirs
		$dir = $this->checkdir($dir);
		$contents = $this->ftp_getrawlist($dir);
		if(!is_array($contents)) {
			trigger_error(pt_readdirerr.$dir);
			return false;
		}
		$i = 0;
		$class = false;
		foreach($contents as $element)
		{
			//Ignore some special elements
			if($element == '.' || $element == '..' || stristr($element, 'Replays')) continue;

			//Create tmpdir
			$tmpdir = $dir.$element;

			//Check if file or dir
			$isFile = $this->ftp_is_dir($tmpdir) ? false : true;
			if(!$isFile) $tmpdir = $this->checkdir($dir.$element);

			//Create data array
			$id = uniqid();
			$output[$id] = array(
				'name'		=> $element,
				'dir'		=> $tmpdir,
				'isfile'	=> $isFile,
				'filetype'	=> ($isFile) ? $this->getFileType($element) : $this->getFileType($element.'.rcpxzpfa'),
				'time'		=> ($isFile) ? ftp_mdtm($this->ftpstream, $tmpdir)+0 : false,
				'size'		=> ($isFile) ? ftp_size($this->ftpstream, $tmpdir)+0 : false,
				'header'	=> false
			);
		}
		return isset($output) ? $output : false;
	}

	private function loc_getData($dir)
	{
		$dir = $this->checkdir($dir);
		$stream = opendir($dir);
		if(!$stream) {
			trigger_error(pt_readdirerr.$dir);
			return false;
		}

		$i		= 0;
		$class	= false;
		for(;(false !== ($element = readdir($stream)));)
		{
			//Ignore some special elements
			if($element == '.' || $element == '..' || stristr($element, 'Replays')) continue;

			//Create tmpdir
			$tmpdir = $dir.$element;

			//Check if file or dir
			$isFile = is_dir($tmpdir) ? false : true;
			if(!$isFile) $tmpdir = $this->checkdir($dir.$element);

			//Create data array
			$id = uniqid();
			$output[$id] = array(
				'name'		=> $element,
				'dir'		=> $tmpdir,
				'isfile'	=> $isFile,
				'filetype'	=> ($isFile) ? $this->getFileType($element) : $this->getFileType($element.'.rcpxzpfa'),
				'time'		=> ($isFile) ? @filemtime($tmpdir)+0 : false,
				'size'		=> ($isFile) ? @filesize($tmpdir)+0 : false,
				'header'	=> ($isFile) ? $this->getChallengeHeader($tmpdir) : false
			);
		}
		closedir($stream);
		return isset($output) ? $output : false;
	}

	private function jsFix($dir)
	{
		return str_replace('\\', '\\\\', $dir);
	}

	private function ftp_is_dir($dir)
	{
		if($this->ftp_chkstream() && @ftp_chdir($this->ftpstream, $dir)) {
			//@ftp_chdir($this->ftpstream, '..');
			@ftp_cdup($this->ftpstream);
			return true;
		}
		return false;
	}

	private function ftp_chkstream()
	{
		if(!$this->ftpstream) {
			trigger_error(pt_ftperr2);
			return false;
		}
		return true;
	}

	private function ftp_getrawlist($dir)
	{
		$this->debug('FTP rawlist dir: '.$dir);

		//Get rawlist
		ftp_chdir($this->ftpstream, $dir);
		//$list = ftp_rawlist($this->ftpstream, $dir);
		$list = ftp_rawlist($this->ftpstream, "-a");
		@ftp_cdup($this->ftpstream);
		if(!is_array($list)) {
			return;
		}
		$this->debug('FTP rawlist data: <pre>'.print_r($list,true).'</pre>');

		$newlist = array();
		reset($list);
		foreach($list AS $data)
		{
			switch($this->ftpsystype)
			{
				case 'windows_nt':
					//Windows NT
					//02-21-10  07:43AM                 Logs				| Array([0] => 02-21-10	[1] => 07:43AM	[2] =>		[3] => Logs)
					//12-21-09  12:22PM                 2750 access.xml		| Array([0] => 12-21-09	[1] => 12:22PM	[2] => 2750	[3] => access.xml)
					$data = preg_split('/[\s]+/', $data, 4);
					$tstr = $data[3];
				break;

				default:
					//Linux
					//-rw-r--r--   1 rcptest@team-hal.de team-hal.de      391 Feb 10 18:55 activity_hal-ko-dsp_web.log
					$data = preg_split('/[\s]+/', $data, 9);
					$tstr = $data[8];
				break;
			}

			//Remove all kind of slashes
			$tstr = str_replace(array('/', '\\'),'',$tstr);
			if(empty($tstr)) continue;
			$newlist[] = $tstr;
		}
		$this->debug('FTP parsed RawList data: <pre>'.print_r($newlist,true).'</pre>');
		return $newlist;
	}

	private function replacedir($dir, $cdir = false)
	{
		//Shortens the path to something the dedicated server can work with
		if($cdir) {
			return str_replace($cdir,'',$dir);
		}
		return str_replace($this->root,'',$dir);
	}

	private function checkdir($dir, $ptd = false)
	{
		//Trim spaces
		$dir = trim($dir);

		//Check and add (if needed) last slash for the path
		if(substr($dir, -1) != $this->slash) {
			$dir = $dir.$this->slash;
		}

		//Replace every double slash to prevent invalid dirs
		$dir = str_replace($this->slash.$this->slash, $this->slash, $dir);

		//Security Check, path of files or dir should not above root dir
		//if it is, we will set it to default root path
		if($ptd || strstr($dir, $this->root)) {
			return $dir;
		}

		return $this->root;
	}

	private function checkfilename($value)
	{
		$value = stripslashes($value);
		$value = str_replace(' ', '_', $value);
		$value = preg_replace('![^a-zA-Z0-9\_\-\.]!', '', $value);
		return $value;
	}

	private function getFileType($file)
	{
		$tmp1      = strrchr($file, '.');
		$tmp2      = strrchr(str_replace($tmp1,'',$file), '.');
		$extension = substr($tmp2.$tmp1, 1);
		if(array_key_exists(strtolower($extension), $this->types)) {
			return $this->types[strtolower($extension)];
		}

		return $this->types['default'];
	}

	private function filesort($a, $b)
	{
		$key = $this->filesort['key'];

		//Folders ontop
		if($a['isfile'] === false && $b['isfile'] === true) { //a is folder, b is file
			return -1;
		} elseif($a['isfile'] === true && $b['isfile'] === false) { //a is file, b is folder
			return 1;
		}

		//Files...
		if($a[$key] == $b[$key]) {
			return 0;
		}

		if($this->filesort['dir']) {
			return ($a[$key] < $b[$key]) ? 1 : -1;
		} else {
			return ($a[$key] < $b[$key]) ? -1 : 1;
		}
	}

	private function getqueue()
	{
		foreach($_REQUEST as $key => $content)
		{
			//echo $content."<br>";
			$content = str_replace('\\\\', '\\', $content); // 2008-2-1 AssemblerManiac - now that java has the extra \ char, we need to get rid of them to process it right in php
			if(!empty($_REQUEST[$key]) && strstr($content, $this->root)) {
				if($this->ftp) {
					if(!$this->ftp_is_dir($content)) {
						$this->queue[] = $content;
					}
				} else {
					if(is_file($content)) {
						$this->queue[] = $content;
					}
				}
			}
		}
		//print_r($this->queue);
	}

	public function AddChallenges()
	{
		$this->getqueue();
		foreach($this->queue AS $value)
		{
			$value = $this->replacedir($value);
			$this->debug('workpath: '.$value);
			Core::getObject('actions')->add('AddChallenge', $value);
		}
	}

	public function InsertChallenges()
	{
		$this->getqueue();
		foreach($this->queue AS $value)
		{
			$value = $this->replacedir($value);
			$this->debug('workpath: '.$value);
			Core::getObject('actions')->add('InsertChallenge', $value);
		}
	}

	public function DeleteFiles()
	{
		$this->getqueue();
		foreach($this->queue AS $value)
		{
			if($this->ftp && $this->ftp_chkstream()) {
				ftp_delete($this->ftpstream, $value);
			} else {
				if(!unlink($value)) trigger_error(pt_deleteerr.$value);
			}
		}
	}

	public function SaveMatchSettings()
	{
		Core::getObject('actions')->add('SaveMatchSettings', $this->replacedir($_REQUEST['file']));
	}

	public function SaveNewMatchSettings()
	{
		$tmp = $this->replacedir($this->checkdir($_REQUEST['dir'])).$this->checkfilename($_REQUEST['file']);
		$this->debug('workpath: '.$tmp);
		Core::getObject('actions')->add('SaveMatchSettings', $tmp);
	}

	public function LoadMatchSettings()
	{
		Core::getObject('actions')->add('LoadMatchSettings', $this->replacedir($_REQUEST['file']));
	}

	public function AppendPlaylistFromMatchSettings()
	{
		Core::getObject('actions')->add('AppendPlaylistFromMatchSettings', $this->replacedir($_REQUEST['file']));
	}

	public function SaveNewServerSettings()
	{
		$tmp = $this->replacedir($this->checkdir($_REQUEST['dir'])).$this->checkfilename($_REQUEST['file']);
		$this->debug('workpath: '.$tmp);
		Core::getObject('serversettings')->save($tmp);
	}

	public function SaveServerSettings()
	{
		Core::getObject('serversettings')->save($_REQUEST['file']);
	}

	public function LoadServerSettings()
	{
		Core::getObject('serversettings')->load($_REQUEST['file']);
	}

	public function getChallengeHeader($file)
	{
			//Read challenge header
			$xml = file_get_contents($file);
			$tmp = preg_match('!<header(.*?)<\/header>!sim', $xml, $results);
			try {
				$map = @new SimpleXMLElement('<challenge>'. $results[0] .'</challenge>', null);
			} catch(Exception $e) { }

			if((string) $map->header['type'] == 'challenge') {
				return array(
					'uid'	=> (string) $map->header->ident['uid'],
					'name'	=> urldecode((string) $map->header->ident['name']),
					'author'=> (string) $map->header->ident['author'],
					'envir'	=> (string) $map->header->desc['envir']
				);
			}
			return array();
	}
}
?>