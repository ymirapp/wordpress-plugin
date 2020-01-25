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
     * Check if all the required arguments are the given arguments array.
     */
    protected function checkRequiredArguments(array $arguments, array $requiredArguments = [])
    {
        $missingArguments = array_diff_key($requiredArguments, $arguments);

        if (empty($missingArguments)) {
            return;
        }

        $this->missingError(reset($missingArguments), 'argument');
    }

    /**
     * Check if all the required options are the given options array.
     */
    protected function checkRequiredOptions(array $options, array $requiredOptions = [])
    {
        $missingOptions = array_diff($requiredOptions, array_keys($options));

        if (empty($missingOptions)) {
            return;
        }

        $this->missingError(reset($missingOptions), 'option');
    }

    /**
     * Write error message.
     */
    protected function error(string $message)
    {
        \WP_CLI::error($message);
    }

    /**
     * Write success message.
     */
    protected function success(string $message)
    {
        \WP_CLI::success($message);
    }

    /**
     * Generate a missing error.
     */
    private function missingError(string $name, string $type)
    {
        $this->error(sprintf('"%s" %s is missing', $name, $type));
    }
}
