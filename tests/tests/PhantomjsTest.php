<?php

use Openbuildings\Spiderling\Driver_Phantomjs_Connection;
use Openbuildings\Spiderling\Network;
use Openbuildings\Spiderling\Attempt;
use Openbuildings\Spiderling\Phantomjs;

/**
 * @package spiderling
 * @group   phantomjs
 */
class PhantomjsTest extends PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->connection = new Driver_Phantomjs_Connection('http://localhost');
	}

	public function test_methods()
	{
		$this->assertTrue(Network::is_port_open('localhost', 4440));
		$this->connection->port(4440);
		$pid = $this->connection->start('echo.js');

		$this->assertTrue(Attempt::make(function() {
			return ! Network::is_port_open('localhost', 4440);
		}), 'Should be running after some attempts');


		try
		{
			$this->connection->port(4440);
			$this->connection->start('echo.js');
			$this->fail('Should rise an exception');
		}
		catch (Exception $e)
		{
			// Pass
		}

		$this->connection->kill($pid);

		$this->assertTrue(Attempt::make(function() {
			return Network::is_port_open('localhost', 4440);
		}), 'Should not be running after some attempts');
	}
}
