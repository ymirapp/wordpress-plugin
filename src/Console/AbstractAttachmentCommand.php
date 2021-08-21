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

use Ymir\Plugin\Attachment\AttachmentFileManager;

/**
 * Base WP-CLI command for dealing with attachments.
 */
abstract class AbstractAttachmentCommand extends AbstractCommand
{
    /**
     * The attachment file manager.
     *
     * @var AttachmentFileManager
     */
    protected $fileManager;

    /**
     * Constructor.
     */
    public function __construct(AttachmentFileManager $fileManager)
    {
        if (defined('ABSPATH')) {
            require_once ABSPATH.'wp-admin/includes/image.php';
            require_once ABSPATH.'wp-admin/includes/image-edit.php';
        }

        $this->fileManager = $fileManager;
    }

    /**
     * Add all the backup image sizes using the image metadata.
     */
    protected function addBackupImageSizes(\WP_Post $attachment, array $imageMetadata, string $suffix)
    {
        $backupSizes = $this->getBackupImageSizes($attachment);

        if (isset($imageMetadata['file'], $imageMetadata['height'], $imageMetadata['width'])) {
            $backupSizes = $this->addBackupSize($backupSizes, $imageMetadata, 'full', $suffix);
        }

        if (empty($imageMetadata['sizes']) || !is_array($imageMetadata['sizes'])) {
            $imageMetadata['sizes'] = [];
        }

        foreach ($imageMetadata['sizes'] as $size => $metadata) {
            $backupSizes = $this->addBackupSize($backupSizes, $metadata, $size, $suffix);
        }

        update_post_meta($attachment->ID, '_wp_attachment_backup_sizes', $backupSizes);
    }

    /**
     * Checks if the given attachment file needs to be suffixed.
     */
    protected function attachmentFileNeedsSuffix(\WP_Post $attachment, string $filePath): bool
    {
        $backupSizes = $this->getBackupImageSizes($attachment);

        if (!$this->isImageCleanupActive()) {
            return true;
        }

        return isset($backupSizes['full-orig']['file']) && $backupSizes['full-orig']['file'] === pathinfo($filePath, PATHINFO_BASENAME);
    }

    /**
     * Generate a unique suffix for the given file.
     */
    protected function generateSuffix(string $file): string
    {
        $suffix = time().rand(100, 999);

        while (file_exists($this->generateSuffixedFilePath($file, (string) $suffix))) {
            ++$suffix;
        }

        return $suffix;
    }

    /**
     * Generate a file path using the given file and suffix.
     */
    protected function generateSuffixedFilePath(string $file, string $suffix = ''): string
    {
        if (empty($suffix)) {
            $suffix = $this->generateSuffix($file);
        }

        $dirname = pathinfo($file, PATHINFO_DIRNAME);
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        $filename = preg_replace('/-e([0-9]+)$/', '', pathinfo($file, PATHINFO_FILENAME));

        return sprintf('%s/%s-e%s.%s', $dirname, $filename, $suffix, $extension);
    }

    /**
     * Get the attachment for the given ID.
     */
    protected function getAttachment($attachmentId): \WP_Post
    {
        if (!is_numeric($attachmentId)) {
            $this->error(sprintf('Attachment ID "%s" isn\'t numeric', $attachmentId));
        }

        $attachment = get_post($attachmentId);

        if (!$attachment instanceof \WP_Post) {
            $this->error(sprintf('No attachment found for ID "%s"', $attachmentId));
        } elseif ('attachment' !== $attachment->post_type) {
            $this->error(sprintf('Post "%s" isn\'t an attachment', $attachment->ID));
        }

        return $attachment;
    }

    /**
     * Get the metadata of the given attachment.
     */
    protected function getAttachmentMetadata(\WP_Post $attachment): array
    {
        $metadata = wp_get_attachment_metadata($attachment->ID);

        if (!is_array($metadata)) {
            $this->error(sprintf('Attachment "%s" has no metadata', $attachment->ID));
        }

        return $metadata;
    }

    /**
     * Get the backup image sizes for the given attachment.
     */
    protected function getBackupImageSizes(\WP_Post $attachment): array
    {
        $backupSizes = [];

        if (!empty($attachment->_wp_attachment_backup_sizes)) {
            $backupSizes = $attachment->_wp_attachment_backup_sizes;
        }

        return is_array($backupSizes) ? $backupSizes : [];
    }

    /**
     * Get the path to the attachment file.
     */
    protected function getFilePath(\WP_Post $attachment): string
    {
        $filePath = get_attached_file($attachment->ID);

        if (false === $filePath) {
            $this->error(sprintf('Attachment "%s" has no attached file', $attachment->ID));
        } elseif (!file_exists($filePath)) {
            $this->error(sprintf('Attachment file "%s" doesn\'t exist', $filePath));
        } elseif (!is_readable($filePath)) {
            $this->error(sprintf('Attachment file "%s" isn\'t readable', $filePath));
        } elseif ($this->fileManager->isInUploadsDirectory($filePath)) {
            $filePath = $this->fileManager->copyToTempDirectory($filePath);
        }

        return $filePath;
    }

    /**
     * Get the image editor to use for the given attachment.
     */
    protected function getImageEditor(string $filePath): \WP_Image_Editor
    {
        $imageEditor = wp_get_image_editor($filePath);

        if (!$imageEditor instanceof \WP_Image_Editor) {
            $this->error(sprintf('Couldn\'t find a compatible image editor for "%s"', $filePath));
        }

        return $imageEditor;
    }

    /**
     * Checks if the "IMAGE_EDIT_OVERWRITE" constant is active. If it is, it means that we
     * want to cleanup image edits and not keep them all.
     *
     * @see https://wordpress.org/support/article/editing-wp-config-php/#cleanup-image-edits
     */
    protected function isImageCleanupActive(): bool
    {
        return defined('IMAGE_EDIT_OVERWRITE') && IMAGE_EDIT_OVERWRITE;
    }

    /**
     * Update the attached file path.
     */
    protected function updateAttachedFilePath(\WP_Post $attachment, string $filePath): string
    {
        $relativePath = $this->fileManager->getRelativePath($filePath);

        update_attached_file($attachment->ID, $relativePath);

        return $relativePath;
    }

    /**
     * Add the given backup image size.
     */
    private function addBackupSize(array $backupSizes, array $imageMetadata, string $size, string $suffix): array
    {
        if (!isset($imageMetadata['file'], $imageMetadata['height'], $imageMetadata['width'])) {
            return $backupSizes;
        }

        $file = pathinfo($imageMetadata['file'], PATHINFO_BASENAME);
        $backupImageName = $this->getBackupImageName($backupSizes, $file, $size, $suffix);

        if (empty($backupImageName)) {
            return $backupSizes;
        }

        $backupSizes[$backupImageName] = [
            'file' => $file,
            'height' => (int) $imageMetadata['height'],
            'width' => (int) $imageMetadata['width'],
        ];

        return $backupSizes;
    }

    /**
     * Get the backup image name.
     */
    private function getBackupImageName(array $backupSizes, string $file, string $size, string $suffix): string
    {
        $backupImageName = '';
        $originalImageFile = $backupSizes["{$size}-orig"]['file'] ?? '';

        if (empty($originalImageFile)) {
            $backupImageName = "{$size}-orig";
        } elseif (!$this->isImageCleanupActive() && !empty($suffix) && $originalImageFile !== $file) {
            $backupImageName = "{$size}-{$suffix}";
        }

        return $backupImageName;
    }
}
