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

	/**
	 * Start a phantomjs server in the background. Set port, server js file, additional files and log file.
	 *
	 * @param  string $file       the server js file
	 * @param  integer $port      the port to start the server on
	 * @param  string $additional additional file, passed to the js server
	 * @param  string $log_file
	 * @return string             the pid of the newly started process
	 */
	public static function start($file, $port, $additional = NULL, $log_file = '/dev/null')
	{
		if ( ! Network::is_port_open('localhost', $port))
			throw new Exception('Port :port is already taken', array(':port' => $port));

		if ($log_file !== '/dev/null' AND ! is_file($log_file))
			throw new Exception('Log file (:log_file) must be a file or /dev/null', array(':log_file' => $log_file));

		return shell_exec(strtr('nohup :command > :log 2> :log & echo $!', array(
			':command' => self::command($file, $port, $additional),
			':log' => $log_file,
		)));
	}

	/**
	 * kill a server on a given pid
	 * @param  string $pid
	 */
	public static function kill($pid)
	{
		shell_exec('kill '.$pid);
	}

	/**
	 * Return the command to start the phantomjs server
	 *
	 * @param  string $file       the server js file
	 * @param  integer $port
	 * @param  string $additional additional js file
	 * @return string
	 */
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

		return "phantomjs --ssl-protocol=any --ignore-ssl-errors=true {$file} {$port} {$additional}";
	}
}
