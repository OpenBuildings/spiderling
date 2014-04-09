<?php

namespace Openbuildings\Spiderling;

/**
 * @package    Openbuildings\Spiderling
 * @author     Ivan Kerin
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
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
