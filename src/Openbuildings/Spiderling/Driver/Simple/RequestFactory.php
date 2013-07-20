<?php

namespace Openbuildings\Spiderling;

/**
 * Xpath extension for native driver
 *
 * @package    Openbuildings\Spiderling
 * @author     Ivan Kerin
 * @copyright  (c) 2012-2013 OpenBuildings Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
interface Driver_Simple_RequestFactory
{
	public function current_url();

	public function current_path();

	public function user_agent();

	public function execute($method, $url, array $post = NULL);
}