<?php

/**
 * @license MIT
 * @copyright (c) 2024 Virus3D
 */

declare(strict_types=1);

use PhpObfuscator\{Action, Config};
use Symfony\Component\Config\FileLocator;

require 'vendor/autoload.php';

$is_console = (\PHP_SAPI == 'cli');

$configDir = [__DIR__.'/config'];

$fileLocator = new FileLocator($configDir);
$resource    = $fileLocator->locate('config.yaml', null, false);
if (empty($resource))
{
    trigger_error('Config not found.', \E_USER_ERROR);
}

$config = new Config($resource[0]);

$action = new Action($config, __DIR__, $is_console);
$action->run();
