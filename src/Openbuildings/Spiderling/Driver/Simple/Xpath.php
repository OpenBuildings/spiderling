<?php

namespace Openbuildings\Spiderling;

/**
 * Xpath extension for native driver
 *
 * @package    Openbuildings\Spiderling
 * @author     Ivan Kerin
 * @copyright  (c) 2012-2013 OpenBuildings Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
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