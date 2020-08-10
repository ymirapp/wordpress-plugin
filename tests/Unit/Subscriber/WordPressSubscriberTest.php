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

use Ymir\Plugin\Subscriber\WordPressSubscriber;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\Subscriber\WordPressSubscriber
 */
class WordPressSubscriberTest extends TestCase
{
    public function testEnableUrlRewriteWithOtherServerSoftware()
    {
        $this->assertFalse((new WordPressSubscriber('PHP', $this->faker->url))->enableUrlRewrite(false));
    }

    public function testEnableUrlRewriteWithYmirServerSoftware()
    {
        $this->assertTrue((new WordPressSubscriber('YMIR', $this->faker->url))->enableUrlRewrite(false));
    }

    public function testEnableVisualEditorWithOtherServerSoftware()
    {
        $this->assertFalse((new WordPressSubscriber('PHP', $this->faker->url))->enableVisualEditor(false));
    }

    public function testEnableVisualEditorWithYmirServerSoftware()
    {
        $this->assertTrue((new WordPressSubscriber('YMIR', $this->faker->url))->enableVisualEditor(false));
    }

    public function testRewritePluginUrlOnlyKeepsDirectoryBelowPlugins()
    {
        $siteUrl = $this->faker->url;

        $this->assertSame($siteUrl.'/directory/plugins/test.php', (new WordPressSubscriber('PHP', $siteUrl))->rewritePluginUrl($this->faker->url.'/foo/directory/plugins/test.php'));
    }

    public function testSanitizeFileNameCharacters()
    {
        $this->assertSame(
            ['?', '[', ']', '/', '\\', '=', '<', '>', ':', ';', ',', "'", '"', '&', '$', '#', '*', '(', ')', '|', '~', '`', '!', '{', '}', '+', chr(0)],
            (new WordPressSubscriber('YMIR', $this->faker->url))->sanitizeFileNameCharacters()
        );
    }
}
