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

namespace Ymir\Plugin\Tests\Unit\Console;

use Ymir\Plugin\Console\RunAllCronCommand;
use Ymir\Plugin\Tests\Mock\ConsoleClientInterfaceMockTrait;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Mock\WPSiteMockTrait;
use Ymir\Plugin\Tests\Mock\WPSiteQueryMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\Console\RunAllCronCommand
 */
class RunAllCronCommandTest extends TestCase
{
    use ConsoleClientInterfaceMockTrait;
    use FunctionMockTrait;
    use WPSiteMockTrait;
    use WPSiteQueryMockTrait;

    public function testGetName()
    {
        $this->assertSame('ymir run-all-cron', RunAllCronCommand::getName());
    }

    public function testGetSynopsis()
    {
        $this->assertSame([], RunAllCronCommand::getSynopsis());
    }

    public function testInvokeGetsCurrentSiteUrlWhenThereIsNoWPSiteQueryObject()
    {
        $consoleClient = $this->getConsoleClientInterfaceMock();
        $get_site_url = $this->getFunctionMock($this->getNamespace(RunAllCronCommand::class), 'get_site_url');

        $consoleClient->expects($this->once())
                      ->method('runCron')
                      ->with($this->identicalTo('current_site_url'));

        $get_site_url->expects($this->once())
                     ->with($this->identicalTo(0))
                     ->willReturn('current_site_url');

        (new RunAllCronCommand($consoleClient, null))([], []);
    }

    public function testInvokeQueriesForSiteUrlsWhenThereIsAWPSiteQueryObject()
    {
        $consoleClient = $this->getConsoleClientInterfaceMock();
        $get_site_url = $this->getFunctionMock($this->getNamespace(RunAllCronCommand::class), 'get_site_url');
        $wpSite1 = $this->getWPSiteMock();
        $wpSite2 = $this->getWPSiteMock();
        $wpSiteQuery = $this->getWPSiteQueryMock();

        $consoleClient->expects($this->exactly(2))
                      ->method('runCron')
                      ->withConsecutive(
                          [$this->identicalTo('site_url_1')],
                          [$this->identicalTo('site_url_2')]
                      );

        $get_site_url->expects($this->exactly(2))
                     ->withConsecutive(
                         [$this->identicalTo(1)],
                         [$this->identicalTo(2)]
                     )
                     ->willReturnOnConsecutiveCalls('site_url_1', 'site_url_2');

        $wpSite1->blog_id = 1;

        $wpSite2->blog_id = 2;

        $wpSiteQuery->expects($this->once())
                    ->method('query')
                    ->with($this->identicalTo([
                        'number' => 0,
                        'spam' => 0,
                        'deleted' => 0,
                        'archived' => 0,
                    ]))
                    ->willReturn([$wpSite1, $wpSite2]);

        (new RunAllCronCommand($consoleClient, $wpSiteQuery))([], []);
    }
}
