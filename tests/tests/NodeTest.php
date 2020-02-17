<?php

use Openbuildings\Spiderling\Node;
use Openbuildings\Spiderling\Nodelist;
use Openbuildings\Spiderling\Driver_Simple;

/**
 * @package spiderling
 * @group   node
 */
class NodeTest extends Spiderling_TestCase {

	public $node;
	public $driver;

	public function setUp()
	{
		parent::setUp();

		$this->driver = $this
			->getMockBuilder('Openbuildings\Spiderling\Driver_Simple')
			->setMethods(array(
				'get',
				'post',
				'confirm',
				'execute',
				'screenshot',
				'move_to',
				'drop_files',
			))
			->getMock();

		$this->driver->default_wait_time = 1;

		$html_content = file_get_contents(TESTVIEWS.'index.html');

		$this->driver->content($html_content);

		$this->node = new Node($this->driver);
	}

	public function test_finders()
	{
		$node = $this->node->find_field('Enter Name');
		$this->assertInstanceOf('Openbuildings\Spiderling\Node', $node);
		$this->assertNode(array('input', 'name' => 'name'), $node);

		$node = $this->node->find_link('Subpage Title 3');
		$this->assertInstanceOf('Openbuildings\Spiderling\Node', $node);
		$this->assertNode(array('a', 'id' => 'navlink-3'), $node);

		$node = $this->node->find_button('Submit Button');
		$this->assertInstanceOf('Openbuildings\Spiderling\Node', $node);
		$this->assertNode(array('button', 'id' => 'submit-btn'), $node);

		$node = $this->node->find('form.contact');
		$this->assertInstanceOf('Openbuildings\Spiderling\Node', $node);
		$this->assertNode(array('form', 'action' => '/test_functest/contact'), $node);

		$node = $this->node->find(array('field', 'Enter Name'));
		$this->assertInstanceOf('Openbuildings\Spiderling\Node', $node);
		$this->assertNode(array('input', 'name' => 'name'), $node);

		$nodes = $this->node->all('.content ul.subnav li > a');
		$this->assertInstanceOf('Openbuildings\Spiderling\Nodelist', $nodes);

		$this->expectException('Openbuildings\Spiderling\Exception_Notfound');
		$node = $this->node->find('form.contact-not-present');
	}

	public function test_not_present()
	{
		$present = $this->node->not_present('form.contact-not-present');
		$this->assertTrue($present);

		$this->expectException('Openbuildings\Spiderling\Exception_Found');
		$node = $this->node->not_present('form.contact');
	}

	public function test_getters()
	{
		$form = $this->node->find('form');
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

		$option = $this->node->find_field('country')->find('option');
		$this->assertFalse($option->is_selected(), 'Should not be a selected option');
		$option->select_option();
		$this->assertTrue($option->is_selected(), 'Should be a selected option');

		$checkbox = $this->node->find_field('Enter Notify Me');
		$this->assertFalse($checkbox->is_checked(), 'Should not be checked by default');

		$checkbox->set(TRUE);
		$this->assertTrue($checkbox->is_checked(), 'Should be checked after action');

		$radio = $this->node->find_field('Gender Female');
		$this->assertTrue($radio->is_checked(), 'Should be checked by default');

		$radio->set(FALSE);
		$this->assertFalse($radio->is_checked(), 'Should not be checked after action');
	}

	public function test_setters()
	{
		$input = $this->node->find_field('Enter Name');
		$input->set('New Name');
		$this->assertNode(array('input', 'value' => 'New Name'), $input);

		$link = $this->node->find_link('Subpage Title 3');
		$this->driver
			->expects($this->at(0))
			->method('get')
			->with($this->equalTo($link->attribute('href')));

		$link->click();

		$option = $this->node->find_field('country')->find('option', array('at' => 1));
		$option->select_option();

		$this->assertNode(array('option', 'selected' => 'selected'), $option);
		$option->unselect_option();

		$this->assertNull($option->attribute('selected'));

		$textarea = $this->node->find_field('message');
		$text = $textarea->value();

		$textarea->append('new text');
		$this->assertNode(array('textarea', $text.'new text'), $textarea);

		$this->node->attach_file('file', 'new file');
		$input = $this->node->find_field('file');

		$this->assertNode(array('input', 'value' => 'new file'), $input);
	}

