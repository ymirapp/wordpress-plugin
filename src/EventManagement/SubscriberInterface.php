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
 * A Subscriber knows what specific WordPress events it wants to listen to.
 *
 * When an EventManager adds a Subscriber, it gets all the WordPress events that
 * it wants to listen to. It then adds the subscriber as a listener for each of them.
 */
interface SubscriberInterface
{
    /**
     * Returns an array of events that this subscriber wants to listen to.
     *
     * The array key is the event name. The value can be:
     *
     *  * The method name
     *  * An array with the method name and priority
     *  * An array with the method name, priority and number of accepted arguments
     *
     * For instance:
     *
     *  * ['hook_name' => 'method_name']
     *  * ['hook_name' => ['method_name', $priority]]
     *  * ['hook_name' => ['method_name', $priority, $accepted_args]]
     *  * ['hook_name' => [['method_name_1', $priority_1, $accepted_args_1]], ['method_name_2', $priority_2, $accepted_args_2]]]
     */
    public static function getSubscribedEvents(): array;
}
