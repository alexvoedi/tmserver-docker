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
class rcp_usage
{
	public  $outmode  = true;
	public  $type     = 'rcp_ramusage';
	private $elements = array();
	private $file     = false;
	private $enabled  = false;

	public function __construct()
	{
		$this->enabled = Core::getSetting('debug');
		$suffix = Core::getSetting('live') ? 'live' : 'web';
		$this->file = 'performance_'.$suffix;
	}

	public function beginCheck($id, $force = false)
	{
		if(!$this->enabled) return;
		$this->elements[$id] = new $this->type;
		$this->elements[$id]->beginCheck($id, $force);
	}

	public function stopCheck($id)
	{
		if(!$this->enabled) return;
		$this->elements[$id]->stopCheck();
		$result = $this->elements[$id]->GetResult();
		if($result[0]) {
			$this->printOutput($result[1]);
		}

		$this->elements[$id] = null;
		unset($this->elements[$id]);

		return isset($result) ? $result : 0;
	}

	private function printOutput($string)
	{
		if(!$this->enabled) return;
		if($this->outmode) {
			writeLog($this->file, $string);
		} else {
			echo $string."\r\n";
		}
	}
}

class rcp_ramusage
{
	private $id;
	private $start;
	private $end;
	private $force;

	public function __construct()
	{
		$this->start = 0;
		$this->end   = 0;
	}

	public function beginCheck($id,$force)
	{
		$this->id    = $id;
		$this->start = memory_get_usage();
		$this->force = $force;
	}

	public function stopCheck()
	{
		$this->end = memory_get_usage();
	}

	public function getResult()
	{
		$result = (int)$this->end - (int)$this->start;
		$result = $result / 1024;
		$result = sprintf('%4d', $result);
		return array($this->force ? true : $result, "MemoryUsage(kb): \t{$result} \t{$this->id}");
	}
}

class rcp_timeusage
{
	private $id;
	private $start;
	private $end;
	private $force;

	public function __construct()
	{
		$this->start = 0;
		$this->end   = 0;
	}

	public function beginCheck($id,$force)
	{
		$this->id    = $id;
		$this->start = microtime(true);
		$this->force = $force;
	}

	public function stopCheck()
	{
		$this->end = microtime(true);
	}

	public function getResult()
	{
		$result = $this->end - $this->start;
		$result = round($result, 4);
		return array($this->force ? true : $result, "TimeUsage(sec.): \t{$result} \t{$this->id}");
	}
}
?>