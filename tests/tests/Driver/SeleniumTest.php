<?php

use Openbuildings\Spiderling\Attempt;
use Openbuildings\Spiderling\Driver_Selenium;
use Openbuildings\Spiderling\Exception_Selenium;
use Openbuildings\Spiderling\Driver_Selenium_Connection;

/**
 * @package spiderling
 * @group   driver
 * @group   driver.selenium
 */
class Driver_SeleniumTest extends Spiderling_TestCase {

	public static $driver;

	public static function setUpBeforeClass()
	{
		parent::setUpBeforeClass();
		self::$driver = new Driver_Selenium();
		self::$driver->base_url('http://6ca1671dbfe9477b14ce-fabb5009fe9cc97c5f42aa7fac8fcd02.r26.cf3.rackcdn.com');

		self::$driver->visit('/remote-form.html');
	}

	public function find($xpath)
	{
		$ids = Attempt::make(function() use ($xpath) {
			return Driver_SeleniumTest::$driver->all($xpath);
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

	public function test_confirm_and_alert_text()
	{
		self::$driver->execute(NULL, 'if (confirm("Test Confirm?"))alert("Test Alert");');

		$this->assertEquals('Test Confirm?', self::$driver->alert_text());
		self::$driver->confirm(FALSE);
		try
		{
			self::$driver->alert_text();
			$this->fail('Should not have a dialog open');
		}
		catch (Exception_Selenium $exception)
		{
			// Normal flow
		}

		self::$driver->execute(NULL, 'if (confirm("Test Confirm?"))alert("Test Alert");');
		self::$driver->confirm(TRUE);
		$this->assertEquals('Test Alert', self::$driver->alert_text());
		self::$driver->confirm(TRUE);

		try
		{
			self::$driver->alert_text();
			$this->fail('Should not have a dialog open');
		}
		catch (Exception_Selenium $exception)
		{
			// Normal flow
		}
	}

	public function test_move_to()
	{
		$id = $this->find("//button[@id='submit-btn']");
		self::$driver->execute($id, "arguments[0].onmouseover = function(){ alert('test'); }");
		self::$driver->move_to($id, 5, 5);

		$this->assertEquals('test', self::$driver->alert_text());
		self::$driver->confirm(FALSE);
		self::$driver->visit('/remote-form.html');
	}

	public function test_all()
	{
		$this->assertCount(1, self::$driver->all('//textarea'));

		$this->assertCount(0, self::$driver->all('//nonexistant-tag'));
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

	public function test_connection()
	{
		$connection = new Driver_Selenium_Connection();
		$driver = new Driver_Selenium();

		$driver->connection($connection);

		$this->assertSame($connection, $driver->connection());

		$driver = new Driver_Selenium();
		$connection = $driver->connection();
		$this->assertInstanceOf('OpenBuildings\Spiderling\Driver_Selenium_Connection', $connection);
		$this->assertTrue($connection->is_started());
		unset($driver);
	}

	public function test_cookie()
	{
		self::$driver->cookie('test', 'value', array('path' => '/'));

		$cookies = self::$driver->cookies();

		$this->assertEquals('test', $cookies[0]['name']);
		$this->assertEquals('value', $cookies[0]['value']);
		$this->assertEquals('/', $cookies[0]['path']);

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

		$this->assertEquals('form.html', $value);
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

	public function test_is_page_active()
	{
		$this->assertTrue(self::$driver->is_page_active());

		$driver = new Driver_Selenium();

		$this->assertFalse($driver->is_page_active());
	}
}

