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

use Ymir\Plugin\Subscriber\ContentDeliveryNetworkImageProcessingSubscriber;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Mock\WPErrorMockTrait;
use Ymir\Plugin\Tests\Mock\WPPostMockTrait;
use Ymir\Plugin\Tests\Mock\WPRESTRequestMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\Subscriber\ContentDeliveryNetworkImageProcessingSubscriber
 */
class ContentDeliveryNetworkImageProcessingSubscriberTest extends TestCase
{
    use FunctionMockTrait;
    use WPErrorMockTrait;
    use WPPostMockTrait;
    use WPRESTRequestMockTrait;

    public function provideImageContentWithImageDimensionsForContentWidthGlobal(): array
    {
        return [
            [
                '<img class="alignnone size-thumbnail" src="https://assets.com/uploads/image-150x150.jpg" alt="" />',
                '<img class="alignnone size-thumbnail" src="https://assets.com/uploads/image.jpg?height=150&width=150&cropped" alt="" />',
            ],
            [
                '<img class="alignnone size-thumbnail" src="https://assets.com/uploads/image-1024x575.jpg" alt="" />',
                '<img class="alignnone size-thumbnail" src="https://assets.com/uploads/image.jpg?height=404&width=720&cropped" alt="" />',
            ],
            [
                '<img class="alignnone size-thumbnail" src="https://assets.com/uploads/image.jpg" height="575" alt="" />',
                '<img class="alignnone size-thumbnail" src="https://assets.com/uploads/image.jpg?height=575" alt="" />',
            ],
        ];
    }

    public function provideImageContentWithImageDimensionsInFilename(): array
    {
        return [
            [
                '<figure class="wp-block-image size-medium"><a href=https://assets.com/uploads/image.jpg"><img src="https://assets.com/uploads/image-150x150.jpg" alt="" /></a></figure>',
                '<figure class="wp-block-image size-medium"><a href=https://assets.com/uploads/image.jpg"><img src="https://assets.com/uploads/image.jpg?height=150&width=150&cropped" alt="" /></a></figure>',
            ],
            [
                '<figure class="wp-block-gallery columns-1 is-cropped"><ul class="blocks-gallery-grid"><li class="blocks-gallery-item"><figure><a href="https://assets.com/uploads/image.jpg"><img src="https://assets.com/uploads/image-1024x575.jpg" alt="" data-id="42" data-full-url="https://assets.com/uploads/image.jpg" /></a></figure></li></ul></figure>',
                '<figure class="wp-block-gallery columns-1 is-cropped"><ul class="blocks-gallery-grid"><li class="blocks-gallery-item"><figure><a href="https://assets.com/uploads/image.jpg"><img src="https://assets.com/uploads/image.jpg?height=575&width=1024&cropped" alt="" data-id="42" data-full-url="https://assets.com/uploads/image.jpg" /></a></figure></li></ul></figure>',
            ],
            [
                '<img class="alignnone size-thumbnail" src="https://assets.com/uploads/image-150x150.jpg" alt="" />',
                '<img class="alignnone size-thumbnail" src="https://assets.com/uploads/image.jpg?height=150&width=150&cropped" alt="" />',
            ],
        ];
    }

    public function provideImageContentWithImageDimensionsInImgTagAttributes(): array
    {
        return [
            [
                '<img class="alignnone size-thumbnail" src="https://assets.com/uploads/image-150x150.jpg" alt="" width="150" height="150" />',
                '<img class="alignnone size-thumbnail" src="https://assets.com/uploads/image.jpg?height=150&width=150&cropped" alt="" />',
            ],
            [
                '<img class="alignnone size-thumbnail" src="https://assets.com/uploads/image.jpg" alt="" width="150" height="150" />',
                '<img class="alignnone size-thumbnail" src="https://assets.com/uploads/image.jpg?height=150&width=150&cropped" alt="" />',
            ],
            [
                '<img class="alignnone size-thumbnail" src="https://assets.com/uploads/image.jpg" alt="" width="150" />',
                '<img class="alignnone size-thumbnail" src="https://assets.com/uploads/image.jpg?width=150" alt="" />',
            ],
            [
                '<img class="alignnone size-thumbnail" src="https://assets.com/uploads/image.jpg" alt="" height="150" />',
                '<img class="alignnone size-thumbnail" src="https://assets.com/uploads/image.jpg?height=150" alt="" />',
            ],
            [
                '<img class="alignnone size-thumbnail" src="https://assets.com/uploads/image.jpg?height=300&width=300" alt="" height="150" />',
                '<img class="alignnone size-thumbnail" src="https://assets.com/uploads/image.jpg?height=150" alt="" />',
            ],
        ];
    }

