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

namespace Ymir\Plugin\Tests\Unit\Support;

use Ymir\Plugin\Support\WordPress;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

class WordPressTest extends TestCase
{
    use FunctionMockTrait;

    public function testIsAutosaveOrRevisionReturnsFalseWhenPostIsNotAutosaveOrRevision()
    {
        $function_exists = $this->getFunctionMock($this->getNamespace(WordPress::class), 'function_exists');
        $function_exists->expects($this->exactly(2))
                        ->withConsecutive(
                            [$this->identicalTo('wp_is_post_autosave')],
                            [$this->identicalTo('wp_is_post_revision')]
                        )
                        ->willReturn(true);

        $wp_is_post_autosave = $this->getFunctionMock($this->getNamespace(WordPress::class), 'wp_is_post_autosave');
        $wp_is_post_autosave->expects($this->once())
                            ->with($this->identicalTo(42))
                            ->willReturn(false);

        $wp_is_post_revision = $this->getFunctionMock($this->getNamespace(WordPress::class), 'wp_is_post_revision');
        $wp_is_post_revision->expects($this->once())
                            ->with($this->identicalTo(42))
                            ->willReturn(false);

        $this->assertFalse(WordPress::isAutosaveOrRevision(42));
    }

    public function testIsAutosaveOrRevisionReturnsFalseWhenWordPressFunctionsAreUnavailable()
    {
        $function_exists = $this->getFunctionMock($this->getNamespace(WordPress::class), 'function_exists');
        $function_exists->expects($this->once())
                        ->with($this->identicalTo('wp_is_post_autosave'))
                        ->willReturn(false);

        $wp_is_post_autosave = $this->getFunctionMock($this->getNamespace(WordPress::class), 'wp_is_post_autosave');
        $wp_is_post_autosave->expects($this->never());

        $wp_is_post_revision = $this->getFunctionMock($this->getNamespace(WordPress::class), 'wp_is_post_revision');
        $wp_is_post_revision->expects($this->never());

        $this->assertFalse(WordPress::isAutosaveOrRevision(42));
    }

    public function testIsAutosaveOrRevisionReturnsTrueWhenPostIsAutosave()
    {
        $function_exists = $this->getFunctionMock($this->getNamespace(WordPress::class), 'function_exists');
        $function_exists->expects($this->exactly(2))
                        ->withConsecutive(
                            [$this->identicalTo('wp_is_post_autosave')],
                            [$this->identicalTo('wp_is_post_revision')]
                        )
                        ->willReturn(true);

        $wp_is_post_autosave = $this->getFunctionMock($this->getNamespace(WordPress::class), 'wp_is_post_autosave');
        $wp_is_post_autosave->expects($this->once())
                            ->with($this->identicalTo(42))
                            ->willReturn(true);

        $wp_is_post_revision = $this->getFunctionMock($this->getNamespace(WordPress::class), 'wp_is_post_revision');
        $wp_is_post_revision->expects($this->never());

        $this->assertTrue(WordPress::isAutosaveOrRevision(42));
    }

    public function testIsAutosaveOrRevisionReturnsTrueWhenPostIsRevision()
    {
        $function_exists = $this->getFunctionMock($this->getNamespace(WordPress::class), 'function_exists');
        $function_exists->expects($this->exactly(2))
                        ->withConsecutive(
                            [$this->identicalTo('wp_is_post_autosave')],
                            [$this->identicalTo('wp_is_post_revision')]
                        )
                        ->willReturn(true);

        $wp_is_post_autosave = $this->getFunctionMock($this->getNamespace(WordPress::class), 'wp_is_post_autosave');
        $wp_is_post_autosave->expects($this->once())
                            ->with($this->identicalTo(42))
                            ->willReturn(false);

        $wp_is_post_revision = $this->getFunctionMock($this->getNamespace(WordPress::class), 'wp_is_post_revision');
        $wp_is_post_revision->expects($this->once())
                            ->with($this->identicalTo(42))
                            ->willReturn(true);

        $this->assertTrue(WordPress::isAutosaveOrRevision(42));
    }
}
