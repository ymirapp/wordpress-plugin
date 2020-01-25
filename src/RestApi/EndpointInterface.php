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
 * A WordPress REST API endpoint.
 */
interface EndpointInterface
{
    /**
     * Get the path pattern of the REST API endpoint.
     */
    public static function getPath(): string;

    /**
     * Get the expected arguments for the REST API endpoint.
     */
    public function getArguments(): array;

    /**
     * Get the callback used by the REST API endpoint.
     */
    public function getCallback(): callable;

    /**
     * Get the HTTP methods that the REST API endpoint responds to.
     */
    public function getMethods(): array;

    /**
     * Get the callback used to validate a request to the REST API endpoint.
     */
    public function getPermissionCallback(): callable;
}
