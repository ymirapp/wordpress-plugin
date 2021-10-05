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
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\Subscriber\RedirectSubscriber
 */
class RedirectSubscriberTest extends TestCase
{
    use FunctionMockTrait;

    /**
     * @backupGlobals enabled
     */
    public function testAddSlashToWpAdminWithHttpHostDifferentThanPrimaryDomainName()
    {
        $_SERVER['HTTP_HOST'] = 'another_domain_name';
        $_SERVER['REQUEST_URI'] = '/wp-admin';

        $wp_redirect = $this->getFunctionMock($this->getNamespace(RedirectSubscriber::class), 'wp_redirect');
        $wp_redirect->expects($this->once())
                    ->with($this->identicalTo('https://domain_name/wp-admin/'), $this->identicalTo(301))
                    ->willReturn(false);

        (new RedirectSubscriber('domain_name', false))->redirect();
    }

    /**
     * @backupGlobals enabled
     */
    public function testAddSlashToWpAdminWithHttpHostSameAsPrimaryDomainName()
    {
        $_SERVER['HTTP_HOST'] = 'domain_name';
        $_SERVER['REQUEST_URI'] = '/wp-admin';

        $wp_redirect = $this->getFunctionMock($this->getNamespace(RedirectSubscriber::class), 'wp_redirect');
        $wp_redirect->expects($this->once())
            ->with($this->identicalTo('https://domain_name/wp-admin/'), $this->identicalTo(301))
            ->willReturn(false);

        (new RedirectSubscriber('domain_name', false))->redirect();
    }

    public function testDoesntRedirectToPrimaryDomainNameWhenMultisiteEnabled()
    {
        $wp_redirect = $this->getFunctionMock($this->getNamespace(RedirectSubscriber::class), 'wp_redirect');
        $wp_redirect->expects($this->never());

        (new RedirectSubscriber('domain_name', true))->redirect();
    }

    /**
     * @backupGlobals enabled
     */
    public function testDoesntRedirectWhenWpAdminHasSlashAlready()
    {
        $_SERVER['HTTP_HOST'] = 'domain_name';
        $_SERVER['REQUEST_URI'] = '/wp-admin/';

        $wp_redirect = $this->getFunctionMock($this->getNamespace(RedirectSubscriber::class), 'wp_redirect');
        $wp_redirect->expects($this->never());

        (new RedirectSubscriber('domain_name', false))->redirect();
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

    /**
     * @backupGlobals enabled
     */
    public function testRedirectsToPrimaryDomainNameWithHttpHostDifferentThanPrimaryDomainName()
    {
        $_SERVER['HTTP_HOST'] = 'another_domain_name';

        $wp_redirect = $this->getFunctionMock($this->getNamespace(RedirectSubscriber::class), 'wp_redirect');
        $wp_redirect->expects($this->once())
                    ->with($this->identicalTo('https://domain_name'), $this->identicalTo(301))
                    ->willReturn(false);

        (new RedirectSubscriber('domain_name', false))->redirect();
    }

    /**
     * @backupGlobals enabled
     */
    public function testRedirectsToPrimaryDomainNameWithHttpHostDifferentThanPrimaryDomainNameAddsRequestUri()
    {
        $_SERVER['HTTP_HOST'] = 'another_domain_name';
        $_SERVER['REQUEST_URI'] = '/uri';

        $wp_redirect = $this->getFunctionMock($this->getNamespace(RedirectSubscriber::class), 'wp_redirect');
        $wp_redirect->expects($this->once())
                    ->with($this->identicalTo('https://domain_name/uri'), $this->identicalTo(301))
                    ->willReturn(false);

        (new RedirectSubscriber('domain_name', false))->redirect();
    }

    /**
     * @backupGlobals enabled
     */
    public function testRedirectsToPrimaryDomainNameWithHttpHostSameAsPrimaryDomainName()
    {
        $_SERVER['HTTP_HOST'] = 'domain_name';

        $wp_redirect = $this->getFunctionMock($this->getNamespace(RedirectSubscriber::class), 'wp_redirect');
        $wp_redirect->expects($this->never());

        (new RedirectSubscriber('domain_name', false))->redirect();
    }
}
