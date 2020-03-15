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
class rcp_timedevents
{

	/**
	 * Array of timedevent objects
	 * @access protected
	 */
	private $events = array();

	/**
	* Executes TimedEvents with a callback function
	*
	* This functions executes all with update() added events and call there callback function.
	* Timed events without endless looping will be removed after the execution-count is reached
	* @author hal.sascha
	*/
	public function execute()
	{
		if(empty($this->events)) return;
		foreach($this->events AS $key => $event)
		{
			$callback = $event->getCallback();
			if(!$event->error) {
				if($callback) {
					Plugins::triggerEvent($callback, $event->params);
				}
			} else {
				$this->remove($key);
			}
		}
	}

	/**
	* Adds a new timed event
	*
	* Creates a new timed event with a new $id and a strtotime() formated string.
	* The specified callback will be called if the time is reached and the repeat count is true.
	* @param string $id
	* @param string $time
	* @param int $repeat
	* @param string $callback
	* @param array $params
	* @author hal.sascha
	*/
	public function update($id, $timestr, $repeat, $callback, $params = false)
	{
		$this->events[$id] = new rcp_timedevent($timestr, $repeat, $callback, $params);
	}

	/**
	* Removes a timed event
	*
	* @param string $id
	* @author hal.sascha
	*/
	public function remove($id)
	{
		$this->events[$id] = null;
		unset($this->events[$id]);
	}

	/**
	* Checks if a timedevent with a specified ID is available
	*
	* @param string $id
	* @author hal.sascha
	*/
	public function check($id)
	{
		return array_key_exists($id, $this->events);
	}

	/**
	* Returns a array with all timedevent objects
	*
	* @author hal.sascha
	*/
	public function getAll()
	{
		return $this->events;
	}
}

class rcp_timedevent
{
	private $timestr;
	private $triggertime;
	private $repeat;
	private $repeatc;
	private $callback;
	public  $params;
	public  $error;

	public function __construct($timestr, $repeat, $callback, $params)
	{
		$this->timestr	= $timestr;
		$this->repeat	= $repeat; //Set repeat = 0 for unlimited repetitions
		$this->callback	= $callback;
		$this->params	= $params;
		$this->repeatc	= 0;
		$this->update();
	}

	public function update()
	{
		$this->triggertime = strtotime($this->timestr);
		if(!$this->triggertime) $this->error = true;
		++$this->repeatc;
	}

	public function getCallback()
	{
		if(Core::getSetting('time') > $this->triggertime) {
			if(!$this->repeat || $this->repeatc <= $this->repeat) {
				$this->update();
				return $this->callback;
			} else {
				$this->error = true;
			}
		}
		return false;
	}
}
?>