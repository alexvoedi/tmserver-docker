<?php
/**
* remoteCP 4
* ütf-8 release
*
* @package remoteCPlive
* @author hal.sascha
* @copyright (c) 2006-2009
* @version 4.0.3.6
*
* rcp_oggerator class is derived from Ogg.class.php v1.3e by Nicolas Ricquemaque <f1iqf@hotmail.com>
* For more info see: http://opensource.grisambre.net/ogg/
* Ogg format: http://en.wikipedia.org/wiki/Ogg
* Comments  : http://www.xiph.org/vorbis/doc/v-comment.html
*/
class rcp_oggerator
{
	// Constants
	private $version = '[rcp_oggerator.class.php v1.0]';

	// Options
	private $caching	= true;
	private $utf8		= true;

	// Private variables
	private $LastError	= false;
	private $Streams	= array();
	private $Data;
	private $CacheDir;
	private $refresh;

	/**
	 * Reads a ogg file
	 *
	 * @param string $OggFile - url or local path to a .ogg file
	 * @param string $CacheDir - optional cache directory
	 * @return object
	 */
	public function __construct($OggFile, $CacheDir = "./cache/cache.ogg")
	{
		// Check php version
		if(intval(PHP_VERSION)<4) return($this->creturn("PHP version must me >= 4 but we show PHP v".PHP_VERSION));

		clearstatcache();
		$this->Streams['oggfile'] = $OggFile;
		if((strpos($OggFile,"://") === false) && !file_exists($this->realpath($OggFile))) return ($this->creturn("Inexisting OGG file ".$OggFile));

		// Handle caching
		if($this->caching) {
			$this->CacheDir = rtrim($CacheDir, '/');
			if(!is_dir($this->realpath($this->CacheDir))) @mkdir($this->realpath($this->CacheDir));
			$cache = $this->CacheDir."/".strtr($this->Streams['oggfile'],"/:?&#","-----").".cache";
			$this->Streams['cachefile'] = $cache;
			if(is_resource($cachefile = @fopen($this->realpath($this->Streams['cachefile']), "r"))) {
				if($s = fread($cachefile,filesize($this->realpath($this->Streams['cachefile'])))) {
					$this->Streams = unserialize($s);

					// Adapt string encoding to what's required by user
					if(isset($this->Streams['summary'])) $this->Streams['summary'] = $this->Decode($this->Streams['summary']);
					if(isset($this->Streams['theora']['vendor'])) $this->Streams['theora']['vendor'] = $this->Decode($this->Streams['theora']['vendor']);
					if(isset($this->Streams['vorbis']['vendor'])) $this->Streams['vorbis']['vendor'] = $this->Decode($this->Streams['vorbis']['vendor']);
					if(isset($this->Streams['theora']['comments'])) {
						foreach($this->Streams['theora']['comments'] as $key => $comment)
						{
							$comment = explode('=', $comment);
							$this->Streams['theora']['comments'][$comment[0]] = $this->Decode($comment[1]);
						}
					}
					if(isset($this->Streams['vorbis']['comments'])) {
						foreach($this->Streams['vorbis']['comments'] as $key => $comment)
						{
							$comment = explode('=', $comment);
							$this->Streams['vorbis']['comments'][$comment[0]] = $this->Decode($comment[1]);
						}
					}
					$this->Streams['encoding'] = $this->utf8 ? "utf-8" : "ansi";

					// Double-check if the file we found in cache is really the one we should have, by comparing their sizes, for local files only
					if((strpos($OggFile,"://") === false) && ($this->Streams['size'] != filesize($this->realpath($this->Streams['oggfile'])))) unset($this->Streams['source']);
					$this->creturn(true);
				}

				fclose($cachefile);
				$this->Streams['cachefile'] = $cache;
			}
		}

		// If file could not be read from cache (or sizes did not match), we parse it from file
		if(!isset($this->Streams['source'])) $this->analyze(); 

		// Now check if a write has been left in progress (for local files only)
		if((strpos($OggFile,"://") === false) && ($tmp = glob($this->realpath($this->Streams['oggfile']."*.tmp")))) {
			if(count($tmp) == 1) {
				$this->Streams['tmpfile'] = $tmp[0];
			// if there is several possible files, use the last one, delete others
			} elseif(count($tmp) > 1) {
				$this->Streams['tmpfile'] = $tmp[0];
				foreach($tmp as $file)
				{
					if(filemtime($file) > filemtime($this->Streams['tmpfile'])) {
						@unlink($this->Streams['tmpfile']); // supress old uncompleted tmp file
						$this->Streams['tmpfile'] = $file;
					}
				}
			}

			if(isset($this->Streams['tmpfile'])) {
				$this->Streams['tmpfile'] = substr($this->Streams['tmpfile'],strpos($this->Streams['tmpfile'],$this->Streams['oggfile']));
				list($this->refresh,$this->Streams['tmpfileptr']) = sscanf($this->Streams['tmpfile'],$this->Streams['oggfile'].".r%d.p%d.tmp");
			}
		}
	}

