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
 * The event manager manages events using the WordPress plugin API.
 */
class EventManager
{
    /**
     * Adds a callback to a specific hook of the WordPress plugin API.
     *
     * @uses add_filter()
     */
    public function addCallback(string $hookName, callable $callback, int $priority = 10, int $acceptedArgs = 1)
    {
        add_filter($hookName, $callback, $priority, $acceptedArgs);
    }

    /**
     * Add an event subscriber.
     *
     * The event manager registers all the hooks that the given subscriber
     * wants to register with the WordPress Plugin API.
     */
    public function addSubscriber(SubscriberInterface $subscriber)
    {
        if ($subscriber instanceof EventManagerAwareInterface) {
            $subscriber->setEventManager($this);
        }

        foreach ($subscriber->getSubscribedEvents() as $hookName => $parameters) {
            $this->addSubscriberCallback($subscriber, $hookName, $parameters);
        }
    }

    /**
     * Executes all the functions registered with the hook with the given name.
     *
     * @uses do_action_ref_array()
     */
    public function execute(string $hookName, $argument = null)
    {
        // Remove $hook_name from the arguments
        $arguments = array_slice(func_get_args(), 1);

        // We use "do_action_ref_array" so that we can mock the function. This
        // isn't possible if we use "call_user_func_array" with "do_action".
        do_action_ref_array($hookName, $arguments);
    }

    /**
     * Filters the given value by applying all the changes associated with the hook with the given name to
     * the given value. Returns the filtered value.
     *
     * @uses apply_filters_ref_array()
     */
    public function filter(string $hookName, $value)
    {
        // Remove $hook_name from the arguments
        $arguments = array_slice(func_get_args(), 1);

        // We use "apply_filters_ref_array" so that we can mock the function. This
        // isn't possible if we use "call_user_func_array" with "apply_filters".
        return apply_filters_ref_array($hookName, $arguments);
    }

    /**
     * Get the name of the hook that WordPress plugin API is executing. Returns
     * false if it isn't executing a hook.
     *
     * @uses current_filter()
     */
    public function getCurrentHook(): string
    {
        return current_filter();
    }

    /**
     * Checks the WordPress plugin API to see if the given hook has
     * the given callback. The priority of the callback will be returned
     * or false. If no callback is given will return true or false if
     * there's any callbacks registered to the hook.
     *
     * @uses has_filter()
     */
    public function hasCallback(string $hookName, $callback = false)
    {
        return has_filter($hookName, $callback);
    }

    /**
     * Removes the given callback from the given hook. The WordPress plugin API only
     * removes the hook if the callback and priority match a registered hook.
     *
     * @uses remove_filter()
     */
    public function removeCallback(string $hookName, callable $callback, int $priority = 10): bool
    {
        return remove_filter($hookName, $callback, $priority);
    }

    /**
     * Remove an event subscriber.
     *
     * The event manager removes all the hooks that the given subscriber
     * wants to register with the WordPress Plugin API.
     */
    public function removeSubscriber(SubscriberInterface $subscriber)
    {
        foreach ($subscriber->getSubscribedEvents() as $hookName => $parameters) {
            $this->removeSubscriberCallback($subscriber, $hookName, $parameters);
        }
    }

    /**
     * Adds the given subscriber's callback to a specific hook
     * of the WordPress plugin API.
     */
    private function addSubscriberCallback(SubscriberInterface $subscriber, string $hookName, $parameters)
    {
        if (is_string($parameters)) {
            $this->addCallback($hookName, [$subscriber, $parameters]);
        } elseif (is_array($parameters) && isset($parameters[0])) {
            $this->addCallback($hookName, [$subscriber, $parameters[0]], isset($parameters[1]) ? $parameters[1] : 10, isset($parameters[2]) ? $parameters[2] : 1);
        }
    }

    /**
     * Removes the given subscriber's callback to a specific hook
     * of the WordPress plugin API.
     */
    private function removeSubscriberCallback(SubscriberInterface $subscriber, $hookName, $parameters)
    {
        if (is_string($parameters)) {
            $this->removeCallback($hookName, [$subscriber, $parameters]);
        } elseif (is_array($parameters) && isset($parameters[0])) {
            $this->removeCallback($hookName, [$subscriber, $parameters[0]], isset($parameters[1]) ? $parameters[1] : 10);
        }
    }
}
