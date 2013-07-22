<?php

use Openbuildings\Spiderling\Environment_Group_Static;

class Environment_Group_StaticDummy {

	public static $var_public       = 'value 1';
	protected static $var_protected = 'value 2';
	private static $var_private     = 'value 3';

	public static function get_var_protected()
	{
		return self::$var_protected;
	}	

	public static function get_var_private()
	{
		return self::$var_private;
	}
}

/**
 * @package spiderling
 * @group   environment
 * @group   environment.static
 */
class Environment_Group_StaticTest extends PHPUnit_Framework_TestCase {


	public function test_methods()
	{
		$group = new Environment_Group_Static;

		$this->assertEquals('value 1', $group->get('Environment_Group_StaticDummy::$var_public'));
		$this->assertEquals('value 2', $group->get('Environment_Group_StaticDummy::$var_protected'));
		$this->assertEquals('value 3', $group->get('Environment_Group_StaticDummy::$var_private'));

		$group->set('Environment_Group_StaticDummy::$var_public', 'new 1');
		$group->set('Environment_Group_StaticDummy::$var_protected', 'new 2');
		$group->set('Environment_Group_StaticDummy::$var_private', 'new 3');

		$this->assertEquals('new 1', Environment_Group_StaticDummy::$var_public);
		$this->assertEquals('new 2', Environment_Group_StaticDummy::get_var_protected());
		$this->assertEquals('new 3', Environment_Group_StaticDummy::get_var_private());

		$this->assertEquals('new 1', $group->get('Environment_Group_StaticDummy::$var_public'));
		$this->assertEquals('new 2', $group->get('Environment_Group_StaticDummy::$var_protected'));
		$this->assertEquals('new 3', $group->get('Environment_Group_StaticDummy::$var_private'));

		$this->assertTrue($group->has('Environment_Group_StaticDummy::$var_public'));
		$this->assertTrue($group->has('Environment::$some'));
		$this->assertFalse($group->has('other variable'));
	}
}
