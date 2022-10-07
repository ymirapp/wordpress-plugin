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

namespace Ymir\Plugin\CloudStorage;

/**
 * A stream wrapper used to interact with cloud storage using a cloud storage API client.
 *
 * @see https://www.php.net/manual/en/class.streamwrapper.php
 */
abstract class AbstractCloudStorageStreamWrapper
{
    /**
     * The resource handled by the stream which is set by PHP.
     *
     * @var resource|null
     */
    public $context;

    /**
     * Cache of object and directory lookups.
     *
     * @var \ArrayObject|null
     */
    protected $cache;

    /**
     * The cloud storage objects retrieved with "dir_opendir".
     *
     * @var \ArrayIterator|null
     */
    protected $openedDirectoryObjects;

    /**
     * The path when "dir_opendir" was called.
     *
     * @var string|null
     */
    protected $openedDirectoryPath;

    /**
     * The prefix used to get the cloud storage objects with "dir_opendir".
     *
     * @var string|null
     */
    protected $openedDirectoryPrefix;

    /**
     * Mode used when the stream was opened.
     *
     * @var string
     */
    protected $openedStreamMode;

    /**
     * The key for the cloud storage object opened by "stream_open".
     *
     * @var string
     */
    protected $openedStreamObjectKey;

    /**
     * The resource containing the cloud storage object opened by "stream_open".
     *
     * @var resource|null
     */
    protected $openedStreamObjectResource;

    /**
     * Get the protocol used by the stream wrapper.
     */
    public static function getProtocol(): string
    {
        throw new \RuntimeException('Must overrider "getProtocol" method');
    }

    /**
     * Register the cloud storage stream wrapper.
     */
    public static function register(CloudStorageClientInterface $client, \ArrayObject $cache = null)
    {
        if (in_array(static::getProtocol(), stream_get_wrappers())) {
            stream_wrapper_unregister(static::getProtocol());
        }

        stream_wrapper_register(static::getProtocol(), static::class, STREAM_IS_URL);

        $defaultOptions = stream_context_get_options(stream_context_get_default());

        $defaultOptions[static::getProtocol()]['client'] = $client;

        if ($cache instanceof \ArrayObject) {
            $defaultOptions[static::getProtocol()]['cache'] = $cache;
        } elseif (!isset($defaultOptions[static::getProtocol()]['cache'])) {
            $defaultOptions[static::getProtocol()]['cache'] = new \ArrayObject();
        }

        stream_context_set_default($defaultOptions);
    }

    /**
     * Close directory handle.
     *
     * @see https://www.php.net/manual/en/streamwrapper.dir-closedir.php
     */
    public function dir_closedir(): bool
    {
        $this->openedDirectoryObjects = null;
        $this->openedDirectoryPath = null;
        $this->openedDirectoryPrefix = null;
        gc_collect_cycles();

        return true;
    }

    /**
     * Open directory handle.
     *
     * @see https://www.php.net/manual/en/streamwrapper.dir-opendir.php
     */
    public function dir_opendir(string $path, int $options): bool
    {
        return $this->call(function () use ($path) {
            $this->openedDirectoryPath = $path;

            if ('*' === substr($path, -1)) {
                $path = ltrim($this->parsePath($path), '/');
                $this->openedDirectoryPrefix = substr($path, 0, (strrpos($path, '/') ?: 0) + 1);
                $this->openedDirectoryObjects = new \ArrayIterator($this->getClient()->getObjects(rtrim($path, '*')));
            } else {
                $this->openedDirectoryPrefix = trim($this->parsePath($path), '/').'/';
                $this->openedDirectoryObjects = new \ArrayIterator($this->getClient()->getObjects($this->openedDirectoryPrefix));
            }
        });
    }

    /**
     * Read entry from directory handle.
     *
     * @see https://www.php.net/manual/en/streamwrapper.dir-readdir.php
     */
    public function dir_readdir()
    {
        if (!$this->openedDirectoryObjects instanceof \ArrayIterator || !$this->openedDirectoryObjects->valid()) {
            return false;
        }

        $current = $this->openedDirectoryObjects->current();

        if (empty($current['Key'])) {
            return false;
        }

        $details = [];

        if (isset($current['Size'])) {
            $details['size'] = $current['Size'];
        }
        if (isset($current['LastModified'])) {
            $details['last-modified'] = $current['LastModified'];
        }

        $filename = substr($current['Key'], strlen($this->openedDirectoryPrefix));

        $this->setCacheValue($this->openedDirectoryPath.$filename, $this->getStat($current['Key'], $details));

        $this->openedDirectoryObjects->next();

        return $filename;
    }

