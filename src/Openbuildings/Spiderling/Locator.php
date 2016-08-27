<?php

namespace Openbuildings\Spiderling;

use Symfony\Component\CssSelector;

/**
 * Locator - converts varios locator formats into xpath
 *
 * @package    Openbuildings\Spiderling
 * @author     Ivan Kerin
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Locator {

	const DEFAULT_TYPE = 'css';

	/**
	 * Converted xpath cache
	 * @var string
	 */
	protected $_xpath;

	/**
	 * The type of the locator, can be css, field, xpath, label, link or button
	 * @var string
	 */
	protected $_type;

	/**
	 * The selector used to generate the xpath
	 * @var string
	 */
	protected $_selector;

	/**
	 * Additional filters to apply after the xpath is found
	 * @var array
	 */
	protected $_filters;

	/**
	 * @param string $type
	 * @param string $selector
	 * @param array $filters
	 */
	function __construct($type, $selector, array $filters = array())
	{
		$this->_type = $type === NULL ? self::DEFAULT_TYPE : $type;
		$this->_selector = $selector;
		$this->_filters = $filters;
	}

	/**
	 * Check if a Node item matches the current filters
	 * @param  Node    $item
	 * @param  integer  $index
	 * @return boolean
	 */
	public function is_filtered(Node $item, $index)
	{
		foreach ($this->filters() as $filter => $value)
		{
			try {
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

			} catch (Exception_Staleelement $e) {
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Try matching "at" filter
	 *
	 * @param  Node   $item
	 * @param  integer $index
	 * @param  string $value
	 * @return boolean
	 */
	public function filter_by_at(Node $item, $index, $value)
	{
		return $index == $value;
	}

	/**
	 * Try matching "value" filter
	 *
	 * @param  Node   $item
	 * @param  integer $index
	 * @param  string $value
	 * @return boolean
	 */
	public function filter_by_value(Node $item, $index, $value)
	{
		return $item->value() == $value;
	}

	/**
	 * Try matching "text" filter
	 *
	 * @param  Node   $item
	 * @param  integer $index
	 * @param  string $value
	 * @return boolean
	 */
	public function filter_by_text(Node $item, $index, $value)
	{
		$text = $item->text();

		return ($text AND $value AND mb_stripos($text, $value) !== FALSE);
	}

	/**
	 * Try matching "visible" filter
	 *
	 * @param  Node   $item
	 * @param  integer $index
	 * @param  boolean $value
	 * @return boolean
	 */
	public function filter_by_visible(Node $item, $index, $value)
	{
		return $item->is_visible() === $value;
	}

	/**
	 * Try matching "attributes" filter
	 *
	 * @param  Node   $item
	 * @param  integer $index
	 * @param  array $value
	 * @return boolean
	 */
	public function filter_by_attributes(Node $item, $index, array $value)
	{
		foreach ($value as $attribute_name => $attribute_val)
		{
			if ($item->attribute($attribute_name) != $attribute_val)
				return FALSE;
		}

		return TRUE;
	}

	/**
	 * Return the xpath representaiton of the selector, using the appropriate method
	 * @return string
	 */
	public function xpath()
	{
		if ( ! $this->_xpath)
		{
			switch ($this->type())
			{
				case 'css':
					$this->_xpath = '//'.$this->convert_css_selector_to_xpath($this->selector());
				break;

				case 'xpath':
					$this->_xpath = $this->selector();
				break;

				case 'field':
					$this->_xpath = $this->field_to_xpath($this->selector());
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

	/**
	 * Getter. Current type, one of css, field, xpath, label, link or button
	 * @return string
	 */
	public function type()
	{
		return $this->_type;
	}

	/**
	 * Getter. Current selector
	 * @return string
	 */
	public function selector()
	{
		return $this->_selector;
	}

	/**
	 * Getter. Current filters
	 * @return array
	 */
	public function filters()
	{
		return $this->_filters;
	}

	/**
	 * Convert field selector into xpath
	 *
	 * @param  string $selector
	 * @return string
	 */
	public function field_to_xpath($selector)
	{
		$type = "(self::input and (not(@type) or @type != 'submit')) or self::textarea or self::select";

		$matchers['by name']        = "@name = '$selector'";
		$matchers['by id']          = "@id = '$selector'";
		$matchers['by placeholder'] = "@placeholder = '$selector'";
		$matchers['by label for']   = "@id = //label[normalize-space() = '$selector']/@for";
		$matchers['by option']      = "(self::select and ./option[(@value = \"\" or not(@value)) and contains(normalize-space(), \"$selector\")])";

		return "//*[($type) and (".join(' or ', $matchers).")]";
	}

	/**
	 * Convert label selector to xpath
	 * @param  string $selector
	 * @return string
	 */
	public function label_to_xpath($selector)
	{
		$type = "self::label";

		$matchers['by id']           = "@id = '$selector'";
		$matchers['by title']        = "contains(@title, '$selector')";
		$matchers['by content text'] = "contains(normalize-space(), '$selector')";
		$matchers['by img alt']      = "descendant::img[contains(@alt, '$selector')]";

		return "//*[($type) and (".join(' or ', $matchers).")]";
	}

	/**
	 * Convert link selector to xpath
	 * @param  string $selector
	 * @return string
	 */
	public function link_to_xpath($selector)
	{
		$matchers['by title']        = "contains(@title, '$selector')";
		$matchers['by id']           = "@id = '$selector'";
		$matchers['by content text'] = "contains(normalize-space(), '$selector')";
		$matchers['by img alt']      = "descendant::img[contains(@alt, '$selector')]";

		return "//a[".join(' or ', $matchers)."]";
	}

	/**
	 * Convert button selector to xpath
	 * @param  string $selector
	 * @return string
	 */
	public function button_to_xpath($selector)
	{
		$type = "(self::input and @type = 'submit') or self::button";

		$matchers['by title']        = "contains(@title, '$selector')";
		$matchers['by id']           = "@id = '$selector'";
		$matchers['by content text'] = "contains(normalize-space(), '$selector')";
		$matchers['by img alt']      = "descendant::img[contains(@alt, '$selector')]";
		$matchers['by value']        = "contains(@value, '$selector')";
		$matchers['by name']         = "@name = '$selector'";

		return "//*[($type) and (".join(' or ', $matchers).")]";
	}

	/**
	 * Return a pretty printed representation of the locator
	 * @return string
	 */
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

    /**
     * Convert a CSS selector to XPath.
     * Uses Symfony CSS Selector 2.8 and above API if possible.
     * Otherwise fallback to pre-2.8 static interface.
     *
     * @param  string $css_selector CSS selector
     * @return string XPath selector
     */
    private function convert_css_selector_to_xpath($css_selector)
    {
        if (class_exists('Symfony\Component\CssSelector\CssSelectorConverter')) {
            $converter = new CssSelector\CssSelectorConverter();
            return $converter->toXPath($this->selector());
        }

        return CssSelector\CssSelector::toXPath($this->selector());
    }
}
