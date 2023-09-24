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

namespace Ymir\Plugin\Http;

/**
 * HTTP client that mirrors the WordPress HTTP API.
 *
 * @see https://developer.wordpress.org/plugins/http-api
 */
interface ClientInterface
{
    /**
     * Send an HTTP request.
     *
     * @see WP_Http::request
     */
    public function request(string $url, array $options = []): array;
}
