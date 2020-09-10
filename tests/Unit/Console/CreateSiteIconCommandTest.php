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

use Ymir\Plugin\Console\CreateSiteIconCommand;
use Ymir\Plugin\Tests\Mock\AttachmentFileManagerMockTrait;
use Ymir\Plugin\Tests\Mock\EventManagerMockTrait;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Mock\WPPostMockTrait;
use Ymir\Plugin\Tests\Mock\WPSiteIconMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\Console\CreateSiteIconCommand
 */
class CreateSiteIconCommandTest extends TestCase
{
    use AttachmentFileManagerMockTrait;
    use EventManagerMockTrait;
    use FunctionMockTrait;
    use WPPostMockTrait;
    use WPSiteIconMockTrait;

    public function testGetName()
    {
        $this->assertSame('ymir create-site-icon', CreateSiteIconCommand::getName());
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
        ], CreateSiteIconCommand::getSynopsis());
    }

    public function testInvoke()
    {
        $arguments = ['4'];
        $options = ['image_height' => '420', 'image_width' => '240', 'height' => '42', 'width' => '24', 'x' => '14', 'y' => '21'];

        $eventManager = $this->getEventManagerMock();
        $siteIcon = $this->getWPSiteIconMock();

        $eventManager->expects($this->once())
                     ->method('filter')
                     ->with($this->identicalTo('wp_create_file_in_uploads'), $this->identicalTo('file/path/cropped.jpg'), $this->identicalTo(4))
                     ->willReturn('filtered/file/path/cropped.jpg');
        $eventManager->expects($this->once())
                     ->method('addCallback')
                     ->with($this->identicalTo('intermediate_image_sizes_advanced'), $this->identicalTo([$siteIcon, 'additional_sizes']));
        $eventManager->expects($this->once())
                     ->method('removeCallback')
                     ->with($this->identicalTo('intermediate_image_sizes_advanced'), $this->identicalTo([$siteIcon, 'additional_sizes']));

        $siteIcon->expects($this->once())
                 ->method('create_attachment_object')
                 ->with($this->identicalTo('filtered/file/path/cropped.jpg'), $this->identicalTo(4))
                 ->willReturn(['ID' => 4]);
        $siteIcon->expects($this->once())
                 ->method('insert_attachment')
                 ->with($this->identicalTo([]), $this->identicalTo('filtered/file/path/cropped.jpg'))
                 ->willReturn(5);

        $post = $this->getWPPostMock();
        $post->ID = 4;
        $post->post_type = 'attachment';
        $post->post_mime_type = 'image/jpeg';

        $get_post = $this->getFunctionMock($this->getNamespace(CreateSiteIconCommand::class), 'get_post');
        $get_post->expects($this->once())
                 ->with($this->identicalTo('4'))
                 ->willReturn($post);

        $wp_crop_image = $this->getFunctionMock($this->getNamespace(CreateSiteIconCommand::class), 'wp_crop_image');
        $wp_crop_image->expects($this->once())
                      ->with($this->identicalTo(4), $this->identicalTo('14'), $this->identicalTo('21'), $this->identicalTo('24'), $this->identicalTo('42'), $this->identicalTo('240'), $this->identicalTo('420'))
                      ->willReturn('file/path/cropped.jpg');

        (new CreateSiteIconCommand($this->getAttachmentFileManagerMock(), $eventManager, $siteIcon))($arguments, $options);
    }
}
