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

namespace Ymir\Plugin\Subscriber;

use Ymir\Plugin\EventManagement\AbstractEventManagerAwareSubscriber;
use Ymir\Plugin\PageCache\ContentDeliveryNetworkPageCacheClientInterface;
use Ymir\Plugin\Support\Collection;

/**
 * Subscriber that handles interaction with the content delivery network handling page caching.
 */
class ContentDeliveryNetworkPageCachingSubscriber extends AbstractEventManagerAwareSubscriber
{
    /**
     * Client interacting with the content delivery network handling page caching.
     *
     * @var ContentDeliveryNetworkPageCacheClientInterface
     */
    private $pageCacheClient;

    /**
     * Flag whether page caching is disabled or not.
     *
     * @var bool
     */
    private $pageCachingDisabled;

    /**
     * Base URL for the WordPress REST API endpoints.
     *
     * @var string
     */
    private $restBaseUrl;

    /**
     * Constructor.
     */
    public function __construct(ContentDeliveryNetworkPageCacheClientInterface $pageCacheClient, string $restUrl, bool $pageCachingDisabled = false)
    {
        $this->pageCacheClient = $pageCacheClient;
        $this->pageCachingDisabled = $pageCachingDisabled;
        $this->restBaseUrl = rtrim($restUrl, '/').'/wp/v2';
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'delete_attachment' => ['clearPost', 10, 2],
            'deleted_post' => ['clearPost', 10, 2],
            'edit_post' => ['clearPost', 10, 2],
            'import_start' => 'clearCache',
            'import_end' => 'clearCache',
            'save_post' => ['clearPost', 10, 2],
            'shutdown' => 'sendClearRequest',
            'switch_theme' => 'clearCache',
            'customize_save_after' => 'clearCache',
            'trashed_post' => ['clearPost', 10, 2],
        ];
    }

    /**
     * Clear the entire page cache.
     */
    public function clearCache()
    {
        $this->eventManager->execute('ymir_page_caching_clear_all');

        $this->pageCacheClient->clearAll();
    }

    /**
     * Clear all the URLs related to the given post for the page cache.
     */
    public function clearPost($postId)
    {
        if ($this->pageCachingDisabled) {
            return;
        }

        $permalink = get_permalink($postId);
        $post = get_post($postId);

        if (!$post instanceof \WP_Post
            || !is_string($permalink)
            || !in_array($post->post_status, ['publish', 'private', 'trash', 'pending', 'draft'], true)
            || in_array($post->post_type, ['nav_menu_item', 'revision'], true)
        ) {
            return;
        }

        if ('trash' === $post->post_status) {
            $permalink = str_replace('__trashed', '', $permalink);
        }

        $permalink = rtrim($permalink, '/').'/';
        $postType = get_post_type_object($postId);
        $urlsToClear = new Collection([$permalink, rtrim(home_url(), '/').'/']);

        // Custom post archive
        if ('page' === get_site_option('show_on_front') && !empty(get_site_option('page_for_posts'))) {
            $urlsToClear[] = get_permalink(get_site_option('page_for_posts'));
        }

        // REST API endpoint
        if ($postType instanceof \WP_Post_Type && !empty($postType->rest_base)) {
            $urlsToClear[] = $this->restBaseUrl.sprintf('/%s/%s/', $postType->rest_base, $postId);
        }

        // Category URLs
        $categories = (new Collection(get_the_category($postId)))->filter(function ($category) {
            return $category instanceof \WP_Term;
        });
        $urlsToClear = $urlsToClear->merge($categories->map(function (\WP_Term $category) {
            return get_category_link($category);
        }));
        $urlsToClear = $urlsToClear->merge($categories->map(function (\WP_Term $category) {
            return !empty($category->term_id) ? $this->restBaseUrl.sprintf('/categories/%s/', $category->term_id) : '';
        }));

        // Tag URLs
        $tagBase = get_site_option('tag_base');
        $tags = (new Collection(get_the_tags($postId)))->filter(function ($tag) {
            return $tag instanceof \WP_Term;
        });

        if (empty($tagBase)) {
            $tagBase = 'tag';
        }

        $urlsToClear = $urlsToClear->merge($tags->map(function (\WP_Term $tag) {
            return get_tag_link($tag);
        }));
        $urlsToClear = $urlsToClear->merge($tags->map(function (\WP_Term $tag) use ($tagBase) {
            return !empty($tag->term_id) ? $this->restBaseUrl.sprintf('/%s/%s/', trim($tagBase, '/'), $tag->term_id) : '';
        }));

        // Taxonomy URLs
        $taxonomyTerms = (new Collection(get_post_taxonomies($postId)))->filter(function ($taxonomy) {
            $taxonomy = get_taxonomy($taxonomy);

            return $taxonomy instanceof \WP_Taxonomy && $taxonomy->public;
        })->flatMap(function (string $taxonomy) use ($postId) {
            $terms = wp_get_post_terms($postId, $taxonomy);

            return is_array($terms) ? $terms : [];
        })->filter(function ($term) {
            return $term instanceof \WP_Term;
        });
        $urlsToClear = $urlsToClear->merge($taxonomyTerms->map(function (\WP_Term $taxonomyTerm) {
            return get_term_link($taxonomyTerm);
        }));
        $urlsToClear = $urlsToClear->merge($taxonomyTerms->map(function (\WP_Term $taxonomyTerm) {
            return isset($taxonomyTerm->slug, $taxonomyTerm->taxonomy) ? $this->restBaseUrl.sprintf('/%s/%s/', $taxonomyTerm->taxonomy, $taxonomyTerm->slug) : '';
        }));

        // Add Post related URLs
        if ('post' === $post->post_type) {
            // Author URLs
            $urlsToClear[] = get_author_posts_url((int) $post->post_author);
            $urlsToClear[] = get_author_feed_link((int) $post->post_author);
            $urlsToClear[] = $this->restBaseUrl.sprintf('/users/%s/', $post->post_author);

            // Feed URLs
            $urlsToClear[] = get_bloginfo_rss('rdf_url');
            $urlsToClear[] = get_bloginfo_rss('rss_url');
            $urlsToClear[] = get_bloginfo_rss('rss2_url');
            $urlsToClear[] = get_bloginfo_rss('atom_url');
            $urlsToClear[] = get_bloginfo_rss('comments_rss2_url');
            $urlsToClear[] = get_post_comments_feed_link($postId);
        }

        // Custom post type archive and feed URLs
        if (!in_array($post->post_type, ['post', 'page'], true) && !empty($post->post_type)) {
            $urlsToClear[] = get_post_type_archive_link($post->post_type);
            $urlsToClear[] = get_post_type_archive_feed_link($post->post_type);
        }

        $urlsToClear = $this->eventManager->filter('ymir_page_caching_urls_to_clear', $urlsToClear);

        if (!$urlsToClear instanceof Collection) {
            return;
        }

        $urlsToClear->filter(function ($url) {
            return is_string($url) && !empty($url);
        })->each(function (string $url) {
            $this->pageCacheClient->clearUrl($url);
        });
    }

    /**
     * Send request to content delivery network to clear all requested URLs from its cache.
     */
    public function sendClearRequest()
    {
        $this->eventManager->execute('ymir_page_caching_send_clear_request');

        $this->pageCacheClient->sendClearRequest();
    }
}
