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

namespace Ymir\Plugin\Tests\Unit\Subscriber;

use Ymir\Plugin\Subscriber\AdminSubscriber;
use Ymir\Plugin\Support\Collection;
use Ymir\Plugin\Tests\Mock\EventManagerMockTrait;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\Subscriber\AdminSubscriber
 */
class AdminSubscriberTest extends TestCase
{
    use EventManagerMockTrait;
    use FunctionMockTrait;

    public function testDisplayAdminNoticesAddsIsDismissibleClassWhenDismissibleIsTrue()
    {
        $eventManager = $this->getEventManagerMock();
        $notices = new Collection([
            ['message' => 'foo', 'type' => 'warning', 'dismissible' => true],
        ]);
        $printf = $this->getFunctionMock($this->getNamespace(AdminSubscriber::class), 'printf');
        $subscriber = new AdminSubscriber();

        $eventManager->expects($this->once())
                     ->method('filter')
                     ->with('ymir_admin_notices', $this->isInstanceOf(Collection::class))
                     ->willReturn($notices);

        $printf->expects($this->once())
               ->with(
                   $this->identicalTo('<div class="notice notice-%s %s"><p><strong>Ymir:</strong> %s</p></div>'),
                   $this->identicalTo('warning'),
                   $this->identicalTo('is-dismissible'),
                   $this->identicalTo('foo')
               );

        $subscriber->setEventManager($eventManager);

        $subscriber->displayAdminNotices();
    }

    public function testDisplayAdminNoticesConvertsStringToArray()
    {
        $eventManager = $this->getEventManagerMock();
        $notices = new Collection('foo');
        $printf = $this->getFunctionMock($this->getNamespace(AdminSubscriber::class), 'printf');
        $subscriber = new AdminSubscriber();

        $eventManager->expects($this->once())
                     ->method('filter')
                     ->with('ymir_admin_notices', $this->isInstanceOf(Collection::class))
                     ->willReturn($notices);

        $printf->expects($this->once())
               ->with(
                   $this->identicalTo('<div class="notice notice-%s %s"><p><strong>Ymir:</strong> %s</p></div>'),
                   $this->identicalTo('info'),
                   $this->identicalTo(''),
                   $this->identicalTo('foo')
               );

        $subscriber->setEventManager($eventManager);

        $subscriber->displayAdminNotices();
    }

    public function testDisplayAdminNoticesDefaultsToInfoTypeWithInvalidType()
    {
        $eventManager = $this->getEventManagerMock();
        $notices = new Collection([
            ['message' => 'foo', 'type' => 'bar'],
        ]);
        $printf = $this->getFunctionMock($this->getNamespace(AdminSubscriber::class), 'printf');
        $subscriber = new AdminSubscriber();

        $eventManager->expects($this->once())
                     ->method('filter')
                     ->with('ymir_admin_notices', $this->isInstanceOf(Collection::class))
                     ->willReturn($notices);

        $printf->expects($this->once())
               ->with(
                   $this->identicalTo('<div class="notice notice-%s %s"><p><strong>Ymir:</strong> %s</p></div>'),
                   $this->identicalTo('info'),
                   $this->identicalTo(''),
                   $this->identicalTo('foo')
               );

        $subscriber->setEventManager($eventManager);

        $subscriber->displayAdminNotices();
    }

    public function testDisplayAdminNoticesDoesNothingIfCollectionIsEmpty()
    {
        $eventManager = $this->getEventManagerMock();
        $printf = $this->getFunctionMock(AdminSubscriber::class, 'printf');
        $subscriber = new AdminSubscriber();

        $eventManager->expects($this->once())
                     ->method('filter')
                     ->with('ymir_admin_notices', $this->isInstanceOf(Collection::class))
                     ->willReturnArgument(1);

        $printf->expects($this->never());

        $subscriber->setEventManager($eventManager);

        $subscriber->displayAdminNotices();
    }

    public function testDisplayAdminNoticesDoesNothingIfCollectionItemIsntAnArrayOrString()
    {
        $eventManager = $this->getEventManagerMock();
        $notices = new Collection(1);
        $printf = $this->getFunctionMock(AdminSubscriber::class, 'printf');
        $subscriber = new AdminSubscriber();

        $eventManager->expects($this->once())
                     ->method('filter')
                     ->with('ymir_admin_notices', $this->isInstanceOf(Collection::class))
                     ->willReturn($notices);

        $printf->expects($this->never());

        $subscriber->setEventManager($eventManager);

        $subscriber->displayAdminNotices();
    }

    public function testDisplayAdminNoticesDoesNothingIfFilterDoesntReturnCollection()
    {
        $eventManager = $this->getEventManagerMock();
        $printf = $this->getFunctionMock(AdminSubscriber::class, 'printf');
        $subscriber = new AdminSubscriber();

        $eventManager->expects($this->once())
                     ->method('filter')
                     ->with('ymir_admin_notices', $this->isInstanceOf(Collection::class))
                     ->willReturn(null);

        $printf->expects($this->never());

        $subscriber->setEventManager($eventManager);

        $subscriber->displayAdminNotices();
    }

    public function testDisplayAdminNoticesDoesntAddIsDismissibleClassWhenDismissibleIsFalse()
    {
        $eventManager = $this->getEventManagerMock();
        $notices = new Collection([
            ['message' => 'foo', 'type' => 'warning', 'dismissible' => false],
        ]);
        $printf = $this->getFunctionMock($this->getNamespace(AdminSubscriber::class), 'printf');
        $subscriber = new AdminSubscriber();

        $eventManager->expects($this->once())
                     ->method('filter')
                     ->with('ymir_admin_notices', $this->isInstanceOf(Collection::class))
                     ->willReturn($notices);

        $printf->expects($this->once())
               ->with(
                   $this->identicalTo('<div class="notice notice-%s %s"><p><strong>Ymir:</strong> %s</p></div>'),
                   $this->identicalTo('warning'),
                   $this->identicalTo(''),
                   $this->identicalTo('foo')
               );

        $subscriber->setEventManager($eventManager);

        $subscriber->displayAdminNotices();
    }

    public function testDisplayAdminNoticesUsesValidType()
    {
        $eventManager = $this->getEventManagerMock();
        $notices = new Collection([
            ['message' => 'foo', 'type' => 'warning'],
        ]);
        $printf = $this->getFunctionMock($this->getNamespace(AdminSubscriber::class), 'printf');
        $subscriber = new AdminSubscriber();

        $eventManager->expects($this->once())
                     ->method('filter')
                     ->with('ymir_admin_notices', $this->isInstanceOf(Collection::class))
                     ->willReturn($notices);

        $printf->expects($this->once())
               ->with(
                   $this->identicalTo('<div class="notice notice-%s %s"><p><strong>Ymir:</strong> %s</p></div>'),
                   $this->identicalTo('warning'),
                   $this->identicalTo(''),
                   $this->identicalTo('foo')
               );

        $subscriber->setEventManager($eventManager);

        $subscriber->displayAdminNotices();
    }

    public function testGetSubscribedEvents()
    {
        $callbacks = AdminSubscriber::getSubscribedEvents();

        foreach ($callbacks as $callback) {
            $this->assertTrue(method_exists(AdminSubscriber::class, is_array($callback) ? $callback[0] : $callback));
        }

        $subscribedEvents = [
            'admin_notices' => 'displayAdminNotices',
        ];

        $this->assertSame($subscribedEvents, $callbacks);
    }
}
