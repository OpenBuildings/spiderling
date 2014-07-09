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

	/**
	 * The file storing the pid of the current phantomjs server process
	 * @var string
	 */
	protected $_pid_file;

	/**
	 * The pid of the current phantomjs server process
	 * @var string
	 */
	protected $_pid;

	/**
	 * Ulr of the phantomjs server
	 * @var string
	 */
	protected $_server = 'http://localhost';

	/**
	 * Port of the phantomjs server
	 * @var string
	 */
	protected $_port;

	/**
	 * Getter / Setter of the phantomjs server port.
	 * If none is set it tries to fine an unused port between 4445 and 5000
	 * @param  string $port
	 * @return string|Driver_Phantomjs_Connection
	 */
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

	/**
	 * Getter / Setter of the current phantomjs server url
	 * @param  string $server
	 * @return string|Driver_Phantomjs_Connection
	 */
	public function server($server = NULL)
	{
		if ($server !== NULL)
		{
			$this->_server = $server;
			return $this;
		}
		return $this->_server;
	}

	/**
	 * Get the host of the current phantomjs server (without the protocol part)
	 * @return string
	 */
	public function host()
	{
		return parse_url($this->server(), PHP_URL_HOST);
	}

	/**
	 * Getter, get the current phantomjs server process pid
	 * @return string
	 */
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

	/**
	 * Start a new phantomjs server, optionally provide pid_file and log file.
	 * If you provide a pid_file, it will kill the process currently running on that pid, before starting the new one
	 * If the start is unsuccessfull it will return FALSE
	 * @param  string $pid_file
	 * @param  string $log_file
	 * @return boolean
	 */
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

	/**
	 * Check if the phantomjs server has been started
	 * @return boolean
	 */
	public function is_started()
	{
		return (bool) $this->_pid;
	}

	/**
	 * Check if the phantomjs server is actually running (the port is taken)
	 * @return boolean
	 */
	public function is_running()
	{
		return ! Network::is_port_open($this->host(), $this->port());
	}

	/**
	 * Gracefully stop the phantomjs server. Return FALSE on failure. Clear the pid_file if set
	 * @return boolean
	 */
	public function stop()
	{
		if ($this->is_started())
		{
			$this->delete('session');
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

	/**
	 * Getter - get the current pid_file
	 * @return string
	 */
	public function pid_file()
	{
		return $this->_pid_file;
	}

	/**
	 * Perform a get request on the phantomjs server
	 * @param  string $command
	 * @return mixed
	 */
	public function get($command)
	{
		return $this->call($command);
	}

	/**
	 * Perform a post request on the phantomjs server
	 * @param  string $command
	 * @param  array  $params
	 * @return mixed
	 */
	public function post($command, array $params)
	{
		$options = array();
		$options[CURLOPT_POST] = TRUE;
		$options[CURLOPT_POSTFIELDS] = http_build_query($params);

		return $this->call($command, $options);
	}

	/**
	 * Perform a delete request on the phantomjs server
	 * @param  string $command
	 * @return mixed
	 */
	public function delete($command)
	{
		$options = array();
		$options[CURLOPT_CUSTOMREQUEST] = 'DELETE';

		return $this->call($command, $options);
	}

	/**
	 * Get the full url of a command (including server and port)
	 * @param  string $command
	 * @return string
	 */
	public function command_url($command)
	{
		return rtrim($this->server(), '/').':'.$this->port().'/'.$command;
	}

	/**
	 * Perform a custom request on the phantomjs server, using curl
	 * @param  string $command
	 * @param  array  $options
	 * @return mixed
	 */
	protected function call($command, array $options = array())
	{
		$curl = curl_init();
		$options[CURLOPT_URL] = $this->command_url($command);
		$options[CURLOPT_RETURNTRANSFER] = TRUE;
		$options[CURLOPT_FOLLOWLOCATION] = TRUE;

		curl_setopt_array($curl, $options);

		$raw = '';

		Attempt::make(function() use ($curl, & $raw) {
			$raw = trim(curl_exec($curl));
			return curl_getinfo($curl, CURLINFO_HTTP_CODE) == 200;
		});

		$error = curl_error($curl);
		$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		curl_close($curl);

		if ($error)
			throw new Exception_Driver('Curl ":command" throws exception :error', array(':command' => $command, ':error' => $error));

		if ($code != 200)
			throw new Exception_Driver('Unexpected response from the panthomjs for :command: :code', array(':command' => $command, ':code' => $code));

		$result = json_decode($raw, TRUE);

		return $result;
	}
}
