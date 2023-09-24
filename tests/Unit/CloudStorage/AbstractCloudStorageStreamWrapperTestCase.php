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

namespace Ymir\Plugin\Tests\Unit\CloudStorage;

use Ymir\Plugin\CloudStorage\AbstractCloudStorageStreamWrapper;
use Ymir\Plugin\Tests\Mock\CloudStorageClientInterfaceMockTrait;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @coversNothing
 */
abstract class AbstractCloudStorageStreamWrapperTestCase extends TestCase
{
    use CloudStorageClientInterfaceMockTrait;
    use FunctionMockTrait;

    public function testDirClosedir()
    {
        $wrapper = $this->getStreamWrapperObject();
        $wrapperReflection = new \ReflectionObject($wrapper);

        $openedDirectoryObjectsReflection = $wrapperReflection->getProperty('openedDirectoryObjects');
        $openedDirectoryObjectsReflection->setAccessible(true);
        $openedDirectoryObjectsReflection->setValue($wrapper, new \ArrayIterator());

        $openedDirectoryPathReflection = $wrapperReflection->getProperty('openedDirectoryPath');
        $openedDirectoryPathReflection->setAccessible(true);
        $openedDirectoryPathReflection->setValue($wrapper, "{$this->getProtocol()}:///directory/");

        $openedDirectoryPrefixReflection = $wrapperReflection->getProperty('openedDirectoryPrefix');
        $openedDirectoryPrefixReflection->setAccessible(true);
        $openedDirectoryPrefixReflection->setValue($wrapper, 'directory/');

        $gc_collect_cycles = $this->getFunctionMock($this->getNamespace($this->getStreamWrapperClass()), 'gc_collect_cycles');
        $gc_collect_cycles->expects($this->once());

        $wrapper->dir_closedir();

        $this->assertNull($openedDirectoryObjectsReflection->getValue($wrapper));
        $this->assertNull($openedDirectoryPathReflection->getValue($wrapper));
        $this->assertNull($openedDirectoryPrefixReflection->getValue($wrapper));
    }

    public function testDirOpendirWithRegularDirectory()
    {
        $client = $this->getCloudStorageClientInterfaceMock();
        $objects = [
            ['Key' => 'directory/foo'],
            ['Key' => 'directory/bar'],
        ];
        $wrapper = $this->getStreamWrapperObject();
        $wrapperReflection = new \ReflectionObject($wrapper);

        $client->expects($this->once())
               ->method('getObjects')
               ->with($this->identicalTo('directory/'))
               ->willReturn($objects);

        $openedDirectoryObjectsReflection = $wrapperReflection->getProperty('openedDirectoryObjects');
        $openedDirectoryObjectsReflection->setAccessible(true);

        $openedDirectoryPathReflection = $wrapperReflection->getProperty('openedDirectoryPath');
        $openedDirectoryPathReflection->setAccessible(true);

        $openedDirectoryPrefixReflection = $wrapperReflection->getProperty('openedDirectoryPrefix');
        $openedDirectoryPrefixReflection->setAccessible(true);

        $this->getStreamWrapperClass()::register($client, new \ArrayObject());

        $this->assertTrue($wrapper->dir_opendir("{$this->getProtocol()}:///directory", 0));

        $openedDirectoryObjects = $openedDirectoryObjectsReflection->getValue($wrapper);

        $this->assertInstanceOf(\ArrayIterator::class, $openedDirectoryObjects);
        $this->assertSame($objects, $openedDirectoryObjects->getArrayCopy());
        $this->assertSame("{$this->getProtocol()}:///directory", $openedDirectoryPathReflection->getValue($wrapper));
        $this->assertSame('directory/', $openedDirectoryPrefixReflection->getValue($wrapper));
    }

    public function testDirOpendirWithWildcard()
    {
        $client = $this->getCloudStorageClientInterfaceMock();
        $objects = [
            ['Key' => 'directory/subdirectory/foo'],
            ['Key' => 'directory/subdirectory/foo-1'],
        ];
        $wrapper = $this->getStreamWrapperObject();
        $wrapperReflection = new \ReflectionObject($wrapper);

        $client->expects($this->once())
               ->method('getObjects')
               ->with($this->identicalTo('directory/subdirectory/file'))
               ->willReturn($objects);

        $openedDirectoryObjectsReflection = $wrapperReflection->getProperty('openedDirectoryObjects');
        $openedDirectoryObjectsReflection->setAccessible(true);

        $openedDirectoryPathReflection = $wrapperReflection->getProperty('openedDirectoryPath');
        $openedDirectoryPathReflection->setAccessible(true);

        $openedDirectoryPrefixReflection = $wrapperReflection->getProperty('openedDirectoryPrefix');
        $openedDirectoryPrefixReflection->setAccessible(true);

        $this->getStreamWrapperClass()::register($client, new \ArrayObject());

        $this->assertTrue($wrapper->dir_opendir("{$this->getProtocol()}:///directory/subdirectory/file*", 0));

        $openedDirectoryObjects = $openedDirectoryObjectsReflection->getValue($wrapper);

        $this->assertInstanceOf(\ArrayIterator::class, $openedDirectoryObjects);
        $this->assertSame($objects, $openedDirectoryObjects->getArrayCopy());
        $this->assertSame("{$this->getProtocol()}:///directory/subdirectory/file*", $openedDirectoryPathReflection->getValue($wrapper));
        $this->assertSame('directory/subdirectory/', $openedDirectoryPrefixReflection->getValue($wrapper));
    }

    public function testDirReaddirWhenOpenedDirectoryObjectIsInvalid()
    {
        $objects = $this->getMockBuilder(\ArrayIterator::class)->getMock();
        $wrapper = $this->getStreamWrapperObject();

        $objects->expects($this->once())
                ->method('valid')
                ->willReturn(false);

        $wrapperReflection = new \ReflectionObject($wrapper);

        $openedDirectoryObjectsReflection = $wrapperReflection->getProperty('openedDirectoryObjects');
        $openedDirectoryObjectsReflection->setAccessible(true);
        $openedDirectoryObjectsReflection->setValue($wrapper, $objects);

        $this->assertFalse($wrapper->dir_readdir());
    }

    public function testDirReaddirWhenOpenedDirectoryObjectIsNull()
    {
        $wrapper = $this->getStreamWrapperObject();

        $this->assertFalse($wrapper->dir_readdir());
    }

