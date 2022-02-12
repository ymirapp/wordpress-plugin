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

namespace Ymir\Plugin\CloudStorage;

class PublicCloudStorageStreamWrapper extends AbstractCloudStorageStreamWrapper
{
    /**
     * {@inheritdoc}
     */
    public static function getProtocol(): string
    {
        return 'ymir-public';
    }
}
