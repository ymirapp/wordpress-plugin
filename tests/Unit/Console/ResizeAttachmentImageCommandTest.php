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

use PHPUnit\Framework\TestCase;
use Ymir\Plugin\Console\ResizeAttachmentImageCommand;
use Ymir\Plugin\Tests\Mock\AttachmentFileManagerMockTrait;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Mock\WPImageEditorMockTrait;
use Ymir\Plugin\Tests\Mock\WPPostMockTrait;

/**
 * @covers \Ymir\Plugin\Console\ResizeAttachmentImageCommand
 */
class ResizeAttachmentImageCommandTest extends TestCase
{
    use AttachmentFileManagerMockTrait;
    use FunctionMockTrait;
    use WPImageEditorMockTrait;
    use WPPostMockTrait;

    public function testGetArguments()
    {
        $this->assertArraySubset([
            [
                'type' => 'positional',
                'name' => 'attachmentId',
            ],
            [
                'type' => 'assoc',
                'name' => 'height',
            ],
            [
                'type' => 'assoc',
                'name' => 'width',
            ],
        ], ResizeAttachmentImageCommand::getArguments());
    }

    public function testGetName()
    {
        $this->assertSame('ymir resize-attachment-image', ResizeAttachmentImageCommand::getName());
    }

    public function testInvoke()
    {
        $arguments = ['4'];
        $options = ['height' => '42', 'width' => '24'];

        $fileManager = $this->getAttachmentFileManagerMock();
        $fileManager->expects($this->once())
                    ->method('getRelativePath')
                    ->with($this->matchesRegularExpression('/attached\/file\/path-e[0-9]*\.jpg/'))
                    ->willReturn('relative/attached/file/path.jpg');
        $fileManager->expects($this->once())
                    ->method('isInUploadsDirectory')
                    ->with($this->identicalTo('attached/file/path.jpg'))
                    ->willReturn(false);

        $imageEditor = $this->getWPImageEditorMock();
        $imageEditor->expects($this->once())
                    ->method('get_size')
                    ->willReturn(['height' => 42, 'width' => 24]);
        $imageEditor->expects($this->once())
                    ->method('resize')
                    ->with($this->identicalTo(24), $this->identicalTo(42))
                    ->willReturn(true);

        $post = $this->getWPPostMock();
        $post->ID = 4;
        $post->post_type = 'attachment';
        $post->post_mime_type = 'image/jpeg';

        $file_exists = $this->getFunctionMock($this->getNamespace(ResizeAttachmentImageCommand::class), 'file_exists');
        $file_exists->expects($this->exactly(2))
                    ->withConsecutive(
                        [$this->identicalTo('attached/file/path.jpg')],
                        [$this->matchesRegularExpression('/attached\/file\/path-e[0-9]*\.jpg/')]
                    )
                    ->willReturnOnConsecutiveCalls(true, false);

        $get_attached_file = $this->getFunctionMock($this->getNamespace(ResizeAttachmentImageCommand::class), 'get_attached_file');
        $get_attached_file->expects($this->once())
                          ->with($this->identicalTo(4))
                          ->willReturn('attached/file/path.jpg');

        $get_post = $this->getFunctionMock($this->getNamespace(ResizeAttachmentImageCommand::class), 'get_post');
        $get_post->expects($this->once())
                 ->with($this->identicalTo('4'))
                 ->willReturn($post);

        $is_readable = $this->getFunctionMock($this->getNamespace(ResizeAttachmentImageCommand::class), 'is_readable');
        $is_readable->expects($this->once())
                    ->with($this->identicalTo('attached/file/path.jpg'))
                    ->willReturn(true);

        $update_attached_file = $this->getFunctionMock($this->getNamespace(ResizeAttachmentImageCommand::class), 'update_attached_file');
        $update_attached_file->expects($this->once())
                             ->with($this->identicalTo(4), $this->identicalTo('relative/attached/file/path.jpg'));

        $update_post_meta = $this->getFunctionMock($this->getNamespace(ResizeAttachmentImageCommand::class), 'update_post_meta');
        $update_post_meta->expects($this->once())
                         ->with($this->identicalTo(4), $this->identicalTo('_wp_attachment_backup_sizes'), $this->identicalTo([]));

        $wp_get_attachment_metadata = $this->getFunctionMock($this->getNamespace(ResizeAttachmentImageCommand::class), 'wp_get_attachment_metadata');
        $wp_get_attachment_metadata->expects($this->once())
                                   ->with($this->identicalTo(4))
                                   ->willReturn(['sizes' => []]);

        $wp_get_image_editor = $this->getFunctionMock($this->getNamespace(ResizeAttachmentImageCommand::class), 'wp_get_image_editor');
        $wp_get_image_editor->expects($this->once())
                            ->with($this->identicalTo('attached/file/path.jpg'))
                            ->willReturn($imageEditor);

        $wp_save_image_file = $this->getFunctionMock($this->getNamespace(ResizeAttachmentImageCommand::class), 'wp_save_image_file');
        $wp_save_image_file->expects($this->once())
                           ->with($this->matchesRegularExpression('/attached\/file\/path-e[0-9]*\.jpg/'), $this->identicalTo($imageEditor), $this->identicalTo('image/jpeg'), $this->identicalTo(4))
                           ->willReturn(true);

        $wp_update_attachment_metadata = $this->getFunctionMock($this->getNamespace(ResizeAttachmentImageCommand::class), 'wp_update_attachment_metadata');
        $wp_update_attachment_metadata->expects($this->once())
                                      ->with($this->identicalTo(4), $this->identicalTo(['sizes' => [], 'file' => 'relative/attached/file/path.jpg', 'width' => 24, 'height' => 42]));

        (new ResizeAttachmentImageCommand($fileManager))($arguments, $options);
    }
}
