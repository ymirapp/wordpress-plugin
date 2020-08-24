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
        $this->assertFalse((new WordPressSubscriber('PHP', 'https://'.$this->faker->domainName))->enableUrlRewrite(false));
    }

    public function testEnableUrlRewriteWithYmirServerSoftware()
    {
        $this->assertTrue((new WordPressSubscriber('YMIR', 'https://'.$this->faker->domainName))->enableUrlRewrite(false));
    }

    public function testEnableVisualEditorWithOtherServerSoftware()
    {
        $this->assertFalse((new WordPressSubscriber('PHP', 'https://'.$this->faker->domainName))->enableVisualEditor(false));
    }

    public function testEnableVisualEditorWithYmirServerSoftware()
    {
        $this->assertTrue((new WordPressSubscriber('YMIR', 'https://'.$this->faker->domainName))->enableVisualEditor(false));
    }

    public function testGetSubscribedEvents()
    {
        $callbacks = WordPressSubscriber::getSubscribedEvents();

        foreach ($callbacks as $callback) {
            $this->assertTrue(method_exists(WordPressSubscriber::class, is_array($callback) ? $callback[0] : $callback));
        }

        $subscribedEvents = [
            'got_url_rewrite' => 'enableUrlRewrite',
            'plugins_url' => 'rewritePluginUrl',
            'sanitize_file_name_chars' => 'sanitizeFileNameCharacters',
            'user_can_richedit' => 'enableVisualEditor',
        ];

        $this->assertSame($subscribedEvents, $callbacks);
    }

    public function testRewritePluginUrlOnlyKeepsDirectoryBelowPlugins()
    {
        $siteUrl = 'https://'.$this->faker->domainName;

        $this->assertSame($siteUrl.'/directory/plugins/test.php', (new WordPressSubscriber('PHP', $siteUrl))->rewritePluginUrl('https://'.$this->faker->domainName.'/foo/directory/plugins/test.php'));
    }

    public function testSanitizeFileNameCharacters()
    {
        $this->assertSame(
            ['?', '[', ']', '/', '\\', '=', '<', '>', ':', ';', ',', "'", '"', '&', '$', '#', '*', '(', ')', '|', '~', '`', '!', '{', '}', '+', chr(0)],
            (new WordPressSubscriber('YMIR', 'https://'.$this->faker->domainName))->sanitizeFileNameCharacters()
        );
    }
}
