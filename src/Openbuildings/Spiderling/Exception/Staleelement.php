<?php

namespace Openbuildings\Spiderling;

/**
 * @package    Openbuildings\Spiderling
 * @author     Ivan Kerin
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Exception_Staleelement extends Exception {

	public function __construct(\Exception $previous = NULL)
	{
		parent::__construct(
			'StaleElementReferenceException: Element is no longer attached to the DOM',
			array(),
			$previous
		);

		$this->code = 10;
	}
}
