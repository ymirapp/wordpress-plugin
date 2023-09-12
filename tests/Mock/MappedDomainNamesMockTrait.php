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

namespace Ymir\Plugin\Tests\Mock;

use PHPUnit\Framework\MockObject\MockObject;
use Ymir\Plugin\ValueObject\MappedDomainNames;

trait MappedDomainNamesMockTrait
{
    /**
     * Creates a mock of a MappedDomainNames object.
     */
    private function getMappedDomainNamesMock(): MockObject
    {
        return $this->getMockBuilder(MappedDomainNames::class)
                    ->disableOriginalConstructor()
                    ->getMock();
    }
}
