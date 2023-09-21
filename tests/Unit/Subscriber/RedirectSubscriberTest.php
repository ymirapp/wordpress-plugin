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

use Ymir\Plugin\Subscriber\RedirectSubscriber;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Mock\MappedDomainNamesMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\Subscriber\RedirectSubscriber
 */
class RedirectSubscriberTest extends TestCase
{
    use FunctionMockTrait;
    use MappedDomainNamesMockTrait;

    public function testAddSlashToBedrockWpAdminWithHttpHostDifferentThanPrimaryDomainName()
    {
        $mappedDomainNames = $this->getMappedDomainNamesMock();
        $mappedDomainNames->expects($this->once())
                          ->method('getPrimaryDomainNameURL')
                          ->willReturn('https://domain_name');
        $mappedDomainNames->expects($this->once())
                          ->method('IsMappedDomainName')
                          ->with($this->identicalTo('another_domain_name'))
                          ->willReturn(false);

        $wp_redirect = $this->getFunctionMock($this->getNamespace(RedirectSubscriber::class), 'wp_redirect');
        $wp_redirect->expects($this->once())
                    ->with($this->identicalTo('https://domain_name/wp/wp-admin/'), $this->identicalTo(301))
                    ->willReturn(false);

        (new RedirectSubscriber($mappedDomainNames, 'another_domain_name', '/wp/wp-admin', false, 'bedrock'))->redirect();
    }

    public function testAddSlashToBedrockWpAdminWithHttpHostSameAsPrimaryDomainName()
    {
        $mappedDomainNames = $this->getMappedDomainNamesMock();
        $mappedDomainNames->expects($this->once())
                          ->method('getPrimaryDomainNameURL')
                          ->willReturn('https://domain_name');
        $mappedDomainNames->expects($this->once())
                          ->method('IsMappedDomainName')
                          ->with($this->identicalTo('domain_name'))
                          ->willReturn(true);

        $wp_redirect = $this->getFunctionMock($this->getNamespace(RedirectSubscriber::class), 'wp_redirect');
        $wp_redirect->expects($this->once())
                    ->with($this->identicalTo('https://domain_name/wp/wp-admin/'), $this->identicalTo(301))
                    ->willReturn(false);

        (new RedirectSubscriber($mappedDomainNames, 'domain_name', '/wp/wp-admin', false, 'bedrock'))->redirect();
    }

    public function testAddSlashToWpAdminWithHttpHostDifferentThanPrimaryDomainName()
    {
        $mappedDomainNames = $this->getMappedDomainNamesMock();
        $mappedDomainNames->expects($this->once())
                          ->method('getPrimaryDomainNameURL')
                          ->willReturn('https://domain_name');
        $mappedDomainNames->expects($this->once())
                          ->method('IsMappedDomainName')
                          ->with($this->identicalTo('another_domain_name'))
                          ->willReturn(false);

        $wp_redirect = $this->getFunctionMock($this->getNamespace(RedirectSubscriber::class), 'wp_redirect');
        $wp_redirect->expects($this->once())
                    ->with($this->identicalTo('https://domain_name/wp-admin/'), $this->identicalTo(301))
                    ->willReturn(false);

        (new RedirectSubscriber($mappedDomainNames, 'another_domain_name', '/wp-admin'))->redirect();
    }

    public function testAddSlashToWpAdminWithHttpHostSameAsPrimaryDomainName()
    {
        $mappedDomainNames = $this->getMappedDomainNamesMock();
        $mappedDomainNames->expects($this->once())
                          ->method('getPrimaryDomainNameURL')
                          ->willReturn('https://domain_name');
        $mappedDomainNames->expects($this->once())
                          ->method('IsMappedDomainName')
                          ->with($this->identicalTo('domain_name'))
                          ->willReturn(true);

        $wp_redirect = $this->getFunctionMock($this->getNamespace(RedirectSubscriber::class), 'wp_redirect');
        $wp_redirect->expects($this->once())
                    ->with($this->identicalTo('https://domain_name/wp-admin/'), $this->identicalTo(301))
                    ->willReturn(false);

        (new RedirectSubscriber($mappedDomainNames, 'domain_name', '/wp-admin'))->redirect();
    }

