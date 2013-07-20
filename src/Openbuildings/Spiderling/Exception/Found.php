<?php

namespace Openbuildings\Spiderling;

/**
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2013 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Exception_Found extends Exception {

	public $driver;
	public $locator;

	public function __construct(Locator $locator, Driver $driver)
	{
		$this->driver = $driver;
		$this->locator = $locator;

		parent::__construct('Item (:type) ":selector", filters :filters, found by driver ":driver"', array(
			':type' => $locator->type(), 
			':selector' => $locator->selector(),
			':driver' => $driver->name, 
			':filters' => json_encode($locator->filters()),
		));
	}
}