	public function test_confirm()
	{
		$this->driver
			->expects($this->once())
			->method('confirm')
			->with($this->equalTo(TRUE));

		$this->node->confirm(TRUE);
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

		$this->node->find('#navlink-1')->execute('test script', function($result) use ( & $executed) {
			$executed = TRUE;
			$this->assertEquals('result', $result);
		});

		$this->assertTrue($executed);

		$result = $this->node->find('#navlink-1')->execute('test script 2');

		$this->assertEquals('result 2', $result);
	}

	public function test_actions()
	{
		$this->driver->expects($this->at(0))->method('get')->with($this->equalTo('/test_functest/subpage1'));
		$this->driver->expects($this->at(1))->method('get')->with($this->equalTo('/test_functest/subpage2'));
		$this->driver->expects($this->at(2))->method('post')->with($this->equalTo('/test_functest/contact'));

		$this->node->click_on('#navlink-1');
		$this->node->click_link('icon 2');
		$this->node->click_button('Submit Item');

		$this->node->fill_in('Enter Message', 'New Text');
		$this->assertNode(array('textarea', 'New Text', 'id' => 'message'), $this->node->find('#message'));

		$this->node->choose('Gender Male');
		$this->assertNode(array('input', 'id' => 'gender-1', 'checked' => 'checked'), $this->node->find('#gender-1'));

		$this->node->check('Enter Notify Me');
		$this->assertNode(array('input', 'id' => 'notifyme', 'checked' => 'checked'), $this->node->find('#notifyme'));

		$this->node->uncheck('Enter Notify Me');
		$this->assertNull($this->node->find('#notifyme')->attribute('checked'));

		$this->node->select('Enter Country', 'United States');
		$this->assertEquals('us', $this->node->find_field('Enter Country')->value());

		$this->node->select('Enter Country', array('value' => 'uk'));
		$this->assertEquals('uk', $this->node->find_field('Enter Country')->value());

		$this->node->unselect('Enter Country', 'uk');
		$this->assertNull($this->node->find_field('Enter Country')->value());
	}

	public function test_screenshot()
	{
		$this->driver
			->expects($this->once())
			->method('screenshot')
			->with($this->equalTo('file.png'));

		$this->node->screenshot('file.png');
	}

	public function test_next_wait_time()
	{
		$this->assertEquals($this->driver->default_wait_time, $this->node->next_wait_time());

		$this->node->next_wait_time(1000);

		$this->assertEquals(1000, $this->node->next_wait_time());
	}

	public function test_hover()
	{
		$this->driver
			->expects($this->exactly(5))
			->method('move_to');

		$this->node->hover_field('Enter Name');
		$this->node->hover_link('Subpage Title 3');
		$this->node->hover_button('Submit Button');
		$this->node->hover_on('.content ul.subnav li > a');
		$this->node->find('#navlink-1')->hover();

		$this->expectException('Openbuildings\Spiderling\Exception_Notfound');
		$node = $this->node->hover_on('form.contact-not-present');
	}

	public function test_wait()
	{
		$start = microtime(TRUE);

		$this->node->wait(100);

		$end = microtime(TRUE);

		$this->assertGreaterThanOrEqual(0.1, $end - $start);
	}

	public function test_is_root_and_parent()
	{
		$page = $this->node;
		$link = $this->node->find('#navlink-1');

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

		$this->node->find('#navlink-1')->drop_files(array('file1', 'file2'));
	}

	public function test_extension()
	{
		$extension = $this
			->getMockBuilder('Node_Test_Extension')
			->setMethods(array('test_mock', 'test_mock2'))
			->getMock();

		$extension->expects($this->once())
			->method('test_mock')
			->with($this->identicalTo($this->node), $this->equalTo('argument1'));

		$this->node->extension($extension);

		$this->node->test_mock('argument1');

		$child_node = new Node($this->driver, $this->node);
		$this->assertSame($extension, $child_node->extension());

		$extension->expects($this->once())
			->method('test_mock2')
			->with($this->identicalTo($child_node), $this->equalTo('argument2'));

		$child_node->test_mock2('argument2');
	}

	public function test_traverse()
	{
		$form = $this->node->find('form');
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
			array(array('fieldren', 'Maraba'), array(), NULL),
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
			$this->expectException('Exception');
			$locator = Node::get_locator($selector, $filters)->xpath();
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
