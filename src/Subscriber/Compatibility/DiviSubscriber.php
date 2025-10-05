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

namespace Ymir\Plugin\Subscriber\Compatibility;

use Ymir\Plugin\EventManagement\SubscriberInterface;

/**
 * Subscriber that handles Divi compatibility.
 *
 * Disables Divi's dynamic asset features to ensure all styles are rendered correctly
 * when using Ymir's cloud storage and content delivery network. Tries to mirror
 * the behaviour used by the Divi page builder preview page.
 *
 * @see et_pb_preview_page_disable_dynamic_assets()
 */
class DiviSubscriber implements SubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'et_builder_should_load_all_module_data' => 'loadAllModuleData',
            'et_disable_js_on_demand' => 'disableJsOnDemand',
            'et_use_dynamic_css' => 'disableDynamicCss',
            'et_should_generate_dynamic_assets' => 'disableDynamicAssets',
            'et_builder_critical_css_enabled' => 'disableCriticalCss',
            'et_builder_post_feature_cache_enabled' => 'disableFeatureCache',
        ];
    }

    /**
     * Disable critical CSS feature.
     */
    public function disableCriticalCss(): bool
    {
        return false;
    }

    /**
     * Disable dynamic assets generation feature.
     */
    public function disableDynamicAssets(): bool
    {
        return false;
    }

    /**
     * Disable dynamic CSS generation feature.
     */
    public function disableDynamicCss(): bool
    {
        return false;
    }

    /**
     * Disable feature manager cache.
     */
    public function disableFeatureCache(): bool
    {
        return false;
    }

    /**
     * Disable JavaScript on-demand loading feature.
     */
    public function disableJsOnDemand(): bool
    {
        return true;
    }

    /**
     * Instruct the shortcode manager to register and load all modules/shortcodes.
     */
    public function loadAllModuleData(): bool
    {
        return true;
    }
}
