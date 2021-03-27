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
 * Command that runs the "wp cron event run" command on all WordPress sites.
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
     * Class used for querying the WordPress sites.
     *
     * @var \WP_Site_Query
     */
    private $siteQuery;

    /**
     * Constructor.
     */
    public function __construct(ConsoleClientInterface $consoleClient, ?\WP_Site_Query $siteQuery = null)
    {
        $this->consoleClient = $consoleClient;
        $this->siteQuery = $siteQuery;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(array $arguments, array $options)
    {
        foreach ($this->getSiteUrls() as $siteUrl) {
            $this->info(sprintf('Running "wp cron event run" on "%s"', $siteUrl));
            $this->consoleClient->runCron($siteUrl);
        }
        $this->success('All cron commands run successfully');
    }

    /**
     * {@inheritdoc}
     */
    public static function getDescription(): string
    {
        return 'Runs the "wp cron event run" command on all WordPress sites';
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
    private function getSiteUrls(): array
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

        return array_filter(array_map(function (int $blogId) {
            return get_site_url($blogId);
        }, $blogIds));
    }
}
