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

use Ymir\Plugin\Subscriber\RestApiSubscriber;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\Subscriber\RestApiSubscriber
 */
class RestApiSubscriberTest extends TestCase
{
    use FunctionMockTrait;

    protected function setUp()
    {
        require_once 'fixtures/Endpoint.php';
    }

    public function testGetSubscribedEvents()
    {
        $callbacks = RestApiSubscriber::getSubscribedEvents();

        foreach ($callbacks as $callback) {
            $this->assertTrue(method_exists(RestApiSubscriber::class, is_array($callback) ? $callback[0] : $callback));
        }

        $subscribedEvents = [
            'rest_api_init' => 'registerEndpoints',
        ];

        $this->assertSame($subscribedEvents, $callbacks);
    }

    public function testRegisterEndpoints()
    {
        $endpoint = new Endpoint();

        $registerRestRoute = $this->getFunctionMock($this->getNamespace(RestApiSubscriber::class), 'register_rest_route');
        $registerRestRoute->expects($this->once())
                          ->with(
                              $this->identicalTo('namespace'),
                              $this->identicalTo('/endpoint'),
                              $this->identicalTo([
                                  'args' => ['argument'],
                                  'callback' => [$endpoint, 'endpointCallback'],
                                  'methods' => ['GET'],
                                  'permission_callback' => [$endpoint, 'permissionCallback'],
                              ])
                          );

        $subscriber = new RestApiSubscriber('/namespace/', [$endpoint]);

        $subscriber->registerEndpoints();
    }
}
