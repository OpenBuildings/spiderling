<?php


use Openbuildings\Spiderling\Environment_Group_Globals;

/**
 * @package spiderling
 * @group   spiderling.environment
 * @group   spiderling.environment.globals
 */
class Environment_Group_GlobalsTest extends PHPUnit_Framework_TestCase {

	public function test_methods()
	{
		$group = new Environment_Group_Globals;

		$_POST = array('some name' => 'some value');
		$_GET = array('some 2 name' => 'some 2 value');

		$this->assertEquals($_POST, $group->get('_POST'));
		$this->assertEquals($_GET, $group->get('_GET'));
		$this->assertEquals(array(), $group->get('_FILES'));

		$group->set('_POST', array('new name' => 'new value'));

		$this->assertEquals(array('new name' => 'new value'), $group->get('_POST'));
		$this->assertEquals(array('new name' => 'new value'), $_POST);

		$this->assertTrue($group->has('_POST'));
		$this->assertTrue($group->has('_GET'));
		$this->assertTrue($group->has('_SERVER'));
		$this->assertTrue($group->has('_FILES'));
		$this->assertFalse($group->has('_TEST'));
	}
}