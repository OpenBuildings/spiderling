<?php

use Openbuildings\Spiderling\Environment_Group_Server;
use Openbuildings\Spiderling\Environment_Notset;

/**
 * @package spiderling
 * @group   environment
 * @group   environment.server
 */
class Environment_Group_ServerTest extends PHPUnit_Framework_TestCase {

	public function test_methods()
	{
		$group = new Environment_Group_Server;

		$_SERVER = array('HOST' => 'some host', 'REQUEST_URI' => 'some uri');

		$this->assertEquals('some host', $group->get('HOST'));
		$this->assertEquals('some uri', $group->get('REQUEST_URI'));
		$this->assertInstanceOf('Openbuildings\Spiderling\Environment_Notset', $group->get('SOME_VARIABLE'));

		$group->set('HOST', 'new host');
		$group->set('REQUEST_URI', new Environment_Notset);

		$this->assertEquals(array('HOST' => 'new host'), $_SERVER);

		$this->assertEquals('new host', $group->get('HOST'));

		$this->assertTrue($group->has('REQUEST_URI'));
		$this->assertTrue($group->has('HOST'));
		$this->assertTrue($group->has('SOME_VARIABLE'));
		$this->assertFalse($group->has('other variable'));
	}
}