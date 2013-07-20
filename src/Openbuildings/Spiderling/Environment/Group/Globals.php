<?php

namespace Openbuildings\Spiderling;

/**
* 
*/
class Environment_Group_Globals implements Environment_Group {

	public function set($name, $value)
	{
		global $$name;

		$$name = $value; 
	}

	public function get($name)
	{
		global $$name;

		return $$name;
	}

	public function has($name)
	{
		return in_array($name, array('_GET', '_POST', '_SERVER', '_FILES', '_COOKIE', '_SESSION'));
	}
}