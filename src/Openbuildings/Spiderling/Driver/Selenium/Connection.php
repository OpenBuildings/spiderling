<?php

namespace Openbuildings\Spiderling;

/**
 *
 * @package    Functest
 * @author     Ivan Kerin
 * @copyright  (c) 2012 OpenBuildings Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Driver_Selenium_Connection
{
	protected $_url;
	protected $_session_id;
	protected $_curl;

	protected $_server = 'http://localhost:4444/wd/hub/';
	
	public function server($server = NULL)
	{
		if ($server !== NULL)
		{
			$this->_server = $server;
			return $this;
		}
		return $this->_server;
	}

	public function __construct($server = NULL)
	{
		if ($server) 
		{
			$this->server($server);
		}
	}

	public function start(array $desiredCapabilities = NULL)
	{
		if ( ! ($this->_session_id = $this->reuse_session())
		{
			$this->_session_id = $this->new_session($desiredCapabilities);
		}

		$this->_server .= "session/{$this->_session_id}/";
	}

	public function reuse_session()
	{
		$sessions = $this->get('sessions');
		foreach ($sessions as $session) 
		{
			$id = Arr::get($session, 'id');
			try
			{
				$this->get("session/$id/window_handle");
				return $id;
			}
			catch (Exception_Driver $exception)
			{
				$this->delete("session/$id");
			}
		}
	}

	public function new_session(array $desiredCapabilities = NULL)
	{
		if ( ! $desiredCapabilities) 
		{
			$desiredCapabilities = array('browserName' => 'firefox');
		}

		$session = $this->post('session', array('desiredCapabilities' => $desiredCapabilities));
		return $session['webdriver.remote.sessionid'];
	}

	public function session_id()
	{
		return (bool) $this->_session_id;
	}

	public function get($command)
	{
		return $this->call($command);
	}

	public function post($command, array $params)
	{
		$options = array();
		$options[CURLOPT_POST] = TRUE;
		$options[CURLOPT_POSTFIELDS] = json_encode($params);

		return $this->call($command, $options);	
	}

	public function delete($command)
	{
		$options = array();
		$options[CURLOPT_CUSTOMREQUEST] = Request::DELETE;
		
		return $this->call($command, $options);	
	}

	public function call($command, array $options = array())
	{
		$curl = curl_init();
		$options[CURLOPT_URL] = $this->_url.$command;
		$options[CURLOPT_RETURNTRANSFER] = TRUE;
		$options[CURLOPT_FOLLOWLOCATION] = TRUE;
		$options[CURLOPT_HTTPHEADER] = array(
			'Content-Type: application/json;charset=UTF-8',
			'Accept: application/json',
		);

		curl_setopt_array($curl, $options);
		
		$raw = trim(curl_exec($curl));

		$result = json_decode($raw, TRUE);

		if ($error = curl_error($curl))
			throw new Exception_Driver('Curl ":command" throws exception :error', array(':command' => $command, ':error' => $error));

		if ($result['status'] != 0)
			throw new Exception_Driver($result['status']);
		
		return $result['value'];
	}
}