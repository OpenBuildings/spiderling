<?php

use Openbuildings\Spiderling\Node;
use Openbuildings\Spiderling\Nodelist;
use Openbuildings\Spiderling\Driver_Simple;
use Openbuildings\Spiderling\PHPUnit_TestCase_Spiderling;

/**
 * @package spiderling
 * @group   node
 */
class NodeTest extends PHPUnit_TestCase_Spiderling {

	public $page;
	public $driver;

	public function setUp()
	{
		parent::setUp();

		$this->driver = $this->getMock('Openbuildings\Spiderling\Driver_Simple', array('get', 'post', 'confirm', 'execute', 'screenshot', 'move_to', 'drop_files'));

		$html_content = file_get_contents(TESTVIEWS.'index.html');

		$this->driver->content($html_content);

		$this->page = $this->driver->page();
	}

	public function test_finders()
	{
		$node = $this->page->find_field('Enter Name');
		$this->assertInstanceOf('Openbuildings\Spiderling\Node', $node);
		$this->assertNode(array('input', 'name' => 'name'), $node);

		$node = $this->page->find_link('Subpage Title 3');
		$this->assertInstanceOf('Openbuildings\Spiderling\Node', $node);
		$this->assertNode(array('a', 'id' => 'navlink-3'), $node);

		$node = $this->page->find_button('Submit Button');
		$this->assertInstanceOf('Openbuildings\Spiderling\Node', $node);
		$this->assertNode(array('button', 'id' => 'submit-btn'), $node);

		$node = $this->page->find('form.contact');
		$this->assertInstanceOf('Openbuildings\Spiderling\Node', $node);
		$this->assertNode(array('form', 'action' => '/test_functest/contact'), $node);

		$node = $this->page->find(array('field', 'Enter Name'));
		$this->assertInstanceOf('Openbuildings\Spiderling\Node', $node);
		$this->assertNode(array('input', 'name' => 'name'), $node);

		$nodes = $this->page->all('.content ul.subnav li > a');
		$this->assertInstanceOf('Openbuildings\Spiderling\Nodelist', $nodes);

		$this->setExpectedException('Openbuildings\Spiderling\Exception_Notfound');
		$node = $this->page->find('form.contact-not-present');
	}

	public function test_not_present()
	{
		$present = $this->page->not_present('form.contact-not-present');
		$this->assertTrue($present);

		$this->setExpectedException('Openbuildings\Spiderling\Exception_Found');
		$node = $this->page->not_present('form.contact');
	}

	public function test_getters()
	{
		$form = $this->page->find('form');
		$input = $form->find_field('Enter Name');
		$textarea = $form->find_field('Enter Message');

		$this->assertNode(array('form', 'action' => '/test_functest/contact'), $form->dom(), 'Should extract the right DOMElement');

		$this->assertEquals("(//descendant-or-self::form)[1]", $form->id());

		$this->assertEquals('<input id="name" name="name" value="Tomas"/>', $input->html());
		
		$this->assertEquals('<input id="name" name="name" value="Tomas"/>', $input->__toString());
		
		$this->assertEquals('input', $input->tag_name());
		$this->assertEquals('Tomas', $input->attribute('value'));
		$this->assertContains('Lorem ipsum dolor sit amet', $textarea->text());
		$this->assertTrue($textarea->is_visible());
		$this->assertEquals('Tomas', $input->value());

		$option = $this->page->find_field('country')->find('option');
		$this->assertFalse($option->is_selected(), 'Should not be a selected option');
		$option->select_option();
		$this->assertTrue($option->is_selected(), 'Should be a selected option');

		$checkbox = $this->page->find_field('Enter Notify Me');
		$this->assertFalse($checkbox->is_checked(), 'Should not be checked by default');

		$checkbox->set(TRUE);
		$this->assertTrue($checkbox->is_checked(), 'Should be checked after action');

		$radio = $this->page->find_field('Gender Female');
		$this->assertTrue($radio->is_checked(), 'Should be checked by default');

		$radio->set(FALSE);
		$this->assertFalse($radio->is_checked(), 'Should not be checked after action');
	}

