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
 * Command to resize an attachment image.
 */
class ResizeAttachmentImageCommand extends AbstractAttachmentCommand
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(array $arguments, array $options)
    {
        $attachment = $this->getAttachment($arguments[0]);
        $imageMetadata = $this->getAttachmentMetadata($attachment);
        $height = (int) $options['height'];
        $width = (int) $options['width'];

        if ($height <= 0) {
            $this->error('"height" must be greater than 0');
        } elseif ($width <= 0) {
            $this->error('"width" must be greater than 0');
        }

        $filePath = $this->getFilePath($attachment);
        $image = $this->getImageEditor($filePath);

        if ($image->resize($width, $height) instanceof \WP_Error) {
            $this->error(sprintf('Error trying to resize attachment "%s"', $attachment->ID));
        }

        $suffix = '';

        if ($this->attachmentFileNeedsSuffix($attachment, $filePath)) {
            $suffix = $this->generateSuffix($filePath);
            $filePath = $this->generateSuffixedFilePath($filePath, $suffix);
        }

        if (!wp_save_image_file($filePath, $image, $attachment->post_mime_type, $attachment->ID)) {
            $this->error(sprintf('Unable to save image "%s"', $filePath));
        }

        if (!empty($suffix)) {
            $this->addBackupImageSizes($attachment, $imageMetadata, $suffix);
        }

        $imageSize = $image->get_size();

        $imageMetadata['file'] = $this->updateAttachedFilePath($attachment, $filePath);
        $imageMetadata['width'] = $imageSize['width'] ?? 0;
        $imageMetadata['height'] = $imageSize['height'] ?? 0;

        wp_update_attachment_metadata($attachment->ID, $imageMetadata);

        $this->success(sprintf('Resized attachment "%s" to %sx%s', $attachment->ID, $imageMetadata['width'], $imageMetadata['height']));
    }

    /**
     * {@inheritdoc}
     */
    public static function getDescription(): string
    {
        return 'Resizes the image associated with the given attachment';
    }

    /**
     * {@inheritdoc}
     */
    public static function getSynopsis(): array
    {
        return [
            [
                'type' => 'positional',
                'name' => 'attachmentId',
                'description' => 'The ID of the attachment',
            ],
            [
                'type' => 'assoc',
                'name' => 'height',
                'description' => 'The height of the resized image',
            ],
            [
                'type' => 'assoc',
                'name' => 'width',
                'description' => 'The width of the resized image',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected static function getCommandName(): string
    {
        return 'resize-attachment-image';
    }
}
