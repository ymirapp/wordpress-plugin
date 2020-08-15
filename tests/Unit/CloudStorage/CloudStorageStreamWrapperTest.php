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

use PHPUnit\Framework\Error\Warning;
use Ymir\Plugin\CloudStorage\CloudStorageStreamWrapper;
use Ymir\Plugin\Tests\Mock\CloudStorageClientInterfaceMockTrait;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\CloudStorage\CloudStorageStreamWrapper
 */
class CloudStorageStreamWrapperTest extends TestCase
{
    use CloudStorageClientInterfaceMockTrait;
    use FunctionMockTrait;

    public function testDirClosedir()
    {
        $wrapper = new CloudStorageStreamWrapper();
        $wrapperReflection = new \ReflectionObject($wrapper);

        $openedDirectoryObjectsReflection = $wrapperReflection->getProperty('openedDirectoryObjects');
        $openedDirectoryObjectsReflection->setAccessible(true);
        $openedDirectoryObjectsReflection->setValue($wrapper, new \ArrayIterator());

        $openedDirectoryPathReflection = $wrapperReflection->getProperty('openedDirectoryPath');
        $openedDirectoryPathReflection->setAccessible(true);
        $openedDirectoryPathReflection->setValue($wrapper, 'cloudstorage:///directory/');

        $openedDirectoryPrefixReflection = $wrapperReflection->getProperty('openedDirectoryPrefix');
        $openedDirectoryPrefixReflection->setAccessible(true);
        $openedDirectoryPrefixReflection->setValue($wrapper, 'directory/');

        $gc_collect_cycles = $this->getFunctionMock($this->getNamespace(CloudStorageStreamWrapper::class), 'gc_collect_cycles');
        $gc_collect_cycles->expects($this->once());

        $wrapper->dir_closedir();

        $this->assertNull($openedDirectoryObjectsReflection->getValue($wrapper));
        $this->assertNull($openedDirectoryPathReflection->getValue($wrapper));
        $this->assertNull($openedDirectoryPrefixReflection->getValue($wrapper));
    }

    public function testDirOpendir()
    {
        $client = $this->getCloudStorageClientInterfaceMock();
        $objects = [
            ['Key' => 'directory/foo'],
            ['Key' => 'directory/bar'],
        ];
        $wrapper = new CloudStorageStreamWrapper();
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

        CloudStorageStreamWrapper::register($client, new \ArrayObject());

        $this->assertTrue($wrapper->dir_opendir('cloudstorage:///directory', 0));

        $openedDirectoryObjects = $openedDirectoryObjectsReflection->getValue($wrapper);

        $this->assertInstanceOf(\ArrayIterator::class, $openedDirectoryObjects);
        $this->assertSame($objects, $openedDirectoryObjects->getArrayCopy());
        $this->assertSame('cloudstorage:///directory', $openedDirectoryPathReflection->getValue($wrapper));
        $this->assertSame('directory/', $openedDirectoryPrefixReflection->getValue($wrapper));
    }

    public function testDirReaddirWhenOpenedDirectoryObjectIsInvalid()
    {
        $objects = $this->getMockBuilder(\ArrayIterator::class)->getMock();
        $wrapper = new CloudStorageStreamWrapper();

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
        $wrapper = new CloudStorageStreamWrapper();

        $this->assertFalse($wrapper->dir_readdir());
    }

