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
     * Constructor.
     */
    public function __construct(string $serverSoftware)
    {
        $this->serverSoftware = strtolower($serverSoftware);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'got_url_rewrite' => 'enableUrlRewrite',
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
     * Replace the list of characters that WordPress uses to sanitize file names.
     *
     * We need to remove some characters since we url encode file names.
     */
    public function sanitizeFileNameCharacters(): array
    {
        return ['?', '[', ']', '/', '\\', '=', '<', '>', ':', ';', ',', "'", '"', '&', '$', '#', '*', '(', ')', '|', '~', '`', '!', '{', '}', '+', chr(0)];
    }
}
