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

namespace Ymir\Plugin\RestApi;

use Ymir\Plugin\CloudStorage\CloudStorageClientInterface;
use Ymir\Plugin\Console\ConsoleClientInterface;

/**
 * REST API endpoint to create a WordPress attachment for the given file path.
 */
class CreateAttachmentEndpoint extends AbstractEndpoint
{
    /**
     * The cloud storage API client.
     *
     * @var CloudStorageClientInterface
     */
    private $cloudStorageClient;

    /**
     * The console client.
     *
     * @var ConsoleClientInterface
     */
    private $consoleClient;

    /**
     * Whether to force the creation of the attachment metadata asynchronously or not.
     *
     * @var bool
     */
    private $forceAsync;

    /**
     * The path to uploads directory.
     *
     * @var string
     */
    private $uploadsDirectory;

    /**
     * The URL to uploads directory.
     *
     * @var string
     */
    private $uploadsUrl;

    /**
     * Constructor.
     */
    public function __construct(CloudStorageClientInterface $cloudStorageClient, ConsoleClientInterface $consoleClient, string $uploadsDirectory, string $uploadsUrl, bool $forceAsync = false)
    {
        $this->cloudStorageClient = $cloudStorageClient;
        $this->consoleClient = $consoleClient;
        $this->forceAsync = $forceAsync;
        $this->uploadsDirectory = $uploadsDirectory;
        $this->uploadsUrl = $uploadsUrl;
    }

    /**
     * {@inheritdoc}
     */
    public static function getPath(): string
    {
        return '/attachments';
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments(): array
    {
        return [
            'path' => [
                'required' => true,
                'sanitize_callback' => function ($value) {
                    return filter_var($value, FILTER_SANITIZE_STRING);
                },
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getMethods(): array
    {
        return ['POST'];
    }

    /**
     * {@inheritdoc}
     */
    public function respond(\WP_REST_Request $request)
    {
        $path = ltrim($request->get_param('path'), '/');

        // Need to extract the "sites/{blog_id}" for multisite
        preg_match('/(uploads.*)/', $this->uploadsDirectory, $matches);

        if (empty($matches[1])) {
            throw new \RuntimeException('Unable to parse uploads directory');
        }

        $details = $this->cloudStorageClient->getObjectDetails(trim($matches[1], '/').'/'.$path);
        $url = $this->uploadsUrl.'/'.$path;

        $attachment = [
            'guid' => $url,
            'post_author' => get_current_user_id(),
            'post_mime_type' => $details['type'] ?? '',
            'post_title' => sanitize_text_field(pathinfo(urldecode($path), PATHINFO_FILENAME)),
        ];

        $attachmentId = wp_insert_attachment($attachment, $path, 0, true);

        if ($attachmentId instanceof \WP_Error) {
            return $attachmentId;
        }

        $async = $this->needsAsync($details);

        if ($async) {
            wp_update_attachment_metadata($attachmentId, $this->createBaseImageMetadata($path));
        }

        $this->consoleClient->createAttachmentMetadata($attachmentId, $async);

        return wp_prepare_attachment_for_js($attachmentId);
    }

    /**
     * {@inheritdoc}
     */
    public function validateRequest(\WP_REST_Request $request): bool
    {
        return current_user_can('upload_files');
    }

    /**
     * Create the base image metadata.
     */
    private function createBaseImageMetadata(string $path): array
    {
        $metadata = [
            'file' => $path,
        ];
        $size = @getimagesize($this->uploadsDirectory.'/'.$path);

        if (is_array($size) && isset($size[0], $size[1])) {
            $metadata['height'] = $size[1];
            $metadata['width'] = $size[0];
        }

        return $metadata;
    }

    /**
     * Determine if we need to create the attachment metadata asynchronously or not.
     */
    private function needsAsync(array $details): bool
    {
        if ($this->forceAsync) {
            return true;
        }

        return isset($details['size'], $details['type']) && 0 === stripos($details['type'], 'image/') && $details['size'] > wp_convert_hr_to_bytes('15MB');
    }
}
