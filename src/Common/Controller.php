<?php
namespace Wslim\Common;

use Wslim\Util\StringHelper;
use Wslim\Ioc;
use Wslim\Util\UriHelper;
use Wslim\Util\Paginator;

/**
 * Controller
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class Controller implements ControllerInterface
{
    /**
     * event aware
     */
	use EventAwareTrait; 
	
	/**
	 * default options
	 * @var array
	 */
	static protected $defaultOptions = [
	    'responseDataKey' => 'data',
	];
	
	/**
	 * @var \Wslim\Common\Module
	 */
	protected $module;
	
	/**
	 * @var string
	 */
	protected $name;
	
	/**
	 * action
	 * @var string
	 */
	protected $action;
	
	/**
	 * clientType
	 * @var string
	 */
	protected $clientType;
	
    /**
     * http request
     * @var RequestInterface
     */
    protected $request;
    
    /**
     * http response
     * @var ResponseInterface
     */
    protected $response;
    
    /**
     * response data, notice it is different of view data
     * @access private
     * @var \Wslim\Common\Collection
     */
    private $responseData;
    
    /**
     * store render content
     * @var string
     */
    protected $renderBuffer = null;
    
    /**
     * errorInfo
     * @var \Wslim\Common\ErrorInfo
     */
    protected $errorInfo;
    
    /**
     * real handler
     * @var callable
     */
    private $handler    = null;
    
    /**
     * handle stack
     * @var array
     */
    private $handleStack = null;
    
    /**
     * the stop handling status 停止处理的状态标志
     * @var boolean
     */
    private $stopped    = false;
    
    /**
     * [overwrite] set events
     * @return array ['eventName' => 'callable']
     */
    public function events()
    {
        return [
            'beforeHandle'  => 'beforeHandle',
            'afterHandle'   => 'afterHandle',
        ];
    }
    
    /**
     * construct.
     * don't rewrite, only when you sure call parent::__construct.
     * @param callable $handler
     */
    public function __construct($handler=null) 
    {
        $handler && $this->setHandler($handler);
        
        $this->responseData = new Collection();
        
        $this->options = array_merge(static::$defaultOptions, (array)$this->options);
    }
    
    /**
     * get current app
     * @return \Wslim\Common\App
     */
    final public function getApp()
    {
        return Ioc::app();
    }
    
    /**
     * get request method
     * @return string
     */
    final public function getRequestMethod()
    {
        $request || $request = static::getApp()->getRequest();
        return $request->getMethod();
    }
    
    /**
     * get real handler
     * @return \Wslim\Common\Controller|callable
     */
    final public function getHandler()
    {
        return $this->handler ? $this->handler : $this;
    }
    
    /**
     * set handler
     * @param  mixed $handler
     * @return static
     */
    final protected function setHandler($handler)
    {
        if (is_callable($handler, false)) {
            $this->handleStack = ['default' => $handler];
        } elseif (is_object($handler)) {
            $this->handler = $handler;
        } else {
            $this->name = $handler;
        }
    }
    
    /**
     * {@inheritDoc}
     * @see ControllerInterface::getModule()
     */
    final public function getModule()
    {
        return $this->module;
    }
    
    /**
     * {@inheritDoc}
     * @see ControllerInterface::setModule()
     */
    final public function setModule($module)
    {
    	$this->module = $module;
    }
    
    /**
     * get controller name of lowercase, like 'company/com_info'
     * 
     * @param  bool   $lowercase default false
     * @return string
     */
    final public function getName()
    {
        return StringHelper::toUnderscorePath($this->name);
    }
    
    /**
     * get controller name of camelcase, like 'Company/ComInfo'
     * 
     * @param  bool   $lowercase default false
     * @return string
     */
    final public function getCamelCaseName()
    {
        return str_replace('\\', '/', StringHelper::toClassName($this->name));
    }
    
    /**
     * {@inheritDoc}
     * @see ControllerInterface::setName()
     */
    public function setName($name)
    {
    	$this->name = $name;
    }
    
    /**
     * get action name, return underscore string, like 'add_article'
     * @return string
     */
    final public function getAction()
    {
        return StringHelper::toUnderscorePath($this->action);
    }
    
    /**
     * get action name, return little camecase string, like 'addArticle'
     * @return string
     */
    final public function getCamelCaseAction()
    {
        return StringHelper::toLittleCamelCase($this->action);
    }
    
    /**
     * {@inheritDoc}
     * @see ControllerInterface::setAction()
     */
    final public function setAction($action)
    {
        $this->action = $action;
    }
    
    /**
     * get long name of lowercase, module and controller and action /module/some_controller/some_action
     * lowercase is easy to build url
     * 
     * @return string
     */
    final public function getLongName()
    {
        $name = ($this->module ? $this->module->getName() . '/' : '') . $this->getName();
        return $this->action ? $name . '/' . $this->getAction() : $name;
    }
    
    final public function getClientType()
    {
        return $this->clientType;
    }
    
    /**
     * {@inheritDoc}
     * @see ControllerInterface::setClientType()
     */
    final public function setClientType($clientType)
    {
        $this->clientType = $clientType;
    }
    
    /**
     * set stop status, can be called in init(), beforeGet/Post(), action(), 
     * @return static
     */
    final public function stop()
    {
        $this->stopped = true;
        return $this;
    }
    
    /**
     * is stop handle
     * @return boolean
     */
    final public function isStopped()
    {
        return $this->stopped;
    }
    
    /**
     * get render buffer
     * @return string
     */
    final protected function getRenderBuffer()
    {
        return $this->renderBuffer;
    }
    
    /**
     * clear render buffer
     * @return void
     */
    final protected function clearRenderBuffer()
    {
        $this->renderBuffer = null;
    }
    
    /**
     * is access forbidden method, use to inherit class check method is can access
     * @param  string $method
     * @return boolean
     */
    protected function isForbiddenMethod($method)
    {
        return in_array($method, get_class_methods(get_class()));
    }
    
    /**
     * get handle stack
     * @param  RequestInterface $request
     * @return array
     */
    final public function getHandleStack($request=null)
    {
        if (!is_array($this->handleStack)) {
            $this->handleStack = [];
            
            $request || $request = static::getApp()->getRequest();
            
            $handler = $this->getHandler();
            $requestMethod = ucfirst(strtolower($request->getMethod()));
            $action = $this->getCamelCaseAction();
            
            // 1. 调用栈 handleStack
            if (!$this->handleStack) {
                if ($requestMethod === 'Options') {
                    array_push($this->handleStack, 'doOptions');
                } else {
                    // do<Get|Post>Action or <action>(only for GET), if not exist then notFound()
                    $actions = [];
                    
                    $actions[]  = 'do' . $requestMethod . ($action && $action!=='index' ? ucfirst($action) : '');
                    
                    if ($requestMethod === 'Get') {
                        if (!$action || $action == 'index') {
                            $actions[]  = 'index';
                        } else {
                            if (!$this->isForbiddenMethod($action)) {
                                $actions[] = $action;
                            }
                        }
                    }
                    
                    foreach ($actions as $method) {
                        if (method_exists($handler, $method)) {
                            array_push($this->handleStack, $method);
                            break;
                        }
                    }
                }
                
                if ($this->handleStack && in_array($requestMethod, ['Get', 'Post']) && $this->getHandler() instanceof \Wslim\Common\Controller) {
                    // before<RequestMethod>
                    $this->handleStack = array_merge(['init', 'before' . $requestMethod], $this->handleStack);
                }
            }
        }
        
        return $this->handleStack;
    }
    
    /**
     * convert data to array or scalar. notice: 非基础类型或数组类型进行转化,以支持json格式输出
     * @param  mixed $data
     * @return array
     */
    protected function formatResponseValue($data)
    {
        if ($data instanceof Paginator) {
            $key = $this->options['responseDataKey'] ? : 'data';
            return [
                'paginator' => $data->toArray(),
                $key => $data->getData(),
            ];
        } elseif ($data instanceof Collection) {
            return $data->all();
        }
        
        return $data;
    }
    
    /**
     * get or set response data, it is not different of view data, view data is use only to view. 
     * notice: 非基础类型或数组类型进行转化,以支持json格式输出
     * 
     * @param  mixed $key
     * @param  mixed $value
     * @return Collection|mixed
     */
    public function responseData($key=null, $value=null)
    {
        if (!$key) {    // get all responseData
            return $this->responseData;
        } elseif (is_null($value)) {
            if (is_scalar($key)) {  // get by key
                return isset($this->responseData[$key]) ? $this->responseData[$key] : null;
            }
            
            $data = static::formatResponseValue($key);
            if (is_array($data)) {
                $res = [];
                foreach ($data as $k=>$v) {
                    if (!is_scalar($v) && !is_array($v)) {
                        $vv = static::formatResponseValue($v);
                        if (is_numeric($k)) {
                            $res = array_merge($res, $vv);
                        } else {
                            $res[$k] = $vv;
                        }
                        unset($data[$k]);
                    } elseif (!is_numeric($k)) {
                        $res[$k] = $v;
                        unset($data[$k]);
                    }
                }
                if ($data) {
                    $res['data'] = $data;
                }
                $this->responseData->set($res);
            }
        } else {
            $this->responseData->set($key, static::formatResponseValue($value));
        }
        
        return $this;
    }
    
    /**
     * get errinfo, if no error return null
     * 
     * @return NULL|\Wslim\Common\ErrorInfo
     */
    public function getError()
    {
        return $this->errorInfo && $this->errorInfo['errcode'] ? $this->errorInfo : null;
    }
    
    /**
     * handle the request and return response, handleStack store the called methods.
     * [init, before<Get/Post>, ]
     * 
     * @param  RequestInterface  $request
     * @param  ResponseInterface $response
     * 
     * @return ResponseInterface
     */
    public function handle($request, $response)
    {
        // init options, don't place this in __construct, because defaultOptions could be get action name.
        if ($doptions = static::defaultOptions()) {
            $this->options = array_merge($this->options, $doptions);
        }
        
        // start ob, ob_flush() 由具体render()方法根据情况调用
        ob_start();
        
        $this->request  = $request;
        $this->response = $response;
        
        $this->getHandleStack($request);
        
        static::trigger('beforeHandle');
        
        if (!$this->handleStack) {
            return $this->response;
        }
        
        $handler = $this->getHandler();
        if (!$handler instanceof Controller) {
            $handler->request   = $request;
            $handler->response  = $response;
            $handler->module    = $this->module;
            $handler->handleStack = & $this->handleStack;
        }
        
        // call handler stack
        $output = null;
        
        foreach ($this->handleStack as $do) {
            if ($this->isStopped()) {
                if ($res = $this->getError()) {
                    $output = $this->error($res);
                }
                break;
            };
            
            $output = is_callable($do) ? call_user_func_array($do, [$request, $response]) : $handler->$do();
        }
        
        // output handle, must be run
        $this->_outputHandle($output);
        
        static::trigger('afterHandle');
        
        // Return the response
        return $this->response;
    }
    
    /**
     * [overwrite] extend class initialize, get or post will execute 
     * 
     * @return mixed
     */
    protected function init()
    {
        // do something
    }
    
    /**
     * final handle output
     * @param  mixed $output
     * @return ResponseInterface
     */
    protected function _outputHandle($output=null)
    {
        if ($output === null) {
            // do nothing
        } elseif ($output instanceof ResponseInterface) {
            $this->response = $output;
        } elseif ($output) {
            if (is_scalar($output)) {
                $this->response = $this->renderText($output);
            } elseif (is_array($output)) {
                $this->response = $this->renderJson($output);
            } elseif ($output instanceof Collection) {
                $this->response = $this->renderJson($output->all());
            }
        }
        
        if ($data = $this->getRenderBuffer()) {
            $this->response->write($data);
        }
        
        return $this->response;
    }
    
    /**
     * [overwrite] Automatically executed before the controller action. Can be used to set
     * class properties, do authorization checks, and execute other custom code.
     *
     * @param  Event $event
     * @return mixed
     */
    public function beforeHandle($event)
    {
        // Nothing by default
    }
    
    /**
     * [overwrite] Automatically executed after the controller action. Can be used to apply
     * transformation to the response, add extra output, and execute
     * other custom code.
     *
     * @param  Event $event
     * @return mixed
     */
    public function afterHandle($event)
    {
        // default
        //Debugger::traceMemory($this->getLongName() . ' controller handle end');
    }
    
    /**
     * before render, handle ob and response data.
     * @param  mixed $data
     * @return void
     */
    protected function beforeRender($data=null)
    {
        // 非调试模式下，清除缓存区以免影响输出?
        if (!Config::get('debug')) {
            //ob_end_clean();
        }
        $this->clearRenderBuffer();
        
        $data && $this->responseData($data);
        
    }
    
    /**
     * render string
     * @param  string $string
     * @return ResponseInterface
     */
    public function renderText($string)
    {
        $this->renderBuffer .= $string;
        return $this->response;
    }
    
    /**
     * render json string, 统一输出结果为 ['errcode'=>.., 'errmsg'=>.., 'somekey'=>..]
     * 
     * @param  array $data
     * @return ResponseInterface
     */
    public function renderJson($data=null)
    {
        $this->beforeRender($data);
        
        return $this->renderText(json_encode($this->responseData->all()));
    }
    
    /**
     * jump
     * 
     * @param  mixed $errcode
     * @param  mixed $errmsg
     * @param  array $data
     * @return ResponseInterface
     */
    protected function jump($errcode=null, $errmsg=null, $data=null)
    {
        $this->stop();
        
        $this->errorInfo  = ErrorInfo::instance($errcode, $errmsg, $data);
        
        return $this->renderJson($this->errorInfo);
    }
    
    /**
     * success
     *
     * @param  mixed $errmsg
     * @param  array $data
     * @return ResponseInterface
     */
    protected function success($errmsg=null, $data=null)
    {
        $this->errorInfo = ErrorInfo::success($errmsg, $data);
        
        if (!isset($this->errorInfo['errmsg'])) {
            $this->errorInfo['errmsg'] = '操作成功';
        }
        
        return $this->jump($this->errorInfo);
    }
    
    /**
     * error
     *
     * @param  mixed $errcode
     * @param  mixed $errmsg
     * @param  array $data
     * @return ResponseInterface
     */
    protected function error($errcode=-1, $errmsg=null, $data=null)
    {
        !isset($this->errorInfo['errcode']) && $this->errorInfo = ErrorInfo::error($errcode, $errmsg, $data);
        
        if (!isset($this->errorInfo['errmsg'])) {
            $this->errorInfo['errmsg'] = '操作失败';
        }
        
        return $this->jump($this->errorInfo);
    }
    
    /**
     * fail, alias of error()
     *
     * @param  mixed $errcode
     * @param  mixed $errmsg
     * @param  array $data
     * @return ResponseInterface
     */
    protected function fail($errcode=-1, $errmsg=null, $data=null)
    {
        return static::error($errcode, $errmsg, $data);
    }
    
    /**
     * [overwrite] default notFound handler
     * @return mixed
     */
    protected function notFound()
    {
        $errmsg = sprintf('[%s][%s]method not found: %s:%s. handlers: %s', $this->request->getMethod(), UriHelper::getCurrentUrl(), get_class($this), $this->getLongName(), implode('|', $this->getHandleStack()));
        Ioc::logger('router')->error($errmsg);
        
        $handler = Ioc::get('notFoundHandler');
        return $handler($this->request, $this->response);
    }
    
    /**
     * handle OPTIONS request
     * @return \Wslim\Web\Response
     */
    protected function doOptions()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header('Access-Control-Allow-Headers: Content-Type, Content-Length, Authorization, Accept, Origin, X-Requested-With');
    }
    
    /********************************************************
     * request convenient methods
     ********************************************************/
    /**
     * convenient method for get params
     * @param  string $key can be: name|get:name|post:name|get:*|post:*|*
     * @param  mixed  $default
     * @return mixed
     */
    final protected function input($key=null, $default=null)
    {
        return $this->request->input($key, $default);
    }
    
    /**
     * get request int params
     * @param  string $key
     * @param  int    $default
     * @return int
     */
    final protected function inputInt($key, $default=null)
    {
        return intval($this->input($key, $default));
    }
    
}