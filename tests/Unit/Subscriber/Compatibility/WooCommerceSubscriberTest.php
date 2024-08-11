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
use Ymir\Plugin\Tests\Mock\EventManagerMockTrait;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\Subscriber\Compatibility\WooCommerceSubscriber
 */
class WooCommerceSubscriberTest extends TestCase
{
    use EventManagerMockTrait;
    use FunctionMockTrait;

    public function testChangeLogDirectoryWhenLogDirectoryIsAStringThatDoesntStartWithThePublicCloudStorageProtocol()
    {
        $logDirectory = '/var/logs';

        $this->assertSame($logDirectory, (new WooCommerceSubscriber('https://foo.com'))->changeLogDirectory($logDirectory));
    }

    public function testChangeLogDirectoryWhenLogDirectoryIsAStringThatStartsWithThePublicCloudStorageProtocol()
    {
        $this->assertSame(sprintf('%s:///wc-logs/', PrivateCloudStorageStreamWrapper::getProtocol()), (new WooCommerceSubscriber('https://foo.com'))->changeLogDirectory(sprintf('%s:///uploads', PublicCloudStorageStreamWrapper::getProtocol())));
    }

    public function testChangeLogDirectoryWhenLogDirectoryIsntAString()
    {
        $logDirectory = 42;

        $this->assertSame($logDirectory, (new WooCommerceSubscriber('https://foo.com'))->changeLogDirectory($logDirectory));
    }

    public function testDisableCheckImportFilePath()
    {
        $this->assertFalse((new WooCommerceSubscriber('https://foo.com'))->disableCheckImportFilePath());
    }

    public function testDisableImageResizeWithImageProcessingWithImageProcessingDisabled()
    {
        $this->assertTrue((new WooCommerceSubscriber('https://foo.com'))->disableImageResizeWithImageProcessing(true));
    }

    public function testDisableImageResizeWithImageProcessingWithImageProcessingEnabled()
    {
        $this->assertFalse((new WooCommerceSubscriber('https://foo.com', 'https://assets.com/assets/uuid', true))->disableImageResizeWithImageProcessing(true));
    }

    public function testFixAssetUrlPathsInCachedScriptDataIfAssetsUrlIsEmpty()
    {
        $wp_json_encode = $this->getFunctionMock($this->getNamespace(WooCommerceSubscriber::class), 'wp_json_encode');
        $wp_json_encode->expects($this->never());

        $this->assertSame('foo', (new WooCommerceSubscriber('https://foo.com'))->fixAssetUrlPathsInCachedScriptData('foo'));
    }

    public function testFixAssetUrlPathsInCachedScriptDataIfJsonDecodeHasError()
    {
        $json_last_error = $this->getFunctionMock($this->getNamespace(WooCommerceSubscriber::class), 'json_last_error');
        $json_last_error->expects($this->once())
                        ->willReturn(JSON_ERROR_SYNTAX);

        $wp_json_encode = $this->getFunctionMock($this->getNamespace(WooCommerceSubscriber::class), 'wp_json_encode');
        $wp_json_encode->expects($this->never());

        $this->assertSame('foo', (new WooCommerceSubscriber('https://foo.com', 'https://assets.com/assets/uuid'))->fixAssetUrlPathsInCachedScriptData('foo'));
    }

    public function testFixAssetUrlPathsInCachedScriptDataIfScriptDataHasNoScriptData()
    {
        $json_last_error = $this->getFunctionMock($this->getNamespace(WooCommerceSubscriber::class), 'json_last_error');
        $json_last_error->expects($this->once())
                        ->willReturn(JSON_ERROR_NONE);

        $wp_json_encode = $this->getFunctionMock($this->getNamespace(WooCommerceSubscriber::class), 'wp_json_encode');
        $wp_json_encode->expects($this->never());

        $this->assertSame('foo', (new WooCommerceSubscriber('https://foo.com', 'https://assets.com/assets/uuid'))->fixAssetUrlPathsInCachedScriptData('foo'));
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

        $this->assertSame(json_encode($expectedScriptData), (new WooCommerceSubscriber('https://foo.com', 'https://assets.com/assets/new_uuid'))->fixAssetUrlPathsInCachedScriptData(json_encode($scriptData)));
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

        $this->assertSame(json_encode($expectedScriptData), (new WooCommerceSubscriber('https://foo.com', 'https://foo.com/assets/new_uuid'))->fixAssetUrlPathsInCachedScriptData(json_encode($scriptData)));
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
        ];

        $this->assertSame($subscribedEvents, $callbacks);
    }
}
