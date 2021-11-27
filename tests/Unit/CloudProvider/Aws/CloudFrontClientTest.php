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

use Ymir\Plugin\CloudProvider\Aws\CloudFrontClient;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Mock\HttpClientMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\CloudProvider\Aws\CloudFrontClient
 */
class CloudFrontClientTest extends TestCase
{
    use FunctionMockTrait;
    use HttpClientMockTrait;

    public function provideFilterUniquePaths(): array
    {
        return [
            [
                ['/foo', '/bar', '/foo/bar', '/foo/*', '/bar', '/baz/*', '/foo/bar/*'],
                ['/baz/*', '/foo/*', '/foo', '/bar'],
            ],
            [
                ['/foo', '/bar', '/foo/bar', '/foo/*', '/bar', '/*', '/baz/*', '/foo/bar/*'],
                ['/*'],
            ],
            [
                ['/foo', '/bar', '/foo/bar', '/foo/*', '/bar', '*', '/baz/*', '/foo/bar/*'],
                ['*'],
            ],
        ];
    }

    public function provideGenerateInvalidationPayload(): array
    {
        return [
            [
                ['/foo'],
                '0YZjRydzgMgvDbZ7',
                1637682966,
                "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<InvalidationBatch xmlns=\"http://cloudfront.amazonaws.com/doc/2020-05-31/\"><CallerReference>0YZjRydzgMgvDbZ7-1637682966</CallerReference><Paths><Items><Path>/foo</Path></Items><Quantity>1</Quantity></Paths></InvalidationBatch>\n",
            ],
        ];
    }

    public function testClearAllAddsWildcardPath()
    {
        $invalidationPathsProperty = new \ReflectionProperty(CloudFrontClient::class, 'invalidationPaths');
        $invalidationPathsProperty->setAccessible(true);

        $client = new CloudFrontClient($this->getHttpClientMock(), 'distribution-id', 'aws-key', 'aws-secret');
        $client->clearAll();

        $this->assertSame(['/*'], $invalidationPathsProperty->getValue($client));
    }

    public function testClearAllRemovesPreviouslyAddedPaths()
    {
        $invalidationPathsProperty = new \ReflectionProperty(CloudFrontClient::class, 'invalidationPaths');
        $invalidationPathsProperty->setAccessible(true);

        $client = new CloudFrontClient($this->getHttpClientMock(), 'distribution-id', 'aws-key', 'aws-secret');

        $invalidationPathsProperty->setValue($client, ['/path']);

        $client->clearAll();

        $this->assertSame(['/*'], $invalidationPathsProperty->getValue($client));
    }

    public function testClearUrlAddsSlashToPath()
    {
        $path = $this->faker->slug;

        $invalidationPathsProperty = new \ReflectionProperty(CloudFrontClient::class, 'invalidationPaths');
        $invalidationPathsProperty->setAccessible(true);

        $client = new CloudFrontClient($this->getHttpClientMock(), 'distribution-id', 'aws-key', 'aws-secret');
        $client->clearUrl($path);

        $this->assertSame(['/'.$path], $invalidationPathsProperty->getValue($client));
    }

    public function testClearUrlWithAPath()
    {
        $path = parse_url($this->faker->url, PHP_URL_PATH);

        $invalidationPathsProperty = new \ReflectionProperty(CloudFrontClient::class, 'invalidationPaths');
        $invalidationPathsProperty->setAccessible(true);

        $client = new CloudFrontClient($this->getHttpClientMock(), 'distribution-id', 'aws-key', 'aws-secret');
        $client->clearUrl($path);

        $this->assertSame([$path], $invalidationPathsProperty->getValue($client));
    }

    public function testClearUrlWithAUrl()
    {
        $url = $this->faker->url;

        $invalidationPathsProperty = new \ReflectionProperty(CloudFrontClient::class, 'invalidationPaths');
        $invalidationPathsProperty->setAccessible(true);

        $client = new CloudFrontClient($this->getHttpClientMock(), 'distribution-id', 'aws-key', 'aws-secret');
        $client->clearUrl($url);

        $this->assertSame([parse_url($url, PHP_URL_PATH)], $invalidationPathsProperty->getValue($client));
    }

    /**
     * @dataProvider provideFilterUniquePaths
     */
    public function testFilterUniquePaths(array $paths, array $expectedPaths)
    {
        $filterUniquePathsMethod = new \ReflectionMethod(CloudFrontClient::class, 'filterUniquePaths');
        $filterUniquePathsMethod->setAccessible(true);

        $this->assertSame($expectedPaths, $filterUniquePathsMethod->invoke(new CloudFrontClient($this->getHttpClientMock(), 'distribution-id', 'aws-key', 'aws-secret'), $paths));
    }

    /**
     * @dataProvider provideGenerateInvalidationPayload
     */
    public function testGenerateInvalidationPayload(array $paths, string $prefix, int $time, string $expectedXml)
    {
        $generateInvalidationPayloadMethod = new \ReflectionMethod(CloudFrontClient::class, 'generateInvalidationPayload');
        $generateInvalidationPayloadMethod->setAccessible(true);

        $base64_encode = $this->getFunctionMock($this->getNamespace(CloudFrontClient::class), 'base64_encode');
        $base64_encode->expects($this->once())
                      ->willReturn($prefix);

        $timeFunction = $this->getFunctionMock($this->getNamespace(CloudFrontClient::class), 'time');
        $timeFunction->expects($this->once())
                     ->willReturn($time);

        $this->assertSame($expectedXml, $generateInvalidationPayloadMethod->invoke(new CloudFrontClient($this->getHttpClientMock(), 'distribution-id', 'aws-key', 'aws-secret'), $paths));
    }

