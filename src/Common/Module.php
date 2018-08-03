<?php
namespace Wslim\Common;

use InvalidArgumentException;
use Wslim\Util\UriHelper;
use Wslim\Util\StringHelper;
use Wslim\Util\ArrayHelper;
use Wslim\View\View;
use Wslim\Ioc;
use Wslim\Util\DataHelper;

/**
 * Module like as a sub app
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class Module extends Component
{
	/**
	 * base real path
	 * @access public
	 * @var string
	 */
	protected $basePath;
	
	/**
	 * module must set name for not root 
	 * @var string
	 */
	protected $name;
	
	/**
	 * alias
	 * @var string
	 */
	public $alias;
	
	/**
	 * namespace
	 * @var string
	 */
	protected $namespace;
	
	/**
	 * base url
	 * @var string
	 */
	protected $baseUrl;
	
	/**
	 * sub modules
	 * @var array
	 */
	protected $_modules;
	
	/**
	 * sub modules aliases
	 * @var array
	 */
	protected $_moduleAliases;
	
	/**
	 * construct, init params [basePath=>, name=>, env=>'dev', ...]
	 * 
	 * @param  array|string  $config must contain basePath, if string it is basePath
	 * 
	 * @throws InvalidConfigException
	 */
	public function __construct($config)
	{
	    parent::__construct();
	    
	    // 1. check and set basePath
		if (is_string($config)) {
		    $basePath = $config;
		    $config = [];
		} elseif (isset($config['basePath'])) {
		    $basePath = $config['basePath'];
		    unset($config['basePath']);
		} else {
		    throw new InvalidConfigException('module __construct() need a basePath key array');
		}
		
		$basePath = str_replace('\\', '/', $basePath);
		$rootPath = Config::getRootPath();
		if (strpos($basePath, $rootPath) === false) {
		    $basePath = $rootPath . $basePath;
		}
		
		if (!file_exists($basePath)) {
		    throw new InvalidConfigException('module basePath is not exist:' . $basePath);
		}
		
		$this->basePath = rtrim($basePath, '/\\') . '/';
		
		// 2. set default components, only do once
		if ($this->defaultComponents()) {
		    foreach ($this->defaultComponents() as $id => $component) {
		        if (!$this->has($id)) {
		            $this->set($id, $component);
		        }
		    }
		}
		
		// 3. set default modules
		if ($this->defaultModules()) {
		    $this->setModules($this->defaultModules());
		}
		
		// 4. before init: load default and common config
		$this->beforeInit();
		
		// 5. module config, first load, use config to configure class need after set autoload.
		Config::load('config', null, $this->basePath);
		if ($this->isApp()) {
		    $fconfig = Config::get('app', null, false);
		    $fconfig && $config = ArrayHelper::merge($fconfig, $config);
		} else {
		    $fconfig = Config::get('app', basename($this->basePath), false);
		    $fconfig && $config = ArrayHelper::merge($fconfig, $config);
		}
		
		/**
		 * 6. module autoload
		 * use composer autoload
		 * psr-0（命名空间与目录路径basename同）psr-4（命名空间不含目录路径的basename）
		 */
		$loader = Ioc::loader();
		$namespace = isset($config['namespace']) && $config['namespace'] ? $config['namespace'] : $this->getNamespace();
		$loader->setPsr4(ltrim($namespace, '\\') . '\\', $this->basePath. 'src');
		// 只调用一次，如果 $loader 由入口页面设置为composer返回的则已经调用过了，可不必再调用
		//$loader->register();
		//print_r(spl_autoload_functions());exit;
		
		// 7. module config use to self
		$config && $this->configure($config);
		
		// 8. extend class init
		$this->init();
		
		//Debugger::traceMemory($this->getLongName());
	}
	
	/**
	 * default sub modules
	 * @return array
	 */
	protected function defaultModules()
	{
	    return [];
	}
	
	/**
	 * init config
	 * @return void
	 */
	protected function beforeInit()
	{
	    // TODO
	}
	
	/**
	 * extend class init, called after initCommon() and current config
	 * @return void
	 */
	protected function init()
	{
	    // TODO
	}
	
	/**
	 * module is app 
	 * @return boolean
	 */
	protected function isApp()
	{
	    return $this instanceof App;
	}
	
	/**
	 * get module name
	 * @return string
	 */
	public function getName()
	{
	    if (!isset($this->name)) {
	        $this->name = $this->isApp() ? '' : basename($this->basePath);
	    }
	    return $this->name;
	}
	
	/**
	 * set name
	 * @param  string $name
	 * @return static
	 */
	public function setName($name)
	{
	    $this->name = $name;
	    return $this;
	}
	
	/**
	 * get long name
	 * @return string
	 */
	public function getLongName()
	{
	    return trim(str_replace(Config::getRootPath(), '', $this->basePath), '\\/');
	}
	
	/**
	 * get basePath, end with '/'
	 * @return string
	 */
	public function getBasePath()
	{
	    return rtrim($this->basePath, '\//') . '/';
	}
	
	/**
	 * set basePath
	 * @param  string $basePath
	 * @return static
	 */
	public function setBasePath($basePath)
	{
	    $this->basePath = $basePath;
	    return $this;
	}
	
	/**
	 * get namespace
	 * @return string
	 */
	public function getNamespace()
	{
	    if (!isset($this->namespace)) {
	        if ($this->isApp()) {
	            $this->namespace = Config::get('app.namespace') ?: '\\' . ucfirst(basename($this->basePath));
	        } else {
	            $name = str_replace(Ioc::app()->basePath, '', $this->basePath);
	            $name = StringHelper::toClassName($name);
	            $this->namespace = Ioc::app()->getNamespace() . '\\' . $name;
	        }
	    }
	    
	    return $this->namespace;
	}
	
	/**
	 * set namespace
	 * @param  string $namespace
	 * @return static
	 */
	public function setNamespace($namespace)
	{
	    $this->namespace = rtrim($namespace, '\\');
	    
	    // set autoload
	    if ($this->namespace) {
	        Ioc::loader()->setPsr4($this->namespace, $this->basePath . 'src');
	    }
	    
	    return $this;
	}
	
	/**
	 * get baseUrl
	 * @return string
	 */
	public function getBaseUrl($withScript=false)
	{
	    $url = trim($this->baseUrl, '/'); 
	    if (!$url) {
	        if ($this->isApp()) {
	            if (!($url = Config::getRootUrl())) {
	                $url = UriHelper::getBaseUrl();
	            }
	        } else {
	            $url = Ioc::app()->getBaseUrl($withScript);
	            if ($sdomain = $this->getOption('second_domain')) {
	                $url = UriHelper::buildSecondDomainUrl($sdomain, $url);
	            } else {
	                $url .= $this->getName() ? '/' . trim($this->getName(), '/') : '';
	            }
	        }
	        
	        $this->baseUrl = $url;
	    } elseif (strpos($url, 'http') !== 0) {
	        $url = trim($url, '/');
	        $url = UriHelper::getBaseUrl() . ($url ? '/'.$url : '');
	        $this->baseUrl = $url;
	    }
	    
	    if ($withScript && (intval(Config::getCommon('router.url_mode')) === 0)) {
	        $scriptName = UriHelper::getScriptBasename();
	        if (strpos($url, $scriptName) === false) {
	            $url = rtrim($url, '/') . '/' . $scriptName;
	        }
	    }
	    return $url;
	}
	
	/**
	 * set baseUrl
	 * @param  string $baseUrl
	 * @return static
	 */
	public function setBaseUrl($baseUrl)
	{
	    $this->baseUrl = $baseUrl;
	    return $this;
	}
	
	/**
	 * get sub modules, if param is true then return Module object array
	 * @return Module[]
	 */
	public function getModules($inited=false)
	{
	    if ($inited) {
	        $instances = [];
	        if ($this->_modules) foreach ($this->_modules as $v) {
	            $instances[] = $this->getModule($v);
	        }
	        return $instances;
	    }
	    
		return $this->_modules;
	}
	
	/**
	 * set sub modules, set definitions or objects
	 * 如果配置设置了 modules，app::construct()会自动触发此调用
	 * 
	 * @param  array $modules
	 * @return static
	 */
	public function setModules($modules)
	{
		foreach ($modules as $name => $module) {
		    $this->setModule($name, $module);
		}
		
		return $this;
	}
	
	/**
	 * parse alias, return real name
	 * @param  string $alias
	 * @return string
	 */
	protected function parseModuleAlias($alias)
	{
	    return isset($this->_moduleAliases[$alias]) ? $this->_moduleAliases[$alias] : $alias;
	}
	
	/**
	 * has named sub module
	 * @param  string $name
	 * @return boolean
	 */
	public function hasModule($name)
	{
	    $name = $this->parseModuleAlias($name);
	    
	    return isset($this->_modules) && in_array($name, $this->_modules);
	}
	
	/**
	 * get sub module instance
	 * @param  string $name
	 * @return Module|null
	 */
	public function getModule($name)
	{
	    $name = $this->parseModuleAlias($name);
	    $uid = 'module.' . $name;
	    
	    if ($this->hasModule($name)) {
	        
		    /* @var $module Module */
	        return $this->get($uid);
		} else {
		    $basePath = rtrim($this->basePath, '\\/' ) . '/' . $name;
		    if (file_exists($basePath)) {
		        
		        $this->setModule($name, $basePath);
		        
		        return $this->get($uid);;
		    }
		}
		
		return null;
	}
	
	/**
	 * set sub module
	 * 
	 * @param  mixed  $name   Module or string or array definition
	 * @param  mixed  $module Module or string or array definition
	 * @return static
	 */
	public function setModule($name, $module=null)
	{
	    if (!$module) {
	        $module = $name;
	    }
	    
	    if ($module instanceof Module) {
	        $name  = $module->getName();
	        $alias = $module->alias;
	    } else {
	        if (is_string($module)) {
	            $module = ['basePath' => $module];
	        } elseif (is_array($module)) {
	            if (!isset($module['basePath'])) {
	                $module['basePath'] = $name;
	            }
	        } else {
	            throw new InvalidArgumentException('modules config must be array.');
	        }
	        
	        if (strpos($module['basePath'], $this->basePath) === false) {
	            $module['basePath'] = rtrim($this->basePath, '/\\') . '/' . trim($module['basePath'], '/\\') . '/';
	        }
	        $module['class'] = '\\Wslim\\Common\\Module';
	        
	        if (is_numeric($name)) {
	            $name = basename($module['basePath']);
	        }
	        $name = trim($name, '/');
	        $module['name'] = $name;
	        $alias = isset($module['alias']) ? $module['alias'] : null;
	    }
	    
		if (!isset($this->_modules)) {
		    $this->_modules = [];
		}
		
		if ($alias) {
		    $this->_moduleAliases[$alias] = $name;
		}
		
		if (!in_array($name, $this->_modules)) {
		    array_push($this->_modules, $name);
		    $uid = 'module.' . $name;
            $this->set($uid, $module);
		}
		
		return $this;
	}
	
	/**
	 * format controller name
	 * @param  string $name
	 * @return string
	 */
	protected function formatControllerName($name)
	{
	    $name = str_replace('..', '.', $name);
	    $name = str_replace('./', '', $name);
	    return DataHelper::formatCode($name);
	}
	
	/**
	 * get controller instance, return null if not found.
	 * notice: this method do not check action if exists, for url '/a/b/c' if 'A/B' Controller is exists then return it.
	 * 
	 * @param  string $module
	 * @param  string $handler
	 * @return ControllerInterface $object
	 */
	public function getController($module, $handler=null, $type='Controller')
	{
	    $module    = isset($module) ? trim($module, '/') : '';
	    $handler   = isset($handler) ? trim($handler, '/') : '';
	    
	    if ($module && strpos($module, '/')) {
	        if ($handler === 'index') {
	            $handler = '';
	        }
	        
            // think about performance, module limit / level.
            $moduleLevel = intval(Config::get('router.module_level')) ? : 1;
            if ($moduleLevel < 0) {
                $moduleLevel = 1;
            } elseif ($moduleLevel > 2) {
                $moduleLevel  = 2;
            }
            
            $parts = explode('/', $module, $moduleLevel + 1);
            
            if (count($parts) <= $moduleLevel) {
                if ($module && $moduleInstance = $this->getModule($module)) {
                    return $moduleInstance->getController(null, $handler, $type);
                }
            }
            
            $mhandler = array_pop($parts);
            $module   = implode('/', $parts);
            
            if ($mhandler === 'index') {
                $mhandler = '';
            }
            $handler = $handler ? $mhandler . '/' . $handler : $mhandler;
	        
	        Ioc::logger('route')->debug(sprintf('[%s][find-m]%s:%s', UriHelper::getRelativeUrl(), $module, $handler));
	        
	        if ($module && $moduleInstance = $this->getModule($module)) {
	            return $moduleInstance->getController(null, $handler, $type);
	        } else {
	            return static::getController($module, $handler, $type);
	        }
	    } elseif ($module && $moduleInstance = $this->getModule($module)) {
	        return $moduleInstance->getController(null, $handler, $type);
	    } else {
            // 如， /a/b 1) 先查找对应控制器 a/b， 2)不存在则查找 a/b/index， 3) 不存在当包含/时则查找 a并把b当作action，不含/时则找 index并把控制器名当作action
            $namespace  = $this->getNamespace();
            $handler = implode('/', [$module, $handler]);
			$handler = trim($handler, '/') ? : 'index'; 
			$className  = Ioc::findClass($handler, $type, $namespace); 
			$action = null; 
			
			Ioc::logger('route')->debug(sprintf('[%s][find-c]%s:%s', UriHelper::getRelativeUrl(), $this->getName(), $handler));
			
			if (!$className) {
			    if ($handler == 'index') {
			        return null;
			    }
			    
			    $indexClassName = Ioc::findClass($handler . '/index', $type, $namespace);
			    
			    Ioc::logger('route')->debug(sprintf('[%s][find-c]%s:%s', UriHelper::getRelativeUrl(), $this->getName(), $handler . '/index'));
			    
			    if ($indexClassName) {
					$handler    = $handler . '/index';
					$className  = $indexClassName;
				} else {
				    if (($pos = strrpos($handler, '/')) !== false) {
				        $action = substr($handler, $pos + 1);
				        $handler = substr($handler, 0, $pos); 
				        $className  = Ioc::findClass($handler, $type, $namespace);
				        
				        Ioc::logger('route')->debug(sprintf('[%s][find-c]%s:%s', UriHelper::getRelativeUrl(), $this->getName(), $handler));
				        
				        if (!$className) {
				            $handler .= '/index';
				            $className = Ioc::findClass($handler, $type, $namespace);
				            
				            Ioc::logger('route')->debug(sprintf('[%s][find-c]%s:%s', UriHelper::getRelativeUrl(), $this->getName(), $handler));
				            
				            if (!$className) {
				                return null;
				            }
				        }
				    } else {
				        $action  = $handler;
				        $handler = 'index';
				        $className  = Ioc::findClass($handler, $type, $namespace);
				        
				        Ioc::logger('route')->debug(sprintf('[%s][find-c]%s:%s', UriHelper::getRelativeUrl(), $this->getName(), $handler));
				        
				        if (!$className) {
				            return null;
				        }
				    }
				}
			}
			
			/** @var $object ControllerInterface */
			$object = new $className($handler);
			
			if (!$object instanceof ControllerInterface) {
			    if (Ioc::app() && Ioc::app()->isCli() && class_exists('\\Wslim\\Console\\Controller')) {
			        $object = new \Wslim\Console\Controller($object);
			    } elseif (Ioc::app() && !Ioc::app()->isCli() && class_exists('\\Wslim\\Web\\Controller')) {
			        $object = new \Wslim\Web\Controller($object);
			    } else {
                    $object = new Controller($object);
			    }
			}
			$object->setModule($this);
			$object->setAction($action);
			
			return $object;
		}
	}
	
	/**
	 * get view instance
	 * 
	 * @return \Wslim\View\View
	 */
	public function getView()
	{
	    if (!$this->has('view')) {
	        // 先获取本module设置的，再覆盖公共设置
	        $config = Config::get('view', $this->getName());
	        
	        if (!isset($config['templatePath'])) {
	            $config['templatePath'] = $this->basePath . 'view';
	        }
	        if (!isset($config['compiledPath'])) {
	            $config['compiledPath'] = rtrim(Config::getStoragePath(), '/') . '/view/' . ($this->getName()?:'_app') . '/';
	            $config['compiledPath'] = str_replace('//', '/', $config['compiledPath']);
	        }
	        if (!isset($config['htmlPath'])) {
	            $config['htmlPath'] = rtrim(Config::getCommon('view.htmlPath'), '/') . '/' . $this->getName() . '/';
	            $config['htmlPath'] = str_replace('//', '/', $config['htmlPath']);
	        }
	        if (!isset($config['theme'])) {
	            $config['theme'] = Config::getCommon('view.theme');
	        }
	        
	        $object = new View($config);
	        $this->set('view', $object);
	    }
	    return $this->get('view');
	}
	
	/**
	 * is valid theme
	 * @param  string $theme
	 * @return boolean
	 */
	public function isValidTheme($theme)
	{
	    $path = str_replace('//', '/', $this->basePath . 'view/' . $theme);
	    return file_exists($path) ? true: false;
	}
	
}
