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

namespace Ymir\Plugin\Tests\Unit\Subscriber;

use Ymir\Plugin\Subscriber\RootsSubscriber;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\Subscriber\RootsSubscriber
 */
class RootsSubscriberTest extends TestCase
{
    use FunctionMockTrait;

    public function provideRootsProjectTypes(): array
    {
        return [
            ['bedrock'],
            ['radicle'],
        ];
    }

    public function testEnsureHomeUrlDoesntContainWpDoesNothingForNonRootsProject()
    {
        $homeUrl = 'https://'.$this->faker->domainName.'/wp';

        $this->assertSame($homeUrl, (new RootsSubscriber('wordpress'))->ensureHomeUrlDoesntContainWp($homeUrl));
    }

    /**
     * @dataProvider provideRootsProjectTypes
     */
    public function testEnsureHomeUrlDoesntContainWpDoesNothingForRootsProjectWithNoWp(string $projectType)
    {
        $homeUrl = 'https://'.$this->faker->domainName;

        $this->assertSame($homeUrl, (new RootsSubscriber($projectType))->ensureHomeUrlDoesntContainWp($homeUrl));
    }

    /**
     * @dataProvider provideRootsProjectTypes
     */
    public function testEnsureHomeUrlDoesntContainWpRemovesWpForRootsProject(string $projectType)
    {
        $homeUrl = 'https://'.$this->faker->domainName;

        $this->assertSame($homeUrl, (new RootsSubscriber($projectType))->ensureHomeUrlDoesntContainWp($homeUrl.'/wp'));
    }

    /**
     * @dataProvider provideRootsProjectTypes
     */
    public function testEnsureNetworkSiteUrlContainsWpAddsWpForRootsProject(string $projectType)
    {
        $baseUrl = 'https://'.$this->faker->domainName;
        $path = '/path';

        $this->assertSame($baseUrl.'/wp'.$path, (new RootsSubscriber($projectType))->ensureNetworkSiteUrlContainsWp($baseUrl.$path, $path));
    }

    /**
     * @dataProvider provideRootsProjectTypes
     */
    public function testEnsureNetworkSiteUrlContainsWpAddsWpForRootsProjectAndAddsSlash(string $projectType)
    {
        $baseUrl = 'https://'.$this->faker->domainName;
        $path = 'path';

        $this->assertSame($baseUrl.'/wp/'.$path, (new RootsSubscriber($projectType))->ensureNetworkSiteUrlContainsWp($baseUrl.$path, $path));
    }

    public function testEnsureNetworkSiteUrlContainsWpDoesNothingForNonRootsProject()
    {
        $networkSiteUrl = 'https://'.$this->faker->domainName;

        $this->assertSame($networkSiteUrl, (new RootsSubscriber('wordpress'))->ensureNetworkSiteUrlContainsWp($networkSiteUrl, ''));
    }

    /**
     * @dataProvider provideRootsProjectTypes
     */
    public function testEnsureNetworkSiteUrlContainsWpDoesntAddsWpForRootsProjectWhenWpAlreadyInUrl(string $projectType)
    {
        $baseUrl = 'https://'.$this->faker->domainName.'/wp';
        $path = '/path';

        $this->assertSame($baseUrl.$path, (new RootsSubscriber($projectType))->ensureNetworkSiteUrlContainsWp($baseUrl.$path, $path));
    }

    /**
     * @dataProvider provideRootsProjectTypes
     */
    public function testEnsureSiteUrlContainsWpAddsWpForRootsProjectOnMainSite(string $projectType)
    {
        $is_main_site = $this->getFunctionMock($this->getNamespace(RootsSubscriber::class), 'is_main_site');
        $siteUrl = 'https://'.$this->faker->domainName;

        $is_main_site->expects($this->once())
                     ->willReturn(true);

        $this->assertSame($siteUrl.'/wp', (new RootsSubscriber($projectType))->ensureSiteUrlContainsWp($siteUrl));
    }

    /**
     * @dataProvider provideRootsProjectTypes
     */
    public function testEnsureSiteUrlContainsWpAddsWpForRootsProjectOnSubdomainInstalls(string $projectType)
    {
        $is_main_site = $this->getFunctionMock($this->getNamespace(RootsSubscriber::class), 'is_main_site');
        $is_subdomain_install = $this->getFunctionMock($this->getNamespace(RootsSubscriber::class), 'is_subdomain_install');
        $siteUrl = 'https://'.$this->faker->domainName;

        $is_main_site->expects($this->once())
                     ->willReturn(false);

        $is_subdomain_install->expects($this->once())
                             ->willReturn(true);

        $this->assertSame($siteUrl.'/wp', (new RootsSubscriber($projectType))->ensureSiteUrlContainsWp($siteUrl));
    }

    public function testEnsureSiteUrlContainsWpDoesNothingForNonRootsProject()
    {
        $siteUrl = 'https://'.$this->faker->domainName;

        $this->assertSame($siteUrl, (new RootsSubscriber('wordpress'))->ensureSiteUrlContainsWp($siteUrl));
    }

    /**
     * @dataProvider provideRootsProjectTypes
     */
    public function testEnsureSiteUrlContainsWpDoesNothingForRootsProjectWithSubdirectoryMultisite(string $projectType)
    {
        $is_main_site = $this->getFunctionMock($this->getNamespace(RootsSubscriber::class), 'is_main_site');
        $is_subdomain_install = $this->getFunctionMock($this->getNamespace(RootsSubscriber::class), 'is_subdomain_install');
        $siteUrl = 'https://'.$this->faker->domainName;

        $is_main_site->expects($this->once())
                     ->willReturn(false);

        $is_subdomain_install->expects($this->once())
                             ->willReturn(false);

        $this->assertSame($siteUrl, (new RootsSubscriber($projectType))->ensureSiteUrlContainsWp($siteUrl));
    }

    /**
     * @dataProvider provideRootsProjectTypes
     */
    public function testEnsureSiteUrlContainsWpDoesntAddWpForRootsProjectWhenWpAlreadyInUrl(string $projectType)
    {
        $siteUrl = 'https://'.$this->faker->domainName.'/wp';

        $this->assertSame($siteUrl, (new RootsSubscriber($projectType))->ensureSiteUrlContainsWp($siteUrl));
    }

    public function testGetSubscribedEvents()
    {
        $callbacks = RootsSubscriber::getSubscribedEvents();

        foreach ($callbacks as $callback) {
            $this->assertTrue(method_exists(RootsSubscriber::class, is_array($callback) ? $callback[0] : $callback));
        }

        $subscribedEvents = [
            'network_site_url' => ['ensureNetworkSiteUrlContainsWp', 10, 2],
            'option_home' => 'ensureHomeUrlDoesntContainWp',
            'option_siteurl' => 'ensureSiteUrlContainsWp',
        ];

        $this->assertSame($subscribedEvents, $callbacks);
    }
}
