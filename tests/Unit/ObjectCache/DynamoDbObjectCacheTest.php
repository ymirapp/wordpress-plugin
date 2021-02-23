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

namespace Ymir\Plugin\Tests\Unit\ObjectCache;

use Ymir\Plugin\ObjectCache\DynamoDbObjectCache;
use Ymir\Plugin\Tests\Mock\DynamoDbClientMockTrait;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\ObjectCache\DynamoDbObjectCache
 */
class DynamoDbObjectCacheTest extends TestCase
{
    use DynamoDbClientMockTrait;
    use FunctionMockTrait;

    public function testAddWithExpiry()
    {
        $client = $this->getDynamoDbClientMock();
        $time = $this->getFunctionMock($this->getNamespace(DynamoDbObjectCache::class), 'time');

        $client->expects($this->once())
               ->method('putItem')
               ->with($this->identicalTo([
                   'TableName' => 'table',
                   'Item' => [
                       'key' => ['S' => 'group:key'],
                       'value' => ['S' => 's:5:"value";'],
                       'expires_at' => ['N' => 60],
                   ],
                   'ConditionExpression' => 'attribute_not_exists(#key) OR #expires_at < :now',
                   'ExpressionAttributeNames' => [
                       '#key' => 'key',
                       '#expires_at' => 'expires_at',
                   ],
                   'ExpressionAttributeValues' => [
                       ':now' => ['N' => '42'],
                   ],
               ]));

        $time->expects($this->once())
             ->willReturn(42);

        $objectCache = new DynamoDbObjectCache($client, false, 'table');

        $this->assertTrue($objectCache->add('group', 'key', 'value', 60));
    }

    public function testAddWithoutExpiry()
    {
        $client = $this->getDynamoDbClientMock();
        $time = $this->getFunctionMock($this->getNamespace(DynamoDbObjectCache::class), 'time');

        $client->expects($this->once())
               ->method('putItem')
               ->with($this->identicalTo([
                   'TableName' => 'table',
                   'Item' => [
                       'key' => ['S' => 'group:key'],
                       'value' => ['S' => 's:5:"value";'],
                   ],
                   'ConditionExpression' => 'attribute_not_exists(#key) OR #expires_at < :now',
                   'ExpressionAttributeNames' => [
                       '#key' => 'key',
                       '#expires_at' => 'expires_at',
                   ],
                   'ExpressionAttributeValues' => [
                       ':now' => ['N' => '42'],
                   ],
               ]));

        $time->expects($this->once())
             ->willReturn(42);

        $objectCache = new DynamoDbObjectCache($client, false, 'table');

        $this->assertTrue($objectCache->add('group', 'key', 'value'));
    }

    public function testDelete()
    {
        $client = $this->getDynamoDbClientMock();

        $client->expects($this->once())
               ->method('deleteItem')
               ->with($this->identicalTo([
                   'TableName' => 'table',
                   'Key' => [
                       'key' => ['S' => 'group:key'],
                   ],
               ]));

        $objectCache = new DynamoDbObjectCache($client, false, 'table');

        $this->assertTrue($objectCache->delete('group', 'key'));
    }

    public function testFlush()
    {
        $client = $this->getDynamoDbClientMock();

        $objectCache = new DynamoDbObjectCache($client, false, 'table');

        $this->assertTrue($objectCache->flush());
    }

    public function testGetMultipleReturnsAllValues()
    {
        $client = $this->getDynamoDbClientMock();

        $client->expects($this->once())
               ->method('batchGetItem')
               ->with($this->identicalTo([
                   'RequestItems' => [
                       'table' => [
                           'ConsistentRead' => false,
                           'Keys' => [
                               ['key' => ['S' => 'group:key1']],
                               ['key' => ['S' => 'group:key2']],
                           ],
                       ],
                   ],
               ]))
               ->willReturn([
                   'Responses' => ['table' => [
                       ['key' => ['S' => 'group:key1'], 'value' => ['S' => 's:3:"foo";']],
                       ['key' => ['S' => 'group:key2'], 'value' => ['S' => 's:3:"bar";']],
                   ]],
               ]);

        $objectCache = new DynamoDbObjectCache($client, false, 'table');

        $this->assertSame([
            'key1' => 'foo',
            'key2' => 'bar',
        ], $objectCache->getMultiple('group', ['key1', 'key2']));
    }

