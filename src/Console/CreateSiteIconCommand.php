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
 * Command for creating a site icon.
 */
class CreateSiteIconCommand extends AbstractCropAttachmentImageCommand
{
    /**
     * WordPress site icon class.
     *
     * @var \WP_Site_Icon
     */
    private $siteIcon;

    /**
     * Constructor.
     */
    public function __construct(AttachmentFileManager $fileManager, EventManager $eventManager, \WP_Site_Icon $siteIcon)
    {
        parent::__construct($fileManager, $eventManager);

        $this->siteIcon = $siteIcon;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDescription(): string
    {
        return 'Create a site icon';
    }

    /**
     * {@inheritdoc}
     */
    protected static function getCommandName(): string
    {
        return 'create-site-icon';
    }

    /**
     * {@inheritdoc}
     */
    protected function createCroppedImageAttachment(\WP_Post $originalAttachment, string $context, string $croppedImage): int
    {
        if (!empty($originalAttachment->_wp_attachment_context) && 'site-icon' == $originalAttachment->_wp_attachment_context) {
            wp_delete_file($croppedImage);

            return $originalAttachment->ID;
        }

        $croppedImage = $this->eventManager->filter('wp_create_file_in_uploads', $croppedImage, $originalAttachment->ID);

        $attachment = $this->siteIcon->create_attachment_object($croppedImage, $originalAttachment->ID);
        unset($attachment['ID']);

        $this->eventManager->addCallback('intermediate_image_sizes_advanced', [$this->siteIcon, 'additional_sizes']);
        $attachmentId = $this->siteIcon->insert_attachment($attachment, $croppedImage);
        $this->eventManager->removeCallback('intermediate_image_sizes_advanced', [$this->siteIcon, 'additional_sizes']);

        return $attachmentId;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSuccessMessage(): string
    {
        return 'Site icon successfully created';
    }
}
