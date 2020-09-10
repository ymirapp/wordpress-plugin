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
 * Command to create the attachment metadata.
 */
class CreateAttachmentMetadataCommand extends AbstractAttachmentCommand
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(array $arguments, array $options)
    {
        $attachment = $this->getAttachment($arguments[0]);

        wp_update_attachment_metadata($attachment->ID, wp_generate_attachment_metadata($attachment->ID, $this->getFilePath($attachment)));

        $this->success(sprintf('Created metadata for attachment "%s"', $attachment->ID));
    }

    /**
     * {@inheritdoc}
     */
    public static function getDescription(): string
    {
        return 'Creates the metadata for the given attachment';
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
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected static function getCommandName(): string
    {
        return 'create-attachment-metadata';
    }
}
