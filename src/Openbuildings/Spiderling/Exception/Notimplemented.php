<?php

namespace Openbuildings\Spiderling;

/**
 * @package    Openbuildings\Spiderling
 * @author     Ivan Kerin
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Exception_Notimplemented extends Exception {

	public function __construct($method, Driver $driver)
	{
		parent::__construct('Method \':method\' not implemented by driver \':driver\'', array(':method' => $method, ':driver' => $driver->name));
	}
}
