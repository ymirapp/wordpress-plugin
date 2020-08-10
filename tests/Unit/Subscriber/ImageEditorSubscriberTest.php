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

namespace Ymir\Plugin\Tests\Unit\Subscriber;

use Ymir\Plugin\Attachment\GDImageEditor;
use Ymir\Plugin\Attachment\ImagickImageEditor;
use Ymir\Plugin\Subscriber\ImageEditorSubscriber;
use Ymir\Plugin\Tests\Mock\AttachmentFileManagerMockTrait;
use Ymir\Plugin\Tests\Mock\ConsoleClientInterfaceMockTrait;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\Subscriber\ImageEditorSubscriber
 */
class ImageEditorSubscriberTest extends TestCase
{
    use AttachmentFileManagerMockTrait;
    use ConsoleClientInterfaceMockTrait;
    use FunctionMockTrait;

    /**
     * @backupGlobals enabled
     */
    public function testForwardCropImageRequest()
    {
        $_POST['id'] = '4';
        $_POST['context'] = 'foo_context';
        $_POST['cropDetails'] = [
            'x1' => '24',
            'y1' => '42',
            'width' => '14',
            'height' => '21',
            'dst_width' => '140',
            'dst_height' => '210',
        ];

        $consoleClient = $this->getConsoleClientInterfaceMock();
        $consoleClient->expects($this->once())
                      ->method('createCroppedAttachmentImage')
                      ->with($this->identicalTo(4), $this->identicalTo(14), $this->identicalTo(21), $this->identicalTo(24), $this->identicalTo(42), $this->identicalTo('foo-context'), $this->identicalTo(140), $this->identicalTo(210))
                      ->willReturn(5);

        $check_ajax_referer = $this->getFunctionMock($this->getNamespace(ImageEditorSubscriber::class), 'check_ajax_referer');
        $check_ajax_referer->expects($this->once())
                           ->with($this->identicalTo('image_editor-4'), $this->identicalTo('nonce'));

        $current_user_can = $this->getFunctionMock($this->getNamespace(ImageEditorSubscriber::class), 'current_user_can');
        $current_user_can->expects($this->once())
                         ->with($this->identicalTo('edit_post'), $this->identicalTo(4))
                         ->willReturn(true);

        $wp_prepare_attachment_for_js = $this->getFunctionMock($this->getNamespace(ImageEditorSubscriber::class), 'wp_prepare_attachment_for_js');
        $wp_prepare_attachment_for_js->expects($this->once())
                                     ->with($this->identicalTo(5))
                                     ->willReturn([]);

        $wp_send_json_success = $this->getFunctionMock($this->getNamespace(ImageEditorSubscriber::class), 'wp_send_json_success');
        $wp_send_json_success->expects($this->once())
                             ->with($this->identicalTo([]));

        (new ImageEditorSubscriber($consoleClient, $this->getAttachmentFileManagerMock()))->forwardCropImageRequest();
    }