    public function testDirReaddirWhenOpenedDirectoryObjectReturnsObjectWithNoKey()
    {
        $objects = $this->getMockBuilder(\ArrayIterator::class)->getMock();
        $wrapper = $this->getStreamWrapperObject();

        $objects->expects($this->once())
                ->method('valid')
                ->willReturn(true);

        $objects->expects($this->once())
                ->method('current')
                ->willReturn([]);

        $wrapperReflection = new \ReflectionObject($wrapper);

        $cacheReflection = $wrapperReflection->getProperty('cache');
        $cacheReflection->setAccessible(true);

        $openedDirectoryObjectsReflection = $wrapperReflection->getProperty('openedDirectoryObjects');
        $openedDirectoryObjectsReflection->setAccessible(true);
        $openedDirectoryObjectsReflection->setValue($wrapper, $objects);

        $this->assertFalse($wrapper->dir_readdir());
    }

    public function testDirReaddirWithLastModified()
    {
        $objects = $this->getMockBuilder(\ArrayIterator::class)->getMock();
        $client = $this->getCloudStorageClientInterfaceMock();
        $wrapper = $this->getStreamWrapperObject();

        $objects->expects($this->once())
                ->method('valid')
                ->willReturn(true);

        $objects->expects($this->once())
                ->method('current')
                ->willReturn(['Key' => 'directory/file.ext', 'LastModified' => '10 September 2000']);

        $objects->expects($this->once())
                ->method('next');

        $wrapperReflection = new \ReflectionObject($wrapper);

        $cacheReflection = $wrapperReflection->getProperty('cache');
        $cacheReflection->setAccessible(true);

        $openedDirectoryObjectsReflection = $wrapperReflection->getProperty('openedDirectoryObjects');
        $openedDirectoryObjectsReflection->setAccessible(true);
        $openedDirectoryObjectsReflection->setValue($wrapper, $objects);

        $openedDirectoryPathReflection = $wrapperReflection->getProperty('openedDirectoryPath');
        $openedDirectoryPathReflection->setAccessible(true);
        $openedDirectoryPathReflection->setValue($wrapper, "{$this->getProtocol()}:///directory/");

        $openedDirectoryPrefixReflection = $wrapperReflection->getProperty('openedDirectoryPrefix');
        $openedDirectoryPrefixReflection->setAccessible(true);
        $openedDirectoryPrefixReflection->setValue($wrapper, 'directory/');

        $this->getStreamWrapperClass()::register($client, new \ArrayObject());

        $expectedStat = [
            0 => 0,  'dev' => 0,
            1 => 0,  'ino' => 0,
            2 => 0100777,  'mode' => 0100777,
            3 => 0,  'nlink' => 0,
            4 => 0,  'uid' => 0,
            5 => 0,  'gid' => 0,
            6 => -1, 'rdev' => -1,
            7 => 0,  'size' => 0,
            8 => 0,  'atime' => 0,
            9 => 968544000,  'mtime' => 968544000,
            10 => 968544000,  'ctime' => 968544000,
            11 => -1, 'blksize' => -1,
            12 => -1, 'blocks' => -1,
        ];

        $this->assertSame('file.ext', $wrapper->dir_readdir());
        $this->assertSame(["{$this->getProtocol()}:///directory/file.ext" => $expectedStat], $cacheReflection->getValue($wrapper)->getArrayCopy());
    }

    public function testDirReaddirWithNoLastModifiedOrSize()
    {
        $objects = $this->getMockBuilder(\ArrayIterator::class)->getMock();
        $client = $this->getCloudStorageClientInterfaceMock();
        $wrapper = $this->getStreamWrapperObject();

        $objects->expects($this->once())
               ->method('valid')
               ->willReturn(true);

        $objects->expects($this->once())
                ->method('current')
                ->willReturn(['Key' => 'directory/file.ext']);

        $objects->expects($this->once())
                ->method('next');

        $wrapperReflection = new \ReflectionObject($wrapper);

        $cacheReflection = $wrapperReflection->getProperty('cache');
        $cacheReflection->setAccessible(true);

        $openedDirectoryObjectsReflection = $wrapperReflection->getProperty('openedDirectoryObjects');
        $openedDirectoryObjectsReflection->setAccessible(true);
        $openedDirectoryObjectsReflection->setValue($wrapper, $objects);

        $openedDirectoryPathReflection = $wrapperReflection->getProperty('openedDirectoryPath');
        $openedDirectoryPathReflection->setAccessible(true);
        $openedDirectoryPathReflection->setValue($wrapper, "{$this->getProtocol()}:///directory/");

        $openedDirectoryPrefixReflection = $wrapperReflection->getProperty('openedDirectoryPrefix');
        $openedDirectoryPrefixReflection->setAccessible(true);
        $openedDirectoryPrefixReflection->setValue($wrapper, 'directory/');

        $this->getStreamWrapperClass()::register($client, new \ArrayObject());

        $expectedStat = [
            0 => 0,  'dev' => 0,
            1 => 0,  'ino' => 0,
            2 => 0100777,  'mode' => 0100777,
            3 => 0,  'nlink' => 0,
            4 => 0,  'uid' => 0,
            5 => 0,  'gid' => 0,
            6 => -1, 'rdev' => -1,
            7 => 0,  'size' => 0,
            8 => 0,  'atime' => 0,
            9 => 0,  'mtime' => 0,
            10 => 0,  'ctime' => 0,
            11 => -1, 'blksize' => -1,
            12 => -1, 'blocks' => -1,
        ];

        $this->assertSame('file.ext', $wrapper->dir_readdir());
        $this->assertSame(["{$this->getProtocol()}:///directory/file.ext" => $expectedStat], $cacheReflection->getValue($wrapper)->getArrayCopy());
    }

