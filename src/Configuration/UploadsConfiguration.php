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
 * Configures the dependency injection container with Uploads parameters and services.
 */
class UploadsConfiguration implements ContainerConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function modify(Container $container)
    {
        $container['upload_url'] = $container->service(function (Container $container) {
            $uploadUrl = (string) getenv('YMIR_UPLOAD_URL');

            if (!Str::contains($uploadUrl, ['cloudfront.net', 's3.amazonaws.com']) && $container['is_multisite_subdomain'] && $container['ymir_mapped_domain_names']->isMappedDomainName($container['site_domain'])) {
                $uploadUrl = rtrim($container['home_url'], '/');
            }

            return $uploadUrl;
        });
        $container['upload_limit'] = $container->service(function () {
            return getenv('YMIR_UPLOAD_LIMIT') ?: (defined('YMIR_UPLOAD_LIMIT') ? YMIR_UPLOAD_LIMIT : '15MB');
        });
    }
}
