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
use Ymir\Plugin\Http\CurlClient;
use Ymir\Plugin\Http\WordPressClient;
use Ymir\Plugin\ValueObject\MappedDomainNames;

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
        $container['ymir_cdn_image_processing_enabled'] = $container->service(function () {
            if (defined('YMIR_CDN_IMAGE_PROCESSING_ENABLED')) {
                return (bool) YMIR_CDN_IMAGE_PROCESSING_ENABLED;
            } elseif (false !== getenv('YMIR_CDN_IMAGE_PROCESSING_ENABLED')) {
                return (bool) getenv('YMIR_CDN_IMAGE_PROCESSING_ENABLED');
            }

            return false;
        });
        $container['ymir_environment'] = getenv('YMIR_ENVIRONMENT') ?: '';
        $container['ymir_http_client'] = $container->service(function (Container $container) {
            return function_exists('_wp_http_get_object') ? new WordPressClient(_wp_http_get_object(), $container['ymir_plugin_version']) : new CurlClient($container['ymir_plugin_version']);
        });
        $container['ymir_mapped_domain_names'] = $container->service(function (Container $container) {
            return new MappedDomainNames((array) explode(',', (string) getenv('YMIR_DOMAIN_NAMES')), $container['ymir_primary_domain_name']);
        });
        $container['ymir_primary_domain_name'] = (string) getenv('YMIR_PRIMARY_DOMAIN_NAME');
        $container['ymir_project_type'] = getenv('YMIR_PROJECT_TYPE') ?: 'wordpress';
        $container['ymir_plugin_version'] = '1.26.4';
        $container['ymir_using_vanity_domain'] = $container->service(function (Container $container) {
            return false !== stripos($container['site_url'], '.ymirsites.com');
        });
    }
}
