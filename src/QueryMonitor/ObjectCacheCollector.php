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

namespace Ymir\Plugin\QueryMonitor;

use Ymir\Plugin\ObjectCache\ObjectCacheInterface;

class ObjectCacheCollector extends \QM_Collector
{
    /**
     * The ID of the collector.
     *
     * @var string
     */
    public const COLLECTOR_ID = 'ymir-object-cache';

    /**
     * The ID of the collector.
     *
     * @var string
     */
    public $id = self::COLLECTOR_ID;

    /**
     * The active object cache.
     */
    private $cache;

    /**
     * Constructor.
     */
    public function __construct($cache)
    {
        $this->cache = $cache;
    }

    /**
     * Populate the `data` property.
     */
    public function process()
    {
        if (!$this->cache instanceof ObjectCacheInterface) {
            return;
        }

        $info = $this->cache->getInfo();

        $this->data = array_merge($this->data, $info);

        // Generate the Query Monitor data used on the "Overview" tab.
        $this->data['stats']['cache_hits'] = $info['hits'];
        $this->data['stats']['cache_misses'] = $info['misses'];
        $this->data['cache_hit_percentage'] = $info['ratio'];

        $this->data['has_object_cache'] = wp_using_ext_object_cache();

        $this->data['object_cache_extensions'] = array_map('extension_loaded', [
            'APCu' => 'APCu',
            'Memcache' => 'Memcache',
            'Memcached' => 'Memcached',
            'Redis' => 'Redis',
        ]);
        $this->data['opcode_cache_extensions'] = array_map('extension_loaded', [
            'APC' => 'APC',
            'Zend OPcache' => 'Zend OPcache',
        ]);

        $this->data['has_opcode_cache'] = array_filter($this->data['opcode_cache_extensions']) ? true : false;
    }
}
