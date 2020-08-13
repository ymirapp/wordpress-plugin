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
class CloudStorageStreamWrapper
{
    /**
     * Name of the protocol used by the wrapper.
     *
     * @var string
     */
    public const PROTOCOL = 'cloudstorage';

    /**
     * The resource handled by the stream which is set by PHP.
     *
     * @var resource|null
     */
    public $context;

    /**
     * Cache of object and directory lookups.
     *
     * @var array
     */
    private $cache = [];

    /**
     * The key for the cloud storage object being accessed.
     *
     * @var string
     */
    private $key;

    /**
     * Mode used when the stream was opened.
     *
     * @var string
     */
    private $mode;

    /**
     * The resource containing the cloud storage object being accessed.
     *
     * @var resource|null
     */
    private $objectResource;

    /**
     * Register the cloud storage stream wrapper.
     */
    public static function register(CloudStorageClientInterface $client)
    {
        if (in_array(self::PROTOCOL, stream_get_wrappers())) {
            stream_wrapper_unregister(self::PROTOCOL);
        }

        stream_wrapper_register(self::PROTOCOL, self::class, STREAM_IS_URL);

        $defaultOptions = stream_context_get_options(stream_context_get_default());

        $defaultOptions[self::PROTOCOL]['client'] = $client;

        stream_context_set_default($defaultOptions);
    }

    /**
     * Create a directory.
     *
     * @see http://www.php.net/manual/en/streamwrapper.mkdir.php
     */
    public function mkdir($path, $mode): bool
    {
        return $this->call(function () use ($path, $mode) {
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
     * @see http://www.php.net/manual/en/streamwrapper.rename.php
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
     * @see http://www.php.net/manual/en/streamwrapper.rmdir.php
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
     * @see http://www.php.net/manual/en/streamwrapper.stream-cast.php
     */
    public function stream_cast(): bool
    {
        return false;
    }

    /**
     * Close the file.
     *
     * @see http://www.php.net/manual/en/streamwrapper.stream-close.php
     */
    public function stream_close()
    {
        $this->cache = [];
        fclose($this->objectResource);
    }

    /**
     * Checks if we're at the end of a file.
     *
     * @see http://www.php.net/manual/en/streamwrapper.stream-eof.php
     */
    public function stream_eof(): bool
    {
        return feof($this->objectResource);
    }

    /**
     * Flushes the output of the cloud storage object.
     *
     * @see http://www.php.net/manual/en/streamwrapper.stream-flush.php
     *
     * @return bool
     */
    public function stream_flush()
    {
        if ('r' === $this->mode) {
            return false;
        }

        return $this->call(function () {
            rewind($this->objectResource);

            $this->getClient()->putObject($this->key, stream_get_contents($this->objectResource), $this->getMimetype());

            $this->removeCacheValue($this->key);
        });
    }

    /**
     * Change stream metadata.
     *
     * @see http://www.php.net/manual/en/streamwrapper.stream-metadata.php
     */
    public function stream_metadata(): bool
    {
        return false;
    }

    /**
     * Opens the cloud storage object at the given path.
     *
     * @see http://www.php.net/manual/en/streamwrapper.stream-open.php
     */
    public function stream_open(string $path, string $mode): bool
    {
        return $this->call(function () use ($path, $mode) {
            $this->key = $this->parsePath($path);
            $this->mode = $this->parseMode($this->key, $mode);

            $client = $this->getClient();
            $object = '';

            if ('a' === $this->mode) {
                try {
                    $object = $client->getObject($this->key);
                } catch (\Exception $exception) {
                }
            } elseif ('r' === $this->mode) {
                $object = $client->getObject($this->key);
            }

            if ('r' !== $this->mode) {
                // Test that we can save the file that we're opening
                $client->putObject($this->key, $object);
            }

            $this->objectResource = fopen('php://temp', 'r+');
            fwrite($this->objectResource, $object);

            if ('r' === $this->mode) {
                rewind($this->objectResource);
            }
        });
    }

    /**
     * Read from the cloud storage object.
     *
     * @see http://www.php.net/manual/en/streamwrapper.stream-read.php
     */
    public function stream_read(int $count)
    {
        return fread($this->objectResource, $count);
    }

    /**
     * Seeks to specific location in the cloud storage object.
     *
     * @see http://www.php.net/manual/en/streamwrapper.stream-seek.php
     */
    public function stream_seek(int $offset, int $whence = SEEK_SET): bool
    {
        return 0 === fseek($this->objectResource, $offset, $whence);
    }

    /**
     * Get information about the cloud storage object.
     *
     * @see http://www.php.net/manual/en/streamwrapper.stream-stat.php
     */
    public function stream_stat()
    {
        return $this->getStat($this->key);
    }

    /**
     * Retrieve the current position of a stream.
     *
     * @see http://www.php.net/manual/en/streamwrapper.stream-tell.php
     */
    public function stream_tell()
    {
        return ftell($this->objectResource);
    }

    /**
     * Write to cloud storage object.
     *
     * @see http://www.php.net/manual/en/streamwrapper.stream-write.php
     */
    public function stream_write($data)
    {
        return fwrite($this->objectResource, $data);
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
     * @see http://www.php.net/manual/en/streamwrapper.url-stat.php
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
     * Get the cache value for the given key.
     */
    private function getCacheValue(string $key)
    {
        return $this->cache[$key] ?? null;
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

        $extension = strtolower(pathinfo($this->key, PATHINFO_EXTENSION));

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

        $context = $context[self::PROTOCOL] ?? [];
        $default = $default[self::PROTOCOL] ?? [];

        return $context + $default;
    }

    /**
     * Get the stat function return value with the given stat values merged in.
     */
    private function getStat(string $key)
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

        if (empty($key)) {
            return $stat;
        }

        return $this->call(function () use ($key, $stat) {
            $client = $this->getClient();

            if (!$client->objectExists($key)) {
                return false;
            }

            $details = $client->getObjectDetails($key);

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

        if (!in_array($mode, ['r', 'w', 'a', 'x'])) {
            throw new \InvalidArgumentException(sprintf('"%s" mode isn\'t supported. Must be "r", "w", "a", "x"', $mode));
        } elseif ('x' === $mode && $client->objectExists($key)) {
            throw new \InvalidArgumentException('Cannot have an existing object when opening with mode "x"');
        } elseif ('r' === $mode && !$client->objectExists($key)) {
            throw new \InvalidArgumentException('Must have an existing object when opening with mode "r"');
        }

        return $mode;
    }

    /**
     * Parse and validate the given path into the key used to get the cloud storage object.
     */
    private function parsePath(string $path): string
    {
        $protocol = self::PROTOCOL.'://';

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
        clearstatcache(true, $key);
        unset($this->cache[$key]);
    }

    /**
     * Set the given cache value for the given key.
     */
    private function setCacheValue(string $key, $value)
    {
        $this->cache[$key] = $value;
    }
}
