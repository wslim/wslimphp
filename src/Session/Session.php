<?php
namespace Wslim\Session;

use Wslim\Common\Collection;
use Wslim\Common\StorageInterface;
use Wslim\Util\HttpHelper;

if (! class_exists('SessionException', false)) {
    class SessionException extends \Exception {

    }
}

/**
 * session class
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class Session extends Collection
{
    /**
	 * Session handler.
	 *
	 * @var  \Wslim\Session\HandlerInterface
	 */
	protected $handler = null;
	
	/**
	 * Session storage.
	 *
	 * @var  \Wslim\Common\StorageInterface
	 */
	protected $storage = null;
	
	/**
	 * Session options.
	 *
	 * @var  array
	 */
	protected $options = array();
	
	/**
	 * @var bool Whether ot not the session has started
	 */
	protected $started = false;
	
	/**
	 * default options
	 * @var array
	 */
    static private $defaultOptions = [
        'handler'       => 'native',        // native|storage 后者为使用 storage 类来缓存
        'session_name'  => 'default',       // session_name() 调用此名称
        'save_handler'  => 'files',
        'save_path'     => 'sessions',      // relative storage path
        'session_ttl'   => 1440,            // default 1440
        //'serialize_handler' => 'php', // php, php_serialize, php_binary
        'cookie_domain' => '',    		    // Cookie 作用域
        'cookie_path'   => '/',             // Cookie 作用路径
        'cookie_ttl'    => 0,               // Cookie 生命周期，0 表示随浏览器进程
        'group'         => '',              // 分组,用于区分不同的session,同时作为 cookie_prefix
        'timeout'       => 3,               // 用于缓存服务器连接超时，对于memcache/redis建议小一点，官方的timeout为1s
    ];
    
	/**
	 * 对于使用 storage 类来保存的，设置auto_start=0
	 * @param  string $options
	 * @throws SessionException
	 */
    public function __construct($options=null)
    {
        // validate that headers have not already been sent
        if (headers_sent()) {
            throw new SessionException('Unable to register session handler because headers have already been sent.');
        }
        
        // obtain the options
        // $this->options = array_change_key_case($options, CASE_LOWER);
        $this->options = array_merge(static::$defaultOptions, is_array($options) ? $options : array());
        
        // check handler
        if (!in_array($this->options['handler'], array('native', 'storage'))) {
        	$this->options['handler'] = 'native';
        }
    }
    
    /**
     * ini_set
     */
    protected function init()
    {
        // check options
        foreach ($this->options as $key => $value) {
            if ($key === 'session_ttl') {
                $this->options['gc_maxlifetime'] =  $value;
                unset($this->options['session_ttl']);
            } elseif( $key === 'cookie_ttl') {
                $this->options['cookie_lifetime'] =  $value;
                unset($this->options['cookie_ttl']);
            }
        }
        
        // file storage
        if ($this->options['storage'] == 'file') {
            if (isset($this->options['save_path'])) {
                if ($this->options['group']) {
                    $this->options['save_path'] = $this->options['save_path'] . '/' . $this->options['group'];
                }
                $this->checkFilePath($this->options['save_path']);
            }
        }
        
        $handlerClass = '\\Wslim\\Session\\Handler\\'. ucfirst($this->options['handler']).'Handler';
        
        // check handler
        if ($this->options['handler'] === 'native') {
            
            //$this->handler = new $handlerClass;
        } elseif ($this->options['handler'] === 'storage') {
            $storageOptions = [
                'throw_error' => 0, // 不报错，自动切换到native
            ];
            foreach ($this->options as $key => $value) {
                if (in_array($key, StorageInterface::AllowOptions) || $key === 'storage') {
                    $storageOptions[$key] = $value;
                }
            }
            $this->options['save_handler'] = 'user';
            
            ini_set('session.auto_start', 0);
            
            // create handler 
            $this->handler = new $handlerClass($storageOptions);
        }
        
        // set ini options
        $supportedOptions = array('save_path', 'name', 'save_handler',
            'gc_probability', 'gc_divisor', 'gc_maxlifetime', 'serialize_handler',
            'cookie_lifetime', 'cookie_path', 'cookie_domain', 'cookie_secure',
            'cookie_httponly', 'use_strict_mode', 'use_cookies', 'use_only_cookies',
            'referer_check', 'entropy_file', 'entropy_length', 'cache_limiter',
            'cache_expire', 'use_trans_sid', 'hash_function', 'hash_bits_per_character',
            'upload_progress.enabled', 'upload_progress.cleanup', 'upload_progress.prefix',
            'upload_progress.name', 'upload_progress.freq', 'upload_progress.min_freq',
            'lazy_write'
        );
        foreach ($this->options as $key => $value) {
            if (in_array($key, $supportedOptions)) {
                ini_set('session.' . $key, $value);
            }
        }
        
        if (version_compare(PHP_VERSION, '7.0.0') >= 0) {
            ini_set('session.lazy_write', 0);
        }
    }
    
    private function parseSavePath($storageOptions)
    {
        $servres = isset($storageOptions['servers']) && $storageOptions['servers'] ? $storageOptions['servers'] : null;
        if (!$servers) {
            if (isset($storageOptions['host'])) {
                $servres[] = [
                    'host' => $storageOptions['host'] ? : 'localhost',
                ];
            }
        }
        
        $save_paths = [];
        if (in_array($storageOptions['storage'], ['redis', 'wslim_redis'])) {
            foreach ($servres as $v) {
                if (!isset($v['port'])) $v['port'] = '6379';
                $save_paths[] = 'tcp://' . $v['host'] . ':' . $v['port'];
            }
        } elseif ($storageOptions['storage'] === 'memcache') {
            foreach ($servres as $v) {
                if (!isset($v['port'])) $v['port'] = '11211';
                $save_paths[] = $v['host'] . ':' . $v['port'];
            }
        } elseif ($storageOptions['storage'] === 'memcached') {
            $save_path = 'tcp://';
            foreach ($servres as $v) {
                if (!isset($v['port'])) $v['port'] = '11211';
                $save_paths[] = 'tcp://' . $v['host'] . ':' . $v['port'];
            }
        }
        
        return implode(';', $save_paths);
    }

    /**
     * get one option
     * @param  string $key
     * @return mixed
     */
    public function getOption($key)
    {
        return isset($this->options[$key]) ? $this->options[$key] : null ;
    }
    
    /**
     * set one option, please call it before start()
     * 
     * @param  string $key
     * @param  mixed  $value
     * @return static
     */
    public function setOption($key, $value)
    {
        if (is_array($value)) {
            $this->options[$key] = array_merge($this->options[$key], $value);
        } else {
            $this->options[$key] = $value;
        }
        
        return $this;
    }
    
    /**
     * get session handler
     * @return \Wslim\Session\HandlerInterface
     */
    public function getHandler()
    {
        return $this->handler;
    }
    
    /**
     * get session storage, return null when native.
     * @return \Wslim\Common\StorageInterface
     */
    public function getStorage()
    {
        if ($this->handler) {
            return $this->handler->getStorage();
        }
        
        return null;
    }
    
    /**
     * Return whether the session has started or not
     *
     * @return bool True if the session has started
     */
    public function isStarted()
    {
        return $this->started;
    }
    
    /**
     * Start the session
     * notice: for CacheHandler, need call $session->setCache($cache) before start()
     *         
     * @return bool True if the session is started
     */
    public function start()
    {
        if ($this->started) {
            return true;
        }
        
        // start the session
        if (session_status() == PHP_SESSION_NONE) {
            
            $this->init();
            
            // not native
            if ($this->handler) {
                $this->handler->register();
            }
            // 将 session_write_close() 函数注册为关闭会话的函数。
            //@session_register_shutdown();
            //register_shutdown_function('session_write_close');
            
            $res = session_start();
            
            // 将Collection的data绑定到 $_SESSION
            $this->data = & $_SESSION;
            $this->started = true;
            
            if (static::getStorage()) {
                $key = static::getSessionKey();
                $sdata = static::getStorage()->get($key);
                if ($this->data && !$sdata) {
                    foreach ($_SESSION as $k => $v) {
                        $_SESSION[$k] = $v;
                    }
                }
                \Wslim\Ioc::logger('session')->debug(sprintf('[%s]start: %s storage: %s', static::getId(), json_encode($this->data), json_encode($sdata)));
            }
        }
        
        return true;
    }
    
    /**
     * Saves and closes the session
     */
    public function close()
    {
        $this->started = false;
        
        \Wslim\Ioc::logger('session')->debug(sprintf('[%s]close', static::getId()));
    }
    
    /**
     * Regenerates the session
     *
     * @param bool $destroy True to destroy the current session
     * @param int $lifetime The lifetime of the session cookie in seconds
     * @return bool True if regenerated, false otherwise
     */
    public function regenerate($destroy = false, $lifetime = null)
    {
        if (!$this->isStarted()) {
            return false;
        }
        
        if (null !== $lifetime) {
            ini_set('session.cookie_lifetime', $lifetime);
        }
    
        \Wslim\Ioc::logger('session')->debug(sprintf('[%s]regenerate', static::getId()));
        
        return session_regenerate_id($destroy);
    }
    
    /**
     * Return the session ID
     *
     * @return string The session ID
     */
    public function getId()
    {
        $this->start();
        
        return session_id();
    }
    
    /**
     * Set the session ID
     *
     * @param string $sessionId The ID to set
     * @throws SessionException
     */
    public function setId($sessionId)
    {
        if ($this->isStarted()) {
            throw new SessionException('Session already started, can not change ID.');
        }
        
        session_id($sessionId);
    }
    
    public function getSessionKey()
    {
        $id = static::getId();
        return 'sess_' . $id;
    }
    
    /**
     * Return the session name
     *
     * @return string The session name
     */
    public function getName()
    {
        $this->start();
        
        return session_name();
    }
    
    /**
     * Set the session name
     *
     * @param string $name The session name
     */
    public function setName($name)
    {
        if ($this->isStarted()) {
            throw new SessionException('Session already started, can not change name.');
        }
        
        session_name($name);
    }
    
    /**
     * Read session information for the given name
     *
     * @param string $name The name of the item to read
     * @return mixed The value stored in $name of the session, or an empty string.
     */
    public function get($key, $default = null)
    {
        $this->start();
        
        $value = isset($_SESSION[$key]) ? $_SESSION[$key] : '';
        
        \Wslim\Ioc::logger('session')->debug(sprintf('[%s]get %s:%s', static::getId(), $key, print_r($value, true)));
        
        return $value;
    }
    
    /**
     * Writes the given session information to the given name
     *
     * @param string $name The name to write to
     * @param mixed  $value The value to write
     */
    public function set($key, $value=null)
    {
        $this->start();
        
        $_SESSION[$key] = $value;
        
        //session_write_close();
        
        $sdata = is_scalar($key) ? [$key => $value] : $key;
        \Wslim\Ioc::logger('session')->debug(sprintf('[%s]set %s:%s', static::getId(), $key, print_r($value, true)));
        
        return $this;
    }
    
    /**
     * Unsets the value of a given session variable, or the entire session of
     * all values
     *
     * @param string $name The name to unset
     */
    public function clear($key = null)
    {
        $this->start();
        
        if ($key) {
            unset($_SESSION[$key]);
        } else {
            $_SESSION = [];
            
            // error: session_destroy() warning Session object destruction failed
            //session_destroy();
        }
    }
    
    /**
     * check file path
     * @param string $filePath
     * @throws \RuntimeException
     * @return boolean
     */
    protected function checkFilePath($filePath)
    {
        if (!is_dir($filePath)) {
            mkdir($filePath, 0755, true);
        }
        
        if (!is_writable($filePath)) {
            throw new \RuntimeException(sprintf('Session path `%s` is not writable.', $filePath));
        }
        
        return true;
    }
    
    /**
     * get cookie
     * @param string $name
     * @return string
     */
    static public function getCookie($name=null)
    {
        $prefix = $this->options['group'] ? $this->options['group'] . '_' : 'WS_';
        if ($name) $name =  $prefix . $name;
        return HttpHelper::getCookie($name);
    }
    
    /**
     * set cookie
     * @param string  $name
     * @param string  $value
     * @param int     $expire
     * @param string  $path
     * @param string  $domain
     * @param boolean $secure
     * 
     * @return void
     */
    static public function setCookie($name, $value, $expire='3600', $path='', $domain='', $secure=false)
    {
        $prefix = $this->options['group'] ? $this->options['group'] . '_' : 'WS_';
        if ($name) $name =  $prefix . $name;
        HttpHelper::setCookie($name, $value, $expire, $path, $domain, $secure);
    }
    
}