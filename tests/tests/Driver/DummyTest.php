<?php

use Openbuildings\Spiderling\Driver_Dummy;
use Openbuildings\Spiderling\Exception_Notimplemented;

/**
 * @package spiderling
 * @group   driver
 * @group   driver.dummy
 */
class Driver_DummyTest extends Spiderling_TestCase {

	public $driver;

	public function setUp()
	{
		$this->driver = new Driver_Dummy();
	}

	/**
	 * @expectedException Openbuildings\Spiderling\Exception_Notimplemented
	 */
	public function test_clear()
	{
		$this->driver->clear();
	}

	/**
	 * @expectedException Openbuildings\Spiderling\Exception_Notimplemented
	 */
	public function test_all()
	{
		$this->driver->all(0);
	}

	/**
	 * @expectedException Openbuildings\Spiderling\Exception_Notimplemented
	 */
	public function test_find()
	{
		$this->driver->find(0);
	}

	/**
	 * @expectedException Openbuildings\Spiderling\Exception_Notimplemented
	 */
	public function test_content()
	{
		$this->driver->content();
	}

	/**
	 * @expectedException Openbuildings\Spiderling\Exception_Notimplemented
	 */
	public function test_tag_name()
	{
		$this->driver->tag_name(0);
	}

	/**
	 * @expectedException Openbuildings\Spiderling\Exception_Notimplemented
	 */
	public function test_attribute()
	{
		$this->driver->attribute(0, 'test');
	}

	/**
	 * @expectedException Openbuildings\Spiderling\Exception_Notimplemented
	 */
	public function test_html()
	{
		$this->driver->html(0);
	}

	/**
	 * @expectedException Openbuildings\Spiderling\Exception_Notimplemented
	 */
	public function test_text()
	{
		$this->driver->text(0);
	}

	/**
	 * @expectedException Openbuildings\Spiderling\Exception_Notimplemented
	 */
	public function test_value()
	{
		$this->driver->value(0);
	}

	/**
	 * @expectedException Openbuildings\Spiderling\Exception_Notimplemented
	 */
	public function test_is_visible()
	{
		$this->driver->is_visible(0);
	}

	/**
	 * @expectedException Openbuildings\Spiderling\Exception_Notimplemented
	 */
	public function test_is_selected()
	{
		$this->driver->is_selected(0);
	}

	/**
	 * @expectedException Openbuildings\Spiderling\Exception_Notimplemented
	 */
	public function test_is_checked()
	{
		$this->driver->is_checked(0);
	}

	/**
	 * @expectedException Openbuildings\Spiderling\Exception_Notimplemented
	 */
	public function test_set()
	{
		$this->driver->set(0, 1);
	}

	/**
	 * @expectedException Openbuildings\Spiderling\Exception_Notimplemented
	 */
	public function test_select_option()
	{
		$this->driver->select_option(0, 1);
	}

	/**
	 * @expectedException Openbuildings\Spiderling\Exception_Notimplemented
	 */
	public function test_click()
	{
		$this->driver->click(0);
	}

	/**
	 * @expectedException Openbuildings\Spiderling\Exception_Notimplemented
	 */
	public function test_visit()
	{
		$this->driver->visit('test');
	}

	/**
	 * @expectedException Openbuildings\Spiderling\Exception_Notimplemented
	 */
	public function test_current_path()
	{
		$this->driver->current_path();
	}

	/**
	 * @expectedException Openbuildings\Spiderling\Exception_Notimplemented
	 */
	public function test_current_url()
	{
		$this->driver->current_url();
	}

	/**
	 * @expectedException Openbuildings\Spiderling\Exception_Notimplemented
	 */
	public function test_confirm()
	{
		$this->driver->confirm(TRUE);
	}

	/**
	 * @expectedException Openbuildings\Spiderling\Exception_Notimplemented
	 */
	public function test_alert_text()
	{
		$this->driver->alert_text();
	}

	/**
	 * @expectedException Openbuildings\Spiderling\Exception_Notimplemented
	 */
	public function test_is_page_active()
	{
		$this->driver->is_page_active();
	}

	public function test_javascript_errors()
	{
		$this->assertEquals(array(), $this->driver->javascript_errors());
	}

	public function test_javascript_messages()
	{
		$this->assertEquals(array(), $this->driver->javascript_messages());
	}

	public function test_page()
	{
		$page = $this->driver->page();
		$this->assertInstanceOf('Openbuildings\Spiderling\Node', $page);
	}

	/**
	 * @expectedException Openbuildings\Spiderling\Exception_Notimplemented
	 */
	public function test_move_to()
	{
		$this->driver->move_to(0, 0, 0);
	}

	/**
	 * @expectedException Openbuildings\Spiderling\Exception_Notimplemented
	 */
	public function test_screenshot()
	{
		$this->driver->screenshot('test');
	}

	/**
	 * @expectedException Openbuildings\Spiderling\Exception_Notimplemented
	 */
	public function test_execute()
	{
		$this->driver->execute(0, 'script');
	}

	/**
	 * @expectedException Openbuildings\Spiderling\Exception_Notimplemented
	 */
	public function test_user_agent()
	{
		$this->driver->user_agent();
	}

	/**
	 * @expectedException Openbuildings\Spiderling\Exception_Notimplemented
	 */
	public function test_cookie()
	{
		$this->driver->cookie('test', 'test');
	}

}

