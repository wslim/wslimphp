<?php
namespace Wslim\Common;

use Wslim\Ioc;

/**
 * app
 * 
 * lifecycle enents: 
 *      app::beforeRoute
 *      app::afterRoute
 *      app::beforeControll
 *      app::afterControll
 *      app::beforeSend
 *      app::afterSend
 * 
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
abstract class App extends Module
{
    /**
     * current module with matched route
     * @access protected
     * @var Module|string|array
     */
    protected $currentModule;
    
    /**
     * default module
     * @access protected
     * @var Module|string|array
     */
    protected $defaultModule;
    
    /**
     * is cli
     * @var boolean
     */
    protected $isCli=false;
    
	/**
	 * create app instance and run it
	 * @param array|string $config if string it is basePath
	 * @param boolean      $run    if run
	 * 
	 * @return static
	 */
    static public function launch($config, $run = true)
	{
		if (is_string($config)) {
		    $config = ['basePath' => $config];
		}
		
		$app = new static($config);
		
		if ($run) {
		    return $app->run();
		}
		
		return $app;
	}
	
	/**
	 * construct
	 */
	public function __construct($config=null)
	{
	    Ioc::setApp($this);
	    
	    if (!$config) {
	        $config = ['basePath' => ROOT_PATH . ($this->isCli ? 'cli' : 'app')];
	    }
	    
	    parent::__construct($config);
	}
	
	/**
	 * is cli
	 * @return boolean
	 */
	public function isCli()
	{
	    return (bool) $this->isCli;
	}

	/**
	 * {@inheritDoc}
	 * @see \Wslim\Common\Module::defaultComponents()
	 */
	protected function defaultComponents()
	{
	    return [
	        //'testClass'    => '\\Wslim\\Test\\TestClass',
	    ];
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Wslim\Common\Module::beforeInit()
	 */
	protected function beforeInit()
	{
	    // load/set default config
	    Config::setDefault(DefaultConfig::common());
	    
	    // load common config: /common/config/config.php
	    Config::load('config');
	    
	    // autoload common class: /common/src/...
	    $ns = Config::getCommon('commonNamespace', 'Common');
	    $ns = trim($ns, '\\') . '\\';
	    Ioc::loader()->setPsr4($ns, Config::getCommonPath() . 'src');
	    
	    // init namespaces: load or init
	    Ioc::initNamespace();
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Wslim\Common\App::init()
	 */
	protected function init()
	{
	    // language load lang
	    Ioc::language()->setLocale(Config::get('lang'));
	    Ioc::language()->load('common');
	    
	    // errorHandler register
	    Ioc::errorHandler()->register();
	    
	    if ($components = Config::get('components')) {
	        foreach ($components as $key => $def) {
	            Ioc::container()->setShared($key, $def);
	        }
	    }
	}
	
	/**
	 * run app
	 * @param  boolean $silent
	 * @return mixed   $response
	 */
	public function run($silent=false)
	{
	    $request   = $this->getRequest();
	    
	    $this->trigger('beforeRoute');
	    $controller = $this->route($request);
	    $this->trigger('afterRoute');
	    
	    // regain request, because routing() could be rewrite request.
	    $request   = $this->getRequest(); 
	    static::trigger('beforeControll');
	    $response = $controller->handle($request, $this->getResponse());
	    $this->trigger('afterControll');
	    
	    $response = $this->finalize($response);
	    
	    // send response
	    if (!$silent) {
	        $this->trigger('beforeSend');
	        $this->send($response);
	        $this->trigger('afterSend');
	    }
	    
	    return $response;
	}
	
	/**
	 * before controll::handle()
	 * @param  Event $event
	 * @return mixed
	 */
	protected function beforeControll($event)
	{
	    //echo __METHOD__;
	}
	
	/**
	 * after controll, after controll::handle()
	 * @param  Event $event
	 * @return mixed
	 */
	protected function afterControll($event)
	{
	    //echo __METHOD__;
	}
	
	/**
	 * route request and return a controller
	 * 
	 * @param  RequestInterface $request
	 * @return ControllerInterface
	 */
	abstract function route(RequestInterface $request);
	
	/**
	 * [overwrite] send response to client
	 * @param  ResponseInterface $response
	 * @return void
	 */
	function send(ResponseInterface $response)
	{
	    return $this->getResponse();
	}
	
	/**
	 * [overwrite] finalize response
	 * @param  mixed $output
	 * @return ResponseInterface
	 */
	public function finalize($output)
	{
	    return $this->getResponse();
	}
	
	/**
	 * get current module parsed from uri
	 * 
	 * @return \Wslim\Common\Module
	 */
	public function getCurrentModule()
	{
	    if ($this->currentModule && !($this->currentModule instanceof Module)) {
	       $this->currentModule = $this->getModule($this->currentModule);
	    }
	    
	    return $this->currentModule ? : $this;
	}
	
	/**
	 * set current module
	 * @param  mixed  $module string|array|Module
	 * @return static
	 */
	public function setCurrentModule($module)
	{
	    $this->setModule($module);
	    $this->currentModule = $module;
	    return $this;
	}
	
	/**
	 * get default module
	 *
	 * @return \Wslim\Common\Module
	 */
	public function getDefaultModule()
	{
	    if ($this->defaultModule && !($this->defaultModule instanceof Module)) {
	       $this->defaultModule = $this->getModule($this->defaultModule);
	    }
	    
	    return $this->defaultModule ? : $this;
	}
	
	/**
	 * set default module
	 * @param  mixed  $module string|array|Module
	 * @return static
	 */
	public function setDefaultModule($module)
	{
	    $this->setModule($module);
	    $this->defaultModule = $module;
	    return $this;
	}
	
	/**
	 * is default module
	 * @param  mixed   $module
	 * @return boolean
	 */
	public function isDefaultModule($module=null)
	{
	    if (!$module) {
	        return $this->getCurrentModule()->getName() == $this->getDefaultModule()->getName();
	    } elseif (is_string($module)) {
	        return $module == $this->getDefaultModule()->getName();
	    } elseif ($module instanceof Module) {
	        return $module->getName() == $this->getDefaultModule()->getName();
	    }
	    
	    return false;
	}
	
	/**
	 * get request
	 * @return RequestInterface
	 */
    abstract public function getRequest();
    
    /**
     * get response
     * @return ResponseInterface
     */
    abstract public function getResponse();
    
}

