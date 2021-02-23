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
        $container['wordpress_object_cache'] = $container->service(function (Container $container) {
            if (!class_exists(\WP_Object_Cache::class)) {
                require_once ABSPATH.WPINC.'/class-wp-object-cache.php';
            }

            return new WordPressObjectCache(new \WP_Object_Cache());
        });
        $container['ymir_object_cache'] = $container->service(function (Container $container) {
            $table = getenv('YMIR_CACHE_TABLE');

            return is_string($table) ? new DynamoDbObjectCache($container['dynamodb_client'], $container['is_multisite'], $table) : $container['wordpress_object_cache'];
        });
    }
}