    /**
     * Rewind directory handle.
     *
     * @see https://www.php.net/manual/en/streamwrapper.dir-rewinddir.php
     */
    public function dir_rewinddir(): bool
    {
        return $this->call(function () {
            if (!is_string($this->openedDirectoryPath)) {
                return false;
            }

            $this->dir_opendir($this->openedDirectoryPath, 0);

            return true;
        });
    }

    /**
     * Create a directory.
     *
     * @see https://www.php.net/manual/en/streamwrapper.mkdir.php
     */
    public function mkdir($path, $mode): bool
    {
        return $this->call(function () use ($path) {
            $client = $this->getClient();
            $key = rtrim($this->parsePath($path), '/').'/';

            $this->removeCacheValue($path);

            if ($client->objectExists($key)) {
                throw new \RuntimeException(sprintf('Directory "%s" already exists', $path));
            }

            $client->putObject($key, '');
        });
    }

    /**
     * Rename a file.
     *
     * @see https://www.php.net/manual/en/streamwrapper.rename.php
     */
    public function rename(string $pathFrom, string $pathTo): bool
    {
        return $this->call(function () use ($pathFrom, $pathTo) {
            $client = $this->getClient();
            $sourceKey = $this->parsePath($pathFrom);
            $targetKey = $this->parsePath($pathTo);

            $this->removeCacheValue($pathFrom);
            $this->removeCacheValue($pathTo);

            $client->copyObject($sourceKey, $targetKey);
            $client->deleteObject($sourceKey);
        });
    }

    /**
     * Removes a directory.
     *
     * @see https://www.php.net/manual/en/streamwrapper.rmdir.php
     */
    public function rmdir(string $path): bool
    {
        return $this->call(function () use ($path) {
            $client = $this->getClient();
            $key = rtrim($this->parsePath($path), '/').'/';

            if ('/' === $key) {
                throw new \RuntimeException('Cannot delete root directory');
            }

            $this->removeCacheValue($path);

            // The directory itself counts as an object.
            if (1 < count($client->getObjects($key, 2))) {
                throw new \RuntimeException(sprintf('Directory "%s" isn\'t empty', $path));
            }

            $client->deleteObject($key);
        });
    }

    /**
     * Retrieve the underlaying resource.
     *
     * @see https://www.php.net/manual/en/streamwrapper.stream-cast.php
     */
    public function stream_cast(): bool
    {
        return false;
    }

    /**
     * Close the file.
     *
     * @see https://www.php.net/manual/en/streamwrapper.stream-close.php
     */
    public function stream_close()
    {
        $this->cache = null;
        fclose($this->openedStreamObjectResource);
    }

    /**
     * Checks if we're at the end of a file.
     *
     * @see https://www.php.net/manual/en/streamwrapper.stream-eof.php
     */
    public function stream_eof(): bool
    {
        return feof($this->openedStreamObjectResource);
    }

    /**
     * Flushes the output of the cloud storage object.
     *
     * @see https://www.php.net/manual/en/streamwrapper.stream-flush.php
     *
     * @return bool
     */
    public function stream_flush()
    {
        if ('r' === $this->openedStreamMode) {
            return false;
        }

        return $this->call(function () {
            rewind($this->openedStreamObjectResource);

            $this->getClient()->putObject($this->openedStreamObjectKey, stream_get_contents($this->openedStreamObjectResource), $this->getMimetype());

            $this->removeCacheValue(static::getProtocol().'://'.$this->openedStreamObjectKey);
        });
    }

    /**
     * Advisory file locking.
     *
     * @see https://www.php.net/manual/en/streamwrapper.stream-lock.php
     */
    public function stream_lock(): bool
    {
        return false;
    }

    /**
     * Change stream metadata.
     *
     * @see https://www.php.net/manual/en/streamwrapper.stream-metadata.php
     */
    public function stream_metadata(): bool
    {
        return false;
    }

