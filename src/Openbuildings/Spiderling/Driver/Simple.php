<?php

namespace Openbuildings\Spiderling;

use Openbuildings\EnvironmentBackup\Environment;
use Openbuildings\EnvironmentBackup\Environment_Group_Globals;
use Openbuildings\EnvironmentBackup\Environment_Group_Server;
use Openbuildings\EnvironmentBackup\Environment_Group_Static;

/**
 * Use Curl to load urls.
 * In memory.
 * No Javascript
 *
 * @package    Openbuildings\Spiderling
 * @author     Ivan Kerin
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Driver_Simple extends Driver {

	/**
	 * The name of the driver
	 * @var string
	 */
	public $name = 'simple';

	/**
	 * Wait time is meaningless in simple driver as it does not support javascript
	 * @var integer
	 */
	public $default_wait_time = 10;

	/**
	 * The raw html content of the page
	 * @var string
	 */
	protected $_content;

	/**
	 * The DOMDocument of the current page
	 * @var DOMDocument
	 */
	protected $_dom;

	/**
	 * The DOMXpath object for finding elements on the page
	 * @var DOMXpath
	 */
	protected $_xpath;

	/**
	 * Object for handling forms on the page
	 * @var Driver_Simple_Forms
	 */
	protected $_forms;

	/**
	 * Environment object for handling backups of environment state
	 * @var Environment
	 */
	protected $_environment;

	/**
	 * Driver_Simple_RequestFactory object for opening new pages
	 * @var Driver_Simple_RequestFactory
	 */
	protected $_request_factory;

	function __construct()
	{
		$this->_dom = new \DOMDocument('1.0', 'utf-8');
		$this->_environment = new Environment(array(
			'globals' => new Environment_Group_Globals(),
			'server' => new Environment_Group_Server(),
			'static' => new Environment_Group_Static(),
		));

		$this->_request_factory = new Driver_Simple_RequestFactory_HTTP();
	}

	/**
	 * Getter / Setter for the request factory object for the driver
	 * @param  Driver_Simple_RequestFactory $request_factory
	 * @return Driver_Simple_RequestFactory|Driver_Simple
	 */
	public function request_factory(Driver_Simple_RequestFactory $request_factory = NULL)
	{
		if ($request_factory !== NULL)
		{
			$this->_request_factory = $request_factory;
			return $this;
		}

		return $this->_request_factory;
	}

	/**
	 * Getter for the current environment
	 * @return Environment
	 */
	public function environment()
	{
		return $this->_environment;
	}

	/**
	 * Restore the environment
	 * @return Driver_Simple $this
	 */
	public function clear()
	{
		$this->environment()->restore();

		return $this;
	}

	/**
	 * Getter / Setter of the raw content html
	 * @param  string $content
	 * @return string|Driver_Simple
	 */
	public function content($content = NULL)
	{
		if ($content !== NULL)
		{
			$this->_content = (string) $content;
			$this->initialize();

			return $this;
		}
		return $this->_content;
	}

	/**
	 * Initialze the dom, xpath and forms objects, based on the content string
	 */
	public function initialize()
	{
		@ $this->_dom->loadHTML($this->content());
		$this->_dom->encoding = 'utf-8';
		$this->_xpath = new Driver_Simple_Xpath($this->_dom);
		$this->_forms = new Driver_Simple_Forms($this->_xpath);
	}

	/**
	 * Getter the current forms object
	 * @return Driver_Simple_Forms
	 */
	public function forms()
	{
		return $this->_forms;
	}

	/**
	 * Getter for the current xpath object for the page
	 * @return DOMXpath
	 */
	public function xpath()
	{
		return $this->_xpath;
	}

	/**
	 * Get the DOMElement for the current id, or root if no id is given
	 * @param  string $id
	 * @return DOMElement
	 */
	public function dom($id = NULL)
	{
		return $id ? $this->xpath()->find($id) : $this->_dom;
	}

	/**
	 * Initiate a get request to a given uri
	 * @param  string $uri
	 * @param  array  $query an array for the http query
	 * @return Driver_Simple        $this
	 */
	public function get($uri, array $query = array())
	{
		return $this->request('GET', $uri, $query);
	}

	/**
	 * Initiate a post request to a given uri
	 * @param  string $uri
	 * @param  array  $query
	 * @param  array  $post
	 * @param  array  $files set the $_FILES array appropriately
	 * @return Driver_Simple        $this
	 */
	public function post($uri, array $query = array(), array $post = array(), array $files = array())
	{
		return $this->request('POST', $uri, $query, $post, $files);
	}

	/**
	 * Initiate a request with a custom method
	 * @param  string $method
	 * @param  string $uri
	 * @param  array  $query
	 * @param  array  $post
	 * @param  array  $files  set the $_FILES array
	 * @return Driver_Simple         $this
	 */
	public function request($method, $uri, array $query = array(), array $post = array(), array $files = array())
	{
		$uri_params = parse_url($uri, PHP_URL_QUERY);

		if ($uri_params)
		{
			parse_str($uri_params, $params);
			$query = array_merge($params, $query);
			$uri = str_replace('?'.$uri_params, '', $uri);
		}

		$url = $uri.($query ? '?'.http_build_query($query) : '');

		$this->environment()->backup_and_set(array(
			'_GET' => $query,
			'_POST' => $post,
			'_FILES' => $files,
			'_SESSION' => isset($_SESSION) ? $_SESSION : array(),
		));

		$response = $this->request_factory()->execute($method, $url, $post);

		$this->content($response);

		return $this;
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
		return $this->dom($id)->tagName;
	}

	/**
	 * Get the attribute of a Node with id. If the attribute does not exist, returns NULL
	 * @param  string $id
	 * @param  string $name
	 * @return string
	 */
	public function attribute($id, $name)
	{
		$node = $this->dom($id);

		return $node->hasAttribute($name) ? $node->getAttribute($name) : NULL;
	}

	/**
	 * Return the raw html of a Node with id, along with all of its children.
	 * @param  string $id
	 * @return string
	 */
	public function html($id)
	{
		if ($id === NULL)
			return $this->dom()->saveHTML();

		$node = $this->dom($id);

		return $node->ownerDocument->saveXml($node);
	}

	/**
	 * Return the text of a Node with id, with all the spaces collapsed, similar to browser rendering.
	 * @param  string $id
	 * @return string
	 */
	public function text($id)
	{
		$text = $this->dom($id)->textContent;
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
		return $this->forms()->get_value($id);
	}

	/**
	 * Check if a Node with id is visible.
	 * A Node is considered hidden if it has a "display:none" inline style or its a script or head tag.
	 * @param  string  $id
	 * @return boolean
	 */
	public function is_visible($id)
	{
		$node = $this->dom($id);

		$hidden_nodes = $this->xpath()->query("./ancestor-or-self::*[contains(@style, 'display:none') or contains(@style, 'display: none') or name()='script' or name()='head']", $node);
		return $hidden_nodes->length == 0;
	}

	/**
	 * Check if a Node with id of an option element is selected
	 * @param  string  $id
	 * @return boolean
	 */
	public function is_selected($id)
	{
		return (bool) $this->dom($id)->getAttribute('selected');
	}

	/**
	 * Check if a Node with id of an input element (radio or checkbox) is checked
	 * @param  string  $id
	 * @return boolean
	 */
	public function is_checked($id)
	{
		return (bool) $this->dom($id)->getAttribute('checked');
	}

	/**
	 * Set the value of a Node with id of a form element
	 * @param string $id
	 * @param string $value
	 */
	public function set($id, $value)
	{
		$this->forms()->set_value($id, $value);
	}

	/**
	 * Set the option value that is selected of a Node of a select element
	 * @param  string $id
	 * @param  string $value
	 */
	public function select_option($id, $value)
	{
		$this->forms()->set_value($id, $value);
	}

	/**
	 * Return the serialized representation of the input values of a form.
	 * Do not include files or disabled inputs, as well as submit inputs and buttons
	 * @param  string $id
	 * @return string
	 */
	public function serialize_form($id)
	{
		return $this->forms()->serialize_form($id);
	}

	/**
	 * Click on a Node with id, triggering a link or form submit
	 * @param  string $id
	 * @throws Exception_Driver If not a clickable element
	 */
	public function click($id)
	{
		$node = $this->dom($id);

		if ($node->hasAttribute('href'))
		{
			$this->get($node->getAttribute('href'));
		}
		elseif (($node->tagName == 'input' AND $node->getAttribute('type') == 'submit') OR $node->tagName == 'button')
		{
			$form = $this->xpath()->find('./ancestor::form', $node);

			$action = $form->hasAttribute('action') ? $form->getAttribute('action') : $this->request->uri();

			$method = $form->hasAttribute('method') ? strtoupper($form->getAttribute('method')) : 'GET';

			$post = $this->forms()->serialize_form($form);

			$files = $this->forms()->serialize_files($form);

			if (in_array($node->tagName, array('button', 'input')) AND $node->hasAttribute('name'))
			{
				$post = $post.'&'.$node->getAttribute('name').'='.$node->getAttribute('value');
			}
			parse_str($post, $post);
			parse_str($files, $files);

			if ($method == 'GET')
			{
				$this->get($action, $post);
			}
			else
			{
				$this->post($action, array(), $post, $files);
			}
		}
		else
		{
			throw new Exception_Driver('The html tag :tag cannot be clicked', array(':tag' => $node->tagName));
		}
	}

	/**
	 * Go to a given url address
	 * @param  string $uri
	 * @param  array  $query
	 * @return Driver_Simple        $this
	 */
	public function visit($uri, array $query = array())
	{
		return $this->get($uri, $query);
	}

	/**
	 * Get the current path (without host and protocol)
	 * @return string
	 */
	public function current_path()
	{
		return $this->request_factory()->current_path();
	}

	/**
	 * Get the current url
	 * @return string
	 */
	public function current_url()
	{
		return $this->request_factory()->current_url();
	}

	/**
	 * Find all ids of a given XPath
	 * @param  string $xpath
	 * @param  string $parent id of the parent node
	 * @return array
	 */
	public function all($xpath, $parent = NULL)
	{
		$xpath = $parent.$xpath;
		$ids = array();
		foreach ($this->xpath()->query($xpath) as $index => $elmenets)
		{
			$ids[] = "($xpath)[".($index+1)."]";
		}
		return $ids;
	}

	/**
	 * Check if anything has been loaded
	 * @return boolean
	 */
	public function is_page_active()
	{
		return (bool) $this->content();
	}

	/**
	 * Return the current user agent
	 * @return string
	 */
	public function user_agent()
	{
		return $this->request_factory()->user_agent();
	}

	/**
	 * Set the cookie (by setting the $_COOKIE array directly)
	 * @param  string $name
	 * @param  mixed $value
	 * @param  array  $parameters - not utalized by this driver
	 */
	public function cookie($name, $value, array $parameters = array())
	{
		$_COOKIE[$name] = $value;
	}

}
