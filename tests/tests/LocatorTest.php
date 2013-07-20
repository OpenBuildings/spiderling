<?php defined('SYSPATH') OR die('No direct script access.');

use Openbuildings\Spiderling\Locator;
use Openbuildings\Spiderling\Node;
use Openbuildings\Spiderling\Driver_Simple;
use Openbuildings\Spiderling\PHPUnit_TestCase_Spiderling;

/**
 * @package spiderling
 * @group   spiderling
 * @group   spiderling.locator
 */
class LocatorTest extends PHPUnit_TestCase_Spiderling {

	public function provider_types()
	{
		return array(
			array('.nav', array(), 'css'),
			array('.field a', array(), 'css'),
			array(array('field', 'Maraba'), array(), 'field'),
			array(array('link', 'Maraba'), array(), 'link'),
			array(array('button', 'Maraba'), array(), 'button'),
			array(array('xpath', '//Maraba'), array(), 'xpath'),
			array(array('field', 'Maraba'), array('value' => '1'), 'field'),
			array('fieldren', 'Maraba', array(), NULL),
		);
	}

	/**
	 * @dataProvider provider_types
	 */
	public function test_types($selector, $filters, $expected_type)
	{
		if ($expected_type)
		{
			$locator = new Locator($selector, $filters);

			$this->assertEquals($expected_type, $locator->type(), 'Should load appropriate type');	
		}
		else
		{
			$this->setExpectedException('Exception');
			$locator = new Locator($selector, $filters);
		}
	}

	public function provider_finders()
	{
		$html_content = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'index.html');
		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->loadHTML($html_content);
		$xpath = new DOMXPath($doc);

