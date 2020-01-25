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

/**
 * Manages attachment files.
 */
class AttachmentFileManager
{
    /**
     * The temporary directory used by the file manager.
     *
     * @var string
     */
    private $tempDirectory;

    /**
     * The path to uploads directory.
     *
     * @var string
     */
    private $uploadsDirectory;

    /**
     * Constructor.
     */
    public function __construct(string $uploadsDirectory)
    {
        $this->tempDirectory = $this->createTempDirectory();
        $this->uploadsDirectory = $uploadsDirectory;
    }

    /**
     * Delete everything when we're done the execution.
     */
    public function __destruct()
    {
        if (!is_string($this->tempDirectory) || !is_dir($this->tempDirectory)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->tempDirectory, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($files as $file) {
            $function = $file->isDir() ? 'rmdir' : 'unlink';
            $function($file->getRealPath());
        }
    }

    /**
     * Copy the given file from the uploads directory to the temp directory.
     */
    public function copyToTempDirectory(string $file): string
    {
        return $this->copy($file, $this->uploadsDirectory, $this->tempDirectory);
    }

    /**
     * Copy the given file from the temp directory to the uploads directory.
     */
    public function copyToUploadsDirectory(string $file): string
    {
        return $this->copy($file, $this->tempDirectory, $this->uploadsDirectory);
    }

    /**
     * Get the relative path of the given file.
     */
    public function getRelativePath(string $file): string
    {
        if ($this->isInTempDirectory($file)) {
            $file = ltrim(str_replace($this->tempDirectory, '', $file), '/');
        } elseif ($this->isInUploadsDirectory($file)) {
            $file = ltrim(str_replace($this->uploadsDirectory, '', $file), '/');
        }

        return $file;
    }

    /**
     * Get the temporary directory file path for the given file.
     */
    public function getTempFilePath(string $file): string
    {
        return rtrim($this->tempDirectory, '/').'/'.ltrim($this->getRelativePath($file), '/');
    }

    /**
     * Get the uploads directory file path for the given file.
     */
    public function getUploadsFilePath(string $file): string
    {
        return rtrim($this->uploadsDirectory, '/').'/'.ltrim($this->getRelativePath($file), '/');
    }

    /**
     * Check if the given file is in the temporary directory.
     */
    public function isInTempDirectory(string $file): bool
    {
        return 0 === strpos($file, $this->tempDirectory);
    }

    /**
     * Check if the given file is in the uploads directory.
     */
    public function isInUploadsDirectory(string $file): bool
    {
        return 0 === strpos($file, $this->uploadsDirectory);
    }

    /**
     * Copy the given file from the source directory to the destination directory.
     */
    private function copy(string $file, string $sourceDirectory, string $destinationDirectory): string
    {
        if (0 !== strpos($file, $sourceDirectory)) {
            throw new \InvalidArgumentException(sprintf('The "%s" file isn\'t in the "%s" directory', $file, $sourceDirectory));
        } elseif (!file_exists($file)) {
            throw new \InvalidArgumentException(sprintf('The "%s" file doesn\'t exist', $file));
        } elseif (!is_readable($file)) {
            throw new \InvalidArgumentException(sprintf('The "%s" file isn\'t readable', $file));
        }

        $copy = str_replace($sourceDirectory, $destinationDirectory, $file);

        if (!file_exists(dirname($copy))) {
            mkdir(dirname($copy), 0777, true);
        }

        if (!file_exists($copy) && !copy($file, $copy)) {
            throw new \RuntimeException(sprintf('Unable to copy file from "%s" to "%s"', $file, $copy));
        }

        return $copy;
    }

    /**
     * Create a temporary directory used during this code execution.
     */
    private function createTempDirectory(): string
    {
        $baseDirectory = get_temp_dir();
        $maxAttempts = 100;

        if (!is_dir($baseDirectory)) {
            throw new \RuntimeException(sprintf('"%s" isn\'t a directory', $baseDirectory));
        } elseif (!is_writable($baseDirectory)) {
            throw new \RuntimeException(sprintf('"%s" isn\'t writable', $baseDirectory));
        }

        $attempts = 0;
        do {
            ++$attempts;
            $tmpDirectory = sprintf('%s%s%s', $baseDirectory, 'ymir_', mt_rand(100000, mt_getrandmax()));
        } while (!mkdir($tmpDirectory) && $attempts < $maxAttempts);

        if (!is_dir($tmpDirectory)) {
            throw new \RuntimeException('Failed to create a temp directory');
        }

        return $tmpDirectory;
    }
}
