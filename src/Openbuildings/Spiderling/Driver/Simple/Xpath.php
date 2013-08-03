<?php

namespace Openbuildings\Spiderling;

/**
 * Xpath extension for simple driver
 *
 * @package    Openbuildings\Spiderling
 * @author     Ivan Kerin
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Driver_Simple_Xpath extends \DOMXpath
{
	public function find($expression, $contextnode = NULL)
	{
		if ($expression instanceof \DOMNode)
			return $expression;

		@ $items = $contextnode ? $this->query($expression, $contextnode) : $this->query($expression);

		if ( ! $items)
			throw new Exception_Xpath('Error in expression: ":expression"', array(':expression' => $expression));

		if ($items->length == 0)
			throw new Exception_Xpath('No element for selector ":expression"', array(':expression' => $expression));

		return $items->item(0);

	}
}