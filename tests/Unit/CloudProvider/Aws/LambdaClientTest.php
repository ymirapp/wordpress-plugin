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

use Ymir\Plugin\CloudProvider\Aws\LambdaClient;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Mock\WPHttpMockTrait;
use Ymir\Plugin\Tests\Mock\WPPostMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\CloudProvider\Aws\LambdaClient
 */
class LambdaClientTest extends TestCase
{
    use FunctionMockTrait;
    use WPHttpMockTrait;
    use WPPostMockTrait;

    public function testCreateAttachmentMetadata()
    {
        $http = $this->getWPHttpMock();
        $http->expects($this->once())
             ->method('request')
             ->with(
                 $this->identicalTo('https://lambda.us-east-1.amazonaws.com/2015-03-31/functions/test-function/invocations?Qualifier=deployed'),
                 $this->identicalTo([
                     'headers' => [
                         'content-type' => 'application/json',
                         'host' => 'lambda.us-east-1.amazonaws.com',
                         'x-amz-content-sha256' => '535345b72986631a024ca46820e9603c45f007b7fa6511ec685323e7ccacae00',
                         'x-amz-date' => '20200515T181004Z',
                         'x-amz-invocation-type' => 'RequestResponse',
                         'authorization' => 'AWS4-HMAC-SHA256 Credential=aws-key/20200515/us-east-1/lambda/aws4_request,SignedHeaders=content-type;host;x-amz-content-sha256;x-amz-date;x-amz-invocation-type,Signature=f4a505b1667d703cc9288ba7ce8fff19b2fbe84c708b979cec03fcfc675a22f0',
                     ],
                     'method' => 'POST',
                     'timeout' => 300,
                     'body' => '{"php":"bin\/wp ymir create-attachment-metadata 4"}',
                 ])
             )
             ->willReturn([
                 'body' => '{"output": "Success: Created metadata for attachment \"4\""}',
             ]);

        $gmdate = $this->getFunctionMock($this->getNamespace(LambdaClient::class), 'gmdate');
        $gmdate->expects($this->exactly(5))
               ->withConsecutive(
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd')]
               )
               ->willReturnOnConsecutiveCalls('20200515T181004Z', '20200515', '20200515T181004Z', '20200515', '20200515');

        $post = $this->getWPPostMock();
        $post->ID = 4;

        (new LambdaClient($http, 'test-function', 'aws-key', 'us-east-1', 'aws-secret'))->createAttachmentMetadata($post);
    }

    public function testCreateCroppedAttachmentImage()
    {
        $http = $this->getWPHttpMock();
        $http->expects($this->once())
             ->method('request')
             ->with(
                 $this->identicalTo('https://lambda.us-east-1.amazonaws.com/2015-03-31/functions/test-function/invocations?Qualifier=deployed'),
                 $this->identicalTo([
                     'headers' => [
                         'content-type' => 'application/json',
                         'host' => 'lambda.us-east-1.amazonaws.com',
                         'x-amz-content-sha256' => '67b5cfe7c74c70a373a4bb4173b6d1620e3f1ca6b5ea34566f7f938d97e23e3d',
                         'x-amz-date' => '20200515T181004Z',
                         'x-amz-invocation-type' => 'RequestResponse',
                         'authorization' => 'AWS4-HMAC-SHA256 Credential=aws-key/20200515/us-east-1/lambda/aws4_request,SignedHeaders=content-type;host;x-amz-content-sha256;x-amz-date;x-amz-invocation-type,Signature=5f9a4d27e320873f8e0fb96c960d6d837a074e7310e72515e89a589ecf5f3b3b',
                     ],
                     'method' => 'POST',
                     'timeout' => 300,
                     'body' => '{"php":"bin\/wp ymir create-cropped-image 4 --width=42 --height=24 --x=14 --y=21"}',
                 ])
             )
             ->willReturn([
                 'body' => '{"output": "Success: Cropped attachment image successfully created with ID 5"}',
             ]);

        $gmdate = $this->getFunctionMock($this->getNamespace(LambdaClient::class), 'gmdate');
        $gmdate->expects($this->exactly(5))
               ->withConsecutive(
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd')]
               )
               ->willReturnOnConsecutiveCalls('20200515T181004Z', '20200515', '20200515T181004Z', '20200515', '20200515');

        $post = $this->getWPPostMock();
        $post->ID = 4;

        $this->assertSame(5, (new LambdaClient($http, 'test-function', 'aws-key', 'us-east-1', 'aws-secret'))->createCroppedAttachmentImage($post, 42, 24, 14, 21));
    }

