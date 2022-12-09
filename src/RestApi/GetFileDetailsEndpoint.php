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

/**
 * REST API endpoint to get information for a given filename.
 */
class GetFileDetailsEndpoint extends AbstractEndpoint
{
    /**
     * The cloud storage API client.
     *
     * @var CloudStorageClientInterface
     */
    private $client;

    /**
     * Full path to upload directory.
     *
     * @var string
     */
    private $uploadsPath;

    /**
     * Subdirectory if uploads use year/month folders option is on.
     *
     * @var string
     */
    private $uploadsSubdirectory;

    /**
     * Constructor.
     */
    public function __construct(CloudStorageClientInterface $client, string $uploadsPath, string $uploadsSubdirectory)
    {
        $this->client = $client;
        $this->uploadsPath = $uploadsPath;
        $this->uploadsSubdirectory = trim($uploadsSubdirectory, '/').'/';
    }

    /**
     * {@inheritdoc}
     */
    public static function getPath(): string
    {
        return '/file-details';
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments(): array
    {
        return [
            'filename' => [
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
        return ['GET'];
    }

    /**
     * {@inheritdoc}
     */
    public function respond(\WP_REST_Request $request)
    {
        $filename = wp_unique_filename($this->uploadsPath, urlencode(wp_basename(sanitize_file_name(htmlspecialchars_decode($request->get_param('filename'), ENT_QUOTES)))));
        $path = $this->uploadsSubdirectory.$filename;

        // Need to extract the "sites/{blog_id}" for multisite
        preg_match('/(uploads.*)/', substr($this->uploadsPath, 0, -strlen($this->uploadsSubdirectory)), $matches);

        if (empty($matches[1])) {
            throw new \RuntimeException('Unable to parse uploads path');
        }

        return [
            'filename' => $filename,
            'path' => $path,
            'upload_url' => $this->client->createPutObjectRequest(trim($matches[1], '/').'/'.$path),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function validateRequest(\WP_REST_Request $request): bool
    {
        return current_user_can('upload_files');
    }
}
