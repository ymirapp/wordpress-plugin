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
 * Subscriber that handles the integration with the Query Monitor plugin.
 */
class QueryMonitorSubscriber implements SubscriberInterface
{
    /**
     * Query monitor collectors.
     *
     * @var array
     */
    private $collectors;

    /**
     * Query monitor panels.
     *
     * @var array
     */
    private $panels;

    /**
     * The path to the Query Monitor views directory.
     *
     * @var string
     */
    private $viewsDirectory;

    /**
     * Constructor.
     */
    public function __construct(array $collectors, array $panels, string $viewsDirectory)
    {
        $this->collectors = $collectors;
        $this->panels = $panels;
        $this->viewsDirectory = $viewsDirectory;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'init' => 'addCollectors',
            'qm/outputter/html' => ['addPanels', 10, 2],
            'qm/output/panel_menus' => 'addPanelsToMenu',
        ];
    }

    /**
     * Add all the Ymir Query Monitor data collectors.
     */
    public function addCollectors()
    {
        if (!is_plugin_active('query-monitor/query-monitor.php') || !class_exists(\QM_Collector::class)) {
            return;
        }

        foreach ($this->collectors as $collector) {
            \QM_Collectors::add($collector);
        }
    }

    /**
     * Add all the Ymir Query Monitor panels.
     */
    public function addPanels(array $panels, \QM_Collectors $collectors): array
    {
        if (!class_exists(\QM_Output_Html::class)) {
            return $panels;
        }

        $this->panels = array_map(function (string $panel) use ($collectors) {
            return $panel::createFromCollectors($collectors, $this->viewsDirectory);
        }, $this->panels);

        return array_merge($panels, $this->panels);
    }

    /**
     * Add panels to Query Monitor panel menu.
     */
    public function addPanelsToMenu(array $menu)
    {
        foreach ($this->panels as $panel) {
            $menu = $panel->addToMenu($menu);
        }

        return $menu;
    }
}
