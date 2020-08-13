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

use PHPUnit\Framework\Error\Warning;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ymir\Plugin\CloudStorage\CloudStorageStreamWrapper;
use Ymir\Plugin\Tests\Mock\CloudStorageClientInterfaceMockTrait;

/**
 * @covers \Ymir\Plugin\CloudStorage\CloudStorageStreamWrapper
 */
class CloudStorageStreamWrapperPhpTest extends TestCase
{
    use CloudStorageClientInterfaceMockTrait;

    /**
     * @var MockObject
     */
    private $client;

    public function setUp()
    {
        $this->client = $this->getCloudStorageClientInterfaceMock();

        CloudStorageStreamWrapper::register($this->client);
    }

    public function testAppendsToExistingFile()
    {
        $this->client->expects($this->once())
                     ->method('getObject')
                     ->with($this->identicalTo('/file'))
                     ->willReturn('test');

        $this->client->expects($this->exactly(2))
                     ->method('putObject')
                     ->withConsecutive(
                         [$this->identicalTo('/file'), $this->identicalTo('test')],
                         [$this->identicalTo('/file'), $this->identicalTo('testing'), $this->identicalTo('')]
                     );

        $file = fopen('cloudstorage:///file', 'a');

        $this->assertEquals(4, ftell($file));
        $this->assertEquals(3, fwrite($file, 'ing'));
        $this->assertTrue(fclose($file));
    }

    public function testAppendsToNonExistentFile()
    {
        $this->client->expects($this->once())
                     ->method('getObject')
                     ->with($this->identicalTo('/file'))
                     ->willThrowException(new \RuntimeException('Object "/file" not found'));

        $this->client->expects($this->once())
                     ->method('putObject')
                     ->with($this->identicalTo('/file'), $this->identicalTo(''));

        $file = fopen('cloudstorage:///file', 'a');

        $this->assertEquals(0, ftell($file));
        $this->assertTrue(fclose($file));
    }

    public function testDoesNotErrorOnFileExists()
    {
        $this->client->expects($this->once())
                     ->method('objectExists')
                     ->with($this->identicalTo('/file'))
                     ->willReturn(false);

        $this->assertFileNotExists('cloudstorage:///file');
    }

    public function testDoesNotErrorOnIsLink()
    {
        $this->client->expects($this->once())
                     ->method('objectExists')
                     ->with($this->identicalTo('/file'))
                     ->willReturn(false);

        $this->assertFalse(is_link('cloudstorage:///file'));
    }

    public function testFileType()
    {
        $this->client->expects($this->exactly(2))
                     ->method('objectExists')
                     ->withConsecutive(
                         [$this->identicalTo('/file')],
                         [$this->identicalTo('/directory/')]
                     )
                     ->willReturn(true);

        $this->client->expects($this->exactly(2))
                     ->method('getObjectDetails')
                     ->withConsecutive(
                         [$this->identicalTo('/file')],
                         [$this->identicalTo('/directory/')]
                     )
                     ->willReturnOnConsecutiveCalls(
                         ['size' => 5],
                         ['size' => 0]
                     );

        $this->assertSame('file', filetype('cloudstorage:///file'));
        $this->assertSame('dir', filetype('cloudstorage:///directory/'));
    }

    public function testFopenWhenFileDoesntExist()
    {
        $this->expectException(Warning::class);
        $this->expectExceptionMessage('Must have an existing object when opening with mode "r"');

        $this->client->expects($this->once())
                     ->method('objectExists')
                     ->with($this->identicalTo('/file'))
                     ->willReturn(false);

        fopen('cloudstorage:///file', 'r');
    }

    public function testFopenWithUnsupportedMode()
    {
        $this->expectException(Warning::class);
        $this->expectExceptionMessage('"c" mode isn\'t supported. Must be "r", "w", "a", "x"');

        fopen('cloudstorage:///file', 'c');
    }

    public function testFopenWithXMode()
    {
        $this->client->expects($this->once())
                     ->method('objectExists')
                     ->with($this->identicalTo('/file'))
                     ->willReturn(false);

        $this->client->expects($this->once())
                     ->method('putObject')
                     ->with($this->identicalTo('/file'), $this->identicalTo(''));

        fopen('cloudstorage:///file', 'x');
    }

