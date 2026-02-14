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

namespace Ymir\Plugin\Subscriber\Compatibility;

use Ymir\Plugin\CloudStorage\PrivateCloudStorageStreamWrapper;
use Ymir\Plugin\CloudStorage\PublicCloudStorageStreamWrapper;
use Ymir\Plugin\EventManagement\SubscriberInterface;
use Ymir\Plugin\PageCache\ContentDeliveryNetworkPageCacheClientInterface;
use Ymir\Plugin\Support\Collection;

/**
 * Subscriber that handles WooCommerce compatibility.
 */
class WooCommerceSubscriber implements SubscriberInterface
{
    /**
     * URL to the deployed WordPress assets on the cloud storage.
     *
     * @var string
     */
    private $assetsUrl;

    /**
     * Flag whether image processing is enabled or not.
     *
     * @var bool
     */
    private $isImageProcessingEnabled;

    /**
     * Client interacting with the content delivery network handling page caching.
     *
     * @var ContentDeliveryNetworkPageCacheClientInterface
     */
    private $pageCacheClient;

    /**
     * The page caching options.
     *
     * @var array
     */
    private $pageCachingOptions;

    /**
     * WordPress site URL.
     *
     * @var string
     */
    private $siteUrl;

    /**
     * Constructor.
     */
    public function __construct(ContentDeliveryNetworkPageCacheClientInterface $pageCacheClient, string $siteUrl, string $assetsUrl = '', bool $isImageProcessingEnabled = false, array $pageCachingOptions = [])
    {
        $this->assetsUrl = rtrim($assetsUrl, '/');
        $this->isImageProcessingEnabled = $isImageProcessingEnabled;
        $this->pageCacheClient = $pageCacheClient;
        $this->pageCachingOptions = $pageCachingOptions;
        $this->siteUrl = rtrim($siteUrl, '/');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'transient_woocommerce_blocks_asset_api_script_data' => 'fixAssetUrlPathsInCachedScriptData',
            'transient_woocommerce_blocks_asset_api_script_data_ssl' => 'fixAssetUrlPathsInCachedScriptData',
            'woocommerce_csv_importer_check_import_file_path' => 'disableCheckImportFilePath',
            'woocommerce_log_directory' => 'changeLogDirectory',
            'woocommerce_product_csv_importer_check_import_file_path' => 'disableCheckImportFilePath',
            'woocommerce_resize_images' => 'disableImageResizeWithImageProcessing',
            'woocommerce_update_product' => 'clearCacheOnProductUpdate',
            'woocommerce_update_product_variation' => 'clearCacheOnProductVariationUpdate',
        ];
    }

    /**
     * Change the log directory to point to the private cloud storage.
     */
    public function changeLogDirectory($logDirectory)
    {
        return is_string($logDirectory) && str_starts_with($logDirectory, PublicCloudStorageStreamWrapper::getProtocol())
             ? sprintf('%s:///wc-logs/', PrivateCloudStorageStreamWrapper::getProtocol())
             : $logDirectory;
    }

    /**
     * Clear all the related product URLs when a product is updated.
     */
    public function clearCacheOnProductUpdate($productId)
    {
        $this->clearProductUrls($productId);
    }

    /**
     * Clear all the related product URLs when a product variation is updated.
     */
    public function clearCacheOnProductVariationUpdate($variationId, $variation)
    {
        if (!is_object($variation) || !method_exists($variation, 'get_parent_id')) {
            return;
        }

        $this->clearProductUrls($variation->get_parent_id());
    }

    /**
     * Disable "check import file path" so that imports work with S3 storage.
     */
    public function disableCheckImportFilePath(): bool
    {
        return false;
    }

    /**
     * Disable image resizing when image processing is enabled.
     */
    public function disableImageResizeWithImageProcessing($resizeImages)
    {
        return $this->isImageProcessingEnabled ? false : $resizeImages;
    }

    /**
     * Fix the asset URL paths in the cached script data.
     */
    public function fixAssetUrlPathsInCachedScriptData($value)
    {
        if (empty($this->assetsUrl)) {
            return $value;
        }

        $cachedScriptData = json_decode((string) $value, true);

        if (JSON_ERROR_NONE !== json_last_error() || empty($cachedScriptData['script_data'])) {
            return $value;
        }

        $cachedScriptData['script_data'] = (new Collection($cachedScriptData['script_data']))->mapWithKeys(function (array $script, string $key) {
            if (!empty($script['src'])) {
                $script['src'] = preg_replace(sprintf('#^https?://(%s|%s)/assets/[^/]*#i', parse_url($this->siteUrl, PHP_URL_HOST), parse_url($this->assetsUrl, PHP_URL_HOST)), $this->assetsUrl, $script['src']);
            }

            return [$key => $script];
        })->all();

        return wp_json_encode($cachedScriptData);
    }

    /**
     * Clear all the URLs related to the given product from the page cache.
     */
    private function clearProductUrls($productId)
    {
        if (empty($this->pageCachingOptions['invalidation_enabled'])) {
            return;
        } elseif (!empty($this->pageCachingOptions['clear_all_on_post_update'])) {
            $this->pageCacheClient->clearAll();

            return;
        }

        $permalink = get_permalink($productId);

        if (!is_string($permalink)) {
            return;
        }

        $urlsToClear = new Collection();

        $urlsToClear[] = rtrim($permalink, '/').'/';

        if (function_exists('wc_get_page_permalink')) {
            $urlsToClear[] = rtrim(wc_get_page_permalink('shop'), '/').'/*';
        }

        // Product category URLs
        $productCategories = (new Collection(get_the_terms($productId, 'product_cat')))->filter(function ($category) {
            return $category instanceof \WP_Term;
        });
        $urlsToClear = $urlsToClear->merge($productCategories->map(function (\WP_Term $category) {
            return rtrim(get_term_link($category), '/').'/*';
        }));

        // Product tag URLs
        $productTags = (new Collection(get_the_terms($productId, 'product_tag')))->filter(function ($category) {
            return $category instanceof \WP_Term;
        });
        $urlsToClear = $urlsToClear->merge($productTags->map(function (\WP_Term $tag) {
            return rtrim(get_term_link($tag), '/').'/*';
        }));

        $this->pageCacheClient->clearUrls($urlsToClear);
    }
}
