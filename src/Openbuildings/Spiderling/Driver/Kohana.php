<?php

namespace Openbuildings\Spiderling;

/**
 * Func_Test Native driver. 
 * In memory kohana request response classes. 
 * No Javascript
 *
 * @package    Func_Test
 * @author     Ivan Kerin
 * @copyright  (c) 2012 OpenBuildings Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Driver_Kohana extends Driver_Simple {

	public $name = 'kohana';

	function __construct()
	{
		parent::__construct();
		$this->_request_factory = new Driver_Kohana_RequestFactory_Kohana();
	}
	
	public function response()
	{
		return $this->request_factory()->response();
	}
}
