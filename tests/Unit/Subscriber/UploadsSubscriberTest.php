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

use PHPUnit\Framework\TestCase;
use Ymir\Plugin\Subscriber\UploadsSubscriber;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;

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
            'upload_dir' => 'replaceUploadDirectories',
            'upload_size_limit' => 'overrideUploadSizeLimit',
            '_wp_relative_upload_path' => ['useFileManagerForRelativePath', 10, 2],
        ];

        $this->assertSame($subscribedEvents, $callbacks);
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
