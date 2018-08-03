<?php
namespace Wslim\Util;

/**
 * HttpRequest
 * when set verbose option, return ['status'=>, 'body'=>, 'info'=>, 'header'=>, 'request_header'=>, 'content_type'=>]
 * notice: body is responseText
 * 
 * 1) direct method:
 * 
 * <ul> 
 * <li> HttpRequest::get($url, $data, $options) </li>
 * <li> HttpRequest::post($url, $data, $options)</li>
 * </ul>
 * 
 * 2) custom call:
 * <p>
 * <br> $http = HttpRequest::instance()instance($url)->method('GET')->data(['name' => 'value']);
 * <br> $error = $http->getError();
 * <br> $errorStr = $http->getErrorString();
 * <br> $res  = $http->getResponse();
 * <br> $info = $http->getResponseInfo();
 * <br> $body = $http->getResponseText();
 * <br> $bodyArray = $http->toArray();
 * <br> $bodyJson = $http->toJson();
 * </p>
 * 
 * 3) save file:
 * <p>
 * <br> HttpRequest::instance($url, $data, $options)->save($filename);
 * <br> HttpRequest::get($url, $data, ['savePath' => $filename]);
 * </p>
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class HttpRequest
{
    const TYPES         = ['CURL', 'SOCKET', 'FILE'];
    const METHODS       = ['GET', 'POST', 'HEAD', 'PUT', 'DELETE', 'PATCH'];
    const DATA_TYPES    = ['text', 'array', 'json'];
    
    /**********************************************************
     * static mathods
     **********************************************************/
    /**
     * http request instance, return HttpRequest object. 
     * 
     * @param  mixed  $method if array ['url'=>..., 'data'=>..., 'dataType'=>...]
     * @param  mixed  $url
     * @param  mixed  $data
     * @param  array  $options
     * @return static
     */
    static public function instance($method = null, $url = null, $data = false, $options = false)
    {
        return new static($method, $url, $data, $options);
    }
    
    /**
     * 模拟GET请求，默认返回字串，可以指定 options['dataType'=>'text|json|xml|array']
     *
     * @param  string|array $url
     * @param  string|array $data
     * @param  array        $options
     * 
     * @return mixed 失败时返false,成功返响应主体
     * 
     * @example
     * ```
     * HttpRequest::get('http://api.example.com/?a=123&b=456');
     * ```
     */
    static public function get($url, $data=null, $options=null)
    {
        $instance = new static('GET', $url, $data, $options);
        return $instance->getResponse();
    }
    
    /**
     * 模拟GET请求，并返回数组
     *
     * @param  string|array $url
     * @param  string|array $data
     * @param  array        $options
     *
     * @return array
     * 
     * @example
     * ```
     * HttpRequest::getArray('http://api.example.com/?a=123&b=456');
     * ```
     */
    static public function getArray($url, $data=null, $options=null)
    {
        $instance = new static('GET', $url, $data, $options);
        
        return $instance->toArray();
    }
    
    /**
     * 模拟GET请求，并返回json对象(stdClass)
     *
     * @param  string|array $url
     * @param  string|array $data
     * @param  array        $options
     * 
     * @return \stdClass
     */
    static public function getJson($url, $data=null, $options=null)
    {
        $instance = new static('GET', $url, $data, $options);
        return $instance->toJson();
    }
    
    /**
     * 模拟POST请求，默认返回字串，可以指定 options['dataType'=>'text|json|xml|array']
     * options: dataType,
     * @param  string|array $url
     * @param  string|array $data
     * @param  array        $options
     *
     * @return mixed 失败时返false,成功返post的响应值
     *
     * @example
     * ```
     * HttpRequest::post('http://api.example.com/?a=123', array('abc'=>'123', 'efg'=>'567'));
     * HttpRequest::post('http://api.example.com/', '这是post原始内容');
     * HttpRequest::post('http://api.example.com/', array('abc'=>'123', 'file1'=>'@/data/1.jpg')); //文件post上传
     * ```
     */
    static public function post($url, $data, $options=null)
    {
        $instance = new static('POST', $url, $data, $options);
        return $instance->getResponse();
    }
    
    /**
     * http post and return array
     * @param  string|array $url
     * @param  string|array $data
     * @param  array        $options
     * 
     * @return array
     */
    static public function postArray($url, $data, $options=null)
    {
        $instance = new static('POST', $url, $data, $options);
        return $instance->toArray();
    }
    
    /**
     * convert path str encoding
     * @param  string $filename
     * @return string
     */
    static private function convertEncoding($filename)
    {
        return strtoupper(substr(PHP_OS,0,3))==='WIN' ? iconv('UTF-8', 'GBK', $filename) : $filename;
    }
    
    /**********************************************************
     * instance mathods
     **********************************************************/
    /**
     * definition
     * @var array
     */
    private $def = [
        'type'      => 'CURL',  // CURL|SOCKET|FILE
        'method'    => 'GET',
        'url'       => null,
        'data'      => null,
        'dataType'  => 'text',  // text|json|array
        'header'    => [
            //'Accept'            => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            //'Accept-Language'   => 'zh-CN,zh;q=0.8,en-US;q=0.5,en;q=0.3',
            //'Accept-Encoding'   => 'gzip, deflate, br',
            //'Accept-Charset'    => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
            //'User-Agent'        => 'Mozilla/5.0 Firefox/56.0',
            //'Connection'        => 'close',
        ],
        'cookie'    => null,
        'success'   => null,
        'error'     => null,
        'timeout'   => 0,
        'verbose'   => 0,       // if true return verbose result
        'isDownload'=> 0,       // if download mode then return ['body'=>...]
        'savePath'  => null,    // save file path
    ];
    
    /**
     * options, name is upper, contain curl options with prefix CURLOPT_
     * @var array
     */
    private $options = [
        'HTTP_VERSION'  => '1.1',
        'RETURN_HEADERS'=> 1,
    ];
    
    /**
     * error array
     * @var array
     */
    private $error;
    private $xmlErrors;
    private $doneExecuted = false;
    private $responseInfo, $responseHeaders, $responseText, $status;
    
    /**
     * consturct, use multi param or array params, params key is [method, url, data, dataType, timeout, options]
     * 
     * @param mixed  $method
     * @param mixed  $url
     * @param mixed  $data
     * @param array  $options if string it is dataType, can be array|json|text
     */
    public function __construct($method = null, $url = null, $data = null, $options = null) 
    {
        if ($options) {
            if (is_string($options) && in_array($options, static::DATA_TYPES)) {
                $this->dataType($options);
            } elseif (is_callable($options)) {
                $this->success($options);
            } elseif (is_array($options)) {
                $this->options($options);
            }
        }
        
        if ($data) {
            if (is_string($data)) {
                if (in_array($data, static::DATA_TYPES)) {
                    $this->dataType($data);
                } else {
                    $this->data($data);
                }
            } elseif (is_callable($data)) {
                $this->success($data);
            } else {
                $this->data($data);
            }
        }
        
        if ($url) {
            if (is_string($url)) {
                $this->url($url);
            } elseif (is_callable($url)) {
                $this->success($url);
            } elseif (is_array($url)) {
                $this->options($url);
            }
        }
        
        if ($method) {
            if (is_string($method)) {
                if (strlen($method) < 7) {
                    $this->method($method);
                } else {
                    $this->url($method);
                }
            } else {
                $method = (array) $method;
                if (isset($method[0])) {
                    $this->url($method);
                } else {
                    $this->options($method);
                }
            }
        }
    }
    
    /**
     * set handle type, can be CURL|SOCKET|FILE
     * @param  string $type
     * @return static
     */
    public function type($type)
    {
        $type = strtoupper($type);
        if (!in_array($type, ['CURL', 'SOCKET', 'FILE'])) {
            $type = 'CURL';
        }
        $this->def['type'] = $type;
        return $this;
    }
    
    /**
     * set method
     * @param  string $method
     * @return static
     */
    public function method($method)
    {
        $method = strtoupper($method);
        if (in_array($method, static::METHODS)) {
            $this->def['method'] = $method;
        } else {
            $this->error = ['errcode'=>-1, 'errmsg'=>"Invalid method: $method"];
        }
        return $this;
    }
    
    /**
     * set url
     * @param  string $url
     * @return static
     */
    public function url($url)
    {
        if (strpos($url, 'https:') === 0) {
            $this->def['ssl'] = true;
        } else {
            if (strpos($url, 'http:') === false && strpos($url, '//') === false) {
                $url = 'http://' . $url;
            }
        }
        
        $this->def['url'] = $url;
        return $this;
    }
    
    /**
     * set request data
     * @param  mixed $data array or string
     * @return static
     */
    public function data($data) {
        $this->def['data'] = $data;
        return $this;
    }
    
    /**
     * set dataTYpe
     * @param  string $dataType
     * @return static
     */
    public function dataType($dataType) {
        $this->def['dataType'] = $dataType;
        return $this;
    }
    
    /**
     * set timeout
     * @param  int $timeout
     * @return static
     */
    public function timeout($timeout)
    {
        $this->def['timeout'] = $timeout;
        return $this;
    }
    
    /**
     * set request header
     * @param  string $name
     * @param  mixed  $value
     * @return static
     */
    public function header($name, $value=null) 
    {
        if (is_array($name)) {
            $this->def['header'] = array_merge($this->def['header'], $name);
        } else {
            $this->def['header'][$name] = $value;
        }
        return $this;
    }
    
    /**
     * set request cookie
     * @param  string $name
     * @param  string $value
     * @return static
     */
    public function cookie($name, $value=null) 
    {
        $str = '';
        if (is_array($name)) {
            $arr = [];
            foreach ($name as $k=>$v) {
                $arr[] = $k . '=' . $v;
            }
            $str = implode(';', $arr);
        } elseif ($value) {
            $str = $name . '=' . $value;
        } else {
            $str = $name;
        }
        
        $this->def['cookie'] = $this->def['cookie'] ? $this->def['cookie'].';'.$str : $str;

        return $this;
    }
    
    /**
     * set success callback
     * @param callable $func
     * @return static
     */
    public function success($func)
    {
        $this->def['success'] = $func;
        return $this;
    }
    
    /**
     * set error callback
     * @param  callable $func
     * @return static
     */
    public function error($func)
    {
        $this->def['error'] = $func;
        return $this;
    }
    
    /**
     * set verbose output
     * @param  boolean $bool
     * @return static
     */
    public function verbose($bool)
    {
        $this->def['verbose'] = $bool;
        return $this;
    }
    
    /**
     * set savePath
     * @param  string $savePath
     * @return static
     */
    public function savePath($savePath)
    {
        $this->def['savePath'] = $savePath;
        return $this;
    }
    
    /**
     * set options
     * @param  mixed $option array or string
     * @param  mixed $value
     * @return static
     */
    public function options($option, $value=null) 
    {
        if (is_array($option)) {
            foreach ($option as $k=>$v) {
                $this->options($k, $v);
            }
        } else {
            // 兼容转换
            if ($option === 'cookies') {
                $option = 'cookie';
            } elseif ($option === 'headers') {
                $option = 'header';
            }
            
            if (array_key_exists($option, $this->def)) {
                if (method_exists($this, $option)) {
                    $this->$option($value);
                }
            } else {
                $this->options[strtoupper($option)] = $value;
            }
        }
        return $this;
    }
    
    /**
     * request execute
     * 
     * @return static
     */
    protected function execute()
    {
        if ($this->doneExecuted) {
            
            return $this;
        }
        
        $this->doneExecuted = true;
        
        if ($this->def['type'] === 'FILE' && $this->def['method'] === 'GET') {
            $this->executeFile();
        } elseif (in_array($this->def['type'], ['CURL', 'FILE']) && function_exists('curl_init')) {
            $this->executeCurl();
        } else {
            $this->executeSocket();
        }
        // handle success/error callback
        static::callback();
        
        return $this;
    }
    
    protected function executeFile()
    {
        if ($this->def['data']) {
            $this->def['url'] .= (strpos($this->def['url'], '?') ? "&" : '/?') . $this->parseQuery($this->def['data']);
        }
        
        $this->responseText = file_get_contents($this->def['url']);
    }
    
    protected function executeCurl() 
    {
        // check
        if (!$this->def['url']) {
            return $this->setError('url is not set');
        }
        
        $ch = curl_init();
        
        // method and data
        $method = $this->def['method'];
        if ($method === 'GET') {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            
            if ($this->def['data']) {
                $this->def['url'] .= (strpos($this->def['url'], '?') ? "&" : '?') . $this->parseQuery($this->def['data']);
            }
        } elseif ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($this->def['data']) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->parseData($this->def['data']));
            }
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_PUT, true);
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }
        
        // header, require array
        if ($this->def['header']) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header2Array($this->def['header']));
        }
        
        // cookie
        if ($this->def['cookie']) {
            curl_setopt($ch, CURLOPT_COOKIE, $this->def['cookie']);
        }
        
        // set return header option
        $returnHeaders = (bool) $this->options['RETURN_HEADERS'];
        curl_setopt($ch, CURLOPT_HEADER, $returnHeaders);       // 设为 TRUE 获取responseHeader，curl_exec()返回结果是 header和body的组合文本，需要手动分离
        curl_setopt($ch, CURLINFO_HEADER_OUT, $returnHeaders);  // 设为 TRUE 时curl_getinfo()返回结果包含 request_header 信息，从 PHP 5.1.3 开始可用。
        
        // register callback which process the headers
        if (isset($this->def['headerCallback']) && $this->def['headerCallback']) {
            if (!is_callable($this->def['headerCallback']) && is_string($this->def['headerCallback'])) {
                $this->def['headerCallback'] = array(&$this, $this->def['headerCallback']);
            }
            curl_setopt($ch, CURLOPT_HEADERFUNCTION, $this->def['headerCallback']);
        }
        
        // url and base
        curl_setopt($ch, CURLOPT_URL, $this->def['url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );   // return result
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);    // allow redirect
        if ($this->def['timeout']) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->def['timeout']);
        }
        
        // ssl
        if (strpos($this->def['url'], 'https') === 0) {
            //
            //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
            //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);    // 1的值不再支持，请使用2或0
            
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);    // 1的值不再支持，请使用2或0
            curl_setopt($ch, CURLOPT_SSLVERSION, 1);
        }
        
        // Authentication
        if (isset($this->def['authUsername']) && isset($this->def['authPassword']) && $this->def['authUsername']) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $this->def['authUsername'] . ':' . $this->def['authPassword']);
        }
        
        // custom option
        try {
            foreach ($this->options as $k => $v) {
                if (strpos($k, 'CURLOPT_') !== false) {
                    curl_setopt($ch, get_defined_constants()[$k], $v);
                }
            }
        } catch (\Exception $e) {
            // log
        }
        
        $this->responseText     = curl_exec($ch);    // 如果设置了 CURLOPT_HEADER, 返回结果是 header和body的组合文本，需要手动分离
        $this->status           = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->responseInfo     = curl_getinfo($ch);
        
        if ($errno = curl_errno($ch)) {
            $this->setError(curl_errno($ch), curl_error($ch));
        } else {
            if ($returnHeaders) {
                // 如果有302,返回文本会有多个headers内容，需要去掉
                $res = explode("\r\n\r\n", $this->responseText, 4);
                $header = array_shift($res); 
                while (count($res)>1 && stripos($res[0], 'HTTP') === 0) {
                    $header = array_shift($res);
                }
                $this->responseHeaders  = $this->header2Array($header);
                $this->responseText     = implode("\r\n\r\n", $res);
            }
        }
        
        curl_close($ch);
    }
    
    protected function executeSocket() 
    {
        $uri        = $this->def['url'];
        $method     = $this->def['method'];
        $httpVersion = $this->options['HTTP_VERSION'];
        $data       = $this->def['data'];
        $crlf = "\r\n";
        
        $rsp = '';
        
        // parse host, port
        preg_match('/(https?):\/\/([^\:\/]+)(:\d+)?/', $uri, $matches);
        $isSSL = isset($matches[1]) && $matches[1]=='https' ? true : false;
        $host = isset($matches[2]) ? $matches[2] : null;
        $port = isset($matches[3]) ? str_replace(':', '', $matches[3]) : null;
        if (!$host) {
            $this->setError('Host set error');
            return false;
        }
        $port = $port ? : ($isSSL ? 443 : 80);
        
        // Deal with the data first.
        if ($data && $method === 'POST') {
            $data = $this->parseQuery($data);
        } else if ($data && $method === 'GET') {
            $uri .= (strpos($uri, '?') ? "&" : '/?') . $this->parseQuery($data);
            $data = $crlf;
        } else {
            $data = $crlf;
        }
        
        // Then add
        if ($method === 'POST') {
            $this->header('Content-Type', 'application/x-www-form-urlencoded');
            $this->header('Content-Length', strlen($data));
        } else {
            $this->header('Content-Type', 'text/plain');
            $this->header('Content-Length', strlen($crlf));
        }
        if (isset($this->def['authUsername']) && isset($this->def['authPassword']) && $this->def['authUsername'] && $this->def['authPassword']) {
            $this->header('Authorization', 'Basic '.base64_encode($this->def['authUsername'].':'.$this->def['authPassword']));
        }
        
        $headers = $this->def['header'];
        $req = '';
        $req .= $method.' '.$uri.' HTTP/'.$httpVersion.$crlf;
        $req .= "Host: ".$host.$crlf;
        foreach ($headers as $header => $content) {
            if (is_numeric($header)) continue;  // 跳过无效值
            $req .= $header.': '.$content.$crlf;
        }
        $req .= $crlf;
        if ($method === 'POST') {
            $req .= $data;
        } else {
            $req .= $crlf;
        }
        
        // Construct hostname.
        $fsock_host = ($isSSL ? 'ssl://' : '').$host;
        
        // Open socket.
        $httpreq = @fsockopen($fsock_host, $port, $errno, $errstr, 30);
        
        // Handle an error.
        if (!$httpreq) {
            $this->setError($errno, $errstr);
            return false;
        }
        
        // Send the request.
        fputs($httpreq, $req);
        
        // Receive the response.
        /*
        while ($line = fgets($httpreq)) {
            $rsp .= $line;
        }
        */
        while (!feof($httpreq)) {
            $rsp .= fgets($httpreq);
        }
        
        // Extract the headers and the responseText.
        list($headers, $responseText) = explode($crlf.$crlf, $rsp, 2);
        
        // Store the finalized response.
        // HTTP/1.1 下过滤掉分块的标志符
        if ($httpVersion == '1.1') {
            $responseText = static::unchunkHttp11($responseText);
        }
        $this->responseText = $responseText;
        
        // Store the response headers.
        $headers = explode($crlf, $headers);
        $this->status = array_shift($headers);  // HTTP/1.1 200 OK
        $this->status = explode(' ', $this->status)[1];
        $this->responseHeaders = array();
        foreach ($headers as $header) {
            list($key, $val) = explode(': ', $header);
            $this->responseHeaders[$key] = $val;
        }
        
        fclose($httpreq);
        
    }
    
    private function callback()
    {
        if (!$this->error) {
            if ($this->def['success']) {
                $callback = static::parseCallback($this->def['success']);
                $callback($this->getResponse());
            }
        } else {
            if ($this->def['error']) {
                $callback = static::parseCallback($this->def['error']);
                $callback($this->getError());
            }
        }
    }
    
    /**
     * convert @ prefixed file names to CurlFile class, since @ prefix is deprecated as of PHP 5.6
     * @param mixed $data
     * @param mixed
     */
    protected function parseData($data=null)
    {
        if (!$data) $data = $this->def['data'];
        
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                if (strpos($v, '@') === 0 && class_exists('\CURLFile')) {
                    $v = ltrim($v, '@');
                    $data[$k] = new \CURLFile($v);
                }
            }
        }
        
        return $data;
    }
    
    protected function parseQuery($data=null)
    {
        if (!$data) $data = $this->def['data'];
        
        if (is_array($data)) {
            $data_array = array();
            foreach ($this->def['data'] as $key => $val) {
                if (!is_string($val)) {
                    $val = json_encode($val);
                }
                $data_array[] = urlencode($key).'='.urlencode($val);
            }
            return implode('&', $data_array);
        } else {
            return $data;
        }
    }
    
    private function parseCallback($func)
    {
        if (is_callable($func)) {
            return $func;
        } elseif (is_string($func)) {
            return [&$this, $func];
        } else {
            return null;
        }
    }
    
    /**
     * parse header to array
     * @param  $str
     * @return array
     */
    private function header2Array($str)
    {
        if (is_array($str)) return $str;
        
        $result = [];
        $array = explode("\n", trim(str_replace("\r\n", "\n", $str), "\n"));
        foreach($array as $i => $line) {
            if ($i === 0) {
                $result['Http-Status'] = $line; // "HTTP/1.1 200 OK"
            } else {
                $header = explode(':', $line, 2);
                if (!$header[0]) continue;
                
                if (isset($header[1])) {
                    $result[trim($header[0])] = trim($header[1]);
                } else {
                    $result[] = trim($header[0]);
                }
            }
        }
        return $result;
    }
    
    /**
     * parse header to string
     * @param  mixed  $headers
     * @return string
     */
    private function header2String($header)
    {
        $str = '';
        if (is_array($header)) foreach ($header as $k=>$v) {
            if (is_numeric($k)) continue;
            $str .= $k . ': ' . $v . "\r\n";
        } else {
            $str = $header;
        }
        return $str;
    }
    
    private function data2json($data)
    {
        if (is_string($data) && (preg_match('/^(\"\')?[\{\[]/', $data))) {
            return json_decode($data, true);
        }
        return $data;
    }
    
    private function setError($errno, $errmsg=null)
    {
        $this->error = is_numeric($errno) ? ['errcode' => $errno, 'errmsg' => $errmsg] : ['errcode' => -1, 'errmsg' => $errno];
        return $this;
    }
    
    /**
     * get error, ['errcode'=>, 'errmsg'=>]
     * @return array
     */
    public function getError() 
    {
        $this->execute();
        
        return $this->error;
    }
    
    /**
     * get error string
     * @return string|NULL
     */
    public function getErrorString()
    {
        $this->execute();
        
        return $this->error ? $this->error['errcode'] . ':' . $this->error['errmsg'] : null;
    }
    
    /**
     * get resposne, return for dataType result
     * @return mixed
     */
    public function getResponse()
    {
        $this->execute();
        
        switch ($this->def['dataType']) {
            case 'array':
                $result = static::toArray();
                break;
            case 'object':
            case 'json':
                $result = static::toJson();
                break;
            default:
                $result = $this->getErrorString() ? : $this->responseText;
        }
        
        return $result;
    }
    
    /**
     * get verbose response
     * success return ['status'=>, 'body'=>, 'info'=>, 'header'=>, 'request_header'=>, 'content_type'=>]
     * error   return ['errcode'=>n, 'errmsg'=>...]
     * 
     * @return array
     */
    public function getVerboseResponse() 
    {
        $this->execute();
        
        if ($this->error) {
            return $this->error;
        }
        
        $contentType = $this->getContentType();
        
        $body = ($this->def['dataType'] == 'array' || strpos($contentType, 'json') !== false) ? static::data2json($this->responseText) : $this->responseText;
        
        return [
            'status'    => $this->status,
            'body'      => $body, 
            'info'      => $this->responseInfo,
            'header'    => $this->responseHeaders,
            'request_header'    => $this->getRequestHeaders(),
            'content_type'      => $contentType
        ];
    }
    
    /**
     * get resposne status code: 200|xxx
     * @return int
     */
    public function getStatus()
    {
        $this->execute();
        
        return $this->status;
    }
    
    /**
     * get response info
     * @return array
     */
    public function getResponseInfo()
    {
        $this->execute();
        
        return $this->responseInfo;
    }
    
    /**
     * get response text
     * @return string
     */
    public function getResponseText() 
    {
        $this->execute();
        
        if ($this->error) {
            return $this->getErrorString();
        }
        
        return $this->responseText;
    }
    
    public function getResponseHeaders() 
    {
        $this->execute();
        return $this->responseHeaders;
    }
    
    
    public function getResponseHeadersString()
    {
        $this->execute();
        
        return static::header2String($this->responseHeaders);
    }
    
    public function getResponseCookie($cookie = false)
    {
        $this->execute();
        
        if($cookie !== false) {
            return isset($this->responseHeaders["Set-Cookie"][$cookie]) ? $this->responseHeaders["Set-Cookie"][$cookie] : null;
        }
        return isset($this->responseHeaders["Set-Cookie"]) ? $this->responseHeaders["Set-Cookie"] : null;
    }
    
    /**
     * get content type
     * @return string
     */ 
    public function getContentType()
    {
        if (isset($this->responseInfo['content_type'])) {
            $contentType = $this->responseInfo['content_type'];
        } else {
            $contentType = isset($this->responseHeaders['Content-Type']) ? $this->responseHeaders['Content-Type'] : null;
        }
        
        if ($contentType) {
            $contentTypeParts = preg_split('/\s*[;,]\s*/', $contentType);
            return strtolower($contentTypeParts[0]);
        }
        
        return null;
    }
    
    
    public function getRequestHeaders()
    {
        $this->execute();
        
        return isset($this->responseInfo['request_header']) ? $this->header2Array($this->responseInfo['request_header']) : null;
    }
    
    public function getRequestHeadersString()
    {
        $this->execute();
        
        return isset($this->responseInfo['request_header']) ? $this->responseInfo['request_header'] : '';
    }
    
    /**
     * to json object, maybe stdClass|true|false|null
     * @return mixed
     */
    public function toObject() 
    {
        return $this->toJson();
    }
    
    /**
     * to json object, maybe stdClass|true|false|null
     * @return mixed
     */
    public function toJson()
    {
        return json_decode(json_encode($this->toArray()));
    }
    
    /**
     * return  array response
     * @return array
     */
    public function toArray()
    {
        $this->dataType('array');
        
        $this->execute();
        
        if ($this->def['savePath']) {
            return $this->save();
        } elseif ($this->def['verbose']) {
            return $this->getVerboseResponse();
        }
        
        return $this->getError() ? : json_decode($this->responseText, true);
    }
    
    /**
     * save 用于下载请求内容；返回 info 信息和 body 组成的数组，可根据 content_type 判断mime类型来确定内容类型
     * 
     * @param  string $savePath
     * 
     * @return array  ['errorcode'=>, 'errmsg'=>.., 'info'=>..., 'save_path' =>, ]
     *
     * -- 返回结果示例
     * [
     * 'errcode' => 0,
     * 'errmsg'  => 'save ok',
     * 'info'    => 
            {
             url": "http://file.api.weixin.qq.com/cgi-bin/media/get?access_token=My4oqLEyFVrgFF-XOZagdvbTt9XywYjGwMg_GxkPwql7-f0BpnvXFCOKBUyAf0agmZfMChW5ECSyTAgAoaoU2WMyj7aVHmB17ce4HzLRZ3XFTbm2vpKt_9gYA29xrwIKpnvH-BYmNFSddt7re5ZrIg&media_id=QQ9nj-7ctrqA8t3WKU3dQN24IuFV_516MfZRZNnQ0c-BFVkk66jUkPXF49QE9L1l",
             "content_type": "image/jpeg",
             "http_code": 200,
             "header_size": 308,
             "request_size": 316,
             "filetime": -1,
             "ssl_verify_result": 0,
             "redirect_count": 0,
             "total_time": 1.36,
             "namelookup_time": 1.016,
             "connect_time": 1.078,
             "pretransfer_time": 1.078,
             "size_upload": 0,
             "size_download": 105542,
             "speed_download": 77604,
             "speed_upload": 0,
             "download_content_length": 105542,
             "upload_content_length": 0,
             "starttransfer_time": 1.141,
             "redirect_time": 0,
            },
        'save_path' => ... 
       ]
     */
    public function save($savePath=null)
    {
        $this->execute();
        
        if ($this->error) {
            return $this->error;
        }
        
        $info = $this->getResponseInfo();
        if ($this->getStatus() != '200') {
            return [
                'errcode'   => -1, 
                'errmsg'    => 'http status error:' . $this->getStatus(),
                'info'      => $info
            ];
        }
        
        $savePath || $savePath = $this->def['savePath'];
        if ($savePath) {
            // ext is set
            if ( !strpos(pathinfo($savePath, PATHINFO_BASENAME), '.') ) {
                $savePath = rtrim($savePath, '/\\') . '/' . pathinfo($this->def['url'], PATHINFO_BASENAME);
            }
            
            if ($content_type = $this->getContentType()) {
                $content_type = explode('/', $content_type, 2);
                $rFileExt = $content_type[count($content_type) - 1];
                $fielExt = pathinfo($savePath, PATHINFO_EXTENSION);
                if (!strpos(pathinfo($savePath, PATHINFO_BASENAME), '.') && $fielExt !== $rFileExt && strlen($rFileExt) < 5) {
                    $savePath .= '.' . $rFileExt;
                }
            }
            
            // mkdir
            $dir = pathinfo($savePath, PATHINFO_DIRNAME);
            $dir = static::convertEncoding($dir);
            
            if (!file_exists($dir)) {
                @mkdir($dir, '0555', true);
                if (!file_exists($dir)) {
                    return [
                        'errcode'   =>-1, 
                        'errmsg'    =>'create dir failure'
                    ];
                }
            }
            
            // if filename contain chinese word, need convert to GBK
            $len = file_put_contents(static::convertEncoding($savePath), $this->getResponseText());
            if ($len) {
                return [
                    'errcode'   =>0, 
                    'errmsg'    =>'save file ok.', 
                    'save_path' => $savePath
                ];
            }
            
            return [
                'errcode'   =>-1, 
                'errmsg'    =>'save file failure.'
            ];
        }
        
        return [
            'errcode'   => -2,
            'errmsg'    => 'save path is not set.',
            'info'      => $info
        ];
    }
       
    /**
     * fsockopen 读取因为使用了 Transfer-Encoding: chunked, 会多出分块时的数字字符，需要去掉。方法一，会用如下，方法二，使用 HTTP/1.0
     * @param  string $data
     * @return string
     */
    function unchunkHttp11($data) {
        /*
        $fp = 0;
        $outData = "";
        while ($fp < strlen($data)) {
            $rawnum = substr($data, $fp, strpos(substr($data, $fp), "\r\n") + 2);
            $num = hexdec(trim($rawnum));
            $fp += strlen($rawnum);
            $chunk = substr($data, $fp, $num);
            $outData .= $chunk;
            $fp += strlen($chunk);
        }
        return $outData;
        */
        
        return preg_replace_callback(
            '/(?:(?:\r\n|\n)|^)([0-9A-F]+)(?:\r\n|\n){1,2}(.*?)'.
            '((?:\r\n|\n)(?:[0-9A-F]+(?:\r\n|\n))|$)/si',
            create_function(
                '$matches',
                'return hexdec($matches[1]) == strlen($matches[2]) ? $matches[2] : $matches[0];'
                ),
            $data
        );
    }
    
    
    
    
}