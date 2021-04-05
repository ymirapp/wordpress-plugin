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

namespace Ymir\Plugin\Tests\Integration\CloudStorage;

use PHPUnit\Framework\TestCase;
use Ymir\Plugin\CloudProvider\Aws\S3Client;
use Ymir\Plugin\CloudStorage\CloudStorageStreamWrapper;
use Ymir\Plugin\Http\Client;

/**
 * @covers \Ymir\Plugin\CloudStorage\CloudStorageStreamWrapper
 */
class CloudStorageStreamWrapperS3Test extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = new S3Client(new Client('test'), 'ymir-plugin-test', getenv('AWS_TEST_ACCESS_KEY_ID') ?: $_ENV['AWS_TEST_ACCESS_KEY_ID'], 'us-east-1', getenv('AWS_TEST_SECRET_ACCESS_KEY') ?: $_ENV['AWS_TEST_SECRET_ACCESS_KEY']);

        CloudStorageStreamWrapper::register($this->client);
    }

    public function testCopyFromLocal()
    {
        $tempFilePath = tempnam(sys_get_temp_dir(), 'ymir-').'.txt';
        $relativePath = '/'.basename($tempFilePath);
        $s3FilePath = 'cloudstorage://'.$relativePath;

        file_put_contents($tempFilePath, 'bar');

        $this->assertFalse(file_exists($s3FilePath));

        copy($tempFilePath, $s3FilePath);

        $this->assertTrue(file_exists($s3FilePath));

        $this->assertSame('bar', file_get_contents($s3FilePath));

        $this->client->deleteObject($relativePath);
    }

    public function testCopyFromS3()
    {
        $filePath = tempnam(sys_get_temp_dir(), 'ymir-');

        unlink($filePath);

        $this->assertFalse(file_exists($filePath));

        copy('cloudstorage:///foo.txt', $filePath);

        $this->assertTrue(file_exists($filePath));

        $this->assertSame("bar\n", file_get_contents($filePath));
    }

    public function testFileExists()
    {
        $this->assertTrue(file_exists('cloudstorage:///foo.txt'));
    }

    public function testIsReadable()
    {
        $this->assertTrue(is_readable('cloudstorage:///foo.txt'));
    }

    public function testMkdirAndRmdir()
    {
        $directoryName = 'directory'.rand();
        $directoryPath = sprintf('/%s', $directoryName);

        $this->assertFalse($this->client->objectExists($directoryPath.'/'));

        mkdir('cloudstorage://'.$directoryPath);

        $this->assertTrue($this->client->objectExists($directoryPath.'/'));

        rmdir('cloudstorage://'.$directoryPath);

        $this->assertFalse($this->client->objectExists($directoryPath.'/'));
    }
}