    public function testDirReaddirWhenOpenedDirectoryObjectReturnsObjectWithNoKey()
    {
        $objects = $this->getMockBuilder(\ArrayIterator::class)->getMock();
        $wrapper = new CloudStorageStreamWrapper();

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
        $wrapper = new CloudStorageStreamWrapper();

        $objects->expects($this->once())
                ->method('valid')
                ->willReturn(true);

        $objects->expects($this->once())
                ->method('current')
                ->willReturn(['Key' => 'directory/file', 'LastModified' => '10 September 2000']);

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
        $openedDirectoryPathReflection->setValue($wrapper, 'cloudstorage:///directory/');

        $openedDirectoryPrefixReflection = $wrapperReflection->getProperty('openedDirectoryPrefix');
        $openedDirectoryPrefixReflection->setAccessible(true);
        $openedDirectoryPrefixReflection->setValue($wrapper, 'directory/');

        CloudStorageStreamWrapper::register($client, new \ArrayObject());

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

        $this->assertSame('file', $wrapper->dir_readdir());
        $this->assertSame(['cloudstorage:///directory/file' => $expectedStat], $cacheReflection->getValue($wrapper)->getArrayCopy());
    }

    public function testDirReaddirWithNoLastModifiedOrSize()
    {
        $objects = $this->getMockBuilder(\ArrayIterator::class)->getMock();
        $client = $this->getCloudStorageClientInterfaceMock();
        $wrapper = new CloudStorageStreamWrapper();

        $objects->expects($this->once())
               ->method('valid')
               ->willReturn(true);

        $objects->expects($this->once())
                ->method('current')
                ->willReturn(['Key' => 'directory/file']);

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
        $openedDirectoryPathReflection->setValue($wrapper, 'cloudstorage:///directory/');

        $openedDirectoryPrefixReflection = $wrapperReflection->getProperty('openedDirectoryPrefix');
        $openedDirectoryPrefixReflection->setAccessible(true);
        $openedDirectoryPrefixReflection->setValue($wrapper, 'directory/');

        CloudStorageStreamWrapper::register($client, new \ArrayObject());

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

        $this->assertSame('file', $wrapper->dir_readdir());
        $this->assertSame(['cloudstorage:///directory/file' => $expectedStat], $cacheReflection->getValue($wrapper)->getArrayCopy());
    }

    public function testDirReaddirWithSize()
    {
        $objects = $this->getMockBuilder(\ArrayIterator::class)->getMock();
        $client = $this->getCloudStorageClientInterfaceMock();
        $wrapper = new CloudStorageStreamWrapper();

        $objects->expects($this->once())
                ->method('valid')
                ->willReturn(true);

        $objects->expects($this->once())
                ->method('current')
                ->willReturn(['Key' => 'directory/file', 'Size' => 42]);

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
        $openedDirectoryPathReflection->setValue($wrapper, 'cloudstorage:///directory/');

        $openedDirectoryPrefixReflection = $wrapperReflection->getProperty('openedDirectoryPrefix');
        $openedDirectoryPrefixReflection->setAccessible(true);
        $openedDirectoryPrefixReflection->setValue($wrapper, 'directory/');

        CloudStorageStreamWrapper::register($client, new \ArrayObject());

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

        $this->assertSame('file', $wrapper->dir_readdir());
        $this->assertSame(['cloudstorage:///directory/file' => $expectedStat], $cacheReflection->getValue($wrapper)->getArrayCopy());
    }

    public function testDirRewinddirWithInvalidopenedDirectoryPrefix()
    {
        $wrapper = new CloudStorageStreamWrapper();

        $this->assertFalse($wrapper->dir_rewinddir());
    }

    public function testDirRewinddirWithValidopenedDirectoryPrefix()
    {
        $client = $this->getCloudStorageClientInterfaceMock();
        $objects = [
            ['Key' => 'directory/foo'],
            ['Key' => 'directory/bar'],
        ];
        $wrapper = new CloudStorageStreamWrapper();
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
        $openedDirectoryPathReflection->setValue($wrapper, 'cloudstorage:///directory/');

        $openedDirectoryPrefixReflection = $wrapperReflection->getProperty('openedDirectoryPrefix');
        $openedDirectoryPrefixReflection->setAccessible(true);
        $openedDirectoryPrefixReflection->setValue($wrapper, 'directory/');

        CloudStorageStreamWrapper::register($client, new \ArrayObject());

        $this->assertTrue($wrapper->dir_rewinddir());

        $openedDirectoryObjects = $openedDirectoryObjectsReflection->getValue($wrapper);

        $this->assertInstanceOf(\ArrayIterator::class, $openedDirectoryObjects);
        $this->assertSame($objects, $openedDirectoryObjects->getArrayCopy());
        $this->assertSame('cloudstorage:///directory/', $openedDirectoryPathReflection->getValue($wrapper));
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

        $clearstatcache = $this->getFunctionMock($this->getNamespace(CloudStorageStreamWrapper::class), 'clearstatcache');
        $clearstatcache->expects($this->once())
                       ->with($this->identicalTo(true), $this->identicalTo('cloudstorage:///foo'));

        CloudStorageStreamWrapper::register($client, new \ArrayObject());

        (new CloudStorageStreamWrapper())->mkdir('cloudstorage:///foo', 0777);
    }

