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

namespace Ymir\Plugin\Console;

/**
 * A WP-CLI command.
 */
interface CommandInterface
{
    /**
     * Executes the command.
     */
    public function __invoke(array $arguments, array $options);

    /**
     * Get the command description.
     */
    public static function getDescription(): string;

    /**
     * Get the command name.
     */
    public static function getName(): string;

    /**
     * Get the positional and associative arguments a command accepts.
     */
    public static function getSynopsis(): array;
}
