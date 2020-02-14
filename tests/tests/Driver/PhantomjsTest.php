<?php

use Openbuildings\Spiderling\Attempt;
use Openbuildings\Spiderling\Driver_Phantomjs;
use Openbuildings\Spiderling\Driver_Phantomjs_Connection;

/**
 * @package spiderling
 * @group   driver
 * @group   driver.phantomjs
 */
class Driver_PhantomjsTest extends Spiderling_TestCase {

	public static $driver;

	public static function setUpBeforeClass()
	{
		parent::setUpBeforeClass();

		self::$driver = new Driver_Phantomjs();
		self::$driver->base_url('http://clippings-spiderling.s3-website-eu-west-1.amazonaws.com');
		self::$driver->visit('/remote-form.html');
	}

	public function find($xpath)
	{
		$ids = Attempt::make(function() use ($xpath) {
			return Driver_PhantomjsTest::$driver->all($xpath);
		});

		return isset($ids[0]) ? $ids[0] : NULL;
	}

	public function test_accessors()
	{
		$tag_name = self::$driver->tag_name($this->find("//select[@id='post_category']"));
		$this->assertEquals('select', $tag_name);

		$tag_id = self::$driver->attribute($this->find("//select[@id='post_category']"), 'id');
		$this->assertEquals('post_category', $tag_id);

		$html = self::$driver->html($this->find("//textarea[@id='post_body']"));
		$this->assertEquals('<textarea name="post[body]" id="post_body" cols="30" rows="10">Lorem Ipsum</textarea>', $html);

		$text = self::$driver->text($this->find("//div[@id='text']"));
		$this->assertEquals('Lorem £Ipsum Dolor Sit Amet', $text);

		$text = self::$driver->text($this->find("//textarea[@id='post_body']"));
		$this->assertEquals('Lorem Ipsum', $text);

		$value = self::$driver->value($this->find("//input[@id='post_title']"));
		$this->assertEquals('Title 1', $value);

		$value = self::$driver->value($this->find("//textarea[@id='post_body']"));
		$this->assertEquals('Lorem Ipsum', $value);

		$value = self::$driver->value($this->find("//select[@id='post_category']"));
		$this->assertEquals('hw', $value);

		$value = self::$driver->value($this->find("//select[@id='post_ads']"));
		$this->assertEquals(array('text', 'affiliate'), $value);

		$is_selected = self::$driver->is_selected($this->find("//option[@value='sw']"));
		$this->assertFalse($is_selected);

		$is_selected = self::$driver->is_selected($this->find("//option[@value='hw']"));
		$this->assertTrue($is_selected);

		$is_checked = self::$driver->is_checked($this->find("//input[@value='big']"));
		$this->assertFalse($is_checked);

		$is_checked = self::$driver->is_checked($this->find("//input[@value='small']"));
		$this->assertTrue($is_checked);
	}

	public function test_visible()
	{
		$value = self::$driver->is_visible($this->find("//select[@id='post_ads']"));
		$this->assertTrue($value);

		$value = self::$driver->is_visible($this->find("//div[@id='hidden']"));
		$this->assertFalse($value);

		$value = self::$driver->is_visible($this->find("//div[@id='visible']"));
		$this->assertTrue($value);
	}

	public function test_all()
	{
		$this->assertCount(1, self::$driver->all('//textarea'));

		$this->assertCount(0, self::$driver->all('//nonexistant-tag'));
	}

	public function test_connection()
	{
		$connection = new Driver_Phantomjs_Connection();
		$driver = new Driver_Phantomjs();

		$driver->connection($connection);

		$this->assertSame($connection, $driver->connection());

		$driver = new Driver_Phantomjs();
		$connection = $driver->connection();
		$this->assertInstanceOf('OpenBuildings\Spiderling\Driver_Phantomjs_Connection', $connection);
		$this->assertTrue($connection->is_started());
		unset($driver);
	}

	public function test_next_query()
	{
		self::$driver->next_query(array('next' => 'true'));

		self::$driver->visit('/remote-form.html');

		$this->assertEquals(self::$driver->base_url().'/remote-form.html?next=true', self::$driver->current_url());

		self::$driver->visit('/remote-form.html');

		$this->assertEquals(self::$driver->base_url().'/remote-form.html', self::$driver->current_url());
	}

	public function test_screenshot()
	{
		self::$driver->screenshot(TESTVIEWS.'screenshot.png');

		$this->assertFileExists(TESTVIEWS.'screenshot.png');

		unlink(TESTVIEWS.'screenshot.png');
	}

	public function test_content()
	{
		$html = self::$driver->content();
		$this->assertContains('<legend>Author</legend>', $html);
	}

