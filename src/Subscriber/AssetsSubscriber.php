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
 * Subscriber for managing the integration between WordPress and the deployed assets.
 */
class AssetsSubscriber implements SubscriberInterface
{
    /**
     * URL to the deployed WordPress assets on the cloud storage.
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
     * URL to the the uploads directory on the cloud storage.
     *
     * @var string
     */
    private $uploadUrl;

    /**
     * Constructor.
     */
    public function __construct(string $siteUrl, string $assetsUrl = '', string $projectType = '', string $uploadUrl = '')
    {
        $this->assetsUrl = rtrim($assetsUrl, '/');
        $this->projectType = $projectType;
        $this->siteUrl = rtrim($siteUrl, '/');
        $this->uploadUrl = rtrim($uploadUrl, '/');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'content_url' => 'rewriteContentUrl',
            'plugins_url' => 'rewritePluginsUrl',
            'script_loader_src' => 'replaceSiteUrlWithAssetsUrl',
            'style_loader_src' => 'replaceSiteUrlWithAssetsUrl',
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
     * Replace the site URL with the assets URL.
     */
    public function replaceSiteUrlWithAssetsUrl(string $url): string
    {
        if (!$this->doesUrlNeedRewrite($url)) {
            return $url;
        }

        $url = str_ireplace($this->siteUrl, '', $url);

        // We need to ensure we always have the /wp/ prefix in the asset URLs when using Bedrock. This gets messed
        // up in multisite subdirectory installations because it would be handled by a rewrite rule normally. We
        // need to handle it programmatically instead.
        if ('bedrock' === $this->projectType && '/wp/' !== substr($url, 0, 4) && '/app/' !== substr($url, 0, 5)) {
            $url = '/wp'.$url;
        }

        return $this->assetsUrl.$url;
    }

    /**
     * Rewrite the wp-content URL so it points to the assets URL.
     */
    public function rewriteContentUrl(string $url): string
    {
        $contentDirectoryName = '/wp-content';

        if (defined('CONTENT_DIR')) {
            $contentDirectoryName = CONTENT_DIR;
        }

        $contentDirectoryName = '/'.ltrim($contentDirectoryName, '/');

        $matches = [];
        preg_match(sprintf('/http(s)?:\/\/.*(%s.*)/', preg_quote($contentDirectoryName, '/')), $url, $matches);

        if (empty($matches[2])) {
            return $url;
        }

        return $this->assetsUrl.$matches[2];
    }

    /**
     * Rewrite the plugins URL so it points to the assets URL.
     */
    public function rewritePluginsUrl(string $url): string
    {
        $matches = [];
        preg_match('/http(s)?:\/\/.*(\/[^\/]*\/plugins.*)/', $url, $matches);

        if (empty($matches[2])) {
            return $url;
        }

        return $this->assetsUrl.$matches[2];
    }

    /**
     * Check if we need to rewrite the given URL.
     */
    private function doesUrlNeedRewrite(string $url): bool
    {
        return false !== stripos($url, $this->siteUrl)
            && (!empty($this->assetsUrl) && false === stripos($url, $this->assetsUrl))
            && (empty($this->assetsUrl) || false === stripos($url, $this->uploadUrl));
    }
}
