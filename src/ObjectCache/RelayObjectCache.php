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

use Relay\Event;
use Relay\Relay;

class RelayObjectCache extends AbstractRedisObjectCache
{
    /**
     * Relay Redis client.
     *
     * @var Relay
     */
    private $relayClient;

    /**
     * Constructor.
     */
    public function __construct(Relay $relayClient, bool $isMultisite, string $prefix = '')
    {
        parent::__construct($isMultisite, $prefix);

        $this->relayClient = $relayClient;

        $this->relayClient->onFlushed(function () {
            $this->onFlushed();
        });
        $this->relayClient->onInvalidated(function (Event $event) {
            $this->onInvalidated($event);
        }, !empty($prefix) ? $prefix.':*' : null);
    }

    /**
     * {@inheritdoc}
     */
    public function add(string $group, string $key, $value, int $expire = 0): bool
    {
        $this->relayClient->dispatchEvents();

        return parent::add($group, $key, $value, $expire);
    }

    /**
     * {@inheritdoc}
     */
    public function decrement(string $group, string $key, int $offset = 1)
    {
        $this->relayClient->dispatchEvents();

        parent::decrement($group, $key, $offset);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $group, string $key): bool
    {
        $this->relayClient->dispatchEvents();

        return parent::delete($group, $key);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $group, string $key, bool $force = false, &$found = null)
    {
        $this->relayClient->dispatchEvents();

        return parent::get($group, $key, $force, $found);
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple(string $group, array $keys, bool $force = false): array
    {
        $this->relayClient->dispatchEvents();

        return parent::getMultiple($group, $keys, $force);
    }

    /**
     * {@inheritdoc}
     */
    public function increment(string $group, string $key, int $offset = 1)
    {
        $this->relayClient->dispatchEvents();

        parent::increment($group, $key, $offset);
    }

    /**
     * {@inheritdoc}
     */
    public function replace(string $group, string $key, $value, int $expire = 0): bool
    {
        $this->relayClient->dispatchEvents();

        return parent::replace($group, $key, $value, $expire);
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $group, string $key, $value, int $expire = 0): bool
    {
        $this->relayClient->dispatchEvents();

        return parent::set($group, $key, $value, $expire);
    }

    /**
     * {@inheritdoc}
     */
    protected function runCommand(string $command, ...$arguments)
    {
        return $this->relayClient->{$command}(...$arguments);
    }

    /**
     * Callback for the `flushed` event.
     */
    private function onFlushed()
    {
        $this->flushMemory();
    }

    /**
     * Callback for the `invalidated` event.
     */
    private function onInvalidated(Event $event)
    {
        $this->deleteFromMemory((string) $event->key);
    }
}
