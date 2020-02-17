<?php

use Openbuildings\Spiderling\Locator;
use Openbuildings\Spiderling\Node;
use Openbuildings\Spiderling\Driver_Simple;

/**
 * @package spiderling
 * @group   locator
 */
class LocatorTest extends Spiderling_TestCase {

	public function provider_finders()
	{
		$html_content = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'index.html');
		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->loadHTML($html_content);
		$xpath = new DOMXPath($doc);

		return array(
			array($xpath, 'css', '.content .subnav', 1, array('ul', 'class' => 'subnav')),
			array($xpath, 'css', 'form[action="/test_functest/contact"]', 1, array('form', 'class' => 'contact', 'action' => '/test_functest/contact')),
			array($xpath, 'css', '.content .subnav > li > a', 3, array('a', 'class' => 'navlink')),
			array($xpath, 'css', '#index', 1, array('div', 'id' => 'index')),
			array($xpath, 'css', '.page p', 3, array('p', 'id' => 'p-1')),

			array($xpath, 'link', 'Subpage 1', 1, array('a', 'href' => '/test_functest/subpage1')),
			array($xpath, 'link', 'Subpage 2', 1, array('a', 'href' => '/test_functest/subpage2')),
			array($xpath, 'link', 'navlink-3', 1, array('a', 'href' => '/test_functest/subpage3')),
			array($xpath, 'link', 'Subpage Title 1', 1, array('a', 'href' => '/test_functest/subpage1')),
			array($xpath, 'link', 'icon 3', 1, array('a', 'href' => '/test_functest/subpage3')),

			array($xpath, 'button', 'submit input', 1, array('input', 'id' => 'submit')),
			array($xpath, 'button', 'Submit Item', 1, array('input', 'id' => 'submit')),
			array($xpath, 'button', 'submit', 1, array('input', 'id' => 'submit')),
			array($xpath, 'button', 'Submit Button', 1, array('button', 'id' => 'submit-btn')),
			array($xpath, 'button', 'submit-btn', 1, array('button', 'id' => 'submit-btn')),
			array($xpath, 'button', 'Submit Image', 1, array('button', 'id' => 'submit-btn-icon')),
			array($xpath, 'button', 'Image Title', 1, array('button', 'id' => 'submit-btn-icon')),
			array($xpath, 'button', 'submit-btn-icon', 1, array('button', 'id' => 'submit-btn-icon')),

			array($xpath, 'field', 'email', 1, array('input', 'id' => 'email')),
			array($xpath, 'field', 'Enter Email', 1, array('input', 'id' => 'email')),
			array($xpath, 'field', 'This is your email', 1, array('input', 'id' => 'email')),
			array($xpath, 'field', 'name', 1, array('input', 'id' => 'name')),
			array($xpath, 'field', 'message', 1, array('textarea', 'id' => 'message')),
			array($xpath, 'field', 'Enter Message', 1, array('textarea', 'id' => 'message')),
			array($xpath, 'field', 'country', 1, array('select', 'id' => 'country')),
			array($xpath, 'field', 'Enter Country', 1, array('select', 'id' => 'country')),
			array($xpath, 'field', 'submit', 0, NULL),
			array($xpath, 'field', 'gender', 2, array('input', 'type' => 'radio', 'name' => 'gender')),
			array($xpath, 'field', 'Gender Male', 1, array('input', 'type' => 'radio', 'name' => 'gender', 'value' => 'male')),
			array($xpath, 'field', 'Gender Female', 1, array('input', 'type' => 'radio', 'name' => 'gender', 'value' => 'female')),

			array($xpath, 'xpath', '//form[@class="contact"]', 1, array('form', 'class' => 'contact', 'action' => '/test_functest/contact')),

			array($xpath, 'label', 'Enter Country', 1, array('label')),
		);
	}

	/**
	 * @dataProvider provider_finders
	 */
	public function test_finders($xpath, $type, $selector, $count, $expected_node)
	{
		$locator = new Locator($type, $selector);

		$result = $xpath->query($locator->xpath());

		$node = $result->item(0);
		$this->assertEquals($count, $result->length, 'Should have '.$count.' of elements with from xpath '.$locator->xpath());

		if ($expected_node)
		{
			$this->assertNode($expected_node, $node, 'Should have a tag from xpath '.$locator->xpath());
		}
	}


	/**
	 * @expectedException Openbuildings\Spiderling\Exception
	 */
	public function test_missing_locator()
	{
		$locator = new Locator('non-existant-locator', 'test');
		$locator->xpath();
	}

	public function test_is_filtered()
	{
		$node = new Node(new Driver_Simple());

		$locator = $this->getMockBuilder('Openbuildings\Spiderling\Locator')
			->setMethods(array('filter_by_at', 'filter_by_value', 'filter_by_visible', 'filter_by_attributes'))
			->setConstructorArgs(array(
				'css',
				'div',
				array(
					'at' => 'test_at',
					'value' => 'test_value',
					'visible' => TRUE,
					'attributes' => array('name' => 'test'),
				)
			))
			->getMock();

		$locator
			->expects($this->once())
			->method('filter_by_at')
			->with($this->identicalTo($node), $this->equalTo(1), $this->equalTo('test_at'))
			->will($this->returnValue(TRUE));

		$locator
			->expects($this->once())
			->method('filter_by_value')
			->with($this->identicalTo($node), $this->equalTo(1), $this->equalTo('test_value'))
			->will($this->returnValue(TRUE));

		$locator
			->expects($this->once())
			->method('filter_by_visible')
			->with($this->identicalTo($node), $this->equalTo(1), $this->equalTo(TRUE))
			->will($this->returnValue(TRUE));

		$locator
			->expects($this->once())
			->method('filter_by_attributes')
			->with($this->identicalTo($node), $this->equalTo(1), $this->equalTo(array('name' => 'test')))
			->will($this->returnValue(TRUE));


		$this->assertTrue($locator->is_filtered($node, 1));

		$locator = $this->getMockBuilder('Openbuildings\Spiderling\Locator')
			->setMethods(array('filter_by_at', 'filter_by_value'))
			->setConstructorArgs(array(
				'css',
				'div',
				array('at' => 'test_at', 'value' => 'test_value')
			))
			->getMock();

		$locator
			->expects($this->once())
			->method('filter_by_at')
			->with($this->identicalTo($node), $this->equalTo(2), $this->equalTo('test_at'))
			->will($this->returnValue(FALSE));

		$locator
			->expects($this->never())
			->method('filter_by_value');

		$this->assertFalse($locator->is_filtered($node, 2));

		$locator = new Locator('css', '.body', array('non-existant-filter' => TRUE));

		$this->expectException('Openbuildings\Spiderling\Exception');
		$locator->is_filtered($node, 3);
	}

	public function test_filter_by_at()
	{
		$locator = new Locator('css', '.body');
		$node = new Node(new Driver_Simple());

		$this->assertTrue($locator->filter_by_at($node, 1, 1));
		$this->assertFalse($locator->filter_by_at($node, 1, 2));
	}

	public function test_filter_by_value()
	{
		$locator = new Locator('css', '.body');

		$node = $this->getMockBuilder('Openbuildings\Spiderling\Node')
			->setMethods(array('value'))
			->setConstructorArgs(array(new Driver_Simple()))
			->getMock();

		$node
			->expects($this->exactly(2))
			->method('value')
			->will($this->returnValue('test_html'));

		$this->assertTrue($locator->filter_by_value($node, 1, 'test_html'));
		$this->assertFalse($locator->filter_by_value($node, 1, 'test_html_no_matching'));
	}

	public function test_filter_by_text()
	{
		$locator = new Locator('css', '.body');

		$node = $this->getMockBuilder('Openbuildings\Spiderling\Node')
			->setMethods(array('text'))
			->setConstructorArgs(array(new Driver_Simple()))
			->getMock();

		$node
			->expects($this->exactly(2))
			->method('text')
			->will($this->returnValue('test_text'));

		$this->assertTrue($locator->filter_by_text($node, 1, 'test_text'));
		$this->assertFalse($locator->filter_by_text($node, 1, 'test_text_no_matching'));
	}

	public function test_filter_by_visible()
	{
		$locator = new Locator('css', '.body');

		$node = $this->getMockBuilder('Openbuildings\Spiderling\Node')
			->setMethods(array('is_visible'))
			->setConstructorArgs(array(new Driver_Simple()))
			->getMock();

		$node
			->expects($this->exactly(2))
			->method('is_visible')
			->will($this->returnValue(TRUE));

		$this->assertTrue($locator->filter_by_visible($node, 1, TRUE));
		$this->assertFalse($locator->filter_by_visible($node, 1, FALSE));
	}

	public function test_filter_by_attributes()
	{
		$locator = new Locator('css', '.body');

		$node = $this->getMockBuilder('Openbuildings\Spiderling\Node')
			->setMethods(array('attribute'))
			->setConstructorArgs(array(new Driver_Simple()))
			->getMock();

		$node
			->expects($this->exactly(2))
			->method('attribute')
			->with($this->equalTo('type'))
			->will($this->returnValue('text'));

		$this->assertTrue($locator->filter_by_attributes($node, 1, array('type' => 'text')));
		$this->assertFalse($locator->filter_by_attributes($node, 1, array('type' => 'date')));
	}

	public function test_to_string()
	{
		$locator = new Locator('css', '.body', array('at' => 1, 'attributes' => array('type' => 'text')));
		$this->assertEquals('Locator: (css) .body filters: {"at":1,"attributes":{"type":"text"}}', (string) $locator);
	}
}

