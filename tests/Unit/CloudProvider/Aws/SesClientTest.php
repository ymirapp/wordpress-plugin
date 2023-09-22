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

use Ymir\Plugin\CloudProvider\Aws\SesClient;
use Ymir\Plugin\Tests\Mock\EmailMockTrait;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Mock\HttpClientMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\CloudProvider\Aws\LambdaClient
 */
class SesClientTest extends TestCase
{
    use EmailMockTrait;
    use FunctionMockTrait;
    use HttpClientMockTrait;

    public function testSendEmail()
    {
        $email = $this->getEmailMock();
        $email->expects($this->once())
              ->method('toString')
              ->willReturn('email');

        $http = $this->getHttpClientMock();
        $http->expects($this->once())
             ->method('request')
             ->with(
                 $this->identicalTo('https://email.us-east-1.amazonaws.com/'),
                 $this->identicalTo([
                     'headers' => [
                         'host' => 'email.us-east-1.amazonaws.com',
                         'x-amz-content-sha256' => '93c8df40dd7aabcd009385e2496d874342612b116f80638899066a8f6a2e72e6',
                         'x-amz-date' => '20200515T181004Z',
                         'authorization' => 'AWS4-HMAC-SHA256 Credential=aws-key/20200515/us-east-1/ses/aws4_request,SignedHeaders=host;x-amz-content-sha256;x-amz-date,Signature=105ad9051f2aaeb3471e626dfd368d2bccf87ea0626ace1ea0fbf459864eb62f',
                     ],
                     'method' => 'POST',
                     'timeout' => 300,
                     'body' => 'Action=SendRawEmail&RawMessage.Data=ZW1haWw%3D',
                 ])
             )
             ->willReturn([
                 'response' => ['code' => 200],
             ]);

        $gmdate = $this->getFunctionMock($this->getNamespace(SesClient::class), 'gmdate');
        $gmdate->expects($this->exactly(5))
                ->withConsecutive(
                    [$this->identicalTo('Ymd\THis\Z')],
                    [$this->identicalTo('Ymd')],
                    [$this->identicalTo('Ymd\THis\Z')],
                    [$this->identicalTo('Ymd')],
                    [$this->identicalTo('Ymd')]
                )
                ->willReturnOnConsecutiveCalls('20200515T181004Z', '20200515', '20200515T181004Z', '20200515', '20200515');

        (new SesClient($http, 'aws-key', 'us-east-1', 'aws-secret'))->sendEmail($email);
    }

    public function testSendEmailWithSesError()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SES API request failed: Email address is not verified. The following identities failed the check in region US-EAST-1: WordPress <wordpress@example.com>');

        $email = $this->getEmailMock();
        $email->expects($this->once())
              ->method('toString')
              ->willReturn('email');

        $http = $this->getHttpClientMock();
        $http->expects($this->once())
             ->method('request')
             ->with(
                 $this->identicalTo('https://email.us-east-1.amazonaws.com/'),
                 $this->identicalTo([
                     'headers' => [
                         'host' => 'email.us-east-1.amazonaws.com',
                         'x-amz-content-sha256' => '93c8df40dd7aabcd009385e2496d874342612b116f80638899066a8f6a2e72e6',
                         'x-amz-date' => '20200515T181004Z',
                         'authorization' => 'AWS4-HMAC-SHA256 Credential=aws-key/20200515/us-east-1/ses/aws4_request,SignedHeaders=host;x-amz-content-sha256;x-amz-date,Signature=105ad9051f2aaeb3471e626dfd368d2bccf87ea0626ace1ea0fbf459864eb62f',
                     ],
                     'method' => 'POST',
                     'timeout' => 300,
                     'body' => 'Action=SendRawEmail&RawMessage.Data=ZW1haWw%3D',
                 ])
             )
             ->willReturn([
                 'body' => "<ErrorResponse xmlns=\"http://ses.amazonaws.com/doc/2010-12-01/\">\n  <Error>\n    <Type>Sender</Type>\n    <Code>MessageRejected</Code>\n    <Message>Email address is not verified. The following identities failed the check in region US-EAST-1: WordPress &lt;wordpress@example.com&gt;</Message>\n  </Error>\n  <RequestId>da8072bc-3550-4d98-81f5-28169cc53c7b</RequestId>\n</ErrorResponse>\n",
                 'response' => ['code' => 400],
             ]);

