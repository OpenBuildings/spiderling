<?php 

require_once __DIR__.'/../vendor/autoload.php';

define('TESTVIEWS', realpath(__DIR__.DIRECTORY_SEPARATOR.'views').DIRECTORY_SEPARATOR);

Kohana::modules(array(
	'spiderling' => __DIR__.'/..',
));

function test_autoload($class)
{
	$file = str_replace('_', '/', $class);

	if ($file = Kohana::find_file('tests/classes', $file))
	{
		require_once $file;
	}
}

spl_autoload_register('test_autoload');