	public function test_base_url()
	{
		$old = self::$driver->base_url();

		self::$driver->base_url('http://example.com');

		$this->assertEquals('http://example.com', self::$driver->base_url());

		self::$driver->base_url($old);
	}

	public function test_cookie()
	{
		self::$driver->cookie('test', 'value', array('path' => '/test/'));

		$cookies = self::$driver->cookies();

		$this->assertEquals('test', $cookies[0]['name']);
		$this->assertEquals('value', $cookies[0]['value']);
		$this->assertEquals('/test/', $cookies[0]['path']);

		self::$driver->clear();

		$this->assertCount(0, self::$driver->cookies());
	}

	public function test_actions()
	{
		$this->assertValueSet($this->find("//input[@id='post_title']"), 'New Title', 'New Title', self::$driver);

		$this->assertValueSet($this->find("//textarea[@id='post_body']"), 'New Text', 'New Title', self::$driver);

		$this->assertValueSet($this->find("//input[@value='tiny']"), TRUE, 'tiny', self::$driver);

		$this->assertValueSet($this->find("//input[@value='sendval']"), TRUE, 'sendval', self::$driver);

		self::$driver->select_option($this->find("//select[@id='post_category']//option[text()='Software']"), TRUE);

		$value = self::$driver->value($this->find("//select[@id='post_category']"));
		$this->assertEquals('sw', $value);

		$old_is_selected = self::$driver->is_selected($this->find("//select[@id='post_category']//option[text()='Hardware']"));
		$this->assertFalse($old_is_selected);

		self::$driver->select_option($this->find("//select[@id='post_ads']//option[text()='Banner']"), TRUE);

		$value = self::$driver->value($this->find("//select[@id='post_ads']"));
		$this->assertEquals(array('banner', 'text', 'affiliate'), $value);

		self::$driver->select_option($this->find("//select[@id='post_ads']//option[text()='Text']"), FALSE);

		$value = self::$driver->value($this->find("//select[@id='post_ads']"));
		$this->assertEquals(array('banner', 'affiliate'), $value);

		$value = self::$driver->value($this->find("//select[@id='post_ads']"));

		self::$driver->set($this->find("//select[@id='post_ads']//option[text()='Text']"), TRUE);

		$value = self::$driver->value($this->find("//select[@id='post_ads']"));
		$this->assertEquals(array('banner', 'text', 'affiliate'), $value);

		self::$driver->set($this->find("//select[@id='post_ads']//option[text()='Text']"), FALSE);

		$value = self::$driver->value($this->find("//select[@id='post_ads']"));
		$this->assertEquals(array('banner', 'affiliate'), $value);

		self::$driver->set($this->find("//input[@type='file']"), TESTVIEWS.'form.html');

		$value = self::$driver->value($this->find("//input[@type='file']"));

		$this->assertEquals('C:\fakepath\form.html', $value);
	}

	public function test_clicks()
	{
		self::$driver->click($this->find("//a[@id='visible-link']"));

		$title = self::$driver->text($this->find("//h1"));
		$this->assertEquals('Linked', $title);

		$this->assertEquals(self::$driver->base_url().'/remote-linked.html', self::$driver->current_url());

		$this->assertEquals('/remote-linked.html', self::$driver->current_path());

		self::$driver->visit('/remote-form.html');

		self::$driver->click($this->find("//button[@id='submit-btn']"));
		$title = self::$driver->text($this->find("//h1"));
		$this->assertEquals('Submitted', $title);

		self::$driver->visit('/remote-form.html');

		self::$driver->click($this->find("//input[@id='submit']"));
		$title = self::$driver->text($this->find("//h1"));
		$this->assertEquals('Submitted', $title);

		self::$driver->visit('/remote-form.html');
	}

	public function test_user_agent()
	{
		$this->assertContains('PhantomJS', self::$driver->user_agent());
	}

	public function test_is_page_active()
	{
		$this->assertTrue(self::$driver->is_page_active());

		$driver = new Driver_Phantomjs();

		$this->assertFalse($driver->is_page_active());
	}

	public function test_javascript_messages()
	{
		self::$driver->execute($this->find("//select[@id='post_category']"), "console.debug('new test message');");

		$this->assertEquals(array('new test message'), self::$driver->javascript_messages());
	}

	public function test_javascript_errors()
	{
		self::$driver->execute($this->find("//select[@id='post_category']"), "nonexistant_function()");

		$expected_errors = array(
			array(
				'errorMessage' => "ReferenceError: Can't find variable: nonexistant_function",
				'sourceName' => 'undefined',
				'lineNumber' => 1,
			)
		);

		$this->assertEquals($expected_errors, self::$driver->javascript_errors());
	}
}

