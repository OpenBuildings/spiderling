<?php

use Openbuildings\Spiderling\Node;
use PHPUnit\Framework\TestCase;

/**
 * Used for testing Spiderling
 *
 * @package    Openbuildings\Spiderling
 * @author     Ivan Kerin
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
abstract class Spiderling_TestCase extends TestCase {

	public function assertValueSet($node, $value, $expected_value, $driver, $message = 'Should set value of field properly')
	{
		$driver->set($node, 'New Title');
		$new_value = $driver->value($node);
		$this->assertEquals($expected_value, $new_value, $message);
	}

	public function assertNode($options, $tag, $message = 'Tag should be present')
	{
		$this->assertNotNull($tag, $message);

		if ($tag instanceof Node)
		{
			$tag = $tag->dom();
		}

		$this->assertInstanceOf('DOMNode', $tag, 'Should be of appropriate html tag type');

		foreach ((array) $options as $name => $value)
		{
			switch($name)
			{
				case '0':
					$this->assertEquals($value, $tag->nodeName, "The tag should be with type {$value} but was {$tag->nodeName}");
				break;
				case '1':
					$this->assertEquals($value, $tag->textContent, "The tag should have text {$value} but had {$tag->textContent}");
				break;
				default:
					$this->assertTrue($tag->hasAttribute($name), "Tag should have attribute {$name}");
					$this->assertEquals($value, $tag->getAttribute($name), "Tag's attribute {$name} should be {$value} but was {$tag->getAttribute($name)}");
			}
		}
	}
}