    public function testGetMultipleWithExpiredValues()
    {
        $client = $this->getDynamoDbClientMock();

        $client->expects($this->once())
               ->method('batchGetItem')
               ->with($this->identicalTo([
                   'RequestItems' => [
                       'table' => [
                           'ConsistentRead' => false,
                           'Keys' => [
                               ['key' => ['S' => 'group:key1']],
                               ['key' => ['S' => 'group:key2']],
                           ],
                       ],
                   ],
               ]))
               ->willReturn([
                   'Responses' => ['table' => [
                       ['key' => ['S' => 'group:key1'], 'value' => ['S' => 's:3:"foo";'], 'expires_at' => ['N' => 0]],
                       ['key' => ['S' => 'group:key2'], 'value' => ['S' => 's:3:"bar";']],
                   ]],
               ]);

        $objectCache = new DynamoDbObjectCache($client, false, 'table');

        $this->assertSame([
            'key1' => false,
            'key2' => 'bar',
        ], $objectCache->getMultiple('group', ['key1', 'key2']));
    }

    public function testGetMultipleWithInvalidResponse()
    {
        $client = $this->getDynamoDbClientMock();

        $client->expects($this->once())
               ->method('batchGetItem')
               ->with($this->identicalTo([
                   'RequestItems' => [
                       'table' => [
                            'ConsistentRead' => false,
                            'Keys' => [
                                ['key' => ['S' => 'group:key1']],
                                ['key' => ['S' => 'group:key2']],
                            ],
                       ],
                   ],
               ]));

        $objectCache = new DynamoDbObjectCache($client, false, 'table');

        $this->assertSame([
            'key1' => false,
            'key2' => false,
        ], $objectCache->getMultiple('group', ['key1', 'key2']));
    }

    public function testGetMultipleWithMissingValues()
    {
        $client = $this->getDynamoDbClientMock();

        $client->expects($this->once())
               ->method('batchGetItem')
               ->with($this->identicalTo([
                   'RequestItems' => [
                       'table' => [
                           'ConsistentRead' => false,
                           'Keys' => [
                               ['key' => ['S' => 'group:key1']],
                               ['key' => ['S' => 'group:key2']],
                           ],
                       ],
                   ],
               ]))
               ->willReturn([
                   'Responses' => ['table' => [
                       ['key' => ['S' => 'group:key2'], 'value' => ['S' => 's:3:"bar";']],
                   ]],
               ]);

        $objectCache = new DynamoDbObjectCache($client, false, 'table');

        $this->assertSame([
            'key1' => false,
            'key2' => 'bar',
        ], $objectCache->getMultiple('group', ['key1', 'key2']));
    }

    public function testGetReturnsFalseWhenExpired()
    {
        $client = $this->getDynamoDbClientMock();

        $client->expects($this->once())
               ->method('getItem')
               ->with($this->identicalTo([
                   'TableName' => 'table',
                   'ConsistentRead' => false,
                   'Key' => [
                       'key' => ['S' => 'group:key'],
                   ],
               ]))
               ->willReturn([
                   'Item' => ['value' => ['S' => '5.1'], 'expires_at' => ['N' => 0]],
               ]);

        $objectCache = new DynamoDbObjectCache($client, false, 'table');

        $this->assertFalse($objectCache->get('group', 'key'));
    }

    public function testGetReturnsFalseWithInvalidResponse()
    {
        $client = $this->getDynamoDbClientMock();

        $client->expects($this->once())
               ->method('getItem')
               ->with($this->identicalTo([
                   'TableName' => 'table',
                   'ConsistentRead' => false,
                   'Key' => [
                       'key' => ['S' => 'group:key'],
                   ],
               ]));

        $objectCache = new DynamoDbObjectCache($client, false, 'table');

        $this->assertFalse($objectCache->get('group', 'key'));
    }

    public function testGetReturnsFloat()
    {
        $client = $this->getDynamoDbClientMock();

        $client->expects($this->once())
               ->method('getItem')
               ->with($this->identicalTo([
                   'TableName' => 'table',
                   'ConsistentRead' => false,
                   'Key' => [
                       'key' => ['S' => 'group:key'],
                   ],
               ]))
               ->willReturn([
                   'Item' => ['value' => ['S' => '5.1']],
               ]);

        $objectCache = new DynamoDbObjectCache($client, false, 'table');

        $this->assertSame(5.1, $objectCache->get('group', 'key'));
    }

    public function testGetReturnsInt()
    {
        $client = $this->getDynamoDbClientMock();

        $client->expects($this->once())
               ->method('getItem')
               ->with($this->identicalTo([
                   'TableName' => 'table',
                   'ConsistentRead' => false,
                   'Key' => [
                       'key' => ['S' => 'group:key'],
                   ],
               ]))
               ->willReturn([
                   'Item' => ['value' => ['S' => '5']],
               ]);

        $objectCache = new DynamoDbObjectCache($client, false, 'table');

        $this->assertSame(5, $objectCache->get('group', 'key'));
    }

