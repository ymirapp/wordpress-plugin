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
use Ymir\Plugin\Http\Client;

/**
 * Configures the dependency injection container with Ymir parameters and services.
 */
class YmirConfiguration implements ContainerConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function modify(Container $container)
    {
        $container['ymir_environment'] = getenv('YMIR_ENVIRONMENT') ?: '';
        $container['ymir_http_client'] = $container->service(function (Container $container) {
            return new Client($container['ymir_plugin_version']);
        });
        $container['ymir_primary_domain_name'] = getenv('YMIR_PRIMARY_DOMAIN_NAME') ?: '';
        $container['ymir_project_type'] = getenv('YMIR_PROJECT_TYPE') ?: 'wordpress';
        $container['ymir_plugin_version'] = '1.11.6';
        $container['ymir_using_vanity_domain'] = $container->service(function (Container $container) {
            return false !== stripos($container['site_url'], '.ymirsites.com');
        });
    }
}