    public function testSendClearRequestWithSuccessfulResponse()
    {
        $base64_encode = $this->getFunctionMock($this->getNamespace(CloudFrontClient::class), 'base64_encode');
        $base64_encode->expects($this->once())
                      ->willReturn('0YZjRydzgMgvDbZ7');

        $http = $this->getHttpClientMock();
        $http->expects($this->once())
             ->method('request')
             ->with(
                 $this->identicalTo('https://cloudfront.amazonaws.com/2020-05-31/distribution/distribution-id/invalidation'),
                 $this->identicalTo([
                     'headers' => [
                         'host' => 'cloudfront.amazonaws.com',
                         'x-amz-content-sha256' => '0bfce47169864e4e072e14d7ad5fda263ecffcf1b40245c2b0fa1f8ed750b02c',
                         'x-amz-date' => '20200515T181004Z',
                         'authorization' => 'AWS4-HMAC-SHA256 Credential=aws-key/20200515/us-east-1/cloudfront/aws4_request,SignedHeaders=host;x-amz-content-sha256;x-amz-date,Signature=71a81381134e6c45a29116f59b96334ae990a0220b04bf6c2540b4216797048c',
                     ],
                     'method' => 'POST',
                     'timeout' => 300,
                     'body' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<InvalidationBatch xmlns=\"http://cloudfront.amazonaws.com/doc/2020-05-31/\"><CallerReference>0YZjRydzgMgvDbZ7-20200515</CallerReference><Paths><Items><Path>/path</Path></Items><Quantity>1</Quantity></Paths></InvalidationBatch>\n",
                 ])
             )
             ->willReturn([
                 'response' => ['code' => 201],
             ]);

        $gmdate = $this->getFunctionMock($this->getNamespace(CloudFrontClient::class), 'gmdate');
        $gmdate->expects($this->exactly(5))
               ->withConsecutive(
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd')]
               )
               ->willReturnOnConsecutiveCalls('20200515T181004Z', '20200515', '20200515T181004Z', '20200515', '20200515');

        $timeFunction = $this->getFunctionMock($this->getNamespace(CloudFrontClient::class), 'time');
        $timeFunction->expects($this->once())
                     ->willReturn('20200515');

        $invalidationPathsProperty = new \ReflectionProperty(CloudFrontClient::class, 'invalidationPaths');
        $invalidationPathsProperty->setAccessible(true);

        $client = new CloudFrontClient($http, 'distribution-id', 'aws-key', 'aws-secret');

        $invalidationPathsProperty->setValue($client, ['/path']);

        $client->sendClearRequest();
    }

    public function testSendClearRequestWithUnsuccessfulResponse()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalidation request failed');

        $base64_encode = $this->getFunctionMock($this->getNamespace(CloudFrontClient::class), 'base64_encode');
        $base64_encode->expects($this->once())
                      ->willReturn('0YZjRydzgMgvDbZ7');

        $http = $this->getHttpClientMock();
        $http->expects($this->once())
             ->method('request')
             ->with(
                 $this->identicalTo('https://cloudfront.amazonaws.com/2020-05-31/distribution/distribution-id/invalidation'),
                 $this->identicalTo([
                     'headers' => [
                         'host' => 'cloudfront.amazonaws.com',
                         'x-amz-content-sha256' => '0bfce47169864e4e072e14d7ad5fda263ecffcf1b40245c2b0fa1f8ed750b02c',
                         'x-amz-date' => '20200515T181004Z',
                         'authorization' => 'AWS4-HMAC-SHA256 Credential=aws-key/20200515/us-east-1/cloudfront/aws4_request,SignedHeaders=host;x-amz-content-sha256;x-amz-date,Signature=71a81381134e6c45a29116f59b96334ae990a0220b04bf6c2540b4216797048c',
                     ],
                     'method' => 'POST',
                     'timeout' => 300,
                     'body' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<InvalidationBatch xmlns=\"http://cloudfront.amazonaws.com/doc/2020-05-31/\"><CallerReference>0YZjRydzgMgvDbZ7-20200515</CallerReference><Paths><Items><Path>/path</Path></Items><Quantity>1</Quantity></Paths></InvalidationBatch>\n",
                 ])
             )
             ->willReturn([
                 'response' => ['code' => 400],
             ]);

        $gmdate = $this->getFunctionMock($this->getNamespace(CloudFrontClient::class), 'gmdate');
        $gmdate->expects($this->exactly(5))
               ->withConsecutive(
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd')]
               )
               ->willReturnOnConsecutiveCalls('20200515T181004Z', '20200515', '20200515T181004Z', '20200515', '20200515');

        $timeFunction = $this->getFunctionMock($this->getNamespace(CloudFrontClient::class), 'time');
        $timeFunction->expects($this->once())
                     ->willReturn('20200515');

        $invalidationPathsProperty = new \ReflectionProperty(CloudFrontClient::class, 'invalidationPaths');
        $invalidationPathsProperty->setAccessible(true);

        $client = new CloudFrontClient($http, 'distribution-id', 'aws-key', 'aws-secret');

        $invalidationPathsProperty->setValue($client, ['/path']);

        $client->sendClearRequest();
    }
}
