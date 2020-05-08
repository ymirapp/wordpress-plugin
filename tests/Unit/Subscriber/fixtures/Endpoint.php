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

namespace Ymir\Plugin\Tests\Unit\Subscriber;

use Ymir\Plugin\RestApi\EndpointInterface;

class Endpoint implements EndpointInterface
{
    public static function getPath(): string
    {
        return '/endpoint';
    }

    public function endpointCallback()
    {
    }

    public function getArguments(): array
    {
        return ['argument'];
    }

    public function getCallback(): callable
    {
        return [$this, 'endpointCallback'];
    }

    public function getMethods(): array
    {
        return ['GET'];
    }

    public function getPermissionCallback(): callable
    {
        return [$this, 'permissionCallback'];
    }

    public function permissionCallback()
    {
    }
}
