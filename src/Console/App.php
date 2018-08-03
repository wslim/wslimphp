<?php
namespace Wslim\Console;

use Wslim\Common\App as BaseApp;
use Wslim\Ioc;
use Wslim\Common\DefaultConfig;
use Wslim\Common\Config;
use Wslim\Util\StringHelper;
use Wslim\Common\RequestInterface;
use Wslim\Common\ResponseInterface;

/**
 * Console App
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class App extends BaseApp
{
    /**
     * overwrite, is cli
     * @var boolean
     */
    protected $isCli            = true;
    
    /**
     * overwrite, preinit modules
     */ 
    protected $_isInitModules   = true;
    
    /** @var Controller[] */
    private $controllers        = [];
    
    /**
     * default controller
     * @var string
     */
    private $defaultController  = 'list';
    
    /**
     * need helps, when controller is not help it is true
     * @var string
     */
    private $needHelps          = false;
    private $catchExceptions    = true;
    private $autoExit           = true;
    private $definition;
    
    /**
     * default controllers
     * @return array
     */
    protected function defaultControllers()
    {
        return [
            "Wslim\\Console\\Controller\\HelpController",
            "Wslim\\Console\\Controller\\ListController",
            "Wslim\\Console\\Controller\\ClearController",
            "Wslim\\Console\\Controller\\BuildController",
            "Wslim\\Console\\Controller\\Build\\ControllerController",
            "Wslim\\Console\\Controller\\Build\\ModelController",
            "Wslim\\Console\\Controller\\Build\\ViewController",
        ];
    }
    
    /**
     * 获取默认输入定义
     * @return Definition
     */
    protected function getDefaultDefinition()
    {
        return new Definition([
            new Argument('controller', Argument::REQUIRED, 'The controller to execute'),
            new Option('--help', '-h', Option::VALUE_NONE, 'Display this help message'),
            new Option('--version', '-V', Option::VALUE_NONE, 'Display this console version'),
            new Option('--quiet', '-q', Option::VALUE_NONE, 'Do not output any message'),
            new Option('--verbose', '-v|vv|vvv', Option::VALUE_NONE, 'Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug'),
            new Option('--ansi', '', Option::VALUE_NONE, 'Force ANSI output'),
            new Option('--no-ansi', '', Option::VALUE_NONE, 'Disable ANSI output'),
            new Option('--no-interaction', '-n', Option::VALUE_NONE, 'Do not ask any interactive question'),
        ]);
    }
    
    /**
	 * {@inheritDoc}
	 * @see \Wslim\Common\Module::beforeInit()
	 */
	protected function beforeInit()
    {
        parent::beforeInit();
        
        $this->isCli = true;
        
        // load default config
        Config::setDefault(DefaultConfig::console());
        
        // load default definition
        $this->definition     = $this->getDefaultDefinition();
        
        // load default available controller, depend naemspace
        $this->setControllers($this->defaultControllers());
        
    }
    
    /**
     * {@inheritDoc}
     * @see \Wslim\Common\App::init()
     */
    protected function init()
    {
        parent::init();
        
    }
    
	/**
	 * [overwrite]app run
	 * @param  boolean $silent
	 * @return int
	 */
	public function run($silent = false)
	{
	    $request  = $this->getRequest();
	    $response = $this->getResponse();
	    
	    $this->configureIO($request, $response);
	    
	    try {
	        if (true === $request->hasParameterOption(['--version', '-V'])) {
	            $response->writeln($this->getVersion());
	            $exitCode = 0;
	        } else {
	            // get controller name
	            $name = $request->getFirstArgument();
	            
	            if (true === $request->hasParameterOption(['--help', '-h'])) {
	                if (!$name) {
	                    $name  = 'help';
	                    $request = $request->withArguments(['help']);
	                } else {
	                    $this->needHelps = true;
	                }
	            }
	            
	            if (!$name) {
	                $name  = $this->defaultController;
	                $request = $request->withArguments([$this->defaultController]);
	            }
	            
	            $controller = $this->findController($name);
	            
	            $exitCode = $controller->handle($request, $response);
	        }
	    } catch (\Exception $e) {
	        if (!$this->catchExceptions) {
	            throw $e;
	        }
	        
	        $response->renderException($e);
	        
	        $exitCode = $e->getCode();
	        if (is_numeric($exitCode)) {
	            $exitCode = (int) $exitCode;
	            if (0 === $exitCode) {
	                $exitCode = 1;
	            }
	        } else {
	            $exitCode = 1;
	        }
	    }
	    
	    if ($this->autoExit) {
	        if ($exitCode > 255) {
	            $exitCode = 255;
	        }
	        
	        exit($exitCode);
	    }
	    
	    return $exitCode;
	}
	
	public function route(RequestInterface $request)
	{
	    
	}
	
	public function send(ResponseInterface $response)
	{
	    
	}
	
	/**
	 * 设置输入参数定义
	 * @param Definition $definition
	 */
	public function setDefinition(Definition $definition)
	{
	    $this->definition = $definition;
	}
	
	/**
	 * 获取输入参数定义
	 * @return Definition The Definition instance
	 */
	public function getDefinition()
	{
	    return $this->definition;
	}
	
	/**
	 * get version
	 * @return string
	 */
	public function getVersion()
	{
	    return '<info>version 3.1</info>';
	}
	
	/**
	 * Gets the help message.
	 * @return string A help message.
	 */
	public function getHelp()
	{
	    return '<info>Console Tool</info>';
	}
	
	/**
	 * 是否捕获异常
	 * @param bool $boolean
	 * @api
	 */
	public function setCatchExceptions($boolean)
	{
	    $this->catchExceptions = (bool) $boolean;
	}
	
	/**
	 * 是否自动退出
	 * @param bool $boolean
	 * @api
	 */
	public function setAutoExit($boolean)
	{
	    $this->autoExit = (bool) $boolean;
	}
	
	/**
	 * set default controller
	 * @param string $controllerName The Controller name
	 */
	public function setDefaultController($controllerName)
	{
	    $this->defaultController = $controllerName;
	}
	
	/**
	 * get all controllers
	 * @param  string $namespace 命名空间
	 * @return Controller[]
	 */
	public function getControllers($namespace=null)
	{
	    if (null === $namespace) {
	        return $this->controllers;
	    }
	    
	    $controllers = [];
	    foreach ($this->controllers as $name => $controller) {
	        if ($this->extractNamespace($name, substr_count($namespace, ':') + 1) === $namespace) {
	            $controllers[$name] = $controller;
	        }
	    }
	    
	    return $controllers;
	}
	
	/**
	 * set and load controllers
	 * @param Controller[] $controllers
	 */
	public function setControllers(array $controllers)
	{
	    foreach ($controllers as $name => $controller) {
	        $this->setController($name, $controller);
	    }
	}
	
	/**
	 * setController
	 * @param  Controller $controller
	 * @return Controller
	 */
	public function setController($name, $controller=null)
	{
	    if ($name instanceof Controller) {
	        $controller = $name;
	        $sname = $controller->getName();
	    } elseif ($controller instanceof Controller) {
	        $sname = $name && !is_numeric($name) ? $name : $controller->getName();
	    } else {
	        if ($controller) {
	            $class_name = Ioc::findClass($controller, 'controller');
	        } else {
	            $class_name = Ioc::findClass($name, 'controller');
	        }
	        
	        if ($class_name && is_subclass_of($class_name, "\\Wslim\\Console\\Controller")) {
	            $sname = is_numeric($name) ? null : $name;
	            $controller =  new $class_name($sname);
	            if (!$sname) {
	                $sname = $controller->getName();
	            }
	        } else {
	            throw new \LogicException(sprintf('Controller class "%s" is not exists.', $controller));
	        }
	    }
	    
	    if (!$controller->isEnabled()) {
	        return;
	    }
	    
	    if (null === $controller->getDefinition()) {
	        throw new \LogicException(sprintf('Controller class "%s" is not correctly initialized. You probably forgot to call the parent constructor.', get_class($controller)));
	    }
	    
	    $this->controllers[$sname] = $controller;
	    
	    foreach ($controller->getAliases() as $alias) {
	        $this->controllers[$alias] = $controller;
	    }
	    
	    return $controller;
	}
	
	/**
	 * 获取指令
	 * @param  string $name 指令名称
	 * @param  mixed  $controller 
	 * @param  string $type default 'controller'
	 * @return Controller
	 * @throws \InvalidArgumentException
	 */
	public function getController($module, $controller=null, $type="controller")
	{
	    if (isset($this->controllers[$module])) {
	        $controller = $this->controllers[$module];
	    } else {
	        $controller = parent::getController($module, $controller, $type);
	        if (!$controller) {
	            throw new \InvalidArgumentException(sprintf('The controller "%s" does not exist.', $module));
	        }
	    }
	    
	    if ($this->needHelps) {
	        $this->needHelps = false;
	        
	        /** @var \Wslim\Console\Controller\HelpController $helpController */
	        $helpController = $this->getController('help');
	        $helpController->setController($controller);
	        
	        return $helpController;
	    }
	    
	    return $controller;
	}
	
	/**
	 * 某个指令是否存在
	 * @param string $name 指令名称
	 * @return bool
	 */
	public function hasController($name)
	{
	    return isset($this->controllers[$name]);
	}
	
	/**
	 * 查找指令
	 * @param string $name 名称或者别名
	 * @return Controller
	 * @throws \InvalidArgumentException
	 */
	public function findController($name)
	{
	    $allControllers = array_keys($this->controllers);
	    
	    $expr        = preg_replace_callback('{([^:]+|)}', function ($matches) {
	        return preg_quote($matches[1]) . '[^:]*';
	    }, $name);
	    
	    $controllers = preg_grep('{^' . $expr . '}', $allControllers);
	    
        if (empty($controllers) || count(preg_grep('{^' . $expr . '$}', $controllers)) < 1) {
            if (false !== $pos = strrpos($name, ':')) {
                $this->findControllerNamespace(substr($name, 0, $pos));
            }
            
            $message = sprintf('Controller "%s" is not defined.', $name);
            
            if ($alternatives = $this->findAlternatives($name, $allControllers)) {
                if (1 == count($alternatives)) {
                    $message .= "\n\nDid you mean this?\n    ";
                } else {
                    $message .= "\n\nDid you mean one of these?\n    ";
                }
                $message .= implode("\n    ", $alternatives);
            }
            
            throw new \InvalidArgumentException($message);
        }
        
        if (count($controllers) > 1) {
            $controllerList = $this->controllers;
            $controllers    = array_filter($controllers, function ($nameOrAlias) use ($controllerList, $controllers) {
                $controllerName = $controllerList[$nameOrAlias]->getName();
                
                return $controllerName === $nameOrAlias || !in_array($controllerName, $controllers);
            });
        }
        
        $exact = in_array($name, $controllers, true);
        if (count($controllers) > 1 && !$exact) {
            $suggestions = $this->getAbbreviationSuggestions(array_values($controllers));
            
            throw new \InvalidArgumentException(sprintf('Controller "%s" is ambiguous (%s).', $name, $suggestions));
        }
        
        return $this->getController($exact ? $name : reset($controllers));
	}
	
	/**
	 * 查找可用的controller名称或缩写
	 * @param string $namespace
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public function findControllerNamespace($namespace)
	{
	    $prefixs = $this->getControllerNamespaces();
	    
	    $expr          = preg_replace_callback('{([^:]+|)}', function ($matches) {
	        return preg_quote($matches[1]) . '[^:]*';
	    }, $namespace);
	    
	    $find_names = preg_grep('{^' . $expr . '}', $prefixs);
        
	    if (empty($find_names)) {
	        $message = sprintf('There are no controllers defined in the "%s" namespace.', $namespace);
            
	        if ($alternatives = $this->findAlternatives($namespace, $prefixs)) {
                if (1 == count($alternatives)) {
                    $message .= "\n\nDid you mean this?\n    ";
                } else {
                    $message .= "\n\nDid you mean one of these?\n    ";
                }
                
                $message .= implode("\n    ", $alternatives);
            }
            
            throw new \InvalidArgumentException($message);
        }
        
        $exact = in_array($namespace, $find_names, true);
        if (count($find_names) > 1 && !$exact) {
            throw new \InvalidArgumentException(sprintf('The namespace "%s" is ambiguous (%s).', $namespace, $this->getAbbreviationSuggestions(array_values($find_names))));
        }
        
        return $exact ? $namespace : reset($find_names);
	}
	
	
	/**
	 * getControllerNamespaces,注意不是完整的命名空间
	 * 
	 * @return array
	 */
	public function getControllerNamespaces()
	{
	    $names = [];
	    foreach ($this->controllers as $controller) {
	        $names = array_merge($names, StringHelper::explodeAllPrefixs($controller->getName()));
	        
	        foreach ($controller->getAliases() as $alias) {
	            $names = array_merge($names, StringHelper::explodeAllPrefixs($alias));
	        }
	    }
	    
	    return array_values(array_unique(array_filter($names)));
	}
	
	/**
	 * 获取可能的指令名
	 * @param array $names
	 * @return array
	 */
	public static function getAbbreviations($names)
	{
	    $abbrevs = [];
	    foreach ($names as $name) {
	        for ($len = strlen($name); $len > 0; --$len) {
	            $abbrev             = substr($name, 0, $len);
	            $abbrevs[$abbrev][] = $name;
	        }
	    }
	    
	    return $abbrevs;
	}
	
	/**
	 * 配置基于用户的参数和选项的输入和输出实例。
	 * @param Request  $request  输入实例
	 * @param Response $response 输出实例
	 */
	protected function configureIO(Request $request, Response $response)
	{
	    if (true === $request->hasParameterOption(['--ansi'])) {
	        $response->setDecorated(true);
	    } elseif (true === $request->hasParameterOption(['--no-ansi'])) {
	        $response->setDecorated(false);
	    }
	    
	    if (true === $request->hasParameterOption(['--no-interaction', '-n'])) {
	        $request->setInteractive(false);
	    }
	    
	    if (true === $request->hasParameterOption(['--quiet', '-q'])) {
	        $response->setVerbosity(Response::VERBOSITY_QUIET);
	    } else {
	        if ($request->hasParameterOption('-vvv') || $request->hasParameterOption('--verbose=3') || $request->getParameterOption('--verbose') === 3) {
	            $response->setVerbosity(Response::VERBOSITY_DEBUG);
	        } elseif ($request->hasParameterOption('-vv') || $request->hasParameterOption('--verbose=2') || $request->getParameterOption('--verbose') === 2) {
	            $response->setVerbosity(Response::VERBOSITY_VERY_VERBOSE);
	        } elseif ($request->hasParameterOption('-v') || $request->hasParameterOption('--verbose=1') || $request->hasParameterOption('--verbose') || $request->getParameterOption('--verbose')) {
	            $response->setVerbosity(Response::VERBOSITY_VERBOSE);
	        }
	    }
	}

	/**
	 * 获取可能的建议
	 * @param array $abbrevs
	 * @return string
	 */
	private function getAbbreviationSuggestions($abbrevs)
	{
	    return sprintf('%s, %s%s', $abbrevs[0], $abbrevs[1], count($abbrevs) > 2 ? sprintf(' and %d more', count($abbrevs) - 2) : '');
	}
	
	/**
	 * 返回命名空间部分
	 * @param string $name  指令
	 * @param string $limit 部分的命名空间的最大数量
	 * @return string
	 */
	public function extractNamespace($name, $limit = null)
	{
	    $parts = explode(':', $name);
	    array_pop($parts);
	    
	    return implode(':', null === $limit ? $parts : array_slice($parts, 0, $limit));
	}
	
	/**
	 * 查找可替代的建议
	 * @param string             $name
	 * @param array|\Traversable $collection
	 * @return array
	 */
	private function findAlternatives($name, $collection)
	{
	    $threshold    = 1e3;
	    $alternatives = [];
	    
	    $collectionParts = [];
	    foreach ($collection as $item) {
	        $collectionParts[$item] = explode(':', $item);
	    }
	    
	    foreach (explode(':', $name) as $i => $subname) {
	        foreach ($collectionParts as $collectionName => $parts) {
	            $exists = isset($alternatives[$collectionName]);
	            if (!isset($parts[$i]) && $exists) {
	                $alternatives[$collectionName] += $threshold;
	                continue;
	            } elseif (!isset($parts[$i])) {
	                continue;
	            }
	            
	            $lev = levenshtein($subname, $parts[$i]);
	            if ($lev <= strlen($subname) / 3 || '' !== $subname && false !== strpos($parts[$i], $subname)) {
	                $alternatives[$collectionName] = $exists ? $alternatives[$collectionName] + $lev : $lev;
	            } elseif ($exists) {
	                $alternatives[$collectionName] += $threshold;
	            }
	        }
	    }
	    
	    foreach ($collection as $item) {
	        $lev = levenshtein($name, $item);
	        if ($lev <= strlen($name) / 3 || false !== strpos($item, $name)) {
	            $alternatives[$item] = isset($alternatives[$item]) ? $alternatives[$item] - $lev : $lev;
	        }
	    }
	    
	    $alternatives = array_filter($alternatives, function ($lev) use ($threshold) {
	        return $lev < 2 * $threshold;
	    });
	        asort($alternatives);
	        
	        return array_keys($alternatives);
	}
	
	/**
	 * get request
	 * @return Request
	 */
	public function getRequest()
	{
	    $id = 'request';
	    if (!$this->has($id)) {
	        $this->set($id, function () {
	           return Request::createFromGlobals();
	        });
	    }
	    return $this->get($id);
	}
	
	/**
	 * get response
	 * @return Response
	 */
	public function getResponse()
	{
	    $id = 'response';
	    if (!$this->has($id)) {
	        $this->set($id, function () {
	            return new Response();
	        });
	    }
	    return $this->get($id);
	}
	
}