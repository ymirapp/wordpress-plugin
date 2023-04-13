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
            'woocommerce_csv_importer_check_import_file_path' => 'disableCheckImportFilePath',
            'woocommerce_product_csv_importer_check_import_file_path' => 'disableCheckImportFilePath',
        ];

        $this->assertSame($subscribedEvents, $callbacks);
    }
}
