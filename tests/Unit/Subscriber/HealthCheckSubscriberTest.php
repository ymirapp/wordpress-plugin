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

use Ymir\Plugin\EventManagement\WordPressAwareInterface;
use Ymir\Plugin\Subscriber\HealthCheckSubscriber;
use Ymir\Plugin\Tests\Mock\EventManagerMockTrait;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

class HealthCheckSubscriberTest extends TestCase
{
    use EventManagerMockTrait;
    use FunctionMockTrait;

    /**
     * The server parameters before the test.
     *
     * @var array
     */
    private $server;

    /**
     * The subscriber used by the test.
     *
     * @var HealthCheckSubscriber
     */
    private $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->server = $_SERVER;
        $this->subscriber = new HealthCheckSubscriber();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/ymir/health';
        $_SERVER['YMIR_HEALTH_CHECK'] = '1';
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;

        parent::tearDown();
    }

    public function provideUnauthorizedServerParameters(): array
    {
        return [
            'missing marker' => [[], ['YMIR_HEALTH_CHECK']],
            'marker set to "0"' => [['YMIR_HEALTH_CHECK' => '0'], []],
            'marker set to "true"' => [['YMIR_HEALTH_CHECK' => 'true'], []],
            'marker set to boolean true' => [['YMIR_HEALTH_CHECK' => true], []],
            'marker set to integer 1' => [['YMIR_HEALTH_CHECK' => 1], []],
            'marker set with whitespace' => [['YMIR_HEALTH_CHECK' => '1 '], []],
            'marker only sent as an HTTP header' => [['HTTP_YMIR_HEALTH_CHECK' => '1'], ['YMIR_HEALTH_CHECK']],
            'POST request' => [['REQUEST_METHOD' => 'POST'], []],
            'HEAD request' => [['REQUEST_METHOD' => 'HEAD'], []],
            'missing request method' => [[], ['REQUEST_METHOD']],
            'missing request uri' => [[], ['REQUEST_URI']],
        ];
    }

    public function testGetSubscribedEvents(): void
    {
        $callbacks = HealthCheckSubscriber::getSubscribedEvents();

        foreach ($callbacks as $callback) {
            $this->assertTrue(method_exists(HealthCheckSubscriber::class, is_array($callback) ? $callback[0] : $callback));
        }

        $subscribedEvents = [
            'redirect_canonical' => ['preventCanonicalPathRedirectForHealthCheck', PHP_INT_MIN, 2],
            'template_redirect' => ['respondToHealthCheck', PHP_INT_MAX],
        ];

        $this->assertSame($subscribedEvents, $callbacks);
    }

    public function testImplementsWordPressAwareInterface(): void
    {
        $this->assertInstanceOf(WordPressAwareInterface::class, $this->subscriber);
    }

    public function testPreventCanonicalPathRedirectForHealthCheckKeepsRedirectForOtherRequests(): void
    {
        $_SERVER['YMIR_HEALTH_CHECK'] = '0';

        $this->assertSame('https://example.com/health-tips/', $this->subscriber->preventCanonicalPathRedirectForHealthCheck('https://example.com/health-tips/', 'https://example.com/ymir/health'));
    }

    public function testPreventCanonicalPathRedirectForHealthCheckKeepsRedirectToOtherHost(): void
    {
        $this->mockHealthCheckRequest();

        $this->assertSame('https://example.com/ymir/health', $this->subscriber->preventCanonicalPathRedirectForHealthCheck('https://example.com/ymir/health', 'https://www.example.com/ymir/health'));
    }

    public function testPreventCanonicalPathRedirectForHealthCheckKeepsRedirectToOtherScheme(): void
    {
        $this->mockHealthCheckRequest();

        $this->assertSame('https://example.com/ymir/health', $this->subscriber->preventCanonicalPathRedirectForHealthCheck('https://example.com/ymir/health', 'http://example.com/ymir/health'));
    }

    public function testPreventCanonicalPathRedirectForHealthCheckPreventsPathRedirect(): void
    {
        $this->mockHealthCheckRequest();

        $this->assertFalse($this->subscriber->preventCanonicalPathRedirectForHealthCheck('https://example.com/health-tips/', 'https://example.com/ymir/health'));
    }

    public function testRespondToHealthCheckDoesNothingBeforeWordPressIsSupplied(): void
    {
        $this->getFunctionMock($this->getNamespace(HealthCheckSubscriber::class), 'is_404')
             ->expects($this->once())
             ->willReturn(false);

        $this->getFunctionMock($this->getNamespace(HealthCheckSubscriber::class), 'wp_send_json')
             ->expects($this->never());

        $eventManager = $this->getEventManagerMock();
        $eventManager->expects($this->never())
                     ->method('execute');

        $this->subscriber->setEventManager($eventManager);

        $this->subscriber->respondToHealthCheck();
    }

    public function testRespondToHealthCheckDoesNothingForOtherPath(): void
    {
        $_SERVER['REQUEST_URI'] = '/ymir/healthy';

        $this->mockHealthCheckRequest();

        $this->getFunctionMock($this->getNamespace(HealthCheckSubscriber::class), 'wp_send_json')
             ->expects($this->never());

        $eventManager = $this->getEventManagerMock();
        $eventManager->expects($this->never())
                     ->method('execute');

        $this->subscriber->setEventManager($eventManager);

        $this->subscriber->respondToHealthCheck();
    }

    public function testRespondToHealthCheckDoesNothingWhenSiteOwnsPath(): void
    {
        $this->mockHealthCheckRequest(false);

        $this->getFunctionMock($this->getNamespace(HealthCheckSubscriber::class), 'wp_send_json')
             ->expects($this->never());

        $eventManager = $this->getEventManagerMock();
        $eventManager->expects($this->never())
                     ->method('execute');

        $this->subscriber->setEventManager($eventManager);

        $this->subscriber->respondToHealthCheck();
    }

    public function testRespondToHealthCheckDoesNothingWhenSiteRoutesPlainPermalinkRequest(): void
    {
        $this->mockHealthCheckRequest(false, false, ['s' => 'health']);

        $this->getFunctionMock($this->getNamespace(HealthCheckSubscriber::class), 'wp_send_json')
             ->expects($this->never());

        $eventManager = $this->getEventManagerMock();
        $eventManager->expects($this->never())
                     ->method('execute');

        $this->subscriber->setEventManager($eventManager);

        $this->subscriber->respondToHealthCheck();
    }

    /**
     * @dataProvider provideUnauthorizedServerParameters
     */
    public function testRespondToHealthCheckDoesNothingWithoutAuthorizedGetRequest(array $parameters, array $removedParameters): void
    {
        $_SERVER = array_merge($_SERVER, $parameters);

        foreach ($removedParameters as $parameter) {
            unset($_SERVER[$parameter]);
        }

        $this->getFunctionMock($this->getNamespace(HealthCheckSubscriber::class), 'wp_send_json')
             ->expects($this->never());

        $eventManager = $this->getEventManagerMock();
        $eventManager->expects($this->never())
                     ->method('execute');

        $this->subscriber->setEventManager($eventManager);

        $this->subscriber->respondToHealthCheck();
    }

    public function testRespondToHealthCheckSendsDownResponseWhenDiagnosticsFail(): void
    {
        $this->mockHealthCheckRequest();

        $this->getFunctionMock($this->getNamespace(HealthCheckSubscriber::class), 'error_log')
             ->expects($this->once())
             ->with($this->stringContains('Ymir health check failed: RuntimeException: database password'));

        $this->getFunctionMock($this->getNamespace(HealthCheckSubscriber::class), 'nocache_headers')
             ->expects($this->once());

        $this->getFunctionMock($this->getNamespace(HealthCheckSubscriber::class), 'wp_send_json')
             ->expects($this->once())
             ->with($this->identicalTo(['status' => 'down']), $this->identicalTo(500));

        $eventManager = $this->getEventManagerMock();
        $eventManager->expects($this->once())
                     ->method('execute')
                     ->with($this->identicalTo('ymir_diagnosing_health'))
                     ->willThrowException(new \RuntimeException('database password'));

        $this->subscriber->setEventManager($eventManager);

        $this->subscriber->respondToHealthCheck();
    }

    public function testRespondToHealthCheckSendsDownResponseWhenReportingFails(): void
    {
        $this->mockHealthCheckRequest();

        $this->getFunctionMock($this->getNamespace(HealthCheckSubscriber::class), 'error_log')
             ->expects($this->once())
             ->willThrowException(new \RuntimeException('reporting failed'));

        $this->getFunctionMock($this->getNamespace(HealthCheckSubscriber::class), 'nocache_headers')
             ->expects($this->once());

        $this->getFunctionMock($this->getNamespace(HealthCheckSubscriber::class), 'wp_send_json')
             ->expects($this->once())
             ->with($this->identicalTo(['status' => 'down']), $this->identicalTo(500));

        $eventManager = $this->getEventManagerMock();
        $eventManager->expects($this->once())
                     ->method('execute')
                     ->willThrowException(new \Error('diagnostic error'));

        $this->subscriber->setEventManager($eventManager);

        $this->subscriber->respondToHealthCheck();
    }

    public function testRespondToHealthCheckSendsUpResponse(): void
    {
        $this->mockHealthCheckRequest();

        $this->getFunctionMock($this->getNamespace(HealthCheckSubscriber::class), 'nocache_headers')
             ->expects($this->once());

        $this->getFunctionMock($this->getNamespace(HealthCheckSubscriber::class), 'wp_send_json')
             ->expects($this->once())
             ->with($this->identicalTo(['status' => 'up']), $this->identicalTo(200));

        $eventManager = $this->getEventManagerMock();
        $eventManager->expects($this->once())
                     ->method('execute')
                     ->with($this->identicalTo('ymir_diagnosing_health'));

        $this->subscriber->setEventManager($eventManager);

        $this->subscriber->respondToHealthCheck();

        $this->assertTrue(DONOTCACHEPAGE);
    }

    public function testRespondToHealthCheckSendsUpResponseInSubdirectoryWithPlainPermalinks(): void
    {
        $_SERVER['REQUEST_URI'] = '/blog/ymir/health/';

        $this->mockHealthCheckRequest(false, false, [], 'https://example.com/blog');

        $this->getFunctionMock($this->getNamespace(HealthCheckSubscriber::class), 'nocache_headers')
             ->expects($this->once());

        $this->getFunctionMock($this->getNamespace(HealthCheckSubscriber::class), 'wp_send_json')
             ->expects($this->once())
             ->with($this->identicalTo(['status' => 'up']), $this->identicalTo(200));

        $eventManager = $this->getEventManagerMock();
        $eventManager->expects($this->once())
                     ->method('execute')
                     ->with($this->identicalTo('ymir_diagnosing_health'));

        $this->subscriber->setEventManager($eventManager);

        $this->subscriber->respondToHealthCheck();
    }

    public function testRespondToHealthCheckUsesUpdatedWordPressRequest(): void
    {
        $wp = $this->mockHealthCheckRequest(false, false);
        $wp->query_vars['s'] = 'health';

        $this->getFunctionMock($this->getNamespace(HealthCheckSubscriber::class), 'wp_send_json')
             ->expects($this->never());

        $eventManager = $this->getEventManagerMock();
        $eventManager->expects($this->never())
                     ->method('execute');

        $this->subscriber->setEventManager($eventManager);

        $this->subscriber->respondToHealthCheck();
    }

    /**
     * Supply the WordPress request environment and mock the WordPress request helpers.
     */
    private function mockHealthCheckRequest(bool $is404 = true, bool $didPermalink = true, array $queryVariables = [], string $homeUrl = 'https://example.com'): \WP
    {
        $wp = new \WP();
        $wp->did_permalink = $didPermalink;
        $wp->query_vars = $queryVariables;

        $this->subscriber->setWordPress($wp);

        $this->getFunctionMock($this->getNamespace(HealthCheckSubscriber::class), 'is_404')
             ->expects($this->any())
             ->willReturn($is404);

        $this->getFunctionMock($this->getNamespace(HealthCheckSubscriber::class), 'home_url')
             ->expects($this->any())
             ->willReturn($homeUrl);

        return $wp;
    }
}