		return array(
			array($xpath, array('css', '.content .subnav'), 1, array('ul', 'class' => 'subnav')),
			array($xpath, array('css', 'form[action="/test_functest/contact"]'), 1, array('form', 'class' => 'contact', 'action' => '/test_functest/contact')),
			array($xpath, array('css', '.content .subnav > li > a'), 3, array('a', 'class' => 'navlink')),
			array($xpath, array('css', '#index'), 1, array('div', 'id' => 'index')),
			array($xpath, array('css', '.page p'), 3, array('p', 'id' => 'p-1')),

			array($xpath, array('link', 'Subpage 1'), 1, array('a', 'href' => '/test_functest/subpage1')),
			array($xpath, array('link', 'Subpage 2'), 1, array('a', 'href' => '/test_functest/subpage2')),
			array($xpath, array('link', 'navlink-3'), 1, array('a', 'href' => '/test_functest/subpage3')),
			array($xpath, array('link', 'Subpage Title 1'), 1, array('a', 'href' => '/test_functest/subpage1')),
			array($xpath, array('link', 'icon 3'), 1, array('a', 'href' => '/test_functest/subpage3')),

			array($xpath, array('button', 'submit input'), 1, array('input', 'id' => 'submit')),
			array($xpath, array('button', 'Submit Item'), 1, array('input', 'id' => 'submit')),
			array($xpath, array('button', 'submit'), 1, array('input', 'id' => 'submit')),
			array($xpath, array('button', 'Submit Button'), 1, array('button', 'id' => 'submit-btn')),
			array($xpath, array('button', 'submit-btn'), 1, array('button', 'id' => 'submit-btn')),
			array($xpath, array('button', 'Submit Image'), 1, array('button', 'id' => 'submit-btn-icon')),
			array($xpath, array('button', 'Image Title'), 1, array('button', 'id' => 'submit-btn-icon')),
			array($xpath, array('button', 'submit-btn-icon'), 1, array('button', 'id' => 'submit-btn-icon')),

			array($xpath, array('field', 'email'), 1, array('input', 'id' => 'email')),
			array($xpath, array('field', 'Enter Email'), 1, array('input', 'id' => 'email')),
			array($xpath, array('field', 'This is your email'), 1, array('input', 'id' => 'email')),
			array($xpath, array('field', 'name'), 1, array('input', 'id' => 'name')),
			array($xpath, array('field', 'message'), 1, array('textarea', 'id' => 'message')),
			array($xpath, array('field', 'Enter Message'), 1, array('textarea', 'id' => 'message')),
			array($xpath, array('field', 'country'), 1, array('select', 'id' => 'country')),
			array($xpath, array('field', 'Enter Country'), 1, array('select', 'id' => 'country')),
			array($xpath, array('field', 'submit'), 0, NULL),
			array($xpath, array('field', 'gender'), 2, array('input', 'type' => 'radio', 'name' => 'gender')),
			array($xpath, array('field', 'Gender Male'), 1, array('input', 'type' => 'radio', 'name' => 'gender', 'value' => 'male')),
			array($xpath, array('field', 'Gender Female'), 1, array('input', 'type' => 'radio', 'name' => 'gender', 'value' => 'female')),

			array($xpath, array('xpath', '//form[@class="contact"]'), 1, array('form', 'class' => 'contact', 'action' => '/test_functest/contact')),

			array($xpath, array('label', 'Enter Country'), 1, array('label')),
		);
	}

	/**
	 * @dataProvider provider_finders
	 */
	public function test_finders($xpath, $selector, $count, $expected_node)
	{
		$locator = new Locator($selector);

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
		$locator = new Locator(array('non-existant-locator', 'test'));
		$locator->xpath();
	}

	public function test_construct()
	{
		$locator = new Locator('.body', array('at' => 1));

		$this->assertEquals('css', $locator->type());
		$this->assertEquals('.body', $locator->selector());
		$this->assertEquals(array('at' => 1), $locator->filters());

		$locator = new Locator(array('field', 'username', array('value' => 2)));

		$this->assertEquals('field', $locator->type());
		$this->assertEquals('username', $locator->selector());
		$this->assertEquals(array('value' => 2), $locator->filters());

		$locator = new Locator(array('field', array('label', 'username', array('value' => 2))));

		$this->assertEquals('label', $locator->type());
		$this->assertEquals('username', $locator->selector());
		$this->assertEquals(array('value' => 2), $locator->filters());

	}

	public function test_is_filtered()
	{
		$node = new Node(new Driver_Simple());

		$locator = $this->getMock(
			'Openbuildings\Spiderling\Locator', 
			array('filter_by_at', 'filter_by_value', 'filter_by_visible', 'filter_by_attributes'), 
			array('div', array(
				'at' => 'test_at', 
				'value' => 'test_value',
				'visible' => TRUE,
				'attributes' => array('name' => 'test'),
			))
		);

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

		$locator = $this->getMock(
			'Openbuildings\Spiderling\Locator', 
			array('filter_by_at', 'filter_by_value'), 
			array('div', array('at' => 'test_at', 'value' => 'test_value'))
		);

		$locator
			->expects($this->once())
			->method('filter_by_at')
			->with($this->identicalTo($node), $this->equalTo(2), $this->equalTo('test_at'))
			->will($this->returnValue(FALSE));

		$locator
			->expects($this->never())
			->method('filter_by_value');

		$this->assertFalse($locator->is_filtered($node, 2));

		$locator = new Locator('.body', array('non-existant-filter' => TRUE));

		$this->setExpectedException('Openbuildings\Spiderling\Exception');
		$locator->is_filtered($node, 3);
	}

	public function test_filter_by_at()
	{
		$locator = new Locator('.body');
		$node = new Node(new Driver_Simple());

		$this->assertTrue($locator->filter_by_at($node, 1, 1));
		$this->assertFalse($locator->filter_by_at($node, 1, 2));
	}

	public function test_filter_by_value()
	{
		$locator = new Locator('.body');

		$node = $this->getMock(
			'Openbuildings\Spiderling\Node', 
			array('value'), 
			array(new Driver_Simple())
		);

		$node
			->expects($this->exactly(2))
			->method('value')
			->will($this->returnValue('test_html'));

		$this->assertTrue($locator->filter_by_value($node, 1, 'test_html'));
		$this->assertFalse($locator->filter_by_value($node, 1, 'test_html_no_matching'));
	}

	public function test_filter_by_text()
	{
		$locator = new Locator('.body');

		$node = $this->getMock(
			'Openbuildings\Spiderling\Node', 
			array('text'), 
			array(new Driver_Simple())
		);

		$node
			->expects($this->exactly(2))
			->method('text')
			->will($this->returnValue('test_text'));

		$this->assertTrue($locator->filter_by_text($node, 1, 'test_text'));
		$this->assertFalse($locator->filter_by_text($node, 1, 'test_text_no_matching'));
	}
	
	public function test_filter_by_visible()
	{
		$locator = new Locator('.body');

		$node = $this->getMock(
			'Openbuildings\Spiderling\Node', 
			array('is_visible'), 
			array(new Driver_Simple())
		);

		$node
			->expects($this->exactly(2))
			->method('is_visible')
			->will($this->returnValue(TRUE));

		$this->assertTrue($locator->filter_by_visible($node, 1, TRUE));
		$this->assertFalse($locator->filter_by_visible($node, 1, FALSE));
	}	

	public function test_filter_by_attributes()
	{
		$locator = new Locator('.body');

		$node = $this->getMock(
			'Openbuildings\Spiderling\Node', 
			array('attribute'), 
			array(new Driver_Simple())
		);

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
		$locator = new Locator('.body', array('at' => 1, 'attributes' => array('type' => 'text')));
		$this->assertEquals('Locator: (css) .body filters: {"at":1,"attributes":{"type":"text"}}', (string) $locator);
	}
}

