<?php

namespace Openbuildings\Spiderling;

/**
 * Connect to phantomjs service, optionally start one if not present on a new port.
 * Send requests to phantomjs
 * 
 * @package    Openbuildings\Spiderling
 * @author     Ivan Kerin
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Driver_Phantomjs_Connection {
	
	protected $_pid_file;
	protected $_pid;
	protected $_server = 'http://localhost';
	protected $_port;
	
	public function port($port = NULL)
	{
		if ($port !== NULL)
		{
			$this->_port = $port;
			return $this;
		}

		if ( ! $this->_port) 
		{
			$this->_port = Network::ephimeral_port($this->host(), 4445, 5000);
		}

		return $this->_port;
	}
	
	public function server($server = NULL)
	{
		if ($server !== NULL)
		{
			$this->_server = $server;
			return $this;
		}
		return $this->_server;
	}

	public function host()
	{
		return parse_url($this->server(), PHP_URL_HOST);
	}

	public function pid()
	{
		return $this->_pid;
	}

	public function __construct($server = NULL)
	{
		if ($server) 
		{
			$this->server($server);
		}
	}
	
	public function start($pid_file = NULL, $log_file = '/dev/null')
	{
		if ($pid_file)
		{
			$this->_pid_file = $pid_file;
			if (is_file($this->_pid_file)) 
			{
				Phantomjs::kill(file_get_contents($pid_file));
				unlink($this->_pid_file);
			}
		}

		$this->_pid = Phantomjs::start('phantom.js', $this->port(), 'phantomjs-connection.js', $log_file);

		if ($this->_pid_file)
		{
			file_put_contents($this->_pid_file, $this->_pid);
		}

		$self = $this;

		return Attempt::make(function() use ($self) {
			return $self->is_running();
		});
	}

	public function is_started()
	{
		return (bool) $this->_pid;
	}

	public function is_running()
	{
		return ! Network::is_port_open($this->host(), $this->port());
	}

	public function stop()
	{
		if ($this->is_started()) 
		{
			$this->delete('session', array());
			$this->_pid = NULL;
		}

		if ($this->_pid_file AND is_file($this->_pid_file)) 
		{
			unlink($this->_pid_file);
		}
		$self = $this;

		return Attempt::make(function() use ($self) {
			return ! $self->is_running();
		});

	}

	public function pid_file()
	{
		return $this->_pid_file;
	}

	public function get($command)
	{
		return $this->call($command);
	}

	public function post($command, array $params)
	{
		$options = array();
		$options[CURLOPT_POST] = TRUE;
		$options[CURLOPT_POSTFIELDS] = http_build_query($params);

		return $this->call($command, $options);
	}

	public function delete($command)
	{
		$options = array();
		$options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
		
		return $this->call($command, $options);	
	}

	public function command_url($command)
	{
		return rtrim($this->server(), '/').':'.$this->port().'/'.$command;
	}

	protected function call($command, array $options = array())
	{
		$curl = curl_init();
		$options[CURLOPT_URL] = $this->command_url($command);
		$options[CURLOPT_RETURNTRANSFER] = TRUE;
		$options[CURLOPT_FOLLOWLOCATION] = TRUE;

		curl_setopt_array($curl, $options);

		$raw = trim(curl_exec($curl));

		$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		$result = json_decode($raw, TRUE);

		if ($error = curl_error($curl))
			throw new Exception_Driver('Curl ":command" throws exception :error', array(':command' => $command, ':error' => $error));

		if ($code != 200)
			throw new Exception_Driver('Unexpected response from the panthomjs for :command: :code', array(':command' => $command, ':code' => $code));

		return $result;
	}
}
