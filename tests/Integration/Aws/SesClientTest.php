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

namespace Ymir\Plugin\Tests\Integration\Aws;

use Ymir\Plugin\CloudProvider\Aws\SesClient;
use Ymir\Plugin\Http\CurlClient;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\CloudProvider\Aws\SesClient
 */
class SesClientTest extends TestCase
{
    public function testCanSendEmailsReturnsFalse()
    {
        $client = new SesClient(new CurlClient('test'), getenv('AWS_TEST_ACCESS_KEY_ID') ?: $_ENV['AWS_TEST_ACCESS_KEY_ID'], 'us-west-1', getenv('AWS_TEST_SECRET_ACCESS_KEY') ?: $_ENV['AWS_TEST_SECRET_ACCESS_KEY']);

        $this->assertFalse($client->canSendEmails());
    }

    public function testCanSendEmailsReturnsTrue()
    {
        $client = new SesClient(new CurlClient('test'), getenv('AWS_TEST_ACCESS_KEY_ID') ?: $_ENV['AWS_TEST_ACCESS_KEY_ID'], 'us-east-1', getenv('AWS_TEST_SECRET_ACCESS_KEY') ?: $_ENV['AWS_TEST_SECRET_ACCESS_KEY']);

        $this->assertTrue($client->canSendEmails());
    }
}
