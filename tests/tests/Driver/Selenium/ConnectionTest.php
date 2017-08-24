<?php

use Openbuildings\Spiderling\Driver_Selenium_Connection;

/**
 * @package spiderling
 * @group   driver
 * @group   driver.selenium
 * @group   driver.selenium.connection
 */
class Driver_Selenium_ConnectionTest extends Spiderling_TestCase {

	public function test_command_url_and_accessors()
	{
		$connection = new Driver_Selenium_Connection('test');
		$this->assertEquals('test', $connection->server());
		$connection->server('http://localhost:4444/wd/hub/');

		foreach ($connection->get('sessions') as $session)
		{
			$connection->delete('session/'.$session['id']);
		}

		$connection->start();

		$this->assertTrue($connection->is_started());

		$connection = new Driver_Selenium_Connection();

		$connection->start();

		$this->assertTrue($connection->is_started());

		$connection = new Driver_Selenium_Connection();

		$this->assertCount(1, $connection->get('sessions'));

		// var_dump($connection->get('sessions'));
	}
}

