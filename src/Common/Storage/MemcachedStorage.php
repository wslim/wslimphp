<?php
namespace Wslim\Common\Storage;

/**
 * Class MemcachedStorage
 * It need a driver of \Memcached
 * 
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class MemcachedStorage extends AbstractStorage
{
    /**
     * Property defaultOptions.
     *
     * @var  array
     */
    static protected $defaultOptions  = [
        'host'     => '127.0.0.1',
        'port'     => 11211,
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
		if (!extension_loaded('memcached') || !class_exists('\Memcached'))
		{
			throw new \RuntimeException('Memcached not supported.');
		}

		parent::__construct($options);
	}

	public function exists($key)
	{
		$this->connect();

		$key = $this->formatKey($key);
		
		$this->driver->get($key);

		return ($this->driver->getResultCode() != \Memcached::RES_NOTFOUND);
	}
    
	public function get($key)
	{
		$this->connect();

		$key = $this->formatKey($key);
		
		$value = $this->driver->get($key);
		$code = $this->driver->getResultCode();

		if ($code === \Memcached::RES_SUCCESS) {
		    $value = $this->decodeValue($value);
			return $value;
		}

		return null;
	}

	/**
	 * {@inheritDoc}
	 * @see \Wslim\Common\StorageInterface::set()
	 */
	public function set($key, $value, $ttl = null)
	{
		$this->connect();
        
		$oldKey = $key;
		
		$key = $this->formatKey($key);
		$value = $this->encodeValue($value);
		$ttl = $ttl ? $ttl : $this->options['ttl'];

		$this->driver->set($key, $value, $ttl);
        
		return (bool) ($this->driver->getResultCode() == \Memcached::RES_SUCCESS);
	}

	/**
	 * {@inheritDoc}
	 * @see \Wslim\Common\StorageInterface::remove()
	 */
	public function remove($key)
	{
		$this->connect();

		$key = $this->formatKey($key);
		$this->driver->delete($key);

		$rc = $this->driver->getResultCode();

		if ( ($rc != \Memcached::RES_SUCCESS)) {
			return false;
		}

		return true;
	}

	/**
	 * This will wipe out the entire cache's keys
	 *
	 * @return boolean
	 */
	public function flush()
	{
		return $this->driver->connect()->flush();
	}
    
	public function connect()
	{
		// We want to only create the driver once.
		if ($this->driver)
		{
			return true;
		}
		
		if (!class_exists('Memcached')) {
		    return false;
		}
        
		$this->driver = new \Memcached;

		$this->driver->setOption(\Memcached::OPT_COMPRESSION, false);
		$this->driver->setOption(\Memcached::OPT_LIBKETAMA_COMPATIBLE, true);

		return true;
	}
}
