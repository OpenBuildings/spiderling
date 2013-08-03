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

	public $name = 'phantomjs';
	public $_next_query = array();

	protected $_user_agent;
	protected $_connection;
	protected $_base_url = '';
	
	public function base_url($base_url = NULL)
	{
		if ($base_url !== NULL)
		{
			$this->_base_url = $base_url;
			return $this;
		}
		return $this->_base_url;
	}
	
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

	public function __destruct()
	{
		if ($this->_connection AND $this->_connection->is_started()) 
		{
			$this->_connection->stop();
		}
	}

	public function clear()
	{
		$this->connection()->delete('cookies');
	}

	public function content($content = NULL)
	{
		return $this->connection()->get('source');
	}

	/**
	 * GETTERS
	 */
	
	public function tag_name($id)
	{
		return $this->connection()->get("element/$id/name");
	}

	public function attribute($id, $name)
	{
		return $this->connection()->get("element/$id/attribute/$name");	
	}

	public function html($id)
	{
		if ($id === NULL)
			return $this->content();

		return $this->connection()->get("element/$id/html");
	}

	public function text($id)
	{
		return $this->connection()->get("element/$id/text");	
	}

	public function value($id)
	{
		return $this->connection()->get("element/$id/value");	
	}

	public function is_visible($id)
	{
		return $this->connection()->get("element/$id/visible");	
	}

	public function is_selected($id)
	{
		return $this->connection()->get("element/$id/selected");	
	}

	public function is_checked($id)
	{
		return $this->connection()->get("element/$id/checked");	
	}
	
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

	public function select_option($id, $value)
	{
		$this->connection()->post("element/$id/selected", array('value' => $value));
	}

	public function click($id)
	{
		$this->connection()->post("element/$id/click", array());
	}

	public function visit($uri, array $query = NULL)
	{
		$query = array_merge((array) $this->_next_query, (array) $query);

		$this->_next_query = NULL;
		$url = $this->base_url().$uri.($query ? '?'.http_build_query($query) : '');

		$this->connection()->post('url', array('value' => $url));
	}

	public function current_path()
	{
		$url = parse_url($this->connection()->get('url'));

		return $url['path'].(isset($url['query']) ? '?'.$url['query'] : '');
	}

	public function current_url()
	{
		return urldecode($this->connection()->get('url'));
	}

	public function all($xpath, $parent = NULL)
	{
		return $this->connection()->post(($parent === NULL ? '' : 'element/'.$parent.'/').'elements', array('value' => '.'.$xpath));
	}

	public function next_query(array $query)
	{
		$this->_next_query = $query;
	}

	public function is_page_active()
	{
		return (bool) $this->_connection;
	}

	public function javascript_errors()
	{
		return $this->connection()->get('errors', array());
	}

	public function javascript_messages()
	{
		return $this->connection()->get('messages', array());
	}

	public function screenshot($file)
	{
		$this->connection()->post('screenshot', array('value' => $file));
	}

	public function user_agent()
	{
		if ( ! $this->_user_agent)
		{
			$settings = $this->connection()->get('settings');
			$this->_user_agent = isset($settings['userAgent']) ? $settings['userAgent'] : NULL;
		}

		return $this->_user_agent;
	}

	public function execute($id, $script)
	{
		return $this->connection()->post('execute', array(
			'value' => $script,
			'id' => $id,
		));
	}

	public function cookies()
	{
		return $this->connection()->get('cookies');
	}

	public function cookie($name, $value, array $parameters = array())
	{
		return $this->connection()->post('cookie', array(
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
