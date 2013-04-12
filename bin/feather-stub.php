<?php
/**
 * This file is only intended to be used as the stub for the phar file.
 */

require_once __DIR__ . '/../vendor/autoload.php';

define('FEATHER_VERSION', 'DEV');

use Zroger\Feather\Application;

$app = new Application(FEATHER_VERSION);
$app->run();
