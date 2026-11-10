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

use PHPUnit\Framework\MockObject\MockObject;
use Ymir\Plugin\CloudProvider\Aws\LambdaClient;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Mock\HttpClientMockTrait;
use Ymir\Plugin\Tests\Mock\WPPostMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

class LambdaClientTest extends TestCase
{
    use FunctionMockTrait;
    use HttpClientMockTrait;
    use WPPostMockTrait;

    public function provideEditAttachmentImageCommands(): array
    {
        return [
            'apostrophe in history' => ['[{"c":"it\'s"}]', 'all', "ymir edit-attachment-image 4 '[{\"c\":\"it'\\''s\"}]' --apply='all'"],
            'injection in history' => ['["\';touch /tmp/pwned;\'"]', 'all', "ymir edit-attachment-image 4 '[\"'\\'';touch /tmp/pwned;'\\''\"]' --apply='all'"],
            'injection in apply' => ['[{"r":90}]', "x' \$(id) '", "ymir edit-attachment-image 4 '[{\"r\":90}]' --apply='x'\\'' \$(id) '\\'''"],
            'unicode in history' => ['[{"c":"café 日本語"}]', 'all', "ymir edit-attachment-image 4 '[{\"c\":\"café 日本語\"}]' --apply='all'"],
        ];
    }

    public function provideRunWpCliCommandSiteUrls(): array
    {
        return [
            'default url' => ["https://foo.bar/' && id && '", '', "--url='https://foo.bar/'\\'' && id && '\\'''"],
            'override url' => ['https://foo.bar', "https://baz.bar/'\$(id)'", "--url='https://baz.bar/'\\''\$(id)'\\'''"],
        ];
    }

