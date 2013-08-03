<?php

namespace Openbuildings\Spiderling;

use Symfony\Component\CssSelector\CssSelector;

/**
 * Locator - converts varios locator formats into xpath
 *
 * @package    Openbuildings\Spiderling
 * @author     Ivan Kerin
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Locator {

	protected $_xpath;
	protected $_type;
	protected $_selector;
	protected $_filters;

	function __construct($selector, array $filters = array())
	{
		if ( ! is_array($selector))
		{
			$selector = array(
				'css',
				$selector,
				$filters
			);
		}
		// Manage nested selectors
		elseif (is_array($selector[1]))
		{
			$selector = $selector[1];
		}

		$this->_type = $selector[0];
		$this->_selector = $selector[1];
		$this->_filters = isset($selector[2]) ? $selector[2] : array();
	}

	public function is_filtered(Node $item, $index)
	{
		foreach ($this->filters() as $filter => $value) 
		{
			switch ($filter) 
			{
				case 'at':
					$matches_filter = $this->filter_by_at($item, $index, $value);
				break;

				case 'value':
					$matches_filter = $this->filter_by_value($item, $index, $value);
				break;

				case 'text':
					$matches_filter = $this->filter_by_text($item, $index, $value);
				break;

				case 'visible':
					$matches_filter = $this->filter_by_visible($item, $index, $value);
				break;

				case 'attributes':
					$matches_filter = $this->filter_by_attributes($item, $index, $value);			
				break;

				default:
					throw new Exception('Filter :filter does not exist', array(':filter' => $filter));
			}

			if ( ! $matches_filter) 
				return FALSE;
		}

		return TRUE;
	}

	public function filter_by_at(Node $item, $index, $value)
	{
		return $index == $value;
	}

	public function filter_by_value(Node $item, $index, $value)
	{
		return $item->value() == $value;
	}

	public function filter_by_text(Node $item, $index, $value)
	{
		$text = $item->text();
		
		return ($text AND $value AND mb_stripos($text, $value) !== FALSE);
	}

	public function filter_by_visible(Node $item, $index, $value)
	{
		return $item->is_visible() === $value;
	}

	public function filter_by_attributes(Node $item, $index, array $value)
	{
		foreach ($value as $attribute_name => $attribute_val) 
		{
			if ($item->attribute($attribute_name) != $attribute_val)
				return FALSE;
		}
		
		return TRUE;
	}

	public function xpath()
	{
		if ( ! $this->_xpath)
		{
			switch ($this->type()) 
			{
				case 'css':
					$this->_xpath = $this->css_to_xpath($this->selector());
				break;

				case 'field':
					$this->_xpath = $this->field_to_xpath($this->selector());
				break;

				case 'xpath':
					$this->_xpath = $this->xpath_to_xpath($this->selector());
				break;

				case 'label':
					$this->_xpath = $this->label_to_xpath($this->selector());
				break;

				case 'link':
					$this->_xpath = $this->link_to_xpath($this->selector());
				break;
				
				case 'button':
					$this->_xpath = $this->button_to_xpath($this->selector());
				break;

				default:
					throw new Exception('Locator type ":type" does not exist', array(':type' => $this->_type));
			}
		}

		return $this->_xpath;
	}

	public function type()
	{
		return $this->_type;
	}

	public function selector()
	{
		return $this->_selector;
	}

	public function filters()
	{
		return $this->_filters;
	}

	public function css_to_xpath($locator)
	{
		return '//'.CssSelector::toXPath($locator);
	}

	public function xpath_to_xpath($locator)
	{
		return $locator;
	}

	public function field_to_xpath($locator)
	{
		$type = "(self::input and (not(@type) or @type != 'submit')) or self::textarea or self::select";
			
		$matchers['by name']        = "@name = '$locator'";
		$matchers['by id']          = "@id = '$locator'";
		$matchers['by placeholder'] = "@placeholder = '$locator'";
		$matchers['by label for']   = "@id = //label[normalize-space() = '$locator']/@for";
		$matchers['by option']      = "(self::select and ./option[(@value = \"\" or not(@value)) and contains(normalize-space(), \"$locator\")])";

		return "//*[($type) and (".join(' or ', $matchers).")]";
	}

	public function label_to_xpath($locator)
	{
		$type = "self::label";
			
		$matchers['by id']           = "@id = '$locator'";
		$matchers['by title']        = "contains(@title, '$locator')";
		$matchers['by content text'] = "contains(normalize-space(), '$locator')";
		$matchers['by img alt']      = "descendant::img[contains(@alt, '$locator')]";

		return "//*[($type) and (".join(' or ', $matchers).")]";
	}

	public function link_to_xpath($locator)
	{
		$matchers['by title']        = "contains(@title, '$locator')";
		$matchers['by id']           = "@id = '$locator'";
		$matchers['by content text'] = "contains(normalize-space(), '$locator')";
		$matchers['by img alt']      = "descendant::img[contains(@alt, '$locator')]";

		return "//a[".join(' or ', $matchers)."]";	
	}

	public function button_to_xpath($locator)
	{
		$type = "(self::input and @type = 'submit') or self::button";

		$matchers['by title']        = "contains(@title, '$locator')";
		$matchers['by id']           = "@id = '$locator'";
		$matchers['by content text'] = "contains(normalize-space(), '$locator')";
		$matchers['by img alt']      = "descendant::img[contains(@alt, '$locator')]";
		$matchers['by value']        = "contains(@value, '$locator')";
		$matchers['by name']         = "@name = '$locator'";

		return "//*[($type) and (".join(' or ', $matchers).")]";
	}

	public function __toString()
	{
		if ($this->filters())
		{
			$filters = " filters: ".json_encode($this->filters());
		}
		else
		{
			$filters = '';
		}
		return "Locator: ({$this->type()}) {$this->selector()}".$filters;
	}
}
