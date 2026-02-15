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

namespace Ymir\Plugin\Tests\Unit\Subscriber\Compatibility;

use Ymir\Plugin\Subscriber\Compatibility\ElementorSubscriber;
use Ymir\Plugin\Tests\Mock\ContentDeliveryNetworkPageCacheClientInterfaceMockTrait;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\Subscriber\Compatibility\ElementorSubscriber
 */
class ElementorSubscriberTest extends TestCase
{
    use ContentDeliveryNetworkPageCacheClientInterfaceMockTrait;
    use FunctionMockTrait;

    public function testClearElementorLoopPageCacheOnDelete()
    {
        $get_post_type = $this->getFunctionMock($this->getNamespace(ElementorSubscriber::class), 'get_post_type');
        $get_post_type->expects($this->once())
                      ->with($this->identicalTo(42))
                      ->willReturn('page');

        $delete_transient = $this->getFunctionMock($this->getNamespace(ElementorSubscriber::class), 'delete_transient');
        $delete_transient->expects($this->once())
                         ->with($this->identicalTo('ymir_elementor_wc_loop_page_ids'));

        $this->createSubscriber()->clearElementorLoopPageCacheOnDelete(42);
    }

    public function testClearElementorLoopPageCacheOnDeleteDoesNothingForNonElementorPostType()
    {
        $get_post_type = $this->getFunctionMock($this->getNamespace(ElementorSubscriber::class), 'get_post_type');
        $get_post_type->expects($this->once())
                      ->with($this->identicalTo(42))
                      ->willReturn('post');

        $delete_transient = $this->getFunctionMock($this->getNamespace(ElementorSubscriber::class), 'delete_transient');
        $delete_transient->expects($this->never());

        $this->createSubscriber()->clearElementorLoopPageCacheOnDelete(42);
    }

    public function testClearElementorLoopPagesDoesNothingWhenInvalidationIsDisabled()
    {
        $get_transient = $this->getFunctionMock($this->getNamespace(ElementorSubscriber::class), 'get_transient');
        $get_transient->expects($this->never());

        $pageCacheClient = $this->getContentDeliveryNetworkPageCacheClientInterfaceMock();
        $pageCacheClient->expects($this->never())
                        ->method('clearUrls');

        $this->createSubscriber(['invalidation_enabled' => false], $pageCacheClient)->clearElementorLoopPages();
    }

    public function testClearElementorLoopPagesUsesCachedPageIds()
    {
        $get_transient = $this->getFunctionMock($this->getNamespace(ElementorSubscriber::class), 'get_transient');
        $get_transient->expects($this->once())
                      ->with($this->identicalTo('ymir_elementor_wc_loop_page_ids'))
                      ->willReturn([15]);

        $get_posts = $this->getFunctionMock($this->getNamespace(ElementorSubscriber::class), 'get_posts');
        $get_posts->expects($this->never());

        $set_transient = $this->getFunctionMock($this->getNamespace(ElementorSubscriber::class), 'set_transient');
        $set_transient->expects($this->never());

        $get_permalink = $this->getFunctionMock($this->getNamespace(ElementorSubscriber::class), 'get_permalink');
        $get_permalink->expects($this->once())
                      ->with($this->identicalTo(15))
                      ->willReturn('https://foo.com/shop-grid/');

        $pageCacheClient = $this->getContentDeliveryNetworkPageCacheClientInterfaceMock();
        $pageCacheClient->expects($this->once())
                        ->method('clearUrls')
                        ->with($this->callback(function ($urls) {
                            $this->assertSame(['https://foo.com/shop-grid/'], $urls->all());

                            return true;
                        }));

        $this->createSubscriber(['invalidation_enabled' => true], $pageCacheClient)->clearElementorLoopPages();
    }

    public function testClearElementorLoopPagesWithTransientMissDiscoversAndCachesPageIds()
    {
        $get_transient = $this->getFunctionMock($this->getNamespace(ElementorSubscriber::class), 'get_transient');
        $get_transient->expects($this->once())
                      ->with($this->identicalTo('ymir_elementor_wc_loop_page_ids'))
                      ->willReturn(false);

        $get_posts = $this->getFunctionMock($this->getNamespace(ElementorSubscriber::class), 'get_posts');
        $get_posts->expects($this->once())
                  ->willReturn([10, 20]);

        $get_post_meta = $this->getFunctionMock($this->getNamespace(ElementorSubscriber::class), 'get_post_meta');
        $get_post_meta->expects($this->exactly(2))
                      ->withConsecutive(
                          [10, '_elementor_data', true],
                          [20, '_elementor_data', true]
                      )
                      ->willReturnOnConsecutiveCalls(
                          '{"widgetType":"woocommerce-products"}',
                          '{"widgetType":"heading"}'
                      );

        $set_transient = $this->getFunctionMock($this->getNamespace(ElementorSubscriber::class), 'set_transient');
        $set_transient->expects($this->once())
                      ->with(
                          $this->identicalTo('ymir_elementor_wc_loop_page_ids'),
                          $this->identicalTo([10]),
                          $this->identicalTo(600)
                      )
                      ->willReturn(true);

        $get_permalink = $this->getFunctionMock($this->getNamespace(ElementorSubscriber::class), 'get_permalink');
        $get_permalink->expects($this->once())
                      ->with($this->identicalTo(10))
                      ->willReturn('https://foo.com/shop-grid/');

        $pageCacheClient = $this->getContentDeliveryNetworkPageCacheClientInterfaceMock();
        $pageCacheClient->expects($this->once())
                        ->method('clearUrls')
                        ->with($this->callback(function ($urls) {
                            $this->assertSame(['https://foo.com/shop-grid/'], $urls->all());

                            return true;
                        }));

        $this->createSubscriber(['invalidation_enabled' => true], $pageCacheClient)->clearElementorLoopPages();
    }

    public function testGetSubscribedEvents()
    {
        $callbacks = ElementorSubscriber::getSubscribedEvents();

        foreach ($callbacks as $callback) {
            $this->assertTrue(method_exists(ElementorSubscriber::class, is_array($callback) ? $callback[0] : $callback));
        }

        $subscribedEvents = [
            'woocommerce_update_product' => 'clearElementorLoopPages',
            'woocommerce_update_product_variation' => 'clearElementorLoopPages',
            'save_post_product' => ['clearElementorLoopPagesOnSave', 20, 3],
            'save_post_product_variation' => ['clearElementorLoopPagesOnSave', 20, 3],
            'save_post_page' => ['clearElementorLoopPageCache', 10, 3],
            'save_post_elementor_library' => ['clearElementorLoopPageCache', 10, 3],
            'deleted_post' => ['clearElementorLoopPageCacheOnDelete', 10, 1],
        ];

        $this->assertSame($subscribedEvents, $callbacks);
    }

    /**
     * Create a new ElementorSubscriber instance.
     */
    private function createSubscriber(array $pageCachingOptions = [], $pageCacheClient = null): ElementorSubscriber
    {
        return new ElementorSubscriber($pageCacheClient ?: $this->getContentDeliveryNetworkPageCacheClientInterfaceMock(), $pageCachingOptions);
    }
}
