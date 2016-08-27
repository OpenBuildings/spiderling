<?php

namespace Openbuildings\Spiderling;

/**
 * Use Selenium to load pages.
 * Has Javascript
 *
 * @package    Openbuildings\Spiderling
 * @author     Ivan Kerin
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Driver_Selenium extends Driver {

	/**
	 * Driver name
	 * @var string
	 */
	public $name = 'selenium';

	/**
	 * Array containing parameters to be added on the next request
	 * @var array
	 */
	protected $_next_query = array();

	/**
	 * Variable holding the current Driver_Selenium_Connection
	 * @var Driver_Selenium_Connection
	 */
	protected $_connection;

	/**
	 * The base URL, to be prefixed on each request
	 * @var string
	 */
	protected $_base_url = '';

	/**
	 * As selenium might be a bit slow to respond, we increase the default wait time.
	 * @var integer
	 */
	public $default_wait_time = 4000;

	/**
	 * Getter / Setter of the base_url, that will be prefixed on each request
	 * @param  string $base_url
	 * @return string|Driver_PHantomjs
	 */
	public function base_url($base_url = NULL)
	{
		if ($base_url !== NULL)
		{
			$this->_base_url = $base_url;
			return $this;
		}
		return $this->_base_url;
	}

	/**
	 * Clear the connection by deleting all cookies
	 */
	public function clear()
	{
		$this->connection()->delete('cookie');
	}

	/**
	 * Getter of the raw content html, this driver does not allow "setting"
	 *
	 * @param  string $content
	 * @return string
	 */
	public function content($content = NULL)
	{
		return $this->connection()->get('source');
	}

	/**
	 * Getter / Setter of the Driver_Selenium_Connection object.
	 * Use this to customize the connection, otherwise a default one on a random port will be used
	 *
	 * @param  Driver_Selenium_Connection $connection
	 * @return Driver_Selenium_Connection|Driver_Selenium
	 */
	public function connection(Driver_Selenium_Connection $connection = NULL)
	{
		if ($connection !== NULL)
		{
			$this->_connection = $connection;
			return $this;
		}

		if ( ! $this->_connection)
		{
			$this->_connection = new Driver_Selenium_Connection();
			$this->_connection->start(array('browserName' => 'firefox', 'acceptSslCerts' => FALSE));
		}

		return $this->_connection;
	}

	/**
	 * NODE GETTERS
	 * =====================================
	 */

	/**
	 * Get the tag name of a Node with id. e.g. DIV, SPAN ...
	 * @param  string $id
	 * @return string
	 */
	public function tag_name($id)
	{
		return $this->connection()->get("element/$id/name");
	}

	/**
	 * Get the attribute of a Node with id. If the attribute does not exist, returns NULL
	 * @param  string $id
	 * @param  string $name
	 * @return string
	 */
	public function attribute($id, $name)
	{
		return $this->connection()->get("element/$id/attribute/$name");
	}

	/**
	 * Return the raw html of a Node with id, along with all of its children.
	 * @param  string $id
	 * @return string
	 */
	public function html($id)
	{
		if ($id === NULL)
			return $this->content();

		return $this->execute($id, 'return arguments[0].outerHTML');
	}

	/**
	 * Return the text of a Node with id, with all the spaces collapsed, similar to browser rendering.
	 * @param  string $id
	 * @return string
	 */
	public function text($id)
	{
		$text = $this->connection()->get("element/$id/text");
		$text = preg_replace('/[ \s\f\n\r\t\v ]+/u', ' ', $text);
		return trim($text);
	}

	/**
	 * Return the value of a Node of a form element, e.g. INPUT, TEXTAREA or SELECT
	 * @param  string $id
	 * @return string
	 */
	public function value($id)
	{
		if ($this->tag_name($id) == 'select' AND $this->attribute($id, 'multiple'))
		{
			$self = $this;
			$values = array();
			foreach ($this->all('//option', $id) as $option_id)
			{
				if ($this->is_selected($option_id))
				{
					$values []= $this->value($option_id);
				}
			}
			return $values;
		}
		else
		{
			return $this->connection()->get("element/$id/value");
		}
	}

	/**
	 * Check if a Node with id is visible.
	 * @param  string  $id
	 * @return boolean
	 */
	public function is_visible($id)
	{
		return $this->connection()->get("element/$id/displayed");
	}

	/**
	 * Check if a Node with id of an option element is selected
	 * @param  string  $id
	 * @return boolean
	 */
	public function is_selected($id)
	{
		return $this->connection()->get("element/$id/selected");
	}

	/**
	 * Check if a Node with id of an input element (radio or checkbox) is checked
	 * @param  string  $id
	 * @return boolean
	 */
	public function is_checked($id)
	{
		return $this->connection()->get("element/$id/selected");
	}

	/**
	 * Set the value of a Node with id of a form element
	 * @param string $id
	 * @param string $value
	 */
	public function set($id, $value)
	{
		$tag_name = $this->tag_name($id);

		if ($tag_name == 'textarea')
		{
			$this->connection()->post("element/$id/clear", array());
			$this->connection()->post("element/$id/value", array('value' => str_split($value)));
		}
		elseif ($tag_name == 'input')
		{
			$type = $this->attribute($id, 'type');
			if ($type == 'checkbox' OR $type == 'radio')
			{
				$this->connection()->post("element/$id/click", array());
			}
			else
			{
				if ($type !== 'file')
				{
					$this->connection()->post("element/$id/clear", array());
				}
				$this->connection()->post("element/$id/value", array('value' => str_split($value)));
			}
		}
		elseif ($tag_name == 'option')
		{
			$this->connection()->post("element/$id/click", array());
		}
	}

	/**
	 * Set the option value that is selected of a Node of a select element
	 * @param  string $id
	 * @param  string $value
	 */
	public function select_option($id, $value)
	{
		$this->connection()->post("element/$id/click", array());
	}

	/**
	 * Confirm or cancel for the next confirmation dialog
	 * @param  bool $confirm
	 */
	public function confirm($confirm)
	{
		if ($confirm)
		{
			$this->connection()->post('accept_alert', array());
		}
		else
		{
			$this->connection()->post('dismiss_alert', array());
		}
	}

	/**
	 * Get the text of the currently displayed alert / confirm /prompt dialog
	 * @param  bool $confirm
	 */
	public function alert_text()
	{
		return $this->connection()->get("alert_text");
	}

	/**
	 * Click on a Node with id, triggering a link or form submit
	 * @param  string $id
	 */
	public function click($id)
	{
		$this->connection()->post("element/$id/click", array());
	}

	/**
	 * Go to a given url address, use next_query along with the provided query array
	 * @param  string $uri
	 * @param  array  $query
	 */
	public function visit($uri, array $query = array())
	{
		$query = array_merge((array) $this->_next_query, (array) $query);

		$this->_next_query = NULL;

		// Check for implicit query string
		if (strpos($uri, '?') !== FALSE)
		{
			$query = array_merge(self::extract_query_from_uri($uri), $query);
			$uri = substr($uri, 0, strpos($uri, '?'));
		}

		$url = $this->base_url().$uri.($query ? '?'.http_build_query($query) : '');

		$this->connection()->post('url', array('url' => $url));
	}

	/**
	 * Get the current path (without host and protocol)
	 * @return string
	 */
	public function current_path()
	{
		$url = parse_url($this->connection()->get('url'));

		return $url['path'].(isset($url['query']) ? '?'.$url['query'] : '');
	}

	/**
	 * Get the current url
	 * @return string
	 */
	public function current_url()
	{
		return urldecode($this->connection()->get('url'));
	}

	/**
	 * Find all ids of a given XPath
	 * @param  string $xpath
	 * @param  string $parent id of the parent node
	 * @return array
	 */
	public function all($xpath, $parent = NULL)
	{
		$elements = $this->connection()->post(($parent === NULL ? '' : 'element/'.$parent.'/').'elements', array('using' => 'xpath', 'value' => '.'.$xpath));

		return array_map(function($item){ return $item['ELEMENT'];}, $elements);
	}

	/**
	 * Setter for the next_query variable, to be added to the next visit's query
	 * @param  array  $query
	 */
	public function next_query(array $query)
	{
		$this->_next_query = $query;
		return $this;
	}

	/**
	 * Execute raw javascript. it will be executed in the context of a given node ('this' will point to the node) and the return of the script will be the return of this method
	 * @param  string $id
	 * @param  string $script
	 * @return mixed
	 */
	public function execute($id, $script)
	{
		return $this->connection()->post('execute', array(
			'script' => $script,
			'args' => $id ? array(array('ELEMENT' => $id)) : array()
		));
	}

	/**
	 * Check if a connection is active
	 * @return boolean
	 */
	public function is_page_active()
	{
		return (bool) $this->_connection;
	}

	/**
	 * Move the mouse to a given element, or coordinates, or coordinates relative to a given element
	 * @param  string $id
	 * @param  integer $x
	 * @param  integer $y
	 */
	public function move_to($id = NULL, $x = NULL, $y = NULL)
	{
		$this->connection()->post('moveto', array_filter(array(
			'element' => $id,
			'xoffset' => $x,
			'yoffset' => $y
		), function($param)
		{
			return ($param OR $param === 0);
		}));
	}

	/**
	 * Do a screenshot of the current page into a file
	 * @param  string $file
	 */
	public function screenshot($file)
	{
		$data = $this->connection()->get('screenshot');

		file_put_contents($file, base64_decode($data));
	}

	/**
	 * Return all the current cookies
	 * @return array
	 */
	public function cookies()
	{
		return $this->connection()->get('cookie');
	}

	/**
	 * Set a cookie. Use parameters to set "expiry", "path", "domain" and "secure"
	 * @param  string $name
	 * @param  mixed $value
	 * @param  array  $parameters
	 */
	public function cookie($name, $value, array $parameters = array())
	{
		$parameters = array_merge(array(
			'name' => $name,
			'value' => $value,
			'expiry' => time() + 86400,
		), $parameters);

		return $this->connection()->post('cookie', array('cookie' => $parameters));
	}
}
