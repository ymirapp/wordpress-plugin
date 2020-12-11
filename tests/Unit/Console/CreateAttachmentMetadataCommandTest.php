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

namespace Ymir\Plugin\Tests\Unit\Console;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use Ymir\Plugin\Console\CreateAttachmentMetadataCommand;
use Ymir\Plugin\Tests\Mock\AttachmentFileManagerMockTrait;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Mock\WPPostMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\Console\CreateAttachmentMetadataCommand
 */
class CreateAttachmentMetadataCommandTest extends TestCase
{
    use AttachmentFileManagerMockTrait;
    use FunctionMockTrait;
    use WPPostMockTrait;

    public function testGetName()
    {
        $this->assertSame('ymir create-attachment-metadata', CreateAttachmentMetadataCommand::getName());
    }

    public function testGetSynopsis()
    {
        Assert::assertArraySubset([
            [
                'type' => 'positional',
                'name' => 'attachmentId',
            ],
        ], CreateAttachmentMetadataCommand::getSynopsis());
    }

    public function testInvoke()
    {
        $arguments = ['4'];

        $fileManager = $this->getAttachmentFileManagerMock();
        $fileManager->expects($this->once())
                    ->method('isInUploadsDirectory')
                    ->with($this->identicalTo('attached/file/path.jpg'))
                    ->willReturn(false);

        $post = $this->getWPPostMock();
        $post->ID = 4;
        $post->post_type = 'attachment';
        $post->post_mime_type = 'image/jpeg';

        $file_exists = $this->getFunctionMock($this->getNamespace(CreateAttachmentMetadataCommand::class), 'file_exists');
        $file_exists->expects($this->once())
                    ->with($this->identicalTo('attached/file/path.jpg'))
                    ->willReturn(true);

        $get_attached_file = $this->getFunctionMock($this->getNamespace(CreateAttachmentMetadataCommand::class), 'get_attached_file');
        $get_attached_file->expects($this->once())
                          ->with($this->identicalTo(4))
                          ->willReturn('attached/file/path.jpg');

        $get_post = $this->getFunctionMock($this->getNamespace(CreateAttachmentMetadataCommand::class), 'get_post');
        $get_post->expects($this->once())
                 ->with($this->identicalTo('4'))
                 ->willReturn($post);

        $is_readable = $this->getFunctionMock($this->getNamespace(CreateAttachmentMetadataCommand::class), 'is_readable');
        $is_readable->expects($this->once())
                    ->with($this->identicalTo('attached/file/path.jpg'))
                    ->willReturn(true);

        $wp_generate_attachment_metadata = $this->getFunctionMock($this->getNamespace(CreateAttachmentMetadataCommand::class), 'wp_generate_attachment_metadata');
        $wp_generate_attachment_metadata->expects($this->once())
                                        ->with($this->identicalTo(4), $this->identicalTo('attached/file/path.jpg'))
                                        ->willReturn(['meta' => 'data']);

        $wp_update_attachment_metadata = $this->getFunctionMock($this->getNamespace(CreateAttachmentMetadataCommand::class), 'wp_update_attachment_metadata');
        $wp_update_attachment_metadata->expects($this->once())
                                      ->with($this->identicalTo(4), $this->identicalTo(['meta' => 'data']));

        (new CreateAttachmentMetadataCommand($fileManager))($arguments, []);
    }
}
