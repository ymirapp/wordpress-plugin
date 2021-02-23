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

use Ymir\Plugin\CloudProvider\Aws\SesClient;
use Ymir\Plugin\DependencyInjection\Container;
use Ymir\Plugin\DependencyInjection\ContainerConfigurationInterface;
use Ymir\Plugin\Email\Email;

/**
 * Configures the dependency injection container with email parameters and services.
 */
class EmailConfiguration implements ContainerConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function modify(Container $container)
    {
        $container['email_client'] = $container->service(function (Container $container) {
            return new SesClient($container['ymir_http_client'], $container['cloud_provider_key'], $container['cloud_provider_region'], $container['cloud_provider_secret']);
        });
        $container['email'] = function (Container $container) {
            return new Email($container['event_manager'], $container['default_email_from'], $container['phpmailer'], $container['blog_charset']);
        };
    }
}
