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

namespace Ymir\Plugin\Tests\Unit\Attachment;

use Ymir\Plugin\Attachment\AttachmentFileManager;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

class AttachmentFileManagerTest extends TestCase
{
    use FunctionMockTrait;

    /**
     * @var string
     */
    private $baseDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseDirectory = sys_get_temp_dir().'/ymir-attachment-file-manager-test-'.uniqid();

        mkdir($this->baseDirectory.'/uploads/2026/11', 0777, true);
        mkdir($this->baseDirectory.'/tmp');
    }

    protected function tearDown(): void
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->baseDirectory, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
        }

        rmdir($this->baseDirectory);

        parent::tearDown();
    }

    public function provideInvalidFilesToCopy(): array
    {
        return [
            'file outside the uploads directory' => ['/outside/image.jpg', 'The "/outside/image.jpg" file isn\'t in the "%s/uploads" directory'],
            'missing file' => ['%s/uploads/2026/11/missing.jpg', 'The "%s/uploads/2026/11/missing.jpg" file doesn\'t exist'],
        ];
    }

    public function testCopyToTempDirectoryReusesExistingTempFile(): void
    {
        file_put_contents($this->baseDirectory.'/uploads/2026/11/image.jpg', 'original');

        $fileManager = $this->createFileManager();
        $tempFile = $fileManager->copyToTempDirectory($this->baseDirectory.'/uploads/2026/11/image.jpg');

        file_put_contents($tempFile, 'edited');

        $this->assertSame($tempFile, $fileManager->copyToTempDirectory($this->baseDirectory.'/uploads/2026/11/image.jpg'));
        $this->assertStringEqualsFile($tempFile, 'edited');
    }

    /**
     * @dataProvider provideInvalidFilesToCopy
     */
    public function testCopyToTempDirectoryThrowsExceptionWithInvalidFile(string $file, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf($message, $this->baseDirectory));

        $this->createFileManager()->copyToTempDirectory(sprintf($file, $this->baseDirectory));
    }

    public function testCopyToUploadsDirectoryCopiesTempFileToSameRelativePathReplacingExistingFile(): void
    {
        file_put_contents($this->baseDirectory.'/uploads/2026/11/image.jpg', 'original');
        file_put_contents($this->baseDirectory.'/uploads/2026/11/image-150x150.jpg', 'old thumbnail');

        $fileManager = $this->createFileManager();
        $tempFile = $fileManager->copyToTempDirectory($this->baseDirectory.'/uploads/2026/11/image.jpg');

        $this->assertTrue($fileManager->isInTempDirectory($tempFile));
        $this->assertStringEndsWith('/2026/11/image.jpg', $tempFile);
        $this->assertStringEqualsFile($tempFile, 'original');

        $resizedTempFile = $fileManager->getTempFilePath($this->baseDirectory.'/uploads/2026/11/image-150x150.jpg');
        file_put_contents($resizedTempFile, 'resized');

        $resizedFile = $fileManager->copyToUploadsDirectory($resizedTempFile);

        $this->assertSame($this->baseDirectory.'/uploads/2026/11/image-150x150.jpg', $resizedFile);
        $this->assertStringEqualsFile($resizedFile, 'resized');
    }

    public function testDestructorRemovesTempDirectory(): void
    {
        file_put_contents($this->baseDirectory.'/uploads/2026/11/image.jpg', 'image');

        $fileManager = $this->createFileManager();
        $tempFile = $fileManager->copyToTempDirectory($this->baseDirectory.'/uploads/2026/11/image.jpg');

        $this->assertFileExists($tempFile);

        unset($fileManager);

        $this->assertSame([], array_values(array_diff(scandir($this->baseDirectory.'/tmp'), ['.', '..'])));
    }

    public function testFilePathsMapBetweenTempAndUploadsDirectories(): void
    {
        $fileManager = $this->createFileManager();
        $uploadsFile = $this->baseDirectory.'/uploads/2026/11/image.jpg';
        $tempFile = $fileManager->getTempFilePath($uploadsFile);

        $this->assertStringStartsWith($this->baseDirectory.'/tmp/ymir_', $tempFile);
        $this->assertStringEndsWith('/2026/11/image.jpg', $tempFile);
        $this->assertSame('2026/11/image.jpg', $fileManager->getRelativePath($uploadsFile));
        $this->assertSame('2026/11/image.jpg', $fileManager->getRelativePath($tempFile));
        $this->assertSame($uploadsFile, $fileManager->getUploadsFilePath($tempFile));
        $this->assertSame($tempFile, $fileManager->getTempFilePath($tempFile));
        $this->assertSame($uploadsFile, $fileManager->getUploadsFilePath('2026/11/image.jpg'));
    }

    private function createFileManager(): AttachmentFileManager
    {
        $get_temp_dir = $this->getFunctionMock($this->getNamespace(AttachmentFileManager::class), 'get_temp_dir');
        $get_temp_dir->expects($this->once())
                     ->willReturn($this->baseDirectory.'/tmp/');

        return new AttachmentFileManager($this->baseDirectory.'/uploads');
    }
}
