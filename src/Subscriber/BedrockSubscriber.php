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
 * Subscriber for Bedrock project support.
 */
class BedrockSubscriber implements SubscriberInterface
{
    /**
     * The Ymir project type.
     *
     * @var string
     */
    private $projectType;

    /**
     * Constructor.
     */
    public function __construct(string $projectType)
    {
        $this->projectType = $projectType;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'network_site_url' => ['ensureNetworkSiteUrlContainsWp', 10, 2],
            'option_home' => 'ensureHomeUrlDoesntContainWp',
            'option_siteurl' => 'ensureSiteUrlContainsWp',
        ];
    }

    /**
     * Ensure that the home URL doesn't contain the /wp subdirectory.
     */
    public function ensureHomeUrlDoesntContainWp(string $homeUrl): string
    {
        if ($this->isBedrockProject() && '/wp' === substr($homeUrl, -3)) {
            $homeUrl = substr($homeUrl, 0, -3);
        }

        return $homeUrl;
    }

    /**
     * Ensure that the network site URL contains the /wp subdirectory.
     */
    public function ensureNetworkSiteUrlContainsWp(string $networkSiteUrl, string $path): string
    {
        if (!$this->isBedrockProject()) {
            return $networkSiteUrl;
        }

        $baseUrl = rtrim(substr($networkSiteUrl, 0, -strlen($path)), '/');

        if ('/wp' !== substr($baseUrl, -3)) {
            $baseUrl .= '/wp';
        }

        return $baseUrl.'/'.ltrim($path, '/');
    }

    /**
     * Ensure that site URL contains the /wp subdirectory for Bedrock projects.
     */
    public function ensureSiteUrlContainsWp(string $siteUrl): string
    {
        if ($this->isBedrockProject() && '/wp' !== substr($siteUrl, -3) && (is_main_site() || is_subdomain_install())) {
            $siteUrl .= '/wp';
        }

        return $siteUrl;
    }

    /**
     * Checks if this is a Bedrock project.
     */
    private function isBedrockProject(): bool
    {
        return 'bedrock' === $this->projectType;
    }
}
