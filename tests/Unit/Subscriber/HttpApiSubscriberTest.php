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

namespace Ymir\Plugin\Tests\Unit\Subscriber;

use Ymir\Plugin\Subscriber\HttpApiSubscriber;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\Subscriber\HttpApiSubscriber
 */
class HttpApiSubscriberTest extends TestCase
{
    use FunctionMockTrait;

    public function testAddPostfieldsForEmptyPutRequest()
    {
        $handle = fopen('php://temp', 'r+');

        $curl_setopt = $this->getFunctionMock($this->getNamespace(HttpApiSubscriber::class), 'curl_setopt');
        $curl_setopt->expects($this->once())
                    ->with($this->identicalTo($handle), $this->identicalTo(CURLOPT_POSTFIELDS), $this->identicalTo(''));

        (new HttpApiSubscriber())->addPostfieldsForEmptyPutRequest($handle, ['method' => 'PUT', 'body' => '']);
    }

    public function testGetSubscribedEvents()
    {
        $callbacks = HttpApiSubscriber::getSubscribedEvents();

        foreach ($callbacks as $callback) {
            $this->assertTrue(method_exists(HttpApiSubscriber::class, is_array($callback) ? $callback[0] : $callback));
        }

        $subscribedEvents = [
            'http_api_curl' => ['addPostfieldsForEmptyPutRequest', 10, 3],
        ];

        $this->assertSame($subscribedEvents, $callbacks);
    }
}
