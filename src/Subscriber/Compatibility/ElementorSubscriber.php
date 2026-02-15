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

namespace Ymir\Plugin\Subscriber\Compatibility;

use Ymir\Plugin\EventManagement\SubscriberInterface;
use Ymir\Plugin\PageCache\ContentDeliveryNetworkPageCacheClientInterface;
use Ymir\Plugin\Support\Collection;

/**
 * Subscriber that handles Elementor compatibility.
 */
class ElementorSubscriber implements SubscriberInterface
{
    /**
     * Transient key containing Elementor page IDs with WooCommerce loops.
     *
     * @var string
     */
    private const LOOP_PAGE_IDS_TRANSIENT = 'ymir_elementor_wc_loop_page_ids';

    /**
     * Client interacting with the content delivery network handling page caching.
     *
     * @var ContentDeliveryNetworkPageCacheClientInterface
     */
    private $pageCacheClient;

    /**
     * The page caching options.
     *
     * @var array
     */
    private $pageCachingOptions;

    /**
     * Constructor.
     */
    public function __construct(ContentDeliveryNetworkPageCacheClientInterface $pageCacheClient, array $pageCachingOptions = [])
    {
        $this->pageCacheClient = $pageCacheClient;
        $this->pageCachingOptions = $pageCachingOptions;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'woocommerce_update_product' => 'clearElementorLoopPages',
            'woocommerce_update_product_variation' => 'clearElementorLoopPages',
            'save_post_product' => ['clearElementorLoopPagesOnSave', 20, 3],
            'save_post_product_variation' => ['clearElementorLoopPagesOnSave', 20, 3],
            'save_post_page' => ['clearElementorLoopPageCache', 10, 3],
            'save_post_elementor_library' => ['clearElementorLoopPageCache', 10, 3],
            'deleted_post' => ['clearElementorLoopPageCacheOnDelete', 10, 1],
        ];
    }

    /**
     * Clear loop page discovery transient when Elementor content can change.
     */
    public function clearElementorLoopPageCache(int $postId)
    {
        if ($this->isAutosaveOrRevision($postId)) {
            return;
        }

        $this->clearElementorLoopPagesCacheTransient();
    }

    /**
     * Clear loop page discovery transient when relevant posts are deleted.
     */
    public function clearElementorLoopPageCacheOnDelete(int $postId)
    {
        if (!in_array(get_post_type($postId), ['page', 'elementor_library'], true)) {
            return;
        }

        $this->clearElementorLoopPagesCacheTransient();
    }

    /**
     * Clear cache for Elementor loop pages.
     */
    public function clearElementorLoopPages()
    {
        if (empty($this->pageCachingOptions['invalidation_enabled'])) {
            return;
        }

        $loopPagesIds = $this->getElementorLoopPageIds();

        if ($loopPagesIds->isEmpty()) {
            return;
        }

        $urlsToClear = $loopPagesIds->map(function (int $pageId) {
            return get_permalink($pageId);
        })->filter(function ($url) {
            return is_string($url) && '' !== $url;
        });

        if ($urlsToClear->isEmpty()) {
            return;
        }

        $this->pageCacheClient->clearUrls($urlsToClear);
    }

    /**
     * Clear Elementor loop page cache when a product is saved directly.
     */
    public function clearElementorLoopPagesOnSave(int $postId)
    {
        if ($this->isAutosaveOrRevision($postId)) {
            return;
        }

        $this->clearElementorLoopPages();
    }

    /**
     * Clear Elementor loop pages discovery transient.
     */
    private function clearElementorLoopPagesCacheTransient(): void
    {
        delete_transient(self::LOOP_PAGE_IDS_TRANSIENT);
    }

    /**
     * Get Elementor page IDs likely containing WooCommerce loops.
     */
    private function getElementorLoopPageIds(): Collection
    {
        $loopPagesIds = get_transient(self::LOOP_PAGE_IDS_TRANSIENT);

        if (is_array($loopPagesIds)) {
            return new Collection($loopPagesIds);
        }

        $loopPagesIds = (new Collection(get_posts([
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_elementor_data',
                    'compare' => 'EXISTS',
                ],
                [
                    'relation' => 'OR',
                    [
                        'key' => '_elementor_data',
                        'value' => 'woocommerce-products',
                        'compare' => 'LIKE',
                    ],
                    [
                        'key' => '_elementor_data',
                        'value' => 'archive-products',
                        'compare' => 'LIKE',
                    ],
                    [
                        'key' => '_elementor_data',
                        'value' => 'loop-grid',
                        'compare' => 'LIKE',
                    ],
                    [
                        'key' => '_elementor_data',
                        'value' => 'product_cat',
                        'compare' => 'LIKE',
                    ],
                    [
                        'key' => '_elementor_data',
                        'value' => 'product_tag',
                        'compare' => 'LIKE',
                    ],
                ],
            ],
        ])))->filter(function ($pageId) {
            return is_int($pageId) || ctype_digit((string) $pageId);
        })->map(function ($pageId) {
            return (int) $pageId;
        })->filter(function (int $pageId) {
            $elementorData = get_post_meta($pageId, '_elementor_data', true);

            if (empty($elementorData) || !is_string($elementorData)) {
                return false;
            }

            return str_contains($elementorData, 'archive-products')
                || str_contains($elementorData, 'woocommerce-products')
                || (str_contains($elementorData, 'loop-grid')
                    && (str_contains($elementorData, '"product"')
                        || str_contains($elementorData, 'product_cat')
                        || str_contains($elementorData, 'product_tag')));
        })->unique()->all();

        set_transient(self::LOOP_PAGE_IDS_TRANSIENT, $loopPagesIds, 10 * MINUTE_IN_SECONDS);

        return new Collection($loopPagesIds);
    }

    /**
     * Check if a post save is an autosave or revision.
     */
    private function isAutosaveOrRevision(int $postId): bool
    {
        return (bool) wp_is_post_autosave($postId) || (bool) wp_is_post_revision($postId);
    }
}
