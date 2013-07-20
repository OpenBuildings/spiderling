<?php

namespace Openbuildings\Spiderling;

/**
 * @package    Openbuildings\Spiderling
 * @author     Ivan Kerin
 * @copyright  (c) 2012-2013 OpenBuildings Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Driver_Simple_RequestFactory_HTTP implements Driver_Simple_RequestFactory
{
	protected $_user_agent = 'Spiderling Simple Driver';
	protected $_current_url;
	
	public function user_agent($user_agent = NULL)
	{
		if ($user_agent !== NULL)
		{
			$this->_user_agent = $user_agent;
			return $this;
		}
		return $this->_user_agent;
	}

	public function current_url()
	{
		return $this->_current_url;
	}

	public function current_path()
	{
		$url = parse_url($this->current_url());

		return $url['path'].(isset($url['query']) ? '?'.$url['query'] : '');
	}

	public function execute($method, $url, array $post = NULL)
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