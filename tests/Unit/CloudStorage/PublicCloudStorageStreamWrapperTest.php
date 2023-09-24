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

namespace Ymir\Plugin\Tests\Unit\CloudStorage;

use Ymir\Plugin\CloudStorage\PublicCloudStorageStreamWrapper;

/**
 * @covers \Ymir\Plugin\CloudStorage\PublicCloudStorageStreamWrapper
 */
class PublicCloudStorageStreamWrapperTest extends AbstractCloudStorageStreamWrapperTestCase
{
    protected function getAcl(): string
    {
        return 'public-read';
    }

    protected function getStreamWrapperClass(): string
    {
        return PublicCloudStorageStreamWrapper::class;
    }
}
