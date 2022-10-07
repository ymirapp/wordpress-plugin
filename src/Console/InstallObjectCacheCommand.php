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
 * Command that installs the object cache drop-in.
 */
class InstallObjectCacheCommand extends AbstractCommand
{
    /**
     * The WordPress content directory.
     *
     * @var string
     */
    private $contentDirectory;

    /**
     * The file system.
     *
     * @var \WP_Filesystem_Direct
     */
    private $filesystem;

    /**
     * The path to the Ymir plugin directory.
     *
     * @var string
     */
    private $pluginDirectory;

    /**
     * Constructor.
     */
    public function __construct(string $contentDirectory, \WP_Filesystem_Direct $filesystem, string $pluginDirectory)
    {
        $this->contentDirectory = $contentDirectory;
        $this->filesystem = $filesystem;
        $this->pluginDirectory = $pluginDirectory;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(array $arguments, array $options)
    {
        $dropin = rtrim($this->contentDirectory, '/').'/object-cache.php';
        $force = isset($options['force']);

        if (!$force && $this->filesystem->exists($dropin)) {
            $this->error('Please use the "--force" option to overwrite an existing object-cache drop-in');
        } elseif (!$this->filesystem->copy(rtrim($this->pluginDirectory, '/').'/stubs/object-cache.php', $dropin, $force, fileperms(ABSPATH.'index.php') & 0777 | 0644)) {
            $this->error('Unable to copy the object cache drop-in');
        }

        if (function_exists('wp_opcache_invalidate')) {
            wp_opcache_invalidate($dropin, true);
        }

        $this->success('Object cache drop-in installed successfully');
    }

    /**
     * {@inheritdoc}
     */
    public static function getDescription(): string
    {
        return 'Install the Ymir object cache drop-in';
    }

    /**
     * {@inheritdoc}
     */
    public static function getSynopsis(): array
    {
        return [
            [
                'type' => 'flag',
                'name' => 'force',
                'description' => 'Force the installation of the drop-in even if one is present already',
                'optional' => true,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected static function getCommandName(): string
    {
        return 'install-object-cache';
    }
}
