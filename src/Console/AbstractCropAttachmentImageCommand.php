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

namespace Ymir\Plugin\Console;

use Ymir\Plugin\Attachment\AttachmentFileManager;
use Ymir\Plugin\EventManagement\EventManager;

/**
 * Base command for cropping an attachment image.
 */
abstract class AbstractCropAttachmentImageCommand extends AbstractAttachmentCommand
{
    /**
     * The plugin event manager.
     *
     * @var EventManager
     */
    protected $eventManager;

    /**
     * Constructor.
     */
    public function __construct(AttachmentFileManager $fileManager, EventManager $eventManager)
    {
        parent::__construct($fileManager);

        $this->eventManager = $eventManager;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(array $arguments, array $options)
    {
        $attachment = $this->getAttachment($arguments[0]);
        $context = $options['context'] ?? 'site-icon';
        $croppedImage = wp_crop_image($attachment->ID, $options['x'], $options['y'], $options['width'], $options['height'], $options['image_width'] ?? null, $options['image_height'] ?? null);

        if ($croppedImage instanceof \WP_Error) {
            $this->error($croppedImage->get_error_message());
        } elseif (empty($croppedImage) || !is_string($croppedImage)) {
            $this->error('Unable to crop attachment image');
        }

        $this->success(sprintf('%s with ID %s', $this->getSuccessMessage(), $this->createCroppedImageAttachment($attachment, $context, $croppedImage)));
    }

    /**
     * {@inheritdoc}
     */
    public static function getSynopsis(): array
    {
        return [
            [
                'type' => 'positional',
                'name' => 'attachmentId',
                'description' => 'The ID of the attachment',
            ],
            [
                'type' => 'assoc',
                'name' => 'height',
                'description' => 'The height of the crop',
            ],
            [
                'type' => 'assoc',
                'name' => 'width',
                'description' => 'The width of the crop',
            ],
            [
                'type' => 'assoc',
                'name' => 'x',
                'description' => 'The X coordinate of the cropped region\'s top left corner',
            ],
            [
                'type' => 'assoc',
                'name' => 'y',
                'description' => 'The Y coordinate of the cropped region\'s top left corner',
            ],
            [
                'type' => 'assoc',
                'name' => 'image_height',
                'description' => 'The height of the cropped image',
                'optional' => true,
            ],
            [
                'type' => 'assoc',
                'name' => 'image_width',
                'description' => 'The width of the cropped image',
                'optional' => true,
            ],
        ];
    }

    /**
     * Create a new attachment for the cropped image.
     */
    abstract protected function createCroppedImageAttachment(\WP_Post $originalAttachment, string $context, string $croppedImage): int;

    /**
     * Get the message when an attachment image has been successfully cropped.
     */
    abstract protected function getSuccessMessage(): string;
}
