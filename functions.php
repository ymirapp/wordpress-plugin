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

use Ymir\Plugin\Console\ConsoleClientInterface;
use Ymir\Plugin\Plugin;

/**
 * Run the given WP-CLI command.
 *
 * The command will run asynchronously using the "console" lambda function. By default, the command will run
 * asynchronously. For multisite installs, the command will run on the main site unless a site URL is given.
 *
 * @api
 */
function ymir_run_wp_cli_command(string $command, bool $async = true, string $siteUrl = '')
{
    global $ymir;

    if (!$ymir instanceof Plugin) {
        throw new \RuntimeException('Ymir plugin isn\'t active');
    }

    $client = $ymir->getContainer()->get('console_client');

    if (!$client instanceof ConsoleClientInterface) {
        throw new \RuntimeException('Unable to get the console client');
    }

    $client->runWpCliCommand($command, $async, $siteUrl);
}