    /**
     * @backupGlobals enabled
     */
    public function testForwardImageEditorRequestCallsEditAttachmentImage()
    {
        $_POST['do'] = 'save';
        $_POST['postid'] = '4';
        $_REQUEST['history'] = '[{"r":90}]';
        $_REQUEST['target'] = 'all';

        $expectedMessage = '{"fw":42,"fh":24,"thumbnail":"attachment_url?w=128&h=128","msg":"Image saved"}';

        $consoleClient = $this->getConsoleClientInterfaceMock();
        $consoleClient->expects($this->once())
                      ->method('editAttachmentImage')
                      ->with($this->identicalTo(4), $this->identicalTo('[{"r":90}]'), $this->identicalTo('all'));

        $check_ajax_referer = $this->getFunctionMock($this->getNamespace(ImageEditorSubscriber::class), 'check_ajax_referer');
        $check_ajax_referer->expects($this->once())
                           ->with($this->identicalTo('image_editor-4'), $this->identicalTo(''));

        $current_user_can = $this->getFunctionMock($this->getNamespace(ImageEditorSubscriber::class), 'current_user_can');
        $current_user_can->expects($this->once())
                         ->with($this->identicalTo('edit_post'), $this->identicalTo(4))
                         ->willReturn(true);

        $esc_js = $this->getFunctionMock($this->getNamespace(ImageEditorSubscriber::class), 'esc_js');
        $esc_js->expects($this->once())
               ->with($this->identicalTo('Image saved'))
               ->willReturnArgument(0);

        $wp_die = $this->getFunctionMock($this->getNamespace(ImageEditorSubscriber::class), 'wp_die');
        $wp_die->expects($this->once())
               ->with($this->identicalTo($expectedMessage));

        $wp_get_attachment_metadata = $this->getFunctionMock($this->getNamespace(ImageEditorSubscriber::class), 'wp_get_attachment_metadata');
        $wp_get_attachment_metadata->expects($this->exactly(2))
                                   ->with($this->identicalTo(4))
                                   ->willReturn(['height' => 24, 'width' => 42]);

        $wp_get_attachment_url = $this->getFunctionMock($this->getNamespace(ImageEditorSubscriber::class), 'wp_get_attachment_url');
        $wp_get_attachment_url->expects($this->once())
                              ->with($this->identicalTo(4))
                              ->willReturn('attachment_url');

        $wp_json_encode = $this->getFunctionMock($this->getNamespace(ImageEditorSubscriber::class), 'wp_json_encode');
        $wp_json_encode->expects($this->once())
                       ->with($this->equalTo(json_decode($expectedMessage)))
                       ->willReturn($expectedMessage);

        $wp_unslash = $this->getFunctionMock($this->getNamespace(ImageEditorSubscriber::class), 'wp_unslash');
        $wp_unslash->expects($this->once())
                   ->with($this->identicalTo('[{"r":90}]'))
                   ->willReturn('[{"r":90}]');

        $__ = $this->getFunctionMock($this->getNamespace(ImageEditorSubscriber::class), '__');
        $__->expects($this->once())
            ->with($this->identicalTo('Image saved'))
            ->willReturnArgument(0);

        (new ImageEditorSubscriber($consoleClient, $this->getAttachmentFileManagerMock()))->forwardImageEditorRequest();
    }

    /**
     * @backupGlobals enabled
     */
    public function testForwardImageEditorRequestCallsResizeAttachmentImage()
    {
        $_POST['do'] = 'scale';
        $_POST['postid'] = '4';
        $_REQUEST['fwidth'] = '14';
        $_REQUEST['fheight'] = '21';

        $expectedMessage = '{"fw":42,"fh":24,"thumbnail":"attachment_url?w=128&h=128","msg":"Image saved"}';

        $consoleClient = $this->getConsoleClientInterfaceMock();
        $consoleClient->expects($this->once())
                      ->method('resizeAttachmentImage')
                      ->with($this->identicalTo(4), $this->identicalTo(14), $this->identicalTo(21));

        $check_ajax_referer = $this->getFunctionMock($this->getNamespace(ImageEditorSubscriber::class), 'check_ajax_referer');
        $check_ajax_referer->expects($this->once())
                           ->with($this->identicalTo('image_editor-4'), $this->identicalTo(''));

        $current_user_can = $this->getFunctionMock($this->getNamespace(ImageEditorSubscriber::class), 'current_user_can');
        $current_user_can->expects($this->once())
                         ->with($this->identicalTo('edit_post'), $this->identicalTo(4))
                         ->willReturn(true);

        $esc_js = $this->getFunctionMock($this->getNamespace(ImageEditorSubscriber::class), 'esc_js');
        $esc_js->expects($this->once())
               ->with($this->identicalTo('Image saved'))
               ->willReturnArgument(0);

        $wp_die = $this->getFunctionMock($this->getNamespace(ImageEditorSubscriber::class), 'wp_die');
        $wp_die->expects($this->once())
               ->with($this->identicalTo(''));

        $wp_get_attachment_metadata = $this->getFunctionMock($this->getNamespace(ImageEditorSubscriber::class), 'wp_get_attachment_metadata');
        $wp_get_attachment_metadata->expects($this->exactly(2))
                                   ->with($this->identicalTo(4))
                                   ->willReturn(['height' => 24, 'width' => 42]);

        $wp_get_attachment_url = $this->getFunctionMock($this->getNamespace(ImageEditorSubscriber::class), 'wp_get_attachment_url');
        $wp_get_attachment_url->expects($this->once())
                              ->with($this->identicalTo(4))
                              ->willReturn('attachment_url');

        $wp_image_editor = $this->getFunctionMock($this->getNamespace(ImageEditorSubscriber::class), 'wp_image_editor');
        $wp_image_editor->expects($this->once())
                        ->with($this->identicalTo(4), $this->equalTo(json_decode($expectedMessage)));

        $wp_json_encode = $this->getFunctionMock($this->getNamespace(ImageEditorSubscriber::class), 'wp_json_encode');
        $wp_json_encode->expects($this->once())
                       ->with($this->equalTo(json_decode($expectedMessage)))
                       ->willReturn($expectedMessage);

        $__ = $this->getFunctionMock($this->getNamespace(ImageEditorSubscriber::class), '__');
        $__->expects($this->once())
           ->with($this->identicalTo('Image saved'))
           ->willReturnArgument(0);

        (new ImageEditorSubscriber($consoleClient, $this->getAttachmentFileManagerMock()))->forwardImageEditorRequest();
    }

