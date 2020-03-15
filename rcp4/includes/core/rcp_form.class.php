<?php
/**
* remoteCP 4
* ütf-8 release
*
* @package remoteCP
* @author hal.sascha
* @copyright (c) 2006-2009
* @version 4.0.3.5
*/
class rcp_form
{
	/**
	* Validates a HTML form
	*
	* $array contains a array of HTML form elements
	* @param array $array
	* @author hal.sascha
	*/
	public function check($array)
	{
		if(is_array($array)) {
			foreach($array AS $value)
			{
				if($value === true) {
					return false;
				}
			}
		}
		return true;
	}
}
?>