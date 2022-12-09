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
use Ymir\Plugin\Support\Collection;

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
     * The WordPress content directory name.
     *
     * @var string
     */
    private $contentDirectoryName;

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
     * The URL to uploads directory.
     *
     * @var string
     */
    private $uploadsUrl;

    /**
     * Constructor.
     */
    public function __construct(string $contentDirectoryName, string $siteUrl, string $assetsUrl = '', string $projectType = '', string $uploadsUrl = '')
    {
        $this->assetsUrl = rtrim($assetsUrl, '/');
        $this->contentDirectoryName = '/'.ltrim($contentDirectoryName, '/');
        $this->projectType = $projectType;
        $this->siteUrl = rtrim($siteUrl, '/');
        $this->uploadsUrl = rtrim($uploadsUrl, '/');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'content_url' => 'rewriteContentUrl',
            'includes_url' => 'rewriteIncludesUrl',
            'plugins_url' => 'rewritePluginsUrl',
            'script_loader_src' => 'rewriteEnqueuedUrl',
            'style_loader_src' => 'rewriteEnqueuedUrl',
            'the_content' => ['replaceUrlsInContent', 99999], // Make the priority high, but less than 999999 which is the Jetpack Photon hook priority
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
     * Replace broken URLs in the post content so they correctly point to the current assets URL.
     */
    public function replaceUrlsInContent(string $content): string
    {
        if (empty($this->assetsUrl)) {
            return $content;
        }

        // The assumption is that all URLs are surrounded by either quotes or double quotes.
        $patterns = [
            '%"(?P<url>https?://[^"]*?)"%is',
            "%'(?P<url>https?://[^']*?)'%is",
        ];
        $urls = new Collection();

        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $content, $matches);

            $urls = $urls->merge($matches['url'] ?? []);
        }

        if ($urls->isEmpty()) {
            return $content;
        }

        $assetsHost = parse_url($this->assetsUrl, PHP_URL_HOST);
        $siteHost = parse_url($this->siteUrl, PHP_URL_HOST);
        $uploadsDirectory = $this->contentDirectoryName.'/uploads';
        $urls = $urls->unique();

        // If we have a hardcoded URL to an asset, we want to dynamically update it to the
        // current asset URL.
        $assetsUrls = $urls->filter(function (string $url) use ($assetsHost) {
            return parse_url($url, PHP_URL_HOST) === $assetsHost;
        })->mapWithKeys(function (string $url) {
            return [$url => $this->rewriteAssetsUrl('%https?://[^/]*/assets/[^/]*(.*)%i', $url)];
        })->all();

        // Get all the URLs pointing to the "/wp-content" directory
        $contentUrls = $urls->filter(function (string $url) use ($siteHost) {
            return parse_url($url, PHP_URL_HOST) === $siteHost;
        })->filter(function (string $url) {
            $path = parse_url($url, PHP_URL_PATH);

            return is_string($path) && 0 === stripos($path, $this->contentDirectoryName);
        });

        // Point all non-uploads "/wp-content" URLs to the assets URL.
        $nonUploadsUrls = $contentUrls->filter(function (string $url) use ($uploadsDirectory) {
            return false === stripos(parse_url($url, PHP_URL_PATH), $uploadsDirectory);
        })->mapWithKeys(function (string $url) {
            return [$url => $this->rewriteAssetsUrl(sprintf('#https?://[^/]*(%s.*)#', $this->contentDirectoryName), $url)];
        })->all();

        // Point all URLs to "/wp-content/uploads" to the uploads URL.
        $uploadsUrls = $contentUrls->filter(function (string $url) use ($uploadsDirectory) {
            $path = parse_url($url, PHP_URL_PATH);

            return is_string($path) && 0 === stripos($path, $uploadsDirectory);
        })->mapWithKeys(function (string $url) use ($uploadsDirectory) {
            return [$url => $this->rewriteUploadsUrl(sprintf('#https?://[^/]*%s(.*)#', $uploadsDirectory), $url)];
        })->all();

        foreach (array_merge($assetsUrls, $nonUploadsUrls, $uploadsUrls) as $originalUrl => $newUrl) {
            $content = str_replace($originalUrl, $newUrl, $content);
        }

        return $content;
    }

    /**
     * Rewrite the wp-content URL to point it to the assets URL.
     */
    public function rewriteContentUrl(string $url): string
    {
        return $this->rewriteAssetsUrl(sprintf('#https?://.*(%s.*)#', $this->contentDirectoryName), $url);
    }

    /**
     * Rewrite the enqueued URLs done via "wp_enqueue_script" and "wp_enqueue_style".
     */
    public function rewriteEnqueuedUrl(string $url): string
    {
        // Some plugins enqueue scripts and styles with two slashes which breaks CloudFront and S3.
        $url = preg_replace('#(?<!^|http:|https:)//#i', '/', $url);

        if (!$this->doesUrlNeedRewrite($url)) {
            return $url;
        }

        $uri = str_ireplace($this->siteUrl, '', $url);

        // We need to ensure we always have the /wp/ prefix in the asset URLs when using Bedrock. This gets messed
        // up in multisite subdirectory installations because it would be handled by a rewrite rule normally. We
        // need to handle it programmatically instead.
        if ('bedrock' === $this->projectType && '/wp/' !== substr($uri, 0, 4) && '/app/' !== substr($uri, 0, 5)) {
            $uri = '/wp'.$uri;
        }

        return $this->assetsUrl.'/'.ltrim($uri, '/');
    }

    /**
     * Rewrite the wp-includes URL to point it to the assets URL.
     */
    public function rewriteIncludesUrl(string $url): string
    {
        return $this->rewriteAssetsUrl('#https?://[^/]*((/[^/]*)?/wp-includes.*)#', $url);
    }

    /**
     * Rewrite the plugins URL to point it to the assets URL.
     */
    public function rewritePluginsUrl(string $url): string
    {
        return $this->rewriteAssetsUrl('#https?://.*(/[^/]*/plugins.*)#', $url);
    }

    /**
     * Check if we need to rewrite the given URL.
     */
    private function doesUrlNeedRewrite(string $url): bool
    {
        return false !== stripos($url, $this->siteUrl)
            && (!empty($this->assetsUrl) && false === stripos($url, $this->assetsUrl))
            && (empty($this->uploadsUrl) || false === stripos($url, $this->uploadsUrl));
    }

    /**
     * Rewrite the given URL to point to the assets URL based on the given REGEX pattern.
     */
    private function rewriteAssetsUrl(string $pattern, string $url): string
    {
        preg_match($pattern, $url, $matches);

        return empty($matches[1]) ? $url : $this->assetsUrl.'/'.ltrim($matches[1], '/');
    }

    /**
     * Rewrite the given URL to point to the uploads URL based on the given REGEX pattern.
     */
    private function rewriteUploadsUrl(string $pattern, string $url): string
    {
        preg_match($pattern, $url, $matches);

        return empty($matches[1]) ? $url : $this->uploadsUrl.'/'.ltrim($matches[1], '/');
    }
}
