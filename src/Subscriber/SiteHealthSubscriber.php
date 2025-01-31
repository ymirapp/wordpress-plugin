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

use Ymir\Plugin\EventManagement\SubscriberInterface;

/**
 * Subscriber that manages the integration with the WordPress site health system.
 */
class SiteHealthSubscriber implements SubscriberInterface
{
    /**
     * The Ymir environment that the WordPress site is running in.
     *
     * @var string
     */
    private $environment;

    /**
     * Constructor.
     */
    public function __construct(string $environment)
    {
        $this->environment = $environment;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'site_status_tests' => 'adjustSiteHealthTests',
        ];
    }

    /**
     * Adjust the site health tests that are run on an Ymir site.
     */
    public function adjustSiteHealthTests(array $tests): array
    {
        if (!empty($this->environment)) {
            unset($tests['async']['background_updates'], $tests['direct']['update_temp_backup_writable']);
        }

        return $tests;
    }
}
