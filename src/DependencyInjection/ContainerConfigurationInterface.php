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

/**
 * A container configuration object configures a dependency injection container during the build process.
 */
interface ContainerConfigurationInterface
{
    /**
     * Modifies the given dependency injection container.
     */
    public function modify(Container $container);
}
