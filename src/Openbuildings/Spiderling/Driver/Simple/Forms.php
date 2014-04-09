<?php

namespace Openbuildings\Spiderling;

/**
 * Helper to handle form serialization, modification and posting
 *
 * @package    Openbuildings\Spiderling
 * @author     Ivan Kerin
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Driver_Simple_Forms
{
	/**
	 * DOMXPath object to perform queries with
	 * @var DOMXPath
	 */
	protected $_xpath;

	function __construct($xpath)
	{
		$this->xpath = $xpath;
	}

	/**
	 * Get the value of a DOMElement with a given xpath
	 * @param  string $xpath
	 * @return mixed
	 */
	public function get_value($xpath)
	{
		$node = $this->xpath->find($xpath);

		switch ($node->tagName)
		{
			case 'textarea':
				return $node->textContent;
			break;

			case 'select':
				$options = array();
				foreach ($this->xpath->query(".//option[@selected]", $node) as $option)
				{
					$options[] = $option->hasAttribute('value') ? $option->getAttribute('value') : $option->textContent;
				}
				return $node->hasAttribute('multiple') ? $options : (isset($options[0]) ? $options[0] : NULL);
			break;
			default:
				return $node->getAttribute('value');
		}
	}

	/**
	 * Set the value of a DOMElement, identified by an xpath, calls one of the stter methods
	 * @param string $xpath
	 * @param mixed $value
	 */
	public function set_value($xpath, $value)
	{
		$node = $this->xpath->find($xpath);

		$setter = 'set_value_input';
		$type = $node->getAttribute('type');

		if ($node->tagName == 'input' AND $type == 'checkbox')
		{
			$setter = 'set_value_checkbox';
		}

		elseif ($node->tagName == 'input' AND $type == 'radio')
		{
			$setter = 'set_value_radio';
		}

		elseif ($node->tagName == 'textarea')
		{
			$setter = 'set_value_textarea';
		}

		elseif ($node->tagName == 'option')
		{
			$setter = 'set_value_option';
		}

		$this->{$setter}($node, $value);
	}

	/**
	 * Set the value of a checkbos DOMNode
	 * @param DOMNode $checkbox
	 * @param boolean   $value
	 */
	public function set_value_checkbox(\DOMNode $checkbox, $value)
	{
		if ($value)
		{
			$checkbox->setAttribute('checked', 'checked');
		}
		else
		{
			$checkbox->removeAttribute('checked');
		}
	}

	/**
	 * Set the value of a radio DOMNode, uncheck any other radio input in the same group
	 * @param DOMNode $radio
	 * @param boolean   $value
	 */
	public function set_value_radio(\DOMNode $radio, $value)
	{
		$name = $radio->getAttribute('name');
		foreach ($this->xpath->query("//input[@type='radio' and @name='$name' and @checked]") as $other_radio)
		{
			$other_radio->removeAttribute('checked');
		}
		if ($value)
		{
			$radio->setAttribute('checked', 'checked');
		}
	}

	/**
	 * Set the value of a normal input
	 * @param DOMNode $input
	 * @param string   $value
	 */
	public function set_value_input(\DOMNode $input, $value)
	{
		$input->setAttribute('value', $value);
	}

	/**
	 * Set the value of a normal textarea
	 * @param DOMNode $textarea
	 * @param string   $value
	 */
	public function set_value_textarea(\DOMNode $textarea, $value)
	{
		$textarea->nodeValue = $value;
	}

	/**
	 * Set the value of an option DOMNode, unselect other options in this select, if it is not multiple
	 * @param DOMNode $option
	 * @param boolean   $value
	 */
	public function set_value_option(\DOMNode $option, $value)
	{
		if ($value)
		{
			$select = $this->xpath->find("./ancestor::select", $option);

			if ( ! $select->hasAttribute('multiple'))
			{
				foreach ($this->xpath->query(".//option[@selected]", $select) as $old_option)
				{
					$old_option->removeAttribute('selected');
				}
			}

			$option->setAttribute('selected', 'selected');
		}
		else
		{
			$option->removeAttribute('selected');
		}
	}

	/**
	 * Return all the contents of file inputs in a form, identified by an xpath
	 * @param  string $xpath
	 * @return string
	 */
	public function serialize_files($xpath)
	{
		$form = $this->xpath->find($xpath);

		$fields = ".//*[not(@disabled) and (self::input and @type = 'file')]";
		$data = array();
		foreach ($this->xpath->query($fields, $form) as $field)
		{
			$data[] = $field->getAttribute('name').'='.$field->getAttribute('value');
		}

		return join('&', $data);
	}

	/**
	 * Return the contents of all the inputs from a form, identified by an xpath.
	 * Don't include file inputs or disabled inputs
	 * @param  string $xpath
	 * @return string
	 */
	public function serialize_form($xpath)
	{
		$form = $this->xpath->find($xpath);

		$types['radio']    = "(self::input and @type = 'radio' and @checked)";
		$types['checkbox'] = "(self::input and @type = 'checkbox' and @checked)";
		$types['others']   = "(self::input and @type != 'radio' and @type != 'checkbox' and @type != 'file' and @type != 'submit')";
		$types['notype']   = "(self::input and not(@type))";
		$types['select']   = "(self::select)";
		$types['textarea'] = "(self::textarea)";

		$fields = ".//*[not(@disabled) and (".join(' or ', $types).")]";
		$data = array();
		foreach ($this->xpath->query($fields, $form) as $field)
		{
			$value = $this->get_value($field);
			if (is_array($value))
			{
				foreach ($value as $name => $value_item)
				{
					$data[] = $field->getAttribute('name')."[$name]".'='.urlencode($value_item);
				}
			}
			else
			{
				$data[] = $field->getAttribute('name').'='.urlencode($value);
			}
		}

		return join('&', $data);
	}
}
