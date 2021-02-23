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

namespace Ymir\Plugin\DependencyInjection;

use Ymir\Plugin\Plugin;

/**
 * Trait used to get the services from the dependency injection container
 * when a class can't have their dependencies injected.
 */
trait ServiceLocatorTrait
{
    /**
     * Get a service from the plugin's dependency injection container.
     */
    protected static function getService(string $service)
    {
        global $ymir;

        if (!$ymir instanceof Plugin) {
            throw new \RuntimeException('Ymir plugin isn\'t active');
        }

        return $ymir->getContainer()->get($service);
    }
}
