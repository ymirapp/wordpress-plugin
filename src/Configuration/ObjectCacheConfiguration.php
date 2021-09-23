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

namespace Ymir\Plugin\Configuration;

use Ymir\Plugin\CloudProvider\Aws\DynamoDbClient;
use Ymir\Plugin\DependencyInjection\Container;
use Ymir\Plugin\DependencyInjection\ContainerConfigurationInterface;
use Ymir\Plugin\ObjectCache\DynamoDbObjectCache;
use Ymir\Plugin\ObjectCache\RedisClusterObjectCache;
use Ymir\Plugin\ObjectCache\WordPressObjectCache;

/**
 * Configures the dependency injection container with object cache and services.
 */
class ObjectCacheConfiguration implements ContainerConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function modify(Container $container)
    {
        $container['dynamodb_client'] = $container->service(function (Container $container) {
            return new DynamoDbClient($container['ymir_http_client'], $container['cloud_provider_key'], $container['cloud_provider_region'], $container['cloud_provider_secret']);
        });
        $container['redis_client'] = $container->service(function () {
            $client = null;
            $endpoint = getenv('YMIR_REDIS_ENDPOINT');

            if (is_string($endpoint)) {
                $client = new \RedisCluster(null, [$endpoint.':6379'], 1.0, 1.0, true);

                $client->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_IGBINARY);
                $client->setOption(\Redis::OPT_COMPRESSION, \Redis::COMPRESSION_ZSTD);
            }

            return $client;
        });
        $container['ymir_dynamodb_object_cache'] = $container->service(function (Container $container) {
            $table = getenv('YMIR_CACHE_TABLE');

            return is_string($table) ? new DynamoDbObjectCache($container['dynamodb_client'], $container['is_multisite'], $table) : null;
        });
        $container['ymir_redis_object_cache'] = $container->service(function (Container $container) {
            $client = $container['redis_client'];

            return $client instanceof \RedisCluster ? new RedisClusterObjectCache($client, $container['is_multisite']) : null;
        });
        $container['ymir_wordpress_object_cache'] = $container->service(function () {
            if (!class_exists(\WP_Object_Cache::class)) {
                require_once ABSPATH.WPINC.'/class-wp-object-cache.php';
            }

            return new WordPressObjectCache(new \WP_Object_Cache());
        });
        $container['ymir_object_cache'] = $container->service(function (Container $container) {
            $cache = $container['ymir_wordpress_object_cache'];

            if ($container['ymir_redis_object_cache'] instanceof RedisClusterObjectCache) {
                $cache = $container['ymir_redis_object_cache'];
            } elseif ($container['ymir_dynamodb_object_cache'] instanceof DynamoDbObjectCache) {
                $cache = $container['ymir_dynamodb_object_cache'];
            }

            return $cache;
        });
    }
}
