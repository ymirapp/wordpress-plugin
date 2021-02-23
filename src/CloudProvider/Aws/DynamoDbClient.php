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

namespace Ymir\Plugin\CloudProvider\Aws;

/**
 * The client for AWS DynamoDB API.
 */
class DynamoDbClient extends AbstractClient
{
    /**
     * Get one or more items from one or more tables.
     */
    public function batchGetItem(array $arguments): array
    {
        $response = $this->perform('BatchGetItem', $arguments);

        if (200 !== $this->parseResponseStatusCode($response)) {
            throw new \RuntimeException('Unable to get cache items');
        }

        $items = json_decode($response['body'], true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \RuntimeException('Unable to decode response from DynamoDB API');
        }

        return $items;
    }

    /**
     * Deletes a single item in a table by primary key.
     */
    public function deleteItem(array $arguments)
    {
        $response = $this->perform('DeleteItem', $arguments);

        if (200 !== $this->parseResponseStatusCode($response)) {
            throw new \RuntimeException('Unable to delete cache item');
        }
    }

    /**
     * Get one item from a DynamoDB table.
     */
    public function getItem(array $arguments)
    {
        $response = $this->perform('GetItem', $arguments);

        if (200 !== $this->parseResponseStatusCode($response)) {
            throw new \RuntimeException('Unable to delete cache item');
        }

        $item = json_decode($response['body'], true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \RuntimeException('Unable to decode response from DynamoDB API');
        }

        return $item;
    }

    /**
     * Creates a new item, or replaces an old item with a new item.
     */
    public function putItem(array $arguments)
    {
        $response = $this->perform('PutItem', $arguments);

        if (200 !== $this->parseResponseStatusCode($response)) {
            throw new \RuntimeException('Unable to save cache item');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getService(): string
    {
        return 'dynamodb';
    }

    /**
     * Perform the given operation DynamoDB operation.
     */
    private function perform(string $operation, array $arguments = []): array
    {
        return $this->request('post', '/', json_encode($arguments), [
            'content-type' => 'application/x-amz-json-1.0',
            'x-amz-target' => sprintf('DynamoDB_20120810.%s', $operation),
        ]);
    }
}