    public function testFopenWithXModeAndExistingFile()
    {
        $this->expectException(Warning::class);
        $this->expectExceptionMessage('Cannot have an existing object when opening with mode "x"');

        $this->client->expects($this->once())
                     ->method('objectExists')
                     ->with($this->identicalTo('/file'))
                     ->willReturn(true);

        fopen('cloudstorage:///file', 'x');
    }

    public function testGuessContentType()
    {
        $this->client->expects($this->exactly(2))
                     ->method('putObject')
                     ->withConsecutive(
                         [$this->identicalTo('/file.xml'), $this->identicalTo(''), $this->identicalTo('')],
                         [$this->identicalTo('/file.xml'), $this->identicalTo('test'), $this->identicalTo('application/xml')]
                     );

        file_put_contents('cloudstorage:///file.xml', 'test');
    }

    public function testMkdirCreatesObject()
    {
        $this->client->expects($this->once())
                     ->method('objectExists')
                     ->with($this->identicalTo('/directory/'))
                     ->willReturn(false);

        $this->client->expects($this->once())
                     ->method('putObject')
                     ->with($this->identicalTo('/directory/'), $this->identicalTo(''));

        $this->assertTrue(mkdir('cloudstorage:///directory'));
    }

    public function testMkdirWithExistingDirectory()
    {
        $this->expectException(Warning::class);
        $this->expectExceptionMessage('Directory "cloudstorage:///directory" already exists');

        $this->client->expects($this->once())
                     ->method('objectExists')
                     ->with($this->identicalTo('/directory/'))
                     ->willReturn(true);

        $this->assertFalse(mkdir('cloudstorage:///directory'));
    }

    public function testReadingFile()
    {
        $this->client->expects($this->exactly(2))
                     ->method('objectExists')
                     ->with($this->identicalTo('/file'))
                     ->willReturn(true);

        $this->client->expects($this->once())
                     ->method('getObject')
                     ->with($this->identicalTo('/file'))
                     ->willReturn('testing 123');

        $file = fopen('cloudstorage:///file', 'r');

        $this->assertEquals(0, ftell($file));
        $this->assertFalse(feof($file));
        $this->assertEquals('test', fread($file, 4));
        $this->assertEquals(4, ftell($file));
        $this->assertEquals(0, fseek($file, 0));
        $this->assertEquals('testing 123', stream_get_contents($file));
        $this->assertTrue(feof($file));
        $this->assertTrue(fclose($file));
    }

    public function testRegistersStreamWrapperOnlyOnce()
    {
        $this->assertContains(CloudStorageStreamWrapper::PROTOCOL, stream_get_wrappers());

        CloudStorageStreamWrapper::register($this->client);

        $this->assertContains(CloudStorageStreamWrapper::PROTOCOL, stream_get_wrappers());
    }

    public function testRenameSuccessful()
    {
        $this->client->expects($this->once())
                     ->method('copyObject')
                     ->with($this->identicalTo('/file'), $this->identicalTo('/newfile.txt'));

        $this->client->expects($this->once())
                     ->method('deleteObject')
                     ->with($this->identicalTo('/file'));

        $this->assertTrue(rename('cloudstorage:///file', 'cloudstorage:///newfile.txt'));
    }

    public function testRenameWhenCopyObjectThrowsException()
    {
        $this->expectException(Warning::class);
        $this->expectExceptionMessage('Could not copy object "/file"');

        $this->client->expects($this->once())
                     ->method('copyObject')
                     ->with($this->identicalTo('/file'), $this->identicalTo('/newfile.txt'))
                     ->willThrowException(new \RuntimeException('Could not copy object "/file"'));

        $this->assertFalse(rename('cloudstorage:///file', 'cloudstorage:///newfile.txt'));
    }

    public function testRenameWhenDeleteObjectThrowsException()
    {
        $this->expectException(Warning::class);
        $this->expectExceptionMessage('Unable to delete object "/file"');

        $this->client->expects($this->once())
                     ->method('copyObject')
                     ->with($this->identicalTo('/file'), $this->identicalTo('/newfile.txt'));

        $this->client->expects($this->once())
                     ->method('deleteObject')
                     ->with($this->identicalTo('/file'))
                    ->willThrowException(new \RuntimeException('Unable to delete object "/file"'));

        $this->assertFalse(rename('cloudstorage:///file', 'cloudstorage:///newfile.txt'));
    }

    public function testRenameWithDifferentProtocols()
    {
        $this->expectException(Warning::class);
        $this->expectExceptionMessage('rename(): Cannot rename a file across wrapper types');

        $this->assertFalse(rename('cloudstorage:///file', 'php://temp'));
    }

