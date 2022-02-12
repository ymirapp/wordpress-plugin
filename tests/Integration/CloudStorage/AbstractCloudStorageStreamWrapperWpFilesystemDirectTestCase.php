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
use Ymir\Plugin\Http\Client;

/**
 * @coversNothing
 */
abstract class AbstractCloudStorageStreamWrapperWpFilesystemDirectTestCase extends TestCase
{
    private $client;

    private $filesystem;

    protected function setUp(): void
    {
        $this->client = new S3Client(new Client('test'), 'ymir-plugin-test', getenv('AWS_TEST_ACCESS_KEY_ID') ?: $_ENV['AWS_TEST_ACCESS_KEY_ID'], 'us-east-1', getenv('AWS_TEST_SECRET_ACCESS_KEY') ?: $_ENV['AWS_TEST_SECRET_ACCESS_KEY']);
        $this->filesystem = new \WP_Filesystem_Direct(null);

        $this->getStreamWrapper()::register($this->client);
    }

    public function testFile()
    {
        $filePath = "{$this->getProtocol()}:///file".rand().'.txt';

        $this->assertFalse($this->filesystem->is_file($filePath));

        $this->assertTrue($this->filesystem->put_contents($filePath, 'test', 0555));

        $this->assertTrue($this->filesystem->is_file($filePath));

        $this->assertTrue($this->filesystem->delete($filePath));

        $this->assertFalse($this->filesystem->is_file($filePath));
    }

    public function testMkdirAndRmdir()
    {
        $directoryName = 'directory/subdirectory'.rand();
        $directoryPath = sprintf('/%s', $directoryName);
        $directoryFullPath = "{$this->getProtocol()}://".$directoryPath;

        $this->assertFalse($this->client->objectExists($directoryPath.'/'));

        $this->assertTrue($this->filesystem->mkdir($directoryFullPath, 0755));

        $this->assertTrue($this->client->objectExists($directoryPath.'/'));

        $this->assertTrue($this->filesystem->rmdir($directoryFullPath));

        $this->assertFalse($this->client->objectExists($directoryPath.'/'));
    }

    abstract protected function getStreamWrapper(): string;

    private function getProtocol(): string
    {
        return $this->getStreamWrapper()::getProtocol();
    }
}
