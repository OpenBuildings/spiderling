<?php

namespace Openbuildings\Spiderling;

/**
 * Func_Test Native Driver request. Uses Native Kohana Requests with a little patching to make them work with tests
 *
 * @package    Func_Test
 * @author     Ivan Kerin
 * @copyright  (c) 2012 OpenBuildings Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Driver_Kohana_RequestFactory_Kohana implements Driver_Simple_RequestFactory {

	protected $_request;
	protected $_response;
	protected $_max_redirects = 5;
	
	public function max_redirects($max_redirects = NULL)
	{
		if ($max_redirects !== NULL)
		{
			$this->_max_redirects = (int) $max_redirects;
			return $this;
		}
		return $this->_max_redirects;
	}

	public function user_agent()
	{
		return \Request::$user_agent;
	}

	public function current_url()
	{
		return \URL::site($this->current_path(), TRUE);
	}

	public function current_path()
	{
		if ( ! $this->_request)
			return NULL;

		return $this->_request->uri();
	}

	public function response()
	{
		return $this->_response;
	}

	public function execute($method, $url, array $post = array())
	{
		$redirects_count = 1;

		$this->_request = \Request::factory($url)
			->method($method)
			->post($post);

		$this->_response = $this->_request->execute();

		while (($this->_response->status() >= 300 AND $this->_response->status() < 400))
		{
			$redirects_count++;

			if ($redirects_count >= $this->max_redirects())
				throw new Exception_Toomanyredirects('Maximum Number of redirects (5) for url :url', array(':url' => $url));

			$url_parts = parse_url($this->_response->headers('location'));

			$url = $url_parts['path'].(isset($url_parts['query']) ? '?'.$url_parts['query'] : '');

			$this->_request = \Request::factory($url);
			$this->_response = $this->_request->execute();
		}

		return $this->_response->body();
	}
}
