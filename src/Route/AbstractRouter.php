<?php
namespace Wslim\Route;

use Wslim\Common\InvalidConfigException;
use InvalidArgumentException;
use RuntimeException;

abstract class AbstractRouter implements RouterInterface
{	
	/**
	 * Base path used in pathFor()
	 *
	 * @var string
	 */
	protected $basePath = '';
	
	/**
	 * Routes
	 *
	 * @var Route[]
	 */
	protected $routes = null;
	
	/**
	 * Route counter incrementer
	 * @var int
	 */
	protected $routeCounter = 0;
	
	/**
	 * Route groups
	 *
	 * @var \Wslim\Route\RouteGroup[]
	 */
	protected $routeGroups = [];

	
	/**
	 * Set found route invocation strategy
	 *
	 * @param callable $callable
	 */
	public function setFoundHandler(callable $callable)
	{
		$this->foundHandler= $callable;
	}

	/**
	 * Set the base path used in pathFor()
	 *
	 * @param string $basePath
	 *
	 * @return self
	 *
	 * @throws InvalidArgumentException
	 */
	public function setBasePath($basePath)
	{
		if (!is_string($basePath)) {
			throw new InvalidArgumentException('Router basePath must be a string');
		}
		
		$this->basePath = $basePath;
		
		return $this;
	}
	
	/**
	 * Add route
	 *
	 * @param  string[] $methods Array of HTTP methods
	 * @param  string   $pattern The route pattern
	 * @param  callable $callable The route callable
	 *
	 * @return RouteInterface
	 *
	 * @throws InvalidArgumentException if the route pattern isn't a string
	 */
	public function map($methods, $pattern, $callable)
	{
		$methods = (array) $methods;
		if (!is_string($pattern)) {
			throw new InvalidArgumentException('Route pattern must be a string');
		}
		
		// Prepend parent group pattern(s)
		if ($this->routeGroups) {
			$pattern = $this->processGroups() . $pattern;
		}
		
		// According to RFC methods are defined in uppercase (See RFC 7231)
		$methods = array_map("strtoupper", $methods);
		
		// Add route
		$route = $this->createRoute($methods, $pattern, $callable);
		$this->routes[$route->getIdentifier()] = $route;
		$this->routeCounter++;
		
		return $route;
	}
	/**
	 * Create a new Route object
	 *
	 * @param  string[] $methods Array of HTTP methods
	 * @param  string   $pattern The route pattern
	 * @param  callable $callable The route callable
	 *
	 * @return \Wslim\Route\RouteInterface
	 */
	protected function createRoute($methods, $pattern, $callable)
	{
		$route = new Route($methods, $pattern, $callable, $this->routeGroups, $this->routeCounter);
		
		return $route;
	}
	
	/**
	 * Get route objects
	 *
	 * @return \Wslim\Route\Route[]
	 */
	public function getRoutes()
	{
		return $this->routes;
	}
	
	/**
	 * Get named route object
	 *
	 * @param string $name        Route name
	 *
	 * @return Route
	 *
	 * @throws RuntimeException   If named route does not exist
	 */
	public function getNamedRoute($name)
	{
		foreach ($this->routes as $route) {
			if ($name == $route->getName()) {
				return $route;
			}
		}
		throw new RuntimeException('Named route does not exist for name: ' . $name);
	}
	
	/**
	 * Remove named route
	 *
	 * @param string $name        Route name
	 *
	 * @throws RuntimeException   If named route does not exist
	 */
	public function removeNamedRoute($name)
	{
		$route = $this->getNamedRoute($name);
		
		// no exception, route exists, now remove by id
		unset($this->routes[$route->getIdentifier()]);
	}
	
	/**
	 * Process route groups
	 *
	 * @return string A group pattern to routes with
	 */
	protected function processGroups()
	{
		$pattern = "";
		foreach ($this->routeGroups as $group) {
			$pattern .= $group->getPattern();
		}
		return $pattern;
	}
	
	/**
	 * Add a route group to the array
	 *
	 * @param string   $pattern
	 * @param callable $callable
	 *
	 * @return RouteGroupInterface
	 */
	public function pushGroup($pattern, $callable)
	{
		$group = new RouteGroup($pattern, $callable);
		array_push($this->routeGroups, $group);
		return $group;
	}
	
