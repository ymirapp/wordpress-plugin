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

use Ymir\Plugin\RestApi\GetFileDetailsEndpoint;
use Ymir\Plugin\Tests\Mock\CloudStorageClientInterfaceMockTrait;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Mock\WPRESTRequestMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\RestApi\GetFileDetailsEndpoint
 */
class GetFileDetailsEndpointTest extends TestCase
{
    use CloudStorageClientInterfaceMockTrait;
    use FunctionMockTrait;
    use WPRESTRequestMockTrait;

    public function testGetArguments()
    {
        $arguments = (new GetFileDetailsEndpoint($this->getCloudStorageClientInterfaceMock(), 'uploads_path', 'uploads_subdir'))->getArguments();

        $this->assertTrue($arguments['filename']['required']);
        $this->assertIsCallable($arguments['filename']['sanitize_callback']);
    }

    public function testGetMethods()
    {
        $this->assertSame(['GET'], (new GetFileDetailsEndpoint($this->getCloudStorageClientInterfaceMock(), 'uploads_path', 'uploads_subdir'))->getMethods());
    }

    public function testGetPath()
    {
        $this->assertSame('/file-details', GetFileDetailsEndpoint::getPath());
    }

    public function testRespondWithEncodedCharacters()
    {
        $cloudStorageClient = $this->getCloudStorageClientInterfaceMock();
        $cloudStorageClient->expects($this->once())
                           ->method('createPutObjectRequest')
                           ->with($this->identicalTo('uploads/2020/08/Revenu+Qu%C3%A9bec+-+Inscription+d%27une+entreprise+en+d%C3%A9marrage.pdf'))
                           ->willReturn('cloudstorage_put_request_url');

        $request = $this->getWPRESTRequestMock();
        $request->expects($this->once())
                ->method('get_param')
                ->with($this->identicalTo('filename'))
                ->willReturn('Revenu Québec - Inscription d&#39;une entreprise en démarrage.pdf');

        $sanitize_file_name = $this->getFunctionMock($this->getNamespace(GetFileDetailsEndpoint::class), 'sanitize_file_name');
        $sanitize_file_name->expects($this->once())
                           ->with($this->identicalTo('Revenu Québec - Inscription d\'une entreprise en démarrage.pdf'))
                           ->willReturn('Revenu Québec - Inscription d\'une entreprise en démarrage.pdf');

        $wp_basename = $this->getFunctionMock($this->getNamespace(GetFileDetailsEndpoint::class), 'wp_basename');
        $wp_basename->expects($this->once())
                    ->with($this->identicalTo('Revenu Québec - Inscription d\'une entreprise en démarrage.pdf'))
                    ->willReturn('Revenu Québec - Inscription d\'une entreprise en démarrage.pdf');

        $wp_unique_filename = $this->getFunctionMock($this->getNamespace(GetFileDetailsEndpoint::class), 'wp_unique_filename');
        $wp_unique_filename->expects($this->once())
                           ->with($this->identicalTo('cloudstorage:///uploads/2020/08'), $this->identicalTo('Revenu+Qu%C3%A9bec+-+Inscription+d%27une+entreprise+en+d%C3%A9marrage.pdf'))
                           ->willReturn('Revenu+Qu%C3%A9bec+-+Inscription+d%27une+entreprise+en+d%C3%A9marrage.pdf');

        $this->assertSame([
            'filename' => 'Revenu+Qu%C3%A9bec+-+Inscription+d%27une+entreprise+en+d%C3%A9marrage.pdf',
            'path' => '2020/08/Revenu+Qu%C3%A9bec+-+Inscription+d%27une+entreprise+en+d%C3%A9marrage.pdf',
            'upload_url' => 'cloudstorage_put_request_url',
        ], (new GetFileDetailsEndpoint($cloudStorageClient, 'cloudstorage:///uploads/2020/08', '/2020/08'))->respond($request));
    }

    public function testRespondWithMultisiteUploadsPath()
    {
        $cloudStorageClient = $this->getCloudStorageClientInterfaceMock();
        $cloudStorageClient->expects($this->once())
                           ->method('createPutObjectRequest')
                           ->with($this->identicalTo('uploads/sites/2/2020/08/test.txt'))
                           ->willReturn('cloudstorage_put_request_url');

        $request = $this->getWPRESTRequestMock();
        $request->expects($this->once())
                ->method('get_param')
                ->with($this->identicalTo('filename'))
                ->willReturn('test.txt');

        $sanitize_file_name = $this->getFunctionMock($this->getNamespace(GetFileDetailsEndpoint::class), 'sanitize_file_name');
        $sanitize_file_name->expects($this->once())
                           ->with($this->identicalTo('test.txt'))
                           ->willReturn('test.txt');

        $wp_basename = $this->getFunctionMock($this->getNamespace(GetFileDetailsEndpoint::class), 'wp_basename');
        $wp_basename->expects($this->once())
                    ->with($this->identicalTo('test.txt'))
                    ->willReturn('test.txt');

        $wp_unique_filename = $this->getFunctionMock($this->getNamespace(GetFileDetailsEndpoint::class), 'wp_unique_filename');
        $wp_unique_filename->expects($this->once())
                           ->with($this->identicalTo('cloudstorage:///uploads/sites/2/2020/08'), $this->identicalTo('test.txt'))
                           ->willReturn('test.txt');

        $this->assertSame([
            'filename' => 'test.txt',
            'path' => '2020/08/test.txt',
            'upload_url' => 'cloudstorage_put_request_url',
        ], (new GetFileDetailsEndpoint($cloudStorageClient, 'cloudstorage:///uploads/sites/2/2020/08', '/2020/08'))->respond($request));
    }

    public function testValidateRequest()
    {
        $current_user_can = $this->getFunctionMock($this->getNamespace(GetFileDetailsEndpoint::class), 'current_user_can');
        $current_user_can->expects($this->once())
                         ->with($this->identicalTo('upload_files'))
                         ->willReturn(true);

        $this->assertTrue((new GetFileDetailsEndpoint($this->getCloudStorageClientInterfaceMock(), 'uploads_path', 'uploads_subdir'))->validateRequest($this->getWPRESTRequestMock()));
    }
}