    public function testDirReaddirWithSize()
    {
        $objects = $this->getMockBuilder(\ArrayIterator::class)->getMock();
        $client = $this->getCloudStorageClientInterfaceMock();
        $wrapper = $this->getStreamWrapperObject();

        $objects->expects($this->once())
                ->method('valid')
                ->willReturn(true);

        $objects->expects($this->once())
                ->method('current')
                ->willReturn(['Key' => 'directory/file.ext', 'Size' => 42]);

        $objects->expects($this->once())
                ->method('next');

        $wrapperReflection = new \ReflectionObject($wrapper);

        $cacheReflection = $wrapperReflection->getProperty('cache');
        $cacheReflection->setAccessible(true);

        $openedDirectoryObjectsReflection = $wrapperReflection->getProperty('openedDirectoryObjects');
        $openedDirectoryObjectsReflection->setAccessible(true);
        $openedDirectoryObjectsReflection->setValue($wrapper, $objects);

        $openedDirectoryPathReflection = $wrapperReflection->getProperty('openedDirectoryPath');
        $openedDirectoryPathReflection->setAccessible(true);
        $openedDirectoryPathReflection->setValue($wrapper, "{$this->getProtocol()}:///directory/");

        $openedDirectoryPrefixReflection = $wrapperReflection->getProperty('openedDirectoryPrefix');
        $openedDirectoryPrefixReflection->setAccessible(true);
        $openedDirectoryPrefixReflection->setValue($wrapper, 'directory/');

        $this->getStreamWrapperClass()::register($client, new \ArrayObject());

        $expectedStat = [
            0 => 0,  'dev' => 0,
            1 => 0,  'ino' => 0,
            2 => 0100777,  'mode' => 0100777,
            3 => 0,  'nlink' => 0,
            4 => 0,  'uid' => 0,
            5 => 0,  'gid' => 0,
            6 => -1, 'rdev' => -1,
            7 => 42,  'size' => 42,
            8 => 0,  'atime' => 0,
            9 => 0,  'mtime' => 0,
            10 => 0,  'ctime' => 0,
            11 => -1, 'blksize' => -1,
            12 => -1, 'blocks' => -1,
        ];

        $this->assertSame('file.ext', $wrapper->dir_readdir());
        $this->assertSame(["{$this->getProtocol()}:///directory/file.ext" => $expectedStat], $cacheReflection->getValue($wrapper)->getArrayCopy());
    }

    public function testDirRewinddirWithInvalidopenedDirectoryPrefix()
    {
        $wrapper = $this->getStreamWrapperObject();

        $this->assertFalse($wrapper->dir_rewinddir());
    }

    public function testDirRewinddirWithValidopenedDirectoryPrefix()
    {
        $client = $this->getCloudStorageClientInterfaceMock();
        $objects = [
            ['Key' => 'directory/foo'],
            ['Key' => 'directory/bar'],
        ];
        $wrapper = $this->getStreamWrapperObject();
        $wrapperReflection = new \ReflectionObject($wrapper);

        $client->expects($this->once())
               ->method('getObjects')
               ->with($this->identicalTo('directory/'))
               ->willReturn($objects);

        $openedDirectoryObjectsReflection = $wrapperReflection->getProperty('openedDirectoryObjects');
        $openedDirectoryObjectsReflection->setAccessible(true);
        $openedDirectoryObjectsReflection->setValue($wrapper, new \ArrayIterator());

        $openedDirectoryPathReflection = $wrapperReflection->getProperty('openedDirectoryPath');
        $openedDirectoryPathReflection->setAccessible(true);
        $openedDirectoryPathReflection->setValue($wrapper, "{$this->getProtocol()}:///directory/");

        $openedDirectoryPrefixReflection = $wrapperReflection->getProperty('openedDirectoryPrefix');
        $openedDirectoryPrefixReflection->setAccessible(true);
        $openedDirectoryPrefixReflection->setValue($wrapper, 'directory/');

        $this->getStreamWrapperClass()::register($client, new \ArrayObject());

        $this->assertTrue($wrapper->dir_rewinddir());

        $openedDirectoryObjects = $openedDirectoryObjectsReflection->getValue($wrapper);

        $this->assertInstanceOf(\ArrayIterator::class, $openedDirectoryObjects);
        $this->assertSame($objects, $openedDirectoryObjects->getArrayCopy());
        $this->assertSame("{$this->getProtocol()}:///directory/", $openedDirectoryPathReflection->getValue($wrapper));
        $this->assertSame('directory/', $openedDirectoryPrefixReflection->getValue($wrapper));
    }

    public function testMkdirWhenDirectoryDoesntExist()
    {
        $client = $this->getCloudStorageClientInterfaceMock();

        $client->expects($this->once())
               ->method('objectExists')
               ->with($this->identicalTo('/foo/'))
               ->willReturn(false);

        $client->expects($this->once())
               ->method('putObject')
               ->with($this->identicalTo('/foo/'), $this->identicalTo(''));

        $clearstatcache = $this->getFunctionMock($this->getNamespace($this->getStreamWrapperClass()), 'clearstatcache');
        $clearstatcache->expects($this->once())
                       ->with($this->identicalTo(true), $this->identicalTo("{$this->getProtocol()}:///foo"));

        $this->getStreamWrapperClass()::register($client, new \ArrayObject());

        $this->getStreamWrapperObject()->mkdir("{$this->getProtocol()}:///foo", 0777);
    }

