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
     * The Ymir project type.
     *
     * @var string
     */
    private $projectType;

    /**
     * WordPress site URL.
     *
     * @var string
     */
    private $siteUrl;

    /**
     * Constructor.
     */
    public function __construct(string $siteUrl, string $assetsUrl = '', string $projectType = '')
    {
        $this->assetsUrl = rtrim($assetsUrl, '/');
        $this->projectType = $projectType;
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
            'wp_resource_hints' => ['addAssetsUrlToDnsPrefetch', 10, 2],
        ];
    }

    /**
     * Add the assets URL to the "dns-prefetch" resource hints if the assets URL is on a different domain
     * as the site URL.
     */
    public function addAssetsUrlToDnsPrefetch(array $urls, string $type): array
    {
        if ('dns-prefetch' === $type && !empty($this->assetsUrl) && 0 !== stripos($this->assetsUrl, $this->siteUrl)) {
            $urls[] = $this->assetsUrl;
        }

        return $urls;
    }

    /**
     * Replace the loader source with the assets URL.
     */
    public function replaceLoaderSource(string $src): string
    {
        if (empty($this->assetsUrl) || false !== stripos($src, $this->assetsUrl) || false === stripos($src, $this->siteUrl)) {
            return $src;
        }

        $src = str_ireplace($this->siteUrl, '', $src);

        // We need to ensure we always have the /wp/ prefix in the asset URLs when using Bedrock. This gets messed
        // up in multisite subdirectory installations because it would be handled by a rewrite rule normally. We
        // need to handle it programmatically instead.
        if ('bedrock' === $this->projectType && '/wp/' !== substr($src, 0, 4) && '/app/' !== substr($src, 0, 5)) {
            $src = '/wp'.$src;
        }

        return $this->assetsUrl.$src;
    }
}
