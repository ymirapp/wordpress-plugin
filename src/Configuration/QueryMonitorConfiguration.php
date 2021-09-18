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

use Ymir\Plugin\DependencyInjection\Container;
use Ymir\Plugin\DependencyInjection\ContainerConfigurationInterface;
use Ymir\Plugin\QueryMonitor\ObjectCacheCollector;
use Ymir\Plugin\QueryMonitor\ObjectCachePanel;

/**
 * Configures the dependency injection container with Query Monitor parameters and services.
 */
class QueryMonitorConfiguration implements ContainerConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function modify(Container $container)
    {
        $container['query_monitor_active'] = $container->service(function () {
            $isActive = is_plugin_active('query-monitor/query-monitor.php');

            if ($isActive) {
                require_once WP_CONTENT_DIR.'/plugins/query-monitor/classes/Collector.php';
                require_once WP_CONTENT_DIR.'/plugins/query-monitor/classes/Collectors.php';
                require_once WP_CONTENT_DIR.'/plugins/query-monitor/output/Html.php';
            }

            return $isActive;
        });
        $container['query_monitor_collectors'] = $container->service(function (Container $container) {
            return [
                $container['query_monitor_object_cache_collector'],
            ];
        });
        $container['query_monitor_object_cache_collector'] = $container->service(function (Container $container) {
            return new ObjectCacheCollector($container['wp_object_cache']);
        });
        $container['query_monitor_panels'] = $container->service(function () {
            return [
                ObjectCachePanel::class,
            ];
        });
    }
}
