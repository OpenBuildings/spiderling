<?php

namespace Openbuildings\Spiderling;

/**
* 
*/
class Environment_Group_Static implements Environment_Group {

	public function set($name, $value)
	{
		list($class, $name) = explode('::$', $name, 2);

		$class = new \ReflectionClass($class);
		$property = $class->getProperty($name);
		$property->setAccessible(TRUE);
		$property->setValue(NULL, $value);
	}

	public function get($name)
	{
		list($class, $name) = explode('::$', $name, 2);

		$class = new \ReflectionClass($class);
		$property = $class->getProperty($name);
		$property->setAccessible(TRUE);

		return $property->getValue();
	}

	public function has($name)
	{
		return strpos($name, '::$') !== FALSE;
	}
}