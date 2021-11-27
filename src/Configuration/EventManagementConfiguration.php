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
use Ymir\Plugin\EventManagement\EventManager;
use Ymir\Plugin\Subscriber;

/**
 * Configures the dependency injection container with the plugin's event management service.
 */
class EventManagementConfiguration implements ContainerConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function modify(Container $container)
    {
        $container['event_manager'] = $container->service(function (Container $container) {
            return new EventManager();
        });

        // "upload_dir" is used everywhere and we need the filter to be registered before we make the call
        // to "subscribers" which wires a lot of services together that use the upload directories.
        $container['priority_subscribers'] = $container->service(function (Container $container) {
            return [
                new Subscriber\BedrockSubscriber($container['ymir_project_type']),
                new Subscriber\UploadsSubscriber($container['content_directory'], $container['content_url'], $container['cloud_storage_protocol'], $container['upload_url'], $container['upload_limit']),
            ];
        });

        $container['subscribers'] = $container->service(function (Container $container) {
            $subscribers = [
                new Subscriber\AssetsSubscriber($container['content_directory'], $container['site_url'], $container['assets_url'], $container['ymir_project_type'], $container['uploads_baseurl']),
                new Subscriber\DisallowIndexingSubscriber($container['ymir_using_vanity_domain']),
                new Subscriber\ImageEditorSubscriber($container['console_client'], $container['file_manager']),
                new Subscriber\PluploadSubscriber($container['plugin_relative_path'], $container['rest_namespace'], $container['assets_url'], $container['plupload_error_messages']),
                new Subscriber\RedirectSubscriber($container['ymir_primary_domain_name'], $container['is_multisite'], $container['ymir_project_type']),
                new Subscriber\RestApiSubscriber($container['rest_namespace'], $container['rest_endpoints']),
                new Subscriber\SecurityHeadersSubscriber(),
                new Subscriber\WordPressSubscriber($container['server_software'], $container['site_url']),
            ];

            if ($container['query_monitor_active']) {
                $subscribers[] = new Subscriber\QueryMonitorSubscriber($container['query_monitor_collectors'], $container['query_monitor_panels'], $container['plugin_dir_path'].'/resources/views/query-monitor');
            }
            if (is_plugin_active('woocommerce/woocommerce.php')) {
                $subscribers[] = new Subscriber\WooCommerceSubscriber();
            }

            return $subscribers;
        });
    }
}
