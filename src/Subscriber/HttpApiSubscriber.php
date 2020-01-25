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
 * Subscriber for the WordPress HTTP API.
 */
class HttpApiSubscriber implements SubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'http_api_curl' => ['addPostfieldsForEmptyPutRequest', 10, 3],
        ];
    }

    /**
     * By default, the HTTP API doesn't let us add an empty postfield for PUT requests.
     */
    public function addPostfieldsForEmptyPutRequest($handle, array $request, string $url)
    {
        if (!is_resource($handle)
            || 'put' !== strtolower($request['method'])
            || !isset($request['body'])
            || '' !== trim($request['body'])
        ) {
            return;
        }

        curl_setopt($handle, CURLOPT_POSTFIELDS, $request['body']);
    }
}
