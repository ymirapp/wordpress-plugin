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

/**
 * Command for creating a site icon.
 */
class CreateSiteIconCommand extends AbstractCropAttachmentImageCommand
{
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
    public static function getName(): string
    {
        return parent::getName().' create-site-icon';
    }

    /**
     * {@inheritdoc}
     */
    protected function createCroppedImageAttachment(\WP_Post $originalAttachment, string $context, string $croppedImage): int
    {
        if ('site-icon' == $originalAttachment->_wp_attachment_context) {
            wp_delete_file($croppedImage);

            return $originalAttachment->ID;
        }

        require_once ABSPATH.'wp-admin/includes/class-wp-site-icon.php';
        $siteIcon = new \WP_Site_Icon();

        $croppedImage = $this->eventManager->filter('wp_create_file_in_uploads', $croppedImage, $originalAttachment->ID);

        $attachment = $siteIcon->create_attachment_object($croppedImage, $originalAttachment->ID);
        unset($attachment['ID']);

        $this->eventManager->addCallback('intermediate_image_sizes_advanced', [$siteIcon, 'additional_sizes']);
        $attachmentId = $siteIcon->insert_attachment($attachment, $croppedImage);
        $this->eventManager->removeCallback('intermediate_image_sizes_advanced', [$siteIcon, 'additional_sizes']);

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
