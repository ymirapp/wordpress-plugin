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

use Ymir\Plugin\Http\ClientInterface;
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
    public function __construct(ClientInterface $client, string $distributionId, string $key, string $secret)
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
    public function clearUrls($urls)
    {
        if (is_array($urls) || is_string($urls)) {
            $urls = new Collection($urls);
        } elseif (!$urls instanceof Collection) {
            throw new \InvalidArgumentException('Urls must be an array, a collection or a string');
        }

        $urls->filter(function ($url) {
            return is_string($url) && !empty($url);
        })->each(function (string $url) {
            $this->clearUrl($url);
        });
    }

    /**
     * Invalidate the given paths.
     */
    public function invalidatePaths(array $paths)
    {
        $concretePaths = array_filter($paths, function (string $path) {
            return !str_ends_with($path, '*');
        });
        $wildcardPaths = array_filter($paths, function (string $path) {
            return str_ends_with($path, '*');
        });

        while (!empty($wildcardPaths) || !empty($concretePaths)) {
            $batch = array_splice($wildcardPaths, 0, 15);
            $batch = array_merge($batch, array_splice($concretePaths, 0, 3000 - count($batch)));

            $this->sendInvalidation($batch);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function sendClearRequest(?callable $guard = null)
    {
        if (empty($this->invalidationPaths)) {
            return;
        }

        $paths = $this->filterUniquePaths($this->invalidationPaths);

        $this->invalidationPaths = [];

        if ($guard && !$guard($paths)) {
            return;
        }

        $this->invalidatePaths($paths);
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
     * Filter all paths and only keep unique ones.
     */
    private function filterUniquePaths(array $paths): array
    {
        if (1 === count($paths)) {
            return $paths;
        }

        $paths = (new Collection($paths))->unique();

        $filteredPaths = $paths->filter(function (string $path) {
            return !str_ends_with($path, '*');
        })->all();
        $wildcardPaths = $paths->filter(function (string $path) {
            return str_ends_with($path, '*');
        });

        $wildcardPaths = $wildcardPaths->map(function (string $path) use ($wildcardPaths) {
            $filteredWildcardPaths = preg_grep(sprintf('/^%s/', str_replace('\*', '.*', preg_quote($path, '/'))), $wildcardPaths->all(), PREG_GREP_INVERT);
            $filteredWildcardPaths[] = $path;

            return $filteredWildcardPaths;
        });

        $wildcardPaths = new Collection(!$wildcardPaths->isEmpty() ? array_intersect(...$wildcardPaths->all()) : []);

        $wildcardPaths->each(function (string $path) use (&$filteredPaths) {
            $filteredPaths = preg_grep(sprintf('/^%s/', str_replace('\*', '.*', preg_quote($path, '/'))), $filteredPaths, PREG_GREP_INVERT);
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

    /**
     * Send an invalidation request.
     */
    private function sendInvalidation(array $paths)
    {
        $attempts = 0;
        $maxAttempts = 3;
        $payload = $this->generateInvalidationPayload($paths);

        while ($attempts < $maxAttempts) {
            ++$attempts;

            $response = $this->request('post', "/2020-05-31/distribution/{$this->distributionId}/invalidation", $payload);
            $statusCode = $this->parseResponseStatusCode($response);

            if (201 === $statusCode) {
                return;
            }

            $awsError = $this->parseAwsError($response['body'] ?? '');
            $errorCode = $awsError['code'] ?? '';

            $shouldRetry = $statusCode >= 500 || 429 === $statusCode || (400 === $statusCode && in_array($errorCode, ['Throttling', 'TooManyInvalidationsInProgress'], true));

            if (!$shouldRetry || $attempts >= $maxAttempts) {
                throw new \RuntimeException($this->createExceptionMessage('Invalidation request failed', $response));
            }

            sleep($attempts * 2);
        }
    }
}
