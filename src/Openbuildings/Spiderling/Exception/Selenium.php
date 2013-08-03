<?php

namespace Openbuildings\Spiderling;

/**
 * @package    Openbuildings\Spiderling
 * @author     Ivan Kerin
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Exception_Selenium extends Exception {

	protected static $names = array(
		6 => 'NoSuchDriver',
		7 => 'NoSuchElement',
		8 => 'NoSuchFrame',
		9 => 'UnknownCommand',
		10 => 'StaleElementReference',
		11 => 'ElementNotVisible',
		12 => 'InvalidElementState',
		13 => 'UnknownError',
		15 => 'ElementIsNotSelectable',
		17 => 'JavaScriptError',
		19 => 'XPathLookupError',
		21 => 'Timeout',
		23 => 'NoSuchWindow',
		24 => 'InvalidCookieDomain',
		25 => 'UnableToSetCookie',
		26 => 'UnexpectedAlertOpen',
		27 => 'NoAlertOpenError',
		28 => 'ScriptTimeout',
		29 => 'InvalidElementCoordinates',
		30 => 'IMENotAvailable',
		31 => 'IMEEngineActivationFailed',
		32 => 'InvalidSelector',
		33 => 'SessionNotCreatedException',
		34 => 'MoveTargetOutOfBounds',
	);

	protected static $messages = array(
		6 => 'A session is either terminated or not started',
		7 => 'An element could not be located on the page using the given search parameters.',
		8 => 'A request to switch to a frame could not be satisfied because the frame could not be found.',
		9 => 'The requested resource could not be found, or a request was received using an HTTP method that is not supported by the mapped resource.',
		10 => 'An element command failed because the referenced element is no longer attached to the DOM.',
		11 => 'An element command could not be completed because the element is not visible on the page.',
		12 => 'An element command could not be completed because the element is in an invalid state (e.g. attempting to click a disabled element).',
		13 => 'An unknown server-side error occurred while processing the command.',
		15 => 'An attempt was made to select an element that cannot be selected.',
		17 => 'An error occurred while executing user supplied JavaScript.',
		19 => 'An error occurred while searching for an element by XPath.',
		21 => 'An operation did not complete before its timeout expired.',
		23 => 'A request to switch to a different window could not be satisfied because the window could not be found.',
		24 => 'An illegal attempt was made to set a cookie under a different domain than the current page.',
		25 => 'A request to set a cookie\'s value could not be satisfied.',
		26 => 'A modal dialog was open, blocking this operation',
		27 => 'An attempt was made to operate on a modal dialog when one was not open.',
		28 => 'A script did not complete before its timeout expired.',
		29 => 'The coordinates provided to an interactions operation are invalid.',
		30 => 'IME was not available.',
		31 => 'An IME engine could not be started.',
		32 => 'Argument was an invalid selector (e.g. XPath/CSS).',
		33 => 'A new session could not be created.',
		34 => 'Target provided for a move action is out of bounds.',
	);

	public $name;

	public function __construct($code, \Exception $previous = NULL)
	{
		$message = isset(self::$messages[$code]) ? self::$messages[$code] : 'Unknown Error';
		$this->name = isset(self::$names[$code]) ? self::$names[$code] : 'UnknownError';

		parent::__construct($this->name.': '.$message, array(), $previous);

		$this->code = $code;
	}
}
