<?php

namespace Openbuildings\Spiderling;

/**
 * Kohana driver, tries to use Kohana's internal Request methods to load urls.
 * Use this to very efficiently load kohana actions.
 * No Javascript
 *
 * @package    Openbuildings\Spiderling
 * @author     Ivan Kerin
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Driver_Kohana extends Driver_Simple {

	public $name = 'kohana';

	function __construct()
	{
		parent::__construct();
		$this->_request_factory = new Driver_Kohana_RequestFactory_Kohana();
	}

	/**
	 * Return a Response object of the last request operation
	 * @return Response
	 */
	public function response()
	{
		return $this->request_factory()->response();
	}
}
