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
 * A wrapper for the built-in WordPress object cache.
 */
class WordPressObjectCache implements ObjectCacheInterface
{
    /**
     * The built-in WordPress object cache.
     *
     * @var \WP_Object_Cache
     */
    private $cache;

    /**
     * Constructor.
     */
    public function __construct(\WP_Object_Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function add(string $group, string $key, $value, int $expire = 0): bool
    {
        return $this->cache->add($key, $value, $group, $expire);
    }

    /**
     * {@inheritdoc}
     */
    public function addGlobalGroups(array $groups)
    {
        $this->cache->add_global_groups($groups);
    }

    /**
     * {@inheritdoc}
     */
    public function addNonPersistentGroups(array $groups)
    {
        // Built-in WordPress object cache isn't persistent.
    }

    /**
     * {@inheritdoc}
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function decrement(string $group, string $key, int $offset = 1)
    {
        return $this->cache->decr($key, $offset, $group);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $group, string $key): bool
    {
        return $this->cache->delete($key, $group);
    }

    /**
     * {@inheritdoc}
     */
    public function flush(): bool
    {
        return $this->cache->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $group, string $key, bool $force = false, &$found = null)
    {
        return $this->cache->get($key, $group, $force, $found);
    }

    /**
     * {@inheritdoc}
     */
    public function getInfo(): array
    {
        return [
            'hits' => $this->cache->cache_hits,
            'misses' => $this->cache->cache_misses,
            'ratio' => round(($this->cache->cache_hits / ($this->cache->cache_hits + $this->cache->cache_misses)) * 100, 1),
            'type' => str_replace('ObjectCache', '', (new \ReflectionClass($this))->getShortName()),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple(string $group, array $keys, bool $force = false): array
    {
        return $this->cache->get_multiple($keys, $group, $force);
    }

    /**
     * {@inheritdoc}
     */
    public function increment(string $group, string $key, int $offset = 1)
    {
        return $this->cache->incr($key, $offset, $group);
    }

    /**
     * {@inheritdoc}
     */
    public function replace(string $group, string $key, $value, int $expire = 0): bool
    {
        return $this->cache->replace($key, $value, $group, $expire);
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $group, string $key, $value, int $expire = 0): bool
    {
        return $this->cache->set($key, $value, $group, $expire);
    }

    /**
     * {@inheritdoc}
     */
    public function switchToBlog(int $blogId)
    {
        $this->cache->switch_to_blog($blogId);
    }
}
