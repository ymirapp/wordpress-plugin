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

namespace Ymir\Plugin\EventManagement;

/**
 * Used by classes that want to access the event manager via setter injection.
 */
interface EventManagerAwareInterface
{
    /**
     * Set the event manager.
     */
    public function setEventManager(EventManager $eventManager);
}
