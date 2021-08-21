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

namespace Ymir\Plugin\Subscriber;

use Ymir\Plugin\Attachment\AttachmentFileManager;
use Ymir\Plugin\Attachment\GDImageEditor;
use Ymir\Plugin\Attachment\ImagickImageEditor;
use Ymir\Plugin\Console\ConsoleClientInterface;
use Ymir\Plugin\EventManagement\AbstractEventManagerAwareSubscriber;

/**
 * Subscriber for WordPress image editors.
 */
class ImageEditorSubscriber extends AbstractEventManagerAwareSubscriber
{
    /**
     * The console serverless function client.
     *
     * @var ConsoleClientInterface
     */
    private $consoleClient;

    /**
     * The attachment file manager.
     *
     * @var AttachmentFileManager
     */
    private $fileManager;

    /**
     * Constructor.
     */
    public function __construct(ConsoleClientInterface $consoleClient, AttachmentFileManager $fileManager)
    {
        $this->consoleClient = $consoleClient;
        $this->fileManager = $fileManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'wp_ajax_crop-image' => ['forwardCropImageRequest', 1],
            'wp_ajax_image-editor' => ['forwardImageEditorRequest', 1],
            'wp_image_editors' => 'replaceImageEditors',
            'wp_read_image_metadata' => ['readImageMetadataLocally', 10, 2],
        ];
    }

    /**
     * Forward AJAX requests that need to run wp_crop_image to console serverless function.
     *
     * @see wp_ajax_crop_image()
     */
    public function forwardCropImageRequest()
    {
        if (!isset($_POST['cropDetails'], $_POST['id']) || !is_array($_POST['cropDetails'])) {
            return;
        }

        $attachmentId = (int) $_POST['id'];

        if (!$this->isAttachmentIdValid($attachmentId, 'nonce')) {
            wp_send_json_error();
        }

        $context = !empty($_POST['context']) ? str_replace('_', '-', $_POST['context']) : '';
        $details = array_map('absint', $_POST['cropDetails']);

        if (!isset($details['x1'], $details['y1'], $details['width'], $details['height'])) {
            wp_send_json_error();
        }

        try {
            $croppedAttachmentImageId = $this->consoleClient->createCroppedAttachmentImage($attachmentId, $details['width'], $details['height'], $details['x1'], $details['y1'], $context, $details['dst_width'] ?? 0, $details['dst_height'] ?? 0);
        } catch (\Exception $exception) {
            wp_send_json_error(['message' => $exception->getMessage()]);
        }

        if ('site-icon' === $context) {
            require_once ABSPATH.'wp-admin/includes/class-wp-site-icon.php';
            $siteIcon = new \WP_Site_Icon();

            $this->eventManager->addCallback('image_size_names_choose', [$siteIcon, 'additional_sizes']);
        }

        wp_send_json_success(wp_prepare_attachment_for_js($croppedAttachmentImageId));
    }

    /**
     * Forward image editor requests that need to run wp_save_image to console serverless function.
     *
     * @see wp_ajax_image_editor()
     */
    public function forwardImageEditorRequest()
    {
        if (!isset($_POST['do'], $_POST['postid']) || !in_array($_POST['do'], ['save', 'scale'])) {
            return;
        }

        $attachmentId = (int) $_POST['postid'];
        $operation = $_POST['do'];

        if (!$this->isAttachmentIdValid($attachmentId)) {
            wp_die('-1');
        }

        $message = wp_json_encode($this->runImageEditorOperationCommand($attachmentId, $operation));

        if ('save' !== $operation) {
            wp_image_editor($attachmentId, json_decode($message));
            $message = '';
        }

        wp_die($message);
    }

    /**
     * Reading exif data doesn't work with streams so we read the metadata from a local file.
     */
    public function readImageMetadataLocally($metadata, string $file)
    {
        if (!$this->fileManager->isInUploadsDirectory($file)) {
            return $metadata;
        }

        $copy = $this->fileManager->copyToTempDirectory($file);
        $metadata = wp_read_image_metadata($copy);

        return $metadata;
    }

    /**
     * Replace both image editors with our modified image editors using the attachment file manager.
     */
    public function replaceImageEditors(): array
    {
        return [
            ImagickImageEditor::class,
            GDImageEditor::class,
        ];
    }

    /**
     * Get the thumbnail for the given attachment.
     */
    private function getAttachmentThumbnail(int $attachmentId): string
    {
        if (!empty($_REQUEST['context']) && 'edit-attachment' == $_REQUEST['context']) {
            return wp_get_attachment_image_src($attachmentId, [900, 600], true)[0] ?? '';
        }

        $metadata = wp_get_attachment_metadata($attachmentId);

        if (isset($metadata['sizes']['thumbnail']['file'])) {
            return $this->fileManager->getUploadsFilePath($metadata['sizes']['thumbnail']['file']);
        }

        $attachmentUrl = wp_get_attachment_url($attachmentId);

        if (!is_string($attachmentUrl)) {
            return '';
        }

        return $attachmentUrl.'?w=128&h=128';
    }

    /**
     * Check if the attachment ID from the HTTP request is valid.
     */
    private function isAttachmentIdValid(int $attachmentId, string $nonceQueryArg = ''): bool
    {
        check_ajax_referer("image_editor-{$attachmentId}", $nonceQueryArg);

        return current_user_can('edit_post', $attachmentId);
    }

    /**
     * Run the serverless console command to perform the given image editor operation on the given attachment.
     */
    private function runImageEditorOperationCommand(int $attachmentId, string $operation): \stdClass
    {
        $message = new \stdClass();

        try {
            $target = !empty($_REQUEST['target']) ? preg_replace('/[^a-z0-9_-]+/i', '', $_REQUEST['target']) : '';

            if ('scale' === $operation && isset($_REQUEST['fwidth'], $_REQUEST['fheight'])) {
                $this->consoleClient->resizeAttachmentImage($attachmentId, (int) $_REQUEST['fwidth'], (int) $_REQUEST['fheight']);
            } elseif ('save' === $operation && isset($_REQUEST['history'], $_REQUEST['target'])) {
                $this->consoleClient->editAttachmentImage($attachmentId, wp_unslash($_REQUEST['history']), $target);
            }

            $metadata = wp_get_attachment_metadata($attachmentId);

            if ('thumbnail' !== $target && isset($metadata['width'], $metadata['height'])) {
                $message->fw = $metadata['width'];
                $message->fh = $metadata['height'];
            }

            if ('nothumb' !== $target) {
                $message->thumbnail = $this->getAttachmentThumbnail($attachmentId);
            }

            $message->msg = esc_js(__('Image saved'));
        } catch (\Exception $exception) {
            $message->error = $exception->getMessage();
        }

        return $message;
    }
}
