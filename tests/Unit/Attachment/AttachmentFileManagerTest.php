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

    public function testDestructorRemovesTempDirectory(): void
    {
        $get_temp_dir = $this->getFunctionMock($this->getNamespace(AttachmentFileManager::class), 'get_temp_dir');
        $get_temp_dir->expects($this->once())
                     ->willReturn($this->baseDirectory.'/tmp/');

        file_put_contents($this->baseDirectory.'/uploads/2026/11/image.jpg', 'image');

        $fileManager = new AttachmentFileManager($this->baseDirectory.'/uploads');
        $tempFile = $fileManager->copyToTempDirectory($this->baseDirectory.'/uploads/2026/11/image.jpg');

        $this->assertFileExists($tempFile);

        unset($fileManager);

        $this->assertSame([], array_values(array_diff(scandir($this->baseDirectory.'/tmp'), ['.', '..'])));
    }
}
