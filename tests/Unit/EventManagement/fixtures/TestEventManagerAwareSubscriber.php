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

namespace Ymir\Plugin\Tests\Unit\EventManagement;

use Ymir\Plugin\EventManagement\EventManager;
use Ymir\Plugin\EventManagement\EventManagerAwareInterface;

class TestEventManagerAwareSubscriber extends TestSubscriber implements EventManagerAwareInterface
{
    protected $eventManager;

    public static function getSubscribedEvents(): array
    {
        return [
            'foo' => 'on_foo',
            'bar' => ['on_bar', 5],
            'foobar' => ['on_foobar', 5, 2],
        ];
    }

    public function setEventManager(EventManager $eventManager)
    {
        $this->eventManager = $eventManager;
    }
}
