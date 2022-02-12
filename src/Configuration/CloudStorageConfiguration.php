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

use Ymir\Plugin\CloudProvider\Aws\S3Client;
use Ymir\Plugin\CloudStorage\PrivateCloudStorageStreamWrapper;
use Ymir\Plugin\CloudStorage\PublicCloudStorageStreamWrapper;
use Ymir\Plugin\DependencyInjection\Container;
use Ymir\Plugin\DependencyInjection\ContainerConfigurationInterface;

/**
 * Configures the dependency injection container with the cloud storage parameters and services.
 */
class CloudStorageConfiguration implements ContainerConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function modify(Container $container)
    {
        $container['private_cloud_storage_client'] = $container->service(function (Container $container) {
            return new S3Client($container['ymir_http_client'], $container['cloud_provider_private_store'], $container['cloud_provider_key'], $container['cloud_provider_region'], $container['cloud_provider_secret']);
        });
        $container['private_cloud_storage_protocol'] = PrivateCloudStorageStreamWrapper::getProtocol().'://';
        $container['public_cloud_storage_client'] = $container->service(function (Container $container) {
            return new S3Client($container['ymir_http_client'], $container['cloud_provider_public_store'], $container['cloud_provider_key'], $container['cloud_provider_region'], $container['cloud_provider_secret']);
        });
        $container['public_cloud_storage_protocol'] = PublicCloudStorageStreamWrapper::getProtocol().'://';
    }
}