    /**
     * Opens the cloud storage object at the given path.
     *
     * @see https://www.php.net/manual/en/streamwrapper.stream-open.php
     */
    public function stream_open(string $path, string $mode): bool
    {
        return $this->call(function () use ($path, $mode) {
            $this->openedStreamObjectKey = $this->parsePath($path);
            $this->openedStreamMode = $this->parseMode($this->openedStreamObjectKey, $mode);

            $client = $this->getClient();
            $object = '';

            if (in_array($this->openedStreamMode, ['a', 'a+'])) {
                try {
                    $object = $client->getObject($this->openedStreamObjectKey);
                } catch (\Exception $exception) {
                }
            } elseif (in_array($this->openedStreamMode, ['r', 'r+'])) {
                $object = $client->getObject($this->openedStreamObjectKey);
            }

            if ('r' !== $this->openedStreamMode) {
                // Test that we can save the file that we're opening
                $client->putObject($this->openedStreamObjectKey, $object);

                // Remove the cache value in case we interacted with the file before using something
                // like "file_exists". If we don't write to the file, there won't be any cache busting
                // so this is the only opportunity to do so.
                $this->removeCacheValue(static::getProtocol().'://'.$this->openedStreamObjectKey);
            }

            $this->openedStreamObjectResource = fopen('php://temp', 'r+');
            fwrite($this->openedStreamObjectResource, $object);

            if (in_array($this->openedStreamMode, ['r', 'r+'])) {
                rewind($this->openedStreamObjectResource);
            }
        });
    }

    /**
     * Read from the cloud storage object.
     *
     * @see https://www.php.net/manual/en/streamwrapper.stream-read.php
     */
    public function stream_read(int $count)
    {
        return fread($this->openedStreamObjectResource, $count);
    }

    /**
     * Seeks to specific location in the cloud storage object.
     *
     * @see https://www.php.net/manual/en/streamwrapper.stream-seek.php
     */
    public function stream_seek(int $offset, int $whence = SEEK_SET): bool
    {
        return 0 === fseek($this->openedStreamObjectResource, $offset, $whence);
    }

    /**
     * Get information about the cloud storage object.
     *
     * @see https://www.php.net/manual/en/streamwrapper.stream-stat.php
     */
    public function stream_stat()
    {
        $stat = $this->getStat($this->openedStreamObjectKey);

        if (!is_array($stat) || !is_resource($this->openedStreamObjectResource)) {
            return $stat;
        }

        $fstat = fstat($this->openedStreamObjectResource);

        if (isset($fstat['size'])) {
            $stat[7] = $stat['size'] = $fstat['size'];
        }

        return $stat;
    }

    /**
     * Retrieve the current position of a stream.
     *
     * @see https://www.php.net/manual/en/streamwrapper.stream-tell.php
     */
    public function stream_tell()
    {
        return ftell($this->openedStreamObjectResource);
    }

    /**
     * Truncate stream.
     *
     * @see https://www.php.net/manual/en/streamwrapper.stream-truncate.php
     */
    public function stream_truncate(int $newSize): bool
    {
        return $this->call(function () use ($newSize) {
            rewind($this->openedStreamObjectResource);

            ftruncate($this->openedStreamObjectResource, $newSize);

            $this->getClient()->putObject($this->openedStreamObjectKey, stream_get_contents($this->openedStreamObjectResource), $this->getMimetype());

            $this->removeCacheValue(static::getProtocol().'://'.$this->openedStreamObjectKey);
        });
    }

    /**
     * Write to cloud storage object.
     *
     * @see https://www.php.net/manual/en/streamwrapper.stream-write.php
     */
    public function stream_write($data)
    {
        return fwrite($this->openedStreamObjectResource, $data);
    }

    /**
     * Delete a file.
     *
     * @see https://www.php.net/manual/en/streamwrapper.unlink.php
     */
    public function unlink(string $path): bool
    {
        return $this->call(function () use ($path) {
            $key = $this->parsePath($path);

            $this->removeCacheValue($path);

            $this->getClient()->deleteObject($key);
        });
    }

    /**
     * Retrieve information about the file at the given path.
     *
     * @see https://www.php.net/manual/en/streamwrapper.url-stat.php
     */
    public function url_stat(string $path, int $flags)
    {
        $stat = $this->getCacheValue($path);

        if (null !== $stat) {
            return $stat;
        }

        $stat = $this->getStat($this->parsePath($path));

        $this->setCacheValue($path, $stat);

        return $stat;
    }

    /**
     * Call the given callback and catch any exception thrown and convert them as errors.
     */
    private function call(callable $callback)
    {
        try {
            $return = $callback();

            if (null === $return) {
                $return = true;
            }

            return $return;
        } catch (\Exception $exception) {
            trigger_error($exception->getMessage(), E_USER_WARNING);

            return false;
        }
    }

    /**
     * Get the cache used for storing stat values.
     */
    private function getCache(): \ArrayObject
    {
        if (!$this->cache instanceof \ArrayObject) {
            $this->cache = $this->getOption('cache') ?: new \ArrayObject();
        }

        return $this->cache;
    }

