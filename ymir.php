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

/*
Plugin Name: Ymir
Description: Integrates WordPress with the Ymir platform.
Author: Carl Alexander
Author URI: https://ymirapp.com
Version: 1.3.0
License: GPL3
*/

require_once __DIR__.'/bootstrap.php';

global $ymir;

// Add load plugin action
add_action('after_setup_theme', array($ymir, 'load'));

// Load Ymir pluggable functions if the plugin is active
if (!function_exists('is_plugin_active')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
if (is_plugin_active(plugin_basename(__FILE__))) {
    require_once __DIR__.'/pluggable.php';
}
