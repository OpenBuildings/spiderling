<?php

namespace Openbuildings\Spiderling;

/**
* 
*/
class Environment_Group_Config implements Environment_Group {

	public function set($name, $value)
	{
		list($group, $name) = explode('.', $name, 2);

		\Kohana::$config->load($group)->set($name, $value);
	}

	public function get($name)
	{
		return \Kohana::$config->load($name);
	}

	public function has($name)
	{
		return strpos($name, '.') !== FALSE;
	}
}