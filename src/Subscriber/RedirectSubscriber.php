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
 * Subscriber that manages redirects that would have been handled by the web server.
 */
class RedirectSubscriber implements SubscriberInterface
{
    /**
     * The primary domain name that we want to redirect requests to.
     *
     * @var string
     */
    private $domainName;

    /**
     * Flag whether this is a multisite installation or not.
     *
     * @var bool
     */
    private $isMultisite;

    /**
     * Constructor.
     */
    public function __construct(string $domainName, bool $isMultisite)
    {
        $this->domainName = $domainName;
        $this->isMultisite = $isMultisite;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'init' => ['redirectToDomainName', 1],
        ];
    }

    /**
     * Redirect to the primary domain name if necessary.
     */
    public function redirectToDomainName()
    {
        if ($this->isMultisite || empty($_SERVER['HTTP_HOST']) || $_SERVER['HTTP_HOST'] === $this->domainName) {
            return;
        }

        $url = 'https://'.$this->domainName;

        if (!empty($_SERVER['REQUEST_URI'])) {
            $url .= $_SERVER['REQUEST_URI'];
        }

        if (wp_redirect($url, 301)) {
            exit;
        }
    }
}
