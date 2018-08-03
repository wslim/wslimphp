<?php
namespace Wslim\Common\Storage;

/**
 * Class MemcacheStorage
 * It need a driver, \Memcache $driver  The data storage driver.
 * see https://pecl.php.net/package/memcache/3.0.8/windows
 */
class MemcacheStorage extends AbstractStorage
{
    /**
     * Property defaultOptions.
     *
     * @var  array
     */
    static protected $defaultOptions  = [
        'servers'   => [
            //'host'    => '127.0.0.1',
            //'port'    => 11211,
        ],
        'host'      => '127.0.0.1',
        'port'     	=> 11211,
        'pconnect' 	=> 0
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
	    // !extension_loaded('memcache')
		if (!class_exists('\MemcachePool'))    
		{
			throw new \RuntimeException('Memcache not supported, ensure install pecl memcache-3.*.');
		}

		parent::__construct($options);
	}

	/**
	 * {@inheritDoc}
	 * @see \Wslim\Common\StorageInterface::exists()
	 */
	public function exists($key)
	{
		$this->connect();
		
		$key = $this->formatKey($key);
		
		return (bool) $this->driver->get($key);
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

	/**
	 * {@inheritDoc}
	 * @see \Wslim\Common\StorageInterface::get()
	 */
	public function get($key)
	{
	    $value = static::getRaw($key);
        
	    //\Wslim\Ioc::logger('cache')->debug(sprintf('get: %s %s', $key, print_r($this, true)));
	    
		$value = $this->decodeValue($value);
		
		return $value;
	}

	/**
	 * {@inheritDoc}
	 * @see \Wslim\Common\StorageInterface::set()
	 */
	public function set($key, $value, $ttl = null)
	{
	    if (!$this->connect()) {
	        return null;
	    }
	    //\Wslim\Ioc::logger('cache')->debug(sprintf('set: %s %s', $key, print_r($value, true)));
	    
		$oldKey = $key;
		
		$key = $this->formatKey($key);
		$value = $this->encodeValue($value);		
		$ttl = $ttl ? $ttl : $this->options['ttl'];

		$result = $this->driver->set($key, $value, MEMCACHE_COMPRESSED, $ttl);
		
		return $result;
	}

	/**
	 * {@inheritDoc}
	 * @see \Wslim\Common\StorageInterface::remove()
	 */
	public function remove($key)
	{
		$this->connect();
        
		$key = $this->formatKey($key);
		return $this->driver->delete($key);
	}

	/**
	 * This will wipe out the entire cache's keys
	 *
	 * @return boolean
	 */
	public function flush()
	{
		return $this->driver->flush();
	}
    
	public function connect()
	{
		// We want to only create the driver once.
		if ($this->driver) {
			return true;
		}
        
		$this->driver = new \MemcachePool;
		//$this->driver = new \Memcache;
		
		$res = false;
		if (isset($this->options['servers']) && $this->options['servers']) {
		    $servers = (array) $this->options['servers'];
		} elseif (isset($this->options['host'])) {
		    $servers[0] = $this->options;
		}
		
		$timeout = isset($this->options['timeout']) && $this->options['timeout'] ? intval($this->options['timeout']) : null;
		foreach ($servers as $v) {
		    $host = $v['host'] ? : 'localhost';
		    $port = isset($v['port']) ? (int) $v['port'] : 11211;
		    // params: host, port, persistent, weight, timeout
		    $res = $this->driver->addserver($host, $port, true, null, $timeout);
		}
		
		$this->driver->setFailureCallback(function () {
		    //throw new \ErrorException('memcache server failure.');
		});
		
		return $res;
	}
	
	public function close()
	{
	    if ($this->driver) {
	        $this->driver->close();
	    }
	    
	    return true;
	}
	
}
