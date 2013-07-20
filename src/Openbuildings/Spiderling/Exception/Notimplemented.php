<?php

namespace Openbuildings\Spiderling;

/**
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2013 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Exception_Notimplemented extends Exception {
	
	public function __construct($method, Driver $driver)
	{
		parent::__construct('Method \':method\' not implemented by driver \':driver\'', array(':method' => $method, ':driver' => $driver->name));
	}
}
