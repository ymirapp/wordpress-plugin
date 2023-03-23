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
 * Helper class for interacting with WP-CLI.
 */
class WpCli
{
    /**
     * Write error message.
     */
    public function error(string $message)
    {
        \WP_CLI::error($message);
    }

    /**
     * Write an information message.
     */
    public function info(string $message)
    {
        \WP_CLI::log($message);
    }

    /**
     * Check if the given WP-CLI command is registered.
     */
    public function isCommandRegistered(string $command): bool
    {
        return is_array(\WP_CLI::get_runner()->find_command_to_run($command));
    }

    /**
     * Register the given command with WP-CLI.
     */
    public function registerCommand(CommandInterface $command)
    {
        if (!$this->isWpCliActive()) {
            return;
        }

        \WP_CLI::add_command($command::getName(), $command, [
            'shortdesc' => $command::getDescription(),
            'synopsis' => $command::getSynopsis(),
        ]);
    }

    /**
     * Write success message.
     */
    public function success(string $message)
    {
        \WP_CLI::success($message);
    }

    /**
     * Checks if WP-CLI is active.
     */
    private function isWpCliActive(): bool
    {
        return defined('WP_CLI') && WP_CLI;
    }
}
