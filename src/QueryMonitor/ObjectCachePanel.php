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

namespace Ymir\Plugin\QueryMonitor;

use Ymir\Plugin\Support\Collection;

class ObjectCachePanel extends \QM_Output_Html
{
    /**
     * Path to the view used by the panel.
     *
     * @var string
     */
    private $view;

    /**
     * Constructor.
     */
    public function __construct(ObjectCacheCollector $collector, string $view)
    {
        parent::__construct($collector);

        $this->view = $view;
    }

    /**
     * Create a panel from the Query Monitor collectors.
     */
    public static function createFromCollectors(\QM_Collectors $collectors, string $viewDirectory)
    {
        $collector = $collectors::get(ObjectCacheCollector::COLLECTOR_ID);

        if (!$collector instanceof ObjectCacheCollector) {
            throw new \RuntimeException('Unable to get "ObjectCacheCollector"');
        }

        return new static($collector, rtrim($viewDirectory, '/').'/object-cache.php');
    }

    /**
     * Add the panel to the WordPress admin bar menu.
     */
    public function addToAdminBarMenu(array $menu): array
    {
        $menu[$this->collector->id()] = $this->menu([
            'title' => esc_html($this->name()),
        ]);

        return $menu;
    }

    /**
     * Add the panel to the Query Monitor panel menu.
     */
    public function addToMenu(array $menu): array
    {
        $menu = new Collection($menu);
        $position = $menu->keys()->search('qm-db_queries-$wpdb');

        if (!is_int($position)) {
            $position = $menu->count();
        }

        return array_merge($menu->slice(0, $position)->all(), [$this->collector->id() => $this->menu(['title' => $this->name()])], $menu->slice($position)->all());
    }

    /**
     * Get the ID of the panel.
     */
    public function id(): string
    {
        return 'ymir-object-cache';
    }

    /**
     * Get the name of the view.
     */
    public function name(): string
    {
        $data = $this->collector->get_data();
        $name = 'Object Cache';

        if (!empty($data['type'])) {
            $name .= sprintf(' (%s)', $data['type']);
        }

        return $name;
    }

    /**
     * Generate the HTML of the query monitor panel.
     */
    public function output()
    {
        if (!file_exists($this->view)) {
            return;
        }

        $data = $this->collector->get_data();

        require $this->view;
    }
}
