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
    public function provideReplaceUrlsInContent(): array
    {
        return [
            ['replaces-with-assets-url.html'],
            ['replaces-with-uploads-url.html'],
            ['updates-assets-urls.html'],
            ['urls-with-no-path.html'],
        ];
    }

    public function testAddAssetsUrlToDnsPrefetchDoesntAddAssetsUrlWhenDomainDifferentFromSiteUrl()
    {
        $this->assertSame(['https://assets.com/assets/uuid'], (new AssetsSubscriber('content_dir', 'https://foo.com', 'https://assets.com/assets/uuid'))->addAssetsUrlToDnsPrefetch([], 'dns-prefetch'));
    }

    public function testAddAssetsUrlToDnsPrefetchDoesntAddAssetsWhenSameDomainAsSiteUrl()
    {
        $this->assertSame([], (new AssetsSubscriber('content_dir', 'https://foo.com', 'https://foo.com'))->addAssetsUrlToDnsPrefetch([], 'dns-prefetch'));
    }

    public function testAddAssetsUrlToDnsPrefetchWhenNoAssetsUrl()
    {
        $this->assertSame([], (new AssetsSubscriber('content_dir', 'https://foo.com'))->addAssetsUrlToDnsPrefetch([], 'foo'));
    }

    public function testAddAssetsUrlToDnsPrefetchWhenWrongTypeAndDifferentAssetsDomain()
    {
        $this->assertSame([], (new AssetsSubscriber('content_dir', 'https://foo.com', 'https://assets.com/assets/uuid'))->addAssetsUrlToDnsPrefetch([], 'foo'));
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
            'the_content' => ['replaceUrlsInContent', 99999],
            'wp_resource_hints' => ['addAssetsUrlToDnsPrefetch', 10, 2],
        ];

        $this->assertSame($subscribedEvents, $callbacks);
    }

    public function testReplaceSiteUrlWithAssetsUrlAddsWpWhenMissingWithBedrockProjectWithSourceSameAsSiteUrl()
    {
        $this->assertSame('https://assets.com/assets/uuid/wp/asset.css', (new AssetsSubscriber('content_dir', 'https://foo.com', 'https://assets.com/assets/uuid', 'bedrock', 'https://assets.com/uploads'))->replaceSiteUrlWithAssetsUrl('https://foo.com/asset.css'));
    }

    public function testReplaceSiteUrlWithAssetsUrlDoesntAddWpWithBedrockProjectWithAppUrl()
    {
        $this->assertSame('https://assets.com/assets/uuid/app/asset.css', (new AssetsSubscriber('content_dir', 'https://foo.com', 'https://assets.com/assets/uuid', 'bedrock', 'https://assets.com/uploads'))->replaceSiteUrlWithAssetsUrl('https://foo.com/app/asset.css'));
    }

    public function testReplaceSiteUrlWithAssetsUrlDoesntAddWpWithBedrockProjectWithSourceSameAsSiteUrl()
    {
        $this->assertSame('https://assets.com/assets/uuid/wp/asset.css', (new AssetsSubscriber('content_dir', 'https://foo.com', 'https://assets.com/assets/uuid', 'bedrock', 'https://assets.com/uploads'))->replaceSiteUrlWithAssetsUrl('https://foo.com/wp/asset.css'));
    }

    public function testReplaceSiteUrlWithAssetsUrlWithEmptyAssetsUrl()
    {
        $this->assertSame('https://foo.com/asset.css', (new AssetsSubscriber('content_dir', 'https://foo.com'))->replaceSiteUrlWithAssetsUrl('https://foo.com/asset.css'));
    }

    public function testReplaceSiteUrlWithAssetsUrlWithSourceDifferentFromSiteUrl()
    {
        $this->assertSame('https://bar.com/asset.css', (new AssetsSubscriber('content_dir', 'https://foo.com', 'https://assets.com/assets/uuid'))->replaceSiteUrlWithAssetsUrl('https://bar.com/asset.css'));
    }

    public function testReplaceSiteUrlWithAssetsUrlWithSourceSameAsSiteUrl()
    {
        $this->assertSame('https://assets.com/assets/uuid/asset.css', (new AssetsSubscriber('content_dir', 'https://foo.com', 'https://assets.com/assets/uuid'))->replaceSiteUrlWithAssetsUrl('https://foo.com/asset.css'));
    }

    public function testReplaceSiteUrlWithAssetsUrlWithSourceSameAsUploadUrl()
    {
        $this->assertSame('https://foo.com/uploads/asset.css', (new AssetsSubscriber('content_dir', 'https://foo.com', 'https://foo.com/assets/uuid', '', 'https://foo.com/uploads'))->replaceSiteUrlWithAssetsUrl('https://foo.com/uploads/asset.css'));
    }

    /**
     * @dataProvider provideReplaceUrlsInContent
     */
    public function testReplaceUrlsInContentWithDifferentAssetsAndSiteDomain(string $filename)
    {
        list($content, $expected) = explode("\n--EXPECTED--\n", trim(file_get_contents(__DIR__.'/data/replace-urls-content/different-assets-and-site-domain/'.$filename)), 2);

        $this->assertSame($expected, (new AssetsSubscriber('content_dir', 'https://foo.com', 'https://assets.com/assets/uuid', '', 'https://assets.com/uploads'))->replaceUrlsInContent($content));
    }

    /**
     * @dataProvider provideReplaceUrlsInContent
     */
    public function testReplaceUrlsInContentWithSameAssetsAndSiteDomain(string $filename)
    {
        list($content, $expected) = explode("\n--EXPECTED--\n", trim(file_get_contents(__DIR__.'/data/replace-urls-content/same-assets-and-site-domain/'.$filename)), 2);

        $this->assertSame($expected, (new AssetsSubscriber('content_dir', 'https://foo.com', 'https://foo.com/assets/uuid', '', 'https://foo.com/uploads'))->replaceUrlsInContent($content));
    }

    public function testRewriteContentUrlDoesntKeepDirectoryBelowContentDir()
    {
        $this->assertSame('https://assets.com/assets/uuid/content_dir/test.php', (new AssetsSubscriber('content_dir', 'https://foo.com', 'https://assets.com/assets/uuid'))->rewriteContentUrl('https://foo.com/foo/directory/content_dir/test.php'));
    }

    public function testRewriteContentUrlUsesContentDirConstant()
    {
        $this->assertSame('https://assets.com/assets/uuid/app/test.php', (new AssetsSubscriber('app', 'https://foo.com', 'https://assets.com/assets/uuid'))->rewriteContentUrl('https://foo.com/foo/directory/app/test.php'));
    }

    public function testRewritePluginsUrlOnlyKeepsDirectoryBelowPlugins()
    {
        $this->assertSame('https://assets.com/assets/uuid/directory/plugins/test.php', (new AssetsSubscriber('content_dir', 'https://foo.com', 'https://assets.com/assets/uuid'))->rewritePluginsUrl('https://foo.com/foo/directory/plugins/test.php'));
    }
}
