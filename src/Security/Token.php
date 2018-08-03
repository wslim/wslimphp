<?php
namespace Wslim\Security;

use Wslim\Common\ErrorInfo;
use Wslim\Ioc;
use Wslim\Util\StringHelper;

/**
 * token实现之通用的token.
 * 
 * ```
 * $data = ['a'=>'aaa'];
 * $token = Token::instance('default')->get();
 * $vefiry = Token::instance('default')->verify($token, $data);
 * Token::instance()->reset($token);
 * ```
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class Token
{    
    /**
     * instances
     * @var Token[]
     */
    static protected $instances;
    
    /**
     * default options
     * @var array
     */
    static protected $defaultOptions = [
        'name'      => 'token',
        'title'     => 'token',
        'expire'    => 7200,
        'crypt'     => 'default',
        'delimiter' => '||',
        'cache'     => 1,
        'auto_refresh' => 0,
        'shared'    => 0,   // 共享token根据数据生成token，应用需确保数据唯一性且多个操作互不影响
    ];
    
    /**
     * options
     * @var array
     */
    protected $options = [];
    
    /**
     * return instance
     * @param  string $name
     * @return static
     */
    static public function instance($name=null)
    {
        $options = $name ? ['name' => $name] : [];
        $name = $name ?: get_called_class();
        
        if (!isset(static::$instances[$name])) {
            static::$instances[$name] = new static($name);
        }
        
        return static::$instances[$name];
    }
    
    /**
     * is token format, only english character
     * @param  string $value
     * @return boolean
     */
    static public function isToken($value)
    {
        $regex = '^[a-z0-9_\/\-\:\.]+$';
        $option = 'i';
        return (bool) preg_match('/' . $regex . '/' . $option, $value);
    }
    
    /**
     * construct
     */
    public function __construct($options=null)
    {
        if ($options ) {
            if (is_scalar($options)) {
                $options = ['name' => $options];
            }
            $this->options = array_merge($this->options, (array) $options);
        }
        
        $this->options = array_merge(static::$defaultOptions, $this->options);
        
        static::formatName();
    }
    
    /**
     * format name
     * @return void
     */
    protected function formatName()
    {
        $name = str_replace('\\', '/', trim($this->options['name']));
        
        if (strpos($name, '/')) {
            $names = explode('/', $name);
            $name = array_pop($names);
            if (!$name) {
                $name = array_pop($names);
            }
        }
        
        $this->options['name'] = StringHelper::toUnderscoreVariable($name);
    }
    
    /**
     * get expire, it is second number
     * @return int
     */
    public function getExpire()
    {
        return intval($this->options['expire']);
    }
    
    /**
     * set expire
     * @param  int    $expire
     * @return static
     */
    public function setExpire($expire)
    {
        $this->options['expire'] = intval($expire);
        return $this;
    }
    
    /**
     * get delimeter
     * @return string
     */
    public function getDelimiter()
    {
        return isset($this->options['delimiter']) ? trim($this->options['delimiter']) : '||';
    }
    
    /**
     * get title
     * @return string
     */
    public function getTitle()
    {
        return isset($this->options['title']) ? trim($this->options['title']) : (isset($this->options['name']) ? trim($this->options['name']) : 'token');
    }
    
    /**
     * set options
     * @param  array $options
     * @return static
     */
    public function setOptions(array $options)
    {
        if ($options) {
            foreach ($options as $k => $v) {
                $this->options[$k] = $v;
            }
        }
        
        static::formatName();
        
        return $this;
    }
    
    /**
     * format data, data is use to verify server(build) and client data
     * @param  mixed $data
     * @return array ksort array
     */
    protected function formatData($data=null)
    {
        if (!is_null($data)) {
            if (is_scalar($data)) {
                $data = ['id' => $data];
            }
        } else {
            $data = (array) $data;
        }
        
        foreach ($data as $k => $v) {
            if ($v == "" || is_bool($v)) {
                $data[$k] = intval($v);
            } elseif (is_numeric($v)) {
                $data[$k] = (strpos($v, '.') !== false) ? floatval($v) : intval($v);
            }
        }
        
        $data && ksort($data);
        
        return $data;
    }
    
    /**
     * get storage
     * 
     * @return \Wslim\Common\Cache|\Wslim\Session\Session
     */
    protected function getStorage()
    {
        if ($this->options['cache']) {
            // set ttl is 0, relize expired by other method
            return Ioc::cache($this->options['name'])->setOption('ttl', 0);
        } else {
            return Ioc::session();
        }
    }
    
    /**
     * build token, $data is server data, use to verify client data
     * @param  mixed  $data
     * @return string
     */
    public function get($data=null, $token=null)
    {
        if ($token) {
            $res = static::refresh($token, $data);
            if (isset($res['token'])) {
                return $res['token'];
            }
        }
        
        $data = static::formatData($data);
        
        if ($this->options['shared']) {
            $token = md5(serialize($data));
            $data['_token_'] = $token;
        } else {
            $token = Crypt::instance()->encrypt(uniqid());
        }
        $key = $token;
        
        $data['_expire_time_'] = time() + static::getExpire();
        
        static::getStorage()->set($key, $data);
        
        return $token;
    }
    
    /**
     * verify token, 第二个参数 $data 为客户端的数据用于和服务端比对
     * @param  string  $token
     * @param  mixed   $data
     * @return \Wslim\Common\ErrorInfo
     */
    public function verify($token, $data=null)
    {
        $data = static::formatData($data);
        
        $key = trim($token);
        
        // if shared, use same key based data
        if ($this->options['shared']) {
            $key = md5(serialize($data));
            $data['_token_'] = $token;
        }
        
        $sdata = static::getStorage()->get($key);
        
        if (!$sdata || !isset($sdata['_expire_time_'])) {
            return ErrorInfo::error(ErrorInfo::ERR_TOKEN_INVALID, $this->getTitle() . ' 已过期，请刷新重试[0]。');
        } else {
            if (time() > $sdata['_expire_time_'] ) {
                // if auto refresh, reset old token and return new token
                if ($this->options['auto_refresh']) {
                    
                    $this->reset($token, $data);
                    
                    $newtoken = static::get($data);
                    return ErrorInfo::success(['token'=>$newtoken, 'refresh_token'=>$token]);
                }
                
                return ErrorInfo::error(ErrorInfo::ERR_TOKEN_EXPIRED, $this->getTitle() . ' 已过期，请刷新重试[1]。');
            }
            
            if ($data) {
                foreach ($data as $k => $v) {
                    if (!isset($sdata[$k]) || $v != $sdata[$k]) {
                        $msg = sprintf($this->getTitle() . ' 不正确[%s]', $k);
                        return ErrorInfo::error(ErrorInfo::ERR_TOKEN_DATA, $msg);
                        break;
                    }
                }
            }
        }
        
        return ErrorInfo::success($this->options['name'] . ' 正确');
    }
    
    /**
     * reset, need call after handle business successfully.
     * @param  string $token
     * @param  mixed  $data
     * @return void
     */
    public function reset($token=null, $data=null)
    {
        if ($this->options['shared']) {
            $data = static::formatData($data);
            $key2 = md5(serialize($data));
            
            static::getStorage()->remove($key2);
        }
        
        if ($token) {
            $key = trim($token);
            
            static::getStorage()->remove($key);
        }
    }
    
    /**
     * refresh same token, use to refresh time and other data
     * 
     * @param  string $token
     * @param  mixed  $data
     * @return \Wslim\Common\ErrorInfo ['errcode'=>0, 'token'=>, 'refresh_token'=>'if not refresh, no this item']
     */
    public function refresh($token, $data=null)
    {
        $data = static::formatData($data);
        
        $key = trim($token);
        
        // if shared, use same key based data
        if ($this->options['shared']) {
            $key = md5(serialize($data));
            $data['_token_'] = $token;
        }
        
        $sdata = static::getStorage()->get($key);
        
        // if has old token and not expired then refresh, else return new
        if ($sdata && time() < $sdata['_expire_time_']) {
            return ErrorInfo::success($this->options['title'] . '不需要刷新', ['token'=>$newtoken]);
        }
        
        $this->reset($token, $data);
        
        $newtoken = static::get($data);
        
        return ErrorInfo::success(['token'=>$newtoken, 'refresh_token'=>$token]);
    }
    
    /**
     * refresh token rel data
     * @param  string $token
     * @param  mixed $data
     * @return void
     */
    public function refreshData($token, $data=null)
    {
        $key = $token;
        
        if ($key) {
            $data = static::formatData($data);
            
            $sdata = static::getStorage()->get($key);
            
            if ($sdata) {
                $data = array_merge($sdata, $data);
            }
            
            $data['_expire_time_'] = time() + static::getExpire();
            
            static::getStorage()->set($key, $data);
        }
    }
    
    /**
     * warning, clear all token cache, you should call clearExpired()
     * @return void
     */
    public function clear()
    {
        if ($this->options['cache']) {
            Ioc::cache($this->options['name'])->clear();;
        }
    }
    
    /**
     * clear expired token
     * 
     * @return \Wslim\Common\ErrorInfo
     */
    public function clearExpired()
    {
        if ($this->options['cache']) {
            $keys = Ioc::cache($this->options['name'])->clearExpired();
        }
        
        return ErrorInfo::success($this->options['name'] . ' clear success: ' . $count . ' items');
    }
    
}