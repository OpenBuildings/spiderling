<?php

use Openbuildings\Spiderling\Driver_Simple_RequestFactory_HTTP;

/**
 * @package spiderling
 * @group   driver
 * @group   driver.simple
 */
class Driver_Simple_RequestFactory_HTTPTest extends Spiderling_TestCase {

	public $factory;

	public function setUp()
	{
		$this->factory = new Driver_Simple_RequestFactory_HTTP();
	}

	public function test_request()
	{
		$content = $this->factory->execute('GET', 'http://clippings-spiderling.s3-website-eu-west-1.amazonaws.com/remote-form.html', array('test' => 'value'));

		$this->assertContains('<legend>Author</legend>', $content);
		$this->assertEquals('http://clippings-spiderling.s3-website-eu-west-1.amazonaws.com/remote-form.html', $this->factory->current_url());
		$this->assertEquals('/remote-form.html', $this->factory->current_path());

		$this->expectException('Openbuildings\Spiderling\Exception_Curl');

		$this->factory->execute('GET', 'http://clippings-spiderling.s3-website-eu-west-1.amazonaws.com/not-existst.html');
	}

	public function test_user_agent()
	{
		$agent = $this->factory->user_agent();
		$this->assertEquals('Spiderling Simple Driver', $agent);

		$this->factory->user_agent('Test');

		$agent = $this->factory->user_agent();
		$this->assertEquals('Test', $agent);
	}
}

