<?php

/*
 * This file is part of Ymir WordPress plugin.
 *
 * (c) Carl Alexander <support@ymirapp.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*
Plugin Name: Ymir
Description: Integrates WordPress with the Ymir platform.
Author: Carl Alexander
Author URI: https://ymirapp.com
Version: 1.0.0
License: GPL3
*/

if (version_compare(PHP_VERSION, '7.1', '<')) {
    exit(sprintf('Ymir requires PHP 7.1 or higher. Your WordPress site is using PHP %s.', PHP_VERSION));
}

// Setup class autoloader
require_once dirname(__FILE__) . '/src/Autoloader.php';
\Ymir\Plugin\Autoloader::register();

// Load plugin
global $ymir;
$ymir = new \Ymir\Plugin\Plugin(__FILE__);
add_action('after_setup_theme', array($ymir, 'load'));
