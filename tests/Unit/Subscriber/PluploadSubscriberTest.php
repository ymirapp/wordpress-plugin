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
use Ymir\Plugin\Subscriber\PluploadSubscriber;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Mock\WPScriptsMockTrait;

/**
 * @covers \Ymir\Plugin\Subscriber\PluploadSubscriber
 */
class PluploadSubscriberTest extends TestCase
{
    use FunctionMockTrait;
    use WPScriptsMockTrait;

    public function testEditDefaultSettings()
    {
        $rest_url = $this->getFunctionMock($this->getNamespace(PluploadSubscriber::class), 'rest_url');
        $rest_url->expects($this->exactly(2))
                 ->withConsecutive(
                     [$this->identicalTo('namespace/attachments')],
                     [$this->identicalTo('namespace/file-details')]
                 )
                 ->willReturnOnConsecutiveCalls('https://api.com/namespace/attachments', 'https://api.com/namespace/file-details');

        $this->assertSame([
            'attachments_endpoint_url' => 'https://api.com/namespace/attachments',
            'file_endpoint_url' => 'https://api.com/namespace/file-details',
        ], (new PluploadSubscriber('/foo', 'namespace'))->editDefaultSettings([]));
    }

    public function testGetSubscribedEvents()
    {
        $callbacks = PluploadSubscriber::getSubscribedEvents();

        foreach ($callbacks as $callback) {
            $this->assertTrue(method_exists(PluploadSubscriber::class, is_array($callback) ? $callback[0] : $callback));
        }

        $subscribedEvents = [
            'wp_default_scripts' => ['replacePluploadScripts', 99],
            'plupload_default_settings' => 'editDefaultSettings',
            'plupload_init' => 'editDefaultSettings',
        ];

        $this->assertSame($subscribedEvents, $callbacks);
    }

    public function testReplacePluploadScripts()
    {
        $scripts = $this->getWPScriptsMock();

        $scripts->expects($this->exactly(3))
                ->method('remove')
                ->withConsecutive(
                    [$this->identicalTo('plupload')],
                    [$this->identicalTo('plupload-handlers')],
                    [$this->identicalTo('wp-plupload')]
                );

        $scripts->expects($this->exactly(3))
                ->method('add')
                ->withConsecutive(
                    [$this->identicalTo('plupload'), $this->identicalTo('https://assets.com/plugin/assets/js/plupload.js'), $this->identicalTo(['moxiejs', 'wp-api-request'])],
                    [$this->identicalTo('plupload-handlers'), $this->identicalTo('https://assets.com/plugin/assets/js/handlers.js'), $this->identicalTo(['plupload', 'jquery'])],
                    [$this->identicalTo('wp-plupload'), $this->identicalTo('https://assets.com/plugin/assets/js/wp-plupload.js'), $this->identicalTo(['plupload', 'jquery', 'json2', 'media-models', 'wp-api-request'])]
                );

        $scripts->expects($this->exactly(2))
                ->method('localize')
                ->withConsecutive(
                    [$this->identicalTo('plupload-handlers'), $this->identicalTo('pluploadL10n'), $this->identicalTo(['error' => 'message'])],
                    [$this->identicalTo('wp-plupload'), $this->identicalTo('pluploadL10n'), $this->identicalTo(['error' => 'message'])]
                );

        (new PluploadSubscriber('/plugin', 'namespace', 'https://assets.com', ['error' => 'message']))->replacePluploadScripts($scripts);
    }
}
