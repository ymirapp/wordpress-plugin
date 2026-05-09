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

    public function provideRootsProjectTypes(): array
    {
        return [
            ['bedrock'],
            ['radicle'],
        ];
    }

    public function provideRootsProjectTypesAndStartingPaths(): array
    {
        return [
            ['bedrock', '/app/'],
            ['bedrock', '/wp/'],
            ['radicle', '/build/'],
            ['radicle', '/content/'],
            ['radicle', '/dist/'],
            ['radicle', '/wp/'],
        ];
    }

    public function testAddAssetsUrlToDnsPrefetchDoesntAddAssetsUrlWhenDomainDifferentFromSiteUrl(): void
    {
        $this->assertSame(['https://assets.com/assets/uuid'], (new AssetsSubscriber('content_dir', 'https://foo.com', 'https://assets.com/assets/uuid'))->addAssetsUrlToDnsPrefetch([], 'dns-prefetch'));
    }

    public function testAddAssetsUrlToDnsPrefetchDoesntAddAssetsWhenSameDomainAsSiteUrl(): void
    {
        $this->assertSame([], (new AssetsSubscriber('content_dir', 'https://foo.com', 'https://foo.com'))->addAssetsUrlToDnsPrefetch([], 'dns-prefetch'));
    }

    public function testAddAssetsUrlToDnsPrefetchWhenNoAssetsUrl(): void
    {
        $this->assertSame([], (new AssetsSubscriber('content_dir', 'https://foo.com'))->addAssetsUrlToDnsPrefetch([], 'foo'));
    }

    public function testAddAssetsUrlToDnsPrefetchWhenWrongTypeAndDifferentAssetsDomain(): void
    {
        $this->assertSame([], (new AssetsSubscriber('content_dir', 'https://foo.com', 'https://assets.com/assets/uuid'))->addAssetsUrlToDnsPrefetch([], 'foo'));
    }

    public function testGetSubscribedEvents(): void
    {
        $callbacks = AssetsSubscriber::getSubscribedEvents();

        foreach ($callbacks as $callback) {
            $this->assertTrue(method_exists(AssetsSubscriber::class, is_array($callback) ? $callback[0] : $callback));
        }

        $subscribedEvents = [
            'content_url' => 'rewriteContentUrl',
            'includes_url' => 'rewriteIncludesUrl',
            'plugins_url' => 'rewritePluginsUrl',
            'script_loader_src' => 'rewriteEnqueuedUrl',
            'style_loader_src' => 'rewriteEnqueuedUrl',
            'the_content' => ['replaceUrlsInContent', 99999],
            'wp_resource_hints' => ['addAssetsUrlToDnsPrefetch', 10, 2],
        ];

        $this->assertSame($subscribedEvents, $callbacks);
    }

    /**
     * @dataProvider provideReplaceUrlsInContent
     */
    public function testReplaceUrlsInContentWithDifferentAssetsAndSiteDomain(string $filename): void
    {
        list($content, $expected) = explode("\n--EXPECTED--\n", trim(file_get_contents(__DIR__.'/data/replace-urls-content/different-assets-and-site-domain/'.$filename)), 2);

        $this->assertSame($expected, (new AssetsSubscriber('content_dir', 'https://foo.com', 'https://assets.com/assets/uuid', '', 'https://assets.com/uploads'))->replaceUrlsInContent($content));
    }

    /**
     * @dataProvider provideReplaceUrlsInContent
     */
    public function testReplaceUrlsInContentWithSameAssetsAndSiteDomain(string $filename): void
    {
        list($content, $expected) = explode("\n--EXPECTED--\n", trim(file_get_contents(__DIR__.'/data/replace-urls-content/same-assets-and-site-domain/'.$filename)), 2);

        $this->assertSame($expected, (new AssetsSubscriber('content_dir', 'https://foo.com', 'https://foo.com/assets/uuid', '', 'https://foo.com/uploads'))->replaceUrlsInContent($content));
    }

    public function testRewriteContentUrlDoesntKeepDirectoryBelowContentDir(): void
    {
        $this->assertSame('https://assets.com/assets/uuid/content_dir/test.php', (new AssetsSubscriber('content_dir', 'https://foo.com', 'https://assets.com/assets/uuid'))->rewriteContentUrl('https://foo.com/foo/directory/content_dir/test.php'));
    }

    public function testRewriteContentUrlUsesContentDirConstant(): void
    {
        $this->assertSame('https://assets.com/assets/uuid/app/test.php', (new AssetsSubscriber('app', 'https://foo.com', 'https://assets.com/assets/uuid'))->rewriteContentUrl('https://foo.com/foo/directory/app/test.php'));
    }

    /**
     * @dataProvider provideRootsProjectTypes
     */
    public function testRewriteEnqueuedUrlAddsWpWhenMissingWithRootsProjectWithSourceSameAsSiteUrl(string $projectType): void
    {
        $this->assertSame('https://assets.com/assets/uuid/wp/asset.css', (new AssetsSubscriber('content_dir', 'https://foo.com', 'https://assets.com/assets/uuid', $projectType, 'https://assets.com/uploads'))->rewriteEnqueuedUrl('https://foo.com/asset.css'));
    }

    /**
     * @dataProvider provideRootsProjectTypesAndStartingPaths
     */
    public function testRewriteEnqueuedUrlDoesntAddWpWithRootsProjectAndStartingPath(string $projectType, string $startingPath): void
    {
        $this->assertSame(sprintf('https://assets.com/assets/uuid%sasset.css', $startingPath), (new AssetsSubscriber('content_dir', 'https://foo.com', 'https://assets.com/assets/uuid', $projectType, 'https://assets.com/uploads'))->rewriteEnqueuedUrl(sprintf('https://foo.com%sasset.css', $startingPath)));
    }

    /**
     * @dataProvider provideRootsProjectTypes
     */
    public function testRewriteEnqueuedUrlDoesntAddWpWithRootsProjectWithSourceSameAsSiteUrl(string $projectType): void
    {
        $this->assertSame('https://assets.com/assets/uuid/wp/asset.css', (new AssetsSubscriber('content_dir', 'https://foo.com', 'https://assets.com/assets/uuid', $projectType, 'https://assets.com/uploads'))->rewriteEnqueuedUrl('https://foo.com/wp/asset.css'));
    }

    public function testRewriteEnqueuedUrlDoesntRemoveDoubleSlashesWhenUrlStartsWithDoubleSlash(): void
    {
        $this->assertSame('//uploads.com/asset.css', (new AssetsSubscriber('content_dir', 'https://foo.com', 'https://foo.com/assets/uuid', '', 'https://foo.com/uploads'))->rewriteEnqueuedUrl('//uploads.com//asset.css'));
    }

    public function testRewriteEnqueuedUrlRemovesDoubleSlashesWithSiteUrl(): void
    {
        $this->assertSame('https://assets.com/assets/uuid/asset.css', (new AssetsSubscriber('content_dir', 'https://foo.com', 'https://assets.com/assets/uuid'))->rewriteEnqueuedUrl('https://foo.com//asset.css'));
    }

    public function testRewriteEnqueuedUrlRemovesDoubleSlashesWithUploadUrl(): void
    {
        $this->assertSame('https://foo.com/uploads/asset.css', (new AssetsSubscriber('content_dir', 'https://foo.com', 'https://foo.com/assets/uuid', '', 'https://foo.com/uploads'))->rewriteEnqueuedUrl('https://foo.com//uploads//asset.css'));
    }

    public function testRewriteEnqueuedUrlWithEmptyAssetsUrl(): void
    {
        $this->assertSame('https://foo.com/asset.css', (new AssetsSubscriber('content_dir', 'https://foo.com'))->rewriteEnqueuedUrl('https://foo.com/asset.css'));
    }

    public function testRewriteEnqueuedUrlWithEnqueuedUrlUsingDifferentAssetsUrlAndDifferentAssetDomain(): void
    {
        $this->assertSame('https://assets.com/assets/new_uuid/asset.css', (new AssetsSubscriber('content_dir', 'https://foo.com', 'https://assets.com/assets/new_uuid'))->rewriteEnqueuedUrl('https://assets.com/assets/old_uuid/asset.css'));
    }

    public function testRewriteEnqueuedUrlWithEnqueuedUrlUsingDifferentAssetsUrlAndSameAssetDomain(): void
    {
        $this->assertSame('https://foo.com/assets/new_uuid/asset.css', (new AssetsSubscriber('content_dir', 'https://foo.com', 'https://foo.com/assets/new_uuid'))->rewriteEnqueuedUrl('https://foo.com/assets/old_uuid/asset.css'));
    }

    public function testRewriteEnqueuedUrlWithSourceDifferentFromSiteUrl(): void
    {
        $this->assertSame('https://bar.com/asset.css', (new AssetsSubscriber('content_dir', 'https://foo.com', 'https://assets.com/assets/uuid'))->rewriteEnqueuedUrl('https://bar.com/asset.css'));
    }

    public function testRewriteEnqueuedUrlWithSourceSameAsSiteUrl(): void
    {
        $this->assertSame('https://assets.com/assets/uuid/asset.css', (new AssetsSubscriber('content_dir', 'https://foo.com', 'https://assets.com/assets/uuid'))->rewriteEnqueuedUrl('https://foo.com/asset.css'));
    }

    public function testRewriteEnqueuedUrlWithSourceSameAsUploadUrl(): void
    {
        $this->assertSame('https://foo.com/uploads/asset.css', (new AssetsSubscriber('content_dir', 'https://foo.com', 'https://foo.com/assets/uuid', '', 'https://foo.com/uploads'))->rewriteEnqueuedUrl('https://foo.com/uploads/asset.css'));
    }

    /**
     * @dataProvider provideRootsProjectTypes
     */
    public function testRewriteIncludesUrlWithRootsProjectIncludesDirectory(string $projectType): void
    {
        $this->assertSame('https://assets.com/assets/uuid/wp/wp-includes/js/script.min.js', (new AssetsSubscriber('content_dir', 'https://foo.com', 'https://assets.com/assets/uuid', $projectType))->rewriteIncludesUrl('https://foo.com/wp/wp-includes/js/script.min.js'));
    }

    public function testRewriteIncludesUrlWithStandardIncludesDirectory(): void
    {
        $this->assertSame('https://assets.com/assets/uuid/wp-includes/js/script.min.js', (new AssetsSubscriber('content_dir', 'https://foo.com', 'https://assets.com/assets/uuid'))->rewriteIncludesUrl('https://foo.com/wp-includes/js/script.min.js'));
    }

    public function testRewriteIncludesUrlWithSubdirectoryMultisite(): void
    {
        $this->assertSame('https://foo.com/test/assets/uuid/wp-includes/js/script.min.js', (new AssetsSubscriber('content_dir', 'https://foo.com/test', 'https://foo.com/test/assets/uuid'))->rewriteIncludesUrl('https://foo.com/test/wp-includes/js/script.min.js'));
    }

    public function testRewritePluginsUrlOnlyKeepsDirectoryBelowPlugins(): void
    {
        $this->assertSame('https://assets.com/assets/uuid/directory/plugins/test.php', (new AssetsSubscriber('content_dir', 'https://foo.com', 'https://assets.com/assets/uuid'))->rewritePluginsUrl('https://foo.com/foo/directory/plugins/test.php'));
    }
}
