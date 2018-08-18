<?php
namespace Wslim\Console;


class ConsoleDescriptor
{

    const GLOBAL_NAMESPACE = '_global';

    /**
     * @var App
     */
    private $console;

    /**
     * @var null|string
     */
    private $namespace;

    /**
     * @var array
     */
    private $namespaces;

    /**
     * @var Controller[]
     */
    private $controllers;

    /**
     * @var Controller[]
     */
    private $aliases;

    /**
     * construct
     * @param App $console
     * @param string|null  $namespace
     */
    public function __construct(App $console, $namespace = null)
    {
        $this->console   = $console;
        $this->namespace = $namespace;
    }

    /**
     * @return array
     */
    public function getNamespaces()
    {
        if (null === $this->namespaces) {
            $this->inspectConsole();
        }

        return $this->namespaces;
    }

    /**
     * @return Controller[]
     */
    public function getControllers()
    {
        if (null === $this->controllers) {
            $this->inspectConsole();
        }

        return $this->controllers;
    }

    /**
     * @param string $name
     * @return Controller
     * @throws \InvalidArgumentException
     */
    public function getController($name)
    {
        if (!isset($this->controllers[$name]) && !isset($this->aliases[$name])) {
            throw new \InvalidArgumentException(__METHOD__ . ': ' . sprintf('Controller %s does not exist.', $name));
        }

        return isset($this->controllers[$name]) ? $this->controllers[$name] : $this->aliases[$name];
    }

    private function inspectConsole()
    {
        $this->controllers   = [];
        $this->namespaces = [];

        $all = $this->console->getControllers($this->namespace ? $this->console->findControllerNamespace($this->namespace) : null);
        foreach ($this->sortControllers($all) as $namespace => $controllers) {
            $names = [];

            /** @var Controller $controller */
            foreach ($controllers as $name => $controller) {
                if (!$controller->getName()) {
                    continue;
                }

                if ($controller->getName() === $name) {
                    $this->controllers[$name] = $controller;
                } else {
                    $this->aliases[$name] = $controller;
                }

                $names[] = $name;
            }

            $this->namespaces[$namespace] = ['id' => $namespace, 'controllers' => $names];
        }
    }

    /**
     * @param array $controllers
     * @return array
     */
    private function sortControllers(array $controllers)
    {
        $namespacedControllers = [];
        foreach ($controllers as $name => $controller) {
            $key = $this->console->extractNamespace($name, 1);
            if (!$key) {
                $key = self::GLOBAL_NAMESPACE;
            }

            $namespacedControllers[$key][$name] = $controller;
        }
        ksort($namespacedControllers);

        foreach ($namespacedControllers as &$controllersSet) {
            ksort($controllersSet);
        }
        // unset reference to keep scope clear
        unset($controllersSet);

        return $namespacedControllers;
    }
}