    public function testGetUnserializesFloat()
    {
        $client = $this->getDynamoDbClientMock();

        $client->expects($this->once())
               ->method('getItem')
               ->with($this->identicalTo([
                   'TableName' => 'table',
                   'ConsistentRead' => false,
                   'Key' => [
                       'key' => ['S' => 'group:key'],
                   ],
               ]))
               ->willReturn([
                   'Item' => ['value' => ['S' => 'd:5.1;']],
               ]);

        $objectCache = new DynamoDbObjectCache($client, false, 'table');

        $this->assertSame(5.1, $objectCache->get('group', 'key'));
    }

    public function testGetUnserializesInt()
    {
        $client = $this->getDynamoDbClientMock();

        $client->expects($this->once())
               ->method('getItem')
               ->with($this->identicalTo([
                   'TableName' => 'table',
                   'ConsistentRead' => false,
                   'Key' => [
                       'key' => ['S' => 'group:key'],
                   ],
               ]))
               ->willReturn([
                   'Item' => ['value' => ['S' => 'i:5;']],
               ]);

        $objectCache = new DynamoDbObjectCache($client, false, 'table');

        $this->assertSame(5, $objectCache->get('group', 'key'));
    }

    public function testGetUnserializesString()
    {
        $client = $this->getDynamoDbClientMock();

        $client->expects($this->once())
               ->method('getItem')
               ->with($this->identicalTo([
                   'TableName' => 'table',
                   'ConsistentRead' => false,
                   'Key' => [
                       'key' => ['S' => 'group:key'],
                   ],
               ]))
               ->willReturn([
                   'Item' => ['value' => ['S' => 's:5:"value";']],
               ]);

        $objectCache = new DynamoDbObjectCache($client, false, 'table');

        $this->assertSame('value', $objectCache->get('group', 'key'));
    }

    public function testReplaceWithExpiry()
    {
        $client = $this->getDynamoDbClientMock();
        $time = $this->getFunctionMock($this->getNamespace(DynamoDbObjectCache::class), 'time');

        $client->expects($this->once())
               ->method('putItem')
               ->with($this->identicalTo([
                   'TableName' => 'table',
                   'Item' => [
                       'key' => ['S' => 'group:key'],
                       'value' => ['S' => 's:5:"value";'],
                       'expires_at' => ['N' => 60],
                   ],
                   'ConditionExpression' => 'attribute_exists(#key) AND #expires_at > :now',
                   'ExpressionAttributeNames' => [
                       '#key' => 'key',
                       '#expires_at' => 'expires_at',
                   ],
                   'ExpressionAttributeValues' => [
                       ':now' => ['N' => '42'],
                   ],
               ]));

        $time->expects($this->once())
             ->willReturn(42);

        $objectCache = new DynamoDbObjectCache($client, false, 'table');

        $this->assertTrue($objectCache->replace('group', 'key', 'value', 60));
    }

    public function testReplaceWithoutExpiry()
    {
        $client = $this->getDynamoDbClientMock();
        $time = $this->getFunctionMock($this->getNamespace(DynamoDbObjectCache::class), 'time');

        $client->expects($this->once())
            ->method('putItem')
            ->with($this->identicalTo([
                'TableName' => 'table',
                'Item' => [
                    'key' => ['S' => 'group:key'],
                    'value' => ['S' => 's:5:"value";'],
                ],
                'ConditionExpression' => 'attribute_exists(#key) AND #expires_at > :now',
                'ExpressionAttributeNames' => [
                    '#key' => 'key',
                    '#expires_at' => 'expires_at',
                ],
                'ExpressionAttributeValues' => [
                    ':now' => ['N' => '42'],
                ],
            ]));

        $time->expects($this->once())
             ->willReturn(42);

        $objectCache = new DynamoDbObjectCache($client, false, 'table');

        $this->assertTrue($objectCache->replace('group', 'key', 'value'));
    }

    public function testSetWithExpiry()
    {
        $client = $this->getDynamoDbClientMock();

        $client->expects($this->once())
               ->method('putItem')
                  ->with($this->identicalTo([
                   'TableName' => 'table',
                   'Item' => [
                       'key' => ['S' => 'group:key'],
                       'value' => ['S' => 's:5:"value";'],
                       'expires_at' => ['N' => 60],
                   ],
               ]));

        $objectCache = new DynamoDbObjectCache($client, false, 'table');

        $this->assertTrue($objectCache->set('group', 'key', 'value', 60));
    }

    public function testSetWithoutExpiry()
    {
        $client = $this->getDynamoDbClientMock();

        $client->expects($this->once())
               ->method('putItem')
               ->with($this->identicalTo([
                   'TableName' => 'table',
                   'Item' => [
                       'key' => ['S' => 'group:key'],
                       'value' => ['S' => 's:5:"value";'],
                   ],
               ]));

        $objectCache = new DynamoDbObjectCache($client, false, 'table');

        $this->assertTrue($objectCache->set('group', 'key', 'value'));
    }
}
