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
use Ymir\Plugin\Configuration\ObjectCacheConfiguration;
use Ymir\Plugin\DependencyInjection\Container;
use Ymir\Plugin\ObjectCache\WordPressObjectCache;

/**
 * @covers \Ymir\Plugin\Configuration\ObjectCacheConfiguration
 */
class ObjectCacheConfigurationTest extends TestCase
{
    private $container;

    protected function setUp(): void
    {
        $this->container = new Container();

        (new ObjectCacheConfiguration())->modify($this->container);
    }

    public function testGetObjectCache()
    {
        $this->assertInstanceOf(WordPressObjectCache::class, $this->container->get('ymir_object_cache'));
    }
}
