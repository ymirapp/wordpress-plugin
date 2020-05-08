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

namespace Ymir\Plugin\Tests\Unit\RestApi;

use PHPUnit\Framework\TestCase;
use Ymir\Plugin\RestApi\AbstractEndpoint;
use Ymir\Plugin\Tests\Mock\WPRESTRequestMockTrait;

/**
 * @covers \Ymir\Plugin\RestApi\AbstractEndpoint
 */
class AbstractEndpointTest extends TestCase
{
    use WPRESTRequestMockTrait;

    public function testGetCallback()
    {
        $endpoint = $this->getMockForAbstractClass(AbstractEndpoint::class);

        $this->assertSame([$endpoint, 'respond'], $endpoint->getCallback());
    }

    public function testGetPermissionCallback()
    {
        $endpoint = $this->getMockForAbstractClass(AbstractEndpoint::class);

        $this->assertSame([$endpoint, 'validateRequest'], $endpoint->getPermissionCallback());
    }

    public function testValidateRequest()
    {
        $endpoint = $this->getMockForAbstractClass(AbstractEndpoint::class);
        $request = $this->getWPRESTRequestMock();

        $this->assertTrue($endpoint->validateRequest($request));
    }
}
