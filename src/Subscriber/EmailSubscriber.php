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

namespace Ymir\Plugin\Subscriber;

use Ymir\Plugin\Email\EmailClientInterface;
use Ymir\Plugin\EventManagement\SubscriberInterface;
use Ymir\Plugin\Support\Collection;

/**
 * Subscriber that handles the email integration.
 */
class EmailSubscriber implements SubscriberInterface
{
    /**
     * The email client.
     *
     * @var EmailClientInterface
     */
    private $client;

    /**
     * Flag whether email sending is enabled or not.
     *
     * @var bool
     */
    private $isEmailSendingEnabled;

    /**
     * Flag whether the WordPress site is using a vanity domain or not.
     *
     * @var bool
     */
    private $usingVanityDomain;

    /**
     * Constructor.
     */
    public function __construct(EmailClientInterface $client, bool $isEmailSendingEnabled, bool $usingVanityDomain)
    {
        $this->client = $client;
        $this->isEmailSendingEnabled = $isEmailSendingEnabled;
        $this->usingVanityDomain = $usingVanityDomain;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'ymir_admin_notices' => 'displayAdminNotices',
        ];
    }

    /**
     * Display admin notices related to email integration.
     */
    public function displayAdminNotices($notices)
    {
        if (!$this->isEmailSendingEnabled || !$notices instanceof Collection) {
            return $notices;
        }

        if (!$this->client->canSendEmails()) {
            $notices[] = [
                'message' => 'Sending emails using SES is disabled because your AWS isn\'t approved to send emails. To learn how to approve your AWS account, check out <a href="https://docs.ymirapp.com/team-resources/email.html#getting-your-aws-account-approved-for-sending-email">the documentation</a>.',
                'type' => 'error',
            ];
        } elseif ($this->usingVanityDomain) {
            $notices[] = [
                'message' => 'Sending emails using SES is disabled because the site is using a vanity domain. To learn how to map a domain to your environment, check out <a href="https://docs.ymirapp.com/guides/domain-mapping.html">this guide</a>.',
                'type' => 'warning',
            ];
        }

        return $notices;
    }
}
