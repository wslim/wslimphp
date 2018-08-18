<?php
namespace Wslim\Common\Storage;

/**
 * Class XcacheStorage
 *
 * @author 28136957@qq.com
 * @link   wslim.cn
 */
class XcacheStorage extends AbstractStorage
{
	/**
	 * Constructor.
	 *
	 * @param   mixed $options An options array, or an object that implements \ArrayAccess
	 *
	 * @throws \RuntimeException
	 */
	public function __construct($options = array())
	{
		if (!extension_loaded('xcache') || !is_callable('xcache_get')) {
			throw new \RuntimeException('XCache not supported.');
		}

		parent::__construct($options);
	}

	public function exists($key)
	{
	    $key = $this->formatKey($key);
		return xcache_isset($key);
	}

	public function get($key)
	{
		if ($this->exists($key)) {
		    $key = $this->formatKey($key);
			return xcache_get($key);
		}

		return null;
	}

	public function set($key, $value, $ttl = null)
	{
	    $key = $this->formatKey($key);
	    $value = $this->encodeValue($value);
	    $ttl = $ttl ? $ttl : $this->options['ttl'];
	    xcache_set($key, $value, $ttl);

	    return true;
	}

	public function remove($key)
	{
	    $key = $this->formatKey($key);
		xcache_unset($key);

		return true;
	}

	public function clear()
	{
		return true;
	}
}