	public function test_setters()
	{
		$input = $this->page->find_field('Enter Name');
		$input->set('New Name');
		$this->assertNode(array('input', 'value' => 'New Name'), $input);

		$link = $this->page->find_link('Subpage Title 3');
		$this->driver
			->expects($this->at(0))
			->method('get')
			->with($this->equalTo($link->attribute('href')));

		$link->click();

		$option = $this->page->find_field('country')->find('option', array('at' => 1));
		$option->select_option();

		$this->assertNode(array('option', 'selected' => 'selected'), $option);
		$option->unselect_option();

		$this->assertNull($option->attribute('selected'));

		$textarea = $this->page->find_field('message');
		$text = $textarea->value();

		$textarea->append('new text');
		$this->assertNode(array('textarea', $text.'new text'), $textarea);

		$this->page->attach_file('file', 'new file');
		$input = $this->page->find_field('file');

		$this->assertNode(array('input', 'value' => 'new file'), $input);
	}

	public function test_confirm()
	{
		$this->driver
			->expects($this->once())
			->method('confirm')
			->with($this->equalTo(TRUE));

		$this->page->confirm(TRUE);
	}

	public function test_execute()
	{
		$this->driver
			->expects($this->at(0))
			->method('execute')
			->with($this->equalTo("(//descendant-or-self::*[@id = 'navlink-1'])[1]"), $this->equalTo('test script'))
			->will($this->returnValue('result'));

		$this->driver
			->expects($this->at(1))
			->method('execute')
			->with($this->equalTo("(//descendant-or-self::*[@id = 'navlink-1'])[1]"), $this->equalTo('test script 2'))
			->will($this->returnValue('result 2'));			

		$executed = FALSE;
			
		$this->page->find('#navlink-1')->execute('test script', function($result) use ( & $executed) {
			$executed = TRUE;
			PHPUnit_Framework_Assert::assertEquals('result', $result);
		});

		$this->assertTrue($executed);

		$result = $this->page->find('#navlink-1')->execute('test script 2');

		$this->assertEquals('result 2', $result);
	}

	public function test_actions()
	{
		$this->driver->expects($this->at(0))->method('get')->with($this->equalTo('/test_functest/subpage1'));
		$this->driver->expects($this->at(1))->method('get')->with($this->equalTo('/test_functest/subpage2'));
		$this->driver->expects($this->at(2))->method('post')->with($this->equalTo('/test_functest/contact'));

		$this->page->click_on('#navlink-1');
		$this->page->click_link('icon 2');
		$this->page->click_button('Submit Item');

		$this->page->fill_in('Enter Message', 'New Text');
		$this->assertNode(array('textarea', 'New Text', 'id' => 'message'), $this->page->find('#message'));

		$this->page->choose('Gender Male');
		$this->assertNode(array('input', 'id' => 'gender-1', 'checked' => 'checked'), $this->page->find('#gender-1'));		

		$this->page->check('Enter Notify Me');
		$this->assertNode(array('input', 'id' => 'notifyme', 'checked' => 'checked'), $this->page->find('#notifyme'));		

		$this->page->uncheck('Enter Notify Me');
		$this->assertNull($this->page->find('#notifyme')->attribute('checked'));

		$this->page->select('Enter Country', 'United States');
		$this->assertEquals('us', $this->page->find_field('Enter Country')->value());

		$this->page->select('Enter Country', array('value' => 'uk'));
		$this->assertEquals('uk', $this->page->find_field('Enter Country')->value());

		$this->page->unselect('Enter Country', 'uk');
		$this->assertNull($this->page->find_field('Enter Country')->value());
	}

	public function test_screenshot()
	{
		$this->driver
			->expects($this->once())
			->method('screenshot')
			->with($this->equalTo('file.png'));

		$this->page->screenshot('file.png');
	}

