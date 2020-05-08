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

use PHPUnit\Framework\TestCase;
use Ymir\Plugin\Subscriber\AssetsSubscriber;

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

    public function testReplaceLoaderSourceWithEmptyAssetsUrl()
    {
        $subscriber = new AssetsSubscriber('https://foo.com');

        $this->assertSame('https://foo.com/asset.css', $subscriber->replaceLoaderSource('https://foo.com/asset.css'));
    }

    public function testReplaceLoaderSourceWithSourceDifferentFromSiteUrl()
    {
        $subscriber = new AssetsSubscriber('https://foo.com', 'https://assets.com');

        $this->assertSame('https://bar.com/asset.css', $subscriber->replaceLoaderSource('https://bar.com/asset.css'));
    }

    public function testReplaceLoaderSourceWithSourceSameAsSiteUrl()
    {
        $subscriber = new AssetsSubscriber('https://foo.com', 'https://assets.com');

        $this->assertSame('https://assets.com/asset.css', $subscriber->replaceLoaderSource('https://foo.com/asset.css'));
    }
}
