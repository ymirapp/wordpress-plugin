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
     * WP-CLI.
     *
     * @var WpCli
     */
    protected $wpCli;

    /**
     * {@inheritdoc}
     */
    public function __construct(WpCli $wpCli)
    {
        $this->wpCli = $wpCli;
    }

    /**
     * {@inheritdoc}
     */
    final public static function getName(): string
    {
        return sprintf('ymir %s', static::getCommandName());
    }

    /**
     * {@inheritdoc}
     */
    public static function getSynopsis(): array
    {
        return [];
    }

    /**
     * Get the "ymir" command name.
     */
    abstract protected static function getCommandName(): string;
}
