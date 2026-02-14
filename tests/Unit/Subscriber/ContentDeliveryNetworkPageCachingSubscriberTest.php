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

use Ymir\Plugin\Subscriber\ContentDeliveryNetworkPageCachingSubscriber;
use Ymir\Plugin\Support\Collection;
use Ymir\Plugin\Tests\Mock\ContentDeliveryNetworkPageCacheClientInterfaceMockTrait;
use Ymir\Plugin\Tests\Mock\EventManagerMockTrait;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Mock\WPPostMockTrait;
use Ymir\Plugin\Tests\Mock\WPPostTypeMockTrait;
use Ymir\Plugin\Tests\Mock\WPTaxonomyMockTrait;
use Ymir\Plugin\Tests\Mock\WPTermMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\Subscriber\ContentDeliveryNetworkPageCachingSubscriber
 */
class ContentDeliveryNetworkPageCachingSubscriberTest extends TestCase
{
    use ContentDeliveryNetworkPageCacheClientInterfaceMockTrait;
    use EventManagerMockTrait;
    use FunctionMockTrait;
    use WPPostMockTrait;
    use WPPostTypeMockTrait;
    use WPTaxonomyMockTrait;
    use WPTermMockTrait;

    public function testClearCache()
    {
        $eventManager = $this->getEventManagerMock();
        $eventManager->expects($this->once())
                     ->method('execute')
                     ->with($this->identicalTo('ymir_page_caching_clear_all'));

        $pageCacheClient = $this->getContentDeliveryNetworkPageCacheClientInterfaceMock();
        $pageCacheClient->expects($this->once())
                        ->method('clearAll');

        $subscriber = new ContentDeliveryNetworkPageCachingSubscriber($pageCacheClient, 'rest_url');

        $subscriber->setEventManager($eventManager);

        $subscriber->clearCache();
    }

    public function testClearPostAddsCategoryUrls()
    {
        $category = $this->getWPTermMock();
        $category->term_id = 24;

        $eventManager = $this->getEventManagerMock();
        $eventManager->expects($this->once())
                     ->method('filter')
                     ->with($this->identicalTo('ymir_page_caching_urls_to_clear'), $this->isInstanceOf(Collection::class), $this->identicalTo(42))
                     ->willReturn($this->returnArgument(1));

        $post = $this->getWPPostMock();
        $post->post_status = 'publish';
        $post->post_type = 'page';

        $get_post = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post');
        $get_post->expects($this->once())
                 ->with($this->identicalTo(42))
                 ->willReturn($post);

        $get_post_type_object = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post_type_object');
        $get_post_type_object->expects($this->once())
                             ->with($this->identicalTo(42))
                             ->willReturn(false);

        $get_permalink = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_permalink');
        $get_permalink->expects($this->once())
                      ->with($this->identicalTo(42))
                      ->willReturn('permalink');

        $get_site_option = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_site_option');
        $get_site_option->expects($this->exactly(2))
                        ->withConsecutive(
                            [$this->identicalTo('show_on_front')],
                            [$this->identicalTo('tag_base')]
                        )
                        ->willReturnOnConsecutiveCalls(false, '/tag/');

        $get_the_category = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_the_category');
        $get_the_category->expects($this->once())
                         ->with($this->identicalTo(42))
                         ->willReturn([$category]);

        $get_the_tags = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_the_tags');
        $get_the_tags->expects($this->once())
                     ->with($this->identicalTo(42))
                     ->willReturn([]);

        $get_post_taxonomies = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post_taxonomies');
        $get_post_taxonomies->expects($this->once())
                            ->with($this->identicalTo(42))
                            ->willReturn([]);

        $home_url = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'home_url');
        $home_url->expects($this->once())
                 ->willReturn('home_url');

        $get_category_link = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_category_link');
        $get_category_link->expects($this->once())
                          ->with($this->identicalTo($category))
                          ->willReturn('category_url');

        $pageCacheClient = $this->getContentDeliveryNetworkPageCacheClientInterfaceMock();
        $pageCacheClient->expects($this->exactly(4))
                        ->method('clearUrl')
                        ->withConsecutive(
                            [$this->identicalTo('permalink/')],
                            [$this->identicalTo('home_url/')],
                            [$this->identicalTo('category_url')],
                            [$this->identicalTo('rest_url/wp/v2/categories/24/')]
                        );

        $subscriber = new ContentDeliveryNetworkPageCachingSubscriber($pageCacheClient, 'rest_url', ['invalidation_enabled' => true]);

        $subscriber->setEventManager($eventManager);

