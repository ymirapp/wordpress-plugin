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

namespace Ymir\Plugin\Tests\Unit\Subscriber;

use Ymir\Plugin\Subscriber\UploadsSubscriber;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\Subscriber\UploadsSubscriber
 */
class UploadsSubscriberTest extends TestCase
{
    use FunctionMockTrait;

    public function testConstructorWithInvalidUploadSizeLimit()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"uploadSizeLimit" needs to be a numeric value or a string');

        $subscriber = new UploadsSubscriber('content_dir', 'content_url', 'cloudstorage_dir', 'upload_url', ['test']);
    }

    public function testConstructorWithNumericUploadSizeLimit()
    {
        $subscriber = new UploadsSubscriber('content_dir', 'content_url', 'cloudstorage_dir', 'upload_url', 15);

        $subscriberReflection = new \ReflectionObject($subscriber);

        $uploadSizeLimitReflection = $subscriberReflection->getProperty('uploadSizeLimit');
        $uploadSizeLimitReflection->setAccessible(true);

        $this->assertSame(15, $uploadSizeLimitReflection->getValue($subscriber));
    }

    public function testConstructorWithStringUploadSizeLimit()
    {
        $wp_convert_hr_to_bytes = $this->getFunctionMock($this->getNamespace(UploadsSubscriber::class), 'wp_convert_hr_to_bytes');
        $wp_convert_hr_to_bytes->expects($this->once())
                               ->with($this->identicalTo('15MB'))
                               ->willReturn(15);

        $subscriber = new UploadsSubscriber('content_dir', 'content_url', 'cloudstorage_dir', 'upload_url', '15MB');

        $subscriberReflection = new \ReflectionObject($subscriber);

        $uploadSizeLimitReflection = $subscriberReflection->getProperty('uploadSizeLimit');
        $uploadSizeLimitReflection->setAccessible(true);

        $this->assertSame(15, $uploadSizeLimitReflection->getValue($subscriber));
    }

    public function testGetSubscribedEvents()
    {
        $callbacks = UploadsSubscriber::getSubscribedEvents();

        foreach ($callbacks as $callback) {
            $this->assertTrue(method_exists(UploadsSubscriber::class, is_array($callback) ? $callback[0] : $callback));
        }

        $subscribedEvents = [
            'pre_wp_unique_filename_file_list' => ['getUniqueFilenameList', 10, 3],
            'pre_option_upload_path' => 'disableOption',
            'pre_option_upload_url_path' => 'disableOption',
            'upload_dir' => 'replaceUploadDirectories',
            'upload_size_limit' => 'overrideUploadSizeLimit',
            '_wp_relative_upload_path' => ['useFileManagerForRelativePath', 10, 2],
        ];

        $this->assertSame($subscribedEvents, $callbacks);
    }

    public function testGetUniqueFilenameListWithCloudStorageDirectoryNoMatchingDirectory()
    {
        $this->assertNull((new UploadsSubscriber('content_dir', 'content_url', 'cloudstorage_dir', 'upload_url'))->getUniqueFilenameList(null, 'directory', 'filename'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetUniqueFilenameListWithPathinfoReturnsFalse()
    {
        $pathinfo = $this->getFunctionMock($this->getNamespace(UploadsSubscriber::class), 'pathinfo');
        $pathinfo->expects($this->once())
                 ->with($this->identicalTo('filename'), $this->identicalTo(PATHINFO_FILENAME))
                 ->willReturn(false);

        $this->assertNull((new UploadsSubscriber('content_dir', 'content_url', 'cloudstorage_dir', 'upload_url'))->getUniqueFilenameList(null, 'cloudstorage_dir/directory', 'filename'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetUniqueFilenameListWithScandirReturnsFalse()
    {
        $scandir = $this->getFunctionMock($this->getNamespace(UploadsSubscriber::class), 'scandir');
        $scandir->expects($this->once())
                ->with($this->identicalTo('cloudstorage_dir/directory/filename*'))
                ->willReturn(false);

        $this->assertNull((new UploadsSubscriber('content_dir', 'content_url', 'cloudstorage_dir', 'upload_url'))->getUniqueFilenameList(null, 'cloudstorage_dir/directory', 'filename'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetUniqueFilenameListWithScandirReturnsFileList()
    {
        $scandir = $this->getFunctionMock($this->getNamespace(UploadsSubscriber::class), 'scandir');
        $scandir->expects($this->once())
                ->with($this->identicalTo('cloudstorage_dir/directory/filename*'))
                ->willReturn(['filename']);

        $this->assertSame(['filename'], (new UploadsSubscriber('content_dir', 'content_url', 'cloudstorage_dir', 'upload_url'))->getUniqueFilenameList(null, 'cloudstorage_dir/directory', 'filename'));
    }

    public function testOverrideUploadSizeLimitWithLimit()
    {
        $this->assertSame(15, (new UploadsSubscriber('content_dir', 'content_url', 'cloudstorage_dir', 'upload_url', 15))->overrideUploadSizeLimit(10));
    }

    public function testOverrideUploadSizeLimitWithNoLimit()
    {
        $this->assertSame(10, (new UploadsSubscriber('content_dir', 'content_url', 'cloudstorage_dir', 'upload_url'))->overrideUploadSizeLimit(10));
    }

    public function testReplaceUploadDirectoriesReplacesBaseDir()
    {
        $this->assertSame([
            'basedir' => 'cloudstorage_dir/foo',
        ], (new UploadsSubscriber('content_dir', 'content_url', 'cloudstorage_dir', 'upload_url'))->replaceUploadDirectories([
            'basedir' => 'content_dir/foo',
        ]));
    }

    public function testReplaceUploadDirectoriesReplacesBaseUrl()
    {
        $this->assertSame([
            'baseurl' => 'upload_url/foo',
        ], (new UploadsSubscriber('content_dir', 'content_url', 'cloudstorage_dir', 'upload_url'))->replaceUploadDirectories([
            'baseurl' => 'content_url/foo',
        ]));
    }

    public function testReplaceUploadDirectoriesReplacesPath()
    {
        $this->assertSame([
            'path' => 'cloudstorage_dir/foo',
        ], (new UploadsSubscriber('content_dir', 'content_url', 'cloudstorage_dir', 'upload_url'))->replaceUploadDirectories([
            'path' => 'content_dir/foo',
        ]));
    }

    public function testReplaceUploadDirectoriesReplacesUrl()
    {
        $this->assertSame([
            'url' => 'upload_url/foo',
        ], (new UploadsSubscriber('content_dir', 'content_url', 'cloudstorage_dir', 'upload_url'))->replaceUploadDirectories([
            'url' => 'content_url/foo',
        ]));
    }

    public function testReplaceUploadDirectoriesWithNoCloudstorageDirectory()
    {
        $this->assertSame([
            'basedir' => 'content_dir/foo',
            'path' => 'content_dir/foo',
        ], (new UploadsSubscriber('content_dir', 'content_url'))->replaceUploadDirectories([
            'basedir' => 'content_dir/foo',
            'path' => 'content_dir/foo',
        ]));
    }

    public function testReplaceUploadDirectoriesWithNoUploadUrl()
    {
        $this->assertSame([
            'baseurl' => 'content_url/foo',
            'url' => 'content_url/foo',
        ], (new UploadsSubscriber('content_dir', 'content_url', 'cloudstorage_dir'))->replaceUploadDirectories([
            'baseurl' => 'content_url/foo',
            'url' => 'content_url/foo',
        ]));
    }
}
