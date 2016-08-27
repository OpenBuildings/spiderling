<?php

namespace Openbuildings\Spiderling;

/**
 * Use phantomjs to load urls.
 * Has Javascript
 *
 * @package    Openbuildings\Spiderling
 * @author     Ivan Kerin
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Driver_Phantomjs extends Driver {

	/**
	 * Driver name
	 * @var string
	 */
	public $name = 'phantomjs';

	/**
	 * Array containing parameters to be added on the next request
	 * @var array
	 */
	public $_next_query = array();

	/**
	 * User agent string cache
	 * @var string
	 */
	protected $_user_agent;

	/**
	 * Variable holding the current Driver_Phantomjs_Connection
	 * @var Driver_Phantomjs_Connection
	 */
	protected $_connection;

	/**
	 * The base URL, to be prefixed on each request
	 * @var string
	 */
	protected $_base_url = '';

	/**
	 * Getter / Setter of the base_url, that will be prefixed on each request
	 * @param  string $base_url
	 * @return string|Driver_PHantomjs
	 */
	public function base_url($base_url = NULL)
	{
		if ($base_url !== NULL)
		{
			$this->_base_url = (string) $base_url;
			return $this;
		}
		return $this->_base_url;
	}

	/**
	 * Getter / Setter of the Driver_Phantomjs_Connection object.
	 * Use this to customize the connection, otherwise a default one on a random port will be used
	 *
	 * @param  Driver_Phantomjs_Connection $connection
	 * @return Driver_Phantomjs_Connection|Driver_Phantomjs
	 */
	public function connection(Driver_Phantomjs_Connection $connection = NULL)
	{
		if ($connection !== NULL)
		{
			$this->_connection = $connection;
			return $this;
		}

		if ( ! $this->_connection)
		{
			$this->_connection = new Driver_Phantomjs_Connection();
			$this->_connection->start();
		}

		return $this->_connection;
	}

	/**
	 * If a connection has been started, stop it
	 */
	public function __destruct()
	{
		if ($this->_connection AND $this->_connection->is_started())
		{
			$this->_connection->stop();
		}
	}

	/**
	 * Clear the connection by deleting all cookies
	 */
	public function clear()
	{
		$this->connection()->delete('cookies');
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

		return $this->connection()->get("element/$id/html");
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
		return $this->connection()->get("element/$id/value");
	}

	/**
	 * Check if a Node with id is visible.
	 * @param  string  $id
	 * @return boolean
	 */
	public function is_visible($id)
	{
		return $this->connection()->get("element/$id/visible");
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
		return $this->connection()->get("element/$id/checked");
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
			$this->connection()->post("element/$id/value", array('value' => $value));
		}
		elseif ($tag_name == 'input')
		{
			$type = $this->attribute($id, 'type');
			if ($type == 'checkbox' OR $type == 'radio')
			{
				$this->connection()->post("element/$id/click", array());
			}
			elseif ($type == 'file')
			{
				$this->connection()->post("element/$id/upload", array('value' => $value));
			}
			else
			{
				$this->connection()->post("element/$id/value", array('value' => $value));
			}
		}
		elseif ($tag_name == 'option')
		{
			$this->connection()->post("element/$id/selected", array('value' => $value));
		}
	}

	/**
	 * Set the option value that is selected of a Node of a select element
	 * @param  string $id
	 * @param  string $value
	 */
	public function select_option($id, $value)
	{
		$this->connection()->post("element/$id/selected", array('value' => $value));
	}

	/**
	 * Click on a Node with id, triggering a link or form submit
	 * @param  string $id
	 * @throws Exception_Driver If not a clickable element
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

		$this->connection()->post('url', array('value' => $url));
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
		return $this->connection()->post(($parent === NULL ? '' : 'element/'.$parent.'/').'elements', array('value' => '.'.$xpath));
	}

	/**
	 * Setter for the next_query variable, to be added to the next visit's query
	 * @param  array  $query
	 */
	public function next_query(array $query)
	{
		$this->_next_query = $query;
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
	 * Return all javascript errors for the current page
	 * @return array
	 */
	public function javascript_errors()
	{
		return $this->connection()->get('errors', array());
	}

	/**
	 * Return all console messages for the current page
	 * @return array
	 */
	public function javascript_messages()
	{
		return $this->connection()->get('messages', array());
	}

	/**
	 * Do a screenshot of the current page into a file
	 * @param  string $file
	 */
	public function screenshot($file)
	{
		$this->connection()->post('screenshot', array('value' => $file));
	}

	/**
	 * Get the current user agent
	 * @return string
	 */
	public function user_agent()
	{
		if ( ! $this->_user_agent)
		{
			$settings = $this->connection()->get('settings');
			$this->_user_agent = isset($settings['userAgent']) ? $settings['userAgent'] : NULL;
		}

		return $this->_user_agent;
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
			'value' => $script,
			'id' => $id,
		));
	}

	/**
	 * Return all the current cookies
	 * @return array
	 */
	public function cookies()
	{
		return $this->connection()->get('cookies');
	}

	/**
	 * Set a cookie. Use parameters to set "expires", "path", "domain", "secure" and "httponly"
	 * @param  string $name
	 * @param  mixed $value
	 * @param  array  $parameters
	 */
	public function cookie($name, $value, array $parameters = array())
	{
		$this->connection()->post('cookie', array(
			'name' => $name,
			'value' => $value,
			'expires' => isset($parameters['expires']) ? $parameters['expires'] : time() + 86400,
			'path' => isset($parameters['path']) ? $parameters['path'] : '/',
			'domain' => isset($parameters['domain']) ? $parameters['domain'] : NULL,
			'secure' => isset($parameters['secure']) ? $parameters['secure'] : FALSE,
			'httponly' => isset($parameters['httponly']) ? $parameters['httponly'] : FALSE,
		));
	}
}
