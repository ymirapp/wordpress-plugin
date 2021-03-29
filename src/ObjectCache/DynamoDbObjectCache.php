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

use Ymir\Plugin\CloudProvider\Aws\DynamoDbClient;
use Ymir\Plugin\Support\Collection;

/**
 * Object cache that persists data on DynamoDB.
 */
class DynamoDbObjectCache extends AbstractPersistentObjectCache
{
    /**
     * The client used to interact with DynamoDB.
     *
     * @var DynamoDbClient
     */
    private $dynamoDbClient;

    /**
     * The table used by the object cache.
     *
     * @var string
     */
    private $table;

    /**
     * Constructor.
     */
    public function __construct(DynamoDbClient $dynamoDbClient, bool $isMultisite, string $table, string $prefix = '')
    {
        parent::__construct($isMultisite, $prefix);

        $this->dynamoDbClient = $dynamoDbClient;
        $this->table = $table;
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable(): bool
    {
        try {
            $this->getValue('test');

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
        $this->dynamoDbClient->deleteItem([
            'TableName' => $this->table,
            'Key' => [
                'key' => ['S' => $key],
            ],
        ]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function flushPersistentCache(): bool
    {
        // We can't flush a DynamoDB table. Instead, we need to delete and recreate the table.
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function getValuesFromPersistentCache($keys)
    {
        return is_string($keys) ? $this->getValue($keys) : $this->getValues((array) $keys);
    }

    /**
     * {@inheritdoc}
     */
    protected function storeValueInPersistentCache(string $key, $value, int $expire = 0, int $mode = 0): bool
    {
        $arguments = [
            'TableName' => $this->table,
            'Item' => [
                'key' => ['S' => $key],
                'value' => ['S' => is_numeric($value) ? (string) $value : serialize($value)],
            ],
        ];

        if ($expire > 0) {
            $arguments['Item']['expires_at'] = ['N' => $expire];
        }

        if (self::MODE_ADD === $mode) {
            $arguments['ConditionExpression'] = 'attribute_not_exists(#key) OR #expires_at < :now';
        } elseif (self::MODE_REPLACE === $mode) {
            $arguments['ConditionExpression'] = 'attribute_exists(#key) AND #expires_at > :now';
        }

        if (0 !== $mode) {
            $arguments['ExpressionAttributeNames'] = [
                '#key' => 'key',
                '#expires_at' => 'expires_at',
            ];
            $arguments['ExpressionAttributeValues'] = [
                ':now' => ['N' => (string) time()],
            ];
        }

        $this->dynamoDbClient->putItem($arguments);

        return true;
    }

    /**
     * Get the value stored in DynamoDB for the given key.
     */
    private function getValue(string $key)
    {
        $start = round(microtime(true) * 1000);

        $response = $this->dynamoDbClient->getItem([
            'TableName' => $this->table,
            'ConsistentRead' => false,
            'Key' => [
                'key' => ['S' => $key],
            ],
        ]);

        ++$this->requests;
        $this->requestTime += (round(microtime(true) * 1000) - $start);

        if (!isset($response['Item']['value']['S']) || $this->isExpired($response['Item'])) {
            return false;
        }

        return $this->unserialize($response['Item']['value']['S']);
    }

    /**
     * Get the values stored in DynamoDB for the given keys.
     */
    private function getValues(array $keys): array
    {
        return (new Collection($keys))->chunk(100)->map(function (Collection $chunkedKeys) {
            $start = round(microtime(true) * 1000);

            $response = $this->dynamoDbClient->batchGetItem([
                'RequestItems' => [
                    $this->table => [
                        'ConsistentRead' => false,
                        'Keys' => $chunkedKeys->map(function (string $key) {
                            return ['key' => ['S' => $key]];
                        })->values()->all(),
                    ],
                ],
            ]);

            ++$this->requests;
            $this->requestTime += (round(microtime(true) * 1000) - $start);

            $current = time();

            return isset($response['Responses'][$this->table]) ? (new Collection($response['Responses'][$this->table]))->filter(function (array $item) use ($current) {
                return !$this->isExpired($item, $current);
            })->mapWithKeys(function (array $item) {
                return [$item['key']['S'] => $this->unserialize($item['value']['S'])];
            })->all() : [];
        })->collapse()->all();
    }

    /**
     * Determine if the given item is expired.
     */
    private function isExpired(array $item, ?int $time = null): bool
    {
        if (null === $time) {
            $time = time();
        }

        return isset($item['expires_at']['N']) && $item['expires_at']['N'] <= $time;
    }

    /**
     * Unserialize the value.
     */
    private function unserialize(string $value)
    {
        if (false !== filter_var($value, FILTER_VALIDATE_INT)) {
            return (int) $value;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return unserialize($value);
    }
}
