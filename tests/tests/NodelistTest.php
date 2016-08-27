<?php

use Openbuildings\Spiderling\Node;
use Openbuildings\Spiderling\Nodelist;
use Openbuildings\Spiderling\Driver_Simple;

/**
 * @package spiderling
 * @group   nodelist
 */
class NodelistTest extends Spiderling_TestCase {

	public $page;

	public function setUp()
	{
		parent::setUp();

		$driver = $this
			->getMockBuilder('Openbuildings\Spiderling\Driver_Simple')
			->setMethods(array('get', 'post'))
			->getMock();

		$html_content = file_get_contents(TESTVIEWS.'index.html');

		$driver->content($html_content);

		$this->page = $driver->page();
	}

	public function test_all()
	{
		$nodes = $this->page->all('.content ul.subnav li > a');
		$this->assertInstanceOf('Openbuildings\Spiderling\Nodelist', $nodes);
		$this->assertCount(3, $nodes);
		$this->assertEquals('css', $nodes->locator()->type());
		$this->assertEquals('.content ul.subnav li > a', $nodes->locator()->selector());

		$this->assertInstanceOf('Openbuildings\Spiderling\Node', $nodes[0]);
		$this->assertNode(array('a', 'id' => 'navlink-1'), $nodes[0]);

		$this->assertInstanceOf('Openbuildings\Spiderling\Node', $nodes->first());
		$this->assertNode(array('a', 'id' => 'navlink-1'), $nodes->first());

		$this->assertInstanceOf('Openbuildings\Spiderling\Node', $nodes[1]);
		$this->assertNode(array('a', 'id' => 'navlink-2'), $nodes[1]);

		$this->assertInstanceOf('Openbuildings\Spiderling\Node', $nodes[2]);
		$this->assertNode(array('a', 'id' => 'navlink-3'), $nodes[2]);

		$this->assertInstanceOf('Openbuildings\Spiderling\Node', $nodes->last());
		$this->assertNode(array('a', 'id' => 'navlink-3'), $nodes->last());
	}

	public function test_to_string()
	{
		$nodes = $this->page->all('.content ul.subnav li > a');
		$string = $nodes->__toString();

		$this->assertContains('.content ul.subnav li > a', $string, 'Should have the selector as string');
		$this->assertContains('<a class="navlink" id="navlink-2" title="Subpage Title 2" href="/test_functest/subpage2">', $string);
	}

	public function test_as_array()
	{
		$nodes = $this->page->all('.content ul.subnav li > a')->as_array();
		$this->assertInternalType('array', $nodes);

		$this->assertNode(array('a', 'id' => 'navlink-1'), $nodes[0]);
		$this->assertNode(array('a', 'id' => 'navlink-2'), $nodes[1]);
		$this->assertNode(array('a', 'id' => 'navlink-3'), $nodes[2]);
	}

	public function test_iteratable_and_seek()
	{
		$list = $this->page->all('.content ul.subnav li > a');

		foreach ($list as $i => $item)
		{
			if ($i == 0)
			{
				$this->assertNode(array('a', 'id' => 'navlink-1'), $item);
			}
			elseif ($i == 1)
			{
				$this->assertNode(array('a', 'id' => 'navlink-2'), $item);
			}
			elseif ($i == 2)
			{
				$this->assertNode(array('a', 'id' => 'navlink-3'), $item);
			}
		}

		$this->assertTrue($list->seek(0));
		$this->assertNode(array('a', 'id' => 'navlink-1'), $list->current());

		$this->assertTrue($list->seek(2));
		$this->assertNode(array('a', 'id' => 'navlink-3'), $list->current());

		$this->assertFalse($list->seek(3));
	}

	/**
	 * @expectedException Openbuildings\Spiderling\Exception
	 */
	public function test_offsetUnset()
	{
		$list = $this->page->all('.content ul.subnav li > a');
		unset($list[0]);
	}

	public function test_driver()
	{
		$list = $this->page->all('.content ul.subnav li > a');

		$this->assertInstanceOf('Openbuildings\Spiderling\Driver_Simple', $list->driver());
	}

	/**
	 * @expectedException Openbuildings\Spiderling\Exception
	 */
	public function test_offsetSet()
	{
		$list = $this->page->all('.content ul.subnav li > a');
		$list[0] = 'test';
	}

}
