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
    public function testGetSubscribedEvents()
    {
        $callbacks = AssetsSubscriber::getSubscribedEvents();

        foreach ($callbacks as $callback) {
            $this->assertTrue(method_exists(AssetsSubscriber::class, is_array($callback) ? $callback[0] : $callback));
        }

        $subscribedEvents = [
            'script_loader_src' => 'replaceLoaderSource',
            'style_loader_src' => 'replaceLoaderSource',
        ];

        $this->assertSame($subscribedEvents, $callbacks);
    }

    public function testReplaceLoaderSourceAddsWpWhenMissingWithBedrockProjectWithSourceSameAsSiteUrl()
    {
        $this->assertSame('https://assets.com/wp/asset.css', (new AssetsSubscriber('https://foo.com', 'https://assets.com', 'bedrock'))->replaceLoaderSource('https://foo.com/asset.css'));
    }

    public function testReplaceLoaderSourceDoesntAddWpWithBedrockProjectWithAppUrl()
    {
        $this->assertSame('https://assets.com/app/asset.css', (new AssetsSubscriber('https://foo.com', 'https://assets.com', 'bedrock'))->replaceLoaderSource('https://foo.com/app/asset.css'));
    }

    public function testReplaceLoaderSourceDoesntAddWpWithBedrockProjectWithSourceSameAsSiteUrl()
    {
        $this->assertSame('https://assets.com/wp/asset.css', (new AssetsSubscriber('https://foo.com', 'https://assets.com', 'bedrock'))->replaceLoaderSource('https://foo.com/wp/asset.css'));
    }

    public function testReplaceLoaderSourceWithEmptyAssetsUrl()
    {
        $this->assertSame('https://foo.com/asset.css', (new AssetsSubscriber('https://foo.com'))->replaceLoaderSource('https://foo.com/asset.css'));
    }

    public function testReplaceLoaderSourceWithSourceDifferentFromSiteUrl()
    {
        $this->assertSame('https://bar.com/asset.css', (new AssetsSubscriber('https://foo.com', 'https://assets.com'))->replaceLoaderSource('https://bar.com/asset.css'));
    }

    public function testReplaceLoaderSourceWithSourceSameAsSiteUrl()
    {
        $this->assertSame('https://assets.com/asset.css', (new AssetsSubscriber('https://foo.com', 'https://assets.com'))->replaceLoaderSource('https://foo.com/asset.css'));
    }
}