    public function testCreateCroppedAttachmentImageWithSiteIconContext()
    {
        $http = $this->getWPHttpMock();
        $http->expects($this->once())
             ->method('request')
             ->with(
                 $this->identicalTo('https://lambda.us-east-1.amazonaws.com/2015-03-31/functions/test-function/invocations?Qualifier=deployed'),
                 $this->identicalTo([
                     'headers' => [
                         'content-type' => 'application/json',
                         'host' => 'lambda.us-east-1.amazonaws.com',
                         'x-amz-content-sha256' => 'dd47a12ebb767cc130d679a3f6f5bcce3deaa899bbdd0d5e748f21995bae8a6f',
                         'x-amz-date' => '20200515T181004Z',
                         'x-amz-invocation-type' => 'RequestResponse',
                         'authorization' => 'AWS4-HMAC-SHA256 Credential=aws-key/20200515/us-east-1/lambda/aws4_request,SignedHeaders=content-type;host;x-amz-content-sha256;x-amz-date;x-amz-invocation-type,Signature=1057490aeab7b68454af5b895bd59f9b1ffdd291f2211c2ccbb8d42bc3c6dc74',
                     ],
                     'method' => 'POST',
                     'timeout' => 300,
                     'body' => '{"php":"bin\/wp ymir create-site-icon 4 --width=42 --height=24 --x=14 --y=21"}',
                 ])
             )
             ->willReturn([
                 'body' => '{"output": "Success: Site icon successfully created with ID 5"}',
             ]);

        $gmdate = $this->getFunctionMock($this->getNamespace(LambdaClient::class), 'gmdate');
        $gmdate->expects($this->exactly(5))
               ->withConsecutive(
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd')]
               )
               ->willReturnOnConsecutiveCalls('20200515T181004Z', '20200515', '20200515T181004Z', '20200515', '20200515');

        $post = $this->getWPPostMock();
        $post->ID = 4;

        $this->assertSame(5, (new LambdaClient($http, 'test-function', 'aws-key', 'us-east-1', 'aws-secret'))->createCroppedAttachmentImage($post, 42, 24, 14, 21, 'site-icon'));
    }

    public function testEditAttachmentImage()
    {
        $http = $this->getWPHttpMock();
        $http->expects($this->once())
             ->method('request')
             ->with(
                 $this->identicalTo('https://lambda.us-east-1.amazonaws.com/2015-03-31/functions/test-function/invocations?Qualifier=deployed'),
                 $this->identicalTo([
                     'headers' => [
                         'content-type' => 'application/json',
                         'host' => 'lambda.us-east-1.amazonaws.com',
                         'x-amz-content-sha256' => 'ab4a43796745a71470451dddd3cf9893442cd63e1df5eeecca27df9138bd7b71',
                         'x-amz-date' => '20200515T181004Z',
                         'x-amz-invocation-type' => 'RequestResponse',
                         'authorization' => 'AWS4-HMAC-SHA256 Credential=aws-key/20200515/us-east-1/lambda/aws4_request,SignedHeaders=content-type;host;x-amz-content-sha256;x-amz-date;x-amz-invocation-type,Signature=25de89e7b982fcd795211cc63af8f86210061ca12fe8a3f4a940823edb916c33',
                     ],
                     'method' => 'POST',
                     'timeout' => 300,
                     'body' => '{"php":"bin\/wp ymir edit-attachment-image 4 \'[{\"r\":90}]\' --apply=all"}',
                 ])
             )
             ->willReturn([
                 'body' => '{"output": "Success: Edited attachment \"4\""}',
             ]);

        $gmdate = $this->getFunctionMock($this->getNamespace(LambdaClient::class), 'gmdate');
        $gmdate->expects($this->exactly(5))
               ->withConsecutive(
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd')]
               )
               ->willReturnOnConsecutiveCalls('20200515T181004Z', '20200515', '20200515T181004Z', '20200515', '20200515');

        $post = $this->getWPPostMock();
        $post->ID = 4;

        (new LambdaClient($http, 'test-function', 'aws-key', 'us-east-1', 'aws-secret'))->editAttachmentImage($post, '[{"r":90}]');
    }

    public function testResizeAttachmentImage()
    {
        $http = $this->getWPHttpMock();
        $http->expects($this->once())
             ->method('request')
             ->with(
                 $this->identicalTo('https://lambda.us-east-1.amazonaws.com/2015-03-31/functions/test-function/invocations?Qualifier=deployed'),
                 $this->identicalTo([
                     'headers' => [
                         'content-type' => 'application/json',
                         'host' => 'lambda.us-east-1.amazonaws.com',
                         'x-amz-content-sha256' => '9699521da60d5b2869619e735a7cde5dfe7abcb2f3f7803a9242355d8edbbd30',
                         'x-amz-date' => '20200515T181004Z',
                         'x-amz-invocation-type' => 'RequestResponse',
                         'authorization' => 'AWS4-HMAC-SHA256 Credential=aws-key/20200515/us-east-1/lambda/aws4_request,SignedHeaders=content-type;host;x-amz-content-sha256;x-amz-date;x-amz-invocation-type,Signature=a66efcd83eb78017c1e2797652574a4be33a5f1c65fc70b958d37c5d06ba7a88',
                     ],
                     'method' => 'POST',
                     'timeout' => 300,
                     'body' => '{"php":"bin\/wp ymir resize-attachment-image 4 --width=42 --height=24"}',
                 ])
             )
             ->willReturn([
                 'body' => '{"output": "Success: Resized attachment \"4\" to 42x24"}',
             ]);

        $gmdate = $this->getFunctionMock($this->getNamespace(LambdaClient::class), 'gmdate');
        $gmdate->expects($this->exactly(5))
               ->withConsecutive(
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd')]
               )
               ->willReturnOnConsecutiveCalls('20200515T181004Z', '20200515', '20200515T181004Z', '20200515', '20200515');

        $post = $this->getWPPostMock();
        $post->ID = 4;

        (new LambdaClient($http, 'test-function', 'aws-key', 'us-east-1', 'aws-secret'))->resizeAttachmentImage($post, 42, 24);
    }
}
