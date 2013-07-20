<?php

namespace Openbuildings\Spiderling;

/**
 * Func_Test Basic driver you have to extend this class and implement its functions
 *
 * @package    Func_Test
 * @author     Ivan Kerin
 * @copyright  (c) 2012 OpenBuildings Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Attempt {

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
