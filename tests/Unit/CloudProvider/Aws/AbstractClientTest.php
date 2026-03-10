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

namespace Ymir\Plugin\Tests\Unit\CloudProvider\Aws;

use Ymir\Plugin\CloudProvider\Aws\S3Client;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Mock\HttpClientMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

class AbstractClientTest extends TestCase
{
    use FunctionMockTrait;
    use HttpClientMockTrait;

    public function testCreatePresignedRequestWithSecurityToken()
    {
        $gmdate = $this->getFunctionMock($this->getNamespace(S3Client::class), 'gmdate');
        $gmdate->expects($this->exactly(5))
               ->withConsecutive(
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd')]
               )
               ->willReturnOnConsecutiveCalls('20200515', '20200515T181004Z', '20200515T181004Z', '20200515', '20200515');

        $client = new S3Client($this->getHttpClientMock(), 'test-bucket', 'aws-key', 'us-east-1', 'aws-secret', 'security-token');
        $createPresignedRequestMethod = new \ReflectionMethod(S3Client::class, 'createPresignedRequest');
        $createPresignedRequestMethod->setAccessible(true);

        $request = $createPresignedRequestMethod->invoke($client, '/object-key', 'put');

        $this->assertIsString($request);

        $query = parse_url($request, PHP_URL_QUERY);

        $this->assertIsString($query);

        parse_str($query, $parameters);

        $this->assertSame('security-token', $parameters['X-Amz-Security-Token']);
    }
}
