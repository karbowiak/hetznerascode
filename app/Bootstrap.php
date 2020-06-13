<?php

namespace Hac;

use Hac\Helpers\Hetzner;
use Composer\Autoload\ClassLoader;
use League\Container\Container;

/**
 * Class Bootstrap
 * @package Hac
 *
 * Defined below, are the various classes available through the container
 * This allows for simpler fetching of them when only bootstrapper is available
 *
 */
class Bootstrap
{
    protected ClassLoader $autoloader;
    protected Container $container;

    /**
     * Bootstrap constructor.
     *
     * @param \Composer\Autoload\ClassLoader $autoloader
     */
    public function __construct(ClassLoader $autoloader)
    {
        $this->autoloader = $autoloader;
    }

    /**
     * Initializes the container
     */
    public function initContainer(): void
    {
        $container = new Container();

        // Default container items
        $container->add('container', $container);
        $container->add('autoloader', $this->autoloader);
        $container->add('hetzner', new Hetzner($this));

        $this->container = $container;
    }

    /**
     * @return \League\Container\Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Returns the requested element from the container
     *
     * @param $name
     *
     * @return array|mixed|object
     */
    public function __get($name)
    {
        if ($this->container->has($name)) {
            return $this->container->get($name);
        }
        return null;
    }

    /**
     * @param $name
     *
     * @return array|mixed|object
     */
    public function get($name)
    {
        if ($this->container->has($name)) {
            return $this->container->get($name);
        }
        return null;
    }
}
