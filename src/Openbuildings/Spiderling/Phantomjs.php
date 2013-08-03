<?php

namespace Openbuildings\Spiderling;

/**
 * A class for starting and stopping phantomjs service, using Spiderling assets
 * 
 * @package    Openbuildings\Spiderling
 * @author     Ivan Kerin
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Phantomjs {

	public static function start($file, $port, $additional = NULL, $log_file = '/dev/null')
	{
		if ( ! Network::is_port_open('localhost', $port))
			throw new Exception('Port :port is already taken', array(':port' => $port));

		return shell_exec(strtr('nohup :command > :log 2> :log & echo $!', array(
			':command' => self::command($file, $port, $additional),
			':log' => $log_file,
		)));
	}

	public static function kill($pid)
	{
		shell_exec('kill '.$pid);
	}

	public static function command($file, $port, $additional = NULL)
	{
		$dir = realpath(__DIR__.'/../../../assets').'/';

		$file = $dir.$file;

		if ( ! is_file($file)) 
			throw new Exception('Cannot start phantomjs: file :file is not found', array(':file' => $file));

		if ($additional) 
		{
			if ( ! is_file($file)) 
				throw new Exception('Cannot start phantomjs: file :additional is not found', array(':additional' => $additional));

			$additional = $dir.$additional;
		}

		return "phantomjs --ignore-ssl-errors=true {$file} {$port} {$additional}";
	}
}
