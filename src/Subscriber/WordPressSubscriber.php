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

namespace Ymir\Plugin\Subscriber;

use Ymir\Plugin\EventManagement\SubscriberInterface;

/**
 * Subscriber for interacting with WordPress core functions.
 */
class WordPressSubscriber implements SubscriberInterface
{
    /**
     * The server identification string received by PHP.
     *
     * @var string
     */
    private $serverSoftware;

    /**
     * WordPress site URL.
     *
     * @var string
     */
    private $siteUrl;

    /**
     * Constructor.
     */
    public function __construct(string $serverSoftware, string $siteUrl)
    {
        $this->serverSoftware = strtolower($serverSoftware);
        $this->siteUrl = rtrim($siteUrl, '/');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'got_url_rewrite' => 'enableUrlRewrite',
            'plugins_url' => 'rewritePluginUrl',
            'sanitize_file_name_chars' => 'sanitizeFileNameCharacters',
            'user_can_richedit' => 'enableVisualEditor',
        ];
    }

    /**
     * Overwrite URL rewrite setting if we're on the Ymir runtime.
     */
    public function enableUrlRewrite(bool $urlRewriteEnabled): bool
    {
        if ('ymir' === $this->serverSoftware) {
            $urlRewriteEnabled = true;
        }

        return $urlRewriteEnabled;
    }

    /**
     * Overwrite URL rewrite setting if we're on the Ymir runtime.
     */
    public function enableVisualEditor(bool $visualEditorEnabled): bool
    {
        if ('ymir' === $this->serverSoftware) {
            $visualEditorEnabled = true;
        }

        return $visualEditorEnabled;
    }

    /**
     * Rewrite the plugin URL so that it matches the site URL.
     *
     * The plugin URL doesn't use the current site URL when you're on the non-primary site
     * of a multisite installation. This breaks the URL rewriting for pointing assets to the
     * assets URL.
     */
    public function rewritePluginUrl(string $url): string
    {
        $matches = [];
        preg_match('/http(s)?:\/\/.*(\/[^\/]*\/plugins.*)/', $url, $matches);

        if (empty($matches[2])) {
            return $url;
        }

        return $this->siteUrl.$matches[2];
    }

    /**
     * Replace the list of characters that WordPress uses to sanitize file names.
     *
     * We need to remove some characters since we url encode file names.
     */
    public function sanitizeFileNameCharacters(): array
    {
        return ['?', '[', ']', '/', '\\', '=', '<', '>', ':', ';', ',', "'", '"', '&', '$', '#', '*', '(', ')', '|', '~', '`', '!', '{', '}', '+', chr(0)];
    }
}
