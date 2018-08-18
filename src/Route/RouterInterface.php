<?php
namespace Wslim\Route;

use Wslim\Common\InvalidConfigException;
use RuntimeException;
use InvalidArgumentException;

/**
 * Router Interface
 */
interface RouterInterface
{
    // array keys from route result
    const DISPATCH_STATUS = 0;
    const ALLOWED_METHODS = 1;

    /**
     * Add route
     *
     * @param string[] $methods Array of HTTP methods
     * @param string   $pattern The route pattern
     * @param callable $callable The route callable
     *
     * @return RouteInterface
     */
    public function map($methods, $pattern, $callable);

    /**
     * Dispatch router for HTTP request
     *
     * @param  string $method
     * @param  string $uri
     * 
     * @return DispatchResult
     *
     */
    public function dispatch($method, $uri);
 
    /**
     * Add a route group to the array
     *
     * @param string   $pattern The group pattern
     * @param callable $callable A group callable
     *
     * @return RouteGroupInterface
     */
    public function pushGroup($pattern, $callable);

    /**
     * Removes the last route group from the array
     *
     * @return bool True if successful, else False
     */
    public function popGroup();

    /**
     * Get named route object
     *
     * @param string $name        Route name
     *
     * @return \Wslim\Route\RouteInterface
     *
     * @throws RuntimeException   If named route does not exist
     */
    public function getNamedRoute($name);

    /**
     * @param $identifier
     *
     * @return \Wslim\Route\RouteInterface
     */
    public function lookupRoute($identifier);

    /**
     * Build the path for a named route excluding the base path
     *
     * @param string $name        Route name
     * @param array  $data        Named argument replacement data
     * @param array  $queryParams Optional query string parameters
     *
     * @return string
     *
     * @throws RuntimeException         If named route does not exist
     * @throws InvalidArgumentException If required data not provided
     */
    public function relativePathFor($name, array $data = [], array $queryParams = []);

    /**
     * Build the path for a named route including the base path
     *
     * @param string $name        Route name
     * @param array  $data        Named argument replacement data
     * @param array  $queryParams Optional query string parameters
     *
     * @return string
     *
     * @throws RuntimeException         If named route does not exist
     * @throws InvalidArgumentException If required data not provided
     */
    public function pathFor($name, array $data = [], array $queryParams = []);

    /**
     * add routes from config data
     *
     * @param  array $config
     * @throws InvalidConfigException
     * @return static
     */
    public function loadRoutes($config);
    
}
