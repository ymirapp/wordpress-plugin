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

namespace Ymir\Plugin\PageCache;

/**
 * A client for interacting with the content delivery network handling page caching.
 */
interface ContentDeliveryNetworkPageCacheClientInterface
{
    /**
     * Clear the entire page cache.
     */
    public function clearAll();

    /**
     * Clear the given URL from the page cache.
     */
    public function clearUrl(string $url);

    /**
     * Send request to content delivery network to clear all requested URLs from its cache.
     */
    public function sendClearRequest();
}