    public function testReturnsStreamSizeFromHeaders()
    {
        $this->client->expects($this->exactly(2))
                     ->method('objectExists')
                     ->with($this->identicalTo('/file'))
                     ->willReturn(true);

        $this->client->expects($this->once())
                     ->method('getObject')
                     ->with($this->identicalTo('/file'))
                     ->willReturn('testing 123');

        $this->client->expects($this->once())
                     ->method('getObjectDetails')
                     ->with($this->identicalTo('/file'))
                     ->willReturn(['size' => 5]);

        $resource = fopen('cloudstorage:///file', 'r');

        $this->assertEquals(5, fstat($resource)['size']);
    }

    public function testRmdirCanDeleteNestedDirectory()
    {
        $this->client->expects($this->once())
                     ->method('getObjects')
                     ->with($this->identicalTo('/directory/subdirectory/'), $this->identicalTo(2))
                     ->willReturn(['/directory/subdirectory/']);

        $this->client->expects($this->once())
                     ->method('deleteObject')
                     ->with($this->identicalTo('/directory/subdirectory/'));

        $this->assertTrue(rmdir('cloudstorage:///directory/subdirectory'));
    }

    public function testRmdirWhenDeleteObjectThrowsException()
    {
        $this->expectException(Warning::class);
        $this->expectExceptionMessage('Unable to delete object "/directory/"');

        $this->client->expects($this->once())
                     ->method('getObjects')
                     ->with($this->identicalTo('/directory/'), $this->identicalTo(2))
                     ->willReturn(['/directory/']);

        $this->client->expects($this->once())
                     ->method('deleteObject')
                     ->with($this->identicalTo('/directory/'))
                     ->willThrowException(new \RuntimeException('Unable to delete object "/directory/"'));

        $this->assertFalse(rmdir('cloudstorage:///directory'));
    }

    public function testRmdirWhenGetObjectsReturnsMoreThanOneObject()
    {
        $this->expectException(Warning::class);
        $this->expectExceptionMessage('Directory "cloudstorage:///directory" isn\'t empty');

        $this->client->expects($this->once())
                     ->method('getObjects')
                     ->with($this->identicalTo('/directory/'), $this->identicalTo(2))
                     ->willReturn(['/directory/', '/directory/file']);

        $this->client->expects($this->never())
                     ->method('deleteObject');

        $this->assertFalse(rmdir('cloudstorage:///directory'));
    }

    public function testRmdirWithNothing()
    {
        $this->expectException(Warning::class);
        $this->expectExceptionMessage('Cannot delete root directory');

        $this->assertFalse(rmdir('cloudstorage://'));
    }

    public function testStatWithProtocol()
    {
        clearstatcache(false, 'cloudstorage://');
        $stat = stat('cloudstorage://');

        $this->assertEquals(0040777, $stat['mode']);
    }

    public function testStreamCastReturnsFalse()
    {
        $this->expectException(Warning::class);
        $this->expectExceptionMessage('stream_select(): cannot represent a stream of type user-space as a select()able descriptor');

        $this->client->expects($this->once())
                     ->method('objectExists')
                     ->with($this->identicalTo('/file'))
                     ->willReturn(true);

        $this->client->expects($this->once())
                     ->method('getObject')
                     ->with($this->identicalTo('/file'))
                     ->willReturn('testing 123');

        $read = [fopen('cloudstorage:///file', 'r')];
        $write = $except = null;

        $this->assertFalse(stream_select($read, $write, $except, 0));
    }

    public function testThrowsExceptionWhenContextHasNoClient()
    {
        $this->expectException(Warning::class);
        $this->expectExceptionMessage('No cloud storage client found in the stream contex');

        fopen('cloudstorage:///file', 'r', false, stream_context_create([
            'cloudstorage' => ['client' => null],
        ]));
    }

    public function testUnlink()
    {
        $this->client->expects($this->once())
                     ->method('deleteObject')
                     ->with($this->identicalTo('/file'));

        $this->assertTrue(unlink('cloudstorage:///file'));
    }

    public function testUnlinkWhenDeleteObjectThrowsException()
    {
        $this->expectException(Warning::class);
        $this->expectExceptionMessage('Unable to delete object "/file"');

        $this->client->expects($this->once())
                     ->method('deleteObject')
                     ->with($this->identicalTo('/file'))
                     ->willThrowException(new \RuntimeException('Unable to delete object "/file"'));

        $this->assertFalse(unlink('cloudstorage:///file'));
    }