	/**
	 * Removes the last route group from the array
	 *
	 * @return RouteGroup|bool The RouteGroup if successful, else False
	 */
	public function popGroup()
	{
		$group = array_pop($this->routeGroups);
		return $group instanceof RouteGroup ? $group : false;
	}
	
	/**
	 * @param $identifier
	 * @return \Wslim\Route\RouteInterface
	 */
	public function lookupRoute($identifier)
	{
		if (!isset($this->routes[$identifier])) {
			throw new RuntimeException('Route not found, looks like your route cache is stale.');
		}
		return $this->routes[$identifier];
	}
	
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
	public function pathFor($name, array $data = [], array $queryParams = [])
	{
		$url = $this->relativePathFor($name, $data, $queryParams);
		
		if ($this->basePath) {
			$url = $this->basePath . $url;
		}
		
		return $url;
	}
	
	/**----------------------------- 扩展快捷设置方法 ----------------------------------**/
	/**
	 * Add GET route
	 *
	 * @param  string $pattern  The route URI pattern
	 * @param  callable|string  $callable The route callback routine
	 *
	 * @return \Wslim\Route\RouteInterface
	 */
	public function get($pattern, $callable)
	{
		return $this->map(['GET'], $pattern, $callable);
	}
	
	/**
	 * Add POST route
	 *
	 * @param  string $pattern  The route URI pattern
	 * @param  callable|string  $callable The route callback routine
	 *
	 * @return \Wslim\Route\RouteInterface
	 */
	public function post($pattern, $callable)
	{
		return $this->map(['POST'], $pattern, $callable);
	}
	
	/**
	 * Add PUT route
	 *
	 * @param  string $pattern  The route URI pattern
	 * @param  callable|string  $callable The route callback routine
	 *
	 * @return \Wslim\Route\RouteInterface
	 */
	public function put($pattern, $callable)
	{
		return $this->map(['PUT'], $pattern, $callable);
	}
	
	/**
	 * Add PATCH route
	 *
	 * @param  string $pattern  The route URI pattern
	 * @param  callable|string  $callable The route callback routine
	 *
	 * @return \Wslim\Route\RouteInterface
	 */
	public function patch($pattern, $callable)
	{
		return $this->map(['PATCH'], $pattern, $callable);
	}
	
	/**
	 * Add DELETE route
	 *
	 * @param  string $pattern  The route URI pattern
	 * @param  callable|string  $callable The route callback routine
	 *
	 * @return \Wslim\Route\RouteInterface
	 */
	public function delete($pattern, $callable)
	{
		return $this->map(['DELETE'], $pattern, $callable);
	}
	
	/**
	 * Add OPTIONS route
	 *
	 * @param  string $pattern  The route URI pattern
	 * @param  callable|string  $callable The route callback routine
	 *
	 * @return \Wslim\Route\RouteInterface
	 */
	public function options($pattern, $callable)
	{
		return $this->map(['OPTIONS'], $pattern, $callable);
	}
	
	/**
	 * Add route for any HTTP method
	 *
	 * @param  string $pattern  The route URI pattern
	 * @param  callable|string  $callable The route callback routine
	 *
	 * @return \Wslim\Route\RouteInterface
	 */
	public function any($pattern, $callable)
	{
		return $this->map(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $pattern, $callable);
	}
	
	/**
	 * add route for any http method, alias of any()
	 * @param string   $pattern
	 * @param callable $callable
	 * @return \Wslim\Route\RouteInterface
	 */
	public function add($pattern, $callable)
	{
		return $this->any($pattern, $callable);
	}
	
	/**
	 * add group route for 
	 * @param string   $pattern
	 * @param callable $callable when group match run
	 * @param callable $immediateCallable run immediate
	 * 
	 * @return \Wslim\Route\RouteGroupInterface
	 */
	public function group($pattern, $callable, $immediateCallable=null)
	{
		$group = $this->pushGroup($pattern, $callable);
		if ($immediateCallable) call_user_func($immediateCallable, $this);
		$this->popGroup();
		return $group;
	}
	
	/**
	 * get all route's patterns
	 *  
	 * @return string[]
	 */
	public function getRoutesPatterns()
	{
		$patterns = [];
		$rows = $this->getRoutes();
		if ($rows) foreach ($rows as $route) {
		    $patterns[] = $route->getPattern();
		}
		return $patterns;
	}
	
