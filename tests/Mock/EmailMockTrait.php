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
use Ymir\Plugin\Email\Email;

trait EmailMockTrait
{
    /**
     * Creates a mock of a Email object.
     */
    private function getEmailMock(): MockObject
    {
        return $this->getMockBuilder(Email::class)
                    ->disableOriginalConstructor()
                    ->getMock();
    }
}
