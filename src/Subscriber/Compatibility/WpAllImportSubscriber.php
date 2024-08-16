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

class WpAllImportSubscriber implements SubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'wp_all_import_is_php_allowed' => 'disablePhpExecution',
        ];
    }

    /**
     * Don't allow PHP execution during import.
     */
    public function disablePhpExecution(): bool
    {
        return false;
    }
}
