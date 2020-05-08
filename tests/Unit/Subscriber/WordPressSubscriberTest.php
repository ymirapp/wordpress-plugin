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

use PHPUnit\Framework\TestCase;
use Ymir\Plugin\Subscriber\WordPressSubscriber;

/**
 * @covers \Ymir\Plugin\Subscriber\WordPressSubscriber
 */
class WordPressSubscriberTest extends TestCase
{
    public function testEnableUrlRewriteWithOtherServerSoftware()
    {
        $this->assertFalse((new WordPressSubscriber('PHP'))->enableUrlRewrite(false));
    }

    public function testEnableUrlRewriteWithYmirServerSoftware()
    {
        $this->assertTrue((new WordPressSubscriber('YMIR'))->enableUrlRewrite(false));
    }

    public function testEnableVisualEditorWithOtherServerSoftware()
    {
        $this->assertFalse((new WordPressSubscriber('PHP'))->enableVisualEditor(false));
    }

    public function testEnableVisualEditorWithYmirServerSoftware()
    {
        $this->assertTrue((new WordPressSubscriber('YMIR'))->enableVisualEditor(false));
    }

    public function testSanitizeFileNameCharacters()
    {
        $this->assertSame(
            ['?', '[', ']', '/', '\\', '=', '<', '>', ':', ';', ',', "'", '"', '&', '$', '#', '*', '(', ')', '|', '~', '`', '!', '{', '}', '+', chr(0)],
            (new WordPressSubscriber('ymir'))->sanitizeFileNameCharacters()
        );
    }
}