    public function testUrlStatDataClearedOnWrite()
    {
        $this->client->expects($this->exactly(2))
                     ->method('objectExists')
                     ->with($this->identicalTo('/file'))
                     ->willReturn(true);

        $this->client->expects($this->exactly(2))
                     ->method('getObjectDetails')
                     ->with($this->identicalTo('/file'))
                     ->willReturnOnConsecutiveCalls(['size' => 124], ['size' => 125]);

        $this->client->expects($this->exactly(2))
                     ->method('putObject')
                     ->withConsecutive(
                         [$this->identicalTo('/file'), $this->identicalTo(''), $this->identicalTo('')],
                         [$this->identicalTo('/file'), $this->identicalTo('test'), $this->identicalTo('')]
                     );

        $this->assertEquals(124, filesize('cloudstorage:///file'));

        file_put_contents('cloudstorage:///file', 'test');

        $this->assertEquals(125, filesize('cloudstorage:///file'));
    }

    public function testUrlStatReturnsObjectDetails()
    {
        $time = strtotime('now');

        $this->client->expects($this->once())
                     ->method('objectExists')
                     ->with($this->identicalTo('/file'))
                     ->willReturn(true);

        $this->client->expects($this->once())
                     ->method('getObjectDetails')
                     ->with($this->identicalTo('/file'))
                     ->willReturn(['size' => 5, 'last-modified' => gmdate('r', $time)]);

        clearstatcache(false, 'cloudstorage:///file');
        $stat = stat('cloudstorage:///file');

        $this->assertEquals(0100777, $stat['mode']);
        $this->assertEquals(5, $stat['size']);
        $this->assertEquals($time, $stat['mtime']);
        $this->assertEquals($time, $stat['ctime']);
    }

    public function testUrlStatUsesCacheData()
    {
        $this->client->expects($this->once())
                     ->method('objectExists')
                     ->with($this->identicalTo('/file'))
                     ->willReturn(true);

        $this->client->expects($this->once())
                     ->method('getObjectDetails')
                     ->with($this->identicalTo('/file'))
                     ->willReturn(['size' => 124]);

        $this->assertEquals(124, filesize('cloudstorage:///file'));
        $this->assertEquals(124, filesize('cloudstorage:///file'));
    }

    public function testUrlStatWhenGetObjectDetailsThrowsException()
    {
        $this->expectException(Warning::class);
        $this->expectExceptionMessage('Object "/file" not found');

        $this->client->expects($this->once())
                     ->method('objectExists')
                     ->with($this->identicalTo('/file'))
                     ->willReturn(true);

        $this->client->expects($this->once())
                     ->method('getObjectDetails')
                     ->with($this->identicalTo('/file'))
                     ->willThrowException(new \RuntimeException('Object "/file" not found'));

        $this->assertFalse(filesize('cloudstorage:///file'));
    }

    public function testWritingEmptyFile()
    {
        $this->client->expects($this->once())
                     ->method('putObject')
                     ->with($this->identicalTo('/file'), $this->identicalTo(''));

        $file = fopen('cloudstorage:///file', 'w');

        $this->assertEquals(0, fwrite($file, ''));
        $this->assertTrue(fclose($file));
    }

    public function testWritingFile()
    {
        $this->client->expects($this->exactly(2))
                     ->method('putObject')
                     ->withConsecutive(
                         [$this->identicalTo('/file'), $this->identicalTo('')],
                         [$this->identicalTo('/file'), $this->identicalTo('test'), $this->identicalTo('')]
                     );

        $file = fopen('cloudstorage:///file', 'w');

        $this->assertEquals(4, fwrite($file, 'test'));
        $this->assertTrue(fclose($file));
    }

    public function testWritingFileWhenPutObjectThrowsException()
    {
        $this->expectException(Warning::class);
        $this->expectExceptionMessage('Unable to save object "/file"');

        $this->client->expects($this->at(0))
                     ->method('putObject')
                     ->with($this->identicalTo('/file'), $this->identicalTo(''));

        $this->client->expects($this->at(1))
                     ->method('putObject')
                     ->with($this->identicalTo('/file'), $this->identicalTo('test'), $this->identicalTo(''))
                     ->willThrowException(new \RuntimeException('Unable to save object "/file"'));

        $file = fopen('cloudstorage:///file', 'w');

        fwrite($file, 'test');
        fclose($file);
    }
}
