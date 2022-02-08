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
        $container['force_async_attachment_creation'] = $container->service(function () {
            if (false !== getenv('YMIR_FORCE_ASYNC_ATTACHMENT_CREATION')) {
                return (bool) getenv('YMIR_FORCE_ASYNC_ATTACHMENT_CREATION');
            } elseif (defined('YMIR_FORCE_ASYNC_ATTACHMENT_CREATION')) {
                return (bool) YMIR_FORCE_ASYNC_ATTACHMENT_CREATION;
            }

            return false;
        });
    }
}
