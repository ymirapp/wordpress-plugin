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

namespace Ymir\Plugin\Subscriber;

use Ymir\Plugin\EventManagement\SubscriberInterface;
use Ymir\Plugin\RestApi\EndpointInterface;

/**
 * Subscriber that manages the integration with the WordPress REST API.
 */
class RestApiSubscriber implements SubscriberInterface
{
    /**
     * The WordPress REST API endpoints used by the plugin.
     *
     * @var EndpointInterface[]
     */
    private $endpoints;

    /**
     * The namespace of the WordPress REST API endpoints managed by the subscriber.
     *
     * @var string
     */
    private $namespace;

    /**
     * Constructor.
     */
    public function __construct(string $namespace, array $endpoints = [])
    {
        $this->endpoints = [];
        $this->namespace = trim($namespace, '/');

        foreach ($endpoints as $endpoint) {
            $this->addEndpoint($endpoint);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'rest_api_init' => 'registerEndpoints',
        ];
    }

    /**
     * Register endpoints with the WordPress REST API.
     */
    public function registerEndpoints()
    {
        foreach ($this->endpoints as $endpoint) {
            $this->registerEndpoint($endpoint);
        }
    }

    /**
     * Add a new WordPress REST API endpoint to the subscriber.
     */
    private function addEndpoint(EndpointInterface $endpoint)
    {
        $this->endpoints[] = $endpoint;
    }

    /**
     * Get the arguments used to configure the endpoint with the WordPress REST API server.
     *
     * @return array
     */
    private function getArguments(EndpointInterface $endpoint)
    {
        return [
            'args' => $endpoint->getArguments(),
            'callback' => $endpoint->getCallback(),
            'methods' => $endpoint->getMethods(),
            'permission_callback' => $endpoint->getPermissionCallback(),
        ];
    }

    /**
     * Register the given endpoint with the WordPress REST API.
     */
    private function registerEndpoint(EndpointInterface $endpoint)
    {
        register_rest_route($this->namespace, $endpoint->getPath(), $this->getArguments($endpoint));
    }
}
