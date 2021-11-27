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

/**
 * Base AWS client.
 */
abstract class AbstractClient
{
    /**
     * The Ymir HTTP client.
     *
     * @var Client
     */
    private $client;

    /**
     * The AWS API key.
     *
     * @var string
     */
    private $key;

    /**
     * The AWS region used by the client.
     *
     * @var string
     */
    private $region;

    /**
     * The AWS API secret.
     *
     * @var string
     */
    private $secret;

    /**
     * The AWS security token.
     *
     * @var string
     */
    private $securityToken;

    /**
     * Constructor.
     */
    public function __construct(Client $client, string $key, string $region, string $secret)
    {
        $this->client = $client;
        $this->key = $key;
        $this->region = $region;
        $this->secret = $secret;
        $this->securityToken = getenv('AWS_SESSION_TOKEN') ?: '';
    }

    /**
     * Create the base headers used for all AWS requests.
     */
    protected function createBaseHeaders(?string $body): array
    {
        return array_filter([
            'host' => $this->getHostname(),
            'x-amz-content-sha256' => $this->hash($body),
            'x-amz-date' => $this->getTimestamp(),
            'x-amz-security-token' => $this->securityToken,
        ]);
    }

    /**
     * Creates a presigned request for the given key and method.
     *
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/sigv4-query-string-auth.html
     */
    protected function createPresignedRequest(string $path, string $method, array $headers = []): string
    {
        $headers = $this->mergeHeaders($headers, [
            'host' => $this->getHostname(),
        ]);
        $method = strtoupper($method);
        $uri = $path.'?'.http_build_query(array_filter([
            'X-Amz-Algorithm' => 'AWS4-HMAC-SHA256',
            'X-Amz-Credential' => $this->key.'/'.$this->getScope(),
            'X-Amz-Date' => $this->getTimestamp(),
            'X-Amz-Expires' => HOUR_IN_SECONDS,
            'X-Amz-Security-Token' => $this->securityToken,
            'X-Amz-SignedHeaders' => $this->createSignedHeaders($headers),
        ]));

        return $this->createRequestUrl($uri).'&X-Amz-Signature='.$this->createSignature($uri, $headers, $method);
    }

    /**
     * Get the name of the API endpoint for the client.
     */
    protected function getEndpointName(): string
    {
        return $this->getService();
    }

    /**
     * Get the hostname for the AWS request.
     */
    protected function getHostname(): string
    {
        return "{$this->getEndpointName()}.{$this->region}.amazonaws.com";
    }

    /**
     * Parse the status code from the given response.
     */
    protected function parseResponseStatusCode(array $response): int
    {
        if (!isset($response['response']['code'])) {
            throw new \InvalidArgumentException('Missing status code header in the response array');
        }

        return (int) $response['response']['code'];
    }

    /**
     * Makes a request to the AWS API for the given URI.
     */
    protected function request(string $method, string $uri, ?string $body = null, array $headers = []): array
    {
        $arguments = [
            'headers' => $this->mergeHeaders($this->createBaseHeaders($body), $headers),
            'method' => strtoupper($method),
            'timeout' => 300,
        ];
        $arguments['headers']['authorization'] = $this->createAuthorizationHeader($uri, $arguments['headers'], $arguments['method']);

        if (null !== $body) {
            $arguments['body'] = $body;
        }

        return $this->client->request($this->createRequestUrl($uri), $arguments);
    }

    /**
     * Get the AWS service that we're making a request to.
     */
    abstract protected function getService(): string;

    /**
     * Create the authorization header used for the AWS request for the given URI.
     *
     * @see https://docs.aws.amazon.com/general/latest/gr/sigv4-add-signature-to-request.html
     */
    private function createAuthorizationHeader(string $uri, array $headers = [], string $method = 'GET'): string
    {
        return 'AWS4-HMAC-SHA256 '.implode(',', [
            'Credential='.$this->key.'/'.$this->getScope(),
            'SignedHeaders='.$this->createSignedHeaders($headers),
            'Signature='.$this->createSignature($uri, $headers, $method),
        ]);
    }

    /**
     * Create an AWS canonical request.
     *
     * @see https://docs.aws.amazon.com/general/latest/gr/sigv4-create-canonical-request.html
     */
    private function createCanonicalRequest(string $uri, array $headers = [], string $method = 'GET'): string
    {
        $uriParts = explode('?', $uri);

        if (!isset($uriParts[0])) {
            throw new \InvalidArgumentException('Error parsing request URI');
        }

        $request = $method.PHP_EOL;
        $request .= '/'.ltrim($uriParts[0], '/').PHP_EOL;

        if (isset($uriParts[1])) {
            $request .= $uriParts[1];
        }

        $request .= PHP_EOL;

        foreach ($headers as $key => $value) {
            $request .= sprintf('%s:%s'.PHP_EOL, strtolower($key), $value);
        }

        $request .= PHP_EOL;
        $request .= $this->createSignedHeaders($headers).PHP_EOL;
        $request .= $headers['x-amz-content-sha256'] ?? 'UNSIGNED-PAYLOAD';

        return $this->hash($request);
    }

    /**
     * Create the request URL using the given URI.
     */
    private function createRequestUrl(string $uri): string
    {
        return 'https://'.$this->getHostname().$uri;
    }

    /**
     * Create the signature for the AWS request.
     *
     * @see https://docs.aws.amazon.com/general/latest/gr/sigv4-calculate-signature.html
     */
    private function createSignature(string $uri, array $headers = [], string $method = 'GET'): string
    {
        return $this->hash(
            $this->createStringToSign($this->createCanonicalRequest($uri, $headers, $method), $this->getTimestamp()),
            $this->hash('aws4_request', $this->hash($this->getService(), $this->hash($this->region, $this->hash($this->getDate(), 'AWS4'.$this->secret, true), true), true), true)
        );
    }

    /**
     * Create signed headers from the given set of headers.
     */
    private function createSignedHeaders(array $headers): string
    {
        return implode(';', array_map('strtolower', array_keys($headers)));
    }

    /**
     * Creates a "string to sign" for the AWS request.
     *
     * @see https://docs.aws.amazon.com/general/latest/gr/sigv4-create-string-to-sign.html
     */
    private function createStringToSign(string $canonicalRequest, string $timestamp): string
    {
        return "AWS4-HMAC-SHA256\n{$timestamp}\n{$this->getScope()}\n$canonicalRequest";
    }

    /**
     * Get an AWS formatted date.
     */
    private function getDate(): string
    {
        return gmdate('Ymd');
    }

    /**
     * The scope of the AWS request.
     */
    private function getScope(): string
    {
        return implode('/', [$this->getDate(), $this->region, $this->getService(), 'aws4_request']);
    }

    /**
     * Get the AWS timestamp.
     */
    private function getTimestamp(): string
    {
        return gmdate('Ymd\THis\Z');
    }

    /**
     * Generate a hash value.
     */
    private function hash(?string $data, string $key = '', bool $raw = false): string
    {
        $algorithm = 'sha256';

        return empty($key) ? hash($algorithm, (string) $data, $raw) : hash_hmac($algorithm, (string) $data, $key, $raw);
    }

    /**
     * Merge headers together and sort them.
     */
    private function mergeHeaders(array $baseHeaders, array $headersToMerge): array
    {
        $mergedHeaders = array_merge($baseHeaders, $headersToMerge);

        ksort($mergedHeaders);

        return $mergedHeaders;
    }
}