        $subscriber->clearPost(42);
    }

    public function testClearPostAddsCustomPostArchive()
    {
        $eventManager = $this->getEventManagerMock();
        $eventManager->expects($this->once())
                     ->method('filter')
                     ->with($this->identicalTo('ymir_page_caching_urls_to_clear'), $this->isInstanceOf(Collection::class), $this->identicalTo(42))
                     ->willReturn($this->returnArgument(1));

        $post = $this->getWPPostMock();
        $post->post_status = 'publish';
        $post->post_type = 'page';

        $get_post = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post');
        $get_post->expects($this->once())
                 ->with($this->identicalTo(42))
                 ->willReturn($post);

        $get_post_type_object = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post_type_object');
        $get_post_type_object->expects($this->once())
                             ->with($this->identicalTo(42))
                             ->willReturn(false);

        $get_permalink = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_permalink');
        $get_permalink->expects($this->exactly(2))
                      ->withConsecutive(
                          [$this->identicalTo(42)],
                          [$this->identicalTo(24)]
                      )
                      ->willReturnOnConsecutiveCalls('permalink', 'post_archive_permalink');

        $get_site_option = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_site_option');
        $get_site_option->expects($this->exactly(4))
                        ->withConsecutive(
                            [$this->identicalTo('show_on_front')],
                            [$this->identicalTo('page_for_posts')],
                            [$this->identicalTo('page_for_posts')],
                            [$this->identicalTo('tag_base')]
                        )
                        ->willReturnOnConsecutiveCalls('page', 24, 24, '/tag/');

        $get_the_category = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_the_category');
        $get_the_category->expects($this->once())
                         ->with($this->identicalTo(42))
                         ->willReturn([]);

        $get_the_tags = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_the_tags');
        $get_the_tags->expects($this->once())
                     ->with($this->identicalTo(42))
                     ->willReturn([]);

        $get_post_taxonomies = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post_taxonomies');
        $get_post_taxonomies->expects($this->once())
                            ->with($this->identicalTo(42))
                            ->willReturn([]);

        $home_url = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'home_url');
        $home_url->expects($this->once())
                 ->willReturn('home_url');

        $pageCacheClient = $this->getContentDeliveryNetworkPageCacheClientInterfaceMock();
        $pageCacheClient->expects($this->exactly(3))
            ->method('clearUrl')
            ->withConsecutive(
                [$this->identicalTo('permalink/')],
                [$this->identicalTo('home_url/')],
                [$this->identicalTo('post_archive_permalink')]
            );

        $subscriber = new ContentDeliveryNetworkPageCachingSubscriber($pageCacheClient, 'rest_url', ['invalidation_enabled' => true]);

        $subscriber->setEventManager($eventManager);

        $subscriber->clearPost(42);
    }

    public function testClearPostAddsCustomPostTypeArchiveAndFeed()
    {
        $eventManager = $this->getEventManagerMock();
        $eventManager->expects($this->once())
                     ->method('filter')
                     ->with($this->identicalTo('ymir_page_caching_urls_to_clear'), $this->isInstanceOf(Collection::class), $this->identicalTo(42))
                     ->willReturn($this->returnArgument(1));

        $post = $this->getWPPostMock();
        $post->post_status = 'publish';
        $post->post_type = 'custom_post_type';

        $get_post = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post');
        $get_post->expects($this->once())
                 ->with($this->identicalTo(42))
                 ->willReturn($post);

        $get_post_type_object = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post_type_object');
        $get_post_type_object->expects($this->once())
                             ->with($this->identicalTo(42))
                             ->willReturn(false);

        $get_permalink = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_permalink');
        $get_permalink->expects($this->once())
                      ->with($this->identicalTo(42))
                      ->willReturn('permalink');

        $get_site_option = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_site_option');
        $get_site_option->expects($this->exactly(2))
                        ->withConsecutive(
                            [$this->identicalTo('show_on_front')],
                            [$this->identicalTo('tag_base')]
                        )
                        ->willReturnOnConsecutiveCalls(false, '/tag/');

        $get_the_category = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_the_category');
        $get_the_category->expects($this->once())
                         ->with($this->identicalTo(42))
                         ->willReturn([]);

        $get_the_tags = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_the_tags');
        $get_the_tags->expects($this->once())
                     ->with($this->identicalTo(42))
                     ->willReturn([]);

        $get_post_taxonomies = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post_taxonomies');
        $get_post_taxonomies->expects($this->once())
                            ->with($this->identicalTo(42))
                            ->willReturn([]);

        $home_url = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'home_url');
        $home_url->expects($this->once())
                 ->willReturn('home_url');

        $get_post_type_archive_link = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post_type_archive_link');
        $get_post_type_archive_link->expects($this->once())
                                  ->with($this->identicalTo('custom_post_type'))
                                  ->willReturn('custom_post_type_archive_url');

        $get_post_type_archive_feed_link = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post_type_archive_feed_link');
        $get_post_type_archive_feed_link->expects($this->once())
                                        ->with($this->identicalTo('custom_post_type'))
                                        ->willReturn('custom_post_type_archive_feed_url');

        $pageCacheClient = $this->getContentDeliveryNetworkPageCacheClientInterfaceMock();
        $pageCacheClient->expects($this->exactly(4))
                        ->method('clearUrl')
                        ->withConsecutive(
                            [$this->identicalTo('permalink/')],
                            [$this->identicalTo('home_url/')],
                            [$this->identicalTo('custom_post_type_archive_url')],
                            [$this->identicalTo('custom_post_type_archive_feed_url')]
                        );

        $subscriber = new ContentDeliveryNetworkPageCachingSubscriber($pageCacheClient, 'rest_url', ['invalidation_enabled' => true]);

        $subscriber->setEventManager($eventManager);

        $subscriber->clearPost(42);
    }

    public function testClearPostAddsPostRelatedUrls()
    {
        $eventManager = $this->getEventManagerMock();
        $eventManager->expects($this->once())
                     ->method('filter')
                     ->with($this->identicalTo('ymir_page_caching_urls_to_clear'), $this->isInstanceOf(Collection::class), $this->identicalTo(42))
                     ->willReturn($this->returnArgument(1));

        $post = $this->getWPPostMock();
        $post->post_author = '24';
        $post->post_status = 'publish';
        $post->post_type = 'post';

        $get_post = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post');
        $get_post->expects($this->once())
                 ->with($this->identicalTo(42))
                 ->willReturn($post);

        $get_post_type_object = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post_type_object');
        $get_post_type_object->expects($this->once())
                             ->with($this->identicalTo(42))
                             ->willReturn(false);

        $get_permalink = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_permalink');
        $get_permalink->expects($this->once())
                      ->with($this->identicalTo(42))
                      ->willReturn('permalink');

        $get_site_option = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_site_option');
        $get_site_option->expects($this->exactly(2))
                        ->withConsecutive(
                            [$this->identicalTo('show_on_front')],
                            [$this->identicalTo('tag_base')]
                        )
                        ->willReturnOnConsecutiveCalls(false, '/tag/');

        $get_the_category = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_the_category');
        $get_the_category->expects($this->once())
                         ->with($this->identicalTo(42))
                         ->willReturn([]);

        $get_the_tags = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_the_tags');
        $get_the_tags->expects($this->once())
                     ->with($this->identicalTo(42))
                     ->willReturn([]);

        $get_post_taxonomies = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post_taxonomies');
        $get_post_taxonomies->expects($this->once())
                            ->with($this->identicalTo(42))
                            ->willReturn([]);

        $home_url = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'home_url');
        $home_url->expects($this->once())
                 ->willReturn('home_url');

        $get_author_posts_url = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_author_posts_url');
        $get_author_posts_url->expects($this->once())
                             ->with($this->identicalTo(24))
                             ->willReturn('author_posts_url');

        $get_author_feed_link = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_author_feed_link');
        $get_author_feed_link->expects($this->once())
                             ->with($this->identicalTo(24))
                             ->willReturn('author_feed_url');

        $get_bloginfo_rss = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_bloginfo_rss');
        $get_bloginfo_rss->expects($this->exactly(5))
                         ->withConsecutive(
                             [$this->identicalTo('rdf_url')],
                             [$this->identicalTo('rss_url')],
                             [$this->identicalTo('rss2_url')],
                             [$this->identicalTo('atom_url')],
                             [$this->identicalTo('comments_rss2_url')]
                         )
                         ->willReturnOnConsecutiveCalls('rdf_url', 'rss_url', 'rss2_url', 'atom_url', 'comments_rss2_url');

        $get_post_comments_feed_link = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post_comments_feed_link');
        $get_post_comments_feed_link->expects($this->once())
                                    ->with($this->identicalTo(42))
                                    ->willReturn('post_commments_feed_url');

        $pageCacheClient = $this->getContentDeliveryNetworkPageCacheClientInterfaceMock();
        $pageCacheClient->expects($this->exactly(11))
                        ->method('clearUrl')
                        ->withConsecutive(
                            [$this->identicalTo('permalink/')],
                            [$this->identicalTo('home_url/')],
                            [$this->identicalTo('author_posts_url')],
                            [$this->identicalTo('author_feed_url')],
                            [$this->identicalTo('rest_url/wp/v2/users/24/')],
                            [$this->identicalTo('rdf_url')],
                            [$this->identicalTo('rss_url')],
                            [$this->identicalTo('rss2_url')],
                            [$this->identicalTo('atom_url')],
                            [$this->identicalTo('comments_rss2_url')],
                            [$this->identicalTo('post_commments_feed_url')]
                        );

        $subscriber = new ContentDeliveryNetworkPageCachingSubscriber($pageCacheClient, 'rest_url', ['invalidation_enabled' => true]);

        $subscriber->setEventManager($eventManager);

        $subscriber->clearPost(42);
    }

    public function testClearPostAddsPublicTaxonomyUrls()
    {
        $eventManager = $this->getEventManagerMock();
        $eventManager->expects($this->once())
                     ->method('filter')
                     ->with($this->identicalTo('ymir_page_caching_urls_to_clear'), $this->isInstanceOf(Collection::class), $this->identicalTo(42))
                     ->willReturn($this->returnArgument(1));

        $post = $this->getWPPostMock();
        $post->post_status = 'publish';
        $post->post_type = 'page';

        $taxonomy = $this->getWPTaxonomyMock();
        $taxonomy->public = true;

        $taxonomyTerm = $this->getWPTermMock();
        $taxonomyTerm->term_id = 24;
        $taxonomyTerm->slug = 'slug';
        $taxonomyTerm->taxonomy = 'taxonomy';

        $get_post = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post');
        $get_post->expects($this->once())
                 ->with($this->identicalTo(42))
                 ->willReturn($post);

        $get_post_type_object = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post_type_object');
        $get_post_type_object->expects($this->once())
                             ->with($this->identicalTo(42))
                             ->willReturn(false);

        $get_permalink = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_permalink');
        $get_permalink->expects($this->once())
                      ->with($this->identicalTo(42))
                      ->willReturn('permalink');

        $get_site_option = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_site_option');
        $get_site_option->expects($this->exactly(2))
                        ->withConsecutive(
                            [$this->identicalTo('show_on_front')],
                            [$this->identicalTo('tag_base')]
                        )
                        ->willReturnOnConsecutiveCalls(false, '/tag/');

        $get_the_category = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_the_category');
        $get_the_category->expects($this->once())
                        ->with($this->identicalTo(42))
                        ->willReturn([]);

        $get_the_tags = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_the_tags');
        $get_the_tags->expects($this->once())
                     ->with($this->identicalTo(42))
                     ->willReturn([]);

        $get_post_taxonomies = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post_taxonomies');
        $get_post_taxonomies->expects($this->once())
                            ->with($this->identicalTo(42))
                            ->willReturn(['taxonomy']);

        $home_url = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'home_url');
        $home_url->expects($this->once())
                 ->willReturn('home_url');

        $get_taxonomy = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_taxonomy');
        $get_taxonomy->expects($this->once())
                     ->with($this->identicalTo('taxonomy'))
                     ->willReturn($taxonomy);

        $get_term_link = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_term_link');
        $get_term_link->expects($this->once())
                      ->with($this->identicalTo($taxonomyTerm))
                      ->willReturn('taxonomy_url');

        $wp_get_post_terms = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'wp_get_post_terms');
        $wp_get_post_terms->expects($this->once())
                          ->with($this->identicalTo(42), $this->identicalTo('taxonomy'))
                          ->willReturn([$taxonomyTerm]);

        $pageCacheClient = $this->getContentDeliveryNetworkPageCacheClientInterfaceMock();
        $pageCacheClient->expects($this->exactly(4))
                        ->method('clearUrl')
                        ->withConsecutive(
                            [$this->identicalTo('permalink/')],
                            [$this->identicalTo('home_url/')],
                            [$this->identicalTo('taxonomy_url')],
                            [$this->identicalTo('rest_url/wp/v2/taxonomy/slug/')]
                        );

        $subscriber = new ContentDeliveryNetworkPageCachingSubscriber($pageCacheClient, 'rest_url', ['invalidation_enabled' => true]);

        $subscriber->setEventManager($eventManager);

        $subscriber->clearPost(42);
    }

    public function testClearPostAddsRestApiEndpointIfWeHavePostTypeObject()
    {
        $eventManager = $this->getEventManagerMock();
        $eventManager->expects($this->once())
                     ->method('filter')
                     ->with($this->identicalTo('ymir_page_caching_urls_to_clear'), $this->isInstanceOf(Collection::class), $this->identicalTo(42))
                     ->willReturn($this->returnArgument(1));

        $post = $this->getWPPostMock();
        $post->post_status = 'publish';
        $post->post_type = 'page';

        $postType = $this->getWPPostTypeMock();
        $postType->rest_base = 'pages';

        $get_post = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post');
        $get_post->expects($this->once())
                 ->with($this->identicalTo(42))
                 ->willReturn($post);

        $get_post_type_object = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post_type_object');
        $get_post_type_object->expects($this->once())
                            ->with($this->identicalTo(42))
                            ->willReturn($postType);

        $get_permalink = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_permalink');
        $get_permalink->expects($this->once())
                      ->with($this->identicalTo(42))
                      ->willReturn('permalink');

        $get_site_option = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_site_option');
        $get_site_option->expects($this->exactly(2))
                        ->withConsecutive(
                            [$this->identicalTo('show_on_front')],
                            [$this->identicalTo('tag_base')]
                        )
                        ->willReturnOnConsecutiveCalls(false, '/tag/');

        $get_the_category = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_the_category');
        $get_the_category->expects($this->once())
                         ->with($this->identicalTo(42))
                         ->willReturn([]);

        $get_the_tags = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_the_tags');
        $get_the_tags->expects($this->once())
                     ->with($this->identicalTo(42))
                     ->willReturn([]);

        $get_post_taxonomies = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post_taxonomies');
        $get_post_taxonomies->expects($this->once())
                            ->with($this->identicalTo(42))
                            ->willReturn([]);

        $home_url = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'home_url');
        $home_url->expects($this->once())
                 ->willReturn('home_url');

        $pageCacheClient = $this->getContentDeliveryNetworkPageCacheClientInterfaceMock();
        $pageCacheClient->expects($this->exactly(3))
                        ->method('clearUrl')
                        ->withConsecutive(
                            [$this->identicalTo('permalink/')],
                            [$this->identicalTo('home_url/')],
                            [$this->identicalTo('rest_url/wp/v2/pages/42/')]
                        );

        $subscriber = new ContentDeliveryNetworkPageCachingSubscriber($pageCacheClient, 'rest_url', ['invalidation_enabled' => true]);

        $subscriber->setEventManager($eventManager);

        $subscriber->clearPost(42);
    }

    public function testClearPostAddsTagUrls()
    {
        $eventManager = $this->getEventManagerMock();
        $eventManager->expects($this->once())
                     ->method('filter')
                     ->with($this->identicalTo('ymir_page_caching_urls_to_clear'), $this->isInstanceOf(Collection::class), $this->identicalTo(42))
                     ->willReturn($this->returnArgument(1));

        $post = $this->getWPPostMock();
        $post->post_status = 'publish';
        $post->post_type = 'page';

        $tag = $this->getWPTermMock();
        $tag->term_id = 24;

        $get_post = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post');
        $get_post->expects($this->once())
                 ->with($this->identicalTo(42))
                 ->willReturn($post);

        $get_post_type_object = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post_type_object');
        $get_post_type_object->expects($this->once())
                             ->with($this->identicalTo(42))
                             ->willReturn(false);

        $get_permalink = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_permalink');
        $get_permalink->expects($this->once())
                      ->with($this->identicalTo(42))
                      ->willReturn('permalink');

        $get_site_option = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_site_option');
        $get_site_option->expects($this->exactly(2))
                        ->withConsecutive(
                            [$this->identicalTo('show_on_front')],
                            [$this->identicalTo('tag_base')]
                        )
                        ->willReturnOnConsecutiveCalls(false, '/tag/');

        $get_the_category = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_the_category');
        $get_the_category->expects($this->once())
                         ->with($this->identicalTo(42))
                         ->willReturn([]);

        $get_the_tags = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_the_tags');
        $get_the_tags->expects($this->once())
                     ->with($this->identicalTo(42))
                     ->willReturn([$tag]);

        $get_post_taxonomies = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post_taxonomies');
        $get_post_taxonomies->expects($this->once())
                            ->with($this->identicalTo(42))
                            ->willReturn([]);

        $home_url = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'home_url');
        $home_url->expects($this->once())
                 ->willReturn('home_url');

        $get_tag_link = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_tag_link');
        $get_tag_link->expects($this->once())
                     ->with($this->identicalTo($tag))
                     ->willReturn('tag_url');

        $pageCacheClient = $this->getContentDeliveryNetworkPageCacheClientInterfaceMock();
        $pageCacheClient->expects($this->exactly(4))
            ->method('clearUrl')
            ->withConsecutive(
                [$this->identicalTo('permalink/')],
                [$this->identicalTo('home_url/')],
                [$this->identicalTo('tag_url')],
                [$this->identicalTo('rest_url/wp/v2/tag/24/')]
            );

        $subscriber = new ContentDeliveryNetworkPageCachingSubscriber($pageCacheClient, 'rest_url', ['invalidation_enabled' => true]);

        $subscriber->setEventManager($eventManager);

        $subscriber->clearPost(42);
    }

    public function testClearPostClearsEntireCacheIfClearAllOnPostUpdateOptionIsEnabled()
    {
        $eventManager = $this->getEventManagerMock();
        $eventManager->expects($this->once())
                     ->method('execute')
                     ->with($this->identicalTo('ymir_page_caching_clear_all'));

        $pageCacheClient = $this->getContentDeliveryNetworkPageCacheClientInterfaceMock();
        $pageCacheClient->expects($this->once())
                        ->method('clearAll');

        $subscriber = new ContentDeliveryNetworkPageCachingSubscriber($pageCacheClient, 'rest_url', ['clear_all_on_post_update' => true, 'invalidation_enabled' => true]);

        $subscriber->setEventManager($eventManager);

        $subscriber->clearPost(42);
    }

    public function testClearPostClearsUrlsReturnedByFilterIfItReturnsAnArray()
    {
        $eventManager = $this->getEventManagerMock();
        $eventManager->expects($this->once())
                     ->method('filter')
                     ->with($this->identicalTo('ymir_page_caching_urls_to_clear'), $this->isInstanceOf(Collection::class), $this->identicalTo(42))
                     ->willReturn(['*']);

        $post = $this->getWPPostMock();
        $post->post_status = 'publish';
        $post->post_type = 'page';

        $get_post = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post');
        $get_post->expects($this->once())
                 ->with($this->identicalTo(42))
                 ->willReturn($post);

        $get_post_type_object = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post_type_object');
        $get_post_type_object->expects($this->once())
                             ->with($this->identicalTo(42))
                             ->willReturn(false);

        $get_permalink = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_permalink');
        $get_permalink->expects($this->once())
                      ->with($this->identicalTo(42))
                      ->willReturnOnConsecutiveCalls('permalink');

        $get_site_option = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_site_option');
        $get_site_option->expects($this->exactly(2))
                        ->withConsecutive(
                            [$this->identicalTo('show_on_front')],
                            [$this->identicalTo('tag_base')]
                        )
                        ->willReturnOnConsecutiveCalls(false, '/tag/');

        $get_the_category = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_the_category');
        $get_the_category->expects($this->once())
                         ->with($this->identicalTo(42))
                         ->willReturn([]);

        $get_the_tags = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_the_tags');
        $get_the_tags->expects($this->once())
                     ->with($this->identicalTo(42))
                     ->willReturn([]);

        $get_post_taxonomies = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post_taxonomies');
        $get_post_taxonomies->expects($this->once())
                            ->with($this->identicalTo(42))
                            ->willReturn([]);

        $home_url = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'home_url');
        $home_url->expects($this->once())
                 ->willReturn('home_url');

        $pageCacheClient = $this->getContentDeliveryNetworkPageCacheClientInterfaceMock();
        $pageCacheClient->expects($this->once())
                        ->method('clearUrl')
                        ->with($this->identicalTo('*'));

        $subscriber = new ContentDeliveryNetworkPageCachingSubscriber($pageCacheClient, 'rest_url', ['invalidation_enabled' => true]);

        $subscriber->setEventManager($eventManager);

        $subscriber->clearPost(42);
    }

    public function testClearPostClearsUrlsReturnedByFilterIfItReturnsAString()
    {
        $eventManager = $this->getEventManagerMock();
        $eventManager->expects($this->once())
                     ->method('filter')
                     ->with($this->identicalTo('ymir_page_caching_urls_to_clear'), $this->isInstanceOf(Collection::class), $this->identicalTo(42))
                     ->willReturn('*');

        $post = $this->getWPPostMock();
        $post->post_status = 'publish';
        $post->post_type = 'page';

        $get_post = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post');
        $get_post->expects($this->once())
                 ->with($this->identicalTo(42))
                 ->willReturn($post);

        $get_post_type_object = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post_type_object');
        $get_post_type_object->expects($this->once())
                             ->with($this->identicalTo(42))
                             ->willReturn(false);

        $get_permalink = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_permalink');
        $get_permalink->expects($this->once())
                      ->with($this->identicalTo(42))
                      ->willReturnOnConsecutiveCalls('permalink');

        $get_site_option = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_site_option');
        $get_site_option->expects($this->exactly(2))
                        ->withConsecutive(
                            [$this->identicalTo('show_on_front')],
                            [$this->identicalTo('tag_base')]
                        )
                        ->willReturnOnConsecutiveCalls(false, '/tag/');

        $get_the_category = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_the_category');
        $get_the_category->expects($this->once())
                         ->with($this->identicalTo(42))
                         ->willReturn([]);

        $get_the_tags = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_the_tags');
        $get_the_tags->expects($this->once())
                     ->with($this->identicalTo(42))
                     ->willReturn([]);

        $get_post_taxonomies = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post_taxonomies');
        $get_post_taxonomies->expects($this->once())
                            ->with($this->identicalTo(42))
                            ->willReturn([]);

        $home_url = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'home_url');
        $home_url->expects($this->once())
                 ->willReturn('home_url');

        $pageCacheClient = $this->getContentDeliveryNetworkPageCacheClientInterfaceMock();
        $pageCacheClient->expects($this->once())
                        ->method('clearUrl')
                        ->with($this->identicalTo('*'));

        $subscriber = new ContentDeliveryNetworkPageCachingSubscriber($pageCacheClient, 'rest_url', ['invalidation_enabled' => true]);

        $subscriber->setEventManager($eventManager);

        $subscriber->clearPost(42);
    }

    public function testClearPostDoesNothingIfFilterReturnsNull()
    {
        $eventManager = $this->getEventManagerMock();
        $eventManager->expects($this->once())
                     ->method('filter')
                     ->with($this->identicalTo('ymir_page_caching_urls_to_clear'), $this->isInstanceOf(Collection::class), $this->identicalTo(42))
                     ->willReturn(null);

        $post = $this->getWPPostMock();
        $post->post_status = 'publish';
        $post->post_type = 'page';

        $get_post = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post');
        $get_post->expects($this->once())
                 ->with($this->identicalTo(42))
                 ->willReturn($post);

        $get_post_type_object = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post_type_object');
        $get_post_type_object->expects($this->once())
                             ->with($this->identicalTo(42))
                             ->willReturn(false);

        $get_permalink = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_permalink');
        $get_permalink->expects($this->once())
                      ->with($this->identicalTo(42))
                      ->willReturnOnConsecutiveCalls('permalink');

        $get_site_option = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_site_option');
        $get_site_option->expects($this->exactly(2))
                        ->withConsecutive(
                            [$this->identicalTo('show_on_front')],
                            [$this->identicalTo('tag_base')]
                        )
                        ->willReturnOnConsecutiveCalls(false, '/tag/');

        $get_the_category = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_the_category');
        $get_the_category->expects($this->once())
                         ->with($this->identicalTo(42))
                         ->willReturn([]);

        $get_the_tags = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_the_tags');
        $get_the_tags->expects($this->once())
                     ->with($this->identicalTo(42))
                     ->willReturn([]);

        $get_post_taxonomies = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post_taxonomies');
        $get_post_taxonomies->expects($this->once())
                            ->with($this->identicalTo(42))
                            ->willReturn([]);

        $home_url = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'home_url');
        $home_url->expects($this->once())
                 ->willReturn('home_url');

        $pageCacheClient = $this->getContentDeliveryNetworkPageCacheClientInterfaceMock();
        $pageCacheClient->expects($this->never())
                        ->method('clearUrl');

        $subscriber = new ContentDeliveryNetworkPageCachingSubscriber($pageCacheClient, 'rest_url', ['invalidation_enabled' => true]);

        $subscriber->setEventManager($eventManager);

        $subscriber->clearPost(42);
    }

    public function testClearPostDoesNothingIfPageCachingDisabled()
    {
        $get_permalink = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_permalink');
        $get_permalink->expects($this->never());

        $get_post = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post');
        $get_post->expects($this->never());

        $pageCacheClient = $this->getContentDeliveryNetworkPageCacheClientInterfaceMock();
        $pageCacheClient->expects($this->never())
                        ->method('clearUrl');

        (new ContentDeliveryNetworkPageCachingSubscriber($pageCacheClient, 'rest_url', ['invalidation_enabled' => false]))->clearPost(42);
    }

    public function testClearPostFixesTrashedPostPermalinkUrl()
    {
        $eventManager = $this->getEventManagerMock();
        $eventManager->expects($this->once())
                     ->method('filter')
                     ->with($this->identicalTo('ymir_page_caching_urls_to_clear'), $this->isInstanceOf(Collection::class), $this->identicalTo(42))
                     ->willReturn($this->returnArgument(1));

        $post = $this->getWPPostMock();
        $post->post_status = 'trash';
        $post->post_type = 'page';

        $get_post = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post');
        $get_post->expects($this->once())
                 ->with($this->identicalTo(42))
                 ->willReturn($post);

        $get_post_type_object = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post_type_object');
        $get_post_type_object->expects($this->once())
                             ->with($this->identicalTo(42))
                             ->willReturn(false);

        $get_permalink = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_permalink');
        $get_permalink->expects($this->once())
                      ->with($this->identicalTo(42))
                      ->willReturn('permalink__trashed');

        $get_site_option = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_site_option');
        $get_site_option->expects($this->exactly(2))
                        ->withConsecutive(
                            [$this->identicalTo('show_on_front')],
                            [$this->identicalTo('tag_base')]
                        )
                        ->willReturnOnConsecutiveCalls(false, '/tag/');

        $get_the_category = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_the_category');
        $get_the_category->expects($this->once())
                         ->with($this->identicalTo(42))
                         ->willReturn([]);

        $get_the_tags = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_the_tags');
        $get_the_tags->expects($this->once())
                     ->with($this->identicalTo(42))
                     ->willReturn([]);

        $get_post_taxonomies = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post_taxonomies');
        $get_post_taxonomies->expects($this->once())
                            ->with($this->identicalTo(42))
                            ->willReturn([]);

        $home_url = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'home_url');
        $home_url->expects($this->once())
                 ->willReturn('home_url');

        $pageCacheClient = $this->getContentDeliveryNetworkPageCacheClientInterfaceMock();
        $pageCacheClient->expects($this->exactly(2))
                        ->method('clearUrl')
                        ->withConsecutive(
                            [$this->identicalTo('permalink/')],
                            [$this->identicalTo('home_url/')]
                        );

        $subscriber = new ContentDeliveryNetworkPageCachingSubscriber($pageCacheClient, 'rest_url', ['invalidation_enabled' => true]);

        $subscriber->setEventManager($eventManager);

        $subscriber->clearPost(42);
    }

    public function testClearPostWhenGetPermalinkDoesntReturnAString()
    {
        $eventManager = $this->getEventManagerMock();
        $eventManager->expects($this->once())
                     ->method('filter')
                     ->with($this->identicalTo('ymir_page_caching_urls_to_clear'), $this->isInstanceOf(Collection::class), $this->identicalTo(42))
                     ->willReturn($this->returnArgument(1));

        $get_post = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post');
        $get_post->expects($this->once())
                 ->with($this->identicalTo(42))
                 ->willReturn($this->getWPPostMock());

        $get_permalink = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_permalink');
        $get_permalink->expects($this->once())
                       ->with($this->identicalTo(42))
                       ->willReturn(false);

        $pageCacheClient = $this->getContentDeliveryNetworkPageCacheClientInterfaceMock();
        $pageCacheClient->expects($this->never())
                        ->method('clearUrl');

        $subscriber = new ContentDeliveryNetworkPageCachingSubscriber($pageCacheClient, 'rest_url', ['invalidation_enabled' => true]);

        $subscriber->setEventManager($eventManager);

        $subscriber->clearPost(42);
    }

    public function testClearPostWhenGetPostDoesntReturnAPost()
    {
        $eventManager = $this->getEventManagerMock();
        $eventManager->expects($this->once())
                     ->method('filter')
                     ->with($this->identicalTo('ymir_page_caching_urls_to_clear'), $this->isInstanceOf(Collection::class), $this->identicalTo(42))
                     ->willReturn($this->returnArgument(1));

        $get_post = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post');
        $get_post->expects($this->once())
                 ->with($this->identicalTo(42))
                 ->willReturn(null);

        $get_permalink = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_permalink');
        $get_permalink->expects($this->once())
                       ->with($this->identicalTo(42))
                       ->willReturn(false);

        $pageCacheClient = $this->getContentDeliveryNetworkPageCacheClientInterfaceMock();
        $pageCacheClient->expects($this->never())
            ->method('clearUrl');

        $subscriber = new ContentDeliveryNetworkPageCachingSubscriber($pageCacheClient, 'rest_url', ['invalidation_enabled' => true]);

        $subscriber->setEventManager($eventManager);

        $subscriber->clearPost(42);
    }

    public function testClearPostWhenPostStatusIsInherit()
    {
        $eventManager = $this->getEventManagerMock();
        $eventManager->expects($this->once())
                     ->method('filter')
                     ->with($this->identicalTo('ymir_page_caching_urls_to_clear'), $this->isInstanceOf(Collection::class), $this->identicalTo(42))
                     ->willReturn($this->returnArgument(1));

        $post = $this->getWPPostMock();
        $post->post_status = 'inherit';

        $get_post = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post');
        $get_post->expects($this->once())
                 ->with($this->identicalTo(42))
                 ->willReturn($post);

        $get_permalink = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_permalink');
        $get_permalink->expects($this->once())
                       ->with($this->identicalTo(42))
                       ->willReturn(false);

        $pageCacheClient = $this->getContentDeliveryNetworkPageCacheClientInterfaceMock();
        $pageCacheClient->expects($this->never())
                        ->method('clearUrl');

        $subscriber = new ContentDeliveryNetworkPageCachingSubscriber($pageCacheClient, 'rest_url', ['invalidation_enabled' => true]);

        $subscriber->setEventManager($eventManager);

        $subscriber->clearPost(42);
    }

    public function testClearPostWhenPostTypeIsNavMenuItem()
    {
        $eventManager = $this->getEventManagerMock();
        $eventManager->expects($this->once())
                     ->method('filter')
                     ->with($this->identicalTo('ymir_page_caching_urls_to_clear'), $this->isInstanceOf(Collection::class), $this->identicalTo(42))
                     ->willReturn($this->returnArgument(1));

        $post = $this->getWPPostMock();
        $post->post_status = 'publish';
        $post->post_type = 'nav_menu_item';

        $get_post = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post');
        $get_post->expects($this->once())
                 ->with($this->identicalTo(42))
                 ->willReturn($post);

        $get_permalink = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_permalink');
        $get_permalink->expects($this->once())
                       ->with($this->identicalTo(42))
                       ->willReturn(false);

        $pageCacheClient = $this->getContentDeliveryNetworkPageCacheClientInterfaceMock();
        $pageCacheClient->expects($this->never())
                        ->method('clearUrl');

        $subscriber = new ContentDeliveryNetworkPageCachingSubscriber($pageCacheClient, 'rest_url', ['invalidation_enabled' => true]);

        $subscriber->setEventManager($eventManager);

        $subscriber->clearPost(42);
    }

    public function testClearPostWhenPostTypeIsRevision()
    {
        $eventManager = $this->getEventManagerMock();
        $eventManager->expects($this->once())
                     ->method('filter')
                     ->with($this->identicalTo('ymir_page_caching_urls_to_clear'), $this->isInstanceOf(Collection::class), $this->identicalTo(42))
                     ->willReturn($this->returnArgument(1));

        $post = $this->getWPPostMock();
        $post->post_status = 'publish';
        $post->post_type = 'revision';

        $get_post = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_post');
        $get_post->expects($this->once())
                 ->with($this->identicalTo(42))
                 ->willReturn($post);

        $get_permalink = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkPageCachingSubscriber::class), 'get_permalink');
        $get_permalink->expects($this->once())
                       ->with($this->identicalTo(42))
                       ->willReturn(false);

        $pageCacheClient = $this->getContentDeliveryNetworkPageCacheClientInterfaceMock();
        $pageCacheClient->expects($this->never())
                        ->method('clearUrl');

        $subscriber = new ContentDeliveryNetworkPageCachingSubscriber($pageCacheClient, 'rest_url', ['invalidation_enabled' => true]);

        $subscriber->setEventManager($eventManager);

        $subscriber->clearPost(42);
    }

    public function testGetSubscribedEvents()
    {
        $callbacks = ContentDeliveryNetworkPageCachingSubscriber::getSubscribedEvents();

        foreach ($callbacks as $callback) {
            $this->assertTrue(method_exists(ContentDeliveryNetworkPageCachingSubscriber::class, is_array($callback) ? $callback[0] : $callback));
        }

        $subscribedEvents = [
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

        $this->assertSame($subscribedEvents, $callbacks);
    }

    public function testSendClearRequest()
    {
        $eventManager = $this->getEventManagerMock();
        $eventManager->expects($this->once())
                     ->method('execute')
                     ->with($this->identicalTo('ymir_page_caching_send_clear_request'));

        $pageCacheClient = $this->getContentDeliveryNetworkPageCacheClientInterfaceMock();
        $pageCacheClient->expects($this->once())
                        ->method('sendClearRequest');

        $subscriber = new ContentDeliveryNetworkPageCachingSubscriber($pageCacheClient, 'rest_url');

        $subscriber->setEventManager($eventManager);

        $subscriber->sendClearRequest();
    }
}
