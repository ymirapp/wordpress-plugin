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

use Ymir\Plugin\Subscriber\BedrockSubscriber;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\Subscriber\BedrockSubscriber
 */
class BedrockSubscriberTest extends TestCase
{
    use FunctionMockTrait;

    public function testEnsureHomeUrlDoesntContainWpDoesNothingForBedrockProjectWithNoWp()
    {
        $homeUrl = 'https://'.$this->faker->domainName;

        $this->assertSame($homeUrl, (new BedrockSubscriber('bedrock'))->ensureHomeUrlDoesntContainWp($homeUrl));
    }

    public function testEnsureHomeUrlDoesntContainWpDoesNothingForNonBedrockProject()
    {
        $homeUrl = 'https://'.$this->faker->domainName.'/wp';

        $this->assertSame($homeUrl, (new BedrockSubscriber('wordpress'))->ensureHomeUrlDoesntContainWp($homeUrl));
    }

    public function testEnsureHomeUrlDoesntContainWpRemovesWpForBedrockProject()
    {
        $homeUrl = 'https://'.$this->faker->domainName;

        $this->assertSame($homeUrl, (new BedrockSubscriber('bedrock'))->ensureHomeUrlDoesntContainWp($homeUrl.'/wp'));
    }

    public function testEnsureNetworkSiteUrlContainsWpAddsWpForBedrockProject()
    {
        $baseUrl = 'https://'.$this->faker->domainName;
        $path = '/path';

        $this->assertSame($baseUrl.'/wp'.$path, (new BedrockSubscriber('bedrock'))->ensureNetworkSiteUrlContainsWp($baseUrl.$path, $path));
    }

    public function testEnsureNetworkSiteUrlContainsWpAddsWpForBedrockProjectAndAddsSlash()
    {
        $baseUrl = 'https://'.$this->faker->domainName;
        $path = 'path';

        $this->assertSame($baseUrl.'/wp/'.$path, (new BedrockSubscriber('bedrock'))->ensureNetworkSiteUrlContainsWp($baseUrl.$path, $path));
    }

    public function testEnsureNetworkSiteUrlContainsWpDoesNothingForNonBedrockProject()
    {
        $networkSiteUrl = 'https://'.$this->faker->domainName;

        $this->assertSame($networkSiteUrl, (new BedrockSubscriber('wordpress'))->ensureNetworkSiteUrlContainsWp($networkSiteUrl, ''));
    }

    public function testEnsureNetworkSiteUrlContainsWpDoesntAddsWpForBedrockProjectWhenWpAlreadyInUrl()
    {
        $baseUrl = 'https://'.$this->faker->domainName.'/wp';
        $path = '/path';

        $this->assertSame($baseUrl.$path, (new BedrockSubscriber('bedrock'))->ensureNetworkSiteUrlContainsWp($baseUrl.$path, $path));
    }

    public function testEnsureSiteUrlContainsWpAddsWpForBedrockProjectOnMainSite()
    {
        $is_main_site = $this->getFunctionMock($this->getNamespace(BedrockSubscriber::class), 'is_main_site');
        $siteUrl = 'https://'.$this->faker->domainName;

        $is_main_site->expects($this->once())
                     ->willReturn(true);

        $this->assertSame($siteUrl.'/wp', (new BedrockSubscriber('bedrock'))->ensureSiteUrlContainsWp($siteUrl));
    }

    public function testEnsureSiteUrlContainsWpAddsWpForBedrockProjectOnSubdomainInstalls()
    {
        $is_main_site = $this->getFunctionMock($this->getNamespace(BedrockSubscriber::class), 'is_main_site');
        $is_subdomain_install = $this->getFunctionMock($this->getNamespace(BedrockSubscriber::class), 'is_subdomain_install');
        $siteUrl = 'https://'.$this->faker->domainName;

        $is_main_site->expects($this->once())
                     ->willReturn(false);

        $is_subdomain_install->expects($this->once())
                             ->willReturn(true);

        $this->assertSame($siteUrl.'/wp', (new BedrockSubscriber('bedrock'))->ensureSiteUrlContainsWp($siteUrl));
    }

    public function testEnsureSiteUrlContainsWpDoesNothingForBedrockProjectWithSubdirectoryMultisite()
    {
        $is_main_site = $this->getFunctionMock($this->getNamespace(BedrockSubscriber::class), 'is_main_site');
        $is_subdomain_install = $this->getFunctionMock($this->getNamespace(BedrockSubscriber::class), 'is_subdomain_install');
        $siteUrl = 'https://'.$this->faker->domainName;

        $is_main_site->expects($this->once())
                     ->willReturn(false);

        $is_subdomain_install->expects($this->once())
                             ->willReturn(false);

        $this->assertSame($siteUrl, (new BedrockSubscriber('bedrock'))->ensureSiteUrlContainsWp($siteUrl));
    }

    public function testEnsureSiteUrlContainsWpDoesNothingForNonBedrockProject()
    {
        $siteUrl = 'https://'.$this->faker->domainName;

        $this->assertSame($siteUrl, (new BedrockSubscriber('wordpress'))->ensureSiteUrlContainsWp($siteUrl));
    }

    public function testEnsureSiteUrlContainsWpDoesntAddWpForBedrockProjectWhenWpAlreadyInUrl()
    {
        $siteUrl = 'https://'.$this->faker->domainName.'/wp';

        $this->assertSame($siteUrl, (new BedrockSubscriber('bedrock'))->ensureSiteUrlContainsWp($siteUrl));
    }

    public function testGetSubscribedEvents()
    {
        $callbacks = BedrockSubscriber::getSubscribedEvents();

        foreach ($callbacks as $callback) {
            $this->assertTrue(method_exists(BedrockSubscriber::class, is_array($callback) ? $callback[0] : $callback));
        }

        $subscribedEvents = [
            'network_site_url' => ['ensureNetworkSiteUrlContainsWp', 10, 2],
            'option_home' => 'ensureHomeUrlDoesntContainWp',
            'option_siteurl' => 'ensureSiteUrlContainsWp',
        ];

        $this->assertSame($subscribedEvents, $callbacks);
    }
}
