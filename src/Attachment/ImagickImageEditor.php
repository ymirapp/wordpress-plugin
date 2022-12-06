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

namespace Ymir\Plugin\Attachment;

use Ymir\Plugin\DependencyInjection\ServiceLocatorTrait;

/**
 * Imagick image editor that copies files to a temporary directory for faster processing.
 */
class ImagickImageEditor extends \WP_Image_Editor_Imagick
{
    use ServiceLocatorTrait;

    /**
     * Flag whether to disable creating image subsizes or not.
     *
     * @var bool
     */
    private $disableImageSubsizes;

    /**
     * The attachment file manager.
     *
     * @var AttachmentFileManager
     */
    private $fileManager;

    /**
     * Constructor.
     */
    public function __construct($file)
    {
        $this->disableImageSubsizes = (bool) self::getService('ymir_cdn_image_processing_enabled');
        $this->fileManager = self::getService('file_manager');

        if (!$this->fileManager instanceof AttachmentFileManager) {
            throw new \RuntimeException('"fileManager must be an instance of "AttachmentFileManager"');
        }

        if ($this->fileManager->isInUploadsDirectory($file)) {
            $file = $this->fileManager->copyToTempDirectory($file);
        }

        parent::__construct($file);
    }

    /**
     * {@inheritdoc}
     */
    public function make_subsize($size)
    {
        return !$this->disableImageSubsizes || !is_array($size) || (!isset($size['height']) && !isset($size['width'])) ? parent::make_subsize($size) : [
            'file' => wp_basename($this->file),
            'width' => $size['width'],
            'height' => $size['height'],
            'mime-type' => $this->mime_type,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function save($destFilename = null, $mimeType = null)
    {
        $savedImage = parent::save($destFilename, $mimeType);

        // The "save" method changes the "file" property which is an issue with "multi_resize"
        // since we call the "save" method multiple times. So after the first save, the "file"
        // property won't point to the temporary directory anymore so revert it back.
        if ($this->fileManager->isInUploadsDirectory($this->file)) {
            $this->file = $this->fileManager->copyToTempDirectory($this->file);
        }

        return $savedImage;
    }

    /**
     * {@inheritdoc}
     */
    protected function _save($image, $filename = null, $mimeType = null)
    {
        // Imagick by default can't handle ymir-public:// paths so have it save the file locally.
        if (is_string($filename) && $this->fileManager->isInUploadsDirectory($filename)) {
            $filename = $this->fileManager->getTempFilePath($filename);
        }

        $savedImage = parent::_save($image, $filename, $mimeType);

        if ($savedImage instanceof \WP_Error || empty($savedImage['path']) || !$this->fileManager->isInTempDirectory($savedImage['path'])) {
            return $savedImage;
        }

        $savedImage['path'] = $this->fileManager->copyToUploadsDirectory($savedImage['path']);

        return $savedImage;
    }
}