    public function testMkdirWhenDirectoryExists()
    {
        $this->expectWarning();
        $this->expectExceptionMessage("Directory \"{$this->getProtocol()}:///foo\" already exists");

        $client = $this->getCloudStorageClientInterfaceMock();

        $client->expects($this->once())
               ->method('objectExists')
               ->with($this->identicalTo('/foo/'))
               ->willReturn(true);

        $this->getStreamWrapperClass()::register($client, new \ArrayObject());

        $this->getStreamWrapperObject()->mkdir("{$this->getProtocol()}:///foo", 0777);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRegisterWithExistingWrapper()
    {
        $cache = new \ArrayObject();
        $client = $this->getCloudStorageClientInterfaceMock();

        $stream_context_get_options = $this->getFunctionMock($this->getNamespace($this->getStreamWrapperClass()), 'stream_context_get_options');
        $stream_context_get_options->expects($this->once())
                                   ->willReturn([]);

        $stream_context_set_default = $this->getFunctionMock($this->getNamespace($this->getStreamWrapperClass()), 'stream_context_set_default');
        $stream_context_set_default->expects($this->once())
                                   ->with($this->identicalTo(["{$this->getProtocol()}" => ['client' => $client, 'cache' => $cache]]));

        $stream_get_wrappers = $this->getFunctionMock($this->getNamespace($this->getStreamWrapperClass()), 'stream_get_wrappers');
        $stream_get_wrappers->expects($this->once())
                            ->willReturn(["{$this->getProtocol()}"]);

        $stream_wrapper_register = $this->getFunctionMock($this->getNamespace($this->getStreamWrapperClass()), 'stream_wrapper_register');
        $stream_wrapper_register->expects($this->once())
                                ->with($this->identicalTo("{$this->getProtocol()}"), $this->identicalTo($this->getStreamWrapperClass()), STREAM_IS_URL);

        $stream_wrapper_unregister = $this->getFunctionMock($this->getNamespace($this->getStreamWrapperClass()), 'stream_wrapper_unregister');
        $stream_wrapper_unregister->expects($this->once())
                                  ->with($this->identicalTo("{$this->getProtocol()}"));

        $this->getStreamWrapperClass()::register($client, $cache);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRegisterWithoutExistingWrapper()
    {
        $cache = new \ArrayObject();
        $client = $this->getCloudStorageClientInterfaceMock();

        $stream_context_get_options = $this->getFunctionMock($this->getNamespace($this->getStreamWrapperClass()), 'stream_context_get_options');
        $stream_context_get_options->expects($this->once())
                                   ->willReturn([]);

        $stream_context_set_default = $this->getFunctionMock($this->getNamespace($this->getStreamWrapperClass()), 'stream_context_set_default');
        $stream_context_set_default->expects($this->once())
                                   ->with($this->identicalTo(["{$this->getProtocol()}" => ['client' => $client, 'cache' => $cache]]));

        $stream_get_wrappers = $this->getFunctionMock($this->getNamespace($this->getStreamWrapperClass()), 'stream_get_wrappers');
        $stream_get_wrappers->expects($this->once())
                            ->willReturn([]);

        $stream_wrapper_register = $this->getFunctionMock($this->getNamespace($this->getStreamWrapperClass()), 'stream_wrapper_register');
        $stream_wrapper_register->expects($this->once())
                                ->with($this->identicalTo("{$this->getProtocol()}"), $this->identicalTo($this->getStreamWrapperClass()), STREAM_IS_URL);

        $stream_wrapper_unregister = $this->getFunctionMock($this->getNamespace($this->getStreamWrapperClass()), 'stream_wrapper_unregister');
        $stream_wrapper_unregister->expects($this->never());

        $this->getStreamWrapperClass()::register($client, $cache);
    }

    public function testRenameSuccessful()
    {
        $client = $this->getCloudStorageClientInterfaceMock();

        $client->expects($this->once())
               ->method('copyObject')
               ->with($this->identicalTo('/foo.txt'), $this->identicalTo('/bar.txt'));

        $client->expects($this->once())
               ->method('deleteObject')
               ->with($this->identicalTo('/foo.txt'));

        $clearstatcache = $this->getFunctionMock($this->getNamespace($this->getStreamWrapperClass()), 'clearstatcache');
        $clearstatcache->expects($this->exactly(2))
                       ->withConsecutive(
                           [$this->identicalTo(true), $this->identicalTo("{$this->getProtocol()}:///foo.txt")],
                           [$this->identicalTo(true), $this->identicalTo("{$this->getProtocol()}:///bar.txt")]
                       );

        $this->getStreamWrapperClass()::register($client, new \ArrayObject());

        $this->getStreamWrapperObject()->rename("{$this->getProtocol()}:///foo.txt", "{$this->getProtocol()}:///bar.txt");
    }

    public function testRmdirWithEmptydirectory()
    {
        $client = $this->getCloudStorageClientInterfaceMock();

        $client->expects($this->once())
               ->method('getObjects')
               ->with($this->identicalTo('/foo/'), $this->identicalTo(2))
               ->willReturn(['/foo/']);

        $client->expects($this->once())
               ->method('deleteObject')
               ->with($this->identicalTo('/foo/'));

        $clearstatcache = $this->getFunctionMock($this->getNamespace($this->getStreamWrapperClass()), 'clearstatcache');
        $clearstatcache->expects($this->once())
                       ->with($this->identicalTo(true), $this->identicalTo("{$this->getProtocol()}:///foo"));

        $this->getStreamWrapperClass()::register($client, new \ArrayObject());

        $this->getStreamWrapperObject()->rmdir("{$this->getProtocol()}:///foo", 0777);
    }

    public function testRmdirWithNonEmptydirectory()
    {
        $this->expectWarning();
        $this->expectExceptionMessage("Directory \"{$this->getProtocol()}:///foo\" isn't empty");

        $client = $this->getCloudStorageClientInterfaceMock();

        $client->expects($this->once())
               ->method('getObjects')
               ->with($this->identicalTo('/foo/'), $this->identicalTo(2))
               ->willReturn(['/foo/', '/foo/bar.txt']);

        $client->expects($this->never())
               ->method('deleteObject');

        $clearstatcache = $this->getFunctionMock($this->getNamespace($this->getStreamWrapperClass()), 'clearstatcache');
        $clearstatcache->expects($this->once())
                       ->with($this->identicalTo(true), $this->identicalTo("{$this->getProtocol()}:///foo"));

        $this->getStreamWrapperClass()::register($client, new \ArrayObject());

        $this->getStreamWrapperObject()->rmdir("{$this->getProtocol()}:///foo", 0777);
    }

    public function testStreamCast()
    {
        $this->assertFalse($this->getStreamWrapperObject()->stream_cast());
    }

    public function testStreamClose()
    {
        $wrapper = $this->getStreamWrapperObject();

        $wrapperReflection = new \ReflectionObject($wrapper);

        $cacheReflection = $wrapperReflection->getProperty('cache');
        $cacheReflection->setAccessible(true);

        $cacheReflection->setValue($wrapper, ['foo']);

        $fclose = $this->getFunctionMock($this->getNamespace($this->getStreamWrapperClass()), 'fclose');
        $fclose->expects($this->once());

        $wrapper->stream_close();

        $this->assertNull($cacheReflection->getValue($wrapper));
    }

    public function testStreamEof()
    {
        $feof = $this->getFunctionMock($this->getNamespace($this->getStreamWrapperClass()), 'feof');
        $feof->expects($this->once())
             ->willReturn(false);

        $this->assertFalse($this->getStreamWrapperObject()->stream_eof());
    }

    public function testStreamFlushWhenNotReading()
    {
        $wrapper = $this->getStreamWrapperObject();

        $wrapperReflection = new \ReflectionObject($wrapper);

        $keyReflection = $wrapperReflection->getProperty('openedStreamObjectKey');
        $keyReflection->setAccessible(true);

        $modeReflection = $wrapperReflection->getProperty('openedStreamMode');
        $modeReflection->setAccessible(true);

        $keyReflection->setValue($wrapper, '/foo.txt');
        $modeReflection->setValue($wrapper, 'w');

        $rewind = $this->getFunctionMock($this->getNamespace($this->getStreamWrapperClass()), 'rewind');
        $rewind->expects($this->once());

        $stream_get_contents = $this->getFunctionMock($this->getNamespace($this->getStreamWrapperClass()), 'stream_get_contents');
        $stream_get_contents->expects($this->once())
                            ->willReturn('foo');

        $client = $this->getCloudStorageClientInterfaceMock();
        $client->expects($this->once())
               ->method('putObject')
               ->with($this->identicalTo('/foo.txt'), $this->identicalTo('foo'), $this->identicalTo($this->getAcl()), $this->identicalTo('text/plain'));

        $this->getStreamWrapperClass()::register($client, new \ArrayObject());

        $this->assertTrue($wrapper->stream_flush());
    }

    public function testStreamFlushWhenReading()
    {
        $wrapper = $this->getStreamWrapperObject();

        $wrapperReflection = new \ReflectionObject($wrapper);

        $modeReflection = $wrapperReflection->getProperty('openedStreamMode');
        $modeReflection->setAccessible(true);

        $modeReflection->setValue($wrapper, 'r');

        $this->assertFalse($wrapper->stream_flush());
    }

    public function testStreamLock()
    {
        $this->assertFalse($this->getStreamWrapperObject()->stream_lock());
    }

    public function testStreamMetadata()
    {
        $this->assertFalse($this->getStreamWrapperObject()->stream_metadata());
    }

    public function testStreamOpenWithInvalidMode()
    {
        $this->expectWarning();
        $this->expectExceptionMessage('"e" mode isn\'t supported. Must be "r", "r+", "w", "a", "a+", "x"');

        $this->getStreamWrapperObject()->stream_open("{$this->getProtocol()}:///foo.txt", 'e');
    }

    public function testStreamOpenWithModeA()
    {
        $client = $this->getCloudStorageClientInterfaceMock();

        $client->expects($this->once())
               ->method('getObject')
               ->with($this->identicalTo('/foo.txt'))
               ->willReturn('foo');

        $client->expects($this->once())
               ->method('putObject')
               ->with($this->identicalTo('/foo.txt'), $this->identicalTo('foo'));

        $fwrite = $this->getFunctionMock($this->getNamespace($this->getStreamWrapperClass()), 'fwrite');
        $fwrite->expects($this->once())
               ->with($this->callback(function ($value) { return is_resource($value); }), $this->identicalTo('foo'));

        $rewind = $this->getFunctionMock($this->getNamespace($this->getStreamWrapperClass()), 'rewind');
        $rewind->expects($this->never());

        $this->getStreamWrapperClass()::register($client, new \ArrayObject());

        $this->getStreamWrapperObject()->stream_open("{$this->getProtocol()}:///foo.txt", 'a');
    }

    public function testStreamOpenWithModeAPlus()
    {
        $client = $this->getCloudStorageClientInterfaceMock();

        $client->expects($this->once())
               ->method('getObject')
               ->with($this->identicalTo('/foo.txt'))
               ->willReturn('foo');

        $client->expects($this->once())
               ->method('putObject')
               ->with($this->identicalTo('/foo.txt'), $this->identicalTo('foo'));

        $fwrite = $this->getFunctionMock($this->getNamespace($this->getStreamWrapperClass()), 'fwrite');
        $fwrite->expects($this->once())
               ->with($this->callback(function ($value) { return is_resource($value); }), $this->identicalTo('foo'));

        $rewind = $this->getFunctionMock($this->getNamespace($this->getStreamWrapperClass()), 'rewind');
        $rewind->expects($this->never());

        $this->getStreamWrapperClass()::register($client, new \ArrayObject());

        $this->getStreamWrapperObject()->stream_open("{$this->getProtocol()}:///foo.txt", 'a+');
    }

    public function testStreamOpenWithModeRAndFileDoesntExist()
    {
        $this->expectWarning();
        $this->expectExceptionMessage('Must have an existing object when opening with mode "r"');

        $client = $this->getCloudStorageClientInterfaceMock();

        $client->expects($this->once())
               ->method('objectExists')
               ->with($this->identicalTo('/foo.txt'))
               ->willReturn(false);

        $fwrite = $this->getFunctionMock($this->getNamespace($this->getStreamWrapperClass()), 'fwrite');
        $fwrite->expects($this->never());

        $this->getStreamWrapperClass()::register($client, new \ArrayObject());

        $this->getStreamWrapperObject()->stream_open("{$this->getProtocol()}:///foo.txt", 'r');
    }

    public function testStreamOpenWithModeRAndFileExists()
    {
        $client = $this->getCloudStorageClientInterfaceMock();

        $client->expects($this->once())
               ->method('objectExists')
               ->with($this->identicalTo('/foo.txt'))
               ->willReturn(true);

        $client->expects($this->once())
               ->method('getObject')
               ->with($this->identicalTo('/foo.txt'))
               ->willReturn('foo');

        $fwrite = $this->getFunctionMock($this->getNamespace($this->getStreamWrapperClass()), 'fwrite');
        $fwrite->expects($this->once())
               ->with($this->callback(function ($value) { return is_resource($value); }), $this->identicalTo('foo'));

        $rewind = $this->getFunctionMock($this->getNamespace($this->getStreamWrapperClass()), 'rewind');
        $rewind->expects($this->once())
               ->with($this->callback(function ($value) { return is_resource($value); }));

        $this->getStreamWrapperClass()::register($client, new \ArrayObject());

        $this->getStreamWrapperObject()->stream_open("{$this->getProtocol()}:///foo.txt", 'r');
    }

    public function testStreamOpenWithModeRPlusAndFileDoesntExist()
    {
        $this->expectWarning();
        $this->expectExceptionMessage('Must have an existing object when opening with mode "r+"');

        $client = $this->getCloudStorageClientInterfaceMock();

        $client->expects($this->once())
               ->method('objectExists')
               ->with($this->identicalTo('/foo.txt'))
               ->willReturn(false);

        $fwrite = $this->getFunctionMock($this->getNamespace($this->getStreamWrapperClass()), 'fwrite');
        $fwrite->expects($this->never());

        $this->getStreamWrapperClass()::register($client, new \ArrayObject());

        $this->getStreamWrapperObject()->stream_open("{$this->getProtocol()}:///foo.txt", 'r+');
    }

    public function testStreamOpenWithModeRPlusAndFileExists()
    {
        $client = $this->getCloudStorageClientInterfaceMock();

        $client->expects($this->once())
               ->method('objectExists')
               ->with($this->identicalTo('/foo.txt'))
               ->willReturn(true);

        $client->expects($this->once())
               ->method('getObject')
               ->with($this->identicalTo('/foo.txt'))
               ->willReturn('foo');

        $fwrite = $this->getFunctionMock($this->getNamespace($this->getStreamWrapperClass()), 'fwrite');
        $fwrite->expects($this->once())
               ->with($this->callback(function ($value) { return is_resource($value); }), $this->identicalTo('foo'));

        $rewind = $this->getFunctionMock($this->getNamespace($this->getStreamWrapperClass()), 'rewind');
        $rewind->expects($this->once())
               ->with($this->callback(function ($value) { return is_resource($value); }));

        $this->getStreamWrapperClass()::register($client, new \ArrayObject());

        $this->getStreamWrapperObject()->stream_open("{$this->getProtocol()}:///foo.txt", 'r+');
    }

    public function testStreamOpenWithModeW()
    {
        $client = $this->getCloudStorageClientInterfaceMock();

        $client->expects($this->once())
               ->method('putObject')
               ->with($this->identicalTo('/foo.txt'), $this->identicalTo(''));

        $fwrite = $this->getFunctionMock($this->getNamespace($this->getStreamWrapperClass()), 'fwrite');
        $fwrite->expects($this->once())
               ->with($this->callback(function ($value) { return is_resource($value); }), $this->identicalTo(''));

        $this->getStreamWrapperClass()::register($client, new \ArrayObject());

        $this->getStreamWrapperObject()->stream_open("{$this->getProtocol()}:///foo.txt", 'w');
    }

    public function testStreamOpenWithModeXAndFileDoesntExist()
    {
        $client = $this->getCloudStorageClientInterfaceMock();

        $client->expects($this->once())
               ->method('objectExists')
               ->with($this->identicalTo('/foo.txt'))
               ->willReturn(false);

        $client->expects($this->once())
               ->method('putObject')
               ->with($this->identicalTo('/foo.txt'), $this->identicalTo(''));

        $fwrite = $this->getFunctionMock($this->getNamespace($this->getStreamWrapperClass()), 'fwrite');
        $fwrite->expects($this->once())
               ->with($this->callback(function ($value) { return is_resource($value); }), $this->identicalTo(''));

        $this->getStreamWrapperClass()::register($client, new \ArrayObject());

        $this->getStreamWrapperObject()->stream_open("{$this->getProtocol()}:///foo.txt", 'x');
    }

    public function testStreamOpenWithModeXAndFileExists()
    {
        $this->expectWarning();
        $this->expectExceptionMessage('Cannot have an existing object when opening with mode "x"');

        $client = $this->getCloudStorageClientInterfaceMock();

        $client->expects($this->once())
               ->method('objectExists')
               ->with($this->identicalTo('/foo.txt'))
               ->willReturn(true);

        $this->getStreamWrapperClass()::register($client, new \ArrayObject());

        $this->getStreamWrapperObject()->stream_open("{$this->getProtocol()}:///foo.txt", 'x');
    }

    public function testStreamRead()
    {
        $fread = $this->getFunctionMock($this->getNamespace($this->getStreamWrapperClass()), 'fread');
        $fread->expects($this->once())
              ->with($this->anything(), $this->identicalTo(3))
              ->willReturn('foo');

        $this->assertSame('foo', $this->getStreamWrapperObject()->stream_read(3));
    }

    public function testStreamSeek()
    {
        $fseek = $this->getFunctionMock($this->getNamespace($this->getStreamWrapperClass()), 'fseek');
        $fseek->expects($this->once())
              ->with($this->anything(), $this->identicalTo(3), $this->identicalTo(SEEK_END))
              ->willReturn(0);

        $this->assertTrue($this->getStreamWrapperObject()->stream_seek(3, SEEK_END));
    }

    public function testStreamStatWhenObjectDoesntExist()
    {
        $client = $this->getCloudStorageClientInterfaceMock();
        $wrapper = $this->getStreamWrapperObject();

        $client->expects($this->once())
               ->method('getObjectDetails')
               ->with($this->identicalTo('/foo.txt'))
               ->willThrowException(new \RuntimeException('Object "/foo.txt" not found'));

        $wrapperReflection = new \ReflectionObject($wrapper);

        $keyReflection = $wrapperReflection->getProperty('openedStreamObjectKey');
        $keyReflection->setAccessible(true);
        $keyReflection->setValue($wrapper, '/foo.txt');

        $this->getStreamWrapperClass()::register($client, new \ArrayObject());

        $this->assertFalse($wrapper->stream_stat());
    }

    public function testStreamStatWithDirectoryWontMakeApiCall()
    {
        $client = $this->getCloudStorageClientInterfaceMock();
        $wrapper = $this->getStreamWrapperObject();

        $client->expects($this->never())
               ->method('getObjectDetails');

        $wrapperReflection = new \ReflectionObject($wrapper);

        $keyReflection = $wrapperReflection->getProperty('openedStreamObjectKey');
        $keyReflection->setAccessible(true);
        $keyReflection->setValue($wrapper, '/directory/');

        $this->getStreamWrapperClass()::register($client, new \ArrayObject());

        $this->assertSame([
            0 => 0,  'dev' => 0,
            1 => 0,  'ino' => 0,
            2 => 0040777,  'mode' => 0040777,
            3 => 0,  'nlink' => 0,
            4 => 0,  'uid' => 0,
            5 => 0,  'gid' => 0,
            6 => -1, 'rdev' => -1,
            7 => 0,  'size' => 0,
            8 => 0,  'atime' => 0,
            9 => 0,  'mtime' => 0,
            10 => 0,  'ctime' => 0,
            11 => -1, 'blksize' => -1,
            12 => -1, 'blocks' => -1,
        ], $wrapper->stream_stat());
    }

    public function testStreamStatWithFileSize()
    {
        $client = $this->getCloudStorageClientInterfaceMock();
        $wrapper = $this->getStreamWrapperObject();

        $client->expects($this->once())
               ->method('getObjectDetails')
               ->with($this->identicalTo('/foo.txt'))
               ->willReturn(['size' => 42]);

        $wrapperReflection = new \ReflectionObject($wrapper);

        $keyReflection = $wrapperReflection->getProperty('openedStreamObjectKey');
        $keyReflection->setAccessible(true);
        $keyReflection->setValue($wrapper, '/foo.txt');

        $this->getStreamWrapperClass()::register($client, new \ArrayObject());

        $this->assertSame([
            0 => 0,  'dev' => 0,
            1 => 0,  'ino' => 0,
            2 => 0100777,  'mode' => 0100777,
            3 => 0,  'nlink' => 0,
            4 => 0,  'uid' => 0,
            5 => 0,  'gid' => 0,
            6 => -1, 'rdev' => -1,
            7 => 42,  'size' => 42,
            8 => 0,  'atime' => 0,
            9 => 0,  'mtime' => 0,
            10 => 0,  'ctime' => 0,
            11 => -1, 'blksize' => -1,
            12 => -1, 'blocks' => -1,
        ], $wrapper->stream_stat());
    }

    public function testStreamStatWithLastModified()
    {
        $client = $this->getCloudStorageClientInterfaceMock();
        $wrapper = $this->getStreamWrapperObject();

        $client->expects($this->once())
               ->method('getObjectDetails')
               ->with($this->identicalTo('/foo.txt'))
               ->willReturn(['last-modified' => '10 September 2000']);

        $wrapperReflection = new \ReflectionObject($wrapper);

        $keyReflection = $wrapperReflection->getProperty('openedStreamObjectKey');
        $keyReflection->setAccessible(true);
        $keyReflection->setValue($wrapper, '/foo.txt');

        $this->getStreamWrapperClass()::register($client, new \ArrayObject());

        $this->assertSame([
            0 => 0,  'dev' => 0,
            1 => 0,  'ino' => 0,
            2 => 0100777,  'mode' => 0100777,
            3 => 0,  'nlink' => 0,
            4 => 0,  'uid' => 0,
            5 => 0,  'gid' => 0,
            6 => -1, 'rdev' => -1,
            7 => 0,  'size' => 0,
            8 => 0,  'atime' => 0,
            9 => 968544000,  'mtime' => 968544000,
            10 => 968544000,  'ctime' => 968544000,
            11 => -1, 'blksize' => -1,
            12 => -1, 'blocks' => -1,
        ], $wrapper->stream_stat());
    }

    public function testStreamStatWithRegularFile()
    {
        $client = $this->getCloudStorageClientInterfaceMock();
        $wrapper = $this->getStreamWrapperObject();

        $client->expects($this->once())
               ->method('getObjectDetails')
               ->with($this->identicalTo('/foo.txt'))
               ->willReturn([]);

        $wrapperReflection = new \ReflectionObject($wrapper);

        $keyReflection = $wrapperReflection->getProperty('openedStreamObjectKey');
        $keyReflection->setAccessible(true);
        $keyReflection->setValue($wrapper, '/foo.txt');

        $this->getStreamWrapperClass()::register($client, new \ArrayObject());

        $this->assertSame([
            0 => 0,  'dev' => 0,
            1 => 0,  'ino' => 0,
            2 => 0100777,  'mode' => 0100777,
            3 => 0,  'nlink' => 0,
            4 => 0,  'uid' => 0,
            5 => 0,  'gid' => 0,
            6 => -1, 'rdev' => -1,
            7 => 0,  'size' => 0,
            8 => 0,  'atime' => 0,
            9 => 0,  'mtime' => 0,
            10 => 0,  'ctime' => 0,
            11 => -1, 'blksize' => -1,
            12 => -1, 'blocks' => -1,
        ], $wrapper->stream_stat());
    }

    public function testStreamTell()
    {
        $ftell = $this->getFunctionMock($this->getNamespace($this->getStreamWrapperClass()), 'ftell');
        $ftell->expects($this->once())
              ->with($this->anything())
              ->willReturn(42);

        $this->assertSame(42, $this->getStreamWrapperObject()->stream_tell());
    }

    public function testStreamWrite()
    {
        $fwrite = $this->getFunctionMock($this->getNamespace($this->getStreamWrapperClass()), 'fwrite');
        $fwrite->expects($this->once())
               ->with($this->anything(), $this->identicalTo('foo'))
               ->willReturn(3);

        $this->assertSame(3, $this->getStreamWrapperObject()->stream_write('foo'));
    }

    public function testUnlink()
    {
        $client = $this->getCloudStorageClientInterfaceMock();

        $client->expects($this->once())
               ->method('deleteObject')
               ->with($this->identicalTo('/foo.txt'));

        $clearstatcache = $this->getFunctionMock($this->getNamespace($this->getStreamWrapperClass()), 'clearstatcache');
        $clearstatcache->expects($this->once())
                       ->with($this->identicalTo(true), $this->identicalTo("{$this->getProtocol()}:///foo.txt"));

        $this->getStreamWrapperClass()::register($client, new \ArrayObject());

        $this->assertTrue($this->getStreamWrapperObject()->unlink("{$this->getProtocol()}:///foo.txt"));
    }

    public function testUrlStatWhenCached()
    {
        $client = $this->getCloudStorageClientInterfaceMock();
        $wrapper = $this->getStreamWrapperObject();

        $wrapperReflection = new \ReflectionObject($wrapper);

        $cacheReflection = $wrapperReflection->getProperty('cache');
        $cacheReflection->setAccessible(true);
        $cacheReflection->setValue($wrapper, new \ArrayObject(["{$this->getProtocol()}:///foo.txt" => ['foo_stat']]));

        $this->getStreamWrapperClass()::register($client, new \ArrayObject());

        $this->assertSame(['foo_stat'], $wrapper->url_stat("{$this->getProtocol()}:///foo.txt", 1));
    }

    public function testUrlStatWhenObjectDoesntExist()
    {
        $client = $this->getCloudStorageClientInterfaceMock();
        $wrapper = $this->getStreamWrapperObject();

        $client->expects($this->once())
               ->method('getObjectDetails')
               ->with($this->identicalTo('/foo.txt'))
               ->willThrowException(new \RuntimeException('Object "/foo.txt" not found'));

        $wrapperReflection = new \ReflectionObject($wrapper);

        $cacheReflection = $wrapperReflection->getProperty('cache');
        $cacheReflection->setAccessible(true);

        $this->getStreamWrapperClass()::register($client, new \ArrayObject());

        $this->assertFalse($wrapper->url_stat("{$this->getProtocol()}:///foo.txt", 1));
        $this->assertSame(["{$this->getProtocol()}:///foo.txt" => false], $cacheReflection->getValue($wrapper)->getArrayCopy());
    }

    public function testUrlStatWithDirectoryWontMakeApiCall()
    {
        $client = $this->getCloudStorageClientInterfaceMock();
        $wrapper = $this->getStreamWrapperObject();

        $client->expects($this->never())
               ->method('getObjectDetails');

        $wrapperReflection = new \ReflectionObject($wrapper);

        $cacheReflection = $wrapperReflection->getProperty('cache');
        $cacheReflection->setAccessible(true);

        $expectedStat = [
            0 => 0,  'dev' => 0,
            1 => 0,  'ino' => 0,
            2 => 0040777,  'mode' => 0040777,
            3 => 0,  'nlink' => 0,
            4 => 0,  'uid' => 0,
            5 => 0,  'gid' => 0,
            6 => -1, 'rdev' => -1,
            7 => 0,  'size' => 0,
            8 => 0,  'atime' => 0,
            9 => 0,  'mtime' => 0,
            10 => 0,  'ctime' => 0,
            11 => -1, 'blksize' => -1,
            12 => -1, 'blocks' => -1,
        ];

        $this->getStreamWrapperClass()::register($client, new \ArrayObject());

        $this->assertSame($expectedStat, $wrapper->url_stat("{$this->getProtocol()}:///directory/", 1));
        $this->assertSame(["{$this->getProtocol()}:///directory/" => $expectedStat], $cacheReflection->getValue($wrapper)->getArrayCopy());
    }

    public function testUrlStatWithFileSize()
    {
        $client = $this->getCloudStorageClientInterfaceMock();
        $wrapper = $this->getStreamWrapperObject();

        $client->expects($this->once())
               ->method('getObjectDetails')
               ->with($this->identicalTo('/foo.txt'))
               ->willReturn(['size' => 42]);

        $wrapperReflection = new \ReflectionObject($wrapper);

        $cacheReflection = $wrapperReflection->getProperty('cache');
        $cacheReflection->setAccessible(true);

        $this->getStreamWrapperClass()::register($client, new \ArrayObject());

        $expectedStat = [
            0 => 0,  'dev' => 0,
            1 => 0,  'ino' => 0,
            2 => 0100777,  'mode' => 0100777,
            3 => 0,  'nlink' => 0,
            4 => 0,  'uid' => 0,
            5 => 0,  'gid' => 0,
            6 => -1, 'rdev' => -1,
            7 => 42,  'size' => 42,
            8 => 0,  'atime' => 0,
            9 => 0,  'mtime' => 0,
            10 => 0,  'ctime' => 0,
            11 => -1, 'blksize' => -1,
            12 => -1, 'blocks' => -1,
        ];

        $this->assertSame($expectedStat, $wrapper->url_stat("{$this->getProtocol()}:///foo.txt", 1));
        $this->assertSame(["{$this->getProtocol()}:///foo.txt" => $expectedStat], $cacheReflection->getValue($wrapper)->getArrayCopy());
    }

    public function testUrlStatWithLastModified()
    {
        $client = $this->getCloudStorageClientInterfaceMock();
        $wrapper = $this->getStreamWrapperObject();

        $client->expects($this->once())
               ->method('getObjectDetails')
               ->with($this->identicalTo('/foo.txt'))
               ->willReturn(['last-modified' => '10 September 2000']);

        $wrapperReflection = new \ReflectionObject($wrapper);

        $cacheReflection = $wrapperReflection->getProperty('cache');
        $cacheReflection->setAccessible(true);

        $this->getStreamWrapperClass()::register($client, new \ArrayObject());

        $expectedStat = [
            0 => 0,  'dev' => 0,
            1 => 0,  'ino' => 0,
            2 => 0100777,  'mode' => 0100777,
            3 => 0,  'nlink' => 0,
            4 => 0,  'uid' => 0,
            5 => 0,  'gid' => 0,
            6 => -1, 'rdev' => -1,
            7 => 0,  'size' => 0,
            8 => 0,  'atime' => 0,
            9 => 968544000,  'mtime' => 968544000,
            10 => 968544000,  'ctime' => 968544000,
            11 => -1, 'blksize' => -1,
            12 => -1, 'blocks' => -1,
        ];

        $this->assertSame($expectedStat, $wrapper->url_stat("{$this->getProtocol()}:///foo.txt", 1));
        $this->assertSame(["{$this->getProtocol()}:///foo.txt" => $expectedStat], $cacheReflection->getValue($wrapper)->getArrayCopy());
    }

    public function testUrlStatWithRegularFile()
    {
        $client = $this->getCloudStorageClientInterfaceMock();
        $wrapper = $this->getStreamWrapperObject();

        $client->expects($this->once())
               ->method('getObjectDetails')
               ->with($this->identicalTo('/foo.txt'))
               ->willReturn([]);

        $wrapperReflection = new \ReflectionObject($wrapper);

        $cacheReflection = $wrapperReflection->getProperty('cache');
        $cacheReflection->setAccessible(true);

        $this->getStreamWrapperClass()::register($client, new \ArrayObject());

        $expectedStat = [
            0 => 0,  'dev' => 0,
            1 => 0,  'ino' => 0,
            2 => 0100777,  'mode' => 0100777,
            3 => 0,  'nlink' => 0,
            4 => 0,  'uid' => 0,
            5 => 0,  'gid' => 0,
            6 => -1, 'rdev' => -1,
            7 => 0,  'size' => 0,
            8 => 0,  'atime' => 0,
            9 => 0,  'mtime' => 0,
            10 => 0,  'ctime' => 0,
            11 => -1, 'blksize' => -1,
            12 => -1, 'blocks' => -1,
        ];

        $this->assertSame($expectedStat, $wrapper->url_stat("{$this->getProtocol()}:///foo.txt", 1));
        $this->assertSame(["{$this->getProtocol()}:///foo.txt" => $expectedStat], $cacheReflection->getValue($wrapper)->getArrayCopy());
    }

    abstract protected function getAcl(): string;

    abstract protected function getStreamWrapperClass(): string;

    private function getProtocol(): string
    {
        return $this->getStreamWrapperObject()::getProtocol();
    }

    private function getStreamWrapperObject(): AbstractCloudStorageStreamWrapper
    {
        $class = $this->getStreamWrapperClass();

        return new $class();
    }
}
