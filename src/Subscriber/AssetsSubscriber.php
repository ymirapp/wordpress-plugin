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

/**
 * Subscriber for managing WordPress assets.
 */
class AssetsSubscriber implements SubscriberInterface
{
    /**
     * URL to the plugin's assets folder.
     *
     * @var string
     */
    private $assetsUrl;

    /**
     * URL to the plugin's assets folder.
     *
     * @var string
     */
    private $siteUrl;

    /**
     * Constructor.
     */
    public function __construct(string $siteUrl, string $assetsUrl = '')
    {
        $this->assetsUrl = rtrim($assetsUrl, '/');
        $this->siteUrl = rtrim($siteUrl, '/');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'script_loader_src' => 'replaceLoaderSource',
            'style_loader_src' => 'replaceLoaderSource',
        ];
    }

    /**
     * Replace the loader source with the assets URL.
     */
    public function replaceLoaderSource(string $src): string
    {
        if (empty($this->assetsUrl) || false !== stripos($src, $this->assetsUrl)) {
            return $src;
        }

        return str_ireplace($this->siteUrl, $this->assetsUrl, $src);
    }
}
