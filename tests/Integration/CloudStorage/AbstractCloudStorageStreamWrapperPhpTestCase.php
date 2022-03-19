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

use PHPUnit\Framework\Constraint\FileExists;
use PHPUnit\Framework\Constraint\LogicalNot;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ymir\Plugin\Tests\Mock\CloudStorageClientInterfaceMockTrait;

/**
 * @coversNothing
 */
abstract class AbstractCloudStorageStreamWrapperPhpTestCase extends TestCase
{
    use CloudStorageClientInterfaceMockTrait;

    /**
     * @var MockObject
     */
    protected $client;

    protected function setUp(): void
    {
        $this->client = $this->getCloudStorageClientInterfaceMock();

        $this->getStreamWrapper()::register($this->client, new \ArrayObject());
    }

    public function testAppendsToExistingFile()
    {
        $this->client->expects($this->once())
                     ->method('getObject')
                     ->with($this->identicalTo('/file.ext'))
                     ->willReturn('test');

        $this->client->expects($this->exactly(2))
                     ->method('putObject')
                     ->withConsecutive(
                         [$this->identicalTo('/file.ext'), $this->identicalTo('test')],
                         [$this->identicalTo('/file.ext'), $this->identicalTo('testing'), $this->identicalTo('')]
                     );

        $file = fopen("{$this->getProtocol()}:///file.ext", 'a');

        $this->assertEquals(4, ftell($file));
        $this->assertEquals(3, fwrite($file, 'ing'));
        $this->assertTrue(fclose($file));
    }

    public function testAppendsToNonExistentFile()
    {
        $this->client->expects($this->once())
                     ->method('getObject')
                     ->with($this->identicalTo('/file.ext'))
                     ->willThrowException(new \RuntimeException('Object "/file" not found'));

        $this->client->expects($this->once())
                     ->method('putObject')
                     ->with($this->identicalTo('/file.ext'), $this->identicalTo(''));

        $file = fopen("{$this->getProtocol()}:///file.ext", 'a');

        $this->assertEquals(0, ftell($file));
        $this->assertTrue(fclose($file));
    }

    public function testDoesNotErrorOnFileExists()
    {
        $this->client->expects($this->once())
                     ->method('getObjectDetails')
                     ->with($this->identicalTo('/file.ext'))
                     ->willThrowException(new \RuntimeException('Object "/file" not found'));

        // Fix compatibility between PHPUnit 8.5 and 9.5
        $this->assertThat("{$this->getProtocol()}:///file.ext", new LogicalNot(new FileExists()));
    }

    public function testDoesNotErrorOnIsLink()
    {
        $this->client->expects($this->once())
                     ->method('getObjectDetails')
                     ->with($this->identicalTo('/file.ext'))
                     ->willThrowException(new \RuntimeException('Object "/file" not found'));

        $this->assertFalse(is_link("{$this->getProtocol()}:///file.ext"));
    }

    public function testFileType()
    {
        $this->client->expects($this->once())
                     ->method('getObjectDetails')
                     ->withConsecutive(
                         [$this->identicalTo('/file.ext')],
                         [$this->identicalTo('/directory/')]
                     )
                     ->willReturnOnConsecutiveCalls(
                         ['size' => 5],
                         ['size' => 0]
                     );

        $this->assertSame('file', filetype("{$this->getProtocol()}:///file.ext"));
        $this->assertSame('dir', filetype("{$this->getProtocol()}:///directory/"));
    }

    public function testFopenWhenFileDoesntExist()
    {
        $this->expectWarning();
        $this->expectExceptionMessage('Must have an existing object when opening with mode "r"');

        $this->client->expects($this->once())
                     ->method('objectExists')
                     ->with($this->identicalTo('/file.ext'))
                     ->willReturn(false);

        fopen("{$this->getProtocol()}:///file.ext", 'r');
    }

    public function testFopenWithUnsupportedMode()
    {
        $this->expectWarning();
        $this->expectExceptionMessage('"c" mode isn\'t supported. Must be "r", "w", "a", "a+", "x"');

        fopen("{$this->getProtocol()}:///file.ext", 'c');
    }

    public function testFopenWithXMode()
    {
        $this->client->expects($this->once())
                     ->method('objectExists')
                     ->with($this->identicalTo('/file.ext'))
                     ->willReturn(false);

        $this->client->expects($this->once())
                     ->method('putObject')
                     ->with($this->identicalTo('/file.ext'), $this->identicalTo(''));

        fopen("{$this->getProtocol()}:///file.ext", 'x');
    }

