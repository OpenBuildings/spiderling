<?php

namespace Openbuildings\Spiderling;

/**
 * Extend exception to allow variables
 *
 * @package    Openbuildings\Spiderling
 * @author     Ivan Kerin
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Exception extends \Exception {

	public function __construct($message, array $variables = array(), \Exception $previous = NULL)
	{
		if ($variables)
		{
			$message = strtr($message, $variables);
		}

		parent::__construct($message, 0, $previous);
	}
}
