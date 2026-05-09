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

namespace Ymir\Plugin\Tests\Unit\Configuration;

use Ymir\Plugin\Configuration\AssetsConfiguration;
use Ymir\Plugin\DependencyInjection\Container;
use Ymir\Plugin\Tests\Unit\TestCase;
use Ymir\Plugin\ValueObject\MappedDomainNames;

class AssetsConfigurationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        putenv('YMIR_ASSETS_PATH=assets/uuid');
        putenv('YMIR_ASSETS_URL=https://foo.com/assets/uuid');
        putenv('YMIR_CUSTOM_ASSETS_URL');
    }

    protected function tearDown(): void
    {
        putenv('YMIR_ASSETS_PATH');
        putenv('YMIR_ASSETS_URL');
        putenv('YMIR_CUSTOM_ASSETS_URL');

        parent::tearDown();
    }

    public function provideAssetsUrls(): array
    {
        return [
            'single site with local assets url' => [
                'https://foo.com/assets/uuid',
                false,
                false,
                'foo.com',
                new MappedDomainNames([], 'foo.com'),
                'https://foo.com/assets/uuid',
            ],
            'subdomain multisite with mapped domain and local assets url' => [
                'https://foo.com/assets/uuid',
                true,
                true,
                'foo.com',
                new MappedDomainNames([], 'foo.com'),
                'https://foo.com/test/assets/uuid',
            ],
            'subdomain multisite with unmapped domain and local assets url' => [
                'https://foo.com/assets/uuid',
                true,
                true,
                'foo.com',
                new MappedDomainNames([], 'bar.com'),
                'https://foo.com/assets/uuid',
            ],
            'subdomain multisite with mapped domain and cloudfront assets url' => [
                'https://cloudfront.net/assets/uuid',
                true,
                true,
                'foo.com',
                new MappedDomainNames([], 'foo.com'),
                'https://cloudfront.net/assets/uuid',
            ],
            'subdomain multisite with mapped domain and s3 assets url' => [
                'https://s3.amazonaws.com/bucket/assets/uuid',
                true,
                true,
                'foo.com',
                new MappedDomainNames([], 'foo.com'),
                'https://s3.amazonaws.com/bucket/assets/uuid',
            ],
            'subdirectory multisite with same-domain local assets url' => [
                'https://foo.com/assets/uuid',
                true,
                false,
                'foo.com',
                new MappedDomainNames([], 'foo.com'),
                'https://foo.com/test/assets/uuid',
            ],
            'subdirectory multisite with different-domain local assets url' => [
                'https://assets.com/assets/uuid',
                true,
                false,
                'foo.com',
                new MappedDomainNames([], 'foo.com'),
                'https://assets.com/assets/uuid',
            ],
            'subdirectory multisite with cloudfront assets url' => [
                'https://cloudfront.net/assets/uuid',
                true,
                false,
                'foo.com',
                new MappedDomainNames([], 'foo.com'),
                'https://cloudfront.net/assets/uuid',
            ],
            'subdirectory multisite with s3 assets url' => [
                'https://s3.amazonaws.com/bucket/assets/uuid',
                true,
                false,
                'foo.com',
                new MappedDomainNames([], 'foo.com'),
                'https://s3.amazonaws.com/bucket/assets/uuid',
            ],
        ];
    }

    public function testAssetsPathDefaultsToEmptyString(): void
    {
        putenv('YMIR_ASSETS_PATH');

        $container = new Container();

        (new AssetsConfiguration())->modify($container);

        $this->assertSame('', $container['assets_path']);
    }

    public function testAssetsPathUsesEnvironmentVariable(): void
    {
        $container = new Container();

        (new AssetsConfiguration())->modify($container);

        $this->assertSame('assets/uuid', $container['assets_path']);
    }

    /**
     * @dataProvider provideAssetsUrls
     */
    public function testAssetsUrl(string $assetsUrl, bool $isMultisite, bool $isMultisiteSubdomain, string $siteDomain, MappedDomainNames $mappedDomainNames, string $expectedAssetsUrl): void
    {
        putenv('YMIR_ASSETS_URL='.$assetsUrl);

        $container = $this->createContainer($isMultisite, $isMultisiteSubdomain, $siteDomain, $mappedDomainNames);

        (new AssetsConfiguration())->modify($container);

        $this->assertSame($expectedAssetsUrl, $container['assets_url']);
    }

    public function testAssetsUrlDefaultsToEmptyString(): void
    {
        putenv('YMIR_ASSETS_URL');

        $container = $this->createContainer(false, false, 'foo.com', new MappedDomainNames([], 'foo.com'));

        (new AssetsConfiguration())->modify($container);

        $this->assertSame('', $container['assets_url']);
    }

    public function testAssetsUrlUsesCustomAssetsUrl(): void
    {
        putenv('YMIR_CUSTOM_ASSETS_URL=https://assets.com/custom/');

        $container = new Container();

        (new AssetsConfiguration())->modify($container);

        $this->assertSame('https://assets.com/custom/assets/uuid', $container['assets_url']);
    }

    private function createContainer(bool $isMultisite, bool $isMultisiteSubdomain, string $siteDomain, MappedDomainNames $mappedDomainNames): Container
    {
        return new Container([
            'home_url' => 'https://foo.com/test',
            'is_multisite' => $isMultisite,
            'is_multisite_subdomain' => $isMultisiteSubdomain,
            'site_domain' => $siteDomain,
            'ymir_mapped_domain_names' => $mappedDomainNames,
        ]);
    }
}
