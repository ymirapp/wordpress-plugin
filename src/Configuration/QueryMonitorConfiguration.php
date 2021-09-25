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
            if (!is_plugin_active('query-monitor/query-monitor.php')) {
                return false;
            }

            $files = [
                WP_CONTENT_DIR.'/plugins/query-monitor/classes/Collector.php',
                WP_CONTENT_DIR.'/plugins/query-monitor/classes/Collectors.php',
                WP_CONTENT_DIR.'/plugins/query-monitor/classes/Output.php',
                WP_CONTENT_DIR.'/plugins/query-monitor/output/Html.php',
            ];

            if (!array_filter($files, 'file_exists')) {
                return false;
            }

            foreach ($files as $file) {
                require_once $file;
            }

            return true;
        });
        $container['query_monitor_display_object_cache_active'] = $container->service(function (Container $container) {
            if (!wp_using_ext_object_cache()) {
                return false;
            }

            $dropInData = get_plugin_data(WP_CONTENT_DIR.'/object-cache.php', false, false);
            $pluginData = get_plugin_data($container['plugin_dir_path'].'/stubs/object-cache.php', false, false);

            return isset($dropInData['PluginName'], $pluginData['PluginName']) && $dropInData['PluginName'] === $pluginData['PluginName'];
        });
        $container['query_monitor_collectors'] = $container->service(function (Container $container) {
            $collectors = [];

            if ($container['query_monitor_display_object_cache_active']) {
                $collectors[] = $container['query_monitor_object_cache_collector'];
            }

            return $collectors;
        });
        $container['query_monitor_object_cache_collector'] = $container->service(function (Container $container) {
            return new ObjectCacheCollector($container['wp_object_cache']);
        });
        $container['query_monitor_panels'] = $container->service(function (Container $container) {
            $panels = [];

            if ($container['query_monitor_display_object_cache_active']) {
                $panels[] = ObjectCachePanel::class;
            }

            return $panels;
        });
    }
}
