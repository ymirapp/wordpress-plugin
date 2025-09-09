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

class LifterLmsSubscriber implements SubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'llms_setup_wizard_access' => 'changeSetupWizardAccess',
        ];
    }

    /**
     * When the filesystem is read-only, WordPress removes "install_plugins" capability. LifterLMS uses that capability
     * to check if you can use the setup wizard. We change this to another administrator capability.
     */
    public function changeSetupWizardAccess(): string
    {
        return 'manage_options';
    }
}