    public function testMkdirWhenDirectoryExists()
    {
        $this->expectException(Warning::class);
        $this->expectExceptionMessage('Directory "cloudstorage:///foo" already exists');

        $client = $this->getCloudStorageClientInterfaceMock();

        $client->expects($this->once())
               ->method('objectExists')
               ->with($this->identicalTo('/foo/'))
               ->willReturn(true);

        CloudStorageStreamWrapper::register($client, new \ArrayObject());

        (new CloudStorageStreamWrapper())->mkdir('cloudstorage:///foo', 0777);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRegisterWithExistingWrapper()
    {
        $cache = new \ArrayObject();
        $client = $this->getCloudStorageClientInterfaceMock();

        $stream_context_get_options = $this->getFunctionMock($this->getNamespace(CloudStorageStreamWrapper::class), 'stream_context_get_options');
        $stream_context_get_options->expects($this->once())
                                   ->willReturn([]);

        $stream_context_set_default = $this->getFunctionMock($this->getNamespace(CloudStorageStreamWrapper::class), 'stream_context_set_default');
        $stream_context_set_default->expects($this->once())
                                   ->with($this->identicalTo(['cloudstorage' => ['client' => $client, 'cache' => $cache]]));

        $stream_get_wrappers = $this->getFunctionMock($this->getNamespace(CloudStorageStreamWrapper::class), 'stream_get_wrappers');
        $stream_get_wrappers->expects($this->once())
                            ->willReturn(['cloudstorage']);

        $stream_wrapper_register = $this->getFunctionMock($this->getNamespace(CloudStorageStreamWrapper::class), 'stream_wrapper_register');
        $stream_wrapper_register->expects($this->once())
                                ->with($this->identicalTo('cloudstorage'), $this->identicalTo(CloudStorageStreamWrapper::class), STREAM_IS_URL);

        $stream_wrapper_unregister = $this->getFunctionMock($this->getNamespace(CloudStorageStreamWrapper::class), 'stream_wrapper_unregister');
        $stream_wrapper_unregister->expects($this->once())
                                  ->with($this->identicalTo('cloudstorage'));

        CloudStorageStreamWrapper::register($client, $cache);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRegisterWithoutExistingWrapper()
    {
        $cache = new \ArrayObject();
        $client = $this->getCloudStorageClientInterfaceMock();

        $stream_context_get_options = $this->getFunctionMock($this->getNamespace(CloudStorageStreamWrapper::class), 'stream_context_get_options');
        $stream_context_get_options->expects($this->once())
                                   ->willReturn([]);

        $stream_context_set_default = $this->getFunctionMock($this->getNamespace(CloudStorageStreamWrapper::class), 'stream_context_set_default');
        $stream_context_set_default->expects($this->once())
                                   ->with($this->identicalTo(['cloudstorage' => ['client' => $client, 'cache' => $cache]]));

        $stream_get_wrappers = $this->getFunctionMock($this->getNamespace(CloudStorageStreamWrapper::class), 'stream_get_wrappers');
        $stream_get_wrappers->expects($this->once())
                            ->willReturn([]);

        $stream_wrapper_register = $this->getFunctionMock($this->getNamespace(CloudStorageStreamWrapper::class), 'stream_wrapper_register');
        $stream_wrapper_register->expects($this->once())
                                ->with($this->identicalTo('cloudstorage'), $this->identicalTo(CloudStorageStreamWrapper::class), STREAM_IS_URL);

        $stream_wrapper_unregister = $this->getFunctionMock($this->getNamespace(CloudStorageStreamWrapper::class), 'stream_wrapper_unregister');
        $stream_wrapper_unregister->expects($this->never());

        CloudStorageStreamWrapper::register($client, $cache);
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

        $clearstatcache = $this->getFunctionMock($this->getNamespace(CloudStorageStreamWrapper::class), 'clearstatcache');
        $clearstatcache->expects($this->exactly(2))
                       ->withConsecutive(
                           [$this->identicalTo(true), $this->identicalTo('cloudstorage:///foo.txt')],
                           [$this->identicalTo(true), $this->identicalTo('cloudstorage:///bar.txt')]
                       );

        CloudStorageStreamWrapper::register($client, new \ArrayObject());

        (new CloudStorageStreamWrapper())->rename('cloudstorage:///foo.txt', 'cloudstorage:///bar.txt');
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

        $clearstatcache = $this->getFunctionMock($this->getNamespace(CloudStorageStreamWrapper::class), 'clearstatcache');
        $clearstatcache->expects($this->once())
                       ->with($this->identicalTo(true), $this->identicalTo('cloudstorage:///foo'));

        CloudStorageStreamWrapper::register($client, new \ArrayObject());

        (new CloudStorageStreamWrapper())->rmdir('cloudstorage:///foo', 0777);
    }

    public function testRmdirWithNonEmptydirectory()
    {
        $this->expectException(Warning::class);
        $this->expectExceptionMessage('Directory "cloudstorage:///foo" isn\'t empty');

        $client = $this->getCloudStorageClientInterfaceMock();

        $client->expects($this->once())
               ->method('getObjects')
               ->with($this->identicalTo('/foo/'), $this->identicalTo(2))
               ->willReturn(['/foo/', '/foo/bar.txt']);

        $client->expects($this->never())
               ->method('deleteObject');

        $clearstatcache = $this->getFunctionMock($this->getNamespace(CloudStorageStreamWrapper::class), 'clearstatcache');
        $clearstatcache->expects($this->once())
                       ->with($this->identicalTo(true), $this->identicalTo('cloudstorage:///foo'));

        CloudStorageStreamWrapper::register($client, new \ArrayObject());

        (new CloudStorageStreamWrapper())->rmdir('cloudstorage:///foo', 0777);
    }

    public function testStreamCast()
    {
        $this->assertFalse((new CloudStorageStreamWrapper())->stream_cast());
    }

    public function testStreamClose()
    {
        $wrapper = new CloudStorageStreamWrapper();

        $wrapperReflection = new \ReflectionObject($wrapper);

        $cacheReflection = $wrapperReflection->getProperty('cache');
        $cacheReflection->setAccessible(true);

        $cacheReflection->setValue($wrapper, ['foo']);

        $fclose = $this->getFunctionMock($this->getNamespace(CloudStorageStreamWrapper::class), 'fclose');
        $fclose->expects($this->once());

        $wrapper->stream_close();

        $this->assertNull($cacheReflection->getValue($wrapper));
    }

    public function testStreamEof()
    {
        $feof = $this->getFunctionMock($this->getNamespace(CloudStorageStreamWrapper::class), 'feof');
        $feof->expects($this->once())
             ->willReturn(false);

        $this->assertFalse((new CloudStorageStreamWrapper())->stream_eof());
    }

    public function testStreamFlushWhenNotReading()
    {
        $wrapper = new CloudStorageStreamWrapper();

        $wrapperReflection = new \ReflectionObject($wrapper);

        $keyReflection = $wrapperReflection->getProperty('openedStreamObjectKey');
        $keyReflection->setAccessible(true);

        $modeReflection = $wrapperReflection->getProperty('openedStreamMode');
        $modeReflection->setAccessible(true);

        $keyReflection->setValue($wrapper, '/foo.txt');
        $modeReflection->setValue($wrapper, 'w');

        $rewind = $this->getFunctionMock($this->getNamespace(CloudStorageStreamWrapper::class), 'rewind');
        $rewind->expects($this->once());

        $stream_get_contents = $this->getFunctionMock($this->getNamespace(CloudStorageStreamWrapper::class), 'stream_get_contents');
        $stream_get_contents->expects($this->once())
                            ->willReturn('foo');

        $client = $this->getCloudStorageClientInterfaceMock();
        $client->expects($this->once())
               ->method('putObject')
               ->with($this->identicalTo('/foo.txt'), $this->identicalTo('foo'), $this->identicalTo('text/plain'));

        CloudStorageStreamWrapper::register($client, new \ArrayObject());

        $this->assertTrue($wrapper->stream_flush());
    }

    public function testStreamFlushWhenReading()
    {
        $wrapper = new CloudStorageStreamWrapper();

        $wrapperReflection = new \ReflectionObject($wrapper);

        $modeReflection = $wrapperReflection->getProperty('openedStreamMode');
        $modeReflection->setAccessible(true);

        $modeReflection->setValue($wrapper, 'r');

        $this->assertFalse($wrapper->stream_flush());
    }

    public function testStreamMetadata()
    {
        $this->assertFalse((new CloudStorageStreamWrapper())->stream_metadata());
    }

    public function testStreamOpenWithInvalidMode()
    {
        $this->expectException(Warning::class);
        $this->expectExceptionMessage('"r+" mode isn\'t supported. Must be "r", "w", "a", "x"');

        (new CloudStorageStreamWrapper())->stream_open('cloudstorage:///foo.txt', 'r+');
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

        $fwrite = $this->getFunctionMock($this->getNamespace(CloudStorageStreamWrapper::class), 'fwrite');
        $fwrite->expects($this->once())
               ->with($this->callback(function ($value) { return is_resource($value); }), $this->identicalTo('foo'));

        CloudStorageStreamWrapper::register($client, new \ArrayObject());

        (new CloudStorageStreamWrapper())->stream_open('cloudstorage:///foo.txt', 'a');
    }

    public function testStreamOpenWithModeRAndFileDoesntExist()
    {
        $this->expectException(Warning::class);
        $this->expectExceptionMessage('Must have an existing object when opening with mode "r"');

        $client = $this->getCloudStorageClientInterfaceMock();

        $client->expects($this->once())
               ->method('objectExists')
               ->with($this->identicalTo('/foo.txt'))
               ->willReturn(false);

        $fwrite = $this->getFunctionMock($this->getNamespace(CloudStorageStreamWrapper::class), 'fwrite');
        $fwrite->expects($this->never());

        CloudStorageStreamWrapper::register($client, new \ArrayObject());

        (new CloudStorageStreamWrapper())->stream_open('cloudstorage:///foo.txt', 'r');
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

        $fwrite = $this->getFunctionMock($this->getNamespace(CloudStorageStreamWrapper::class), 'fwrite');
        $fwrite->expects($this->once())
               ->with($this->callback(function ($value) { return is_resource($value); }), $this->identicalTo('foo'));

        $rewind = $this->getFunctionMock($this->getNamespace(CloudStorageStreamWrapper::class), 'rewind');
        $rewind->expects($this->once())
               ->with($this->callback(function ($value) { return is_resource($value); }));

        CloudStorageStreamWrapper::register($client, new \ArrayObject());

        (new CloudStorageStreamWrapper())->stream_open('cloudstorage:///foo.txt', 'r');
    }

    public function testStreamOpenWithModeW()
    {
        $client = $this->getCloudStorageClientInterfaceMock();

        $client->expects($this->once())
               ->method('putObject')
               ->with($this->identicalTo('/foo.txt'), $this->identicalTo(''));

        $fwrite = $this->getFunctionMock($this->getNamespace(CloudStorageStreamWrapper::class), 'fwrite');
        $fwrite->expects($this->once())
               ->with($this->callback(function ($value) { return is_resource($value); }), $this->identicalTo(''));

        CloudStorageStreamWrapper::register($client, new \ArrayObject());

        (new CloudStorageStreamWrapper())->stream_open('cloudstorage:///foo.txt', 'w');
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

        $fwrite = $this->getFunctionMock($this->getNamespace(CloudStorageStreamWrapper::class), 'fwrite');
        $fwrite->expects($this->once())
               ->with($this->callback(function ($value) { return is_resource($value); }), $this->identicalTo(''));

        CloudStorageStreamWrapper::register($client, new \ArrayObject());

        (new CloudStorageStreamWrapper())->stream_open('cloudstorage:///foo.txt', 'x');
    }

    public function testStreamOpenWithModeXAndFileExists()
    {
        $this->expectException(Warning::class);
        $this->expectExceptionMessage('Cannot have an existing object when opening with mode "x"');

        $client = $this->getCloudStorageClientInterfaceMock();

        $client->expects($this->once())
               ->method('objectExists')
               ->with($this->identicalTo('/foo.txt'))
               ->willReturn(true);

        CloudStorageStreamWrapper::register($client, new \ArrayObject());

        (new CloudStorageStreamWrapper())->stream_open('cloudstorage:///foo.txt', 'x');
    }

    public function testStreamRead()
    {
        $fread = $this->getFunctionMock($this->getNamespace(CloudStorageStreamWrapper::class), 'fread');
        $fread->expects($this->once())
              ->with($this->anything(), $this->identicalTo(3))
              ->willReturn('foo');

        $this->assertSame('foo', (new CloudStorageStreamWrapper())->stream_read(3));
    }

    public function testStreamSeek()
    {
        $fseek = $this->getFunctionMock($this->getNamespace(CloudStorageStreamWrapper::class), 'fseek');
        $fseek->expects($this->once())
              ->with($this->anything(), $this->identicalTo(3), $this->identicalTo(SEEK_END))
              ->willReturn(0);

        $this->assertTrue((new CloudStorageStreamWrapper())->stream_seek(3, SEEK_END));
    }

    public function testStreamStatWhenObjectDoesntExist()
    {
        $client = $this->getCloudStorageClientInterfaceMock();
        $wrapper = new CloudStorageStreamWrapper();

        $client->expects($this->once())
               ->method('getObjectDetails')
               ->with($this->identicalTo('/foo.txt'))
               ->willThrowException(new \RuntimeException('Object "/foo.txt" not found'));

        $wrapperReflection = new \ReflectionObject($wrapper);

        $keyReflection = $wrapperReflection->getProperty('openedStreamObjectKey');
        $keyReflection->setAccessible(true);
        $keyReflection->setValue($wrapper, '/foo.txt');

        CloudStorageStreamWrapper::register($client, new \ArrayObject());

        $this->assertFalse($wrapper->stream_stat());
    }

    public function testStreamStatWithDirectory()
    {
        $client = $this->getCloudStorageClientInterfaceMock();
        $wrapper = new CloudStorageStreamWrapper();

        $client->expects($this->once())
               ->method('getObjectDetails')
               ->with($this->identicalTo('/directory/'))
               ->willReturn(['size' => 0]);

        $wrapperReflection = new \ReflectionObject($wrapper);

        $keyReflection = $wrapperReflection->getProperty('openedStreamObjectKey');
        $keyReflection->setAccessible(true);
        $keyReflection->setValue($wrapper, '/directory/');

        CloudStorageStreamWrapper::register($client, new \ArrayObject());

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
        $wrapper = new CloudStorageStreamWrapper();

        $client->expects($this->once())
               ->method('getObjectDetails')
               ->with($this->identicalTo('/foo.txt'))
               ->willReturn(['size' => 42]);

        $wrapperReflection = new \ReflectionObject($wrapper);

        $keyReflection = $wrapperReflection->getProperty('openedStreamObjectKey');
        $keyReflection->setAccessible(true);
        $keyReflection->setValue($wrapper, '/foo.txt');

        CloudStorageStreamWrapper::register($client, new \ArrayObject());

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
        $wrapper = new CloudStorageStreamWrapper();

        $client->expects($this->once())
               ->method('getObjectDetails')
               ->with($this->identicalTo('/foo.txt'))
               ->willReturn(['last-modified' => '10 September 2000']);

        $wrapperReflection = new \ReflectionObject($wrapper);

        $keyReflection = $wrapperReflection->getProperty('openedStreamObjectKey');
        $keyReflection->setAccessible(true);
        $keyReflection->setValue($wrapper, '/foo.txt');

        CloudStorageStreamWrapper::register($client, new \ArrayObject());

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
        $wrapper = new CloudStorageStreamWrapper();

        $client->expects($this->once())
               ->method('getObjectDetails')
               ->with($this->identicalTo('/foo.txt'))
               ->willReturn([]);

        $wrapperReflection = new \ReflectionObject($wrapper);

        $keyReflection = $wrapperReflection->getProperty('openedStreamObjectKey');
        $keyReflection->setAccessible(true);
        $keyReflection->setValue($wrapper, '/foo.txt');

        CloudStorageStreamWrapper::register($client, new \ArrayObject());

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
        $ftell = $this->getFunctionMock($this->getNamespace(CloudStorageStreamWrapper::class), 'ftell');
        $ftell->expects($this->once())
              ->with($this->anything())
              ->willReturn(42);

        $this->assertSame(42, (new CloudStorageStreamWrapper())->stream_tell());
    }

    public function testStreamWrite()
    {
        $fwrite = $this->getFunctionMock($this->getNamespace(CloudStorageStreamWrapper::class), 'fwrite');
        $fwrite->expects($this->once())
               ->with($this->anything(), $this->identicalTo('foo'))
               ->willReturn(3);

        $this->assertSame(3, (new CloudStorageStreamWrapper())->stream_write('foo'));
    }

    public function testUnlink()
    {
        $client = $this->getCloudStorageClientInterfaceMock();

        $client->expects($this->once())
               ->method('deleteObject')
               ->with($this->identicalTo('/foo.txt'));

        $clearstatcache = $this->getFunctionMock($this->getNamespace(CloudStorageStreamWrapper::class), 'clearstatcache');
        $clearstatcache->expects($this->once())
                       ->with($this->identicalTo(true), $this->identicalTo('cloudstorage:///foo.txt'));

        CloudStorageStreamWrapper::register($client, new \ArrayObject());

        $this->assertTrue((new CloudStorageStreamWrapper())->unlink('cloudstorage:///foo.txt'));
    }

    public function testUrlStatWhenCached()
    {
        $client = $this->getCloudStorageClientInterfaceMock();
        $wrapper = new CloudStorageStreamWrapper();

        $wrapperReflection = new \ReflectionObject($wrapper);

        $cacheReflection = $wrapperReflection->getProperty('cache');
        $cacheReflection->setAccessible(true);
        $cacheReflection->setValue($wrapper, new \ArrayObject(['cloudstorage:///foo.txt' => ['foo_stat']]));

        CloudStorageStreamWrapper::register($client, new \ArrayObject());

        $this->assertSame(['foo_stat'], $wrapper->url_stat('cloudstorage:///foo.txt', 1));
    }

    public function testUrlStatWhenObjectDoesntExist()
    {
        $client = $this->getCloudStorageClientInterfaceMock();
        $wrapper = new CloudStorageStreamWrapper();

        $client->expects($this->once())
               ->method('getObjectDetails')
               ->with($this->identicalTo('/foo.txt'))
               ->willThrowException(new \RuntimeException('Object "/foo.txt" not found'));

        $wrapperReflection = new \ReflectionObject($wrapper);

        $cacheReflection = $wrapperReflection->getProperty('cache');
        $cacheReflection->setAccessible(true);

        CloudStorageStreamWrapper::register($client, new \ArrayObject());

        $this->assertFalse($wrapper->url_stat('cloudstorage:///foo.txt', 1));
        $this->assertSame(['cloudstorage:///foo.txt' => false], $cacheReflection->getValue($wrapper)->getArrayCopy());
    }

    public function testUrlStatWithDirectory()
    {
        $client = $this->getCloudStorageClientInterfaceMock();
        $wrapper = new CloudStorageStreamWrapper();

        $client->expects($this->once())
               ->method('getObjectDetails')
               ->with($this->identicalTo('/directory/'))
               ->willReturn(['size' => 0]);

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

        CloudStorageStreamWrapper::register($client, new \ArrayObject());

        $this->assertSame($expectedStat, $wrapper->url_stat('cloudstorage:///directory/', 1));
        $this->assertSame(['cloudstorage:///directory/' => $expectedStat], $cacheReflection->getValue($wrapper)->getArrayCopy());
    }

    public function testUrlStatWithFileSize()
    {
        $client = $this->getCloudStorageClientInterfaceMock();
        $wrapper = new CloudStorageStreamWrapper();

        $client->expects($this->once())
               ->method('getObjectDetails')
               ->with($this->identicalTo('/foo.txt'))
               ->willReturn(['size' => 42]);

        $wrapperReflection = new \ReflectionObject($wrapper);

        $cacheReflection = $wrapperReflection->getProperty('cache');
        $cacheReflection->setAccessible(true);

        CloudStorageStreamWrapper::register($client, new \ArrayObject());

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

        $this->assertSame($expectedStat, $wrapper->url_stat('cloudstorage:///foo.txt', 1));
        $this->assertSame(['cloudstorage:///foo.txt' => $expectedStat], $cacheReflection->getValue($wrapper)->getArrayCopy());
    }

    public function testUrlStatWithLastModified()
    {
        $client = $this->getCloudStorageClientInterfaceMock();
        $wrapper = new CloudStorageStreamWrapper();

        $client->expects($this->once())
               ->method('getObjectDetails')
               ->with($this->identicalTo('/foo.txt'))
               ->willReturn(['last-modified' => '10 September 2000']);

        $wrapperReflection = new \ReflectionObject($wrapper);

        $cacheReflection = $wrapperReflection->getProperty('cache');
        $cacheReflection->setAccessible(true);

        CloudStorageStreamWrapper::register($client, new \ArrayObject());

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

        $this->assertSame($expectedStat, $wrapper->url_stat('cloudstorage:///foo.txt', 1));
        $this->assertSame(['cloudstorage:///foo.txt' => $expectedStat], $cacheReflection->getValue($wrapper)->getArrayCopy());
    }

    public function testUrlStatWithRegularFile()
    {
        $client = $this->getCloudStorageClientInterfaceMock();
        $wrapper = new CloudStorageStreamWrapper();

        $client->expects($this->once())
               ->method('getObjectDetails')
               ->with($this->identicalTo('/foo.txt'))
               ->willReturn([]);

        $wrapperReflection = new \ReflectionObject($wrapper);

        $cacheReflection = $wrapperReflection->getProperty('cache');
        $cacheReflection->setAccessible(true);

        CloudStorageStreamWrapper::register($client, new \ArrayObject());

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

        $this->assertSame($expectedStat, $wrapper->url_stat('cloudstorage:///foo.txt', 1));
        $this->assertSame(['cloudstorage:///foo.txt' => $expectedStat], $cacheReflection->getValue($wrapper)->getArrayCopy());
    }
}