    public function testFopenWithXModeAndExistingFile()
    {
        $this->expectWarning();
        $this->expectExceptionMessage('Cannot have an existing object when opening with mode "x"');

        $this->client->expects($this->once())
                     ->method('objectExists')
                     ->with($this->identicalTo('/file.ext'))
                     ->willReturn(true);

        fopen("{$this->getProtocol()}:///file.ext", 'x');
    }

    public function testGuessContentType()
    {
        $this->client->expects($this->exactly(2))
                     ->method('putObject')
                     ->withConsecutive(
                         [$this->identicalTo('/file.xml'), $this->identicalTo(''), $this->identicalTo('')],
                         [$this->identicalTo('/file.xml'), $this->identicalTo('test'), $this->identicalTo('application/xml')]
                     );

        file_put_contents("{$this->getProtocol()}:///file.xml", 'test');
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

        $this->assertTrue(mkdir("{$this->getProtocol()}:///directory"));
    }

    public function testMkdirWithExistingDirectory()
    {
        $this->expectWarning();
        $this->expectExceptionMessage("Directory \"{$this->getProtocol()}:///directory\" already exists");

        $this->client->expects($this->once())
                     ->method('objectExists')
                     ->with($this->identicalTo('/directory/'))
                     ->willReturn(true);

        $this->assertFalse(mkdir("{$this->getProtocol()}:///directory"));
    }

    public function testPrioritizesInternalStreamSizeOverSizeFromCloudStorage()
    {
        $this->client->expects($this->once())
                     ->method('objectExists')
                     ->with($this->identicalTo('/file.ext'))
                     ->willReturn(true);

        $this->client->expects($this->once())
                     ->method('getObject')
                     ->with($this->identicalTo('/file.ext'))
                     ->willReturn('testing 123');

        $this->client->expects($this->once())
                     ->method('getObjectDetails')
                     ->with($this->identicalTo('/file.ext'))
                     ->willReturn(['size' => 5]);

        $resource = fopen("{$this->getProtocol()}:///file.ext", 'r');

        $this->assertEquals(11, fstat($resource)['size']);
    }

    public function testReaddirCachesStatValue()
    {
        $this->client->expects($this->once())
                     ->method('getObjects')
                     ->with($this->identicalTo('directory/'))
                     ->willReturn([
                         ['Key' => 'directory/foo.ext', 'Size' => 1],
                         ['Key' => 'directory/bar.ext', 'Size' => 2],
                     ]);

        $directory = "{$this->getProtocol()}:///directory";
        $opendir = opendir($directory);

        $this->assertIsResource($opendir);

        $file1 = readdir($opendir);
        $this->assertEquals('foo.ext', $file1);
        $this->assertEquals(1, filesize($directory.$file1));

        $file2 = readdir($opendir);
        $this->assertEquals('bar.ext', $file2);
        $this->assertEquals(2, filesize($directory.$file2));

        closedir($opendir);
    }

    public function testReadingDirectory()
    {
        $this->client->expects($this->once())
                     ->method('getObjects')
                     ->with($this->identicalTo('directory/'))
                     ->willReturn([
                         ['Key' => 'directory/a.ext', 'Size' => 1],
                         ['Key' => 'directory/b.ext', 'Size' => 2],
                         ['Key' => 'directory/c.ext', 'Size' => 3],
                         ['Key' => 'directory/d.ext', 'Size' => 4],
                         ['Key' => 'directory/e.ext', 'Size' => 5],
                         ['Key' => 'directory/f.ext', 'Size' => 6],
                         ['Key' => 'directory/g.ext', 'Size' => 7],
                     ]);

        $directory = "{$this->getProtocol()}:///directory";
        $opendir = opendir($directory);

        $this->assertIsResource($opendir);

        $files = [];
        while (false !== ($file = readdir($opendir))) {
            $files[] = $file;
        }

        $expected = ['a.ext', 'b.ext', 'c.ext', 'd.ext', 'e.ext', 'f.ext', 'g.ext'];
        $this->assertEquals($expected, $files);

        $this->assertSame(4, filesize($directory.'d.ext'));
        $this->assertSame(6, filesize($directory.'f.ext'));

        closedir($opendir);
    }

