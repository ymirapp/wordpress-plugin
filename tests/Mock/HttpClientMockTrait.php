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

namespace Ymir\Plugin\Tests\Mock;

use PHPUnit\Framework\MockObject\MockObject;
use Ymir\Plugin\Http\ClientInterface;

trait HttpClientMockTrait
{
    /**
     * Creates a mock of a ClientInterface object.
     */
    private function getHttpClientMock(): MockObject
    {
        return $this->getMockBuilder(ClientInterface::class)
                    ->disableOriginalConstructor()
                    ->getMock();
    }
}
