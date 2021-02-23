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
 * A WordPress Object Cache that persists data between executions.
 */
interface PersistentObjectCacheInterface extends ObjectCacheInterface
{
    /**
     * Checks if the persistent cache is available.
     */
    public function isAvailable(): bool;
}
