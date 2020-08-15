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

use Ymir\Plugin\Attachment\AttachmentFileManager;
use Ymir\Plugin\DependencyInjection\ServiceLocatorTrait;
use Ymir\Plugin\EventManagement\SubscriberInterface;

/**
 * Subscriber that manages WordPress uploads.
 */
class UploadsSubscriber implements SubscriberInterface
{
    use ServiceLocatorTrait;

    /**
     * The cloud storage directory that we want to change the uploads directory to.
     *
     * @var string
     */
    private $cloudStorageDirectory;

    /**
     * The WordPress content directory.
     *
     * @var string
     */
    private $contentDirectory;

    /**
     * The WordPress content directory.
     *
     * @var string
     */
    private $contentUrl;

    /**
     * The maximum allowed upload size.
     *
     * @var int
     */
    private $uploadSizeLimit;

    /**
     * The url that we want to change the uploads url to.
     *
     * @var string
     */
    private $uploadUrl;

    /**
     * Constructor.
     */
    public function __construct(string $contentDirectory, string $contentUrl, string $cloudStorageDirectory = '', string $uploadUrl = '', $uploadSizeLimit = null)
    {
        $this->cloudStorageDirectory = $cloudStorageDirectory;
        $this->contentDirectory = $contentDirectory;
        $this->contentUrl = $contentUrl;
        $this->uploadUrl = $uploadUrl;

        if (!empty($uploadSizeLimit) && is_string($uploadSizeLimit)) {
            $uploadSizeLimit = wp_convert_hr_to_bytes($uploadSizeLimit);
        } elseif (empty($uploadSizeLimit) || is_numeric($uploadSizeLimit)) {
            $uploadSizeLimit = (int) $uploadSizeLimit;
        }

        if (!is_int($uploadSizeLimit)) {
            throw new \InvalidArgumentException('"uploadSizeLimit" needs to be a numeric value or a string');
        }

        $this->uploadSizeLimit = $uploadSizeLimit;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'pre_wp_unique_filename_file_list' => ['getUniqueFilenameList', 10, 3],
            'upload_dir' => 'replaceUploadDirectories',
            'upload_size_limit' => 'overrideUploadSizeLimit',
            '_wp_relative_upload_path' => ['useFileManagerForRelativePath', 10, 2],
        ];
    }

    /**
     * Get the files used for wp_unique_filename() to prevent performance issues with scandir in large directories.
     */
    public function getUniqueFilenameList(?array $files, string $directory, string $filename): ?array
    {
        $filename = pathinfo($filename, PATHINFO_FILENAME);

        if (0 !== stripos($directory, $this->cloudStorageDirectory) || empty($filename) || !is_string($filename)) {
            return $files;
        }

        return scandir(rtrim($directory, '/').'/'.$filename.'*') ?: null;
    }

    /**
     * Override the upload size limit.
     */
    public function overrideUploadSizeLimit(int $uploadSizeLimit): int
    {
        if (!empty($this->uploadSizeLimit)) {
            $uploadSizeLimit = $this->uploadSizeLimit;
        }

        return $uploadSizeLimit;
    }

    /**
     * Replaces the WordPress upload directories with ones pointing to the cloud storage stream.
     */
    public function replaceUploadDirectories(array $directories): array
    {
        if (empty($this->cloudStorageDirectory) || empty($this->uploadUrl)) {
            return $directories;
        }

        if (isset($directories['basedir'])) {
            $directories['basedir'] = str_replace($this->contentDirectory, $this->cloudStorageDirectory, $directories['basedir']);
        }
        if (isset($directories['baseurl'])) {
            $directories['baseurl'] = str_replace($this->contentUrl, $this->uploadUrl, $directories['baseurl']);
        }
        if (isset($directories['path'])) {
            $directories['path'] = str_replace($this->contentDirectory, $this->cloudStorageDirectory, $directories['path']);
        }
        if (isset($directories['url'])) {
            $directories['url'] = str_replace($this->contentUrl, $this->uploadUrl, $directories['url']);
        }

        return $directories;
    }

    /**
     * Use the file manager to get the relative path since an upload could be in the temp directory.
     */
    public function useFileManagerForRelativePath(string $newPath, string $originalPath): string
    {
        // The AttachmentFileManager can't be injected in the constructor because it needs
        // the uploads directories filter to be registered first which we do in this subscriber.
        $fileManager = self::getService('file_manager');

        if (!$fileManager instanceof AttachmentFileManager) {
            throw new \RuntimeException('"fileManager must be an instance of "AttachmentFileManager"');
        }

        return $fileManager->getRelativePath($originalPath);
    }
}
