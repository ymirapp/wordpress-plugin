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

use Ymir\Plugin\CloudProvider\Aws\CloudFrontClient;
use Ymir\Plugin\DependencyInjection\Container;
use Ymir\Plugin\DependencyInjection\ContainerConfigurationInterface;

/**
 * Configures the dependency injection container with page cache and services.
 */
class PageCacheConfiguration implements ContainerConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function modify(Container $container)
    {
        $container['cloudfront_client'] = $container->service(function (Container $container) {
            return new CloudFrontClient($container['ymir_http_client'], getenv('YMIR_DISTRIBUTION_ID'), $container['cloud_provider_key'], $container['cloud_provider_secret']);
        });
        $container['is_page_caching_disabled'] = $container->service(function (Container $container) {
            if (false !== getenv('YMIR_DISABLE_PAGE_CACHING')) {
                return (bool) getenv('YMIR_DISABLE_PAGE_CACHING');
            } elseif (defined('YMIR_DISABLE_PAGE_CACHING')) {
                return (bool) YMIR_DISABLE_PAGE_CACHING;
            }

            return parse_url($container['upload_url'], PHP_URL_HOST) !== parse_url(WP_HOME, PHP_URL_HOST);
        });
    }
}
