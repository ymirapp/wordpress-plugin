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

namespace Ymir\Plugin\Subscriber\Compatibility;

use Ymir\Plugin\EventManagement\SubscriberInterface;

/**
 * Subscriber that handles WooCommerce compatibility.
 */
class WooCommerceSubscriber implements SubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'woocommerce_csv_importer_check_import_file_path' => 'disableCheckImportFilePath',
            'woocommerce_product_csv_importer_check_import_file_path' => 'disableCheckImportFilePath',
        ];
    }

    /**
     * Disable "check import file path" so that imports work with S3 storage.
     */
    public function disableCheckImportFilePath(): bool
    {
        return false;
    }
}
