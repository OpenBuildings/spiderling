<?php

namespace Openbuildings\Spiderling;

/**
* 
*/
class Environment_Group_Server implements Environment_Group {

	public function set($name, $value)
	{
		if ($value instanceof Environment_Notset) 
		{
			unset($_SERVER[$name]);
		}
		else
		{
			$_SERVER[$name] = $value;
		}
	}

	public function get($name)
	{
		return isset($_SERVER[$name]) ? $_SERVER[$name] : new Environment_Notset;
	}

	public function has($name)
	{
		return (preg_match('/^[A-Z_-]+$/', $name) OR isset($_SERVER[$name]));
	}
}