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

use Ymir\Plugin\Subscriber\EmailSubscriber;
use Ymir\Plugin\Support\Collection;
use Ymir\Plugin\Tests\Mock\EmailClientMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\Subscriber\EmailSubscriber
 */
class EmailSubscriberTest extends TestCase
{
    use EmailClientMockTrait;

    public function testDisplayAdminNoticeAddsNoticeIfEmailClientCannotSendEmails()
    {
        $client = $this->getEmailClientMock();

        $client->expects($this->once())
               ->method('canSendEmails')
               ->willReturn(false);

        $notices = (new EmailSubscriber($client, true, false))->displayAdminNotices(new Collection());

        $this->assertCount(1, $notices);
        $this->assertSame('error', $notices[0]['type']);
        $this->assertSame('Sending emails using SES is disabled because your AWS isn\'t approved to send emails. To learn how to approve your AWS account, check out <a href="https://docs.ymirapp.com/team-resources/email.html#getting-your-aws-account-approved-for-sending-email">the documentation</a>.', $notices[0]['message']);
    }

    public function testDisplayAdminNoticeAddsNoticeIfEmailClientCannotSendEmailsAndUsingVanityDomainIsTrue()
    {
        $client = $this->getEmailClientMock();

        $client->expects($this->once())
               ->method('canSendEmails')
               ->willReturn(false);

        $notices = (new EmailSubscriber($client, true, true))->displayAdminNotices(new Collection());

        $this->assertCount(1, $notices);
        $this->assertSame('error', $notices[0]['type']);
        $this->assertSame('Sending emails using SES is disabled because your AWS isn\'t approved to send emails. To learn how to approve your AWS account, check out <a href="https://docs.ymirapp.com/team-resources/email.html#getting-your-aws-account-approved-for-sending-email">the documentation</a>.', $notices[0]['message']);
    }

    public function testDisplayAdminNoticeAddsNoticeIfUsingVanityDomainIsTrue()
    {
        $client = $this->getEmailClientMock();

        $client->expects($this->once())
               ->method('canSendEmails')
               ->willReturn(true);

        $notices = (new EmailSubscriber($client, true, true))->displayAdminNotices(new Collection());

        $this->assertCount(1, $notices);
        $this->assertSame('warning', $notices[0]['type']);
        $this->assertSame('Sending emails using SES is disabled because the site is using a vanity domain. To learn how to map a domain to your environment, check out <a href="https://docs.ymirapp.com/guides/domain-mapping.html">this guide</a>.', $notices[0]['message']);
    }

    public function testDisplayAdminNoticeDoesNothingIfEmailSendingIsDisabled()
    {
        $this->assertCount(0, (new EmailSubscriber($this->getEmailClientMock(), false, true))->displayAdminNotices(new Collection()));
    }

    public function testDisplayAdminNoticeDoesNothingIfWeDontPassACollectionObject()
    {
        $this->assertNull((new EmailSubscriber($this->getEmailClientMock(), true, false))->displayAdminNotices(null));
    }

    public function testGetSubscribedEvents()
    {
        $callbacks = EmailSubscriber::getSubscribedEvents();

        foreach ($callbacks as $callback) {
            $this->assertTrue(method_exists(EmailSubscriber::class, is_array($callback) ? $callback[0] : $callback));
        }

        $subscribedEvents = [
            'ymir_admin_notices' => 'displayAdminNotices',
        ];

        $this->assertSame($subscribedEvents, $callbacks);
    }
}
