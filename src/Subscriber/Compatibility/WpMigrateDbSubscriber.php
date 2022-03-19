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

namespace Ymir\Plugin\Subscriber\Compatibility;

use Ymir\Plugin\EventManagement\SubscriberInterface;

/**
 * Subscriber that handles WP Migrate DB compatibility.
 */
class WpMigrateDbSubscriber implements SubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'wpmdb_transfers_temp_dir' => 'changeTempDirectory',
        ];
    }

    /**
     * Change the temp directory.
     */
    public function changeTempDirectory(): string
    {
        return '/tmp/';
    }
}
