<?php

declare(strict_types=1);

/**
 * Plugin Name: Ymir object cache drop-in
 * Plugin URI: https://ymirapp.com
 * Description: Connects your WordPress object-cache to the cache server managed by the Ymir platform.
 * Author: Carl Alexander
 * Author URI: https://ymirapp.com
 */

$objectCacheApiPaths = array_filter(array_map(function (string $filePath) {
    return dirname($filePath).'/object-cache-api.php';
}, (array) glob(WP_CONTENT_DIR.'/plugins/*/ymir.php')), function (string $filePath) {
    return is_readable($filePath);
});

if (!empty($objectCacheApiPaths)) {
    require_once reset($objectCacheApiPaths);
}
