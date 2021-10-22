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

use Symfony\Component\Dotenv\Dotenv;

require_once __DIR__.'/../vendor/autoload.php';

$dotEnvFilePath = __DIR__.'/../.env';

if (file_exists($dotEnvFilePath)) {
    (new Dotenv())->load($dotEnvFilePath);
}

/**
 * PHPUnit bootstrap file for Ymir WordPress plugin.
 */
$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

$_core_dir = getenv('WP_CORE_DIR');
if (!$_core_dir) {
    $_core_dir = '/tmp/wordpress';
}

require_once __DIR__.'/constants.php';
require_once __DIR__.'/functions.php';

DG\BypassFinals::enable();

require_once $_core_dir.'/wp-admin/includes/class-wp-site-icon.php';
require_once $_core_dir.'/wp-includes/class-phpmailer.php';
require_once $_core_dir.'/wp-includes/class-wp-error.php';
require_once $_core_dir.'/wp-includes/class-wp-image-editor.php';
require_once $_core_dir.'/wp-includes/class-wp-post.php';
require_once $_core_dir.'/wp-includes/class-wp-site.php';
require_once $_core_dir.'/wp-includes/class-wp-site-query.php';
require_once $_core_dir.'/wp-includes/class.wp-dependencies.php';
require_once $_core_dir.'/wp-includes/class.wp-scripts.php';
require_once $_core_dir.'/wp-includes/load.php';
require_once $_core_dir.'/wp-includes/rest-api/class-wp-rest-request.php';
