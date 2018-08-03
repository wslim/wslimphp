<?php
namespace Wslim\Web;

use RuntimeException;
use Slim\Http\Body;
use Wslim\Ioc;
use Wslim\Common\App as BaseApp;
use Wslim\Common\Config;
use Wslim\Common\DefaultConfig;
use Wslim\Common\InvalidConfigException;
use Wslim\Common\ResponseInterface;
use Wslim\Route\DispatchResult;
use Wslim\Route\FastRouter;
use Wslim\Util\UriHelper;
use Wslim\Common\RequestInterface;
use Wslim\Common\Controller;
use Wslim\Route\RouteInterface;

/**
 * web application
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class App extends BaseApp
{
    /**
     * {@inheritDoc}
     * @see \Wslim\Common\Module::defaultComponents()
     */
    protected function defaultComponents()
    {
        $components = array(
            // notAllowedHandler
            'notAllowedHandler' => '\\Wslim\\Web\\NotAllowedHandler',
            
            // notFoundHandler
            'notFoundHandler'	=> '\\Wslim\\Web\\NotFoundHandler',
        );
        
        // merge parent
        return array_merge(parent::defaultComponents(), $components);
    }
    
    /**
     * {@inheritDoc}
     * @see \Wslim\Common\Module::beforeInit()
     */
    protected function beforeInit()
	{
	    parent::beforeInit();
	    
	    // load default config
	    Config::setDefault(DefaultConfig::web());
	    
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Wslim\Common\App::route()
	 * @throws InvalidConfigException
	 */
	public function route(RequestInterface $request)
	{
	    // set errorHandler ContentType
	    Ioc::errorHandler()->setContentType($request->detectContentType());
	    
	    /** @var Request $request */
	    $uri = $request->getUri();
	    $uriPath   = $uri->getPath();
	    $uriQuery  = $uri->getQuery();
	    if (strpos('/' . $uriQuery . '&', $uriPath . '&') === 0) {
	        $uri = $uri->withQuery(trim(str_replace($uriPath, '', '/' . $uriQuery), '&'));
	        $request = $request->withUri($uri);
	    }
	    
	    // queryParams
	    $queryParams = $request->getQueryParams();
	    $reUriPath  = trim($uriPath, '/'); 
	    $reUriPath2 = str_replace('.', '_', $reUriPath);
	    if (isset($_GET[$reUriPath2]) && !$_GET[$reUriPath2]) {
	        unset($queryParams[$reUriPath]);
	        unset($_GET[$reUriPath]);
	        unset($_GET[$reUriPath2]);
        }
        
	    if (!strpos($uriPath, '.php')) {
	        Config::set('router.url_mode', 1);
	    }
	    
	    // router parse and dispatch
	    $router    = $this->getRouter();
	    $result    = $router->dispatch($request->getMethod(), $uriPath);
	    
	    $this->setRoute($result->route);
	    
	    if ($result->flag == DispatchResult::FOUND) {
	        
	        $basePath = $this->getBasePath();
	        
	        // route and route arguments and route callable
	        $route = $result->route; 
	        $routeArguments = $route->getArguments();
	        $controller = static::getControllerFromArguments($routeArguments);
	        $callable = $route->getCallable();
	        
	        // if queryParams has r param: https://domain.cn/?r=/a/b/c
	        if (isset($queryParams['r'])) {
	            $controller = $queryParams['r'];
	            unset($queryParams['r']);
	        }
	        unset($routeArguments['controller']);
	        
	        // merge routeArguments and queryParams
	        $request = $request->withQueryParams(array_merge($routeArguments, $queryParams));
	        
	        if (is_callable($callable, false)) {         // 1. url => callbale, return it
	            $object = $callable;
	        } elseif (is_string($callable)) {
	            if (strpos($callable, 'http') === 0) {   // 2. url => https://xxx , return file_get_contents() from it
	                $object = function () use ($callable) {
	                    return file_get_contents($callable);
	                };
	            } elseif (strpos($callable, '.php') !== false) { // 3. url => ***.php, try [***.php, basePath/***.php, basePath/php/***.php]
	                $handleFile = false;
	                $files = [
	                    $callable,
	                    $basePath . trim($callable, '\/'),
	                    $basePath . 'php/'. trim($callable, '\/')
	                ];
	                foreach ($files as $file) {
	                    if (file_exists($file)) {
	                        $handleFile = $file;
	                        break;
	                    }
	                }
	                
	                if (!$handleFile) {
	                    throw new InvalidConfigException('page is not exists:' . $callable);
	                }
	                
	                $object = function () use ($handleFile) {
	                    return include $handleFile;
	                };
	            } else {     // 4. url => string|array, parse to controller
	                
	                if ($callable) {
	                    $callable = trim($callable, '\/');
	                    $parts = parse_url($callable);
	                    if (isset($parts['path'])) {
	                        if (!$controller) {
	                            $controller = $parts['path'];
	                        } else {
	                            $controller = $parts['path'] . '/' . trim($controller, '\/');
	                        }
	                    }
	                    
	                    if (isset($parts['query'])) {
	                        parse_str($parts['query'], $arr);
	                        foreach ($arr as $k=>$v) {
	                            $routeArguments[$k] = urlencode($v);
	                        }
	                        $request = $request->withQueryParams(array_merge($routeArguments, $queryParams));
	                    }
	                }
	                
	                // get controller: first app then module
	                $module = $this->getCurrentModule()->getName();
	                $controller = trim($controller, '/');
	                
	                if (!$controller || ($module && strpos('/' . $controller . '/', '.' . $module . '/') !== 0) ) {
	                    $object = $this->getCurrentModule()->getController($controller);
	                } else {
	                    $object = $this->getController($controller);
	                }
	                
	                if (!$object && !$this->isDefaultModule()) {
                        $object = $this->getDefaultModule()->getController($controller);
	                }
	                
	                if ($object && $object->getHandleStack($request)) {
	                    // set current module
	                    $this->currentModule = $object->getModule();
	                    
	                } else {
	                    $object = $this->get('notFoundHandler');
	                }
	            }
	        } else {
	            throw new InvalidConfigException('route is error:' . print_r($callable, true));
	        }
	    } elseif ($result->flag == DispatchResult::METHOD_NOT_ALLOWED) {
	        $request = $request->withAttributes('allowMethods', $result->info);
	        $object = $this->get('notAllowedHandler');
	    } else {
	        $object = $this->get('notFoundHandler');
	    }
	    
	    // reset request
	    $this->setRequest($request);
	    
	    if (!($object instanceof Controller)) {
	        $object = new \Wslim\Web\Controller($object);
	    }
	    
	    $oClassname = get_class($object->getHandler());
	    $errmsg = sprintf('[%s][%s]%s:%s', $request->getMethod(), UriHelper::getCurrentUrl(), $oClassname, $object->getCamelCaseAction());
	    Ioc::logger('route')->debug($errmsg);
	    
	    return $object;
	}
	
	private function getControllerFromArguments(& $args)
	{
	    if (!$args) {
	        return null;
	    }
	    $controllers = [];
	    foreach ($args as $k => $v) {
	        if (preg_match('/controller\d?/', $k)) {
	            $num = str_replace('controller', '', $k);
	            $controllers[intval($num)] = $v;
	            unset($args[$k]);
	        }
	    }
	    return $controllers ? str_replace('.html', '', implode('/', $controllers)) : null;
	}
	
	/**
	 * Finalize response
	 * if output is string then append/prepend the response
	 * 
	 * @param  mixed  $output
	 * @return ResponseInterface
	 * 
	 * @throws RuntimeException
	 */
	public function finalize($output=null)
	{
	    $response = $this->getResponse();
	    if ($output instanceof ResponseInterface) {
	        $response = $output;
	    } elseif (is_string($output) && !empty($output) && $response->getBody()->isWritable()) {
	        if (Config::getCommon('response.outputBuffering') === 'prepend') {
	            // prepend output buffer content
	            $body = new Body(fopen('php://temp', 'r+'));
	            $body->write($output . $response->getBody());
	            $response = $response->withBody($body);
	        } else {
	            // append output buffer content
	            $response->getBody()->write($output);
	        }
	    }
	    
	    // stop PHP sending a Content-Type automatically
	    ini_set('default_mimetype', '');
	    
	    if ($this->isEmptyResponse($response)) {
	        return $response->withoutHeader('Content-Type')->withoutHeader('Content-Length');
	    }
	    
	    if (!$response->hasHeader('Content-Type')) {
	        $response = $response->withHeader('Content-Type', 'text/html; charset=UTF-8');
	    }
	    
	    // Add Content-Length header if `addContentLengthHeader` is set
	    if (Config::getCommon('response.addContentLengthHeader')) {
	        if (ob_get_length() > 0) {
	            throw new \RuntimeException("Unexpected data in output buffer. " .
	                "Maybe you have characters before an opening <?php tag?");
	        }
	        
	        $size = $response->getBody()->getSize();
	        if ($size !== null && !$response->hasHeader('Content-Length')) {
	            $response = $response->withHeader('Content-Length', (string) $size);
	        }
	    }
	    
	    return $response;
	}
	
	/**
	 * Send the response the client
	 *
	 * @param  ResponseInterface $response
	 * @return void
	 */
	public function send(ResponseInterface $response)
	{	
	    
		// Send response
		if (!headers_sent()) {
			// Status
			header(sprintf(
					'HTTP/%s %s %s',
					$response->getProtocolVersion(),
					$response->getStatusCode(),
					$response->getReasonPhrase()
					));
			
			// Headers
			foreach ($response->getHeaders() as $name => $values) {
				foreach ($values as $value) {
					header(sprintf('%s: %s', $name, $value), false);
				}
			}
		}
		
		// Body
		if (!$this->isEmptyResponse($response)) {
			$body = $response->getBody();
			if ($body->isSeekable()) {
				$body->rewind();
			}
			$chunkSize = Config::getCommon('response.chunkSize', 4096);
			$contentLength  = $response->getHeaderLine('Content-Length');
			if (!$contentLength) {
				$contentLength = $body->getSize();
			}
			
			if (isset($contentLength)) {
				$amountToRead = $contentLength;
				while ($amountToRead > 0 && !$body->eof()) {
					$data = $body->read(min($chunkSize, $amountToRead));
					echo $data;
					
					$amountToRead -= strlen($data);
					
					if (connection_status() != CONNECTION_NORMAL) {
						break;
					}
				}
			} else {
				while (!$body->eof()) {
					echo $body->read($chunkSize);
					if (connection_status() != CONNECTION_NORMAL) {
						break;
					}
				}
			}
		}
	}

	/**
	 * Helper method, which returns true if the provided response must not output a body and false
	 * if the response could have a body.
	 *
	 * @see https://tools.ietf.org/html/rfc7231
	 *
	 * @param ResponseInterface $response
	 * @return bool
	 */
	protected function isEmptyResponse(ResponseInterface $response)
	{
		if (method_exists($response, 'isEmpty')) {
			return $response->isEmpty();
		}
		
		return in_array($response->getStatusCode(), [204, 205, 304]);
	}
	
	/**
	 * Get router
	 *
	 * @return \Wslim\Route\RouterInterface
	 */
	public function getRouter()
	{
	    $name = 'router';
	    if (!$this->has($name)) {
	        $this->set($name, function () {
	            /*@var FastRouter $router */
	            $router = new FastRouter();
	            
	            $cacheFile = Config::getCommon('router.cache_file');
	            if (!Config::getCommon('debug') && $cacheFile) {
	                $router->setCacheFile($cacheFile);
	            }
	            
	            $routes = Config::load('routes');
	            if ($routes) {
	                $router->loadRoutes($routes);
	            }
	            
	            return $router;
	        });
	    }
	    return $this->get($name);
	}
	
	/**
	 * get current route
	 * @return RouteInterface
	 */
	public function getRoute()
	{
	    $name = 'router.route';
	    if (Ioc::has($name)) {
	        return Ioc::get($name);
	    }
	    
	    return null;
	}
	
	/**
	 * set current route
	 * @param  RouteInterface $route
	 * @return \Wslim\Web\App
	 */
	public function setRoute($route)
	{
	    $name = 'router.route';
	    $route && Ioc::setShared($name, $route);
	    
	    return $this;
	}
	
	/**
	 * get Request
	 * @return Request
	 */
	public function getRequest()
	{
	    $name = 'web.request';
	    if (!Ioc::has($name)) {
	        Ioc::setShared($name, function(){
	            return Request::createFromGlobals($_SERVER);
	        });
	    }
	    
	    return Ioc::get($name);
	}
	
	/**
	 * set Request
	 * @param  Request $request
	 * @return void
	 */
	public function setRequest($request)
	{
	    $name = 'web.request';
	    Ioc::setShared($name, $request);
	}
	
	/**
	 * get response
	 * @return Response
	 */
	public function getResponse()
	{
	    $name = 'web.response';
	    if (!Ioc::has($name)) {
	        Ioc::setShared($name, function () {
	            //$headers = new Headers(['Content-Type' => 'text/html; charset=UTF-8']);
	            $response = new Response(200);
	            return $response->withProtocolVersion(Config::getCommon('http.version'));
	        });
	    }
	    
	    return Ioc::get($name);
	}
	
	/**
	 * clear all modules view cache
	 * 
	 * @return boolean
	 */
	public function clearViewCache()
	{
	    $modules = $this->getModules();
	    if ($modules) foreach ($modules as $module) {
	        if ($instance = $this->getModule($module)) {
	            $instance->getView()->clearCache();
	        }
	    }
	    return true;
	}
}


