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

use Ymir\Plugin\EventManagement\SubscriberInterface;
use Ymir\Plugin\Support\Collection;

/**
 * Subscriber for managing whether we allow indexing or not.
 */
class DisallowIndexingSubscriber implements SubscriberInterface
{
    /**
     * Flag whether the WordPress site is using a vanity domain or not.
     *
     * @var bool
     */
    private $usingVanityDomain;

    /**
     * Constructor.
     */
    public function __construct(bool $usingVanityDomain)
    {
        $this->usingVanityDomain = $usingVanityDomain;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'ymir_admin_notices' => 'displayAdminNotice',
            'pre_option_blog_public' => 'filterBlogPublic',
        ];
    }

    /**
     * Display admin notice about search indexing being disabled.
     */
    public function displayAdminNotice($notices)
    {
        if ($notices instanceof Collection && $this->usingVanityDomain) {
            $notices[] = [
                'message' => 'Search engine indexing is disallowed when using a vanity domain. To learn how to map a domain to your environment, check out <a href="https://docs.ymirapp.com/guides/domain-mapping.html">this guide</a>.',
                'type' => 'warning',
            ];
        }

        return $notices;
    }

    /**
     * Filter the "blog_public" option value.
     */
    public function filterBlogPublic($value)
    {
        return !$this->usingVanityDomain ? $value : 0;
    }
}
