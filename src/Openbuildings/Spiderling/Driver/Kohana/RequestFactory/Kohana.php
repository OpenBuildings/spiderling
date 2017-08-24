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
	protected $_previous_url;

	public function __construct()
	{
		$this->user_agent('Spiderling Kohana Driver');
	}

	public function max_redirects($max_redirects = NULL)
	{
		if ($max_redirects !== NULL)
		{
			$this->_max_redirects = (int) $max_redirects;
			return $this;
		}
		return $this->_max_redirects;
	}

	/**
	 * Getter / Setter for the user agent, used when performing the requests
	 * @param  string $user_agent
	 * @return string|Driver_Simple_RequestFactory_HTTP
	 */
	public function user_agent($user_agent = NULL)
	{
		if ($user_agent !== NULL)
		{
			\Request::$user_agent = $user_agent;
			return $this;
		}
		return \Request::$user_agent;
	}

	public function current_url()
	{
		return \URL::site($this->current_path(), TRUE);
	}

	public function previous_url()
	{
		return $this->_previous_url;
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

		$this->_request = new \Request($url);
		$this->_request
			->method($method)
			->post($post)
			->body(http_build_query($post));

		if ($this->_previous_url) {
			$this->_request->referrer($this->_previous_url);
		}

		$this->_previous_url = $this->current_url().\URL::query($this->_request->query(), FALSE);

		\Request::$initial = $this->_request;

		$this->_response = $this->_request->execute();

		while (($this->_response->status() >= 300 AND $this->_response->status() < 400))
		{
			$redirects_count++;

			if ($redirects_count >= $this->max_redirects())
				throw new Exception_Toomanyredirects(
					'Maximum Number of redirects (:max_redirects) for url :url',
					array(
						':url' => $url,
						':max_redirects' => $this->max_redirects(),
					)
				);

			$url_parts = parse_url($this->_response->headers('location'));

			$query = isset($url_parts['query']) ? $url_parts['query'] : '';
			parse_str($query, $query);

			$_GET = $query;

			$url = $url_parts['path'];

			$this->_request = new \Request($url);
			$this->_request->query($query);

			\Request::$initial = $this->_request;

			$this->_response = $this->_request->execute();
		}

		return $this->_response->body();
	}
}
