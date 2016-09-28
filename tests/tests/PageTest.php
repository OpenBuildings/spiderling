<?php

use Openbuildings\Spiderling\Page;
use Openbuildings\Spiderling\Driver_Simple;

/**
 * @package spiderling
 * @group   page
 */
class PageTest extends Spiderling_TestCase {

	public function test_default_driver_and_extension()
	{
		$page = new Page();
		$this->assertInstanceOf('Openbuildings\Spiderling\Driver_Simple', $page->driver());

		$this->assertNull($page->extension());

		$extension = $this
			->getMockBuilder('Page_Test_Extension')
			->getMock();

		$page = new Page(NULL, $extension);

		$this->assertSame($extension, $page->extension());
	}

	public function test_methods()
	{
		$driver = $this
			->getMockBuilder('Openbuildings\Spiderling\Driver_Simple')
			->getMock();

		$driver->expects($this->once())
			->method('visit')
			->with($this->equalTo('http://example.com'), $this->equalTo(array('test' => 'value')));

		$driver->expects($this->once())
			->method('content')
			->will($this->returnValue('content test'));

		$driver->expects($this->once())
			->method('current_url')
			->will($this->returnValue('current_url test'));

		$driver->expects($this->once())
			->method('current_path')
			->will($this->returnValue('current_path test'));

		$page = new Page($driver);

		$this->assertSame($driver, $page->driver());

		$page->visit('http://example.com', array('test' => 'value'));

		$this->assertEquals('content test', $page->content());
		$this->assertEquals('current_url test', $page->current_url());
		$this->assertEquals('current_path test', $page->current_path());
	}

}
