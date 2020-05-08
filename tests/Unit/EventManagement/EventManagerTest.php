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

namespace Ymir\Plugin\Tests\Unit\EventManagement;

use PHPUnit\Framework\TestCase;
use Ymir\Plugin\EventManagement\EventManager;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;

/**
 * @covers \Ymir\Plugin\EventManagement\EventManager
 */
class EventManagerTest extends TestCase
{
    use FunctionMockTrait;

    /**
     * @var EventManager
     */
    private $manager;

    protected function setUp()
    {
        require_once 'fixtures/functions.php';
        require_once 'fixtures/TestSubscriber.php';
        require_once 'fixtures/TestEventManagerAwareSubscriber.php';

        $this->manager = new EventManager();
    }

    protected function tearDown()
    {
        $this->manager = null;
    }

    public function testAddCallback()
    {
        $add_filter = $this->getFunctionMock($this->getNamespace(EventManager::class), 'add_filter');
        $add_filter->expects($this->once())
                   ->with($this->equalTo('foo'), $this->equalTo('on_foo'), $this->equalTo(5), $this->equalTo(2));

        $this->manager->addCallback('foo', 'on_foo', 5, 2);
    }

    public function testAddEventManagerAwareSubscriber()
    {
        $subscriber = new TestEventManagerAwareSubscriber();

        $add_filter = $this->getFunctionMock($this->getNamespace(EventManager::class), 'add_filter');
        $add_filter->expects($this->exactly(3))
                   ->withConsecutive(
                       [$this->equalTo('foo'), $this->identicalTo([$subscriber, 'on_foo']), $this->equalTo(10), $this->equalTo(1)],
                       [$this->equalTo('bar'), $this->identicalTo([$subscriber, 'on_bar']), $this->equalTo(5), $this->equalTo(1)],
                       [$this->equalTo('foobar'), $this->identicalTo([$subscriber, 'on_foobar']), $this->equalTo(5), $this->equalTo(2)]
                   );

        $this->manager->addSubscriber($subscriber);

        $reflection = new \ReflectionObject($subscriber);
        $eventManagerProperty = $reflection->getProperty('eventManager');
        $eventManagerProperty->setAccessible(true);

        $this->assertSame($this->manager, $eventManagerProperty->getValue($subscriber));
    }

    public function testAddSubscriber()
    {
        $subscriber = new TestSubscriber();

        $add_filter = $this->getFunctionMock($this->getNamespace(EventManager::class), 'add_filter');
        $add_filter->expects($this->exactly(3))
                   ->withConsecutive(
                       [$this->equalTo('foo'), $this->identicalTo([$subscriber, 'on_foo']), $this->equalTo(10), $this->equalTo(1)],
                       [$this->equalTo('bar'), $this->identicalTo([$subscriber, 'on_bar']), $this->equalTo(5), $this->equalTo(1)],
                       [$this->equalTo('foobar'), $this->identicalTo([$subscriber, 'on_foobar']), $this->equalTo(5), $this->equalTo(2)]
                   );

        $this->manager->addSubscriber($subscriber);
    }

    public function testExecute()
    {
        $do_action_ref_array = $this->getFunctionMock($this->getNamespace(EventManager::class), 'do_action_ref_array');
        $do_action_ref_array->expects($this->once())
                            ->with($this->equalTo('foo'), $this->equalTo(['bar']));

        $this->manager->execute('foo', 'bar');
    }

    public function testFilter()
    {
        $apply_filters_ref_array = $this->getFunctionMock($this->getNamespace(EventManager::class), 'apply_filters_ref_array');
        $apply_filters_ref_array->expects($this->once())
                                ->with($this->equalTo('foo'), $this->equalTo(['bar']))
                                ->willReturn('foobar');

        $this->assertEquals('foobar', $this->manager->filter('foo', 'bar'));
    }

    public function testGetCurrentHook()
    {
        $current_filter = $this->getFunctionMock($this->getNamespace(EventManager::class), 'current_filter');
        $current_filter->expects($this->once())
                       ->willReturn('foo');

        $this->assertEquals('foo', $this->manager->getCurrentHook());
    }

    public function testHasCallback()
    {
        $has_filter = $this->getFunctionMock($this->getNamespace(EventManager::class), 'has_filter');
        $has_filter->expects($this->once())
                   ->with($this->equalTo('foo'), $this->equalTo('on_foo'))
                   ->willReturn(10);

        $this->assertEquals(10, $this->manager->hasCallback('foo', 'on_foo'));
    }

    public function testRemoveCallback()
    {
        $remove_filter = $this->getFunctionMock($this->getNamespace(EventManager::class), 'remove_filter');
        $remove_filter->expects($this->once())
                      ->with($this->equalTo('foo'), $this->equalTo('on_foo'), $this->equalTo(2))
                      ->willReturn(true);

        $this->assertTrue($this->manager->removeCallback('foo', 'on_foo', 2));
    }

    public function testRemoveSubscriber()
    {
        $subscriber = new TestSubscriber();

        $remove_filter = $this->getFunctionMock($this->getNamespace(EventManager::class), 'remove_filter');
        $remove_filter->expects($this->exactly(3))
                      ->withConsecutive(
                          [$this->equalTo('foo'), $this->identicalTo([$subscriber, 'on_foo']), $this->equalTo(10)],
                          [$this->equalTo('bar'), $this->identicalTo([$subscriber, 'on_bar']), $this->equalTo(5)],
                          [$this->equalTo('foobar'), $this->identicalTo([$subscriber, 'on_foobar']), $this->equalTo(5)]
                      )
                      ->willReturn(true);

        $this->manager->removeSubscriber($subscriber);
    }
}
