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

use Ymir\Plugin\CloudProvider\Aws\CloudFrontClient;
use Ymir\Plugin\Http\HttpClient;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\CloudProvider\Aws\CloudFrontClient
 */
class CloudFrontClientTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new CloudFrontClient(new HttpClient('test'), 'E2VS70WYKKG4W7', getenv('AWS_TEST_ACCESS_KEY_ID') ?: $_ENV['AWS_TEST_ACCESS_KEY_ID'], getenv('AWS_TEST_SECRET_ACCESS_KEY') ?: $_ENV['AWS_TEST_SECRET_ACCESS_KEY']);
    }

    public function testClearUrl()
    {
        $this->expectNotToPerformAssertions();

        $this->client->clearUrl($this->faker->url);
        $this->client->sendClearRequest();
    }
}
