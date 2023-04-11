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

use Ymir\Plugin\EventManagement\AbstractEventManagerAwareSubscriber;

/**
 * Subscriber that handles WooCommerce compatibility.
 */
class WooCommerceSubscriber extends AbstractEventManagerAwareSubscriber
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'init' => 'configureActionScheduler',
            'woocommerce_csv_importer_check_import_file_path' => 'disableCheckImportFilePath',
            'woocommerce_product_csv_importer_check_import_file_path' => 'disableCheckImportFilePath',
            'ymir_scheduled_site_cron_commands' => 'scheduleActionSchedulerCommand',
        ];
    }

    /**
     * Configure Action Scheduler to run on the cloud provider.
     */
    public function configureActionScheduler()
    {
        if (!class_exists(\ActionScheduler::class)) {
            return;
        }

        $this->eventManager->removeCallback('action_scheduler_run_queue', [\ActionScheduler::runner(), 'run']);
    }

    /**
     * Disable "check import file path" so that imports work with S3 storage.
     */
    public function disableCheckImportFilePath(): bool
    {
        return false;
    }

    /**
     * Schedule the action scheduler command to run during the cron process.
     */
    public function scheduleActionSchedulerCommand(array $commands): array
    {
        $commands[] = $this->eventManager->filter('ymir_woocommerce_action_scheduler_command', 'action-scheduler run --batches=1');

        return $commands;
    }
}
