<?php

namespace Openbuildings\Spiderling;

use Openbuildings\EnvironmentBackup\Environment;
use Openbuildings\EnvironmentBackup\Environment_Group_Globals;
use Openbuildings\EnvironmentBackup\Environment_Group_Server;
use Openbuildings\EnvironmentBackup\Environment_Group_Static;

/**
 * Use Curl to load urls.
 * In memory.
 * No Javascript
 *
 * @package    Openbuildings\Spiderling
 * @author     Ivan Kerin
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Driver_SimpleXML extends Driver_Simple {

	/**
	 * The name of the driver
	 * @var string
	 */
	public $name = 'simpleXML';

	/**
	 * Initialze the dom, xpath and forms objects, based on the content string
	 */
	public function initialize()
	{
		@ $this->_dom->loadXML($this->content());
		$this->_dom->encoding = 'utf-8';
		$this->_xpath = new Driver_Simple_Xpath($this->_dom);
		$this->_forms = new Driver_Simple_Forms($this->_xpath);
	}
}
