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

require_once __DIR__.'/bootstrap.php';

use Ymir\Plugin\ObjectCache\ObjectCacheInterface;
use Ymir\Plugin\ObjectCache\PersistentObjectCacheInterface;

/**
 * Ymir object cache API.
 *
 * @link https://developer.wordpress.org/reference/classes/wp_object_cache
 */

/**
 * Adds data to the cache, if the cache key doesn't already exist.
 *
 * @link https://developer.wordpress.org/reference/functions/wp_cache_add
 */
function wp_cache_add($key, $data, $group = '', $expire = 0): bool
{
    global $wp_object_cache;

    return $wp_object_cache->add(trim((string) $group) ?: 'default', (string) $key, $data, (int) $expire);
}

/**
 * Adds a group or set of groups to the list of global groups.
 *
 * @link https://developer.wordpress.org/reference/functions/wp_cache_add_global_groups
 */
function wp_cache_add_global_groups($groups)
{
    global $wp_object_cache;

    $wp_object_cache->addGlobalGroups((array) $groups);
}

/**
 * Adds a group or set of groups to the list of non-persistent groups.
 *
 * @link https://developer.wordpress.org/reference/functions/wp_cache_add_non_persistent_groups
 */
function wp_cache_add_non_persistent_groups($groups)
{
    global $wp_object_cache;

    $wp_object_cache->addNonPersistentGroups((array) $groups);
}

/**
 * Closes the cache.
 *
 * @link https://developer.wordpress.org/reference/functions/wp_cache_close
 */
function wp_cache_close(): bool
{
    global $wp_object_cache;

    return $wp_object_cache->close();
}

/**
 * Decrements numeric cache item's value.
 *
 * @link https://developer.wordpress.org/reference/functions/wp_cache_decr
 */
function wp_cache_decr($key, $offset = 1, $group = '')
{
    global $wp_object_cache;

    return $wp_object_cache->decrement(trim((string) $group) ?: 'default', (string) $key, (int) $offset);
}

/**
 * Removes the cache contents matching key and group.
 *
 * @link https://developer.wordpress.org/reference/functions/wp_cache_delete
 */
function wp_cache_delete($key, $group = ''): bool
{
    global $wp_object_cache;

    return $wp_object_cache->delete(trim((string) $group) ?: 'default', (string) $key);
}

/**
 * Removes all cache items.
 *
 * @link https://developer.wordpress.org/reference/functions/wp_cache_flush
 */
function wp_cache_flush(): bool
{
    global $wp_object_cache;

    return $wp_object_cache->flush();
}

/**
 * Retrieves the cache contents from the cache by key and group.
 *
 * @link https://developer.wordpress.org/reference/functions/wp_cache_get
 */
function wp_cache_get($key, $group = '', $force = false, &$found = null)
{
    global $wp_object_cache;

    return $wp_object_cache->get(trim((string) $group) ?: 'default', (string) $key, (bool) $force, $found);
}

/**
 * Retrieves multiple values from the cache in one call.
 *
 * @link https://developer.wordpress.org/reference/functions/wp_cache_get_multiple
 */
function wp_cache_get_multiple($keys, $group = '', $force = false): array
{
    global $wp_object_cache;

    return $wp_object_cache->getMultiple(trim((string) $group) ?: 'default', (array) $keys, (bool) $force);
}

/**
 * Increment numeric cache item's value.
 *
 * @link https://developer.wordpress.org/reference/functions/wp_cache_incr
 */
function wp_cache_incr($key, $offset = 1, $group = '')
{
    global $wp_object_cache;

    return $wp_object_cache->increment(trim((string) $group) ?: 'default', (string) $key, (int) $offset);
}

/**
 * Sets up Object Cache Global and assigns it.
 *
 * @link https://developer.wordpress.org/reference/functions/wp_cache_init
 */
function wp_cache_init()
{
    global $wp_object_cache, $ymir;

    try {
        $objectCache = $ymir->getContainer()->get('ymir_object_cache');

        if (!$objectCache instanceof ObjectCacheInterface) {
            throw new RuntimeException('Object cache needs to implement ObjectCacheInterface');
        } elseif ($objectCache instanceof PersistentObjectCacheInterface && !$objectCache->isAvailable()) {
            throw new RuntimeException('Persistent object cache is unavailable');
        }

        $wp_object_cache = $objectCache;
    } catch (Exception $exception) {
        $wp_object_cache = $ymir->getContainer()->get('ymir_wordpress_object_cache');
    }

}

/**
 * Replaces the contents of the cache with new data.
 *
 * @link https://developer.wordpress.org/reference/functions/wp_cache_replace
 */
function wp_cache_replace($key, $data, $group = '', $expire = 0): bool
{
    global $wp_object_cache;

    return $wp_object_cache->replace(trim((string) $group) ?: 'default', (string) $key, $data, (int) $expire);
}

/**
 * Reset internal cache keys and structures.
 *
 * If the cache back end uses global blog or site IDs as part of its cache keys,
 * this function instructs the back end to reset those keys and perform any cleanup
 * since blog or site IDs have changed since cache init.
 *
 * This function is deprecated. Use wp_cache_switch_to_blog() instead of this
 * function when preparing the cache for a blog switch. For clearing the cache
 * during unit tests, consider using wp_cache_init(). wp_cache_init() is not
 * recommended outside of unit tests as the performance penalty for using it is
 * high.
 *
 * @link https://developer.wordpress.org/reference/functions/wp_cache_reset
 */
function wp_cache_reset() {
    _deprecated_function(__FUNCTION__, '3.5.0', 'WP_Object_Cache::reset()');
}

/**
 * Saves the data to the cache.
 *
 * Differs from wp_cache_add() and wp_cache_replace() in that it will always write data.
 *
 * @link https://developer.wordpress.org/reference/functions/wp_cache_save
 */
function wp_cache_set($key, $data, $group = '', $expire = 0): bool
{
    global $wp_object_cache;

    return $wp_object_cache->set(trim((string) $group) ?: 'default', (string) $key, $data, (int) $expire);
}

/**
 * Switches the internal blog ID.
 *
 * This changes the blog id used to create keys in blog specific groups.
 *
 * @link https://developer.wordpress.org/reference/functions/wp_cache_switch_to_blog
 */
function wp_cache_switch_to_blog($blogId)
{
    global $wp_object_cache;

    $wp_object_cache->switchToBlog((int) $blogId);
}
