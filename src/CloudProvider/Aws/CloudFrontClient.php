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

use Ymir\Plugin\Http\Client;
use Ymir\Plugin\PageCache\ContentDeliveryNetworkPageCacheClientInterface;
use Ymir\Plugin\Support\Collection;

/**
 * The client for AWS CloudFront API.
 */
class CloudFrontClient extends AbstractClient implements ContentDeliveryNetworkPageCacheClientInterface
{
    /**
     * The ID of the CloudFront distribution.
     *
     * @var string
     */
    private $distributionId;

    /**
     * All the paths that we want to invalidate.
     *
     * @var array
     */
    private $invalidationPaths;

    /**
     * {@inheritdoc}
     */
    public function __construct(Client $client, string $distributionId, string $key, string $secret)
    {
        parent::__construct($client, $key, 'us-east-1', $secret);

        $this->distributionId = $distributionId;
        $this->invalidationPaths = [];
    }

    /**
     * {@inheritdoc}
     */
    public function clearAll()
    {
        $this->addPath('/*');
    }

    /**
     * {@inheritdoc}
     */
    public function clearUrl(string $url)
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (false === $path) {
            throw new \RuntimeException(sprintf('Unable to parse URL: %s', $url));
        }

        $this->addPath('/'.ltrim((string) $path, '/'));
    }

    /**
     * {@inheritdoc}
     */
    public function sendClearRequest()
    {
        if (empty($this->invalidationPaths)) {
            return;
        }

        $this->createInvalidation($this->invalidationPaths);

        $this->invalidationPaths = [];
    }

    /**
     * {@inheritdoc}
     */
    protected function getHostname(): string
    {
        return 'cloudfront.amazonaws.com';
    }

    /**
     * {@inheritdoc}
     */
    protected function getService(): string
    {
        return 'cloudfront';
    }

    /**
     * Add the given path to the list.
     */
    private function addPath(string $path)
    {
        if (in_array($path, ['*', '/*'])) {
            $this->invalidationPaths = ['/*'];
        }

        if (['/*'] === $this->invalidationPaths) {
            return;
        }

        $this->invalidationPaths[] = $path;
    }

    /**
     * Create an invalidation request.
     */
    private function createInvalidation($paths)
    {
        if (is_string($paths)) {
            $paths = [$paths];
        } elseif (!is_array($paths)) {
            throw new \InvalidArgumentException('"paths" argument must be an array or a string');
        }

        if (count($paths) > 1) {
            $paths = $this->filterUniquePaths($paths);
        }

        $response = $this->request('post', "/2020-05-31/distribution/{$this->distributionId}/invalidation", $this->generateInvalidationPayload($paths));

        if (201 !== $this->parseResponseStatusCode($response)) {
            throw new \RuntimeException('Invalidation request failed');
        }
    }

    /**
     * Filter all paths and only keep unique ones.
     */
    private function filterUniquePaths(array $paths): array
    {
        $paths = (new Collection($paths))->unique();

        $filteredPaths = $paths->filter(function (string $path) {
            return '*' !== substr($path, -1);
        })->all();
        $wildcardPaths = $paths->filter(function (string $path) {
            return '*' === substr($path, -1);
        });

        $wildcardPaths = $wildcardPaths->map(function (string $path) use ($wildcardPaths) {
            $filteredWildcardPaths = preg_grep(sprintf('/%s/', str_replace('\*', '.*', preg_quote($path, '/'))), $wildcardPaths->all(), PREG_GREP_INVERT);
            $filteredWildcardPaths[] = $path;

            return $filteredWildcardPaths;
        });

        $wildcardPaths = new Collection(!$wildcardPaths->isEmpty() ? array_intersect(...$wildcardPaths->all()) : []);

        if ($wildcardPaths->count() > 15) {
            throw new \RuntimeException('CloudFront only allows for a maximum of 15 wildcard invalidations');
        }

        $wildcardPaths->each(function (string $path) use (&$filteredPaths) {
            $filteredPaths = preg_grep(sprintf('/%s/', str_replace('\*', '.*', preg_quote($path, '/'))), $filteredPaths, PREG_GREP_INVERT);
        });

        return array_merge($wildcardPaths->all(), $filteredPaths);
    }

    /**
     * Generate a unique caller reference.
     */
    private function generateCallerReference(): string
    {
        $length = 16;
        $reference = '';

        while (strlen($reference) < $length) {
            $size = $length - strlen($reference);

            $bytes = random_bytes($size);

            $reference .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }

        return $reference.'-'.time();
    }

    /**
     * Generate the XML payload for an invalidation request.
     */
    private function generateInvalidationPayload(array $paths): string
    {
        $xmlDocument = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><InvalidationBatch xmlns="http://cloudfront.amazonaws.com/doc/2020-05-31/"></InvalidationBatch>');

        $xmlDocument->addChild('CallerReference', $this->generateCallerReference());

        $pathsNode = $xmlDocument->addChild('Paths');
        $itemsNode = $pathsNode->addChild('Items');

        foreach ($paths as $path) {
            $itemsNode->addChild('Path', $path);
        }

        $pathsNode->addChild('Quantity', (string) count($paths));

        $xml = $xmlDocument->asXML();

        if (!is_string($xml)) {
            throw new \RuntimeException('Unable to generate invalidation XML payload');
        }

        return $xml;
    }
}
