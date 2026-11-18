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

use Ymir\Plugin\Subscriber\PluginUpdateSubscriber;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Mock\HttpClientMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

class PluginUpdateSubscriberTest extends TestCase
{
    use FunctionMockTrait;
    use HttpClientMockTrait;

    public function provideFailedLookups(): array
    {
        return [
            'request exception' => [null],
            'unsuccessful response' => [['response' => ['code' => 500], 'body' => '']],
            'invalid json' => [['response' => ['code' => 200], 'body' => '<html></html>']],
            'missing package' => [['response' => ['code' => 200], 'body' => '{"packages":{}}']],
            'no versions' => [['response' => ['code' => 200], 'body' => '{"packages":{"ymirapp/wordpress-plugin":[]}}']],
        ];
    }

    /**
     * @dataProvider provideFailedLookups
     */
    public function testDisplayUpdateMessageCachesFailedLookupWithoutDisplayingMessage(?array $response): void
    {
        $httpClient = $this->getHttpClientMock();
        $request = $httpClient->expects($this->once())
                              ->method('request');

        if (null === $response) {
            $request->willThrowException(new \RuntimeException('cURL error 28: Operation timed out'));
        } else {
            $request->willReturn($response);
        }

        $get_site_transient = $this->getFunctionMock($this->getNamespace(PluginUpdateSubscriber::class), 'get_site_transient');
        $get_site_transient->expects($this->once())
                           ->with('ymir_latest_plugin_version')
                           ->willReturn(false);

        $set_site_transient = $this->getFunctionMock($this->getNamespace(PluginUpdateSubscriber::class), 'set_site_transient');
        $set_site_transient->expects($this->once())
                           ->with('ymir_latest_plugin_version', '', 12 * HOUR_IN_SECONDS);

        $this->expectOutputString('');

        (new PluginUpdateSubscriber($httpClient, 'ymir-wordpress-plugin/ymir.php', '1.29.1'))->displayUpdateMessage('ymir-wordpress-plugin/ymir.php');
    }

    public function testDisplayUpdateMessageDisplaysMessageWithCachedLatestVersionWithoutRequest(): void
    {
        $httpClient = $this->getHttpClientMock();
        $httpClient->expects($this->never())
                   ->method('request');

        $get_site_transient = $this->getFunctionMock($this->getNamespace(PluginUpdateSubscriber::class), 'get_site_transient');
        $get_site_transient->expects($this->once())
                           ->with('ymir_latest_plugin_version')
                           ->willReturn('1.30.0');

        $set_site_transient = $this->getFunctionMock($this->getNamespace(PluginUpdateSubscriber::class), 'set_site_transient');
        $set_site_transient->expects($this->never());

        $this->expectOutputRegex('/View version 1\.30\.0 details/');

        (new PluginUpdateSubscriber($httpClient, 'ymir-wordpress-plugin/ymir.php', '1.29.1'))->displayUpdateMessage('ymir-wordpress-plugin/ymir.php');
    }

    public function testDisplayUpdateMessageDisplaysMessageWithLatestVersionFromPackagist(): void
    {
        $httpClient = $this->getHttpClientMock();
        $httpClient->expects($this->once())
                   ->method('request')
                   ->with('https://repo.packagist.org/p2/ymirapp/wordpress-plugin.json', ['timeout' => 5])
                   ->willReturn([
                       'response' => ['code' => 200],
                       'body' => '{"minified":"composer/2.0","packages":{"ymirapp/wordpress-plugin":[{"version":"v1.30.0"},{"version":"v1.29.1"},{"version":"v1.29.0"}]}}',
                   ]);

        $get_site_transient = $this->getFunctionMock($this->getNamespace(PluginUpdateSubscriber::class), 'get_site_transient');
        $get_site_transient->expects($this->once())
                           ->with('ymir_latest_plugin_version')
                           ->willReturn(false);

        $set_site_transient = $this->getFunctionMock($this->getNamespace(PluginUpdateSubscriber::class), 'set_site_transient');
        $set_site_transient->expects($this->once())
                           ->with('ymir_latest_plugin_version', '1.30.0', 12 * HOUR_IN_SECONDS);

        $this->expectOutputString('<div class="update-message notice inline notice-warning notice-alt"><p>There is a new version of Ymir available. <a href="https://github.com/ymirapp/wordpress-plugin/blob/main/CHANGELOG.md" >View version 1.30.0 details</a>.</p></div>');

        (new PluginUpdateSubscriber($httpClient, 'ymir-wordpress-plugin/ymir.php', '1.29.1'))->displayUpdateMessage('ymir-wordpress-plugin/ymir.php');
    }

