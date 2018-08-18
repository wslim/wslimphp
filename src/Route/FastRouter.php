<?php
namespace Wslim\Route;

use RuntimeException;
use InvalidArgumentException;
use FastRoute\RouteParser;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use Wslim\Util\UriHelper;

/**
 * fast router, wrap FastRoute class
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class FastRouter extends AbstractRouter
{
	/**
	 * Parser
	 *
	 * @var \FastRoute\RouteParser
	 */
	protected $routeParser;
	
	/**
	 * Path to fast route cache file. Set to false to disable route caching
	 *
	 * @var string|False
	 */
	protected $cacheFile = false;
	
	/**
	 * @var \FastRoute\Dispatcher
	 */
	protected $dispatcher;
	
	/**
	 * Create new router
	 *
	 * @param RouteParser   $parser
	 */
	public function __construct(RouteParser $parser = null)
	{
		$this->routeParser = $parser ?: new Std();
	}
	
	/**
	 * get cache file
	 * @return string
	 */
	public function getCacheFile()
	{
	    return $this->cacheFile;
	}
	
	/**
	 * Set path to fast route cache file. If this is false then route caching is disabled.
	 *
	 * @param string|false $cacheFile
	 *
	 * @return self
	 *
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	public function setCacheFile($cacheFile)
	{
		if (!is_string($cacheFile) && $cacheFile !== false) {
			throw new InvalidArgumentException('Router cacheFile must be a string or false');
		}
		
		if ($cacheFile !== false && !is_writable(dirname($cacheFile))) {
			throw new RuntimeException('Router cacheFile directory must be writable');
		}
		
		$this->cacheFile = $cacheFile;
		
		return $this;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Wslim\Route\RouterInterface::dispatch()
	 */
	public function dispatch($method, $uri)
	{
	    $this->loadDefaultRoutes();
	    
	    $routeArguments = [];
	    
		// strip base url
		if (strpos($_SERVER['SCRIPT_NAME'], $uri) === 0) {
		    $uri = '/';
		} else {
		    $uri = str_replace($_SERVER['SCRIPT_NAME'], '', $uri);
            $uri = '/' . trim($uri, '/');
            
            // separate uri to uri and params
            $params = [];
            if (strpos($uri, '/p/')) {
                $parts = explode('/p/', $uri, 2);
                $uri = $parts[0];
                $params = $parts[1];
            } elseif (strpos($uri, '&')) {
                $parts = explode('&', $uri, 2);
                $uri = $parts[0];
                $params = $parts[1];
            }
            
            if ($params) {
                $routeArguments = UriHelper::parseQueryFromPath($params);
            }
		}
		
		/**
		 * @var array $routeInfo
		 * [0]  not found
		 * [1, $routeIndentifier, [GET, $request_uri]] found 
		 * [2, ['GET', 'OTHER_ALLOWED_METHODS']] not allowed
		 * 
		 * @link   https://github.com/nikic/FastRoute/blob/master/src/Dispatcher.php
		 */
		$routeInfo = $this->createDispatcher()->dispatch($method, $uri);
		
		$result = new DispatchResult();
		$result->flag = $routeInfo[0];
		
		if ($routeInfo[0] === Dispatcher::FOUND) {
			$route = $this->lookupRoute($routeInfo[1]);
			
			foreach ($routeInfo[2] as $k => $v) {
				$routeArguments[$k] = urldecode($v);
			}
			$route->setArguments($routeArguments);
			
			$result->route = $route;
		} elseif ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
			$result->info = $routeInfo[RouterInterface::ALLOWED_METHODS];
		}
		//print_r($this->getRoutesPatterns()); print_r($result); exit;
		
		return $result;
	}

	/**
	 * @return \FastRoute\Dispatcher
	 */
	protected function createDispatcher()
	{
		if ($this->dispatcher) {
			return $this->dispatcher;
		}
		
		$routeDefinitionCallback = function (RouteCollector $r) {
			foreach ($this->getRoutes() as $route) {
				$r->addRoute($route->getMethods(), $route->getPattern(), $route->getIdentifier());
			}
		};
		
		if ($this->cacheFile) {
			$this->dispatcher = \FastRoute\cachedDispatcher($routeDefinitionCallback, [
					'routeParser' => $this->routeParser,
					'cacheFile' => $this->cacheFile,
			]);
		} else {
			$this->dispatcher = \FastRoute\simpleDispatcher($routeDefinitionCallback, [
					'routeParser' => $this->routeParser,
			]);
		}
		
		return $this->dispatcher;
	}
	
	/**
	 * @param \FastRoute\Dispatcher $dispatcher
	 */
	public function setDispatcher(Dispatcher $dispatcher)
	{
		$this->dispatcher = $dispatcher;
	}
	
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
	public function relativePathFor($name, array $data = [], array $queryParams = [])
	{
		$route = $this->getNamedRoute($name);
		$pattern = $route->getPattern();
		
		$routeDatas = $this->routeParser->parse($pattern);
		// $routeDatas is an array of all possible routes that can be made. There is
		// one routedata for each optional parameter plus one for no optional parameters.
		//
		// The most specific is last, so we look for that first.
		$routeDatas = array_reverse($routeDatas);
		
		$segments = [];
		foreach ($routeDatas as $routeData) {
			foreach ($routeData as $item) {
				if (is_string($item)) {
					// this segment is a static string
					$segments[] = $item;
					continue;
				}
				
				// This segment has a parameter: first element is the name
				if (!array_key_exists($item[0], $data)) {
					// we don't have a data element for this segment: cancel
					// testing this routeData item, so that we can try a less
					// specific routeData item.
					$segments = [];
					$segmentName = $item[0];
					break;
				}
				$segments[] = $data[$item[0]];
			}
			if (!empty($segments)) {
				// we found all the parameters for this route data, no need to check
				// less specific ones
				break;
			}
		}
		
		if (empty($segments)) {
			throw new InvalidArgumentException('Missing data for URL segment: ' . $segmentName);
		}
		$url = implode('', $segments);
		
		if ($queryParams) {
			$url .= '?' . http_build_query($queryParams);
		}
		
		return $url;
	}
	
}