	/**
	 * add routes from config data, route support {} and []: '/user/{name}[/{id:[0-9]+}]'
	 * 
	 * @param  array $config
	 * @throws InvalidConfigException
	 * @return static
	 */
	public function loadRoutes($config)
	{		
	    $allMethods = '*';  //['GET', 'POST'];
		
	    $mArguments = [];
	    if (isset($config['arguments'])) {
	        $mArguments = (array)$config['arguments'];
	        unset($config['arguments']);
	    }
	    
		if ($config) foreach ($config as $key => $value) {
			if (is_numeric($key)) {
				$key   = $value;
				$value = '';
			}
			
			// 'group:/m|/mobile', support seprate by |
			if (strpos($key, 'group:') === 0) {
				$pattern = str_replace('group:', '', $key);
				$groups = explode('|', $pattern);
				foreach ($groups as $v) {
				    $v = '/' . trim(trim($v), '/');
				    if ($v && $v !== '/') {
				        $this->pushGroup($v, null);
				        $this->loadRoutes($value);
				        $this->popGroup();
				    } else {
				        $this->loadRoutes($value);
				    }
				}
				
				continue;
			} 
			
			// 'pattern' => ...
			$pattern     = '/' . ltrim($key, '/');
			$arguments   = [];
			if (is_string($value)) {
				$methods  = $allMethods;
				$callable = $value;
			} elseif (is_array($value)) {
				if (count($value) === 1) {
					$methods   = $allMethods;
					$callable  = $value[0];
				} else {
					$methods   = empty($value[0]) || $value[0] == '*' ? $allMethods : $value[0];
					$callable  = $value[1];
					$arguments = isset($value[2]) ? $value[2] : [];
				}
				
			} else {
				$message = 'route config error.';
				throw new InvalidConfigException($message);
			}
			
			// pattern replace
			//$pattern = str_replace('{controller}', '{controller:[a-zA-Z0-9\_\/]+}', $pattern);
			$pattern = preg_replace('/\{(controller[0-9]?)\}/', '{\1:[a-zA-Z0-9\_\/]+(?:.html)?}', $pattern);
			$pattern = str_replace('{params}', '{params:.+}', $pattern);
			
			$inner_regs = [
			    '{id}'       => '{id:num}',
			    ':number'    => ':[0-9]+',
			    ':num'       => ':[0-9]+',
			    ':id'        => ':[0-9]+',
			    ':name'      => ':[a-zA-Z0-9\-\_\.]+',
			    ':any'       => ':.*',
			];
			foreach ($inner_regs as $k => $v) {
			    $pattern = str_replace($k, $v, $pattern);
			}
			
			// replace regrex
			$pattern = preg_replace('/\{([a-zA-Z0-9\-\_]+)\}/', '{\1:[a-zA-Z0-9\_\-\.]+}', $pattern);
			
			// add .html
			if ($pattern !== '/' && !strpos($pattern, '.html')) {
			    $pattern = preg_replace('/(\]*)$/', '[.html]\1', $pattern, 1);
			}
			
			//print_r($pattern . PHP_EOL);
 			//print_r($pattern . ':' . is_callable($callable, true) . PHP_EOL);
			$this->map($methods, $pattern, $callable)->setArguments($arguments);
		}
		
		return $this;
	}
	
	/**
	 * get default route: '/{controller}[/p/{params}]'
	 * @return string[]
	 */
	protected function getDefaultRoutes()
	{
	    $routes = [
	        //'/{controller}[/p/{params}]',
	        '/{controller}/{id}[.html]',   // for /article/12.html
	        '/{controller}',        // for /article.html
	    ];
	    
	    // root route for '/'
	    $hasRootRoute = false;
	    $patterns = $this->getRoutesPatterns();
	    foreach ($patterns as $pattern) {
	        if (strpos($pattern, '/[') === 0 || $pattern == '/') {
	            $hasRootRoute = true;
	            break;
	        }
	    }
	    
	    if (!$hasRootRoute) {
	        $routes['/'] = '/';
	    }
	    
	    return $routes;
	}
	
	/**
	 * load default routes
	 * @return void
	 */
	protected function loadDefaultRoutes()
	{
	    $routes = $this->getDefaultRoutes();
		$this->loadRoutes($routes);
	}	
}
