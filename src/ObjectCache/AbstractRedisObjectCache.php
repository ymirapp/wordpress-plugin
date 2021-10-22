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

use Ymir\Plugin\Support\Collection;

/**
 * Base class for object caches that persists data to Redis.
 */
abstract class AbstractRedisObjectCache extends AbstractPersistentObjectCache
{
    /**
     * {@inheritdoc}
     */
    public function isAvailable(): bool
    {
        try {
            $this->runCommand('ping');

            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function deleteValueFromPersistentCache(string $key): bool
    {
        try {
            return (bool) $this->runCommand('del', $key);
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function flushPersistentCache(): bool
    {
        try {
            return $this->runCommand('flushDB', true);
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getValuesFromPersistentCache($keys)
    {
        $start = round(microtime(true) * 1000);

        $values = is_array($keys) ? $this->getValues($keys) : $this->getValue($keys);

        ++$this->requests;
        $this->requestTime += round((microtime(true) * 1000) - $start, 2);

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    protected function storeValueInPersistentCache(string $key, $value, int $expire = 0, int $mode = 0): bool
    {
        $options = [];

        if (self::MODE_ADD === $mode) {
            $options[] = 'nx';
        } elseif (self::MODE_REPLACE === $mode) {
            $options[] = 'xx';
        }

        if ($expire > 0) {
            $options['ex'] = $expire;
        }

        try {
            return $this->runCommand('set', $key, $value, $options);
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * Run the specified Redis command.
     */
    abstract protected function runCommand(string $command, ...$arguments);

    /**
     * Get the value stored in Redis for the given key.
     */
    private function getValue(string $key)
    {
        try {
            return $this->runCommand('get', $key);
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * Get the values stored in Redis for all the given keys.
     */
    private function getValues(array $keys): array
    {
        try {
            $values = $this->runCommand('mget', $keys);
        } catch (\Exception $exception) {
            $values = [];
        }

        return (new Collection($keys))->mapWithKeys(function (string $key, int $index) use ($values) {
            return [$key => $values[$index] ?? false];
        })->filter()->all();
    }
}
