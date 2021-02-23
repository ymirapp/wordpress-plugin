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

$objectCacheApiPaths = array_filter(array_map(function (string $filePath) {
    return dirname($filePath).'/object-cache-api.php';
}, (array) glob(WP_CONTENT_DIR.'/plugins/*/ymir.php')), function (string $filePath) {
    return is_readable($filePath);
});

if (!empty($objectCacheApiPaths)) {
    require_once reset($objectCacheApiPaths);
}
