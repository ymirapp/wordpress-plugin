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
use Ymir\Plugin\Http\ClientInterface;

/**
 * Subscriber that shows in the plugin list when a newer version of the Ymir plugin is available.
 */
class PluginUpdateSubscriber implements SubscriberInterface
{
    /**
     * Site transient key containing the latest plugin version, or an empty string if the lookup failed.
     */
    private const LATEST_VERSION_TRANSIENT = 'ymir_latest_plugin_version';

    /**
     * Packagist package name of the plugin.
     */
    private const PACKAGE_NAME = 'ymirapp/wordpress-plugin';

    /**
     * The HTTP client used to look up the latest plugin version.
     *
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * The basename of the plugin's main file.
     *
     * @var string
     */
    private $pluginBasename;

    /**
     * The version of the running plugin.
     *
     * @var string
     */
    private $pluginVersion;

    /**
     * Constructor.
     */
    public function __construct(ClientInterface $httpClient, string $pluginBasename, string $pluginVersion)
    {
        $this->httpClient = $httpClient;
        $this->pluginBasename = $pluginBasename;
        $this->pluginVersion = $pluginVersion;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'after_plugin_row_meta' => 'displayUpdateMessage',
        ];
    }

    /**
     * Display an update message in the plugin's row of the plugin list when a newer version is available.
     *
     * WordPress only displays its own update row to users who can update plugins, which "DISALLOW_FILE_MODS" prevents. The
     * message reuses the core string WordPress shows those users so that it's translated.
     */
    public function displayUpdateMessage($pluginFile): void
    {
        if ($pluginFile !== $this->pluginBasename) {
            return;
        }

        $latestVersion = $this->getLatestVersion();

        if (empty($latestVersion) || version_compare($latestVersion, $this->pluginVersion, '<=')) {
            return;
        }

        printf('<div class="update-message notice inline notice-warning notice-alt"><p>%s</p></div>', sprintf(__('There is a new version of %1$s available. <a href="%2$s" %3$s>View version %4$s details</a>.'), 'Ymir', 'https://github.com/ymirapp/wordpress-plugin/blob/main/CHANGELOG.md', '', htmlspecialchars($latestVersion, ENT_QUOTES)));
    }

    /**
     * Fetch the latest version of the plugin from Packagist, which lists the newest version first.
     */
    private function fetchLatestVersion(): string
    {
        try {
            $response = $this->httpClient->request(sprintf('https://repo.packagist.org/p2/%s.json', self::PACKAGE_NAME), ['timeout' => 5]);
        } catch (\RuntimeException $exception) {
            return '';
        }

        $metadata = 200 === (int) ($response['response']['code'] ?? 0) ? json_decode((string) ($response['body'] ?? ''), true) : null;

        return ltrim((string) ($metadata['packages'][self::PACKAGE_NAME][0]['version'] ?? ''), 'v');
    }

    /**
     * Get the latest version of the plugin, or an empty string if it's unknown.
     */
    private function getLatestVersion(): string
    {
        $latestVersion = get_site_transient(self::LATEST_VERSION_TRANSIENT);

        if (is_string($latestVersion)) {
            return $latestVersion;
        }

        $latestVersion = $this->fetchLatestVersion();

        set_site_transient(self::LATEST_VERSION_TRANSIENT, $latestVersion, 12 * HOUR_IN_SECONDS);

        return $latestVersion;
    }
}