    public function testGetSubscribedEvents()
    {
        $callbacks = ImageEditorSubscriber::getSubscribedEvents();

        foreach ($callbacks as $callback) {
            $this->assertTrue(method_exists(ImageEditorSubscriber::class, is_array($callback) ? $callback[0] : $callback));
        }

        $subscribedEvents = [
            'wp_ajax_crop-image' => ['forwardCropImageRequest', 1],
            'wp_ajax_image-editor' => ['forwardImageEditorRequest', 1],
            'wp_image_editors' => 'replaceImageEditors',
            'wp_read_image_metadata' => ['readImageMetadataLocally', 10, 2],
        ];

        $this->assertSame($subscribedEvents, $callbacks);
    }

    public function testReadImageMetadataLocallyWhenInUploadsDirectory()
    {
        $fileManager = $this->getAttachmentFileManagerMock();

        $fileManager->expects($this->once())
                    ->method('isInUploadsDirectory')
                    ->with($this->identicalTo('file'))
                    ->willReturn(true);

        $fileManager->expects($this->once())
                    ->method('copyToTempDirectory')
                    ->with($this->identicalTo('file'))
                    ->willReturn('copy');

        $wp_read_image_metadata = $this->getFunctionMock($this->getNamespace(ImageEditorSubscriber::class), 'wp_read_image_metadata');
        $wp_read_image_metadata->expects($this->once())
                               ->with($this->identicalTo('copy'))
                               ->willReturn('bar');

        $this->assertSame('bar', (new ImageEditorSubscriber($this->getConsoleClientInterfaceMock(), $fileManager))->readImageMetadataLocally('foo', 'file'));
    }

    public function testReadImageMetadataLocallyWhenNotInUploadsDirectory()
    {
        $fileManager = $this->getAttachmentFileManagerMock();

        $fileManager->expects($this->once())
                    ->method('isInUploadsDirectory')
                    ->with($this->identicalTo('file'))
                    ->willReturn(false);

        $this->assertSame('foo', (new ImageEditorSubscriber($this->getConsoleClientInterfaceMock(), $fileManager))->readImageMetadataLocally('foo', 'file'));
    }

    public function testReplaceImageEditors()
    {
        $this->assertSame([
            ImagickImageEditor::class,
            GDImageEditor::class,
        ], (new ImageEditorSubscriber($this->getConsoleClientInterfaceMock(), $this->getAttachmentFileManagerMock()))->replaceImageEditors());
    }
}
