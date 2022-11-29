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

namespace Ymir\Plugin;

use Ymir\Plugin\CloudStorage\PrivateCloudStorageStreamWrapper;
use Ymir\Plugin\CloudStorage\PublicCloudStorageStreamWrapper;
use Ymir\Plugin\Console\CommandInterface;
use Ymir\Plugin\DependencyInjection\Container;

/**
 * Ymir Plugin.
 */
class Plugin
{
    /**
     * The plugin's dependency injection container.
     *
     * @var Container
     */
    private $container;

    /**
     * The file path of the plugin.
     *
     * @var string
     */
    private $filePath;

    /**
     * Flag to track if the plugin is loaded.
     *
     * @var bool
     */
    private $loaded;

    /**
     * Constructor.
     */
    public function __construct(string $filePath)
    {
        if (!defined('ABSPATH')) {
            throw new \RuntimeException('"ABSPATH" constant isn\'t defined');
        }

        $rootDirectory = ABSPATH;

        if ('/wp/' === substr($rootDirectory, -4)) {
            $rootDirectory = substr($rootDirectory, 0, -3);
        }

        $this->container = new Container([
            'root_directory' => $rootDirectory,
            'plugin_name' => basename($filePath, '.php'),
        ]);
        $this->filePath = $filePath;

        $this->container->configure([
            Configuration\AssetsConfiguration::class,
            Configuration\AttachmentConfiguration::class,
            Configuration\CloudProviderConfiguration::class,
            Configuration\CloudStorageConfiguration::class,
            Configuration\ConsoleConfiguration::class,
            Configuration\EmailConfiguration::class,
            Configuration\EventManagementConfiguration::class,
            Configuration\PageCacheConfiguration::class,
            Configuration\PhpConfiguration::class,
            Configuration\RestApiConfiguration::class,
            Configuration\UploadsConfiguration::class,
            Configuration\WordPressConfiguration::class,
            Configuration\YmirConfiguration::class,
        ]);

        $this->loaded = false;
    }

    /**
     * Get the plugin's dependency injection container.
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Checks if the plugin is active.
     */
    public function isActive(): bool
    {
        return is_plugin_active($this->container['plugin_basename']) || preg_match('#mu-plugins/[^/]*/ymir\.php$#i', $this->filePath);
    }

    /**
     * Checks if the plugin is loaded.
     */
    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    /**
     * Checks if SES is enabled.
     */
    public function isSesEnabled(): bool
    {
        return !$this->container['ymir_using_vanity_domain']
            && false === getenv('YMIR_DISABLE_SES')
            && (!defined('YMIR_DISABLE_SES') || !YMIR_DISABLE_SES);
    }

    /**
     * Loads the plugin into WordPress.
     */
    public function load()
    {
        if ($this->isLoaded()) {
            return;
        }

        $this->container['plugin_basename'] = plugin_basename($this->filePath);
        $this->container['plugin_dir_path'] = plugin_dir_path($this->filePath);
        $this->container['plugin_dir_url'] = plugin_dir_url($this->filePath);
        $this->container['plugin_relative_path'] = '/'.trim(str_replace($this->container['root_directory'], '', plugin_dir_path($this->filePath)), '/');

        PrivateCloudStorageStreamWrapper::register($this->container['private_cloud_storage_client']);
        PublicCloudStorageStreamWrapper::register($this->container['public_cloud_storage_client']);

        foreach ($this->container['local_commands'] as $command) {
            $this->registerCommand($command);
        }

        if ($this->isLocal()) {
            return;
        }

        foreach ($this->container['priority_subscribers'] as $subscriber) {
            $this->container['event_manager']->addSubscriber($subscriber);
        }

        foreach ($this->container['subscribers'] as $subscriber) {
            $this->container['event_manager']->addSubscriber($subscriber);
        }

        foreach ($this->container['commands'] as $command) {
            $this->registerCommand($command);
        }

        $this->loaded = true;
    }

    /**
     * Checks if the code is running locally.
     */
    private function isLocal(): bool
    {
        return empty($this->container['ymir_environment']);
    }

    /**
     * Register the given command with WP-CLI.
     */
    private function registerCommand(CommandInterface $command)
    {
        if (!$this->container['is_wp_cli'] || !class_exists('\WP_CLI')) {
            return;
        }

        \WP_CLI::add_command($command::getName(), $command, [
            'shortdesc' => $command::getDescription(),
            'synopsis' => $command::getSynopsis(),
        ]);
    }
}
