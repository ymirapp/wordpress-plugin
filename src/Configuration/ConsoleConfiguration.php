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

use Ymir\Plugin\CloudProvider\Aws\LambdaClient;
use Ymir\Plugin\Console;
use Ymir\Plugin\DependencyInjection\Container;
use Ymir\Plugin\DependencyInjection\ContainerConfigurationInterface;

/**
 * Configures the dependency injection container with WP-CLI parameters and services.
 */
class ConsoleConfiguration implements ContainerConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function modify(Container $container)
    {
        $container['commands'] = $container->service(function (Container $container) {
            return [
                new Console\CreateAttachmentMetadataCommand($container['file_manager'], $container['wp_cli']),
                new Console\CreateCroppedImageCommand($container['file_manager'], $container['event_manager'], $container['wp_cli']),
                new Console\CreateSiteIconCommand($container['file_manager'], $container['event_manager'], $container['site_icon'], $container['wp_cli']),
                new Console\EditAttachmentImageCommand($container['file_manager'], $container['wp_cli']),
                new Console\ResizeAttachmentImageCommand($container['file_manager'], $container['wp_cli']),
                new Console\RunAllCronCommand($container['console_client'], $container['wp_cli'], $container['site_query']),
            ];
        });
        $container['console_client'] = $container->service(function (Container $container) {
            return new LambdaClient($container['ymir_http_client'], $container['cloud_provider_function_name'], $container['cloud_provider_key'], $container['cloud_provider_region'], $container['cloud_provider_secret'], $container['site_url']);
        });
        $container['wp_cli'] = $container->service(function () {
            return new Console\WpCli();
        });
        $container['local_commands'] = $container->service(function () {
            return [];
        });
    }
}