    /**
     * Get the cache value for the given key.
     */
    private function getCacheValue(string $key)
    {
        $cache = $this->getCache();
        $value = null;

        if ($cache->offsetExists($key)) {
            $value = $cache->offsetGet($key);
        }

        return $value;
    }

    /**
     * Get the cloud storage API client.
     */
    private function getClient(): CloudStorageClientInterface
    {
        $client = $this->getOption('client');

        if (!$client instanceof CloudStorageClientInterface) {
            throw new \RuntimeException('No cloud storage client found in the stream context');
        }

        return $client;
    }

    /**
     * Get the mimetype of the cloud storage object.
     */
    private function getMimetype(): string
    {
        $mimetypes = [
            '3gp' => 'video/3gpp',
            '7z' => 'application/x-7z-compressed',
            'aac' => 'audio/x-aac',
            'ai' => 'application/postscript',
            'aif' => 'audio/x-aiff',
            'asc' => 'text/plain',
            'asf' => 'video/x-ms-asf',
            'atom' => 'application/atom+xml',
            'avi' => 'video/x-msvideo',
            'bmp' => 'image/bmp',
            'bz2' => 'application/x-bzip2',
            'cer' => 'application/pkix-cert',
            'crl' => 'application/pkix-crl',
            'crt' => 'application/x-x509-ca-cert',
            'css' => 'text/css',
            'csv' => 'text/csv',
            'cu' => 'application/cu-seeme',
            'deb' => 'application/x-debian-package',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'dvi' => 'application/x-dvi',
            'eot' => 'application/vnd.ms-fontobject',
            'eps' => 'application/postscript',
            'epub' => 'application/epub+zip',
            'etx' => 'text/x-setext',
            'flac' => 'audio/flac',
            'flv' => 'video/x-flv',
            'gif' => 'image/gif',
            'gz' => 'application/gzip',
            'htm' => 'text/html',
            'html' => 'text/html',
            'ico' => 'image/x-icon',
            'ics' => 'text/calendar',
            'ini' => 'text/plain',
            'iso' => 'application/x-iso9660-image',
            'jar' => 'application/java-archive',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'js' => 'text/javascript',
            'json' => 'application/json',
            'latex' => 'application/x-latex',
            'log' => 'text/plain',
            'm4a' => 'audio/mp4',
            'm4v' => 'video/mp4',
            'mid' => 'audio/midi',
            'midi' => 'audio/midi',
            'mov' => 'video/quicktime',
            'mkv' => 'video/x-matroska',
            'mp3' => 'audio/mpeg',
            'mp4' => 'video/mp4',
            'mp4a' => 'audio/mp4',
            'mp4v' => 'video/mp4',
            'mpe' => 'video/mpeg',
            'mpeg' => 'video/mpeg',
            'mpg' => 'video/mpeg',
            'mpg4' => 'video/mp4',
            'oga' => 'audio/ogg',
            'ogg' => 'audio/ogg',
            'ogv' => 'video/ogg',
            'ogx' => 'application/ogg',
            'pbm' => 'image/x-portable-bitmap',
            'pdf' => 'application/pdf',
            'pgm' => 'image/x-portable-graymap',
            'png' => 'image/png',
            'pnm' => 'image/x-portable-anymap',
            'ppm' => 'image/x-portable-pixmap',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'ps' => 'application/postscript',
            'qt' => 'video/quicktime',
            'rar' => 'application/x-rar-compressed',
            'ras' => 'image/x-cmu-raster',
            'rss' => 'application/rss+xml',
            'rtf' => 'application/rtf',
            'sgm' => 'text/sgml',
            'sgml' => 'text/sgml',
            'svg' => 'image/svg+xml',
            'swf' => 'application/x-shockwave-flash',
            'tar' => 'application/x-tar',
            'tif' => 'image/tiff',
            'tiff' => 'image/tiff',
            'torrent' => 'application/x-bittorrent',
            'ttf' => 'application/x-font-ttf',
            'txt' => 'text/plain',
            'wav' => 'audio/x-wav',
            'webm' => 'video/webm',
            'webp' => 'image/webp',
            'wma' => 'audio/x-ms-wma',
            'wmv' => 'video/x-ms-wmv',
            'woff' => 'application/x-font-woff',
            'wsdl' => 'application/wsdl+xml',
            'xbm' => 'image/x-xbitmap',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xml' => 'application/xml',
            'xpm' => 'image/x-xpixmap',
            'xwd' => 'image/x-xwindowdump',
            'yaml' => 'text/yaml',
            'yml' => 'text/yaml',
            'zip' => 'application/zip',
        ];

        $extension = strtolower(pathinfo($this->openedStreamObjectKey, PATHINFO_EXTENSION));

        return $mimetypes[$extension] ?? '';
    }

