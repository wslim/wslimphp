<?php
namespace Wslim\Common\Storage;

use Wslim\Redis\Redis;

/**
 * RedisStorage, 不支持 key/value 的格式化，对key原样保存，对字串值一律以serialize保存
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class WslimRedisStorage extends AbstractStorage
{
	/**
	 * @var \Wslim\Redis\Redis
	 */
	protected $driver;
	
	/**
	 * Property defaultOptions.
	 *
	 * @var  array
	 */
	static protected $defaultOptions  = [
	    //'key_format'     => null;    // null, md5
	    //'data_format'    => 'json',  // null, string, json, serialize, csv, tsv, xml
	    'host'         => '127.0.0.1',
	    'port'         => 6379,
	    'database'     => 0,
	    'prefix'       => 'wslim:',
	]; 

	/**
	 * Class init.
	 * 
	 * @param   mixed      $options An options array, or an object that implements \ArrayAccess
	 *
	 * @throws \RuntimeException
	 */
	public function __construct($options = array())
	{
		if (!class_exists('\Wslim\Redis\Redis')) {
			throw new \RuntimeException('class \Wslim\Redis\Redis not supported.');
		}
		
		parent::__construct($options);
	}
	
	public function exists($key)
	{
		$this->connect();

		$key = $this->formatKey($key);
		
		return $this->driver->exists($key);
	}
	
	public function getRaw($key)
	{
	    if (!$this->connect()) {
	        return null;
	    }
	    
	    $key = $this->formatKey($key);
	    
	    $value = $this->driver->get($key);
	    
	    return $value;
	}

	public function get($key)
	{
	    $value = static::getRaw($key);
        
		//$value = $this->decodeValue($value);
		$value = unserialize($value);
		
		return $value;
	}

	public function set($key, $value, $ttl = null)
	{
		$this->connect();
		
        $key = $this->formatKey($key);
		
        //$value = $this->encodeValue($value);
        $value = serialize($value);
        
		if (!$this->driver->set($key, $value)) {
			return false;
		}
		
		$ttl = $ttl ? $ttl : $this->options['ttl'];
		
		if ($ttl) {
		    $this->driver->expire($key, $ttl);
		}
        
		return true;
	}
    
	public function remove($key)
	{
		$this->connect();

		$key = $this->formatKey($key);
		$this->driver->del($key);

		return true;
	}

	/**
	 * don't call this
	 * {@inheritDoc}
	 * @see \Wslim\Common\Storage\AbstractStorage::flush()
	 */
	public function flush()
	{
		$this->connect();

		return $this->driver->flushall();
	}
	
	public function connect()
	{
		// We want to only create the driver once.
		if (isset($this->driver)) {
			return true;
		}
		
		$this->driver = new Redis($this->options);
		
		return $this->driver->connectionStatus();
	}
	
	/**
	 * overwrite, format key
	 * @param string $key
	 * @return string
	 */
	public function formatKey($key)
	{
	    return ($this->options['group'] ? rtrim($this->options['group'] , '/') . '/' : '') . $key;
	}
}

