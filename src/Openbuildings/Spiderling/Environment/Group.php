<?php

namespace Openbuildings\Spiderling;

/**
* 
*/
interface Environment_Group {

	public function set($name, $value);

	public function get($name);

	public function has($name);
}