	public function test_next_wait_time()
	{
		$this->assertEquals(2000, $this->page->next_wait_time());
		
		$this->page->next_wait_time(1000);

		$this->assertEquals(1000, $this->page->next_wait_time());
	}

	public function test_hover()
	{
		$this->driver
			->expects($this->exactly(5))
			->method('move_to');

		$this->page->hover_field('Enter Name');
		$this->page->hover_link('Subpage Title 3');
		$this->page->hover_button('Submit Button');
		$this->page->hover_on('.content ul.subnav li > a');
		$this->page->find('#navlink-1')->hover();

		$this->setExpectedException('Openbuildings\Spiderling\Exception_Notfound');
		$node = $this->page->hover_on('form.contact-not-present');
	}

	public function test_wait()
	{
		$start = microtime(TRUE);

		$this->page->wait(100);

		$end = microtime(TRUE);

		$this->assertGreaterThanOrEqual(0.1, $end - $start);
	}

	public function test_is_root_and_parent()
	{
		$page = $this->page;
		$link = $this->page->find('#navlink-1');

		$this->assertTrue($page->is_root());
		$this->assertNull($page->parent());

		$this->assertFalse($link->is_root());
		$this->assertSame($page, $link->parent());
	}

	public function test_drop_files()
	{
		$this->driver
			->expects($this->once())
			->method('drop_files')
			->with($this->equalTo("(//descendant-or-self::*[@id = 'navlink-1'])[1]"), $this->equalTo(array('file1', 'file2')));

		$this->page->find('#navlink-1')->drop_files(array('file1', 'file2'));
	}

	public function test_extensions()
	{
		$extension = $this->getMock('Node_Test_Extension', array('test_mock'));

		$extension->expects($this->once())
			->method('test_mock')
			->with($this->identicalTo($this->page), $this->equalTo('argument1'));

		$this->page->extension($extension);

		$this->page->test_mock('argument1');
	}

	public function test_traverse()
	{
		$form = $this->page->find('form');
		$this->assertNode(array('form', 'class' => 'contact'), $form);

		$fieldset = $form->find('fieldset');
		$this->assertNode(array('fieldset'), $fieldset);

		$actions = $fieldset->find('.actions');
		$this->assertNode(array('div', 'class' => 'actions'), $actions);

		$button = $actions->find_button('Submit Button');
		$this->assertNode(array('button', 'id' => 'submit-btn'), $button);

		$button_parent = $button->end();
		$this->assertNode(array('div', 'class' => 'actions'), $button_parent);

		$actions_parent = $button_parent->end();
		$this->assertNode(array('fieldset'), $actions_parent);

		$fieldset_parent = $actions_parent->end();
		$this->assertNode(array('form', 'class' => 'contact'), $form);
	}

	public function provider_get_locator()
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
	 * @dataProvider provider_get_locator
	 */
	public function test_get_locator($selector, $filters, $expected_type)
	{
		if ($expected_type)
		{
			$locator = Node::get_locator($selector, $filters);

			$this->assertEquals($expected_type, $locator->type(), 'Should load appropriate type');	
		}
		else
		{
			$this->setExpectedException('Exception');
			$locator = Node::get_locator($selector, $filters);
		}
	}


	public function test_get_locator_parameters()
	{
		$locator = Node::get_locator('.body', array('at' => 1));

		$this->assertEquals('css', $locator->type());
		$this->assertEquals('.body', $locator->selector());
		$this->assertEquals(array('at' => 1), $locator->filters());

		$locator = Node::get_locator(array('field', 'username', array('value' => 2)));

		$this->assertEquals('field', $locator->type());
		$this->assertEquals('username', $locator->selector());
		$this->assertEquals(array('value' => 2), $locator->filters());

		$locator = Node::get_locator(array('field', array('label', 'username', array('value' => 2))));

		$this->assertEquals('label', $locator->type());
		$this->assertEquals('username', $locator->selector());
		$this->assertEquals(array('value' => 2), $locator->filters());

	}

}