	public function getComments()
	{
		return $this->Streams['vorbis']['comments'];
	}

	public function getValue($key)
	{
		if(!array_key_exists($key, $this->Streams['vorbis'])) return false;
		return $this->Streams['vorbis'][$key];
	}

	public function getError()
	{
		return $this->LastError;
	}

	// Private methods
	private function CacheUpdate()
	{
		if(!$this->caching) return($this->creturn("CACHING disabled, therefore updating cache is not possible..."));
		if(is_resource($cachefile = fopen($this->realpath($this->Streams['cachefile']), "w"))) {
			$src = $this->Streams['source'];
			$cache = $this->Streams['cachefile'];
			$this->Streams['source'] = "cache";
			unset($this->Streams['cachefile']);
			fputs($cachefile, serialize($this->Streams));
			$this->Streams['source'] = $src;
			$this->Streams['cachefile'] = $cache;
			fclose($cachefile);
			return($this->creturn(true));
		} else {
			return($this->creturn("Writting cache file unsuccessfull"));
		}
	}

	private function creturn($error = false)
	{
		if(is_string($error)) {
			$this->LastError = "OGG Error: $error";
			return(false);
		} else {
			$this->LastError = false;
			return($error);
		}
	}

	private function Decode($string)
	{
		// If string utf8, return it, or encode it in utf8
		if($this->utf8) {
			return((utf8_encode(utf8_decode($string)) == $string) ? $string : utf8_encode($string));
		 // Return ansi
		} else {
			return((utf8_encode(utf8_decode($string)) == $string) ? utf8_decode($string) : $string);
		}
	}

	private function Read32LE(&$buffer,$pos)
	{
		// Read 32 bits little endian from buffer from index $pos
		return(ord($buffer[$pos+0])+(ord($buffer[$pos+1])<<8)+(ord($buffer[$pos+2])<<16)+(ord($buffer[$pos+3])<<24));
	}

	// Read and decodes an integer up to 32 bits from the buffer
	private function readBits($nb,&$buffer,&$bitptr)
	{
		if($nb > 32) $nb=32;
		if($nb == 0) return(0);
		for($bit = $nb, $r = 0; $bit > 0; $bit--, $bitptr++)
		{
			$r += (ord(substr($buffer,$bitptr>>3,1))&pow(2,7-$bitptr%8))>0?pow(2,$bit-1):0;
		}
		return($r);	
	}

