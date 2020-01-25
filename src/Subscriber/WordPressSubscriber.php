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
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'sanitize_file_name_chars' => 'sanitizeFileNameCharacters',
        ];
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
