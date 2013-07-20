<?php

use Openbuildings\Spiderling\Driver_Kohana;
use Openbuildings\Spiderling\PHPUnit_TestCase_Spiderling;

/**
 * @package spiderling
 * @group   spiderling
 * @group   spiderling.driver
 * @group   spiderling.driver.kohana
 */
class Driver_KohanaTest extends PHPUnit_TestCase_Spiderling {

	public function setUp()
	{
		$_SERVER['SERVER_NAME'] = 'example.com';
		$_SERVER['HTTP_USER_AGENT'] = 'Test User Agent';
	}

	public function test_request()
	{
		$driver = new Driver_Kohana();
		$driver->get('/test/index');

		$this->assertInstanceOf('\Response', $driver->response());
		$this->assertEquals(200, $driver->response()->status());

		$this->assertEquals('Index View', $driver->content());

		$this->assertEquals('http://example.com/test/index', $driver->current_url());
		$this->assertEquals('test/index', $driver->current_path());
		$this->assertEquals('Test User Agent', $driver->user_agent());
	}

	public function test_redirect()
	{
		$driver = new Driver_Kohana();
		$driver->get('/test/redirected');
		$this->assertInstanceOf('\Response', $driver->response());
		$this->assertEquals(200, $driver->response()->status());

		$this->assertEquals('Final View', $driver->content());

		$driver->request_factory()->max_redirects(1);
		$this->setExpectedException('Openbuildings\Spiderling\Exception_Toomanyredirects');
		$driver->get('/test/redirected');
	}

	public function test_too_many_redirects()
	{
		$this->setExpectedException('Openbuildings\Spiderling\Exception_Toomanyredirects');

		$driver = new Driver_Kohana();
		$driver->get('/test/too_many_redirects');
	}
}

