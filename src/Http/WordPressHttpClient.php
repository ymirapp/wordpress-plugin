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

class WordPressHttpClient implements ClientInterface
{
    /**
     * WordPress HTTP transport used for communication.
     *
     * @var \WP_Http
     */
    private $http;

    /**
     * The Ymir plugin version.
     *
     * @var string
     */
    private $version;

    /**
     * Constructor.
     */
    public function __construct(\WP_Http $http, string $version)
    {
        $this->http = $http;
        $this->version = $version;
    }

    /**
     * {@inheritdoc}
     */
    public function request(string $url, array $options = []): array
    {
        $options['user-agent'] = sprintf('ymir-plugin/%s php-requests/%s', $this->version, class_exists('\WpOrg\Requests\Requests') ? \WpOrg\Requests\Requests::VERSION : \Requests::VERSION);

        $response = $this->http->request($url, $options);

        if ($response instanceof \WP_Error) {
            throw new \RuntimeException($response->get_error_message());
        } elseif (!is_array($response)) {
            throw new \RuntimeException('Unexpected response from WordPress HTTP client');
        }

        return $response;
    }
}
