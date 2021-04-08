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

namespace Ymir\Plugin\RestApi;

/**
 * Base class for WordPress REST API endpoints.
 */
abstract class AbstractEndpoint implements EndpointInterface
{
    /**
     * {@inheritdoc}
     */
    public function getArguments(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    final public function getCallback(): callable
    {
        return [$this, 'respond'];
    }

    /**
     * {@inheritdoc}
     */
    final public function getPermissionCallback(): callable
    {
        return [$this, 'validateRequest'];
    }

    /**
     * Validates the request made to the REST API endpoint.
     */
    public function validateRequest(\WP_REST_Request $request): bool
    {
        return true;
    }

    /**
     * Respond to a request to the REST API endpoint.
     */
    abstract public function respond(\WP_REST_Request $request);
}
