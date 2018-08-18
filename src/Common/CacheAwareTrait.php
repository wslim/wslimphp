<?php
namespace Wslim\Common;

use Wslim\Util\StringHelper;

/**
 * class has cache aware. main methods: getCache(), setCache(), cacheGet(), cacheSet(), cacheRemove(), cacheCall(), cacheClear()
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
trait CacheAwareTrait
{
    /**
     * options, must overwrite
     * notice: trait didn't define same property as class
     * @var array
     */
    //protected $options = [ 'cache'   => [...], 'other' => ... ];
    
    /**
     * cache instance
     * @var \Wslim\Common\Cache
     */
    protected $cache = null;
    
    /**
     * default options. 
     * if overwrite you need merge parent.
     * 
     * @var array
     */
    protected function defaultOptions()
    {
        return [
            'cache' => [
                'storage'       => 'file',  // null|file|memcache|memcached|redis|wslim_redis|xcache
                'path'          => 'caches',
                'key_format'    => '',      // md5
                'data_format'   => 'json',  // null, string, json, serialize, csv, tsv, xml
                'group'         => '',
                'ttl'           => 7200
            ]
        ];
    }
    
    public function getCacheKeyPrefix()
    {
        $prefix = '';
        if (isset($this->options['cache_key_prefix'])) {
            $prefix = $this->options['cache_key_prefix'];
        } elseif (isset($this->options['cache']['key_prefix'])) {
            $prefix = $this->options['cache']['key_prefix'];
        }
        
        return $prefix ? : get_class($this);
    }
    
    /**
     * get cache instance
     * @return \Wslim\Common\Cache
     */
    public function getCache()
    {
        if (!$this->cache) {
            $options = isset($this->options['cache']) ? $this->options['cache'] : [];
            foreach (static::defaultOptions()['cache'] as $k=>$v) {
                if (!isset($options[$k])) {
                    $options[$k] = $v;
                }
            }
            if (!file_exists($options['path'])) {
                $options['path'] = Config::getStoragePath() . trim($options['path'], '\\/');
            }
            
            $this->cache = new Cache($options); 
        }
        
        return $this->cache;
    }
    
    /**
     * set cache instance
     * @param \Wslim\Common\Cache $cache
     * @return static
     */
    public function setCache($cache)
    {
        $this->cache = $cache;
        
        return $this;
    }
    
    /**
     * 获取缓存中的值，相当于 $this->getCache()->get()
     * @param  string $key
     * @param  string $cache_mode
     * @return mixed array|NULL
     */
    public function cacheGet($key, $cache_mode = Cache::CACHE_AUTO)
    {
        if ($cache_mode === Cache::CACHE_FLUSH_ONLY ) {
            $this->getCache()->remove($key);
            return null;
        }
        
        return $this->getCache()->get($key);
    }
    
    /**
     * 设置缓存值，相当于 $this->getCache()->set()
     * @param  string $key
     * @param  mixed  $result
     * 
     * @return static
     */
    public function cacheSet($key, $result)
    {
        $this->getCache()->set($key, $result);
        
        return $this;
    }
    
    /**
     * 移除缓存项，支持同时删除一项或多项，相当于 $this->getCache()->mremove()
     * @param  array|string $keys
     * @return static
     */
    public function cacheRemove($keys)
    {
        $keys = (array) $keys;
        $this->getCache()->mremove($keys);
        
        return $this;
    }
    
    /**
     * clear cache by group, group is can function name
     * @param  string $group
     * @return static
     */
    public function cacheFlushByGroup($group)
    {
        $group = StringHelper::toUnderscoreVariable(get_called_class()) . '/' . StringHelper::toUnderscoreVariable($group);
        $this->getCache()->setGroup($group)->clear();
        
        return $this;
    }
    
    /**
     * clear all cache, warning，相当于 $this->getCache()->clear()
     * @return static
     */
    public function cacheClear()
    {
        $this->getCache()->clear();
        
        return $this;
    }
    
    /**
     * 设置缓存处理器, key 可以自动生成
     * @param string            $key
     * @param callable|string   $callable
     * @param array             $args
     * @param string            $cache_mode
     * 
     * @return mixed
     * 
     * debug_backtrace:
     * -- class method
        [0] => Array
        (
            [file] => E:\code\php\phpvendor\learn\debug_backtrace.php
            [line] => 6
            [function] => handle2
            [class] => Demo
            [object] => Demo Object
                (
                )

            [type] => ->
            [args] => Array
                (
                )

        )
        
        -- function
        [0] => Array
        (
            [file] => E:\code\php\phpvendor\learn\debug_backtrace.php
            [line] => 11
            [function] => b
            [args] => Array
                (
                )

        )
     */
    public function cacheCall($key=null, $callable=null, $args=null, $cache_mode = Cache::CACHE_AUTO)
    {
        $backtrace = debug_backtrace(null, 2);
        array_shift($backtrace);
        $caller = isset($backtrace[0]) ? $backtrace[0] : null;
        $function = $callable ?: (isset($caller['function']) ? $caller['function'] : null);
        
        // 先处理好args，后边要作为生成key的参数
        if (is_null($args)) {
            if (isset($caller['args'])) $args = $caller['args'];
            
            // 利用反射保证参数正确
            if ($function && is_string($function) && isset($caller['class'])) {
                $reflection = new \ReflectionMethod($caller['class'], $function);
                $originParams = $reflection->getParameters();
                $originArgs   = [];
                if ($originParams) foreach ($originParams as $v) {
                    if ($v->isOptional()) {
                        $originArgs[] = $v->getDefaultValue();
                    } else {
                        $originArgs[] = null;
                    }
                }
                if ($originArgs) foreach ($originArgs as $k => $v) {
                    if (!isset($args[$k])) {
                        $args[$k] = $originArgs[$k];
                    }
                }
            }
        } else {
            $args = (array) $args;
        }
        
        // cache mode
        if ($args && isset($args[count($args) - 1]) && Cache::isCacheEnum($args[count($args) - 1])) {
            $cache_mode = array_pop($args);
        }
        
        // 自动生成key，注意实际保存时存储对象会依据配置对此key进行再一次encode
        // $args 中数字与字串类型不同会影响生成key不同，因此调用函数前确保参数类型
        if (!$key) {
            // 处理args, 将字符串表示的 int 转化为 Int
            if ($args) foreach ($args as $k => $v) {
                if (is_numeric($v) && (strlen($v) <= 19) && preg_match('/^\d+$/', $v)) {
                    $args[$k] = intval($v);
                }
            }
            
            if (count($args) == 1 && is_scalar($args[0])) {
                $keystr = $args[0];
            } else {
                $keystr = md5(serialize($args));
            }
            
            $key = StringHelper::toUnderscoreVariable(static::getCacheKeyPrefix()) . '/';
            $key .= (is_string($function) ? StringHelper::toUnderscoreVariable($function) . '/' : '') . $keystr;
        }
        
        //$msg = __METHOD__' : ' . $cache_mode . ':' . $key . print_r($args, true) . PHP_EOL;
        
        // callable $function
        if (is_null($function)) {
            $function = [$this, $function];
        } elseif (!is_callable($function, false) && is_string($function)) {
            $function = [$this, $function];
        }
        
        // handle
        array_push($args, Cache::CACHE_NOT);
        if ($cache_mode === Cache::CACHE_NOT) {
            return call_user_func_array($function, $args);
        } elseif ($cache_mode === Cache::CACHE_FLUSH_ONLY ) {
            $this->getCache()->remove($key);
            return null;
        } else {
            if ($cache_mode === Cache::CACHE_FLUSH) {
                $this->getCache()->remove($key);
            }
            
            return $this->getCache()->call($key, $function, $args);
        }
    }
    
}