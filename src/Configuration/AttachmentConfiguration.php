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

use Ymir\Plugin\Attachment\AttachmentFileManager;
use Ymir\Plugin\DependencyInjection\Container;
use Ymir\Plugin\DependencyInjection\ContainerConfigurationInterface;

/**
 * Configures the dependency injection container with WordPress attachment services.
 */
class AttachmentConfiguration implements ContainerConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function modify(Container $container)
    {
        $container['file_manager'] = $container->service(function (Container $container) {
            return new AttachmentFileManager($container['uploads_basedir']);
        });
    }
}
