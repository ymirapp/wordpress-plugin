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

use Ymir\Plugin\EventManagement\SubscriberInterface;
use Ymir\Plugin\RestApi\CreateAttachmentEndpoint;
use Ymir\Plugin\RestApi\GetFileDetailsEndpoint;

/**
 * Subscriber for the Plupload library.
 */
class PluploadSubscriber implements SubscriberInterface
{
    /**
     * URL to the plugin's assets folder.
     *
     * @var string
     */
    private $assetsUrl;

    /**
     * The Plupload error messages.
     *
     * @var array
     */
    private $errorMessages;

    /**
     * The Ymir REST API namespace.
     *
     * @var string
     */
    private $restApiNamespace;

    /**
     * Constructor.
     */
    public function __construct(string $pluginRelativePath, string $restApiNamespace, string $assetsUrl = '', array $errorMessages = [])
    {
        $this->assetsUrl = rtrim($assetsUrl, '/').'/'.trim($pluginRelativePath, '/').'/assets/js';
        $this->errorMessages = $errorMessages;
        $this->restApiNamespace = $restApiNamespace;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'wp_default_scripts' => ['replacePluploadScripts', 99],
            'plupload_default_settings' => 'editDefaultSettings',
            'plupload_init' => 'editDefaultSettings',
        ];
    }

    public function editDefaultSettings(array $defaultSettings): array
    {
        $defaultSettings['attachments_endpoint_url'] = rest_url($this->restApiNamespace.CreateAttachmentEndpoint::getPath());
        $defaultSettings['file_endpoint_url'] = rest_url($this->restApiNamespace.GetFileDetailsEndpoint::getPath());

        return $defaultSettings;
    }

    /**
     * Replace the plupload scripts with the modified ones for Ymir.
     */
    public function replacePluploadScripts(\WP_Scripts $scripts)
    {
        $scripts->remove('plupload');
        $scripts->remove('plupload-handlers');
        $scripts->remove('wp-plupload');

        $scripts->add('plupload', "{$this->assetsUrl}/plupload.js", ['moxiejs', 'wp-api-request']);
        $scripts->add('plupload-handlers', "{$this->assetsUrl}/handlers.js", ['plupload', 'jquery']);
        $scripts->add('wp-plupload', "{$this->assetsUrl}/wp-plupload.js", ['plupload', 'jquery', 'json2', 'media-models', 'wp-api-request']);

        $scripts->localize('plupload-handlers', 'pluploadL10n', $this->errorMessages);
        $scripts->localize('wp-plupload', 'pluploadL10n', $this->errorMessages);
    }
}
