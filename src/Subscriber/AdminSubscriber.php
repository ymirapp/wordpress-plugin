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

use Ymir\Plugin\EventManagement\AbstractEventManagerAwareSubscriber;
use Ymir\Plugin\Support\Collection;

/**
 * Subscriber that handles interactions with the WordPress admin.
 */
class AdminSubscriber extends AbstractEventManagerAwareSubscriber
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'admin_notices' => 'displayAdminNotices',
        ];
    }

    /**
     * Display all admin notices.
     */
    public function displayAdminNotices()
    {
        $notices = $this->eventManager->filter('ymir_admin_notices', new Collection());

        if (!$notices instanceof Collection) {
            return;
        }

        $notices->map(function ($notice) {
            return is_string($notice) ? ['message' => $notice] : $notice;
        })->filter(function ($notice) {
            return is_array($notice) && !empty($notice['message']);
        })->each(function (array $notice) {
            $message = $notice['message'] ?? '';
            $type = strtolower($notice['type'] ?? 'info');

            if (!in_array($type, ['error', 'info', 'success', 'warning'])) {
                $type = 'info';
            }

            printf('<div class="notice notice-%s %s"><p><strong>Ymir:</strong> %s</p></div>', $type, !empty($notice['dismissible']) ? 'is-dismissible' : '', $message);
        });
    }
}
