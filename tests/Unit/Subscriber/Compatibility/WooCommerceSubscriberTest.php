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

namespace Ymir\Plugin\Tests\Unit\Subscriber\Compatibility;

use Ymir\Plugin\CloudStorage\PrivateCloudStorageStreamWrapper;
use Ymir\Plugin\CloudStorage\PublicCloudStorageStreamWrapper;
use Ymir\Plugin\Subscriber\Compatibility\WooCommerceSubscriber;
use Ymir\Plugin\Tests\Mock\ContentDeliveryNetworkPageCacheClientInterfaceMockTrait;
use Ymir\Plugin\Tests\Mock\EventManagerMockTrait;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Mock\WPTermMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\Subscriber\Compatibility\WooCommerceSubscriber
 */
class WooCommerceSubscriberTest extends TestCase
{
    use ContentDeliveryNetworkPageCacheClientInterfaceMockTrait;
    use EventManagerMockTrait;
    use FunctionMockTrait;
    use WPTermMockTrait;

    public function testChangeLogDirectoryWhenLogDirectoryIsAStringThatDoesntStartWithThePublicCloudStorageProtocol()
    {
        $logDirectory = '/var/logs';

        $this->assertSame($logDirectory, $this->createSubscriber()->changeLogDirectory($logDirectory));
    }

    public function testChangeLogDirectoryWhenLogDirectoryIsAStringThatStartsWithThePublicCloudStorageProtocol()
    {
        $this->assertSame(sprintf('%s:///wc-logs/', PrivateCloudStorageStreamWrapper::getProtocol()), $this->createSubscriber()->changeLogDirectory(sprintf('%s:///uploads', PublicCloudStorageStreamWrapper::getProtocol())));
    }

    public function testChangeLogDirectoryWhenLogDirectoryIsntAString()
    {
        $logDirectory = 42;

        $this->assertSame($logDirectory, $this->createSubscriber()->changeLogDirectory($logDirectory));
    }

    public function testClearCacheOnProductUpdateWhenClearAllOnPostUpdateIsDisabled()
    {
        $function_exists = $this->getFunctionMock($this->getNamespace(WooCommerceSubscriber::class), 'function_exists');
        $function_exists->expects($this->once())
                        ->with('wc_get_page_permalink')
                        ->willReturn(true);

        $get_permalink = $this->getFunctionMock($this->getNamespace(WooCommerceSubscriber::class), 'get_permalink');
        $get_permalink->expects($this->once())
                      ->with(123)
                      ->willReturn('https://foo.com/product/bar/');

        $wc_get_page_permalink = $this->getFunctionMock($this->getNamespace(WooCommerceSubscriber::class), 'wc_get_page_permalink');
        $wc_get_page_permalink->expects($this->once())
                               ->with('shop')
                               ->willReturn('https://foo.com/shop/');

        $get_the_terms = $this->getFunctionMock($this->getNamespace(WooCommerceSubscriber::class), 'get_the_terms');
        $get_the_terms->expects($this->exactly(2))
                      ->withConsecutive(
                          [123, 'product_cat'],
                          [123, 'product_tag']
                      )
                      ->willReturnOnConsecutiveCalls(
                          [$this->getWPTermMock()],
                          [$this->getWPTermMock()]
                      );

        $get_term_link = $this->getFunctionMock($this->getNamespace(WooCommerceSubscriber::class), 'get_term_link');
        $get_term_link->expects($this->exactly(2))
                      ->willReturnOnConsecutiveCalls(
                          'https://foo.com/category/cat1/',
                          'https://foo.com/tag/tag1/'
                      );

        $pageCacheClient = $this->getContentDeliveryNetworkPageCacheClientInterfaceMock();
        $pageCacheClient->expects($this->once())
                        ->method('clearUrls')
                        ->with($this->callback(function ($urls) {
                            $this->assertSame([
                                'https://foo.com/product/bar/',
                                'https://foo.com/shop/*',
                                'https://foo.com/category/cat1/*',
                                'https://foo.com/tag/tag1/*',
                            ], $urls->all());

                            return true;
                        }));

        $this->createSubscriber('https://foo.com', '', false, ['invalidation_enabled' => true, 'clear_all_on_post_update' => false], $pageCacheClient)->clearCacheOnProductUpdate(123);
    }

