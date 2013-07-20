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
	protected $_next_query = array();
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

	public function clear()
	{
		$this->connection()->delete('cookie');
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
			$this->_connection->start(array('browserName' => 'firefox', 'acceptSslCerts' => FALSE));
		}

		return $this->_connection;
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

	public function alert_text()
	{
		return $this->connection()->get("alert_text");
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
		$elements = $this->connection()->post(($parent === NULL ? '' : 'element/'.$parent.'/').'elements', array('using' => 'xpath', 'value' => '.'.$xpath));

		return array_map(function($item){ return $item['ELEMENT'];}, $elements);
	}

	public function next_query(array $query)
	{
		$this->_next_query = $query;
		return $this;
	}

	public function is_page_active()
	{
		return (bool) $this->_connection;
	}

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
		return $this;
	}

	public function screenshot($file)
	{
		$data = $this->connection()->get('screenshot');

		file_put_contents($file, base64_decode($data));
	}

	public function cookies()
	{
		return $this->connection()->get('cookie');		
	}

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