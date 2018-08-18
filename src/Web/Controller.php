<?php
namespace Wslim\Web;

use Slim\Http\Uri;
use Wslim\Common\Collection;
use Wslim\Common\ErrorInfo;
use Wslim\Common\ResponseInterface;
use Wslim\Common\DataFormatter\XmlFormatter;
use Wslim\Util\UriHelper;
use Wslim\Util\HttpHelper;
use Wslim\Ioc;
use Wslim\Common\Config;
use Wslim\Security\FormToken;

/**
 * 
 * Web Controller
 *
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class Controller extends \Wslim\Common\Controller
{
    /**
     * http request
     * @var Request
     */
    protected $request;
    
    /**
     * http response
     * @var Response
     */
    protected $response;
    
    /**
     * [overwrite] token verify additional data, 比如页面生成token时使用了['id'=>2]，则post时会和这里的 data 进行验证
     * @var mixed
     */
    protected $csrfTokenData = null;
    
    /**
     * default options
     * @var array
     */
    static protected $defaultOptions = [
        'responseDataKey'   => 'data',
        'csrfTokenEnabled'  => 1,
        'csrfTokenName'     => '_form_token',
        // auto reset, default true, 对于允许多个提交的设为0然后手动调用 resetCsrfToken() 在适当时机重置 
        'csrfTokenAutoReset'=> 1,
        // 提交的间隔时间，可以重写
        'postInterval'      => 0.5,
    ];
    
    /**
     * [overwrite] 建议在 init() 里第一句执行
     * @return \Wslim\Session\Session
     */
    protected function getSession()
    {
        $session = Ioc::session();
        $session->start();
        return $session;
    }
    
    /**
     * [overwrite] execute before get
     * @return  mixed
     */
    protected function beforeGet()
    {
        // do something
        
    }
    
    /**
     * [overwrite] execute before post
     * @return  mixed
     */
    protected function beforePost()
    {
        // check: token
        $res = $this->verifyCsrfToken();
        if ($res && $res['errcode']) {
            return $this->error($res);
        }
        
        // check: post interval
        if (!$this->isStopped()) {
            $res = $this->verifyRequestInterval();
            if ($res['errcode']) {
                return $this->error($res);
            }
        }
    }
    
    /**
     * get csrf token, it is request param 
     * @return string
     */
    protected function getCsrfToken()
    {
        return $this->request->getParam($this->options['csrfTokenName']);
    }
    
    /**
     * [overwrite] need verified data
     * 
     * @return mixed can be null|numeric|array
     */
    protected function getCsrfTokenData()
    {
        return $this->csrfTokenData;
    }
    
    /**
     * set token additional data
     * @param  mixed $data
     * @return static
     */
    protected function setCsrfTokenData($data)
    {
        $this->csrfTokenData = $data;
        return $this;
    }
    
    /**
     * [overwrite]verify token
     * @param  string $token if null then auto get from form
     * @return \Wslim\Common\ErrorInfo
     */
    protected function verifyCsrfToken($token=null)
    {
        if ($this->options['csrfTokenEnabled']) {
            $token || $token = $this->request->getParam($this->options['csrfTokenName']);
            
            $data = static::getCsrfTokenData();
            
            $res = FormToken::instance()->verify($token, $data);
            
            if ($res['errcode']) {
                $this->stop()->errorInfo = $res;
            }
        }
        
        return $this->errorInfo;
    }
    
    /**
     * [overwrite] reset token
     * @param  string $token if null then auto get from form
     * @return void
     */
    protected function resetCsrfToken($token=null)
    {
        $token || $token = $this->request->getParam($this->options['csrfTokenName']);
        FormToken::instance()->reset($token);
    }
    
    /**
     * [overwrite] verify post interval
     * @access protected
     * @return \Wslim\Common\ErrorInfo
     */
    protected function verifyRequestInterval()
    {
        $check = false;
        if ($this->options['postInterval'] && $this->request->getMethod() === 'POST') {
            if (Config::get('session.enable') && $session = $this->getSession()) {
                if ($session->has('last_post_time')) {
                    $interval = time() - $session->get('last_post_time');
                    if ($interval > $this->options['postInterval']) {
                        $session->set('last_post_time', time());
                    } else {
                        $check = true;
                    }
                } else {
                    $session->set('last_post_time', time());
                }
            }
        }
        
        if ($check) {
            $errinfo['errcode'] = -501;
            $errinfo['errmsg']  = '提交太快了，请' . $this->options['postInterval'] . '秒后重试';
            
            return ErrorInfo::error($errinfo);
        }
        
        return ErrorInfo::success();
    }
    
    /**
     * [overwrite] default handle get
     * @access protected
     * @return mixed
     */
    /*
    private function doGet()
    {
        return $this->response;
    }
    */
    /**
     * [overwrite] default handle post
     * @access protected
     * @return mixed
     */
    /*
    private function doPost()
    {
        return $this->doGet();
    }
    */
    
    /**
     * {@inheritDoc}
     * @see \Wslim\Common\Controller::afterHandle()
     */
    public function afterHandle($event)
    {
        parent::afterHandle($event);
        
        // reset form_token
        if ($this->request->getMethod() === 'POST') {
            
            if ($this->errorInfo && !$this->errorInfo['errcode'] && $this->options['csrfTokenEnabled'] && $this->options['csrfTokenAutoReset']) {
                $this->resetCsrfToken();
            }
        }
    }
    
    /**
     * [overwrite]
     * {@inheritDoc}
     * @see \Wslim\Common\Controller::jump()
     */
    protected function jump($errcode=null, $errmsg=null, $data=null)
    {
        $this->stop();
        
        $renderType = $this->getRenderType();
        
        $this->errorInfo  = ErrorInfo::instance($errcode, $errmsg, $data);
        
        if ($renderType === 'json') {
            return $this->renderJson($this->errorInfo);
        } else {
            return $this->renderMessageBox($this->errorInfo);
        }
    }
    
    /**
     * {@inheritDoc}
     * @see \Wslim\Common\Controller::isForbiddenMethod()
     */
    protected function isForbiddenMethod($method)
    {
        return in_array($method, get_class_methods(get_class()));
    }
    
    /***********************************************************
     * new methods
     ***********************************************************/
    /**
     * get view
     * @return \Wslim\View\View
     */
    final public function getView()
    {
        return $this->module->getView();
    }
    
    /**
     * get or set view data
     * @param  string|array|Collection $key
     * @param  mixed        $value
     * @return \Wslim\Common\Collection|static if no params then return viewData, if assign param then set view data and return static
     */
    public function viewData($key=null, $value=null)
    {
        $view = $this->getView();
        if (!$key) {
            return $view->getData();
        } elseif (is_null($value) && is_string($key)) {
            $viewData = $view->getData();
            return isset($viewData[$key]) ? $viewData[$key] : null;
        } else {
            $view->setData($key, $value);
            return $this;
        }
    }
    
    /**
     * called auto by renderHtml() or renderXml()
     * @return static
     */
    final protected function beforeRenderView()
    {
        $this->initView();
        
        $view = $this->getView();
        
        if (!$view->getBeginContent()) {
            $beginContent  = "<?php use Wslim\Ioc; ?>" . PHP_EOL;
            $view->setBeginContent($beginContent);
        }
        
        // ioc
        Ioc::$htmlHelper || Ioc::$htmlHelper = new HtmlHelper();
        Ioc::$formHelper || Ioc::$formHelper = new FormHelper();
        
        // view data
        $viewData = $view->getData();
        $viewData['rootUrl']  = Config::getRootUrl();
        $viewData['baseUrl']  = $this->getModule()->getBaseUrl(true);
        $viewData['lang']     = Ioc::language()->getData();
        $viewData['requestParams'] = $this->input();
        $viewData['page']     = $this->inputInt('page');
        
        return $this;
    }
    
    /**
     * [overwrite] init view, called auto by renderHtml() or renderXml()
     * @return static
     */
    protected function initView()
    {
        
    }
    
    /**
     * get content type, it is json/xml/html/text
     *
     * default "application/x-www-form-urlencoded; charset=UTF-8"
     *
     * @return NULL|string
     */
    protected function getRenderType()
    {
        $headers = $this->request->getHeaders();
        
        if (isset($headers['X-Requested-With'])) {
            return 'json';
        }
        
        if ($this->input('get.ajax')) {
            return 'json';
        }
        
        $contentType = $this->request->detectContentType();
        
        if (strpos($contentType, 'json')) {
            return 'json';
        } elseif (strpos($contentType, 'xml')) {
            return 'xml';
        } elseif (strpos($contentType, 'html')) {
            return 'html';
        } elseif (strpos($contentType, 'plain')) {
            return 'text';
        }
        
        return 'html';
    }
    
    /**
     * render template
     * @param string $template
     * @param array  $data
     *
     * @return ResponseInterface
     */
    final public function render($template=null, $data=null)
    {
        $renderType = $this->getRenderType();
        switch ($renderType) {
            case 'json':
                $this->renderJson($data);
                break;
            case 'xml':
                $this->renderXml($data);
                break;
            case 'html':
                $this->renderHtml($template, $data);
                break;
            default:
                $string = print_r($data, true);
                $this->renderText($string);
                break;
        }
        
        return $this->response;
    }
    
    /**
     * [overwrite]render json string, 统一输出结果为 ['errcode'=>.., 'errmsg'=>.., 'somekey'=>..]
     *
     * @param  array $data
     * @return ResponseInterface
     */
    public function renderJson($data=null)
    {
        $this->beforeRender($data);
        
        $res = ErrorInfo::instance($this->responseData())->all();
        
        foreach ($res as $k => $v) {
            if ($v instanceof Collection) {
                $res[$k] = $v->all();
            }
        }
        
        if ($callback = $this->input('callback')) {
            $string = $callback . '(' . json_encode($res) . ')';
        } else {
            $this->response = $this->response->withHeader('Content-Type', 'application/json;charset=utf-8');
            $string = json_encode($res);
        }
        
        return $this->renderText($string);
    }
    
    /**
     * render xml from template
     * @param  array  $data
     * @return ResponseInterface
     */
    public function renderXml($data=null)
    {
        $this->beforeRender($data);
        
        $this->response = $this->response->withHeader('Content-Type', 'application/xml;charset=utf-8');
        
        $string = XmlFormatter::encode($this->responseData()->all());
        
        return static::renderText($string);
    }
    
    /**
     * render html from template
     * @param  string $template
     * @param  array  $data
     * @return ResponseInterface
     */
    public function renderHtml($template=null, $data=null)
    {
        $this->beforeRender($data);
        
        $this->beforeRenderView();
        
        $template = !empty($template) ? $template : $this->getName();
        
        $string = $this->module->getView()->render($template, $this->responseData());
        
        return $this->renderText($string);
    }
    
    /**
     * render messageBox
     *
     * @param  array  $data
     * @return ResponseInterface
     */
    public function renderMessageBox($data=null)
    {
        $this->beforeRender($data);
        
        $this->beforeRenderView();
        
        // use view inner view/message.html
        $output = $this->getView()->renderMessageBox($this->responseData()->all());
        
        return $this->renderText($output);
    }
    
    /**
     * forward the uri
     * @param  string $path
     * @param  string|array $query
     * @return ResponseInterface
     */
    final public function forward($path, $query=null)
    {
        $this->stop();
        
        $parts = parse_url($path);
        $path  = $parts['path'];
        $pathQuery = isset($parts['query']) ? $parts['query'] : null;
        
        if (strpos($path, '/') === 0) {
            $controller = Ioc::web()->getController($path);
        } else {
            $controller = $this->getModule()->getController($path);
        }
        
        if (!$controller) {
            $this->request = $this->request->withUri(Uri::createFromString($path));
            
            return $this->notFound();
        }
        
        if ($pathQuery && is_string($pathQuery)) {
            parse_str($pathQuery, $pathQuery);
        }
        if ($query && is_string($query)) {
            parse_str($query, $query);
        }
        $query = array_merge((array)$pathQuery, (array)$query);
        
        if ($query) {
            $this->request = $this->request->withQueryParams($pathQuery);
        }
        
        return $controller->handle($this->request, $this->response);
    }
    
    /**
     * Issues a HTTP redirect.
     *
     * @param  string  $uri   URI to redirect to
     * @param  int     $response_code  HTTP Status code to use for the redirect
     * 
     * @return void
     */
    public function redirect($uri=null, $response_code = 302)
    {
        http_response_code($response_code);
        header("Location:" . $uri);
        exit;
    }
    
    /**
     * 自动路由客户端类型，第一次会判断客户端类型，并设置cookie[_clienttype]，此后只检测cookie
     * pc端页面可以加入以下代码，访问移动端. 
     * <p>
     * ```
     * <a href="{Ioc::url('mobile:')}" class="btn">访问手机版</a>
     * ```
     * </p>
     */
    protected function autoRouteClientType()
    {
        // client type is set
        $clienttype = $this->input('_clienttype') ? : HttpHelper::getCookie('_clienttype');
        
        if (!$clienttype) {
            $clientType = ClientType::detectClientType();
            if ($clientType !== ClientType::PC && $clientType !== $this->getModule()->getName()) {
                $path = ltrim($this->request->getUri()->getPath(), '/');
                $parts = explode('/', $url, 2);
                if ($parts[0] === $this->getModule()->getName()) {
                    $url = Ioc::url($clientType . ':' . isset($parts[1]) ? $parts[1] : '');
                } else {
                    $url = Ioc::url($clientType . ':' . $path);
                }
                
                HttpHelper::setCookie('_clienttype', $this->getModule()->getName(), 3600, null, UriHelper::GetRootDomain());
                
                $url = UriHelper::buildUrl($url, $this->request->getUri()->getQuery());
                static::redirect($url);
            }
        } else {
            $this->viewData('_clienttype', $clienttype);
            HttpHelper::setCookie('_clienttype', $clienttype, 3600, null, UriHelper::GetRootDomain());
        }
    }
    
    /**
     * get current page, default 1
     * @return int
     */
    protected function getCurrentPage()
    {
        return max($this->inputInt('page'), 1);
    }


}