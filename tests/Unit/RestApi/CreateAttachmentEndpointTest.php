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

namespace Ymir\Plugin\Tests\Unit\RestApi;

use PHPUnit\Framework\TestCase;
use Ymir\Plugin\RestApi\CreateAttachmentEndpoint;
use Ymir\Plugin\Tests\Mock\CloudStorageClientInterfaceMockTrait;
use Ymir\Plugin\Tests\Mock\ConsoleClientInterfaceMockTrait;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Mock\WPErrorMockTrait;
use Ymir\Plugin\Tests\Mock\WPRESTRequestMockTrait;

/**
 * @covers \Ymir\Plugin\RestApi\CreateAttachmentEndpoint
 */
class CreateAttachmentEndpointTest extends TestCase
{
    use CloudStorageClientInterfaceMockTrait;
    use ConsoleClientInterfaceMockTrait;
    use FunctionMockTrait;
    use WPErrorMockTrait;
    use WPRESTRequestMockTrait;

    public function testGetArguments()
    {
        $arguments = (new CreateAttachmentEndpoint($this->getCloudStorageClientInterfaceMock(), $this->getConsoleClientInterfaceMock(), 'uploads_dir', 'uploads_url'))->getArguments();

        $this->assertTrue($arguments['path']['required']);
        $this->assertIsCallable($arguments['path']['sanitize_callback']);
    }

    public function testGetMethods()
    {
        $this->assertSame(['POST'], (new CreateAttachmentEndpoint($this->getCloudStorageClientInterfaceMock(), $this->getConsoleClientInterfaceMock(), 'uploads_dir', 'uploads_url'))->getMethods());
    }

    public function testGetPath()
    {
        $this->assertSame('/attachments', CreateAttachmentEndpoint::getPath());
    }

    public function testRespondReturnsAsyncResponseForLargeImage()
    {
        $cloudStorageClient = $this->getCloudStorageClientInterfaceMock();
        $cloudStorageClient->expects($this->once())
                           ->method('getObjectDetails')
                           ->with('uploads/filename.jpg')
                           ->willReturn(['size' => 16, 'type' => 'image/jpeg']);

        $consoleClient = $this->getConsoleClientInterfaceMock();
        $consoleClient->expects($this->once())
                      ->method('createAttachmentMetadata')
                      ->with($this->identicalTo('attachment_id'), $this->identicalTo(true));

        $get_current_user_id = $this->getFunctionMock($this->getNamespace(CreateAttachmentEndpoint::class), 'get_current_user_id');
        $get_current_user_id->expects($this->once())
            ->willReturn('user_id');

        $request = $this->getWPRESTRequestMock();
        $request->expects($this->once())
                ->method('get_param')
                ->with($this->identicalTo('path'))
                ->willReturn('/filename.jpg');

        $sanitize_text_field = $this->getFunctionMock($this->getNamespace(CreateAttachmentEndpoint::class), 'sanitize_text_field');
        $sanitize_text_field->expects($this->once())
                            ->with($this->identicalTo('filename'))
                            ->willReturn('filename');

        $wp_convert_hr_to_bytes = $this->getFunctionMock($this->getNamespace(CreateAttachmentEndpoint::class), 'wp_convert_hr_to_bytes');
        $wp_convert_hr_to_bytes->expects($this->once())
                               ->with($this->identicalTo('15MB'))
                               ->willReturn(15);

        $wp_insert_attachment = $this->getFunctionMock($this->getNamespace(CreateAttachmentEndpoint::class), 'wp_insert_attachment');
        $wp_insert_attachment->expects($this->once())
                             ->with($this->identicalTo(['guid' => 'uploads_url/filename.jpg', 'post_author' => 'user_id', 'post_mime_type' => 'image/jpeg', 'post_title' => 'filename']), $this->identicalTo('filename.jpg'), $this->identicalTo(0), $this->identicalTo(true))
                             ->willReturn('attachment_id');

        $wp_prepare_attachment_for_js = $this->getFunctionMock($this->getNamespace(CreateAttachmentEndpoint::class), 'wp_prepare_attachment_for_js');
        $wp_prepare_attachment_for_js->expects($this->once())
                                     ->with($this->identicalTo('attachment_id'))
                                     ->willReturn([]);

        $wp_update_attachment_metadata = $this->getFunctionMock($this->getNamespace(CreateAttachmentEndpoint::class), 'wp_update_attachment_metadata');
        $wp_update_attachment_metadata->expects($this->once())
                                      ->with($this->identicalTo('attachment_id'), $this->identicalTo(['file' => 'filename.jpg']));

        $this->assertSame([], (new CreateAttachmentEndpoint($cloudStorageClient, $consoleClient, 'uploads_dir', 'uploads_url'))->respond($request));
    }

