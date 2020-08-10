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
                         'x-amz-content-sha256' => 'feef80f78bb5a777af338f15f23eb277357af137e7c3033eabf7867f3d446544',
                         'x-amz-date' => '20200515T181004Z',
                         'x-amz-invocation-type' => 'RequestResponse',
                         'authorization' => 'AWS4-HMAC-SHA256 Credential=aws-key/20200515/us-east-1/lambda/aws4_request,SignedHeaders=content-type;host;x-amz-content-sha256;x-amz-date;x-amz-invocation-type,Signature=e9568bbd1f3645609439951bcbe20452a59fe840d181839acf4e1575ee20b866',
                     ],
                     'method' => 'POST',
                     'timeout' => 300,
                     'body' => '{"php":"bin\/wp ymir create-cropped-image 4 --width=\'42\' --height=\'24\' --x=\'14\' --y=\'21\'"}',
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
                         'x-amz-content-sha256' => '9e6a7b355eb763f8746ebad2232fef4155f06eafe9685529c21ce3c65ee4ddf7',
                         'x-amz-date' => '20200515T181004Z',
                         'x-amz-invocation-type' => 'RequestResponse',
                         'authorization' => 'AWS4-HMAC-SHA256 Credential=aws-key/20200515/us-east-1/lambda/aws4_request,SignedHeaders=content-type;host;x-amz-content-sha256;x-amz-date;x-amz-invocation-type,Signature=4830f405ce3615abb3a6932a171662e9fd3fef2ee8b079901f39866cf8480297',
                     ],
                     'method' => 'POST',
                     'timeout' => 300,
                     'body' => '{"php":"bin\/wp ymir create-site-icon 4 --width=\'42\' --height=\'24\' --x=\'14\' --y=\'21\'"}',
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
                         'x-amz-content-sha256' => '695dabae3cd2d3eace3894a38c7854cce841845d685ddb2ad81a38b833423cda',
                         'x-amz-date' => '20200515T181004Z',
                         'x-amz-invocation-type' => 'RequestResponse',
                         'authorization' => 'AWS4-HMAC-SHA256 Credential=aws-key/20200515/us-east-1/lambda/aws4_request,SignedHeaders=content-type;host;x-amz-content-sha256;x-amz-date;x-amz-invocation-type,Signature=bba8c1a6508cd5b6a05e5a5fbeb738848e57ddb09b12d1bf831fe5579f212063',
                     ],
                     'method' => 'POST',
                     'timeout' => 300,
                     'body' => '{"php":"bin\/wp ymir edit-attachment-image 4 \'[{\"r\":90}]\' --apply=\'all\'"}',
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
                         'x-amz-content-sha256' => '12d8ffe0d0db46ce8b7d54803c0fd2692d891a0db5867ed61f822c485929c609',
                         'x-amz-date' => '20200515T181004Z',
                         'x-amz-invocation-type' => 'RequestResponse',
                         'authorization' => 'AWS4-HMAC-SHA256 Credential=aws-key/20200515/us-east-1/lambda/aws4_request,SignedHeaders=content-type;host;x-amz-content-sha256;x-amz-date;x-amz-invocation-type,Signature=81e0405bb61b1e8b40bca8674a70d73e9f823739279b3f7a965914e8551e01e7',
                     ],
                     'method' => 'POST',
                     'timeout' => 300,
                     'body' => '{"php":"bin\/wp ymir resize-attachment-image 4 --width=\'42\' --height=\'24\'"}',
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
