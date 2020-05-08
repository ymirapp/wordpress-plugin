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

namespace Ymir\Plugin\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Ymir\Plugin\DependencyInjection\Container;
use Ymir\Plugin\Tests\Mock\ContainerConfigurationInterfaceMockTrait;

/**
 * @covers \Ymir\Plugin\DependencyInjection\Container
 */
class ContainerTest extends TestCase
{
    use ContainerConfigurationInterfaceMockTrait;

    /**
     * @var Container
     */
    private $container;

    protected function setUp()
    {
        $this->container = new Container();
    }

    protected function tearDown()
    {
        $this->container = null;
    }

    public function testConfigureArrayCast()
    {
        $configuration = $this->getContainerConfigurationInterfaceMock();
        $configuration->expects($this->once())
                      ->method('modify')
                      ->with($this->identicalTo($this->container));

        $this->container->configure($configuration);
    }

    public function testConfigureWithArray()
    {
        $fooConfiguration = $this->getContainerConfigurationInterfaceMock();
        $fooConfiguration->expects($this->once())
                          ->method('modify')
                          ->with($this->identicalTo($this->container));

        $barConfiguration = $this->getContainerConfigurationInterfaceMock();
        $barConfiguration->expects($this->once())
                          ->method('modify')
                          ->with($this->identicalTo($this->container));

        $this->container->configure([$fooConfiguration, $barConfiguration]);
    }

    public function testConstructor()
    {
        $arguments = ['foo' => 'bar'];
        $container = new Container($arguments);

        $this->assertEquals($arguments['foo'], $container['foo']);
    }

    public function testIsset()
    {
        $this->container['null'] = null;
        $this->container['param'] = 'value';
        $this->container['service'] = function () {
            return new \stdClass();
        };

        $this->assertTrue(isset($this->container['null']));
        $this->assertTrue(isset($this->container['param']));
        $this->assertTrue(isset($this->container['service']));
        $this->assertFalse(isset($this->container['non_existent']));
    }

    public function testOffsetGet()
    {
        $this->container['null'] = null;
        $this->container['param'] = 'value';
        $this->container['service'] = function () {
            return new \stdClass();
        };

        $this->assertNull($this->container['null']);
        $this->assertEquals('value', $this->container['param']);
        $this->assertInstanceOf('stdClass', $this->container['service']);
    }

    public function testOffsetGetException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Container doesn\'t have a value stored for the "foo" key');

        echo $this->container['foo'];
    }

    public function testService()
    {
        $this->container['service'] = $this->container->service(function (Container $container) {
            return new \stdClass();
        });

        $foo_service = $this->container['service'];
        $this->assertInstanceOf('stdClass', $foo_service);

        $bar_service = $this->container['service'];
        $this->assertInstanceOf('stdClass', $bar_service);

        $this->assertSame($foo_service, $bar_service);
    }

    public function testServiceException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Service definition is not a Closure or invokable object');

        require_once 'fixtures/functions.php';

        $this->container->service('foo');
    }

    public function testUnset()
    {
        $this->container['null'] = null;
        $this->container['param'] = 'value';

        unset($this->container['null'], $this->container['param']);

        $this->assertFalse(isset($this->container['null']));
        $this->assertFalse(isset($this->container['param']));
    }
}