    public function testRespondReturnsError()
    {
        $cloudStorageClient = $this->getCloudStorageClientInterfaceMock();
        $cloudStorageClient->expects($this->once())
                           ->method('getObjectDetails')
                           ->with('uploads/filename.txt')
                           ->willReturn(['type' => 'text/plain']);

        $error = $this->getWPErrorMock();

        $get_current_user_id = $this->getFunctionMock($this->getNamespace(CreateAttachmentEndpoint::class), 'get_current_user_id');
        $get_current_user_id->expects($this->once())
                            ->willReturn('user_id');

        $request = $this->getWPRESTRequestMock();
        $request->expects($this->once())
                ->method('get_param')
                ->with($this->identicalTo('path'))
                ->willReturn('/filename.txt');

        $sanitize_text_field = $this->getFunctionMock($this->getNamespace(CreateAttachmentEndpoint::class), 'sanitize_text_field');
        $sanitize_text_field->expects($this->once())
                            ->with($this->identicalTo('filename'))
                            ->willReturn('filename');

        $wp_insert_attachment = $this->getFunctionMock($this->getNamespace(CreateAttachmentEndpoint::class), 'wp_insert_attachment');
        $wp_insert_attachment->expects($this->once())
                             ->with($this->identicalTo(['guid' => 'uploads_url/filename.txt', 'post_author' => 'user_id', 'post_mime_type' => 'text/plain', 'post_title' => 'filename']), $this->identicalTo('filename.txt'), $this->identicalTo(0), $this->identicalTo(true))
                             ->willReturn($error);

        $this->assertSame($error, (new CreateAttachmentEndpoint($cloudStorageClient, $this->getConsoleClientInterfaceMock(), 'uploads_dir', 'uploads_url'))->respond($request));
    }

    public function testRespondReturnsResponse()
    {
        $cloudStorageClient = $this->getCloudStorageClientInterfaceMock();
        $cloudStorageClient->expects($this->once())
                           ->method('getObjectDetails')
                           ->with('uploads/filename.txt')
                           ->willReturn(['type' => 'text/plain']);

        $consoleClient = $this->getConsoleClientInterfaceMock();
        $consoleClient->expects($this->once())
                      ->method('createAttachmentMetadata')
                      ->with($this->identicalTo('attachment_id'), $this->identicalTo(false));

        $get_current_user_id = $this->getFunctionMock($this->getNamespace(CreateAttachmentEndpoint::class), 'get_current_user_id');
        $get_current_user_id->expects($this->once())
                            ->willReturn('user_id');

        $request = $this->getWPRESTRequestMock();
        $request->expects($this->once())
                ->method('get_param')
                ->with($this->identicalTo('path'))
                ->willReturn('/filename.txt');

        $sanitize_text_field = $this->getFunctionMock($this->getNamespace(CreateAttachmentEndpoint::class), 'sanitize_text_field');
        $sanitize_text_field->expects($this->once())
                            ->with($this->identicalTo('filename'))
                            ->willReturn('filename');

        $wp_insert_attachment = $this->getFunctionMock($this->getNamespace(CreateAttachmentEndpoint::class), 'wp_insert_attachment');
        $wp_insert_attachment->expects($this->once())
                             ->with($this->identicalTo(['guid' => 'uploads_url/filename.txt', 'post_author' => 'user_id', 'post_mime_type' => 'text/plain', 'post_title' => 'filename']), $this->identicalTo('filename.txt'), $this->identicalTo(0), $this->identicalTo(true))
                             ->willReturn('attachment_id');

        $wp_prepare_attachment_for_js = $this->getFunctionMock($this->getNamespace(CreateAttachmentEndpoint::class), 'wp_prepare_attachment_for_js');
        $wp_prepare_attachment_for_js->expects($this->once())
                                     ->with($this->identicalTo('attachment_id'))
                                     ->willReturn([]);

        $this->assertSame([], (new CreateAttachmentEndpoint($cloudStorageClient, $consoleClient, 'uploads_dir', 'uploads_url'))->respond($request));
    }
}
