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

use Ymir\Plugin\Subscriber\SiteHealthSubscriber;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\Subscriber\SiteHealthSubscriber
 */
class SiteHealthSubscriberTest extends TestCase
{
    public function testAdjustSiteHealthTestsDoestRemoveTestsInNonYmirEnvironment()
    {
        $subscriber = new SiteHealthSubscriber('');
        $tests = [
            'async' => [
                'background_updates' => [
                    'label' => 'Background updates are working',
                    'test' => 'background_updates',
                    'recommended' => true,
                ],
            ],
            'direct' => [
                'update_temp_backup_writable' => [
                    'label' => 'Update backup directory is writable',
                    'test' => 'update_temp_backup_writable',
                    'recommended' => true,
                ],
            ],
        ];

        $this->assertSame($tests, $subscriber->adjustSiteHealthTests($tests));
    }

    public function testAdjustSiteHealthTestsRemovesTestsInYmirEnvironment()
    {
        $subscriber = new SiteHealthSubscriber('staging');
        $tests = [
            'async' => [
                'background_updates' => [
                    'label' => 'Background updates are working',
                    'test' => 'background_updates',
                    'recommended' => true,
                ],
            ],
            'direct' => [
                'update_temp_backup_writable' => [
                    'label' => 'Update backup directory is writable',
                    'test' => 'update_temp_backup_writable',
                    'recommended' => true,
                ],
            ],
        ];

        $this->assertSame([
            'async' => [],
            'direct' => [],
        ], $subscriber->adjustSiteHealthTests($tests));
    }
}