    public function testCreateAttachmentMetadata(): void
    {
        $http = $this->getHttpClientMock();
        $http->expects($this->once())
             ->method('request')
             ->with(
                 $this->identicalTo('https://lambda.us-east-1.amazonaws.com/2015-03-31/functions/test-function/invocations?Qualifier=deployed'),
                 $this->identicalTo([
                     'headers' => [
                         'content-type' => 'application/json',
                         'host' => 'lambda.us-east-1.amazonaws.com',
                         'x-amz-content-sha256' => '89ad346262253983d50de33f1c15c62e3ee594effee173409e334f12ff812fdb',
                         'x-amz-date' => '20200515T181004Z',
                         'x-amz-invocation-type' => 'RequestResponse',
                         'authorization' => 'AWS4-HMAC-SHA256 Credential=aws-key/20200515/us-east-1/lambda/aws4_request,SignedHeaders=content-type;host;x-amz-content-sha256;x-amz-date;x-amz-invocation-type,Signature=67fb8c95e6617ce371a940ece52e3cd54f0d4a31326b9ac373acf41de70f743e',
                         'content-length' => 77,
                     ],
                     'method' => 'POST',
                     'timeout' => 300,
                     'body' => '{"php":"bin\/wp ymir create-attachment-metadata 4 --url=\'https:\/\/foo.bar\'"}',
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

        (new LambdaClient($http, 'test-function', 'aws-key', 'us-east-1', 'aws-secret', 'https://foo.bar'))->createAttachmentMetadata($post);
    }

    public function testCreateAttachmentMetadataWithSpecialCharacters(): void
    {
        $http = $this->getHttpClientMock();
        $http->expects($this->once())
            ->method('request')
            ->with(
                $this->identicalTo('https://lambda.us-east-1.amazonaws.com/2015-03-31/functions/test-function/invocations?Qualifier=deployed'),
                $this->identicalTo([
                    'headers' => [
                        'content-type' => 'application/json',
                        'host' => 'lambda.us-east-1.amazonaws.com',
                        'x-amz-content-sha256' => '89ad346262253983d50de33f1c15c62e3ee594effee173409e334f12ff812fdb',
                        'x-amz-date' => '20200515T181004Z',
                        'x-amz-invocation-type' => 'RequestResponse',
                        'authorization' => 'AWS4-HMAC-SHA256 Credential=aws-key/20200515/us-east-1/lambda/aws4_request,SignedHeaders=content-type;host;x-amz-content-sha256;x-amz-date;x-amz-invocation-type,Signature=67fb8c95e6617ce371a940ece52e3cd54f0d4a31326b9ac373acf41de70f743e',
                        'content-length' => 77,
                    ],
                    'method' => 'POST',
                    'timeout' => 300,
                    'body' => '{"php":"bin\/wp ymir create-attachment-metadata 4 --url=\'https:\/\/foo.bar\'"}',
                ])
            )
            ->willReturn([
                'body' => '{"output": "\u001b[32;1mSuccess:\u001b[0m Created metadata for attachment \"4\""}',
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

        (new LambdaClient($http, 'test-function', 'aws-key', 'us-east-1', 'aws-secret', 'https://foo.bar'))->createAttachmentMetadata($post);
    }

    public function testCreateCroppedAttachmentImage(): void
    {
        $http = $this->getHttpClientMock();
        $http->expects($this->once())
             ->method('request')
             ->with(
                 $this->identicalTo('https://lambda.us-east-1.amazonaws.com/2015-03-31/functions/test-function/invocations?Qualifier=deployed'),
                 $this->identicalTo([
                     'headers' => [
                         'content-type' => 'application/json',
                         'host' => 'lambda.us-east-1.amazonaws.com',
                         'x-amz-content-sha256' => 'b9e41e71602e5c9424fd1d186f127dc547fd2b78fafa7370e4c164a1d9e0a44e',
                         'x-amz-date' => '20200515T181004Z',
                         'x-amz-invocation-type' => 'RequestResponse',
                         'authorization' => 'AWS4-HMAC-SHA256 Credential=aws-key/20200515/us-east-1/lambda/aws4_request,SignedHeaders=content-type;host;x-amz-content-sha256;x-amz-date;x-amz-invocation-type,Signature=4800f426f76cdea0b25c56c3640904e1b3ee6d1a60131c7f37967956ba1e8bb9',
                         'content-length' => 116,
                     ],
                     'method' => 'POST',
                     'timeout' => 300,
                     'body' => '{"php":"bin\/wp ymir create-cropped-image 4 --width=\'42\' --height=\'24\' --x=\'14\' --y=\'21\' --url=\'https:\/\/foo.bar\'"}',
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

        $this->assertSame(5, (new LambdaClient($http, 'test-function', 'aws-key', 'us-east-1', 'aws-secret', 'https://foo.bar'))->createCroppedAttachmentImage($post, 42, 24, 14, 21));
    }

    public function testCreateCroppedAttachmentImageQuotesContext(): void
    {
        $client = new LambdaClient($this->getHttpClientMockForCommand("ymir create-cropped-image 4 --width='42' --height='24' --x='14' --y='21' --context='custom'\\'';id;'\\''' --url='https://foo.bar'", '{"output": "Success: Cropped attachment image successfully created with ID 5"}'), 'test-function', 'aws-key', 'us-east-1', 'aws-secret', 'https://foo.bar');

        $this->assertSame(5, $client->createCroppedAttachmentImage(4, 42, 24, 14, 21, "custom';id;'"));
    }

    public function testCreateCroppedAttachmentImageRejectsNullByteInContextWithoutInvokingLambda(): void
    {
        $http = $this->getHttpClientMock();
        $http->expects($this->never())
             ->method('request');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Shell arguments cannot contain null bytes');

        (new LambdaClient($http, 'test-function', 'aws-key', 'us-east-1', 'aws-secret', 'https://foo.bar'))->createCroppedAttachmentImage(4, 42, 24, 14, 21, "custom\0' --url='https://evil.bar");
    }

    public function testCreateCroppedAttachmentImageWithSiteIconContext(): void
    {
        $http = $this->getHttpClientMock();
        $http->expects($this->once())
             ->method('request')
             ->with(
                 $this->identicalTo('https://lambda.us-east-1.amazonaws.com/2015-03-31/functions/test-function/invocations?Qualifier=deployed'),
                 $this->identicalTo([
                     'headers' => [
                         'content-type' => 'application/json',
                         'host' => 'lambda.us-east-1.amazonaws.com',
                         'x-amz-content-sha256' => 'f2a2acd92a51068aa1507b2880633baf622605844b6603e31579a913eba295a2',
                         'x-amz-date' => '20200515T181004Z',
                         'x-amz-invocation-type' => 'RequestResponse',
                         'authorization' => 'AWS4-HMAC-SHA256 Credential=aws-key/20200515/us-east-1/lambda/aws4_request,SignedHeaders=content-type;host;x-amz-content-sha256;x-amz-date;x-amz-invocation-type,Signature=5520e5edcd609fb60bb2b141d21382e3bc710f8ff3db3fc596aff2a7bb275930',
                         'content-length' => 112,
                     ],
                     'method' => 'POST',
                     'timeout' => 300,
                     'body' => '{"php":"bin\/wp ymir create-site-icon 4 --width=\'42\' --height=\'24\' --x=\'14\' --y=\'21\' --url=\'https:\/\/foo.bar\'"}',
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

        $this->assertSame(5, (new LambdaClient($http, 'test-function', 'aws-key', 'us-east-1', 'aws-secret', 'https://foo.bar'))->createCroppedAttachmentImage($post, 42, 24, 14, 21, 'site-icon'));
    }

    public function testEditAttachmentImage(): void
    {
        $http = $this->getHttpClientMock();
        $http->expects($this->once())
             ->method('request')
             ->with(
                 $this->identicalTo('https://lambda.us-east-1.amazonaws.com/2015-03-31/functions/test-function/invocations?Qualifier=deployed'),
                 $this->identicalTo([
                     'headers' => [
                         'content-type' => 'application/json',
                         'host' => 'lambda.us-east-1.amazonaws.com',
                         'x-amz-content-sha256' => '9c8e3203152a03a5cfe05b2134e9b72b1af1790294eeeef1400ab2f903319fcc',
                         'x-amz-date' => '20200515T181004Z',
                         'x-amz-invocation-type' => 'RequestResponse',
                         'authorization' => 'AWS4-HMAC-SHA256 Credential=aws-key/20200515/us-east-1/lambda/aws4_request,SignedHeaders=content-type;host;x-amz-content-sha256;x-amz-date;x-amz-invocation-type,Signature=eaee834514ad51afe46f2e2936951ffa383706fcb7f4cff5f3e511a4e43c947c',
                         'content-length' => 101,
                     ],
                     'method' => 'POST',
                     'timeout' => 300,
                     'body' => '{"php":"bin\/wp ymir edit-attachment-image 4 \'[{\"r\":90}]\' --apply=\'all\' --url=\'https:\/\/foo.bar\'"}',
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

        (new LambdaClient($http, 'test-function', 'aws-key', 'us-east-1', 'aws-secret', 'https://foo.bar'))->editAttachmentImage($post, '[{"r":90}]');
    }

    public function testEditAttachmentImagePreservesUnicodeUnderCLocale(): void
    {
        $locale = setlocale(LC_CTYPE, '0');
        setlocale(LC_CTYPE, 'C');

        try {
            (new LambdaClient($this->getHttpClientMockForCommand("ymir edit-attachment-image 4 '[{\"c\":\"café 日本語\"}]' --apply='all' --url='https://foo.bar'", '{"output": "Success: Edited attachment \"4\""}'), 'test-function', 'aws-key', 'us-east-1', 'aws-secret', 'https://foo.bar'))->editAttachmentImage(4, '[{"c":"café 日本語"}]');
        } finally {
            setlocale(LC_CTYPE, $locale);
        }
    }

    /**
     * @dataProvider provideEditAttachmentImageCommands
     */
    public function testEditAttachmentImageQuotesEachValueAsOneArgument(string $changes, string $apply, string $expectedCommand): void
    {
        (new LambdaClient($this->getHttpClientMockForCommand($expectedCommand." --url='https://foo.bar'", '{"output": "Success: Edited attachment \"4\""}'), 'test-function', 'aws-key', 'us-east-1', 'aws-secret', 'https://foo.bar'))->editAttachmentImage(4, $changes, $apply);
    }

    public function testEditAttachmentImageRejectsInvalidJsonWithoutInvokingLambda(): void
    {
        $http = $this->getHttpClientMock();
        $http->expects($this->never())
             ->method('request');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The given image "changes" must be valid JSON');

        (new LambdaClient($http, 'test-function', 'aws-key', 'us-east-1', 'aws-secret', 'https://foo.bar'))->editAttachmentImage(4, "'; id; '");
    }

    public function testResizeAttachmentImage(): void
    {
        $http = $this->getHttpClientMock();
        $http->expects($this->once())
             ->method('request')
             ->with(
                 $this->identicalTo('https://lambda.us-east-1.amazonaws.com/2015-03-31/functions/test-function/invocations?Qualifier=deployed'),
                 $this->identicalTo([
                     'headers' => [
                         'content-type' => 'application/json',
                         'host' => 'lambda.us-east-1.amazonaws.com',
                         'x-amz-content-sha256' => '9ab19c39949c96a4a6c2f8a4563b48c7ec23fa4b067fee2b615912b11df3ab71',
                         'x-amz-date' => '20200515T181004Z',
                         'x-amz-invocation-type' => 'RequestResponse',
                         'authorization' => 'AWS4-HMAC-SHA256 Credential=aws-key/20200515/us-east-1/lambda/aws4_request,SignedHeaders=content-type;host;x-amz-content-sha256;x-amz-date;x-amz-invocation-type,Signature=bf3efbb104ae2375b88a3385a88d2b0e13c5796c6c38431c9ad897679c9647b4',
                         'content-length' => 101,
                     ],
                     'method' => 'POST',
                     'timeout' => 300,
                     'body' => '{"php":"bin\/wp ymir resize-attachment-image 4 --width=\'42\' --height=\'24\' --url=\'https:\/\/foo.bar\'"}',
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

        (new LambdaClient($http, 'test-function', 'aws-key', 'us-east-1', 'aws-secret', 'https://foo.bar'))->resizeAttachmentImage($post, 42, 24);
    }

    /**
     * @dataProvider provideRunWpCliCommandSiteUrls
     */
    public function testRunWpCliCommandQuotesSiteUrl(string $defaultSiteUrl, string $siteUrl, string $expectedUrlOption): void
    {
        $this->assertSame('', (new LambdaClient($this->getHttpClientMockForCommand('cron event run --due-now '.$expectedUrlOption, '', 'Event'), 'test-function', 'aws-key', 'us-east-1', 'aws-secret', $defaultSiteUrl))->runWpCliCommand('wp cron event run --due-now', true, $siteUrl));
    }

    private function getHttpClientMockForCommand(string $expectedCommand, string $responseBody, string $invocationType = 'RequestResponse'): MockObject
    {
        $http = $this->getHttpClientMock();
        $http->expects($this->once())
             ->method('request')
             ->with(
                 $this->identicalTo('https://lambda.us-east-1.amazonaws.com/2015-03-31/functions/test-function/invocations?Qualifier=deployed'),
                 $this->callback(function (array $options) use ($expectedCommand, $invocationType) {
                     return $invocationType === $options['headers']['x-amz-invocation-type'] && ['php' => 'bin/wp '.$expectedCommand] === json_decode($options['body'], true);
                 })
             )
             ->willReturn(['body' => $responseBody]);

        return $http;
    }
}
