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
 * Command for creating a cropped attachment image.
 */
class CreateCroppedImageCommand extends AbstractCropAttachmentImageCommand
{
    /**
     * {@inheritdoc}
     */
    public static function getDescription(): string
    {
        return 'Create a cropped attachment image';
    }

    /**
     * {@inheritdoc}
     */
    public static function getSynopsis(): array
    {
        $arguments = parent::getSynopsis();

        $arguments[] = [
            'type' => 'assoc',
            'name' => 'context',
            'description' => 'The context of the image crop',
        ];

        return $arguments;
    }

    /**
     * {@inheritdoc}
     */
    protected static function getCommandName(): string
    {
        return 'create-cropped-image';
    }

    /**
     * {@inheritdoc}
     */
    protected function createCroppedImageAttachment(\WP_Post $originalAttachment, string $context, string $croppedImage): int
    {
        if ('site-icon' === $context) {
            $this->error(sprintf('Please use the "%s" command to create a site icon', CreateSiteIconCommand::getName()));
        }

        $this->eventManager->execute('wp_ajax_crop_image_pre_save', $context, $originalAttachment->ID, $croppedImage);

        $croppedImage = $this->eventManager->filter('wp_create_file_in_uploads', $croppedImage, $originalAttachment->ID);

        $originalUrl = wp_get_attachment_url($originalAttachment->ID);
        $url = str_replace(wp_basename($originalUrl), wp_basename($croppedImage), $originalUrl);

        $size = @getimagesize($croppedImage);

        $attachment = [
            'post_title' => wp_basename($croppedImage),
            'post_content' => $url,
            'post_mime_type' => $size['mime'] ?? 'image/jpeg',
            'guid' => $url,
            'context' => $context,
        ];

        $attachmentId = wp_insert_attachment($attachment, $croppedImage);
        $metadata = wp_generate_attachment_metadata($attachmentId, $croppedImage);

        $metadata = $this->eventManager->filter('wp_ajax_cropped_attachment_metadata', $metadata);

        wp_update_attachment_metadata($attachmentId, $metadata);

        $attachmentId = $this->eventManager->filter('wp_ajax_cropped_attachment_id', $attachmentId, $context);

        return $attachmentId;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSuccessMessage(): string
    {
        return 'Cropped attachment image successfully created';
    }
}
