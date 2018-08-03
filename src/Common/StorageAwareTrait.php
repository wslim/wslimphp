<?php
namespace Wslim\Common;

use InvalidArgumentException;
use Wslim\Util\StringHelper;

/**
 * Class has storage aware, instance owned a storage object.
 * Class can implements \ArrayAccess and StorageInterface
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
trait StorageAwareTrait
{    
    /**
     * storage instance
     *
     * @var  StorageInterface
     */
    protected $storage = null;
    
    /**
     * options
     * @var array
     */
    protected $options = null;
    
    /**
     * overwrite default options, call by __construct(). 
     * if overwrite you need merge parent.
     * 
     * @return array
     */
    protected function defaultOptions()
    {
        return [
            'storage'       => 'file',  // null|file|memcache|memcached|redis|wslim_redis|xcache
        ];
    }
    
    /**
     * instances array
     * @var static[]
     */
    static protected $instances;
    
    /**
     * Class init, options reference AbstraceStorage
     *
     * @param array $options
     */
    public function __construct($options=null)
    {
        if ($options) foreach ($options as $k => $v) {
            $this->options[$k] = $v;
        }
        
        if ($doptions = $this->defaultOptions()) foreach ($doptions as $k => $v) {
            if (!isset($this->options[$k])) {
                $this->options[$k] = $v;
            }
        }
    }
    
    /**
     * with specified group, return new object
     * @param  string $file
     * @return static
     */
    public function withGroup($group)
    {
        $group = str_replace('\\', '/', $group);
        if (!isset(static::$instances[$group])) {
            $instance = new static($this->options);
            $instance->setGroup($group);
            static::$instances[$group] = $instance;
        }
        return static::$instances[$group];
    }
    
    /**
     * set group option, it is different of withGroup, the latter return new instance
     * @param string $key
     * @return static
     */
    public function setGroup($group)
    {
        if (isset($this->options['group']) && $this->options['group']) {
            $group = $this->options['group'] . '/' . $group;
        }
        return $this->setOption('group', $group);
    }
    
    /**
     * get an option
     * @param string $key
     * @return mixed
     */
    public function getOption($key=null)
    {
        if ($key) {
            return isset($this->options[$key]) ? $this->options[$key] : null;
        } else {
            return $this->options;
        }
    }
    
    /**
     * set option
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function setOption($key, $value=null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->setOption($k, $v);
            }
        } else {
            $this->options[$key] = $value;
            
            if ($this->storage && in_array($key, StorageInterface::AllowOptions)) {
                $this->storage->setOption($key, $value);
            }
        }
        return $this;
    }
    
    /**
     * get real key
     * @param  mixed $key
     * @return string $realKey
     */
    public function formatKey($key)
    {
        return $this->getStorage()->formatKey($key);
    }
    
    /**
     * connect drive
     * @return boolean
     */
    public function connect()
    {
        return $this->getStorage()->connect();
    }
    
    /**
     * close drive
     * @return boolean
     */
    public function close()
    {
        return $this->getStorage()->close();
    }
    
    public function getRaw($key)
    {
        return $this->getStorage()->getRaw($key);
    }
    
    /**
     * fetch from the cache.
     *
     * @param   string  $key  The unique key
     *
     * @return  mixed   The cached value or null if not exists.
     *
     */
    public function get($key)
    {
        return $this->getStorage()->get($key);
    }
    
    /**
     * Persisting our data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string       $key   The key of the item to store
     * @param mixed        $value The value of the item to store
     * @param null|integer $ttl   Optional. The TTL value of this item. If no value is sent and the driver supports TTL
     *                          then the library may set a default value for it or let the driver take care of that.
     *
     * @return boolean
     */
    public function set($key, $value, $ttl = null)
    {   
        return $this->getStorage()->set($key, $value, $ttl);
    }
    
    /**
     * append value for key
     * @param string   $key
     * @param mixed    $value
     * @param null|int $ttl
     * 
     * @return boolean
     */
    public function append($key, $value, $ttl=null)
    {
        return $this->getStorage()->append($key, $value, $ttl);
    }
    
    /**
     * Remove an item from the cache by its unique key
     *
     * @param string $key The unique cache key of the item to remove
     *
     * @return boolean    The result of the delete operation
     */
    public function remove($key)
    {
        return $this->getStorage()->remove($key);
    }
    
    /**
     * warning: This will wipe out the entire cache's keys
     *
     * @return boolean The result of the empty operation
     */
    public function clear()
    {
        return $this->getStorage()->clear();
    }
    
    /**
     * clear expired items
     * @return boolean
     */
    public function clearExpired()
    {
        return $this->getStorage()->clearExpired();
    }
    
    /**
     * exists
     *
     * @param string $key
     *
     * @return  bool
     */
    public function exists($key)
    {
        return $this->getStorage()->exists($key);
    }
    
    /**
     * Obtain multiple DataItems by their unique keys
     *
     * @param array $keys A list of keys that can obtained in a single operation.
     *
     * @return array 
     */
    public function mget(array $keys)
    {
        return $this->getStorage()->mget($keys);
    }
    
    /**
     * Persisting a set of key => value pairs in the cache, with an optional TTL.
     *
     * @param array        $items An array of key => value pairs for a multiple-set operation.
     * @param null|integer $ttl   Optional. The TTL value of this item. If no value is sent and the driver supports TTL
     *                            then the library may set a default value for it or let the driver take care of that.
     *
     * @return static 
     */
    public function mset(array $items, $ttl = null)
    {
        $this->getStorage()->mset($items, $ttl);
        
        return $this;
    }
    
    /**
     * Remove multiple cache items in a single operation
     *
     * @param array $keys The array of keys to be removed
     *
     * @return static Return self to support chaining.
     */
    public function mremove(array $keys)
    {
        $this->getStorage()->mremove($keys);
        
        return $this;
    }
    
    /**
     * call
     *
     * @param string   $key
     * @param callable $callable
     * @param array    $args
     *
     * @throws \InvalidArgumentException
     * @return  mixed
     */
    public function call($key, $callable, $args = array())
    {
        return $this->getStorage()->call($key, $callable, $args);
    }
    
    /**
     * getStorage
     *
     * @return  \Wslim\Common\StorageInterface
     */
    public function getStorage()
    {
        if (!isset($this->storage)) {
            // 检测配置
            $storageType = StringHelper::toClassName($this->options['storage']);
            $storageClass = '\\Wslim\\Common\\Storage\\'. $storageType . 'Storage';
            if (!class_exists($storageClass)) {
                throw new InvalidArgumentException('storage = null|file|memcache|memcached|redis|xcache ,');
            }
            
            // 实例化 storage
            $partialOptions = [];
            foreach($this->options as $k => $v) {
                if (in_array($k, StorageInterface::AllowOptions)) {
                    $partialOptions[$k] = $v;
                    //unset($this->options[$k]);
                }
            }
            
            $this->storage = new $storageClass($partialOptions);
        }
        
        return $this->storage;
    }
    
    /**
     * setStorage
     *
     * @param   \Wslim\Common\StorageInterface $storage
     *
     * @return  static 
     */
    public function setStorage($storage)
    {
        $this->storage = $storage;
        
        return $this;
    }
    
    /**
     * Is a property exists or not.
     *
     * @param mixed $offset Offset key.
     *
     * @return  boolean
     */
    public function offsetExists($offset)
    {
        return $this->exists($offset);
    }
    
    /**
     * Get a property.
     *
     * @param mixed $offset Offset key.
     *
     * @throws  \InvalidArgumentException
     * @return  mixed The value to return.
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }
    
    /**
     * Set a value to property.
     *
     * @param mixed $offset Offset key.
     * @param mixed $value  The value to set.
     *
     * @throws  \InvalidArgumentException
     * @return  void
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }
    
    /**
     * Unset a property.
     *
     * @param mixed $offset Offset key to unset.
     *
     * @throws  \InvalidArgumentException
     * @return  void
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }
    
    
    
}