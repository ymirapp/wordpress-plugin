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
use Ymir\Plugin\ValueObject\MappedDomainNames;

/**
 * Subscriber that manages redirects that would have been handled by the web server.
 */
class RedirectSubscriber implements SubscriberInterface
{
    /**
     * Flag whether this is a multisite installation or not.
     *
     * @var bool
     */
    private $isMultisite;

    /**
     * All the domain names mapped to the environment.
     *
     * @var MappedDomainNames
     */
    private $mappedDomainNames;

    /**
     * The Ymir project type.
     *
     * @var string
     */
    private $projectType;

    /**
     * Constructor.
     */
    public function __construct(bool $isMultisite, MappedDomainNames $mappedDomainNames, string $projectType = '')
    {
        $this->isMultisite = $isMultisite;
        $this->mappedDomainNames = $mappedDomainNames;
        $this->projectType = $projectType;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'init' => ['redirect', 1],
        ];
    }

    /**
     * Perform a redirect if needed.
     */
    public function redirect()
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $url = '';

        if (empty($host)) {
            return;
        }

        $url = $this->redirectToPrimaryDomainName($url, $host, $uri);
        $url = $this->addSlashToWpAdmin($url, $uri);

        if (!empty($url) && wp_redirect($url, 301)) {
            exit;
        }
    }

    /**
     * Add slash to "wp-admin" if necessary.
     */
    private function addSlashToWpAdmin(string $url, string $uri): string
    {
        if (!preg_match('%^(/wp)?/wp-admin$%i', $uri)) {
            return $url;
        } elseif (!empty($url)) {
            return $url.'/';
        }

        $url = $this->mappedDomainNames->getPrimaryDomainNameUrl();

        if ('bedrock' === $this->projectType) {
            $url .= '/wp';
        }

        return $url.'/wp-admin/';
    }

    /**
     * Redirect to the primary domain name if necessary.
     */
    private function redirectToPrimaryDomainName(string $url, string $host, string $uri): string
    {
        if ($this->isMultisite || $this->mappedDomainNames->isMappedDomainName($host)) {
            return $url;
        }

        $url = $this->mappedDomainNames->getPrimaryDomainNameUrl();

        if (!empty($uri)) {
            $url .= $uri;
        }

        return $url;
    }
}
