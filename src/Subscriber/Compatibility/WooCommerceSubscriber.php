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

use Ymir\Plugin\EventManagement\SubscriberInterface;
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
     * WordPress site URL.
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
            'transient_woocommerce_blocks_asset_api_script_data' => 'fixAssetUrlPathsInCachedScriptData',
            'transient_woocommerce_blocks_asset_api_script_data_ssl' => 'fixAssetUrlPathsInCachedScriptData',
            'woocommerce_csv_importer_check_import_file_path' => 'disableCheckImportFilePath',
            'woocommerce_product_csv_importer_check_import_file_path' => 'disableCheckImportFilePath',
        ];
    }

    /**
     * Disable "check import file path" so that imports work with S3 storage.
     */
    public function disableCheckImportFilePath(): bool
    {
        return false;
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
}
