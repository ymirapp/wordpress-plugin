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

/**
 * Plugin Name: Ymir
 * Plugin URI: https://ymirapp.com
 * Description: Integrates WordPress with the Ymir platform.
 * Version: 1.17.0
 * Author: Carl Alexander
 * Author URI: https://ymirapp.com
 * License: GPL3
 */

require_once __DIR__.'/bootstrap.php';

global $ymir;

// Load "is_plugin_active" function used by the plugin
if (!function_exists('is_plugin_active')) {
    require_once ABSPATH.'wp-admin/includes/plugin.php';
}

// Load Ymir plugin
$ymir->load();

// Load Ymir pluggable functions if the plugin is active
if ($ymir->isActive()) {
    require_once __DIR__.'/pluggable.php';
}
