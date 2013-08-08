<?php

use Openbuildings\Spiderling\Phantomjs;
use Openbuildings\Spiderling\Network;
use Openbuildings\Spiderling\Attempt;
use Openbuildings\Spiderling\Driver_Phantomjs_Connection;

/**
 * @package spiderling
 * @group   driver
 * @group   driver.phantomjs
 */
class Driver_Phantomjs_ConnectionTest extends Spiderling_TestCase {

	public $connection;

	public function setUp()
	{
		$this->connection = new Driver_Phantomjs_Connection('http://localhost');
	}

	public function test_command_url_and_accessors()
	{
		$this->connection
			->server('http://example.com');

		$this->connection
			->port(6000);

		$expected_url = 'http://example.com:6000/test';

		$this->assertEquals($expected_url, $this->connection->command_url('test'));
	}

	public function test_start_and_stop()
	{
		$this->assertFalse($this->connection->is_running());

		file_put_contents(TESTVIEWS.'test.pid', Phantomjs::start('echo.js', 4441));

		Attempt::make(function() {
			return ! Network::is_port_open('localhost', 4441);
		});

		$start_result = $this->connection
			->port(6000)
			->start(TESTVIEWS.'test.pid');

		$this->assertTrue(Attempt::make(function() {
			return Network::is_port_open('localhost', 4441);
		}), 'Should remove old running phantomjs from the pid file');


		$this->assertFileExists(TESTVIEWS.'test.pid');
		$this->assertEquals(TESTVIEWS.'test.pid', $this->connection->pid_file());
		$this->assertEquals($this->connection->pid(), file_get_contents($this->connection->pid_file()));

		$this->assertTrue($start_result);

		$this->assertTrue($this->connection->is_running());

		$stop_result = $this->connection
			->stop();

		$this->assertTrue($stop_result);
		$this->assertFileNotExists(TESTVIEWS.'test.pid');

		$this->assertFalse($this->connection->is_running());
	}
}

