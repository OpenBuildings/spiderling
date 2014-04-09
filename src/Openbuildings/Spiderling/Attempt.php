<?php

namespace Openbuildings\Spiderling;

/**
 * A helper class to express attempting to do something several times with a small interval
 *
 * @package    Openbuildings\Spiderling
 * @author     Ivan Kerin
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Attempt {

	/**
	 * Execute $callable until $timeout is reached. By doing an attempt each $step milliseconds
	 * @param  Callable  $callbale the method to be called, if the result is FALSE, try again until TRUE or timeout reached
	 * @param  integer $timeout  timeout in milliseconds
	 * @param  integer $step
	 * @return mixed            first not FALSE result
	 */
	public static function make($callbale, $timeout = 2000, $step = 50)
	{
		$retries = ceil($timeout / $step);

		do
		{
			$result = $callbale();
			$retries -= 1;
			if ( ! $result)
			{
				usleep($step * 1000);
			}
		}
		while ($retries > 0 AND ! $result);

		return $result;
	}

}