    public function testClearCacheOnProductUpdateWhenClearAllOnPostUpdateIsEnabled()
    {
        $pageCacheClient = $this->getContentDeliveryNetworkPageCacheClientInterfaceMock();
        $pageCacheClient->expects($this->once())
                        ->method('clearAll');
        $pageCacheClient->expects($this->never())
                        ->method('clearUrls');

        $this->createSubscriber('https://foo.com', '', false, ['invalidation_enabled' => true, 'clear_all_on_post_update' => true], $pageCacheClient)->clearCacheOnProductUpdate(123);
    }

    public function testClearCacheOnProductUpdateWhenInvalidationIsDisabled()
    {
        $pageCacheClient = $this->getContentDeliveryNetworkPageCacheClientInterfaceMock();
        $pageCacheClient->expects($this->never())
                        ->method('clearUrls');

        $this->createSubscriber('https://foo.com', '', false, ['invalidation_enabled' => false], $pageCacheClient)->clearCacheOnProductUpdate(123);
    }

    public function testClearCacheOnProductVariationUpdate()
    {
        $function_exists = $this->getFunctionMock($this->getNamespace(WooCommerceSubscriber::class), 'function_exists');
        $function_exists->expects($this->once())
                        ->with('wc_get_page_permalink')
                        ->willReturn(true);

        $variation = $this->getMockBuilder(\stdClass::class)
                          ->addMethods(['get_parent_id'])
                          ->getMock();
        $variation->expects($this->once())
                  ->method('get_parent_id')
                  ->willReturn(123);

        $get_permalink = $this->getFunctionMock($this->getNamespace(WooCommerceSubscriber::class), 'get_permalink');
        $get_permalink->expects($this->once())
                      ->with(123)
                      ->willReturn('https://foo.com/product/bar/');

        $wc_get_page_permalink = $this->getFunctionMock($this->getNamespace(WooCommerceSubscriber::class), 'wc_get_page_permalink');
        $wc_get_page_permalink->expects($this->once())
                               ->with('shop')
                               ->willReturn('https://foo.com/shop/');

        $get_the_terms = $this->getFunctionMock($this->getNamespace(WooCommerceSubscriber::class), 'get_the_terms');
        $get_the_terms->expects($this->exactly(2))
                      ->willReturn([]);

        $pageCacheClient = $this->getContentDeliveryNetworkPageCacheClientInterfaceMock();
        $pageCacheClient->expects($this->once())
                        ->method('clearUrls')
                        ->with($this->callback(function ($urls) {
                            $this->assertSame([
                                'https://foo.com/product/bar/',
                                'https://foo.com/shop/*',
                            ], $urls->all());

                            return true;
                        }));

        $this->createSubscriber('https://foo.com', '', false, ['invalidation_enabled' => true], $pageCacheClient)->clearCacheOnProductVariationUpdate(456, $variation);
    }

    public function testDisableCheckImportFilePath()
    {
        $this->assertFalse($this->createSubscriber()->disableCheckImportFilePath());
    }

    public function testDisableImageResizeWithImageProcessingWithImageProcessingDisabled()
    {
        $this->assertTrue($this->createSubscriber()->disableImageResizeWithImageProcessing(true));
    }

    public function testDisableImageResizeWithImageProcessingWithImageProcessingEnabled()
    {
        $this->assertFalse($this->createSubscriber('https://foo.com', 'https://assets.com/assets/uuid', true)->disableImageResizeWithImageProcessing(true));
    }

    public function testFixAssetUrlPathsInCachedScriptDataIfAssetsUrlIsEmpty()
    {
        $wp_json_encode = $this->getFunctionMock($this->getNamespace(WooCommerceSubscriber::class), 'wp_json_encode');
        $wp_json_encode->expects($this->never());

        $this->assertSame('foo', $this->createSubscriber()->fixAssetUrlPathsInCachedScriptData('foo'));
    }

    public function testFixAssetUrlPathsInCachedScriptDataIfJsonDecodeHasError()
    {
        $json_last_error = $this->getFunctionMock($this->getNamespace(WooCommerceSubscriber::class), 'json_last_error');
        $json_last_error->expects($this->once())
                        ->willReturn(JSON_ERROR_SYNTAX);

        $wp_json_encode = $this->getFunctionMock($this->getNamespace(WooCommerceSubscriber::class), 'wp_json_encode');
        $wp_json_encode->expects($this->never());

        $this->assertSame('foo', $this->createSubscriber('https://foo.com', 'https://assets.com/assets/uuid')->fixAssetUrlPathsInCachedScriptData('foo'));
    }

