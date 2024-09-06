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
use Ymir\Plugin\Email\EmailClientInterface;

trait EmailClientMockTrait
{
    /**
     * Creates a mock of a EmailClientInterface object.
     */
    private function getEmailClientMock(): MockObject
    {
        return $this->getMockBuilder(EmailClientInterface::class)
                    ->disableOriginalConstructor()
                    ->getMock();
    }
}