    public function testReadingFile()
    {
        $this->client->expects($this->once())
                     ->method('objectExists')
                     ->with($this->identicalTo('/file.ext'))
                     ->willReturn(true);

        $this->client->expects($this->once())
                     ->method('getObject')
                     ->with($this->identicalTo('/file.ext'))
                     ->willReturn('testing 123');

        $file = fopen("{$this->getProtocol()}:///file.ext", 'r');

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
        $this->assertContains($this->getStreamWrapper()::getProtocol(), stream_get_wrappers());

        $this->getStreamWrapper()::register($this->client);

        $this->assertContains($this->getStreamWrapper()::getProtocol(), stream_get_wrappers());
    }

    public function testRenameSuccessful()
    {
        $this->client->expects($this->once())
                     ->method('copyObject')
                     ->with($this->identicalTo('/file.ext'), $this->identicalTo('/newfile.txt'));

        $this->client->expects($this->once())
                     ->method('deleteObject')
                     ->with($this->identicalTo('/file.ext'));

        $this->assertTrue(rename("{$this->getProtocol()}:///file.ext", "{$this->getProtocol()}:///newfile.txt"));
    }

    public function testRenameWhenCopyObjectThrowsException()
    {
        $this->expectWarning();
        $this->expectExceptionMessage('Could not copy object "/file"');

        $this->client->expects($this->once())
                     ->method('copyObject')
                     ->with($this->identicalTo('/file.ext'), $this->identicalTo('/newfile.txt'))
                     ->willThrowException(new \RuntimeException('Could not copy object "/file"'));

        $this->assertFalse(rename("{$this->getProtocol()}:///file.ext", "{$this->getProtocol()}:///newfile.txt"));
    }

    public function testRenameWhenDeleteObjectThrowsException()
    {
        $this->expectWarning();
        $this->expectExceptionMessage('Unable to delete object "/file"');

        $this->client->expects($this->once())
                     ->method('copyObject')
                     ->with($this->identicalTo('/file.ext'), $this->identicalTo('/newfile.txt'));

        $this->client->expects($this->once())
                     ->method('deleteObject')
                     ->with($this->identicalTo('/file.ext'))
                    ->willThrowException(new \RuntimeException('Unable to delete object "/file"'));

        $this->assertFalse(rename("{$this->getProtocol()}:///file.ext", "{$this->getProtocol()}:///newfile.txt"));
    }

    public function testRenameWithDifferentProtocols()
    {
        $this->expectWarning();
        $this->expectExceptionMessage('rename(): Cannot rename a file across wrapper types');

        $this->assertFalse(rename("{$this->getProtocol()}:///file.ext", 'php://temp'));
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

        $this->assertTrue(rmdir("{$this->getProtocol()}:///directory/subdirectory"));
    }

    public function testRmdirWhenDeleteObjectThrowsException()
    {
        $this->expectWarning();
        $this->expectExceptionMessage('Unable to delete object "/directory/"');

        $this->client->expects($this->once())
                     ->method('getObjects')
                     ->with($this->identicalTo('/directory/'), $this->identicalTo(2))
                     ->willReturn(['/directory/']);

        $this->client->expects($this->once())
                     ->method('deleteObject')
                     ->with($this->identicalTo('/directory/'))
                     ->willThrowException(new \RuntimeException('Unable to delete object "/directory/"'));

        $this->assertFalse(rmdir("{$this->getProtocol()}:///directory"));
    }

    public function testRmdirWhenGetObjectsReturnsMoreThanOneObject()
    {
        $this->expectWarning();
        $this->expectExceptionMessage("Directory \"{$this->getProtocol()}:///directory\" isn't empty");

        $this->client->expects($this->once())
                     ->method('getObjects')
                     ->with($this->identicalTo('/directory/'), $this->identicalTo(2))
                     ->willReturn(['/directory/', '/directory/file.ext']);

        $this->client->expects($this->never())
                     ->method('deleteObject');

        $this->assertFalse(rmdir("{$this->getProtocol()}:///directory"));
    }

    public function testRmdirWithNothing()
    {
        $this->expectWarning();
        $this->expectExceptionMessage('Cannot delete root directory');

        $this->assertFalse(rmdir("{$this->getProtocol()}://"));
    }

