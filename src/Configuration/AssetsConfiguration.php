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
use Ymir\Plugin\Support\Str;

/**
 * Configures the dependency injection container with WordPress attachment services.
 */
class AssetsConfiguration implements ContainerConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function modify(Container $container)
    {
        $container['assets_path'] = $container->service(function () {
            return getenv('YMIR_ASSETS_PATH') ?: '';
        });
        $container['assets_url'] = $container->service(function (Container $container) {
            $customAssetsUrl = getenv('YMIR_CUSTOM_ASSETS_URL');

            if (is_string($customAssetsUrl)) {
                return rtrim($customAssetsUrl, '/').'/'.$container['assets_path'];
            }

            $assetsUrl = getenv('YMIR_ASSETS_URL') ?: (defined('YMIR_ASSETS_URL') ? YMIR_ASSETS_URL : '');

            if (!Str::contains($assetsUrl, ['cloudfront.net', 's3.amazonaws.com']) && $container['is_multisite_subdomain'] && $container['ymir_mapped_domain_names']->isMappedDomainName($container['site_domain'])) {
                $assetsUrl = rtrim($container['site_url'], '/').'/'.$container['assets_path'];
            }

            return $assetsUrl;
        });
    }
}
