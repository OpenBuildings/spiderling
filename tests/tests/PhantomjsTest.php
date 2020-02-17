<?php


use Openbuildings\Spiderling\Network;
use Openbuildings\Spiderling\Attempt;
use Openbuildings\Spiderling\Phantomjs;
use PHPUnit\Framework\TestCase;

/**
 * @package spiderling
 * @group   phantomjs
 */
class PhantomjsTest extends TestCase {

	public function test_methods()
	{
		$this->assertTrue(Network::is_port_open('localhost', 4440));

		$pid = Phantomjs::start('echo.js', 4440);

		$this->assertTrue(Attempt::make(function() {
			return ! Network::is_port_open('localhost', 4440);
		}), 'Should be running after some attempts');


		try
		{
			Phantomjs::start('echo.js', 4440);
			$this->fail('Should rise an exception');
		}
		catch (Exception $e)
		{
			// Pass
		}

		Phantomjs::kill($pid);

		$this->assertTrue(Attempt::make(function() {
			return Network::is_port_open('localhost', 4440);
		}), 'Should not be running after some attempts');
	}
}