    public function testScandirWithRegularDirectory()
    {
        $this->client->expects($this->once())
            ->method('getObjects')
            ->with($this->identicalTo('directory/'))
            ->willReturn([
                ['Key' => 'directory/foo'],
                ['Key' => 'directory/bar'],
            ]);

        $this->assertSame(['bar', 'foo'], scandir("{$this->getProtocol()}:///directory"));
    }

    public function testScandirWithWildcard()
    {
        $this->client->expects($this->once())
                     ->method('getObjects')
                     ->with($this->identicalTo('directory/subdirectory/file'))
                     ->willReturn([
                         ['Key' => 'directory/subdirectory/foo'],
                         ['Key' => 'directory/subdirectory/foo-1'],
                     ]);

        $this->assertSame(['foo', 'foo-1'], scandir("{$this->getProtocol()}:///directory/subdirectory/file*"));
    }

    public function testStatWithProtocol()
    {
        clearstatcache(false, "{$this->getProtocol()}://");
        $stat = stat("{$this->getProtocol()}://");

        $this->assertEquals(0040777, $stat['mode']);
    }

    public function testStreamCastReturnsFalse()
    {
        if (\PHP_VERSION_ID >= 80000) {
            // This test throws an exception on PHP 8 with the message "No stream arrays were passed". This is due
            // to "stream_cast" returning false. It seems to tell "stream_select" that the fopen resource is invalid
            // now. The AWS SDK still doesn't run its test suite in PHP 8 so unsure what the real impact is or how to
            // fix it properly.
            $this->markTestSkipped('Test broken on PHP 8.0');
        }

        $this->expectWarning();
        $this->expectExceptionMessage('stream_select(): cannot represent a stream of type user-space as a select()able descriptor');

        $this->client->expects($this->once())
                     ->method('objectExists')
                     ->with($this->identicalTo('/file.ext'))
                     ->willReturn(true);

        $this->client->expects($this->once())
                     ->method('getObject')
                     ->with($this->identicalTo('/file.ext'))
                     ->willReturn('testing 123');

        $read = [fopen("{$this->getProtocol()}:///file.ext", 'r')];
        $write = $except = null;

        stream_select($read, $write, $except, 0);
    }

    public function testThrowsExceptionWhenContextHasNoClient()
    {
        $this->expectWarning();
        $this->expectExceptionMessage('No cloud storage client found in the stream context');

        fopen("{$this->getProtocol()}:///file.ext", 'r', false, stream_context_create([
            $this->getProtocol() => ['client' => null],
        ]));
    }

    public function testTruncatesFile()
    {
        $this->client->expects($this->once())
                     ->method('getObject')
                     ->with($this->identicalTo('/file.ext'))
                     ->willReturn('testing');

        $this->client->expects($this->exactly(2))
                     ->method('putObject')
                     ->withConsecutive(
                         [$this->identicalTo('/file.ext'), $this->identicalTo('testing')],
                         [$this->identicalTo('/file.ext'), $this->identicalTo('test'), $this->identicalTo('')]
                     );

        $file = fopen("{$this->getProtocol()}:///file.ext", 'a');

        $this->assertTrue(ftruncate($file, 4));
        $this->assertTrue(fclose($file));
    }

    public function testUnlink()
    {
        $this->client->expects($this->once())
                     ->method('deleteObject')
                     ->with($this->identicalTo('/file.ext'));

        $this->assertTrue(unlink("{$this->getProtocol()}:///file.ext"));
    }

    public function testUnlinkWhenDeleteObjectThrowsException()
    {
        $this->expectWarning();
        $this->expectExceptionMessage('Unable to delete object "/file"');

        $this->client->expects($this->once())
                     ->method('deleteObject')
                     ->with($this->identicalTo('/file.ext'))
                     ->willThrowException(new \RuntimeException('Unable to delete object "/file"'));

        $this->assertFalse(unlink("{$this->getProtocol()}:///file.ext"));
    }

    public function testUrlStatDataClearedOnWrite()
    {
        $this->client->expects($this->exactly(2))
                     ->method('getObjectDetails')
                     ->with($this->identicalTo('/file.ext'))
                     ->willReturnOnConsecutiveCalls(['size' => 124], ['size' => 125]);

        $this->client->expects($this->exactly(2))
                     ->method('putObject')
                     ->withConsecutive(
                         [$this->identicalTo('/file.ext'), $this->identicalTo(''), $this->identicalTo('')],
                         [$this->identicalTo('/file.ext'), $this->identicalTo('test'), $this->identicalTo('')]
                     );

        $this->assertEquals(124, filesize("{$this->getProtocol()}:///file.ext"));

        file_put_contents("{$this->getProtocol()}:///file.ext", 'test');

        $this->assertEquals(125, filesize("{$this->getProtocol()}:///file.ext"));
    }