    public function testFixAssetUrlPathsInCachedScriptDataIfScriptDataHasNoScriptData()
    {
        $json_last_error = $this->getFunctionMock($this->getNamespace(WooCommerceSubscriber::class), 'json_last_error');
        $json_last_error->expects($this->once())
                        ->willReturn(JSON_ERROR_NONE);

        $wp_json_encode = $this->getFunctionMock($this->getNamespace(WooCommerceSubscriber::class), 'wp_json_encode');
        $wp_json_encode->expects($this->never());

        $this->assertSame('foo', $this->createSubscriber('https://foo.com', 'https://assets.com/assets/uuid')->fixAssetUrlPathsInCachedScriptData('foo'));
    }

    public function testFixAssetUrlPathsInCachedScriptDataWithDifferentAssetsUrlAndDifferentAssetDomain()
    {
        $scriptData = [
            'script_data' => [
                'script.js' => [
                    'src' => 'https://assets.com/assets/old_uuid/script.js',
                ],
            ],
        ];
        $expectedScriptData = [
            'script_data' => [
                'script.js' => [
                    'src' => 'https://assets.com/assets/new_uuid/script.js',
                ],
            ],
        ];

        $wp_json_encode = $this->getFunctionMock($this->getNamespace(WooCommerceSubscriber::class), 'wp_json_encode');
        $wp_json_encode->expects($this->once())
                       ->with($this->identicalTo($expectedScriptData))
                        ->willReturn(json_encode($expectedScriptData));

        $this->assertSame(json_encode($expectedScriptData), $this->createSubscriber('https://foo.com', 'https://assets.com/assets/new_uuid')->fixAssetUrlPathsInCachedScriptData(json_encode($scriptData)));
    }

    public function testFixAssetUrlPathsInCachedScriptDataWithDifferentAssetsUrlAndSameAssetDomain()
    {
        $scriptData = [
            'script_data' => [
                'script.js' => [
                    'src' => 'https://foo.com/assets/old_uuid/script.js',
                ],
            ],
        ];
        $expectedScriptData = [
            'script_data' => [
                'script.js' => [
                    'src' => 'https://foo.com/assets/new_uuid/script.js',
                ],
            ],
        ];

        $wp_json_encode = $this->getFunctionMock($this->getNamespace(WooCommerceSubscriber::class), 'wp_json_encode');
        $wp_json_encode->expects($this->once())
            ->with($this->identicalTo($expectedScriptData))
            ->willReturn(json_encode($expectedScriptData));

        $this->assertSame(json_encode($expectedScriptData), $this->createSubscriber('https://foo.com', 'https://foo.com/assets/new_uuid')->fixAssetUrlPathsInCachedScriptData(json_encode($scriptData)));
    }

    public function testGetSubscribedEvents()
    {
        $callbacks = WooCommerceSubscriber::getSubscribedEvents();

        foreach ($callbacks as $callback) {
            $this->assertTrue(method_exists(WooCommerceSubscriber::class, is_array($callback) ? $callback[0] : $callback));
        }

        $subscribedEvents = [
            'transient_woocommerce_blocks_asset_api_script_data' => 'fixAssetUrlPathsInCachedScriptData',
            'transient_woocommerce_blocks_asset_api_script_data_ssl' => 'fixAssetUrlPathsInCachedScriptData',
            'woocommerce_csv_importer_check_import_file_path' => 'disableCheckImportFilePath',
            'woocommerce_log_directory' => 'changeLogDirectory',
            'woocommerce_product_csv_importer_check_import_file_path' => 'disableCheckImportFilePath',
            'woocommerce_resize_images' => 'disableImageResizeWithImageProcessing',
            'woocommerce_update_product' => 'clearCacheOnProductUpdate',
            'woocommerce_update_product_variation' => 'clearCacheOnProductVariationUpdate',
        ];

        $this->assertSame($subscribedEvents, $callbacks);
    }

    /**
     * Create a new WooCommerceSubscriber instance.
     */
    private function createSubscriber(string $siteUrl = 'https://foo.com', string $assetsUrl = '', bool $isImageProcessingEnabled = false, array $pageCachingOptions = [], $pageCacheClient = null): WooCommerceSubscriber
    {
        return new WooCommerceSubscriber($pageCacheClient ?: $this->getContentDeliveryNetworkPageCacheClientInterfaceMock(), $siteUrl, $assetsUrl, $isImageProcessingEnabled, $pageCachingOptions);
    }
}
