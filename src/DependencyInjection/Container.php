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

namespace Ymir\Plugin\DependencyInjection;

/**
 * The plugin's dependency injection container.
 */
class Container implements \ArrayAccess
{
    /**
     * Tracks all container keys that have been locked because they were accessed.
     *
     * @var array
     */
    private $locked;

    /**
     * Values stored inside the container.
     *
     * @var array
     */
    private $values;

    /**
     * Constructor.
     */
    public function __construct(array $values = [])
    {
        $this->values = $values;
    }

    /**
     * Configure the container using the given container configuration object(s).
     */
    public function configure($configurations)
    {
        if (!is_array($configurations)) {
            $configurations = [$configurations];
        }

        foreach ($configurations as $configuration) {
            $this->modify($configuration);
        }
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     */
    public function get($id)
    {
        return $this->offsetGet($id);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($key): bool
    {
        return array_key_exists($key, $this->values);
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($key)
    {
        if (!array_key_exists($key, $this->values)) {
            throw new \InvalidArgumentException(sprintf('Container doesn\'t have a value stored for the "%s" key.', $key));
        }

        $this->locked[$key] = true;

        return $this->values[$key] instanceof \Closure ? $this->values[$key]($this) : $this->values[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($key, $value): void
    {
        if (isset($this->locked[$key])) {
            throw new \RuntimeException(sprintf('Container value "%s" is locked and cannot be modified.', $key));
        }

        $this->values[$key] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($key): void
    {
        unset($this->locked[$key], $this->values[$key]);
    }

    /**
     * Creates a closure used for creating a service using the given callable.
     */
    public function service(callable $callable): callable
    {
        if (!is_object($callable) || !method_exists($callable, '__invoke')) {
            throw new \InvalidArgumentException('Service definition is not a Closure or invokable object.');
        }

        return function (self $container) use ($callable) {
            static $object;

            if (null === $object) {
                $object = $callable($container);
            }

            return $object;
        };
    }

    /**
     * Modify the container using the given container configuration object.
     */
    private function modify($configuration)
    {
        if (is_string($configuration)) {
            $configuration = new $configuration();
        }

        if (!$configuration instanceof ContainerConfigurationInterface) {
            throw new \InvalidArgumentException('Configuration object must implement the "ContainerConfigurationInterface".');
        }

        $configuration->modify($this);
    }
}
