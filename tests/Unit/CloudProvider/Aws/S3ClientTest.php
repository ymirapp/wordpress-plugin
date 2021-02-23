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

/**
 * @covers \Ymir\Plugin\CloudProvider\Aws\S3Client
 */
class S3ClientTest extends TestCase
{
    use FunctionMockTrait;
    use HttpClientMockTrait;

    public function testCopyObject()
    {
        $http = $this->getHttpClientMock();
        $http->expects($this->once())
             ->method('request')
             ->with(
                 $this->identicalTo('https://test-bucket.s3.us-east-1.amazonaws.com/target-key'),
                 $this->identicalTo([
                     'headers' => [
                         'host' => 'test-bucket.s3.us-east-1.amazonaws.com',
                         'x-amz-acl' => 'public-read',
                         'x-amz-content-sha256' => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
                         'x-amz-copy-source' => '/test-bucket/source-key',
                         'x-amz-date' => '20200515T181004Z',
                         'authorization' => 'AWS4-HMAC-SHA256 Credential=aws-key/20200515/us-east-1/s3/aws4_request,SignedHeaders=host;x-amz-acl;x-amz-content-sha256;x-amz-copy-source;x-amz-date,Signature=9c49fd8fcf33a5fc59267cf87b9f5f15cf61a33d7fcb8eda1e1e8d9d5b1487a6',
                     ],
                     'method' => 'PUT',
                     'timeout' => 300,
                 ])
             )
             ->willReturn([
                 'response' => ['code' => 200],
             ]);

        $gmdate = $this->getFunctionMock($this->getNamespace(S3Client::class), 'gmdate');
        $gmdate->expects($this->exactly(5))
               ->withConsecutive(
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd')]
               )
               ->willReturnOnConsecutiveCalls('20200515T181004Z', '20200515', '20200515T181004Z', '20200515', '20200515');

        (new S3Client($http, 'test-bucket', 'aws-key', 'us-east-1', 'aws-secret'))->copyObject('source-key', 'target-key');
    }

    public function testCreatePutObjectRequest()
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

