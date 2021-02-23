<?php

declare(strict_types=1);

/*
 * This file is part of Ymir WordPress plugin.
 *
 * (c) Carl Alexander <support@ymirapp.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (version_compare(PHP_VERSION, '7.2', '<')) {
    exit(sprintf('Ymir requires PHP 7.2 or higher. Your WordPress site is using PHP %s.', PHP_VERSION));
}

// Setup class autoloader
require_once dirname(__FILE__).'/src/Autoloader.php';
\Ymir\Plugin\Autoloader::register();

global $ymir;

$ymir = new \Ymir\Plugin\Plugin(__DIR__.'/ymir.php');
