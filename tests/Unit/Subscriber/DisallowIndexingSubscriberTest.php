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

use Ymir\Plugin\Subscriber\DisallowIndexingSubscriber;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\Subscriber\DisallowIndexingSubscriber
 */
class DisallowIndexingSubscriberTest extends TestCase
{
    public function testFilterBlogPublicReturnsSameValueIfIndexingAllowed()
    {
        $this->assertSame('value', (new DisallowIndexingSubscriber('https://foo.com'))->filterBlogPublic('value'));
    }

    public function testFilterBlogPublicReturnsZeroValueIfSiteUrlUsesVanityDomain()
    {
        $this->assertSame(0, (new DisallowIndexingSubscriber('https://subdomain.ymirsites.com'))->filterBlogPublic('value'));
    }
}
