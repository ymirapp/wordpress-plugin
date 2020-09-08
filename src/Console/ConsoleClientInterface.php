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
 * A client for running specific console commands on a serverless platform.
 */
interface ConsoleClientInterface
{
    /**
     * Create the metadata for the given attachment.
     */
    public function createAttachmentMetadata($attachment, bool $async = false);

    /**
     * Create a cropped version of the given attachment image.
     */
    public function createCroppedAttachmentImage($attachment, int $width, int $height, int $x, int $y, string $context = '', int $imageWidth = 0, int $imageHeight = 0): int;

    /**
     * Edit the given attachment image using the given changes.
     */
    public function editAttachmentImage($attachment, string $changes, string $apply = 'all');

    /**
     * Resize the given attachment image to the given width and height.
     */
    public function resizeAttachmentImage($attachment, int $width, int $height);

    /**
     * Run the WordPress cron for the given site URL.
     */
    public function runCron(string $siteUrl);
}
