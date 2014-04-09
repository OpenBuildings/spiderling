<?php

namespace Openbuildings\Spiderling;

/**
 * Page - represents HTML Page
 *
 * @package    Openbuildings\Spiderling
 * @author     Ivan Kerin
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Page extends Node {

	function __construct(Driver $driver = NULL, $extension = NULL)
	{
		$this->_driver = $driver ?: new Driver_Simple;

		if ($extension)
		{
			$this->_extension = $extension;
		}
	}

	/**
	 * Initiate a visit with the currently selected driver
	 * @param  string $uri
	 * @param  array  $query
	 * @return $this
	 */
	public function visit($uri, array $query = array())
	{
		$this->driver()->visit($uri, $query);

		return $this;
	}

	/**
	 * Return the content of the last request from the currently selected driver
	 * @return string
	 */
	public function content()
	{
		return $this->driver()->content();
	}

	/**
	 * Return the current browser url without the domain
	 * @return string
	 */
	public function current_path()
	{
		return $this->driver()->current_path();
	}

	/**
	 * Return the current url
	 * @return string
	 */
	public function current_url()
	{
		return $this->driver()->current_url();
	}
}
