<?php

/**
 * Tests bootstrap
 *
 * @package Qis
 */

date_default_timezone_set('America/Chicago');

sprintf("PHP Version: %s\n", PHP_VERSION);

$root = realpath(dirname(dirname(__FILE__)));

$autoload = require $root . DIRECTORY_SEPARATOR . 'vendor/autoload.php';
$autoload->set('Qis', array($root . DIRECTORY_SEPARATOR . 'src'));
$autoload->set('Qi_', array($root . DIRECTORY_SEPARATOR . 'lib'));

require_once 'BaseTestCase.php';

if (!function_exists('get_called_class')) {
    include_once 'get_called_class.func.php';
}
