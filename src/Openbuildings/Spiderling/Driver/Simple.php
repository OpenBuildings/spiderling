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

	public $name = 'simple';

	protected $_content;
	protected $_dom;
	protected $_xpath;
	protected $_forms;
	protected $_environment;
	protected $_request;

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
	
	public function request_factory(Driver_Simple_RequestFactory $request_factory = NULL)
	{
		if ($request_factory !== NULL)
		{
			$this->_request_factory = $request_factory;
			return $this;
		}

		return $this->_request_factory;
	}

	public function environment()
	{
		return $this->_environment;
	}

	public function clear()
	{
		$this->environment()->restore();
	}

	public function content($content = NULL)
	{
		if ($content !== NULL)
		{
			$this->_content = $content;
			$this->initialize();

			return $this;
		}
		return $this->_content;
	}

	public function initialize()
	{
		@ $this->_dom->loadHTML($this->content());
		$this->_dom->encoding = 'utf-8';
		$this->_xpath = new Driver_Simple_Xpath($this->_dom);
		$this->_forms = new Driver_Simple_Forms($this->_dom, $this->_xpath);
	}

	public function forms()
	{
		return $this->_forms;
	}

	public function xpath()
	{
		return $this->_xpath;
	}

	public function dom($id = NULL)
	{
		return $id ? $this->xpath()->find($id) : $this->_dom;
	}

	public function get($uri, array $query = array())
	{
		return $this->request('GET', $uri, $query);
	}

	public function post($uri, array $query = array(), array $post = array(), array $files = array())
	{
		return $this->request('POST', $uri, $query, $post, $files);
	}

	public function request($method, $uri, array $query = array(), array $post = array(), array $files = array())
	{
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
	 * GETTERS
	 */

	public function tag_name($id)
	{
		return $this->dom($id)->tagName;
	}

	public function attribute($id, $name)
	{
		$node = $this->dom($id);

		return $node->hasAttribute($name) ? $node->getAttribute($name) : NULL;
	}

	public function html($id)
	{
		if ( ! $id)
			return $this->dom()->saveHTML();
		
		$node = $this->dom($id);

		return $node->ownerDocument->saveXml($node);
	}

	public function text($id)
	{
		$text = $this->dom($id)->textContent;
		$text = preg_replace('/([\t\n\r]|\s\s+| )/', ' ', $text);
		
		return trim($text);
	}

	public function value($id)
	{
		return $this->forms()->get_value($id);
	}

	public function is_visible($id)
	{
		$node = $this->dom($id);

		$hidden_nodes = $this->xpath()->query("./ancestor-or-self::*[contains(@style, 'display:none') or contains(@style, 'display: none') or name()='script' or name()='head']", $node);
		return $hidden_nodes->length == 0;
	}

	public function is_selected($id)
	{
		return (bool) $this->dom($id)->getAttribute('selected');
	}

	public function is_checked($id)
	{
		return (bool) $this->dom($id)->getAttribute('checked');
	}

	public function set($id, $value)
	{
		$this->forms()->set_value($id, $value);
	}

	public function select_option($id, $value)
	{
		$node = $this->forms()->set_value($id, $value);
	}

	public function serialize_form($id)
	{
		return $this->forms()->serialize_form($id);
	}

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

			$post = $this->forms()->serialize_form($form);

			$files = $this->forms()->serialize_files($form);

			if (in_array($node->tagName, array('button', 'input')) AND $node->hasAttribute('name'))
			{
				$post = $post.'&'.$node->getAttribute('name').'='.$node->getAttribute('value');
			}
			parse_str($post, $post);
			parse_str($files, $files);

			$this->post($action, array(), $post, $files);
		}
		else
		{
			throw new Exception_Driver('The html tag :tag cannot be clicked', array(':tag' => $node->tagName));
		}
	}

	public function visit($uri, array $query = array())
	{
		return $this->get($uri, $query);
	}

	public function current_path()
	{
		return $this->request_factory()->current_path();
	}

	public function current_url()
	{
		return $this->request_factory()->current_url();
	}

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

	public function is_page_active()
	{
		return (bool) $this->content();
	}

	public function user_agent()
	{
		return $this->request_factory()->user_agent();
	}

	public function cookie($name, $value, array $parameters = array())
	{
		$_COOKIE[$name] = $value;
	}

}
