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

use Ymir\Plugin\EventManagement\WordPressAwareInterface;

class TestWordPressAwareSubscriber extends TestSubscriber implements WordPressAwareInterface
{
    public $wp;

    public function setWordPress(\WP $wp): void
    {
        $this->wp = $wp;
    }
}