    public function provideImageContentWithImageDimensionsInWordPress(): array
    {
        return [
            [
                '<img class="alignnone size-full wp-image-42" src="https://assets.com/uploads/image.jpg" alt="" />',
                '<img class="alignnone size-full wp-image-42" src="https://assets.com/uploads/image.jpg?height=575&width=1024" alt="" />',
                'full',
                ['https://assets.com/uploads/image.jpg', 1024, 575],
            ],
            [
                '<img class="alignnone size-thumbnail wp-image-42" src="https://assets.com/uploads/image.jpg" alt="" />',
                '<img class="alignnone size-thumbnail wp-image-42" src="https://assets.com/uploads/image.jpg?height=150&width=150&cropped" alt="" />',
                'thumbnail',
                ['https://assets.com/uploads/image.jpg', 150, 150],
            ],
            [
                '<img class="alignnone wp-image-42" src="https://assets.com/uploads/image.jpg" alt="" />',
                '<img class="alignnone wp-image-42" src="https://assets.com/uploads/image.jpg?height=575&width=1024" alt="" />',
                'full',
                ['https://assets.com/uploads/image.jpg', 1024, 575],
            ],
            [
                '<img class="alignnone size-missing wp-image-42" src="https://assets.com/uploads/image.jpg" alt="" />',
                '<img class="alignnone size-missing wp-image-42" src="https://assets.com/uploads/image.jpg" alt="" />',
                'missing',
                false,
            ],
        ];
    }

    public function testGenerateScaledDownImageForFullSizeImageWithNoImageMetadata()
    {
        $is_admin = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'is_admin');
        $is_admin->expects($this->once())
                 ->willReturn(false);