    public function testDisplayUpdateMessageDoesNothingForOtherPlugins(): void
    {
        $httpClient = $this->getHttpClientMock();
        $httpClient->expects($this->never())
                   ->method('request');

        $get_site_transient = $this->getFunctionMock($this->getNamespace(PluginUpdateSubscriber::class), 'get_site_transient');
        $get_site_transient->expects($this->never());

        $this->expectOutputString('');

        (new PluginUpdateSubscriber($httpClient, 'ymir-wordpress-plugin/ymir.php', '1.29.1'))->displayUpdateMessage('akismet/akismet.php');
    }

    public function testDisplayUpdateMessageDoesNothingIfCachedLookupFailed(): void
    {
        $httpClient = $this->getHttpClientMock();
        $httpClient->expects($this->never())
                   ->method('request');

        $get_site_transient = $this->getFunctionMock($this->getNamespace(PluginUpdateSubscriber::class), 'get_site_transient');
        $get_site_transient->expects($this->once())
                           ->with('ymir_latest_plugin_version')
                           ->willReturn('');

        $this->expectOutputString('');

        (new PluginUpdateSubscriber($httpClient, 'ymir-wordpress-plugin/ymir.php', '1.29.1'))->displayUpdateMessage('ymir-wordpress-plugin/ymir.php');
    }

    public function testDisplayUpdateMessageDoesNothingIfPluginIsUpToDate(): void
    {
        $get_site_transient = $this->getFunctionMock($this->getNamespace(PluginUpdateSubscriber::class), 'get_site_transient');
        $get_site_transient->expects($this->once())
                           ->with('ymir_latest_plugin_version')
                           ->willReturn('1.29.1');

        $this->expectOutputString('');

        (new PluginUpdateSubscriber($this->getHttpClientMock(), 'ymir-wordpress-plugin/ymir.php', '1.29.1'))->displayUpdateMessage('ymir-wordpress-plugin/ymir.php');
    }

    public function testDisplayUpdateMessageEscapesLatestVersion(): void
    {
        $get_site_transient = $this->getFunctionMock($this->getNamespace(PluginUpdateSubscriber::class), 'get_site_transient');
        $get_site_transient->expects($this->once())
                           ->with('ymir_latest_plugin_version')
                           ->willReturn('1.30.0<script>');

        $this->expectOutputRegex('/View version 1\.30\.0&lt;script&gt; details/');

        (new PluginUpdateSubscriber($this->getHttpClientMock(), 'ymir-wordpress-plugin/ymir.php', '1.29.1'))->displayUpdateMessage('ymir-wordpress-plugin/ymir.php');
    }

    public function testGetSubscribedEvents(): void
    {
        $callbacks = PluginUpdateSubscriber::getSubscribedEvents();

        foreach ($callbacks as $callback) {
            $this->assertTrue(method_exists(PluginUpdateSubscriber::class, is_array($callback) ? $callback[0] : $callback));
        }

        $subscribedEvents = [
            'after_plugin_row_meta' => 'displayUpdateMessage',
        ];

        $this->assertSame($subscribedEvents, $callbacks);
    }
}
