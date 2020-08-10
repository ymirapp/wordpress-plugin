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

use Ymir\Plugin\Subscriber\SecurityHeadersSubscriber;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\Subscriber\SecurityHeadersSubscriber
 */
class SecurityHeadersSubscriberTest extends TestCase
{
    use FunctionMockTrait;

    public function testAddSecurityHeaders()
    {
        $headers = (new SecurityHeadersSubscriber())->addSecurityHeaders([]);

        $this->assertEmpty(array_diff($headers, [
            'Referrer-Policy' => 'same-origin',
            'Strict-Transport-Security' => 'max-age=15768000; includeSubDomains; preload',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-XSS-Protection' => '1; mode=block',
        ]));
    }

    public function testSendSecurityHeaders()
    {
        $header = $this->getFunctionMock($this->getNamespace(SecurityHeadersSubscriber::class), 'header');

        $header->expects($this->exactly(5))
               ->withConsecutive(
                   [$this->identicalTo('Referrer-Policy: same-origin')],
                   [$this->identicalTo('Strict-Transport-Security: max-age=15768000; includeSubDomains; preload')],
                   [$this->identicalTo('X-Content-Type-Options: nosniff')],
                   [$this->identicalTo('X-Frame-Options: SAMEORIGIN')],
                   [$this->identicalTo('X-XSS-Protection: 1; mode=block')]
               );

        (new SecurityHeadersSubscriber())->sendSecurityHeaders();
    }
}
