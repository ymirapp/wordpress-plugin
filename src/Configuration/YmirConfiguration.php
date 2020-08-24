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
        $container['ymir_primary_domain_name'] = getenv('YMIR_PRIMARY_DOMAIN_NAME') ?: '';
        $container['ymir_project_type'] = getenv('YMIR_PROJECT_TYPE') ?: 'wordpress';
    }
}
