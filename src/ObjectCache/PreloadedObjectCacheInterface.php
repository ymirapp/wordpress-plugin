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

namespace Ymir\Plugin\ObjectCache;

/**
 * A WordPress Object Cache that preloads cache keys based on.
 */
interface PreloadedObjectCacheInterface extends ObjectCacheInterface
{
    /**
     * Load the object cache with all the keys used by the previous request.
     */
    public function load();
}