    /**
     * Get the given stream option or return default.
     */
    private function getOption(string $name, $default = null)
    {
        return $this->getOptions()[$name] ?? $default;
    }

    /**
     * Get the stream options.
     */
    private function getOptions(): array
    {
        $context = [];
        $default = stream_context_get_options(stream_context_get_default());

        if (is_resource($this->context)) {
            $context = stream_context_get_options($this->context);
        }

        $context = $context[static::getProtocol()] ?? [];
        $default = $default[static::getProtocol()] ?? [];

        return $context + $default;
    }

    /**
     * Get the stat function return value with the given stat values merged in for the given object key.
     */
    private function getStat(string $key, array $details = [])
    {
        // Default stat is directory with 0777 access
        $stat = [
            0 => 0,       'dev' => 0,
            1 => 0,       'ino' => 0,
            2 => 0040777, 'mode' => 0040777,
            3 => 0,       'nlink' => 0,
            4 => 0,       'uid' => 0,
            5 => 0,       'gid' => 0,
            6 => -1,      'rdev' => -1,
            7 => 0,       'size' => 0,
            8 => 0,       'atime' => 0,
            9 => 0,       'mtime' => 0,
            10 => 0,      'ctime' => 0,
            11 => -1,     'blksize' => -1,
            12 => -1,     'blocks' => -1,
        ];

        // Avoid doing API calls if the given object key has no extension. "wp_upload_dir" does a lot
        // of file checks to create directories and that impacts performance significantly.
        if (empty($key) || empty(pathinfo($key, PATHINFO_EXTENSION))) {
            return $stat;
        }

        return $this->call(function () use ($details, $key, $stat) {
            $client = $this->getClient();

            if (empty($details)) {
                try {
                    $details = $client->getObjectDetails($key);
                } catch (\Exception $exception) {
                    return false;
                }
            }

            if ('/' === substr($key, -1) && isset($details['size']) && 0 === $details['size']) {
                return $stat;
            }

            // If we get, we're dealing with a file so switch mode to a regular file with 0777 access
            $stat[2] = $stat['mode'] = 0100777;

            if (isset($details['size'])) {
                $stat[7] = $stat['size'] = $details['size'];
            }

            if (isset($details['last-modified'])) {
                $stat[9] = $stat['mtime'] = $stat[10] = $stat['ctime'] = strtotime($details['last-modified']);
            }

            return $stat;
        });
    }

    /**
     * Parse and validate the given mode to see for the given cloud storage object key.
     */
    private function parseMode(string $key, string $mode): string
    {
        $client = $this->getClient();
        $mode = rtrim($mode, 'bt');

        if (!in_array($mode, ['r', 'r+', 'w', 'a', 'a+', 'x'])) {
            throw new \InvalidArgumentException(sprintf('"%s" mode isn\'t supported. Must be "r", "r+", "w", "a", "a+", "x"', $mode));
        } elseif ('x' === $mode && $client->objectExists($key)) {
            throw new \InvalidArgumentException('Cannot have an existing object when opening with mode "x"');
        } elseif (in_array($mode, ['r', 'r+']) && !$client->objectExists($key)) {
            throw new \InvalidArgumentException(sprintf('Must have an existing object when opening with mode "%s"', $mode));
        }

        return $mode;
    }

    /**
     * Parse and validate the given path into the key used to get the cloud storage object.
     */
    private function parsePath(string $path): string
    {
        $protocol = static::getProtocol().'://';

        if (0 !== strpos($path, $protocol)) {
            throw new \InvalidArgumentException(sprintf('Invalid protocol for "%s"', $path));
        }

        return str_replace($protocol, '', $path);
    }

    /**
     * Remove the cache value for the given key.
     */
    private function removeCacheValue(string $key)
    {
        $cache = $this->getCache();

        clearstatcache(true, $key);

        if ($cache->offsetExists($key)) {
            $cache->offsetUnset($key);
        }
    }

    /**
     * Set the given cache value for the given key.
     */
    private function setCacheValue(string $key, $value)
    {
        $this->getCache()->offsetSet($key, $value);
    }
}
