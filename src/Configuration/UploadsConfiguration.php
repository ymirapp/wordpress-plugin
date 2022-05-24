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
 * Configures the dependency injection container with Uploads parameters and services.
 */
class UploadsConfiguration implements ContainerConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function modify(Container $container)
    {
        $container['upload_url'] = $container->service(function () {
            $uploadUrl = getenv('YMIR_UPLOAD_URL');

            if (!$uploadUrl && defined('YMIR_UPLOAD_URL')) {
                $uploadUrl = YMIR_UPLOAD_URL;
            } elseif (!$uploadUrl && defined('WP_SITEURL')) {
                $uploadUrl = WP_SITEURL;
            }

            return $uploadUrl ?: '';
        });
        $container['upload_limit'] = $container->service(function () {
            return getenv('YMIR_UPLOAD_LIMIT') ?: (defined('YMIR_UPLOAD_LIMIT') ? YMIR_UPLOAD_LIMIT : '15MB');
        });
    }
}