    public function testDoesntRedirectToPrimaryDomainNameWhenMultisiteEnabled()
    {
        $mappedDomainNames = $this->getMappedDomainNamesMock();
        $mappedDomainNames->expects($this->never())
                          ->method('getPrimaryDomainNameURL');
        $mappedDomainNames->expects($this->never())
                          ->method('IsMappedDomainName');

        $wp_redirect = $this->getFunctionMock($this->getNamespace(RedirectSubscriber::class), 'wp_redirect');
        $wp_redirect->expects($this->never());

        (new RedirectSubscriber($mappedDomainNames, 'domain_name', '', true))->redirect();
    }

    public function testDoesntRedirectToPrimaryDomainNameWithHttpHostIsAMappedDomainName()
    {
        $mappedDomainNames = $this->getMappedDomainNamesMock();
        $mappedDomainNames->expects($this->once())
                          ->method('IsMappedDomainName')
                          ->with($this->identicalTo('domain_name'))
                          ->willReturn(true);

        $wp_redirect = $this->getFunctionMock($this->getNamespace(RedirectSubscriber::class), 'wp_redirect');
        $wp_redirect->expects($this->never());

        (new RedirectSubscriber($mappedDomainNames, 'domain_name', ''))->redirect();
    }

    public function testDoesntRedirectWhenWpAdminHasSlashAlready()
    {
        $mappedDomainNames = $this->getMappedDomainNamesMock();
        $mappedDomainNames->expects($this->once())
                          ->method('IsMappedDomainName')
                          ->with($this->identicalTo('domain_name'))
                          ->willReturn(true);

        $wp_redirect = $this->getFunctionMock($this->getNamespace(RedirectSubscriber::class), 'wp_redirect');
        $wp_redirect->expects($this->never());

        (new RedirectSubscriber($mappedDomainNames, 'domain_name', '/wp-admin/'))->redirect();
    }

    public function testGetSubscribedEvents()
    {
        $callbacks = RedirectSubscriber::getSubscribedEvents();

        foreach ($callbacks as $callback) {
            $this->assertTrue(method_exists(RedirectSubscriber::class, is_array($callback) ? $callback[0] : $callback));
        }

        $subscribedEvents = [
            'init' => ['redirect', 1],
        ];

        $this->assertSame($subscribedEvents, $callbacks);
    }

    public function testRedirectsToPrimaryDomainNameWithHttpHostDifferentThanPrimaryDomainName()
    {
        $mappedDomainNames = $this->getMappedDomainNamesMock();
        $mappedDomainNames->expects($this->once())
                          ->method('getPrimaryDomainNameURL')
                          ->willReturn('https://domain_name');
        $mappedDomainNames->expects($this->once())
                          ->method('IsMappedDomainName')
                          ->with($this->identicalTo('another_domain_name'))
                          ->willReturn(false);

        $wp_redirect = $this->getFunctionMock($this->getNamespace(RedirectSubscriber::class), 'wp_redirect');
        $wp_redirect->expects($this->once())
                    ->with($this->identicalTo('https://domain_name'), $this->identicalTo(301))
                    ->willReturn(false);

        (new RedirectSubscriber($mappedDomainNames, 'another_domain_name', ''))->redirect();
    }

    public function testRedirectsToPrimaryDomainNameWithHttpHostDifferentThanPrimaryDomainNameWithRequestUri()
    {
        $mappedDomainNames = $this->getMappedDomainNamesMock();
        $mappedDomainNames->expects($this->once())
                          ->method('getPrimaryDomainNameURL')
                          ->willReturn('https://domain_name');
        $mappedDomainNames->expects($this->once())
                          ->method('IsMappedDomainName')
                          ->with($this->identicalTo('another_domain_name'))
                          ->willReturn(false);

        $wp_redirect = $this->getFunctionMock($this->getNamespace(RedirectSubscriber::class), 'wp_redirect');
        $wp_redirect->expects($this->once())
                    ->with($this->identicalTo('https://domain_name/uri'), $this->identicalTo(301))
                    ->willReturn(false);

        (new RedirectSubscriber($mappedDomainNames, 'another_domain_name', '/uri'))->redirect();
    }
}