    public function testUrlStatMakesNoApiCallsForDirectories()
    {
        $this->client->expects($this->never())
                     ->method('getObjectDetails');

        clearstatcache(false, "{$this->getProtocol()}:///directory");
        $stat = stat("{$this->getProtocol()}:///directory");

        $this->assertEquals(0040777, $stat['mode']);
        $this->assertEquals(0, $stat['size']);
        $this->assertEquals(0, $stat['mtime']);
        $this->assertEquals(0, $stat['ctime']);
    }

    public function testUrlStatReturnsObjectDetails()
    {
        $time = strtotime('now');

        $this->client->expects($this->once())
                     ->method('getObjectDetails')
                     ->with($this->identicalTo('/file.ext'))
                     ->willReturn(['size' => 5, 'last-modified' => gmdate('r', $time)]);

        clearstatcache(false, "{$this->getProtocol()}:///file.ext");
        $stat = stat("{$this->getProtocol()}:///file.ext");

        $this->assertEquals(0100777, $stat['mode']);
        $this->assertEquals(5, $stat['size']);
        $this->assertEquals($time, $stat['mtime']);
        $this->assertEquals($time, $stat['ctime']);
    }

    public function testUrlStatUsesCacheData()
    {
        $this->client->expects($this->once())
                     ->method('getObjectDetails')
                     ->with($this->identicalTo('/file.ext'))
                     ->willReturn(['size' => 124]);

        $this->assertEquals(124, filesize("{$this->getProtocol()}:///file.ext"));
        $this->assertEquals(124, filesize("{$this->getProtocol()}:///file.ext"));
    }

    public function testUrlStatWhenGetObjectDetailsThrowsException()
    {
        $this->expectWarning();
        $this->expectExceptionMessage("filesize(): stat failed for {$this->getProtocol()}:///file.ext");

        $this->client->expects($this->once())
                     ->method('getObjectDetails')
                     ->with($this->identicalTo('/file.ext'))
                     ->willThrowException(new \RuntimeException('Object "/file" not found'));

        $this->assertFalse(filesize("{$this->getProtocol()}:///file.ext"));
    }

    public function testWritingEmptyFile()
    {
        $this->client->expects($this->once())
                     ->method('putObject')
                     ->with($this->identicalTo('/file.ext'), $this->identicalTo(''));

        $file = fopen("{$this->getProtocol()}:///file.ext", 'w');

        $this->assertEquals(0, fwrite($file, ''));
        $this->assertTrue(fclose($file));
    }

    public function testWritingFile()
    {
        $this->client->expects($this->exactly(2))
                     ->method('putObject')
                     ->withConsecutive(
                         [$this->identicalTo('/file.ext'), $this->identicalTo('')],
                         [$this->identicalTo('/file.ext'), $this->identicalTo('test'), $this->identicalTo('')]
                     );

        $file = fopen("{$this->getProtocol()}:///file.ext", 'w');

        $this->assertEquals(4, fwrite($file, 'test'));
        $this->assertTrue(fclose($file));
    }

    public function testWritingFileWhenPutObjectThrowsException()
    {
        $this->expectWarning();
        $this->expectExceptionMessage('Unable to save object "/file"');

        $this->client->expects($this->exactly(2))
                     ->method('putObject')
                     ->withConsecutive(
                         [$this->identicalTo('/file.ext'), $this->identicalTo('')],
                         [$this->identicalTo('/file.ext'), $this->identicalTo('test'), $this->identicalTo('')]
                     )
                     ->willReturnOnConsecutiveCalls(null, $this->throwException(new \RuntimeException('Unable to save object "/file"')));

        $file = fopen("{$this->getProtocol()}:///file.ext", 'w');

        fwrite($file, 'test');
        fclose($file);
    }

    abstract protected function getStreamWrapper(): string;

    private function getProtocol(): string
    {
        return $this->getStreamWrapper()::getProtocol();
    }
}
