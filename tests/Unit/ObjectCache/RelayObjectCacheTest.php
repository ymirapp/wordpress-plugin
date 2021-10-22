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

use Ymir\Plugin\ObjectCache\RelayObjectCache;
use Ymir\Plugin\Tests\Mock\RelayMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\ObjectCache\RelayObjectCache
 */
class RelayObjectCacheTest extends TestCase
{
    use RelayMockTrait;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        if (!extension_loaded('relay')) {
            $this->markTestSkipped('No relay extension installed');
        }
    }

    public function testAddWithExpiry()
    {
        $client = $this->getRelayMock();

        $client->expects($this->once())
               ->method('dispatchEvents');

        $client->expects($this->once())
               ->method('set')
               ->with($this->identicalTo('group:key'), $this->identicalTo('value'), $this->identicalTo(['nx', 'ex' => 60]))
               ->willReturn(true);

        $objectCache = new RelayObjectCache($client, false);

        $this->assertTrue($objectCache->add('group', 'key', 'value', 60));
    }

    public function testAddWithoutExpiry()
    {
        $client = $this->getRelayMock();

        $client->expects($this->once())
               ->method('dispatchEvents');

        $client->expects($this->once())
               ->method('set')
               ->with($this->identicalTo('group:key'), $this->identicalTo('value'), $this->identicalTo(['nx']))
               ->willReturn(true);

        $objectCache = new RelayObjectCache($client, false);

        $this->assertTrue($objectCache->add('group', 'key', 'value'));
    }

    public function testDelete()
    {
        $client = $this->getRelayMock();

        $client->expects($this->once())
               ->method('dispatchEvents');

        $client->expects($this->once())
               ->method('del')
               ->with($this->identicalTo('group:key'))
               ->willReturn(1);

        $objectCache = new RelayObjectCache($client, false);

        $this->assertTrue($objectCache->delete('group', 'key'));
    }

    public function testFlush()
    {
        $client = $this->getRelayMock();

        $client->expects($this->once())
               ->method('flushDB')
               ->with($this->identicalTo(true))
               ->willReturn(true);

        $objectCache = new RelayObjectCache($client, false);

        $this->assertTrue($objectCache->flush());
    }

    public function testGetMultipleReturnsAllValues()
    {
        $client = $this->getRelayMock();

        $client->expects($this->once())
               ->method('dispatchEvents');

        $client->expects($this->once())
               ->method('mget')
               ->with($this->identicalTo(['group:key1', 'group:key2']))
               ->willReturn(['foo', 'bar']);

        $objectCache = new RelayObjectCache($client, false);

        $this->assertSame([
            'key1' => 'foo',
            'key2' => 'bar',
        ], $objectCache->getMultiple('group', ['key1', 'key2']));
    }

    public function testGetMultipleReturnsAllValuesWithAssociativeArrayOfKeys()
    {
        $client = $this->getRelayMock();

        $client->expects($this->once())
               ->method('dispatchEvents');

        $client->expects($this->once())
               ->method('mget')
               ->with($this->identicalTo(['group:key1', 'group:key2']))
               ->willReturn(['foo', 'bar']);

        $objectCache = new RelayObjectCache($client, false);

        $this->assertSame([
            'key1' => 'foo',
            'key2' => 'bar',
        ], $objectCache->getMultiple('group', ['foo' => 'key1', 'bar' => 'key2']));
    }

    public function testGetMultipleWithException()
    {
        $client = $this->getRelayMock();

        $client->expects($this->once())
               ->method('dispatchEvents');

        $client->expects($this->once())
               ->method('mget')
               ->with($this->identicalTo(['group:key1', 'group:key2']))
               ->willThrowException(new \Exception());

        $objectCache = new RelayObjectCache($client, false);

        $this->assertSame([
            'key1' => false,
            'key2' => false,
        ], $objectCache->getMultiple('group', ['key1', 'key2']));
    }

    public function testGetMultipleWithMissingValues()
    {
        $client = $this->getRelayMock();

        $client->expects($this->once())
               ->method('dispatchEvents');

        $client->expects($this->once())
               ->method('mget')
               ->with($this->identicalTo(['group:key1', 'group:key2']))
               ->willReturn([false, 'bar']);

        $objectCache = new RelayObjectCache($client, false);

        $this->assertSame([
            'key1' => false,
            'key2' => 'bar',
        ], $objectCache->getMultiple('group', ['key1', 'key2']));
    }

    public function testGetReturnsFalseWithException()
    {
        $client = $this->getRelayMock();

        $client->expects($this->once())
               ->method('dispatchEvents');

        $client->expects($this->once())
               ->method('dispatchEvents');

        $client->expects($this->once())
               ->method('get')
               ->with($this->identicalTo('group:key'))
               ->willThrowException(new \Exception());

        $objectCache = new RelayObjectCache($client, false);

        $this->assertFalse($objectCache->get('group', 'key'));
    }

    public function testGetReturnsValue()
    {
        $client = $this->getRelayMock();

        $client->expects($this->once())
               ->method('dispatchEvents');

        $client->expects($this->once())
               ->method('get')
               ->with($this->identicalTo('group:key'))
               ->willReturn('value');

        $objectCache = new RelayObjectCache($client, false);

        $this->assertSame('value', $objectCache->get('group', 'key'));
    }

    public function testIsAvailable()
    {
        $client = $this->getRelayMock();
        $client->expects($this->once())
               ->method('ping');

        $objectCache = new RelayObjectCache($client, false);

        $this->assertTrue($objectCache->isAvailable());
    }

    public function testReplaceWithExpiry()
    {
        $client = $this->getRelayMock();

        $client->expects($this->once())
               ->method('dispatchEvents');

        $client->expects($this->once())
               ->method('set')
               ->with($this->identicalTo('group:key'), $this->identicalTo('value'), $this->identicalTo(['xx', 'ex' => 60]))
               ->willReturn(true);

        $objectCache = new RelayObjectCache($client, false);

        $this->assertTrue($objectCache->replace('group', 'key', 'value', 60));
    }

    public function testReplaceWithoutExpiry()
    {
        $client = $this->getRelayMock();

        $client->expects($this->once())
               ->method('dispatchEvents');

        $client->expects($this->once())
               ->method('set')
               ->with($this->identicalTo('group:key'), $this->identicalTo('value'), $this->identicalTo(['xx']))
               ->willReturn(true);

        $objectCache = new RelayObjectCache($client, false);

        $this->assertTrue($objectCache->replace('group', 'key', 'value'));
    }

    public function testSetWithExpiry()
    {
        $client = $this->getRelayMock();

        $client->expects($this->once())
               ->method('dispatchEvents');

        $client->expects($this->once())
               ->method('set')
               ->with($this->identicalTo('group:key'), $this->identicalTo('value'), $this->identicalTo(['ex' => 60]))
               ->willReturn(true);

        $objectCache = new RelayObjectCache($client, false);

        $this->assertTrue($objectCache->set('group', 'key', 'value', 60));
    }

    public function testSetWithoutExpiry()
    {
        $client = $this->getRelayMock();

        $client->expects($this->once())
               ->method('dispatchEvents');

        $client->expects($this->once())
               ->method('set')
               ->with($this->identicalTo('group:key'), $this->identicalTo('value'), $this->identicalTo([]))
               ->willReturn(true);

        $objectCache = new RelayObjectCache($client, false);

        $this->assertTrue($objectCache->set('group', 'key', 'value'));
    }
}
