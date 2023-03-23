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
use Ymir\Plugin\Tests\Mock\EventManagerMockTrait;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Mock\WpCliMockTrait;
use Ymir\Plugin\Tests\Mock\WPSiteMockTrait;
use Ymir\Plugin\Tests\Mock\WPSiteQueryMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\Console\RunAllCronCommand
 */
class RunAllCronCommandTest extends TestCase
{
    use ConsoleClientInterfaceMockTrait;
    use EventManagerMockTrait;
    use FunctionMockTrait;
    use WpCliMockTrait;
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
        $eventManager = $this->getEventManagerMock();
        $get_site_url = $this->getFunctionMock($this->getNamespace(RunAllCronCommand::class), 'get_site_url');
        $wpCli = $this->getWpCliMock();

        $consoleClient->expects($this->once())
                      ->method('runWpCliCommand')
                      ->with($this->identicalTo('cron event run --due-now --quiet'), $this->identicalTo(true), $this->identicalTo('current_site_url'));

        $eventManager->expects($this->once())
                     ->method('filter')
                     ->with($this->identicalTo('ymir_scheduled_site_cron_commands'), $this->identicalTo(['cron event run --due-now --quiet']), $this->identicalTo('current_site_url'))
                     ->willReturnArgument(1);

        $get_site_url->expects($this->once())
                     ->with($this->identicalTo(0))
                     ->willReturn('current_site_url');

        $wpCli->expects($this->once())
               ->method('isCommandRegistered')
               ->with($this->identicalTo('cron event run --due-now --quiet'))
               ->willReturn(true);

        (new RunAllCronCommand($consoleClient, $eventManager, $wpCli, null))([], []);
    }

    public function testInvokeQueriesForSiteUrlsWhenThereIsAWPSiteQueryObject()
    {
        $consoleClient = $this->getConsoleClientInterfaceMock();
        $eventManager = $this->getEventManagerMock();
        $get_site_url = $this->getFunctionMock($this->getNamespace(RunAllCronCommand::class), 'get_site_url');
        $wpCli = $this->getWpCliMock();
        $wpSite1 = $this->getWPSiteMock();
        $wpSite2 = $this->getWPSiteMock();
        $wpSiteQuery = $this->getWPSiteQueryMock();

        $consoleClient->expects($this->exactly(2))
                      ->method('runWpCliCommand')
                      ->withConsecutive(
                          [$this->identicalTo('cron event run --due-now --quiet'), $this->identicalTo(true), $this->identicalTo('site_url_1')],
                          [$this->identicalTo('cron event run --due-now --quiet'), $this->identicalTo(true), $this->identicalTo('site_url_2')]
                      );

        $eventManager->expects($this->exactly(2))
                     ->method('filter')
                     ->withConsecutive(
                         [$this->identicalTo('ymir_scheduled_site_cron_commands'), $this->identicalTo(['cron event run --due-now --quiet']), $this->identicalTo('site_url_1')],
                         [$this->identicalTo('ymir_scheduled_site_cron_commands'), $this->identicalTo(['cron event run --due-now --quiet']), $this->identicalTo('site_url_2')]
                     )
                     ->willReturnArgument(1);

        $get_site_url->expects($this->exactly(2))
                     ->withConsecutive(
                         [$this->identicalTo(1)],
                         [$this->identicalTo(2)]
                     )
                     ->willReturnOnConsecutiveCalls('site_url_1', 'site_url_2');

        $wpCli->expects($this->exactly(2))
              ->method('isCommandRegistered')
              ->withConsecutive(
                  [$this->identicalTo('cron event run --due-now --quiet')],
                  [$this->identicalTo('cron event run --due-now --quiet')]
              )
              ->willReturn(true);

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

        (new RunAllCronCommand($consoleClient, $eventManager, $wpCli, $wpSiteQuery))([], []);
    }
}
