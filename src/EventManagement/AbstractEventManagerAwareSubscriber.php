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
 * Base class for an EventManager aware subscriber.
 */
abstract class AbstractEventManagerAwareSubscriber implements EventManagerAwareInterface, SubscriberInterface
{
    /**
     * The plugin event manager.
     *
     * @var EventManager
     */
    protected $eventManager;

    /**
     * {@inheritdoc}
     */
    public function setEventManager(EventManager $eventManager)
    {
        $this->eventManager = $eventManager;
    }
}
