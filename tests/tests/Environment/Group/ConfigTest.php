<?php


use Openbuildings\Spiderling\Environment_Group_Config;

/**
 * @package spiderling
 * @group   spiderling.environment
 * @group   spiderling.environment.config
 */
class Environment_Group_ConfigTest extends PHPUnit_Framework_TestCase {

	public function test_methods()
	{
		$group = new Environment_Group_Config;

		Kohana::$config->load('environment-test')->set('test', 'test value');

		$this->assertEquals('test value', $group->get('environment-test.test'));
		$this->assertEquals(NULL, $group->get('environment-test.new'));

		$group->set('environment-test.new', 'new value');

		$this->assertEquals('new value', $group->get('environment-test.new'));

		$this->assertTrue($group->has('environment-test.some'));
		$this->assertFalse($group->has('environment-test'));
	}
}