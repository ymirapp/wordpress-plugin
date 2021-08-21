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

/**
 * Subscriber for managing whether we allow indexing or not.
 */
class DisallowIndexingSubscriber implements SubscriberInterface
{
    /**
     * WordPress site URL.
     *
     * @var string
     */
    private $siteUrl;

    /**
     * Constructor.
     */
    public function __construct(string $siteUrl)
    {
        $this->siteUrl = rtrim($siteUrl, '/');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'admin_notices' => 'displayAdminNotice',
            'pre_option_blog_public' => 'filterBlogPublic',
        ];
    }

    /**
     * Display admin notice about search indexing being disabled.
     */
    public function displayAdminNotice()
    {
        if ($this->isIndexingAllowed()) {
            return;
        }

        echo '<div class="notice notice-warning"><p><strong>Ymir:</strong> Search engine indexing is disallowed when using a vanity domain. To learn how to map a domain to your environment, check out <a href="https://docs.ymirapp.com/guides/domain-mapping.html">this guide</a>.</p></div>';
    }

    /**
     * Filter the "blog_public" option value.
     */
    public function filterBlogPublic($value)
    {
        if (!$this->isIndexingAllowed()) {
            $value = 0;
        }

        return $value;
    }

    /**
     * Determine whether if search engine indexing should be allowed or not.
     */
    private function isIndexingAllowed(): bool
    {
        return false === stripos($this->siteUrl, '.ymirsites.com');
    }
}
