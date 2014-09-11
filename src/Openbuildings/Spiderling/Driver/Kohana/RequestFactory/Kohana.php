<?php

namespace Openbuildings\Spiderling;

/**
 * Use kohana requests to load urls, handle redirects
 *
 * @package    Openbuildings\Spiderling
 * @author     Ivan Kerin
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
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

		return '/'.ltrim($this->_request->uri(), '/');
	}

	public function request()
	{
		return $this->_request;
	}

	public function response()
	{
		return $this->_response;
	}

	public function execute($method, $url, array $post = array())
	{
		$redirects_count = 1;

		\Request::$initial = NULL;

		$this->_request = \Request::factory($url)
			->method($method)
			->post($post)
			->body(http_build_query($post));

		\Request::$initial = $this->_request;

		$this->_response = $this->_request->execute();

		while (($this->_response->status() >= 300 AND $this->_response->status() < 400))
		{
			$redirects_count++;

			if ($redirects_count >= $this->max_redirects())
				throw new Exception_Toomanyredirects('Maximum Number of redirects (5) for url :url', array(':url' => $url));

			$url_parts = parse_url($this->_response->headers('location'));

			$query = isset($url_parts['query']) ? $url_parts['query'] : '';
			parse_str($query, $query);

			$_GET = $query;

			$url = $url_parts['path'];

			\Request::$initial = NULL;

			$this->_request = \Request::factory($url);

			\Request::$initial = $this->_request;

			$this->_response = $this->_request->execute();
		}

		return $this->_response->body();
	}
}
