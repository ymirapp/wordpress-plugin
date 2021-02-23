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

use Ymir\Plugin\Support\Collection;

/**
 * Command to edit an attachment image.
 */
class EditAttachmentImageCommand extends AbstractAttachmentCommand
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(array $arguments, array $options)
    {
        $changes = json_decode($arguments[1]);

        if (!is_array($changes)) {
            $this->error('Unable to decode the "changes" argument');
        }

        $apply = strtolower($options['apply']);
        $attachment = $this->getAttachment($arguments[0]);
        $filePath = $this->getFilePath($attachment);
        $image = image_edit_apply_changes($this->getImageEditor($filePath), $changes);
        $imageMetadata = $this->getAttachmentMetadata($attachment);
        $suffix = '';

        if ('thumbnail' === $apply || $this->attachmentFileNeedsSuffix($attachment, $filePath)) {
            $suffix = $this->generateSuffix($filePath);
            $filePath = $this->generateSuffixedFilePath($filePath, $suffix);
        }

        if (!wp_save_image_file($filePath, $image, $attachment->post_mime_type, $attachment->ID)) {
            $this->error(sprintf('Unable to save image "%s"', $filePath));
        }

        if ($this->isImageCleanupActive() && !empty($imageMetadata['sizes'])) {
            $this->deletePreviousImageVersions((array) $imageMetadata['sizes']);
        }

        if (!empty($suffix)) {
            $this->addBackupImageSizes($attachment, $imageMetadata, $suffix);
        }

        if ('thumbnail' !== $apply) {
            $imageSize = $image->get_size();

            $imageMetadata['file'] = $this->updateAttachedFilePath($attachment, $filePath);
            $imageMetadata['width'] = $imageSize['width'] ?? 0;
            $imageMetadata['height'] = $imageSize['height'] ?? 0;
        }

        $imageMetadata['sizes'] = array_merge($imageMetadata['sizes'], $image->multi_resize($this->getImageSizeDimensions($apply)));

        if ('thumbnail' === $apply) {
            wp_delete_file($filePath);
        }

        wp_update_attachment_metadata($attachment->ID, $imageMetadata);

        $this->success(sprintf('Edited attachment "%s"', $attachment->ID));
    }

    /**
     * {@inheritdoc}
     */
    public static function getDescription(): string
    {
        return 'Edit the image associated with the given attachment';
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
                'type' => 'positional',
                'name' => 'changes',
                'description' => 'JSON encoded list of operations to perform on the attachment image',
            ],
            [
                'type' => 'assoc',
                'name' => 'apply',
                'description' => 'The image sizes to apply the changes to',
                'default' => 'all',
                'options' => ['all', 'full', 'nothumb', 'thumbnail'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected static function getCommandName(): string
    {
        return 'edit-attachment-image';
    }

    /**
     * Delete all previous image versions.
     */
    private function deletePreviousImageVersions(array $images)
    {
        (new Collection($images))->filter()->map(function ($image) {
            return is_array($image) && !empty($image['file']) && preg_match('/-e[0-9]{13}-/', $image['file']) ? $image['file'] : null;
        })->each(function (string $imageFile) {
            wp_delete_file($this->fileManager->getUploadsFilePath($imageFile));
        });
    }

    /**
     * Get all the image sizes and their dimensions for the given "apply" value.
     */
    private function getImageSizeDimensions(string $apply): array
    {
        $imageSizes = [];
        $sizes = $this->getImageSizes($apply);

        foreach ($sizes as $size) {
            $imageSizes[$size] = $this->getSizeDimensions($size, $apply);
        }

        return $imageSizes;
    }

    /**
     * Get all the image sizes for the given "apply" value.
     */
    private function getImageSizes(string $apply): array
    {
        if ('full' === $apply) {
            return [];
        }

        $thumbnail = ['thumbnail'];

        if ('thumbnail' === $apply) {
            return $thumbnail;
        }

        $sizes = get_intermediate_image_sizes();

        if ('nothumb' === $apply) {
            $sizes = array_diff($sizes, $thumbnail);
        }

        return $sizes;
    }

    /**
     * Get the dimensions of an image of a given size.
     */
    private function getSizeDimensions(string $size, string $apply): array
    {
        $additionalSizes = wp_get_additional_image_sizes();

        return [
            'crop' => 'thumbnail' === $apply ? false : (isset($additionalSizes[$size]['crop']) ? $additionalSizes[$size]['crop'] : get_option("{$size}_crop")),
            'width' => isset($additionalSizes[$size]['width']) ? (int) $additionalSizes[$size]['width'] : get_option("{$size}_size_w"),
            'height' => isset($additionalSizes[$size]['height']) ? (int) $additionalSizes[$size]['height'] : get_option("{$size}_size_h"),
        ];
    }
}
