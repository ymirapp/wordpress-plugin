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

namespace Ymir\Plugin\Support;

/**
 * Helper class for interacting with WordPress core functions.
 */
class WordPress
{
    /**
     * Check if a post save is an autosave or revision.
     */
    public static function isAutosaveOrRevision(int $postId): bool
    {
        if (!function_exists('wp_is_post_autosave') || !function_exists('wp_is_post_revision')) {
            return false;
        }

        return (bool) wp_is_post_autosave($postId) || (bool) wp_is_post_revision($postId);
    }
}