        $wp_get_attachment_url = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'wp_get_attachment_url');
        $wp_get_attachment_url->expects($this->once())
                              ->with($this->identicalTo(42))
                              ->willReturn('https://assets.com/uploads/image.jpg');

        $wp_get_attachment_metadata = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'wp_get_attachment_metadata');
        $wp_get_attachment_metadata->expects($this->once())
                                   ->with($this->identicalTo(42))
                                   ->willReturn(false);

        $image_resize_dimensions = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'image_resize_dimensions');
        $image_resize_dimensions->expects($this->never());

        $image_constrain_size_for_editor = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'image_constrain_size_for_editor');
        $image_constrain_size_for_editor->expects($this->once())
                                        ->with($this->identicalTo(null), $this->identicalTo(null), $this->identicalTo('full'))
                                        ->willReturn([null, null]);

        $this->assertSame([
            'https://assets.com/uploads/image.jpg', false, false, false,
        ], (new ContentDeliveryNetworkImageProcessingSubscriber($this->getImageSizes(), true, 'https://assets.com/uploads'))->generateScaledDownImage(false, 42, 'full'));
    }

    public function testGenerateScaledDownImageForThumbnailImageWithNoImageMetadata()
    {
        $is_admin = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'is_admin');
        $is_admin->expects($this->once())
                 ->willReturn(false);

        $wp_get_attachment_url = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'wp_get_attachment_url');
        $wp_get_attachment_url->expects($this->once())
                              ->with($this->identicalTo(42))
                              ->willReturn('https://assets.com/uploads/image.jpg');

        $wp_get_attachment_metadata = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'wp_get_attachment_metadata');
        $wp_get_attachment_metadata->expects($this->once())
                                   ->with($this->identicalTo(42))
                                   ->willReturn(false);

        $image_resize_dimensions = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'image_resize_dimensions');
        $image_resize_dimensions->expects($this->never());

        $image_get_intermediate_size = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'image_get_intermediate_size');
        $image_get_intermediate_size->expects($this->once())
                                    ->with($this->identicalTo(42), $this->identicalTo('thumbnail'))
                                    ->willReturn(['width' => 150, 'height' => 150]);

        $image_constrain_size_for_editor = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'image_constrain_size_for_editor');
        $image_constrain_size_for_editor->expects($this->once())
                                        ->with($this->identicalTo(150), $this->identicalTo(150), $this->identicalTo('thumbnail'))
                                        ->willReturn([150, 150]);

        $this->assertSame([
            'https://assets.com/uploads/image.jpg?height=150&width=150&cropped', 150, 150, true,
        ], (new ContentDeliveryNetworkImageProcessingSubscriber($this->getImageSizes(), true, 'https://assets.com/uploads'))->generateScaledDownImage(false, 42, 'thumbnail'));
    }

    public function testGenerateScaledDownImageWhenAttachmentUrlIsntProcessable()
    {
        $is_admin = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'is_admin');
        $is_admin->expects($this->once())
                 ->willReturn(false);

        $wp_get_attachment_url = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'wp_get_attachment_url');
        $wp_get_attachment_url->expects($this->once())
                              ->with($this->identicalTo(42))
                              ->willReturn('https://domain.com/uploads/image.jpg');

        $this->assertFalse((new ContentDeliveryNetworkImageProcessingSubscriber($this->getImageSizes(), true, 'https://assets.com/uploads'))->generateScaledDownImage(false, 42, 'full'));
    }

    public function testGenerateScaledDownImageWhenIsAdminIsTrue()
    {
        $is_admin = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'is_admin');
        $is_admin->expects($this->once())
                 ->willReturn(true);

        $this->assertFalse((new ContentDeliveryNetworkImageProcessingSubscriber($this->getImageSizes(), true, 'https://assets.com/uploads'))->generateScaledDownImage(false, 42, 'full'));
    }

    public function testGenerateScaledDownImageWhenNoAttachmentUrlFound()
    {
        $is_admin = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'is_admin');
        $is_admin->expects($this->once())
                 ->willReturn(false);

        $wp_get_attachment_url = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'wp_get_attachment_url');
        $wp_get_attachment_url->expects($this->once())
                              ->with($this->identicalTo(42))
                              ->willReturn(false);

        $this->assertFalse((new ContentDeliveryNetworkImageProcessingSubscriber($this->getImageSizes(), true, 'https://assets.com/uploads'))->generateScaledDownImage(false, 42, 'full'));
    }

    public function testGenerateScaledDownImageWhenSizeIsInvalidType()
    {
        $is_admin = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'is_admin');
        $is_admin->expects($this->once())
                 ->willReturn(false);

        $this->assertFalse((new ContentDeliveryNetworkImageProcessingSubscriber($this->getImageSizes(), true, 'https://assets.com/uploads'))->generateScaledDownImage(false, 42, null));
    }

    public function testGenerateScaledDownImageWhenSizeIsntInImageSizeArray()
    {
        $is_admin = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'is_admin');
        $is_admin->expects($this->once())
                 ->willReturn(false);

        $this->assertFalse((new ContentDeliveryNetworkImageProcessingSubscriber($this->getImageSizes(), true, 'https://assets.com/uploads'))->generateScaledDownImage(false, 42, 'missing'));
    }

    public function testGenerateScaledDownImageWithSizeArrayAndDifferentResizedDimension()
    {
        $is_admin = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'is_admin');
        $is_admin->expects($this->once())
                 ->willReturn(false);

        $wp_get_attachment_url = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'wp_get_attachment_url');
        $wp_get_attachment_url->expects($this->once())
                              ->with($this->identicalTo(42))
                              ->willReturn('https://assets.com/uploads/image.jpg');

        $wp_get_attachment_metadata = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'wp_get_attachment_metadata');
        $wp_get_attachment_metadata->expects($this->once())
                                   ->with($this->identicalTo(42))
                                   ->willReturn(['width' => 1600, 'height' => 1200]);

        $image_resize_dimensions = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'image_resize_dimensions');
        $image_resize_dimensions->expects($this->once())
                                ->with($this->identicalTo(1600), $this->identicalTo(1200), $this->identicalTo(400), $this->identicalTo(400))
                                ->willReturn([0, 0, 0, 0, 0, 0, 400, 300]);

        $image_constrain_size_for_editor = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'image_constrain_size_for_editor');
        $image_constrain_size_for_editor->expects($this->once())
                                        ->with($this->identicalTo(400), $this->identicalTo(300), $this->identicalTo([400, 400]))
                                        ->willReturn([400, 300]);

        $this->assertSame([
            'https://assets.com/uploads/image.jpg?height=300&width=400', 400, 300, true,
        ], (new ContentDeliveryNetworkImageProcessingSubscriber($this->getImageSizes(), true, 'https://assets.com/uploads'))->generateScaledDownImage(false, 42, [400, 400]));
    }

    public function testGenerateScaledDownImageWithSizeArrayAndNoFullSizeImageMetadata()
    {
        $is_admin = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'is_admin');
        $is_admin->expects($this->once())
                 ->willReturn(false);

        $wp_get_attachment_url = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'wp_get_attachment_url');
        $wp_get_attachment_url->expects($this->once())
                              ->with($this->identicalTo(42))
                              ->willReturn('https://assets.com/uploads/image.jpg');

        $wp_get_attachment_metadata = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'wp_get_attachment_metadata');
        $wp_get_attachment_metadata->expects($this->once())
                                   ->with($this->identicalTo(42))
                                   ->willReturn(false);

        $image_resize_dimensions = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'image_resize_dimensions');
        $image_resize_dimensions->expects($this->never());

        $image_constrain_size_for_editor = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'image_constrain_size_for_editor');
        $image_constrain_size_for_editor->expects($this->once())
                                        ->with($this->identicalTo(400), $this->identicalTo(400), $this->identicalTo([400, 400]))
                                        ->willReturn([400, 400]);

        $this->assertSame([
            'https://assets.com/uploads/image.jpg?height=400&width=400', 400, 400, true,
        ], (new ContentDeliveryNetworkImageProcessingSubscriber($this->getImageSizes(), true, 'https://assets.com/uploads'))->generateScaledDownImage(false, 42, [400, 400]));
    }

    public function testGetSubscribedEvents()
    {
        $callbacks = ContentDeliveryNetworkImageProcessingSubscriber::getSubscribedEvents();

        foreach ($callbacks as $callback) {
            $this->assertTrue(method_exists(ContentDeliveryNetworkImageProcessingSubscriber::class, is_array($callback) ? $callback[0] : $callback));
        }

        $subscribedEvents = [
            'image_downsize' => ['generateScaledDownImage', 10, 3],
            'the_content' => ['rewriteContentImageUrls', 999999],
            'get_post_galleries' => ['rewriteGalleryImageUrls', 999999],
            'rest_after_insert_attachment' => ['maybeDisableImageDownsizeFilterForInsertAttachmentRestRequest', 10, 2],
            'rest_request_after_callbacks' => 'reEnableImageDownsizeFilter',
            'rest_request_before_callbacks' => ['maybeDisableImageDownsizeFilterForRestRequest', 10, 3],
            'wp_calculate_image_srcset' => ['rewriteImageSrcset', 10, 5],
        ];

        $this->assertSame($subscribedEvents, $callbacks);
    }

    public function testMaybeDisableImageDownsizeFilterForInsertAttachmentRestRequestWhenMediaEndpointRouteAndEditContext()
    {
        $subscriber = new ContentDeliveryNetworkImageProcessingSubscriber($this->getImageSizes(), true, 'https://assets.com/uploads');
        $subscriberReflection = new \ReflectionObject($subscriber);

        $imageDownsizeFilterEnabledProperty = $subscriberReflection->getProperty('imageDownsizeFilterEnabled');
        $imageDownsizeFilterEnabledProperty->setAccessible(true);

        $request = $this->getWPRESTRequestMock();

        $request->expects($this->once())
                ->method('get_route')
                ->willReturn('https://domain.com/wp/v2/media');

        $request->expects($this->once())
                ->method('get_param')
                ->willReturn('edit');

        $this->assertTrue($imageDownsizeFilterEnabledProperty->getValue($subscriber));

        $subscriber->maybeDisableImageDownsizeFilterForInsertAttachmentRestRequest($this->getWPPostMock(), $request);

        $this->assertFalse($imageDownsizeFilterEnabledProperty->getValue($subscriber));
    }

    public function testMaybeDisableImageDownsizeFilterForInsertAttachmentRestRequestWhenMediaEndpointRouteButNotEditContext()
    {
        $subscriber = new ContentDeliveryNetworkImageProcessingSubscriber($this->getImageSizes(), true, 'https://assets.com/uploads');
        $subscriberReflection = new \ReflectionObject($subscriber);

        $imageDownsizeFilterEnabledProperty = $subscriberReflection->getProperty('imageDownsizeFilterEnabled');
        $imageDownsizeFilterEnabledProperty->setAccessible(true);

        $request = $this->getWPRESTRequestMock();

        $request->expects($this->once())
                ->method('get_route')
                ->willReturn('https://domain.com/wp/v2/media');

        $request->expects($this->once())
                ->method('get_param')
                ->willReturn('create');

        $this->assertTrue($imageDownsizeFilterEnabledProperty->getValue($subscriber));

        $subscriber->maybeDisableImageDownsizeFilterForInsertAttachmentRestRequest($this->getWPPostMock(), $request);

        $this->assertTrue($imageDownsizeFilterEnabledProperty->getValue($subscriber));
    }

    public function testMaybeDisableImageDownsizeFilterForInsertAttachmentWhenRestRequestWhenNotMediaEndpointRoute()
    {
        $subscriber = new ContentDeliveryNetworkImageProcessingSubscriber($this->getImageSizes(), true, 'https://assets.com/uploads');
        $subscriberReflection = new \ReflectionObject($subscriber);

        $imageDownsizeFilterEnabledProperty = $subscriberReflection->getProperty('imageDownsizeFilterEnabled');
        $imageDownsizeFilterEnabledProperty->setAccessible(true);

        $request = $this->getWPRESTRequestMock();

        $request->expects($this->once())
                ->method('get_route')
                ->willReturn('https://domain.com/wp/v2/post');

        $request->expects($this->never())
                ->method('get_param');

        $this->assertTrue($imageDownsizeFilterEnabledProperty->getValue($subscriber));

        $subscriber->maybeDisableImageDownsizeFilterForInsertAttachmentRestRequest($this->getWPPostMock(), $request);

        $this->assertTrue($imageDownsizeFilterEnabledProperty->getValue($subscriber));
    }

    public function testMaybeDisableImageDownsizeFilterForRestRequestWhenRequestIsntWpRestRequestObject()
    {
        $subscriber = new ContentDeliveryNetworkImageProcessingSubscriber($this->getImageSizes(), true, 'https://assets.com/uploads');
        $subscriberReflection = new \ReflectionObject($subscriber);

        $imageDownsizeFilterEnabledProperty = $subscriberReflection->getProperty('imageDownsizeFilterEnabled');
        $imageDownsizeFilterEnabledProperty->setAccessible(true);

        $this->assertTrue($imageDownsizeFilterEnabledProperty->getValue($subscriber));

        $this->assertSame('', $subscriber->maybeDisableImageDownsizeFilterForRestRequest('', null, null));

        $this->assertTrue($imageDownsizeFilterEnabledProperty->getValue($subscriber));
    }

    public function testMaybeDisableImageDownsizeFilterForRestRequestWhenResponseIsWPError()
    {
        $subscriber = new ContentDeliveryNetworkImageProcessingSubscriber($this->getImageSizes(), true, 'https://assets.com/uploads');
        $subscriberReflection = new \ReflectionObject($subscriber);

        $imageDownsizeFilterEnabledProperty = $subscriberReflection->getProperty('imageDownsizeFilterEnabled');
        $imageDownsizeFilterEnabledProperty->setAccessible(true);

        $request = $this->getWPRESTRequestMock();

        $request->expects($this->never())
                ->method('get_route');

        $request->expects($this->never())
                ->method('get_param');

        $response = $this->getWPErrorMock();

        $this->assertTrue($imageDownsizeFilterEnabledProperty->getValue($subscriber));

        $this->assertSame($response, $subscriber->maybeDisableImageDownsizeFilterForRestRequest($response, null, $request));

        $this->assertTrue($imageDownsizeFilterEnabledProperty->getValue($subscriber));
    }

    public function testMaybeDisableImageDownsizeFilterForRestRequestWhenRestRequestWhenMediaEndpointRouteAndEditContext()
    {
        $subscriber = new ContentDeliveryNetworkImageProcessingSubscriber($this->getImageSizes(), true, 'https://assets.com/uploads');
        $subscriberReflection = new \ReflectionObject($subscriber);

        $imageDownsizeFilterEnabledProperty = $subscriberReflection->getProperty('imageDownsizeFilterEnabled');
        $imageDownsizeFilterEnabledProperty->setAccessible(true);

        $request = $this->getWPRESTRequestMock();

        $request->expects($this->once())
                ->method('get_route')
                ->willReturn('https://domain.com/wp/v2/media');

        $request->expects($this->once())
                ->method('get_param')
                ->willReturn('edit');

        $this->assertTrue($imageDownsizeFilterEnabledProperty->getValue($subscriber));

        $this->assertSame('', $subscriber->maybeDisableImageDownsizeFilterForRestRequest('', null, $request));

        $this->assertFalse($imageDownsizeFilterEnabledProperty->getValue($subscriber));
    }

    public function testMaybeDisableImageDownsizeFilterForRestRequestWhenRestRequestWhenMediaEndpointRouteButNotEditContext()
    {
        $subscriber = new ContentDeliveryNetworkImageProcessingSubscriber($this->getImageSizes(), true, 'https://assets.com/uploads');
        $subscriberReflection = new \ReflectionObject($subscriber);

        $imageDownsizeFilterEnabledProperty = $subscriberReflection->getProperty('imageDownsizeFilterEnabled');
        $imageDownsizeFilterEnabledProperty->setAccessible(true);

        $request = $this->getWPRESTRequestMock();

        $request->expects($this->once())
                ->method('get_route')
                ->willReturn('https://domain.com/wp/v2/media');

        $request->expects($this->once())
                ->method('get_param')
                ->willReturn('create');

        $this->assertTrue($imageDownsizeFilterEnabledProperty->getValue($subscriber));

        $this->assertSame('', $subscriber->maybeDisableImageDownsizeFilterForRestRequest('', null, $request));

        $this->assertTrue($imageDownsizeFilterEnabledProperty->getValue($subscriber));
    }

    public function testMaybeDisableImageDownsizeFilterForRestRequestWhenRestRequestWhenNotMediaEndpointRoute()
    {
        $subscriber = new ContentDeliveryNetworkImageProcessingSubscriber($this->getImageSizes(), true, 'https://assets.com/uploads');
        $subscriberReflection = new \ReflectionObject($subscriber);

        $imageDownsizeFilterEnabledProperty = $subscriberReflection->getProperty('imageDownsizeFilterEnabled');
        $imageDownsizeFilterEnabledProperty->setAccessible(true);

        $request = $this->getWPRESTRequestMock();

        $request->expects($this->once())
                ->method('get_route')
                ->willReturn('https://domain.com/wp/v2/post');

        $request->expects($this->never())
                ->method('get_param');

        $this->assertTrue($imageDownsizeFilterEnabledProperty->getValue($subscriber));

        $this->assertSame('', $subscriber->maybeDisableImageDownsizeFilterForRestRequest('', null, $request));

        $this->assertTrue($imageDownsizeFilterEnabledProperty->getValue($subscriber));
    }

    public function testReEnableImageDownsizeFilterIfFlagWasFalse()
    {
        $subscriber = new ContentDeliveryNetworkImageProcessingSubscriber($this->getImageSizes(), true, 'https://assets.com/uploads');
        $subscriberReflection = new \ReflectionObject($subscriber);

        $imageDownsizeFilterEnabledProperty = $subscriberReflection->getProperty('imageDownsizeFilterEnabled');
        $imageDownsizeFilterEnabledProperty->setAccessible(true);

        $imageDownsizeFilterEnabledProperty->setValue($subscriber, false);

        $this->assertFalse($imageDownsizeFilterEnabledProperty->getValue($subscriber));

        $this->assertSame('', $subscriber->reEnableImageDownsizeFilter(''));

        $this->assertTrue($imageDownsizeFilterEnabledProperty->getValue($subscriber));
    }

    public function testReEnableImageDownsizeFilterIfFlagWasTrue()
    {
        $subscriber = new ContentDeliveryNetworkImageProcessingSubscriber($this->getImageSizes(), true, 'https://assets.com/uploads');
        $subscriberReflection = new \ReflectionObject($subscriber);

        $imageDownsizeFilterEnabledProperty = $subscriberReflection->getProperty('imageDownsizeFilterEnabled');
        $imageDownsizeFilterEnabledProperty->setAccessible(true);

        $this->assertTrue($imageDownsizeFilterEnabledProperty->getValue($subscriber));

        $this->assertSame('', $subscriber->reEnableImageDownsizeFilter(''));

        $this->assertTrue($imageDownsizeFilterEnabledProperty->getValue($subscriber));
    }

    public function testRewriteContentImageUrlsDoesntRewriteSrcsetUrls()
    {
        $content = '<img width="205" height="112" src="https://assets.com/uploads/image.jpg" srcset="https://assets.com/uploads/image.jpg?width=2048 2048w, https://assets.com/uploads/image.jpg?height=164&amp;width=300 300w, https://assets.com/uploads/image.jpg?height=560&amp;width=1024 1024w, https://assets.com/uploads/image.jpg?height=420&amp;width=768 768w" />';
        $expectedContent = '<img src="https://assets.com/uploads/image.jpg?height=112&width=205&cropped" srcset="https://assets.com/uploads/image.jpg?width=2048 2048w, https://assets.com/uploads/image.jpg?height=164&amp;width=300 300w, https://assets.com/uploads/image.jpg?height=560&amp;width=1024 1024w, https://assets.com/uploads/image.jpg?height=420&amp;width=768 768w" />';

        $_prime_post_caches = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), '_prime_post_caches');
        $_prime_post_caches->expects($this->once())
                           ->with($this->identicalTo([]), $this->identicalTo(false));

        $esc_url = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'esc_url');
        $esc_url->expects($this->any())
                ->willReturn($this->returnArgument(0));

        $this->assertSame($expectedContent, (new ContentDeliveryNetworkImageProcessingSubscriber($this->getImageSizes(), true, 'https://assets.com/uploads'))->rewriteContentImageUrls($content));
    }

    public function testRewriteContentImageUrlsDoesntRewriteUrlIfUploadsUrlDoesntMatch()
    {
        $content = '<img class="alignnone size-thumbnail" src="https://domain.com/uploads/image-150x150.jpg" alt="" />';

        $this->assertSame($content, (new ContentDeliveryNetworkImageProcessingSubscriber($this->getImageSizes(), true, 'https://assets.com/uploads'))->rewriteContentImageUrls($content));
    }

    /**
     * @dataProvider provideImageContentWithImageDimensionsForContentWidthGlobal
     */
    public function testRewriteContentImageUrlsUsesContentWidthGlobal(string $content, string $expectedContent)
    {
        $_prime_post_caches = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), '_prime_post_caches');
        $_prime_post_caches->expects($this->once())
                           ->with($this->identicalTo([]), $this->identicalTo(false));

        $esc_url = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'esc_url');
        $esc_url->expects($this->any())
                ->willReturn($this->returnArgument(0));

        $this->assertSame($expectedContent, (new ContentDeliveryNetworkImageProcessingSubscriber($this->getImageSizes(), true, 'https://assets.com/uploads', 720))->rewriteContentImageUrls($content));
    }

    /**
     * @dataProvider provideImageContentWithImageDimensionsInFilename
     */
    public function testRewriteContentImageUrlsUsingFilename(string $content, string $expectedContent)
    {
        $_prime_post_caches = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), '_prime_post_caches');
        $_prime_post_caches->expects($this->once())
                           ->with($this->identicalTo([]), $this->identicalTo(false));

        $esc_url = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'esc_url');
        $esc_url->expects($this->any())
                ->willReturn($this->returnArgument(0));

        $this->assertSame($expectedContent, (new ContentDeliveryNetworkImageProcessingSubscriber($this->getImageSizes(), true, 'https://assets.com/uploads'))->rewriteContentImageUrls($content));
    }

    /**
     * @dataProvider provideImageContentWithImageDimensionsInImgTagAttributes
     */
    public function testRewriteContentImageUrlsUsingImgTagAttributes(string $content, string $expectedContent)
    {
        $_prime_post_caches = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), '_prime_post_caches');
        $_prime_post_caches->expects($this->once())
                           ->with($this->identicalTo([]), $this->identicalTo(false));

        $esc_url = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'esc_url');
        $esc_url->expects($this->any())
                ->willReturn($this->returnArgument(0));

        $this->assertSame($expectedContent, (new ContentDeliveryNetworkImageProcessingSubscriber($this->getImageSizes(), true, 'https://assets.com/uploads'))->rewriteContentImageUrls($content));
    }

    /**
     * @dataProvider provideImageContentWithImageDimensionsInWordPress
     */
    public function testRewriteContentImageUrlsUsingWordPress(string $content, string $expectedContent, string $expectedSize, $returnedImgSrc)
    {
        $post = $this->getWPPostMock();
        $post->post_type = 'attachment';

        $_prime_post_caches = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), '_prime_post_caches');
        $_prime_post_caches->expects($this->once())
                           ->with($this->identicalTo(['42']), $this->identicalTo(false));

        $esc_url = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'esc_url');
        $esc_url->expects($this->any())
                ->willReturn($this->returnArgument(0));

        $get_post = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'get_post');
        $get_post->expects($this->once())
                 ->with($this->identicalTo('42'))
                 ->willReturn($post);

        $wp_get_attachment_image_src = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'wp_get_attachment_image_src');
        $wp_get_attachment_image_src->expects($this->once())
                                    ->with($this->identicalTo('42'), $this->identicalTo($expectedSize))
                                    ->willReturn($returnedImgSrc);

        $this->assertSame($expectedContent, (new ContentDeliveryNetworkImageProcessingSubscriber($this->getImageSizes(), true, 'https://assets.com/uploads'))->rewriteContentImageUrls($content));
    }

    public function testRewriteImageSrcsetFallsBackToValueAttributeIfUnableToParseImageDimensions()
    {
        $actualSources = [
            ['value' => 42, 'descriptor' => 'w', 'url' => 'https://assets.com/uploads/image.jpg'],
        ];
        $expectedSources = [
            ['value' => 42, 'descriptor' => 'w', 'url' => 'https://assets.com/uploads/image.jpg?width=42'],
        ];

        $wp_get_attachment_url = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'wp_get_attachment_url');
        $wp_get_attachment_url->expects($this->once())
                              ->with($this->identicalTo(42))
                              ->willReturn('https://assets.com/uploads/image.jpg');

        $this->assertSame($expectedSources, (new ContentDeliveryNetworkImageProcessingSubscriber($this->getImageSizes(), true, 'https://assets.com/uploads'))->rewriteImageSrcset($actualSources, null, null, null, 42));
    }

    public function testRewriteImageSrcsetParsesDimensionsFromUrlAttribute()
    {
        $actualSources = [
            ['value' => 42, 'descriptor' => 'w', 'url' => 'https://assets.com/uploads/image-150x150.jpg'],
        ];
        $expectedSources = [
            ['value' => 42, 'descriptor' => 'w', 'url' => 'https://assets.com/uploads/image.jpg?height=150&width=150'],
        ];

        $wp_get_attachment_url = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'wp_get_attachment_url');
        $wp_get_attachment_url->expects($this->once())
                              ->with($this->identicalTo(42))
                              ->willReturn('https://assets.com/uploads/image.jpg');

        $this->assertSame($expectedSources, (new ContentDeliveryNetworkImageProcessingSubscriber($this->getImageSizes(), true, 'https://assets.com/uploads'))->rewriteImageSrcset($actualSources, null, null, null, 42));
    }

    public function testRewriteImageSrcsetParsesUrlAttributeIfNoAttachmentIdGiven()
    {
        $actualSources = [
            ['value' => 42, 'descriptor' => 'w', 'url' => 'https://assets.com/uploads/image-150x150.jpg'],
        ];
        $expectedSources = [
            ['value' => 42, 'descriptor' => 'w', 'url' => 'https://assets.com/uploads/image.jpg?height=150&width=150'],
        ];

        $wp_get_attachment_url = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'wp_get_attachment_url');
        $wp_get_attachment_url->expects($this->never());

        $this->assertSame($expectedSources, (new ContentDeliveryNetworkImageProcessingSubscriber($this->getImageSizes(), true, 'https://assets.com/uploads'))->rewriteImageSrcset($actualSources, null, null, null, null));
    }

    public function testRewriteImageSrcsetWhenGetAttachmentUrlReturnsUrlWithQueryString()
    {
        $actualSources = [
            ['value' => 42, 'descriptor' => 'w', 'url' => 'https://assets.com/uploads/image-150x150.jpg'],
        ];
        $expectedSources = [
            ['value' => 42, 'descriptor' => 'w', 'url' => 'https://assets.com/uploads/image.jpg?height=150&width=150'],
        ];

        $wp_get_attachment_url = $this->getFunctionMock($this->getNamespace(ContentDeliveryNetworkImageProcessingSubscriber::class), 'wp_get_attachment_url');
        $wp_get_attachment_url->expects($this->once())
                              ->with($this->identicalTo(42))
                              ->willReturn('https://assets.com/uploads/image.jpg?height=300&width=300');

        $this->assertSame($expectedSources, (new ContentDeliveryNetworkImageProcessingSubscriber($this->getImageSizes(), true, 'https://assets.com/uploads'))->rewriteImageSrcset($actualSources, null, null, null, 42));
    }

    public function testRewriteImageSrcsetWithSourceMissingDescriptorAttribute()
    {
        $sources = [
            ['value' => 42, 'url' => 'https://assets.com/uploads/image-150x150.jpg'],
        ];

        $this->assertSame($sources, (new ContentDeliveryNetworkImageProcessingSubscriber($this->getImageSizes(), true, 'https://assets.com/uploads'))->rewriteImageSrcset($sources, null, null, null, 42));
    }

    public function testRewriteImageSrcsetWithSourceMissingUrlAttribute()
    {
        $sources = [
            ['value' => 42, 'descriptor' => 'w'],
        ];

        $this->assertSame($sources, (new ContentDeliveryNetworkImageProcessingSubscriber($this->getImageSizes(), true, 'https://assets.com/uploads'))->rewriteImageSrcset($sources, null, null, null, 42));
    }

    public function testRewriteImageSrcsetWithSourceMissingValueAttribute()
    {
        $sources = [
            ['descriptor' => 'w', 'url' => 'https://assets.com/uploads/image-150x150.jpg'],
        ];

        $this->assertSame($sources, (new ContentDeliveryNetworkImageProcessingSubscriber($this->getImageSizes(), true, 'https://assets.com/uploads'))->rewriteImageSrcset($sources, null, null, null, 42));
    }

    public function testRewriteImageSrcsetWithUnprocessableUrlAttribute()
    {
        $sources = [
            ['value' => 42, 'descriptor' => 'w', 'url' => 'https://domain.com/uploads/image-150x150.jpg'],
        ];

        $this->assertSame($sources, (new ContentDeliveryNetworkImageProcessingSubscriber($this->getImageSizes(), true, 'https://assets.com/uploads'))->rewriteImageSrcset($sources, null, null, null, 42));
    }

    public function testRewriteImageSrcsetWithWrongDescriptorAttribute()
    {
        $sources = [
            ['value' => 42, 'descriptor' => 'x', 'url' => 'https://assets.com/uploads/image-150x150.jpg'],
        ];

        $this->assertSame($sources, (new ContentDeliveryNetworkImageProcessingSubscriber($this->getImageSizes(), true, 'https://assets.com/uploads'))->rewriteImageSrcset($sources, null, null, null, 42));
    }

    /**
     * Get the image sizes used for the tests.
     */
    private function getImageSizes(): array
    {
        return [
            'thumbnail' => [
                'width' => 150,
                'height' => 150,
                'crop' => true,
            ],
            'full' => [
                'width' => null,
                'height' => null,
                'crop' => false,
            ],
        ];
    }
}
