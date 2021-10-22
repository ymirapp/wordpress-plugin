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
 * Object cache that persists data on a Redis cluster.
 */
class RedisClusterObjectCache extends AbstractRedisObjectCache
{
    /**
     * PhpRedis cluster client.
     *
     * @var \RedisCluster
     */
    private $redisClusterClient;

    /**
     * Constructor.
     */
    public function __construct(\RedisCluster $redisClusterClient, bool $isMultisite, string $prefix = '')
    {
        parent::__construct($isMultisite, $prefix);

        $this->redisClusterClient = $redisClusterClient;
    }

    /**
     * {@inheritdoc}
     */
    protected function runCommand(string $command, ...$arguments)
    {
        if ('ping' === $command) {
            $nodes = $this->redisClusterClient->_masters();

            $arguments = [reset($nodes)];
        }

        return $this->redisClusterClient->{$command}(...$arguments);
    }
}
