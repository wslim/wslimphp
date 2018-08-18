<?php
namespace Wslim\Common\Storage;

/**
 * RedisStorage
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class RedisStorage extends AbstractStorage
{
	/**
	 * @var \Redis
	 */
	protected $driver;
	
	/**
	 * Property defaultOptions.
	 *
	 * @var  array
	 */
	static protected $defaultOptions  = [
	    'host'     => '127.0.0.1',
	    'port'     => 6379,
	    'pconnect' => 0
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
		if (!extension_loaded('redis') || !class_exists('\Redis')) {
			throw new \RuntimeException('Redis not supported.');
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

		$value = $this->decodeValue($value);
		return $value;
	}

	public function set($key, $value, $ttl = null)
	{
		$this->connect();
		
		$oldKey = $key;
		
        $key = $this->formatKey($key);
		$value = $this->encodeValue($value);
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

	public function flush()
	{
		$this->connect();

		return $this->driver->flushall();
	}

	public function mget(array $keys)
	{
		$this->connect();
		
		foreach ($keys as $i => $key) {
		    $keys[$i] = $this->formatKey($key);
		}
		
		$values = $this->driver->mget($keys);
		
		$items = array();
		
		foreach ($values as $index => $value) {	
		    $items[$keys[$index]] = $this->decodeValue($value);
		}
		
		return $items;
	}
	
	public function connect()
	{
		// We want to only create the driver once.
		if (isset($this->driver)) {
			return true;
		}
		
		$this->driver = new \Redis();
		
		if (isset($this->options['servers']) && $this->options['servers']) {
		    $servers = (array) $this->options['servers'];
		} elseif (isset($this->options['host'])) {
		    $servers[0] = $this->options;
		}
		
		$timeout = isset($this->options['timeout']) && $this->options['timeout'] ? intval($this->options['timeout']) : null;
		$host    = $servers[0]['host'];
		$port    = isset($servers[0]['port']) ? (int) $servers[0]['port'] : 6379;
		
		if (($host == 'localhost' || filter_var($host, FILTER_VALIDATE_IP)))
		{
		    $this->driver->connect($host, $port, $timeout);
		}
		else
		{
		    $this->driver->connect($host, null);
		}
        
		return true;
	}
}

