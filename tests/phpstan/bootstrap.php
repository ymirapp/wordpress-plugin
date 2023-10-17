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

define('WP_HOME', '/home/user');
define('WPINC', 'wp-includes');

if (!class_exists('CurlHandle')) {
    class CurlHandle
    {
    }
}

if (!class_exists('GdImage')) {
    class GdImage
    {
    }
}
