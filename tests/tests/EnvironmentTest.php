<?php


use Openbuildings\Spiderling\Environment_Group_Dummy;
use Openbuildings\Spiderling\Environment;

/**
 * @package spiderling
 * @group   spiderling.environment
 */
class EnvironmentTest extends PHPUnit_Framework_TestCase {

	public function test_construct()
	{
		$group = new Environment_Group_Dummy;

		$existing_environment = array(
			'test_existing_key' => 'some value'
		);

		$expected_environment = array(
			'test_key' => 'test value',
			'test_existing_key' => 'new value',
		);

		$group->variables = $existing_environment;

		$environment = new Environment(array('dummy' => $group), $expected_environment);

		$this->assertSame($group, $environment->groups('dummy'), 'Should be able to set / get the group');

		$this->assertEquals($expected_environment, $group->variables, 'Should set the variables when created with parameters');

		$environment->restore();

		$this->assertEquals($existing_environment, $group->variables, 'Should restore variables to original state');

		$environment->backup(array('test_existing_key'));

		$this->assertEquals($existing_environment, $group->variables, 'Backup should not affect variables');

		$environment->set(array('test_existing_key' => 'new value'));

		$this->assertEquals(array('test_existing_key' => 'new value'), $group->variables);

		$environment->restore();

		$this->assertEquals($existing_environment, $group->variables);

		$group2 = new Environment_Group_Dummy;

		$environment->groups('dummy', $group2);

		$this->assertSame($group2, $environment->groups('dummy'), 'Test individual group setter');

		$this->setExpectedException('Openbuildings\Spiderling\Exception');

		$environment->group_for_name('not_test');
	}


}