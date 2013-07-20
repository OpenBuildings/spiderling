<?php

namespace Openbuildings\Spiderling;

/**
 * Func_Test Selenium driver. 
 *
 * @package    Functest
 * @author     Ivan Kerin
 * @copyright  (c) 2012 OpenBuildings Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Driver_Selenium extends Driver {

	public $name = 'selenium';
	public $_next_query = array();

	protected $_connection;

	public function clear()
	{
		$this->connection()->delete('cookie');
	}

	public function session_id()
	{
		return $this->connection()->session_id();
	}

	public function content($content = NULL)
	{
		return $this->connection()->get('source');
	}

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
			$this->_connection->start();
		}

		return $this->_connection;
	}

	public function javascript_errors()
	{
		return $this->connection()->post('execute', array('script' => "return window.JSErrorCollector_errors ? window.JSErrorCollector_errors.pump() : [];", 'args' => array()));
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
		if ( ! $id)
			return $this->content();

		return $this->execute($id, 'return arguments[0].outerHTML');
	}

	public function execute($id, $script)
	{
		return $this->connection()->post('execute', array(
			'script' => $script,
			'args' => $id ? array(array('ELEMENT' => $id)) : array()
		));
	}

	public function text($id)
	{
		$text = $this->connection()->get("element/$id/text");
		$text = preg_replace('/[\t\n\r]/', ' ', $text);
		$text = preg_replace('/\s\s+/', ' ', $text);
		return trim($text);
	}

	public function value($id)
	{
		return $this->connection()->get("element/$id/value");	
	}

	public function is_visible($id)
	{
		return $this->connection()->get("element/$id/displayed");	
	}

	public function is_selected($id)
	{
		return $this->connection()->get("element/$id/selected");	
	}

	public function is_checked($id)
	{
		return $this->connection()->get("element/$id/selected");	
	}

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
				if ($this->is_visible($id))
				{
					$this->connection()->post("element/$id/click", array());
				}
				else
				{
					$this->execute($id, 'arguments[0].checked = true;');
				}
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

	public function append($id, $value)
	{
		$this->connection()->post("element/$id/value", array('value' => str_split($value)));	
	}

	public function select_option($id, $value)
	{
		$this->connection()->post("element/$id/click", array());
	}

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

	public function click($id)
	{
		$this->connection()->post("element/$id/click", array());
	}

	public function visit($uri, array $query = NULL)
	{
		$query = Arr::merge((array) $this->_next_query, (array) $query);

		$this->_next_query = NULL;
		$url = URL::site($uri, 'http').URL::query($query, FALSE);

		$this->connection()->post('url', array('url' => $url));
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
		try 
		{
			$elements = $this->connection()->post(($parent === NULL ? '' : 'element/'.$parent.'/').'elements', array('using' => 'xpath', 'value' => '.'.$xpath));
		} 
		catch (Exception_Selenium $exception) 
		{
			if ($exception->error() == 'NoSuchElement')
			{
				return array();
			}
			else
			{
				throw $exception;
			}
		}

		return Arr::pluck($elements, 'ELEMENT');
	}

	public function next_query(array $query)
	{
		$this->_next_query = $query;
		return $this;
	}

	public function is_page_active()
	{
		return (bool) $this->connection()->session_id();
	}

	public function move_to($id = NULL, $x = NULL, $y = NULL)
	{
		$this->connection()->post('moveto', array_filter(array(
			'element' => $id,
			'xoffset' => $x,
			'yoffset' => $y
		), function($param)
		{
			return $param OR $param === 0;
		}));
		return $this;
	}

	public function screenshot($file)
	{
		$data = $this->connection()->get('screenshot');

		file_put_contents($file, base64_decode($data));
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