        $this->assertSame('https://test-bucket.s3.us-east-1.amazonaws.com/object-key?X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=aws-key%2F20200515%2Fus-east-1%2Fs3%2Faws4_request&X-Amz-Date=20200515T181004Z&X-Amz-Expires=3600&X-Amz-SignedHeaders=host%3Bx-amz-acl&X-Amz-Signature=196d3b99d39a506d8edbd65eb976b3916ec08bc2b8be1859c676c7cf98df1578', (new S3Client($this->getHttpClientMock(), 'test-bucket', 'aws-key', 'us-east-1', 'aws-secret'))->createPutObjectRequest('object-key'));
    }

    public function testDeleteObject()
    {
        $http = $this->getHttpClientMock();
        $http->expects($this->once())
             ->method('request')
             ->with(
                 $this->identicalTo('https://test-bucket.s3.us-east-1.amazonaws.com/object-key'),
                 $this->identicalTo([
                     'headers' => [
                         'host' => 'test-bucket.s3.us-east-1.amazonaws.com',
                         'x-amz-acl' => 'public-read',
                         'x-amz-content-sha256' => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
                         'x-amz-date' => '20200515T181004Z',
                         'authorization' => 'AWS4-HMAC-SHA256 Credential=aws-key/20200515/us-east-1/s3/aws4_request,SignedHeaders=host;x-amz-acl;x-amz-content-sha256;x-amz-date,Signature=5e2a7daf50ba69c5ca92eb5fe403d78f797fb73ce2d280f4ff89db5531928e51',
                     ],
                     'method' => 'DELETE',
                     'timeout' => 300,
                 ])
             )
             ->willReturn([
                 'response' => ['code' => 204],
             ]);

        $gmdate = $this->getFunctionMock($this->getNamespace(S3Client::class), 'gmdate');
        $gmdate->expects($this->exactly(5))
               ->withConsecutive(
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd')]
               )
               ->willReturnOnConsecutiveCalls('20200515T181004Z', '20200515', '20200515T181004Z', '20200515', '20200515');

        (new S3Client($http, 'test-bucket', 'aws-key', 'us-east-1', 'aws-secret'))->deleteObject('object-key');
    }

    public function testGetObject()
    {
        $http = $this->getHttpClientMock();
        $http->expects($this->once())
             ->method('request')
             ->with(
                 $this->identicalTo('https://test-bucket.s3.us-east-1.amazonaws.com/object-key'),
                 $this->identicalTo([
                     'headers' => [
                         'host' => 'test-bucket.s3.us-east-1.amazonaws.com',
                         'x-amz-acl' => 'public-read',
                         'x-amz-content-sha256' => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
                         'x-amz-date' => '20200515T181004Z',
                         'authorization' => 'AWS4-HMAC-SHA256 Credential=aws-key/20200515/us-east-1/s3/aws4_request,SignedHeaders=host;x-amz-acl;x-amz-content-sha256;x-amz-date,Signature=25f9532f5b6604bb011d5f11882df651a3d9af271cc093693a5044dfeb212acd',
                     ],
                     'method' => 'GET',
                     'timeout' => 300,
                 ])
             )
             ->willReturn([
                 'body' => 'object',
                 'response' => ['code' => 200],
             ]);

        $gmdate = $this->getFunctionMock($this->getNamespace(S3Client::class), 'gmdate');
        $gmdate->expects($this->exactly(5))
               ->withConsecutive(
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd')]
               )
               ->willReturnOnConsecutiveCalls('20200515T181004Z', '20200515', '20200515T181004Z', '20200515', '20200515');

        $this->assertSame('object', (new S3Client($http, 'test-bucket', 'aws-key', 'us-east-1', 'aws-secret'))->getObject('object-key'));
    }

    public function testGetObjectDetails()
    {
        $http = $this->getHttpClientMock();
        $http->expects($this->once())
             ->method('request')
             ->with(
                 $this->identicalTo('https://test-bucket.s3.us-east-1.amazonaws.com/object-key'),
                 $this->identicalTo([
                     'headers' => [
                         'host' => 'test-bucket.s3.us-east-1.amazonaws.com',
                         'x-amz-acl' => 'public-read',
                         'x-amz-content-sha256' => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
                         'x-amz-date' => '20200515T181004Z',
                         'authorization' => 'AWS4-HMAC-SHA256 Credential=aws-key/20200515/us-east-1/s3/aws4_request,SignedHeaders=host;x-amz-acl;x-amz-content-sha256;x-amz-date,Signature=ed4a84ec60c1b449a0bdb7e6d58149dcd221b76270a2876c0c38aed7202390d1',
                     ],
                     'method' => 'HEAD',
                     'timeout' => 300,
                 ])
             )
             ->willReturn([
                 'headers' => [
                     'content-type' => 'text/plain',
                     'content-length' => 42,
                     'last-modified' => '10 September 2000',
                 ],
                 'response' => ['code' => 200],
             ]);

        $gmdate = $this->getFunctionMock($this->getNamespace(S3Client::class), 'gmdate');
        $gmdate->expects($this->exactly(5))
               ->withConsecutive(
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd')]
               )
               ->willReturnOnConsecutiveCalls('20200515T181004Z', '20200515', '20200515T181004Z', '20200515', '20200515');

        $this->assertSame([
            'type' => 'text/plain',
            'size' => 42,
            'last-modified' => '10 September 2000',
        ], (new S3Client($http, 'test-bucket', 'aws-key', 'us-east-1', 'aws-secret'))->getObjectDetails('object-key'));
    }

    public function testGetObjects()
    {
        $http = $this->getHttpClientMock();
        $http->expects($this->once())
             ->method('request')
             ->with(
                 $this->identicalTo('https://test-bucket.s3.us-east-1.amazonaws.com/?list-type=2&prefix=prefix_'),
                 $this->identicalTo([
                     'headers' => [
                         'host' => 'test-bucket.s3.us-east-1.amazonaws.com',
                         'x-amz-acl' => 'public-read',
                         'x-amz-content-sha256' => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
                         'x-amz-date' => '20200515T181004Z',
                         'authorization' => 'AWS4-HMAC-SHA256 Credential=aws-key/20200515/us-east-1/s3/aws4_request,SignedHeaders=host;x-amz-acl;x-amz-content-sha256;x-amz-date,Signature=23b76a9f0e119d7130143ab1ca9dd1241e62226ad05fd4723563137f2ae9218f',
                     ],
                     'method' => 'GET',
                     'timeout' => 300,
                 ])
             )
             ->willReturn([
                 'body' => '<?xml version="1.0" encoding="UTF-8"?>
                            <ListObjectsV2Output>
                               <Contents>
                                  <ETag>string</ETag>
                                  <Key>string</Key>
                                  <Size>integer</Size>
                               </Contents>
                            </ListObjectsV2Output>',
                 'response' => ['code' => 200],
             ]);

        $gmdate = $this->getFunctionMock($this->getNamespace(S3Client::class), 'gmdate');
        $gmdate->expects($this->exactly(5))
               ->withConsecutive(
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd')]
               )
               ->willReturnOnConsecutiveCalls('20200515T181004Z', '20200515', '20200515T181004Z', '20200515', '20200515');

        $this->assertSame([[
            'ETag' => 'string',
            'Key' => 'string',
            'Size' => 'integer',
        ]], (new S3Client($http, 'test-bucket', 'aws-key', 'us-east-1', 'aws-secret'))->getObjects('prefix_'));
    }

    public function testObjectExists()
    {
        $http = $this->getHttpClientMock();
        $http->expects($this->once())
             ->method('request')
             ->with(
                 $this->identicalTo('https://test-bucket.s3.us-east-1.amazonaws.com/object-key'),
                 $this->identicalTo([
                     'headers' => [
                         'host' => 'test-bucket.s3.us-east-1.amazonaws.com',
                         'x-amz-acl' => 'public-read',
                         'x-amz-content-sha256' => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
                         'x-amz-date' => '20200515T181004Z',
                         'authorization' => 'AWS4-HMAC-SHA256 Credential=aws-key/20200515/us-east-1/s3/aws4_request,SignedHeaders=host;x-amz-acl;x-amz-content-sha256;x-amz-date,Signature=ed4a84ec60c1b449a0bdb7e6d58149dcd221b76270a2876c0c38aed7202390d1',
                     ],
                     'method' => 'HEAD',
                     'timeout' => 300,
                 ])
             )
             ->willReturn([
                'headers' => [
                    'content-type' => 'text/plain',
                    'content-length' => 42,
                    'last-modified' => '10 September 2000',
                ],
                'response' => ['code' => 200],
             ]);

        $gmdate = $this->getFunctionMock($this->getNamespace(S3Client::class), 'gmdate');
        $gmdate->expects($this->exactly(5))
               ->withConsecutive(
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd')]
               )
               ->willReturnOnConsecutiveCalls('20200515T181004Z', '20200515', '20200515T181004Z', '20200515', '20200515');

        $this->assertTrue((new S3Client($http, 'test-bucket', 'aws-key', 'us-east-1', 'aws-secret'))->objectExists('object-key'));
    }

    public function testPutObject()
    {
        $http = $this->getHttpClientMock();
        $http->expects($this->once())
            ->method('request')
            ->with(
                $this->identicalTo('https://test-bucket.s3.us-east-1.amazonaws.com/object-key'),
                $this->identicalTo([
                    'headers' => [
                        'content-type' => 'text/plain',
                        'host' => 'test-bucket.s3.us-east-1.amazonaws.com',
                        'x-amz-acl' => 'public-read',
                        'x-amz-content-sha256' => '2958d416d08aa5a472d7b509036cb7eafd542add84527e66a145ea64cb4cdc75',
                        'x-amz-date' => '20200515T181004Z',
                        'authorization' => 'AWS4-HMAC-SHA256 Credential=aws-key/20200515/us-east-1/s3/aws4_request,SignedHeaders=content-type;host;x-amz-acl;x-amz-content-sha256;x-amz-date,Signature=41d16e3ea59549c91fcb923e6c0c42e50bb42ef529e0b5ce8b6928fb53d09402',
                    ],
                    'method' => 'PUT',
                    'timeout' => 300,
                    'body' => 'object',
                ])
            )
            ->willReturn([
                'body' => 'object',
                'response' => ['code' => 200],
            ]);

        $gmdate = $this->getFunctionMock($this->getNamespace(S3Client::class), 'gmdate');
        $gmdate->expects($this->exactly(5))
            ->withConsecutive(
                [$this->identicalTo('Ymd\THis\Z')],
                [$this->identicalTo('Ymd')],
                [$this->identicalTo('Ymd\THis\Z')],
                [$this->identicalTo('Ymd')],
                [$this->identicalTo('Ymd')]
            )
            ->willReturnOnConsecutiveCalls('20200515T181004Z', '20200515', '20200515T181004Z', '20200515', '20200515');

        (new S3Client($http, 'test-bucket', 'aws-key', 'us-east-1', 'aws-secret'))->putObject('object-key', 'object', 'text/plain');
    }
}
