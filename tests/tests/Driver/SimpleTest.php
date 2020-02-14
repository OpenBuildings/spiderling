<?php

use Openbuildings\Spiderling\Driver_Simple;

/**
 * @package spiderling
 * @group   driver
 * @group   driver.simple
 */
class Driver_SimpleTest extends Spiderling_TestCase {

	public $driver;

	public $form_data = array(
		'id' => '10',
		'name' => 'Arthur',
		'post' => array(
			'id' => '1',
			'title' => 'Title 1',
			'tag' => array(
				'name' => 'Red',
				'rating' => '20',
				'quantity' => '1'
			),
			'body' => 'Lorem Ipsum',
			'type' => 'small',
			'send' => 'sendval',
			'category' => 'hw',
			'ads' => array(
				'text',
				'affiliate'
			)
		)
	);

	public function setUp()
	{
		parent::setUp();
		$this->driver = new Driver_Simple();
		$request_factory = $this
			->getMockBuilder('Openbuildings\Spiderling\Driver_Simple_RequestFactory_HTTP')
			->getMock();
		$this->driver->request_factory($request_factory);

		$html_content = file_get_contents(TESTVIEWS.'form.html');

		$this->driver->content($html_content);
	}

	public function test_request_factory()
	{
		$driver = new Driver_Simple();
		$request_factory = $driver->request_factory();

		$this->assertInstanceOf('Openbuildings\Spiderling\Driver_Simple_RequestFactory', $request_factory);
	}

	public function test_environment()
	{
		$driver = new Driver_Simple();
		$environment = $driver->environment();

		$this->assertInstanceOf('Openbuildings\EnvironmentBackup\Environment', $environment);
	}

	public function test_serialize_form()
	{
		$form = $this->driver->page()->find('form')->dom();

		$form_data = $this->driver->serialize_form($form);
		parse_str($form_data, $form_data);

		$this->assertEquals($this->form_data, $form_data);
	}

	public function test_dom()
	{
		$node = $this->driver->dom("//select[@id='post_category']");

		$this->assertInstanceOf('DOMNode', $node, 'Should return a node');

		$new_node = $this->driver->dom($node);
		$this->assertSame($node, $new_node, 'Should return the node if passed one');

		$this->assertNode(array('select', 'id' => 'post_category'), $node);

		$this->expectException('Exception');
		$this->driver->dom("//select[@id='not-present-node']");
	}

	public function test_accessors()
	{
		$tag_name = $this->driver->tag_name("//select[@id='post_category']");
		$this->assertEquals('select', $tag_name);

		$tag_id = $this->driver->attribute("//select[@id='post_category']", 'id');
		$this->assertEquals('post_category', $tag_id);

		$html = $this->driver->html("//textarea[@id='post_body']");
		$this->assertEquals('<textarea name="post[body]" id="post_body" cols="30" rows="10">Lorem Ipsum</textarea>', $html);

		$text = $this->driver->text("//div[@id='text']");
		$this->assertEquals('Lorem £Ipsum Dolor Sit Amet', $text);

		$value = $this->driver->value("//input[@id='post_title']");
		$this->assertEquals('Title 1', $value);

		$value = $this->driver->value("//textarea[@id='post_body']");
		$this->assertEquals('Lorem Ipsum', $value);

		$value = $this->driver->value("//select[@id='post_category']");
		$this->assertEquals('hw', $value);

		$value = $this->driver->value("//select[@id='post_ads']");
		$this->assertEquals(array('text', 'affiliate'), $value);

		$is_selected = $this->driver->is_selected("//option[@value='sw']");
		$this->assertFalse($is_selected);

		$is_selected = $this->driver->is_selected("//option[@value='hw']");
		$this->assertTrue($is_selected);

		$is_checked = $this->driver->is_checked("//input[@value='big']");
		$this->assertFalse($is_checked);

		$is_checked = $this->driver->is_checked("//input[@value='small']");
		$this->assertTrue($is_checked);
	}

	public function test_visible()
	{
		$value = $this->driver->is_visible("//select[@id='post_ads']");
		$this->assertTrue($value);

		$value = $this->driver->is_visible("//div[@id='hidden']");
		$this->assertFalse($value);

		$value = $this->driver->is_visible("//div[@id='visible']");
		$this->assertTrue($value);
	}

