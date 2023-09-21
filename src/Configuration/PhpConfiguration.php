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
 * Configures the dependency injection container with PHP parameters and services.
 */
class PhpConfiguration implements ContainerConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function modify(Container $container)
    {
        $container['http_host'] = $_SERVER['HTTP_HOST'] ?? '';
        $container['request_uri'] = $_SERVER['REQUEST_URI'] ?? '';
        $container['server_software'] = $_SERVER['SERVER_SOFTWARE'];
    }
}
