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

use Ymir\Plugin\ObjectCache\AbstractPersistentObjectCache;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\ObjectCache\AbstractPersistentObjectCache
 */
class AbstractPersistentObjectCacheTest extends TestCase
{
    use FunctionMockTrait;

    public function testAddGlobalGroups()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $globalGroupsProperty = $objectCacheReflection->getProperty('globalGroups');
        $globalGroupsProperty->setAccessible(true);

        $this->assertEmpty($globalGroupsProperty->getValue($objectCache));

        $objectCache->addGlobalGroups(['group']);

        $this->assertSame(['group'], $globalGroupsProperty->getValue($objectCache));
    }

    public function testAddNonPersistentGroupsProperty()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $nonPersistentGroupsProperty = $objectCacheReflection->getProperty('nonPersistentGroups');
        $nonPersistentGroupsProperty->setAccessible(true);

        $this->assertEmpty($nonPersistentGroupsProperty->getValue($objectCache));

        $objectCache->addNonPersistentGroups(['group']);

        $this->assertSame(['group'], $nonPersistentGroupsProperty->getValue($objectCache));
    }

    public function testAddReturnsFalseIfCacheAdditionSuspended()
    {
        $function_exists = $this->getFunctionMock($this->getNamespace(AbstractPersistentObjectCache::class), 'function_exists');
        $function_exists->expects($this->once())
                        ->with($this->identicalTo('wp_suspend_cache_addition'))
                        ->willReturn(true);

        $wp_suspend_cache_addition = $this->getFunctionMock($this->getNamespace(AbstractPersistentObjectCache::class), 'wp_suspend_cache_addition');
        $wp_suspend_cache_addition->expects($this->once())
                                  ->willReturn(false);

        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);

        $this->assertFalse($objectCache->add('group', 'key', 'value'));
    }

    public function testAddReturnsFalseIfCachedInMemory()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $cacheProperty->setValue($objectCache, ['group:key' => 'value']);

        $this->assertFalse($objectCache->add('group', 'key', 'value'));
    }

    public function testAddReturnsFalseIfStoreValueInPersistentCacheFailed()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);

        $objectCache->expects($this->once())
                    ->method('storeValueInPersistentCache')
                    ->with($this->identicalTo('group:key'), $this->identicalTo('value'), $this->identicalTo(0), $this->identicalTo(1))
                    ->willReturn(false);

        $this->assertFalse($objectCache->add('group', 'key', 'value'));
    }

    public function testAddReturnsFalseIfStoreValueInPersistentCacheThrowsException()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);

        $objectCache->expects($this->once())
                    ->method('storeValueInPersistentCache')
                    ->with($this->identicalTo('group:key'), $this->identicalTo('value'), $this->identicalTo(0), $this->identicalTo(1))
                    ->willThrowException(new \Exception());

        $this->assertFalse($objectCache->add('group', 'key', 'value'));
    }

    public function testAddReturnsTrueIfInNonPersistentGroup()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $nonPersistentGroupsProperty = $objectCacheReflection->getProperty('nonPersistentGroups');
        $nonPersistentGroupsProperty->setAccessible(true);
        $nonPersistentGroupsProperty->setValue($objectCache, ['group']);

        $objectCache->expects($this->never())
                    ->method('storeValueInPersistentCache');

        $this->assertTrue($objectCache->add('group', 'key', 'value'));

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);

        $this->assertSame(['group:key' => 'value'], $cacheProperty->getValue($objectCache));
    }

    public function testAddReturnsTrueIfStoreValueInPersistentCacheSucceeds()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $objectCache->expects($this->once())
                    ->method('storeValueInPersistentCache')
                    ->with($this->identicalTo('group:key'), $this->identicalTo('value'), $this->identicalTo(0), $this->identicalTo(1))
                    ->willReturn(true);

        $this->assertTrue($objectCache->add('group', 'key', 'value'));

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);

        $this->assertSame(['group:key' => 'value'], $cacheProperty->getValue($objectCache));
    }

    public function testAddStoresAlloptionsInPersistentCacheWhenNotInPersistentCache()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);

        $objectCache->expects($this->exactly(3))
                    ->method('storeValueInPersistentCache')
                    ->withConsecutive(
                        [$this->identicalTo('alloptions_values:key1'), $this->identicalTo('value1'), $this->identicalTo(0), $this->identicalTo(0)],
                        [$this->identicalTo('alloptions_values:key2'), $this->identicalTo('value2'), $this->identicalTo(0), $this->identicalTo(0)],
                        [$this->identicalTo('options:alloptions_keys'), $this->identicalTo(['key1' => true, 'key2' => true]), $this->identicalTo(0), $this->identicalTo(0)]
                    )
                    ->willReturn(true);

        $this->assertTrue($objectCache->add('options', 'alloptions', ['key1' => 'value1', 'key2' => 'value2']));

        $this->assertSame([
            'options:alloptions_keys' => ['key1' => true, 'key2' => true],
            'alloptions_values:key1' => 'value1',
            'alloptions_values:key2' => 'value2',
            'options:alloptions' => ['key1' => 'value1', 'key2' => 'value2'],
        ], $cacheProperty->getValue($objectCache));
    }

    public function testAddStoresDeletesUnusedAlloptionsOptionsInPersistentCache()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $cacheProperty->setValue($objectCache, ['alloptions_values:key1' => 'value1']);

        $objectCache->expects($this->once())
                    ->method('deleteValueFromPersistentCache')
                    ->with($this->identicalTo('alloptions_values:key1'))
                    ->willReturn(true);

        $objectCache->expects($this->once())
                    ->method('getValuesFromPersistentCache')
                    ->with($this->identicalTo('options:alloptions_keys'))
                    ->willReturn(['key1' => true]);

        $objectCache->expects($this->exactly(2))
                    ->method('storeValueInPersistentCache')
                    ->withConsecutive(
                        [$this->identicalTo('alloptions_values:key2'), $this->identicalTo('value2'), $this->identicalTo(0), $this->identicalTo(0)],
                        [$this->identicalTo('options:alloptions_keys'), $this->identicalTo(['key2' => true]), $this->identicalTo(0), $this->identicalTo(0)]
                    )
                    ->willReturn(true);

        $this->assertTrue($objectCache->add('options', 'alloptions', ['key2' => 'value2']));

        $this->assertSame([
            'options:alloptions_keys' => ['key2' => true],
            'alloptions_values:key2' => 'value2',
            'options:alloptions' => ['key2' => 'value2'],
        ], $cacheProperty->getValue($objectCache));
    }

    public function testAddStoresOnlyMissingAlloptionsOptionsInPersistentCache()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $cacheProperty->setValue($objectCache, ['alloptions_values:key1' => 'value1']);

        $objectCache->expects($this->once())
                    ->method('getValuesFromPersistentCache')
                    ->with($this->identicalTo('options:alloptions_keys'))
                    ->willReturn(['key1' => true]);

        $objectCache->expects($this->exactly(2))
                    ->method('storeValueInPersistentCache')
                    ->withConsecutive(
                        [$this->identicalTo('alloptions_values:key2'), $this->identicalTo('value2'), $this->identicalTo(0), $this->identicalTo(0)],
                        [$this->identicalTo('options:alloptions_keys'), $this->identicalTo(['key1' => true, 'key2' => true]), $this->identicalTo(0), $this->identicalTo(0)]
                    )
                    ->willReturn(true);

        $this->assertTrue($objectCache->add('options', 'alloptions', ['key1' => 'value1', 'key2' => 'value2']));

        $this->assertSame([
            'alloptions_values:key1' => 'value1',
            'options:alloptions_keys' => ['key1' => true, 'key2' => true],
            'alloptions_values:key2' => 'value2',
            'options:alloptions' => ['key1' => 'value1', 'key2' => 'value2'],
        ], $cacheProperty->getValue($objectCache));
    }

    public function testClose()
    {
        $this->assertTrue(($this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]))->close());
    }

    public function testDecrementDecrementsValueFromMemoryAndDoestSaveItToPersistentCacheWhenInNonPersistentGroup()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $cacheProperty->setValue($objectCache, ['group:key' => 5]);

        $nonPersistentGroupsProperty = $objectCacheReflection->getProperty('nonPersistentGroups');
        $nonPersistentGroupsProperty->setAccessible(true);
        $nonPersistentGroupsProperty->setValue($objectCache, ['group']);

        $objectCache->expects($this->never())
                    ->method('getValuesFromPersistentCache');

        $objectCache->expects($this->never())
                    ->method('storeValueInPersistentCache');

        $this->assertSame(4, $objectCache->decrement('group', 'key'));
        $this->assertSame(['group:key' => 4], $cacheProperty->getValue($objectCache));
    }

    public function testDecrementDecrementsValueFromMemoryAndSuccessfullySavesItToPersistentCache()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $cacheProperty->setValue($objectCache, ['group:key' => 5]);

        $objectCache->expects($this->never())
                    ->method('getValuesFromPersistentCache');

        $objectCache->expects($this->once())
                    ->method('storeValueInPersistentCache')
                    ->with($this->identicalTo('group:key'), $this->identicalTo(4), $this->identicalTo(0), $this->identicalTo(0))
                    ->willReturn(true);

        $this->assertSame(4, $objectCache->decrement('group', 'key'));
        $this->assertSame(['group:key' => 4], $cacheProperty->getValue($objectCache));
    }

    public function testDecrementDecrementsValueFromPersistentCacheAndSuccessfullySavesItToPersistentCache()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);

        $objectCache->expects($this->once())
                    ->method('getValuesFromPersistentCache')
                    ->with($this->identicalTo('group:key'))
                    ->willReturn(5);

        $objectCache->expects($this->once())
                    ->method('storeValueInPersistentCache')
                    ->with($this->identicalTo('group:key'), $this->identicalTo(4), $this->identicalTo(0), $this->identicalTo(0))
                    ->willReturn(true);

        $this->assertSame(4, $objectCache->decrement('group', 'key'));
        $this->assertSame(['group:key' => 4], $cacheProperty->getValue($objectCache));
    }

    public function testDecrementReturnsFalseWhenNoValueInMemoryAndValueFromPersistentCacheIsntAnInt()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);

        $objectCache->expects($this->once())
                    ->method('getValuesFromPersistentCache')
                    ->with($this->identicalTo('group:key'))
                    ->willReturn('5');

        $this->assertFalse($objectCache->decrement('group', 'key'));
    }

    public function testDecrementReturnsFalseWhenNoValueInMemoryOrPersistentCache()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);

        $objectCache->expects($this->once())
                    ->method('getValuesFromPersistentCache')
                    ->with($this->identicalTo('group:key'))
                    ->willReturn(false);

        $this->assertFalse($objectCache->decrement('group', 'key'));
    }

    public function testDecrementReturnsFalseWhenStoreToPersistentCacheFails()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $cacheProperty->setValue($objectCache, ['group:key' => 5]);

        $objectCache->expects($this->never())
                    ->method('getValuesFromPersistentCache');

        $objectCache->expects($this->once())
                    ->method('storeValueInPersistentCache')
                    ->with($this->identicalTo('group:key'), $this->identicalTo(4), $this->identicalTo(0), $this->identicalTo(0))
                    ->willReturn(false);

        $this->assertFalse($objectCache->decrement('group', 'key'));
    }

    public function testDecrementReturnsFalseWhenValueInMemoryIsntAnIntAndNoValueInPersistentCache()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $cacheProperty->setValue($objectCache, ['group:key' => '5']);

        $objectCache->expects($this->once())
                    ->method('getValuesFromPersistentCache')
                    ->with($this->identicalTo('group:key'))
                    ->willReturn(false);

        $this->assertFalse($objectCache->decrement('group', 'key'));
    }

    public function testDeleteReturnsFalseWhenDeleteFromPersistentCacheFails()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $cacheProperty->setValue($objectCache, ['group:key' => 'value']);

        $objectCache->expects($this->once())
                    ->method('deleteValueFromPersistentCache')
                    ->with($this->identicalTo('group:key'))
                    ->willReturn(false);

        $this->assertFalse($objectCache->delete('group', 'key'));
        $this->assertEmpty($cacheProperty->getValue($objectCache));
    }

    public function testDeleteReturnsFalseWhenInNonPersistentGroupAndNotInMemory()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $nonPersistentGroupsProperty = $objectCacheReflection->getProperty('nonPersistentGroups');
        $nonPersistentGroupsProperty->setAccessible(true);
        $nonPersistentGroupsProperty->setValue($objectCache, ['group']);

        $this->assertFalse($objectCache->delete('group', 'key'));
    }

    public function testDeleteReturnsTrueWhenDeletedSuccessfullyFromPersistentCache()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $cacheProperty->setValue($objectCache, ['group:key' => 'value']);

        $objectCache->expects($this->once())
                    ->method('deleteValueFromPersistentCache')
                    ->with($this->identicalTo('group:key'))
                    ->willReturn(true);

        $this->assertTrue($objectCache->delete('group', 'key'));
        $this->assertEmpty($cacheProperty->getValue($objectCache));
    }

    public function testDeleteReturnsTrueWhenInNonPersistentGroupAndInMemory()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $cacheProperty->setValue($objectCache, ['group:key' => 'value']);

        $nonPersistentGroupsProperty = $objectCacheReflection->getProperty('nonPersistentGroups');
        $nonPersistentGroupsProperty->setAccessible(true);
        $nonPersistentGroupsProperty->setValue($objectCache, ['group']);

        $this->assertTrue($objectCache->delete('group', 'key'));
        $this->assertEmpty($cacheProperty->getValue($objectCache));
    }

    public function testFlushReturnsFalseOnException()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $cacheProperty->setValue($objectCache, ['group:key' => 'value']);

        $objectCache->expects($this->once())
                    ->method('flushPersistentCache')
                    ->willThrowException(new \Exception());

        $this->assertFalse($objectCache->flush());
        $this->assertEmpty($cacheProperty->getValue($objectCache));
    }

    public function testFlushReturnsFalseOnFailure()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $cacheProperty->setValue($objectCache, ['group:key' => 'value']);

        $objectCache->expects($this->once())
                    ->method('flushPersistentCache')
                    ->willReturn(false);

        $this->assertFalse($objectCache->flush());
        $this->assertEmpty($cacheProperty->getValue($objectCache));
    }

    public function testFlushReturnsTrueOnSuccess()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $cacheProperty->setValue($objectCache, ['group:key' => 'value']);

        $objectCache->expects($this->once())
                    ->method('flushPersistentCache')
                    ->willReturn(true);

        $this->assertTrue($objectCache->flush());
        $this->assertEmpty($cacheProperty->getValue($objectCache));
    }

    public function testGetMultipleDoesntGetFromPersistentCacheIfAllKeysAreInMemory()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $cacheProperty->setValue($objectCache, ['group1:key1' => 'value1', 'group1:key2' => 'value2', 'group2:key1' => 'value3', 'group2:key2' => 'value4']);

        $requestedKeysProperty = $objectCacheReflection->getProperty('requestedKeys');
        $requestedKeysProperty->setAccessible(true);

        $objectCache->expects($this->never())
                    ->method('getValuesFromPersistentCache');

        $this->assertEmpty($requestedKeysProperty->getValue($objectCache));

        $this->assertSame(['key1' => 'value1', 'key2' => 'value2'], $objectCache->getMultiple('group1', ['key1', 'key2']));
        $this->assertSame(['group1:key1' => true, 'group1:key2' => true], $requestedKeysProperty->getValue($objectCache));
    }

    public function testGetMultipleGetsFromPersistentCacheIfAllKeysAreInMemoryButForcedIsTrue()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $cacheProperty->setValue($objectCache, ['group1:key1' => 'value1', 'group1:key2' => 'value2', 'group2:key1' => 'value3', 'group2:key2' => 'value4']);

        $requestedKeysProperty = $objectCacheReflection->getProperty('requestedKeys');
        $requestedKeysProperty->setAccessible(true);

        $objectCache->expects($this->once())
                    ->method('getValuesFromPersistentCache')
                    ->with($this->identicalTo(['group1:key1', 'group1:key2']))
                    ->willReturn(['group1:key1' => 'new_value1', 'group1:key2' => 'new_value2']);

        $this->assertEmpty($requestedKeysProperty->getValue($objectCache));

        $this->assertSame(['key1' => 'new_value1', 'key2' => 'new_value2'], $objectCache->getMultiple('group1', ['key1', 'key2'], true));
        $this->assertSame(['group1:key1' => 'new_value1', 'group1:key2' => 'new_value2', 'group2:key1' => 'value3', 'group2:key2' => 'value4'], $cacheProperty->getValue($objectCache));
        $this->assertSame(['group1:key1' => true, 'group1:key2' => true], $requestedKeysProperty->getValue($objectCache));
    }

    public function testGetMultipleGetsMissingValueFromPersistentCache()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $cacheProperty->setValue($objectCache, ['group1:key2' => 'value2', 'group2:key1' => 'value3', 'group2:key2' => 'value4']);

        $requestedKeysProperty = $objectCacheReflection->getProperty('requestedKeys');
        $requestedKeysProperty->setAccessible(true);

        $objectCache->expects($this->once())
                    ->method('getValuesFromPersistentCache')
                    ->with($this->identicalTo(['group1:key1']))
                    ->willReturn(['group1:key1' => 'value1']);

        $this->assertEmpty($requestedKeysProperty->getValue($objectCache));

        $this->assertSame(['key1' => 'value1', 'key2' => 'value2'], $objectCache->getMultiple('group1', ['key1', 'key2']));
        $this->assertSame(['group1:key2' => 'value2', 'group2:key1' => 'value3', 'group2:key2' => 'value4', 'group1:key1' => 'value1'], $cacheProperty->getValue($objectCache));
        $this->assertSame(['group1:key1' => true, 'group1:key2' => true], $requestedKeysProperty->getValue($objectCache));
    }

    public function testGetMultipleSortsReturnValues()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $cacheProperty->setValue($objectCache, ['group1:key1' => 'value1', 'group1:key2' => 'value2', 'group2:key1' => 'value3', 'group2:key2' => 'value4']);

        $objectCache->expects($this->never())
                    ->method('getValuesFromPersistentCache');

        $this->assertSame(['key2' => 'value2', 'key1' => 'value1'], $objectCache->getMultiple('group1', ['key2', 'key1']));
    }

    public function testGetReturnsFalseWhenGettingFromPersistentCacheFails()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);

        $requestedKeysProperty = $objectCacheReflection->getProperty('requestedKeys');
        $requestedKeysProperty->setAccessible(true);

        $objectCache->expects($this->once())
                    ->method('getValuesFromPersistentCache')
                    ->with($this->identicalTo('group:key'))
                    ->willReturn(false);

        $this->assertEmpty($requestedKeysProperty->getValue($objectCache));

        $found = null;

        $this->assertFalse($objectCache->get('group', 'key', true, $found));
        $this->assertEmpty($cacheProperty->getValue($objectCache));
        $this->assertFalse($found);
    }

    public function testGetReturnsFalseWhenGettingFromPersistentCacheThrowsException()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);

        $requestedKeysProperty = $objectCacheReflection->getProperty('requestedKeys');
        $requestedKeysProperty->setAccessible(true);

        $objectCache->expects($this->once())
                    ->method('getValuesFromPersistentCache')
                    ->with($this->identicalTo('group:key'))
                    ->willThrowException(new \Exception());

        $this->assertEmpty($requestedKeysProperty->getValue($objectCache));

        $found = null;

        $this->assertFalse($objectCache->get('group', 'key', true, $found));
        $this->assertEmpty($cacheProperty->getValue($objectCache));
        $this->assertFalse($found);
        $this->assertEmpty($requestedKeysProperty->getValue($objectCache));
    }

    public function testGetReturnsFalseWhenInNotPersistentGroupAndNotInMemory()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $nonPersistentGroupsProperty = $objectCacheReflection->getProperty('nonPersistentGroups');
        $nonPersistentGroupsProperty->setAccessible(true);
        $nonPersistentGroupsProperty->setValue($objectCache, ['group']);

        $requestedKeysProperty = $objectCacheReflection->getProperty('requestedKeys');
        $requestedKeysProperty->setAccessible(true);

        $objectCache->expects($this->never())
                    ->method('getValuesFromPersistentCache');

        $this->assertEmpty($requestedKeysProperty->getValue($objectCache));

        $found = null;

        $this->assertFalse($objectCache->get('group', 'key', false, $found));
        $this->assertFalse($found);
        $this->assertEmpty($requestedKeysProperty->getValue($objectCache));
    }

    public function testGetReturnsFalseWhenInNotPersistentGroupAndNotInMemoryAndForced()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $nonPersistentGroupsProperty = $objectCacheReflection->getProperty('nonPersistentGroups');
        $nonPersistentGroupsProperty->setAccessible(true);
        $nonPersistentGroupsProperty->setValue($objectCache, ['group']);

        $requestedKeysProperty = $objectCacheReflection->getProperty('requestedKeys');
        $requestedKeysProperty->setAccessible(true);

        $objectCache->expects($this->never())
                    ->method('getValuesFromPersistentCache');

        $this->assertEmpty($requestedKeysProperty->getValue($objectCache));

        $found = null;

        $this->assertFalse($objectCache->get('group', 'key', true, $found));
        $this->assertFalse($found);
        $this->assertEmpty($requestedKeysProperty->getValue($objectCache));
    }

    public function testGetReturnsValueFromMemory()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $cacheProperty->setValue($objectCache, ['group:key' => 'value']);

        $requestedKeysProperty = $objectCacheReflection->getProperty('requestedKeys');
        $requestedKeysProperty->setAccessible(true);

        $objectCache->expects($this->never())
                    ->method('getValuesFromPersistentCache');

        $this->assertEmpty($requestedKeysProperty->getValue($objectCache));

        $found = null;

        $this->assertSame('value', $objectCache->get('group', 'key', false, $found));
        $this->assertTrue($found);
        $this->assertEmpty($requestedKeysProperty->getValue($objectCache));
    }

    public function testGetReturnsValueFromPersistentCacheWhenForced()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $cacheProperty->setValue($objectCache, ['group:key' => 'value']);

        $requestedKeysProperty = $objectCacheReflection->getProperty('requestedKeys');
        $requestedKeysProperty->setAccessible(true);

        $objectCache->expects($this->once())
                    ->method('getValuesFromPersistentCache')
                    ->with($this->identicalTo('group:key'))
                    ->willReturn('new_value');

        $this->assertEmpty($requestedKeysProperty->getValue($objectCache));

        $found = null;

        $this->assertSame('new_value', $objectCache->get('group', 'key', true, $found));
        $this->assertSame(['group:key' => 'new_value'], $cacheProperty->getValue($objectCache));
        $this->assertTrue($found);
        $this->assertSame(['group:key' => true], $requestedKeysProperty->getValue($objectCache));
    }

    public function testGetReturnsValueFromPersistentCacheWhenNotInMemory()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);

        $requestedKeysProperty = $objectCacheReflection->getProperty('requestedKeys');
        $requestedKeysProperty->setAccessible(true);

        $objectCache->expects($this->once())
                    ->method('getValuesFromPersistentCache')
                    ->with($this->identicalTo('group:key'))
                    ->willReturn('value');

        $this->assertEmpty($requestedKeysProperty->getValue($objectCache));

        $found = null;

        $this->assertSame('value', $objectCache->get('group', 'key', false, $found));
        $this->assertSame(['group:key' => 'value'], $cacheProperty->getValue($objectCache));
        $this->assertTrue($found);
        $this->assertSame(['group:key' => true], $requestedKeysProperty->getValue($objectCache));
    }

    public function testGetReturnsValueWhenInNotPersistentGroupAndInMemory()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $cacheProperty->setValue($objectCache, ['group:key' => 'value']);

        $nonPersistentGroupsProperty = $objectCacheReflection->getProperty('nonPersistentGroups');
        $nonPersistentGroupsProperty->setAccessible(true);
        $nonPersistentGroupsProperty->setValue($objectCache, ['group']);

        $requestedKeysProperty = $objectCacheReflection->getProperty('requestedKeys');
        $requestedKeysProperty->setAccessible(true);

        $objectCache->expects($this->never())
                    ->method('getValuesFromPersistentCache');

        $this->assertEmpty($requestedKeysProperty->getValue($objectCache));

        $found = null;

        $this->assertSame('value', $objectCache->get('group', 'key', false, $found));
        $this->assertTrue($found);
    }

    public function testIncrementIncrementsValueFromMemoryAndDoestSaveItToPersistentCacheWhenInNonPersistentGroup()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $cacheProperty->setValue($objectCache, ['group:key' => 5]);

        $nonPersistentGroupsProperty = $objectCacheReflection->getProperty('nonPersistentGroups');
        $nonPersistentGroupsProperty->setAccessible(true);
        $nonPersistentGroupsProperty->setValue($objectCache, ['group']);

        $objectCache->expects($this->never())
                    ->method('getValuesFromPersistentCache');

        $objectCache->expects($this->never())
                    ->method('storeValueInPersistentCache');

        $this->assertSame(6, $objectCache->increment('group', 'key'));
        $this->assertSame(['group:key' => 6], $cacheProperty->getValue($objectCache));
    }

    public function testIncrementIncrementsValueFromMemoryAndSuccessfullySavesItToPersistentCache()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $cacheProperty->setValue($objectCache, ['group:key' => 5]);

        $objectCache->expects($this->never())
                    ->method('getValuesFromPersistentCache');

        $objectCache->expects($this->once())
                    ->method('storeValueInPersistentCache')
                    ->with($this->identicalTo('group:key'), $this->identicalTo(6), $this->identicalTo(0), $this->identicalTo(0))
                    ->willReturn(true);

        $this->assertSame(6, $objectCache->increment('group', 'key'));
        $this->assertSame(['group:key' => 6], $cacheProperty->getValue($objectCache));
    }

    public function testIncrementIncrementsValueFromPersistentCacheAndSuccessfullySavesItToPersistentCache()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);

        $objectCache->expects($this->once())
                    ->method('getValuesFromPersistentCache')
                    ->with($this->identicalTo('group:key'))
                    ->willReturn(5);

        $objectCache->expects($this->once())
                    ->method('storeValueInPersistentCache')
                    ->with($this->identicalTo('group:key'), $this->identicalTo(6), $this->identicalTo(0), $this->identicalTo(0))
                    ->willReturn(true);

        $this->assertSame(6, $objectCache->increment('group', 'key'));
        $this->assertSame(['group:key' => 6], $cacheProperty->getValue($objectCache));
    }

    public function testIncrementReturnsFalseWhenNoValueInMemoryAndValueFromPersistentCacheIsntAnInt()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);

        $objectCache->expects($this->once())
                    ->method('getValuesFromPersistentCache')
                    ->with($this->identicalTo('group:key'))
                    ->willReturn('5');

        $this->assertFalse($objectCache->increment('group', 'key'));
    }

    public function testIncrementReturnsFalseWhenNoValueInMemoryOrPersistentCache()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);

        $objectCache->expects($this->once())
                    ->method('getValuesFromPersistentCache')
                    ->with($this->identicalTo('group:key'))
                    ->willReturn(false);

        $this->assertFalse($objectCache->increment('group', 'key'));
    }

    public function testIncrementReturnsFalseWhenStoreToPersistentCacheFails()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $cacheProperty->setValue($objectCache, ['group:key' => 5]);

        $objectCache->expects($this->never())
                    ->method('getValuesFromPersistentCache');

        $objectCache->expects($this->once())
                    ->method('storeValueInPersistentCache')
                    ->with($this->identicalTo('group:key'), $this->identicalTo(6), $this->identicalTo(0), $this->identicalTo(0))
                    ->willReturn(false);

        $this->assertFalse($objectCache->increment('group', 'key'));
    }

    public function testIncrementReturnsFalseWhenValueInMemoryIsntAnIntAndNoValueInPersistentCache()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $cacheProperty->setValue($objectCache, ['group:key' => '5']);

        $objectCache->expects($this->once())
                    ->method('getValuesFromPersistentCache')
                    ->with($this->identicalTo('group:key'))
                    ->willReturn(false);

        $this->assertFalse($objectCache->increment('group', 'key'));
    }

    public function testReplaceReturnsFalseIfInNonPersistentGroupAndNotCachedInMemory()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $nonPersistentGroupsProperty = $objectCacheReflection->getProperty('nonPersistentGroups');
        $nonPersistentGroupsProperty->setAccessible(true);
        $nonPersistentGroupsProperty->setValue($objectCache, ['group']);

        $this->assertFalse($objectCache->replace('group', 'key', 'value'));
    }

    public function testReplaceReturnsFalseIfStoreValueInPersistentCacheFailed()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);

        $objectCache->expects($this->once())
                    ->method('storeValueInPersistentCache')
                    ->with($this->identicalTo('group:key'), $this->identicalTo('value'), $this->identicalTo(0), $this->identicalTo(2))
                    ->willReturn(false);

        $this->assertFalse($objectCache->replace('group', 'key', 'value'));
        $this->assertEmpty($cacheProperty->getValue($objectCache));
    }

    public function testReplaceReturnsFalseIfStoreValueInPersistentCacheThrowsException()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);

        $objectCache->expects($this->once())
                    ->method('storeValueInPersistentCache')
                    ->with($this->identicalTo('group:key'), $this->identicalTo('value'), $this->identicalTo(0), $this->identicalTo(2))
                    ->willThrowException(new \Exception());

        $this->assertFalse($objectCache->replace('group', 'key', 'value'));
        $this->assertEmpty($cacheProperty->getValue($objectCache));
    }

    public function testReplaceReturnsTrueIfInNonPersistentGroup()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $cacheProperty->setValue($objectCache, ['group:key' => 'value']);

        $nonPersistentGroupsProperty = $objectCacheReflection->getProperty('nonPersistentGroups');
        $nonPersistentGroupsProperty->setAccessible(true);
        $nonPersistentGroupsProperty->setValue($objectCache, ['group']);

        $objectCache->expects($this->never())
                    ->method('storeValueInPersistentCache');

        $this->assertTrue($objectCache->replace('group', 'key', 'new_value'));
        $this->assertSame(['group:key' => 'new_value'], $cacheProperty->getValue($objectCache));
    }

    public function testReplaceReturnsTrueIfStoreValueInPersistentCacheSucceeds()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $cacheProperty->setValue($objectCache, ['group:key' => 'value']);

        $objectCache->expects($this->once())
                    ->method('storeValueInPersistentCache')
                    ->with($this->identicalTo('group:key'), $this->identicalTo('new_value'), $this->identicalTo(0), $this->identicalTo(2))
                    ->willReturn(true);

        $this->assertTrue($objectCache->replace('group', 'key', 'new_value'));
        $this->assertSame(['group:key' => 'new_value'], $cacheProperty->getValue($objectCache));
    }

    public function testReplaceStoresAlloptionsInPersistentCacheWhenNotInPersistentCache()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);

        $objectCache->expects($this->exactly(3))
                    ->method('storeValueInPersistentCache')
                    ->withConsecutive(
                        [$this->identicalTo('alloptions_values:key1'), $this->identicalTo('value1'), $this->identicalTo(0), $this->identicalTo(0)],
                        [$this->identicalTo('alloptions_values:key2'), $this->identicalTo('value2'), $this->identicalTo(0), $this->identicalTo(0)],
                        [$this->identicalTo('options:alloptions_keys'), $this->identicalTo(['key1' => true, 'key2' => true]), $this->identicalTo(0), $this->identicalTo(0)]
                    )
                    ->willReturn(true);

        $this->assertTrue($objectCache->replace('options', 'alloptions', ['key1' => 'value1', 'key2' => 'value2']));

        $this->assertSame([
            'options:alloptions_keys' => ['key1' => true, 'key2' => true],
            'alloptions_values:key1' => 'value1',
            'alloptions_values:key2' => 'value2',
            'options:alloptions' => ['key1' => 'value1', 'key2' => 'value2'],
        ], $cacheProperty->getValue($objectCache));
    }

    public function testReplaceStoresDeletesUnusedAlloptionsOptionsInPersistentCache()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $cacheProperty->setValue($objectCache, ['alloptions_values:key1' => 'value1']);

        $objectCache->expects($this->once())
                    ->method('deleteValueFromPersistentCache')
                    ->with($this->identicalTo('alloptions_values:key1'))
                    ->willReturn(true);

        $objectCache->expects($this->once())
                ->method('getValuesFromPersistentCache')
                ->with($this->identicalTo('options:alloptions_keys'))
                ->willReturn(['key1' => true]);

        $objectCache->expects($this->exactly(2))
                    ->method('storeValueInPersistentCache')
                    ->withConsecutive(
                        [$this->identicalTo('alloptions_values:key2'), $this->identicalTo('value2'), $this->identicalTo(0), $this->identicalTo(0)],
                        [$this->identicalTo('options:alloptions_keys'), $this->identicalTo(['key2' => true]), $this->identicalTo(0), $this->identicalTo(0)]
                    )
                    ->willReturn(true);

        $this->assertTrue($objectCache->replace('options', 'alloptions', ['key2' => 'value2']));

        $this->assertSame([
            'options:alloptions_keys' => ['key2' => true],
            'alloptions_values:key2' => 'value2',
            'options:alloptions' => ['key2' => 'value2'],
        ], $cacheProperty->getValue($objectCache));
    }

    public function testReplaceStoresOnlyMissingAlloptionsOptionsInPersistentCache()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $cacheProperty->setValue($objectCache, ['alloptions_values:key1' => 'value1']);

        $objectCache->expects($this->once())
                    ->method('getValuesFromPersistentCache')
                    ->with($this->identicalTo('options:alloptions_keys'))
                    ->willReturn(['key1' => true]);

        $objectCache->expects($this->exactly(2))
                    ->method('storeValueInPersistentCache')
                    ->withConsecutive(
                        [$this->identicalTo('alloptions_values:key2'), $this->identicalTo('value2'), $this->identicalTo(0), $this->identicalTo(0)],
                        [$this->identicalTo('options:alloptions_keys'), $this->identicalTo(['key1' => true, 'key2' => true]), $this->identicalTo(0), $this->identicalTo(0)]
                    )
                    ->willReturn(true);

        $this->assertTrue($objectCache->replace('options', 'alloptions', ['key1' => 'value1', 'key2' => 'value2']));

        $this->assertSame([
            'alloptions_values:key1' => 'value1',
            'options:alloptions_keys' => ['key1' => true, 'key2' => true],
            'alloptions_values:key2' => 'value2',
            'options:alloptions' => ['key1' => 'value1', 'key2' => 'value2'],
        ], $cacheProperty->getValue($objectCache));
    }

    public function testSetReturnsFalseIfStoreValueInPersistentCacheFailed()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);

        $objectCache->expects($this->once())
                    ->method('storeValueInPersistentCache')
                    ->with($this->identicalTo('group:key'), $this->identicalTo('value'), $this->identicalTo(0), $this->identicalTo(0))
                    ->willReturn(false);

        $this->assertFalse($objectCache->set('group', 'key', 'value'));
        $this->assertEmpty($cacheProperty->getValue($objectCache));
    }

    public function testSetReturnsFalseIfStoreValueInPersistentCacheThrowsException()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);

        $objectCache->expects($this->once())
                    ->method('storeValueInPersistentCache')
                    ->with($this->identicalTo('group:key'), $this->identicalTo('value'), $this->identicalTo(0), $this->identicalTo(0))
                    ->willThrowException(new \Exception());

        $this->assertFalse($objectCache->set('group', 'key', 'value'));
        $this->assertEmpty($cacheProperty->getValue($objectCache));
    }

    public function testSetReturnsTrueIfStoreValueInPersistentCacheSucceeds()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);

        $objectCache->expects($this->once())
                    ->method('storeValueInPersistentCache')
                    ->with($this->identicalTo('group:key'), $this->identicalTo('value'), $this->identicalTo(0), $this->identicalTo(0))
                    ->willReturn(true);

        $this->assertTrue($objectCache->set('group', 'key', 'value'));
        $this->assertSame(['group:key' => 'value'], $cacheProperty->getValue($objectCache));
    }

    public function testSetStoresAlloptionsInPersistentCacheWhenNotInPersistentCache()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);

        $objectCache->expects($this->exactly(3))
                    ->method('storeValueInPersistentCache')
                    ->withConsecutive(
                        [$this->identicalTo('alloptions_values:key1'), $this->identicalTo('value1'), $this->identicalTo(0), $this->identicalTo(0)],
                        [$this->identicalTo('alloptions_values:key2'), $this->identicalTo('value2'), $this->identicalTo(0), $this->identicalTo(0)],
                        [$this->identicalTo('options:alloptions_keys'), $this->identicalTo(['key1' => true, 'key2' => true]), $this->identicalTo(0), $this->identicalTo(0)]
                    )
                    ->willReturn(true);

        $this->assertTrue($objectCache->set('options', 'alloptions', ['key1' => 'value1', 'key2' => 'value2']));

        $this->assertSame([
            'options:alloptions_keys' => ['key1' => true, 'key2' => true],
            'alloptions_values:key1' => 'value1',
            'alloptions_values:key2' => 'value2',
            'options:alloptions' => ['key1' => 'value1', 'key2' => 'value2'],
        ], $cacheProperty->getValue($objectCache));
    }

    public function testSetStoresAlloptionsInPersistentCacheWhenPersistentCacheHasNoKeys()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);

        $objectCache->expects($this->exactly(2))
                    ->method('getValuesFromPersistentCache')
                    ->with($this->identicalTo('options:alloptions_keys'))
                    ->willReturn(false);

        $objectCache->expects($this->exactly(3))
                    ->method('storeValueInPersistentCache')
                    ->withConsecutive(
                        [$this->identicalTo('alloptions_values:key1'), $this->identicalTo('value1'), $this->identicalTo(0), $this->identicalTo(0)],
                        [$this->identicalTo('alloptions_values:key2'), $this->identicalTo('value2'), $this->identicalTo(0), $this->identicalTo(0)],
                        [$this->identicalTo('options:alloptions_keys'), $this->identicalTo(['key1' => true, 'key2' => true]), $this->identicalTo(0), $this->identicalTo(0)]
                    )
                    ->willReturn(true);

        $this->assertTrue($objectCache->set('options', 'alloptions', ['key1' => 'value1', 'key2' => 'value2']));

        $this->assertSame([
            'alloptions_values:key1' => 'value1',
            'alloptions_values:key2' => 'value2',
            'options:alloptions_keys' => ['key1' => true, 'key2' => true],
            'options:alloptions' => ['key1' => 'value1', 'key2' => 'value2'],
        ], $cacheProperty->getValue($objectCache));
    }

    public function testSetStoresDeletesUnusedAlloptionsOptionsInPersistentCache()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $cacheProperty->setValue($objectCache, ['alloptions_values:key1' => 'value1']);

        $objectCache->expects($this->once())
                    ->method('deleteValueFromPersistentCache')
                    ->with($this->identicalTo('alloptions_values:key1'))
                    ->willReturn(true);

        $objectCache->expects($this->once())
                    ->method('getValuesFromPersistentCache')
                    ->with($this->identicalTo('options:alloptions_keys'))
                    ->willReturn(['key1' => true]);

        $objectCache->expects($this->exactly(2))
                    ->method('storeValueInPersistentCache')
                    ->withConsecutive(
                        [$this->identicalTo('alloptions_values:key2'), $this->identicalTo('value2'), $this->identicalTo(0), $this->identicalTo(0)],
                        [$this->identicalTo('options:alloptions_keys'), $this->identicalTo(['key2' => true]), $this->identicalTo(0), $this->identicalTo(0)]
                    )
                    ->willReturn(true);

        $this->assertTrue($objectCache->set('options', 'alloptions', ['key2' => 'value2']));

        $this->assertSame([
            'options:alloptions_keys' => ['key2' => true],
            'alloptions_values:key2' => 'value2',
            'options:alloptions' => ['key2' => 'value2'],
        ], $cacheProperty->getValue($objectCache));
    }

    public function testSetStoresOnlyMissingAlloptionsOptionsInPersistentCache()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $cacheProperty = $objectCacheReflection->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $cacheProperty->setValue($objectCache, ['alloptions_values:key1' => 'value1']);

        $objectCache->expects($this->once())
                    ->method('getValuesFromPersistentCache')
                    ->with($this->identicalTo('options:alloptions_keys'))
                    ->willReturn(['key1' => true]);

        $objectCache->expects($this->exactly(2))
                    ->method('storeValueInPersistentCache')
                    ->withConsecutive(
                        [$this->identicalTo('alloptions_values:key2'), $this->identicalTo('value2'), $this->identicalTo(0), $this->identicalTo(0)],
                        [$this->identicalTo('options:alloptions_keys'), $this->identicalTo(['key1' => true, 'key2' => true]), $this->identicalTo(0), $this->identicalTo(0)]
                    )
                    ->willReturn(true);

        $this->assertTrue($objectCache->set('options', 'alloptions', ['key1' => 'value1', 'key2' => 'value2']));

        $this->assertSame([
            'alloptions_values:key1' => 'value1',
            'options:alloptions_keys' => ['key1' => true, 'key2' => true],
            'alloptions_values:key2' => 'value2',
            'options:alloptions' => ['key1' => 'value1', 'key2' => 'value2'],
        ], $cacheProperty->getValue($objectCache));
    }

    public function testSwitchToBlogWithMultisiteDisabled()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [false]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $blogIdProperty = $objectCacheReflection->getProperty('blogId');
        $blogIdProperty->setAccessible(true);

        $this->assertNull($blogIdProperty->getValue($objectCache));

        $objectCache->switchToBlog(3);

        $this->assertNull($blogIdProperty->getValue($objectCache));
    }

    public function testSwitchToBlogWithMultisiteEnabled()
    {
        $objectCache = $this->getMockForAbstractClass(AbstractPersistentObjectCache::class, [true]);
        $objectCacheReflection = new \ReflectionClass(AbstractPersistentObjectCache::class);

        $blogIdProperty = $objectCacheReflection->getProperty('blogId');
        $blogIdProperty->setAccessible(true);

        $this->assertNull($blogIdProperty->getValue($objectCache));

        $objectCache->switchToBlog(3);

        $this->assertSame(3, $blogIdProperty->getValue($objectCache));
    }
}
