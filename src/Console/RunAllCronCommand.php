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

use Ymir\Plugin\EventManagement\EventManager;
use Ymir\Plugin\Support\Collection;

/**
 * Command that runs all the scheduled cron commands on all WordPress sites.
 */
class RunAllCronCommand extends AbstractCommand
{
    /**
     * The console serverless function client.
     *
     * @var ConsoleClientInterface
     */
    private $consoleClient;

    /**
     * The plugin event manager.
     *
     * @var EventManager
     */
    private $eventManager;

    /**
     * Class used for querying the WordPress sites.
     *
     * @var \WP_Site_Query
     */
    private $siteQuery;

    /**
     * Constructor.
     */
    public function __construct(ConsoleClientInterface $consoleClient, EventManager $eventManager, WpCli $wpCli, ?\WP_Site_Query $siteQuery = null)
    {
        parent::__construct($wpCli);

        $this->consoleClient = $consoleClient;
        $this->eventManager = $eventManager;
        $this->siteQuery = $siteQuery;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(array $arguments, array $options)
    {
        $this->wpCli->info('Beginning to run all scheduled cron commands');

        $this->getSiteUrls()->each(function (string $siteUrl) {
            $commands = (new Collection($this->eventManager->filter('ymir_scheduled_site_cron_commands', ['cron event run --due-now --quiet'], $siteUrl)));

            $commands->map(function (string $command) {
                if (0 === strpos($command, 'wp ')) {
                    $command = substr($command, 3);
                }

                return $command;
            })->filter(function (string $command) {
                return $this->wpCli->isCommandRegistered($command);
            })->each(function (string $command) use ($siteUrl) {
                $this->wpCli->info(sprintf('Running "wp %s" on "%s"', $command, $siteUrl));

                $this->consoleClient->runWpCliCommand($command, true, $siteUrl);
            });
        });

        $this->wpCli->success('All scheduled cron commands run successfully');
    }

    /**
     * {@inheritdoc}
     */
    public static function getDescription(): string
    {
        return 'Runs all the scheduled cron commands on all WordPress sites';
    }

    /**
     * {@inheritdoc}
     */
    protected static function getCommandName(): string
    {
        return 'run-all-cron';
    }

    /**
     * Get all the site URLs for this WordPress installation.
     */
    private function getSiteUrls(): Collection
    {
        $blogIds = [0];

        if ($this->siteQuery instanceof \WP_Site_Query) {
            $blogIds = array_map(function (\WP_Site $site) {
                return (int) $site->blog_id;
            }, $this->siteQuery->query([
                'number' => 0,
                'spam' => 0,
                'deleted' => 0,
                'archived' => 0,
            ]));
        }

        return (new Collection($blogIds))->map(function (int $blogId) {
            return get_site_url($blogId);
        })->filter()->values();
    }
}
