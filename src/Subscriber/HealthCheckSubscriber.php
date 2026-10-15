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
use Ymir\Plugin\EventManagement\WordPressAwareInterface;

/**
 * Subscriber that responds to Ymir deployment health checks sent to the "/ymir/health" endpoint.
 */
class HealthCheckSubscriber extends AbstractEventManagerAwareSubscriber implements WordPressAwareInterface
{
    /**
     * The WordPress request environment.
     *
     * @var \WP|null
     */
    private $wp;

    /**
     * {@inheritdoc}
     *
     * Handle core canonical redirects before site filters, and respond after site redirects, authentication and maintenance.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'redirect_canonical' => ['preventCanonicalPathRedirectForHealthCheck', PHP_INT_MIN, 2],
            'template_redirect' => ['respondToHealthCheck', PHP_INT_MAX],
        ];
    }

    /**
     * Prevent WordPress from canonicalizing the path of a health check, such as by guessing a post with a similar slug.
     * Redirects to another scheme, host or port still apply.
     */
    public function preventCanonicalPathRedirectForHealthCheck($redirectUrl, $requestedUrl)
    {
        if (!is_string($redirectUrl) || !$this->isHealthCheckRequest()) {
            return $redirectUrl;
        }

        $redirect = (array) parse_url($redirectUrl);
        $requested = (array) parse_url((string) $requestedUrl);

        foreach (['scheme', 'host', 'port'] as $component) {
            if (($redirect[$component] ?? null) !== ($requested[$component] ?? null)) {
                return $redirectUrl;
            }
        }

        return false;
    }

    /**
     * Run the health diagnostics and send a generic JSON response if the request is a Ymir health check.
     */
    public function respondToHealthCheck(): void
    {
        if (!$this->isHealthCheckRequest()) {
            return;
        }

        $statusCode = 200;

        try {
            $this->eventManager->execute('ymir_diagnosing_health');
        } catch (\Throwable $exception) {
            $this->report($exception);

            $statusCode = 500;
        }

        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }

        nocache_headers();
        wp_send_json(['status' => 200 === $statusCode ? 'up' : 'down'], $statusCode);
    }

    /**
     * {@inheritdoc}
     */
    public function setWordPress(\WP $wp): void
    {
        $this->wp = $wp;
    }

    /**
     * Checks if the current request is a Ymir health check for the "/ymir/health" endpoint that the site doesn't own.
     */
    private function isHealthCheckRequest(): bool
    {
        if (!isset($_SERVER['YMIR_HEALTH_CHECK'], $_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']) || '1' !== $_SERVER['YMIR_HEALTH_CHECK'] || 'GET' !== $_SERVER['REQUEST_METHOD']) {
            return false;
        }

        if (!is_404() && (!$this->wp instanceof \WP || $this->wp->did_permalink || !empty($this->wp->query_vars))) {
            return false;
        }

        return untrailingslashit((string) parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH)) === untrailingslashit((string) parse_url(home_url(), PHP_URL_PATH)).'/ymir/health';
    }

    /**
     * Report the diagnostic exception without letting a reporting failure replace the generic response.
     */
    private function report(\Throwable $exception): void
    {
        try {
            error_log(sprintf('Ymir health check failed: %s', $exception));
        } catch (\Throwable $reportingException) {
        }
    }
}
