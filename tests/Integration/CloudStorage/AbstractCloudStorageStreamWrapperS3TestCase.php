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
use Ymir\Plugin\Http\CurlClient;

/**
 * @coversNothing
 */
abstract class AbstractCloudStorageStreamWrapperS3TestCase extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = new S3Client(new CurlClient('test'), 'ymir-plugin-test', getenv('AWS_TEST_ACCESS_KEY_ID') ?: $_ENV['AWS_TEST_ACCESS_KEY_ID'], 'us-east-1', getenv('AWS_TEST_SECRET_ACCESS_KEY') ?: $_ENV['AWS_TEST_SECRET_ACCESS_KEY']);

        $this->getStreamWrapper()::register($this->client);
    }

    public function testCopyFromLocal()
    {
        $tempFilePath = tempnam(sys_get_temp_dir(), 'ymir-').'.txt';
        $relativePath = '/'.basename($tempFilePath);
        $s3FilePath = "{$this->getProtocol()}://".$relativePath;

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

        copy("{$this->getProtocol()}:///foo.txt", $filePath);

        $this->assertTrue(file_exists($filePath));

        $this->assertSame("bar\n", file_get_contents($filePath));
    }

    public function testFileExistsAfterCreatingEmptyFile()
    {
        $relativePath = '/'.basename(tempnam(sys_get_temp_dir(), 'ymir-').'.txt');
        $s3FilePath = "{$this->getProtocol()}://".$relativePath;

        $this->assertFalse(file_exists($s3FilePath));

        file_put_contents($s3FilePath, '');

        $this->assertTrue(file_exists($s3FilePath));

        $this->client->deleteObject($relativePath);
    }

    public function testFileExistsWithExistingFile()
    {
        $this->assertTrue(file_exists("{$this->getProtocol()}:///foo.txt"));
    }

    public function testIsReadable()
    {
        $this->assertTrue(is_readable("{$this->getProtocol()}:///foo.txt"));
    }

    public function testMkdirAndRmdir()
    {
        $directoryName = 'directory'.rand();
        $directoryPath = sprintf('/%s', $directoryName);
        $directoryFullPath = "{$this->getProtocol()}://".$directoryPath;

        $this->assertFalse($this->client->objectExists($directoryPath.'/'));

        mkdir($directoryFullPath);

        $this->assertTrue($this->client->objectExists($directoryPath.'/'));

        rmdir($directoryFullPath);

        $this->assertFalse($this->client->objectExists($directoryPath.'/'));
    }

    public function testTouchCreatesEmptyFileIfFileDoesntExist()
    {
        $relativePath = '/'.basename(tempnam(sys_get_temp_dir(), 'ymir-').'.txt');
        $s3FilePath = "{$this->getProtocol()}://".$relativePath;

        $this->assertFalse(file_exists($s3FilePath));

        $this->assertTrue(touch($s3FilePath));

        $this->assertTrue(file_exists($s3FilePath));
        $this->assertSame('', file_get_contents($s3FilePath));

        $this->client->deleteObject($relativePath);
    }

    public function testTouchDoesNothingIfFileExists()
    {
        $relativePath = '/'.basename(tempnam(sys_get_temp_dir(), 'ymir-').'.txt');
        $s3FilePath = "{$this->getProtocol()}://".$relativePath;

        $this->assertFalse(file_exists($s3FilePath));

        file_put_contents($s3FilePath, 'foo');

        $this->assertTrue(file_exists($s3FilePath));

        $this->assertTrue(touch($s3FilePath));

        $this->assertSame('foo', file_get_contents($s3FilePath));

        $this->client->deleteObject($relativePath);
    }

    public function testTruncateExistingFile()
    {
        $relativePath = '/'.basename(tempnam(sys_get_temp_dir(), 'ymir-').'.txt');
        $s3FilePath = "{$this->getProtocol()}://".$relativePath;

        $this->assertFalse(file_exists($s3FilePath));

        file_put_contents($s3FilePath, 'tests');

        $this->assertSame(5, filesize($s3FilePath));

        $file = fopen($s3FilePath, 'a');
        $stat = fstat($file);

        ftruncate($file, $stat['size'] - 1);
        fclose($file);

        $this->assertSame('test', file_get_contents($s3FilePath));
        $this->assertSame(4, filesize($s3FilePath));

        $this->client->deleteObject($relativePath);
    }

    public function testTruncateNewFile()
    {
        $relativePath = '/'.basename(tempnam(sys_get_temp_dir(), 'ymir-').'.txt');
        $s3FilePath = "{$this->getProtocol()}://".$relativePath;

        $this->assertFalse(file_exists($s3FilePath));

        $file = fopen($s3FilePath, 'w');

        fwrite($file, 'tests');
        $stat = fstat($file);

        $this->assertSame(5, $stat['size']);

        ftruncate($file, $stat['size'] - 1);
        fclose($file);

        $this->assertSame('test', file_get_contents($s3FilePath));
        $this->assertSame(4, filesize($s3FilePath));

        $this->client->deleteObject($relativePath);
    }

    abstract protected function getStreamWrapper(): string;

    private function getProtocol(): string
    {
        return $this->getStreamWrapper()::getProtocol();
    }
}
