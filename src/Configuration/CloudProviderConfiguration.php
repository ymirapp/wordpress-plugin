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

/**
 * Configures the dependency injection container with cloud provider parameters and services.
 */
class CloudProviderConfiguration implements ContainerConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function modify(Container $container)
    {
        $container['cloud_provider_function_name'] = $container->service(function () {
            $functionName = getenv('AWS_LAMBDA_FUNCTION_NAME');

            if (is_string($functionName)) {
                $functionName = str_replace('website', 'console', $functionName);
            } elseif (defined('YMIR_CLOUD_PROVIDER_FUNCTION_NAME')) {
                $functionName = YMIR_CLOUD_PROVIDER_FUNCTION_NAME;
            }

            return $functionName ?: '';
        });
        $container['cloud_provider_key'] = $container->service(function () {
            return getenv('AWS_ACCESS_KEY_ID') ?: (defined('YMIR_CLOUD_PROVIDER_KEY') ? YMIR_CLOUD_PROVIDER_KEY : '');
        });
        $container['cloud_provider_region'] = $container->service(function () {
            return getenv('AWS_REGION') ?: (defined('YMIR_CLOUD_PROVIDER_REGION') ? YMIR_CLOUD_PROVIDER_REGION : 'us-east-1');
        });
        $container['cloud_provider_secret'] = $container->service(function () {
            return getenv('AWS_SECRET_ACCESS_KEY') ?: (defined('YMIR_CLOUD_PROVIDER_SECRET') ? YMIR_CLOUD_PROVIDER_SECRET : '');
        });
        $container['cloud_provider_private_store'] = $container->service(function () {
            return getenv('YMIR_PRIVATE_STORE') ?: (defined('YMIR_CLOUD_PROVIDER_PRIVATE_STORE') ? YMIR_CLOUD_PROVIDER_PRIVATE_STORE : '');
        });
        $container['cloud_provider_public_store'] = $container->service(function () {
            return getenv('YMIR_PUBLIC_STORE') ?: (defined('YMIR_CLOUD_PROVIDER_PUBLIC_STORE') ? YMIR_CLOUD_PROVIDER_PUBLIC_STORE : '');
        });
    }
}
