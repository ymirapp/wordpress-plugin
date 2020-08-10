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
 * Subscriber that handles the security headers sent back with all WordPress responses.
 */
class SecurityHeadersSubscriber implements SubscriberInterface
{
    /**
     * Standard security headers that would be handled by a web server.
     *
     * @var array
     */
    private const HEADERS = [
        'Referrer-Policy' => 'same-origin',
        'Strict-Transport-Security' => 'max-age=15768000; includeSubDomains; preload',
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-XSS-Protection' => '1; mode=block',
    ];

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'admin_init' => 'sendSecurityHeaders',
            'login_init' => 'sendSecurityHeaders',
            'wp_headers' => 'addSecurityHeaders',
        ];
    }

    /**
     * Add standard security headers to the list of headers being sent back by WordPress.
     */
    public function addSecurityHeaders(array $headers): array
    {
        return array_merge(self::HEADERS, $headers);
    }

    /**
     * Send security headers.
     */
    public function sendSecurityHeaders()
    {
        foreach (self::HEADERS as $header => $value) {
            header("{$header}: {$value}");
        }
    }
}
