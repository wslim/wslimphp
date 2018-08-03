<?php
namespace Wslim\Route;

use InvalidArgumentException;

/**
 * Route Interface
 *
 */
interface RouteInterface
{

    /**
     * Retrieve a specific route argument
     *
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function getArgument($name, $default = null);

    /**
     * Get route arguments
     *
     * @return array
     */
    public function getArguments();

    /**
     * Get route name
     *
     * @return null|string
     */
    public function getName();

    /**
     * Get route pattern
     *
     * @return string
     */
    public function getPattern();
    
    /**
     * get route callable
     * 
     * @return mixed
     */
    public function getCallable();

    /**
     * Set a route argument
     *
     * @param string $name
     * @param string $value
     *
     * @return static
     */
    public function setArgument($name, $value);

    /**
     * Replace route arguments
     *
     * @param array $arguments
     *
     * @return static
     */
    public function setArguments(array $arguments);

    /**
     * Set route name
     *
     * @param string $name
     *
     * @return static
     * @throws InvalidArgumentException if the route name is not a string
     */
    public function setName($name);

}
