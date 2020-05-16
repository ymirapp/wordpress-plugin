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
 * Base WP-CLI command.
 */
abstract class AbstractCommand implements CommandInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getName(): string
    {
        return 'ymir';
    }

    /**
     * Write error message.
     */
    protected function error(string $message)
    {
        if (!class_exists(\WP_CLI::class)) {
            return;
        }

        \WP_CLI::error($message);
    }

    /**
     * Write success message.
     */
    protected function success(string $message)
    {
        if (!class_exists(\WP_CLI::class)) {
            return;
        }

        \WP_CLI::success($message);
    }
}
