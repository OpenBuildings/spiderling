<?php

namespace Openbuildings\Spiderling;

/**
 * Base class for Simple driver request handling.
 * You can easily add your own driver by implementing this interface and extending Simple Driver.
 * Checkout Kohana Driver for an example.
 *
 * @package    Openbuildings\Spiderling
 * @author     Ivan Kerin
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
interface Driver_Simple_RequestFactory
{
	public function current_url();

	public function current_path();

	public function user_agent();

	public function execute($method, $url, array $post = array());
}
