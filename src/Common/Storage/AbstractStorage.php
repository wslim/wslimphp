<?php
namespace Wslim\Common\Storage;

use Wslim\Common\StorageInterface;
use Wslim\Common\DataFormatterInterface;
use InvalidArgumentException;

/**
 * Class AbstractStorage, support ttl, you can set ttl=0 disable it and relize you self.
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
abstract class AbstractStorage implements StorageInterface
{
    /**
     * default options
     * @var array
     */
    static protected $defaultOptions = [
        'key_format'   => 'null',   // null, md5
        'data_format'  => 'null',   // null, string, json, serialize, csv, tsv, xml
        'path'         => 'storage',// storage=file 时必需
        'group'        => '',       // group, for file it is dir
        'ttl'          => 1440,     // 过期时长，单位s
        'root_element' => 'root',   // data_format=xml 时设置 roo_element 名称
        'entity_element'=> 'record',// data_format=xml 时设置 entity_element 名称
    ];
    
    /**
     * config options.
     * @var array
     */
    protected $options ;
    
    /**
     * Property formatter.
     *
     * @var  DataFormatterInterface
     */
    protected $formatter = null;
    
    /**
     * Property driver, The cache storage driver.
     *
     * @var  mixed
     */
    protected $driver = null;
    
    /**
     * need overwrite for redis or memcache(d)
     * {@inheritDoc}
     * @see \Wslim\Common\StorageInterface::connect()
     */
    public function connect()
    {
        return true;
    }
    
    /**
     * need overwrite for redis or memcache(d)
     * {@inheritDoc}
     * @see \Wslim\Common\StorageInterface::close()
     */
    public function close()
    {
        return true;
    }
    
    /**
     * getDriver, The cache storage driver
     *
     * @return  object
     */
    public function getDriver()
    {
        return $this->driver;
    }
    
    /**
     * setDriver, The cache storage driver
     *
     * @param   object $driver
     *
     * @return  static  Return self to support chaining.
     */
    public function setDriver($driver)
    {
        $this->driver = $driver;
        
        return $this;
    }
	
	/**
	 * Constructor.
	 *
	 * @param   array  $options  An options array, or an object that implements \ArrayAccess
	 * 
	 */
	public function __construct($options = null)
	{
	    if ($options) $this->setOption($options);
	    
	    if (static::$defaultOptions) foreach (static::$defaultOptions as $k => $v) {
	        if (!isset($this->options[$k])) $this->options[$k] = $v;
	    }
	    if (self::$defaultOptions) foreach (self::$defaultOptions as $k => $v) {
	        if (!isset($this->options[$k])) $this->options[$k] = $v;
	    }
	    
		$this->checkOptions();
	}
	
	public function getRaw($key)
	{
	    return static::get($key);
	}
    
	/**
	 * getItems
	 *
	 * @param   array $keys
	 *
	 * @return  array
	 */
	public function mget(array $keys)
	{
		$items = array();

		foreach ($keys as $key) {
		    $items[$key] = $this->get($key);
		}
		
		return $items;
	}

	/**
	 * set multi items
	 *
	 * @param   array $items
	 *
	 * @return  boolean
	 */
	public function mset(array $items, $ttl = null)
	{
		foreach ($items as $key => $item) {
			$this->set($key, $item, $ttl);
		}

		return true;
	}

	/**
	 * remove multi keys
	 * @param array $keys
	 * @return boolean
	 */
	public function mremove(array $keys)
	{
		foreach ($keys as $key) {
			$this->remove($key);
		}

		return true;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Wslim\Common\StorageInterface::append()
	 */
	public function append($key, $val, $ttl=null)
	{
	    $old = $this->get($key);
	    $val = $this->formatter->append($old, $val);
	    return $this->set($key, $val, $ttl);
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
	    $args = (array) $args;
	    
	    if (!is_callable($callable)) {
	        throw new \InvalidArgumentException('Not a valid callable.');
	    }
	    
	    if ($this->exists($key)) {
	        return $this->get($key);
	    }
	    
	    $value = call_user_func_array($callable, $args);
	    
	    $this->set($key, $value);
	    
	    return $value;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Wslim\Common\StorageInterface::clear()
	 */
	public function clear()
	{
	    return true;
	}
	
	/**
	 * need overwrite, and clear before you need check is expired
	 * {@inheritDoc}
	 * @see \Wslim\Common\StorageInterface::clearExpired()
	 */
	public function clearExpired()
	{
	    return true;
	}
	
	/**
	 * must overwrite, get keys by group, when key is 'a/b/c' group like 'a' or 'a/b'
	 * 
	 * @param  string $group
	 * @return array  keys belong of group
	 */
	protected function getKeysByGroup($group)
	{
	    return null;
	}
	
	/**
	 * set group option, it is different of withGroup, the latter return new instance
	 * @param string $key
	 * @return mixed
	 */
	public function setGroup($group)
	{
	    if ($this->options['group']) {
	        $group = str_replace('//', '/', $this->options['group'] . '/' . $group);
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
	 */
	public function setOption($key, $value=null)
	{
	    if (is_array($key)) {
	        foreach ($key as $k => $v) {
	            $this->setOption($k, $v);
	        }
	    } else {
	        $this->options[$key] = $value;
	    }
	}
	
	/**
	 * check options, extend class must call parent::checkOptions()
	 * @return void
	 */
	protected function checkOptions()
	{
	    $formatType = ucwords($this->options['data_format']);
	    $formatClass = '\\Wslim\\Common\\DataFormatter\\' . $formatType . 'Formatter';
	    if (!class_exists($formatClass)) {
	        throw new InvalidArgumentException('data_format => null|string|json|serialize|csv|tsv|xml');
	    }
	    if (!isset($this->options['ttl'])) $this->options['ttl'] = 0;
	    if (!$this->formatter) $this->formatter = new $formatClass;
	}
	
	/**
	 * format key
	 * 支持key使用二个目录，其余部分使用md5
	 * 
	 * @param  string $key
	 * @return string
	 */
	public function formatKey($key)
	{
	    $path = '';
	    $key = str_replace('\\', '/', $key);
	    
	    if ($pos = strpos($key, '/')) {
	        $parts = explode('/', $key);
	        if (count($parts) > 2) {
	            $path = array_shift($parts) . '/' . array_shift($parts) . '/';
	        } elseif (count($parts) > 1) {
	            $path = array_shift($parts) . '/';
	        }
	        $key = implode('_', $parts);
	    }
	    
	    if (isset($this->options['key_format']) && $this->options['key_format'] == 'md5') {
	        // 如果不符合key格式则进行md5
	        //strlen($key) < 30
	        if (!preg_match('/^[a-z0-9\-\_\:\.]+$/i', $key)) {
	            $key = md5($key);
	        }
	        
	        // 如果存储键未设置目录，则自动进行二级目录分布
	        if (!$path) {
	            $key = substr($key, 0, 2) . '/' . substr($key, 2, 2) . '/' . $key;
	        }
	    }
	    
	    // little case
	    $key = strtolower($key);
	    
	    return ($this->options['group'] ? rtrim($this->options['group'] , '/') . '/' : '') . $path . $key;
	}
	
	/**
	 * encode value
	 * @param  mixed $data
	 * @return mixed
	 */
	protected function encodeValue($data)
	{
	    // xml wrap entity_element label
	    if ($this->options['data_format'] == 'xml') {
	        $data = $this->formatter->wrap($data, $this->options['entity_element']);
	    }
	    return $this->formatter->encode($data);
	}
	
	/**
	 * decode value
	 * @param mixed $data
	 * @return mixed
	 */
	protected function decodeValue($data)
	{
	    return $this->formatter->decode($data);
	}

}

