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
 * A cloud storage API client.
 */
interface CloudStorageClientInterface
{
    /**
     * Copy the object with the given source key to the given target key.
     */
    public function copyObject(string $sourceKey, string $targetKey);

    /**
     * Creates a presigned "putObject" request.
     */
    public function createPutObjectRequest(string $key): string;

    /**
     * Delete an object from cloud storage.
     */
    public function deleteObject(string $key);

    /**
     * Get an object from cloud storage.
     */
    public function getObject(string $key): string;

    /**
     * Get the details about an object from cloud storage.
     */
    public function getObjectDetails(string $key): array;

    /**
     * Get all the objects starting with the given prefix.
     */
    public function getObjects(string $prefix, int $limit = 0): array;

    /**
     * Check if the given object exists in the cloud storage.
     */
    public function objectExists(string $key): bool;

    /**
     * Put an object into cloud storage.
     */
    public function putObject(string $key, string $object, string $content_type = '');
}
