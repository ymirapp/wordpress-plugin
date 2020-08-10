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

use Ymir\Plugin\Console\EditAttachmentImageCommand;
use Ymir\Plugin\Tests\Mock\AttachmentFileManagerMockTrait;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Mock\WPImageEditorMockTrait;
use Ymir\Plugin\Tests\Mock\WPPostMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\Console\EditAttachmentImageCommand
 */
class EditAttachmentImageCommandTest extends TestCase
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
                'type' => 'positional',
                'name' => 'changes',
            ],
            [
                'type' => 'assoc',
                'name' => 'apply',
                'default' => 'all',
                'options' => ['all', 'full', 'nothumb', 'thumbnail'],
            ],
        ], EditAttachmentImageCommand::getArguments());
    }

    public function testGetName()
    {
        $this->assertSame('ymir edit-attachment-image', EditAttachmentImageCommand::getName());
    }

    public function testInvokeWithApplyFull()
    {
        $arguments = ['4', '[{"r":90}]'];
        $options = ['apply' => 'FULL'];

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
                    ->method('multi_resize')
                    ->with($this->identicalTo([]))
                    ->willReturn([]);

        $post = $this->getWPPostMock();
        $post->ID = 4;
        $post->post_type = 'attachment';
        $post->post_mime_type = 'image/jpeg';

        $file_exists = $this->getFunctionMock($this->getNamespace(EditAttachmentImageCommand::class), 'file_exists');
        $file_exists->expects($this->exactly(2))
                    ->withConsecutive(
                        [$this->identicalTo('attached/file/path.jpg')],
                        [$this->matchesRegularExpression('/attached\/file\/path-e[0-9]*\.jpg/')]
                    )
                    ->willReturnOnConsecutiveCalls(true, false);

        $get_attached_file = $this->getFunctionMock($this->getNamespace(EditAttachmentImageCommand::class), 'get_attached_file');
        $get_attached_file->expects($this->once())
                          ->with($this->identicalTo(4))
                          ->willReturn('attached/file/path.jpg');

        $get_post = $this->getFunctionMock($this->getNamespace(EditAttachmentImageCommand::class), 'get_post');
        $get_post->expects($this->once())
                 ->with($this->identicalTo('4'))
                 ->willReturn($post);

        $image_edit_apply_changes = $this->getFunctionMock($this->getNamespace(EditAttachmentImageCommand::class), 'image_edit_apply_changes');
        $image_edit_apply_changes->expects($this->once())
                                 ->with($this->identicalTo($imageEditor), $this->equalTo(json_decode('[{"r":90}]')))
                                 ->willReturn($imageEditor);

        $is_readable = $this->getFunctionMock($this->getNamespace(EditAttachmentImageCommand::class), 'is_readable');
        $is_readable->expects($this->once())
                    ->with($this->identicalTo('attached/file/path.jpg'))
                    ->willReturn(true);

        $update_attached_file = $this->getFunctionMock($this->getNamespace(EditAttachmentImageCommand::class), 'update_attached_file');
        $update_attached_file->expects($this->once())
                             ->with($this->identicalTo(4), $this->identicalTo('relative/attached/file/path.jpg'));

        $update_post_meta = $this->getFunctionMock($this->getNamespace(EditAttachmentImageCommand::class), 'update_post_meta');
        $update_post_meta->expects($this->once())
                         ->with($this->identicalTo(4), $this->identicalTo('_wp_attachment_backup_sizes'), $this->identicalTo([]));

        $wp_get_attachment_metadata = $this->getFunctionMock($this->getNamespace(EditAttachmentImageCommand::class), 'wp_get_attachment_metadata');
        $wp_get_attachment_metadata->expects($this->once())
                                   ->with($this->identicalTo(4))
                                   ->willReturn(['sizes' => []]);

        $wp_get_image_editor = $this->getFunctionMock($this->getNamespace(EditAttachmentImageCommand::class), 'wp_get_image_editor');
        $wp_get_image_editor->expects($this->once())
                            ->with($this->identicalTo('attached/file/path.jpg'))
                            ->willReturn($imageEditor);

        $wp_save_image_file = $this->getFunctionMock($this->getNamespace(EditAttachmentImageCommand::class), 'wp_save_image_file');
        $wp_save_image_file->expects($this->once())
                           ->with($this->matchesRegularExpression('/attached\/file\/path-e[0-9]*\.jpg/'), $this->identicalTo($imageEditor), $this->identicalTo('image/jpeg'), $this->identicalTo(4))
                           ->willReturn(true);

        $wp_update_attachment_metadata = $this->getFunctionMock($this->getNamespace(EditAttachmentImageCommand::class), 'wp_update_attachment_metadata');
        $wp_update_attachment_metadata->expects($this->once())
                                      ->with($this->identicalTo(4), $this->identicalTo(['sizes' => [], 'file' => 'relative/attached/file/path.jpg', 'width' => 24, 'height' => 42]));

        (new EditAttachmentImageCommand($fileManager))($arguments, $options);
    }
}
