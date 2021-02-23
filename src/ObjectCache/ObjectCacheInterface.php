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

namespace Ymir\Plugin\ObjectCache;

/**
 * A WordPress Object Cache.
 */
interface ObjectCacheInterface
{
    /**
     * Adds a value to the cache, if there's no value stored for the given key.
     *
     * @see https://developer.wordpress.org/reference/classes/wp_object_cache/add
     */
    public function add(string $group, string $key, $value, int $expire = 0): bool;

    /**
     * Adds a group or set of groups to the list of global groups.
     *
     * @see https://developer.wordpress.org/reference/classes/wp_object_cache/add_global_groups
     */
    public function addGlobalGroups(array $groups);

    /**
     * Adds a group or set of groups to the list of non-persistent groups.
     */
    public function addNonPersistentGroups(array $groups);

    /**
     * Closes the cache.
     */
    public function close(): bool;

    /**
     * Decrements numeric cache item's value.
     *
     * @see https://developer.wordpress.org/reference/classes/wp_object_cache/decr
     */
    public function decrement(string $group, string $key, int $offset = 1);

    /**
     * Removes the cache contents matching key and group.
     *
     * @see https://developer.wordpress.org/reference/classes/wp_object_cache/delete
     */
    public function delete(string $group, string $key): bool;

    /**
     * Removes all cache items.
     *
     * @see https://developer.wordpress.org/reference/classes/wp_object_cache/flush
     */
    public function flush(): bool;

    /**
     * Retrieves the cache contents from the cache by key and group.
     *
     * @see https://developer.wordpress.org/reference/classes/wp_object_cache/get
     */
    public function get(string $group, string $key, bool $force = false, &$found = null);

    /**
     * Retrieves multiple values from the cache in one call.
     *
     * @see https://developer.wordpress.org/reference/classes/wp_object_cache/get_multiple
     */
    public function getMultiple(string $group, array $keys, bool $force = false): array; // DO WE REALLY NEED THIS? Merge with get

    /**
     * Increment numeric cache item's value.
     *
     * @see https://developer.wordpress.org/reference/classes/wp_object_cache/incr
     */
    public function increment(string $group, string $key, int $offset = 1);

    /**
     * Replaces the contents of the cache with new data.
     *
     * @see https://developer.wordpress.org/reference/classes/wp_object_cache/replace
     */
    public function replace(string $group, string $key, $value, int $expire = 0): bool;

    /**
     * Saves the value to the cache.
     *
     * @see https://developer.wordpress.org/reference/classes/wp_object_cache/set
     */
    public function set(string $group, string $key, $value, int $expire = 0): bool;

    /**
     * Switches the internal blog ID.
     *
     * @see https://developer.wordpress.org/reference/classes/wp_object_cache/switch_to_blog
     */
    public function switchToBlog(int $blogId);
}
