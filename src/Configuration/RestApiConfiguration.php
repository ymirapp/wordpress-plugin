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
use Ymir\Plugin\RestApi;

/**
 * Configures the dependency injection container with WordPress REST API parameters and services.
 */
class RestApiConfiguration implements ContainerConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function modify(Container $container)
    {
        $container['rest_namespace'] = 'ymir/v1';
        $container['rest_endpoints'] = $container->service(function (Container $container) {
            return [
                new RestApi\CreateAttachmentEndpoint($container['public_cloud_storage_client'], $container['console_client'], $container['uploads_basedir'], $container['uploads_baseurl'], $container['force_async_attachment_creation']),
                new RestApi\GetFileDetailsEndpoint($container['public_cloud_storage_client'], $container['uploads_path'], $container['uploads_subdir']),
            ];
        });
    }
}
