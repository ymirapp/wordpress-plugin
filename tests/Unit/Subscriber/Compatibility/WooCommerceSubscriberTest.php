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

namespace Ymir\Plugin\Tests\Unit\Subscriber\Compatibility;

use Ymir\Plugin\Subscriber\Compatibility\WooCommerceSubscriber;
use Ymir\Plugin\Tests\Mock\EventManagerMockTrait;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\Subscriber\Compatibility\WooCommerceSubscriber
 */
class WooCommerceSubscriberTest extends TestCase
{
    use EventManagerMockTrait;
    use FunctionMockTrait;

    public function testConfigureActionSchedulerWhenActionSchedulerClassDoesntExist()
    {
        $class_exists = $this->getFunctionMock($this->getNamespace(WooCommerceSubscriber::class), 'class_exists');
        $class_exists->expects($this->once())
                     ->with($this->identicalTo('ActionScheduler'))
                     ->willReturn(false);

        $eventManager = $this->getEventManagerMock();
        $eventManager->expects($this->never())
                     ->method('removeCallback');

        $subscriber = new WooCommerceSubscriber();

        $subscriber->setEventManager($eventManager);

        $subscriber->configureActionScheduler();
    }

    public function testDisableCheckImportFilePath()
    {
        $this->assertFalse((new WooCommerceSubscriber())->disableCheckImportFilePath());
    }

    public function testGetSubscribedEvents()
    {
        $callbacks = WooCommerceSubscriber::getSubscribedEvents();

        foreach ($callbacks as $callback) {
            $this->assertTrue(method_exists(WooCommerceSubscriber::class, is_array($callback) ? $callback[0] : $callback));
        }

        $subscribedEvents = [
            'init' => 'configureActionScheduler',
            'woocommerce_csv_importer_check_import_file_path' => 'disableCheckImportFilePath',
            'woocommerce_product_csv_importer_check_import_file_path' => 'disableCheckImportFilePath',
            'ymir_scheduled_site_cron_commands' => 'scheduleActionSchedulerCommand',
        ];

        $this->assertSame($subscribedEvents, $callbacks);
    }

    public function testScheduleActionSchedulerCommand()
    {
        $eventManager = $this->getEventManagerMock();
        $eventManager->expects($this->once())
                     ->method('filter')
                     ->with($this->identicalTo('ymir_woocommerce_action_scheduler_command'), $this->identicalTo('action-scheduler run --batches=1'))
                     ->willReturnArgument(1);

        $subscriber = new WooCommerceSubscriber();

        $subscriber->setEventManager($eventManager);

        $this->assertSame(['action-scheduler run --batches=1'], $subscriber->scheduleActionSchedulerCommand([]));
    }
}