	public function test_actions()
	{
		$this->assertValueSet("//input[@id='post_title']", 'New Title', 'New Title', $this->driver);

		$this->assertValueSet("//textarea[@id='post_body']", 'New Text', 'New Title', $this->driver);

		$this->assertValueSet("//input[@value='tiny']", TRUE, 'tiny', $this->driver);

		$this->assertValueSet("//input[@value='sendval']", TRUE, 'sendval', $this->driver);

		$this->driver->select_option("//select[@id='post_category']//option[text()='Software']", TRUE);

		$value = $this->driver->value("//select[@id='post_category']");
		$this->assertEquals('sw', $value);

		$old_value = $this->driver->dom("//select[@id='post_category']//option[text()='Hardware']");
		$this->assertFalse($old_value->hasAttribute('selected'));

		$this->driver->select_option("//select[@id='post_ads']//option[text()='Banner']", TRUE);

		$value = $this->driver->value("//select[@id='post_ads']");
		$this->assertEquals(array('banner', 'text', 'affiliate'), $value);

		$this->driver->select_option("//select[@id='post_ads']//option[text()='Text']", FALSE);

		$value = $this->driver->value("//select[@id='post_ads']");
		$this->assertEquals(array('banner', 'affiliate'), $value);
	}

	public function test_clicks()
	{
		$this->driver->request_factory()
			->expects($this->at(0))
			->method('execute')
			->with($this->equalTo('GET'), $this->equalTo('/test_functest/page1'));

		$this->driver->request_factory()
			->expects($this->at(1))
			->method('execute')
			->with($this->equalTo('POST'), $this->equalTo('/test_functest/form'), $this->equalTo($this->form_data));

		$this->driver->request_factory()
			->expects($this->at(2))
			->method('execute')
			->with($this->equalTo('POST'), $this->equalTo('/test_functest/form'), $this->equalTo($this->form_data + array('submit_input' => 'Submit Item')));

		$this->driver->request_factory()
			->expects($this->at(3))
			->method('execute')
			->with($this->equalTo('GET'), $this->equalTo('http://example.com/test?test=value'));

		$this->driver->request_factory()
			->expects($this->at(4))
			->method('execute')
			->with($this->equalTo('GET'), $this->equalTo('/test_functest/search?q=search-text'), $this->equalTo(array()));

		$this->driver->click("//a[@id='visible-link']");
		$this->driver->click("//button[@id='submit-btn']");
		$this->driver->click("//input[@id='submit']");
		$this->driver->visit("http://example.com/test", array('test' => 'value'));
		$this->driver->click("//button[@id='search-btn']");


		$this->expectException('Openbuildings\Spiderling\Exception_Driver');
		$this->driver->click("//div[@id='hidden']");
	}

	public function test_current_url()
	{
		$this->driver->request_factory()
			->expects($this->once())
			->method('current_url')
			->will($this->returnValue('testurl'));

		$this->assertEquals('testurl', $this->driver->current_url());
	}

	public function test_current_path()
	{
		$this->driver->request_factory()
			->expects($this->once())
			->method('current_path')
			->will($this->returnValue('testurl'));

		$this->assertEquals('testurl', $this->driver->current_path());
	}

	public function test_user_agent()
	{
		$this->driver->request_factory()
			->expects($this->once())
			->method('user_agent')
			->will($this->returnValue('agent'));

		$this->assertEquals('agent', $this->driver->user_agent());
	}

	public function test_clear()
	{
		$_POST = array('test' => 'value');
		$this->driver->environment()->backup_and_set(array('_POST' => array('new_test' => 'new value')));
		$this->assertEquals(array('new_test' => 'new value'), $_POST);
		$this->driver->clear();

		$this->assertEquals(array('test' => 'value'), $_POST);
	}

	public function test_cookie()
	{
		$_COOKIE = array();
		$this->driver->cookie('test', 'value');
		$this->assertEquals(array('test' => 'value'), $_COOKIE);
	}

	public function test_is_page_active()
	{
		$this->assertTrue($this->driver->is_page_active());

		$driver = new Driver_Simple();

		$this->assertFalse($driver->is_page_active());
	}
}

