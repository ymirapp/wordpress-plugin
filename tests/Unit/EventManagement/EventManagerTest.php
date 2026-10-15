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

use Ymir\Plugin\EventManagement\EventManager;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

class EventManagerTest extends TestCase
{
    use FunctionMockTrait;

    /**
     * Whether WordPress's request environment existed before the test.
     *
     * @var bool
     */
    private $hadWordPress;

    /**
     * @var EventManager
     */
    private $manager;

    /**
     * The WordPress request environment before the test.
     */
    private $wp;

    protected function setUp(): void
    {
        require_once 'fixtures/functions.php';
        require_once 'fixtures/TestSubscriber.php';
        require_once 'fixtures/TestEventManagerAwareSubscriber.php';
        require_once 'fixtures/TestWordPressAwareSubscriber.php';

        $this->hadWordPress = array_key_exists('wp', $GLOBALS);
        $this->wp = $GLOBALS['wp'] ?? null;
        unset($GLOBALS['wp']);

        $this->manager = new EventManager();
    }

    protected function tearDown(): void
    {
        if ($this->hadWordPress) {
            $GLOBALS['wp'] = $this->wp;
        } else {
            unset($GLOBALS['wp']);
        }

        $this->manager = null;
    }

    public function provideWordPressAvailability(): array
    {
        return [
            'before WordPress exists' => [false],
            'WordPress already exists' => [true],
        ];
    }

    public function testAddCallback(): void
    {
        $add_filter = $this->getFunctionMock($this->getNamespace(EventManager::class), 'add_filter');
        $add_filter->expects($this->once())
                   ->with($this->equalTo('foo'), $this->equalTo('on_foo'), $this->equalTo(5), $this->equalTo(2));

        $this->manager->addCallback('foo', 'on_foo', 5, 2);
    }

    public function testAddEventManagerAwareSubscriber(): void
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

    public function testAddSubscriber(): void
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

    /**
     * @dataProvider provideWordPressAvailability
     */
    public function testAddWordPressAwareSubscriber(bool $wordPressExists): void
    {
        if ($wordPressExists) {
            $GLOBALS['wp'] = new \WP();
        }

        $subscriber = new TestWordPressAwareSubscriber();
        $injection = null;

        $this->getFunctionMock($this->getNamespace(EventManager::class), 'add_filter')
             ->expects($this->exactly(4))
             ->withConsecutive(
                 [$this->equalTo('wp'), $this->identicalTo([$subscriber, 'setWordPress']), $this->equalTo(10), $this->equalTo(1)],
                 [$this->equalTo('foo'), $this->identicalTo([$subscriber, 'on_foo']), $this->equalTo(10), $this->equalTo(1)],
                 [$this->equalTo('bar'), $this->identicalTo([$subscriber, 'on_bar']), $this->equalTo(5), $this->equalTo(1)],
                 [$this->equalTo('foobar'), $this->identicalTo([$subscriber, 'on_foobar']), $this->equalTo(5), $this->equalTo(2)]
             )
             ->willReturnCallback(function (string $hookName, callable $callback) use (&$injection) {
                 if ('wp' === $hookName) {
                     $injection = $callback;
                 }

                 return true;
             });

        $this->manager->addSubscriber($subscriber);

        $this->assertNull($subscriber->wp);
        $this->assertIsCallable($injection);

        $GLOBALS['wp'] = $GLOBALS['wp'] ?? new \WP();
        $injection($GLOBALS['wp']);

        $this->assertSame($GLOBALS['wp'], $subscriber->wp);
    }

    public function testExecute(): void
    {
        $do_action_ref_array = $this->getFunctionMock($this->getNamespace(EventManager::class), 'do_action_ref_array');
        $do_action_ref_array->expects($this->once())
                            ->with($this->equalTo('foo'), $this->equalTo(['bar']));

        $this->manager->execute('foo', 'bar');
    }

    public function testFilter(): void
    {
        $apply_filters_ref_array = $this->getFunctionMock($this->getNamespace(EventManager::class), 'apply_filters_ref_array');
        $apply_filters_ref_array->expects($this->once())
                                ->with($this->equalTo('foo'), $this->equalTo(['bar']))
                                ->willReturn('foobar');

        $this->assertEquals('foobar', $this->manager->filter('foo', 'bar'));
    }

    public function testGetCurrentHook(): void
    {
        $current_filter = $this->getFunctionMock($this->getNamespace(EventManager::class), 'current_filter');
        $current_filter->expects($this->once())
                       ->willReturn('foo');

        $this->assertEquals('foo', $this->manager->getCurrentHook());
    }

    public function testHasCallback(): void
    {
        $has_filter = $this->getFunctionMock($this->getNamespace(EventManager::class), 'has_filter');
        $has_filter->expects($this->once())
                   ->with($this->equalTo('foo'), $this->equalTo('on_foo'))
                   ->willReturn(10);

        $this->assertEquals(10, $this->manager->hasCallback('foo', 'on_foo'));
    }

    public function testRemoveCallback(): void
    {
        $remove_filter = $this->getFunctionMock($this->getNamespace(EventManager::class), 'remove_filter');
        $remove_filter->expects($this->once())
                      ->with($this->equalTo('foo'), $this->equalTo('on_foo'), $this->equalTo(2))
                      ->willReturn(true);

        $this->assertTrue($this->manager->removeCallback('foo', 'on_foo', 2));
    }

    public function testRemoveSubscriber(): void
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

    public function testRemoveWordPressAwareSubscriber(): void
    {
        $subscriber = new TestWordPressAwareSubscriber();

        $this->getFunctionMock($this->getNamespace(EventManager::class), 'remove_filter')
             ->expects($this->exactly(4))
             ->withConsecutive(
                 [$this->equalTo('wp'), $this->identicalTo([$subscriber, 'setWordPress']), $this->equalTo(10)],
                 [$this->equalTo('foo'), $this->identicalTo([$subscriber, 'on_foo']), $this->equalTo(10)],
                 [$this->equalTo('bar'), $this->identicalTo([$subscriber, 'on_bar']), $this->equalTo(5)],
                 [$this->equalTo('foobar'), $this->identicalTo([$subscriber, 'on_foobar']), $this->equalTo(5)]
             )
             ->willReturn(true);

        $this->manager->removeSubscriber($subscriber);
    }
}