	private function realpath($path) // if a path to a file is absolute, add document_root
	{
		// Path already absolute or remote
		$root = ($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '/';
		if((strpos($path, $root) !== false) || (strpos($path, '://') !== false)) {
			return($path);
		} elseif($path[0] == '/') {
			return str_replace("//","/",$_SERVER['DOCUMENT_ROOT'].$path);
		// Relative path
		} else {
			return($path);
		}
	} 

	// Add a CRC to an ogg page $str (including headers, crc set to 0)
	private function crcOgg(&$str)
	{
		$crc = 0;
		$polynom = 0x04C11DB7;
		for($i = 0; $i < strlen($str); $i++)
		{
			$c = ord($str[$i]);
			for($j = 0; $j < 8; $j++)
			{
				$bit = 0;
				if($crc&0x80000000) $bit=1;
				if($c&0x80) $bit^=1;
				$c<<=1;	$crc<<=1;
				if($bit) $crc^=$polynom;
			}
		}
		$str[22] = chr($crc&0xFF);
		$str[23] = chr(($crc>>8)&0xFF);
		$str[24] = chr(($crc>>16)&0xFF);
		$str[25] = chr(($crc>>24)&0xFF); 
	}

	// Parse headers to retrieve identification and comments infos
	private function analyze()
	{
		clearstatcache();

		unset($this->Streams['vorbis']);
		unset($this->Streams['theora']); 
		unset($this->Streams['tmpfile']);
		unset($this->Streams['tmpfileptr']); 
		unset($this->Streams['size']);
		unset($this->Streams['duration']);

		$this->Streams['source'] = "file";
		$this->Streams['encoding'] = $this->utf8 ? "utf-8" : "ansi";
		$this->Data = "";

		// If remote file
		if(strpos($this->Streams['oggfile'],"http://") !== false) {
			$url = parse_url($this->Streams['oggfile']);
			$port = isset($url['port']) ? $url['port'] : 80;
			$inputfile = @fsockopen($url['host'], $port,$errno,$errstr,Core::getSetting('timeout_connect'));
			if($inputfile) {
				stream_set_timeout($inputfile, Core::getSetting('timeout_rw'));
				$query = isset($url['query']) ? "?".$url['query'] : "";
				@fwrite($inputfile, "GET ". $url['path'] . $query ." HTTP/1.1\r\nHost: ". $url['host'] ."\r\nUser-Agent: {$this->version}\r\nConnection: close\r\n\r\n");
				for($i = 0; $i < 66; $i++)
				{
					if(!feof($inputfile)) {
						$this->Data.=@fread($inputfile, 1024);
					} else {
						break;
					}
				}
				preg_match('/Content-Length: ([0-9]+)/', $this->Data, $length);
				if(isset($length[1])) $this->Streams['size'] = $length[1];
				fclose($inputfile);
			}
		}

		// Not a remote file or could not read inside it
		if(strpos($this->Data,"OggS") === false) {
			$inputfile = @fopen($this->realpath($this->Streams['oggfile']), "rb");
			if($s=@filesize($this->realpath($this->Streams['oggfile']))) $this->Streams['size'] = $s;
			if(!is_resource($inputfile)) return($this->creturn("Could not open OGG file ".$this->Streams['oggfile']));

			// First read the first 65536 bytes of the file to parse identification and comments headers
			if(!($this->Data = fread($inputfile, 0xFFFF))) return ($this->creturn("Could not read in OGG file ".$this->Streams['oggfile']));
		}
		$this->Streams['summary'] = basename($this->Streams['oggfile'])." (".floor($this->Streams['size']/1024)." kB)\n\n";

		// Parse OGG pages to retrieve interresting data
		for($pos=0;($pos=strpos($this->Data,"OggS",$pos))!==false;$pos++)
		{
			// Unknown stream structure version. We continue parsing, but don't analyse this one which might be a sync problem
			if(ord($this->Data[$pos+4]) != 0) continue;

			// Offset of the packet after header
			$packet=$pos+27+ord($this->Data[$pos+26]);

			// Read CMML data
			if(isset($this->Streams['cmml']) && $this->Read32LE($this->Data,$pos+14)==$this->Streams['cmml']['serial']) {
				$nextogg = strpos($this->Data,"OggS",$pos+1);
				if(isset($this->Streams['cmml']['text'])) $this->Streams['cmml']['text'] .= "\n";
				$this->Streams['cmml']['text'] .= substr($this->Data,$packet,$nextogg-$packet);
			}

			// Page Count = 0 : we can find the stream type and read identification headers
			if($this->Read32LE($this->Data,$pos+18)==0) {
				// Decode vorbis identification header
				if((substr($this->Data,$packet+1,6) == "vorbis") && !isset($this->Streams['vorbis'])) {
					$this->Streams['vorbis'] = array(); 
					$vorbis = &$this->Streams['vorbis'];
					$vorbis['serial'] = $this->Read32LE($this->Data,$pos+14);   
					if(ord($this->Data[$packet]) != 0x01) return($this->creturn(sprintf("Incorrect Vorbis Identification Header(0x%x)",ord($this->Data[$packet]))));
					if($this->Read32LE($this->Data,$packet+7) != 0) return($this->creturn("Incorrect Vorbis stream version = ".$this->Read32LE($this->Data,$packet+7)));
					if(($vorbis['channels'] = ord($this->Data[$packet+11])) == 0) return($this->creturn("Incorrect Vorbis channels number = ".ord($this->Data[$packet+11])));
					if(($vorbis['samplerate'] = $this->Read32LE($this->Data,$packet+12)) == 0) return($this->creturn("Incorrect Vorbis sample rate = ".$this->Read32LE($this->Data,$packet+12)));
					$vorbis['maxbitrate'] = $this->Read32LE($this->Data,$packet+16);
					$vorbis['nombitrate'] = $this->Read32LE($this->Data,$packet+20);
					$vorbis['minbitrate'] = $this->Read32LE($this->Data,$packet+24);
					$vorbis['bitrate'] = ($vorbis['nombitrate'] != 0) ? $vorbis['nombitrate'] : ($vorbis['minbitrate'] + $vorbis['maxbitrate']) / 2;
				// decode theora identification header
				} elseif((substr($this->Data,$packet+1,6)=="theora") && !isset($this->Streams['theora'])) {
					$this->Streams['theora'] = array(); 
					$theora = &$this->Streams['theora'];
					$theora['serial'] = $this->Read32LE($this->Data,$pos+14);
					if(ord($this->Data[$packet]) != 0x80) return($this->creturn(sprintf("Incorrect Theora Identification Header(0x%x)",ord($this->Data[$packet])))); 
					if((($theora['vmaj'] = ord($this->Data[$packet+7])) != 3) || (($theora['vmin'] = ord($this->Data[$packet+8])) != 2)) return($this->creturn("Incorrect Theora stream version"));
					$bitptr=($packet+9)*8;
					$theora['vrev'] = $this->readBits(8,$this->Data,$bitptr); 
					$theora['fmbw'] = $this->readBits(16,$this->Data,$bitptr);
					$theora['fmbh'] = $this->readBits(16,$this->Data,$bitptr);
					$theora['picw'] = $this->readBits(24,$this->Data,$bitptr);
					$theora['pich'] = $this->readBits(24,$this->Data,$bitptr);
					$theora['picx'] = $this->readBits(8,$this->Data,$bitptr);
					$theora['picy'] = $this->readBits(8,$this->Data,$bitptr);
					$theora['width'] = $theora['picw'];
					$theora['height'] = $theora['pich']; 
					$theora['frn'] = $this->readBits(32,$this->Data,$bitptr);
					$theora['frd'] = $this->readBits(32,$this->Data,$bitptr);
					$theora['frate'] = round($theora['frn']/$theora['frd'],2);
					$theora['parn'] = $this->readBits(24,$this->Data,$bitptr);
					$theora['pard'] = $this->readBits(24,$this->Data,$bitptr);
					if($theora['parn']*$theora['pard'] != 0) {
						$theora['pixelaspectratio'] = $theora['parn'].":".$theora['pard'];
					} else {
						$theora['pixelaspectratio'] = "1:1";
					}
					$theora['cs'] = $this->readBits(8,$this->Data,$bitptr);
					if($theora['cs'] == 1) {
						$theora['colorspace'] = "Rec. 470M";
					} elseif($theora['cs']==2) {
						$theora['colorspace']="Rec. 470BG";
					}
					$theora['nombr'] = $this->readBits(24,$this->Data,$bitptr);
					$theora['qual'] = $this->readBits(6,$this->Data,$bitptr);
					$theora['kfgshift'] = $this->readBits(5,$this->Data,$bitptr);
					$theora['pf'] = $this->readBits(2,$this->Data,$bitptr);
					if($theora['pf'] == 0) {
						$theora['pixelformat'] = "4:2:0";
					} elseif($theora['pf'] == 2) {
						$theora['pixelformat'] = "4:2:2";
					} elseif($theora['pf'] == 3) {
						$theora['pixelformat']="4:4:4";
					}
				// Decode ogg skeleton primary header
				} elseif((substr($this->Data,$packet,8) == "fishead\0") && !isset($this->Streams['skeleton'])) {
					$this->Streams['skeleton'] = array();
					$this->Streams['skeleton']['version'] = (ord($this->Data[$packet+8])+(ord($this->Data[$packet+9])<<8)).".".(ord($this->Data[$packet+10])+(ord($this->Data[$packet+11])<<8));
					if(ord($this->Data[$packet+44]) > 0) $this->Streams['skeleton']['utc'] = substr($this->Data,$packet+44,20);
				// Decode ogg CMML primary header
				} elseif((substr($this->Data,$packet,8) == "CMML\0\0\0\0") && !isset($this->Streams['cmml'])) {
					$this->Streams['cmml']=array();
					$this->Streams['cmml']['serial']=$this->Read32LE($this->Data,$pos+14);
					$this->Streams['cmml']['version']=(ord($this->Data[$packet+8])+(ord($this->Data[$packet+9])<<8)).".".(ord($this->Data[$packet+10])+(ord($this->Data[$packet+11])<<8));
				}
			// Page Count = 1 : we can read comments headers
			} elseif($this->Read32LE($this->Data,$pos+18) == 1) {
				$type = substr($this->Data,$packet+1,6);
				if($type == "vorbis") {
					if(ord($this->Data[$packet]) != 0x03) return($this->creturn(sprintf("Incorrect Vorbis Comment Header(0x%x)",ord($this->Data[$packet])))); 
					$table = &$this->Streams['vorbis'];
				} elseif($type == "theora") {
					if(ord($this->Data[$packet]) != 0x81) return($this->creturn(sprintf("Incorrect Theora Comment Header(0x%x)",ord($this->Data[$packet])))); 
					$table = &$this->Streams['theora'];
				} else {
					// Unknown page 1, neither theora nor vorbis comments
					continue;
				}

				$offset = $packet;
				$lenv = $this->Read32LE($this->Data,$offset+7);
				$table['vendor'] = $this->Decode(substr($this->Data,$offset+11,$lenv));
				$offset += 11+$lenv;
				$ncomments = $this->Read32LE($this->Data,$offset);
				$offset += 4;
				$table['comments'] = array();
				for($i = 0; $i < $ncomments; $i++)
				{
					$lcomment = $this->Read32LE($this->Data,$offset);
					$comment = $this->Decode(substr($this->Data,$offset+4,$lcomment));
					$comment = explode('=', $comment);
					$table['comments'][$comment[0]] = $comment[1];
					$offset += 4+$lcomment;
				}

				//vorbis format adds a "0x01" at the end, which theora doesn't
				if($type == "vorbis") $offset++;
				$table['commentlen'] = $offset-$packet;

				// This last part only to get necessary information to change the comments tags 
				//page position in file
				$table['commentpos'] = $pos;
				//next page position in file
				$table['commentnext'] = strpos($this->Data,"OggS",$pos+1);
				$lseg = ord($this->Data[$pos+26])-($table['commentlen']>>8)-1; 
				$table['commentleftSegments'] = substr($this->Data,$pos+27+($table['commentlen']>>8)+1,$lseg);
			}

			// we already have what we need, we can stop parsing
			if(isset($this->Streams['vorbis']['vendor']) && isset($this->Streams['theora']['vendor']) && !isset($this->Streams['cmml'])) break;
		}

		// read ogg skeleton secondary headers
		if(isset($this->Streams['skeleton'])) {
			// parse fisbone pages to retrieve interresting data
			for($pos = 0; ($pos = strpos($this->Data,"fisbone\0",$pos)) !== false; $pos++)
			{
				$serial = $this->Read32LE($this->Data,$pos+12);
				$nextfis = strpos($this->Data,"fisbone\0",$pos+1);
				$nextogg = strpos($this->Data,"OggS",$pos+1);
				$endfis = ($nextfis>0 && $nextfis<$nextogg) ? $nextfis : $nextogg;
				$posmessage = $pos+8+$this->Read32LE($this->Data,$pos+8);
				$lenmessage = $endfis-$posmessage;
				$message = trim(substr($this->Data,$posmessage,$lenmessage));
				if(strlen($message)>0) {
					if(isset($this->Streams['theora']) && $this->Streams['theora']['serial'] == $serial) $this->Streams['theora']['skeleton'] = $message;
					if(isset($this->Streams['vorbis']) && $this->Streams['vorbis']['serial'] == $serial) $this->Streams['vorbis']['skeleton'] = $message;
				}
			}
		}

		// Then read the last 65536 bytes of the file to get last granular pos to calculate streams duration
		$endbuffer = "";
		if(isset($this->Streams['size']) && ($this->Streams['size'] > 0xFFFF)) {
			// still open: so this is local file
			if(is_resource($inputfile)) {
				@fseek($inputfile,-1*0xFFFF,SEEK_END);
				if(!($endbuffer = fread($inputfile, 0xFFFF))) return ($this->creturn("Could not read in OGG file ".$this->Streams['oggfile']));
				fclose($inputfile);
			} else {
				$url = parse_url($this->Streams['oggfile']);
				$port = isset($url['port']) ? $url['port'] : 80;
				$inputfile = @fsockopen($url['host'], $port, $errno, $errstr, Core::getSetting('timeout_connect'));
				if($inputfile) {
					stream_set_timeout($inputfile, Core::getSetting('timeout_rw'));
					$query = isset($url['query']) ? "?".$url['query'] : "";
					@fwrite($inputfile, "GET ".$url['path'].$query." HTTP/1.1\r\nHost: ".$url['host']."\r\nUser-Agent: {$this->version}\r\nAccept-Ranges: bytes\r\nRange: bytes=".($this->Streams['size']-0xFFFF)."-\r\nConnection: close\r\n\r\n");
					for($i = 0; $i < 70; $i++)
					{
						if(!feof($inputfile)) {
							$endbuffer .= @fread($inputfile, 1024);
						} else {
							break;
						}
					}
					fclose($inputfile);
				}
			}
		} else {
			$endbuffer=&$this->Data;
		}

		// parse OGG pages to retrieve interesting data
		for($pos = 0; ($pos = strpos($endbuffer,"OggS",$pos)) !== false; $pos++) 
		{
			if(isset($this->Streams['vorbis']) && ($this->Read32LE($endbuffer,$pos+6) != -1) && ($this->Read32LE($endbuffer,$pos+14) == $this->Streams['vorbis']['serial']) && (ord($endbuffer[$pos+5])&0x4)) {
				$this->Streams['vorbis']['duration'] = round($this->Read32LE($endbuffer,$pos+6) / $this->Streams['vorbis']['samplerate']);
			} elseif(isset($this->Streams['theora']) && ($this->Read32LE($endbuffer,$pos+6) != -1) && ($this->Read32LE($endbuffer,$pos+14) == $this->Streams['theora']['serial'])&&(ord($endbuffer[$pos+5])&0x4)) {
				$this->Streams['theora']['framecount'] = ($this->Read32LE($endbuffer,$pos+6)>>$this->Streams['theora']['kfgshift']) + ($this->Read32LE($endbuffer,$pos+6)&(pow(2,$this->Streams['theora']['kfgshift'])-1));
				$this->Streams['theora']['duration'] = round( $this->Streams['theora']['framecount'] * $this->Streams['theora']['frd'] / $this->Streams['theora']['frn']); 
			}

			// we alread have what we need, we can stop parsing
			if(isset($this->Streams['vorbis']['duration']) && isset($this->Streams['theora']['duration'])) break;
		}

		// update summary 
		if(isset($this->Streams['theora'])) {
			$this->Streams['summary'] .= "Video (theora): ";
			if(isset($this->Streams['theora']['duration'])) $this->Streams['summary'].=$this->Streams['theora']['duration']."s ";
			$this->Streams['summary'] .= $this->Streams['theora']['width']."x".$this->Streams['theora']['height']; 
			$this->Streams['summary'] .= " ".$this->Streams['theora']['frate']."fps";
			if($q = $this->Streams['theora']['qual']) $this->Streams['summary'] .= " Q=$q";
			$this->Streams['summary'] .= "\n";
			if(isset($this->Streams['theora']['comments'])) {
				foreach($this->Streams['theora']['comments'] as $value) 
				{
					$this->Streams['summary'] .= "$value\n";
				}
			}
			$this->Streams['summary'] .= "\n";
		}

		if(isset($this->Streams['vorbis'])) {
			$this->Streams['summary'] .= "Audio (Vorbis";
			$this->Streams['summary'] .= " ".floor($this->Streams['vorbis']['bitrate']/1000)."kb/s";
			$this->Streams['summary'] .= "): ";
			if(isset($this->Streams['vorbis']['duration'])) $this->Streams['summary'] .= $this->Streams['vorbis']['duration']."s ";
			$this->Streams['summary'] .= ($this->Streams['vorbis']['channels']>1) ? "stereo" : "mono";
			$this->Streams['summary'] .= " ".floor($this->Streams['vorbis']['samplerate']/1000)."kB/s\n";
			if(isset($this->Streams['vorbis']['comments'])) {
				foreach($this->Streams['vorbis']['comments'] as $value)
				{
					$this->Streams['summary'] .= "$value\n";
				}
			}
		}

		//global duration is the biggest of each
		if(isset($this->Streams['vorbis']['duration']) && isset($this->Streams['theora']['duration'])) {
			$this->Streams['duration'] = $this->Streams['vorbis']['duration'] > $this->Streams['theora']['duration'] ? $this->Streams['vorbis']['duration'] : $this->Streams['theora']['duration'];
		} elseif(isset($this->Streams['vorbis']['duration'])) {
			$this->Streams['duration']=$this->Streams['vorbis']['duration'];
		} elseif(isset($this->Streams['theora']['duration'])) {
			$this->Streams['duration']=$this->Streams['theora']['duration'];
		}

		if($this->caching) $this->CacheUpdate();
		return($this->creturn(true));
	}
}
?>