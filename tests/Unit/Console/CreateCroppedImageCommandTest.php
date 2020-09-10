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

use Ymir\Plugin\Console\CreateCroppedImageCommand;
use Ymir\Plugin\Tests\Mock\AttachmentFileManagerMockTrait;
use Ymir\Plugin\Tests\Mock\EventManagerMockTrait;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Mock\WPPostMockTrait;
use Ymir\Plugin\Tests\Mock\WPSiteIconMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\Console\CreateCroppedImageCommand
 */
class CreateCroppedImageCommandTest extends TestCase
{
    use AttachmentFileManagerMockTrait;
    use EventManagerMockTrait;
    use FunctionMockTrait;
    use WPPostMockTrait;
    use WPSiteIconMockTrait;

    public function testGetName()
    {
        $this->assertSame('ymir create-cropped-image', CreateCroppedImageCommand::getName());
    }

    public function testGetSynopsis()
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
            [
                'type' => 'assoc',
                'name' => 'x',
            ],
            [
                'type' => 'assoc',
                'name' => 'y',
            ],
            [
                'type' => 'assoc',
                'name' => 'image_height',
                'optional' => true,
            ],
            [
                'type' => 'assoc',
                'name' => 'image_width',
                'optional' => true,
            ],
            [
                'type' => 'assoc',
                'name' => 'context',
            ],
        ], CreateCroppedImageCommand::getSynopsis());
    }

    public function testInvoke()
    {
        $arguments = ['4'];
        $options = ['context' => 'foo', 'image_height' => '420', 'image_width' => '240', 'height' => '42', 'width' => '24', 'x' => '14', 'y' => '21'];

        $eventManager = $this->getEventManagerMock();
        $eventManager->expects($this->once())
                     ->method('execute')
                     ->with($this->identicalTo('wp_ajax_crop_image_pre_save'), $this->identicalTo('foo'), $this->identicalTo(4), $this->identicalTo('file/path/cropped.jpg'));
        $eventManager->expects($this->once())
                     ->method('execute')
                     ->with($this->identicalTo('wp_ajax_crop_image_pre_save'), $this->identicalTo('foo'), $this->identicalTo(4), $this->identicalTo('file/path/cropped.jpg'));
        $eventManager->expects($this->exactly(3))
                     ->method('filter')
                     ->withConsecutive(
                         [$this->identicalTo('wp_create_file_in_uploads'), $this->identicalTo('file/path/cropped.jpg'), $this->identicalTo(4)],
                         [$this->identicalTo('wp_ajax_cropped_attachment_metadata'), $this->identicalTo(['meta' => 'data'])],
                         [$this->identicalTo('wp_ajax_cropped_attachment_id'), $this->identicalTo(5), $this->identicalTo('foo')]
                     )
                     ->willReturnOnConsecutiveCalls('filtered/file/path/cropped.jpg', ['filtered_meta' => 'filtered_data'], 5);

        $post = $this->getWPPostMock();
        $post->ID = 4;
        $post->post_type = 'attachment';
        $post->post_mime_type = 'image/jpeg';

        $get_post = $this->getFunctionMock($this->getNamespace(CreateCroppedImageCommand::class), 'get_post');
        $get_post->expects($this->once())
                 ->with($this->identicalTo('4'))
                 ->willReturn($post);

        $wp_basename = $this->getFunctionMock($this->getNamespace(CreateCroppedImageCommand::class), 'wp_basename');
        $wp_basename->expects($this->exactly(3))
                    ->withConsecutive(
                        [$this->identicalTo('file/path/original.jpg')],
                        [$this->identicalTo('filtered/file/path/cropped.jpg')],
                        [$this->identicalTo('filtered/file/path/cropped.jpg')]
                    )
                    ->willReturnOnConsecutiveCalls('original.jpg', 'cropped.jpg', 'cropped.jpg');

        $wp_crop_image = $this->getFunctionMock($this->getNamespace(CreateCroppedImageCommand::class), 'wp_crop_image');
        $wp_crop_image->expects($this->once())
                      ->with($this->identicalTo(4), $this->identicalTo('14'), $this->identicalTo('21'), $this->identicalTo('24'), $this->identicalTo('42'), $this->identicalTo('240'), $this->identicalTo('420'))
                      ->willReturn('file/path/cropped.jpg');

        $wp_generate_attachment_metadata = $this->getFunctionMock($this->getNamespace(CreateCroppedImageCommand::class), 'wp_generate_attachment_metadata');
        $wp_generate_attachment_metadata->expects($this->once())
                                        ->with($this->identicalTo(5), $this->identicalTo('filtered/file/path/cropped.jpg'))
                                        ->willReturn(['meta' => 'data']);

        $wp_get_attachment_url = $this->getFunctionMock($this->getNamespace(CreateCroppedImageCommand::class), 'wp_get_attachment_url');
        $wp_get_attachment_url->expects($this->once())
                              ->with($this->identicalTo(4))
                              ->willReturn('file/path/original.jpg');

        $wp_insert_attachment = $this->getFunctionMock($this->getNamespace(CreateCroppedImageCommand::class), 'wp_insert_attachment');
        $wp_insert_attachment->expects($this->once())
                             ->with($this->identicalTo(['post_title' => 'cropped.jpg', 'post_content' => 'file/path/cropped.jpg', 'post_mime_type' => 'image/jpeg', 'guid' => 'file/path/cropped.jpg', 'context' => 'foo']), $this->identicalTo('filtered/file/path/cropped.jpg'))
                             ->willReturn(5);

        $wp_update_attachment_metadata = $this->getFunctionMock($this->getNamespace(CreateCroppedImageCommand::class), 'wp_update_attachment_metadata');
        $wp_update_attachment_metadata->expects($this->once())
                                      ->with($this->identicalTo(5), $this->identicalTo(['filtered_meta' => 'filtered_data']));

        (new CreateCroppedImageCommand($this->getAttachmentFileManagerMock(), $eventManager))($arguments, $options);
    }
}