        $gmdate = $this->getFunctionMock($this->getNamespace(SesClient::class), 'gmdate');
        $gmdate->expects($this->exactly(5))
               ->withConsecutive(
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd')]
               )
               ->willReturnOnConsecutiveCalls('20200515T181004Z', '20200515', '20200515T181004Z', '20200515', '20200515');

        (new SesClient($http, 'aws-key', 'us-east-1', 'aws-secret'))->sendEmail($email);
    }

    public function testSendEmailWithSesErrorAndNoBody()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SES API request failed: [unable to parse error message]');

        $email = $this->getEmailMock();
        $email->expects($this->once())
              ->method('toString')
              ->willReturn('email');

        $http = $this->getHttpClientMock();
        $http->expects($this->once())
             ->method('request')
             ->with(
                 $this->identicalTo('https://email.us-east-1.amazonaws.com/'),
                 $this->identicalTo([
                     'headers' => [
                         'host' => 'email.us-east-1.amazonaws.com',
                         'x-amz-content-sha256' => '93c8df40dd7aabcd009385e2496d874342612b116f80638899066a8f6a2e72e6',
                         'x-amz-date' => '20200515T181004Z',
                         'authorization' => 'AWS4-HMAC-SHA256 Credential=aws-key/20200515/us-east-1/ses/aws4_request,SignedHeaders=host;x-amz-content-sha256;x-amz-date,Signature=105ad9051f2aaeb3471e626dfd368d2bccf87ea0626ace1ea0fbf459864eb62f',
                     ],
                     'method' => 'POST',
                     'timeout' => 300,
                     'body' => 'Action=SendRawEmail&RawMessage.Data=ZW1haWw%3D',
                 ])
             )
             ->willReturn([
                 'response' => ['code' => 400],
             ]);

        $gmdate = $this->getFunctionMock($this->getNamespace(SesClient::class), 'gmdate');
        $gmdate->expects($this->exactly(5))
               ->withConsecutive(
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd')]
               )
               ->willReturnOnConsecutiveCalls('20200515T181004Z', '20200515', '20200515T181004Z', '20200515', '20200515');

        (new SesClient($http, 'aws-key', 'us-east-1', 'aws-secret'))->sendEmail($email);
    }

    public function testSendEmailWithWrongStatusCode()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SES API request failed with status code 404');

        $email = $this->getEmailMock();
        $email->expects($this->once())
              ->method('toString')
              ->willReturn('email');

        $http = $this->getHttpClientMock();
        $http->expects($this->once())
             ->method('request')
             ->with(
                 $this->identicalTo('https://email.us-east-1.amazonaws.com/'),
                 $this->identicalTo([
                     'headers' => [
                         'host' => 'email.us-east-1.amazonaws.com',
                         'x-amz-content-sha256' => '93c8df40dd7aabcd009385e2496d874342612b116f80638899066a8f6a2e72e6',
                         'x-amz-date' => '20200515T181004Z',
                         'authorization' => 'AWS4-HMAC-SHA256 Credential=aws-key/20200515/us-east-1/ses/aws4_request,SignedHeaders=host;x-amz-content-sha256;x-amz-date,Signature=105ad9051f2aaeb3471e626dfd368d2bccf87ea0626ace1ea0fbf459864eb62f',
                     ],
                     'method' => 'POST',
                     'timeout' => 300,
                     'body' => 'Action=SendRawEmail&RawMessage.Data=ZW1haWw%3D',
                 ])
             )
             ->willReturn([
                 'response' => ['code' => 404],
             ]);

        $gmdate = $this->getFunctionMock($this->getNamespace(SesClient::class), 'gmdate');
        $gmdate->expects($this->exactly(5))
               ->withConsecutive(
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd')]
               )
               ->willReturnOnConsecutiveCalls('20200515T181004Z', '20200515', '20200515T181004Z', '20200515', '20200515');

        (new SesClient($http, 'aws-key', 'us-east-1', 'aws-secret'))->sendEmail($email);
    }
}
