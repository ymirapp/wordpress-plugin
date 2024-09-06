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
use Ymir\Plugin\Support\Collection;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\Subscriber\DisallowIndexingSubscriber
 */
class DisallowIndexingSubscriberTest extends TestCase
{
    public function testDisplayAdminNoticeAddsNoticeIfUsingVanityDomainIsTrue()
    {
        $notices = (new DisallowIndexingSubscriber(true))->displayAdminNotice(new Collection());

        $this->assertCount(1, $notices);
        $this->assertSame('warning', $notices[0]['type']);
        $this->assertSame('Search engine indexing is disallowed when using a vanity domain. To learn how to map a domain to your environment, check out <a href="https://docs.ymirapp.com/guides/domain-mapping.html">this guide</a>.', $notices[0]['message']);
    }

    public function testDisplayAdminNoticeDoesNotAddNoticeIfUsingVanityDomainIsFalse()
    {
        $this->assertCount(0, (new DisallowIndexingSubscriber(false))->displayAdminNotice(new Collection()));
    }

    public function testDisplayAdminNoticeDoesNothingIfWeDontPassACollectionObject()
    {
        $this->assertNull((new DisallowIndexingSubscriber(false))->displayAdminNotice(null));
    }

    public function testFilterBlogPublicReturnsSameValueIfUsingVanityDomainIsFalse()
    {
        $this->assertSame('value', (new DisallowIndexingSubscriber(false))->filterBlogPublic('value'));
    }

    public function testFilterBlogPublicReturnsZeroValueIfUsingVanityDomainIsTrue()
    {
        $this->assertSame(0, (new DisallowIndexingSubscriber(true))->filterBlogPublic('value'));
    }
}
