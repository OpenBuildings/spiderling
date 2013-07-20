<?php

namespace Openbuildings\Spiderling;

/**
 * @package    Func_Test
 * @author     Ivan Kerin
 * @copyright  (c) 2012 OpenBuildings Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Network {

	public static function is_port_open($host, $port)
	{
		$connection = @fsockopen($host, $port);
		if (is_resource($connection))
		{
			fclose($connection);
			return FALSE;	
		}
		return TRUE;
	}

	public static function ephimeral_port($host, $range_start = 1000, $range_end = 5000, $timeout = 1000)
	{
		return Attempt::make(function() use ($host, $range_start, $range_end) {
			$port = rand($range_start, $range_end);
			return Network::is_port_open($host, $port) ? $port : FALSE;
		}, $timeout);
	}
}
