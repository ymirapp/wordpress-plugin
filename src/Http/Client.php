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

namespace Ymir\Plugin\Http;

use Ymir\Plugin\Support\Collection;

/**
 * Ymir HTTP client that partially mirrors the WordPress HTTP API.
 *
 * @see https://developer.wordpress.org/plugins/http-api
 */
class Client
{
    /**
     * The cURL handle.
     *
     * @var resource
     */
    private $handle;

    /**
     * The Ymir plugin version.
     *
     * @var string
     */
    private $version;

    /**
     * Constructor.
     */
    public function __construct(string $version)
    {
        $handle = curl_init();

        if (!is_resource($handle)) {
            throw new \RuntimeException('Unable to initialize a cURL session');
        }

        curl_setopt($handle, CURLINFO_HEADER_OUT, true);

        curl_setopt($handle, CURLOPT_HEADER, false);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
        curl_setopt($handle, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
        curl_setopt($handle, CURLOPT_CAINFO, ABSPATH.WPINC.'/certificates/ca-bundle.crt');
        curl_setopt($handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2TLS);
        curl_setopt($handle, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        $this->handle = $handle;
        $this->version = $version;
    }

    /**
     * Close cURL session.
     */
    public function __destruct()
    {
        if (is_resource($this->handle)) {
            curl_close($this->handle);
        }
    }

    /**
     * Send an HTTP request.
     *
     * @see WP_Http::request
     */
    public function request(string $url, array $options = []): array
    {
        $options = array_merge([
            'method' => 'GET',
            'timeout' => 5,
            'connect_timeout' => 10,
            'redirection' => 5,
            'user-agent' => sprintf('ymir-plugin/%s', $this->version),
            'headers' => [],
            'body' => null,
        ], $options);

        // By default, cURL sends the "Expect" header all the time which severely impacts
        // performance. Instead, we'll send it if the body is larger than 1 mb like
        // Guzzle does.
        //
        // @see https://stackoverflow.com/questions/22381855/whole-second-delays-when-communicating-with-aws-dynamodb
        $options['headers']['expect'] = !empty($options['body']) && strlen($options['body']) > 1048576 ? '100-Continue' : '';

        if (!in_array($options['method'], ['GET', 'POST'])) {
            curl_setopt($this->handle, CURLOPT_CUSTOMREQUEST, $options['method']);
        } elseif ('POST' === $options['method']) {
            curl_setopt($this->handle, CURLOPT_POST, true);
        }

        if ('HEAD' === $options['method']) {
            curl_setopt($this->handle, CURLOPT_NOBODY, true);
        } elseif (in_array($options['method'], ['POST', 'PUT'])) {
            curl_setopt($this->handle, CURLOPT_POSTFIELDS, $options['body'] ?? '');
        } elseif (!empty($options['body'])) {
            curl_setopt($this->handle, CURLOPT_POSTFIELDS, $options['body']);
        }

        if (!empty($options['headers'])) {
            curl_setopt($this->handle, CURLOPT_HTTPHEADER, array_map(function ($key, $value) {
                return sprintf('%s: %s', $key, $value);
            }, array_keys($options['headers']), $options['headers']));
        }

        curl_setopt($this->handle, CURLOPT_CONNECTTIMEOUT_MS, round($options['connect_timeout'] * 1000));
        curl_setopt($this->handle, CURLOPT_TIMEOUT_MS, round($options['timeout'] * 1000));
        curl_setopt($this->handle, CURLOPT_REFERER, $url);
        curl_setopt($this->handle, CURLOPT_URL, $url);
        curl_setopt($this->handle, CURLOPT_USERAGENT, $options['user-agent']);

        $response = $this->execute($this->handle);

        return $response;
    }

    /**
     * Execute cURL session.
     */
    private function execute($handle): array
    {
        $rawHeaders = '';

        curl_setopt($handle, CURLOPT_HEADERFUNCTION, function ($handle, $header) use (&$rawHeaders) {
            $rawHeaders .= $header;

            return strlen($header);
        });

        $body = curl_exec($handle);

        curl_setopt($this->handle, CURLOPT_HEADERFUNCTION, null);

        if (curl_errno($handle)) {
            throw new \RuntimeException(sprintf('cURL error %s: %s', curl_errno($handle), curl_error($handle)));
        }

        $headers = explode("\n", preg_replace('/\n[ \t]/', ' ', str_replace("\r\n", "\n", $rawHeaders)));
        $matches = [];

        preg_match('#^HTTP/(1\.\d)[ \t]+(\d+)[ \t]+(.+)#i', array_shift($headers), $matches);

        if (!isset($matches[2], $matches[3])) {
            throw new \RuntimeException('Unable to parse response code');
        }

        return [
            'body' => $body,
            'headers' => (new Collection($headers))->filter()->mapWithKeys(function (string $header) {
                list($key, $value) = explode(':', $header, 2);

                return [strtolower($key) => preg_replace('#(\s+)#i', ' ', trim($value))];
            })->all(),
            'response' => [
                'code' => (int) $matches[2],
                'message' => $matches[3],
            ],
        ];
    }
}
