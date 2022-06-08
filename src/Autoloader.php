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

/**
 * Autoloads plugin classes using PSR-4.
 */
class Autoloader
{
    /**
     * Handles autoloading of Ymir plugin classes.
     */
    public static function autoload(string $class)
    {
        if (0 !== strpos($class, __NAMESPACE__)) {
            return;
        }

        $class = substr($class, strlen(__NAMESPACE__));
        $file = __DIR__.str_replace(['\\', "\0"], ['/', ''], $class).'.php';

        if (is_file($file)) {
            require_once $file;
        }
    }

    /**
     * Registers the plugin autoloader as an SPL autoloader.
     */
    public static function register(bool $prepend = false)
    {
        spl_autoload_register([new self(), 'autoload'], true, $prepend);
    }
}
