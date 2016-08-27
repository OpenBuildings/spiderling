<?php

namespace Openbuildings\Spiderling;

/**
 * Load urls using Curl
 *
 * @package    Openbuildings\Spiderling
 * @author     Ivan Kerin
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Driver_Simple_RequestFactory_HTTP implements Driver_Simple_RequestFactory
{
	/**
	 * The user agent to be used when performing the requests
	 * @var string
	 */
	protected $_user_agent = 'Spiderling Simple Driver';

	/**
	 * The last visited url address
	 * @var string
	 */
	protected $_current_url;

	/**
	 * Getter / Setter for the user agent, used when performing the requests
	 * @param  string $user_agent
	 * @return string|Driver_Simple_RequestFactory_HTTP
	 */
	public function user_agent($user_agent = NULL)
	{
		if ($user_agent !== NULL)
		{
			$this->_user_agent = $user_agent;
			return $this;
		}
		return $this->_user_agent;
	}

	/**
	 * Get the url of the last request
	 * @return string
	 */
	public function current_url()
	{
		return $this->_current_url;
	}

	/**
	 * Get the path (no protocol or host) of the last request
	 * @return [type] [description]
	 */
	public function current_path()
	{
		$url = parse_url($this->current_url());

		return $url['path'].(isset($url['query']) ? '?'.$url['query'] : '');
	}

	/**
	 * Perform the request, follow redirects, return the response
	 * @param  string $method
	 * @param  string $url
	 * @param  array $post
	 * @return string
	 */
	public function execute($method, $url, array $post = array())
	{
		$curl = curl_init($url);

		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($curl, CURLOPT_USERAGENT, $this->user_agent());

		if ($post)
		{
			curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
		}

		$response = curl_exec($curl);

		if ($response === FALSE OR curl_getinfo($curl, CURLINFO_HTTP_CODE) !== 200)
		{
			throw new Exception_Curl('Curl: Download Error: :error, status :status on url :url', array(':url' => $url, ':status' => curl_getinfo($curl, CURLINFO_HTTP_CODE), ':error' => curl_error($curl)));
		}

		$this->_current_url = urldecode(curl_getinfo($curl, CURLINFO_EFFECTIVE_URL));

		return $response;
	}
}
