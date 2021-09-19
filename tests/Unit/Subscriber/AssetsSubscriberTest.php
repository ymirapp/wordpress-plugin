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

use Ymir\Plugin\Subscriber\AssetsSubscriber;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\Subscriber\AssetsSubscriber
 */
class AssetsSubscriberTest extends TestCase
{
    public function testAddAssetsUrlToDnsPrefetchDoesntAddAssetsUrlWhenDomainDifferentFromSiteUrl()
    {
        $this->assertSame(['https://assets.com'], (new AssetsSubscriber('https://foo.com', 'https://assets.com'))->addAssetsUrlToDnsPrefetch([], 'dns-prefetch'));
    }

    public function testAddAssetsUrlToDnsPrefetchDoesntAddAssetsWhenSameDomainAsSiteUrl()
    {
        $this->assertSame([], (new AssetsSubscriber('https://foo.com', 'https://foo.com'))->addAssetsUrlToDnsPrefetch([], 'dns-prefetch'));
    }

    public function testAddAssetsUrlToDnsPrefetchWhenNoAssetsUrl()
    {
        $this->assertSame([], (new AssetsSubscriber('https://foo.com'))->addAssetsUrlToDnsPrefetch([], 'foo'));
    }

    public function testAddAssetsUrlToDnsPrefetchWhenWrongTypeAndDifferentAssetsDomain()
    {
        $this->assertSame([], (new AssetsSubscriber('https://foo.com', 'https://assets.com'))->addAssetsUrlToDnsPrefetch([], 'foo'));
    }

    public function testGetSubscribedEvents()
    {
        $callbacks = AssetsSubscriber::getSubscribedEvents();

        foreach ($callbacks as $callback) {
            $this->assertTrue(method_exists(AssetsSubscriber::class, is_array($callback) ? $callback[0] : $callback));
        }

        $subscribedEvents = [
            'content_url' => 'rewriteContentUrl',
            'plugins_url' => 'rewritePluginsUrl',
            'script_loader_src' => 'replaceSiteUrlWithAssetsUrl',
            'style_loader_src' => 'replaceSiteUrlWithAssetsUrl',
            'wp_resource_hints' => ['addAssetsUrlToDnsPrefetch', 10, 2],
        ];

        $this->assertSame($subscribedEvents, $callbacks);
    }

    public function testReplaceSiteUrlWithAssetsUrlAddsWpWhenMissingWithBedrockProjectWithSourceSameAsSiteUrl()
    {
        $this->assertSame('https://assets.com/wp/asset.css', (new AssetsSubscriber('https://foo.com', 'https://assets.com', 'bedrock'))->replaceSiteUrlWithAssetsUrl('https://foo.com/asset.css'));
    }

    public function testReplaceSiteUrlWithAssetsUrlDoesntAddWpWithBedrockProjectWithAppUrl()
    {
        $this->assertSame('https://assets.com/app/asset.css', (new AssetsSubscriber('https://foo.com', 'https://assets.com', 'bedrock'))->replaceSiteUrlWithAssetsUrl('https://foo.com/app/asset.css'));
    }

    public function testReplaceSiteUrlWithAssetsUrlDoesntAddWpWithBedrockProjectWithSourceSameAsSiteUrl()
    {
        $this->assertSame('https://assets.com/wp/asset.css', (new AssetsSubscriber('https://foo.com', 'https://assets.com', 'bedrock'))->replaceSiteUrlWithAssetsUrl('https://foo.com/wp/asset.css'));
    }

    public function testReplaceSiteUrlWithAssetsUrlWithEmptyAssetsUrl()
    {
        $this->assertSame('https://foo.com/asset.css', (new AssetsSubscriber('https://foo.com'))->replaceSiteUrlWithAssetsUrl('https://foo.com/asset.css'));
    }

    public function testReplaceSiteUrlWithAssetsUrlWithSourceDifferentFromSiteUrl()
    {
        $this->assertSame('https://bar.com/asset.css', (new AssetsSubscriber('https://foo.com', 'https://assets.com'))->replaceSiteUrlWithAssetsUrl('https://bar.com/asset.css'));
    }

    public function testReplaceSiteUrlWithAssetsUrlWithSourceSameAsSiteUrl()
    {
        $this->assertSame('https://assets.com/asset.css', (new AssetsSubscriber('https://foo.com', 'https://assets.com'))->replaceSiteUrlWithAssetsUrl('https://foo.com/asset.css'));
    }

    public function testRewriteContentUrlDoesntKeepDirectoryBelowContentDir()
    {
        $this->assertSame('https://assets.com/wp-content/test.php', (new AssetsSubscriber('https://foo.com', 'https://assets.com'))->rewriteContentUrl('https://foo.com/foo/directory/wp-content/test.php'));
    }

    public function testRewriteContentUrlUsesContentDirConstant()
    {
        define('CONTENT_DIR', '/app');

        $this->assertSame('https://assets.com/app/test.php', (new AssetsSubscriber('https://foo.com', 'https://assets.com'))->rewriteContentUrl('https://foo.com/foo/directory/app/test.php'));
    }

    public function testRewritePluginUrlOnlyKeepsDirectoryBelowPlugins()
    {
        $this->assertSame('https://assets.com/directory/plugins/test.php', (new AssetsSubscriber('https://foo.com', 'https://assets.com'))->rewritePluginsUrl('https://foo.com/foo/directory/plugins/test.php'));
    }
}
