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

namespace Ymir\Plugin\CloudProvider\Aws;

use Ymir\Plugin\CloudStorage\CloudStorageClientInterface;
use Ymir\Plugin\Http\Client;

/**
 * The client for AWS S3 API.
 */
class S3Client extends AbstractClient implements CloudStorageClientInterface
{
    /**
     * The S3 bucket.
     *
     * @var string
     */
    private $bucket;

    /**
     * Constructor.
     */
    public function __construct(Client $client, string $bucket, string $key, string $region, string $secret)
    {
        parent::__construct($client, $key, $region, $secret);

        $this->bucket = $bucket;
    }

    /**
     * {@inheritdoc}
     */
    public function copyObject(string $sourceKey, string $targetKey)
    {
        $response = $this->request('put', $targetKey, null, [
            'x-amz-copy-source' => '/'.$this->bucket.$this->createRequestUri($sourceKey),
        ]);

        if (200 !== $this->parseResponseStatusCode($response)) {
            throw new \RuntimeException(sprintf('Could not copy object "%s"', $sourceKey));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createPutObjectRequest(string $key): string
    {
        return $this->createPresignedRequest($this->createRequestUri($key), 'put', [
            'x-amz-acl' => 'public-read',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteObject(string $key)
    {
        $response = $this->request('delete', $key);

        if (204 !== $this->parseResponseStatusCode($response)) {
            throw new \RuntimeException(sprintf('Unable to delete object "%s"', $key));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getObject(string $key): string
    {
        $response = $this->request('get', $key);

        if (200 !== $this->parseResponseStatusCode($response)) {
            throw new \RuntimeException(sprintf('Object "%s" not found', $key));
        }

        return $response['body'] ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectDetails(string $key): array
    {
        $response = $this->request('head', $key);

        if (200 !== $this->parseResponseStatusCode($response)) {
            throw new \RuntimeException(sprintf('Object "%s" not found', $key));
        }

        $details = [];

        if (isset($response['headers']['content-type'])) {
            $details['type'] = $response['headers']['content-type'];
        }
        if (isset($response['headers']['content-length'])) {
            $details['size'] = $response['headers']['content-length'];
        }
        if (isset($response['headers']['last-modified'])) {
            $details['last-modified'] = $response['headers']['last-modified'];
        }

        return $details;
    }

    /**
     * {@inheritdoc}
     */
    public function getObjects(string $prefix, int $limit = 0): array
    {
        if ($limit < 0) {
            throw new \InvalidArgumentException('Given limit must be greater than 0');
        } elseif ($limit > 1000) {
            throw new \InvalidArgumentException('Given limit cannot be greater than 1000');
        }

        $parameters = [
            'list-type' => 2,
        ];
        $prefix = ltrim($this->createRequestUri($prefix), '/');

        if (!empty($prefix)) {
            $parameters['prefix'] = $prefix;
        }

        if (!empty($limit)) {
            $parameters['max-keys'] = $limit;
        }

        ksort($parameters);

        $response = $this->request('get', '/?'.http_build_query($parameters));

        if (200 !== $this->parseResponseStatusCode($response)) {
            throw new \RuntimeException(sprintf('Unable to list objects with prefix "%s"', $prefix));
        } elseif (empty($response['body'])) {
            throw new \RuntimeException('No content returned from S3 API');
        }

        $objects = [];
        $xml = simplexml_load_string($response['body']);

        foreach ($xml->Contents as $object) {
            $objects[] = (array) $object;
        }

        return $objects;
    }

    /**
     * {@inheritdoc}
     */
    public function objectExists(string $key): bool
    {
        try {
            $this->getObjectDetails($key);
        } catch (\Exception $exception) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function putObject(string $key, string $object, string $contentType = '')
    {
        $headers = [];

        if (!empty($contentType)) {
            $headers['content-type'] = $contentType;
        }

        $response = $this->request('put', $key, $object, $headers);

        if (200 !== $this->parseResponseStatusCode($response)) {
            throw new \RuntimeException(sprintf('Unable to save object "%s"', $key));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createBaseHeaders(?string $body, string $acl = 'public-read'): array
    {
        return array_merge(parent::createBaseHeaders($body), [
            'x-amz-acl' => 'public-read',
        ]);
    }

    /**
     * Create the request URI using the object key.
     */
    protected function createRequestUri(string $key): string
    {
        return '/'.ltrim($key, '/');
    }

    /**
     * {@inheritdoc}
     */
    protected function getEndpointName(): string
    {
        return $this->bucket.'.'.$this->getService();
    }

    /**
     * {@inheritdoc}
     */
    protected function getService(): string
    {
        return 's3';
    }

    /**
     * Makes a request to the AWS S3 API for the given object key.
     */
    protected function request(string $method, string $key, ?string $body = null, array $headers = []): array
    {
        return parent::request($method, $this->createRequestUri($key), $body, $headers);
    }
}
